<?php
require_once __DIR__ . '/../config/db.php';

function getCurrentLevel($xp, $pdo) {
    $stmt = $pdo->prepare("
        SELECT * FROM levels
        WHERE xp_required <= :xp
        ORDER BY xp_required DESC
        LIMIT 1
    ");
    $stmt->execute(['xp' => $xp]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getNextLevel($xp, $pdo) {
    $stmt = $pdo->prepare("
        SELECT * FROM levels
        WHERE xp_required > :xp
        ORDER BY xp_required ASC
        LIMIT 1
    ");
    $stmt->execute(['xp' => $xp]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getXpProgress($xp, $pdo) {
    $current = getCurrentLevel($xp, $pdo);
    $next = getNextLevel($xp, $pdo);

    if (!$current || !$next) {
        return 100;
    }

    $range = $next['xp_required'] - $current['xp_required'];
    $progress = $xp - $current['xp_required'];

    return ($range > 0) ? ($progress / $range) * 100 : 100;
}

function difficultyKidLabel(string $difficulty): string {
    return match($difficulty) {
        'Easy' => 'Sprout',
        'Medium' => 'Sapling',
        'Hard' => 'Oak',
        default => $difficulty,
    };
}

function getUserBadges($userId, $pdo) {
    $stmt = $pdo->prepare("
        SELECT b.*, ub.earnedAt
        FROM badges b
        INNER JOIN user_badges ub ON b.id = ub.badgeId
        WHERE ub.userId = :uid
        ORDER BY ub.earnedAt DESC
    ");
    $stmt->execute(['uid' => $userId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getAllBadgesWithStatus($userId, $pdo) {
    $stmt = $pdo->prepare("
        SELECT b.*, ub.earnedAt
        FROM badges b
        LEFT JOIN user_badges ub ON b.id = ub.badgeId AND ub.userId = :uid
        ORDER BY b.id
    ");
    $stmt->execute(['uid' => $userId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function awardBadge($userId, $badgeId, $pdo) {
    $stmt = $pdo->prepare("
        INSERT IGNORE INTO user_badges (userId, badgeId)
        VALUES (:uid, :bid)
    ");
    $stmt->execute(['uid' => $userId, 'bid' => $badgeId]);

    if ($stmt->rowCount() > 0) {
        $stmt = $pdo->prepare("SELECT * FROM badges WHERE id = :bid");
        $stmt->execute(['bid' => $badgeId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    return null;
}

function checkAndAwardBadges($userId, $pdo) {
    $newBadges = [];

    // First Steps - first ever AC
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM Submissions
        WHERE userId = :uid AND verdict = 'AC'
    ");
    $stmt->execute(['uid' => $userId]);
    if ((int)$stmt->fetchColumn() >= 1) {
        $badge = awardBadge($userId, 'first_steps', $pdo);
        if ($badge) $newBadges[] = $badge;
    }

    // On a Roll - 3-day streak
    $stmt = $pdo->prepare("SELECT current_streak FROM Users WHERE id = :uid");
    $stmt->execute(['uid' => $userId]);
    if ((int)$stmt->fetchColumn() >= 3) {
        $badge = awardBadge($userId, 'on_a_roll', $pdo);
        if ($badge) $newBadges[] = $badge;
    }

    // Century - 100 total XP
    $stmt = $pdo->prepare("SELECT xp FROM Users WHERE id = :uid");
    $stmt->execute(['uid' => $userId]);
    if ((int)$stmt->fetchColumn() >= 100) {
        $badge = awardBadge($userId, 'century', $pdo);
        if ($badge) $newBadges[] = $badge;
    }

    // Explorer - solved in 3+ topics
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT p.topic)
        FROM Submissions s
        INNER JOIN Problems p ON s.problemId = p.id
        WHERE s.userId = :uid AND s.verdict = 'AC'
    ");
    $stmt->execute(['uid' => $userId]);
    if ((int)$stmt->fetchColumn() >= 3) {
        $badge = awardBadge($userId, 'explorer', $pdo);
        if ($badge) $newBadges[] = $badge;
    }

    // Topic Master - 100% mastery in any one topic
    $stmt = $pdo->prepare("
        SELECT p.topic, COUNT(p.id) AS total,
               COUNT(DISTINCT s.problemId) AS solved
        FROM Problems p
        LEFT JOIN Submissions s ON p.id = s.problemId
            AND s.userId = :uid AND s.verdict = 'AC'
        GROUP BY p.topic
        HAVING total > 0 AND solved = total
    ");
    $stmt->execute(['uid' => $userId]);
    if ($stmt->fetch(PDO::FETCH_ASSOC)) {
        $badge = awardBadge($userId, 'topic_master', $pdo);
        if ($badge) $newBadges[] = $badge;
    }

    // Sprout Master - 10 Easy solves
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT s.problemId)
        FROM Submissions s
        INNER JOIN Problems p ON s.problemId = p.id
        WHERE s.userId = :uid AND s.verdict = 'AC' AND p.difficulty = 'Easy'
    ");
    $stmt->execute(['uid' => $userId]);
    if ((int)$stmt->fetchColumn() >= 10) {
        $badge = awardBadge($userId, 'sprout_master', $pdo);
        if ($badge) $newBadges[] = $badge;
    }

    return $newBadges;
}
