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
        $userStmt = $this->pdo->prepare('SELECT id, username, rating, university, `rank` FROM users WHERE id = :id');
        $userStmt->execute(['id' => $userId]);
        $user = $userStmt->fetch();
        if (!$user) {
            throw new RuntimeException('User not found.');
        }

        $topics = $this->pdo->query('SELECT DISTINCT topic FROM problems ORDER BY topic')->fetchAll(PDO::FETCH_COLUMN);

        $stmt = $this->pdo->prepare(
            'SELECT s.id, s.session_id, s.problem_id, s.verdict, s.submitted_at,
                    p.topic, p.difficulty,
                    ps.solve_time_seconds, ps.status AS session_status
             FROM submissions s
             INNER JOIN problems p ON p.id = s.problem_id
             LEFT JOIN problem_sessions ps ON ps.id = s.session_id
             WHERE s.user_id = :uid
             ORDER BY s.submitted_at ASC, s.id ASC'
        );
        $stmt->execute(['uid' => $userId]);
        $submissions = $stmt->fetchAll();

        $topicData = [];
        foreach ($topics as $topic) {
            $topicData[$topic] = [
                'submissions' => 0,
                'accepted' => 0,
                'attempted_problems' => [],
                'solved_problems' => [],
                'solved_sessions' => [],
                'last_solved_at' => null,
            ];
        }

        $total = count($submissions);
        $accepted = 0;
        $recentOutcomes = [];
        $solvedSessionIds = [];
        $speedScores = [];
        $difficultyScores = [];

        foreach ($submissions as $submission) {
            $topic = (string) $submission['topic'];
            if (!isset($topicData[$topic])) {
                $topicData[$topic] = [
                    'submissions' => 0,
                    'accepted' => 0,
                    'attempted_problems' => [],
                    'solved_problems' => [],
                    'solved_sessions' => [],
                    'last_solved_at' => null,
                ];
            }

            $topicData[$topic]['submissions']++;
            $topicData[$topic]['attempted_problems'][(string) $submission['problem_id']] = true;

            $isAccepted = strtoupper((string) $submission['verdict']) === 'AC';
            if ($isAccepted) {
                $accepted++;
                $topicData[$topic]['accepted']++;
                $topicData[$topic]['solved_problems'][(string) $submission['problem_id']] = true;
                $topicData[$topic]['last_solved_at'] = (string) $submission['submitted_at'];

                $sessionId = (string) ($submission['session_id'] ?? '');
                if ($sessionId !== '' && !isset($solvedSessionIds[$sessionId])) {
                    $solvedSessionIds[$sessionId] = true;
                    $speed = CodeDnaCalculator::speedScore(
                        (string) $submission['difficulty'],
                        $submission['solve_time_seconds'] !== null ? (int) $submission['solve_time_seconds'] : null
                    );
                    $difficulty = CodeDnaCalculator::difficultyScore((string) $submission['difficulty']);
                    $speedScores[] = $speed;
                    $difficultyScores[] = $difficulty;
                    $topicData[$topic]['solved_sessions'][$sessionId] = [
                        'speed' => $speed,
                        'difficulty' => $difficulty,
                    ];
                }
            }
        }

        foreach (array_slice(array_reverse($submissions), 0, 10) as $recent) {
            $recentOutcomes[] = strtoupper((string) $recent['verdict']) === 'AC' ? 1 : 0;
        }

        $topicScores = [];
        foreach ($topicData as $topic => $data) {
            $topicAccuracy = $data['submissions'] > 0
                ? CodeDnaCalculator::clamp(($data['accepted'] / $data['submissions']) * 100)
                : 0;

            $topicSpeedValues = array_column($data['solved_sessions'], 'speed');
            $topicDifficultyValues = array_column($data['solved_sessions'], 'difficulty');
            $topicSpeed = $topicSpeedValues ? CodeDnaCalculator::clamp(array_sum($topicSpeedValues) / count($topicSpeedValues)) : 0;
            $topicDifficulty = $topicDifficultyValues ? CodeDnaCalculator::clamp(array_sum($topicDifficultyValues) / count($topicDifficultyValues)) : 0;
            $topicRecency = CodeDnaCalculator::recencyScore($data['last_solved_at']);

            $topicScores[$topic] = [
                'score' => CodeDnaCalculator::topicScore($topicAccuracy, $topicDifficulty, $topicSpeed, $topicRecency),
                'accuracy' => $topicAccuracy,
                'speed' => $topicSpeed,
                'difficulty' => $topicDifficulty,
                'recency' => $topicRecency,
                'attempted' => count($data['attempted_problems']),
                'solved' => count($data['solved_problems']),
            ];
        }

        uasort($topicScores, static fn (array $a, array $b): int => $b['score'] <=> $a['score']);

        $accuracy = $total > 0 ? CodeDnaCalculator::clamp(($accepted / $total) * 100) : 0;
        $speed = $speedScores ? CodeDnaCalculator::clamp(array_sum($speedScores) / count($speedScores)) : 0;
        $challenge = $difficultyScores ? CodeDnaCalculator::clamp(array_sum($difficultyScores) / count($difficultyScores)) : 0;
        $solvedTopics = count(array_filter($topicScores, static fn (array $row): bool => $row['solved'] > 0));
        $versatility = count($topicScores) > 0 ? CodeDnaCalculator::clamp(($solvedTopics / count($topicScores)) * 100) : 0;
        $consistency = CodeDnaCalculator::consistency($recentOutcomes);
        $attemptedScores = array_map(
            static fn (array $row): int => (int) $row['score'],
            array_filter($topicScores, static fn (array $row): bool => $row['attempted'] > 0)
        );
        $avgTopic = $attemptedScores ? array_sum($attemptedScores) / count($attemptedScores) : 0;
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
                'solved_problems' => count(array_unique(array_map(
                    static fn (array $row): string => (string) $row['problem_id'],
                    array_filter($submissions, static fn (array $row): bool => strtoupper((string) $row['verdict']) === 'AC')
                ))),
                'solved_topics' => $solvedTopics,
            ],
        ];
    }
}
