<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

final class ProblemRecommendationService
{
    private const PRIOR_WEIGHT = 3.0;

    private const GLOBAL_ACCURACY = 0.50;

    private const FRICTION_PENALTY = 0.50;

    private const MIN_ATTEMPTS = 2;

    public function forUser(string $userId, int $limit = 3): array
    {
        $limit = max(1, min(6, $limit));
        $stats = $this->topicStats($userId);

        $weakest = null;
        $maxWeakness = -1.0;

        foreach ($stats as &$stat) {
            $attempted = (int) $stat['attempted'];
            $solved = (int) $stat['solved'];
            $globalAccuracy = self::GLOBAL_ACCURACY;

            $shrunkAccuracy = (
                $solved + (self::PRIOR_WEIGHT * $globalAccuracy)
            ) / ($attempted + self::PRIOR_WEIGHT);

            $weaknessScore =
                (0.70 * (1 - $shrunkAccuracy)) +
                (0.30 * self::FRICTION_PENALTY);

            $stat['global_accuracy'] = round($globalAccuracy * 100, 2);
            $stat['shrunk_accuracy'] = round($shrunkAccuracy * 100, 2);
            $stat['friction_penalty'] = self::FRICTION_PENALTY;
            $stat['weakness_score'] = round($weaknessScore, 4);
            $stat['eligible'] = $attempted >= self::MIN_ATTEMPTS;

            if (
                $stat['eligible'] &&
                $weaknessScore > $maxWeakness
            ) {
                $maxWeakness = $weaknessScore;
                $weakest = $stat;
            }
        }
        unset($stat);

        if (! $weakest) {
            $weakest = $this->fallbackTopic($stats);
        }

        $problems = $weakest
            ? $this->recommendProblems(
                $userId,
                (string) $weakest['topic'],
                $limit
            )
            : [];

        $topic = $weakest['topic'] ?? null;

        return [
            'weakest' => $weakest,
            'topic_stats' => $stats,
            'problems' => $problems,
            'explanation' => $topic
                ? "Your recent submission history shows that {$topic} is currently your weakest practice field. These unsolved problems were selected to target that weakness."
                : 'Keep solving problems to build enough history for personalized recommendations.',
            'algorithm' => [
                'prior_weight' => self::PRIOR_WEIGHT,
                'global_accuracy_percent' => self::GLOBAL_ACCURACY * 100,
                'friction_penalty' => self::FRICTION_PENALTY,
                'minimum_attempts' => self::MIN_ATTEMPTS,
            ],
        ];
    }

    private function topicStats(string $userId): array
    {
        return DB::table('problems as p')
            ->leftJoin('submissions as s', function ($join) use ($userId): void {
                $join->on('s.problem_id', '=', 'p.id')
                    ->where('s.user_id', $userId);
            })
            ->whereNotNull('p.topic')
            ->groupBy('p.topic')
            ->orderBy('p.topic')
            ->selectRaw(
                "p.topic,
                 COUNT(s.id) AS attempted,
                 SUM(
                    CASE
                        WHEN UPPER(COALESCE(s.verdict, '')) = 'AC' THEN 1
                        ELSE 0
                    END
                 ) AS solved"
            )
            ->get()
            ->map(fn ($row) => [
                'topic' => (string) $row->topic,
                'attempted' => (int) $row->attempted,
                'solved' => (int) $row->solved,
            ])
            ->all();
    }

    private function fallbackTopic(array $stats): ?array
    {
        if (! $stats) {
            return null;
        }

        foreach ($stats as $stat) {
            if (strtolower((string) $stat['topic']) === 'graphs') {
                return $stat + [
                    'global_accuracy' => 50.0,
                    'shrunk_accuracy' => null,
                    'friction_penalty' => self::FRICTION_PENALTY,
                    'weakness_score' => null,
                    'eligible' => false,
                    'fallback' => true,
                ];
            }
        }

        $fallback = $stats[0];

        return $fallback + [
            'global_accuracy' => 50.0,
            'shrunk_accuracy' => null,
            'friction_penalty' => self::FRICTION_PENALTY,
            'weakness_score' => null,
            'eligible' => false,
            'fallback' => true,
        ];
    }

    private function recommendProblems(
        string $userId,
        string $topic,
        int $limit
    ): array {
        return DB::table('problems as p')
            ->where('p.topic', $topic)
            ->whereNotExists(function ($query) use ($userId): void {
                $query->selectRaw('1')
                    ->from('submissions as s')
                    ->whereColumn('s.problem_id', 'p.id')
                    ->where('s.user_id', $userId)
                    ->whereRaw("UPPER(s.verdict) = 'AC'");
            })
            ->orderByRaw(
                "FIELD(p.difficulty, 'Easy', 'Medium', 'Hard')"
            )
            ->orderBy('p.title')
            ->limit($limit)
            ->get([
                'p.id',
                'p.title',
                'p.topic',
                'p.difficulty',
                'p.tags',
            ])
            ->map(fn ($row) => (array) $row)
            ->all();
    }
}
