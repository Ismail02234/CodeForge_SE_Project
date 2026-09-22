# CODEFORGE DEEPSEEK PROVIDER PATCH 002
# Fixed PowerShell .env handling issue from PATCH 001
# User manually adds DEEPSEEK_API_KEY

$ErrorActionPreference = "Stop"

$Backend = (Get-Location).Path
$PHP = "D:\xampp\php\php.exe"

function Backup-File($file) {
    if (Test-Path $file) {
        $backup = "$file.backup-deepseek002-$(Get-Date -Format yyyyMMdd-HHmmss)"
        Copy-Item $file $backup -Force
        Write-Host "[BACKUP] $backup"
    }
}

function Write-NoBom($file,$content) {
    $encoding = New-Object System.Text.UTF8Encoding($false)
    [System.IO.File]::WriteAllText($file,$content,$encoding)
}

$envFile = Join-Path $Backend ".env"
$configFile = Join-Path $Backend "config\ai_assistant.php"
$serviceFile = Join-Path $Backend "app\Services\AiAssistantService.php"

Backup-File $envFile
Backup-File $configFile
Backup-File $serviceFile

# Update .env safely
if (Test-Path $envFile) {

    $envContent = Get-Content $envFile -Raw

    if ($envContent -notmatch "DEEPSEEK_API_KEY") {
        $envContent += "`r`n# DeepSeek AI Provider`r`nDEEPSEEK_API_KEY=`r`n"
    }

    if ($envContent -match "CODEFORGE_AI_PROVIDER=.*") {
        $envContent = $envContent -replace "CODEFORGE_AI_PROVIDER=.*", "CODEFORGE_AI_PROVIDER=deepseek"
    } else {
        $envContent += "`r`nCODEFORGE_AI_PROVIDER=deepseek`r`n"
    }

    if ($envContent -match "CODEFORGE_AI_MODEL=.*") {
        $envContent = $envContent -replace "CODEFORGE_AI_MODEL=.*", "CODEFORGE_AI_MODEL=deepseek-chat"
    } else {
        $envContent += "CODEFORGE_AI_MODEL=deepseek-chat`r`n"
    }

    Write-NoBom $envFile $envContent
    Write-Host "[FIX] .env updated"
}

# Update config only if compatible
if (Test-Path $configFile) {

    $config = Get-Content $configFile -Raw

    if ($config -match "GEMINI_API_KEY") {
        $config = $config.Replace(
            "env('GEMINI_API_KEY')",
            "env('DEEPSEEK_API_KEY', env('GEMINI_API_KEY'))"
        )
    }

    Write-NoBom $configFile $config
    Write-Host "[FIX] AI config updated"
}

# Keep service clean and switch default model only
if (Test-Path $serviceFile) {

    $service = Get-Content $serviceFile -Raw

    $service = $service.Replace(
        "gemini-3.6-flash",
        "deepseek-chat"
    )

    Write-NoBom $serviceFile $service
    Write-Host "[FIX] AI service model updated"
}

# Validate important PHP files only
foreach($file in @($configFile,$serviceFile)) {
    if(Test-Path $file) {
        & $PHP -l $file
        if($LASTEXITCODE -ne 0) {
            throw "PHP syntax failed: $file"
        }
    }
}

Write-Host ""
Write-Host "[SUCCESS] DeepSeek PATCH 002 applied"
Write-Host ""
Write-Host "Now edit .env:"
Write-Host "DEEPSEEK_API_KEY=YOUR_KEY"
Write-Host ""
Write-Host "Then run:"
Write-Host "D:\xampp\php\php.exe artisan optimize:clear"
