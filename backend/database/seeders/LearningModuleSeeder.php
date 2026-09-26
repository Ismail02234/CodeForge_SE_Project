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

        DB::table('learning_modules')->updateOrInsert(
            ['id' => $moduleId],
            [
                'title' => 'Binary Search',
                'slug' => 'binary-search-basics',
                'topic' => 'Searching',
                'description' => 'Master binary search from first principles: sorted arrays, midpoint decisions, and safe boundary updates.',
                'difficulty' => 'Beginner',
                'estimated_minutes' => 20,
                'xp_reward' => 150,
                'is_active' => true,
                'created_at' => $now,
            ]
        );

        $steps = [
            [
                'id' => 'ls1', 'learning_module_id' => $moduleId, 'step_order' => 1,
                'type' => 'explanation', 'title' => 'What is binary search?',
                'content' => 'Binary search finds a target in a sorted array by repeatedly cutting the remaining search range in half. Each comparison tells you which half can be discarded, so the search takes O(log n) time.',
                'question' => null, 'options' => null, 'correct_answer' => null,
                'xp_reward' => 10, 'created_at' => $now,
            ],
            [
                'id' => 'ls2', 'learning_module_id' => $moduleId, 'step_order' => 2,
                'type' => 'explanation', 'title' => 'Why sorted data matters',
                'content' => 'Binary search only works when the values are sorted. If the middle value is too small, every value before it can be ignored. If it is too large, every value after it can be ignored.',
                'question' => null, 'options' => null, 'correct_answer' => null,
                'xp_reward' => 10, 'created_at' => $now,
            ],
            [
                'id' => 'ls3', 'learning_module_id' => $moduleId, 'step_order' => 3,
                'type' => 'explanation', 'title' => 'Understand low, high and mid',
                'content' => 'low and high describe the current search range. mid is the middle index. After checking arr[mid], move high to mid - 1 or low to mid + 1 until the target is found or low becomes greater than high.',
                'question' => null, 'options' => null, 'correct_answer' => null,
                'xp_reward' => 10, 'created_at' => $now,
            ],
            [
                'id' => 'ls4', 'learning_module_id' => $moduleId, 'step_order' => 4,
                'type' => 'mcq', 'title' => 'Predict the correct action',
                'content' => 'You are searching for 7 in [1, 3, 5, 7, 9, 11, 13]. The midpoint is index 3 and arr[mid] is 7.',
                'question' => 'The target equals arr[mid]. What should binary search do?',
                'options' => json_encode([
                    'Return index 3 immediately.',
                    'Search the left half.',
                    'Search the right half.',
                    'Restart from index 0.',
                ]),
                'correct_answer' => 'A', 'xp_reward' => 15, 'created_at' => $now,
            ],
            [
                'id' => 'ls5', 'learning_module_id' => $moduleId, 'step_order' => 5,
                'type' => 'code_trace', 'title' => 'Trace a complete binary search',
                'content' => 'Trace binary search on [2, 4, 6, 8, 10, 12, 14] with target 10. Track low, high, mid and arr[mid] after every comparison.',
                'question' => 'When is 10 found and what index is returned?',
                'options' => json_encode([
                    'Iteration 1, index 3',
                    'Iteration 2, index 4',
                    'Iteration 3, index 5',
                    'Iteration 2, index 3',
                ]),
                'correct_answer' => 'B', 'xp_reward' => 20, 'created_at' => $now,
            ],
        ];

        foreach ($steps as $step) {
            DB::table('learning_steps')->updateOrInsert(['id' => $step['id']], $step);
        }

        // The current Binary Search Play stage intentionally has exactly three games.
        DB::table('play_challenges')
            ->where('learning_module_id', $moduleId)
            ->whereNotIn('id', ['pc4', 'pc5', 'pc6'])
            ->delete();

        $challenges = [
            [
                'id' => 'pc4', 'learning_module_id' => $moduleId, 'type' => 'half_hunt',
                'title' => 'HALF HUNT',
                'instructions' => 'Inspect the midpoint and decide whether the target is in the LEFT or RIGHT half. Complete the hunt to unlock the next game.',
                'config' => json_encode([
                    'array' => [3, 8, 12, 17, 24, 31, 42, 56, 68],
                    'target' => 42,
                    'difficulty' => 'easy',
                    'rounds_per_game' => 3,
                ]),
                'xp_reward' => 35, 'time_limit' => 240, 'created_at' => $now,
            ],
            [
                'id' => 'pc5', 'learning_module_id' => $moduleId, 'type' => 'midpoint_master',
                'title' => 'MIDPOINT MASTER',
                'instructions' => 'Use mid = floor((low + high) / 2) and select the correct midpoint for every round.',
                'config' => json_encode([
                    'rounds' => [
                        ['low' => 0, 'high' => 8, 'expected_mid' => 4],
                        ['low' => 2, 'high' => 7, 'expected_mid' => 4],
                        ['low' => 1, 'high' => 6, 'expected_mid' => 3],
                        ['low' => 3, 'high' => 10, 'expected_mid' => 6],
                        ['low' => 0, 'high' => 5, 'expected_mid' => 2],
                        ['low' => 1, 'high' => 9, 'expected_mid' => 5],
                    ],
                    'correct_score' => 100,
                    'incorrect_score' => -25,
                    'round_completion_bonus' => 50,
                ]),
                'xp_reward' => 45, 'time_limit' => 240, 'created_at' => $now,
            ],
            [
                'id' => 'pc6', 'learning_module_id' => $moduleId, 'type' => 'trace_race',
                'title' => 'TRACE RACE',
                'instructions' => 'Trace a full binary search. Pick the correct midpoint and then choose LEFT, RIGHT, or FOUND until the target is reached.',
                'config' => json_encode([
                    'array' => [2, 5, 8, 12, 16, 23, 31, 44, 57, 68, 79],
                    'target' => 31,
                    'difficulty' => 'easy',
                    'correct_mid_score' => 100,
                    'incorrect_mid_score' => -25,
                    'correct_dir_score' => 100,
                    'incorrect_dir_score' => -25,
                    'found_bonus' => 100,
                    'penalty_extra_move' => 10,
                ]),
                'xp_reward' => 50, 'time_limit' => 300, 'created_at' => $now,
            ],
        ];

        foreach ($challenges as $challenge) {
            DB::table('play_challenges')->updateOrInsert(['id' => $challenge['id']], $challenge);
        }

        if (DB::table('problems')->where('id', 'p8')->exists()) {
            DB::table('learning_problems')->updateOrInsert(
                ['id' => 'lp_bs_prove_1'],
                [
                    'learning_module_id' => $moduleId,
                    'problem_id' => 'p8',
                    'stage' => 'practice',
                    'sort_order' => 1,
                    'created_at' => $now,
                ]
            );
        }
    }
}
