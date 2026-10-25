import fs from 'node:fs';
import path from 'node:path';
import {fileURLToPath} from 'node:url';
const dir=path.dirname(fileURLToPath(import.meta.url));
const roles=[
 ['admin','Administrador','Configurar usuarios, médicos, especialidades y horarios; supervisar operaciones autorizadas','Actúa según permisos efectivos; no se presume acceso irrestricto a datos clínicos'],
 ['recepcion','Recepcionista','Identificar pacientes y registrar o gestionar citas de acuerdo con su autorización','No configura médicos ni horarios con el conjunto inicial de permisos'],
 ['medico','Médico','Consultar su agenda y los datos básicos necesarios para atender','CU-11 se limitará al profesional vinculado y requerirá una política explícita para consultas administrativas'],
 ['paciente','Paciente','Acceder a su cuenta, verificar correo y reservar para su ficha vinculada','No puede escoger la identidad de otro paciente ni consultar comprobantes ajenos']
];
const action=(id,text)=>({id,text});
const check=(id,text,error,retry=null)=>({id,text,error,retry,decision:true});
const flows=[
 {id:'CU-01',title:'Iniciar y cerrar sesión',permission:'Acceso para invitados; funciones internas según sesión vigente y permisos efectivos.',
 sources:['sistema/backend/app/Http/Controllers/Auth/SessionController.php','sistema/backend/app/Http/Middleware/ProtectSession.php','sistema/backend/config/seguridad.php'],
 note:'Límites: 5 fallos por identificador/IP, espera 15 min y límite adicional por IP. Inactividad: 30 min. Credenciales inexistentes, inactivas e incorrectas reciben mensaje genérico. El acceso normal va a /inicio; booking.continue permite continuar en /citas. Recuperación corresponde a CU-01.1.',
 steps:[action('form','Sistema: mostrar formulario de acceso'),action('input','Usuario: enviar identificador y contraseña; CSRF'),check('limit','¿Intento permitido?','Sistema: informar espera temporal; no autenticar','form'),check('auth','¿Credenciales válidas y cuenta activa?','Sistema: registrar fallo sin secretos; sumar intento; error genérico','form'),action('session','Sistema: limpiar contador; regenerar sesión; guardar actividad, hash y token'),action('home','Sistema: mostrar inicio o continuar reserva autorizada'),action('request','Usuario: solicitar una función interna'),check('valid','¿Sesión y cuenta siguen vigentes?','Sistema: invalidar sesión; renovar CSRF; volver a login'),check('permission','¿Tiene permiso para la función?','Sistema: rechazar la función con 403'),action('use','Sistema: mostrar contenido autorizado; actualizar actividad'),check('exit','¿Solicita cerrar sesión?','Usuario: continuar navegando','request'),action('logout','Usuario/Sistema: POST /logout con CSRF; invalidar sesión; renovar CSRF; mostrar login')]},
 {id:'CU-02',title:'Administrar usuarios, roles y permisos',permission:'Sesión vigente y usuarios.gestionar efectivo.',
 sources:['sistema/backend/routes/usuarios.php','sistema/backend/app/Services/Auth/UserAdministration.php'],
 note:'Sin sesión: login; sin permiso: 403. Usuarios y roles se crean/editan; permisos solo cambian de estado dentro del catálogo. No hay borrado físico. Se preservan vínculos inactivos. Datos de contraseña nunca se repueblan. No se crean fichas clínicas ni se envía correo al crear cuentas.',
 steps:[check('access','¿Sesión vigente y permiso efectivo?','Sistema: login o 403; no modificar datos'),action('list','Gestor: listar y elegir usuario, rol o permiso'),action('form','Gestor: completar datos/asignaciones o elegir estado'),check('confirm','¿Confirma la operación?','Sistema: cancelar; volver al listado sin guardar'),check('data','¿Datos y selecciones válidos?','Sistema: mostrar errores de identidad, clave, rol o catálogo','form'),action('begin','Sistema: BEGIN; bloqueo global; volver a comprobar autorización'),check('authorized','¿Mantiene autorización?','Sistema: ROLLBACK; responder 403'),action('save','Sistema: guardar cuenta/rol/estado y vínculos; rotar token y revocar recuperación si corresponde'),check('continuity','¿Se conserva el último gestor y la administración propia?','Sistema: ROLLBACK; explicar rechazo','form'),action('commit','Sistema: COMMIT; registrar auditoría sin secretos'),check('ownsession','¿La sesión actual puede continuar?','Sistema: cerrar sesión por cambio de clave propia; volver a login'),action('result','Sistema: mostrar listado y confirmación')]},
 {id:'CU-03',title:'Registrarse como paciente',permission:'Registro público. Verificación: cuenta autenticada y enlace firmado de la misma cuenta.',
 sources:['sistema/backend/app/Http/Controllers/Pacientes/RegistrationController.php','sistema/backend/app/Services/Pacientes/PatientSelfRegistration.php','sistema/backend/app/Http/Controllers/Auth/VerificationController.php'],
 note:'Cuenta y ficha se crean juntas, con rol paciente activo. No se vinculan fichas previas por coincidencia. Un fallo de correo no revierte el registro. El reenvío exige sesión y espera de 60 s. Verificar correo no reserva una cita. En modo log/array no se entrega a la bandeja.',
 steps:[action('form','Visitante: abrir registro y completar datos personales y de acceso'),check('data','¿Formulario válido?','Sistema: devolver errores sin repoblar contraseña','form'),action('begin','Sistema: BEGIN; bloqueo administrativo; comprobar rol paciente activo'),check('available','¿Alta disponible y sin duplicados?','Sistema: ROLLBACK; informar rechazo sin vincular fichas previas','form'),action('commit','Sistema: crear cuenta, asignación y ficha PAC-ID; COMMIT'),action('mail','Sistema: solicitar correo de verificación después del COMMIT'),check('delivery','¿Solicitud de envío aceptada?','Sistema: conservar cuenta; avisar fallo o modo local y opción de reenvío','login'),action('login','Paciente: iniciar sesión; abrir aviso de verificación'),action('link','Paciente: abrir enlace recibido o solicitar reenvío permitido'),check('signed','¿Misma cuenta, hash, firma y vigencia válidos?','Sistema: rechazar enlace; ofrecer nuevo envío con límite','link'),action('verify','Sistema: marcar correo verificado; mostrar confirmación en inicio')]},
 {id:'CU-04',title:'Registrar y gestionar pacientes en recepción',permission:'Sesión vigente y pacientes.gestionar efectivo.',
 sources:['sistema/backend/app/Http/Controllers/Pacientes/PacienteController.php','sistema/backend/app/Services/Pacientes/PatientRegistration.php'],
 note:'Consultar una ficha no la modifica. Estado se cambia dentro de edición. CI opcional, normalizado y único incluso entre inactivos. En edición se bloquea ficha y comprueba HMAC de versión. No borrar físicamente ni crear automáticamente cuenta o historia clínica.',
 steps:[check('access','¿Sesión y permiso válidos?','Sistema: login o 403; no acceder a fichas'),action('list','Recepcionista: buscar, filtrar y consultar ficha administrativa'),action('form','Recepcionista: elegir alta o edición; completar datos y estado si edita'),check('confirm','¿Desea guardar?','Sistema: cancelar; conservar ficha previa'),check('data','¿Datos válidos?','Sistema: mostrar errores del formulario','form'),action('begin','Sistema: BEGIN; revalidar permiso; bloquear ficha existente'),check('version','¿Versión vigente y CI disponible?','Sistema: ROLLBACK; informar conflicto o duplicado','form'),action('save','Sistema: guardar ficha; generar PAC-ID solo en alta; COMMIT'),action('result','Sistema: mostrar ficha y confirmación; conservar cuenta e historial')]},
 {id:'CU-05',title:'Gestionar médicos y especialidades',permission:'Sesión vigente y medicos.gestionar efectivo.',
 sources:['sistema/backend/app/Services/Medicos/DoctorManagement.php','sistema/backend/app/Services/Especialidades/SpecialtyManagement.php','sistema/backend/routes/medicos.php','sistema/backend/routes/especialidades.php'],
 note:'Médicos: especialidades activas obligatorias; cuenta existente opcional y elegible. Reactivar exige especialidad activa. El estado tiene confirmación separada. Especialidades: nombre único normalizado. Conservar vínculos, horarios y citas. No crear cuentas automáticamente ni modificar roles.',
 steps:[check('access','¿Sesión y permiso válidos?','Sistema: login o 403; no modificar catálogo'),action('list','Administrador: consultar médicos o especialidades; elegir acción'),action('form','Administrador: completar alta/edición o revisar confirmación de estado'),check('confirm','¿Confirma guardar o cambiar estado?','Sistema: cancelar sin guardar'),check('data','¿Datos y selecciones válidos?','Sistema: mostrar errores de nombre, matrícula, especialidad o cuenta','form'),action('begin','Sistema: BEGIN; bloqueo administrativo y registro; revalidar permiso'),check('version','¿Versión, unicidad y relaciones aún válidas?','Sistema: ROLLBACK; informar conflicto o reactivación inválida','form'),action('save','Sistema: guardar médico MED-ID, especialidad, vínculos o estado según acción'),action('commit','Sistema: COMMIT; conservar identidad y relaciones; mostrar listado confirmado')]},
 {id:'CU-06',title:'Configurar horarios de atención y bloqueos',permission:'Sesión vigente y horarios.gestionar efectivo.',
 sources:['sistema/backend/app/Services/Horarios/ScheduleManagement.php','sistema/backend/routes/horarios.php'],
 note:'Franja semanal y vigencia deben producir turnos completos; duración divide la franja. Los bloqueos restan disponibilidad. No solapar registros activos del mismo tipo. No dejar citas pendientes sin cobertura ni bloquearlas. Toda mutación bloquea primero al médico. CU-06 no cancela ni reprograma citas.',
 steps:[check('access','¿Sesión y permiso válidos?','Sistema: login o 403'),action('list','Administrador: consultar horarios o bloqueos del médico'),action('form','Administrador: elegir alta/edición/estado y completar franja, vigencia o periodo'),check('confirm','¿Confirma la operación?','Sistema: cancelar; no cambiar agenda'),check('data','¿Duración, periodo y vigencia válidos?','Sistema: mostrar errores de formato o turnos incompletos','form'),action('begin','Sistema: BEGIN; bloquear médico primero y luego registro; revalidar permiso'),check('version','¿Versión vigente y sin cruces incompatibles?','Sistema: ROLLBACK; informar conflicto de versión o cruce','form'),check('appointments','¿Se conservan cobertura y disponibilidad de citas pendientes?','Sistema: ROLLBACK; exigir resolver las citas por sus casos correspondientes','form'),action('save','Sistema: guardar horario, bloqueo o estado; COMMIT'),action('result','Sistema: mostrar listado actualizado y confirmación')]},
 {id:'CU-07',title:'Consultar disponibilidad y reservar cita',permission:'Paciente: citas.propias, ficha propia activa y correo verificado. Personal: citas.gestionar y paciente activo elegido.',
 sources:['sistema/backend/app/Services/Citas/AppointmentAvailability.php','sistema/backend/app/Services/Citas/AppointmentBooking.php','sistema/backend/app/Http/Controllers/Citas/CitaController.php'],
 note:'El paciente no elige otra identidad. El personal autorizado puede seleccionar paciente sin exigir que tenga cuenta o correo verificado. Consulta hasta 30 días. Revisar no retiene turno. Token cifrado ligado al actor, 30 min. Cita programada y auditoría son atómicas; el comprobante requiere titularidad o permiso de gestión.',
 steps:[check('access','¿Sesión y acceso al paciente válidos?','Sistema: login, verificación o rechazo; no acceder a otra ficha'),action('filters','Actor: elegir especialidad, fecha y médico opcional; personal selecciona paciente autorizado'),action('slots','Sistema: calcular turnos futuros completos; excluir bloqueos y citas de médico/paciente'),check('available','¿Hay turnos disponibles?','Sistema: informar sin disponibilidad; permitir otros filtros','filters'),action('review','Actor/Sistema: elegir y revisar turno; revalidar; emitir token cifrado'),check('confirm','¿Confirma la reserva?','Sistema: cancelar revisión sin crear cita'),action('begin','Sistema: validar token; BEGIN; bloquear médico y luego paciente'),check('token','¿Token vigente y actor/ficha aún autorizados?','Sistema: ROLLBACK; exigir nueva revisión','filters'),check('new','¿Token aún sin reserva confirmada?','Sistema: recuperar la misma cita; no duplicar auditoría','receipt'),action('recheck','Sistema: revalidar horario, médico, especialidad, bloqueos y citas'),check('free','¿Turno sigue libre y sin cruces?','Sistema: ROLLBACK; informar conflicto; volver a consultar','filters'),action('save','Sistema: insertar cita programada, CIT-ID y auditoría; COMMIT'),action('receipt','Sistema: autorizar y mostrar comprobante del titular o gestor')]}
];
const nodes=[],diagrams=[];
for(const f of flows){
 const pre=f.id.toLowerCase().replace('-','');const placements=[],edges=[];
 const add=(id,name,type,x,y,w,h,extra={})=>{const ref=pre+'-'+id;nodes.push({id:ref,name,type,caseId:f.id,...extra});placements.push({ref,x,y,w,h});return ref;};
 const initial=add('inicio','','StateNode',650,40,30,30,{subtype:100});
 const ids=new Map(f.steps.map(s=>[s.id,pre+'-'+s.id]));
 for(let i=0;i<f.steps.length;i++){
  const s=f.steps[i],y=130+i*170;
  add(s.id,s.text,s.decision?'Decision':'Action',s.decision?585:410,y,s.decision?160:510,s.decision?105:95);
  if(s.decision){
   const err=add(s.id+'-alternativa',s.error,'Action',1100,y,480,105);
   edges.push({from:ids.get(s.id),to:err,label:'[no]',type:'ControlFlow'});
   if(s.retry){
    const j=f.steps.findIndex(t=>t.id===s.retry);if(j<0)throw Error('Destino inexistente');
    const ty=130+j*170+47;
    edges.push({from:err,to:ids.get(s.retry),label:'',type:'ControlFlow',path:`1660:${-(y+52)};1660:${-ty};`});
   }else{
    const end=add(s.id+'-fin','','StateNode',1325,y+120,24,24,{subtype:101});
    edges.push({from:err,to:end,label:'',type:'ControlFlow'});
   }
  }
 }
 const end=add('fin','','StateNode',650,130+f.steps.length*170,32,32,{subtype:101});
 edges.push({from:initial,to:ids.get(f.steps[0].id),label:'',type:'ControlFlow'});
 for(let i=0;i<f.steps.length;i++)edges.push({from:ids.get(f.steps[i].id),to:i===f.steps.length-1?end:ids.get(f.steps[i+1].id),label:f.steps[i].decision?'[sí]':'',type:'ControlFlow'});
 add('nota',f.permission+'\n\n'+f.note,'Note',410,230+f.steps.length*170,1170,210);
 diagrams.push({id:pre+'-actividad',caseId:f.id,caseTitle:f.title,title:'Diagrama de actividad',type:'Activity',kind:'actividad',permission:f.permission,note:f.note,sources:f.sources,placements,edges});
}
const actorPlacements=[],actorEdges=[];
for(const [i,[id,name,responsibility,limit]] of roles.entries()){
 const actor='act-'+id,note='nota-'+id,y=70+i*270;
 nodes.push({id:actor,name,type:'Actor',caseId:'ACTORES',notes:'Responsabilidad: '+responsibility+'\nLímite de acceso: '+limit});
 nodes.push({id:note,name:'<p><b>Responsabilidad:</b> '+responsibility+'</p><p><b>Límite de acceso:</b> '+limit+'</p>',type:'Note',caseId:'ACTORES'});
 actorPlacements.push({ref:actor,x:70,y:y+25,w:130,h:160},{ref:note,x:330,y,w:950,h:215});
 actorEdges.push({from:actor,to:note,label:'',type:'NoteLink'});
}
diagrams.push({id:'actores-corregidos',caseId:'ACTORES',caseTitle:'Actores y límites de acceso',title:'Actores y límites de acceso',type:'Use Case',kind:'actores',permission:'Tabla de actores indicada por el usuario.',note:'Cuatro roles de negocio. Visitante y correo son auxiliares en otros contextos; no se añaden como roles de esta tabla. CU-11 permanece pendiente.',sources:['sistema/backend/config/permisos.php'],placements:actorPlacements,edges:actorEdges});
// Reunir recorridos alternativos con nodos de mezcla UML, evitando un join
// implícito al devolver varios flujos directamente a una acción.
for(const d of diagrams.filter(d=>d.kind==='actividad')){
 const incoming=new Map();for(const e of d.edges)incoming.set(e.to,(incoming.get(e.to)||0)+1);
 for(const [target,count] of incoming){
  if(count<2||nodes.find(n=>n.id===target).type!=='Action')continue;
  const p=d.placements.find(p=>p.ref===target),id=target+'-mezcla',cy=p.y-15;
  nodes.push({id,name:'',type:'Decision',caseId:d.caseId,merge:true});
  d.placements.push({ref:id,x:655,y:cy-10,w:20,h:20});
  for(const e of d.edges)if(e.to===target){e.to=id;if(e.path)e.path=e.path.replace(/;1660:-?\d+;$/,`;1660:${-cy};`);}
  d.edges.push({from:id,to:target,label:'',type:'ControlFlow'});
 }
}
for(const d of diagrams){const ids=new Set(d.placements.map(p=>p.ref));for(const e of d.edges)if(!ids.has(e.from)||!ids.has(e.to))throw Error('Conector inválido');for(const source of d.sources)if(!fs.existsSync(path.resolve(dir,'../../../..',source)))throw Error('Fuente inexistente: '+source);}
const cases=[...flows.map(f=>({id:f.id,title:f.title,permission:f.permission,rules:f.note})),{id:'ACTORES',title:'Actores y límites de acceso',permission:'Tabla acordada',rules:'Aplicar permisos efectivos y titularidad; CU-11 pendiente.'}];
fs.writeFileSync(path.join(dir,'modelo.json'),JSON.stringify({title:'Clínica Vida Salud - Actividades CU-01 a CU-07 y actores',date:'2026-10-24',roles,cases,nodes,diagrams},null,2)+'\n');
console.log(JSON.stringify({activities:flows.length,actorDiagrams:1,elements:nodes.length}));
