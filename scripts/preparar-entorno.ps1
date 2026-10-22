# Requiere Windows x64, PowerShell 5.1+, Node.js y acceso a Internet.
# Instala herramientas portables en .tools; no modifica el PATH del sistema.
[CmdletBinding()]
param([switch]$SoloHerramientas)

$ErrorActionPreference = 'Stop'
Set-StrictMode -Version Latest
[Net.ServicePointManager]::SecurityProtocol = [Net.SecurityProtocolType]::Tls12
$repoRoot = Split-Path -Parent $PSScriptRoot
$toolsDir = Join-Path $repoRoot '.tools'
$phpDir = Join-Path $toolsDir 'php'
$phpExe = Join-Path $phpDir 'php.exe'
$composerPhar = Join-Path $toolsDir 'composer.phar'
$appDir = Join-Path $repoRoot 'sistema/backend'
$utf8 = New-Object System.Text.UTF8Encoding($false)

function Write-Utf8([string]$Path, [string]$Text) {
    [IO.File]::WriteAllText($Path, $Text, $utf8)
}

function Get-Download([string]$Url, [string]$Path) {
    Write-Host "Descargando $Url"
    Invoke-WebRequest -Uri $Url -OutFile $Path -UseBasicParsing -TimeoutSec 120
}

function Invoke-Checked([string]$Program, [string[]]$Arguments) {
    & $Program @Arguments
    if ($LASTEXITCODE -ne 0) { throw "Fallo al ejecutar $Program (codigo $LASTEXITCODE)." }
}

if (-not [Environment]::Is64BitOperatingSystem) { throw 'Se requiere Windows x64.' }
if (-not $SoloHerramientas) {
    $null = Get-Command node.exe -ErrorAction Stop
    $null = Get-Command npm.cmd -ErrorAction Stop
}
$null = New-Item -ItemType Directory -Path $toolsDir -Force

if (-not (Test-Path $phpExe)) {
    Write-Host 'Consultando la version estable de PHP 8.4...'
    $manifest = Invoke-RestMethod 'https://downloads.php.net/~windows/releases/releases.json' -TimeoutSec 30
    $release = $manifest.'8.4'
    $build = @($release.PSObject.Properties | Where-Object Name -Match '^nts-.*-x64$')[0].Value.zip
    if ($build.path -notmatch '^php-8\.4\.\d+-nts-Win32-vs\d+-x64\.zip$') {
        throw 'Formato de descarga PHP no reconocido. Revisar el manifiesto oficial.'
    }
    $archive = Join-Path $toolsDir $build.path
    Get-Download "https://downloads.php.net/~windows/releases/$($build.path)" $archive
    if ((Get-FileHash $archive -Algorithm SHA256).Hash -ne $build.sha256) {
        throw 'La descarga PHP no coincide con el SHA256 oficial.'
    }
    Expand-Archive -LiteralPath $archive -DestinationPath $phpDir -Force
}

$caFile = Join-Path $toolsDir 'cacert.pem'
if (-not (Test-Path $caFile)) {
    Get-Download 'https://curl.se/ca/cacert.pem' $caFile
}
$iniPath = Join-Path $phpDir 'php.ini'
$extensions = @('curl', 'fileinfo', 'mbstring', 'openssl', 'pdo_pgsql', 'pgsql', 'pdo_sqlite', 'sqlite3', 'zip', 'intl', 'bcmath')
if (-not (Test-Path $iniPath)) {
    $ini = [IO.File]::ReadAllText((Join-Path $phpDir 'php.ini-development'))
    $ini += "`n; Clinica Vida Salud - desarrollo local`nextension_dir=`"$($phpDir.Replace('\','/'))/ext`"`n"
    foreach ($ext in $extensions) {
        # Algunas extensiones (por ejemplo bcmath) vienen compiladas en PHP.
        if (Test-Path (Join-Path $phpDir "ext/php_$ext.dll")) { $ini += "extension=$ext`n" }
    }
    $ini += "date.timezone=America/La_Paz`nmemory_limit=512M`n"
    $ini += "curl.cainfo=`"$($caFile.Replace('\','/'))`"`nopenssl.cafile=`"$($caFile.Replace('\','/'))`"`n"
    Write-Utf8 $iniPath $ini
}
. (Join-Path $PSScriptRoot 'activar-entorno.ps1')
Invoke-Checked $phpExe @('--version')
$modules = & $phpExe -m
if ($LASTEXITCODE -ne 0) { throw 'PHP no inicia. Revisar Visual C++ Runtime x64.' }
foreach ($ext in $extensions) {
    if ($modules -notcontains $ext) { throw "Falta la extension PHP: $ext" }
}

if (-not (Test-Path $composerPhar)) {
    $installer = Join-Path $toolsDir 'composer-setup.php'
    Get-Download 'https://getcomposer.org/installer' $installer
    $expected = (Invoke-RestMethod 'https://composer.github.io/installer.sig' -TimeoutSec 30).Trim()
    if ((Get-FileHash $installer -Algorithm SHA384).Hash -ne $expected) {
        throw 'El instalador Composer no coincide con su SHA384 oficial.'
    }
    Invoke-Checked $phpExe @($installer, '--2', "--install-dir=$toolsDir", '--filename=composer.phar')
}
Write-Utf8 (Join-Path $toolsDir 'composer.cmd') "@echo off`r`n`"%~dp0php\php.exe`" `"%~dp0composer.phar`" %*`r`n"
Invoke-Checked $phpExe @($composerPhar, '--version')
if ($SoloHerramientas) { Write-Host 'PHP y Composer preparados.'; return }

Push-Location $repoRoot
try {
    if (-not (Test-Path $appDir)) {
        throw 'Falta sistema/backend. Recupera la estructura completa del repositorio antes de preparar el entorno.'
    } elseif (-not (Test-Path (Join-Path $appDir 'artisan'))) {
        throw 'sistema/backend/ existe pero no es una aplicacion Laravel. Revisarla antes de continuar.'
    }
    Set-Location $appDir
    if (-not (Test-Path 'vendor/autoload.php')) {
        Invoke-Checked $phpExe @($composerPhar, 'install', '--prefer-dist', '--no-interaction', '--no-scripts')
    }
    if (-not (Test-Path '.env')) {
        $example = [IO.File]::ReadAllText((Join-Path $appDir '.env.example'))
        $defaults = [ordered]@{
            APP_NAME = '"Clinica Vida Salud"'; APP_URL = 'http://127.0.0.1:8000'
            APP_LOCALE = 'es'; APP_FALLBACK_LOCALE = 'es'; APP_FAKER_LOCALE = 'es_ES'
            DB_CONNECTION = 'pgsql'; DB_HOST = '127.0.0.1'; DB_PORT = '5432'
            DB_DATABASE = 'clinica_vida_salud'; DB_USERNAME = 'clinica_app'; DB_PASSWORD = ''
            SESSION_DRIVER = 'file'; CACHE_STORE = 'file'; QUEUE_CONNECTION = 'sync'
        }
        foreach ($key in $defaults.Keys) {
            $pattern = '(?m)^#?\s*' + [regex]::Escape($key) + '=[^\r\n]*'
            $line = "$key=$($defaults[$key])"
            if ($example -match $pattern) { $example = [regex]::Replace($example, $pattern, $line) }
            else { $example += "`n$line`n" }
        }
        Write-Utf8 (Join-Path $appDir '.env.example') $example
        Write-Utf8 (Join-Path $appDir '.env') $example
    }
    Invoke-Checked $phpExe @($composerPhar, 'run-script', 'post-autoload-dump')
    $localEnv = [IO.File]::ReadAllText((Join-Path $appDir '.env'))
    if ($localEnv -notmatch '(?m)^APP_KEY=.+') { Invoke-Checked $phpExe @('artisan', 'key:generate', '--no-interaction') }
    Invoke-Checked $phpExe @('artisan', 'config:clear')
    Push-Location (Join-Path $repoRoot 'sistema/frontend')
    try {
        if (Test-Path 'package-lock.json') { Invoke-Checked 'npm.cmd' @('ci') }
        else { Invoke-Checked 'npm.cmd' @('install') }
        Invoke-Checked 'npm.cmd' @('run', 'build')
    } finally { Pop-Location }
    Write-Host 'Herramientas, Laravel y frontend preparados.'
    Write-Host 'Si PostgreSQL aun no esta configurado, consulta docs/entorno.md y ejecuta scripts/conectar-postgresql.ps1.'
    Write-Host 'Las pruebas requieren PostgreSQL. Ejecuta php artisan test desde sistema/backend despues de configurar la conexion.'
    Write-Host 'Este script no ejecuta migraciones ni pruebas.'
} finally { Pop-Location }
