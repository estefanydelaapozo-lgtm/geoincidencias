<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CambiarEstadoIncidenciaRequest;
use App\Http\Requests\StoreIncidenciaRequest;
use App\Http\Requests\UpdateIncidenciaRequest;
use App\Models\Estado;
use App\Models\HistorialActividad;
use App\Models\Incidencia;
use App\Models\IncidenciaAsignacion;
use App\Models\IncidenciaEstadoHistorial;
use App\Models\IncidenciaAprobacionHistorial;
use App\Models\IncidenciaComentario;
use App\Models\Notificacion;
use App\Models\Usuario;
use App\Services\AprobacionAutomaticaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class IncidenciasController extends Controller
{
    private const TRANSICIONES = [
        'Registrada' => ['En proceso'],
        'Asignada' => ['En proceso'],
        'En proceso' => ['Resuelta'],
        'Resuelta' => ['En verificación', 'Cerrada'],
        'En verificación' => ['Cerrada'],
        'Cerrada' => [],
        // Reabrir (aprobado/rechazado por separado vía reabrir()) permite retomar el trabajo.
        'Reabierta' => ['En proceso'],
    ];

    private function queryConRelaciones()
    {
        return Incidencia::query()->with([
            'tipo:id_tipo,nombre,id_rol_responsable',
            'tipo.rolResponsable:id_rol,slug,nombre,color,icono',
            'subtipo:id_subtipo,nombre',
            'estado:id_estado,nombre,color,orden',
            'zona:id_zona,nombre,id_ciudad',
            'zona.ciudad:id_ciudad,nombre,id_provincia',
            'zona.ciudad.provincia:id_provincia,nombre,id_pais',
            'zona.ciudad.provincia.pais:id_pais,nombre',
            'creador:id_usuario,nombre,apellido',
            'asignaciones.usuario:id_usuario,nombre,apellido,correo,rol',
            'adminRevisor:id_usuario,nombre,apellido',
            'historialAprobaciones' => fn($q) => $q->orderBy('fecha'),
            'historialAprobaciones.usuario:id_usuario,nombre,apellido,rol',
        ]);
    }

    private function transformar(Incidencia $i): array
    {
        $tieneCoordenadas = $i->latitud !== null && $i->longitud !== null;
        $usuarioActual = request()->user();
        $puedeGestionar = $usuarioActual && in_array($usuarioActual->rol, ['admin', 'supervisor'], true);
        $dentroDePlazo = $i->fecha_limite_accion && now()->lessThan($i->fecha_limite_accion);
        $pendiente = $i->estado_aprobacion === 'pendiente_revision';

        return [
            'id_incidencia'=>$i->id_incidencia, 'titulo'=>$i->titulo, 'descripcion'=>$i->descripcion,
            'prioridad'=>$i->prioridad, 'tipo'=>$i->tipo?->nombre, 'subtipo'=>$i->subtipo?->nombre,
            'rol_responsable'=>$i->tipo?->rolResponsable?->slug, 'institucion_responsable'=>$i->tipo?->rolResponsable?->nombre,
            'estado'=>$i->estado?->nombre, 'color_estado'=>$i->estado?->color,
            'siguientes_estados'=>self::TRANSICIONES[$i->estado?->nombre] ?? [],
            'estado_aprobacion'=>$i->estado_aprobacion, 'zona'=>$i->zona?->nombre,
            'ciudad'=>$i->zona?->ciudad?->nombre, 'provincia'=>$i->zona?->ciudad?->provincia?->nombre,
            'pais'=>$i->zona?->ciudad?->provincia?->pais?->nombre,
            // ── Ubicación exacta de la incidencia ──
            'latitud'=>$i->latitud, 'longitud'=>$i->longitud, 'direccion_texto'=>$i->direccion_texto,
            'tiene_coordenadas'=>$tieneCoordenadas,
            'google_maps_url'=>$tieneCoordenadas ? "https://www.google.com/maps/search/?api=1&query={$i->latitud},{$i->longitud}" : null,
            'como_llegar_url'=>$tieneCoordenadas ? "https://www.google.com/maps/dir/?api=1&destination={$i->latitud},{$i->longitud}&travelmode=driving" : null,
            'foto_url'=>$i->foto ? "/api/incidencias/{$i->id_incidencia}/foto" : null,
            'fecha_ocurrencia'=>$i->fecha_ocurrencia?->format('Y-m-d'), 'hora_ocurrencia'=>$i->hora_ocurrencia,
            'fecha_resolucion'=>$i->fecha_resolucion, 'tiempo_resolucion_horas'=>$i->tiempo_resolucion_horas,
            'fecha_registro'=>$i->fecha_registro, 'fecha_actualizacion'=>$i->fecha_actualizacion,
            'reportante_nombre'=>$i->reportante_nombre, 'reportante_contacto'=>$i->reportante_contacto,
            'creado_por'=>$i->creador?->nombre_completo, 'id_tipo'=>$i->id_tipo, 'id_subtipo'=>$i->id_subtipo,
            'id_estado_actual'=>$i->id_estado_actual, 'id_zona'=>$i->id_zona,
            // ── Aprobación / rechazo (manual o automático) ──
            'motivo_rechazo'=>$i->motivo_rechazo, 'motivo_aprobacion'=>$i->motivo_aprobacion,
            'fecha_aprobacion'=>$i->fecha_aprobacion, 'fecha_rechazo'=>$i->fecha_rechazo,
            'fecha_revision'=>$i->fecha_revision, 'aprobacion_automatica'=>(bool)$i->aprobacion_automatica,
            'revisado_por'=>$i->adminRevisor?->nombre_completo,
            'fecha_limite_accion'=>$i->fecha_limite_accion,
            'ventana_accion_activa'=>$pendiente && $dentroDePlazo,
            'puede_aprobar'=>$puedeGestionar && $pendiente,
            'puede_rechazar'=>$puedeGestionar && $pendiente && $dentroDePlazo,
            'puede_eliminar'=>$puedeGestionar && $pendiente && $dentroDePlazo,
            'historial_aprobacion'=>$i->historialAprobaciones->map(fn($h)=>[
                'accion'=>$h->accion, 'motivo'=>$h->motivo,
                'usuario'=>$h->usuario?->nombre_completo ?? 'Sistema (automático)',
                'fecha'=>optional($h->fecha)->format('Y-m-d H:i:s'),
            ])->values(),
            'asignados'=>$i->asignaciones->map(fn($a)=>[
                'id_usuario'=>$a->id_usuario, 'nombre'=>$a->usuario?->nombre_completo,
                'rol'=>$a->usuario?->rol, 'rol_asignacion'=>$a->rol_asignacion,
            ])->values(),
        ];
    }

    public function index(Request $request)
    {
        AprobacionAutomaticaService::procesarConLimite();
        $query = $this->queryConRelaciones();
        $usuarioActual=$request->user()->loadMissing('rolDetalle');
        if ($usuarioActual->rolDetalle?->es_institucional && !in_array($usuarioActual->rol,['admin','supervisor'],true)) {
            if ($usuarioActual->rol === 'tecnico') {
                // El técnico solo ve las incidencias que le fueron asignadas a él, no las de toda su institución.
                $query->whereHas('asignaciones', fn($q)=>$q->where('id_usuario',$usuarioActual->id_usuario));
            } else {
                $query->whereHas('tipo', fn($q)=>$q->where('id_rol_responsable',$usuarioActual->id_rol));
            }
        }
        if (!($request->boolean('todas') && in_array($request->user()->rol, ['admin','supervisor'], true))) {
            $query->where('estado_aprobacion', 'aprobada');
        }
        if ($buscar = trim((string)$request->query('buscar'))) {
            $query->where(fn($q)=>$q->where('titulo','like',"%{$buscar}%")->orWhere('descripcion','like',"%{$buscar}%"));
        }
        if ($tipo=$request->query('tipo')) $query->where('id_tipo',$tipo);
        if ($subtipo=$request->query('subtipo')) $query->where('id_subtipo',$subtipo);
        if ($estado=$request->query('estado')) $query->whereHas('estado',fn($q)=>$q->where('nombre',$estado));
        if ($prioridad=$request->query('prioridad')) $query->where('prioridad',$prioridad);
        if ($zona=$request->query('zona')) $query->whereHas('zona',fn($q)=>$q->where('nombre',$zona));
        if ($estadoAprobacion=$request->query('estado_aprobacion')) $query->where('estado_aprobacion',$estadoAprobacion);
        if ($desde=$request->query('desde')) $query->whereDate('fecha_ocurrencia','>=',$desde);
        if ($hasta=$request->query('hasta')) $query->whereDate('fecha_ocurrencia','<=',$hasta);

        $porPagina=max(1,min((int)$request->query('por_pagina',10),100));
        $pagina=max(1,(int)$request->query('pagina',1));
        $cacheKey='incidencias.total.'.md5(json_encode($request->except(['pagina'])));
        $total=Cache::remember($cacheKey, now()->addSeconds(60), fn()=>(clone $query)->toBase()->getCountForPagination());
        $datos=$query->latest('fecha_registro')->forPage($pagina,$porPagina)->get()->map(fn($i)=>$this->transformar($i));
        return response()->json(compact('datos','total','pagina')+['data'=>$datos,'por_pagina'=>$porPagina]);
    }

    public function mapa(Request $request)
    {
        AprobacionAutomaticaService::procesarConLimite();
        $usuarioActual = $request->user()->loadMissing('rolDetalle');
        $esInstitucionalNoAdmin = $usuarioActual->rolDetalle?->es_institucional
            && !in_array($usuarioActual->rol, ['admin', 'supervisor'], true);
        $claveCache = $esInstitucionalNoAdmin
            ? "incidencias.mapa.{$usuarioActual->rol}.{$usuarioActual->id_usuario}"
            : 'incidencias.mapa';

        return Cache::remember($claveCache, now()->addSeconds(60), function () use ($usuarioActual, $esInstitucionalNoAdmin) {
            $query = $this->queryConRelaciones()->where('estado_aprobacion', 'aprobada')
                ->whereNotNull('latitud')->whereNotNull('longitud');
            if ($esInstitucionalNoAdmin) {
                if ($usuarioActual->rol === 'tecnico') {
                    $query->whereHas('asignaciones', fn($q) => $q->where('id_usuario', $usuarioActual->id_usuario));
                } else {
                    $query->whereHas('tipo', fn($q) => $q->where('id_rol_responsable', $usuarioActual->id_rol));
                }
            }
            return $query->get()->map(fn($i) => $this->transformar($i));
        });
    }

    public function pendientesAprobacion()
    {
        AprobacionAutomaticaService::procesarConLimite();
        return response()->json($this->queryConRelaciones()->where('estado_aprobacion','pendiente_revision')
            ->oldest('fecha_registro')->get()->map(fn($i)=>$this->transformar($i)));
    }

    public function show(int $id)
    {
        AprobacionAutomaticaService::procesarConLimite();
        $inc=$this->queryConRelaciones()->find($id);
        return $inc ? response()->json($this->transformar($inc)) : response()->json(['ok'=>false,'mensaje'=>'Incidencia no encontrada'],404);
    }

    public function store(StoreIncidenciaRequest $request)
    {
        $usuario=$request->user();
        $estado=Estado::firstOrCreate(
            ['nombre' => 'Registrada'],
            ['descripcion' => 'Incidencia reportada, aun no atendida', 'color' => '#ef4444', 'orden' => 1, 'activo' => 1]
        );
        $incidencia=DB::transaction(function () use ($request,$usuario,$estado) {
            $data=$request->validated();
            $data['titulo']=preg_replace('/\s+/',' ',trim($data['titulo']));
            $data['id_estado_actual']=$estado->id_estado;
            $data['estado_aprobacion']='pendiente_revision';
            $data['id_usuario_creador']=$usuario->id_usuario;
            $data['fecha_limite_accion']=now()->addHours(24);
            $inc=Incidencia::create($data);
            IncidenciaEstadoHistorial::create([
                'id_incidencia'=>$inc->id_incidencia,'id_estado_anterior'=>null,'id_estado_nuevo'=>$estado->id_estado,
                'id_usuario'=>$usuario->id_usuario,'comentario'=>'Incidencia registrada y pendiente de aprobación.',
            ]);
            return $inc;
        });
        $incidencia->load('tipo.rolResponsable');
        $rolResponsable=$incidencia->tipo?->rolResponsable;
        $destinatarios=Usuario::where('activo',1)
            ->where(function($q) use($rolResponsable){
                $q->where('rol','admin');
                if($rolResponsable) $q->orWhere('id_rol',$rolResponsable->id_rol);
            })->get(['id_usuario','rol','id_rol']);
        foreach($destinatarios as $destinatario){
            $esAdmin=$destinatario->rol==='admin';
            Notificacion::create([
                'id_usuario'=>$destinatario->id_usuario,'id_incidencia'=>$incidencia->id_incidencia,
                'titulo'=>$esAdmin?'Nueva incidencia por revisar':'Nueva incidencia asignada a tu institución',
                'mensaje'=>$esAdmin?"{$incidencia->titulo} necesita aprobación.":"{$incidencia->titulo} corresponde a {$rolResponsable->nombre}.",
            ]);
            if(!$esAdmin){
                IncidenciaAsignacion::firstOrCreate(
                    ['id_incidencia'=>$incidencia->id_incidencia,'id_usuario'=>$destinatario->id_usuario],
                    ['rol_asignacion'=>'responsable']
                );
            }
        }
        $this->limpiarCache();
        return response()->json(['ok'=>true,'mensaje'=>'Incidencia registrada en estado Registrada.','id'=>$incidencia->id_incidencia],201);
    }

    public function update(UpdateIncidenciaRequest $request, int $id)
    {
        $inc=Incidencia::findOrFail($id);
        Gate::authorize('update',$inc);
        $inc->update($request->validated()+['fecha_actualizacion'=>now()]);
        HistorialActividad::registrar($request->user()->id_usuario,$id,'edito_incidencia',"Se editaron los datos de la incidencia #{$id}",$request->ip());
        $this->limpiarCache();
        return response()->json(['ok'=>true,'mensaje'=>'Incidencia actualizada.']);
    }

    public function cambiarEstado(CambiarEstadoIncidenciaRequest $request, int $id)
    {
        $inc=$this->queryConRelaciones()->findOrFail($id);
        Gate::authorize('changeState',$inc);
        abort_if($inc->estado_aprobacion !== 'aprobada',422,'La incidencia debe estar aprobada.');
        $nuevo=Estado::findOrFail($request->integer('id_estado'));
        $actual=$inc->estado?->nombre;
        abort_unless(in_array($nuevo->nombre,self::TRANSICIONES[$actual] ?? [],true),422,"Transición no permitida: {$actual} → {$nuevo->nombre}.");

        DB::transaction(function() use($inc,$nuevo,$request){
            $anterior=$inc->id_estado_actual;
            $campos=['id_estado_actual'=>$nuevo->id_estado,'fecha_actualizacion'=>now()];
            if ($nuevo->nombre==='Resuelta') {
                $campos['fecha_resolucion']=now();
                $campos['tiempo_resolucion_horas']=max(0,$inc->fecha_registro?->diffInMinutes(now())/60);
            }
            $inc->update($campos);
            IncidenciaEstadoHistorial::create(['id_incidencia'=>$inc->id_incidencia,'id_estado_anterior'=>$anterior,
                'id_estado_nuevo'=>$nuevo->id_estado,'id_usuario'=>$request->user()->id_usuario,'comentario'=>$request->string('comentario')]);
        });
        HistorialActividad::registrar($request->user()->id_usuario,$id,'cambio_estado',"Estado cambiado de {$actual} a {$nuevo->nombre}",$request->ip());
        $this->limpiarCache();
        return response()->json(['ok'=>true,'mensaje'=>"Estado actualizado a {$nuevo->nombre}."]);
    }

    public function asignarTecnico(Request $request, int $id)
    {
        $data=$request->validate(['id_usuario'=>['required','integer','exists:usuarios,id_usuario']]);
        $tecnico=Usuario::with('rolDetalle')->whereKey($data['id_usuario'])->where('activo',1)->firstOrFail();
        abort_unless($tecnico->rolDetalle?->es_institucional,422,'El usuario seleccionado no pertenece a un rol institucional.');

        // Reasignar reemplaza al responsable anterior, no lo acumula: solo debe
        // quedar UNA persona a cargo a la vez (el resto puede seguir de "apoyo").
        IncidenciaAsignacion::where('id_incidencia',$id)
            ->where('rol_asignacion','responsable')
            ->where('id_usuario','<>',$tecnico->id_usuario)
            ->delete();
        IncidenciaAsignacion::updateOrCreate(['id_incidencia'=>$id,'id_usuario'=>$tecnico->id_usuario],['rol_asignacion'=>'responsable']);

        // Si la incidencia recién fue registrada, pasa a "Asignada" para reflejar
        // que ya tiene un responsable esperando iniciar el trabajo.
        $inc = Incidencia::with('estado')->find($id);
        if ($inc && $inc->estado?->nombre === 'Registrada') {
            $estadoAsignada = Estado::firstOrCreate(['nombre'=>'Asignada'], ['descripcion'=>'Un técnico fue asignado y aún no inicia el trabajo','color'=>'#7C3AED','orden'=>2,'activo'=>1]);
            $anterior = $inc->id_estado_actual;
            $inc->update(['id_estado_actual'=>$estadoAsignada->id_estado,'fecha_actualizacion'=>now()]);
            IncidenciaEstadoHistorial::create([
                'id_incidencia'=>$inc->id_incidencia,'id_estado_anterior'=>$anterior,'id_estado_nuevo'=>$estadoAsignada->id_estado,
                'id_usuario'=>$request->user()->id_usuario,'comentario'=>"Asignada a {$tecnico->nombre_completo}.",
            ]);
            $this->limpiarCache();
        }
        return response()->json(['ok'=>true,'mensaje'=>'Responsable institucional asignado correctamente.']);
    }

    public function subirFoto(Request $request, int $id)
    {
        $inc = Incidencia::findOrFail($id);
        // Puede subir foto/evidencia: quien registró la incidencia (foto inicial),
        // administrador/supervisor, o el técnico asignado (evidencia de trabajo).
        $usuario = $request->user();
        $autorizado = $inc->id_usuario_creador === $usuario->id_usuario
            || Gate::forUser($usuario)->allows('changeState', $inc);
        abort_unless($autorizado, 403, 'No tienes permiso para subir evidencia de esta incidencia.');
        $request->validate(['foto' => ['required','image','mimes:jpg,jpeg,png','max:5120']]);
        if ($inc->foto) Storage::disk('local')->delete($inc->foto);
        $path = $request->file('foto')->store('incidencia-photos', 'local');
        $inc->update(['foto' => $path, 'fecha_actualizacion' => now()]);
        $this->limpiarCache();
        return response()->json(['ok' => true, 'mensaje' => 'Foto de la incidencia guardada.', 'foto_url' => "/api/incidencias/{$inc->id_incidencia}/foto"]);
    }

    public function foto(int $id)
    {
        $inc = Incidencia::findOrFail($id);
        abort_unless($inc->foto && Storage::disk('local')->exists($inc->foto), 404);
        return response()->file(Storage::disk('local')->path($inc->foto), [
            'Cache-Control' => 'private, max-age=3600',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function destroy(Request $request,int $id)
    {
        $i = Incidencia::findOrFail($id);
        Gate::authorize('delete', $i);
        HistorialActividad::registrar($request->user()->id_usuario,$id,'elimino_incidencia',"Se eliminó la incidencia #{$id} dentro de las primeras 24 horas.",$request->ip());
        $i->delete();
        $this->limpiarCache();
        return response()->json(['ok'=>true,'mensaje'=>'Incidencia eliminada.']);
    }

    public function aprobar(Request $request,int $id)
    {
        // Aprobar no exige comentario/motivo: el supervisor solo confirma.
        $d = $request->validate(['motivo'=>['nullable','string','max:500']]);
        $motivo = trim((string)($d['motivo'] ?? '')) ?: 'Aprobada sin observaciones.';
        $i = Incidencia::findOrFail($id);
        Gate::authorize('approve', $i);
        abort_if($i->estado_aprobacion !== 'pendiente_revision', 422, 'La incidencia ya fue revisada anteriormente.');
        DB::transaction(function () use ($i, $request, $motivo) {
            $i->update([
                'estado_aprobacion'=>'aprobada','id_admin_revisor'=>$request->user()->id_usuario,'fecha_revision'=>now(),
                'fecha_aprobacion'=>now(),'motivo_aprobacion'=>$motivo,'aprobacion_automatica'=>false,
            ]);
            IncidenciaAprobacionHistorial::create([
                'id_incidencia'=>$i->id_incidencia,'id_usuario'=>$request->user()->id_usuario,
                'accion'=>'aprobada','motivo'=>$motivo,
            ]);
        });
        HistorialActividad::registrar($request->user()->id_usuario,$id,'aprobo_incidencia',"Incidencia #{$id} aprobada.",$request->ip());
        $this->limpiarCache();
        return response()->json(['ok'=>true,'mensaje'=>'Incidencia aprobada.']);
    }

    public function reabrir(Request $request,int $id)
    {
        $d = $request->validate(['motivo'=>['required','string','min:5','max:500']]);
        $i = $this->queryConRelaciones()->findOrFail($id);
        Gate::authorize('reopen', $i);
        abort_unless(in_array($i->estado?->nombre, ['Cerrada','Resuelta','En verificación'], true), 422, 'Solo se pueden reabrir incidencias resueltas, en verificación o cerradas.');
        $estadoReabierta = Estado::firstOrCreate(['nombre'=>'Reabierta'], ['descripcion'=>'Incidencia reabierta por el supervisor','color'=>'#f97316','orden'=>6,'activo'=>1]);
        DB::transaction(function () use ($i, $estadoReabierta, $request, $d) {
            $anterior = $i->id_estado_actual;
            $i->update(['id_estado_actual'=>$estadoReabierta->id_estado,'fecha_actualizacion'=>now()]);
            IncidenciaEstadoHistorial::create([
                'id_incidencia'=>$i->id_incidencia,'id_estado_anterior'=>$anterior,'id_estado_nuevo'=>$estadoReabierta->id_estado,
                'id_usuario'=>$request->user()->id_usuario,'comentario'=>'Reabierta: '.$d['motivo'],
            ]);
            IncidenciaAprobacionHistorial::create([
                'id_incidencia'=>$i->id_incidencia,'id_usuario'=>$request->user()->id_usuario,
                'accion'=>'reabierta','motivo'=>$d['motivo'],
            ]);
        });
        HistorialActividad::registrar($request->user()->id_usuario,$id,'reabrio_incidencia',"Incidencia #{$id} reabierta: {$d['motivo']}",$request->ip());
        $this->limpiarCache();
        return response()->json(['ok'=>true,'mensaje'=>'Incidencia reabierta.']);
    }

    public function solicitarRevision(Request $request,int $id)
    {
        $d = $request->validate(['motivo'=>['required','string','min:5','max:500']]);
        $i = Incidencia::findOrFail($id);
        abort_unless($i->id_usuario_creador === $request->user()->id_usuario, 403, 'Solo quien reportó la incidencia puede solicitar una revisión.');
        abort_unless(in_array($i->estado?->nombre, ['Resuelta','Cerrada'], true), 422, 'Solo se puede solicitar revisión de incidencias resueltas o cerradas.');
        IncidenciaComentario::create([
            'id_incidencia'=>$id,'id_usuario'=>$request->user()->id_usuario,
            'comentario'=>'Solicitud de revisión del ciudadano: '.$d['motivo'],
        ]);
        $destinatarios = Usuario::where('activo',1)->whereIn('rol',['admin','supervisor'])->get(['id_usuario']);
        foreach ($destinatarios as $destinatario) {
            Notificacion::create([
                'id_usuario'=>$destinatario->id_usuario,'id_incidencia'=>$id,
                'titulo'=>'Solicitud de revisión','mensaje'=>"El ciudadano pidió revisar la incidencia #{$id}: {$d['motivo']}",
            ]);
        }
        HistorialActividad::registrar($request->user()->id_usuario,$id,'solicito_revision',"Se solicitó revisión de la incidencia #{$id}",$request->ip());
        return response()->json(['ok'=>true,'mensaje'=>'Se notificó al supervisor tu solicitud de revisión.']);
    }

    public function rechazar(Request $request,int $id)
    {
        $d = $request->validate(['motivo'=>['required','string','min:5','max:500']]);
        $i = Incidencia::findOrFail($id);
        Gate::authorize('reject', $i);
        abort_if($i->estado_aprobacion !== 'pendiente_revision', 422, 'La incidencia ya fue revisada anteriormente.');
        DB::transaction(function () use ($i, $request, $d) {
            $i->update([
                'estado_aprobacion'=>'rechazada','id_admin_revisor'=>$request->user()->id_usuario,'fecha_revision'=>now(),
                'fecha_rechazo'=>now(),'motivo_rechazo'=>$d['motivo'],
            ]);
            IncidenciaAprobacionHistorial::create([
                'id_incidencia'=>$i->id_incidencia,'id_usuario'=>$request->user()->id_usuario,
                'accion'=>'rechazada','motivo'=>$d['motivo'],
            ]);
        });
        HistorialActividad::registrar($request->user()->id_usuario,$id,'rechazo_incidencia',"Incidencia #{$id} rechazada: {$d['motivo']}",$request->ip());
        $this->limpiarCache();
        return response()->json(['ok'=>true,'mensaje'=>'Incidencia rechazada.']);
    }
    public function exportarCsv(){ return response()->json($this->queryConRelaciones()->where('estado_aprobacion','aprobada')->get()->map(fn($i)=>$this->transformar($i))); }

    private function limpiarCache(): void { Cache::forget('incidencias.mapa'); Cache::forget('dashboard.resumen'); }
}
