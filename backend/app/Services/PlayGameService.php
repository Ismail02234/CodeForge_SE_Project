<?php

namespace App\Services;

class PlayGameService
{
    public function validate(string $type, string $action, object $config, ?int $xpReward = null): array
    {
        $maxScore = (int) ($xpReward ?? $config->xp_reward ?? 0);

        switch ($type) {
            case 'fill_blank':
                return $this->validateFillBlank($action, $config, $maxScore);
            case 'coding':
                return $this->validateCoding($action, $config, $maxScore);
            case 'trace':
                return $this->validateTrace($action, $config, $maxScore);
            case 'binary_search':
                return $this->validateBinarySearch($action, $config, $maxScore);
            case 'midpoint_master':
                return $this->validateMidpointMaster($action, $config, $maxScore);
            case 'trace_race':
                return $this->validateTraceRace($action, $config, $maxScore);
            case 'half_hunt':
                return $this->validateHalfHuntMove($action, $config, $maxScore);
            default:
                return [
                    'score' => 0,
                    'max_score' => $maxScore,
                    'completed' => false,
                    'accuracy' => 0,
                    'xp_reward' => 0,
                    'attempts' => 0,
                ];
        }
    }

    private function validateFillBlank(string $action, object $config, int $maxScore): array
    {
        $blanks = $config->blanks ?? [];
        $parts = preg_split('/\r?\n/', $action);
        $filled = array_values(array_filter(array_map('trim', $parts), fn ($p) => $p !== ''));

        $correct = true;
        foreach ($blanks as $i => $expected) {
            if (! isset($filled[$i]) || trim(strtolower($filled[$i])) !== trim(strtolower($expected))) {
                $correct = false;
                break;
            }
        }

        $score = $correct ? $maxScore : 0;

        return [
            'score' => $score,
            'max_score' => $maxScore,
            'completed' => $correct,
            'accuracy' => $correct ? 100 : 0,
            'xp_reward' => $score,
            'attempts' => 1,
        ];
    }

    private function validateCoding(string $action, object $config, int $maxScore): array
    {
        $steps = $config->steps ?? [];
        $userSteps = json_decode($action, true) ?: [];

        if (! is_array($userSteps) || count($userSteps) !== count($steps)) {
            return [
                'score' => 0,
                'max_score' => $maxScore,
                'completed' => false,
                'accuracy' => 0,
                'xp_reward' => 0,
                'attempts' => 1,
            ];
        }

        $correctCount = 0;
        $totalChecks = 0;
        foreach ($steps as $i => $expected) {
            $u = $userSteps[$i] ?? [];

            if (isset($expected['expected_mid']) && isset($u['mid'])) {
                $totalChecks++;
                if ((int) $u['mid'] === (int) $expected['expected_mid']) {
                    $correctCount++;
                }
            }

            if (isset($expected['expected_next']) && isset($u['next'])) {
                $totalChecks++;
                if (trim(strtolower((string) $u['next'])) === trim(strtolower((string) $expected['expected_next']))) {
                    $correctCount++;
                }
            }
        }

        $ratio = $totalChecks > 0 ? $correctCount / $totalChecks : 0;
        $score = (int) round($maxScore * $ratio);
        $accuracy = $totalChecks > 0 ? (int) round(($correctCount / $totalChecks) * 100) : 0;

        return [
            'score' => $score,
            'max_score' => $maxScore,
            'completed' => $score > 0,
            'accuracy' => $accuracy,
            'xp_reward' => $score,
            'attempts' => 1,
        ];
    }

    private function validateTrace(string $action, object $config, int $maxScore): array
    {
        $decoded = json_decode($action, true);
        $movesUsed = is_array($decoded) ? ($decoded['moves'] ?? null) : null;
        $found = is_array($decoded) ? ($decoded['found'] ?? false) : false;

        if ($movesUsed === null || ! is_numeric($movesUsed) || (int) $movesUsed < 0) {
            return [
                'score' => 0,
                'max_score' => $maxScore,
                'completed' => false,
                'accuracy' => 0,
                'xp_reward' => 0,
                'attempts' => 0,
            ];
        }

        $movesUsed = (int) $movesUsed;
        $maxMoves = (int) ($config->max_moves ?? 4);
        $scoring = $config->scoring ?? [];

        if (! $found) {
            $score = (int) ($scoring->more_than_two ?? 0);
        } elseif ($movesUsed <= $maxMoves) {
            $score = (int) ($scoring->perfect ?? $maxScore);
        } elseif ($movesUsed === $maxMoves + 1) {
            $score = (int) ($scoring->one_extra_move ?? 0);
        } elseif ($movesUsed === $maxMoves + 2) {
            $score = (int) ($scoring->two_extra_moves ?? 0);
        } else {
            $score = (int) ($scoring->more_than_two ?? 0);
        }

        $accuracy = 0;
        if ($found && $maxMoves > 0) {
            $accuracy = $movesUsed <= $maxMoves ? 100 : ($movesUsed === $maxMoves + 1 ? 75 : ($movesUsed === $maxMoves + 2 ? 50 : 25));
        } elseif ($found) {
            $accuracy = 100;
        }

        return [
            'score' => $score,
            'max_score' => $maxScore,
            'completed' => $found,
            'accuracy' => $accuracy,
            'xp_reward' => $score,
            'attempts' => $movesUsed,
        ];
    }

    private function validateBinarySearch(string $action, object $config, int $maxScore): array
    {
        $directions = json_decode($action, true) ?: [];
        if (! is_array($directions)) {
            $directions = [];
        }

        $arr = $config->array ?? [];
        $target = $config->target ?? 0;
        $maxMoves = (int) ($config->max_moves ?? 5);
        $scoring = $config->scoring ?? (object) [
            'correct_decision' => 15,
            'mistake' => -10,
            'found_bonus' => 15,
            'efficiency_bonus' => 5,
            'min_score' => 0,
        ];

        $optimalLow = 0;
        $optimalHigh = count($arr) - 1;
        $minMoves = 0;
        while ($optimalLow <= $optimalHigh && $minMoves < $maxMoves) {
            $optimalMid = (int) floor(($optimalLow + $optimalHigh) / 2);
            if ($arr[$optimalMid] === $target) {
                $minMoves++;
                break;
            }
            $optimalDir = $target > $arr[$optimalMid] ? 'right' : 'left';
            if ($optimalDir === 'left') {
                $optimalHigh = $optimalMid - 1;
            } else {
                $optimalLow = $optimalMid + 1;
            }
            $minMoves++;
        }

        $score = 0;
        $low = 0;
        $high = count($arr) - 1;
        $found = false;
        $allCorrect = true;
        $movesUsed = 0;
        $correctCount = 0;
        $totalSubmitted = 0;

        foreach ($directions as $i => $dir) {
            if ($low > $high || $movesUsed >= $maxMoves) {
                break;
            }

            $mid = (int) floor(($low + $high) / 2);
            $midVal = $arr[$mid] ?? null;

            if ($midVal === null) {
                break;
            }

            $correctDir = 'found';
            if ($midVal !== $target) {
                $correctDir = $target > $midVal ? 'right' : 'left';
            }

            $playerDir = trim(strtolower((string) $dir));
            $totalSubmitted++;

            if ($playerDir === $correctDir) {
                $score += (int) ($scoring->correct_decision ?? 100);
                $correctCount++;
            } else {
                $score += (int) ($scoring->mistake ?? -25);
                $allCorrect = false;
            }

            if ($correctDir === 'found') {
                $found = true;
                $movesUsed = $i + 1;
                break;
            }

            if ($correctDir === 'left') {
                $high = $mid - 1;
            } else {
                $low = $mid + 1;
            }

            $movesUsed = $i + 1;
        }

        if ($found) {
            $score += (int) ($scoring->found_bonus ?? 50);
            $extraMoves = max(0, $movesUsed - $minMoves);
            if ($extraMoves === 0 && $allCorrect) {
                $score += (int) ($scoring->efficiency_bonus ?? 25);
            }
        }

        $score = max(0, $score);
        $score = min($score, $maxScore);

        $accuracy = $totalSubmitted > 0 ? (int) round(($correctCount / $totalSubmitted) * 100) : 0;
        $mistakes = $totalSubmitted - $correctCount;

        return [
            'score' => $score,
            'max_score' => $maxScore,
            'completed' => $found,
            'accuracy' => $accuracy,
            'xp_reward' => $score,
            'attempts' => $movesUsed,
            'moves' => $movesUsed,
            'mistakes' => $mistakes,
        ];
    }

    private function validateMidpointMaster(string $action, object $config, int $maxScore): array
    {
        $rounds = json_decode($action, true) ?: [];

        $totalScore = 0;
        $roundConfigs = $config->rounds ?? [];
        $correctRounds = 0;
        $answeredRounds = 0;
        $mistakes = 0;

        foreach ($roundConfigs as $i => $round) {
            $low = (int) ($round['low'] ?? 0);
            $high = (int) ($round['high'] ?? 0);
            $expectedMid = $high >= $low ? (int) floor(($low + $high) / 2) : 0;
            $userAnswer = isset($rounds[$i]) ? (int) $rounds[$i] : null;

            if ($userAnswer === null) {
                continue;
            }

            $answeredRounds++;

            $totalScore += (int) ($config->round_completion_bonus ?? 50);

            if ($userAnswer === $expectedMid) {
                $totalScore += (int) ($config->correct_score ?? 100);
                $correctRounds++;
            } else {
                $totalScore += (int) ($config->incorrect_score ?? -25);
                $mistakes++;
            }
        }

        $totalScore = max(0, $totalScore);
        $totalScore = min($totalScore, $maxScore);
        $accuracy = $answeredRounds > 0 ? (int) round(($correctRounds / $answeredRounds) * 100) : 0;

        return [
            'score_delta' => $totalScore,
            'score' => $totalScore,
            'max_score' => $maxScore,
            'completed' => $answeredRounds === count($roundConfigs) && count($roundConfigs) > 0,
            'accuracy' => $accuracy,
            'xp_reward' => $totalScore,
            'attempts' => $answeredRounds,
            'correct_rounds' => $correctRounds,
            'total_rounds' => count($roundConfigs),
            'mistakes' => $mistakes,
        ];
    }

    private function validateTraceRace(string $action, object $config, int $maxScore): array
    {
        $decoded = json_decode($action, true) ?: [];
        $moves = $decoded['moves'] ?? [];

        if (! is_array($moves)) {
            $moves = [];
        }

        $arr = $config->array ?? [];
        $target = $config->target ?? 0;

        if (empty($arr)) {
            return [
                'score' => 0,
                'max_score' => $maxScore,
                'completed' => false,
                'accuracy' => 0,
                'xp_reward' => 0,
                'attempts' => 0,
                'moves' => 0,
                'mistakes' => 0,
                'low' => 0,
                'high' => 0,
                'mid' => 0,
                'mid_val' => 0,
                'found' => false,
                'min_moves' => 0,
            ];
        }

        $useLegacyScoring = isset($config->base_score) && ! isset($config->correct_mid_score);
        $correctSequence = $this->simulateTraceRace($arr, $target);
        $minMoves = count($correctSequence);

        $score = 0;
        $correctChecks = 0;
        $totalChecks = 0;
        $mistakes = 0;
        $found = false;
        $userMovesUsed = count($moves);

        foreach ($correctSequence as $i => $expected) {
            if ($i >= $userMovesUsed) {
                break;
            }

            $userMove = $moves[$i];
            if (! is_array($userMove)) {
                continue;
            }

            $userMid = isset($userMove['mid']) ? (int) $userMove['mid'] : -1;
            $userDir = strtolower(trim((string) ($userMove['dir'] ?? '')));
            $expectedMid = $expected['mid'];
            $expectedDir = $expected['dir'];

            if ($useLegacyScoring) {
                if ($userMid !== $expectedMid) {
                    $score -= (int) ($config->penalty_incorrect_mid ?? 100);
                    $mistakes++;
                } else {
                    $correctChecks++;
                }
                $totalChecks++;

                $dirCorrect = false;
                if ($expectedDir === 'found') {
                    $dirCorrect = $userDir === 'found' || $userDir === '';
                } else {
                    $dirCorrect = $userDir === $expectedDir;
                }

                if (! $dirCorrect) {
                    $score -= (int) ($config->penalty_wrong_direction ?? 100);
                    $mistakes++;
                } else {
                    $correctChecks++;
                }
                $totalChecks++;

                if ($expectedDir === 'found') {
                    $found = true;
                    break;
                }
            } else {
                $midCorrect = $userMid === $expectedMid;
                if ($midCorrect) {
                    $score += (int) ($config->correct_mid_score ?? 100);
                    $correctChecks++;
                } else {
                    $score += (int) ($config->incorrect_mid_score ?? -25);
                    $mistakes++;
                }
                $totalChecks++;

                $dirCorrect = false;
                if ($expectedDir === 'found') {
                    $dirCorrect = $userDir === 'found' || $userDir === '';
                } else {
                    $dirCorrect = $userDir === $expectedDir;
                }

                if ($dirCorrect) {
                    $score += (int) ($config->correct_dir_score ?? 100);
                    $correctChecks++;
                } else {
                    $score += (int) ($config->incorrect_dir_score ?? -25);
                    $mistakes++;
                }
                $totalChecks++;

                if ($expectedDir === 'found') {
                    $found = true;
                    $score += (int) ($config->found_bonus ?? 100);
                    break;
                }
            }
        }

        $extraMoves = max(0, $userMovesUsed - $minMoves);
        if ($useLegacyScoring) {
            $score = ($config->base_score ?? 1000) - $extraMoves * (int) ($config->penalty_extra_move ?? 50);
            $score = max(0, $score);
        } else {
            $score -= $extraMoves * (int) ($config->penalty_extra_move ?? 10);
            $score = max(0, $score);
        }

        $accuracy = $totalChecks > 0 ? (int) round(($correctChecks / $totalChecks) * 100) : 0;

        $newLow = 0;
        $newHigh = 0;
        $newMid = 0;
        $newMidVal = 0;

        if ($found && $minMoves > 0) {
            $lastStep = $correctSequence[$minMoves - 1];
            $newLow = $lastStep['low'];
            $newHigh = $lastStep['high'];
            $newMid = $lastStep['mid'];
            $newMidVal = $lastStep['midVal'];
        } elseif ($minMoves > 0) {
            $validMoves = min($userMovesUsed, $minMoves);
            if ($validMoves < $minMoves) {
                $nextStep = $correctSequence[$validMoves];
                $newLow = $nextStep['low'];
                $newHigh = $nextStep['high'];
                $newMid = $nextStep['mid'];
                $newMidVal = $nextStep['midVal'];
            } else {
                $lastStep = $correctSequence[$minMoves - 1];
                $newLow = $lastStep['low'];
                $newHigh = $lastStep['high'];
                $newMid = $lastStep['mid'];
                $newMidVal = $lastStep['midVal'];
                $found = true;
                if (! $useLegacyScoring) {
                    $score += (int) ($config->found_bonus ?? 100);
                }
            }
        } else {
            $newLow = 0;
            $newHigh = count($arr) - 1;
            $newMid = (int) floor(($newLow + $newHigh) / 2);
            $newMidVal = $arr[$newMid] ?? 0;
        }

        return [
            'score' => $score,
            'max_score' => $maxScore,
            'completed' => $found,
            'accuracy' => $accuracy,
            'xp_reward' => $found ? $score : 0,
            'attempts' => $userMovesUsed,
            'moves' => $userMovesUsed,
            'mistakes' => $mistakes,
            'low' => $newLow,
            'high' => $newHigh,
            'mid' => $newMid,
            'mid_val' => $newMidVal,
            'found' => $found,
            'min_moves' => $minMoves,
        ];
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
                'calculation' => "floor(($low + $high) / 2) = floor(".($low + $high)." / 2) = $expectedMid",
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

    public function generateHalfHuntRounds(object $config, int $count = 3): array
    {
        $baseArray = $config->array ?? [];
        $target = $config->target ?? 0;
        $difficulty = strtolower((string) ($config->difficulty ?? 'easy'));

        if (! empty($baseArray) && count($baseArray) >= 7) {
            $rounds = [];
            $arr = array_values($baseArray);
            $n = count($arr);
            $minMoves = 0;
            $simLow = 0;
            $simHigh = $n - 1;
            while ($simLow <= $simHigh) {
                $simMid = (int) floor(($simLow + $simHigh) / 2);
                $minMoves++;
                if ($arr[$simMid] === $target) {
                    break;
                }
                if ($target > $arr[$simMid]) {
                    $simLow = $simMid + 1;
                } else {
                    $simHigh = $simMid - 1;
                }
            }

            $rounds[] = [
                'array' => $arr,
                'target' => $target,
                'initial_low' => 0,
                'initial_high' => $n - 1,
                'min_moves' => $minMoves,
            ];

            return $rounds;
        }

        $range = $this->difficultyRange($difficulty);
        $minLen = $range[0];
        $maxLen = $range[1];

        $seed = $this->seedFromConfig($config);
        $rounds = [];

        for ($r = 0; $r < $count; $r++) {
            $roundSeed = $seed + $r * 137;
            $len = $minLen + ($roundSeed % ($maxLen - $minLen + 1));
            $arr = $this->generateSortedArray($len, $roundSeed);
            $targetIdx = $roundSeed % $len;
            $target = $arr[$targetIdx];

            $minMoves = 0;
            $simLow = 0;
            $simHigh = $len - 1;
            while ($simLow <= $simHigh) {
                $simMid = (int) floor(($simLow + $simHigh) / 2);
                $minMoves++;
                if ($arr[$simMid] === $target) {
                    break;
                }
                if ($target > $arr[$simMid]) {
                    $simLow = $simMid + 1;
                } else {
                    $simHigh = $simMid - 1;
                }
            }

            $rounds[] = [
                'array' => $arr,
                'target' => $target,
                'initial_low' => 0,
                'initial_high' => $len - 1,
                'min_moves' => $minMoves,
            ];
        }

        return $rounds;
    }

    public function validateHalfHuntMove(string $action, object $config, int $maxScore): array
    {
        $decoded = json_decode($action, true);
        if (! is_array($decoded)) {
            return [
                'score_delta' => 0,
                'score' => 0,
                'max_score' => $maxScore,
                'completed' => false,
                'accuracy' => 0,
                'xp_reward' => 0,
                'round_index' => 0,
                'rounds_total' => 0,
                'current_low' => 0,
                'current_high' => 0,
                'current_mid' => 0,
                'mid_val' => 0,
                'found' => false,
                'moves' => 0,
                'mistakes' => 0,
                'correct_dir' => '',
            ];
        }

        $roundIndex = (int) ($decoded['round_index'] ?? 0);
        $directions = is_array($decoded['directions'] ?? null) ? $decoded['directions'] : [];

        $rounds = $this->generateHalfHuntRounds($config);
        $roundsTotal = count($rounds);

        if ($roundIndex < 0 || $roundIndex >= $roundsTotal || $roundsTotal === 0) {
            return [
                'score_delta' => 0,
                'score' => 0,
                'max_score' => $maxScore,
                'completed' => false,
                'accuracy' => 0,
                'xp_reward' => 0,
                'round_index' => $roundIndex,
                'rounds_total' => $roundsTotal,
                'current_low' => 0,
                'current_high' => 0,
                'current_mid' => 0,
                'mid_val' => 0,
                'found' => false,
                'moves' => 0,
                'mistakes' => 0,
                'correct_dir' => '',
            ];
        }

        $round = $rounds[$roundIndex];
        $arr = array_values($round['array'] ?? []);
        $target = (int) ($round['target'] ?? 0);
        $initialLow = (int) ($round['initial_low'] ?? 0);
        $initialHigh = (int) ($round['initial_high'] ?? max(0, count($arr) - 1));

        $low = $initialLow;
        $high = $initialHigh;
        $found = false;
        $movesUsed = 0;
        $mistakes = 0;
        $score = 0;
        $correctDirForLastMove = '';
        $midValForLastMove = 0;
        $midForLastMove = 0;
        $lastMoveDelta = 0;

        foreach ($directions as $dir) {
            if ($low > $high || $found) {
                break;
            }

            $dir = strtolower(trim((string) $dir));
            if ($dir === 'found') {
                $found = true;
                $movesUsed++;
                break;
            }

            if ($dir !== 'left' && $dir !== 'right') {
                continue;
            }

            $mid = (int) floor(($low + $high) / 2);
            $midVal = $arr[$mid] ?? null;

            if ($midVal === null) {
                break;
            }

            $correctDir = $target > $midVal ? 'right' : 'left';
            $isCorrect = $dir === $correctDir;

            $moveDelta = 0;
            if ($midVal === $target) {
                $moveDelta = 100;
                $found = true;
            } elseif ($isCorrect) {
                $moveDelta = 100;
            } else {
                $moveDelta = -25;
                $mistakes++;
            }

            $correctDirForLastMove = $midVal === $target ? 'found' : $correctDir;
            $midValForLastMove = $midVal;
            $midForLastMove = $mid;
            $movesUsed++;
            $score += $moveDelta;
            $lastMoveDelta = $moveDelta;

            if ($midVal === $target) {
                $foundBonus = 100;
                $score += $foundBonus;
                $lastMoveDelta += $foundBonus;
                break;
            }

            if ($correctDir === 'left') {
                $high = $mid - 1;
            } else {
                $low = $mid + 1;
            }
        }

        if (! $found && $movesUsed === 0) {
            $mid = (int) floor(($low + $high) / 2);
            $midVal = $arr[$mid] ?? null;
            if ($midVal === $target) {
                $found = true;
                $score = 100;
                $correctDirForLastMove = 'found';
                $midValForLastMove = $midVal;
                $midForLastMove = $mid;
                $lastMoveDelta = 100;
            }
        }

        $score = max(0, $score);

        $currentMid = $found ? $midForLastMove : (int) floor(($low + $high) / 2);
        $currentMidVal = $arr[$currentMid] ?? 0;

        return [
            'score_delta' => $lastMoveDelta,
            'score' => $score,
            'max_score' => $maxScore,
            'completed' => $found,
            'accuracy' => $found ? 100 : 0,
            'xp_reward' => $found ? $score : 0,
            'round_index' => $roundIndex,
            'rounds_total' => $roundsTotal,
            'current_low' => $low,
            'current_high' => $high,
            'current_mid' => $currentMid,
            'mid_val' => $currentMidVal,
            'found' => $found,
            'moves' => $movesUsed,
            'mistakes' => $mistakes,
            'correct_dir' => $correctDirForLastMove,
        ];
    }

    private function difficultyRange(string $difficulty): array
    {
        $d = strtolower($difficulty);
        if (in_array($d, ['easy', 'beginner', 'simple'], true)) {
            return [7, 9];
        }
        if (in_array($d, ['medium', 'intermediate'], true)) {
            return [10, 12];
        }
        if (in_array($d, ['hard', 'advanced', 'difficult'], true)) {
            return [13, 15];
        }

        return [7, 9];
    }

    private function traceRaceDifficultyRange(string $difficulty): array
    {
        $d = strtolower($difficulty);
        if (in_array($d, ['easy', 'beginner', 'simple'], true)) {
            return [7, 11];
        }
        if (in_array($d, ['medium', 'intermediate'], true)) {
            return [12, 17];
        }
        if (in_array($d, ['hard', 'advanced', 'difficult'], true)) {
            return [18, 25];
        }

        return [7, 11];
    }

    public function generateTraceRaceConfig(object $config): array
    {
        $baseArray = $config->array ?? [];
        $target = $config->target ?? 0;
        $difficulty = strtolower((string) ($config->difficulty ?? 'easy'));

        if (! empty($baseArray) && count($baseArray) >= 7) {
            $arr = array_values($baseArray);
            $minMoves = 0;
            $simLow = 0;
            $simHigh = count($arr) - 1;
            while ($simLow <= $simHigh) {
                $simMid = (int) floor(($simLow + $simHigh) / 2);
                $minMoves++;
                if ($arr[$simMid] === $target) {
                    break;
                }
                if ($target > $arr[$simMid]) {
                    $simLow = $simMid + 1;
                } else {
                    $simHigh = $simMid - 1;
                }
            }

            return [
                'array' => $arr,
                'target' => $target,
                'min_moves' => $minMoves,
            ];
        }

        $range = $this->traceRaceDifficultyRange($difficulty);
        $minLen = $range[0];
        $maxLen = $range[1];

        $seed = $this->seedFromConfig($config);
        $len = $minLen + ($seed % ($maxLen - $minLen + 1));
        $arr = $this->generateSortedArray($len, $seed);
        $targetIdx = $seed % $len;
        $target = $arr[$targetIdx];

        $minMoves = 0;
        $simLow = 0;
        $simHigh = $len - 1;
        while ($simLow <= $simHigh) {
            $simMid = (int) floor(($simLow + $simHigh) / 2);
            $minMoves++;
            if ($arr[$simMid] === $target) {
                break;
            }
            if ($target > $arr[$simMid]) {
                $simLow = $simMid + 1;
            } else {
                $simHigh = $simMid - 1;
            }
        }

        return [
            'array' => $arr,
            'target' => $target,
            'min_moves' => $minMoves,
        ];
    }

    private function generateSortedArray(int $len, int $seed): array
    {
        $arr = [];
        $val = 10 + ($seed % 90);
        $stepBase = 2 + (($seed * 3) % 12);
        for ($i = 0; $i < $len; $i++) {
            $arr[] = $val;
            $val += $stepBase + ($seed % ($i + 2));
            $val = max($val, $arr[$i] + 2);
        }

        return array_slice($arr, 0, $len);
    }

    private function seedFromConfig(object $config): int
    {
        $json = json_encode($config);
        $hash = crc32((string) $json);

        return abs($hash) % 10000;
    }
}
