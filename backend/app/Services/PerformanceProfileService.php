<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class PerformanceProfileService
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
         * Preserve the original Performance Profile scoring semantics while avoiding a
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
                ? PerformanceProfileCalculator::clamp(($row['accepted'] / $row['submissions']) * 100)
                : 0;

            $speed = $row['session_count'] > 0
                ? PerformanceProfileCalculator::clamp($row['speed_sum'] / $row['session_count'])
                : 0;

            $difficulty = $row['session_count'] > 0
                ? PerformanceProfileCalculator::clamp($row['difficulty_sum'] / $row['session_count'])
                : 0;

            $recency = PerformanceProfileCalculator::recencyScore($row['last_solved_at']);

            $topicScores[$topic] = [
                'score' => PerformanceProfileCalculator::topicScore($accuracy, $difficulty, $speed, $recency),
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
            ? PerformanceProfileCalculator::clamp(($accepted / $total) * 100)
            : 0;

        $speed = $globalSessionCount > 0
            ? PerformanceProfileCalculator::clamp($globalSpeedSum / $globalSessionCount)
            : 0;

        $challenge = $globalSessionCount > 0
            ? PerformanceProfileCalculator::clamp($globalDifficultySum / $globalSessionCount)
            : 0;

        $solvedTopics = count(
            array_filter($topicScores, fn (array $row) => $row['solved'] > 0)
        );

        $versatility = count($topicScores) > 0
            ? PerformanceProfileCalculator::clamp(($solvedTopics / count($topicScores)) * 100)
            : 0;

        $consistency = PerformanceProfileCalculator::consistency($recent);

        $attemptedScores = array_column(
            array_filter($topicScores, fn (array $row) => $row['attempted'] > 0),
            'score'
        );

        $averageTopicScore = $attemptedScores
            ? array_sum($attemptedScores) / count($attemptedScores)
            : 0;

        $problemSolving = PerformanceProfileCalculator::clamp(
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

        $learningStats = DB::table('learning_progress as lp')
            ->join('learning_modules as lm', 'lm.id', '=', 'lp.learning_module_id')
            ->where('lp.user_id', $userId)
            ->selectRaw('
                COUNT(lp.learning_module_id) as modules_started,
                SUM(CASE WHEN lp.learn_completed = 1 AND lp.play_completed = 1 AND lp.prove_completed = 1 THEN 1 ELSE 0 END) as mastered,
                SUM(lp.mastery_score) as total_mastery_score,
                SUM(
                    (SELECT COALESCE(SUM(xp_reward), 0) FROM learning_steps WHERE learning_module_id = lp.learning_module_id AND correct_answer IS NOT NULL) +
                    (SELECT COALESCE(SUM(xp_reward), 0) FROM play_challenges WHERE learning_module_id = lp.learning_module_id) +
                     (SELECT 0 FROM learning_problems lp2 WHERE lp2.learning_module_id = lp.learning_module_id LIMIT 1)
                ) as total_possible_score,
                AVG(lp.concept_mastery) as avg_concept_mastery,
                AVG(lp.learn_accuracy) as avg_learn_accuracy,
                AVG(lp.play_accuracy) as avg_play_accuracy,
                AVG(lp.prove_accuracy) as avg_prove_accuracy,
                SUM(lp.learn_attempts) as total_learn_attempts,
                SUM(lp.play_attempts) as total_play_attempts,
                SUM(lp.prove_attempts) as total_prove_attempts
            ')
            ->first();

        $learningMasteryPct = 0;
        if ($learningStats && $learningStats->total_possible_score > 0) {
            $learningMasteryPct = (int) round(($learningStats->total_mastery_score / $learningStats->total_possible_score) * 100);
        }

        $learningByTopic = DB::table('learning_progress as lp')
            ->join('learning_modules as lm', 'lm.id', '=', 'lp.learning_module_id')
            ->where('lp.user_id', $userId)
            ->whereNotNull('lm.topic')
            ->groupBy('lm.topic')
            ->selectRaw('
                lm.topic,
                COUNT(lp.learning_module_id) as modules_started,
                SUM(CASE WHEN lp.learn_completed = 1 AND lp.play_completed = 1 AND lp.prove_completed = 1 THEN 1 ELSE 0 END) as mastered,
                AVG(lp.concept_mastery) as avg_concept_mastery,
                AVG(lp.learn_accuracy) as avg_learn_accuracy,
                AVG(lp.play_accuracy) as avg_play_accuracy,
                AVG(lp.prove_accuracy) as avg_prove_accuracy
            ')
            ->get()
            ->map(fn ($row) => [
                'topic' => (string) $row->topic,
                'modules_started' => (int) $row->modules_started,
                'mastered' => (int) $row->mastered,
                'avg_concept_mastery' => (int) ($row->avg_concept_mastery ?? 0),
                'avg_learn_accuracy' => (int) ($row->avg_learn_accuracy ?? 0),
                'avg_play_accuracy' => (int) ($row->avg_play_accuracy ?? 0),
                'avg_prove_accuracy' => (int) ($row->avg_prove_accuracy ?? 0),
            ])
            ->all();

        return [
            'user' => $user->toArray(),
            'dimensions' => $dimensions,
            'dimension_labels' => $labels,
            'overall' => PerformanceProfileCalculator::clamp(array_sum($dimensions) / count($dimensions)),
            'archetype' => PerformanceProfileCalculator::archetype($dimensions),
            'topics' => $topicScores,
            'strengths' => array_map(fn (string $key) => $labels[$key], $strengths),
            'growth_areas' => array_map(fn (string $key) => $labels[$key], $growthAreas),
            'stats' => [
                'total_submissions' => $total,
                'accepted_submissions' => $accepted,
                'solved_problems' => $solvedProblems,
                'solved_topics' => $solvedTopics,
            ],
            'learning' => [
                'modules_started' => (int) ($learningStats->modules_started ?? 0),
                'modules_completed' => (int) ($learningStats->mastered ?? 0),
                'overall_mastery_pct' => max(0, min(100, $learningMasteryPct)),
                'avg_concept_mastery' => (int) ($learningStats->avg_concept_mastery ?? 0),
                'avg_learn_accuracy' => (int) ($learningStats->avg_learn_accuracy ?? 0),
                'avg_play_accuracy' => (int) ($learningStats->avg_play_accuracy ?? 0),
                'avg_prove_accuracy' => (int) ($learningStats->avg_prove_accuracy ?? 0),
                'total_learn_attempts' => (int) ($learningStats->total_learn_attempts ?? 0),
                'total_play_attempts' => (int) ($learningStats->total_play_attempts ?? 0),
                'total_prove_attempts' => (int) ($learningStats->total_prove_attempts ?? 0),
            ],
            'learning_by_topic' => $learningByTopic,
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
