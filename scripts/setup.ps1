$ErrorActionPreference='Stop'
Set-Location (Split-Path -Parent $PSScriptRoot)
$phpPath=Join-Path $PWD '.runtime\php\php.exe'
if(!(Test-Path -LiteralPath $phpPath)) {
    New-Item -ItemType Directory -Force .runtime\php | Out-Null
    $zipPath=Join-Path $PWD '.runtime\php.zip'
    Invoke-WebRequest 'https://windows.php.net/downloads/releases/php-8.4.27-1-nts-Win32-vs17-x64.zip' -OutFile $zipPath
    if((Get-FileHash -LiteralPath $zipPath -Algorithm SHA256).Hash -ne 'DBAE154A0A89791F36C3D48E68BAE797878333E7D6CB5721E74617DE59B63AB7') { throw 'La descarga de PHP no coincide con la suma oficial.' }
    Expand-Archive -LiteralPath $zipPath -DestinationPath .runtime\php -Force
}
$ini=@'
[PHP]
extension_dir="ext"
extension=curl
extension=fileinfo
extension=mbstring
extension=openssl
extension=pdo_pgsql
extension=pgsql
extension=zip
extension=intl
extension=sodium
memory_limit=512M
date.timezone=America/Bogota
zend_extension=opcache
opcache.enable_cli=1
opcache.memory_consumption=128
opcache.validate_timestamps=1
opcache.revalidate_freq=0
'@
[IO.File]::WriteAllText((Join-Path $PWD '.runtime\php\php.ini'),$ini)
if(!(Test-Path .runtime\composer.phar)) {
    Invoke-WebRequest 'https://getcomposer.org/download/2.9.5/composer.phar' -OutFile .runtime\composer.phar
    Invoke-WebRequest 'https://getcomposer.org/download/2.9.5/composer.phar.sha256sum' -OutFile .runtime\composer.sha256
    $expected=((Get-Content .runtime\composer.sha256 -Raw).Trim() -split '\s+')[0]
    if((Get-FileHash .runtime\composer.phar -Algorithm SHA256).Hash -ne $expected) { throw 'La suma de Composer no coincide.' }
}
function Assert-Exit { if($LASTEXITCODE -ne 0){throw "El comando anterior falló con código $LASTEXITCODE."} }
& $phpPath .runtime\composer.phar install --no-interaction
Assert-Exit
npm.cmd ci --no-audit --no-fund
Assert-Exit
node scripts/prepare-env.mjs
Assert-Exit
# Preserve existing keys. Generate only for a fresh .env.
if((Get-Content .env -Raw) -match '(?m)^APP_KEY=\s*$') { & $phpPath artisan key:generate; Assert-Exit }
$running=Get-NetTCPConnection -LocalPort 55432 -State Listen -ErrorAction SilentlyContinue
if(!$running){Start-Process -FilePath (Get-Command node.exe).Source -ArgumentList @('scripts/database.mjs','start') -WorkingDirectory $PWD -WindowStyle Hidden -RedirectStandardOutput .runtime\postgres.log -RedirectStandardError .runtime\postgres-error.log}
$ready=$false
for($attempt=0;$attempt -lt 60;$attempt++) {
    if(Get-NetTCPConnection -LocalPort 55432 -State Listen -ErrorAction SilentlyContinue){$ready=$true;break}
    Start-Sleep -Milliseconds 500
}
if(!$ready){throw 'PostgreSQL no inició. Consulta .runtime/postgres-error.log.'}
& $phpPath artisan migrate --seed --force
Assert-Exit
npm.cmd run build
Assert-Exit
Write-Host 'Instalación lista. Accesos ficticios: .runtime/demo-access.txt. Inicia con scripts/start.ps1.'
