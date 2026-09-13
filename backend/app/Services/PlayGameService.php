<?php

namespace App\Services;

class PlayGameService
{
    public function validate(string $type, string $action, object $config): int
    {
        $score = 0;

        if ($type === 'midpoint_master') {
            $score = $this->validateMidpointMaster($action, $config);
        } elseif ($type === 'trace_race') {
            $score = $this->validateTraceRace($action, $config);
        }

        return max(0, $score);
    }

    private function validateMidpointMaster(string $action, object $config): int
    {
        $rounds = json_decode($action, true) ?: [];

        $totalScore = 0;
        $roundConfigs = $config->rounds ?? [];

        foreach ($roundConfigs as $i => $round) {
            $expectedMid = (int) ($round['expected_mid'] ?? -1);
            $userAnswer = isset($rounds[$i]) ? (int) $rounds[$i] : null;

            if ($userAnswer === null) {
                continue;
            }

            if ($userAnswer === $expectedMid) {
                $totalScore += (int) ($config->correct_score ?? 100);
            } else {
                $totalScore += (int) ($config->incorrect_score ?? -25);
            }
        }

        return max(0, $totalScore);
    }

    private function validateTraceRace(string $action, object $config): int
    {
        $decoded = json_decode($action, true) ?: [];
        $moves = $decoded['moves'] ?? [];
        $found = (bool) ($decoded['found'] ?? false);

        $arr = $config->array ?? [];
        $target = $config->target ?? 0;
        $baseScore = (int) ($config->base_score ?? 1000);

        if (empty($arr) || !$found) {
            return 0;
        }

        $correctSequence = $this->simulateBinarySearch($arr, $target);

        $score = $baseScore;
        $penaltyPerMistake = (int) ($config->penalty_incorrect_mid ?? 100);
        $penaltyPerExtraMove = (int) ($config->penalty_extra_move ?? 50);
        $penaltyWrongDirection = (int) ($config->penalty_wrong_direction ?? 100);

        $simLow = 0;
        $simHigh = count($arr) - 1;
        $moveIdx = 0;

        while ($simLow <= $simHigh && $moveIdx < count($moves)) {
            $simMid = (int) floor(($simLow + $simHigh) / 2);
            $userMove = $moves[$moveIdx] ?? null;

            if (!is_array($userMove)) {
                $moveIdx++;
                continue;
            }

            $userMid = isset($userMove['mid']) ? (int) $userMove['mid'] : -1;

            if ($userMid !== $simMid) {
                $score -= $penaltyPerMistake;
            }

            $correctDir = $target > $arr[$simMid] ? 'right' : 'left';
            $userDir = strtolower((string) ($userMove['dir'] ?? ''));

            if ($userDir !== '' && $userDir !== $correctDir) {
                $score -= $penaltyWrongDirection;
            }

            if ($arr[$simMid] === $target) {
                break;
            }

            if ($correctDir === 'left') {
                $simHigh = $simMid - 1;
            } else {
                $simLow = $simMid + 1;
            }

            $moveIdx++;
        }

        $minMoves = count($correctSequence);
        $userMovesUsed = count($moves);
        $extraMoves = max(0, $userMovesUsed - $minMoves);
        $score -= $extraMoves * $penaltyPerExtraMove;

        return max(0, $score);
    }

    private function simulateBinarySearch(array $arr, int $target): array
    {
        $sequence = [];
        $low = 0;
        $high = count($arr) - 1;

        while ($low <= $high) {
            $mid = (int) floor(($low + $high) / 2);
            if ($arr[$mid] === $target) {
                $sequence[] = ['mid' => $mid, 'dir' => 'found'];
                break;
            }
            $dir = $target > $arr[$mid] ? 'right' : 'left';
            $sequence[] = ['mid' => $mid, 'dir' => $dir];

            if ($dir === 'left') {
                $high = $mid - 1;
            } else {
                $low = $mid + 1;
            }
        }

        return $sequence;
    }

    public function generateMidpointMasterRounds(object $config): array
    {
        $rounds = $config->rounds ?? [];
        $results = [];

        foreach ($rounds as $round) {
            $low = (int) ($round['low'] ?? 0);
            $high = (int) ($round['high'] ?? 0);
            $expectedMid = $high >= $low ? (int) floor(($low + $high) / 2) : 0;

            $results[] = [
                'low' => $low,
                'high' => $high,
                'expected_mid' => $expectedMid,
                'calculation' => "floor(($low + $high) / 2) = floor(" . ($low + $high) . " / 2) = $expectedMid",
            ];
        }

        return $results;
    }

    public function simulateTraceRace(array $arr, int $target): array
    {
        $steps = [];
        $low = 0;
        $high = count($arr) - 1;

        while ($low <= $high) {
            $mid = (int) floor(($low + $high) / 2);
            $midVal = $arr[$mid];

            if ($midVal === $target) {
                $steps[] = [
                    'mid' => $mid,
                    'midVal' => $midVal,
                    'low' => $low,
                    'high' => $high,
                    'dir' => 'found',
                    'isTarget' => true,
                ];
                break;
            }

            $dir = $target > $midVal ? 'right' : 'left';
            $steps[] = [
                'mid' => $mid,
                'midVal' => $midVal,
                'low' => $low,
                'high' => $high,
                'dir' => $dir,
                'isTarget' => false,
            ];

            if ($dir === 'left') {
                $high = $mid - 1;
            } else {
                $low = $mid + 1;
            }
        }

        return $steps;
    }
}
