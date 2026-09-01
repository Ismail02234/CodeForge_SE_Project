# PATCH-009E-Enable-PHP-Zip-And-Resume.ps1
# CodeForge 3.0
#
# Fixes Composer's current blocker:
#   "The zip extension and unzip/7z commands are both missing"
#
# What it does:
# - backs up XAMPP php.ini
# - enables PHP's zip extension if php_zip.dll is available
# - falls back to installing 7-Zip if PHP zip cannot be enabled
# - verifies Composer can use archive extraction
# - resumes setup-codeforge.ps1 using the existing composer.lock
#
# Run:
#   cd D:\xampp\htdocs\codeforge
#   powershell -ExecutionPolicy Bypass -File .\PATCH-009E-Enable-PHP-Zip-And-Resume.ps1
#
# Optional:
#   powershell -ExecutionPolicy Bypass -File .\PATCH-009E-Enable-PHP-Zip-And-Resume.ps1 -DbPort 3307

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

function Ensure-MySqlRunning([int]$Port) {
    if (PortOpen $Port) {
        return $true
    }

    $startBat = 'D:\xampp\mysql_start.bat'

    if (Test-Path $startBat) {
        Warn "MySQL is not listening on port $Port. Trying XAMPP mysql_start.bat..."

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

function Php-Has-Zip([string]$Php) {
    $modules = & $Php -m 2>$null
    return (($modules -join "`n") -match '(?im)^zip$')
}

function SevenZip-Available {
    $cmd = Get-Command 7z -ErrorAction SilentlyContinue
    if ($null -ne $cmd) {
        return $true
    }

    $sevenZipDir = 'C:\Program Files\7-Zip'
    if (Test-Path (Join-Path $sevenZipDir '7z.exe')) {
        if ($env:Path -notlike "*$sevenZipDir*") {
            $env:Path = "$sevenZipDir;$env:Path"
        }
        return $true
    }

    return $false
}

$root = Split-Path -Parent $MyInvocation.MyCommand.Path
if (-not (Test-Path (Join-Path $root 'backend\composer.json'))) {
    $root = (Get-Location).Path
}

if (-not (Test-Path (Join-Path $root 'backend\composer.json'))) {
    throw 'CodeForge Laravel backend was not found. Put this patch directly inside D:\xampp\htdocs\codeforge.'
}

$setupPath = Join-Path $root 'setup-codeforge.ps1'
if (-not (Test-Path $setupPath)) {
    throw 'setup-codeforge.ps1 was not found.'
}

Set-Location $root

$php = 'D:\xampp\php\php.exe'
$phpIni = 'D:\xampp\php\php.ini'
$phpZipDll = 'D:\xampp\php\ext\php_zip.dll'

if (-not (Test-Path $php)) {
    throw 'XAMPP PHP was not found at D:\xampp\php\php.exe.'
}

if (-not (Test-Path $phpIni)) {
    throw 'XAMPP php.ini was not found at D:\xampp\php\php.ini.'
}

Step 'Checking current PHP ZIP support'

$phpVersion = (& $php -r "echo PHP_VERSION;").Trim()
Ok "PHP $phpVersion"

$zipAlreadyLoaded = Php-Has-Zip $php

if ($zipAlreadyLoaded) {
    Ok 'PHP zip extension is already loaded'
}
else {
    Warn 'PHP zip extension is not currently loaded'

    Step 'Backing up XAMPP php.ini'

    $stamp = Get-Date -Format 'yyyyMMdd-HHmmss'
    $backupDir = Join-Path $root "patch-backups\PATCH-009E-$stamp"
    New-Item -ItemType Directory -Force -Path $backupDir | Out-Null
    Copy-Item $phpIni (Join-Path $backupDir 'php.ini') -Force

    Ok "php.ini backup created: $backupDir"

    if (Test-Path $phpZipDll) {
        Step 'Enabling PHP zip extension in XAMPP'

        $content = [System.IO.File]::ReadAllText($phpIni)

        $changed = $false

        # Common XAMPP / PHP forms.
        if ($content -match '(?im)^\s*;\s*extension\s*=\s*zip\s*$') {
            $content = [Regex]::Replace(
                $content,
                '(?im)^\s*;\s*extension\s*=\s*zip\s*$',
                'extension=zip'
            )
            $changed = $true
        }
        elseif ($content -match '(?im)^\s*;\s*extension\s*=\s*php_zip\.dll\s*$') {
            $content = [Regex]::Replace(
                $content,
                '(?im)^\s*;\s*extension\s*=\s*php_zip\.dll\s*$',
                'extension=php_zip.dll'
            )
            $changed = $true
        }
        elseif ($content -match '(?im)^\s*extension\s*=\s*(zip|php_zip\.dll)\s*$') {
            # Entry already exists but PHP did not load it. Leave it in place and verify below.
            $changed = $false
        }
        else {
            $content = $content.TrimEnd() +
                [Environment]::NewLine +
                [Environment]::NewLine +
                '; CodeForge setup: Composer archive extraction' +
                [Environment]::NewLine +
                'extension=zip' +
                [Environment]::NewLine

            $changed = $true
        }

        if ($changed) {
            [System.IO.File]::WriteAllText(
                $phpIni,
                $content,
                (New-Object System.Text.UTF8Encoding($false))
            )
            Ok 'PHP zip extension enabled in php.ini'
        }
        else {
            Ok 'PHP zip extension entry was already enabled in php.ini'
        }

        Step 'Verifying PHP zip extension'

        $oldPreference = $ErrorActionPreference
        $ErrorActionPreference = 'Continue'
        $moduleOutput = & $php -m 2>&1
        $moduleExit = $LASTEXITCODE
        $ErrorActionPreference = $oldPreference

        if ($moduleExit -eq 0 -and (($moduleOutput -join "`n") -match '(?im)^zip$')) {
            Ok 'PHP zip extension is now loaded'
        }
        else {
            Warn 'PHP zip still did not load after editing php.ini.'
            $moduleOutput | ForEach-Object { Write-Host $_ -ForegroundColor DarkGray }
        }
    }
    else {
        Warn "php_zip.dll was not found at $phpZipDll"
    }
}

$phpZipReady = Php-Has-Zip $php
$sevenZipReady = SevenZip-Available

if (-not $phpZipReady -and -not $sevenZipReady) {
    Step 'Installing 7-Zip fallback for Composer'

    $winget = Get-Command winget -ErrorAction SilentlyContinue

    if ($null -eq $winget) {
        throw @'
PHP zip could not be enabled and winget is unavailable.

Install 7-Zip manually, then reopen PowerShell and rerun PATCH-009E.
'@
    }

    $oldPreference = $ErrorActionPreference
    $ErrorActionPreference = 'Continue'

    & winget install `
        --exact `
        --id 7zip.7zip `
        --accept-package-agreements `
        --accept-source-agreements `
        --silent

    $wingetExit = $LASTEXITCODE
    $ErrorActionPreference = $oldPreference

    $sevenZipDir = 'C:\Program Files\7-Zip'
    if (Test-Path $sevenZipDir) {
        $env:Path = "$sevenZipDir;$env:Path"
    }

    $sevenZipReady = SevenZip-Available

    if (-not $sevenZipReady) {
        throw "7-Zip installation did not become available (winget exit code $wingetExit)."
    }

    Ok '7-Zip fallback is available to Composer'
}

Step 'Verifying Composer archive extraction prerequisite'

if ($phpZipReady) {
    Ok 'Composer can extract packages through PHP zip'
}
elseif ($sevenZipReady) {
    Ok 'Composer can extract packages through 7-Zip'
}
else {
    throw 'Neither PHP zip nor 7-Zip is available.'
}

$composerPhar = Join-Path $root 'tools\composer\composer.phar'

if (-not (Test-Path $composerPhar)) {
    throw 'Project-local Composer was not found. PATCH-009C must be completed first.'
}

$lockFile = Join-Path $root 'backend\composer.lock'

if (Test-Path $lockFile) {
    Ok 'Existing composer.lock found; dependency resolution will not need to start over'
}
else {
    Warn 'composer.lock is missing. setup-codeforge.ps1 will resolve dependencies again.'
}

Step 'Checking MySQL before resuming setup'

if ($DbPort -le 0) {
    if (PortOpen 3307) {
        $DbPort = 3307
    }
    elseif (PortOpen 3306) {
        $DbPort = 3306
    }
    else {
        $configured = Read-XamppMySqlPort
        if ($configured -gt 0) {
            $DbPort = $configured
        }
        else {
            $DbPort = 3307
        }
    }
}

if (Ensure-MySqlRunning $DbPort) {
    Ok "MySQL is responding on port $DbPort"
}
else {
    Warn "MySQL is not responding on port $DbPort."
    Warn 'Composer/npm work can be resumed later, but Laravel migrations require MySQL.'
}

Step 'Resuming complete framework setup'

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
    Write-Host ''
    Write-Host 'The Composer ZIP/archive problem is now fixed.' -ForegroundColor Yellow
    Write-Host 'Send the LAST error block shown above for the next targeted patch.' -ForegroundColor Yellow
    exit $setupExit
}

Step 'PATCH-009E complete'

Write-Host 'PATCH-009E COMPLETED SUCCESSFULLY' -ForegroundColor Green
Write-Host ''
Write-Host "PHP zip:  $phpZipReady" -ForegroundColor White
Write-Host "7-Zip:    $sevenZipReady" -ForegroundColor White
Write-Host "MySQL:    port $DbPort" -ForegroundColor White
Write-Host ''
Write-Host 'Framework setup has continued from the existing composer.lock.' -ForegroundColor Cyan
Write-Host ''
Write-Host 'Start CodeForge with:' -ForegroundColor Cyan
Write-Host '  powershell -ExecutionPolicy Bypass -File .\start-codeforge.ps1' -ForegroundColor White
Write-Host ''
Write-Host 'Frontend: http://localhost:5173' -ForegroundColor Cyan
Write-Host 'Backend:  http://localhost:8000' -ForegroundColor Cyan
