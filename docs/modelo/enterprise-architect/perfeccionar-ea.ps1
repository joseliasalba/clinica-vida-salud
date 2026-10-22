param([string]$Desde='')
$ErrorActionPreference='Stop'
$dir=$PSScriptRoot
$data=Get-Content "$dir/modelo.json" -Raw -Encoding UTF8 | ConvertFrom-Json
$manifest=Get-Content "$dir/manifest.json" -Raw -Encoding UTF8 | ConvertFrom-Json
$app=[Runtime.InteropServices.Marshal]::GetActiveObject('EA.App');$repo=$app.Repository
$dest=Join-Path $dir 'Clinica_Vida_Salud_CU01_CU02.eapx'
if(!$repo.ConnectionString){[void]$repo.OpenFile($dest)}
if($repo.ConnectionString -ne $dest){throw 'El proyecto activo no es el modelo generado.'}
[xml]$index=$repo.SQLQuery('SELECT Object_ID,Alias,Name FROM t_object');$elements=@{}
foreach($n in $data.nodes){
 $row=$index.EADATA.Dataset_0.Data.Row | Where-Object Alias -eq $n.id | Select-Object -First 1
 if(!$row){$row=$index.EADATA.Dataset_0.Data.Row | Where-Object Name -eq $n.name | Select-Object -First 1}
 if($n.id -match '^1[456]-secuencia'){
  $seq=$data.diagrams | Where-Object {$n.id.StartsWith($_.id+'-')} | Select-Object -First 1
  $entry=$manifest | Where-Object id -eq $seq.id
  $sd=$repo.GetDiagramByID($entry.diagramId)
  $pos=0;for($i=0;$i -lt $seq.placements.Count;$i++){if($seq.placements[$i].ref -eq $n.id){$pos=$i;break}}
  $row=@{Object_ID=$sd.DiagramObjects.GetAt($pos).ElementID}
 }
 if(!$row){throw "Elemento ausente: $($n.id)"}
 $e=$repo.GetElementByID([int]$row.Object_ID);$elements[$n.id]=$e
 if($n.id -notmatch '^1[456]-secuencia'){$e.Alias=$n.id;[void]$e.Update()}
 if($n.id -match '^1[456]-secuencia'){$e.Alias='';[void]$e.Update()}
 foreach($a in $e.Attributes){if($a.Type -match 'timestamp without time zone'){$a.Type=$a.Type.Replace('timestamp without time zone','timestamp');[void]$a.Update()}}
 foreach($m in $e.Methods){
  if($n.id -eq 'cService' -and $m.Name -match '^mutate'){$m.Visibility='Private';[void]$m.Update()}
  if($m.Name -match '^([^()]+)\(([^()]*)\)$'){
   $m.Name=$Matches[1];$argsText=$Matches[2];[void]$m.Update()
   if($argsText){foreach($arg in ($argsText -split ',')){$arg=$arg.Trim();$argName=if($arg -cmatch '^[A-Z]'){'request'}else{$arg};$type=if($arg -cmatch '^[A-Z]'){$arg}else{'mixed'};$prm=$m.Parameters.AddNew($argName,$type);[void]$prm.Update()}}
  }
 }
 if($n.type -eq 'TimeLine'){
  $existing=@($e.Partitions | ForEach-Object Name)
  foreach($state in $n.states){if($state -notin $existing){$part=$e.Partitions.AddNew($state,'');$part.Size=45}}
  foreach($transition in $e.StateTransitions){$transition.Event=''}
  [void]$e.Update()
 }
}
foreach($spec in $data.diagrams){
 if($Desde -and [string]::CompareOrdinal($spec.id,$Desde) -lt 0){continue}
 $entry=$manifest | Where-Object id -eq $spec.id;$d=$repo.GetDiagramByID($entry.diagramId)
 $repo.CloseDiagram($d.DiagramID)
 $d.ExtendedStyle='UseAlias=0;HideAtts=0;HideOps=0;HideEStereo=0;ShowIcons=0;'
 if($spec.type -eq 'Timing'){
  $limit=if($spec.id -eq '24-tiempo-cu01'){35}else{4}
  $d.StyleEx="TDurLow=0;TDurHigh=$limit;TDurUnit=;TDurHide=0;StartPos=155;TimeIntervalList=;"
 }
 [void]$d.Update()
 foreach($p in $spec.placements){
  $obj=$d.DiagramObjects | Where-Object ElementID -eq $elements[$p.ref].ElementID | Select-Object -First 1
  $obj.Left=$p.x;$obj.Right=$p.x+$p.w;$obj.Top=-$p.y;$obj.Bottom=-($p.y+$p.h)
  $obj.Sequence=if($p.background){9999}else{1}
  $node=$data.nodes | Where-Object id -eq $p.ref
  if($node.type -eq 'Class'){$obj.Style+='UCRect=1;';$obj.ShowPublicAttributes=$true;$obj.ShowPublicOperations=$true}
  $obj.BackgroundColor=if($node.type -eq 'UseCase'){16246735}elseif($node.type -eq 'Component'){13684991}elseif($node.type -in @('Class','Package','State')){14150399}elseif($node.type -eq 'Note'){15597567}else{16777215}
  [void]$obj.Update()
 }
 $repo.ReloadDiagram($d.DiagramID);$repo.OpenDiagram($d.DiagramID)
 [void]$repo.GetProjectInterface().PutDiagramImageToFile($d.DiagramGUID,(Join-Path $dir $entry.image),1)
 $repo.CloseDiagram($d.DiagramID)
 Write-Output "Revisado: $($spec.id)"
}
$repo.OpenDiagram(($manifest | Where-Object id -eq '04-general-cu').diagramId)
