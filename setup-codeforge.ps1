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

function RefreshCodeForgePath {
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

function NeedCommand([string]$name) {
    RefreshCodeForgePath

    if ($name -eq 'node') {
        foreach ($candidate in @(
            'C:\Program Files\nodejs\node.exe',
            'C:\Program Files (x86)\nodejs\node.exe'
        )) {
            if (Test-Path $candidate) {
                return $candidate
            }
        }
    }

    if ($name -eq 'npm') {
        foreach ($candidate in @(
            'C:\Program Files\nodejs\npm.cmd',
            'C:\Program Files (x86)\nodejs\npm.cmd'
        )) {
            if (Test-Path $candidate) {
                return $candidate
            }
        }
    }

    $command = Get-Command $name -ErrorAction SilentlyContinue

    if ($null -eq $command) {
        throw "$name was not found. Reopen PowerShell or verify its installation."
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