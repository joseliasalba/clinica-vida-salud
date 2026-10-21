# Ejecutar con punto: . .\scripts\activar-entorno.ps1
$repoRoot = Split-Path -Parent $PSScriptRoot
$toolsDir = Join-Path $repoRoot '.tools'
$phpDir = Join-Path $toolsDir 'php'
if (-not (Test-Path (Join-Path $phpDir 'php.exe'))) {
    throw 'Primero ejecuta scripts/preparar-entorno.ps1.'
}
$env:PATH = "$phpDir;$toolsDir;$env:PATH"
$env:PHPRC = $phpDir
$env:COMPOSER_HOME = Join-Path $toolsDir 'composer-home'
$env:COMPOSER_CACHE_DIR = Join-Path $toolsDir 'composer-cache'
