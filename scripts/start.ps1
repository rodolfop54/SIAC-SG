$ErrorActionPreference='Stop'
Set-Location (Split-Path -Parent $PSScriptRoot)
if(!(Test-Path .env)){throw 'Ejecuta primero scripts/setup.ps1.'}
if(!(Get-NetTCPConnection -LocalPort 55432 -State Listen -ErrorAction SilentlyContinue)) {
    Start-Process -FilePath (Get-Command node.exe).Source -ArgumentList @('scripts/database.mjs','start') -WorkingDirectory $PWD -WindowStyle Hidden -RedirectStandardOutput .runtime\postgres.log -RedirectStandardError .runtime\postgres-error.log
    Start-Sleep -Seconds 3
}
& .runtime\php\php.exe -S 127.0.0.1:8000 -t public scripts/router.php
