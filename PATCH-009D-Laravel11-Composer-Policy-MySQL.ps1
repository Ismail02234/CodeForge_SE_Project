# PATCH-009D-Laravel11-Composer-Policy-MySQL.ps1
# CodeForge 3.0
#
# Fixes the current setup blockers:
#   1. Composer 2.10 security-policy blocking of Laravel 11
#   2. No composer.lock on the first dependency resolution
#   3. MySQL/XAMPP port detection and optional automatic start
#   4. Keeps Composer security audit visible after install
#
# IMPORTANT:
# Laravel 11 is intentionally retained because this project was requested on Laravel 11.
# This patch does NOT globally disable Composer security policy.
# It uses --no-blocking only for this Laravel 11 dependency resolution and then runs
# Composer audit so known advisories remain visible.
#
# Run:
#   cd D:\xampp\htdocs\codeforge
#   powershell -ExecutionPolicy Bypass -File .\PATCH-009D-Laravel11-Composer-Policy-MySQL.ps1
#
# If you know the MySQL port:
#   powershell -ExecutionPolicy Bypass -File .\PATCH-009D-Laravel11-Composer-Policy-MySQL.ps1 -DbPort 3307

param(
    [int]$DbPort = 0
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

function Read-XamppMySqlPort {
    $files = @(
        'D:\xampp\mysql\bin\my.ini',
        'D:\xampp\mysql\my.ini'
    )

    foreach ($file in $files) {
        if (-not (Test-Path $file)) {
            continue
        }

        $content = Get-Content $file
        $insideMysqld = $false

        foreach ($line in $content) {
            $trimmed = $line.Trim()

            if ($trimmed -match '^\[(.+)\]$') {
                $insideMysqld = ($Matches[1].ToLowerInvariant() -eq 'mysqld')
                continue
            }

            if ($insideMysqld -and $trimmed -match '^port\s*=\s*(\d+)\s*$') {
                return [int]$Matches[1]
            }
        }
    }

    return 0
}

function Start-XamppMySql([int]$Port) {
    if (PortOpen $Port) {
        return $true
    }

    $startBat = 'D:\xampp\mysql_start.bat'

    if (Test-Path $startBat) {
        Warn "MySQL is not currently listening on port $Port. Trying XAMPP mysql_start.bat..."

        Start-Process `
            -FilePath 'cmd.exe' `
            -ArgumentList '/c', "`"$startBat`"" `
            -WorkingDirectory 'D:\xampp' `
            -WindowStyle Hidden | Out-Null

        for ($i = 0; $i -lt 20; $i++) {
            Start-Sleep -Seconds 1
            if (PortOpen $Port) {
                return $true
            }
        }
    }

    return (PortOpen $Port)
}

function SetEnvValue([string]$Path, [string]$Name, [string]$Value) {
    $content = [System.IO.File]::ReadAllText($Path)
    $pattern = "(?m)^" + [Regex]::Escape($Name) + "=.*$"
    $replacement = "$Name=$Value"

    if ([Regex]::IsMatch($content, $pattern)) {
        $content = [Regex]::Replace($content, $pattern, $replacement)
    }
    else {
        $content = $content.TrimEnd() + [Environment]::NewLine + $replacement + [Environment]::NewLine
    }

    [System.IO.File]::WriteAllText(
        $Path,
        $content,
        (New-Object System.Text.UTF8Encoding($false))
    )
}

function RunNative(
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

$root = Split-Path -Parent $MyInvocation.MyCommand.Path

if (-not (Test-Path (Join-Path $root 'backend\composer.json'))) {
    $root = (Get-Location).Path
}

$backend = Join-Path $root 'backend'
$frontend = Join-Path $root 'frontend'
$setupPath = Join-Path $root 'setup-codeforge.ps1'
$composerJsonPath = Join-Path $backend 'composer.json'

if (-not (Test-Path $composerJsonPath)) {
    throw 'Laravel backend/composer.json was not found. Put this patch in D:\xampp\htdocs\codeforge.'
}

if (-not (Test-Path (Join-Path $frontend 'package.json'))) {
    throw 'SvelteKit frontend/package.json was not found.'
}

Set-Location $root

Step 'Checking prerequisites already installed'

$php = 'D:\xampp\php\php.exe'
if (-not (Test-Path $php)) {
    throw 'XAMPP PHP was not found at D:\xampp\php\php.exe.'
}

$composerPhar = Join-Path $root 'tools\composer\composer.phar'
if (-not (Test-Path $composerPhar)) {
    throw 'Project-local Composer was not found. Run PATCH-009C first.'
}

$node = Get-Command node -ErrorAction SilentlyContinue
$npm = Get-Command npm -ErrorAction SilentlyContinue

if ($null -eq $node -or $null -eq $npm) {
    $nodeDir = 'C:\Program Files\nodejs'
    if (Test-Path $nodeDir) {
        $env:Path = "$nodeDir;$env:Path"
        $node = Get-Command node -ErrorAction SilentlyContinue
        $npm = Get-Command npm -ErrorAction SilentlyContinue
    }
}

if ($null -eq $node -or $null -eq $npm) {
    throw 'Node.js/npm are not visible. Close PowerShell, reopen it, and rerun PATCH-009D.'
}

$phpVersion = (& $php -r "echo PHP_VERSION;").Trim()
$nodeVersion = (& $node.Source --version).Trim()
$npmVersion = (& $npm.Source --version).Trim()

Ok "PHP $phpVersion"
Ok "Node.js $nodeVersion"
Ok "npm $npmVersion"
Ok 'Project-local Composer 2.10.x found'

Warn 'Laravel 11 reached the end of its official security-fix window on March 12, 2026.'
Warn 'The project will stay on Laravel 11 because that is the requested framework version.'
Warn 'Composer advisory blocking will be bypassed only for this dependency resolution; audit reporting remains enabled.'

Step 'Creating PATCH-009D backup'

$stamp = Get-Date -Format 'yyyyMMdd-HHmmss'
$backup = Join-Path $root "patch-backups\PATCH-009D-$stamp"
New-Item -ItemType Directory -Force -Path $backup | Out-Null

Copy-Item $composerJsonPath (Join-Path $backup 'composer.json') -Force

if (Test-Path $setupPath) {
    Copy-Item $setupPath (Join-Path $backup 'setup-codeforge.ps1') -Force
}

$backendEnv = Join-Path $backend '.env'
if (Test-Path $backendEnv) {
    Copy-Item $backendEnv (Join-Path $backup 'backend.env') -Force
}

Ok "Backup created: $backup"

Step 'Pinning to the newest available Laravel 11 release'

$composerJson = Get-Content $composerJsonPath -Raw | ConvertFrom-Json

# Keep this project strictly on Laravel 11 and choose the highest available
# Laravel 11 patch release rather than allowing an older vulnerable 11.x build.
$composerJson.require.'laravel/framework' = '11.56.1'

$composerJson |
    ConvertTo-Json -Depth 50 |
    Set-Content -Path $composerJsonPath -Encoding UTF8

# Remove BOM because PHP/Composer JSON tools do not need one.
$jsonText = [System.IO.File]::ReadAllText($composerJsonPath)
[System.IO.File]::WriteAllText(
    $composerJsonPath,
    $jsonText,
    (New-Object System.Text.UTF8Encoding($false))
)

Ok 'laravel/framework pinned to 11.56.1'

Step 'Finding the XAMPP MySQL port'

if ($DbPort -le 0) {
    if (PortOpen 3307) {
        $DbPort = 3307
    }
    elseif (PortOpen 3306) {
        $DbPort = 3306
    }
    else {
        $configuredPort = Read-XamppMySqlPort

        if ($configuredPort -gt 0) {
            $DbPort = $configuredPort
            Write-Host "Detected port $DbPort from XAMPP my.ini" -ForegroundColor White
        }
        else {
            $DbPort = 3307
            Warn 'Could not read a MySQL port from XAMPP configuration; using the previous CodeForge port 3307.'
        }
    }
}

if (-not (Test-Path $backendEnv)) {
    Copy-Item (Join-Path $backend '.env.example') $backendEnv
}

SetEnvValue $backendEnv 'DB_CONNECTION' 'mysql'
SetEnvValue $backendEnv 'DB_HOST' '127.0.0.1'
SetEnvValue $backendEnv 'DB_PORT' "$DbPort"
SetEnvValue $backendEnv 'DB_DATABASE' 'project'
SetEnvValue $backendEnv 'DB_USERNAME' 'root'

Ok "Laravel database port set to $DbPort"

if (Start-XamppMySql $DbPort) {
    Ok "MySQL is responding on port $DbPort"
}
else {
    Warn "MySQL is still not listening on port $DbPort."
    Warn 'Dependency installation can continue, but Laravel migration will need MySQL running.'
}

Step 'Resolving Laravel 11 dependencies with Composer'

Push-Location $backend

try {
    $composerArgs = @(
        $composerPhar
    )

    if (Test-Path (Join-Path $backend 'composer.lock')) {
        Write-Host 'composer.lock exists; running install.' -ForegroundColor White
        $composerArgs += @(
            'install',
            '--no-interaction',
            '--prefer-dist',
            '--optimize-autoloader',
            '--no-blocking'
        )
    }
    else {
        Write-Host 'No composer.lock exists; resolving once and creating a lock file.' -ForegroundColor White
        $composerArgs += @(
            'update',
            '--no-interaction',
            '--prefer-dist',
            '--optimize-autoloader',
            '--no-blocking'
        )
    }

    RunNative $php $composerArgs 'Laravel 11 dependency resolution failed'

    Ok 'Laravel dependencies installed and composer.lock created'

    Step 'Running Composer security audit (reporting only)'

    $oldPreference = $ErrorActionPreference
    $ErrorActionPreference = 'Continue'

    & $php $composerPhar audit --locked --format=summary
    $auditExit = $LASTEXITCODE

    $ErrorActionPreference = $oldPreference

    if ($auditExit -eq 0) {
        Ok 'Composer audit reported no dependency-policy findings'
    }
    else {
        Warn 'Composer audit found advisories/policy findings. This is expected to remain visible because Laravel 11 is now out of security support.'
        Warn 'The setup is allowed to continue because Laravel 11 was explicitly requested.'
    }
}
finally {
    Pop-Location
}

Step 'Repairing setup-codeforge.ps1 for all future reruns'

$setup = @'
param(
    [int]$DbPort = 0,
    [switch]$SkipFrontendBuild,
    [switch]$SkipSeed
)

$ErrorActionPreference = 'Stop'

$root = Split-Path -Parent $MyInvocation.MyCommand.Path
$backend = Join-Path $root 'backend'
$frontend = Join-Path $root 'frontend'

function Step([string]$message) {
    Write-Host ''
    Write-Host "==> $message" -ForegroundColor Cyan
}

function NeedCommand([string]$name) {
    $command = Get-Command $name -ErrorAction SilentlyContinue
    if ($null -eq $command) {
        throw "$name was not found in PATH."
    }
    return $command.Source
}

function PortOpen([int]$port) {
    $client = New-Object System.Net.Sockets.TcpClient
    try {
        $task = $client.ConnectAsync('127.0.0.1', $port)
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

function ReadXamppPort {
    foreach ($file in @('D:\xampp\mysql\bin\my.ini', 'D:\xampp\mysql\my.ini')) {
        if (-not (Test-Path $file)) {
            continue
        }

        $inside = $false

        foreach ($line in Get-Content $file) {
            $value = $line.Trim()

            if ($value -match '^\[(.+)\]$') {
                $inside = ($Matches[1].ToLowerInvariant() -eq 'mysqld')
                continue
            }

            if ($inside -and $value -match '^port\s*=\s*(\d+)\s*$') {
                return [int]$Matches[1]
            }
        }
    }

    return 0
}

function SetEnvValue([string]$path, [string]$name, [string]$value) {
    $content = [System.IO.File]::ReadAllText($path)
    $pattern = "(?m)^" + [Regex]::Escape($name) + "=.*$"
    $replacement = "$name=$value"

    if ([Regex]::IsMatch($content, $pattern)) {
        $content = [Regex]::Replace($content, $pattern, $replacement)
    }
    else {
        $content = $content.TrimEnd() + [Environment]::NewLine + $replacement + [Environment]::NewLine
    }

    [System.IO.File]::WriteAllText(
        $path,
        $content,
        (New-Object System.Text.UTF8Encoding($false))
    )
}

function RunNative(
    [string]$Executable,
    [string[]]$Arguments,
    [string]$FailureMessage
) {
    $oldPreference = $ErrorActionPreference
    $ErrorActionPreference = 'Continue'

    & $Executable @Arguments
    $code = $LASTEXITCODE

    $ErrorActionPreference = $oldPreference

    if ($code -ne 0) {
        throw "$FailureMessage (exit code $code)"
    }
}

Step 'Checking prerequisites'

$php = 'D:\xampp\php\php.exe'
if (-not (Test-Path $php)) {
    $php = NeedCommand 'php'
}

$composerPhar = Join-Path $root 'tools\composer\composer.phar'
$composerExe = $null

if (-not (Test-Path $composerPhar)) {
    $composerExe = NeedCommand 'composer'
}

$node = NeedCommand 'node'
$npm = NeedCommand 'npm'

$phpVersion = (& $php -r "echo PHP_VERSION;").Trim()
$nodeVersion = (& $node -p "process.versions.node").Trim()
$nodeMajor = [int]($nodeVersion.Split('.')[0])

if ([version]$phpVersion -lt [version]'8.2.0') {
    throw "PHP 8.2+ is required. Found $phpVersion."
}

if ($nodeMajor -lt 20) {
    throw "Node.js 20+ is required. Found $nodeVersion."
}

$modules = (& $php -m) -join "`n"

if ($modules -notmatch '(?im)^pdo_mysql$') {
    throw 'PHP extension pdo_mysql is required.'
}
if ($modules -notmatch '(?im)^openssl$') {
    throw 'PHP extension openssl is required.'
}
if ($modules -notmatch '(?im)^mbstring$') {
    throw 'PHP extension mbstring is required.'
}

Write-Host "[OK] PHP $phpVersion" -ForegroundColor Green
Write-Host "[OK] Node.js $nodeVersion" -ForegroundColor Green
Write-Host '[OK] Composer ready' -ForegroundColor Green
Write-Host '[OK] npm ready' -ForegroundColor Green

Step 'Preparing environment files'

$backendEnv = Join-Path $backend '.env'
$frontendEnv = Join-Path $frontend '.env'

if (-not (Test-Path $backendEnv)) {
    Copy-Item (Join-Path $backend '.env.example') $backendEnv
}

if (-not (Test-Path $frontendEnv)) {
    Copy-Item (Join-Path $frontend '.env.example') $frontendEnv
}

if ($DbPort -eq 0) {
    if (PortOpen 3307) {
        $DbPort = 3307
    }
    elseif (PortOpen 3306) {
        $DbPort = 3306
    }
    else {
        $configuredPort = ReadXamppPort
        if ($configuredPort -gt 0) {
            $DbPort = $configuredPort
        }
        else {
            $DbPort = 3307
        }
    }
}

SetEnvValue $backendEnv 'DB_PORT' "$DbPort"

Write-Host "[OK] MySQL port configured as $DbPort" -ForegroundColor Green

if (-not (PortOpen $DbPort)) {
    throw "MySQL is not listening on port $DbPort. Start MySQL in XAMPP and rerun setup-codeforge.ps1."
}

Step 'Installing Laravel 11 dependencies'

Push-Location $backend

try {
    $composerArguments = @()

    if (Test-Path (Join-Path $backend 'composer.lock')) {
        $composerArguments = @(
            'install',
            '--no-interaction',
            '--prefer-dist',
            '--optimize-autoloader',
            '--no-blocking'
        )
    }
    else {
        $composerArguments = @(
            'update',
            '--no-interaction',
            '--prefer-dist',
            '--optimize-autoloader',
            '--no-blocking'
        )
    }

    if (Test-Path $composerPhar) {
        RunNative $php (@($composerPhar) + $composerArguments) 'Composer dependency installation failed'
    }
    else {
        RunNative $composerExe $composerArguments 'Composer dependency installation failed'
    }

    Step 'Generating Laravel application key'
    RunNative $php @('artisan', 'key:generate', '--force') 'artisan key:generate failed'

    Step 'Applying non-destructive MySQL migrations and indexes'
    RunNative $php @('artisan', 'migrate', '--force') "Laravel could not migrate MySQL on port $DbPort"

    if (-not $SkipSeed) {
        Step 'Running safe seed step'
        RunNative $php @('artisan', 'db:seed', '--force') 'Database seeding failed'
    }

    Step 'Running backend regression tests'
    RunNative $php @('tools\contract_test.php') 'Backend contract tests failed'

    Step 'Validating Laravel application boot and routes'
    RunNative $php @('artisan', 'route:list') 'Laravel route validation failed'
    RunNative $php @('artisan', 'about') 'Laravel framework boot validation failed'
}
finally {
    Pop-Location
}

Step 'Installing SvelteKit dependencies'

Push-Location $frontend

try {
    RunNative $npm @('install', '--no-audit', '--no-fund') 'npm install failed'

    Step 'Running frontend contract tests'
    RunNative $npm @('run', 'test:contract') 'Frontend contract tests failed'

    if (-not $SkipFrontendBuild) {
        Step 'Running Svelte compiler and TypeScript checks'
        RunNative $npm @('run', 'check') 'npm run check failed'

        Step 'Building SvelteKit production bundle'
        RunNative $npm @('run', 'build') 'npm run build failed'
    }
}
finally {
    Pop-Location
}

Step 'Final source verification'

$verifyScript = Join-Path $root 'verify-codeforge.ps1'

if ($SkipFrontendBuild) {
    RunNative 'powershell' @(
        '-NoProfile',
        '-ExecutionPolicy',
        'Bypass',
        '-File',
        $verifyScript
    ) 'Final verification failed'
}
else {
    RunNative 'powershell' @(
        '-NoProfile',
        '-ExecutionPolicy',
        'Bypass',
        '-File',
        $verifyScript,
        '-FullBuild'
    ) 'Final verification failed'
}

Step 'Setup complete'

Write-Host 'CODEFORGE FRAMEWORK SETUP COMPLETE' -ForegroundColor Green
Write-Host ''
Write-Host 'Development start:' -ForegroundColor White
Write-Host '  powershell -ExecutionPolicy Bypass -File .\start-codeforge.ps1' -ForegroundColor Yellow
Write-Host ''
Write-Host 'Frontend: http://localhost:5173' -ForegroundColor Cyan
Write-Host 'Backend:  http://localhost:8000' -ForegroundColor Cyan
'@

[System.IO.File]::WriteAllText(
    $setupPath,
    $setup,
    (New-Object System.Text.UTF8Encoding($false))
)

Ok 'setup-codeforge.ps1 permanently updated for Composer 2.10 policy behavior'

Step 'Continuing CodeForge setup'

if (-not (PortOpen $DbPort)) {
    Warn 'MySQL still is not running.'
    Write-Host ''
    Write-Host 'Laravel/Svelte dependencies have been repaired.' -ForegroundColor White
    Write-Host 'Start MySQL in XAMPP, then run:' -ForegroundColor Yellow
    Write-Host ''
    Write-Host "  powershell -ExecutionPolicy Bypass -File .\setup-codeforge.ps1 -DbPort $DbPort" -ForegroundColor Cyan
    Write-Host ''
    Write-Host 'Do NOT rerun PATCH-009 or restore the old project.' -ForegroundColor Yellow
    exit 0
}

$setupArgs = @(
    '-NoProfile',
    '-ExecutionPolicy',
    'Bypass',
    '-File',
    $setupPath,
    '-DbPort',
    "$DbPort"
)

$oldPreference = $ErrorActionPreference
$ErrorActionPreference = 'Continue'

& powershell @setupArgs
$setupExit = $LASTEXITCODE

$ErrorActionPreference = $oldPreference

if ($setupExit -ne 0) {
    Write-Host ''
    Warn "Setup reached the next blocker and exited with code $setupExit."
    Write-Host 'The Composer/Laravel-11 policy problem is now fixed.' -ForegroundColor Yellow
    Write-Host 'Send the LAST error block shown above for the next targeted patch.' -ForegroundColor Yellow
    exit $setupExit
}

Step 'PATCH-009D complete'

Write-Host 'PATCH-009D COMPLETED SUCCESSFULLY' -ForegroundColor Green
Write-Host ''
Write-Host 'Laravel: 11.56.1' -ForegroundColor White
Write-Host "MySQL:   port $DbPort" -ForegroundColor White
Write-Host "Backup:  $backup" -ForegroundColor DarkGray
Write-Host ''
Write-Host 'Start CodeForge:' -ForegroundColor Cyan
Write-Host '  powershell -ExecutionPolicy Bypass -File .\start-codeforge.ps1' -ForegroundColor White
Write-Host ''
Write-Host 'Frontend: http://localhost:5173' -ForegroundColor Cyan
Write-Host 'Backend:  http://localhost:8000' -ForegroundColor Cyan
