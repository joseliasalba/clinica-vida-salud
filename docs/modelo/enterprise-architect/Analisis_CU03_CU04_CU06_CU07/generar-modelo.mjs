import fs from 'node:fs';
import path from 'node:path';
import {fileURLToPath} from 'node:url';
const dir=path.dirname(fileURLToPath(import.meta.url));
// Clases conceptuales de análisis; las fuentes indican su realización actual.
const definitions=[
 {id:'CU-03',title:'Registrarse como paciente',actor:'Visitante / Paciente',
 permission:'Registro público; verificar correo exige sesión de la misma cuenta y enlace firmado vigente.',
 sources:['docs/casos-de-uso/CU-03/especificacion.md','sistema/backend/app/Services/Pacientes/PatientSelfRegistration.php','sistema/backend/app/Http/Controllers/Pacientes/RegistrationController.php','sistema/backend/app/Http/Controllers/Auth/VerificationController.php'],
 boundary:['V.Registro y verificación',['mostrarRegistro(): Vista','enviarDatos(): Solicitud','mostrarAvisoCorreo(): Vista','solicitarReenvio(): Solicitud','abrirEnlaceFirmado(): Solicitud']],
 control:['C.Autorregistro de paciente',['validarDatos(): Resultado','registrarCuentaYFicha(): Cuenta','asignarRolPaciente(): void','solicitarCorreoTrasCommit(): void','verificarCorreoPropio(): void','limitarReenvio(): void']],
 entities:[['E.Cuenta',['id: bigint','nombre_usuario: string','correo: string','email_verified_at: datetime?','activo: bool'],['verificarCorreo(): void','comprobarIdentidad(): bool']],['E.Paciente',['id: bigint','codigo: string','usuario_id: bigint?','ci: string?','activo: bool'],['identificarFicha(): string','vincularCuentaNueva(): void']],['E.Asignación de rol',['usuario_id: bigint','rol_id: bigint','activo: bool','fecha_asignacion: datetime'],['asignarRolPacienteActivo(): void']]],
 note:'Realización: RegistrationController + PatientSelfRegistration y VerificationController. Cuenta y ficha se crean en una transacción, sin vincular fichas previas por coincidencia. Asignación de rol es un concepto persistido con Query Builder, no un modelo Eloquent adicional. El correo se solicita tras COMMIT; un fallo no elimina la cuenta. El registro no reserva citas.'},
 {id:'CU-04',title:'Registrar y gestionar pacientes en recepción',actor:'Recepción / Gestor autorizado',
 permission:'Sesión vigente y permiso efectivo pacientes.gestionar.',
 sources:['docs/casos-de-uso/CU-04/especificacion.md','sistema/backend/app/Http/Controllers/Pacientes/PacienteController.php','sistema/backend/app/Services/Pacientes/PatientRegistration.php','sistema/backend/app/Models/Paciente.php'],
 boundary:['V.Fichas de pacientes',['mostrarListado(): Vista','buscarYFiltrar(): Solicitud','mostrarFicha(): Vista','mostrarFormulario(): Vista','enviarAltaOEdicion(): Solicitud']],
 control:['C.Gestión de pacientes',['autorizarGestion(): void','consultarFichas(): Coleccion','validarDatosYCI(): void','guardarFicha(): Paciente','comprobarVersion(): void','cambiarEstadoEnEdicion(): void']],
 entities:[['E.Paciente',['id: bigint','codigo: string','ci: string?','nombres: string','apellidos: string','activo: bool'],['identificarFicha(): string','obtenerVersionEdicion(): string','actualizarDatosAdministrativos(): void']]],
 note:'Realización: PacienteController + PatientRegistration; entidad: Paciente. La versión de edición es un HMAC calculado, no otra columna. Alta y edición son transaccionales; edición bloquea ficha y comprueba versión. CI normalizado único incluso entre inactivos. Cambiar estado conserva código, cuenta e historial. No hay borrado físico ni creación de historia clínica.'},
 {id:'CU-06',title:'Configurar horarios de atención y bloqueos',actor:'Administrador autorizado',
 permission:'Sesión vigente y permiso efectivo horarios.gestionar.',
 sources:['sistema/backend/app/Http/Controllers/Horarios/HorarioController.php','sistema/backend/app/Http/Controllers/Horarios/IndisponibilidadController.php','sistema/backend/app/Services/Horarios/ScheduleManagement.php','sistema/backend/app/Models/HorarioAtencion.php','sistema/backend/app/Models/IndisponibilidadMedico.php'],
 boundary:['V.Horarios y bloqueos',['mostrarHorarios(): Vista','mostrarBloqueos(): Vista','mostrarFormulario(): Vista','enviarAltaOEdicion(): Solicitud','confirmarEstado(): Solicitud']],
 control:['C.Configuración de agenda',['autorizarGestion(): void','guardarHorario(): Horario','guardarBloqueo(): Bloqueo','validarVersionYCruces(): void','protegerCitasPendientes(): void','cambiarEstado(): void']],
 entities:[['E.Médico',['id: bigint','codigo: string','activo: bool'],['identificarProfesional(): string']],['E.Horario de atención',['medico_id: bigint','dia_semana: int','franja: intervalo','vigencia: intervalo','duracion_turno: minutos','activo: bool'],['obtenerVersion(): string','delimitarTurnosCompletos(): Coleccion']],['E.Bloqueo de agenda',['medico_id: bigint','periodo: intervalo','motivo: string','activo: bool'],['obtenerVersion(): string','delimitarIndisponibilidad(): Intervalo']],['E.Cita médica',['medico_id: bigint','paciente_id: bigint','periodo: intervalo','estado: string'],['identificarOcupacionPendiente(): Intervalo']]],
 note:'Realización: HorarioController e IndisponibilidadController + ScheduleManagement. Médico, HorarioAtencion, IndisponibilidadMedico y CitaMedica son entidades existentes. Franja, vigencia y periodo resumen sus campos temporales; no son columnas nuevas. Las operaciones expresan responsabilidades de análisis, realizadas también por servicios. Bloquear primero médico; validar versión, cruces y cobertura de citas. CU-06 no cancela ni reprograma.'},
 {id:'CU-07',title:'Consultar disponibilidad y reservar cita',actor:'Paciente / Recepción autorizada',
 permission:'citas.propias: ficha propia activa y correo verificado. citas.gestionar: paciente activo seleccionado.',
 sources:['sistema/backend/app/Http/Controllers/Citas/CitaController.php','sistema/backend/app/Services/Citas/AppointmentAvailability.php','sistema/backend/app/Services/Citas/AppointmentBooking.php','sistema/backend/app/Models/CitaMedica.php'],
 boundary:['V.Disponibilidad y reserva',['mostrarFiltros(): Vista','consultarTurnos(): Solicitud','mostrarRevision(): Vista','confirmarReserva(): Solicitud','mostrarComprobante(): Vista']],
 control:['C.Consulta y reserva de citas',['resolverPacienteAutorizado(): Paciente','consultarDisponibilidad(): Coleccion','revisarTurnoSinRetener(): Revision','emitirTokenCifrado(): string','reservarIdempotente(): Cita','autorizarComprobante(): void']],
 entities:[['E.Paciente',['id: bigint','usuario_id: bigint?','activo: bool'],['identificarTitular(): bigint','comprobarFichaActiva(): bool']],['E.Agenda médica',['medico: Medico','especialidades: Coleccion','horarios: Coleccion','bloqueos: Coleccion','citas: Coleccion'],['delimitarTurnosDisponibles(): Coleccion','excluirCruces(): Coleccion']],['E.Cita médica',['codigo: string','paciente_id: bigint','medico_id: bigint','horario_id: bigint','periodo: intervalo','estado: string'],['identificarReserva(): string','recuperarReintento(): Cita']],['E.Registro de cambio de cita',['cita_id: bigint','actor: Usuario','accion: string','fecha: datetime'],['registrarAltaConfirmada(): void']]],
 note:'Realización del control: CitaController + AppointmentAvailability + AppointmentBooking. Agenda médica agrupa Medico, Especialidad, HorarioAtencion, IndisponibilidadMedico y citas: concepto de análisis, no un modelo Eloquent nuevo. Registro de cambio representa cambios_cita (Query Builder). Revisar no aparta turno. Token cifrado ligado al actor, 30 min; reserva bloquea médico y paciente, revalida y guarda cita programada + auditoría de forma atómica. Reintento devuelve la misma cita. Comprobante solo para titular o gestor.'}
];
const nodes=[],diagrams=[];
for(const c of definitions){
 const prefix=c.id.toLowerCase().replace('-','');
 const add=(suffix,name,type,extra={})=>{const id=prefix+'-'+suffix;nodes.push({id,name,type,...extra});return id;};
 const actor=add('actor',c.actor,'Actor');
 const boundary=add('vista',c.boundary[0],'Class',{stereotype:'boundary',ops:c.boundary[1]});
 const control=add('control',c.control[0],'Class',{stereotype:'control',ops:c.control[1]});
 const entities=c.entities.map(([name,attrs,ops],i)=>add('entidad-'+i,name,'Class',{stereotype:'entity',attrs,ops}));
 const rows=Math.max(1,entities.length),height=rows*245;
 const note=add('nota',c.note,'Note');
 const y=Math.max(80,Math.floor(height/2)-140);
 const placements=[{ref:actor,x:20,y:y+90,w:95,h:130},{ref:boundary,x:190,y,w:300,h:280},{ref:control,x:580,y:y-20,w:330,h:320},...entities.map((ref,i)=>({ref,x:1010,y:40+i*245,w:380,h:205})),{ref:note,x:190,y:Math.max(height,y+320)+100,w:1200,h:170}];
 const edges=[{from:actor,to:boundary,label:'interactúa',type:'Association'},{from:boundary,to:control,label:'solicita',type:'Association'},...entities.map(to=>({from:control,to,label:'',type:'Association'}))];
 diagrams.push({id:prefix+'-clases-analisis',caseId:c.id,caseTitle:c.title,title:'Diagrama de clases de análisis',type:'Logical',kind:'clases-analisis',referencePage:6,permission:c.permission,note:c.note,sources:c.sources,placements,edges});
}
for(const c of definitions)for(const source of c.sources)if(!fs.existsSync(path.resolve(dir,'../../../..',source)))throw Error('Fuente inexistente: '+source);
fs.writeFileSync(path.join(dir,'modelo.json'),JSON.stringify({title:'Clínica Vida Salud - Clases de análisis CU-03 CU-04 CU-06 CU-07',version:1,date:'2026-10-24',cases:definitions.map(c=>({id:c.id,title:c.title,permission:c.permission,rules:c.note,sources:c.sources})),nodes,diagrams},null,2)+'\n');
console.log(JSON.stringify({diagrams:diagrams.length,elements:nodes.length,classes:nodes.filter(n=>n.type==='Class').length}));
