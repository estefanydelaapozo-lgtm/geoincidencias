let usuariosCache=[], usuarioEditando=null;
const ROLES={admin:['👑','Administrador','#f59e0b'],usuario:['👤','Ciudadano','#64748b'],tecnico:['🔧','Técnico general','#06b6d4'],policia:['👮','Policía','#2563eb'],bomberos:['🚒','Bomberos','#dc2626'],salud:['🚑','Salud / Emergencias','#16a34a'],electrica:['⚡','Empresa Eléctrica','#eab308'],agua:['🚰','Agua Potable','#0ea5e9'],obras_publicas:['🛣️','Obras Públicas','#f97316'],medio_ambiente:['🌳','Medio Ambiente','#22c55e'],supervisor:['🧭','Supervisor','#8b5cf6']};
function limpiarFormularioUsuario(){usuarioEditando=null;formUsuario.reset();uActivo.checked=true;tituloFormUsuario.textContent='Crear usuario';textoBtnGuardarUsuario.textContent='Crear usuario';uPassword.placeholder='Mínimo 8 caracteres';btnCancelarEdicionUsuario.style.display='none'}
function estadoBadge(a){return `<span class="badge" style="color:${a?'#00e5a0':'#ff3b6b'}">${a?'Activo':'Inactivo'}</span>`}
function rolBadge(r){const x=ROLES[r]||['◉',r,'#64748b'];return `<span class="badge" style="color:${x[2]};border:1px solid ${x[2]}55;background:${x[2]}18">${x[0]} ${x[1]}</span>`}
async function cargarUsuarios(){const p=new URLSearchParams();if(buscarUsuario.value.trim())p.set('buscar',buscarUsuario.value.trim());if(filtroRolUsuario.value)p.set('rol',filtroRolUsuario.value);if(filtroEstadoUsuario.value!=='')p.set('activo',filtroEstadoUsuario.value);tbodyUsuarios.innerHTML='<tr><td colspan="7" style="text-align:center;padding:35px">Cargando…</td></tr>';try{const r=await fetchAPI(`${API}/admin/usuarios?${p}`),d=await r.json();usuariosCache=d.data||d;totalUsuarios.textContent=`${usuariosCache.length} usuarios`;tbodyUsuarios.innerHTML=usuariosCache.map(u=>`<tr><td>#${u.id_usuario}</td><td><b>${u.nombre||''} ${u.apellido||''}</b><br><small>${u.telefono||'Sin teléfono'}</small></td><td>${u.correo}</td><td>${rolBadge(u.rol)}</td><td>${estadoBadge(!!u.activo)}</td><td>${u.created_at?new Date(u.created_at).toLocaleDateString('es-EC'):'—'}</td><td><button class="btn btn-ghost btn-sm" onclick="editarUsuario(${u.id_usuario})">✎ Editar</button> ${u.activo?`<button class="btn btn-coral btn-sm" onclick="desactivarUsuario(${u.id_usuario})">✕</button>`:''}</td></tr>`).join('')||'<tr><td colspan="7">Sin usuarios</td></tr>'}catch(e){tbodyUsuarios.innerHTML='<tr><td colspan="7">Error al cargar</td></tr>'}}
function editarUsuario(id){const u=usuariosCache.find(x=>+x.id_usuario===+id);if(!u)return;usuarioEditando=id;uNombre.value=u.nombre||'';uApellido.value=u.apellido||'';uCorreo.value=u.correo||'';uTelefono.value=u.telefono||'';uRol.value=u.rol;uActivo.checked=!!u.activo;uPassword.value='';uPassword.placeholder='Vacío para conservar';tituloFormUsuario.textContent=`Editar usuario #${id}`;textoBtnGuardarUsuario.textContent='Guardar cambios';btnCancelarEdicionUsuario.style.display='inline-flex';scrollTo({top:0,behavior:'smooth'})}
async function guardarUsuario(e){e.preventDefault();const d={nombre:uNombre.value.trim(),apellido:uApellido.value.trim(),correo:uCorreo.value.trim(),telefono:uTelefono.value.trim(),rol:uRol.value,activo:uActivo.checked};if(uPassword.value)d.password=uPassword.value;if(!usuarioEditando&&!d.password)return mostrarToast('La contraseña es obligatoria','warning');try{const r=await fetchAPI(usuarioEditando?`${API}/admin/usuarios/${usuarioEditando}`:`${API}/admin/usuarios`,{method:usuarioEditando?'PUT':'POST',body:JSON.stringify(d)}),x=await r.json();if(!r.ok)throw new Error(x.mensaje);mostrarToast(x.mensaje,'success');limpiarFormularioUsuario();cargarUsuarios()}catch(e){mostrarToast(e.message||'Error','danger')}}
async function desactivarUsuario(id){const ok=await confirmarAccion({titulo:'Desactivar usuario',mensaje:'El usuario no podrá iniciar sesión hasta que lo reactives. ¿Continuar?',textoBoton:'Desactivar',peligro:true});if(!ok)return;const r=await fetchAPI(`${API}/admin/usuarios/${id}`,{method:'DELETE'}),d=await r.json();mostrarToast(d.mensaje,r.ok?'success':'danger');cargarUsuarios()}
async function crearSupervisorDemo(){
  const ok=await confirmarAccion({titulo:'Supervisor de prueba',mensaje:'¿Crear o resetear la cuenta de supervisor de prueba (supervisor@geoincidencias.com)?',textoBoton:'Crear/resetear'});
  if(!ok)return;
  try{
    const r=await fetchAPI(`${API}/admin/usuarios/crear-supervisor-demo`,{method:'POST'}),d=await r.json();
    if(!r.ok)throw new Error(d.mensaje||'Error');
    mostrarToast(`✓ Correo: supervisor@geoincidencias.com · Contraseña: 123456`,'success');
    cargarUsuarios();
  }catch(e){mostrarToast(e.message||'No se pudo crear el supervisor de prueba','danger')}
}
async function crearTecnicoDemo(){
  const ok=await confirmarAccion({titulo:'Técnico de prueba',mensaje:'¿Crear o resetear la cuenta de técnico de prueba (tecnico@geoincidencias.com)?',textoBoton:'Crear/resetear'});
  if(!ok)return;
  try{
    const r=await fetchAPI(`${API}/admin/usuarios/crear-tecnico-demo`,{method:'POST'}),d=await r.json();
    if(!r.ok)throw new Error(d.mensaje||'Error');
    mostrarToast(`✓ Correo: tecnico@geoincidencias.com · Contraseña: 123456`,'success');
    cargarUsuarios();
  }catch(e){mostrarToast(e.message||'No se pudo crear el técnico de prueba','danger')}
}
function filtrarRolRapido(rol, btn){document.getElementById('filtroRolUsuario').value=rol;document.querySelectorAll('#quickFiltersUsuarios .qf-chip').forEach(b=>b.classList.remove('active'));btn.classList.add('active');cargarUsuarios()}
formUsuario.addEventListener('submit',guardarUsuario);buscarUsuario.addEventListener('input',()=>setTimeout(cargarUsuarios,200));filtroRolUsuario.addEventListener('change',cargarUsuarios);filtroEstadoUsuario.addEventListener('change',cargarUsuarios);limpiarFormularioUsuario();cargarUsuarios();
