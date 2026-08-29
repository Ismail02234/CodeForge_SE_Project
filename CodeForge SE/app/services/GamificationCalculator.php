<?php

declare(strict_types=1);

final class GamificationCalculator
{
    private const XP_BY_DIFFICULTY = [
        'easy' => 10,
        'medium' => 25,
        'hard' => 50,
    ];

    public static function xpForDifficulty(string $difficulty): int
    {
        return self::XP_BY_DIFFICULTY[strtolower(trim($difficulty))] ?? 10;
    }

    public static function totalXp(array $difficulties): int
    {
        $xp = 0;
        foreach ($difficulties as $difficulty) {
            $xp += self::xpForDifficulty((string) $difficulty);
        }
        return $xp;
    }

    public static function streakStats(array $dates, ?string $today = null): array
    {
        $normalized = [];
        foreach ($dates as $date) {
            $value = substr((string) $date, 0, 10);
            $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $value, new DateTimeZone('UTC'));
            if ($parsed && $parsed->format('Y-m-d') === $value) {
                $normalized[$value] = true;
            }
        }

        $dates = array_keys($normalized);
        sort($dates, SORT_STRING);
        if ($dates === []) {
            return [
                'current_streak' => 0,
                'longest_streak' => 0,
                'last_solved_date' => null,
            ];
        }

        $longest = 1;
        $run = 1;
        for ($i = 1, $count = count($dates); $i < $count; $i++) {
            $previous = new DateTimeImmutable($dates[$i - 1], new DateTimeZone('UTC'));
            $expected = $previous->modify('+1 day')->format('Y-m-d');
            $run = $dates[$i] === $expected ? $run + 1 : 1;
            $longest = max($longest, $run);
        }

        $last = $dates[array_key_last($dates)];
        $tail = 1;
        for ($i = count($dates) - 1; $i > 0; $i--) {
            $previous = new DateTimeImmutable($dates[$i - 1], new DateTimeZone('UTC'));
            if ($previous->modify('+1 day')->format('Y-m-d') !== $dates[$i]) {
                break;
            }
            $tail++;
        }

        $today = $today ?: gmdate('Y-m-d');
        $todayDate = new DateTimeImmutable($today, new DateTimeZone('UTC'));
        $activeDates = [$todayDate->format('Y-m-d'), $todayDate->modify('-1 day')->format('Y-m-d')];
        $current = in_array($last, $activeDates, true) ? $tail : 0;

        return [
            'current_streak' => $current,
            'longest_streak' => $longest,
            'last_solved_date' => $last,
        ];
    }

    public static function levelProgress(int $xp, array $levels): array
    {
        if ($levels === []) {
            return [
                'current' => ['level' => 1, 'title' => 'Code Sprout', 'xp_required' => 0],
                'next' => null,
                'percent' => 100,
            ];
        }

        usort($levels, static fn (array $a, array $b): int => (int) $a['xp_required'] <=> (int) $b['xp_required']);
        $current = $levels[0];
        $next = null;

        foreach ($levels as $level) {
            if ((int) $level['xp_required'] <= $xp) {
                $current = $level;
                continue;
            }
            $next = $level;
            break;
        }

        if ($next === null) {
            $percent = 100;
        } else {
            $range = max(1, (int) $next['xp_required'] - (int) $current['xp_required']);
            $within = max(0, $xp - (int) $current['xp_required']);
            $percent = (int) round(min(1, $within / $range) * 100);
        }

        return [
            'current' => $current,
            'next' => $next,
            'percent' => $percent,
        ];
    }
}
