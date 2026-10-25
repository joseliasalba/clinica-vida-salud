$ErrorActionPreference='Stop'
$dir=Join-Path $PSScriptRoot '../General_Proyecto'
$data=Get-Content (Join-Path $dir 'modelo.json') -Raw -Encoding UTF8 | ConvertFrom-Json
$manifest=Get-Content (Join-Path $dir 'manifest.json') -Raw -Encoding UTF8 | ConvertFrom-Json
$mapping=@(Get-Content (Join-Path $dir 'elementos.json') -Raw -Encoding UTF8 | ConvertFrom-Json)
$repo=([Runtime.InteropServices.Marshal]::GetActiveObject('EA.App')).Repository
if($repo.ConnectionString){
 [xml]$open=$repo.SQLQuery('SELECT Diagram_ID FROM t_diagram')
 foreach($row in $open.EADATA.Dataset_0.Data.Row){$repo.SaveDiagram([int]$row.Diagram_ID)}
 $repo.CloseFile()
 if($repo.Models.Count -gt 0){throw 'No se pudo cerrar el proyecto abierto.'}
}
$dest=[IO.Path]::GetFullPath((Join-Path $dir 'Clinica_Vida_Salud_Modelo_General.eapx'))
Copy-Item -LiteralPath $dest -Destination ($dest+'.bak') -Force
if(!$repo.OpenFile($dest)){throw 'No se abrió el modelo general.'}
$entry=$manifest | Where-Object id -eq '01-lista-actores' | Select-Object -First 1
$spec=$data.diagrams | Where-Object id -eq '01-lista-actores' | Select-Object -First 1
$diagram=$repo.GetDiagramByID($entry.diagramId);$repo.CloseDiagram($diagram.DiagramID)
$owner=$repo.GetPackageByID($diagram.PackageID);$elements=@{}
foreach($p in $spec.placements){
 $n=$data.nodes | Where-Object id -eq $p.ref | Select-Object -First 1
 $map=$mapping | Where-Object id -eq $n.id | Select-Object -First 1
 if($map){$e=$repo.GetElementByID($map.elementId)}else{
  $e=$owner.Elements.AddNew('',$n.type);[void]$e.Update()
  $mapping+=@{id=$n.id;elementId=$e.ElementID;guid=$e.ElementGUID}
 }
 if($n.type -eq 'Note'){$e.Name='';$e.Notes=$n.name}else{$e.Name=$n.name;$e.Notes=$n.notes}
 [void]$e.Update();$elements[$n.id]=$e
}
# Quitar solo las representaciones del diagrama; conservar actores auxiliares
# y sus relaciones existentes en las demás vistas del modelo.
for($i=$diagram.DiagramObjects.Count-1;$i -ge 0;$i--){$diagram.DiagramObjects.DeleteAt($i,$false)}
$diagram.DiagramObjects.Refresh()
foreach($p in $spec.placements){
 $o=$diagram.DiagramObjects.AddNew("l=$($p.x);r=$($p.x+$p.w);t=$(-$p.y);b=$(-($p.y+$p.h));",'')
 $o.ElementID=$elements[$p.ref].ElementID;$o.Style='font=Arial;fontsz=120;UseAlias=0;';$o.BackgroundColor=16777215;[void]$o.Update()
}
$wanted=@()
foreach($edge in $spec.edges){
 $a=$elements[$edge.from];$b=$elements[$edge.to]
 $c=$a.Connectors | Where-Object {$_.Type -eq 'NoteLink' -and $_.SupplierID -eq $b.ElementID} | Select-Object -First 1
 if(!$c){$c=$a.Connectors.AddNew('','NoteLink');$c.SupplierID=$b.ElementID;[void]$c.Update()}
 $wanted+=$c.ConnectorID
}
$diagram.DiagramLinks.Refresh()
foreach($l in $diagram.DiagramLinks){$l.IsHidden=$l.ConnectorID -notin $wanted;[void]$l.Update()}
$diagram.Notes=$spec.notes;$diagram.ExtendedStyle='UseAlias=0;HideAtts=1;HideOps=1;ShowIcons=0;';[void]$diagram.Update()
[void]$repo.Execute("UPDATE t_diagram SET ShowForeign=1 WHERE Diagram_ID=$($diagram.DiagramID)")
$repo.SaveDiagram($diagram.DiagramID);$repo.ReloadDiagram($diagram.DiagramID);$repo.OpenDiagram($diagram.DiagramID)
[void]$repo.GetProjectInterface().PutDiagramImageToFile($diagram.DiagramGUID,(Join-Path $dir $entry.image),1)
$entry.elements=8;$entry.links=4
$manifest | ConvertTo-Json -Depth 8 | Set-Content (Join-Path $dir 'manifest.json') -Encoding UTF8
$mapping | ConvertTo-Json -Depth 5 | Set-Content (Join-Path $dir 'elementos.json') -Encoding UTF8
$root=$repo.Models.GetByName($data.title)
[void]$repo.GetProjectInterface().ExportPackageXMI($root.PackageGUID,11,1,0,1,0,(Join-Path $dir 'Clinica_Vida_Salud_Modelo_General.xmi'))
Write-Output 'Diagrama general de actores corregido; las otras vistas se conservaron.'
