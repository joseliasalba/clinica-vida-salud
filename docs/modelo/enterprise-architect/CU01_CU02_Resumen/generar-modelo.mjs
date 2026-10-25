import fs from 'node:fs';
import path from 'node:path';
import {fileURLToPath} from 'node:url';

const dir = path.dirname(fileURLToPath(import.meta.url));
const cases = JSON.parse(fs.readFileSync(path.join(dir,'casos.json'),'utf8'));

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
 const coords=c.id==='CU-01'?[[240,100],[650,100],[1060,100],[1060,430],[650,430],[240,430]]:[[240,100],[650,100],[1060,100],[240,430],[650,430],[1060,430]];
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
const model={title:'Clínica Vida Salud - CU-01 y CU-02',version:1,reference:'todos los diagramas.pdf, páginas 3, 6, 9 y 14',scope:'Dos casos implementados; cuatro diagramas por caso. Fuentes: código y reglas vigentes al 24/10/2026.',cases:cases.map(({id,title,permission,rules,sources})=>({id,title,permission,rules,sources})),nodes,diagrams};
for(const d of diagrams){
 const ids=new Set(d.placements.map(p=>p.ref));
 for(const e of d.edges)if(!ids.has(e.from)||!ids.has(e.to))throw Error('Conector fuera de diagrama '+d.id);
}
fs.writeFileSync(path.join(dir,'modelo.json'),JSON.stringify(model,null,2)+'\n');
console.log(JSON.stringify({cases:cases.length,diagrams:diagrams.length,nodes:nodes.length}));
