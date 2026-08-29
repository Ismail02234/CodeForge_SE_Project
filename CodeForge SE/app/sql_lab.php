<?php
require_once __DIR__ . '/core/bootstrap.php';

require_admin($pdo);

$query = trim((string) ($_POST['query'] ?? ''));
$error = null;
$rows = [];
$columns = [];
$elapsed = null;
$truncated = false;

function validate_admin_read_query(string $query): array
{
    $query = trim($query);
    if ($query === '') {
        return [false, 'Write a SELECT, WITH, SHOW, DESCRIBE or EXPLAIN query.'];
    }
    if (strlen($query) > 5000) {
        return [false, 'Query is too long for the SQL Lab.'];
    }

    $sql = preg_replace('/;\s*$/', '', $query) ?? $query;
    if (str_contains($sql, ';')) {
        return [false, 'Run one statement at a time.'];
    }
    if (!preg_match('/^(SELECT|WITH|SHOW|DESCRIBE|DESC|EXPLAIN)\b/i', ltrim($sql))) {
        return [false, 'SQL Lab is read-only.'];
    }
    if (preg_match('/\b(INSERT|UPDATE|DELETE|DROP|ALTER|CREATE|TRUNCATE|REPLACE|GRANT|REVOKE|OUTFILE|DUMPFILE|LOAD_FILE|SLEEP|BENCHMARK)\b/i', $sql)) {
        return [false, 'Mutating or dangerous SQL is blocked.'];
    }

    return [true, $sql];
}

if (request_method('POST')) {
    verify_csrf();
    [$ok, $value] = validate_admin_read_query($query);

    if (!$ok) {
        $error = $value;
    } else {
        $timeoutEnabled = false;
        try {
            try {
                $pdo->exec('SET SESSION max_statement_time = 2');
                $timeoutEnabled = true;
            } catch (PDOException) {
                $timeoutEnabled = false;
            }

            $start = microtime(true);
            $stmt = $pdo->query($value);
            $maxRows = 500;

            while (($row = $stmt->fetch(PDO::FETCH_ASSOC)) !== false) {
                if ($columns === []) {
                    $columns = array_keys($row);
                }
                if (count($rows) >= $maxRows) {
                    $truncated = true;
                    break;
                }
                $rows[] = $row;
            }
            $stmt->closeCursor();
            $elapsed = round((microtime(true) - $start) * 1000, 3);
        } catch (PDOException $exception) {
            $error = mb_substr($exception->getMessage(), 0, 500);
        } finally {
            if ($timeoutEnabled) {
                try {
                    $pdo->exec('SET SESSION max_statement_time = 0');
                } catch (PDOException) {
                }
            }
        }
    }
}

$pageTitle = 'SQL Lab';
include __DIR__ . '/includes/header.php';
?>
<div class="page">
    <div class="page-head">
        <div><span class="eyebrow">Administrator · DBMS demonstration</span><h1 class="page-title">SQL Lab</h1><p class="page-subtitle">PDO-based read-only exploration for JOIN, GROUP BY, subquery and EXPLAIN demonstrations. Result size and execution time are capped to keep the development database responsive.</p></div>
    </div>
    <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

    <section class="grid grid-3">
        <div class="card card-pad">
            <span class="eyebrow">Examples</span><h3>Try a query</h3>
            <div class="code-box">SELECT p.topic, COUNT(*) problems
FROM problems p
GROUP BY p.topic
ORDER BY problems DESC;</div>
            <div class="code-box mt-2">EXPLAIN SELECT *
FROM submissions
WHERE user_id = 'u1'
AND verdict = 'AC';</div>
        </div>
        <div class="card card-pad span-2">
            <form method="post" class="form-grid">
                <?= csrf_field() ?>
                <div class="field full"><label>Read-only SQL</label><textarea class="textarea sql-editor" data-tab-indent name="query" maxlength="5000" placeholder="SELECT ..."><?= e($query) ?></textarea></div>
                <div class="field full"><button class="btn btn-primary">Execute query</button></div>
            </form>

            <?php if ($elapsed !== null): ?>
                <div class="alert alert-success mt-2">Query completed in <?= e($elapsed) ?> ms · <?= count($rows) ?> rows loaded<?= $truncated ? ' · output capped at 500 rows' : '' ?>.</div>
            <?php endif; ?>

            <?php if ($columns): ?>
                <div class="table-wrap"><table class="table"><thead><tr><?php foreach ($columns as $column): ?><th><?= e($column) ?></th><?php endforeach; ?></tr></thead><tbody>
                <?php foreach (array_slice($rows, 0, 200) as $row): ?><tr><?php foreach ($row as $value): ?><td><?= e($value) ?></td><?php endforeach; ?></tr><?php endforeach; ?>
                </tbody></table></div>
                <?php if (count($rows) > 200): ?><div class="muted small mt-2">Displaying the first 200 rows in the browser.</div><?php endif; ?>
            <?php endif; ?>
        </div>
    </section>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
