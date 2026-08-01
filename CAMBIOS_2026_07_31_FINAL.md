# GeoIncidencias — Resumen final de esta sesión (30-31/07/2026)

No pude ejecutar `docker compose up --build` en ningún momento (este entorno
de trabajo no tiene Docker ni red). Todo lo de abajo fue revisado a mano:
balance de llaves/paréntesis en cada PHP tocado, `node --check` en todo el
JS (externo e inline) del proyecto. Aun así, probalo antes de darlo por
definitivo — sobre todo el flujo completo aprobar → asignar → en proceso →
resuelta → verificar → cerrar/reabrir.

Las migraciones nuevas se aplican solas: el `Dockerfile` del backend ya
corre `php artisan migrate --force` cada vez que el contenedor arranca, así
que alcanza con `docker compose up -d --build backend` (o `restart backend`
si no cambiaste dependencias).

## Backend (Laravel)

**Roles y permisos**
- Aprobar ya no exige comentario; rechazar sí (mínimo 5 caracteres), y ese
  motivo queda visible para ciudadano/admin/supervisor en el historial.
- Nuevo: reabrir incidencia (`PUT /incidencias/{id}/reabrir`, admin/
  supervisor, motivo obligatorio) y solicitar revisión (`POST /incidencias/
  {id}/solicitar-revision`, ciudadano dueño de la incidencia).
- Ciudadano solo puede editar su incidencia mientras está pendiente de
  validación.
- Técnico ve solo sus incidencias asignadas (antes veía las de toda su
  institución) y ya puede subir evidencia/fotos (antes un bug se lo
  bloqueaba).
- Comentarios/seguimiento restringidos a creador, asignado o admin/
  supervisor (antes cualquier usuario autenticado veía y comentaba
  cualquier incidencia).
- Supervisor ya puede asignar técnicos (antes solo admin).

**Flujo y estados**
- Estados agregados (aditivos, sin tocar los existentes): `Asignada`,
  `En verificación`, `Reabierta`.
- Flujo real: Registrada → (al asignar técnico) Asignada → En proceso →
  Resuelta → En verificación (opcional) → Cerrada. Reabierta vuelve a
  En proceso.
- **Bug corregido:** la tabla `incidencia_aprobaciones_historial` tenía la
  columna `accion` como ENUM limitado a 3 valores; reabrir insertaba
  `'reabierta'` y MySQL lo rechazaba (SQLSTATE 1265). Ya se amplió el ENUM.
  De paso se amplió `historial_actividad.detalle` (era VARCHAR 255, los
  motivos largos podían truncarse).

**Reportes y dashboard**
- Porcentajes en resumen, por-categoría, por-estado, por-responsable;
  nuevos endpoints por-zona y por-anual.
- Dashboard con conteo y porcentaje de rechazadas/reabiertas; cacheado 30s
  con invalidación automática.

**Cuentas de prueba**
- `tecnico@geoincidencias.com` / `123456` — crear con
  `php artisan tecnico:crear-demo` o `POST /admin/usuarios/crear-tecnico-demo`
  (botón "🔧 Técnico de prueba" en Usuarios).

## Frontend

**Rediseño visual (no solo colores)**
- Paleta violeta Material Design 3 sobre fondo claro, tipografía Plus
  Jakarta Sans + Inter.
- **Sidebar propio por rol**: cada rol arma su propio árbol de navegación
  agrupado (no el mismo menú ocultando enlaces) con su color de acento y
  una etiqueta de identidad ("App de campo — Técnico", "Centro de
  supervisión", etc.).
- **Dashboard con jerarquía visual**: resumen principal grande + acciones
  rápidas al costado, tira secundaria de métricas, mapa + actividad
  reciente en feed (ya no tabla), categorías abajo.
- **Panel admin**: tira de KPIs ejecutivos arriba (incidencias totales, por
  aprobar, tasa de resolución, usuarios activos).
- **Panel supervisor**: "centro de monitoreo" con mini-mapa en vivo + 3
  indicadores clave, antes de los filtros.
- **Panel institucional/técnico**: tabla reemplazada por tarjetas verticales
  estilo app de trabajo de campo, con badge de estado visible y mensaje
  explicando por qué no hay más transiciones cuando corresponde.
- **Diálogos nativos del navegador eliminados**: aprobar, reabrir, cambiar
  estado, desactivar usuario y crear cuentas demo usaban `confirm()`/
  `prompt()` nativos (imposibles de estilizar); ahora son modales propios.
- Toasts corregidos (tenían colores oscuros fijos, no heredaban el tema).
- **Mapa con clustering** de marcadores + leyenda de colores por estado
  (Leaflet.markercluster, carga perezosa).
- **Filtros rápidos visibles** (chips) en Incidencias (por estado) y
  Usuarios (por rol), sin necesidad de abrir el desplegable.
- Estados completos con color e icono propio (los 8 del flujo).

## Simplificaciones conscientes

- No hay un paso de "aceptar asignación" separado de "cambiar a en
  proceso": pasar de Asignada a En proceso cumple ambas cosas. Separarlo
  habría significado un campo de datos nuevo (aceptado/no aceptado) que no
  alcancé a justificar dado el resto del alcance.
- Formularios de login/registro/crear incidencia: heredan el tema nuevo
  pero no se reestructuró su distribución interna a fondo.
- Todo lo anterior de entregas previas se mantiene: Docker, docker-compose,
  autenticación, rutas base y funcionalidades existentes no se tocaron.

## Actualización — Base de datos local (ya no depende de Aiven)

El proyecto apuntaba a una base de datos MySQL en Aiven cuyos créditos se
agotaron, dejando el sistema sin acceso. Se reemplazó por un contenedor
MySQL local dentro del propio `docker-compose.yml`:

- Nuevo servicio `db` (mysql:8.0), con datos persistentes en el volumen
  `db_data` (no se pierden al reiniciar el contenedor, solo si borrás el
  volumen a propósito).
- `backend` ahora apunta a `db:3306` en vez del host de Aiven.
- Al arrancar, el backend corre migraciones y, si el sistema no tiene
  ningún administrador todavía, crea automáticamente:
  - Correo: `admin@geoincidencias.com`
  - Contraseña: `123456`
  (no toca nada si ya existe un admin, para no resetear contraseñas reales).

Para levantar todo desde cero:
```
docker compose up -d --build
```
La primera vez tarda un poco más porque MySQL crea la base vacía y corre
todas las migraciones. Después de eso, entrás directo con el admin de
arriba y desde Usuarios creás supervisor/técnico de prueba con sus botones.
