<?php
function updateUserTagStats($uId, $pdo) {
    $globalStmt = $pdo->query("
        SELECT pt.tag, 
               SUM(CASE WHEN s.status = 'AC' THEN 1 ELSE 0 END) as total_ac,
               COUNT(s.submission_id) as total_subs
        FROM problem_tags pt
        JOIN submissions s ON pt.problem_id = s.problem_id
        GROUP BY pt.tag
    ");
    $globalStats = [];
    while ($row = $globalStmt->fetch(PDO::FETCH_ASSOC)) {
        $globalStats[$row['tag']] = $row['total_subs'] > 0 ? ($row['total_ac'] / $row['total_subs']) : 0.5;
    }

    $userStmt = $pdo->prepare("
        SELECT pt.tag,
               COUNT(DISTINCT s.problem_id) as attempted,
               COUNT(DISTINCT CASE WHEN s.status = 'AC' THEN s.problem_id END) as solved,
               SUM(CASE WHEN s.status != 'AC' THEN 1 ELSE 0 END) as failed_submissions
        FROM problem_tags pt
        JOIN submissions s ON pt.problem_id = s.problem_id
        WHERE s.user_id = ?
        GROUP BY pt.tag
    ");
    $userStmt->execute([$uId]);
    $userTags = $userStmt->fetchAll(PDO::FETCH_ASSOC);

    $k = 3;
    $upsert = $pdo->prepare("
        INSERT INTO user_tag_stats (user_id, tag, attempted, solved, failed_submissions, weakness_score)
        VALUES (?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            attempted = VALUES(attempted),
            solved = VALUES(solved),
            failed_submissions = VALUES(failed_submissions),
            weakness_score = VALUES(weakness_score)
    ");

    foreach ($userTags as $row) {
        $tag = $row['tag'];
        $attempted = (int)$row['attempted'];
        $solved = (int)$row['solved'];
        $failed = (int)$row['failed_submissions'];
        $globalAcc = $globalStats[$tag] ?? 0.5;

        $shrunkAccuracy = ($solved + ($k * $globalAcc)) / ($attempted + $k);
        $avgFailed = $attempted > 0 ? ($failed / $attempted) : 0;
        $frictionPenalty = $avgFailed / ($avgFailed + 2);
        $weaknessScore = (0.7 * (1.0 - $shrunkAccuracy)) + (0.3 * $frictionPenalty);

        $upsert->execute([$uId, $tag, $attempted, $solved, $failed, $weaknessScore]);
    }
}

function explainRecommendation($weakTag, $weaknessScore, $strongTag = null, $isUnexplored = false) {
    if ($isUnexplored) {
        return "You have unexplored territory in '{$weakTag}'. Solving these will build your foundation.";
    }
    $penaltyPercent = round($weaknessScore * 100);
    $strongName = $strongTag ? $strongTag : 'other domains';
    return "You are strong in {$strongName} but consistently struggle with {$weakTag} ({$penaltyPercent}% friction index). Try this targeted challenge.";
}

function getRecommendations($uId, $pdo, $limit = 3) {
    updateUserTagStats($uId, $pdo);

    $stmt = $pdo->prepare("
        SELECT tag, weakness_score 
        FROM user_tag_stats 
        WHERE user_id = ? AND attempted >= 2
        ORDER BY weakness_score DESC 
        LIMIT 1
    ");
    $stmt->execute([$uId]);
    $weakProfile = $stmt->fetch(PDO::FETCH_ASSOC);

    $targetTag = null;
    $isUnexplored = false;
    $score = 0;

    if ($weakProfile) {
        $targetTag = $weakProfile['tag'];
        $score = $weakProfile['weakness_score'];
    } else {
        $unexpStmt = $pdo->prepare("
            SELECT pt.tag 
            FROM problem_tags pt
            LEFT JOIN user_tag_stats uts ON pt.tag = uts.tag AND uts.user_id = ?
            WHERE uts.attempted IS NULL OR uts.attempted < 2
            LIMIT 1
        ");
        $unexpStmt->execute([$uId]);
        $unexplored = $unexpStmt->fetch(PDO::FETCH_ASSOC);
        $targetTag = $unexplored ? $unexplored['tag'] : 'Graph';
        $isUnexplored = true;
    }

    $checkCount = $pdo->prepare("
        SELECT COUNT(*) FROM problem_tags pt 
        JOIN problems p ON pt.problem_id = p.problem_id 
        WHERE pt.tag = ? AND p.problem_id NOT IN (
            SELECT problem_id FROM submissions WHERE user_id = ? AND status = 'AC'
        )
    ");
    $checkCount->execute([$targetTag, $uId]);
    $activeProblemCount = $checkCount->fetchColumn();

    $congratsMessage = null;

    if ($activeProblemCount == 0) {
        $congratsMessage = "🎉 Congratulations! You have successfully mastered the current set of {$limit} challenges in '{$targetTag}'. We have generated a fresh batch of 3 advanced problems for you!";
        
        $uniqueBatchId = time();
        $autoTitles = [
            "Advanced {$targetTag} Mastery Level {$uniqueBatchId} - A",
            "Complex {$targetTag} Optimization Level {$uniqueBatchId} - B",
            "Expert {$targetTag} Architectural Design Level {$uniqueBatchId} - C"
        ];
        
        $insertProb = $pdo->prepare("INSERT INTO problems (title, difficulty, rating, tags) VALUES (?, 'Medium', 1550, ?)");
        $insertTagMap = $pdo->prepare("INSERT INTO problem_tags (problem_id, tag) VALUES (?, ?)");

        foreach ($autoTitles as $title) {
            $insertProb->execute([$title, "Graph, {$targetTag}"]);
            $newId = $pdo->lastInsertId();
            $insertTagMap->execute([$newId, $targetTag]);
            $insertTagMap->execute([$newId, 'Graph']);
        }
    }

    $probStmt = $pdo->prepare("
        SELECT p.* 
        FROM problems p
        JOIN problem_tags pt ON p.problem_id = pt.problem_id
        WHERE pt.tag = ? 
        AND p.problem_id NOT IN (
            SELECT problem_id FROM submissions 
            WHERE user_id = ? AND status = 'AC'
        )
        ORDER BY p.rating ASC
        LIMIT ?
    ");
    $probStmt->bindValue(1, $targetTag, PDO::PARAM_STR);
    $probStmt->bindValue(2, $uId, PDO::PARAM_INT);
    $probStmt->bindValue(3, (int)$limit, PDO::PARAM_INT);
    $probStmt->execute();
    $problems = $probStmt->fetchAll(PDO::FETCH_ASSOC);

    $strongStmt = $pdo->prepare("
        SELECT tag FROM user_tag_stats 
        WHERE user_id = ? AND tag != ? AND solved > 0
        ORDER BY weakness_score ASC LIMIT 1
    ");
    $strongStmt->execute([$uId, $targetTag]);
    $strongTag = $strongStmt->fetchColumn() ?: null;

    $explanation = explainRecommendation($targetTag, $score, $strongTag, $isUnexplored);

    return [
        'problems' => $problems,
        'explanation' => $explanation,
        'target_tag' => $targetTag,
        'congrats_message' => $congratsMessage
    ];
}
?>