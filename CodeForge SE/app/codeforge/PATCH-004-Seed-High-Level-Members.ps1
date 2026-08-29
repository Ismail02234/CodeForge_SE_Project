# PATCH-004-Seed-High-Level-Members.ps1
# Creates/updates high-level CodeForge accounts for:
# Nafiz Iqbal Razin -> Razin
# Ismail Hossain    -> Hossain
# Tamjid            -> Tamjid
# Ankita Saha       -> Saha
#
# Password for all accounts: 123456
# Run from D:\xampp\htdocs\codeforge

$ErrorActionPreference = "Stop"

function Write-Step($msg) {
    Write-Host ""
    Write-Host "==> $msg" -ForegroundColor Cyan
}

$scriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$root = $scriptDir
if (-not (Test-Path (Join-Path $root "core\bootstrap.php"))) {
    $root = (Get-Location).Path
}
if (-not (Test-Path (Join-Path $root "core\bootstrap.php"))) {
    throw "Could not find CodeForge project root. Put this patch inside D:\xampp\htdocs\codeforge and run it there."
}

Set-Location $root

$stamp = Get-Date -Format "yyyyMMdd-HHmmss"
$backup = Join-Path $root "patch-backups\PATCH-004-$stamp"
New-Item -ItemType Directory -Force -Path $backup | Out-Null

Write-Step "Creating database backup SQL for current matching users"

$seedPhp = Join-Path $root "patch-004-seed-users.php"
$utf8NoBom = New-Object System.Text.UTF8Encoding($false)

$php = @'
<?php
declare(strict_types=1);

require_once __DIR__ . '/core/bootstrap.php';

$accounts = [
    [
        'full_name' => 'Nafiz Iqbal Razin',
        'username' => 'Razin',
        'rating' => 3050,
        'rank' => 'Legendary Grandmaster',
        'solved' => 520,
    ],
    [
        'full_name' => 'Ismail Hossain',
        'username' => 'Hossain',
        'rating' => 2925,
        'rank' => 'International Grandmaster',
        'solved' => 468,
    ],
    [
        'full_name' => 'Tamjid',
        'username' => 'Tamjid',
        'rating' => 2810,
        'rank' => 'Grandmaster',
        'solved' => 431,
    ],
    [
        'full_name' => 'Ankita Saha',
        'username' => 'Saha',
        'rating' => 2680,
        'rank' => 'International Master',
        'solved' => 389,
    ],
];

function tableColumns(PDO $pdo, string $table): array
{
    $stmt = $pdo->query("SHOW COLUMNS FROM `$table`");
    $cols = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $cols[$row['Field']] = $row;
    }
    return $cols;
}

function makeUserId(PDO $pdo): string
{
    for ($i = 0; $i < 50; $i++) {
        $id = 'u' . random_int(10000, 99999);
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE id = :id');
        $stmt->execute(['id' => $id]);
        if ((int) $stmt->fetchColumn() === 0) {
            return $id;
        }
    }
    throw new RuntimeException('Could not generate a unique user id.');
}

$columns = tableColumns($pdo, 'users');

$required = ['id', 'username', 'password'];
foreach ($required as $requiredColumn) {
    if (!isset($columns[$requiredColumn])) {
        throw new RuntimeException("users table is missing required column: {$requiredColumn}");
    }
}

$passwordHash = password_hash('123456', PASSWORD_DEFAULT);
$created = 0;
$updated = 0;

$pdo->beginTransaction();

try {
    foreach ($accounts as $account) {
        $find = $pdo->prepare('SELECT * FROM users WHERE LOWER(username) = LOWER(:username) LIMIT 1');
        $find->execute(['username' => $account['username']]);
        $existing = $find->fetch(PDO::FETCH_ASSOC);

        $data = [
            'username' => $account['username'],
            'password' => $passwordHash,
        ];

        if (isset($columns['role'])) {
            $data['role'] = 'user';
        }
        if (isset($columns['rating'])) {
            $data['rating'] = $account['rating'];
        }
        if (isset($columns['rank'])) {
            $data['rank'] = $account['rank'];
        }
        if (isset($columns['solvedCount'])) {
            $data['solvedCount'] = $account['solved'];
        }
        if (isset($columns['solved_count'])) {
            $data['solved_count'] = $account['solved'];
        }
        if (isset($columns['full_name'])) {
            $data['full_name'] = $account['full_name'];
        }
        if (isset($columns['name'])) {
            $data['name'] = $account['full_name'];
        }

        if ($existing) {
            $setParts = [];
            $params = ['id' => $existing['id']];

            foreach ($data as $column => $value) {
                $setParts[] = "`{$column}` = :{$column}";
                $params[$column] = $value;
            }

            $sql = 'UPDATE users SET ' . implode(', ', $setParts) . ' WHERE id = :id';
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $updated++;

            echo "[UPDATED] {$account['full_name']} -> {$account['username']} | Rating {$account['rating']} | {$account['rank']}\n";
        } else {
            $data['id'] = makeUserId($pdo);

            if (isset($columns['university'])) {
                $data['university'] = null;
            }

            $fieldNames = array_keys($data);
            $quotedFields = array_map(fn($field) => "`{$field}`", $fieldNames);
            $placeholders = array_map(fn($field) => ":{$field}", $fieldNames);

            $sql = 'INSERT INTO users (' . implode(', ', $quotedFields) . ') VALUES (' . implode(', ', $placeholders) . ')';
            $stmt = $pdo->prepare($sql);
            $stmt->execute($data);
            $created++;

            echo "[CREATED] {$account['full_name']} -> {$account['username']} | Rating {$account['rating']} | {$account['rank']}\n";
        }
    }

    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    throw $e;
}

echo "\nCreated: {$created}\n";
echo "Updated: {$updated}\n";
echo "Password for all four accounts: 123456\n\n";

echo "LOGIN ACCOUNTS\n";
echo "Razin   / 123456\n";
echo "Hossain / 123456\n";
echo "Tamjid  / 123456\n";
echo "Saha    / 123456\n";
'@

[System.IO.File]::WriteAllText($seedPhp, $php, $utf8NoBom)

Write-Step "Running account seeder"

$phpExe = "php"
$xamppPhp = "D:\xampp\php\php.exe"
if (Test-Path $xamppPhp) {
    $phpExe = $xamppPhp
}

$lintOutput = & $phpExe -l $seedPhp 2>&1
if ($LASTEXITCODE -ne 0) {
    Write-Host $lintOutput -ForegroundColor Red
    throw "Seeder PHP syntax check failed."
}
Write-Host "[OK] Seeder syntax valid" -ForegroundColor Green

& $phpExe $seedPhp
if ($LASTEXITCODE -ne 0) {
    throw "Account seeding failed. Read the PHP error above."
}

Write-Step "Removing temporary seeder"
Remove-Item $seedPhp -Force

Write-Step "Patch complete"
Write-Host "PATCH-004 APPLIED SUCCESSFULLY" -ForegroundColor Green
Write-Host ""
Write-Host "Accounts:" -ForegroundColor Cyan
Write-Host "  Nafiz Iqbal Razin -> username: Razin   | password: 123456 | rating: 3050" -ForegroundColor White
Write-Host "  Ismail Hossain    -> username: Hossain | password: 123456 | rating: 2925" -ForegroundColor White
Write-Host "  Tamjid            -> username: Tamjid  | password: 123456 | rating: 2810" -ForegroundColor White
Write-Host "  Ankita Saha       -> username: Saha    | password: 123456 | rating: 2680" -ForegroundColor White
Write-Host ""
Write-Host "Refresh CodeForge with Ctrl + F5 after running this patch." -ForegroundColor Yellow
