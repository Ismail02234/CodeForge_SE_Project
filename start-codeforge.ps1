$ErrorActionPreference = 'Stop'

$root = Split-Path -Parent $MyInvocation.MyCommand.Path
$backend = Join-Path $root 'backend'
$frontend = Join-Path $root 'frontend'

$php = 'D:\xampp\php\php.exe'

if (-not (Test-Path -LiteralPath $php)) {
    $command = Get-Command php -ErrorAction SilentlyContinue

    if ($null -eq $command) {
        throw 'PHP was not found.'
    }

    $php = $command.Source
}

$npmCandidates = @(
    'C:\Program Files\nodejs\npm.cmd',
    'C:\Program Files (x86)\nodejs\npm.cmd'
)

$npm = $null

foreach ($candidate in $npmCandidates) {
    if (Test-Path -LiteralPath $candidate) {
        $npm = $candidate
        break
    }
}

if ($null -eq $npm) {
    $command = Get-Command npm.cmd -ErrorAction SilentlyContinue

    if ($null -ne $command) {
        $npm = $command.Source
    }
}

if ($null -eq $npm) {
    throw 'npm.cmd was not found.'
}

if (-not (Test-Path -LiteralPath (Join-Path $backend 'vendor\autoload.php'))) {
    throw 'Laravel dependencies are not installed. Run setup-codeforge.ps1 first.'
}

if (-not (Test-Path -LiteralPath (Join-Path $frontend 'node_modules'))) {
    throw 'SvelteKit dependencies are not installed. Run setup-codeforge.ps1 first.'
}

function Port-IsListening([int]$Port) {
    $connection = Get-NetTCPConnection -State Listen -LocalPort $Port -ErrorAction SilentlyContinue
    return $null -ne $connection
}

function Wait-Http(
    [string]$Url,
    [int]$Seconds = 20
) {
    $deadline = (Get-Date).AddSeconds($Seconds)

    while ((Get-Date) -lt $deadline) {
        try {
            $response = Invoke-WebRequest `
                -Uri $Url `
                -Method GET `
                -UseBasicParsing `
                -TimeoutSec 2

            if ($response.StatusCode -ge 200 -and $response.StatusCode -lt 500) {
                return $true
            }
        }
        catch {
            Start-Sleep -Milliseconds 500
        }
    }

    return $false
}

Write-Host ''
Write-Host 'CodeForge startup check' -ForegroundColor Cyan
Write-Host '-----------------------' -ForegroundColor DarkGray

Write-Host '[1/5] Clearing Laravel runtime caches...'
Push-Location $backend

try {
    & $php artisan optimize:clear | Out-Null

    if ($LASTEXITCODE -ne 0) {
        throw 'Laravel optimize:clear failed.'
    }
}
finally {
    Pop-Location
}

Write-Host '[OK] Laravel caches cleared.' -ForegroundColor Green

Write-Host '[2/5] Checking backend port 8000...'

if (Port-IsListening 8000) {
    if (Wait-Http 'http://localhost:8000/api/public/stats' 3) {
        Write-Host '[OK] Existing Laravel backend is healthy.' -ForegroundColor Green
    }
    else {
        Write-Host '[ERROR] Port 8000 is occupied, but CodeForge backend is not responding.' -ForegroundColor Red
        Write-Host 'Close the process using port 8000, then run START-CODEFORGE.bat again.' -ForegroundColor Yellow
        exit 1
    }
}
else {
    $backendCommand =
        "Set-Location '$backend'; " +
        "Write-Host 'CODEFORGE LARAVEL API - http://localhost:8000' -ForegroundColor Cyan; " +
        "& '$php' artisan serve --host=localhost --port=8000"

    Start-Process powershell -ArgumentList `
        '-NoExit',
        '-ExecutionPolicy',
        'Bypass',
        '-Command',
        $backendCommand

    if (-not (Wait-Http 'http://localhost:8000/api/public/stats' 20)) {
        Write-Host '[ERROR] Laravel started but did not become reachable on http://localhost:8000.' -ForegroundColor Red
        Write-Host 'Check the Laravel PowerShell window for the actual PHP/Laravel error.' -ForegroundColor Yellow
        exit 1
    }

    Write-Host '[OK] Laravel backend is reachable.' -ForegroundColor Green
}

Write-Host '[3/5] Checking Sanctum CSRF endpoint...'

try {
    $csrfResponse = Invoke-WebRequest `
        -Uri 'http://localhost:8000/sanctum/csrf-cookie' `
        -Method GET `
        -Headers @{
            Accept = 'application/json'
            Origin = 'http://localhost:5173'
        } `
        -UseBasicParsing `
        -TimeoutSec 5

    if ($csrfResponse.StatusCode -notin @(200, 204)) {
        throw "Unexpected status $($csrfResponse.StatusCode)"
    }

    Write-Host '[OK] Sanctum CSRF endpoint is reachable.' -ForegroundColor Green
}
catch {
    Write-Host '[ERROR] Sanctum login-session endpoint is not healthy.' -ForegroundColor Red
    Write-Host $_.Exception.Message -ForegroundColor Yellow
    exit 1
}

Write-Host '[4/5] Starting/checking SvelteKit frontend...'

if (Port-IsListening 5173) {
    Write-Host '[OK] Frontend port 5173 is already listening.' -ForegroundColor Green
}
else {
    $frontendCommand =
        "Set-Location '$frontend'; " +
        "Write-Host 'CODEFORGE SVELTEKIT - http://localhost:5173' -ForegroundColor Cyan; " +
        "& '$npm' run dev -- --host localhost"

    Start-Process powershell -ArgumentList `
        '-NoExit',
        '-ExecutionPolicy',
        'Bypass',
        '-Command',
        $frontendCommand

    if (-not (Wait-Http 'http://localhost:5173' 25)) {
        Write-Host '[ERROR] SvelteKit did not become reachable on http://localhost:5173.' -ForegroundColor Red
        Write-Host 'Check the frontend PowerShell window for the npm/Vite error.' -ForegroundColor Yellow
        exit 1
    }

    Write-Host '[OK] SvelteKit frontend is reachable.' -ForegroundColor Green
}

Write-Host '[5/5] CodeForge connectivity verified.' -ForegroundColor Green
Write-Host ''
Write-Host 'Frontend: http://localhost:5173' -ForegroundColor Cyan
Write-Host 'Backend:  http://localhost:8000' -ForegroundColor Cyan
Write-Host 'Login API: http://localhost:8000/login' -ForegroundColor Cyan
Write-Host ''
Write-Host 'Both frontend and backend are ready.' -ForegroundColor Green
