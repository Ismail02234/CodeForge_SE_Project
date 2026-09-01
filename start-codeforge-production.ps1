# Refresh PATH because Node may have been installed after this PowerShell session opened.
$machinePath = [Environment]::GetEnvironmentVariable('Path', 'Machine')
$userPath = [Environment]::GetEnvironmentVariable('Path', 'User')
$pathParts = @()

if ($machinePath) { $pathParts += $machinePath }
if ($userPath) { $pathParts += $userPath }

$env:Path = ($pathParts -join ';')

$nodeDir = 'C:\Program Files\nodejs'
if ((Test-Path $nodeDir) -and ($env:Path -notlike "*$nodeDir*")) {
    $env:Path = "$nodeDir;$env:Path"
}

$ErrorActionPreference = 'Stop'
$root = Split-Path -Parent $MyInvocation.MyCommand.Path
$backend = Join-Path $root 'backend'
$frontend = Join-Path $root 'frontend'

$php = 'D:\xampp\php\php.exe'
if (-not (Test-Path $php)) {
    $cmd = Get-Command php -ErrorAction SilentlyContinue
    if ($null -eq $cmd) { throw 'PHP was not found.' }
    $php = $cmd.Source
}

if (-not (Test-Path (Join-Path $backend 'vendor\autoload.php'))) {
    throw 'Laravel dependencies are missing. Run setup-codeforge.ps1 first.'
}
if (-not (Test-Path (Join-Path $frontend 'node_modules'))) {
    throw 'SvelteKit dependencies are missing. Run setup-codeforge.ps1 first.'
}
if (-not (Test-Path (Join-Path $frontend 'build\index.js'))) {
    throw 'SvelteKit production build is missing. Run setup-codeforge.ps1 first.'
}

$backendCommand = "Set-Location '$backend'; & '$php' artisan serve --host=localhost --port=8000"
$frontendCommand = "Set-Location '$frontend'; `$env:HOST='localhost'; `$env:PORT='5173'; node build"

Start-Process powershell -ArgumentList '-NoExit', '-ExecutionPolicy', 'Bypass', '-Command', $backendCommand
Start-Sleep -Seconds 1
Start-Process powershell -ArgumentList '-NoExit', '-ExecutionPolicy', 'Bypass', '-Command', $frontendCommand

Write-Host 'CodeForge production servers launched.' -ForegroundColor Green
Write-Host 'Frontend: http://localhost:5173' -ForegroundColor Cyan
Write-Host 'Backend:  http://localhost:8000' -ForegroundColor Cyan
