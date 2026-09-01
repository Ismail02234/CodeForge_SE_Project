<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class CodeDnaService
{
    public function calculate(string $userId): array
    {
        $user = User::query()
            ->select('id', 'username', 'rating', 'university', 'rank')
            ->find($userId);

        if (! $user) {
            throw new RuntimeException('User not found.');
        }

        $topics = DB::table('problems')
            ->whereNotNull('topic')
            ->distinct()
            ->orderBy('topic')
            ->pluck('topic')
            ->map(fn ($topic) => (string) $topic)
            ->all();

        $data = [];

        foreach ($topics as $topic) {
            $data[$topic] = $this->blank();
        }

        /*
         * Submission statistics stay in MySQL. The result set is bounded by
         * the number of topics instead of the number of submissions.
         */
        $summary = DB::table('submissions as s')
            ->join('problems as p', 'p.id', '=', 's.problem_id')
            ->where('s.user_id', $userId)
            ->whereNotNull('p.topic')
            ->groupBy('p.topic')
            ->selectRaw(
                "p.topic,
                 COUNT(*) AS submissions,
                 SUM(CASE WHEN UPPER(s.verdict) = 'AC' THEN 1 ELSE 0 END) AS accepted,
                 COUNT(DISTINCT s.problem_id) AS attempted,
                 COUNT(DISTINCT CASE WHEN UPPER(s.verdict) = 'AC' THEN s.problem_id END) AS solved,
                 MAX(CASE WHEN UPPER(s.verdict) = 'AC' THEN s.submitted_at END) AS last_solved_at"
            )
            ->get();

        foreach ($summary as $row) {
            $topic = (string) $row->topic;
            $data[$topic] ??= $this->blank();

            $data[$topic]['submissions'] = (int) $row->submissions;
            $data[$topic]['accepted'] = (int) $row->accepted;
            $data[$topic]['attempted'] = (int) $row->attempted;
            $data[$topic]['solved'] = (int) $row->solved;
            $data[$topic]['last_solved_at'] = $row->last_solved_at;
        }

        /*
         * Preserve the original Code DNA scoring semantics while avoiding a
         * full problem_sessions -> PHP transfer. We aggregate the exact
         * per-session rounded speed and difficulty scores inside MySQL.
         */
        $sessionSummary = DB::table('problem_sessions as ps')
            ->join('problems as p', 'p.id', '=', 'ps.problem_id')
            ->where('ps.user_id', $userId)
            ->where('ps.status', 'solved')
            ->whereNotNull('ps.solve_time_seconds')
            ->where('ps.solve_time_seconds', '>', 0)
            ->whereNotNull('p.topic')
            ->groupBy('p.topic')
            ->selectRaw(
                "p.topic,
                 COUNT(*) AS session_count,
                 SUM(
                    ROUND(
                        LEAST(
                            100,
                            GREATEST(
                                0,
                                (
                                    CASE LOWER(p.difficulty)
                                        WHEN 'easy' THEN 180.0
                                        WHEN 'medium' THEN 360.0
                                        WHEN 'hard' THEN 600.0
                                        ELSE 300.0
                                    END
                                    / NULLIF(ps.solve_time_seconds, 0)
                                ) * 82.0
                            )
                        )
                    )
                 ) AS speed_sum,
                 SUM(
                    CASE LOWER(p.difficulty)
                        WHEN 'easy' THEN 55
                        WHEN 'medium' THEN 78
                        WHEN 'hard' THEN 100
                        ELSE 50
                    END
                 ) AS difficulty_sum"
            )
            ->get();

        $globalSpeedSum = 0.0;
        $globalDifficultySum = 0.0;
        $globalSessionCount = 0;

        foreach ($sessionSummary as $row) {
            $topic = (string) $row->topic;
            $data[$topic] ??= $this->blank();

            $count = (int) $row->session_count;
            $speedSum = (float) ($row->speed_sum ?? 0);
            $difficultySum = (float) ($row->difficulty_sum ?? 0);

            $data[$topic]['session_count'] = $count;
            $data[$topic]['speed_sum'] = $speedSum;
            $data[$topic]['difficulty_sum'] = $difficultySum;

            $globalSessionCount += $count;
            $globalSpeedSum += $speedSum;
            $globalDifficultySum += $difficultySum;
        }

        $recent = DB::table('submissions')
            ->where('user_id', $userId)
            ->orderByDesc('submitted_at')
            ->orderByDesc('id')
            ->limit(10)
            ->pluck('verdict')
            ->map(fn ($verdict) => strtoupper((string) $verdict) === 'AC' ? 1 : 0)
            ->all();

        $topicScores = [];
        $total = 0;
        $accepted = 0;
        $solvedProblems = 0;

        foreach ($data as $topic => $row) {
            $total += (int) $row['submissions'];
            $accepted += (int) $row['accepted'];
            $solvedProblems += (int) $row['solved'];

            $accuracy = $row['submissions'] > 0
                ? CodeDnaCalculator::clamp(($row['accepted'] / $row['submissions']) * 100)
                : 0;

            $speed = $row['session_count'] > 0
                ? CodeDnaCalculator::clamp($row['speed_sum'] / $row['session_count'])
                : 0;

            $difficulty = $row['session_count'] > 0
                ? CodeDnaCalculator::clamp($row['difficulty_sum'] / $row['session_count'])
                : 0;

            $recency = CodeDnaCalculator::recencyScore($row['last_solved_at']);

            $topicScores[$topic] = [
                'score' => CodeDnaCalculator::topicScore($accuracy, $difficulty, $speed, $recency),
                'accuracy' => $accuracy,
                'speed' => $speed,
                'difficulty' => $difficulty,
                'recency' => $recency,
                'attempted' => (int) $row['attempted'],
                'solved' => (int) $row['solved'],
            ];
        }

        uasort(
            $topicScores,
            fn (array $left, array $right) => $right['score'] <=> $left['score']
        );

        $accuracy = $total > 0
            ? CodeDnaCalculator::clamp(($accepted / $total) * 100)
            : 0;

        $speed = $globalSessionCount > 0
            ? CodeDnaCalculator::clamp($globalSpeedSum / $globalSessionCount)
            : 0;

        $challenge = $globalSessionCount > 0
            ? CodeDnaCalculator::clamp($globalDifficultySum / $globalSessionCount)
            : 0;

        $solvedTopics = count(
            array_filter($topicScores, fn (array $row) => $row['solved'] > 0)
        );

        $versatility = count($topicScores) > 0
            ? CodeDnaCalculator::clamp(($solvedTopics / count($topicScores)) * 100)
            : 0;

        $consistency = CodeDnaCalculator::consistency($recent);

        $attemptedScores = array_column(
            array_filter($topicScores, fn (array $row) => $row['attempted'] > 0),
            'score'
        );

        $averageTopicScore = $attemptedScores
            ? array_sum($attemptedScores) / count($attemptedScores)
            : 0;

        $problemSolving = CodeDnaCalculator::clamp(
            $averageTopicScore * 0.45 +
            $accuracy * 0.30 +
            $challenge * 0.25
        );

        $dimensions = [
            'problem_solving' => $problemSolving,
            'accuracy' => $accuracy,
            'speed' => $speed,
            'consistency' => $consistency,
            'versatility' => $versatility,
            'challenge' => $challenge,
        ];

        $labels = [
            'problem_solving' => 'Problem Solving',
            'accuracy' => 'Accuracy',
            'speed' => 'Speed',
            'consistency' => 'Consistency',
            'versatility' => 'Versatility',
            'challenge' => 'Challenge Handling',
        ];

        $descending = $dimensions;
        arsort($descending);
        $strengths = array_slice(array_keys($descending), 0, 2);

        $ascending = $dimensions;
        asort($ascending);
        $growthAreas = array_slice(array_keys($ascending), 0, 2);

        return [
            'user' => $user->toArray(),
            'dimensions' => $dimensions,
            'dimension_labels' => $labels,
            'overall' => CodeDnaCalculator::clamp(array_sum($dimensions) / count($dimensions)),
            'archetype' => CodeDnaCalculator::archetype($dimensions),
            'topics' => $topicScores,
            'strengths' => array_map(fn (string $key) => $labels[$key], $strengths),
            'growth_areas' => array_map(fn (string $key) => $labels[$key], $growthAreas),
            'stats' => [
                'total_submissions' => $total,
                'accepted_submissions' => $accepted,
                'solved_problems' => $solvedProblems,
                'solved_topics' => $solvedTopics,
            ],
        ];
    }

    private function blank(): array
    {
        return [
            'submissions' => 0,
            'accepted' => 0,
            'attempted' => 0,
            'solved' => 0,
            'last_solved_at' => null,
            'session_count' => 0,
            'speed_sum' => 0.0,
            'difficulty_sum' => 0.0,
        ];
    }
}
