import fs from 'node:fs';
import path from 'node:path';
import {fileURLToPath} from 'node:url';

const dir = path.dirname(fileURLToPath(import.meta.url));
const cases = [
{
 id:'CU-03', title:'Registrarse como paciente', actor:'Visitante / Paciente',
 permission:'Registro público. Verificación: cuenta autenticada, firma vigente y correo de la misma cuenta.',
 sources:['docs/casos-de-uso/CU-03/especificacion.md','sistema/backend/routes/auth.php','sistema/backend/app/Http/Controllers/Auth/VerificationController.php','sistema/backend/app/Services/Pacientes/PatientSelfRegistration.php'],
 goals:['Registrar cuenta de paciente','Verificar correo de la cuenta','Solicitar nuevo enlace'],
 rules:'Cuenta y ficha se crean juntas. Solo rol paciente. No vincular fichas previas por coincidencia de CI o correo. Registrar una cuenta no reserva una cita.',
 comm:['UI_registro / verificación','Registro y verificación','User / Paciente / DB'],
 commMessages:[
 ['1: completar registro','2: iniciar sesión (CU-01)','3: abrir enlace / reenviar'],
 ['1.1: POST /registro','3.1: GET enlace firmado','3.2: POST reenvío (1/min)'],
 ['1.2: register() transaccional','1.3: crear cuenta, rol y ficha','3.3: verificar correo del propietario']],
 commNote:'El control agrupa RegistrationController, VerificationController y PatientSelfRegistration. El correo es una notificación externa; su fallo no deshace la cuenta creada.',
 nav:[
 ['Portada','GET /','Crear cuenta / Iniciar sesión'],['Registro','GET /registro','POST /registro'],['Acceso','GET /login','POST /login (CU-01)'],['Mi inicio','GET /inicio','Aviso de correo pendiente'],['Confirmar correo','GET /verificar-correo','POST reenvío: 1 por minuto'],['Enlace firmado','GET /verificar-correo/{id}/{hash}','Misma cuenta + firma + vigencia']],
 navEdges:[[0,1,'Crear mi cuenta'],[1,2,'[cuenta creada]'],[2,3,'[acceso válido]'],[3,4,'Verificar correo'],[4,5,'Abrir enlace recibido'],[5,3,'[correo verificado]'],[1,1,'[datos inválidos]'],[4,4,'Reenviar / esperar 60 s']],
 navNote:'El enlace se recibe fuera del portal. En modo log no se entrega a la bandeja. Crear la cuenta o reenviar no verifica el correo automáticamente.',
 participants:['Visitante / Paciente','V.Registro / Verificación','RegistrationController /\nVerificationController','PatientSelfRegistration','User / Paciente / DB','Correo'],
 messages:[
 [0,1,'Completar y enviar registro'],[1,2,'POST /registro; validar RegisterPatientRequest'],[2,3,'register(datos permitidos)'],[3,4,'BEGIN + bloqueo de administración'],[3,4,'Crear User, asignar rol paciente y crear ficha PAC-ID'],[4,3,'COMMIT; cuenta y ficha vinculadas',true],[3,2,'Usuario creado',true],[2,5,'sendEmailVerificationNotification()'],[5,2,'Aceptado o fallo de transporte',true],[2,1,'Redirigir a /login; informar resultado',true],[0,1,'Iniciar sesión mediante CU-01'],[0,1,'Abrir el enlace firmado recibido'],[1,2,'GET /verificar-correo/{id}/{hash}'],[2,4,'Comprobar sesión, propietario, hash y firma'],[2,4,'Marcar email_verified_at; conservar la ficha'],[2,1,'Redirigir a /inicio con confirmación',true]],
 sections:[{name:'opt [registro válido]',start:1,end:10},{name:'opt [enlace válido y misma cuenta]',start:12,end:16}],
 seqNote:'Datos inválidos o duplicados: rechazar sin registros parciales. Error de correo: conservar la cuenta e informar reenvío. Firma, correo, cuenta o vigencia incorrectos: rechazar la verificación.'
},
{
 id:'CU-04', title:'Registrar y gestionar pacientes en recepción', actor:'Recepción / Administrador autorizado',
 permission:'Sesión vigente y permiso efectivo pacientes.gestionar.',
 sources:['docs/casos-de-uso/CU-04/especificacion.md','sistema/backend/routes/pacientes.php','sistema/backend/app/Http/Controllers/Pacientes/PacienteController.php','sistema/backend/app/Services/Pacientes/PatientRegistration.php'],
 goals:['Buscar y consultar fichas','Registrar paciente en recepción','Editar ficha administrativa','Activar o desactivar ficha'],
 rules:'CI opcional, normalizado y único incluso entre inactivos. Código PAC-ID inmutable. No borrar fichas ni modificar la cuenta de acceso al cambiar su estado.',
 comm:['UI_pacientes','PacienteController /\nPatientRegistration','Paciente / PostgreSQL'],
 commMessages:[['1: buscar / consultar','2: registrar / editar','3: cambiar estado'],['1.1: index() / show()','2.1: store() / update()','3.1: update(activo, versión)'],['1.2: consultar con filtros','2.2: save() con transacción','2.3: bloquear y validar versión']],
 commNote:'La ficha administrativa no es una historia clínica. Un CI ya existente no provoca fusión ni vinculación automática de cuentas.',
 nav:[['Mi inicio / Menú','GET /inicio','Pacientes'],['Listado de pacientes','GET /pacientes','Buscar, filtrar, paginar'],['Registrar paciente','GET /pacientes/crear','POST /pacientes'],['Ficha administrativa','GET /pacientes/{paciente}','Datos, estado y código'],['Editar paciente','GET /pacientes/{paciente}/editar','PUT /pacientes/{paciente}'],['Estado de la ficha','En el formulario de edición','Activo / inactivo + versión']],
 navEdges:[[0,1,'Pacientes'],[1,2,'Registrar paciente'],[1,3,'Consultar ficha'],[3,4,'Editar'],[2,3,'[alta válida]'],[4,3,'[cambios válidos]'],[4,5,'Cambiar activo'],[5,3,'Guardar edición'],[2,1,'Cancelar'],[4,1,'Cancelar']],
 navNote:'El estado se edita en el formulario del paciente; no existe una ruta de confirmación independiente. Datos o versión inválidos mantienen la edición sin sobrescribir otra ficha.',
 participants:['Recepción / Administrador','V.Pacientes','PacienteController','PatientRegistration','Paciente / PostgreSQL'],
 messages:[[0,1,'Buscar o consultar ficha'],[1,2,'GET /pacientes o /pacientes/{id}'],[2,4,'Consultar filtros / ficha autorizada'],[4,2,'Pacientes o ficha',true],[2,1,'Mostrar listado / ficha',true],[0,1,'Enviar alta o edición'],[1,2,'POST /pacientes o PUT /pacientes/{id}'],[2,3,'save(actor, datos validados, id?)'],[3,4,'BEGIN; revalidar permiso'],[3,4,'[edición] lockForUpdate y comprobar versión'],[3,4,'Insertar / actualizar; generar PAC-ID en alta'],[4,3,'COMMIT o conflicto de unicidad',true],[3,2,'Paciente o error de validación',true],[2,1,'Redirigir a ficha o corregir formulario',true],[0,1,'Editar activo con versión vigente'],[1,2,'PUT /pacientes/{id}; activo y versión'],[2,3,'save(actor, datos, id)'],[3,4,'Transacción: bloquear, validar versión y actualizar'],[3,2,'Ficha conservada; estado actualizado',true],[2,1,'Mostrar ficha y confirmación',true]],
 sections:[{name:'opt [consulta autorizada]',start:1,end:5},{name:'opt [alta o edición]',start:6,end:14},{name:'opt [cambio de estado]',start:15,end:20}],
 seqNote:'CI repetido o versión desactualizada: rechazar y conservar la ficha previa. Sin permiso: 403. Cancelar no guarda; no hay borrado físico.'
},
{
 id:'CU-05', title:'Gestionar médicos y especialidades', actor:'Administrador autorizado',
 permission:'Sesión vigente y permiso efectivo medicos.gestionar.',
 sources:['docs/casos-de-uso/CU-05/especificacion.md','sistema/backend/routes/medicos.php','sistema/backend/routes/especialidades.php','sistema/backend/app/Services/Medicos/DoctorManagement.php','sistema/backend/app/Services/Especialidades/SpecialtyManagement.php'],
 goals:['Gestionar catálogo de especialidades','Registrar o editar médico','Asignar especialidades al médico','Vincular cuenta existente elegible','Activar o desactivar registros'],
 rules:'Especialidades activas obligatorias. Cuenta existente opcional, elegible y sin otra vinculación. Registro profesional y nombre de especialidad únicos normalizados. Conservar relaciones e identidad.',
 comm:['UI_médicos / especialidades','Controllers +\nservicios de gestión','Medico / Especialidad / DB'],
 commMessages:[['1: listar / buscar','2: alta / edición','3: confirmar estado'],['1.1: index()','2.1: store() / update()','3.1: updateState()'],['2.2: validar y guardar datos','2.3: guardar médico y asignaciones','3.2: changeState() con versión']],
 commNote:'Servicios: DoctorManagement y SpecialtyManagement. Vincular una cuenta existente no crea usuarios ni cambia roles. El estado se confirma en una página separada.',
 nav:[['Mi inicio / Menú','GET /inicio','Médicos / Especialidades'],['Listado de médicos','GET /medicos','Buscar y filtrar'],['Formulario de médico','GET /medicos/crear o /{id}/editar','POST /medicos o PUT /{id}'],['Confirmar estado médico','GET /medicos/{id}/estado','PATCH /medicos/{id}/estado'],['Listado de especialidades','GET /especialidades','Buscar y filtrar'],['Formulario / estado de especialidad','GET crear, editar o estado','POST / PUT / PATCH según acción']],
 navEdges:[[0,1,'Médicos'],[0,4,'Especialidades'],[1,2,'Registrar / editar'],[2,1,'Guardar o cancelar'],[1,3,'Activar / desactivar'],[3,1,'Confirmar o cancelar'],[4,5,'Registrar / editar / estado'],[5,4,'Guardar / confirmar / cancelar'],[2,4,'Consultar catálogo desde menú']],
 navNote:'Las rutas GET muestran formularios y confirmaciones; no alteran datos. Las altas y ediciones no cambian el estado; se usa PATCH desde su confirmación.',
 participants:['Administrador autorizado','V.Médicos / Especialidades','Controllers de gestión','DoctorManagement /\nSpecialtyManagement','Modelos / PostgreSQL'],
 messages:[[0,1,'Consultar médicos o especialidades'],[1,2,'GET /medicos o /especialidades'],[2,4,'Consultar filtros y relaciones'],[2,1,'Mostrar listado',true],[0,1,'Enviar alta / edición de médico'],[1,2,'POST /medicos o PUT /medicos/{id}'],[2,3,'save(actor, datos, id?)'],[3,4,'BEGIN; bloqueo global y médico si existe'],[3,4,'Validar permiso, versión, especialidades y cuenta'],[3,4,'Guardar MED-ID y asignaciones sin borrar historial'],[4,3,'COMMIT o ROLLBACK',true],[3,2,'Médico o validación',true],[2,1,'Volver a listado con resultado',true],[0,1,'Confirmar cambio de estado'],[1,2,'PATCH /medicos/{id}/estado'],[2,3,'changeState(actor, id, versión, activo)'],[3,4,'Bloquear; comprobar versión y reactivación válida'],[3,2,'Estado guardado; conservar cuenta y citas',true],[2,1,'Listado y confirmación',true],[0,1,'Guardar especialidad en su formulario'],[1,2,'POST /especialidades o PUT /{id}'],[2,3,'SpecialtyManagement.save(actor, datos, id?)'],[3,4,'Transacción; unicidad normalizada y versión'],[3,2,'Especialidad guardada o error',true],[2,1,'Listado de especialidades',true]],
 sections:[{name:'opt [consulta]',start:1,end:4},{name:'opt [alta o edición de médico]',start:5,end:13},{name:'opt [estado de médico]',start:14,end:19},{name:'opt [catálogo de especialidades]',start:20,end:25}],
 seqNote:'Duplicado o versión antigua: rechazar. Una cuenta vinculada se selecciona explícitamente. Reactivar médico exige especialidad activa. Desactivar conserva horarios y citas.'
},
{
 id:'CU-06', title:'Configurar horarios de atención y bloqueos', actor:'Administrador autorizado',
 permission:'Sesión vigente y permiso efectivo horarios.gestionar.',
 sources:['README.md#cu-06-horarios-y-bloqueos','sistema/backend/routes/horarios.php','sistema/backend/app/Services/Horarios/ScheduleManagement.php'],
 goals:['Consultar horarios de atención','Registrar o editar franja semanal','Registrar o editar bloqueo','Activar o desactivar horario / bloqueo'],
 rules:'Días 1 a 7; duración divide la franja en turnos completos. Vigencia con ocurrencias reales. Bloquear siempre primero el médico. No dejar citas pendientes sin cobertura ni bloquear citas pendientes.',
 comm:['UI_horarios / bloqueos','Controllers +\nScheduleManagement','Horario / Bloqueo / Cita / DB'],
 commMessages:[['1: consultar agenda configurada','2: guardar franja o bloqueo','3: confirmar estado'],['1.1: index()','2.1: store() / update()','3.1: updateState()'],['2.2: lock médico + versión','2.3: comprobar cruces y citas','2.4: guardar / confirmar']],
 commNote:'Los bloqueos restan disponibilidad sin borrar el horario semanal. Horarios y bloqueos activos no se solapan entre sí dentro de su tipo. Se conservan citas y relaciones.',
 nav:[['Mi inicio / Menú','GET /inicio','Horarios de atención'],['Horarios semanales','GET /horarios','Filtrar médico, día y estado'],['Formulario de horario','GET crear o /{id}/editar','POST /horarios o PUT /{id}'],['Confirmar estado','GET /horarios/{id}/estado','PATCH /horarios/{id}/estado'],['Bloqueos y ausencias','GET /horarios/bloqueos','Consultar periodos'],['Formulario / estado de bloqueo','GET crear, editar o estado','POST / PUT / PATCH de bloqueos']],
 navEdges:[[0,1,'Horarios'],[1,2,'Registrar / editar'],[2,1,'Guardar o cancelar'],[1,3,'Activar / desactivar'],[3,1,'Confirmar o cancelar'],[1,4,'Bloqueos y ausencias'],[4,1,'Horarios'],[4,5,'Registrar / editar / estado'],[5,4,'Guardar / confirmar / cancelar']],
 navNote:'Los errores de cruce, duración, vigencia o versión vuelven al formulario. Las citas afectadas se deben resolver en sus propios casos; CU-06 no cancela ni reprograma.',
 participants:['Administrador autorizado','V.Horarios / Bloqueos','HorarioController /\nIndisponibilidadController','ScheduleManagement','Modelos / PostgreSQL'],
 messages:[[0,1,'Consultar horarios o bloqueos'],[1,2,'GET /horarios o /horarios/bloqueos'],[2,4,'Consultar agenda configurada'],[2,1,'Mostrar listado y filtros',true],[0,1,'Enviar franja semanal'],[1,2,'POST /horarios o PUT /horarios/{id}'],[2,3,'saveSchedule(actor, datos, id?)'],[3,4,'BEGIN; lock médico; registro y versión si existe'],[3,4,'Comprobar vigencia, duración y protección de citas'],[3,4,'Guardar; exclusión SQL de franjas activas'],[4,3,'COMMIT o conflicto',true],[3,2,'Horario guardado o validación',true],[2,1,'Listado o corrección',true],[0,1,'Enviar bloqueo o cambio de estado'],[1,2,'POST /horarios/bloqueos o PUT/PATCH'],[2,3,'saveBlock() / changeScheduleState() / changeBlockState()'],[3,4,'BEGIN; lock médico; comprobar versión'],[3,4,'Comprobar citas pendientes y superposición'],[3,4,'Guardar o revertir; no modificar citas'],[3,2,'Resultado de la operación',true],[2,1,'Confirmación o error de intervalo',true]],
 sections:[{name:'opt [consulta]',start:1,end:4},{name:'opt [guardar horario]',start:5,end:13},{name:'opt [bloqueo o estado]',start:14,end:21}],
 seqNote:'Al editar un horario usado por citas se conservan día, horas, duración y fecha inicial. La fecha final y el estado respetan las citas pendientes. Horas de Bolivia e intervalos [inicio, fin).'
},
{
 id:'CU-07', title:'Consultar disponibilidad y reservar cita', actor:'Paciente / Recepción autorizada',
 permission:'citas.propias: ficha propia activa y correo verificado. citas.gestionar: paciente activo seleccionado.',
 sources:['README.md#cu-07-disponibilidad-y-reserva','sistema/backend/routes/citas.php','sistema/backend/app/Services/Citas/AppointmentAvailability.php','sistema/backend/app/Services/Citas/AppointmentBooking.php'],
 goals:['Consultar disponibilidad','Reservar cita médica','Consultar / imprimir comprobante'],
 rules:'Reserva hasta 30 días adelante. Revisar no aparta el turno. Revalidar al guardar. Estado inicial programada. Token ligado al actor, 30 minutos; reintento devuelve la misma cita.',
 comm:['UI_disponibilidad / reserva','CitaController +\nservicios de citas','Cita / Horario / Bloqueo / DB'],
 commMessages:[['1: elegir filtros y turno','2: revisar datos','3: reservar y ver comprobante'],['1.1: index() + slots()','2.1: review() + token cifrado','3.1: store() + book()'],['3.2: lock médico y paciente','3.3: revalidar; cita + auditoría','3.4: COMMIT / devolver cita']],
 commNote:'Servicios: AppointmentAvailability y AppointmentBooking. PostgreSQL impide cruces del mismo médico y paciente. Un reenvío del mismo token devuelve un solo comprobante.',
 nav:[['Portada / Menú','GET / o /inicio','Sacar ticket / Reservar cita'],['Acceso / Verificación','GET /login o /verificar-correo','CU-01 / verificación de CU-03'],['Disponibilidad','GET /citas','Especialidad, médico y fecha'],['Revisar reserva','GET /citas/revisar','Datos + token; aún no aparta'],['Guardar reserva','POST /citas','Solo token; revalidación final'],['Comprobante','GET /citas/{cita}/comprobante','Código CIT-ID; imprimir / PDF']],
 navEdges:[[0,1,'/reservar [sin sesión]'],[0,2,'[sesión habilitada]'],[1,2,'[acceso y verificación requeridos]'],[2,3,'Revisar turno disponible'],[3,2,'Volver sin reservar'],[3,4,'Reservar cita'],[4,5,'[reserva guardada / reintento]'],[4,2,'[turno ocupado / revisión inválida]'],[5,2,'Consultar disponibilidad']],
 navNote:'Recepción puede reservar a pacientes sin cuenta y no exige verificación de su correo. Un paciente solo reserva su ficha vinculada. La consulta del comprobante exige propiedad o permiso de gestión.',
 participants:['Paciente / Recepción','V.Disponibilidad / Reserva','CitaController','AppointmentAvailability','AppointmentBooking','Modelos / PostgreSQL'],
 messages:[[0,1,'Elegir paciente si aplica, especialidad y fecha'],[1,2,'GET /citas; validar filtros y acceso'],[2,4,'patient(actor, paciente?)'],[2,3,'slots(especialidad, fecha, médico?, paciente)'],[3,5,'Consultar agenda activa, bloqueos y citas'],[3,2,'Turnos futuros completos y disponibles',true],[2,1,'Mostrar turnos',true],[0,1,'Revisar turno elegido'],[1,2,'GET /citas/revisar'],[2,4,'review(actor, datos); revalidar turno'],[4,3,'slots(...); comprobar turno exacto'],[4,2,'Datos revisados y token cifrado',true],[2,1,'Mostrar revisión; turno no apartado',true],[0,1,'Confirmar reserva'],[1,2,'POST /citas con token'],[2,4,'book(actor, token)'],[4,5,'BEGIN; lock médico y después paciente'],[4,5,'Revalidar actor y ficha; buscar token existente'],[4,3,'slots(...); revalidar disponibilidad actual'],[4,5,'Insertar cita programada, código CIT-ID y auditoría'],[5,4,'COMMIT o exclusión por cruce',true],[4,2,'Cita creada o la misma cita del reintento',true],[2,1,'Redirigir a comprobante autorizado',true]],
 sections:[{name:'opt [consultar disponibilidad]',start:1,end:7},{name:'opt [revisar turno]',start:8,end:13},{name:'opt [guardar reserva / recuperar reintento]',start:14,end:23}],
 seqNote:'Token ya usado: devolver la misma cita sin insertar otra auditoría. Si el turno se ocupa, expira la revisión o hay cruce: no guardar y volver a consultar. Estado programada no equivale a CU-10 confirmada.'
}
];

const nodes=[]; const diagrams=[];
const node=(id,name,type,extra={})=>{nodes.push({id,name,type,...extra});return id;};
const place=(ref,x,y,w,h,extra={})=>({ref,x,y,w,h,...extra});
const edge=(from,to,label,type='Association',extra={})=>({from,to,label,type,...extra});
function add(c,kind,type,placements,edges,note,extra={}){
 const id=c.id.toLowerCase().replace('-','')+'-'+kind;
 const labels={casos:'Diagrama de casos de uso',comunicacion:'Diagrama de comunicación',navegacion:'Diagrama de navegación',secuencia:'Diagrama de secuencia'};
 diagrams.push({id,caseId:c.id,caseTitle:c.title,title:labels[kind],kind,type,placements,edges,note,sources:c.sources,permission:c.permission,...extra});
}
for(const c of cases){
 const prefix=c.id.toLowerCase().replace('-','');
 const ucActor=node(prefix+'-actor',c.actor,'Actor');
 const system=node(prefix+'-limite','Clínica Vida Salud','Boundary');
 const rules=node(prefix+'-reglas',c.permission+'\n\n'+c.rules,'Note');
 const goals=c.goals.map((x,i)=>node(prefix+'-objetivo-'+i,x,'UseCase'));
 const up=[place(system,230,40,950,Math.max(650,goals.length*125+70),{background:true}),place(ucActor,40,265,100,120),place(rules,850,135,280,350),...goals.map((g,i)=>place(g,390,95+i*125,350,90))];
 add(c,'casos','Use Case',up,goals.map(g=>edge(ucActor,g,'')),c.rules,{referencePage:3});
 const cp=c.comm.map((s,i)=>node(prefix+'-com-'+i,s,'Object',{stereotype:['boundary','control','entity'][i]}));
 const ca=node(prefix+'-com-actor',c.actor,'Actor');
 const cn=node(prefix+'-com-nota',c.commNote,'Note');
 const positions=[place(ca,55,300,80,110),...cp.map((p,i)=>place(p,390+i*425,320,80,80)),place(cn,285,525,1070,125)];
 const commEdges=[edge(ca,cp[0],c.commMessages[0].join('\n'),'Association',{messageGroup:0}),edge(cp[0],cp[1],c.commMessages[1].join('\n'),'Association',{messageGroup:1}),edge(cp[1],cp[2],c.commMessages[2].join('\n'),'Association',{messageGroup:2})];
 add(c,'comunicacion','Analysis',positions,commEdges,c.commNote,{referencePage:6});
 const np=c.nav.map((v,i)=>node(prefix+'-nav-'+i,v[0],'Class',{stereotype:'boundary',ops:[v[1],v[2]]}));
 const na=node(prefix+'-nav-actor',c.actor,'Actor');
 const nn=node(prefix+'-nav-nota',c.navNote,'Note');
 const coords=[[240,100],[650,100],[1060,100],[240,430],[650,430],[1060,430]];
 const nplaces=[place(na,45,105,90,125),...np.map((p,i)=>place(p,...coords[i],295,145)),place(nn,265,750,1040,125)];
 const nedges=[edge(na,np[0],'abrir'),...c.navEdges.map(([a,b,t])=>edge(np[a],np[b],t,'Dependency',{navFrom:a,navTo:b}))];
 add(c,'navegacion','Logical',nplaces,nedges,c.navNote,{referencePage:14});
 const sp=c.participants.map((name,i)=>node(prefix+'-seq-'+i,name,i===0?'Actor':'Object'));
 const sn=node(prefix+'-seq-nota',c.seqNote,'Note');
 const height=160+c.messages.length*52;
 const splaces=sp.map((p,i)=>{const column=c.id==='CU-07'?(i===3?4:i===4?3:i):i;return place(p,45+column*250,65,205,height);});
 splaces.push(place(sn,70,height+160,sp.length*250-100,140));
 for(const [i,section] of c.sections.entries()){
   const f=node(prefix+'-frag-'+i,section.name,'InteractionFragment',{fragment:true});
   splaces.push(place(f,20,180+(section.start-1)*52,sp.length*250+20,(section.end-section.start+1)*52+15,{background:true}));
 }
 const sedges=c.messages.map(([a,b,label,ret],i)=>edge(sp[a],sp[b],label,'Sequence',{sequence:i+1,return:!!ret}));
 add(c,'secuencia','Sequence',splaces,sedges,c.seqNote,{referencePage:9,sections:c.sections});
}
const model={title:'Clínica Vida Salud - CU-03 a CU-07',version:1,reference:'todos los diagramas.pdf, páginas 3, 6, 9 y 14',scope:'Cinco casos implementados; cuatro diagramas por caso. Fuentes: código y reglas vigentes al 23/10/2026.',cases:cases.map(({id,title,permission,rules,sources})=>({id,title,permission,rules,sources})),nodes,diagrams};
for(const d of diagrams){
 const ids=new Set(d.placements.map(p=>p.ref));
 for(const e of d.edges)if(!ids.has(e.from)||!ids.has(e.to))throw Error('Conector fuera de diagrama '+d.id);
}
fs.writeFileSync(path.join(dir,'modelo.json'),JSON.stringify(model,null,2)+'\n');
console.log(JSON.stringify({cases:cases.length,diagrams:diagrams.length,nodes:nodes.length}));
