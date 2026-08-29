<?php

declare(strict_types=1);

require_once __DIR__ . '/CodeDnaCalculator.php';

final class CodeDnaService
{
    public function __construct(private PDO $pdo)
    {
    }

    public function calculate(string $userId): array
    {
        $userStmt = $this->pdo->prepare(
            'SELECT id, username, rating, university, `rank`
             FROM users WHERE id = :id LIMIT 1'
        );
        $userStmt->execute(['id' => $userId]);
        $user = $userStmt->fetch();

        if (!$user) {
            throw new RuntimeException('User not found.');
        }

        $topics = $this->pdo
            ->query('SELECT DISTINCT topic FROM problems ORDER BY topic')
            ->fetchAll(PDO::FETCH_COLUMN);

        $topicData = [];
        foreach ($topics as $topic) {
            $topicData[(string) $topic] = [
                'submissions' => 0,
                'accepted' => 0,
                'attempted' => 0,
                'solved' => 0,
                'last_solved_at' => null,
                'speed_scores' => [],
                'difficulty_scores' => [],
            ];
        }

        // Aggregate submission statistics in SQL rather than loading the user's
        // complete submission history into PHP memory. This matters once seeded
        // elite accounts accumulate hundreds or thousands of attempts.
        $topicStmt = $this->pdo->prepare(
            "SELECT p.topic,
                    COUNT(*) AS submissions,
                    SUM(CASE WHEN s.verdict = 'AC' THEN 1 ELSE 0 END) AS accepted,
                    COUNT(DISTINCT s.problem_id) AS attempted,
                    COUNT(DISTINCT CASE WHEN s.verdict = 'AC' THEN s.problem_id END) AS solved,
                    MAX(CASE WHEN s.verdict = 'AC' THEN s.submitted_at END) AS last_solved_at
             FROM submissions s
             INNER JOIN problems p ON p.id = s.problem_id
             WHERE s.user_id = :uid
             GROUP BY p.topic"
        );
        $topicStmt->execute(['uid' => $userId]);

        foreach ($topicStmt->fetchAll() as $row) {
            $topic = (string) $row['topic'];
            $topicData[$topic] ??= [
                'submissions' => 0,
                'accepted' => 0,
                'attempted' => 0,
                'solved' => 0,
                'last_solved_at' => null,
                'speed_scores' => [],
                'difficulty_scores' => [],
            ];

            $topicData[$topic]['submissions'] = (int) $row['submissions'];
            $topicData[$topic]['accepted'] = (int) $row['accepted'];
            $topicData[$topic]['attempted'] = (int) $row['attempted'];
            $topicData[$topic]['solved'] = (int) $row['solved'];
            $topicData[$topic]['last_solved_at'] = $row['last_solved_at'];
        }

        // One row per completed session is enough for speed/difficulty metrics.
        $sessionStmt = $this->pdo->prepare(
            "SELECT p.topic, p.difficulty, ps.solve_time_seconds
             FROM problem_sessions ps
             INNER JOIN problems p ON p.id = ps.problem_id
             WHERE ps.user_id = :uid
               AND ps.status = 'solved'
               AND ps.solve_time_seconds IS NOT NULL"
        );
        $sessionStmt->execute(['uid' => $userId]);

        $speedScores = [];
        $difficultyScores = [];

        foreach ($sessionStmt->fetchAll() as $row) {
            $topic = (string) $row['topic'];
            $speed = CodeDnaCalculator::speedScore(
                (string) $row['difficulty'],
                (int) $row['solve_time_seconds']
            );
            $difficulty = CodeDnaCalculator::difficultyScore((string) $row['difficulty']);

            $speedScores[] = $speed;
            $difficultyScores[] = $difficulty;

            if (!isset($topicData[$topic])) {
                $topicData[$topic] = [
                    'submissions' => 0,
                    'accepted' => 0,
                    'attempted' => 0,
                    'solved' => 0,
                    'last_solved_at' => null,
                    'speed_scores' => [],
                    'difficulty_scores' => [],
                ];
            }

            $topicData[$topic]['speed_scores'][] = $speed;
            $topicData[$topic]['difficulty_scores'][] = $difficulty;
        }

        $recentStmt = $this->pdo->prepare(
            'SELECT verdict
             FROM submissions
             WHERE user_id = :uid
             ORDER BY submitted_at DESC, id DESC
             LIMIT 10'
        );
        $recentStmt->execute(['uid' => $userId]);
        $recentOutcomes = array_map(
            static fn (array $row): int => strtoupper((string) $row['verdict']) === 'AC' ? 1 : 0,
            $recentStmt->fetchAll()
        );

        $topicScores = [];
        $total = 0;
        $accepted = 0;
        $solvedProblems = 0;

        foreach ($topicData as $topic => $data) {
            $total += (int) $data['submissions'];
            $accepted += (int) $data['accepted'];
            $solvedProblems += (int) $data['solved'];

            $topicAccuracy = $data['submissions'] > 0
                ? CodeDnaCalculator::clamp(($data['accepted'] / $data['submissions']) * 100)
                : 0;

            $topicSpeed = $data['speed_scores'] !== []
                ? CodeDnaCalculator::clamp(array_sum($data['speed_scores']) / count($data['speed_scores']))
                : 0;

            $topicDifficulty = $data['difficulty_scores'] !== []
                ? CodeDnaCalculator::clamp(array_sum($data['difficulty_scores']) / count($data['difficulty_scores']))
                : 0;

            $topicRecency = CodeDnaCalculator::recencyScore(
                is_string($data['last_solved_at']) ? $data['last_solved_at'] : null
            );

            $topicScores[$topic] = [
                'score' => CodeDnaCalculator::topicScore($topicAccuracy, $topicDifficulty, $topicSpeed, $topicRecency),
                'accuracy' => $topicAccuracy,
                'speed' => $topicSpeed,
                'difficulty' => $topicDifficulty,
                'recency' => $topicRecency,
                'attempted' => (int) $data['attempted'],
                'solved' => (int) $data['solved'],
            ];
        }

        uasort($topicScores, static fn (array $a, array $b): int => $b['score'] <=> $a['score']);

        $accuracy = $total > 0 ? CodeDnaCalculator::clamp(($accepted / $total) * 100) : 0;
        $speed = $speedScores !== []
            ? CodeDnaCalculator::clamp(array_sum($speedScores) / count($speedScores))
            : 0;
        $challenge = $difficultyScores !== []
            ? CodeDnaCalculator::clamp(array_sum($difficultyScores) / count($difficultyScores))
            : 0;
        $solvedTopics = count(array_filter($topicScores, static fn (array $row): bool => $row['solved'] > 0));
        $versatility = $topicScores !== []
            ? CodeDnaCalculator::clamp(($solvedTopics / count($topicScores)) * 100)
            : 0;
        $consistency = CodeDnaCalculator::consistency($recentOutcomes);

        $attemptedScores = array_map(
            static fn (array $row): int => (int) $row['score'],
            array_filter($topicScores, static fn (array $row): bool => $row['attempted'] > 0)
        );
        $avgTopic = $attemptedScores !== [] ? array_sum($attemptedScores) / count($attemptedScores) : 0;
        $problemSolving = CodeDnaCalculator::clamp(($avgTopic * 0.45) + ($accuracy * 0.30) + ($challenge * 0.25));

        $dimensions = [
            'problem_solving' => $problemSolving,
            'accuracy' => $accuracy,
            'speed' => $speed,
            'consistency' => $consistency,
            'versatility' => $versatility,
            'challenge' => $challenge,
        ];

        $overall = CodeDnaCalculator::clamp(array_sum($dimensions) / max(1, count($dimensions)));
        $archetype = CodeDnaCalculator::archetype($dimensions);

        $dimensionLabels = [
            'problem_solving' => 'Problem Solving',
            'accuracy' => 'Accuracy',
            'speed' => 'Speed',
            'consistency' => 'Consistency',
            'versatility' => 'Versatility',
            'challenge' => 'Challenge Handling',
        ];

        $sortedDimensions = $dimensions;
        arsort($sortedDimensions);
        $strengthKeys = array_slice(array_keys($sortedDimensions), 0, 2);
        asort($sortedDimensions);
        $weaknessKeys = array_slice(array_keys($sortedDimensions), 0, 2);

        return [
            'user' => $user,
            'dimensions' => $dimensions,
            'dimension_labels' => $dimensionLabels,
            'overall' => $overall,
            'archetype' => $archetype,
            'topics' => $topicScores,
            'strengths' => array_map(static fn (string $key): string => $dimensionLabels[$key], $strengthKeys),
            'growth_areas' => array_map(static fn (string $key): string => $dimensionLabels[$key], $weaknessKeys),
            'stats' => [
                'total_submissions' => $total,
                'accepted_submissions' => $accepted,
                'solved_problems' => $solvedProblems,
                'solved_topics' => $solvedTopics,
            ],
        ];
    }
}
