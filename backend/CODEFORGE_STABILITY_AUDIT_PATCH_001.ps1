# CodeForge Stability Audit Patch 001
# Safe patch: creates backups, fixes known runtime blockers.
# Run from backend directory.

$ErrorActionPreference = "Stop"

$root = Get-Location

function Backup-File($path) {
    if (Test-Path $path) {
        Copy-Item $path "$path.audit-backup-$(Get-Date -Format yyyyMMdd-HHmmss)" -Force
        Write-Host "[BACKUP] $path"
    }
}

# Fix Laravel API guest redirect crash
$app = "bootstrap/app.php"
Backup-File $app

if (Test-Path $app) {
    $text = Get-Content $app -Raw
    if ($text -notmatch "redirectGuestsTo") {
        $text = $text -replace "->withMiddleware\(function \(Middleware \$middleware\): void \{", "->withMiddleware(function (Middleware `$middleware): void {`r`n        `$middleware->redirectGuestsTo(function () { return null; });"
        Set-Content $app $text -Encoding utf8
    }
}

# Fix AI config defaults to current Gemini setup
$config = "config/ai_assistant.php"
Backup-File $config

if (Test-Path $config) {
    $text = Get-Content $config -Raw
    $text = $text.Replace("env('CODEFORGE_AI_PROVIDER', 'openai')", "env('CODEFORGE_AI_PROVIDER', 'gemini')")
    $text = $text.Replace("env('CODEFORGE_AI_MODEL', 'gemini-2.5-flash')", "env('CODEFORGE_AI_MODEL', 'gemini-3.6-flash')")
    Set-Content $config $text -Encoding utf8
}

# Remove PHP BOM from important files written by PowerShell
$files = @(
    "app\Services\AiAssistantService.php",
    "app\Http\Controllers\AiAssistantController.php",
    "bootstrap\app.php",
    "config\ai_assistant.php"
)

foreach ($file in $files) {
    if (Test-Path $file) {
        $full = Join-Path $root $file
        $bytes = [System.IO.File]::ReadAllBytes($full)
        $content = [System.Text.Encoding]::UTF8.GetString($bytes)
        $idx = $content.IndexOf("<?php")
        if ($idx -ge 0) {
            $content = $content.Substring($idx)
            [System.IO.File]::WriteAllText($full,$content,(New-Object System.Text.UTF8Encoding($false)))
        }
    }
}

Write-Host "[CHECK] PHP syntax"

$php="D:\xampp\php\php.exe"
if (Test-Path $php) {
    & $php -l app\Services\AiAssistantService.php
    & $php -l app\Http\Controllers\AiAssistantController.php
    & $php -l bootstrap\app.php
    & $php -l config\ai_assistant.php
}

Write-Host ""
Write-Host "Run:"
Write-Host "D:\xampp\php\php.exe artisan optimize:clear"
Write-Host "Restart Laravel"
Write-Host "Then test:"
Write-Host "- What should I practice?"
Write-Host "- Tell me how to improve my skills?"
Write-Host "- Open Ghost Race"
