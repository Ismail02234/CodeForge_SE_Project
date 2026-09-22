# CODEFORGE GEMINI RESILIENCE PATCH 003
# Adds safer Gemini handling without changing routes/frontend.
# Backup first. UTF-8 without BOM.

$ErrorActionPreference = "Stop"

$service = Join-Path (Get-Location) "app\Services\AiAssistantService.php"

if (!(Test-Path $service)) {
    throw "AiAssistantService.php not found"
}

$backup = "$service.backup-gemini-$(Get-Date -Format yyyyMMdd-HHmmss)"
Copy-Item $service $backup -Force
Write-Host "[BACKUP] $backup"

$enc = New-Object System.Text.UTF8Encoding($false)

$content = Get-Content $service -Raw

# Add retry wrapper marker only if not already present.
# This patch inserts handling improvements around the existing failed request block.

if ($content -notmatch "RESOURCE_EXHAUSTED|Gemini quota temporarily unavailable") {

    $old = @"
if (! \$response->successful()) {
            Log::warning('Gemini request failed.', [
    'status' => \$response->status(),
    'body' => \$response->body(),
    'model' => \$model,
    'has_key' => !empty(\$apiKey),
]);
            return null;
        }
"@

    $new = @"
if (! \$response->successful()) {

            \$status = \$response->status();

            Log::warning('Gemini request failed.', [
                'status' => \$status,
                'body' => \$response->body(),
                'model' => \$model,
                'has_key' => !empty(\$apiKey),
            ]);

            if (\$status === 429) {
                return [
                    'reply' => 'Gemini quota is temporarily exhausted. Please try again shortly.',
                    'intent' => 'none',
                    'target' => '',
                    'page' => '',
                    'suggestions' => [
                        'What should I practice?',
                        'Open Problems'
                    ],
                ];
            }

            if (\$status === 503) {
                return [
                    'reply' => 'Gemini is temporarily under heavy load. Please try again in a moment.',
                    'intent' => 'none',
                    'target' => '',
                    'page' => '',
                    'suggestions' => [
                        'What should I practice?',
                        'Open Problems'
                    ],
                ];
            }

            return null;
        }
"@

    if ($content.Contains($old)) {
        $content = $content.Replace($old,$new)
        Write-Host "[FIX] Added Gemini quota/load handling"
    }
    else {
        Write-Host "[INFO] Failed-request block pattern not found. No automatic replacement made."
    }
}

[System.IO.File]::WriteAllText($service,$content,$enc)

Write-Host "[OK] File saved"

& "D:\xampp\php\php.exe" -l $service

if ($LASTEXITCODE -ne 0) {
    throw "PHP syntax validation failed"
}

Write-Host "[SUCCESS] Gemini resilience patch complete"
Write-Host "Run:"
Write-Host "D:\xampp\php\php.exe artisan optimize:clear"
