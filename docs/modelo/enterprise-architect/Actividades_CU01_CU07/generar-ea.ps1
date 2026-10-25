param([switch]$Recrear)
$ErrorActionPreference='Stop'
$dir=$PSScriptRoot
$data=Get-Content -LiteralPath "$dir/modelo.json" -Raw -Encoding UTF8 | ConvertFrom-Json
$dest=Join-Path $dir 'Clinica_Vida_Salud_Actividades_CU01_CU07.eapx'
Write-Output 'Conectando con Enterprise Architect...'
try{$app=[Runtime.InteropServices.Marshal]::GetActiveObject('EA.App')}catch{$app=New-Object -ComObject EA.App}
$repo=$app.Repository
if($repo.ConnectionString){
 [xml]$open=$repo.SQLQuery('SELECT Diagram_ID FROM t_diagram')
 foreach($row in $open.EADATA.Dataset_0.Data.Row){$repo.SaveDiagram([int]$row.Diagram_ID)}
 $repo.CloseFile()
 if($repo.Models.Count -gt 0){throw 'EA no cerró el proyecto anterior. No se reemplazó ningún archivo.'}
}
if(Test-Path -LiteralPath $dest){
 if(!$Recrear){throw 'El modelo ya existe. Use -Recrear para regenerar conservando un respaldo.'}
 Copy-Item -LiteralPath $dest -Destination "$dest.bak" -Force
}
Copy-Item -LiteralPath 'C:/Program Files (x86)/Sparx Systems/EA Trial/EABase.eapx' -Destination $dest -Force
$repo.SuppressEADialogs=$true
if(!$repo.OpenFile($dest)){throw 'No se pudo abrir el modelo nuevo.'}
$app.Visible=$true
$root=$repo.Models.AddNew($data.title,'Package');[void]$root.Update()
$packages=@{}
foreach($case in $data.cases){$p=$root.Packages.AddNew(($case.id+' '+$case.title),'Package');$p.Notes=$case.permission+[Environment]::NewLine+$case.rules;[void]$p.Update();$packages[$case.id]=$p}
$elements=@{}
foreach($n in $data.nodes){
 $caseId=$n.caseId
 $e=$packages[$caseId].Elements.AddNew($n.name,$n.type);[void]$e.Update()
 $e.Alias=''
 if($n.notes){$e.Notes=$n.notes}
 if($null -ne $n.subtype){$e.Subtype=[int]$n.subtype}
 if($n.stereotype){$e.Stereotype=$n.stereotype}
 if($n.type -eq 'Note'){$e.Name='';$e.Notes=$n.name}
 if($n.type -eq 'InteractionFragment'){$e.Subtype=0}
 [void]$e.Update()
 foreach($attr in $n.attrs){$parts=$attr -split ':',2;$a=$e.Attributes.AddNew($parts[0].Trim(),$parts[1].Trim());$a.Visibility='Public';[void]$a.Update()}
 foreach($op in $n.ops){$parts=$op -split '\(\):\s*',2;$m=$e.Methods.AddNew($parts[0],$parts[1]);$m.Visibility='Public';[void]$m.Update()}
 $elements[$n.id]=$e
}
@($data.nodes | ForEach-Object {@{id=$_.id;elementId=$elements[$_.id].ElementID;guid=$elements[$_.id].ElementGUID}}) | ConvertTo-Json | Set-Content -LiteralPath "$dir/elementos.json" -Encoding UTF8
$created=@{}
$allWanted=@{}
foreach($spec in $data.diagrams){
 $d=$packages[$spec.caseId].Diagrams.AddNew(($spec.caseId+' '+$spec.title),$spec.type)
 $d.ShowDetails=0
 $d.Notes="Objetivo: "+$spec.caseTitle+([Environment]::NewLine+"Precondición: ")+$spec.permission+([Environment]::NewLine+"Reglas: ")+$spec.note+([Environment]::NewLine+"Fuentes: ")+($spec.sources -join '; ')+([Environment]::NewLine+"Referencia de formato: página ")+$spec.referencePage
 $d.ExtendedStyle='UseAlias=0;HideAtts=0;HideOps=0;HideEStereo=0;ShowIcons=0;'
 if($spec.kind -eq 'comunicacion'){$d.ExtendedStyle='UseAlias=0;ShowIcons=1;HideEStereo=0;'}
 if($spec.kind -eq 'navegacion'){$d.StyleEx='SuppressBrackets=1;'}
 [void]$d.Update()
 foreach($p in $spec.placements){
  $n=$data.nodes | Where-Object id -eq $p.ref | Select-Object -First 1
  $o=$d.DiagramObjects.AddNew("l=$($p.x);r=$($p.x+$p.w);t=$(-$p.y);b=$(-($p.y+$p.h));",'')
  $o.ElementID=$elements[$p.ref].ElementID
  $o.Sequence=if($p.background){9999}else{1}
  $o.Style='font=Arial;fontsz=120;UseAlias=0;'
  $o.BackgroundColor=if($n.type -eq 'UseCase'){16246735}elseif($n.type -eq 'Object' -and $spec.kind -eq 'comunicacion'){15658700}elseif($n.type -in @('Class','Actor')){14150399}elseif($n.type -eq 'Note'){15597567}else{16777215}
  if($n.type -eq 'Class'){$o.Style+='UCRect=1;';$o.ShowPublicOperations=$true;$o.ShowPublicAttributes=$true}
  if($n.type -eq 'InteractionFragment'){$o.BackgroundColor=16777215}
  [void]$o.Update()
 }
 $wanted=@()
 foreach($edge in $spec.edges){
  $c=$elements[$edge.from].Connectors.AddNew($edge.label,$edge.type)
  $c.SupplierID=$elements[$edge.to].ElementID
  if($edge.type -eq 'ControlFlow' -and $edge.label){$c.Name='';$c.TransitionGuard=$edge.label.Trim('[',']')}
  if($edge.sequence){$c.SequenceNo=[int]$edge.sequence}
  if($edge.return){$c.Subtype='Return';$c.StyleEx='IsReturn=true;'}
  if($spec.kind -eq 'comunicacion'){$c.Direction='Source -> Destination';$c.SupplierEnd.Navigable='Navigable'}
  [void]$c.Update()
  $wanted+=@{id=$c.ConnectorID;edge=$edge}
 }
 $created[$spec.id]=$d
 $allWanted[$spec.id]=$wanted
 Write-Output ('Creado: '+$spec.id)
}
$images=Join-Path $dir 'diagramas';[void][IO.Directory]::CreateDirectory($images)
$manifest=@()
foreach($spec in $data.diagrams){
 $d=$created[$spec.id]
 $wanted=$allWanted[$spec.id]
 $d.DiagramLinks.Refresh()
 foreach($c in $wanted){
  $l=$d.DiagramLinks | Where-Object ConnectorID -eq $c.id | Select-Object -First 1
  if(!$l){$l=$d.DiagramLinks.AddNew('','');$l.ConnectorID=$c.id}
  $l.IsHidden=$false
  if($c.edge.path){$l.LineStyle=3;$l.Path=$c.edge.path}
  if($spec.kind -eq 'clases-analisis' -and $c.edge.from -match '-control$'){
   $a=$spec.placements | Where-Object ref -eq $c.edge.from | Select-Object -First 1
   $b=$spec.placements | Where-Object ref -eq $c.edge.to | Select-Object -First 1
   $ay=-($a.y+[int]($a.h/2));$by=-($b.y+[int]($b.h/2))
   $l.LineStyle=3;$l.Path="960:${ay};960:${by};"
  }
  if($spec.kind -eq 'comunicacion'){
   $a=$spec.placements | Where-Object ref -eq $c.edge.from | Select-Object -First 1
   $b=$spec.placements | Where-Object ref -eq $c.edge.to | Select-Object -First 1
   $l.LineStyle=3;$l.Path="$($a.x+40):-230;$($b.x+40):-230;"
  }
  if($spec.kind -eq 'navegacion'){
   $a=$spec.placements | Where-Object ref -eq $c.edge.from | Select-Object -First 1
   $b=$spec.placements | Where-Object ref -eq $c.edge.to | Select-Object -First 1
   if($a.ref -eq $b.ref){$l.LineStyle=3;$l.Path="$($a.x+330):$(-$a.y-40);$($a.x+330):$(-$a.y+30);$($a.x+150):$(-$a.y+30);"}
   elseif($a.y -ne $b.y -and $a.x -ne $b.x){$lane=325+([int]$c.edge.navFrom%3)*30;$l.LineStyle=3;$l.Path="$($a.x+147):-$lane;$($b.x+147):-$lane;"}
  }
  [void]$l.Update()
 }
 foreach($l in $d.DiagramLinks){if($l.ConnectorID -notin @($wanted.id)){$l.IsHidden=$true;[void]$l.Update()}}
 $repo.SaveDiagram($d.DiagramID)
 $repo.ReloadDiagram($d.DiagramID);$repo.OpenDiagram($d.DiagramID)
 $filename=$spec.id+'.png'
 [void]$repo.GetProjectInterface().PutDiagramImageToFile($d.DiagramGUID,(Join-Path $images $filename),1)
 $manifest+=@{id=$spec.id;caseId=$spec.caseId;kind=$spec.kind;title=$spec.title;guid=$d.DiagramGUID;diagramId=$d.DiagramID;image='diagramas/'+$filename}
 $repo.CloseDiagram($d.DiagramID)
 Write-Output ('Exportado: '+$spec.id)
}
$manifest | ConvertTo-Json -Depth 6 | Set-Content -LiteralPath "$dir/manifest.json" -Encoding UTF8
[void]$repo.GetProjectInterface().ExportPackageXMI($root.PackageGUID,11,1,0,1,0,(Join-Path $dir 'Clinica_Vida_Salud_Actividades_CU01_CU07.xmi'))
$repo.OpenDiagram($created['cu01-actividad'].DiagramID)
Write-Output ('Modelo final: '+$dest)
