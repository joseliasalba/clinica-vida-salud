param([string]$Solo='')
$ErrorActionPreference='Stop'
$dir=$PSScriptRoot
$data=Get-Content -LiteralPath "$dir/modelo.json" -Raw -Encoding UTF8 | ConvertFrom-Json
$manifest=Get-Content -LiteralPath "$dir/manifest.json" -Raw -Encoding UTF8 | ConvertFrom-Json
$app=[Runtime.InteropServices.Marshal]::GetActiveObject('EA.App');$repo=$app.Repository
if($repo.ConnectionString -ne (Join-Path $dir 'Clinica_Vida_Salud_CU03_CU07.eapx')){throw 'Abra el modelo CU03_CU07 generado antes de perfeccionarlo.'}
$mapping=Join-Path $dir 'elementos.json'
$elements=@{}
if(Test-Path -LiteralPath $mapping){foreach($entry in (Get-Content $mapping -Raw -Encoding UTF8 | ConvertFrom-Json)){$elements[$entry.id]=$repo.GetElementByID($entry.elementId)}}
else{
 [xml]$index=$repo.SQLQuery('SELECT Object_ID,Alias,Name FROM t_object')
 foreach($n in $data.nodes){$row=$index.EADATA.Dataset_0.Data.Row | Where-Object Alias -eq $n.id | Select-Object -First 1;if(!$row){$row=$index.EADATA.Dataset_0.Data.Row | Where-Object Name -eq $n.name | Select-Object -First 1};if(!$row){throw "Elemento ausente: $($n.id)"};$elements[$n.id]=$repo.GetElementByID([int]$row.Object_ID)}
 @($data.nodes | ForEach-Object {@{id=$_.id;elementId=$elements[$_.id].ElementID;guid=$elements[$_.id].ElementGUID}}) | ConvertTo-Json | Set-Content -LiteralPath $mapping -Encoding UTF8
}
foreach($n in $data.nodes){
 $e=$elements[$n.id]
 if($n.type -eq 'Object'){$e.Alias='';$e.Name=$n.name}
 if($n.type -eq 'Note'){$e.Notes=$n.name}
 if($n.type -eq 'Class'){
  for($i=0;$i -lt $n.ops.Count;$i++){$m=$e.Methods.GetAt($i);$m.Name=$n.ops[$i];[void]$m.Update()}
 }
 if($n.type -eq 'InteractionFragment'){$e.Subtype=1;$e.Name=$n.name -replace '^opt\s+',''}
 [void]$e.Update()
}
foreach($spec in $data.diagrams){
 if($Solo -and $spec.id -ne $Solo){continue}
 $entry=$manifest | Where-Object id -eq $spec.id | Select-Object -First 1
 $d=$repo.GetDiagramByID($entry.diagramId);$repo.CloseDiagram($d.DiagramID)
 $d.Notes=@("Objetivo: "+$spec.caseTitle,"Precondición: "+$spec.permission,"Reglas: "+$spec.note,"Fuentes: "+($spec.sources -join '; '),"Referencia: página "+$spec.referencePage) -join [Environment]::NewLine
 $d.StyleEx+='SuppressBrackets=1;'
 [void]$d.Update()
 if($spec.kind -eq 'secuencia'){
  $count=$spec.edges.Count
  foreach($edge in $spec.edges){
   $c=$elements[$edge.from].Connectors | Where-Object {$_.ClientID -eq $elements[$edge.from].ElementID -and $_.SupplierID -eq $elements[$edge.to].ElementID -and $_.Type -eq 'Sequence' -and $_.SequenceNo -eq $edge.sequence} | Select-Object -First 1
   if(!$c){throw "Mensaje ausente en $($spec.id): $($edge.sequence)"}
   $c.Name=$edge.label
   if($edge.return){$c.Subtype='Return';$c.StyleEx='IsReturn=true;'}else{$c.Subtype='Call';$c.StyleEx='IsReturn=false;'}
   [void]$c.Update()
  }
  foreach($p in $spec.placements){
   $o=$d.DiagramObjects | Where-Object ElementID -eq $elements[$p.ref].ElementID | Select-Object -First 1
   $n=$data.nodes | Where-Object id -eq $p.ref | Select-Object -First 1
   if($n.type -eq 'InteractionFragment'){
    $idx=[int]($n.id -replace '^.*-frag-','');$section=$spec.sections[$idx]
    $o.Top=-(105+($section.start-1)*35);$o.Bottom=-(145+($section.end-1)*35)
   }elseif($n.type -eq 'Note'){$o.Top=-(205+$count*35);$o.Bottom=-(330+$count*35)}
   else{$o.Left=$p.x;$o.Right=$p.x+$p.w;$o.Bottom=-(175+$count*35)}
   [void]$o.Update()
  }
 }
 if($spec.kind -eq 'navegacion'){
  $i=0
  foreach($edge in $spec.edges){
   $a=$spec.placements | Where-Object ref -eq $edge.from | Select-Object -First 1
   $b=$spec.placements | Where-Object ref -eq $edge.to | Select-Object -First 1
   $c=$elements[$edge.from].Connectors | Where-Object {$_.ClientID -eq $elements[$edge.from].ElementID -and $_.SupplierID -eq $elements[$edge.to].ElementID -and $_.Type -eq $edge.type} | Select-Object -First 1
   if(!$c){continue}
   $c.Name=$edge.label;[void]$c.Update()
   $l=$d.DiagramLinks | Where-Object ConnectorID -eq $c.ConnectorID | Select-Object -First 1
   $l.LineStyle=3;$path=''
   if($null -eq $edge.navFrom){$l.LineStyle=1}
   elseif($a.ref -eq $b.ref){$path="$($a.x+330):$(-$a.y-40);$($a.x+330):$(-$a.y+30);$($a.x+150):$(-$a.y+30);"}
   elseif($a.y -eq $b.y){
    if([Math]::Abs($a.x-$b.x) -gt 500){$lane=if($a.y -lt 200){40}else{650};$path="$($a.x+147):-$lane;$($b.x+147):-$lane;"}
    else{$off=if($a.x -lt $b.x){50}else{115};$ax=if($a.x -lt $b.x){$a.x+310}else{$a.x-15};$bx=if($a.x -lt $b.x){$b.x-15}else{$b.x+310};$path="${ax}:$(-$a.y-$off);${bx}:$(-$b.y-$off);"}
   }elseif($a.x -eq $b.x){
    if($a.y -lt $b.y){$x=$a.x+60;$path="${x}:$(-$a.y-$a.h-15);${x}:$(-$b.y+15);"}
    else{$x=$a.x+245;$path="${x}:$(-$a.y+15);${x}:$(-$b.y-$b.h-15);"}
   }
   else{$lane=300+($i%3)*35;$path="$($a.x+147):-$lane;$($b.x+147):-$lane;"}
   $l.Path=$path;[void]$l.Update();$i++
  }
 }
 if($spec.kind -eq 'comunicacion'){
  foreach($edge in $spec.edges){
   $a=$elements[$edge.from];$b=$elements[$edge.to]
   $association=$a.Connectors | Where-Object {$_.ClientID -eq $a.ElementID -and $_.SupplierID -eq $b.ElementID -and $_.Type -eq 'Association'} | Select-Object -First 1
   $association.Name='';[void]$association.Update()
   $link=$d.DiagramLinks | Where-Object ConnectorID -eq $association.ConnectorID | Select-Object -First 1
   $link.Path='';$link.LineStyle=1;[void]$link.Update()
   $messageIndex=0
   foreach($line in ($edge.label -split "\n")){
    $parts=$line -split ':',2;$sequence=$parts[0].Trim();$name=$parts[1].Trim()
    $message=$a.Connectors | Where-Object {$_.ClientID -eq $a.ElementID -and $_.Type -eq 'Collaboration' -and $_.SupplierID -eq $b.ElementID -and $_.Name -eq $name} | Select-Object -First 1
    if(!$message){$message=$a.Connectors.AddNew($name,'Collaboration');$message.SupplierID=$b.ElementID;[void]$message.Update()}
    $id=$message.ConnectorID;$assocId=$association.ConnectorID;$did=$d.DiagramID;$number=$messageIndex+1
    $offset=65-$messageIndex*25
    $label=($sequence+': '+$name).Replace("'","''")
    [void]$repo.Execute("UPDATE t_connector SET SourceIsAggregate=$assocId, PDATA1='Synchronous', PDATA3='Call', PDATA4='$sequence', PDATA5='SX=-20;SY=$offset;EX=20;EY=$offset;EDGE=2;', Top_Start_Label='$label', SeqNo=$number, DiagramID=$did WHERE Connector_ID=$id")
    $dl=$d.DiagramLinks | Where-Object ConnectorID -eq $id | Select-Object -First 1
    if(!$dl){$dl=$d.DiagramLinks.AddNew('','');$dl.ConnectorID=$id}
    $dl.Geometry="SX=-20;SY=$offset;EX=20;EY=$offset;EDGE=2;"+'$LLB=;LLT=;LMT=;LMB=;LRT=;LRB=;IRHS=;ILHS=;'
    $color=@(0,255,32768)[$messageIndex]
    $dl.Style="Mode=1;Color=$color;LWidth=0;"
    $dl.IsHidden=$false;[void]$dl.Update()
    $messageIndex++
   }
  }
 }
 $repo.ReloadDiagram($d.DiagramID);$repo.OpenDiagram($d.DiagramID)
 [void]$repo.GetProjectInterface().PutDiagramImageToFile($d.DiagramGUID,(Join-Path $dir $entry.image),1)
 $repo.CloseDiagram($d.DiagramID)
 Write-Output ('Perfeccionado: '+$spec.id)
}
$repo.Models.Refresh();$root=$repo.Models.GetByName($data.title)
[void]$repo.GetProjectInterface().ExportPackageXMI($root.PackageGUID,11,1,0,1,0,(Join-Path $dir 'Clinica_Vida_Salud_CU03_CU07.xmi'))
$repo.OpenDiagram(($manifest | Where-Object id -eq 'cu03-casos').diagramId)
