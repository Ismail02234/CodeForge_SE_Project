<?php
require_once __DIR__ . '/../config/db.php';

function updateUserTagStats(string $userId, PDO $pdo): void {
    $stmt = $pdo->prepare("
        SELECT pt.tag,
               COUNT(s.id) AS attempts,
               COUNT(DISTINCT s.problemId) AS problemsAttempted,
               COUNT(DISTINCT CASE WHEN s.verdict = 'AC' THEN s.problemId END) AS problemsSolved,
               SUM(CASE WHEN s.verdict != 'AC' THEN 1 ELSE 0 END) AS totalFailedSubmissions,
               MAX(s.timestamp) AS lastAttemptAt
        FROM problem_tags pt
        INNER JOIN Submissions s ON pt.problemId = s.problemId AND s.userId = :uid
        GROUP BY pt.tag
    ");
    $stmt->execute(['uid' => $userId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $global = getGlobalTagAccuracy($pdo);

    $upsert = $pdo->prepare("
        INSERT INTO user_tag_stats
            (userId, tag, attempts, problemsAttempted, problemsSolved, totalFailedSubmissions, avgFailedPerProblem, accuracy, weaknessScore, lastAttemptAt)
        VALUES
            (:uid, :tag, :attempts, :problemsAttempted, :problemsSolved, :totalFailedSubmissions, :avgFailedPerProblem, :accuracy, :weaknessScore, :lastAttemptAt)
        ON DUPLICATE KEY UPDATE
            attempts = VALUES(attempts),
            problemsAttempted = VALUES(problemsAttempted),
            problemsSolved = VALUES(problemsSolved),
            totalFailedSubmissions = VALUES(totalFailedSubmissions),
            avgFailedPerProblem = VALUES(avgFailedPerProblem),
            accuracy = VALUES(accuracy),
            weaknessScore = VALUES(weaknessScore),
            lastAttemptAt = VALUES(lastAttemptAt),
            updatedAt = CURRENT_TIMESTAMP
    ");

    foreach ($rows as $row) {
        $attempts = (int)($row['attempts'] ?? 0);
        $problemsAttempted = (int)($row['problemsAttempted'] ?? 0);
        $problemsSolved = (int)($row['problemsSolved'] ?? 0);
        $totalFailedSubmissions = (int)($row['totalFailedSubmissions'] ?? 0);

        $accuracy = $problemsAttempted > 0 ? ($problemsSolved / $problemsAttempted) : 0.0;

        $avgFailedPerProblem = $problemsAttempted > 0 ? ($totalFailedSubmissions / $problemsAttempted) : 0.0;

        $prior = $global[$row['tag']] ?? 0.0;
        $k = 3.0;
        $shrunkAccuracy = ($problemsSolved + $k * $prior) / ($problemsAttempted + $k);

        $frictionPenalty = $avgFailedPerProblem / ($avgFailedPerProblem + 2);
        $weaknessScore = 0.7 * (1 - $shrunkAccuracy) + 0.3 * $frictionPenalty;

        $upsert->execute([
            'uid' => $userId,
            'tag' => $row['tag'],
            'attempts' => $attempts,
            'problemsAttempted' => $problemsAttempted,
            'problemsSolved' => $problemsSolved,
            'totalFailedSubmissions' => $totalFailedSubmissions,
            'avgFailedPerProblem' => $avgFailedPerProblem,
            'accuracy' => $accuracy,
            'weaknessScore' => $weaknessScore,
            'lastAttemptAt' => $row['lastAttemptAt'] ?? null,
        ]);
    }
}

function getGlobalTagAccuracy(PDO $pdo): array {
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }

    $stmt = $pdo->query("
        SELECT pt.tag,
               COUNT(DISTINCT CONCAT(s.userId, '|', s.problemId)) AS attempted,
               COUNT(DISTINCT CASE WHEN s.verdict = 'AC' THEN CONCAT(s.userId, '|', s.problemId) END) AS solved
        FROM problem_tags pt
        LEFT JOIN Submissions s ON pt.problemId = s.problemId
        GROUP BY pt.tag
    ");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $cache = [];
    foreach ($rows as $row) {
        $attempted = (int)($row['attempted'] ?? 0);
        $solved = (int)($row['solved'] ?? 0);
        $cache[$row['tag']] = $attempted > 0 ? ($solved / $attempted) : 0.0;
    }

    return $cache;
}

function getSkillProfile(string $userId, PDO $pdo): array {
    $stmt = $pdo->prepare("
        SELECT *
        FROM user_tag_stats
        WHERE userId = :uid
        ORDER BY weaknessScore DESC
    ");
    $stmt->execute(['uid' => $userId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getRecommendations(string $userId, PDO $pdo, int $limit = 3): array {
    $profile = getSkillProfile($userId, $pdo);

    $weakTag = null;
    $mode = 'unexplored';

    $confidentWeak = array_values(array_filter($profile, function ($row) {
        return (int)($row['problemsAttempted'] ?? 0) >= 2;
    }));

    if (!empty($confidentWeak)) {
        $weakTag = $confidentWeak[0]['tag'];
        $mode = 'weak';
    } else {
        $tagStmt = $pdo->prepare("
            SELECT pt.tag, COUNT(*) AS problem_count
            FROM problem_tags pt
            WHERE pt.tag NOT IN (
                SELECT tag FROM user_tag_stats WHERE userId = :uid
            )
            GROUP BY pt.tag
            ORDER BY problem_count DESC
            LIMIT 1
        ");
        $tagStmt->execute(['uid' => $userId]);
        $tagRow = $tagStmt->fetch(PDO::FETCH_ASSOC);
        if ($tagRow) {
            $weakTag = $tagRow['tag'];
            $mode = 'unexplored';
        }
    }

    if (!$weakTag) {
        return [
            'tag' => null,
            'mode' => 'none',
            'explanation' => 'Keep solving to build your skill profile!',
            'problems' => [],
        ];
    }

    $problemStmt = $pdo->prepare("
        SELECT p.*
        FROM Problems p
        INNER JOIN problem_tags pt ON p.id = pt.problemId
        WHERE pt.tag = :tag
          AND p.id NOT IN (
              SELECT problemId FROM Submissions WHERE userId = :uid AND verdict = 'AC'
          )
        ORDER BY CASE p.difficulty WHEN 'Easy' THEN 1 WHEN 'Medium' THEN 2 WHEN 'Hard' THEN 3 END ASC
        LIMIT :limit
    ");
    $problemStmt->bindValue(':tag', $weakTag);
    $problemStmt->bindValue(':uid', $userId);
    $problemStmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $problemStmt->execute();
    $problems = $problemStmt->fetchAll(PDO::FETCH_ASSOC);

    $explanation = explainRecommendation($weakTag, $mode, $profile);

    return [
        'tag' => $weakTag,
        'mode' => $mode,
        'explanation' => $explanation,
        'problems' => $problems,
    ];
}

function explainRecommendation(string $weakTag, string $mode, array $profile): string {
    if ($mode === 'unexplored') {
        return 'You haven\'t tackled any "' . $weakTag . '" problems yet — trying a few will round out your skill profile.';
    }

    $weakRow = null;
    foreach ($profile as $row) {
        if ($row['tag'] === $weakTag) {
            $weakRow = $row;
            break;
        }
    }

    if (!$weakRow) {
        return 'Keep solving to build your skill profile!';
    }

    $weakPct = round(($weakRow['accuracy'] ?? 0) * 100);
    $n = (int)($weakRow['problemsAttempted'] ?? 0);
    $avgFailed = number_format((float)($weakRow['avgFailedPerProblem'] ?? 0), 1);

    $strongRow = null;
    foreach ($profile as $row) {
        if ($row['tag'] === $weakTag) continue;
        if ((int)($row['problemsAttempted'] ?? 0) >= 2) {
            if ($strongRow === null || ($row['accuracy'] ?? 0) > ($strongRow['accuracy'] ?? 0)) {
                $strongRow = $row;
            }
        }
    }

    if ($strongRow) {
        $strongPct = round(($strongRow['accuracy'] ?? 0) * 100);
        return 'You\'re strong in ' . $strongRow['tag'] . ' (' . $strongPct . '% accuracy), but ' . $weakTag . ' problems trip you up — only ' . $weakPct . '% success over ' . $n . ' attempts, with ' . $avgFailed . ' failed submissions per problem on average. This one targets exactly that gap.';
    }

    return 'You\'ve solved ' . $weakPct . '% of the "' . $weakTag . '" problems you\'ve attempted (' . $n . ' attempts, ' . $avgFailed . ' failed submissions each on average). This one is picked to close that gap.';
}
