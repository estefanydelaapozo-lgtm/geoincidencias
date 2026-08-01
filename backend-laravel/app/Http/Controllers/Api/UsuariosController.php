<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HistorialActividad;
use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class UsuariosController extends Controller
{
    public function index(Request $request)
    {
        $q=Usuario::with('rolDetalle:id_rol,slug,nombre,color,icono,es_institucional')
            ->select('id_usuario','nombre','apellido','correo','rol','id_rol','telefono','saldo_incentivos','activo','created_at')
            ->orderBy('nombre');
        if($b=trim((string)$request->query('buscar',''))) $q->where(fn($x)=>$x->where('nombre','like',"%{$b}%")->orWhere('apellido','like',"%{$b}%")->orWhere('correo','like',"%{$b}%"));
        if($rol=$request->query('rol')) $q->where('rol',$rol);
        if($request->has('activo') && $request->query('activo')!=='') $q->where('activo',$request->boolean('activo'));
        return response()->json($q->get());
    }

    public function show(Request $request,int $id)
    {
        abort_unless($request->user()->rol==='admin' || (int)$request->user()->id_usuario===$id,403);
        return response()->json(Usuario::with('rolDetalle')->findOrFail($id)->makeHidden(['password']));
    }

    public function store(Request $request)
    {
        $data=$this->validar($request,null,true);
        $rol=Rol::where('slug',$data['rol'])->where('activo',1)->firstOrFail();
        $u=Usuario::create($data+['id_rol'=>$rol->id_rol,'password'=>Hash::make($data['password']),'activo'=>$request->boolean('activo',true)]);
        HistorialActividad::registrar($request->user()->id_usuario,null,'crear_usuario',"Creó {$u->correo} con rol {$rol->nombre}",$request->ip());
        return response()->json(['ok'=>true,'mensaje'=>'Usuario creado correctamente.','usuario'=>$u->load('rolDetalle')],201);
    }

    public function update(Request $request,int $id)
    {
        $u=Usuario::findOrFail($id); $actual=$request->user();
        $admin=$actual->rol==='admin'; abort_unless($admin || (int)$actual->id_usuario===$id,403);
        $data=$this->validar($request,$u,false,$admin);
        if($admin && isset($data['rol'])) { $rol=Rol::where('slug',$data['rol'])->where('activo',1)->firstOrFail(); $data['id_rol']=$rol->id_rol; }
        if(empty($data['password'])) unset($data['password']); else $data['password']=Hash::make($data['password']);
        if($admin) $data['activo']=$request->boolean('activo',$u->activo); else unset($data['rol']);
        $u->update($data);
        HistorialActividad::registrar($actual->id_usuario,null,'editar_usuario',"Actualizó {$u->correo}",$request->ip());
        return response()->json(['ok'=>true,'mensaje'=>'Usuario actualizado correctamente.','usuario'=>$u->load('rolDetalle')]);
    }

    public function destroy(Request $request,int $id)
    {
        $u=Usuario::findOrFail($id); abort_if((int)$u->id_usuario===(int)$request->user()->id_usuario,422,'No puedes desactivarte.');
        $u->update(['activo'=>false]); $u->tokens()->delete();
        return response()->json(['ok'=>true,'mensaje'=>'Usuario desactivado correctamente.']);
    }

    public function cambiarPassword(Request $request)
    {
        $d=$request->validate(['password_actual'=>'required|string','password_nuevo'=>['required','string','min:8','regex:/[A-Z]/','regex:/[a-z]/','regex:/[0-9]/']]);
        $u=$request->user(); abort_unless(Hash::check($d['password_actual'],$u->password),422,'La contraseña actual no es correcta.');
        $u->update(['password'=>Hash::make($d['password_nuevo'])]);
        return response()->json(['ok'=>true,'mensaje'=>'Contraseña actualizada correctamente.']);
    }

    // Crea o resetea la cuenta de Supervisor de prueba (correo y contraseña
    // fijos y conocidos), para no depender de la consola/PowerShell.
    public function crearSupervisorDemo(Request $request)
    {
        $rol = Rol::where('slug', 'supervisor')->where('activo', 1)->first();
        abort_if(!$rol, 422, 'No existe el rol "supervisor". Verifica que las migraciones se hayan ejecutado.');
        $correo = 'supervisor@geoincidencias.com';
        $u = Usuario::updateOrCreate(
            ['correo' => $correo],
            [
                'nombre' => 'Supervisor', 'apellido' => 'Demo',
                'password' => Hash::make('123456'),
                'rol' => 'supervisor', 'id_rol' => $rol->id_rol, 'activo' => true,
            ]
        );
        HistorialActividad::registrar($request->user()->id_usuario, null, 'crear_supervisor_demo', "Creó/reseteó la cuenta de supervisor de prueba ({$correo})", $request->ip());
        return response()->json([
            'ok' => true,
            'mensaje' => 'Cuenta de supervisor lista: supervisor@geoincidencias.com / 123456',
            'correo' => $correo, 'password' => '123456',
        ]);
    }

    // Crea o resetea la cuenta de Técnico de prueba (correo y contraseña
    // fijos y conocidos), para no depender de la consola/PowerShell.
    public function crearTecnicoDemo(Request $request)
    {
        $rol = Rol::where('slug', 'tecnico')->where('activo', 1)->first();
        abort_if(!$rol, 422, 'No existe el rol "tecnico". Verifica que las migraciones se hayan ejecutado.');
        $correo = 'tecnico@geoincidencias.com';
        $u = Usuario::updateOrCreate(
            ['correo' => $correo],
            [
                'nombre' => 'Técnico', 'apellido' => 'Demo',
                'password' => Hash::make('123456'),
                'rol' => 'tecnico', 'id_rol' => $rol->id_rol, 'activo' => true,
            ]
        );
        HistorialActividad::registrar($request->user()->id_usuario, null, 'crear_tecnico_demo', "Creó/reseteó la cuenta de técnico de prueba ({$correo})", $request->ip());
        return response()->json([
            'ok' => true,
            'mensaje' => 'Cuenta de técnico lista: tecnico@geoincidencias.com / 123456',
            'correo' => $correo, 'password' => '123456',
        ]);
    }

    private function validar(Request $request,?Usuario $u=null,bool $crear=false,bool $admin=true): array
    {
        $rules=[
            'nombre'=>['required','string','max:100'],'apellido'=>['nullable','string','max:100'],
            'correo'=>['required','email','max:150',Rule::unique('usuarios','correo')->ignore($u?->id_usuario,'id_usuario')],
            'telefono'=>['nullable','string','max:20'],'password'=>[$crear?'required':'nullable','string','min:8'],
        ];
        if($admin) $rules['rol']=['required',Rule::exists('roles','slug')->where('activo',1)];
        return Validator::make($request->all(),$rules)->validate();
    }
}
