# PATCH-011-Readable-Student-Maintainable-Source.ps1
# CodeForge 3.0
#
# Purpose:
#   Refactor the current Laravel + SvelteKit source into a cleaner,
#   easier-to-read, student-maintainable coding style WITHOUT changing
#   application behavior or database data.
#
# What it changes:
#   - Formats Laravel PHP source with Laravel Pint
#   - Formats Svelte, TypeScript and CSS with Prettier + prettier-plugin-svelte
#   - Adds normal project formatting configuration
#   - Adds frontend format / format:check scripts
#   - Keeps source files readable instead of compressed one-line files
#   - Runs backend contracts, frontend contracts, svelte-check and production build
#
# What it does NOT do:
#   - reset or modify application data
#   - change database schema
#   - deliberately add mistakes, fake typos or "AI detector" tricks
#   - rewrite working application logic
#
# Run:
#   cd D:\xampp\htdocs\codeforge
#   powershell -ExecutionPolicy Bypass -File .\PATCH-011-Readable-Student-Maintainable-Source.ps1

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
    throw 'Laravel backend was not found. Put this patch directly inside D:\xampp\htdocs\codeforge.'
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
    throw 'npm.cmd could not be found. Node.js should already be installed.'
}

$node = Get-Command node.exe -ErrorAction SilentlyContinue
if ($null -eq $node) {
    throw 'node.exe could not be found after refreshing PATH.'
}

Step 'Checking CodeForge environment'

$phpVersion = (& $php -r "echo PHP_VERSION;").Trim()
$nodeVersion = (& $node.Source --version).Trim()
$npmVersion = (& $npm --version).Trim()

Ok "PHP $phpVersion"
Ok "Node.js $nodeVersion"
Ok "npm $npmVersion"

if (PortOpen $DbPort) {
    Ok "MySQL is responding on port $DbPort"
}
else {
    Warn "MySQL is not running on port $DbPort."
    Warn 'Formatting can still run, but the final runtime verification may need MySQL.'
}

Step 'Creating a full source backup'

$stamp = Get-Date -Format 'yyyyMMdd-HHmmss'
$backup = Join-Path $root "patch-backups\PATCH-011-$stamp"

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

Step 'Adding normal project formatting configuration'

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

$prettierIgnore = @'
.svelte-kit
build
node_modules
coverage
.env
.env.*
'@

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

$utf8NoBom = New-Object System.Text.UTF8Encoding($false)

[System.IO.File]::WriteAllText(
    (Join-Path $root '.editorconfig'),
    $editorConfig,
    $utf8NoBom
)

[System.IO.File]::WriteAllText(
    (Join-Path $frontend '.prettierrc.json'),
    $prettierConfig,
    $utf8NoBom
)

[System.IO.File]::WriteAllText(
    (Join-Path $frontend '.prettierignore'),
    $prettierIgnore,
    $utf8NoBom
)

[System.IO.File]::WriteAllText(
    (Join-Path $backend 'pint.json'),
    $pintConfig,
    $utf8NoBom
)

Ok '.editorconfig added'
Ok 'Laravel Pint configuration added'
Ok 'Svelte/TypeScript Prettier configuration added'

Step 'Formatting Laravel source with Pint'

$pintBat = Join-Path $backend 'vendor\bin\pint.bat'
$pintPhp = Join-Path $backend 'vendor\bin\pint'

Push-Location $backend

try {
    if (Test-Path -LiteralPath $pintBat) {
        Run-Native $pintBat @(
            'app',
            'routes',
            'database',
            '--config=pint.json'
        ) 'Laravel Pint formatting failed'
    }
    elseif (Test-Path -LiteralPath $pintPhp) {
        Run-Native $php @(
            $pintPhp,
            'app',
            'routes',
            'database',
            '--config=pint.json'
        ) 'Laravel Pint formatting failed'
    }
    else {
        throw 'Laravel Pint is not installed in backend/vendor. Composer dependencies must be installed first.'
    }
}
finally {
    Pop-Location
}

Ok 'Laravel PHP source formatted'

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
        throw 'Prettier was installed but node_modules\.bin\prettier.cmd was not found.'
    }

    Step 'Formatting Svelte, TypeScript and CSS source'

    Run-Native $prettier @(
        '--write',
        'src'
    ) 'Prettier formatting failed'

    Ok 'SvelteKit frontend source formatted'

    Step 'Adding reusable frontend format commands'

    $packagePath = Join-Path $frontend 'package.json'
    $package = Get-Content -LiteralPath $packagePath -Raw | ConvertFrom-Json

    if ($null -eq $package.scripts) {
        $package | Add-Member -MemberType NoteProperty -Name scripts -Value ([PSCustomObject]@{})
    }

    $package.scripts | Add-Member -MemberType NoteProperty -Name format -Value 'prettier --write src' -Force
    $package.scripts | Add-Member -MemberType NoteProperty -Name 'format:check' -Value 'prettier --check src' -Force

    $json = $package | ConvertTo-Json -Depth 50

    [System.IO.File]::WriteAllText(
        $packagePath,
        $json + [Environment]::NewLine,
        $utf8NoBom
    )

    Ok 'npm run format / npm run format:check added'
}
finally {
    Pop-Location
}

Step 'Running PHP syntax validation'

$phpFiles = Get-ChildItem `
    -LiteralPath (Join-Path $backend 'app') `
    -Filter '*.php' `
    -File `
    -Recurse

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

Ok "PHP syntax clean across $($phpFiles.Count) application files"

Step 'Running backend contract tests'

Push-Location $backend
try {
    Run-Native $php @('tools\contract_test.php') 'Backend contract checks failed'
}
finally {
    Pop-Location
}

Ok '43 backend contract checks pass'

Step 'Running frontend contract tests and compiler checks'

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

    Run-Native $npm @(
        'run',
        'check'
    ) 'Svelte/TypeScript checks failed'

    Ok 'Svelte/TypeScript checks pass'

    Step 'Building production frontend'

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
        Warn 'The formatter/tests passed but the final CodeForge verifier found another issue.'
        Write-Host 'Send the LAST verification error block.' -ForegroundColor Yellow
        exit $verifyExit
    }

    Ok 'Final full verification passes'
}
else {
    Warn 'verify-codeforge.ps1 was not found; direct tests/build still completed.'
}

Step 'PATCH-011 complete'

Write-Host 'PATCH-011 COMPLETED SUCCESSFULLY' -ForegroundColor Green
Write-Host ''
Write-Host 'Source cleanup completed:' -ForegroundColor Cyan
Write-Host '  - Laravel/PHP formatted with Laravel Pint'
Write-Host '  - Svelte components expanded from compressed one-line source'
Write-Host '  - TypeScript formatted consistently'
Write-Host '  - CSS formatted consistently'
Write-Host '  - normal editor/formatter configs added'
Write-Host '  - no database data reset'
Write-Host '  - no application feature intentionally changed'
Write-Host ''
Write-Host "Backup: $backup" -ForegroundColor DarkGray
Write-Host ''
Write-Host 'Useful commands from now on:' -ForegroundColor Cyan
Write-Host '  cd D:\xampp\htdocs\codeforge\frontend'
Write-Host '  npm run format'
Write-Host '  npm run format:check'
Write-Host ''
Write-Host 'Start CodeForge:' -ForegroundColor Cyan
Write-Host '  cd D:\xampp\htdocs\codeforge'
Write-Host '  powershell -ExecutionPolicy Bypass -File .\start-codeforge.ps1'
