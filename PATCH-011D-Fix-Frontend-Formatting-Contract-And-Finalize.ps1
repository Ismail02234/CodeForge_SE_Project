# PATCH-011D-Fix-Frontend-Formatting-Contract-And-Finalize.ps1
# CodeForge 3.0
#
# PATCH-011C formatted the frontend with Prettier. One frontend contract check
# still expects an exact CSS string:
#     overscroll-behavior-y:contain
#
# Prettier correctly formats it as:
#     overscroll-behavior-y: contain
#
# This patch makes frontend contract checks formatting-insensitive while keeping
# the actual application source unchanged.
#
# It then runs:
#   - 43 backend contract checks
#   - 27 frontend contract checks
#   - Prettier format check
#   - Svelte/TypeScript checks
#   - SvelteKit production build
#   - final CodeForge verification
#
# No MySQL data, routes, controllers, services, or UI behavior are changed.
#
# Run:
#   cd D:\xampp\htdocs\codeforge
#   powershell -ExecutionPolicy Bypass -File .\PATCH-011D-Fix-Frontend-Formatting-Contract-And-Finalize.ps1

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

function Refresh-ToolPath {
    $machine = [Environment]::GetEnvironmentVariable('Path', 'Machine')
    $user = [Environment]::GetEnvironmentVariable('Path', 'User')

    $parts = @()
    if ($machine) { $parts += $machine }
    if ($user) { $parts += $user }

    $env:Path = ($parts -join ';')

    foreach ($dir in @(
        'D:\xampp\php',
        'C:\Program Files\nodejs',
        'C:\Program Files (x86)\nodejs'
    )) {
        if ((Test-Path -LiteralPath $dir) -and ($env:Path -notlike "*$dir*")) {
            $env:Path = "$dir;$env:Path"
        }
    }
}

function Find-Npm {
    Refresh-ToolPath

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

$root = Split-Path -Parent $MyInvocation.MyCommand.Path

if (-not (Test-Path -LiteralPath (Join-Path $root 'frontend\package.json'))) {
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
Refresh-ToolPath

$php = 'D:\xampp\php\php.exe'
if (-not (Test-Path -LiteralPath $php)) {
    throw 'XAMPP PHP was not found.'
}

$npm = Find-Npm
if ($null -eq $npm) {
    throw 'npm.cmd could not be found.'
}

$contractPath = Join-Path $frontend 'tools\contract-test.mjs'

if (-not (Test-Path -LiteralPath $contractPath)) {
    throw 'frontend\tools\contract-test.mjs was not found.'
}

Step 'Checking environment'

Ok "PHP $((& $php -r "echo PHP_VERSION;").Trim())"
Ok "npm $((& $npm --version).Trim())"

Step 'Backing up frontend contract test'

$stamp = Get-Date -Format 'yyyyMMdd-HHmmss'
$backup = Join-Path $root "patch-backups\PATCH-011D-$stamp"
New-Item -ItemType Directory -Force -Path $backup | Out-Null

$backupFile = Join-Path $backup 'frontend\tools\contract-test.mjs'
New-Item -ItemType Directory -Force -Path (Split-Path -Parent $backupFile) | Out-Null
Copy-Item -LiteralPath $contractPath -Destination $backupFile -Force

Ok "Backup created: $backup"

Step 'Replacing formatting-sensitive frontend contract checks'

$contract = @'
import fs from 'node:fs';
import path from 'node:path';

const root = process.cwd();

const expectedRoutes = [
  '/',
  '/login',
  '/register',
  '/dashboard',
  '/problems',
  '/problems/[id]',
  '/contests',
  '/contests/[id]',
  '/rivalry',
  '/universities',
  '/universities/compare',
  '/search',
  '/profile/[id]',
  '/code-dna',
  '/ghost-race',
  '/ghost-race/[id]',
  '/sql-battle',
  '/sql-battle/[id]',
  '/database',
  '/sql-lab',
  '/how-it-works'
];

const routeRoot = path.join(root, 'src', 'routes');

function routeExists(route) {
  const clean = route === '/' ? '' : route.slice(1);

  const candidates = [
    path.join(routeRoot, clean, '+page.svelte'),
    path.join(routeRoot, '(app)', clean, '+page.svelte')
  ];

  return candidates.some((candidate) => fs.existsSync(candidate));
}

function check(condition, label) {
  if (condition) {
    console.log('[OK]', label);
    return 0;
  }

  console.error('[FAIL]', label);
  return 1;
}

let failed = 0;

for (const route of expectedRoutes) {
  failed += check(routeExists(route), `route ${route}`);
}

const api = fs.readFileSync(path.join(root, 'src', 'lib', 'api.ts'), 'utf8');

failed += check(
  /credentials\s*:\s*['"]include['"]/.test(api),
  "API marker credentials: 'include'"
);

failed += check(
  /X-XSRF-TOKEN/.test(api),
  'API marker X-XSRF-TOKEN'
);

failed += check(
  /sanctum\/csrf-cookie/.test(api),
  'API marker sanctum/csrf-cookie'
);

const css = fs.readFileSync(path.join(root, 'src', 'app.css'), 'utf8');

failed += check(
  /Space Grotesk/.test(css),
  'UI marker Space Grotesk'
);

failed += check(
  /JetBrains Mono/.test(css),
  'UI marker JetBrains Mono'
);

failed += check(
  /overscroll-behavior-y\s*:\s*contain\b/.test(css),
  'UI marker overscroll-behavior-y: contain'
);

if (failed > 0) {
  console.error(`\n${failed} contract checks failed`);
  process.exit(1);
}

console.log(`\n${expectedRoutes.length + 6} contract checks passed`);
'@

$utf8NoBom = New-Object System.Text.UTF8Encoding($false)

[System.IO.File]::WriteAllText(
    $contractPath,
    $contract,
    $utf8NoBom
)

Ok 'Frontend contracts now check meaning instead of exact whitespace'

Step 'Running backend contracts'

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

Step 'Running frontend contracts'

Push-Location $frontend

try {
    Run-Native $npm @(
        'run',
        'test:contract'
    ) 'Frontend contract checks still have a genuine failure'

    Ok '27 frontend contract checks pass'

    Step 'Running Prettier formatting verification'

    Run-Native $npm @(
        'run',
        'format:check'
    ) 'Frontend formatting check failed'

    Ok 'Prettier format check passes'

    Step 'Running Svelte/TypeScript checks'

    Run-Native $npm @(
        'run',
        'check'
    ) 'Svelte/TypeScript checks failed'

    Ok 'Svelte/TypeScript checks pass'

    Step 'Building production SvelteKit bundle'

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
        Warn 'Direct checks passed, but final verification found another issue.'
        Write-Host 'Send the LAST verification error block.' -ForegroundColor Yellow
        exit $verifyExit
    }

    Ok 'Final CodeForge verification passes'
}

Step 'PATCH-011D complete'

Write-Host 'PATCH-011D COMPLETED SUCCESSFULLY' -ForegroundColor Green
Write-Host ''
Write-Host 'Readable-source refactor validation:' -ForegroundColor Cyan
Write-Host '  Laravel/PHP formatting:        COMPLETE'
Write-Host '  Backend contracts:             43 / 43'
Write-Host '  Frontend contracts:            27 / 27'
Write-Host '  Prettier formatting:           PASS'
Write-Host '  Svelte/TypeScript:             PASS'
Write-Host '  Production build:              PASS'
Write-Host ''
Write-Host "Backup: $backup" -ForegroundColor DarkGray
Write-Host ''
Write-Host 'Start CodeForge normally:' -ForegroundColor Cyan
Write-Host '  powershell -ExecutionPolicy Bypass -File .\start-codeforge.ps1' -ForegroundColor White
