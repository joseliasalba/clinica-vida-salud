import fs from 'node:fs';
import path from 'node:path';
import {fileURLToPath} from 'node:url';
const dir=path.dirname(fileURLToPath(import.meta.url));
const source=JSON.parse(fs.readFileSync(path.join(dir,'modelo.json'),'utf8'));
const general=path.join(dir,'../General_Proyecto/modelo.json');
const data=JSON.parse(fs.readFileSync(general,'utf8'));
for(const [id,name,responsibility,limit] of source.roles){
 const text='<p><b>Responsabilidad:</b> '+responsibility+'</p><p><b>Límite de acceso:</b> '+limit+'</p>';
 const actor=data.actors.find(a=>a.id===id);actor.name=name;actor.notes=text;
 const n=data.nodes.find(n=>n.id==='act_'+id);n.name=name;n.notes=text;
 const noteId='limite_actor_'+id;
 const old=data.nodes.find(n=>n.id===noteId);
 if(old)old.name=text;else data.nodes.push({id:noteId,name:text,type:'Note'});
}
const d=data.diagrams.find(d=>d.id==='01-lista-actores');
d.placements=source.roles.flatMap(([id],i)=>[{ref:'act_'+id,x:70,y:95+i*270,w:130,h:160},{ref:'limite_actor_'+id,x:330,y:70+i*270,w:950,h:215}]);
d.edges=source.roles.map(([id])=>({from:'act_'+id,to:'limite_actor_'+id,type:'NoteLink',label:''}));
d.notes='Cuatro roles de negocio según la tabla acordada. Visitante y correo se conservan como auxiliares en otras vistas. CU-11 pendiente, limitado al profesional vinculado y sujeto a política administrativa explícita.';
fs.writeFileSync(general,JSON.stringify(data,null,2)+'\n');
console.log('Fuente del diagrama general de actores actualizada.');
