<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LearningModuleSeeder extends Seeder
{
    public function run(): void
    {
        $moduleId = 'lm1';
        $now = now();

        $moduleExists = DB::table('learning_modules')->where('slug', 'binary-search-basics')->exists();

        if (! $moduleExists) {
            DB::table('learning_modules')->insert([
                'id' => $moduleId,
                'title' => 'Binary Search',
                'slug' => 'binary-search-basics',
                'topic' => 'Searching',
                'description' => 'Master binary search from first principles: sorted invariants, halving logic, and boundary-safe implementations.',
                'difficulty' => 'Beginner',
                'estimated_minutes' => 20,
                'xp_reward' => 150,
                'is_active' => true,
                'created_at' => $now,
            ]);
        }

        $steps = [
            [
                'id' => 'ls1',
                'learning_module_id' => $moduleId,
                'step_order' => 1,
                'type' => 'explanation',
                'title' => 'What is binary search?',
                'content' => 'Binary search is an algorithm that finds the position of a target value within a sorted array. It repeatedly divides the search interval in half, comparing the target with the middle element and eliminating half of the remaining elements with each step. This yields O(log n) time complexity, which is dramatically faster than linear search for large datasets.',
                'question' => null,
                'options' => null,
                'correct_answer' => null,
                'xp_reward' => 10,
                'created_at' => $now,
            ],
            [
                'id' => 'ls2',
                'learning_module_id' => $moduleId,
                'step_order' => 2,
                'type' => 'explanation',
                'title' => 'Why does sorted data matter?',
                'content' => 'Binary search relies on a critical invariant: the input array must be sorted. If the array is unsorted, halving the search space based on a middle-element comparison gives no guarantee about where the target lies. Sorting is the prerequisite that makes the "greater-than" and "less-than" decisions meaningful, allowing us to discard half the remaining candidates with certainty.',
                'question' => null,
                'options' => null,
                'correct_answer' => null,
                'xp_reward' => 10,
                'created_at' => $now,
            ],
            [
                'id' => 'ls3',
                'learning_module_id' => $moduleId,
                'step_order' => 3,
                'type' => 'explanation',
                'title' => 'Understand low/high/mid',
                'content' => 'The three pointers define the current search window. `low` marks the leftmost candidate index, `high` marks the rightmost candidate index, and `mid` is the midpoint — typically computed as `low + (high - low) / 2` to avoid integer overflow. After comparing `arr[mid]` with the target, you either set `high = mid - 1` (target is smaller) or `low = mid + 1` (target is larger), shrinking the window until `low > high`.',
                'question' => null,
                'options' => null,
                'correct_answer' => null,
                'xp_reward' => 10,
                'created_at' => $now,
            ],
            [
                'id' => 'ls4',
                'learning_module_id' => $moduleId,
                'step_order' => 4,
                'type' => 'mcq',
                'title' => 'Predict which half remains',
                'content' => 'You are searching for the value 7 in the sorted array [1, 3, 5, 7, 9, 11, 13]. The midpoint index is 3 (value 7). After this comparison, what should the next search window be?',
                'question' => 'The target equals arr[mid]. What is the correct action?',
                'options' => json_encode([
                    'Return index 3 immediately — the target is found.',
                    'Continue searching the left half [1, 3, 5] anyway.',
                    'Continue searching the right half [9, 11, 13] anyway.',
                    'Set low = mid + 1 and search the entire array again.',
                ]),
                'correct_answer' => 'A',
                'xp_reward' => 15,
                'created_at' => $now,
            ],
            [
                'id' => 'ls5',
                'learning_module_id' => $moduleId,
                'step_order' => 5,
                'type' => 'code_trace',
                'title' => 'Trace a complete binary search',
                'content' => 'Given the sorted array [2, 4, 6, 8, 10, 12, 14] and target 10, walk through each iteration of binary search. Track `low`, `high`, `mid`, and `arr[mid]` until the target is found or the window collapses.',
                'question' => 'At which iteration is the target 10 found, and what is the returned index?',
                'options' => json_encode([
                    'Iteration 1, index 3',
                    'Iteration 2, index 4',
                    'Iteration 3, index 5',
                    'Iteration 2, index 3',
                ]),
                'correct_answer' => 'B',
                'xp_reward' => 20,
                'created_at' => $now,
            ],
        ];

        $stepsExist = DB::table('learning_steps')->where('learning_module_id', $moduleId)->exists();
        if (! $stepsExist) {
            DB::table('learning_steps')->insert($steps);
        }

        $playChallenges = [
            [
                'id' => 'pc1',
                'learning_module_id' => $moduleId,
                'type' => 'fill_blank',
                'title' => 'Choose Left or Right',
                'instructions' => 'You are given a sorted array and a target value. After inspecting the middle element, choose whether the target lies in the left half or the right half. Fill in the missing boundary update.',
                'config' => json_encode([
                    'starter' => "function findTarget(arr, target) {\n  let low = 0, high = arr.length - 1;\n  while (low <= high) {\n    const mid = Math.floor((low + high) / 2);\n    if (arr[mid] === target) return mid;\n    else if (target < arr[mid]) {\n      // Which boundary updates?\n      high = ______;\n    } else {\n      low = ______;\n    }\n  }\n  return -1;\n}",
                    'blanks' => ['mid - 1', 'mid + 1'],
                    'test_cases' => [
                        ['arr' => [1, 3, 5, 7, 9], 'target' => 3, 'expected' => 1],
                        ['arr' => [2, 4, 6, 8], 'target' => 8, 'expected' => 3],
                    ],
                ]),
                'xp_reward' => 25,
                'time_limit' => 300,
                'created_at' => $now,
            ],
            [
                'id' => 'pc2',
                'learning_module_id' => $moduleId,
                'type' => 'coding',
                'title' => 'Find the correct mid',
                'instructions' => 'Given the current `low`, `high`, and the target value, predict the exact `mid` index and the next boundary update for each step of the binary search trace.',
                'config' => json_encode([
                    'array' => [1, 4, 7, 10, 13, 16, 19],
                    'target' => 13,
                    'steps' => [
                        ['low' => 0, 'high' => 6, 'expected_mid' => 3, 'expected_next' => 'low = mid + 1'],
                        ['low' => 4, 'high' => 6, 'expected_mid' => 5, 'expected_next' => 'low = mid + 1'],
                        ['low' => 6, 'high' => 6, 'expected_mid' => 6, 'expected_next' => 'return mid'],
                    ],
                ]),
                'xp_reward' => 30,
                'time_limit' => 240,
                'created_at' => $now,
            ],
            [
                'id' => 'pc3',
                'learning_module_id' => $moduleId,
                'type' => 'trace',
                'title' => 'Trace binary search in minimum moves',
                'instructions' => 'Trace the binary search algorithm on a hidden sorted array. At each step, choose the correct midpoint and boundary update. Complete the search in the minimum number of moves to earn the full XP reward.',
                'config' => json_encode([
                    'array_length' => 15,
                    'target_exists' => true,
                    'max_moves' => 4,
                    'scoring' => [
                        'perfect' => 100,
                        'one_extra_move' => 75,
                        'two_extra_moves' => 50,
                        'more_than_two' => 25,
                    ],
                ]),
                'xp_reward' => 40,
                'time_limit' => 180,
                'created_at' => $now,
            ],
            [
                'id' => 'pc5',
                'learning_module_id' => $moduleId,
                'type' => 'midpoint_master',
                'title' => 'MIDPOINT MASTER',
                'instructions' => 'Given the current `low` and `high` indices, compute the midpoint using `mid = floor((low + high) / 2)`. Select the correct midpoint index for each round. Pay attention to odd and even ranges to master floor division!',
                'config' => json_encode([
                    'rounds' => [
                        ['low' => 0, 'high' => 7, 'expected_mid' => 3],
                        ['low' => 2, 'high' => 9, 'expected_mid' => 5],
                        ['low' => 3, 'high' => 8, 'expected_mid' => 5],
                        ['low' => 5, 'high' => 12, 'expected_mid' => 8],
                        ['low' => 0, 'high' => 3, 'expected_mid' => 1],
                        ['low' => 4, 'high' => 11, 'expected_mid' => 7],
                    ],
                    'correct_score' => 100,
                    'incorrect_score' => -25,
                ]),
                'xp_reward' => 45,
                'time_limit' => 240,
                'created_at' => $now,
            ],
            [
                'id' => 'pc6',
                'learning_module_id' => $moduleId,
                'type' => 'trace_race',
                'title' => 'TRACE RACE',
                'instructions' => 'Trace the complete binary search algorithm on the array. At each step: (1) pick the correct midpoint, (2) choose LEFT or RIGHT to narrow the search space. Can you find the target in the minimum number of moves?',
                'config' => json_encode([
                    'array' => [2, 5, 8, 12, 16, 23, 31, 44, 57, 68, 79],
                    'target' => 31,
                    'base_score' => 1000,
                    'penalty_incorrect_mid' => 100,
                    'penalty_wrong_direction' => 100,
                    'penalty_extra_move' => 50,
                ]),
                'xp_reward' => 50,
                'time_limit' => 300,
                'created_at' => $now,
            ],
            [
                'id' => 'pc4',
                'learning_module_id' => $moduleId,
                'type' => 'binary_search',
                'title' => 'HALF HUNT',
                'instructions' => 'You are hunting a target in a sorted array. At each step, inspect the middle element and decide: should binary search continue on the LEFT half or the RIGHT half? Correct decisions earn points. Mistakes cost points. Find the target to win!',
                'config' => json_encode([
                    'array' => [3, 8, 12, 17, 24, 31, 42, 56, 68],
                    'target' => 42,
                    'max_moves' => 5,
                    'scoring' => [
                        'correct_decision' => 15,
                        'mistake' => -10,
                        'found_bonus' => 15,
                        'efficiency_bonus' => 5,
                        'min_score' => 0,
                    ],
                ]),
                'xp_reward' => 35,
                'time_limit' => 240,
                'created_at' => $now,
            ],
        ];

        $challengesExist = DB::table('play_challenges')->where('learning_module_id', $moduleId)->exists();
        if (! $challengesExist) {
            DB::table('play_challenges')->insert($playChallenges);
        }

        $learningProblems = [
            [
                'id' => 'lp_bs_prove_1',
                'learning_module_id' => $moduleId,
                'problem_id' => 'p8',
                'stage' => 'practice',
                'sort_order' => 1,
                'created_at' => $now,
            ],
        ];

        $problemsExist = DB::table('learning_problems')->where('learning_module_id', $moduleId)->exists();
        if (! $problemsExist) {
            DB::table('learning_problems')->insert($learningProblems);
        }
    }
}
