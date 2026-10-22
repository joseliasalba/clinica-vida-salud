import fs from 'node:fs';
import path from 'node:path';
import {fileURLToPath} from 'node:url';
import PDFDocument from '../../../.tools/pdf-reader/node_modules/pdfkit/js/pdfkit.js';
const dir=path.dirname(fileURLToPath(import.meta.url));
const model=JSON.parse(fs.readFileSync(path.join(dir,'modelo.json'),'utf8'));
const doc=new PDFDocument({size:'A4',margins:{top:42,bottom:42,left:45,right:45},bufferPages:true,info:{Title:model.title,Author:'Clínica Vida Salud',Subject:'Modelado UML de CU-01 y CU-02, según referencia académica'}});
const output=path.join(dir,'Diagramas_CU01_CU02.pdf');
const stream=fs.createWriteStream(output);doc.pipe(stream);
const W=505;const toc=[];
function title(text,size=15){doc.fillColor('black').font('Times-Bold').fontSize(size).text(text,45,doc.y,{width:W});doc.moveDown(.65);}
function text(s){doc.fillColor('black').font('Times-Roman').fontSize(11).text(s,45,doc.y,{width:W,align:'justify',lineGap:2});doc.moveDown(.65);}
function page(heading){doc.addPage();title(heading);}
function table(headers,rows,widths,{labels=false}={}){
 const draw=(row,head)=>{
  doc.font(head?'Times-Bold':'Times-Roman').fontSize(10);
  const heights=row.map((s,i)=>doc.heightOfString(String(s),{width:widths[i]-12,lineGap:1}));
  const h=Math.max(...heights,12)+12;
  if(doc.y+h>780){doc.addPage();if(!head)draw(headers,true);}
  const y=doc.y;let x=45;
  row.forEach((s,i)=>{doc.save().rect(x,y,widths[i],h).fillAndStroke(head?'#307bb3':labels&&i===0?'#e2e2e2':'#ffffff','#111111').restore();doc.fillColor(head?'white':'black').font(head||labels&&i===0?'Times-Bold':'Times-Roman').fontSize(10).text(String(s),x+6,y+6,{width:widths[i]-12,lineGap:1});x+=widths[i];});doc.y=y+h;
 };
 draw(headers,true);for(const row of rows)draw(row,false);doc.x=45;doc.moveDown();
}
title('CLÍNICA VIDA SALUD',20);title('DIAGRAMAS DE CU-01 Y CU-02',17);
text('CU-01: Iniciar y cerrar sesión.\nCU-02: Administrar usuarios, roles y permisos.\nIncluye la extensión CU-01.1: Recuperar contraseña.');
title('CONTENIDO',14);
text('1. CAPTURA DE REQUISITOS\n   Actores, identificación, prioridad, especificaciones y casos de uso.\n2. ANÁLISIS\n   Paquetes, trazabilidad, comunicación y clases de análisis.\n3. DISEÑO\n   Arquitecturas, secuencias, base de datos, clases, estados, tiempo y navegación.\n4. IMPLEMENTACIÓN\n   Componentes de CU-01 y CU-02.');
text('Formato de referencia: todos los diagramas.pdf (15 páginas). Se conservan el orden de las etapas, las tablas con encabezados azules y los diagramas UML exportados desde Enterprise Architect. La cantidad de páginas se amplía para cubrir ambos casos sin reducir excesivamente los gráficos.');
text('Los diagramas representan la implementación local de Laravel y PostgreSQL. No se incorporan módulos clínicos todavía no implementados. Los tiempos ilustrativos se distinguen de las reglas de espera e inactividad.');
page('CAPTURA DE REQUISITOS');
title('Identificación de los casos de uso',13);
table(['Código','Caso de uso','Actor iniciador'],[['CU-01','Iniciar y cerrar sesión','Usuario con cuenta'],['CU-01.1','Recuperar contraseña','Usuario'],['CU-02','Administrar usuarios, roles y permisos','Gestor autorizado']], [65,250,190]);
title('Prioridad de los casos de uso',13);
table(['Caso de uso','Prioridad','Justificación'],[['CU-01','Alta','Permite acceder a las funciones protegidas.'],['CU-01.1','Alta','Restablece el acceso sin revelar la existencia de la cuenta.'],['CU-02','Alta','Gestiona cuentas y autorización; conserva al menos un gestor efectivo.']], [85,70,350]);
title('Actores y roles',13);
text('Usuario representa a administrador, recepción, médico y paciente cuando acceden al sistema. Gestor autorizado es una cuenta activa que posee usuarios.gestionar efectivo; inicialmente corresponde al administrador. Los roles no sustituyen las comprobaciones de permisos en cada solicitud.');
for(const cu of [
 {id:'CU-01',name:'Iniciar y cerrar sesión',purpose:'Identificar al usuario, permitir el acceso autorizado y finalizar la sesión.',description:'Ingreso por nombre de usuario o correo; verificación de cuenta activa y contraseña; regeneración y cierre seguro de sesión.',actor:'Administrador, recepción, médico o paciente (Usuario).',pre:'Cuenta existente y activa; aplicación y PostgreSQL disponibles.',process:'1. Abrir /login.\n2. Introducir identificador y contraseña.\n3. Validar campos, CSRF y límites de intentos.\n4. Verificar cuenta activa y hash.\n5. Regenerar sesión y redirigir a /inicio.\n6. Mostrar funciones según permisos vigentes.\n7. Al salir mediante POST /logout, invalidar sesión y regenerar CSRF.',post:'Sesión autenticada con funciones autorizadas, o sesión invalidada tras el cierre.',exceptions:'Credenciales inválidas o cuenta inactiva: mensaje genérico. Cinco fallos por identificador + IP: espera de 15 minutos; límite adicional de 30 intentos por minuto por IP. Inactividad de 30 minutos, desactivación o revocación: invalidar acceso. Error de persistencia: sin acceso parcial.'},
 {id:'CU-02',name:'Administrar usuarios, roles y permisos',purpose:'Gestionar cuentas y las funciones habilitadas para cada persona.',description:'Crear y editar usuarios y roles; activar o desactivar cuentas, roles y permisos; asignar varios roles y permisos preservando vínculos históricos.',actor:'Gestor autorizado con usuarios.gestionar efectivo.',pre:'Sesión válida de CU-01; permiso efectivo; tablas y catálogo disponibles.',process:'1. Abrir Administrar acceso.\n2. Listar, buscar y filtrar cuentas.\n3. Crear o editar datos, roles y estado.\n4. Validar unicidad, contraseña y selecciones vigentes.\n5. Revalidar autorización dentro de una transacción con bloqueo PostgreSQL.\n6. Guardar y verificar continuidad de administración.\n7. Confirmar; conservar vínculos retirados como inactivos.',post:'Cuenta, rol, asignaciones o estado de permiso guardados de forma atómica. Siempre queda un gestor efectivo.',exceptions:'Sin sesión: login; sin permiso: 403. Datos duplicados, claves inválidas o roles inactivos: errores sin cambios parciales. Retirar la última administración o la propia: rollback. No se permite desactivar la cuenta propia ni borrar registros.'}
]){
 page(`Especificación del caso de uso ${cu.id}`);
 table(['Campo','Especificación'],[['Caso de uso',`${cu.id} ${cu.name}`],['Propósito',cu.purpose],['Descripción',cu.description],['Actores',cu.actor],['Actor iniciador',cu.actor],['Precondición',cu.pre],['Proceso',cu.process],['Postcondición',cu.post],['Excepciones',cu.exceptions]],[110,395],{labels:true});
}
page('Extensión CU-01.1: Recuperar contraseña');
table(['Campo','Especificación'],[['Propósito','Restablecer el acceso mediante un enlace temporal de un solo uso.'],['Actor iniciador','Usuario que solicita recuperar su contraseña.'],['Precondición','Aplicación y almacenamiento disponibles; envío de correo configurado para entrega real.'],['Proceso','Solicitar enlace con correo; emitir respuesta genérica; procesar en cola; generar token almacenado como hash para cuenta activa; enviar enlace; validar token vigente y clave confirmada; actualizar hash e invalidar enlaces y sesiones anteriores.'],['Postcondición','Contraseña actualizada y retorno al login; no se inicia sesión automáticamente.'],['Excepciones','Correo inexistente/inactivo: misma respuesta pública. Token inválido, consumido o vencido: rechazar. Clave inválida: mostrar validación sin repoblarla.'],['Regla de contraseña','8 a 128 caracteres; confirmación; rechazo del catálogo de claves comunes y de un único carácter repetido.'],['Límite del entorno','La entrega de correo real depende de la configuración SMTP. La cola y el flujo de aplicación no implican por sí solos que un mensaje haya sido entregado.']],[110,395],{labels:true});
let lastPhase='';
for(const d of model.diagrams){
 doc.addPage();
 if(lastPhase!==d.phase){title(d.phase.replace(/^\d+ /,'').toUpperCase(),16);lastPhase=d.phase;}
 title(d.title,13);
 toc.push({title:d.title,page:doc.bufferedPageRange().count});
 const y=doc.y;
 doc.image(path.join(dir,'diagramas',d.id+'.png'),45,y,{fit:[W,690-y],align:'center',valign:'top'});
 doc.font('Times-Roman').fontSize(9).fillColor('#333333').text(`Referencia de formato: página ${d.sourcePage}. Modelo: ${d.id}.`,45,730,{width:W});
 if(d.caption)doc.text(d.caption,45,747,{width:W});
 if(d.id==='19-fisico-bd'){
  page('Diseño físico: diccionario de datos');
  text('Metadatos de PostgreSQL consultados en el entorno local. Se muestran estructuras, no registros de usuarios. Las tablas usuario_rol y rol_permiso resuelven relaciones de muchos a muchos. password_reset_tokens se consulta por correo sin clave foránea a usuarios; jobs es infraestructura de cola.');
  for(const tableName of [...new Set(model.schema.map(c=>c.table_name))]){
   if(doc.y>625)doc.addPage();title(tableName,12);
   const rows=model.schema.filter(c=>c.table_name===tableName).map(c=>{
    const composite=['usuario_rol','rol_permiso'].includes(tableName)&&c.column_name.endsWith('_id');
    const key=composite?'PK / FK':c.column_name==='id'||tableName==='password_reset_tokens'&&c.column_name==='email'?'PK':c.column_name.endsWith('_id')?'FK':'—';
    const meaning={id:'Identificador',nombre_usuario:'Nombre de acceso',correo:'Correo de la cuenta',contrasena_hash:'Hash Argon2id',activo:'Estado del registro',remember_token:'Revocación de sesiones',usuario_id:'Usuario vinculado',rol_id:'Rol vinculado',permiso_id:'Permiso vinculado',email:'Correo para recuperación',token:'Hash del token',payload:'Trabajo serializado',queue:'Nombre de cola',attempts:'Intentos de ejecución',fecha_asignacion:'Inicio de vigencia',fecha_expiracion:'Fin de vigencia'}[c.column_name]||c.column_name.replaceAll('_',' ');
    return [c.column_name,c.data_type,meaning,String(c.character_maximum_length??'—'),c.is_nullable==='YES'?'Sí':'No',key];
   });
   table(['Atributo','Tipo','Descripción','Tamaño','Nulo','Llave'],rows,[120,100,155,50,40,40]);
  }
 }
}
page('TRAZABILIDAD Y OBSERVACIONES DEL MODELO');
text('Los archivos de especificación de docs/casos-de-uso/CU-01, CU-01.1 y CU-02 y el código de sistema/backend son la fuente funcional. El PDF aportado es la referencia de presentación. Se adapta su contenido a la clínica, sin copiar actores o tecnologías de su dominio original.');
text('CU-02 depende de una sesión válida como precondición; no se modela un «include» de login para cada operación. Rol y Permiso son entidades del dominio consultadas con Query Builder, no clases Eloquent separadas. El borrado físico no forma parte de estos casos. El catálogo de permisos se define en código.');
text('La arquitectura física representa el entorno local: navegador, aplicación PHP/Laravel y PostgreSQL en la misma estación. El despliegue productivo y la configuración SMTP quedan fuera de esta representación local. No se incluyen contraseñas, registros personales ni tokens reales.');
table(['Diagrama','Página'],toc.map(x=>[x.title,String(x.page)]),[455,50]);
const range=doc.bufferedPageRange();
for(let i=0;i<range.count;i++){doc.switchToPage(i);doc.font('Times-Roman').fontSize(9).fillColor('#555555').text('Clínica Vida Salud · CU-01 / CU-02',45,804,{lineBreak:false});doc.text(String(i+1),520,804,{lineBreak:false});}
doc.end();await new Promise((r,j)=>{stream.on('finish',r);stream.on('error',j)});
console.log(JSON.stringify({output,pages:range.count,diagrams:model.diagrams.length}));
