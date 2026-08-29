# PATCH-002C-Fix-PHP-BOM.ps1
# Fixes PHP "strict_types declaration must be the very first statement" errors
# caused by UTF-8 BOMs written by Windows PowerShell.
# Run from D:\xampp\htdocs\codeforge

$ErrorActionPreference = "Stop"

function Write-Step($msg) {
    Write-Host ""
    Write-Host "==> $msg" -ForegroundColor Cyan
}

$scriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$root = $scriptDir
if (-not (Test-Path (Join-Path $root "core\bootstrap.php"))) {
    $root = (Get-Location).Path
}
if (-not (Test-Path (Join-Path $root "core\bootstrap.php"))) {
    throw "Could not find CodeForge project root. Put this patch inside D:\xampp\htdocs\codeforge and run it there."
}

Set-Location $root

$stamp = Get-Date -Format "yyyyMMdd-HHmmss"
$backup = Join-Path $root "patch-backups\PATCH-002C-$stamp"
New-Item -ItemType Directory -Force -Path $backup | Out-Null

Write-Step "Backing up PHP files that contain UTF-8 BOMs"

$utf8NoBom = New-Object System.Text.UTF8Encoding($false)
$fixed = @()
$phpFiles = Get-ChildItem -Path $root -Filter "*.php" -File -Recurse | Where-Object {
    $_.FullName -notlike "*\patch-backups\*"
}

foreach ($file in $phpFiles) {
    $bytes = [System.IO.File]::ReadAllBytes($file.FullName)

    $hasBom = $bytes.Length -ge 3 -and
              $bytes[0] -eq 0xEF -and
              $bytes[1] -eq 0xBB -and
              $bytes[2] -eq 0xBF

    if ($hasBom) {
        $relative = $file.FullName.Substring($root.Length).TrimStart('\')
        $backupFile = Join-Path $backup $relative
        $backupDir = Split-Path -Parent $backupFile
        New-Item -ItemType Directory -Force -Path $backupDir | Out-Null
        Copy-Item $file.FullName $backupFile -Force

        $text = [System.IO.File]::ReadAllText($file.FullName)
        [System.IO.File]::WriteAllText($file.FullName, $text, $utf8NoBom)

        $fixed += $relative
        Write-Host "[FIXED BOM] $relative" -ForegroundColor Green
    }
}

if ($fixed.Count -eq 0) {
    Write-Host "No UTF-8 BOMs were found. The patch will still run syntax checks." -ForegroundColor Yellow
}

Write-Step "Verifying strict_types placement"

$strictFiles = Get-ChildItem -Path $root -Filter "*.php" -File -Recurse | Where-Object {
    $_.FullName -notlike "*\patch-backups\*"
} | Where-Object {
    (Get-Content $_.FullName -Raw) -match "declare\s*\(\s*strict_types\s*=\s*1\s*\)"
}

foreach ($file in $strictFiles) {
    $bytes = [System.IO.File]::ReadAllBytes($file.FullName)
    if ($bytes.Length -ge 3 -and $bytes[0] -eq 0xEF -and $bytes[1] -eq 0xBB -and $bytes[2] -eq 0xBF) {
        throw "BOM still exists in strict_types PHP file: $($file.FullName)"
    }

    $text = [System.IO.File]::ReadAllText($file.FullName)
    if (-not $text.StartsWith("<?php")) {
        throw "A strict_types PHP file has content before <?php: $($file.FullName)"
    }
}

Write-Step "Running PHP syntax checks"

$phpExe = "php"
$xamppPhp = "D:\xampp\php\php.exe"
if (Test-Path $xamppPhp) {
    $phpExe = $xamppPhp
}

$lintFailed = $false
$filesToLint = @(
    "index.php",
    "dashboard.php",
    "login.php",
    "register.php",
    "logout.php",
    "core\auth.php",
    "core\bootstrap.php",
    "includes\header.php"
)

foreach ($file in $filesToLint) {
    $full = Join-Path $root $file

    if (Test-Path $full) {
        $output = & $phpExe -l $full 2>&1
        if ($LASTEXITCODE -ne 0) {
            Write-Host "[FAIL] $file" -ForegroundColor Red
            $output | ForEach-Object { Write-Host $_ }
            $lintFailed = $true
        } else {
            Write-Host "[OK]   $file" -ForegroundColor Green
        }
    }
}

if ($lintFailed) {
    Write-Host ""
    Write-Host "PATCH-002C FINISHED, BUT A PHP SYNTAX ERROR STILL EXISTS." -ForegroundColor Yellow
    Write-Host "Backup: $backup" -ForegroundColor DarkGray
    exit 1
}

Write-Step "Patch complete"
Write-Host "PATCH-002C APPLIED SUCCESSFULLY" -ForegroundColor Green
Write-Host ""
Write-Host "UTF-8 BOMs removed from: $($fixed.Count) PHP file(s)" -ForegroundColor White
Write-Host "Backup: $backup" -ForegroundColor DarkGray
Write-Host ""
Write-Host "Now open:" -ForegroundColor Cyan
Write-Host "http://localhost/codeforge/" -ForegroundColor White
Write-Host ""
Write-Host "Use Ctrl + F5 once to bypass cached CSS/JS." -ForegroundColor Yellow
