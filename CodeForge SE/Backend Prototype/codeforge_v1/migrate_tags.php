<?php
require_once __DIR__ . '/config/db.php';

$inserted = 0;

$stmt = $pdo->query("SELECT id, tags FROM Problems WHERE tags IS NOT NULL AND tags <> ''");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $problemId = $row['id'];
    $tags = array_map('trim', explode(',', $row['tags']));
    foreach ($tags as $tag) {
        if ($tag === '') continue;
        $tag = strtolower($tag);
        $ins = $pdo->prepare("INSERT IGNORE INTO problem_tags (problemId, tag) VALUES (:pid, :tag)");
        $ins->execute(['pid' => $problemId, 'tag' => $tag]);
        $inserted += $ins->rowCount();
    }
}

echo "Inserted {$inserted} rows into problem_tags.\n";
