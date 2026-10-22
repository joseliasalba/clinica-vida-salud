# Configura la contraseña local de clinica_app sin mostrarla en pantalla.
# Ejecutar desde la raíz: powershell -NoProfile -ExecutionPolicy Bypass -File .\scripts\conectar-postgresql.ps1
$ErrorActionPreference = 'Stop'
Set-StrictMode -Version Latest
$repoRoot = Split-Path -Parent $PSScriptRoot
$appDir = Join-Path $repoRoot 'sistema/backend'
$envPath = Join-Path $appDir '.env'
$phpExe = Join-Path $repoRoot '.tools\php\php.exe'

if (-not (Test-Path $phpExe) -or -not (Test-Path (Join-Path $appDir 'artisan'))) {
    throw 'Laravel o PHP no está preparado. Ejecuta primero scripts/preparar-entorno.ps1.'
}
if (-not (Test-Path $envPath)) { throw 'Falta sistema/backend/.env.' }

$envText = [IO.File]::ReadAllText($envPath)
$settings = [ordered]@{
    DB_CONNECTION = 'pgsql'; DB_HOST = '127.0.0.1'; DB_PORT = '5432'
    DB_DATABASE = 'clinica_vida_salud'; DB_USERNAME = 'clinica_app'
}
foreach ($key in $settings.Keys) {
    $pattern = '(?m)^' + [regex]::Escape($key) + '=[^\r\n]*'
    if ($envText -notmatch $pattern) { throw "Falta $key en sistema/backend/.env. Revisar el archivo antes de continuar." }
    $envText = [regex]::Replace($envText, $pattern, [string]$key + '=' + $settings[$key])
}
if ($envText -notmatch '(?m)^DB_PASSWORD=[^\r\n]*') {
    throw 'Falta DB_PASSWORD en sistema/backend/.env.'
}

$securePassword = Read-Host 'Contraseña que elegiste para clinica_app' -AsSecureString
$bstr = [Runtime.InteropServices.Marshal]::SecureStringToBSTR($securePassword)
try { $password = [Runtime.InteropServices.Marshal]::PtrToStringBSTR($bstr) }
finally { [Runtime.InteropServices.Marshal]::ZeroFreeBSTR($bstr) }
if ([string]::IsNullOrEmpty($password) -or $password -match '[\r\n]') {
    throw 'La contraseña debe tener contenido y no incluir saltos de línea.'
}
$escaped = $password.Replace('\', '\\').Replace('"', '\"')
$password = $null
$envText = [regex]::Replace($envText, '(?m)^DB_PASSWORD=[^\r\n]*', [string]('DB_PASSWORD="' + $escaped + '"'))
$escaped = $null
[IO.File]::WriteAllText($envPath, $envText, (New-Object System.Text.UTF8Encoding($false)))
$envText = $null

Push-Location $appDir
try {
    & $phpExe artisan config:clear
    if ($LASTEXITCODE -ne 0) { throw 'No se pudo limpiar la configuración.' }
    & $phpExe artisan db:show --no-interaction
    if ($LASTEXITCODE -ne 0) { throw 'No se pudo conectar. Revisa la contraseña de clinica_app y repite este script.' }
    Write-Host 'Conexión con clinica_vida_salud comprobada. Ejecutando migraciones...'
    & $phpExe artisan migrate --no-interaction
    if ($LASTEXITCODE -ne 0) { throw 'Falló la migración. Revisar el error antes de continuar.' }
    & $phpExe artisan db:seed --no-interaction
    if ($LASTEXITCODE -ne 0) { throw 'No se pudieron preparar los roles y permisos.' }
    & $phpExe artisan migrate:status
    if ($LASTEXITCODE -ne 0) { throw 'No se pudo consultar el estado de las migraciones.' }
    & $phpExe artisan test
    if ($LASTEXITCODE -ne 0) { throw 'Las pruebas iniciales fallaron.' }
    Write-Host 'PostgreSQL y Laravel configurados. sistema/backend/.env es local y está excluido de Git.'
} finally { Pop-Location }
