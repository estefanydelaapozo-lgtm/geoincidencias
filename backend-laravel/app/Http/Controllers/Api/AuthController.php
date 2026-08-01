<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HistorialActividad;
use App\Models\Usuario;
use App\Services\SecurityAuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    private const MAX_ATTEMPTS = 5;
    private const LOCK_MINUTES = 15;


    public function googleConfig()
    {
        return response()->json([
            'enabled' => filled(config('services.google.client_id')),
            'client_id' => config('services.google.client_id'),
        ]);
    }

    public function google(Request $request)
    {
        $request->validate(['credential' => ['required','string','max:5000']]);
        $clientId = (string) config('services.google.client_id');
        if ($clientId === '') {
            return response()->json(['ok' => false, 'mensaje' => 'Google OAuth no está configurado en el servidor.'], 503);
        }

        $google = Http::timeout(8)->acceptJson()->get('https://oauth2.googleapis.com/tokeninfo', [
            'id_token' => $request->credential,
        ]);
        if (! $google->successful()) {
            SecurityAuditService::record('google_login_invalid_token', $request);
            return response()->json(['ok' => false, 'mensaje' => 'No se pudo validar la cuenta de Google.'], 401);
        }

        $claims = $google->json();
        if (($claims['aud'] ?? null) !== $clientId || ($claims['email_verified'] ?? 'false') !== 'true') {
            SecurityAuditService::record('google_login_invalid_audience', $request, ['email' => $claims['email'] ?? null]);
            return response()->json(['ok' => false, 'mensaje' => 'La cuenta de Google no pertenece a esta aplicación.'], 401);
        }

        $correo = Str::lower((string) ($claims['email'] ?? ''));
        if ($correo === '') return response()->json(['ok' => false, 'mensaje' => 'Google no proporcionó un correo válido.'], 422);

        $usuario = Usuario::where('correo', $correo)->first();
        if ($usuario && ! $usuario->activo) {
            return response()->json(['ok' => false, 'mensaje' => 'La cuenta está desactivada.'], 403);
        }

        if (! $usuario) {
            $usuario = Usuario::create([
                'nombre' => $claims['given_name'] ?? $claims['name'] ?? 'Usuario',
                'apellido' => $claims['family_name'] ?? null,
                'correo' => $correo,
                'password' => Str::random(48),
                'rol' => 'usuario',
                'id_rol' => \App\Models\Rol::where('slug','usuario')->value('id_rol'),
                'google_id' => $claims['sub'] ?? null,
                'auth_provider' => 'google',
                'email_verified_at' => now(),
                'activo' => true,
            ]);
            HistorialActividad::registrar($usuario->id_usuario, null, 'registro_google', "Registro con Google: {$correo}", $request->ip());
        } else {
            $usuario->update([
                'google_id' => $usuario->google_id ?: ($claims['sub'] ?? null),
                'auth_provider' => $usuario->auth_provider === 'local' ? 'local+google' : $usuario->auth_provider,
                'email_verified_at' => $usuario->email_verified_at ?: now(),
            ]);
        }

        $usuario->load('rolDetalle');
        $usuario->tokens()->delete();
        $hours = $request->boolean('remember') ? 24 * 30 : 8;
        $token = $usuario->createToken('google_auth', ['*'], now()->addHours($hours))->plainTextToken;
        HistorialActividad::registrar($usuario->id_usuario, null, 'login_google', "Inicio con Google: {$correo}", $request->ip());

        return response()->json(['ok' => true, 'token' => $token, 'usuario' => $this->usuarioPayload($usuario)]);
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'correo' => 'required|email',
            'password' => 'required|string|max:255',
        ]);
        if ($validator->fails()) {
            return response()->json(['ok' => false, 'mensaje' => 'Correo y contraseña son obligatorios.'], 422);
        }

        $correo = Str::lower(trim((string) $request->correo));
        $key = 'login-attempts:'.sha1($request->ip().'|'.$correo);
        $attempts = (int) Cache::get($key, 0);
        if ($attempts >= self::MAX_ATTEMPTS) {
            SecurityAuditService::record('login_blocked', $request, ['correo' => $correo]);
            return response()->json([
                'ok' => false,
                'mensaje' => 'Demasiados intentos fallidos. Intenta nuevamente en 15 minutos.',
            ], 429)->header('Retry-After', (string) (self::LOCK_MINUTES * 60));
        }

        $usuario = Usuario::with('rolDetalle')->where('correo', $correo)->where('activo', 1)->first();
        if (! $usuario || ! Hash::check($request->password, $usuario->password)) {
            Cache::put($key, $attempts + 1, now()->addMinutes(self::LOCK_MINUTES));
            SecurityAuditService::record('login_failed', $request, [
                'correo' => $correo,
                'attempt' => $attempts + 1,
            ]);
            return response()->json([
                'ok' => false,
                'mensaje' => 'Credenciales incorrectas.',
                'intentos_restantes' => max(0, self::MAX_ATTEMPTS - ($attempts + 1)),
            ], 401);
        }

        Cache::forget($key);
        $usuario->tokens()->delete();
        $hours = $request->boolean('remember') ? 24 * 30 : 8;
        $token = $usuario->createToken('auth_token', ['*'], now()->addHours($hours))->plainTextToken;

        $datosUsuario = $this->usuarioPayload($usuario);
        HistorialActividad::registrar($usuario->id_usuario, null, 'login', "Usuario {$usuario->nombre} inició sesión", $request->ip());

        return response()->json(['ok' => true, 'token' => $token, 'usuario' => $datosUsuario]);
    }

    public function registro(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:100',
            'apellido' => 'required|string|max:100',
            'correo' => 'required|email|max:150|unique:usuarios,correo',
            'password' => ['required','string','min:8','max:72','regex:/[A-Z]/','regex:/[a-z]/','regex:/[0-9]/'],
            'telefono' => 'required|string|max:20',
        ], ['password.regex' => 'La contraseña debe incluir mayúscula, minúscula y número.']);
        if ($validator->fails()) {
            return response()->json(['ok' => false, 'mensaje' => $validator->errors()->first()], 422);
        }

        $usuario = Usuario::create([
            'nombre' => $request->nombre,
            'apellido' => $request->apellido,
            'correo' => Str::lower($request->correo),
            'password' => $request->password,
            'rol' => 'usuario',
            'id_rol' => \App\Models\Rol::where('slug','usuario')->value('id_rol'),
            'telefono' => $request->telefono,
        ]);
        HistorialActividad::registrar($usuario->id_usuario, null, 'registro_usuario', "Nuevo usuario registrado: {$usuario->nombre} ({$usuario->correo})", $request->ip());
        return response()->json(['ok' => true, 'mensaje' => 'Cuenta creada correctamente. Ya puedes iniciar sesión.'], 201);
    }

    public function perfil(Request $request)
    {
        return response()->json($this->usuarioPayload($request->user(), true));
    }

    public function subirFotoPerfil(Request $request)
    {
        $request->validate(['foto' => ['required','image','mimes:jpg,jpeg,png,webp','max:2048']]);
        $usuario = $request->user();
        if ($usuario->foto_perfil) Storage::disk('local')->delete($usuario->foto_perfil);
        $path = $request->file('foto')->store('profile-photos', 'local');
        $usuario->update(['foto_perfil' => $path]);
        return response()->json(['ok' => true, 'mensaje' => 'Foto actualizada.', 'foto_url' => "/api/auth/foto-perfil/{$usuario->id_usuario}"]);
    }

    public function fotoPerfil(int $id)
    {
        $usuario = Usuario::findOrFail($id);
        abort_unless($usuario->foto_perfil && Storage::disk('local')->exists($usuario->foto_perfil), 404);
        return response()->file(Storage::disk('local')->path($usuario->foto_perfil), [
            'Cache-Control' => 'private, max-age=3600',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()?->delete();
        return response()->json(['ok' => true, 'mensaje' => 'Sesión cerrada.']);
    }

    private function usuarioPayload(Usuario $usuario, bool $details = false): array
    {
        $data = [
            'id_usuario' => $usuario->id_usuario,
            'correo' => $usuario->correo,
            'nombre' => $usuario->nombre,
            'apellido' => $usuario->apellido,
            'rol' => $usuario->rol,
            'rol_detalle' => $usuario->rolDetalle,
            'es_institucional' => (bool) ($usuario->rolDetalle?->es_institucional),
            'telefono' => $usuario->telefono,
            'foto_url' => $usuario->foto_perfil ? "/api/auth/foto-perfil/{$usuario->id_usuario}" : null,
            'activo' => (bool) $usuario->activo,
        ];
        if ($details) {
            $data['saldo_incentivos'] = $usuario->saldo_incentivos;
            $data['created_at'] = $usuario->created_at;
        }
        return $data;
    }
}
