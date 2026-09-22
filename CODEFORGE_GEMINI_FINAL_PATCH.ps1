# CODEFORGE GEMINI FINAL PATCH
# Purpose:
# Safely prepares CodeForge AI Copilot for Gemini API usage.
#
# It:
# - verifies AI files exist
# - backs up changed files
# - ensures Gemini config exists
# - ensures API route exists
# - clears Laravel cache
#
# It does NOT store API keys.

$root = "D:\xampp\htdocs\codeforge_AI_Copilot_PATCHED_2026-09-21\codeforge"
$backend = Join-Path $root "backend"

Write-Host "=== CodeForge Gemini Final Patch ==="

if (!(Test-Path $backend)) {
    Write-Host "[ERROR] Backend not found:"
    Write-Host $backend
    exit 1
}

$service = Join-Path $backend "app\Services\AiAssistantService.php"
$controller = Join-Path $backend "app\Http\Controllers\AiAssistantController.php"
$config = Join-Path $backend "config\ai_assistant.php"
$routes = Join-Path $backend "routes\api.php"

foreach ($file in @($service,$controller,$config,$routes)) {
    if (!(Test-Path $file)) {
        Write-Host "[ERROR] Missing file:"
        Write-Host $file
        exit 1
    }
}

Write-Host "[OK] AI files detected"

# Backups
$stamp = Get-Date -Format "yyyyMMdd-HHmmss"
$backup = Join-Path $root "patch-backups\gemini-$stamp"
New-Item -ItemType Directory -Path $backup -Force | Out-Null

Copy-Item $service $backup -Force
Copy-Item $controller $backup -Force
Copy-Item $config $backup -Force
Copy-Item $routes $backup -Force

Write-Host "[OK] Backups created:"
Write-Host $backup

# Update AI config
$configText = Get-Content $config -Raw

$configText = $configText.Replace("env('OPENAI_API_KEY')","env('GEMINI_API_KEY')")
$configText = $configText.Replace("env('CODEFORGE_AI_PROVIDER', 'openai')","env('CODEFORGE_AI_PROVIDER', 'gemini')")
$configText = $configText.Replace("env('CODEFORGE_AI_MODEL', 'gpt-5.6-luna')","env('CODEFORGE_AI_MODEL', 'gemini-2.5-flash')")

Set-Content $config $configText -Encoding UTF8

Write-Host "[OK] Gemini config prepared"

# Ensure route exists
$routeText = Get-Content $routes -Raw

if ($routeText -notmatch "AiAssistantController") {
    $routeText = "use App\Http\Controllers\AiAssistantController;`r`n" + $routeText
}

if ($routeText -notmatch "assistant/chat") {
    $routeText += "`r`nRoute::middleware('auth:sanctum')->post('/assistant/chat', [AiAssistantController::class, 'chat']);`r`n"
}

Set-Content $routes $routeText -Encoding UTF8

Write-Host "[OK] Assistant route checked"

# Environment instructions
$envFile = Join-Path $backend ".env"

if (Test-Path $envFile) {
    $envText = Get-Content $envFile -Raw

    if ($envText -notmatch "CODEFORGE_AI_ENABLED") {
        Add-Content $envFile @"

CODEFORGE_AI_ENABLED=true
CODEFORGE_AI_PROVIDER=gemini
CODEFORGE_AI_MODEL=gemini-2.5-flash
CODEFORGE_AI_TIMEOUT=20
GEMINI_API_KEY=PUT_YOUR_KEY_HERE

"@
        Write-Host "[OK] Added Gemini environment placeholders"
    }
    else {
        Write-Host "[INFO] AI environment already exists"
    }
}

# Clear cache
Set-Location $backend
D:\xampp\php\php.exe artisan optimize:clear

Write-Host ""
Write-Host "======================================="
Write-Host "PATCH COMPLETE"
Write-Host "======================================="
Write-Host ""
Write-Host "NEXT:"
Write-Host "1. Open backend\.env"
Write-Host "2. Replace PUT_YOUR_KEY_HERE with your Gemini API key"
Write-Host "3. Restart Laravel"
Write-Host "4. Start frontend"
Write-Host "5. Test Copilot"
