# CODEFORGE FULL STABILITY PATCH FINAL
# Safe patch: backup -> fix -> syntax validation
# Run from backend folder

$ErrorActionPreference = "Stop"

$root = Get-Location
$php = "D:\xampp\php\php.exe"

if (!(Test-Path $php)) {
    throw "XAMPP PHP not found at $php"
}

function Backup-File($path) {
    if (Test-Path $path) {
        $backup = "$path.backup-$(Get-Date -Format yyyyMMdd-HHmmss)"
        Copy-Item $path $backup -Force
        Write-Host "[BACKUP] $backup"
    }
}

function Save-Utf8NoBom($path, $content) {
    $enc = New-Object System.Text.UTF8Encoding($false)
    [System.IO.File]::WriteAllText($path, $content, $enc)
}

# Files
$service = "app\Services\AiAssistantService.php"
$bootstrap = "bootstrap\app.php"
$api = "routes\api.php"

Backup-File $service
Backup-File $bootstrap
Backup-File $api

# Remove BOM from PHP files safely
Get-ChildItem -Recurse -Filter *.php | ForEach-Object {
    $p = $_.FullName
    $bytes = [System.IO.File]::ReadAllBytes($p)
    if ($bytes.Length -ge 3 -and $bytes[0] -eq 239 -and $bytes[1] -eq 187 -and $bytes[2] -eq 191) {
        $text = [System.Text.Encoding]::UTF8.GetString($bytes[3..($bytes.Length-1)])
        Save-Utf8NoBom $p $text
        Write-Host "[UTF8 FIX] $p"
    }
}

# Fix Laravel API guest redirect issue
if (Test-Path $bootstrap) {
    $c = Get-Content $bootstrap -Raw
    if ($c -notmatch "redirectGuestsTo") {
        $c = $c.Replace(
"->withMiddleware(function (Middleware $middleware): void {",
"->withMiddleware(function (Middleware $middleware): void {
        `$middleware->redirectGuestsTo(function () {
            return null;
        });"
        )
        Save-Utf8NoBom $bootstrap $c
        Write-Host "[FIX] API guest redirect"
    }
}

# Ensure AI assistant accepts coaching intent
if (Test-Path $service) {
    $c = Get-Content $service -Raw

    $c = $c.Replace(
"Supported intents: none, navigate, search, profile, compare, problem, contest, weakest, performance.",
"Supported intents: none, navigate, search, profile, compare, problem, contest, weakest, performance, coach."
)

    $c = $c.Replace(
"'weakest', 'performance'],",
"'weakest', 'performance', 'coach'],"
)

    # add temperature if missing
    if ($c -notmatch "'temperature'\s*=>") {
        $c = $c.Replace(
"'max_output_tokens' => 500,",
"'max_output_tokens' => 500,
                'temperature' => 0.3,"
        )
    }

    Save-Utf8NoBom $service $c
    Write-Host "[FIX] AI service updated"
}

# Validate PHP
Get-ChildItem -Recurse -Filter *.php | ForEach-Object {
    & $php -l $_.FullName | Out-Null
    if ($LASTEXITCODE -ne 0) {
        throw "PHP syntax error: $($_.FullName)"
    }
}

Write-Host ""
Write-Host "[SUCCESS] CodeForge stability patch applied"
Write-Host "Run:"
Write-Host "D:\xampp\php\php.exe artisan optimize:clear"
Write-Host "Then restart Laravel."
