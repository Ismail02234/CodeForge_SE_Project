# PATCH-009F-Fix-Node-Path-And-Resume.ps1
# CodeForge 3.0
#
# Fixes the current blocker:
#   "node was not found in PATH"
#
# Node.js is already installed. This patch:
# - refreshes PATH from Windows machine/user environment
# - adds C:\Program Files\nodejs for the current session if needed
# - permanently hardens setup/start scripts so they can find node/npm
# - prefers npm.cmd on Windows
# - resumes setup-codeforge.ps1
#
# Run from:
#   D:\xampp\htdocs\codeforge

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

function Refresh-ProcessPath {
    $machine = [Environment]::GetEnvironmentVariable('Path', 'Machine')
    $user = [Environment]::GetEnvironmentVariable('Path', 'User')

    $parts = @()

    if ($machine) {
        $parts += $machine
    }

    if ($user) {
        $parts += $user
    }

    $env:Path = ($parts -join ';')

    foreach ($dir in @(
        'C:\Program Files\nodejs',
        'C:\Program Files (x86)\nodejs'
    )) {
        if ((Test-Path $dir) -and ($env:Path -notlike "*$dir*")) {
            $env:Path = "$dir;$env:Path"
        }
    }
}

function Find-Node {
    Refresh-ProcessPath

    $cmd = Get-Command node.exe -ErrorAction SilentlyContinue
    if ($null -ne $cmd) {
        return $cmd.Source
    }

    foreach ($candidate in @(
        'C:\Program Files\nodejs\node.exe',
        'C:\Program Files (x86)\nodejs\node.exe'
    )) {
        if (Test-Path $candidate) {
            return $candidate
        }
    }

    return $null
}

function Find-Npm {
    Refresh-ProcessPath

    # Prefer npm.cmd on Windows to avoid PowerShell execution-policy issues with npm.ps1.
    foreach ($candidate in @(
        'C:\Program Files\nodejs\npm.cmd',
        'C:\Program Files (x86)\nodejs\npm.cmd'
    )) {
        if (Test-Path $candidate) {
            return $candidate
        }
    }

    $cmd = Get-Command npm.cmd -ErrorAction SilentlyContinue
    if ($null -ne $cmd) {
        return $cmd.Source
    }

    $cmd = Get-Command npm -ErrorAction SilentlyContinue
    if ($null -ne $cmd) {
        return $cmd.Source
    }

    return $null
}

$root = Split-Path -Parent $MyInvocation.MyCommand.Path

if (-not (Test-Path (Join-Path $root 'backend\composer.json'))) {
    $root = (Get-Location).Path
}

if (-not (Test-Path (Join-Path $root 'backend\composer.json'))) {
    throw 'CodeForge framework project was not found. Put this patch directly inside D:\xampp\htdocs\codeforge.'
}

Set-Location $root

Step 'Refreshing Windows PATH'

Refresh-ProcessPath

$node = Find-Node
$npm = Find-Npm

if ($null -eq $node) {
    throw @'
Node.js was installed previously but node.exe cannot be found.

Expected location:
  C:\Program Files\nodejs\node.exe

If that file does not exist, reinstall Node.js LTS, then rerun PATCH-009F.
'@
}

if ($null -eq $npm) {
    throw 'Node.js was found, but npm.cmd could not be located.'
}

$nodeDir = Split-Path -Parent $node

if ($env:Path -notlike "*$nodeDir*") {
    $env:Path = "$nodeDir;$env:Path"
}

$nodeVersion = (& $node --version).Trim()
$npmVersion = (& $npm --version).Trim()

Ok "Node.js $nodeVersion"
Ok "npm $npmVersion"
Ok "Node directory: $nodeDir"

Step 'Creating backup of framework scripts'

$stamp = Get-Date -Format 'yyyyMMdd-HHmmss'
$backup = Join-Path $root "patch-backups\PATCH-009F-$stamp"

New-Item -ItemType Directory -Force -Path $backup | Out-Null

foreach ($file in @(
    'setup-codeforge.ps1',
    'start-codeforge.ps1',
    'start-codeforge-production.ps1',
    'verify-codeforge.ps1'
)) {
    $source = Join-Path $root $file

    if (Test-Path $source) {
        Copy-Item $source (Join-Path $backup $file) -Force
    }
}

Ok "Backup created: $backup"

Step 'Hardening setup-codeforge.ps1 Node/npm detection'

$setupPath = Join-Path $root 'setup-codeforge.ps1'

if (-not (Test-Path $setupPath)) {
    throw 'setup-codeforge.ps1 is missing.'
}

$setup = [System.IO.File]::ReadAllText($setupPath)

$oldNeedCommand = @'
function NeedCommand([string]$name) {
    $command = Get-Command $name -ErrorAction SilentlyContinue
    if ($null -eq $command) {
        throw "$name was not found in PATH."
    }
    return $command.Source
}
'@

$newNeedCommand = @'
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
'@

if ($setup.Contains($oldNeedCommand)) {
    $setup = $setup.Replace($oldNeedCommand, $newNeedCommand)
}
elseif ($setup -notmatch 'function RefreshCodeForgePath') {
    # Fallback insertion directly before the existing NeedCommand function.
    $needle = 'function NeedCommand([string]$name) {'
    if ($setup.Contains($needle)) {
        $setup = $setup.Replace(
            $needle,
            $newNeedCommand + [Environment]::NewLine + '# Original function replaced below' + [Environment]::NewLine + 'function __OldNeedCommand([string]$name) {'
        )
    }
    else {
        throw 'Could not find NeedCommand in setup-codeforge.ps1.'
    }
}

[System.IO.File]::WriteAllText(
    $setupPath,
    $setup,
    (New-Object System.Text.UTF8Encoding($false))
)

Ok 'setup-codeforge.ps1 now finds Node/npm even in an old PowerShell session'

Step 'Hardening CodeForge start scripts'

$pathBootstrap = @'
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
'@

foreach ($file in @(
    'start-codeforge.ps1',
    'start-codeforge-production.ps1'
)) {
    $path = Join-Path $root $file

    if (-not (Test-Path $path)) {
        continue
    }

    $content = [System.IO.File]::ReadAllText($path)

    if ($content -notmatch 'Refresh PATH because Node may have been installed') {
        $content = $pathBootstrap + [Environment]::NewLine + [Environment]::NewLine + $content

        [System.IO.File]::WriteAllText(
            $path,
            $content,
            (New-Object System.Text.UTF8Encoding($false))
        )
    }

    Ok "$file hardened"
}

Step 'Verifying Composer/MySQL state'

$composerLock = Join-Path $root 'backend\composer.lock'
$composerPhar = Join-Path $root 'tools\composer\composer.phar'
$php = 'D:\xampp\php\php.exe'

if (Test-Path $composerLock) {
    Ok 'composer.lock exists'
}
else {
    Warn 'composer.lock is missing; Composer will resolve dependencies again.'
}

if (-not (Test-Path $composerPhar)) {
    throw 'Project-local Composer is missing.'
}

$zipModules = (& $php -m) -join "`n"

if ($zipModules -match '(?im)^zip$') {
    Ok 'PHP ZIP extension loaded'
}
else {
    throw 'PHP ZIP extension is no longer loaded.'
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

if (PortOpen $DbPort) {
    Ok "MySQL is responding on port $DbPort"
}
else {
    Warn "MySQL is not currently listening on port $DbPort."
    Warn 'Trying to start XAMPP MySQL...'

    $mysqlStart = 'D:\xampp\mysql_start.bat'

    if (Test-Path $mysqlStart) {
        Start-Process `
            -FilePath 'cmd.exe' `
            -ArgumentList '/c', "`"$mysqlStart`"" `
            -WorkingDirectory 'D:\xampp' `
            -WindowStyle Hidden | Out-Null

        for ($i = 0; $i -lt 20; $i++) {
            Start-Sleep -Seconds 1

            if (PortOpen $DbPort) {
                break
            }
        }
    }

    if (PortOpen $DbPort) {
        Ok "MySQL started on port $DbPort"
    }
    else {
        throw "MySQL could not be started on port $DbPort. Start it manually in XAMPP and rerun PATCH-009F."
    }
}

Step 'Resuming CodeForge framework setup'

$setupArgs = @(
    '-NoProfile',
    '-ExecutionPolicy',
    'Bypass',
    '-File',
    $setupPath,
    '-DbPort',
    "$DbPort"
)

# Explicitly pass the repaired PATH into the nested setup process.
$env:Path = "$nodeDir;$env:Path"

$oldPreference = $ErrorActionPreference
$ErrorActionPreference = 'Continue'

& powershell @setupArgs
$setupExit = $LASTEXITCODE

$ErrorActionPreference = $oldPreference

if ($setupExit -ne 0) {
    Write-Host ''
    Warn "Setup reached the next blocker and exited with code $setupExit."
    Write-Host ''
    Write-Host 'Node/npm PATH detection is now permanently fixed.' -ForegroundColor Yellow
    Write-Host 'Send the LAST error section shown above for the next targeted patch.' -ForegroundColor Yellow
    exit $setupExit
}

Step 'PATCH-009F complete'

Write-Host 'PATCH-009F COMPLETED SUCCESSFULLY' -ForegroundColor Green
Write-Host ''
Write-Host "Node.js: $nodeVersion" -ForegroundColor White
Write-Host "npm:     $npmVersion" -ForegroundColor White
Write-Host "MySQL:   port $DbPort" -ForegroundColor White
Write-Host ''
Write-Host 'Start CodeForge:' -ForegroundColor Cyan
Write-Host '  powershell -ExecutionPolicy Bypass -File .\start-codeforge.ps1' -ForegroundColor White
Write-Host ''
Write-Host 'Frontend: http://localhost:5173' -ForegroundColor Cyan
Write-Host 'Backend:  http://localhost:8000' -ForegroundColor Cyan
