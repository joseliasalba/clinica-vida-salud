param([string]$Solo='')
$ErrorActionPreference='Stop'
$dir=$PSScriptRoot
$data=Get-Content (Join-Path $dir 'modelo.json') -Raw -Encoding UTF8 | ConvertFrom-Json
$manifest=Get-Content (Join-Path $dir 'manifest.json') -Raw -Encoding UTF8 | ConvertFrom-Json
$map=Get-Content (Join-Path $dir 'elementos.json') -Raw -Encoding UTF8 | ConvertFrom-Json
$repo=[Runtime.InteropServices.Marshal]::GetActiveObject('EA.App').Repository
$dest=Join-Path $dir 'Clinica_Vida_Salud_Modelo_General.eapx'
if($repo.ConnectionString -ne $dest){throw 'Abrir el modelo general antes de perfeccionarlo; no se modificó otro archivo.'}
$elements=@{}
foreach($x in $map){if($x.id -in @($data.nodes.id)){$elements[$x.id]=$repo.GetElementByID($x.elementId)}}
foreach($n in $data.nodes){$e=$elements[$n.id];if($n.type -eq 'Note'){$e.Name='';$e.Notes=$n.name}else{$e.Name=$n.name;if($n.notes){$e.Notes=$n.notes}};if($n.stereotype){$e.Stereotype=$n.stereotype};[void]$e.Update()}
$repo.Models.Refresh();$root=$repo.Models.GetByName($data.title);$root.Packages.Refresh()
$catalog=$root.Packages.GetAt(0);$catalog.Name='00 Catalogo reutilizable';[void]$catalog.Update()
$root.Notes='Modelo del proyecto al 24/10/2026. Revision 7dc59ab. CU-11 y CU-13 a CU-17 pendientes. CU-08/09/10/12 integrados. Elementos UML nativos y reutilizados entre vistas.';[void]$root.Update()
foreach($entry in $manifest){
 if($Solo -and $entry.id -notmatch $Solo){continue}
 $d=$repo.GetDiagramByID($entry.diagramId);$repo.CloseDiagram($d.DiagramID)
 $spec=$data.diagrams | Where-Object id -eq $entry.id | Select-Object -First 1
 $entry.elements=$spec.placements.Count;$entry.links=$spec.edges.Count
 for($i=$d.DiagramObjects.Count-1;$i -ge 0;$i--){$o=$d.DiagramObjects.GetAt($i);if($entry.id -ne '01-lista-actores' -and $repo.GetElementByID($o.ElementID).Type -eq 'Note'){$d.DiagramObjects.DeleteAt($i,$false)}}
 $d.DiagramObjects.Refresh()
 foreach($p in $spec.placements){$o=$d.DiagramObjects | Where-Object ElementID -eq $elements[$p.ref].ElementID | Select-Object -First 1;$o.Left=[int][Math]::Round($p.x);$o.Right=[int][Math]::Round($p.x+$p.w);$o.Top=[int][Math]::Round(-$p.y);$o.Bottom=[int][Math]::Round(-($p.y+$p.h));[void]$o.Update()}
 $d.ShowPackageContents=$false
 $d.ExtendedStyle='UseAlias=0;HideAtts=1;HideOps=1;HideEStereo=0;ShowIcons=0;'
 [void]$d.Update()
 foreach($edge in $spec.edges){$c=$elements[$edge.from].Connectors | Where-Object {$_.ClientID -eq $elements[$edge.from].ElementID -and $_.SupplierID -eq $elements[$edge.to].ElementID -and $_.Stereotype -eq $edge.stereotype} | Select-Object -First 1;if($edge.stereotype -eq 'extend'){$c.Type='Dependency';$c.Direction='Source -> Destination';[void]$c.Update()};if($edge.path){$l=$d.DiagramLinks | Where-Object ConnectorID -eq $c.ConnectorID | Select-Object -First 1;$l.LineStyle=3;$l.Path=$edge.path;[void]$l.Update()}}
 [void]$repo.Execute("UPDATE t_diagram SET ShowForeign=$(if($entry.id -eq '01-lista-actores'){1}else{0}) WHERE Diagram_ID=$($d.DiagramID)")
 $repo.ReloadDiagram($d.DiagramID);$repo.OpenDiagram($d.DiagramID)
 [void]$repo.GetProjectInterface().PutDiagramImageToFile($d.DiagramGUID,(Join-Path $dir $entry.image),1)
 $repo.CloseDiagram($d.DiagramID)
 Write-Output ('Revisado: '+$entry.id)
}
$notesPackage=$catalog.Packages | Where-Object Name -Like 'Notas*' | Select-Object -First 1
if($notesPackage){for($i=$notesPackage.Elements.Count-1;$i -ge 0;$i--){if($notesPackage.Elements.GetAt($i).Type -eq 'Note' -and $notesPackage.Elements.GetAt($i).ElementID -notin @($map | Where-Object id -Like 'limite_actor_*' | ForEach-Object elementId)){$notesPackage.Elements.DeleteAt($i,$false)}};$notesPackage.Elements.Refresh();$notesPackage.Name='Limites del sistema';[void]$notesPackage.Update()}
@($data.nodes | ForEach-Object {@{id=$_.id;elementId=$elements[$_.id].ElementID;guid=$elements[$_.id].ElementGUID}}) | ConvertTo-Json -Depth 5 | Set-Content -LiteralPath (Join-Path $dir 'elementos.json') -Encoding UTF8
$manifest | ConvertTo-Json -Depth 8 | Set-Content -LiteralPath (Join-Path $dir 'manifest.json') -Encoding UTF8
[void]$repo.GetProjectInterface().ExportPackageXMI($root.PackageGUID,11,1,0,1,0,(Join-Path $dir 'Clinica_Vida_Salud_Modelo_General.xmi'))
$repo.OpenDiagram(($manifest | Where-Object id -eq '01-lista-actores').diagramId)
