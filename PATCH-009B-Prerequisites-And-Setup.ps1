# PATCH-009B-Prerequisites-And-Setup.ps1
# CodeForge 3.0 - install missing Composer/Node prerequisites and resume framework setup.
# Run from: D:\xampp\htdocs\codeforge
#
# This patch does NOT touch your database or framework source.
# It only installs/bootstraps missing development tools and reruns setup-codeforge.ps1.

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

function Refresh-Path {
    $machine = [Environment]::GetEnvironmentVariable('Path', 'Machine')
    $user = [Environment]::GetEnvironmentVariable('Path', 'User')
    $parts = @()
    if ($machine) { $parts += $machine }
    if ($user) { $parts += $user }
    $env:Path = ($parts -join ';')
}

$root = Split-Path -Parent $MyInvocation.MyCommand.Path
if (-not (Test-Path (Join-Path $root 'setup-codeforge.ps1'))) {
    $root = (Get-Location).Path
}

$setup = Join-Path $root 'setup-codeforge.ps1'
if (-not (Test-Path $setup)) {
    throw 'setup-codeforge.ps1 was not found. Put this patch directly inside D:\xampp\htdocs\codeforge.'
}

Set-Location $root

Step 'Checking XAMPP PHP'
$php = 'D:\xampp\php\php.exe'
if (-not (Test-Path $php)) {
    $phpCmd = Get-Command php -ErrorAction SilentlyContinue
    if ($null -eq $phpCmd) {
        throw 'PHP was not found. Expected D:\xampp\php\php.exe.'
    }
    $php = $phpCmd.Source
}

$phpVersion = & $php -r "echo PHP_VERSION;"
Ok "PHP $phpVersion"

Step 'Checking Composer'
$composerCmd = Get-Command composer -ErrorAction SilentlyContinue

if ($null -eq $composerCmd) {
    Write-Host 'Composer is not installed globally. Bootstrapping a project-local Composer...' -ForegroundColor White

    $composerDir = Join-Path $root 'tools\composer'
    New-Item -ItemType Directory -Force -Path $composerDir | Out-Null

    $installer = Join-Path $composerDir 'composer-setup.php'
    $composerPhar = Join-Path $composerDir 'composer.phar'
    $composerBat = Join-Path $composerDir 'composer.bat'

    try {
        Invoke-WebRequest -UseBasicParsing -Uri 'https://getcomposer.org/installer' -OutFile $installer
    } catch {
        throw "Could not download Composer installer. Check your internet connection. $($_.Exception.Message)"
    }

    & $php $installer --install-dir=$composerDir --filename='composer.phar'
    if ($LASTEXITCODE -ne 0 -or -not (Test-Path $composerPhar)) {
        throw 'Composer installer failed.'
    }

    $bat = "@echo off`r`n`"$php`" `"%~dp0composer.phar`" %*`r`n"
    [System.IO.File]::WriteAllText($composerBat, $bat, [System.Text.Encoding]::ASCII)

    if (Test-Path $installer) {
        Remove-Item $installer -Force
    }

    $env:Path = "$composerDir;$env:Path"
    $composerCmd = Get-Command composer -ErrorAction SilentlyContinue

    if ($null -eq $composerCmd) {
        throw 'Local Composer was created, but PowerShell could not resolve the wrapper.'
    }

    Ok 'Project-local Composer installed'
} else {
    Ok "Composer found at $($composerCmd.Source)"
}

$composerVersion = & composer --version 2>&1
$composerVersion | Select-Object -First 1 | ForEach-Object { Ok "$_" }

Step 'Checking Node.js and npm'
$nodeCmd = Get-Command node -ErrorAction SilentlyContinue
$npmCmd = Get-Command npm -ErrorAction SilentlyContinue

if ($null -eq $nodeCmd -or $null -eq $npmCmd) {
    Write-Host 'Node.js/npm are missing. Trying to install Node.js LTS with winget...' -ForegroundColor White

    $winget = Get-Command winget -ErrorAction SilentlyContinue
    if ($null -eq $winget) {
        throw @'
Node.js is missing and winget is unavailable.
Install Node.js LTS from https://nodejs.org/ , reopen PowerShell, then rerun this patch.
'@
    }

    & winget install --exact --id OpenJS.NodeJS.LTS --accept-package-agreements --accept-source-agreements --silent
    $wingetExit = $LASTEXITCODE

    Refresh-Path

    # Common Node install location; add it if PATH refresh still does not expose node.
    $nodeDir = 'C:\Program Files\nodejs'
    if (Test-Path $nodeDir -and ($env:Path -notlike "*$nodeDir*")) {
        $env:Path = "$nodeDir;$env:Path"
    }

    $nodeCmd = Get-Command node -ErrorAction SilentlyContinue
    $npmCmd = Get-Command npm -ErrorAction SilentlyContinue

    if ($null -eq $nodeCmd -or $null -eq $npmCmd) {
        throw "Node.js installation did not become available in this PowerShell session (winget exit code $wingetExit). Close PowerShell, open it again, and rerun this patch."
    }

    Ok 'Node.js LTS installed'
} else {
    Ok "Node.js found at $($nodeCmd.Source)"
}

$nodeVersion = & node --version
$npmVersion = & npm --version
Ok "Node $nodeVersion"
Ok "npm $npmVersion"

Step 'Checking MySQL'
$mysqlAdmin = 'D:\xampp\mysql\bin\mysqladmin.exe'
$mysqlRunning = $false

if (Test-Path $mysqlAdmin) {
    foreach ($port in @(3307, 3306)) {
        try {
            & $mysqlAdmin --host=127.0.0.1 --port=$port --user=root ping 2>$null | Out-Null
            if ($LASTEXITCODE -eq 0) {
                Ok "MySQL is responding on port $port"
                $mysqlRunning = $true
                break
            }
        } catch {
            # continue checking other port
        }
    }
}

if (-not $mysqlRunning) {
    Warn 'MySQL was not detected on 3307 or 3306.'
    Warn 'If XAMPP MySQL is stopped, start it before setup reaches migrations.'
}

Step 'Resuming CodeForge framework setup'
Write-Host 'Running setup-codeforge.ps1 now...' -ForegroundColor White
Write-Host ''

# Composer installed locally above is available through inherited PATH.
& powershell -NoProfile -ExecutionPolicy Bypass -File $setup
$setupExit = $LASTEXITCODE

if ($setupExit -ne 0) {
    Write-Host ''
    Warn "setup-codeforge.ps1 exited with code $setupExit."
    Write-Host 'The prerequisite installation completed, so fix the specific setup error shown above and rerun:' -ForegroundColor Yellow
    Write-Host '  powershell -ExecutionPolicy Bypass -File .\setup-codeforge.ps1' -ForegroundColor White
    exit $setupExit
}

Step 'Complete'
Write-Host 'PATCH-009B COMPLETED SUCCESSFULLY' -ForegroundColor Green
Write-Host ''
Write-Host 'Composer: ready' -ForegroundColor White
Write-Host "Node:     $nodeVersion" -ForegroundColor White
Write-Host "npm:      $npmVersion" -ForegroundColor White
Write-Host ''
Write-Host 'Framework dependencies, Laravel setup/migrations and SvelteKit checks were handed back to setup-codeforge.ps1.' -ForegroundColor Cyan
Write-Host ''
Write-Host 'Next command:' -ForegroundColor Cyan
Write-Host '  powershell -ExecutionPolicy Bypass -File .\start-codeforge.ps1' -ForegroundColor White
