# PATCH-005-FirstName-MaxProfiles.ps1
# CodeForge - first-name lowercase usernames + near-max profile parameters
#
# Accounts:
#   Nafiz Iqbal Razin -> nafiz
#   Ismail Hossain    -> ismail
#   Tamjid            -> tamjid
#   Ankita Saha       -> ankita
# Password for all: 123456
#
# This patch:
# - safely resolves the accounts created by PATCH-004 / original seed accounts
# - changes usernames to lowercase first names
# - sets very high ratings/ranks
# - seeds high-quality AC history across every programming topic
# - pushes Code DNA dimensions close to 100
# - gives full problem coverage
# - gives maximum available contest scores
# - gives full SQL Arena problem coverage with very fast AC runs
# - prints the resulting Code DNA scores for verification
#
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
$backup = Join-Path $root "patch-backups\PATCH-005-$stamp"
New-Item -ItemType Directory -Force -Path $backup | Out-Null

Write-Step "Preparing safe temporary seeder"

$seedPhp = Join-Path $root "patch-005-max-profiles.php"
$utf8NoBom = New-Object System.Text.UTF8Encoding($false)

$php = @'
<?php
declare(strict_types=1);

require_once __DIR__ . '/core/bootstrap.php';
require_once __DIR__ . '/services/CodeDnaService.php';

$accounts = [
    [
        'full_name' => 'Nafiz Iqbal Razin',
        'username' => 'nafiz',
        'aliases' => ['nafiz', 'Razin', 'razin', 'Nafiz'],
        'rating' => 4950,
    ],
    [
        'full_name' => 'Ismail Hossain',
        'username' => 'ismail',
        'aliases' => ['ismail', 'Ismail', 'Hossain', 'hossain'],
        'rating' => 4900,
    ],
    [
        'full_name' => 'Tamjid',
        'username' => 'tamjid',
        'aliases' => ['tamjid', 'Tamjid'],
        'rating' => 4850,
    ],
    [
        'full_name' => 'Ankita Saha',
        'username' => 'ankita',
        'aliases' => ['ankita', 'Ankita', 'Saha', 'saha'],
        'rating' => 4800,
    ],
];

function tableExists(PDO $pdo, string $table): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.tables
         WHERE table_schema = DATABASE() AND table_name = :table'
    );
    $stmt->execute(['table' => $table]);
    return (int) $stmt->fetchColumn() > 0;
}

function columns(PDO $pdo, string $table): array
{
    $result = [];
    foreach ($pdo->query("SHOW COLUMNS FROM `$table`")->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $result[$row['Field']] = $row;
    }
    return $result;
}

function findUsersByAliases(PDO $pdo, array $aliases): array
{
    if ($aliases === []) {
        return [];
    }

    $conditions = [];
    $params = [];
    foreach (array_values($aliases) as $i => $alias) {
        $key = "a{$i}";
        $conditions[] = "LOWER(username) = LOWER(:{$key})";
        $params[$key] = $alias;
    }

    $stmt = $pdo->prepare(
        'SELECT * FROM users WHERE ' . implode(' OR ', $conditions) . ' ORDER BY created_at ASC, id ASC'
    );
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function chooseCanonical(array $rows, string $desired): ?array
{
    foreach ($rows as $row) {
        if (strtolower((string) $row['username']) === strtolower($desired)) {
            return $row;
        }
    }
    return $rows[0] ?? null;
}

function referencedCount(PDO $pdo, string $userId): int
{
    $targets = [
        ['contests', 'created_by'],
        ['contest_participants', 'user_id'],
        ['problem_sessions', 'user_id'],
        ['submissions', 'user_id'],
        ['ghost_races', 'challenger_id'],
        ['ghost_races', 'ghost_user_id'],
        ['sql_battles', 'player1_id'],
        ['sql_battles', 'player2_id'],
        ['sql_battles', 'winner_id'],
        ['sql_attempts', 'user_id'],
        ['activity_logs', 'user_id'],
    ];

    $total = 0;
    foreach ($targets as [$table, $column]) {
        if (!tableExists($pdo, $table)) {
            continue;
        }
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM `$table` WHERE `$column` = :uid");
        $stmt->execute(['uid' => $userId]);
        $total += (int) $stmt->fetchColumn();
    }
    return $total;
}

function newUserId(PDO $pdo): string
{
    for ($i = 0; $i < 100; $i++) {
        $id = 'u' . random_int(10000, 99999);
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE id = :id');
        $stmt->execute(['id' => $id]);
        if ((int) $stmt->fetchColumn() === 0) {
            return $id;
        }
    }

    throw new RuntimeException('Could not generate unique user ID.');
}

function forceUsernameCase(PDO $pdo, string $id, string $desired): void
{
    $tmp = '__cf_tmp_' . preg_replace('/[^A-Za-z0-9_]/', '', $id) . '_' . random_int(1000, 9999);

    $stmt = $pdo->prepare('UPDATE users SET username = :tmp WHERE id = :id');
    $stmt->execute(['tmp' => $tmp, 'id' => $id]);

    $stmt = $pdo->prepare('UPDATE users SET username = :username WHERE id = :id');
    $stmt->execute(['username' => $desired, 'id' => $id]);
}

function upsertContestScores(PDO $pdo, string $userId): void
{
    if (!tableExists($pdo, 'contests') || !tableExists($pdo, 'contest_participants')) {
        return;
    }

    $contests = $pdo->query(
        'SELECT c.id,
                COALESCE((SELECT SUM(cp.points) FROM contest_problems cp WHERE cp.contest_id = c.id), 0) AS max_points
         FROM contests c'
    )->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare(
        'INSERT INTO contest_participants(contest_id, user_id, score, joined_at)
         VALUES(:contest_id, :user_id, :score, NOW())
         ON DUPLICATE KEY UPDATE score = VALUES(score)'
    );

    foreach ($contests as $contest) {
        $max = max(1000, (int) $contest['max_points']);
        $stmt->execute([
            'contest_id' => $contest['id'],
            'user_id' => $userId,
            'score' => $max,
        ]);
    }
}

function seedSqlArena(PDO $pdo, string $userId, string $username, int $rating, int $accountIndex): void
{
    if (
        !tableExists($pdo, 'arena_users') ||
        !tableExists($pdo, 'arena_problems') ||
        !tableExists($pdo, 'arena_submissions')
    ) {
        return;
    }

    $university = $pdo->query('SELECT name FROM arena_universities ORDER BY name LIMIT 1')->fetchColumn();
    if (!$university) {
        return;
    }

    $upsert = $pdo->prepare(
        'INSERT INTO arena_users(user_id, username, university, rating)
         VALUES(:uid, :username, :university, :rating)
         ON DUPLICATE KEY UPDATE
            username = VALUES(username),
            university = VALUES(university),
            rating = VALUES(rating)'
    );
    $upsert->execute([
        'uid' => $userId,
        'username' => $username,
        'university' => $university,
        'rating' => $rating,
    ]);

    $delete = $pdo->prepare("DELETE FROM arena_submissions WHERE submission_id LIKE :prefix");
    $delete->execute(['prefix' => "cfmaxsql_{$accountIndex}_%"]);

    $problems = $pdo->query('SELECT problem_id FROM arena_problems ORDER BY problem_id')->fetchAll(PDO::FETCH_COLUMN);

    $insert = $pdo->prepare(
        'INSERT INTO arena_submissions(submission_id, user_id, problem_id, verdict, runtime_ms)
         VALUES(:sid, :uid, :pid, \'AC\', :runtime)'
    );

    foreach ($problems as $i => $problemId) {
        $insert->execute([
            'sid' => "cfmaxsql_{$accountIndex}_" . ($i + 1),
            'uid' => $userId,
            'pid' => $problemId,
            'runtime' => 2 + ($i % 3),
        ]);
    }
}

function seedProgrammingHistory(PDO $pdo, string $userId, int $accountIndex): void
{
    if (!tableExists($pdo, 'problems') || !tableExists($pdo, 'problem_sessions') || !tableExists($pdo, 'submissions')) {
        throw new RuntimeException('Required programming-history tables are missing.');
    }

    // Make the patch idempotent: remove only history created by this patch family.
    $deleteSessions = $pdo->prepare(
        "DELETE FROM problem_sessions
         WHERE user_id = :uid AND id LIKE :prefix"
    );
    $deleteSessions->execute([
        'uid' => $userId,
        'prefix' => "cfmax{$accountIndex}_%",
    ]);

    $problems = $pdo->query(
        'SELECT id, topic, difficulty FROM problems ORDER BY id'
    )->fetchAll(PDO::FETCH_ASSOC);

    if ($problems === []) {
        throw new RuntimeException('No programming problems exist.');
    }

    $hard = array_values(array_filter(
        $problems,
        static fn(array $p): bool => strtolower((string) $p['difficulty']) === 'hard'
    ));

    if ($hard === []) {
        $hard = $problems;
    }

    // 300 clean AC sessions per account:
    // - one solve for every current problem => 100% versatility / full coverage
    // - remaining solves concentrated on hard problems => challenge handling ~99
    // - very quick solve times => speed 100
    // - latest outcomes all AC => consistency 100
    $targetSessions = max(300, count($problems));

    $sessionInsert = $pdo->prepare(
        'INSERT INTO problem_sessions
         (id, user_id, problem_id, started_at, completed_at, solve_time_seconds, status)
         VALUES(:id, :uid, :pid, :started, :completed, :seconds, \'solved\')'
    );

    $submissionInsert = $pdo->prepare(
        'INSERT INTO submissions
         (id, session_id, problem_id, user_id, contest_id, verdict, submitted_at,
          elapsed_seconds, runtime_ms, memory_kb, language, source_code, failed_test_case)
         VALUES(:id, :session_id, :pid, :uid, NULL, \'AC\', :submitted,
                :elapsed, :runtime, :memory, \'C++\', :source, NULL)'
    );

    $sequence = [];

    // First: solve every problem once.
    foreach ($problems as $problem) {
        $sequence[] = $problem;
    }

    // Then: dominate hard difficulty without sacrificing topic coverage.
    $h = 0;
    while (count($sequence) < $targetSessions) {
        $sequence[] = $hard[$h % count($hard)];
        $h++;
    }

    // Keep all synthetic solves very recent; ordering is deterministic.
    $baseTs = time() - 3600;

    foreach ($sequence as $i => $problem) {
        $difficulty = strtolower((string) $problem['difficulty']);

        $solveSeconds = match ($difficulty) {
            'easy' => 45,
            'medium' => 90,
            'hard' => 150,
            default => 60,
        };

        $startedTs = $baseTs + ($i * 8);
        $completedTs = $startedTs + $solveSeconds;

        $started = date('Y-m-d H:i:s', $startedTs);
        $completed = date('Y-m-d H:i:s', $completedTs);

        $sessionId = "cfmax{$accountIndex}_" . str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT);
        $submissionId = "cfsub{$accountIndex}_" . str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT);

        $sessionInsert->execute([
            'id' => $sessionId,
            'uid' => $userId,
            'pid' => $problem['id'],
            'started' => $started,
            'completed' => $completed,
            'seconds' => $solveSeconds,
        ]);

        $submissionInsert->execute([
            'id' => $submissionId,
            'session_id' => $sessionId,
            'pid' => $problem['id'],
            'uid' => $userId,
            'submitted' => $completed,
            'elapsed' => $solveSeconds,
            'runtime' => 3 + ($i % 8),
            'memory' => 768 + (($i % 6) * 64),
            'source' => '// CodeForge elite demo profile seed',
        ]);
    }
}

$userColumns = columns($pdo, 'users');
foreach (['id', 'username', 'password'] as $required) {
    if (!isset($userColumns[$required])) {
        throw new RuntimeException("users table missing required column: {$required}");
    }
}

$passwordHash = password_hash('123456', PASSWORD_DEFAULT);
$resolved = [];

$pdo->beginTransaction();

try {
    foreach ($accounts as $index => $account) {
        $rows = findUsersByAliases($pdo, $account['aliases']);
        $canonical = chooseCanonical($rows, $account['username']);

        if (!$canonical) {
            $id = newUserId($pdo);

            $fields = [
                'id' => $id,
                'username' => $account['username'],
                'password' => $passwordHash,
            ];

            if (isset($userColumns['role'])) {
                $fields['role'] = 'user';
            }
            if (isset($userColumns['rating'])) {
                $fields['rating'] = $account['rating'];
            }
            if (isset($userColumns['rank'])) {
                $fields['rank'] = 'Legendary Grandmaster';
            }
            if (isset($userColumns['university'])) {
                $fields['university'] = null;
            }

            $names = array_keys($fields);
            $quoted = array_map(static fn(string $f): string => "`{$f}`", $names);
            $holders = array_map(static fn(string $f): string => ":{$f}", $names);

            $stmt = $pdo->prepare(
                'INSERT INTO users(' . implode(',', $quoted) . ')
                 VALUES(' . implode(',', $holders) . ')'
            );
            $stmt->execute($fields);

            $canonical = ['id' => $id, 'username' => $account['username']];
        }

        $canonicalId = (string) $canonical['id'];

        // Resolve PATCH-004 aliases. Keep a dependent duplicate as a clearly marked legacy
        // account rather than destructively cascading its history.
        foreach ($rows as $row) {
            if ((string) $row['id'] === $canonicalId) {
                continue;
            }

            $duplicateId = (string) $row['id'];
            $refs = referencedCount($pdo, $duplicateId);

            if ($refs === 0) {
                $delete = $pdo->prepare('DELETE FROM users WHERE id = :id');
                $delete->execute(['id' => $duplicateId]);
                echo "[CLEANUP] Removed unused duplicate account {$row['username']}\n";
            } else {
                $legacy = strtolower($account['username']) . '_legacy_' . substr(preg_replace('/[^A-Za-z0-9]/', '', $duplicateId), -5);
                $rename = $pdo->prepare('UPDATE users SET username = :username WHERE id = :id');
                $rename->execute(['username' => $legacy, 'id' => $duplicateId]);
                echo "[SAFE] Preserved dependent duplicate as {$legacy}\n";
            }
        }

        forceUsernameCase($pdo, $canonicalId, $account['username']);

        $sets = ['password = :password'];
        $params = [
            'password' => $passwordHash,
            'id' => $canonicalId,
        ];

        if (isset($userColumns['rating'])) {
            $sets[] = 'rating = :rating';
            $params['rating'] = $account['rating'];
        }
        if (isset($userColumns['rank'])) {
            $sets[] = '`rank` = :rank';
            $params['rank'] = 'Legendary Grandmaster';
        }
        if (isset($userColumns['role'])) {
            $sets[] = 'role = :role';
            $params['role'] = 'user';
        }

        $update = $pdo->prepare(
            'UPDATE users SET ' . implode(', ', $sets) . ' WHERE id = :id'
        );
        $update->execute($params);

        $resolved[] = [
            'id' => $canonicalId,
            'full_name' => $account['full_name'],
            'username' => $account['username'],
            'rating' => $account['rating'],
            'index' => $index + 1,
        ];

        echo "[ACCOUNT] {$account['full_name']} -> {$account['username']} | rating {$account['rating']}\n";
    }

    foreach ($resolved as $account) {
        echo "[STATS] Seeding elite performance history for {$account['username']}...\n";

        seedProgrammingHistory($pdo, $account['id'], $account['index']);
        upsertContestScores($pdo, $account['id']);
        seedSqlArena($pdo, $account['id'], $account['username'], $account['rating'], $account['index']);

        if (tableExists($pdo, 'activity_logs')) {
            $log = $pdo->prepare(
                "INSERT INTO activity_logs(user_id, action, details, created_at)
                 VALUES(:uid, 'profile.elite_seed', 'Elite demo profile generated by PATCH-005', NOW())"
            );
            $log->execute(['uid' => $account['id']]);
        }
    }

    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    throw $e;
}

echo "\n============================================================\n";
echo "CODE DNA VERIFICATION\n";
echo "============================================================\n";

$dnaService = new CodeDnaService($pdo);

foreach ($resolved as $account) {
    $dna = $dnaService->calculate($account['id']);

    echo "\n{$account['full_name']} (@{$account['username']})\n";
    echo "Rating: {$account['rating']} | Rank: Legendary Grandmaster\n";
    echo "Overall DNA: {$dna['overall']}%\n";

    foreach ($dna['dimensions'] as $key => $value) {
        $label = $dna['dimension_labels'][$key] ?? $key;
        echo "  - {$label}: {$value}%\n";
    }

    echo "  - Problems solved: {$dna['stats']['solved_problems']}\n";
    echo "  - Solved topics: {$dna['stats']['solved_topics']}\n";
    echo "  - Accepted submissions: {$dna['stats']['accepted_submissions']}\n";
}

echo "\n============================================================\n";
echo "LOGIN CREDENTIALS\n";
echo "============================================================\n";
echo "nafiz  / 123456\n";
echo "ismail / 123456\n";
echo "tamjid / 123456\n";
echo "ankita / 123456\n";
'@

[System.IO.File]::WriteAllText($seedPhp, $php, $utf8NoBom)

Write-Step "Checking patch PHP syntax"

$phpExe = "php"
$xamppPhp = "D:\xampp\php\php.exe"

if (Test-Path $xamppPhp) {
    $phpExe = $xamppPhp
}

$lintOutput = & $phpExe -l $seedPhp 2>&1

if ($LASTEXITCODE -ne 0) {
    Write-Host $lintOutput -ForegroundColor Red
    throw "PATCH-005 PHP seeder failed syntax validation."
}

Write-Host "[OK] Seeder syntax valid" -ForegroundColor Green

Write-Step "Applying usernames and elite profile statistics"

& $phpExe $seedPhp

if ($LASTEXITCODE -ne 0) {
    throw "PATCH-005 failed. Read the PHP error above. The database transaction rolls back on seeding errors."
}

Write-Step "Removing temporary seeder"
Remove-Item $seedPhp -Force

Write-Step "Patch complete"

Write-Host "PATCH-005 APPLIED SUCCESSFULLY" -ForegroundColor Green
Write-Host ""
Write-Host "Final accounts:" -ForegroundColor Cyan
Write-Host "  Nafiz Iqbal Razin -> nafiz  / 123456 | Rating 4950" -ForegroundColor White
Write-Host "  Ismail Hossain    -> ismail / 123456 | Rating 4900" -ForegroundColor White
Write-Host "  Tamjid            -> tamjid / 123456 | Rating 4850" -ForegroundColor White
Write-Host "  Ankita Saha       -> ankita / 123456 | Rating 4800" -ForegroundColor White
Write-Host ""
Write-Host "The terminal output above shows the actual calculated Code DNA dimensions." -ForegroundColor Yellow
Write-Host "Refresh CodeForge with Ctrl + F5." -ForegroundColor Yellow
