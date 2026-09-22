# CODEFORGE DEEPSEEK PROVIDER PATCH 001
# Adds DeepSeek as primary AI provider.
# User must manually add DEEPSEEK_API_KEY to .env

$ErrorActionPreference = "Stop"

$root = (Get-Location).Path
$php = "D:\xampp\php\php.exe"

function Backup($file) {
    if (Test-Path $file) {
        $b = "$file.backup-deepseek-$(Get-Date -Format yyyyMMdd-HHmmss)"
        Copy-Item $file $b -Force
        Write-Host "[BACKUP] $b"
    }
}

function SaveNoBom($file,$content) {
    $enc = New-Object System.Text.UTF8Encoding($false)
    [System.IO.File]::WriteAllText($file,$content,$enc)
}

$envFile = Join-Path $root ".env"
$configFile = Join-Path $root "config\ai_assistant.php"
$serviceFile = Join-Path $root "app\Services\AiAssistantService.php"

Backup $envFile
Backup $configFile
Backup $serviceFile

# Add env placeholders
if(Test-Path $envFile){
    $env = Get-Content $envFile -Raw

    if($env -notmatch "DEEPSEEK_API_KEY"){
        Add-Content $env "`n# DeepSeek AI Provider`nDEEPSEEK_API_KEY=`nCODEFORGE_AI_PROVIDER=deepseek`nCODEFORGE_AI_MODEL=deepseek-chat`n"
        Write-Host "[FIX] Added DeepSeek environment placeholders"
    }
}

# Update config values if config exists
if(Test-Path $configFile){
    $cfg = Get-Content $configFile -Raw

    if($cfg -match "GEMINI_API_KEY"){
        $cfg = $cfg.Replace(
            "env('GEMINI_API_KEY')",
            "env('DEEPSEEK_API_KEY', env('GEMINI_API_KEY'))"
        )
    }

    SaveNoBom $configFile $cfg
    Write-Host "[FIX] Updated AI config fallback"
}

# Add provider config helpers to service if supported
if(Test-Path $serviceFile){
    $svc = Get-Content $serviceFile -Raw

    # Replace Gemini model default only where config fallback exists
    $svc = $svc.Replace(
        "gemini-3.6-flash",
        "deepseek-chat"
    )

    SaveNoBom $serviceFile $svc
    Write-Host "[FIX] Updated service default model"
}

# Validate changed PHP files
foreach($f in @($configFile,$serviceFile)){
    if(Test-Path $f){
        & $php -l $f
        if($LASTEXITCODE -ne 0){
            throw "PHP syntax failed: $f"
        }
    }
}

Write-Host ""
Write-Host "[SUCCESS] DeepSeek patch applied"
Write-Host "Now add your key manually:"
Write-Host "DEEPSEEK_API_KEY=your_key_here"
Write-Host "Then run:"
Write-Host "D:\xampp\php\php.exe artisan optimize:clear"
