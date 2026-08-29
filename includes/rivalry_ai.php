<?php

/**
 * AI Rivalry Prediction Engine
 *
 * This file calculates the performance
 * of two users and predicts their
 * winning probability.
 */


/*
|--------------------------------------------------------------------------
| 1. Get Basic User Information
|--------------------------------------------------------------------------
*/

function getUserInfo($pdo, $userId)
{
    $stmt = $pdo->prepare("
        SELECT
            id,
            username,
            rating,
            solvedCount
        FROM Users
        WHERE id = ?
    ");

    $stmt->execute([$userId]);

    return $stmt->fetch(PDO::FETCH_ASSOC);
}


/*
|--------------------------------------------------------------------------
| 2. Calculate Submission Accuracy
|--------------------------------------------------------------------------
*/

function getSubmissionAccuracy($pdo, $userId)
{
    $stmt = $pdo->prepare("
        SELECT
            COUNT(*) AS total,
            SUM(
                CASE
                    WHEN verdict = 'AC' THEN 1
                    ELSE 0
                END
            ) AS accepted
        FROM Submissions
        WHERE userId = ?
    ");

    $stmt->execute([$userId]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    $total = (int)($row['total'] ?? 0);
    $accepted = (int)($row['accepted'] ?? 0);

    if ($total === 0) {
        return 0;
    }

    return ($accepted / $total) * 100;
}/*
|--------------------------------------------------------------------------
| 3. Calculate Recent Performance
|--------------------------------------------------------------------------
*/

function getRecentPerformance($pdo, $userId)
{
    $stmt = $pdo->prepare("
        SELECT
            s.verdict,
            p.difficulty
        FROM Submissions s
        LEFT JOIN Problems p
            ON s.problemId = p.id
        WHERE s.userId = ?
        ORDER BY s.timestamp DESC
        LIMIT 20
    ");

    $stmt->execute([$userId]);

    $submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($submissions) === 0) {
        return 0;
    }

    $score = 0;
    $totalWeight = 0;

    foreach ($submissions as $submission) {

        /*
         * Difficulty weight
         */
        $difficulty = strtolower(
            trim($submission['difficulty'] ?? '')
        );

        if ($difficulty === 'hard') {
            $difficultyWeight = 3;
        } elseif ($difficulty === 'medium') {
            $difficultyWeight = 2;
        } else {
            $difficultyWeight = 1;
        }

        /*
         * Accepted submission gets full score.
         * Wrong submission gets partial score.
         */
        if ($submission['verdict'] === 'AC') {
            $performance = 100;
        } else {
            $performance = 0;
        }

        $score += $performance * $difficultyWeight;
        $totalWeight += 100 * $difficultyWeight;
    }

    if ($totalWeight === 0) {
        return 0;
    }

    return ($score / $totalWeight) * 100;
}
/*
|--------------------------------------------------------------------------
| 4. Calculate Average Solving Time
|--------------------------------------------------------------------------
*/

function getAverageSolvingTime($pdo, $userId)
{
    $stmt = $pdo->prepare("
        SELECT AVG(solving_time) AS average_time
        FROM Submissions
        WHERE userId = ?
          AND verdict = 'AC'
          AND solving_time > 0
    ");

    $stmt->execute([$userId]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    $averageTime = (float)($row['average_time'] ?? 0);

    return $averageTime;
}
/*
|--------------------------------------------------------------------------
| 5. Convert Solving Time to Speed Score
|--------------------------------------------------------------------------
*/

function getSpeedScore($pdo, $userId)
{
    $averageTime = getAverageSolvingTime($pdo, $userId);

    /*
     * No solving-time data
     */
    if ($averageTime <= 0) {
        return 50;
    }

    /*
     * Convert time into a 0-100 score.
     *
     * Faster solving = higher score.
     */
    $speedScore = 100 - ($averageTime / 10);

    /*
     * Keep score between 0 and 100.
     */
    $speedScore = max(0, min(100, $speedScore));

    return round($speedScore, 2);
}
/*
|--------------------------------------------------------------------------
| 6. Calculate Problem Difficulty Score
|--------------------------------------------------------------------------
*/

function getDifficultyScore($pdo, $userId)
{
    $stmt = $pdo->prepare("
        SELECT
            p.difficulty,
            s.verdict
        FROM Submissions s
        INNER JOIN Problems p
            ON s.problemId = p.id
        WHERE s.userId = ?
          AND s.verdict = 'AC'
    ");

    $stmt->execute([$userId]);

    $submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($submissions) === 0) {
        return 50;
    }

    $totalScore = 0;
    $maxPossibleScore = 0;

    foreach ($submissions as $submission) {

        $difficulty = strtolower(
            trim($submission['difficulty'] ?? '')
        );

        /*
         * Difficulty levels
         */
        if ($difficulty === 'hard') {

            $difficultyValue = 100;

        } elseif ($difficulty === 'medium') {

            $difficultyValue = 65;

        } else {

            $difficultyValue = 35;
        }

        $totalScore += $difficultyValue;
        $maxPossibleScore += 100;
    }

    return round(
        ($totalScore / $maxPossibleScore) * 100,
        2
    );
}/*
|--------------------------------------------------------------------------
| 7. Calculate Contest Performance
|--------------------------------------------------------------------------
*/

function getContestPerformance($pdo, $userId)
{
    $stmt = $pdo->prepare("
        SELECT AVG(score) AS average_score
        FROM contest_participants
        WHERE userId = ?
    ");

    $stmt->execute([$userId]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    $averageScore = (float)($row['average_score'] ?? 0);

    /*
     * No contest history
     */
    if ($averageScore <= 0) {
        return 50;
    }

    /*
     * Convert contest score into 0-100 range.
     *
     * Assuming contest score is already
     * approximately within 0-100.
     */
    $contestScore = max(
        0,
        min(100, $averageScore)
    );

    return round($contestScore, 2);
}
/*
|--------------------------------------------------------------------------
| 8. Calculate Topic Strength
|--------------------------------------------------------------------------
*/

function getTopicStrength($pdo, $userId)
{
    $stmt = $pdo->prepare("
        SELECT
            p.topic,
            COUNT(*) AS solved_count
        FROM Submissions s
        INNER JOIN Problems p
            ON s.problemId = p.id
        WHERE s.userId = ?
          AND s.verdict = 'AC'
          AND p.topic IS NOT NULL
          AND p.topic != ''
        GROUP BY p.topic
        ORDER BY solved_count DESC
    ");

    $stmt->execute([$userId]);

    $topics = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($topics) === 0) {
        return [];
    }

    /*
     * Find maximum number of solved problems
     */
    $maxSolved = (int)$topics[0]['solved_count'];

    $result = [];

    foreach ($topics as $topic) {

        $solved = (int)$topic['solved_count'];

        /*
         * Convert topic activity into 0-100 score.
         */
        $strength = ($solved / $maxSolved) * 100;

        $result[$topic['topic']] = round($strength, 2);
    }

    return $result;
}/*
|--------------------------------------------------------------------------
| 9. Calculate Head-to-Head History
|--------------------------------------------------------------------------
*/

function getHeadToHead($pdo, $user1Id, $user2Id)
{
    $stmt = $pdo->prepare("
        SELECT
            user1_id,
            user2_id,
            winner_id
        FROM rivalry_history
        WHERE
            (user1_id = :user1 AND user2_id = :user2)
            OR
            (user1_id = :user2 AND user2_id = :user1)
        ORDER BY played_at DESC
    ");

    $stmt->execute([
        ':user1' => $user1Id,
        ':user2' => $user2Id
    ]);

    $matches = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $user1Wins = 0;
    $user2Wins = 0;
    $draws = 0;

    foreach ($matches as $match) {

        if ($match['winner_id'] === $user1Id) {

            $user1Wins++;

        } elseif ($match['winner_id'] === $user2Id) {

            $user2Wins++;

        } else {

            $draws++;
        }
    }

    $totalMatches = count($matches);

    /*
     * No previous rivalry.
     */
    if ($totalMatches === 0) {
        return [
            'user1_wins' => 0,
            'user2_wins' => 0,
            'draws' => 0,
            'total_matches' => 0,
            'user1_score' => 50,
            'user2_score' => 50
        ];
    }

    /*
     * Convert historical wins into scores.
     */
    $user1Score = ($user1Wins / $totalMatches) * 100;
    $user2Score = ($user2Wins / $totalMatches) * 100;

    /*
     * Give both users a neutral baseline
     * if all previous matches were draws.
     */
    if (($user1Wins + $user2Wins) === 0) {
        $user1Score = 50;
        $user2Score = 50;
    }

    return [
        'user1_wins' => $user1Wins,
        'user2_wins' => $user2Wins,
        'draws' => $draws,
        'total_matches' => $totalMatches,
        'user1_score' => round($user1Score, 2),
        'user2_score' => round($user2Score, 2)
    ];
}
/*
|--------------------------------------------------------------------------
| 10. Calculate Final AI Score
|--------------------------------------------------------------------------
*/

function calculateAIScore($pdo, $userId, $opponentId = null)
{
    /*
     * Get individual feature scores
     */
    $user = getUserInfo($pdo, $userId);

    $accuracy = getSubmissionAccuracy(
        $pdo,
        $userId
    );

    $recentPerformance = getRecentPerformance(
        $pdo,
        $userId
    );

    $speedScore = getSpeedScore(
        $pdo,
        $userId
    );

    $difficultyScore = getDifficultyScore(
        $pdo,
        $userId
    );

    $contestPerformance = getContestPerformance(
        $pdo,
        $userId
    );


    /*
     * Topic strength
     */
    $topicStrength = getTopicStrength(
        $pdo,
        $userId
    );

    if (count($topicStrength) > 0) {

        $topicScore = array_sum(
            $topicStrength
        ) / count($topicStrength);

    } else {

        $topicScore = 50;
    }


    /*
     * Head-to-head score
     */
    $headToHeadScore = 50;

    if ($opponentId !== null) {

        $h2h = getHeadToHead(
            $pdo,
            $userId,
            $opponentId
        );

        $headToHeadScore =
            $h2h['user1_score'];
    }


    /*
     * Weighted AI score
     */
    $finalScore =
        ($recentPerformance * 0.20) +
        ($accuracy * 0.15) +
        ($speedScore * 0.15) +
        ($difficultyScore * 0.15) +
        ($contestPerformance * 0.15) +
        ($topicScore * 0.10) +
        ($headToHeadScore * 0.10);


    /*
     * Keep score between 0 and 100
     */
    $finalScore = max(
        0,
        min(100, $finalScore)
    );


    return [
        'user_id' => $userId,
        'username' => $user['username'] ?? 'Unknown',

        'recent_performance' =>
            round($recentPerformance, 2),

        'accuracy' =>
            round($accuracy, 2),

        'speed' =>
            round($speedScore, 2),

        'difficulty' =>
            round($difficultyScore, 2),

        'contest' =>
            round($contestPerformance, 2),

        'topic' =>
            round($topicScore, 2),

        'head_to_head' =>
            round($headToHeadScore, 2),

        'final_score' =>
            round($finalScore, 2)
    ];
}
/*
|--------------------------------------------------------------------------
| 11. Calculate Winning Probability
|--------------------------------------------------------------------------
*/

function calculateWinningProbability(
    $pdo,
    $user1Id,
    $user2Id
) {
    /*
     * Calculate both AI scores
     */
    $user1 = calculateAIScore(
        $pdo,
        $user1Id,
        $user2Id
    );

    $user2 = calculateAIScore(
        $pdo,
        $user2Id,
        $user1Id
    );


    $score1 = $user1['final_score'];
    $score2 = $user2['final_score'];

    $totalScore = $score1 + $score2;


    /*
     * If both users have no meaningful data
     */
    if ($totalScore <= 0) {

        $probability1 = 50;
        $probability2 = 50;

    } else {

        $probability1 =
            ($score1 / $totalScore) * 100;

        $probability2 =
            ($score2 / $totalScore) * 100;
    }


    return [

        'user1' => $user1,

        'user2' => $user2,

        'user1_probability' =>
            round($probability1, 2),

        'user2_probability' =>
            round($probability2, 2)
    ];
}