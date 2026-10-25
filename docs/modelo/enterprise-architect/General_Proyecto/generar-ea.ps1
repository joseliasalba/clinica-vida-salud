param([switch]$Recrear)
$ErrorActionPreference='Stop'
$dir=$PSScriptRoot
$data=Get-Content -LiteralPath (Join-Path $dir 'modelo.json') -Raw -Encoding UTF8 | ConvertFrom-Json
$dest=Join-Path $dir 'Clinica_Vida_Salud_Modelo_General.eapx'
$app=[Runtime.InteropServices.Marshal]::GetActiveObject('EA.App')
$repo=$app.Repository
if($repo.ConnectionString){throw 'EA tiene un proyecto abierto. Guardarlo y cerrarlo antes de generar; no se modificó ese proyecto.'}
if(Test-Path -LiteralPath $dest){
 if(!$Recrear){throw 'Ya existe el modelo generado. Use -Recrear solo para reconstruir conservando un respaldo.'}
 Copy-Item -LiteralPath $dest -Destination ($dest+'.bak') -Force
}
Copy-Item -LiteralPath 'C:/Program Files (x86)/Sparx Systems/EA Trial/EABase.eapx' -Destination $dest -Force
$repo.SuppressEADialogs=$true
if(!$repo.OpenFile($dest)){throw 'No se pudo abrir el archivo nuevo.'}
$app.Visible=$true
$root=$repo.Models.AddNew($data.title,'Package');if(!$root.Update()){throw 'No se pudo crear la raíz'}
$root.Notes='Modelo del proyecto al 24/10/2026. Fuente: revisión 7dc59ab. CU-11 y CU-13 a CU-17 pendientes. CU-08/09/10/12 de Lucas integrados. Cada caso UML es único y se reutiliza entre vistas.'
[void]$root.Update()
$catalog=$root.Packages.AddNew('00 Catálogo reutilizable','Package');[void]$catalog.Update()
$actorPkg=$catalog.Packages.AddNew('Actores','Package');[void]$actorPkg.Update()
$notesPkg=$catalog.Packages.AddNew('Notas y límites','Package');[void]$notesPkg.Update()
$elements=@{};$domain=@{};$phases=@{}
foreach($p in $data.packages){
 $pk=$catalog.Packages.AddNew($p.name,'Package');$pk.Notes=$p.notes;[void]$pk.Update()
 $domain[$p.id]=$pk;$elements['pkg_'+$p.id]=$pk.Element
 $el=$pk.Element;$el.Alias='pkg_'+$p.id;$el.Status=if($p.status -eq 'propuesto'){'Propuesto'}else{'Analizado'};[void]$el.Update()
}
foreach($n in $data.nodes){
 if($n.type -eq 'Package'){continue}
 $owner=if($n.type -eq 'UseCase'){$domain[$n.owner]}elseif($n.type -eq 'Actor'){$actorPkg}else{$notesPkg}
 $e=$owner.Elements.AddNew($n.name,$n.type);if(!$e.Update()){throw ('No se creó elemento '+$n.id)}
 $e.Alias=$n.id
 if($n.notes){$e.Notes=$n.notes}
 if($n.status){$e.Status=$n.status}
 if($n.stereotype){$e.Stereotype=$n.stereotype}
 if($n.type -eq 'Note'){$e.Name='';$e.Notes=$n.name}
 if($n.type -eq 'Boundary'){$e.Name=$n.name}
 [void]$e.Update();$elements[$n.id]=$e
}
$edges=@{}
foreach($spec in $data.diagrams){foreach($edge in $spec.edges){
 $key=$edge.from+'|'+$edge.to+'|'+$edge.type+'|'+$edge.stereotype
 if($edges.ContainsKey($key)){continue}
 $c=$elements[$edge.from].Connectors.AddNew('',$edge.type);$c.SupplierID=$elements[$edge.to].ElementID
 if($edge.stereotype){$c.Stereotype=$edge.stereotype}
 if($edge.type -eq 'Association'){$c.Direction='Unspecified';$c.ClientEnd.Navigable='Non-Navigable';$c.SupplierEnd.Navigable='Non-Navigable'}
 if($edge.type -in @('Dependency','Abstraction','UseCase')){$c.Direction='Source -> Destination'}
 if(!$c.Update()){throw ('No se creó conector '+$key+': '+$c.GetLastError())}
 $edges[$key]=$c.ConnectorID
}}
$images=Join-Path $dir 'diagramas';[void][IO.Directory]::CreateDirectory($images)
$manifest=@()
foreach($spec in $data.diagrams){
 if(!$phases.ContainsKey($spec.phase)){$pk=$root.Packages.AddNew($spec.phase,'Package');[void]$pk.Update();$phases[$spec.phase]=$pk}
 $d=$phases[$spec.phase].Diagrams.AddNew($spec.title,$spec.type)
 $d.ShowDetails=0
 $d.Notes=$spec.notes+[Environment]::NewLine+'Fuente: '+($data.sources -join '; ')+[Environment]::NewLine+'Fecha: '+$data.date
 $d.ExtendedStyle='UseAlias=0;HideAtts=1;HideOps=1;HideEStereo=0;ShowIcons=0;'
 $d.ShowPackageContents=$false
 $d.StyleEx='ShowDiagramFrame=1;SuppressBrackets=1;'
 [void]$d.Update()
 foreach($p in $spec.placements){
  $n=$data.nodes | Where-Object id -eq $p.ref | Select-Object -First 1
  $o=$d.DiagramObjects.AddNew("l=$($p.x);r=$($p.x+$p.w);t=$(-$p.y);b=$(-($p.y+$p.h));",'')
  $o.ElementID=$elements[$p.ref].ElementID
  $o.Sequence=if($p.background){9999}else{1}
  $o.Style='font=Arial;fontsz=120;UseAlias=0;BCol=16777215;'
  $o.BackgroundColor=if($n.type -eq 'UseCase' -and $n.status -eq 'Pendiente'){13431551}elseif($n.type -eq 'UseCase'){16246735}elseif($n.type -eq 'Actor'){14150399}elseif($n.type -eq 'Package' -and $n.status -eq 'propuesto'){15658751}elseif($n.type -eq 'Package'){15790326}elseif($n.type -eq 'Note'){16777215}else{16777215}
  if($p.background){$o.Style+='ShowPkgContents=0;';$o.BackgroundColor=16185078}
  if(!$o.Update()){throw ('No se colocó '+$p.ref)}
 }
 $d.DiagramLinks.Refresh()
 $wanted=@()
 foreach($edge in $spec.edges){
  $key=$edge.from+'|'+$edge.to+'|'+$edge.type+'|'+$edge.stereotype;$cid=$edges[$key];$wanted+=$cid
  $l=$d.DiagramLinks | Where-Object ConnectorID -eq $cid | Select-Object -First 1
  if(!$l){$l=$d.DiagramLinks.AddNew('','');$l.ConnectorID=$cid}
  $l.IsHidden=$false;$l.LineStyle=1
  [void]$l.Update()
 }
 foreach($l in $d.DiagramLinks){if($l.ConnectorID -notin $wanted){$l.IsHidden=$true;[void]$l.Update()}}
 $repo.SaveDiagram($d.DiagramID)
 [void]$repo.Execute("UPDATE t_diagram SET ShowForeign=0 WHERE Diagram_ID=$($d.DiagramID)")
 $repo.ReloadDiagram($d.DiagramID);$repo.OpenDiagram($d.DiagramID)
 $filename=$spec.id+'.png'
 if(!$repo.GetProjectInterface().PutDiagramImageToFile($d.DiagramGUID,(Join-Path $images $filename),1)){throw ('No se exportó '+$filename)}
 $manifest+=@{id=$spec.id;title=$spec.title;phase=$spec.phase;guid=$d.DiagramGUID;diagramId=$d.DiagramID;image='diagramas/'+$filename;elements=$spec.placements.Count;links=$wanted.Count}
 $repo.CloseDiagram($d.DiagramID)
 Write-Output ('Exportado: '+$spec.id)
}
$manifest | ConvertTo-Json -Depth 8 | Set-Content -LiteralPath (Join-Path $dir 'manifest.json') -Encoding UTF8
@($data.nodes | ForEach-Object {@{id=$_.id;elementId=$elements[$_.id].ElementID;guid=$elements[$_.id].ElementGUID}}) | ConvertTo-Json -Depth 5 | Set-Content -LiteralPath (Join-Path $dir 'elementos.json') -Encoding UTF8
[void]$repo.GetProjectInterface().ExportPackageXMI($root.PackageGUID,11,1,0,1,0,(Join-Path $dir 'Clinica_Vida_Salud_Modelo_General.xmi'))
$repo.OpenDiagram(($manifest | Where-Object id -eq '01-lista-actores').diagramId)
Write-Output ('LISTO: '+$dest)
