<?php
function calculateUserStats($userId, $pdo) {
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_subs,
            SUM(CASE WHEN status = 'AC' THEN 1 ELSE 0 END) as total_ac
        FROM submissions 
        WHERE user_id = ?
    ");
    $stmt->execute([$userId]);
    $subData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $totalSubs = (int)($subData['total_subs'] ?? 0);
    $totalAc = (int)($subData['total_ac'] ?? 0);
    
    $accuracy = $totalSubs > 0 ? ($totalAc / $totalSubs) * 100 : 50;

    $speedStmt = $pdo->prepare("
        SELECT AVG(rating) as avg_rating 
        FROM submissions s 
        JOIN problems p ON s.problem_id = p.problem_id 
        WHERE s.user_id = ? AND s.status = 'AC'
    ");
    $speedStmt->execute([$userId]);
    $ratingData = $speedStmt->fetch(PDO::FETCH_ASSOC);
    $avgRating = (float)($ratingData['avg_rating'] ?? 1200);

    return [
        'accuracy' => round($accuracy, 2),
        'solved_count' => $totalAc,
        'avg_rating' => round($avgRating, 2)
    ];
}

function calculateWinProbability($userAId, $userBId, $pdo) {
    $statsA = calculateUserStats($userAId, $pdo);
    $statsB = calculateUserStats($userBId, $pdo);

    $scoreA = ($statsA['accuracy'] * 0.4) + ($statsA['solved_count'] * 3 * 0.3) + (($statsA['avg_rating'] / 20) * 0.3);
    $scoreB = ($statsB['accuracy'] * 0.4) + ($statsB['solved_count'] * 3 * 0.3) + (($statsB['avg_rating'] / 20) * 0.3);

    $totalScore = $scoreA + $scoreB;
    if ($totalScore == 0) {
        $probA = 50;
        $probB = 50;
    } else {
        $probA = ($scoreA / $totalScore) * 100;
        $probB = ($scoreB / $totalScore) * 100;
    }

    return [
        'userA_prob' => round($probA),
        'userB_prob' => round($probB),
        'statsA' => $statsA,
        'statsB' => $statsB
    ];
}
?>