# PATCH-009C-Fix-Composer-Node-And-Resume.ps1
# Fixes the Composer stderr/PowerShell issue from PATCH-009B,
# installs Node.js LTS if needed, hardens setup-codeforge.ps1 native command handling,
# and resumes the framework setup.
#
# Run from:
#   D:\xampp\htdocs\codeforge
#
# Optional:
#   powershell -ExecutionPolicy Bypass -File .\PATCH-009C-Fix-Composer-Node-And-Resume.ps1 -DbPort 3307

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

function Refresh-ProcessPath {
    $machine = [Environment]::GetEnvironmentVariable('Path', 'Machine')
    $user = [Environment]::GetEnvironmentVariable('Path', 'User')

    $parts = @()
    if ($machine) { $parts += $machine }
    if ($user) { $parts += $user }

    $env:Path = ($parts -join ';')

    $nodeDir = 'C:\Program Files\nodejs'
    if ((Test-Path $nodeDir) -and ($env:Path -notlike "*$nodeDir*")) {
        $env:Path = "$nodeDir;$env:Path"
    }
}

$root = Split-Path -Parent $MyInvocation.MyCommand.Path
if (-not (Test-Path (Join-Path $root 'backend\composer.json'))) {
    $root = (Get-Location).Path
}

if (-not (Test-Path (Join-Path $root 'backend\composer.json'))) {
    throw 'CodeForge Laravel backend was not found. Put this patch directly inside D:\xampp\htdocs\codeforge.'
}

Set-Location $root

$php = 'D:\xampp\php\php.exe'
if (-not (Test-Path $php)) {
    $phpCommand = Get-Command php -ErrorAction SilentlyContinue
    if ($null -eq $phpCommand) {
        throw 'PHP was not found. Expected D:\xampp\php\php.exe.'
    }
    $php = $phpCommand.Source
}

Step 'Checking the framework migration'
if (-not (Test-Path (Join-Path $root 'frontend\package.json'))) {
    throw 'The SvelteKit frontend is missing.'
}
if (-not (Test-Path (Join-Path $root 'setup-codeforge.ps1'))) {
    throw 'setup-codeforge.ps1 is missing.'
}
Ok 'Laravel backend and SvelteKit frontend are present'

Step 'Checking PHP'
$phpVersion = (& $php -r "echo PHP_VERSION;").Trim()
if ([version]$phpVersion -lt [version]'8.2.0') {
    throw "Laravel 11 needs PHP 8.2+. Found $phpVersion."
}
Ok "PHP $phpVersion"

Step 'Repairing Composer handling'

$composerDir = Join-Path $root 'tools\composer'
$composerPhar = Join-Path $composerDir 'composer.phar'

if (-not (Test-Path $composerPhar)) {
    New-Item -ItemType Directory -Force -Path $composerDir | Out-Null

    $installer = Join-Path $composerDir 'composer-setup.php'

    try {
        Invoke-WebRequest -UseBasicParsing -Uri 'https://getcomposer.org/installer' -OutFile $installer
    } catch {
        throw "Could not download Composer. Check your internet connection. $($_.Exception.Message)"
    }

    $oldPreference = $ErrorActionPreference
    $ErrorActionPreference = 'Continue'
    & $php $installer --install-dir=$composerDir --filename='composer.phar'
    $composerInstallExit = $LASTEXITCODE
    $ErrorActionPreference = $oldPreference

    if ($composerInstallExit -ne 0 -or -not (Test-Path $composerPhar)) {
        throw 'Composer installation failed.'
    }

    if (Test-Path $installer) {
        Remove-Item $installer -Force
    }
}

# Important: Composer 2.10 can print PHP version information to stderr on Windows.
# We deliberately do not redirect stderr into the PowerShell error stream here.
$oldPreference = $ErrorActionPreference
$ErrorActionPreference = 'Continue'
& $php $composerPhar --version
$composerVersionExit = $LASTEXITCODE
$ErrorActionPreference = $oldPreference

if ($composerVersionExit -ne 0) {
    throw 'The local Composer installation exists but could not run.'
}

Ok "Project-local Composer ready: $composerPhar"

Step 'Checking Node.js and npm'

Refresh-ProcessPath

$nodeCommand = Get-Command node -ErrorAction SilentlyContinue
$npmCommand = Get-Command npm -ErrorAction SilentlyContinue

if ($null -eq $nodeCommand -or $null -eq $npmCommand) {
    Write-Host 'Node.js/npm are not installed. Installing Node.js LTS...' -ForegroundColor White

    $winget = Get-Command winget -ErrorAction SilentlyContinue

    if ($null -eq $winget) {
        throw @'
Node.js is missing and winget is not available.

Install the LTS version of Node.js from:
https://nodejs.org/

Then reopen PowerShell and rerun PATCH-009C.
'@
    }

    $oldPreference = $ErrorActionPreference
    $ErrorActionPreference = 'Continue'

    & winget install --exact --id OpenJS.NodeJS.LTS `
        --accept-package-agreements `
        --accept-source-agreements `
        --silent

    $wingetExit = $LASTEXITCODE
    $ErrorActionPreference = $oldPreference

    Refresh-ProcessPath

    $nodeCommand = Get-Command node -ErrorAction SilentlyContinue
    $npmCommand = Get-Command npm -ErrorAction SilentlyContinue

    if ($null -eq $nodeCommand -or $null -eq $npmCommand) {
        if ($wingetExit -eq 0) {
            throw 'Node.js was installed, but this PowerShell process cannot see it yet. Close PowerShell, open a new one, and rerun PATCH-009C.'
        }

        throw "Node.js installation failed (winget exit code $wingetExit)."
    }

    Ok 'Node.js LTS installed'
}

$nodeVersion = (& $nodeCommand.Source --version).Trim()
$nodeMajor = [int]($nodeVersion.TrimStart('v').Split('.')[0])

if ($nodeMajor -lt 20) {
    throw "Node.js 20+ is required. Found $nodeVersion."
}

$npmVersion = (& $npmCommand.Source --version).Trim()

Ok "Node.js $nodeVersion"
Ok "npm $npmVersion"

Step 'Backing up and repairing setup-codeforge.ps1'

$stamp = Get-Date -Format 'yyyyMMdd-HHmmss'
$backupDir = Join-Path $root "patch-backups\PATCH-009C-$stamp"
New-Item -ItemType Directory -Force -Path $backupDir | Out-Null
Copy-Item (Join-Path $root 'setup-codeforge.ps1') (Join-Path $backupDir 'setup-codeforge.ps1') -Force

$fixedSetup = @'
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
        if (-not $task.Wait(600)) {
            return $false
        }
        return $client.Connected
    } catch {
        return $false
    } finally {
        $client.Dispose()
    }
}

function SetEnvValue([string]$path, [string]$name, [string]$value) {
    $content = [System.IO.File]::ReadAllText($path)
    $pattern = "(?m)^" + [Regex]::Escape($name) + "=.*$"
    $replacement = "$name=$value"

    if ([Regex]::IsMatch($content, $pattern)) {
        $content = [Regex]::Replace($content, $pattern, $replacement)
    } else {
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
    $exitCode = $LASTEXITCODE

    $ErrorActionPreference = $oldPreference

    if ($exitCode -ne 0) {
        throw "$FailureMessage (exit code $exitCode)"
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

if (Test-Path $composerPhar) {
    Write-Host '[OK] Project-local Composer found' -ForegroundColor Green
} else {
    Write-Host '[OK] Global Composer found' -ForegroundColor Green
}

Write-Host '[OK] npm found' -ForegroundColor Green

Step 'Preparing environment files'

$backendEnv = Join-Path $backend '.env'
$frontendEnv = Join-Path $frontend '.env'

if (-not (Test-Path $backendEnv)) {
    Copy-Item (Join-Path $backend '.env.example') $backendEnv
    Write-Host '[OK] backend/.env created' -ForegroundColor Green
}

if (-not (Test-Path $frontendEnv)) {
    Copy-Item (Join-Path $frontend '.env.example') $frontendEnv
    Write-Host '[OK] frontend/.env created' -ForegroundColor Green
}

if ($DbPort -eq 0) {
    if (PortOpen 3307) {
        $DbPort = 3307
    } elseif (PortOpen 3306) {
        $DbPort = 3306
    } else {
        $DbPort = 3307
    }
}

SetEnvValue $backendEnv 'DB_PORT' "$DbPort"
Write-Host "[OK] MySQL port configured as $DbPort" -ForegroundColor Green

if (-not (PortOpen $DbPort)) {
    Write-Host "[WARN] Nothing is listening on MySQL port $DbPort." -ForegroundColor Yellow
    Write-Host '       Start MySQL in XAMPP before migrations run.' -ForegroundColor Yellow
}

Step 'Installing Laravel 11 dependencies'

Push-Location $backend

try {
    if (Test-Path $composerPhar) {
        RunNative $php @(
            $composerPhar,
            'install',
            '--no-interaction',
            '--prefer-dist',
            '--optimize-autoloader'
        ) 'composer install failed'
    } else {
        RunNative $composerExe @(
            'install',
            '--no-interaction',
            '--prefer-dist',
            '--optimize-autoloader'
        ) 'composer install failed'
    }

    Step 'Generating Laravel application key'
    RunNative $php @('artisan', 'key:generate', '--force') 'artisan key:generate failed'

    Step 'Applying non-destructive MySQL migrations and indexes'
    RunNative $php @('artisan', 'migrate', '--force') "Laravel could not migrate MySQL. Check backend/.env and confirm MySQL is running on port $DbPort"

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
    RunNative $npm @(
        'install',
        '--no-audit',
        '--no-fund'
    ) 'npm install failed'

    Step 'Running frontend contract tests'
    RunNative $npm @(
        'run',
        'test:contract'
    ) 'Frontend contract tests failed'

    if (-not $SkipFrontendBuild) {
        Step 'Running Svelte compiler and TypeScript checks'
        RunNative $npm @(
            'run',
            'check'
        ) 'npm run check failed'

        Step 'Building SvelteKit production bundle'
        RunNative $npm @(
            'run',
            'build'
        ) 'npm run build failed'
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
} else {
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
Write-Host 'Production-build start:' -ForegroundColor White
Write-Host '  powershell -ExecutionPolicy Bypass -File .\start-codeforge-production.ps1' -ForegroundColor Yellow
Write-Host ''
Write-Host 'Frontend: http://localhost:5173' -ForegroundColor Cyan
Write-Host 'Backend:  http://localhost:8000' -ForegroundColor Cyan
'@

[System.IO.File]::WriteAllText(
    (Join-Path $root 'setup-codeforge.ps1'),
    $fixedSetup,
    (New-Object System.Text.UTF8Encoding($false))
)

Ok 'setup-codeforge.ps1 repaired'
Write-Host "Backup: $backupDir" -ForegroundColor DarkGray

Step 'Resuming the complete framework setup'

$setupArgs = @(
    '-NoProfile',
    '-ExecutionPolicy',
    'Bypass',
    '-File',
    (Join-Path $root 'setup-codeforge.ps1')
)

if ($DbPort -gt 0) {
    $setupArgs += @('-DbPort', "$DbPort")
}

$oldPreference = $ErrorActionPreference
$ErrorActionPreference = 'Continue'

& powershell @setupArgs
$setupExit = $LASTEXITCODE

$ErrorActionPreference = $oldPreference

if ($setupExit -ne 0) {
    Write-Host ''
    Warn "The repaired setup script stopped with exit code $setupExit."
    Write-Host ''
    Write-Host 'Composer/Node handling has now been fixed permanently.' -ForegroundColor Yellow
    Write-Host 'Read the LAST error above; it will be the next actual setup issue.' -ForegroundColor Yellow
    Write-Host ''
    Write-Host 'After fixing that issue, rerun:' -ForegroundColor Cyan
    Write-Host '  powershell -ExecutionPolicy Bypass -File .\setup-codeforge.ps1' -ForegroundColor White
    exit $setupExit
}

Step 'PATCH-009C complete'

Write-Host 'PATCH-009C COMPLETED SUCCESSFULLY' -ForegroundColor Green
Write-Host ''
Write-Host 'Composer handling: FIXED' -ForegroundColor White
Write-Host "Node.js:           $nodeVersion" -ForegroundColor White
Write-Host "npm:               $npmVersion" -ForegroundColor White
Write-Host ''
Write-Host 'Now start CodeForge with:' -ForegroundColor Cyan
Write-Host '  powershell -ExecutionPolicy Bypass -File .\start-codeforge.ps1' -ForegroundColor White
Write-Host ''
Write-Host 'Frontend: http://localhost:5173' -ForegroundColor Cyan
Write-Host 'Backend:  http://localhost:8000' -ForegroundColor Cyan
