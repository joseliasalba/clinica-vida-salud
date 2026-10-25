import fs from 'node:fs';
import path from 'node:path';
import {fileURLToPath} from 'node:url';
import PDFDocument from '../../../../.tools/pdf-reader/node_modules/pdfkit/js/pdfkit.js';
const dir=path.dirname(fileURLToPath(import.meta.url));
const model=JSON.parse(fs.readFileSync(path.join(dir,'modelo.json'),'utf8').replace(/^\uFEFF/,''));
const manifest=JSON.parse(fs.readFileSync(path.join(dir,'manifest.json'),'utf8').replace(/^\uFEFF/,''));
const output=path.resolve(dir,'../../../../output/pdf/Diagramas_Generales_Vida_Salud.pdf');
fs.mkdirSync(path.dirname(output),{recursive:true});
const doc=new PDFDocument({autoFirstPage:false,info:{Title:'Clínica Vida Salud - Actores, casos de uso y paquetes',Author:'Clínica Vida Salud',Subject:'22 diagramas exportados desde Enterprise Architect'}});
const stream=fs.createWriteStream(output);doc.pipe(stream);
const sheets=[];
for(const spec of model.diagrams.toSorted((a,b)=>a.id.localeCompare(b.id))){
 const entry=manifest.find(m=>m.id===spec.id);
 if(!entry)throw Error('Falta manifiesto '+spec.id);
 const file=path.join(dir,entry.image),im=doc.openImage(file);
 const width=Math.max(720,Math.ceil(im.width*.75+90)),height=Math.ceil(im.height*.75+155);
 doc.addPage({size:[width,height],margins:{left:45,right:45,top:30,bottom:25}});
 doc.font('Times-Bold').fontSize(17).fillColor('#111').text(spec.title,45,28,{width:width-90,height:52});
 doc.image(file,45,90,{width:im.width*.75,height:im.height*.75});
 doc.font('Times-Roman').fontSize(9).fillColor('#555').text('Clínica Vida Salud | 24/10/2026',45,height-44,{lineBreak:false});
 doc.text(String(sheets.length+1)+' / '+model.diagrams.length,width-100,height-44,{width:55,align:'right',lineBreak:false});
 doc.outline.addItem(spec.title,{pageNumber:sheets.length});
 sheets.push({page:sheets.length+1,id:spec.id,image:entry.image,width,height});
}
doc.end();await new Promise((resolve,reject)=>{stream.on('finish',resolve);stream.on('error',reject);});
fs.writeFileSync(path.join(dir,'laminas.json'),JSON.stringify(sheets,null,2)+'\n');
console.log(JSON.stringify({output,pages:sheets.length,bytes:fs.statSync(output).size}));
