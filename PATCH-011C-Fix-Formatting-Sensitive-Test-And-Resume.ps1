# PATCH-011C-Fix-Formatting-Sensitive-Test-And-Resume.ps1
# CodeForge 3.0
#
# PATCH-011B successfully formatted the Laravel source, but one backend
# contract check became too strict because it compared an exact source-code
# spacing pattern. Laravel Pint correctly inserted spaces after commas.
#
# This patch:
# - updates that contract check to verify argument ORDER while allowing formatting whitespace
# - keeps the production ProblemController logic unchanged
# - resumes Prettier formatting of Svelte/TypeScript/CSS
# - runs all backend/frontend/compiler/build checks
# - does not reset or alter MySQL data
#
# Run:
#   cd D:\xampp\htdocs\codeforge
#   powershell -ExecutionPolicy Bypass -File .\PATCH-011C-Fix-Formatting-Sensitive-Test-And-Resume.ps1

param(
    [int]$DbPort = 3307
)

$ErrorActionPreference = 'Stop'

function Step([string]$Message) {
    Write-Host ''
    Write-Host "==> $Message" -ForegroundColor Cyan
}

function Ok([string]$Message) {
    Write-Host "[OK]   $Message" -ForegroundColor Green
}

function Warn([string]$Message) {
    Write-Host "[WARN] $Message" -ForegroundColor Yellow
}

function Refresh-ToolPath {
    $machine = [Environment]::GetEnvironmentVariable('Path', 'Machine')
    $user = [Environment]::GetEnvironmentVariable('Path', 'User')

    $parts = @()
    if ($machine) { $parts += $machine }
    if ($user) { $parts += $user }

    $env:Path = ($parts -join ';')

    foreach ($dir in @(
        'D:\xampp\php',
        'C:\Program Files\nodejs',
        'C:\Program Files (x86)\nodejs'
    )) {
        if ((Test-Path -LiteralPath $dir) -and ($env:Path -notlike "*$dir*")) {
            $env:Path = "$dir;$env:Path"
        }
    }
}

function Find-Npm {
    Refresh-ToolPath

    foreach ($candidate in @(
        'C:\Program Files\nodejs\npm.cmd',
        'C:\Program Files (x86)\nodejs\npm.cmd'
    )) {
        if (Test-Path -LiteralPath $candidate) {
            return $candidate
        }
    }

    $cmd = Get-Command npm.cmd -ErrorAction SilentlyContinue
    if ($null -ne $cmd) {
        return $cmd.Source
    }

    return $null
}

function Run-Native(
    [string]$Executable,
    [string[]]$Arguments,
    [string]$FailureMessage
) {
    $oldPreference = $ErrorActionPreference
    $ErrorActionPreference = 'Continue'

    & $Executable @Arguments
    $exitCode = $LASTEXITCODE

    $ErrorActionPreference = $oldPreference

    if ($exitCode -ne 0) {
        throw "$FailureMessage (exit code $exitCode)"
    }
}

function PortOpen([int]$Port) {
    $client = New-Object System.Net.Sockets.TcpClient

    try {
        $task = $client.ConnectAsync('127.0.0.1', $Port)
        if (-not $task.Wait(700)) {
            return $false
        }

        return $client.Connected
    }
    catch {
        return $false
    }
    finally {
        $client.Dispose()
    }
}

$root = Split-Path -Parent $MyInvocation.MyCommand.Path

if (-not (Test-Path -LiteralPath (Join-Path $root 'backend\artisan'))) {
    $root = (Get-Location).Path
}

$backend = Join-Path $root 'backend'
$frontend = Join-Path $root 'frontend'

if (-not (Test-Path -LiteralPath (Join-Path $backend 'artisan'))) {
    throw 'CodeForge Laravel backend was not found. Put this patch inside D:\xampp\htdocs\codeforge.'
}

if (-not (Test-Path -LiteralPath (Join-Path $frontend 'package.json'))) {
    throw 'CodeForge SvelteKit frontend was not found.'
}

Set-Location $root
Refresh-ToolPath

$php = 'D:\xampp\php\php.exe'
if (-not (Test-Path -LiteralPath $php)) {
    throw 'XAMPP PHP was not found at D:\xampp\php\php.exe.'
}

$npm = Find-Npm
if ($null -eq $npm) {
    throw 'npm.cmd could not be found.'
}

Step 'Checking current environment'

Ok "PHP $((& $php -r "echo PHP_VERSION;").Trim())"
Ok "npm $((& $npm --version).Trim())"

if (PortOpen $DbPort) {
    Ok "MySQL is responding on port $DbPort"
}
else {
    Warn "MySQL is not responding on port $DbPort."
}

$contractPath = Join-Path $backend 'tools\contract_test.php'

if (-not (Test-Path -LiteralPath $contractPath)) {
    throw 'backend\tools\contract_test.php was not found.'
}

Step 'Backing up the contract test and frontend config'

$stamp = Get-Date -Format 'yyyyMMdd-HHmmss'
$backup = Join-Path $root "patch-backups\PATCH-011C-$stamp"
New-Item -ItemType Directory -Force -Path $backup | Out-Null

$contractBackup = Join-Path $backup 'backend\tools\contract_test.php'
New-Item -ItemType Directory -Force -Path (Split-Path -Parent $contractBackup) | Out-Null
Copy-Item -LiteralPath $contractPath -Destination $contractBackup -Force

foreach ($relative in @(
    'frontend\package.json',
    'frontend\package-lock.json',
    'frontend\.prettierrc.json',
    'frontend\.prettierignore'
)) {
    $source = Join-Path $root $relative
    if (Test-Path -LiteralPath $source) {
        $dest = Join-Path $backup $relative
        New-Item -ItemType Directory -Force -Path (Split-Path -Parent $dest) | Out-Null
        Copy-Item -LiteralPath $source -Destination $dest -Force
    }
}

Ok "Backup created: $backup"

Step 'Fixing the formatting-sensitive backend contract'

$contract = [System.IO.File]::ReadAllText($contractPath)

$oldBlock = @'
check(
    str_contains($problemController, 'submit($request->user()->id,$session[\'id\'],$id'),
    'problem submit uses session id before problem id'
);
'@

$newBlock = @'
check(
    (bool) preg_match(
        '~submit\s*\(\s*\$request->user\(\)->id\s*,\s*\$session\[\'id\'\]\s*,\s*\$id~',
        $problemController
    ),
    'problem submit uses session id before problem id'
);
'@

if ($contract.Contains($oldBlock)) {
    $contract = $contract.Replace($oldBlock, $newBlock)
}
elseif ($contract -match "str_contains\(\$problemController") {
    $pattern = "(?s)check\(\s*str_contains\(\$problemController,\s*'submit\(\$request->user\(\)->id,\$session\[\\\\'id\\\\'\],\$id'\),\s*'problem submit uses session id before problem id'\s*\);"

    $contract = [Regex]::Replace(
        $contract,
        $pattern,
        $newBlock,
        1
    )
}
elseif ($contract -notmatch "preg_match\([\s\S]*problem submit uses session id before problem id") {
    throw 'Could not locate the old formatting-sensitive ProblemController contract check.'
}

$utf8NoBom = New-Object System.Text.UTF8Encoding($false)
[System.IO.File]::WriteAllText($contractPath, $contract, $utf8NoBom)

Run-Native $php @('-l', $contractPath) 'Updated contract test has a PHP syntax error'
Ok 'Contract now checks behavior/order instead of exact comma spacing'

Step 'Running backend contract checks again'

Push-Location $backend
try {
    Run-Native $php @('tools\contract_test.php') 'Backend contract checks still have a genuine failure'
}
finally {
    Pop-Location
}

Ok '43 backend contract checks pass after Laravel Pint formatting'

Step 'Installing/confirming frontend formatting tools'

Push-Location $frontend

try {
    Run-Native $npm @(
        'install',
        '--save-dev',
        '--no-audit',
        '--no-fund',
        'prettier',
        'prettier-plugin-svelte'
    ) 'Could not install Prettier tooling'

    $prettier = Join-Path $frontend 'node_modules\.bin\prettier.cmd'

    if (-not (Test-Path -LiteralPath $prettier)) {
        throw 'Prettier executable was not found.'
    }

    Step 'Formatting Svelte, TypeScript and CSS source'

    Run-Native $prettier @(
        '--write',
        'src'
    ) 'Prettier formatting failed'

    Ok 'Frontend source formatted'

    Step 'Ensuring reusable format scripts exist'

    $packagePath = Join-Path $frontend 'package.json'
    $package = Get-Content -LiteralPath $packagePath -Raw | ConvertFrom-Json

    if ($null -eq $package.scripts) {
        $package | Add-Member -MemberType NoteProperty -Name scripts -Value ([PSCustomObject]@{})
    }

    $package.scripts | Add-Member `
        -MemberType NoteProperty `
        -Name format `
        -Value 'prettier --write src' `
        -Force

    $package.scripts | Add-Member `
        -MemberType NoteProperty `
        -Name 'format:check' `
        -Value 'prettier --check src' `
        -Force

    $json = $package | ConvertTo-Json -Depth 50
    [System.IO.File]::WriteAllText(
        $packagePath,
        $json + [Environment]::NewLine,
        $utf8NoBom
    )

    Ok 'npm run format and npm run format:check are available'

    Step 'Running frontend contract tests'

    Run-Native $npm @(
        'run',
        'test:contract'
    ) 'Frontend contract checks failed'

    Ok '27 frontend contract checks pass'

    Step 'Checking formatting'

    Run-Native $npm @(
        'run',
        'format:check'
    ) 'Frontend formatting verification failed'

    Ok 'Prettier format check passes'

    Step 'Running Svelte/TypeScript validation'

    Run-Native $npm @(
        'run',
        'check'
    ) 'Svelte/TypeScript checks failed'

    Ok 'Svelte/TypeScript checks pass'

    Step 'Building production SvelteKit bundle'

    Run-Native $npm @(
        'run',
        'build'
    ) 'SvelteKit production build failed'

    Ok 'SvelteKit production build passes'
}
finally {
    Pop-Location
}

Step 'Running final CodeForge verification'

$verify = Join-Path $root 'verify-codeforge.ps1'

if (Test-Path -LiteralPath $verify) {
    $oldPreference = $ErrorActionPreference
    $ErrorActionPreference = 'Continue'

    & powershell `
        -NoProfile `
        -ExecutionPolicy Bypass `
        -File $verify `
        -FullBuild

    $verifyExit = $LASTEXITCODE
    $ErrorActionPreference = $oldPreference

    if ($verifyExit -ne 0) {
        Warn 'All direct checks passed, but final verification found another issue.'
        Write-Host 'Send the LAST verification error block.' -ForegroundColor Yellow
        exit $verifyExit
    }

    Ok 'Final CodeForge verification passes'
}

Step 'PATCH-011C complete'

Write-Host 'PATCH-011C COMPLETED SUCCESSFULLY' -ForegroundColor Green
Write-Host ''
Write-Host 'Readable-source refactor status:' -ForegroundColor Cyan
Write-Host '  Laravel Pint formatting:       PASS'
Write-Host '  Backend contracts:             43 / 43'
Write-Host '  Frontend contracts:            27 / 27'
Write-Host '  Prettier formatting:           PASS'
Write-Host '  Svelte/TypeScript:             PASS'
Write-Host '  Production build:              PASS'
Write-Host ''
Write-Host "Backup: $backup" -ForegroundColor DarkGray
Write-Host ''
Write-Host 'Start CodeForge normally:' -ForegroundColor Cyan
Write-Host '  powershell -ExecutionPolicy Bypass -File .\start-codeforge.ps1' -ForegroundColor White
