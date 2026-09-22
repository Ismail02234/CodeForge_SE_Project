# CODEFORGE STABILITY PATCH 002
# Safer patch: absolute paths, no BOM, XAMPP PHP validation
# Run from backend directory

$ErrorActionPreference = "Stop"

$Backend = (Get-Location).Path
$PHP = "D:\xampp\php\php.exe"

if (!(Test-Path $PHP)) {
    throw "Cannot find XAMPP PHP: $PHP"
}

function Full($p) {
    return [System.IO.Path]::GetFullPath((Join-Path $Backend $p))
}

function Backup($p) {
    if (Test-Path $p) {
        $b = "$p.backup-$(Get-Date -Format yyyyMMdd-HHmmss)"
        Copy-Item $p $b -Force
        Write-Host "[BACKUP] $b"
    }
}

function Write-NoBom($p,$text) {
    $full = [System.IO.Path]::GetFullPath($p)
    $enc = New-Object System.Text.UTF8Encoding($false)
    [System.IO.File]::WriteAllText($full,$text,$enc)
}

$files = @(
    (Full "app\Services\AiAssistantService.php"),
    (Full "bootstrap\app.php"),
    (Full "routes\api.php")
)

foreach($f in $files){
    Backup $f
}

# Fix all PHP BOM files
Get-ChildItem $Backend -Recurse -Filter *.php | ForEach-Object {
    $p = $_.FullName
    $bytes = [System.IO.File]::ReadAllBytes($p)

    if($bytes.Length -ge 3 -and
       $bytes[0] -eq 239 -and
       $bytes[1] -eq 187 -and
       $bytes[2] -eq 191){

        $txt = [System.Text.Encoding]::UTF8.GetString($bytes[3..($bytes.Length-1)])
        Write-NoBom $p $txt
        Write-Host "[UTF8] Fixed $p"
    }
}

# Fix Laravel API guest redirect
$bootstrap = Full "bootstrap\app.php"

if(Test-Path $bootstrap){
    $txt = Get-Content $bootstrap -Raw

    if($txt -notmatch "redirectGuestsTo"){
        $txt = $txt -replace `
"->withMiddleware\(function \(Middleware \$middleware\): void \{",
"->withMiddleware(function (Middleware `$middleware): void {`r`n        `$middleware->redirectGuestsTo(function () { return null; });"

        Write-NoBom $bootstrap $txt
        Write-Host "[FIX] Guest redirect handling"
    }
}

# AI service cleanup only - preserve existing logic
$service = Full "app\Services\AiAssistantService.php"

if(Test-Path $service){
    $txt = Get-Content $service -Raw

    # ensure Gemini model default is current
    $txt = $txt.Replace(
        "gemini-2.5-flash",
        "gemini-3.6-flash"
    )

    Write-NoBom $service $txt
    Write-Host "[FIX] AI service encoding/model cleanup"
}

# PHP validation
foreach($f in (Get-ChildItem $Backend -Recurse -Filter *.php)){
    & $PHP -l $f.FullName | Out-Null
    if($LASTEXITCODE -ne 0){
        throw "PHP syntax failed: $($f.FullName)"
    }
}

Write-Host ""
Write-Host "[SUCCESS] CodeForge Stability Patch 002 completed"
Write-Host "Next:"
Write-Host "D:\xampp\php\php.exe artisan optimize:clear"
Write-Host "Restart Laravel server"
