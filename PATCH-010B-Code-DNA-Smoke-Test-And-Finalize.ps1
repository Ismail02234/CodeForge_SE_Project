# PATCH-010B-Code-DNA-Smoke-Test-And-Finalize.ps1
# CodeForge 3.0
#
# PATCH-010 already applied the Code DNA frontend/runtime fixes successfully.
# Its smoke test failed only because the temporary PHP test used the wrong
# relative path to backend/vendor/autoload.php.
#
# This follow-up:
# - fixes that diagnostic path
# - preserves the original Code DNA scoring semantics while keeping SQL aggregation
# - runs a REAL Code DNA calculation for nafiz/current high-level data
# - runs all backend/frontend checks
# - runs svelte-check and production build
# - leaves MySQL data untouched
#
# Run:
#   cd D:\xampp\htdocs\codeforge
#   powershell -ExecutionPolicy Bypass -File .\PATCH-010B-Code-DNA-Smoke-Test-And-Finalize.ps1

param(
    [int]$DbPort = 3307
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

function Refresh-NodePath {
    $machine = [Environment]::GetEnvironmentVariable('Path', 'Machine')
    $user = [Environment]::GetEnvironmentVariable('Path', 'User')

    $parts = @()
    if ($machine) { $parts += $machine }
    if ($user) { $parts += $user }

    $env:Path = ($parts -join ';')

    $nodeDir = 'C:\Program Files\nodejs'
    if ((Test-Path -LiteralPath $nodeDir) -and ($env:Path -notlike "*$nodeDir*")) {
        $env:Path = "$nodeDir;$env:Path"
    }
}

function Find-Npm {
    Refresh-NodePath

    foreach ($candidate in @(
        'C:\Program Files\nodejs\npm.cmd',
        'C:\Program Files (x86)\nodejs\npm.cmd'
    )) {
        if (Test-Path -LiteralPath $candidate) {
            return $candidate
        }
    }

    $cmd = Get-Command npm.cmd -ErrorAction SilentlyContinue
    if ($null -ne $cmd) {
        return $cmd.Source
    }

    return $null
}

function PortOpen([int]$Port) {
    $client = New-Object System.Net.Sockets.TcpClient
    try {
        $task = $client.ConnectAsync('127.0.0.1', $Port)
        if (-not $task.Wait(700)) { return $false }
        return $client.Connected
    } catch {
        return $false
    } finally {
        $client.Dispose()
    }
}

function Run-Native(
    [string]$Executable,
    [string[]]$Arguments,
    [string]$FailureMessage
) {
    $oldPreference = $ErrorActionPreference
    $ErrorActionPreference = 'Continue'

    & $Executable @Arguments
    $exitCode = $LASTEXITCODE

    $ErrorActionPreference = $oldPreference

    if ($exitCode -ne 0) {
        throw "$FailureMessage (exit code $exitCode)"
    }
}

$root = Split-Path -Parent $MyInvocation.MyCommand.Path

if (-not (Test-Path -LiteralPath (Join-Path $root 'backend\artisan'))) {
    $root = (Get-Location).Path
}

if (-not (Test-Path -LiteralPath (Join-Path $root 'backend\artisan'))) {
    throw 'CodeForge Laravel backend was not found. Put this patch inside D:\xampp\htdocs\codeforge.'
}

if (-not (Test-Path -LiteralPath (Join-Path $root 'frontend\package.json'))) {
    throw 'CodeForge SvelteKit frontend was not found.'
}

Set-Location $root

$php = 'D:\xampp\php\php.exe'
if (-not (Test-Path -LiteralPath $php)) {
    throw 'XAMPP PHP was not found at D:\xampp\php\php.exe.'
}

$npm = Find-Npm
if ($null -eq $npm) {
    throw 'npm.cmd could not be found.'
}

$node = Get-Command node.exe -ErrorAction SilentlyContinue
if ($null -eq $node) {
    throw 'node.exe could not be found.'
}

Step 'Checking environment'

Ok "PHP $((& $php -r "echo PHP_VERSION;").Trim())"
Ok "Node.js $((& $node.Source --version).Trim())"
Ok "npm $((& $npm --version).Trim())"

if (-not (PortOpen $DbPort)) {
    Warn "MySQL is not listening on port $DbPort. Trying XAMPP MySQL..."

    $mysqlStart = 'D:\xampp\mysql_start.bat'
    if (Test-Path -LiteralPath $mysqlStart) {
        Start-Process `
            -FilePath 'cmd.exe' `
            -ArgumentList '/c', "`"$mysqlStart`"" `
            -WorkingDirectory 'D:\xampp' `
            -WindowStyle Hidden | Out-Null

        for ($i = 0; $i -lt 20; $i++) {
            Start-Sleep -Seconds 1
            if (PortOpen $DbPort) { break }
        }
    }
}

if (-not (PortOpen $DbPort)) {
    throw "MySQL could not be reached on port $DbPort."
}

Ok "MySQL is responding on port $DbPort"

$servicePath = Join-Path $root 'backend\app\Services\CodeDnaService.php'

if (-not (Test-Path -LiteralPath $servicePath)) {
    throw 'CodeDnaService.php is missing.'
}

Step 'Backing up Code DNA service'

$stamp = Get-Date -Format 'yyyyMMdd-HHmmss'
$backup = Join-Path $root "patch-backups\PATCH-010B-$stamp"
New-Item -ItemType Directory -Force -Path $backup | Out-Null

$backupService = Join-Path $backup 'backend\app\Services\CodeDnaService.php'
New-Item -ItemType Directory -Force -Path (Split-Path -Parent $backupService) | Out-Null
Copy-Item -LiteralPath $servicePath -Destination $backupService -Force

Ok "Backup created: $backup"

Step 'Finalizing optimized Code DNA service'

$encoded = @'
PD9waHAKCm5hbWVzcGFjZSBBcHBcU2VydmljZXM7Cgp1c2UgQXBwXE1vZGVsc1xVc2VyOwp1c2UgSWxsdW1pbmF0ZVxTdXBwb3J0
XEZhY2FkZXNcREI7CnVzZSBSdW50aW1lRXhjZXB0aW9uOwoKZmluYWwgY2xhc3MgQ29kZURuYVNlcnZpY2UKewogICAgcHVibGlj
IGZ1bmN0aW9uIGNhbGN1bGF0ZShzdHJpbmcgJHVzZXJJZCk6IGFycmF5CiAgICB7CiAgICAgICAgJHVzZXIgPSBVc2VyOjpxdWVy
eSgpCiAgICAgICAgICAgIC0+c2VsZWN0KCdpZCcsICd1c2VybmFtZScsICdyYXRpbmcnLCAndW5pdmVyc2l0eScsICdyYW5rJykK
ICAgICAgICAgICAgLT5maW5kKCR1c2VySWQpOwoKICAgICAgICBpZiAoISR1c2VyKSB7CiAgICAgICAgICAgIHRocm93IG5ldyBS
dW50aW1lRXhjZXB0aW9uKCdVc2VyIG5vdCBmb3VuZC4nKTsKICAgICAgICB9CgogICAgICAgICR0b3BpY3MgPSBEQjo6dGFibGUo
J3Byb2JsZW1zJykKICAgICAgICAgICAgLT53aGVyZU5vdE51bGwoJ3RvcGljJykKICAgICAgICAgICAgLT5kaXN0aW5jdCgpCiAg
ICAgICAgICAgIC0+b3JkZXJCeSgndG9waWMnKQogICAgICAgICAgICAtPnBsdWNrKCd0b3BpYycpCiAgICAgICAgICAgIC0+bWFw
KGZuICgkdG9waWMpID0+IChzdHJpbmcpICR0b3BpYykKICAgICAgICAgICAgLT5hbGwoKTsKCiAgICAgICAgJGRhdGEgPSBbXTsK
CiAgICAgICAgZm9yZWFjaCAoJHRvcGljcyBhcyAkdG9waWMpIHsKICAgICAgICAgICAgJGRhdGFbJHRvcGljXSA9ICR0aGlzLT5i
bGFuaygpOwogICAgICAgIH0KCiAgICAgICAgLyoKICAgICAgICAgKiBTdWJtaXNzaW9uIHN0YXRpc3RpY3Mgc3RheSBpbiBNeVNR
TC4gVGhlIHJlc3VsdCBzZXQgaXMgYm91bmRlZCBieQogICAgICAgICAqIHRoZSBudW1iZXIgb2YgdG9waWNzIGluc3RlYWQgb2Yg
dGhlIG51bWJlciBvZiBzdWJtaXNzaW9ucy4KICAgICAgICAgKi8KICAgICAgICAkc3VtbWFyeSA9IERCOjp0YWJsZSgnc3VibWlz
c2lvbnMgYXMgcycpCiAgICAgICAgICAgIC0+am9pbigncHJvYmxlbXMgYXMgcCcsICdwLmlkJywgJz0nLCAncy5wcm9ibGVtX2lk
JykKICAgICAgICAgICAgLT53aGVyZSgncy51c2VyX2lkJywgJHVzZXJJZCkKICAgICAgICAgICAgLT53aGVyZU5vdE51bGwoJ3Au
dG9waWMnKQogICAgICAgICAgICAtPmdyb3VwQnkoJ3AudG9waWMnKQogICAgICAgICAgICAtPnNlbGVjdFJhdygKICAgICAgICAg
ICAgICAgICJwLnRvcGljLAogICAgICAgICAgICAgICAgIENPVU5UKCopIEFTIHN1Ym1pc3Npb25zLAogICAgICAgICAgICAgICAg
IFNVTShDQVNFIFdIRU4gVVBQRVIocy52ZXJkaWN0KSA9ICdBQycgVEhFTiAxIEVMU0UgMCBFTkQpIEFTIGFjY2VwdGVkLAogICAg
ICAgICAgICAgICAgIENPVU5UKERJU1RJTkNUIHMucHJvYmxlbV9pZCkgQVMgYXR0ZW1wdGVkLAogICAgICAgICAgICAgICAgIENP
VU5UKERJU1RJTkNUIENBU0UgV0hFTiBVUFBFUihzLnZlcmRpY3QpID0gJ0FDJyBUSEVOIHMucHJvYmxlbV9pZCBFTkQpIEFTIHNv
bHZlZCwKICAgICAgICAgICAgICAgICBNQVgoQ0FTRSBXSEVOIFVQUEVSKHMudmVyZGljdCkgPSAnQUMnIFRIRU4gcy5zdWJtaXR0
ZWRfYXQgRU5EKSBBUyBsYXN0X3NvbHZlZF9hdCIKICAgICAgICAgICAgKQogICAgICAgICAgICAtPmdldCgpOwoKICAgICAgICBm
b3JlYWNoICgkc3VtbWFyeSBhcyAkcm93KSB7CiAgICAgICAgICAgICR0b3BpYyA9IChzdHJpbmcpICRyb3ctPnRvcGljOwogICAg
ICAgICAgICAkZGF0YVskdG9waWNdID8/PSAkdGhpcy0+YmxhbmsoKTsKCiAgICAgICAgICAgICRkYXRhWyR0b3BpY11bJ3N1Ym1p
c3Npb25zJ10gPSAoaW50KSAkcm93LT5zdWJtaXNzaW9uczsKICAgICAgICAgICAgJGRhdGFbJHRvcGljXVsnYWNjZXB0ZWQnXSA9
IChpbnQpICRyb3ctPmFjY2VwdGVkOwogICAgICAgICAgICAkZGF0YVskdG9waWNdWydhdHRlbXB0ZWQnXSA9IChpbnQpICRyb3ct
PmF0dGVtcHRlZDsKICAgICAgICAgICAgJGRhdGFbJHRvcGljXVsnc29sdmVkJ10gPSAoaW50KSAkcm93LT5zb2x2ZWQ7CiAgICAg
ICAgICAgICRkYXRhWyR0b3BpY11bJ2xhc3Rfc29sdmVkX2F0J10gPSAkcm93LT5sYXN0X3NvbHZlZF9hdDsKICAgICAgICB9Cgog
ICAgICAgIC8qCiAgICAgICAgICogUHJlc2VydmUgdGhlIG9yaWdpbmFsIENvZGUgRE5BIHNjb3Jpbmcgc2VtYW50aWNzIHdoaWxl
IGF2b2lkaW5nIGEKICAgICAgICAgKiBmdWxsIHByb2JsZW1fc2Vzc2lvbnMgLT4gUEhQIHRyYW5zZmVyLiBXZSBhZ2dyZWdhdGUg
dGhlIGV4YWN0CiAgICAgICAgICogcGVyLXNlc3Npb24gcm91bmRlZCBzcGVlZCBhbmQgZGlmZmljdWx0eSBzY29yZXMgaW5zaWRl
IE15U1FMLgogICAgICAgICAqLwogICAgICAgICRzZXNzaW9uU3VtbWFyeSA9IERCOjp0YWJsZSgncHJvYmxlbV9zZXNzaW9ucyBh
cyBwcycpCiAgICAgICAgICAgIC0+am9pbigncHJvYmxlbXMgYXMgcCcsICdwLmlkJywgJz0nLCAncHMucHJvYmxlbV9pZCcpCiAg
ICAgICAgICAgIC0+d2hlcmUoJ3BzLnVzZXJfaWQnLCAkdXNlcklkKQogICAgICAgICAgICAtPndoZXJlKCdwcy5zdGF0dXMnLCAn
c29sdmVkJykKICAgICAgICAgICAgLT53aGVyZU5vdE51bGwoJ3BzLnNvbHZlX3RpbWVfc2Vjb25kcycpCiAgICAgICAgICAgIC0+
d2hlcmUoJ3BzLnNvbHZlX3RpbWVfc2Vjb25kcycsICc+JywgMCkKICAgICAgICAgICAgLT53aGVyZU5vdE51bGwoJ3AudG9waWMn
KQogICAgICAgICAgICAtPmdyb3VwQnkoJ3AudG9waWMnKQogICAgICAgICAgICAtPnNlbGVjdFJhdygKICAgICAgICAgICAgICAg
ICJwLnRvcGljLAogICAgICAgICAgICAgICAgIENPVU5UKCopIEFTIHNlc3Npb25fY291bnQsCiAgICAgICAgICAgICAgICAgU1VN
KAogICAgICAgICAgICAgICAgICAgIFJPVU5EKAogICAgICAgICAgICAgICAgICAgICAgICBMRUFTVCgKICAgICAgICAgICAgICAg
ICAgICAgICAgICAgIDEwMCwKICAgICAgICAgICAgICAgICAgICAgICAgICAgIEdSRUFURVNUKAogICAgICAgICAgICAgICAgICAg
ICAgICAgICAgICAgIDAsCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgKAogICAgICAgICAgICAgICAgICAgICAgICAg
ICAgICAgICAgICBDQVNFIExPV0VSKHAuZGlmZmljdWx0eSkKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAg
IFdIRU4gJ2Vhc3knIFRIRU4gMTgwLjAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIFdIRU4gJ21lZGl1
bScgVEhFTiAzNjAuMAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgV0hFTiAnaGFyZCcgVEhFTiA2MDAu
MAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgRUxTRSAzMDAuMAogICAgICAgICAgICAgICAgICAgICAg
ICAgICAgICAgICAgICBFTkQKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgLyBOVUxMSUYocHMuc29sdmVfdGlt
ZV9zZWNvbmRzLCAwKQogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICkgKiA4Mi4wCiAgICAgICAgICAgICAgICAgICAg
ICAgICAgICApCiAgICAgICAgICAgICAgICAgICAgICAgICkKICAgICAgICAgICAgICAgICAgICApCiAgICAgICAgICAgICAgICAg
KSBBUyBzcGVlZF9zdW0sCiAgICAgICAgICAgICAgICAgU1VNKAogICAgICAgICAgICAgICAgICAgIENBU0UgTE9XRVIocC5kaWZm
aWN1bHR5KQogICAgICAgICAgICAgICAgICAgICAgICBXSEVOICdlYXN5JyBUSEVOIDU1CiAgICAgICAgICAgICAgICAgICAgICAg
IFdIRU4gJ21lZGl1bScgVEhFTiA3OAogICAgICAgICAgICAgICAgICAgICAgICBXSEVOICdoYXJkJyBUSEVOIDEwMAogICAgICAg
ICAgICAgICAgICAgICAgICBFTFNFIDUwCiAgICAgICAgICAgICAgICAgICAgRU5ECiAgICAgICAgICAgICAgICAgKSBBUyBkaWZm
aWN1bHR5X3N1bSIKICAgICAgICAgICAgKQogICAgICAgICAgICAtPmdldCgpOwoKICAgICAgICAkZ2xvYmFsU3BlZWRTdW0gPSAw
LjA7CiAgICAgICAgJGdsb2JhbERpZmZpY3VsdHlTdW0gPSAwLjA7CiAgICAgICAgJGdsb2JhbFNlc3Npb25Db3VudCA9IDA7Cgog
ICAgICAgIGZvcmVhY2ggKCRzZXNzaW9uU3VtbWFyeSBhcyAkcm93KSB7CiAgICAgICAgICAgICR0b3BpYyA9IChzdHJpbmcpICRy
b3ctPnRvcGljOwogICAgICAgICAgICAkZGF0YVskdG9waWNdID8/PSAkdGhpcy0+YmxhbmsoKTsKCiAgICAgICAgICAgICRjb3Vu
dCA9IChpbnQpICRyb3ctPnNlc3Npb25fY291bnQ7CiAgICAgICAgICAgICRzcGVlZFN1bSA9IChmbG9hdCkgKCRyb3ctPnNwZWVk
X3N1bSA/PyAwKTsKICAgICAgICAgICAgJGRpZmZpY3VsdHlTdW0gPSAoZmxvYXQpICgkcm93LT5kaWZmaWN1bHR5X3N1bSA/PyAw
KTsKCiAgICAgICAgICAgICRkYXRhWyR0b3BpY11bJ3Nlc3Npb25fY291bnQnXSA9ICRjb3VudDsKICAgICAgICAgICAgJGRhdGFb
JHRvcGljXVsnc3BlZWRfc3VtJ10gPSAkc3BlZWRTdW07CiAgICAgICAgICAgICRkYXRhWyR0b3BpY11bJ2RpZmZpY3VsdHlfc3Vt
J10gPSAkZGlmZmljdWx0eVN1bTsKCiAgICAgICAgICAgICRnbG9iYWxTZXNzaW9uQ291bnQgKz0gJGNvdW50OwogICAgICAgICAg
ICAkZ2xvYmFsU3BlZWRTdW0gKz0gJHNwZWVkU3VtOwogICAgICAgICAgICAkZ2xvYmFsRGlmZmljdWx0eVN1bSArPSAkZGlmZmlj
dWx0eVN1bTsKICAgICAgICB9CgogICAgICAgICRyZWNlbnQgPSBEQjo6dGFibGUoJ3N1Ym1pc3Npb25zJykKICAgICAgICAgICAg
LT53aGVyZSgndXNlcl9pZCcsICR1c2VySWQpCiAgICAgICAgICAgIC0+b3JkZXJCeURlc2MoJ3N1Ym1pdHRlZF9hdCcpCiAgICAg
ICAgICAgIC0+b3JkZXJCeURlc2MoJ2lkJykKICAgICAgICAgICAgLT5saW1pdCgxMCkKICAgICAgICAgICAgLT5wbHVjaygndmVy
ZGljdCcpCiAgICAgICAgICAgIC0+bWFwKGZuICgkdmVyZGljdCkgPT4gc3RydG91cHBlcigoc3RyaW5nKSAkdmVyZGljdCkgPT09
ICdBQycgPyAxIDogMCkKICAgICAgICAgICAgLT5hbGwoKTsKCiAgICAgICAgJHRvcGljU2NvcmVzID0gW107CiAgICAgICAgJHRv
dGFsID0gMDsKICAgICAgICAkYWNjZXB0ZWQgPSAwOwogICAgICAgICRzb2x2ZWRQcm9ibGVtcyA9IDA7CgogICAgICAgIGZvcmVh
Y2ggKCRkYXRhIGFzICR0b3BpYyA9PiAkcm93KSB7CiAgICAgICAgICAgICR0b3RhbCArPSAoaW50KSAkcm93WydzdWJtaXNzaW9u
cyddOwogICAgICAgICAgICAkYWNjZXB0ZWQgKz0gKGludCkgJHJvd1snYWNjZXB0ZWQnXTsKICAgICAgICAgICAgJHNvbHZlZFBy
b2JsZW1zICs9IChpbnQpICRyb3dbJ3NvbHZlZCddOwoKICAgICAgICAgICAgJGFjY3VyYWN5ID0gJHJvd1snc3VibWlzc2lvbnMn
XSA+IDAKICAgICAgICAgICAgICAgID8gQ29kZURuYUNhbGN1bGF0b3I6OmNsYW1wKCgkcm93WydhY2NlcHRlZCddIC8gJHJvd1sn
c3VibWlzc2lvbnMnXSkgKiAxMDApCiAgICAgICAgICAgICAgICA6IDA7CgogICAgICAgICAgICAkc3BlZWQgPSAkcm93WydzZXNz
aW9uX2NvdW50J10gPiAwCiAgICAgICAgICAgICAgICA/IENvZGVEbmFDYWxjdWxhdG9yOjpjbGFtcCgkcm93WydzcGVlZF9zdW0n
XSAvICRyb3dbJ3Nlc3Npb25fY291bnQnXSkKICAgICAgICAgICAgICAgIDogMDsKCiAgICAgICAgICAgICRkaWZmaWN1bHR5ID0g
JHJvd1snc2Vzc2lvbl9jb3VudCddID4gMAogICAgICAgICAgICAgICAgPyBDb2RlRG5hQ2FsY3VsYXRvcjo6Y2xhbXAoJHJvd1sn
ZGlmZmljdWx0eV9zdW0nXSAvICRyb3dbJ3Nlc3Npb25fY291bnQnXSkKICAgICAgICAgICAgICAgIDogMDsKCiAgICAgICAgICAg
ICRyZWNlbmN5ID0gQ29kZURuYUNhbGN1bGF0b3I6OnJlY2VuY3lTY29yZSgkcm93WydsYXN0X3NvbHZlZF9hdCddKTsKCiAgICAg
ICAgICAgICR0b3BpY1Njb3Jlc1skdG9waWNdID0gWwogICAgICAgICAgICAgICAgJ3Njb3JlJyA9PiBDb2RlRG5hQ2FsY3VsYXRv
cjo6dG9waWNTY29yZSgkYWNjdXJhY3ksICRkaWZmaWN1bHR5LCAkc3BlZWQsICRyZWNlbmN5KSwKICAgICAgICAgICAgICAgICdh
Y2N1cmFjeScgPT4gJGFjY3VyYWN5LAogICAgICAgICAgICAgICAgJ3NwZWVkJyA9PiAkc3BlZWQsCiAgICAgICAgICAgICAgICAn
ZGlmZmljdWx0eScgPT4gJGRpZmZpY3VsdHksCiAgICAgICAgICAgICAgICAncmVjZW5jeScgPT4gJHJlY2VuY3ksCiAgICAgICAg
ICAgICAgICAnYXR0ZW1wdGVkJyA9PiAoaW50KSAkcm93WydhdHRlbXB0ZWQnXSwKICAgICAgICAgICAgICAgICdzb2x2ZWQnID0+
IChpbnQpICRyb3dbJ3NvbHZlZCddLAogICAgICAgICAgICBdOwogICAgICAgIH0KCiAgICAgICAgdWFzb3J0KAogICAgICAgICAg
ICAkdG9waWNTY29yZXMsCiAgICAgICAgICAgIGZuIChhcnJheSAkbGVmdCwgYXJyYXkgJHJpZ2h0KSA9PiAkcmlnaHRbJ3Njb3Jl
J10gPD0+ICRsZWZ0WydzY29yZSddCiAgICAgICAgKTsKCiAgICAgICAgJGFjY3VyYWN5ID0gJHRvdGFsID4gMAogICAgICAgICAg
ICA/IENvZGVEbmFDYWxjdWxhdG9yOjpjbGFtcCgoJGFjY2VwdGVkIC8gJHRvdGFsKSAqIDEwMCkKICAgICAgICAgICAgOiAwOwoK
ICAgICAgICAkc3BlZWQgPSAkZ2xvYmFsU2Vzc2lvbkNvdW50ID4gMAogICAgICAgICAgICA/IENvZGVEbmFDYWxjdWxhdG9yOjpj
bGFtcCgkZ2xvYmFsU3BlZWRTdW0gLyAkZ2xvYmFsU2Vzc2lvbkNvdW50KQogICAgICAgICAgICA6IDA7CgogICAgICAgICRjaGFs
bGVuZ2UgPSAkZ2xvYmFsU2Vzc2lvbkNvdW50ID4gMAogICAgICAgICAgICA/IENvZGVEbmFDYWxjdWxhdG9yOjpjbGFtcCgkZ2xv
YmFsRGlmZmljdWx0eVN1bSAvICRnbG9iYWxTZXNzaW9uQ291bnQpCiAgICAgICAgICAgIDogMDsKCiAgICAgICAgJHNvbHZlZFRv
cGljcyA9IGNvdW50KAogICAgICAgICAgICBhcnJheV9maWx0ZXIoJHRvcGljU2NvcmVzLCBmbiAoYXJyYXkgJHJvdykgPT4gJHJv
d1snc29sdmVkJ10gPiAwKQogICAgICAgICk7CgogICAgICAgICR2ZXJzYXRpbGl0eSA9IGNvdW50KCR0b3BpY1Njb3JlcykgPiAw
CiAgICAgICAgICAgID8gQ29kZURuYUNhbGN1bGF0b3I6OmNsYW1wKCgkc29sdmVkVG9waWNzIC8gY291bnQoJHRvcGljU2NvcmVz
KSkgKiAxMDApCiAgICAgICAgICAgIDogMDsKCiAgICAgICAgJGNvbnNpc3RlbmN5ID0gQ29kZURuYUNhbGN1bGF0b3I6OmNvbnNp
c3RlbmN5KCRyZWNlbnQpOwoKICAgICAgICAkYXR0ZW1wdGVkU2NvcmVzID0gYXJyYXlfY29sdW1uKAogICAgICAgICAgICBhcnJh
eV9maWx0ZXIoJHRvcGljU2NvcmVzLCBmbiAoYXJyYXkgJHJvdykgPT4gJHJvd1snYXR0ZW1wdGVkJ10gPiAwKSwKICAgICAgICAg
ICAgJ3Njb3JlJwogICAgICAgICk7CgogICAgICAgICRhdmVyYWdlVG9waWNTY29yZSA9ICRhdHRlbXB0ZWRTY29yZXMKICAgICAg
ICAgICAgPyBhcnJheV9zdW0oJGF0dGVtcHRlZFNjb3JlcykgLyBjb3VudCgkYXR0ZW1wdGVkU2NvcmVzKQogICAgICAgICAgICA6
IDA7CgogICAgICAgICRwcm9ibGVtU29sdmluZyA9IENvZGVEbmFDYWxjdWxhdG9yOjpjbGFtcCgKICAgICAgICAgICAgJGF2ZXJh
Z2VUb3BpY1Njb3JlICogMC40NSArCiAgICAgICAgICAgICRhY2N1cmFjeSAqIDAuMzAgKwogICAgICAgICAgICAkY2hhbGxlbmdl
ICogMC4yNQogICAgICAgICk7CgogICAgICAgICRkaW1lbnNpb25zID0gWwogICAgICAgICAgICAncHJvYmxlbV9zb2x2aW5nJyA9
PiAkcHJvYmxlbVNvbHZpbmcsCiAgICAgICAgICAgICdhY2N1cmFjeScgPT4gJGFjY3VyYWN5LAogICAgICAgICAgICAnc3BlZWQn
ID0+ICRzcGVlZCwKICAgICAgICAgICAgJ2NvbnNpc3RlbmN5JyA9PiAkY29uc2lzdGVuY3ksCiAgICAgICAgICAgICd2ZXJzYXRp
bGl0eScgPT4gJHZlcnNhdGlsaXR5LAogICAgICAgICAgICAnY2hhbGxlbmdlJyA9PiAkY2hhbGxlbmdlLAogICAgICAgIF07Cgog
ICAgICAgICRsYWJlbHMgPSBbCiAgICAgICAgICAgICdwcm9ibGVtX3NvbHZpbmcnID0+ICdQcm9ibGVtIFNvbHZpbmcnLAogICAg
ICAgICAgICAnYWNjdXJhY3knID0+ICdBY2N1cmFjeScsCiAgICAgICAgICAgICdzcGVlZCcgPT4gJ1NwZWVkJywKICAgICAgICAg
ICAgJ2NvbnNpc3RlbmN5JyA9PiAnQ29uc2lzdGVuY3knLAogICAgICAgICAgICAndmVyc2F0aWxpdHknID0+ICdWZXJzYXRpbGl0
eScsCiAgICAgICAgICAgICdjaGFsbGVuZ2UnID0+ICdDaGFsbGVuZ2UgSGFuZGxpbmcnLAogICAgICAgIF07CgogICAgICAgICRk
ZXNjZW5kaW5nID0gJGRpbWVuc2lvbnM7CiAgICAgICAgYXJzb3J0KCRkZXNjZW5kaW5nKTsKICAgICAgICAkc3RyZW5ndGhzID0g
YXJyYXlfc2xpY2UoYXJyYXlfa2V5cygkZGVzY2VuZGluZyksIDAsIDIpOwoKICAgICAgICAkYXNjZW5kaW5nID0gJGRpbWVuc2lv
bnM7CiAgICAgICAgYXNvcnQoJGFzY2VuZGluZyk7CiAgICAgICAgJGdyb3d0aEFyZWFzID0gYXJyYXlfc2xpY2UoYXJyYXlfa2V5
cygkYXNjZW5kaW5nKSwgMCwgMik7CgogICAgICAgIHJldHVybiBbCiAgICAgICAgICAgICd1c2VyJyA9PiAkdXNlci0+dG9BcnJh
eSgpLAogICAgICAgICAgICAnZGltZW5zaW9ucycgPT4gJGRpbWVuc2lvbnMsCiAgICAgICAgICAgICdkaW1lbnNpb25fbGFiZWxz
JyA9PiAkbGFiZWxzLAogICAgICAgICAgICAnb3ZlcmFsbCcgPT4gQ29kZURuYUNhbGN1bGF0b3I6OmNsYW1wKGFycmF5X3N1bSgk
ZGltZW5zaW9ucykgLyBjb3VudCgkZGltZW5zaW9ucykpLAogICAgICAgICAgICAnYXJjaGV0eXBlJyA9PiBDb2RlRG5hQ2FsY3Vs
YXRvcjo6YXJjaGV0eXBlKCRkaW1lbnNpb25zKSwKICAgICAgICAgICAgJ3RvcGljcycgPT4gJHRvcGljU2NvcmVzLAogICAgICAg
ICAgICAnc3RyZW5ndGhzJyA9PiBhcnJheV9tYXAoZm4gKHN0cmluZyAka2V5KSA9PiAkbGFiZWxzWyRrZXldLCAkc3RyZW5ndGhz
KSwKICAgICAgICAgICAgJ2dyb3d0aF9hcmVhcycgPT4gYXJyYXlfbWFwKGZuIChzdHJpbmcgJGtleSkgPT4gJGxhYmVsc1ska2V5
XSwgJGdyb3d0aEFyZWFzKSwKICAgICAgICAgICAgJ3N0YXRzJyA9PiBbCiAgICAgICAgICAgICAgICAndG90YWxfc3VibWlzc2lv
bnMnID0+ICR0b3RhbCwKICAgICAgICAgICAgICAgICdhY2NlcHRlZF9zdWJtaXNzaW9ucycgPT4gJGFjY2VwdGVkLAogICAgICAg
ICAgICAgICAgJ3NvbHZlZF9wcm9ibGVtcycgPT4gJHNvbHZlZFByb2JsZW1zLAogICAgICAgICAgICAgICAgJ3NvbHZlZF90b3Bp
Y3MnID0+ICRzb2x2ZWRUb3BpY3MsCiAgICAgICAgICAgIF0sCiAgICAgICAgXTsKICAgIH0KCiAgICBwcml2YXRlIGZ1bmN0aW9u
IGJsYW5rKCk6IGFycmF5CiAgICB7CiAgICAgICAgcmV0dXJuIFsKICAgICAgICAgICAgJ3N1Ym1pc3Npb25zJyA9PiAwLAogICAg
ICAgICAgICAnYWNjZXB0ZWQnID0+IDAsCiAgICAgICAgICAgICdhdHRlbXB0ZWQnID0+IDAsCiAgICAgICAgICAgICdzb2x2ZWQn
ID0+IDAsCiAgICAgICAgICAgICdsYXN0X3NvbHZlZF9hdCcgPT4gbnVsbCwKICAgICAgICAgICAgJ3Nlc3Npb25fY291bnQnID0+
IDAsCiAgICAgICAgICAgICdzcGVlZF9zdW0nID0+IDAuMCwKICAgICAgICAgICAgJ2RpZmZpY3VsdHlfc3VtJyA9PiAwLjAsCiAg
ICAgICAgXTsKICAgIH0KfQo=
'@

$bytes = [Convert]::FromBase64String(($encoded -replace '\s', ''))
$content = [Text.Encoding]::UTF8.GetString($bytes)
$utf8NoBom = New-Object System.Text.UTF8Encoding($false)

[System.IO.File]::WriteAllText($servicePath, $content, $utf8NoBom)

Run-Native $php @('-l', $servicePath) 'CodeDnaService.php syntax check failed'
Ok 'Code DNA service syntax is valid'

Step 'Running backend contract checks'

Push-Location (Join-Path $root 'backend')
try {
    Run-Native $php @('tools\contract_test.php') 'Backend contract checks failed'
}
finally {
    Pop-Location
}

Ok '43 backend contract checks pass'

Step 'Running corrected real Code DNA smoke test'

$smokePath = Join-Path $root 'backend\storage\framework\code-dna-smoke.php'

$smoke = @'
<?php

declare(strict_types=1);

use App\Models\User;
use App\Services\CodeDnaService;
use Illuminate\Contracts\Console\Kernel;

$backendRoot = dirname(__DIR__, 2);

require $backendRoot . '/vendor/autoload.php';

$app = require $backendRoot . '/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$user = User::query()
    ->orderByRaw("CASE WHEN username = 'nafiz' THEN 0 ELSE 1 END")
    ->orderByDesc('rating')
    ->first();

if (!$user) {
    fwrite(STDERR, "No CodeForge user exists for the Code DNA smoke test.\n");
    exit(2);
}

$start = microtime(true);

try {
    $dna = app(CodeDnaService::class)->calculate((string) $user->id);
} catch (Throwable $e) {
    fwrite(STDERR, $e::class . ': ' . $e->getMessage() . "\n");
    exit(3);
}

$elapsed = microtime(true) - $start;

if (!isset($dna['overall'], $dna['dimensions'], $dna['topics'], $dna['stats'])) {
    fwrite(STDERR, "Code DNA returned an incomplete response.\n");
    exit(4);
}

echo 'User: ' . $user->username . PHP_EOL;
echo 'Overall: ' . $dna['overall'] . PHP_EOL;
echo 'Topics: ' . count($dna['topics']) . PHP_EOL;
echo 'Submissions: ' . $dna['stats']['total_submissions'] . PHP_EOL;
echo 'Generation time: ' . number_format($elapsed, 3) . 's' . PHP_EOL;

if ($elapsed > 12.0) {
    fwrite(STDERR, "Code DNA generation exceeded the 12-second safety limit.\n");
    exit(5);
}
'@

[System.IO.File]::WriteAllText($smokePath, $smoke, $utf8NoBom)

$smokeExit = 1

try {
    Push-Location (Join-Path $root 'backend')

    $oldPreference = $ErrorActionPreference
    $ErrorActionPreference = 'Continue'

    & $php $smokePath
    $smokeExit = $LASTEXITCODE

    $ErrorActionPreference = $oldPreference
}
finally {
    Pop-Location

    if (Test-Path -LiteralPath $smokePath) {
        Remove-Item -LiteralPath $smokePath -Force
    }
}

if ($smokeExit -ne 0) {
    Warn "Real Code DNA smoke test failed with exit code $smokeExit."
    Write-Host 'This is now a genuine runtime/database error rather than the old test-path mistake.' -ForegroundColor Yellow
    Write-Host 'Send the error immediately above.' -ForegroundColor Yellow
    exit $smokeExit
}

Ok 'Real Code DNA calculation succeeded'

Step 'Running frontend contract checks'

Push-Location (Join-Path $root 'frontend')
try {
    Run-Native $npm @('run', 'test:contract') 'Frontend contract checks failed'
    Ok '27 frontend contract checks pass'

    Step 'Running Svelte compiler and TypeScript checks'

    $oldPreference = $ErrorActionPreference
    $ErrorActionPreference = 'Continue'

    & $npm run check
    $checkExit = $LASTEXITCODE

    $ErrorActionPreference = $oldPreference

    if ($checkExit -ne 0) {
        Warn 'Code DNA backend is healthy, but Svelte still found a frontend compiler/type issue.'
        Write-Host 'Send only the LAST svelte-check error block.' -ForegroundColor Yellow
        exit $checkExit
    }

    Ok 'Svelte compiler and TypeScript checks pass'

    Step 'Building SvelteKit production bundle'

    $oldPreference = $ErrorActionPreference
    $ErrorActionPreference = 'Continue'

    & $npm run build
    $buildExit = $LASTEXITCODE

    $ErrorActionPreference = $oldPreference

    if ($buildExit -ne 0) {
        Warn 'Type checking passed, but the production build found another issue.'
        Write-Host 'Send only the LAST build error block.' -ForegroundColor Yellow
        exit $buildExit
    }

    Ok 'SvelteKit production build passes'
}
finally {
    Pop-Location
}

Step 'Running final CodeForge verification'

$verify = Join-Path $root 'verify-codeforge.ps1'

if (Test-Path -LiteralPath $verify) {
    $oldPreference = $ErrorActionPreference
    $ErrorActionPreference = 'Continue'

    & powershell -NoProfile -ExecutionPolicy Bypass -File $verify -FullBuild
    $verifyExit = $LASTEXITCODE

    $ErrorActionPreference = $oldPreference

    if ($verifyExit -ne 0) {
        Warn 'Compiler and build passed, but final verification found another issue.'
        Write-Host 'Send only the LAST verification error block.' -ForegroundColor Yellow
        exit $verifyExit
    }

    Ok 'Final full verification passes'
}

Step 'PATCH-010B complete'

Write-Host 'PATCH-010B COMPLETED SUCCESSFULLY' -ForegroundColor Green
Write-Host ''
Write-Host 'Code DNA backend smoke test: PASS' -ForegroundColor White
Write-Host 'Backend contracts:            PASS' -ForegroundColor White
Write-Host 'Frontend contracts:           PASS' -ForegroundColor White
Write-Host 'Svelte/TypeScript:             PASS' -ForegroundColor White
Write-Host 'Production build:              PASS' -ForegroundColor White
Write-Host ''
Write-Host "Backup: $backup" -ForegroundColor DarkGray
Write-Host ''
Write-Host 'Start/restart CodeForge:' -ForegroundColor Cyan
Write-Host '  powershell -ExecutionPolicy Bypass -File .\start-codeforge.ps1' -ForegroundColor White
Write-Host ''
Write-Host 'Then test:' -ForegroundColor Cyan
Write-Host '  http://localhost:5173/code-dna' -ForegroundColor White
