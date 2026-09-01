<?php

namespace App\Services;

final class CodeDnaCalculator
{
    public static function clamp(float $value): int
    {
        return (int) round(max(0, min(100, $value)));
    }

    public static function speedScore(string $difficulty, ?int $seconds): int
    {
        if (! $seconds || $seconds <= 0) {
            return 0;
        }
        $target = match (strtolower($difficulty)) {
            'easy' => 180,'medium' => 360,'hard' => 600,default => 300
        };

        return self::clamp(($target / $seconds) * 82);
    }

    public static function difficultyScore(string $difficulty): int
    {
        return match (strtolower($difficulty)) {
            'easy' => 55,'medium' => 78,'hard' => 100,default => 50
        };
    }

    public static function recencyScore(?string $date, ?int $now = null): int
    {
        if (! $date || ($ts = strtotime($date)) === false) {
            return 0;
        }
        $days = max(0, (int) floor((($now ?? time()) - $ts) / 86400));

        return match (true) {
            $days <= 14 => 100,$days <= 30 => 90,$days <= 90 => 75,$days <= 180 => 60,default => 45
        };
    }

    public static function topicScore(int $accuracy, int $difficulty, int $speed, int $recency): int
    {
        return self::clamp($accuracy * .45 + $difficulty * .25 + $speed * .20 + $recency * .10);
    }

    public static function consistency(array $outcomes): int
    {
        if (! $outcomes) {
            return 0;
        }
        $count = count($outcomes);
        $mean = array_sum($outcomes) / $count;
        $variance = 0.0;
        foreach ($outcomes as $v) {
            $variance += ($v - $mean) ** 2;
        }
        $variance /= $count;
        $stability = max(0.0, 100 - (sqrt($variance) * 100));

        return self::clamp($mean * 100 * .70 + $stability * .30);
    }

    public static function archetype(array $d): array
    {
        $a = (int) ($d['accuracy'] ?? 0);
        $s = (int) ($d['speed'] ?? 0);
        $c = (int) ($d['challenge'] ?? 0);
        $v = (int) ($d['versatility'] ?? 0);
        $k = (int) ($d['consistency'] ?? 0);

        return match (true) {
            $s >= 80 && $a >= 72 => ['name' => 'Fast Strategist', 'tagline' => 'Solves decisively without sacrificing accuracy.'],
            $a >= 82 => ['name' => 'Precision Solver', 'tagline' => 'Wins through disciplined, low-error execution.'],
            $c >= 78 => ['name' => 'Challenge Hunter', 'tagline' => 'Performs best when the difficulty rises.'],
            $v >= 72 => ['name' => 'Versatile Explorer', 'tagline' => 'Builds strength across a broad algorithmic range.'],
            $k >= 75 => ['name' => 'Steady Climber', 'tagline' => 'Improves through repeatable, stable performance.'],
            default => ['name' => 'Developing Coder', 'tagline' => 'A growing profile with clear opportunities to specialize.'],
        };
    }
}
