<?php

declare(strict_types=1);

final class CodeDnaCalculator
{
    public static function clamp(float $value): int
    {
        return (int) round(max(0, min(100, $value)));
    }

    public static function speedScore(string $difficulty, ?int $solveSeconds): int
    {
        if (!$solveSeconds || $solveSeconds <= 0) {
            return 0;
        }

        $target = match (strtolower($difficulty)) {
            'easy' => 180,
            'medium' => 360,
            'hard' => 600,
            default => 300,
        };

        return self::clamp(($target / $solveSeconds) * 82);
    }

    public static function difficultyScore(string $difficulty): int
    {
        return match (strtolower($difficulty)) {
            'easy' => 55,
            'medium' => 78,
            'hard' => 100,
            default => 50,
        };
    }

    public static function recencyScore(?string $lastSolvedAt, ?int $nowTimestamp = null): int
    {
        if (!$lastSolvedAt) {
            return 0;
        }

        $nowTimestamp ??= time();
        $solved = strtotime($lastSolvedAt);
        if ($solved === false) {
            return 0;
        }

        $days = max(0, (int) floor(($nowTimestamp - $solved) / 86400));
        return match (true) {
            $days <= 14 => 100,
            $days <= 30 => 90,
            $days <= 90 => 75,
            $days <= 180 => 60,
            default => 45,
        };
    }

    public static function topicScore(int $accuracy, int $difficulty, int $speed, int $recency): int
    {
        return self::clamp(
            ($accuracy * 0.45) +
            ($difficulty * 0.25) +
            ($speed * 0.20) +
            ($recency * 0.10)
        );
    }

    // 1 means AC, 0 means any other verdict
    public static function consistency(array $recentOutcomes): int
    {
        if ($recentOutcomes === []) {
            return 0;
        }

        $count = count($recentOutcomes);
        $mean = array_sum($recentOutcomes) / $count;
        $variance = 0.0;
        foreach ($recentOutcomes as $outcome) {
            $variance += ($outcome - $mean) ** 2;
        }
        $variance /= $count;
        $stability = max(0.0, 100 - (sqrt($variance) * 100));
        return self::clamp(($mean * 100 * 0.70) + ($stability * 0.30));
    }

    public static function archetype(array $dimensions): array
    {
        $accuracy = (int) ($dimensions['accuracy'] ?? 0);
        $speed = (int) ($dimensions['speed'] ?? 0);
        $challenge = (int) ($dimensions['challenge'] ?? 0);
        $versatility = (int) ($dimensions['versatility'] ?? 0);
        $consistency = (int) ($dimensions['consistency'] ?? 0);

        if ($speed >= 80 && $accuracy >= 72) {
            return ['name' => 'Fast Strategist', 'tagline' => 'Solves decisively without sacrificing accuracy.'];
        }
        if ($accuracy >= 82) {
            return ['name' => 'Precision Solver', 'tagline' => 'Wins through disciplined, low-error execution.'];
        }
        if ($challenge >= 78) {
            return ['name' => 'Challenge Hunter', 'tagline' => 'Performs best when the difficulty rises.'];
        }
        if ($versatility >= 72) {
            return ['name' => 'Versatile Explorer', 'tagline' => 'Builds strength across a broad algorithmic range.'];
        }
        if ($consistency >= 75) {
            return ['name' => 'Steady Climber', 'tagline' => 'Improves through repeatable, stable performance.'];
        }

        return ['name' => 'Developing Coder', 'tagline' => 'A growing profile with clear opportunities to specialize.'];
    }
}
