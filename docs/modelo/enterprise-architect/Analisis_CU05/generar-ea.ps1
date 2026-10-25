param([switch]$Recrear)
$ErrorActionPreference='Stop'
$dir=$PSScriptRoot
$data=Get-Content -LiteralPath (Join-Path $dir 'modelo.json') -Raw -Encoding UTF8 | ConvertFrom-Json
$dest=Join-Path $dir 'Clinica_Vida_Salud_Analisis_CU05.eapx'
try{$app=[Runtime.InteropServices.Marshal]::GetActiveObject('EA.App')}catch{throw 'Abra Enterprise Architect en su pantalla principal antes de generar el modelo.'}
$repo=$app.Repository
if($repo.ConnectionString){
 if($repo.ConnectionString -eq $dest){$d=$repo.GetCurrentDiagram();if($d){$repo.SaveDiagram($d.DiagramID)};$repo.CloseFile()}
 else{throw 'Otro proyecto esta abierto en EA. Guardarlo y cerrarlo antes de generar este modelo independiente.'}
}
if(Test-Path -LiteralPath $dest){if(!$Recrear){throw 'La entrega ya existe. Use -Recrear para conservar respaldo y reconstruir.'};Copy-Item -LiteralPath $dest -Destination ($dest+'.bak') -Force}
Copy-Item -LiteralPath 'C:/Program Files (x86)/Sparx Systems/EA Trial/EABase.eapx' -Destination $dest -Force
$repo.SuppressEADialogs=$true
if(!$repo.OpenFile($dest)){throw 'No se pudo abrir el nuevo proyecto.'}
$app.Visible=$true
$root=$repo.Models.AddNew($data.title,'Package');[void]$root.Update()
$root.Notes=$data.notes+[Environment]::NewLine+'Fuentes: '+($data.sources -join '; ');[void]$root.Update()
$pkg=$root.Packages.AddNew('CU-05 Gestionar medicos y especialidades','Package');$pkg.Notes=$data.permission;[void]$pkg.Update()
$elements=@{}
foreach($n in $data.nodes){
 $e=$pkg.Elements.AddNew($n.name,$n.type);[void]$e.Update()
 $e.Alias=$n.id
 if($n.stereotype){$e.Stereotype=$n.stereotype}
 $e.Notes=$data.notes
 [void]$e.Update()
 foreach($a in $n.attrs){$pair=$a -split ':\s*',2;$attr=$e.Attributes.AddNew($pair[0],$pair[1]);$attr.Visibility='Public';[void]$attr.Update()}
 foreach($o in $n.ops){$pair=$o -split '\(\):\s*',2;$op=$e.Methods.AddNew($pair[0],$pair[1]);$op.Visibility='Public';[void]$op.Update()}
 $elements[$n.id]=$e
}
$d=$pkg.Diagrams.AddNew('CU-05 Diagrama de clases de análisis','Logical')
$d.ShowDetails=0;$d.ShowPackageContents=$false
$d.ExtendedStyle='UseAlias=0;HideAtts=0;HideOps=0;HideEStereo=0;ShowIcons=0;'
$d.Notes=$data.notes+[Environment]::NewLine+$data.permission
[void]$d.Update()
foreach($n in $data.nodes){
 $o=$d.DiagramObjects.AddNew("l=$($n.x);r=$($n.x+$n.w);t=$(-$n.y);b=$(-($n.y+$n.h));",'')
 $o.ElementID=$elements[$n.id].ElementID
 $o.Style='font=Arial;fontsz=120;UseAlias=0;'
 $o.BackgroundColor=14150399
 if($n.type -eq 'Class'){$o.Style+='UCRect=1;';$o.ShowPublicOperations=$true;$o.ShowPublicAttributes=$true}
 [void]$o.Update()
}
$links=@()
foreach($edge in $data.edges){
 $c=$elements[$edge.from].Connectors.AddNew($edge.label,$edge.type)
 $c.SupplierID=$elements[$edge.to].ElementID
 $c.Direction='Unspecified';$c.ClientEnd.Navigable='Non-Navigable';$c.SupplierEnd.Navigable='Non-Navigable'
 if($edge.fromCardinality){$c.ClientEnd.Cardinality=$edge.fromCardinality}
 if($edge.toCardinality){$c.SupplierEnd.Cardinality=$edge.toCardinality}
 if(!$c.Update()){throw ('No se guardo la relacion '+$edge.from+' '+$edge.to)}
 $links+=@{id=$c.ConnectorID;definition=$edge}
}
$d.DiagramLinks.Refresh()
foreach($entry in $links){
 $l=$d.DiagramLinks | Where-Object ConnectorID -eq $entry.id | Select-Object -First 1
 if(!$l){$l=$d.DiagramLinks.AddNew('','');$l.ConnectorID=$entry.id}
 $l.IsHidden=$false
 if($entry.definition.path){$l.LineStyle=3;$l.Path=$entry.definition.path}else{$l.LineStyle=1}
 [void]$l.Update()
}
[void]$repo.Execute("UPDATE t_diagram SET ShowForeign=0 WHERE Diagram_ID=$($d.DiagramID)")
$repo.SaveDiagram($d.DiagramID);$repo.ReloadDiagram($d.DiagramID);$repo.OpenDiagram($d.DiagramID)
$images=Join-Path $dir 'diagramas';[void][IO.Directory]::CreateDirectory($images)
$image=Join-Path $images 'cu05-clases-analisis.png'
if(!$repo.GetProjectInterface().PutDiagramImageToFile($d.DiagramGUID,$image,1)){throw 'No se exporto la imagen.'}
$manifest=@{id='cu05-clases-analisis';caseId='CU-05';title=$d.Name;diagramId=$d.DiagramID;guid=$d.DiagramGUID;image='diagramas/cu05-clases-analisis.png';elements=$data.nodes.Count;relationships=$data.edges.Count;visibleNotes=0}
$manifest | ConvertTo-Json -Depth 6 | Set-Content -LiteralPath (Join-Path $dir 'manifest.json') -Encoding UTF8
@($data.nodes | ForEach-Object {@{id=$_.id;elementId=$elements[$_.id].ElementID;guid=$elements[$_.id].ElementGUID}}) | ConvertTo-Json | Set-Content -LiteralPath (Join-Path $dir 'elementos.json') -Encoding UTF8
[void]$repo.GetProjectInterface().ExportPackageXMI($root.PackageGUID,11,1,0,1,0,(Join-Path $dir 'Clinica_Vida_Salud_Analisis_CU05.xmi'))
Write-Output ('LISTO: '+$dest)
