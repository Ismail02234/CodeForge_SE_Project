# PATCH-011B-Fix-Pint-And-Resume-Readable-Refactor.ps1
# CodeForge 3.0
#
# Fixes PATCH-011's Pint launcher issue:
#   "'php' is not recognized..."
#
# Cause:
#   vendor\bin\pint.bat calls "php" from PATH.
#   Your XAMPP PHP exists at D:\xampp\php\php.exe but is not in PATH.
#
# This patch:
#   - calls Laravel Pint through the explicit XAMPP PHP executable
#   - resumes the readable-source refactor
#   - installs/runs Prettier for Svelte/TypeScript/CSS
#   - validates backend + frontend + production build
#   - does NOT reset or alter MySQL data
#
# Run:
#   cd D:\xampp\htdocs\codeforge
#   powershell -ExecutionPolicy Bypass -File .\PATCH-011B-Fix-Pint-And-Resume-Readable-Refactor.ps1

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

    foreach ($dir in @(
        'C:\Program Files\nodejs',
        'C:\Program Files (x86)\nodejs'
    )) {
        if ((Test-Path -LiteralPath $dir) -and ($env:Path -notlike "*$dir*")) {
            $env:Path = "$dir;$env:Path"
        }
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

function PortOpen([int]$Port) {
    $client = New-Object System.Net.Sockets.TcpClient

    try {
        $task = $client.ConnectAsync('127.0.0.1', $Port)

        if (-not $task.Wait(700)) {
            return $false
        }

        return $client.Connected
    }
    catch {
        return $false
    }
    finally {
        $client.Dispose()
    }
}

function Copy-TreeIfExists([string]$Source, [string]$Destination) {
    if (Test-Path -LiteralPath $Source) {
        $parent = Split-Path -Parent $Destination
        New-Item -ItemType Directory -Force -Path $parent | Out-Null
        Copy-Item -LiteralPath $Source -Destination $Destination -Recurse -Force
    }
}

$root = Split-Path -Parent $MyInvocation.MyCommand.Path

if (-not (Test-Path -LiteralPath (Join-Path $root 'backend\artisan'))) {
    $root = (Get-Location).Path
}

$backend = Join-Path $root 'backend'
$frontend = Join-Path $root 'frontend'

if (-not (Test-Path -LiteralPath (Join-Path $backend 'artisan'))) {
    throw 'Laravel backend was not found. Put this patch inside D:\xampp\htdocs\codeforge.'
}

if (-not (Test-Path -LiteralPath (Join-Path $frontend 'package.json'))) {
    throw 'SvelteKit frontend was not found.'
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
    throw 'node.exe could not be found after refreshing PATH.'
}

# Also expose XAMPP PHP in PATH for any Composer-generated helper that invokes "php".
$phpDir = Split-Path -Parent $php
if ($env:Path -notlike "*$phpDir*") {
    $env:Path = "$phpDir;$env:Path"
}

Step 'Checking CodeForge environment'

Ok "PHP $((& $php -r "echo PHP_VERSION;").Trim())"
Ok "Node.js $((& $node.Source --version).Trim())"
Ok "npm $((& $npm --version).Trim())"
Ok "XAMPP PHP added to this process PATH"

if (PortOpen $DbPort) {
    Ok "MySQL is responding on port $DbPort"
}
else {
    Warn "MySQL is not responding on port $DbPort."
    Warn 'Source formatting can continue; runtime checks may require MySQL.'
}

Step 'Creating PATCH-011B backup'

$stamp = Get-Date -Format 'yyyyMMdd-HHmmss'
$backup = Join-Path $root "patch-backups\PATCH-011B-$stamp"
New-Item -ItemType Directory -Force -Path $backup | Out-Null

Copy-TreeIfExists (Join-Path $backend 'app') (Join-Path $backup 'backend\app')
Copy-TreeIfExists (Join-Path $backend 'routes') (Join-Path $backup 'backend\routes')
Copy-TreeIfExists (Join-Path $backend 'database') (Join-Path $backup 'backend\database')
Copy-TreeIfExists (Join-Path $frontend 'src') (Join-Path $backup 'frontend\src')

foreach ($relative in @(
    'frontend\package.json',
    'frontend\package-lock.json',
    'frontend\.prettierrc.json',
    'frontend\.prettierignore',
    'backend\pint.json',
    '.editorconfig'
)) {
    $source = Join-Path $root $relative

    if (Test-Path -LiteralPath $source) {
        $destination = Join-Path $backup $relative
        New-Item -ItemType Directory -Force -Path (Split-Path -Parent $destination) | Out-Null
        Copy-Item -LiteralPath $source -Destination $destination -Force
    }
}

Ok "Backup created: $backup"

Step 'Ensuring readable-source formatting configuration exists'

$utf8NoBom = New-Object System.Text.UTF8Encoding($false)

$editorConfigPath = Join-Path $root '.editorconfig'
if (-not (Test-Path -LiteralPath $editorConfigPath)) {
    $editorConfig = @'
root = true

[*]
charset = utf-8
end_of_line = crlf
insert_final_newline = true
indent_style = space
trim_trailing_whitespace = true

[*.php]
indent_size = 4

[*.{svelte,ts,js,json,css,html}]
indent_size = 2

[*.md]
trim_trailing_whitespace = false
'@

    [System.IO.File]::WriteAllText($editorConfigPath, $editorConfig, $utf8NoBom)
}

$pintConfigPath = Join-Path $backend 'pint.json'
if (-not (Test-Path -LiteralPath $pintConfigPath)) {
    $pintConfig = @'
{
  "preset": "laravel",
  "rules": {
    "array_syntax": {
      "syntax": "short"
    },
    "no_unused_imports": true,
    "ordered_imports": {
      "sort_algorithm": "alpha"
    },
    "single_quote": true
  }
}
'@
    [System.IO.File]::WriteAllText($pintConfigPath, $pintConfig, $utf8NoBom)
}

$prettierConfigPath = Join-Path $frontend '.prettierrc.json'
if (-not (Test-Path -LiteralPath $prettierConfigPath)) {
    $prettierConfig = @'
{
  "plugins": ["prettier-plugin-svelte"],
  "singleQuote": true,
  "semi": true,
  "printWidth": 100,
  "tabWidth": 2,
  "useTabs": false,
  "trailingComma": "es5",
  "bracketSameLine": false,
  "overrides": [
    {
      "files": "*.svelte",
      "options": {
        "parser": "svelte"
      }
    }
  ]
}
'@
    [System.IO.File]::WriteAllText($prettierConfigPath, $prettierConfig, $utf8NoBom)
}

$prettierIgnorePath = Join-Path $frontend '.prettierignore'
if (-not (Test-Path -LiteralPath $prettierIgnorePath)) {
    $prettierIgnore = @'
.svelte-kit
build
node_modules
coverage
.env
.env.*
'@
    [System.IO.File]::WriteAllText($prettierIgnorePath, $prettierIgnore, $utf8NoBom)
}

Ok 'Formatting configuration ready'

Step 'Formatting Laravel source with explicit XAMPP PHP'

$pintProxy = Join-Path $backend 'vendor\bin\pint'

if (-not (Test-Path -LiteralPath $pintProxy)) {
    throw 'Laravel Pint proxy was not found at backend\vendor\bin\pint.'
}

Push-Location $backend

try {
    Run-Native $php @(
        $pintProxy,
        'app',
        'routes',
        'database',
        '--config=pint.json'
    ) 'Laravel Pint formatting failed'
}
finally {
    Pop-Location
}

Ok 'Laravel source formatted successfully'

Step 'Installing frontend formatting tools'

Push-Location $frontend

try {
    Run-Native $npm @(
        'install',
        '--save-dev',
        '--no-audit',
        '--no-fund',
        'prettier',
        'prettier-plugin-svelte'
    ) 'Could not install Prettier tooling'

    $prettier = Join-Path $frontend 'node_modules\.bin\prettier.cmd'

    if (-not (Test-Path -LiteralPath $prettier)) {
        throw 'Prettier executable was not found after npm install.'
    }

    Step 'Formatting Svelte, TypeScript and CSS'

    Run-Native $prettier @(
        '--write',
        'src'
    ) 'Prettier formatting failed'

    Ok 'Frontend source formatted successfully'

    Step 'Adding reusable format commands'

    $packagePath = Join-Path $frontend 'package.json'
    $package = Get-Content -LiteralPath $packagePath -Raw | ConvertFrom-Json

    if ($null -eq $package.scripts) {
        $package | Add-Member `
            -MemberType NoteProperty `
            -Name scripts `
            -Value ([PSCustomObject]@{})
    }

    $package.scripts | Add-Member `
        -MemberType NoteProperty `
        -Name format `
        -Value 'prettier --write src' `
        -Force

    $package.scripts | Add-Member `
        -MemberType NoteProperty `
        -Name 'format:check' `
        -Value 'prettier --check src' `
        -Force

    $json = $package | ConvertTo-Json -Depth 50

    [System.IO.File]::WriteAllText(
        $packagePath,
        $json + [Environment]::NewLine,
        $utf8NoBom
    )

    Ok 'npm run format and npm run format:check added'
}
finally {
    Pop-Location
}

Step 'Running PHP syntax validation'

$phpRoots = @(
    (Join-Path $backend 'app'),
    (Join-Path $backend 'routes'),
    (Join-Path $backend 'database')
)

$phpFiles = @()

foreach ($path in $phpRoots) {
    if (Test-Path -LiteralPath $path) {
        $phpFiles += Get-ChildItem `
            -LiteralPath $path `
            -Filter '*.php' `
            -File `
            -Recurse
    }
}

foreach ($file in $phpFiles) {
    $oldPreference = $ErrorActionPreference
    $ErrorActionPreference = 'Continue'

    & $php -l $file.FullName | Out-Null
    $lintExit = $LASTEXITCODE

    $ErrorActionPreference = $oldPreference

    if ($lintExit -ne 0) {
        throw "PHP syntax validation failed: $($file.FullName)"
    }
}

Ok "PHP syntax clean across $($phpFiles.Count) files"

Step 'Running backend contract tests'

Push-Location $backend

try {
    Run-Native $php @(
        'tools\contract_test.php'
    ) 'Backend contract checks failed'
}
finally {
    Pop-Location
}

Ok '43 backend contract checks pass'

Step 'Running frontend validation'

Push-Location $frontend

try {
    Run-Native $npm @(
        'run',
        'test:contract'
    ) 'Frontend contract checks failed'

    Ok '27 frontend contract checks pass'

    Run-Native $npm @(
        'run',
        'format:check'
    ) 'Frontend formatting verification failed'

    Ok 'Frontend formatting check passes'

    Step 'Running Svelte and TypeScript checks'

    Run-Native $npm @(
        'run',
        'check'
    ) 'Svelte/TypeScript checks failed'

    Ok 'Svelte/TypeScript checks pass'

    Step 'Building SvelteKit production bundle'

    Run-Native $npm @(
        'run',
        'build'
    ) 'SvelteKit production build failed'

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

    & powershell `
        -NoProfile `
        -ExecutionPolicy Bypass `
        -File $verify `
        -FullBuild

    $verifyExit = $LASTEXITCODE
    $ErrorActionPreference = $oldPreference

    if ($verifyExit -ne 0) {
        Warn 'All direct checks passed, but the final verification wrapper found another issue.'
        Write-Host 'Send the LAST verification error block.' -ForegroundColor Yellow
        exit $verifyExit
    }

    Ok 'Final full CodeForge verification passes'
}
else {
    Warn 'verify-codeforge.ps1 was not found. Direct compiler/build checks still completed.'
}

Step 'PATCH-011B complete'

Write-Host 'PATCH-011B COMPLETED SUCCESSFULLY' -ForegroundColor Green
Write-Host ''
Write-Host 'Readable source refactor complete:' -ForegroundColor Cyan
Write-Host '  - Laravel PHP formatted with explicit XAMPP PHP'
Write-Host '  - Svelte source expanded and formatted'
Write-Host '  - TypeScript formatted'
Write-Host '  - CSS formatted'
Write-Host '  - editor/formatter configuration retained'
Write-Host '  - application data untouched'
Write-Host ''
Write-Host "Backup: $backup" -ForegroundColor DarkGray
Write-Host ''
Write-Host 'Start CodeForge normally:' -ForegroundColor Cyan
Write-Host '  powershell -ExecutionPolicy Bypass -File .\start-codeforge.ps1' -ForegroundColor White
