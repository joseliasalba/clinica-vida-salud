$ErrorActionPreference='Stop'
$dir=$PSScriptRoot
$manifest=Get-Content "$dir/manifest.json" -Raw -Encoding UTF8 | ConvertFrom-Json
$app=[Runtime.InteropServices.Marshal]::GetActiveObject('EA.App');$repo=$app.Repository
if($repo.ConnectionString -ne (Join-Path $dir 'Clinica_Vida_Salud_CU01_CU02.eapx')){throw 'Abra el modelo generado.'}
foreach($id in @('22-estados-cu01','23-estados-cu02','26-navegacion-cu01')){
 $entry=$manifest | Where-Object id -eq $id;$d=$repo.GetDiagramByID($entry.diagramId)
 if($id -eq '26-navegacion-cu01'){$d.StyleEx+='SuppressBrackets=1;';[void]$d.Update()}
 foreach($link in $d.DiagramLinks){
  if($link.IsHidden){continue}
  $c=$repo.GetConnectorByID($link.ConnectorID);$path=''
  if($id -eq '22-estados-cu01'){
   if($c.Name -eq 'enviar credenciales'){$path='150:-100;460:-100;'}
   if($c.Name -like '[[]incorrectas*'){$path='460:-280;150:-280;'}
   if($c.Name -eq 'volver a login'){$path='15:-630;15:-185;'}
   if($c.Name -like '[[]15 min*'){$path='650:-395;650:-40;150:-40;'}
   if($c.Name -like '[[]válidas*'){$path='300:-315;300:-395;'}
  }
  if($id -eq '23-estados-cu02'){
   if($c.Name -eq 'corregir datos'){$path='490:-100;155:-100;'}
   if($c.Name -like 'reactivar*'){$path='490:-510;155:-510;'}
  }
  if($id -eq '26-navegacion-cu01' -and $c.Name -eq 'POST logout'){$path='640:-265;305:-265;'}
  if($path){$link.LineStyle=3;$link.Path=$path;[void]$link.Update()}
 }
 $repo.ReloadDiagram($d.DiagramID);$repo.OpenDiagram($d.DiagramID)
 [void]$repo.GetProjectInterface().PutDiagramImageToFile($d.DiagramGUID,(Join-Path $dir $entry.image),1)
 $repo.CloseDiagram($d.DiagramID)
}
# Exportacion final: las clases compartidas pueden haberse perfeccionado despues
# de la primera imagen de analisis.
foreach($entry in $manifest){
 $d=$repo.GetDiagramByID($entry.diagramId)
 if($entry.id -like '2[67]-navegacion*'){$d.StyleEx+='SuppressBrackets=1;';[void]$d.Update()}
 if($entry.id -like '0[89]-comunicacion*'){
  $d.ExtendedStyle='UseAlias=0;ShowIcons=1;HideEStereo=0;';[void]$d.Update()
  $pos=0
  foreach($o in $d.DiagramObjects){$e=$repo.GetElementByID($o.ElementID);$o.Left=@(40,280,640,1000)[$pos];$o.Right=$o.Left+65;$o.Top=-180;$o.Bottom=-255;[void]$o.Update();if($e.Type -eq 'Object'){$e.Alias='';[void]$e.Update()};$pos++}
  foreach($link in $d.DiagramLinks){if(!$link.IsHidden){$c=$repo.GetConnectorByID($link.ConnectorID);$source=$d.DiagramObjects | Where-Object ElementID -eq $c.ClientID | Select-Object -First 1;$target=$d.DiagramObjects | Where-Object ElementID -eq $c.SupplierID | Select-Object -First 1;$link.LineStyle=3;$link.Path="$($source.Left+32):-110;$($target.Left+32):-110;";[void]$link.Update()}}
 }
 foreach($obj in $d.DiagramObjects){
  $e=$repo.GetElementByID($obj.ElementID)
  if($e.Type -eq 'Actor'){$obj.BackgroundColor=14150399;[void]$obj.Update()}
  if($e.Type -eq 'Object'){$obj.BackgroundColor=15658700;[void]$obj.Update()}
 }
 $repo.ReloadDiagram($d.DiagramID);$repo.OpenDiagram($d.DiagramID)
 [void]$repo.GetProjectInterface().PutDiagramImageToFile($d.DiagramGUID,(Join-Path $dir $entry.image),1)
 $repo.CloseDiagram($d.DiagramID)
 Write-Output "Imagen final: $($entry.id)"
}
$repo.Models.Refresh()
$root=$repo.Models.GetByName('Clínica Vida Salud - CU-01 y CU-02')
[void]$repo.GetProjectInterface().ExportPackageXMI($root.PackageGUID,11,1,0,1,0,(Join-Path $dir 'Clinica_Vida_Salud_CU01_CU02.xmi'))
$repo.OpenDiagram(($manifest | Where-Object id -eq '04-general-cu').diagramId)
