param([switch]$Recrear, [switch]$Continuar)
$ErrorActionPreference='Stop'
$dir=$PSScriptRoot
$data=Get-Content -LiteralPath "$dir/modelo.json" -Raw -Encoding UTF8 | ConvertFrom-Json
$dest=Join-Path $dir 'Clinica_Vida_Salud_CU01_CU02.eapx'
$app=[Runtime.InteropServices.Marshal]::GetActiveObject('EA.App')
$repo=$app.Repository
if($repo.ConnectionString -and $repo.ConnectionString -ne $dest){throw 'Cierre el proyecto abierto antes de generar este modelo.'}
if($repo.ConnectionString -eq $dest -and !$Continuar){$repo.CloseFile()}
if(!$Continuar -and (Test-Path -LiteralPath $dest)){
 if(!$Recrear){throw 'El modelo ya existe. Use -Recrear para regenerar este archivo generado.'}
 Copy-Item -LiteralPath $dest -Destination "$dest.bak" -Force
}
if(!$Continuar){Copy-Item -LiteralPath 'C:/Program Files (x86)/Sparx Systems/EA Trial/EABase.eapx' -Destination $dest -Force}
$repo.SuppressEADialogs=$true
if(!$repo.ConnectionString -and !$repo.OpenFile($dest)){throw 'No se pudo abrir el modelo.'}
if($Continuar){$repo.Models.Refresh();$root=$repo.Models.GetByName($data.title)}else{$root=$repo.Models.AddNew($data.title,'Package');[void]$root.Update()}
if($Continuar){$root.Packages.Refresh();$shared=$root.Packages.GetByName('00 Elementos compartidos');$shared.Elements.Refresh()}else{$shared=$root.Packages.AddNew('00 Elementos compartidos','Package');[void]$shared.Update()}
$phases=@{};$elements=@{};$diagrams=@{};$wanted=@{}
foreach($phase in ($data.diagrams.phase | Select-Object -Unique)){if($Continuar){$p=$root.Packages.GetByName($phase);for($i=$p.Diagrams.Count-1;$i -ge 0;$i--){$p.Diagrams.DeleteAt($i,$false)};$p.Diagrams.Refresh()}else{$p=$root.Packages.AddNew($phase,'Package');[void]$p.Update()};$phases[$phase]=$p}
if($Continuar){[xml]$index=$repo.SQLQuery('SELECT Object_ID,Alias FROM t_object');$ids=@{};foreach($row in $index.EADATA.Dataset_0.Data.Row){if($row.Alias){$ids[[string]$row.Alias]=[int]$row.Object_ID}}}
foreach($n in $data.nodes){
 if($Continuar -and $ids.ContainsKey($n.id)){$elements[$n.id]=$repo.GetElementByID($ids[$n.id]);continue}
 if($n.type -eq 'Package'){$p=$shared.Packages.AddNew($n.name,'Package');[void]$p.Update();$e=$p.Element}
 else{$e=$shared.Elements.AddNew($n.name,$n.type);[void]$e.Update()}
 $e.Alias=$n.id
 if($n.stereotype){$e.Stereotype=$n.stereotype}
 if($n.subtype){$e.Subtype=$n.subtype}
 if($n.type -eq 'Note'){$e.Notes=$n.name;$e.Name=''}
 if($n.type -eq 'TimeLine'){
   $e.Notes='Estados: '+($n.states -join ' → ')+"`r`n"+(($n.transitions | ForEach-Object {"t=$($_.time): $($_.state) — $($_.event)"}) -join "`r`n")
   foreach($t in $n.transitions){$tx=$e.StateTransitions.AddNew($t.state,'');$tx.TxState=$t.state;$tx.TxTime=$t.time;$tx.Event=$t.event}
 }
 [void]$e.Update()
 foreach($s in $n.attrs){$parts=$s -split ':',2;$a=$e.Attributes.AddNew($parts[0].Trim(),$parts[1].Trim());[void]$a.Update()}
 foreach($s in $n.ops){$parts=$s -split ':',2;$m=$e.Methods.AddNew($parts[0].Trim(),$(if($parts.Length -gt 1){$parts[1].Trim()}else{''}));[void]$m.Update()}
 $elements[$n.id]=$e
}
foreach($spec in $data.diagrams){
 $type=$spec.type;if($type -eq 'Package'){$type='Logical'}
 $d=$phases[$spec.phase].Diagrams.AddNew(($spec.id+' '+$spec.title),$type);[void]$d.Update()
 $d.Notes="Referencia: página $($spec.sourcePage) de todos los diagramas.pdf. Adaptado a CU-01 y CU-02 de Clínica Vida Salud."
 $d.ShowDetails=0;$d.HighlightImports=$false;[void]$d.Update()
 $diagrams[$spec.id]=$d;$wanted[$d.DiagramID]=@()
 foreach($p in $spec.placements){
  $obj=$d.DiagramObjects.AddNew("l=$($p.x);r=$($p.x+$p.w);t=$(-$p.y);b=$(-($p.y+$p.h));",'')
  $obj.ElementID=$elements[$p.ref].ElementID
  $node=$data.nodes | Where-Object id -eq $p.ref
  $color=if($node.type -eq 'UseCase'){16246735}elseif($node.type -eq 'Component'){13684991}elseif($node.type -in @('Class','Package','State')){14150399}elseif($node.type -eq 'Note'){15597567}else{16777215}
  $obj.BackgroundColor=$color
  $obj.Style='font=Arial;fontsz=100;'
  if($p.background){$obj.Sequence=100;$obj.Style+='ShowPackageContents=0;'}
  [void]$obj.Update()
 }
 foreach($edge in $spec.edges){
  $a=$elements[$edge.from];$b=$elements[$edge.to]
  $c=$a.Connectors.AddNew($edge.label,$edge.type);$c.SupplierID=$b.ElementID
  if($edge.stereotype){$c.Stereotype=$edge.stereotype}
  if($edge.sourceCard){$c.ClientEnd.Cardinality=$edge.sourceCard}
  if($edge.targetCard){$c.SupplierEnd.Cardinality=$edge.targetCard}
  if($edge.sequence){$c.SequenceNo=$edge.sequence}
  if($edge.return){$c.Subtype='Return';$c.StyleEx='IsReturn=true;'}
  [void]$c.Update()
  $wanted[$d.DiagramID]+=$c.ConnectorID
 }
 Write-Output "Creado: $($spec.id)"
}
$images=Join-Path $dir 'diagramas';[void](New-Item -ItemType Directory -Path $images -Force)
$manifest=@()
foreach($spec in $data.diagrams){
 $d=$diagrams[$spec.id]
 $d.DiagramLinks.Refresh()
 # Los elementos se comparten; cada vista muestra solo sus conectores.
 foreach($link in $d.DiagramLinks){$link.IsHidden=($link.ConnectorID -notin $wanted[$d.DiagramID]);[void]$link.Update()}
 foreach($cid in $wanted[$d.DiagramID]){
  $exists=$false;foreach($link in $d.DiagramLinks){if($link.ConnectorID -eq $cid){$exists=$true}}
  if(!$exists){$l=$d.DiagramLinks.AddNew('','');$l.ConnectorID=$cid;$l.IsHidden=$false;[void]$l.Update()}
 }
 $repo.ReloadDiagram($d.DiagramID);$repo.OpenDiagram($d.DiagramID)
 $file=Join-Path $images ($spec.id+'.png')
 $repo.GetProjectInterface().PutDiagramImageToFile($d.DiagramGUID,$file,1)
 $repo.CloseDiagram($d.DiagramID)
 $manifest+=@{id=$spec.id;guid=$d.DiagramGUID;diagramId=$d.DiagramID;image="diagramas/$($spec.id).png"}
 Write-Output "Exportado: $($spec.id)"
}
$manifest | ConvertTo-Json | Set-Content -LiteralPath "$dir/manifest.json" -Encoding UTF8
$repo.OpenDiagram($diagrams['04-general-cu'].DiagramID)
Write-Output "Modelo completo: $dest"
