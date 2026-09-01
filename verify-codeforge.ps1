param(
    [switch]$FullBuild
)

$ErrorActionPreference = 'Stop'
$root = Split-Path -Parent $MyInvocation.MyCommand.Path
$backend = Join-Path $root 'backend'
$frontend = Join-Path $root 'frontend'

function Step([string]$message) {
    Write-Host ''
    Write-Host "==> $message" -ForegroundColor Cyan
}

function FindCommand([string]$name) {
    $cmd = Get-Command $name -ErrorAction SilentlyContinue
    if ($null -eq $cmd) { return $null }
    return $cmd.Source
}

$php = 'D:\xampp\php\php.exe'
if (-not (Test-Path $php)) { $php = FindCommand 'php' }
$node = FindCommand 'node'
$npm = FindCommand 'npm'

if (-not $php) { throw 'PHP was not found.' }
if (-not $node) { throw 'Node.js was not found.' }
if (-not $npm) { throw 'npm was not found.' }

Step 'PHP syntax audit'
$failed = $false
Get-ChildItem $backend -Recurse -File -Filter '*.php' | ForEach-Object {
    & $php -l $_.FullName *> $null
    if ($LASTEXITCODE -ne 0) {
        Write-Host "[FAIL] $($_.FullName)" -ForegroundColor Red
        $failed = $true
    }
}
if ($failed) { throw 'PHP syntax audit failed.' }
Write-Host '[OK] All backend PHP files passed lint.' -ForegroundColor Green

Step 'Backend contract tests'
& $php (Join-Path $backend 'tools\contract_test.php')
if ($LASTEXITCODE -ne 0) { throw 'Backend contract tests failed.' }

Step 'Frontend contract tests'
Push-Location $frontend
& $node 'tools\contract-test.mjs'
if ($LASTEXITCODE -ne 0) { Pop-Location; throw 'Frontend contract tests failed.' }

if ($FullBuild) {
    if (-not (Test-Path (Join-Path $backend 'vendor\autoload.php'))) {
        Pop-Location
        throw 'backend/vendor is missing. Run setup-codeforge.ps1 first.'
    }
    if (-not (Test-Path (Join-Path $frontend 'node_modules'))) {
        Pop-Location
        throw 'frontend/node_modules is missing. Run setup-codeforge.ps1 first.'
    }

    Step 'Svelte compiler and type checks'
    & $npm run check
    if ($LASTEXITCODE -ne 0) { Pop-Location; throw 'Svelte check failed.' }

    Step 'Production frontend build'
    & $npm run build
    if ($LASTEXITCODE -ne 0) { Pop-Location; throw 'Frontend build failed.' }

    Pop-Location
    Step 'Laravel framework checks'
    Push-Location $backend
    & $php artisan route:list
    if ($LASTEXITCODE -ne 0) { Pop-Location; throw 'Laravel route validation failed.' }
    & $php artisan about
    if ($LASTEXITCODE -ne 0) { Pop-Location; throw 'Laravel boot validation failed.' }
    Pop-Location
} else {
    Pop-Location
}

Step 'Verification complete'
Write-Host 'CODEFORGE VERIFICATION PASSED' -ForegroundColor Green
if (-not $FullBuild) {
    Write-Host 'Run again with -FullBuild after setup for Svelte/Laravel dependency validation.' -ForegroundColor Yellow
}
