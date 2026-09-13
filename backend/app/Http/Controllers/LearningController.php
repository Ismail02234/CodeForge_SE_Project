<?php

namespace App\Http\Controllers;

use App\Services\PerformanceProfileCalculator;
use App\Services\ProblemPracticeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LearningController extends Controller
{
    private function service(): ProblemPracticeService
    {
        return new ProblemPracticeService(DB::connection()->getPdo());
    }

    public function index(Request $request)
    {
        $userId = $request->user()->id;

        $modules = DB::table('learning_modules as m')
            ->where('m.is_active', true)
            ->select('m.id', 'm.title', 'm.slug', 'm.topic', 'm.description', 'm.difficulty', 'm.estimated_minutes', 'm.xp_reward')
            ->orderBy('m.topic')
            ->orderBy('m.created_at')
            ->get();

        $progressRows = DB::table('learning_progress as lp')
            ->where('lp.user_id', $userId)
            ->select(
                'lp.learning_module_id',
                'lp.learn_completed',
                'lp.play_completed',
                'lp.prove_completed',
                'lp.learn_score',
                'lp.play_score',
                'lp.prove_score',
                'lp.mastery_score',
                'lp.learn_accuracy',
                'lp.play_accuracy',
                'lp.prove_accuracy',
                'lp.concept_mastery',
                'lp.learn_completed_steps',
                'lp.started_at',
                'lp.completed_at',
            )
            ->get();

        $progressByModule = [];
        foreach ($progressRows as $row) {
            $progressByModule[$row->learning_module_id] = $row;
        }

        $moduleIds = $modules->pluck('id')->all();

        $totalPossibleByModule = [];
        $learnTotalsByModule = [];
        $playTotalsByModule = [];
        if (! empty($moduleIds)) {
            $learnTotals = DB::table('learning_steps')
                ->whereIn('learning_module_id', $moduleIds)
                ->whereNotNull('correct_answer')
                ->select('learning_module_id', DB::raw('SUM(xp_reward) as total'))
                ->groupBy('learning_module_id')
                ->get();
            foreach ($learnTotals as $row) {
                $totalPossibleByModule[$row->learning_module_id] = ($totalPossibleByModule[$row->learning_module_id] ?? 0) + (int) $row->total;
                $learnTotalsByModule[$row->learning_module_id] = (int) $row->total;
            }

            $playTotals = DB::table('play_challenges')
                ->whereIn('learning_module_id', $moduleIds)
                ->select('learning_module_id', DB::raw('SUM(xp_reward) as total'))
                ->groupBy('learning_module_id')
                ->get();
            foreach ($playTotals as $row) {
                $totalPossibleByModule[$row->learning_module_id] = ($totalPossibleByModule[$row->learning_module_id] ?? 0) + (int) $row->total;
                $playTotalsByModule[$row->learning_module_id] = (int) $row->total;
            }

            $proveTotals = DB::table('learning_problems as lp')
                ->join('problems as p', 'p.id', '=', 'lp.problem_id')
                ->whereIn('lp.learning_module_id', $moduleIds)
                ->select('lp.learning_module_id', DB::raw('0 as total'))
                ->groupBy('lp.learning_module_id')
                ->get();
            foreach ($proveTotals as $row) {
                $totalPossibleByModule[$row->learning_module_id] = ($totalPossibleByModule[$row->learning_module_id] ?? 0) + (int) $row->total;
            }
        }

        $overall = DB::table('learning_progress as lp')
            ->join('learning_modules as lm', 'lm.id', '=', 'lp.learning_module_id')
            ->where('lp.user_id', $userId)
            ->selectRaw('
                SUM(lp.mastery_score) as total_mastery_score,
                SUM(
                    (SELECT COALESCE(SUM(xp_reward), 0) FROM learning_steps WHERE learning_module_id = lp.learning_module_id AND correct_answer IS NOT NULL) +
                    (SELECT COALESCE(SUM(xp_reward), 0) FROM play_challenges WHERE learning_module_id = lp.learning_module_id) +
                     (SELECT 0 FROM learning_problems lp2 WHERE lp2.learning_module_id = lp.learning_module_id LIMIT 1)
                ) as total_possible_score
            ')
            ->first();

        $overallMasteryPct = 0;
        if ($overall && $overall->total_possible_score > 0) {
            $overallMasteryPct = (int) round(($overall->total_mastery_score / $overall->total_possible_score) * 100);
        }

        $modulesWithProgress = $modules->map(function ($m) use ($progressByModule, $totalPossibleByModule, $learnTotalsByModule, $playTotalsByModule) {
            $progress = $progressByModule[$m->id] ?? null;
            $totalPossible = (int) ($totalPossibleByModule[$m->id] ?? 0);
            $masteryScore = $progress ? (int) ($progress->mastery_score ?? 0) : 0;
            $masteryPct = $totalPossible > 0 ? (int) round(($masteryScore / $totalPossible) * 100) : 0;

            $learnScore = $progress ? (int) ($progress->learn_score ?? 0) : 0;
            $playScore = $progress ? (int) ($progress->play_score ?? 0) : 0;
            $proveScore = $progress ? (int) ($progress->prove_score ?? 0) : 0;

            $totalLearnXp = $learnTotalsByModule[$m->id] ?? 0;
            $totalPlayXp = $playTotalsByModule[$m->id] ?? 0;

            $learnPct = $totalLearnXp > 0 ? (int) round(($learnScore / $totalLearnXp) * 100) : 0;
            $playPct = $totalPlayXp > 0 ? (int) round(($playScore / $totalPlayXp) * 100) : 0;
            $provePct = $progress ? max(0, min(100, (int) ($progress->prove_accuracy ?? 0))) : 0;
            $masteryPct = max(0, min(100, (int) round(($learnPct + $playPct + $provePct) / 3)));

            return [
                'id' => $m->id,
                'title' => $m->title,
                'slug' => $m->slug,
                'topic' => $m->topic,
                'description' => $m->description,
                'difficulty' => $m->difficulty,
                'estimated_minutes' => (int) $m->estimated_minutes,
                'xp_reward' => (int) $m->xp_reward,
                'is_started' => (bool) $progress,
                'is_completed' => $progress
                    ? (bool) ($progress->learn_completed ?? false)
                    && (bool) ($progress->play_completed ?? false)
                    && (bool) ($progress->prove_completed ?? false)
                    : false,
                'mastery_pct' => max(0, min(100, $masteryPct)),
                'mastery_score' => $masteryScore,
                'total_possible_xp' => $totalPossible,
                'progress' => [
                    'learn_score' => $learnScore,
                    'play_score' => $playScore,
                    'prove_score' => $proveScore,
                    'learn_pct' => $learnPct,
                    'play_pct' => $playPct,
                    'prove_pct' => $provePct,
                    'concept_mastery' => $progress ? (int) ($progress->concept_mastery ?? 0) : 0,
                    'learn_completed_steps' => $progress ? (array) ($progress->learn_completed_steps ?? []) : [],
                ],
                'started_at' => $progress ? ($progress->started_at ?? null) : null,
                'completed_at' => $progress ? ($progress->completed_at ?? null) : null,
            ];
        });

        $started = $modulesWithProgress->filter(fn ($m) => $m['is_started'] && ! $m['is_completed']);
        $completed = $modulesWithProgress->filter(fn ($m) => $m['is_completed']);
        $unstarted = $modulesWithProgress->filter(fn ($m) => ! $m['is_started']);

        $recommended = $unstarted->values()->all();

        return [
            'modules' => $modulesWithProgress->values()->all(),
            'continue_learning' => $started->sortBy('mastery_pct')->values()->all(),
            'recommended' => $recommended,
            'completed' => $completed->values()->all(),
            'overall_mastery_pct' => max(0, min(100, $overallMasteryPct)),
        ];
    }

    public function show(Request $request, string $slug)
    {
        $module = DB::table('learning_modules as m')
            ->where('m.slug', $slug)
            ->where('m.is_active', true)
            ->select('m.id', 'm.title', 'm.slug', 'm.topic', 'm.description', 'm.difficulty', 'm.estimated_minutes', 'm.xp_reward')
            ->first();

        abort_if(! $module, 404, 'Module not found.');

        $moduleId = $module->id;

        $steps = DB::table('learning_steps as s')
            ->where('s.learning_module_id', $moduleId)
            ->orderBy('s.step_order')
            ->select('s.id', 's.step_order', 's.type', 's.title', 's.content', 's.question', 's.options', 's.xp_reward')
            ->get()
            ->map(function ($s) {
                $options = [];
                if ($s->options) {
                    $decoded = json_decode($s->options, true);
                    if (is_array($decoded)) {
                        $options = array_keys($decoded) !== range(0, count($decoded) - 1)
                            ? array_values($decoded)
                            : $decoded;
                        $options = array_slice($options, 0, 5);
                        if (count($options) < 2) {
                            $options = [];
                        }
                    }
                }
                $s->options = $options;
                unset($s->correct_answer);

                return $s;
            });

        $challenges = DB::table('play_challenges as c')
            ->where('c.learning_module_id', $moduleId)
            ->orderBy('c.id')
            ->select('c.id', 'c.type', 'c.title', 'c.instructions', 'c.config', 'c.xp_reward', 'c.time_limit')
            ->get()
            ->map(function ($c) {
                $config = [];
                if ($c->config) {
                    $decoded = json_decode($c->config, true);
                    if (is_array($decoded)) {
                        $config = $decoded;
                    }
                }
                $c->config = $config;

                return $c;
            });

        $problems = DB::table('learning_problems as lp')
            ->join('problems as p', 'p.id', '=', 'lp.problem_id')
            ->where('lp.learning_module_id', $moduleId)
            ->orderBy('lp.stage')
            ->orderBy('lp.sort_order')
            ->select('lp.id', 'lp.stage', 'lp.sort_order', 'p.id as problem_id', 'p.title', 'p.topic', 'p.difficulty', 'p.tags')
            ->get();

        $userId = $request->user()->id;
        $progress = DB::table('learning_progress')
            ->where('user_id', $userId)
            ->where('learning_module_id', $moduleId)
            ->first();

        $totalLearnXp = (int) DB::table('learning_steps')
            ->where('learning_module_id', $moduleId)
            ->whereNotNull('correct_answer')
            ->sum('xp_reward');

        $totalPlayXp = (int) DB::table('play_challenges')
            ->where('learning_module_id', $moduleId)
            ->sum('xp_reward');

        $totalProveXp = (int) DB::table('learning_problems as lp')
            ->join('problems as p', 'p.id', '=', 'lp.problem_id')
            ->where('lp.learning_module_id', $moduleId)
            ->sum(DB::raw('0'));

        $totalXp = $totalLearnXp + $totalPlayXp + $totalProveXp;

        $learnScore = (int) ($progress->learn_score ?? 0);
        $playScore = (int) ($progress->play_score ?? 0);
        $proveScore = (int) ($progress->prove_score ?? 0);
        $masteryScore = (int) ($progress->mastery_score ?? $learnScore + $playScore + $proveScore);

        $learnPct = $totalLearnXp > 0 ? (int) round(($learnScore / $totalLearnXp) * 100) : 0;
        $playPct = $totalPlayXp > 0 ? (int) round(($playScore / $totalPlayXp) * 100) : 0;
        $provePct = $progress ? max(0, min(100, (int) ($progress->prove_accuracy ?? 0))) : 0;
        $masteryPct = max(0, min(100, (int) round(($learnPct + $playPct + $provePct) / 3)));

        return [
            'module' => $module,
            'learn_steps' => $steps,
            'play_challenges' => $challenges,
            'prove_problems' => $problems,
            'progress' => $progress ?: null,
            'total_xp' => $totalXp,
            'total_learn_xp' => $totalLearnXp,
            'total_play_xp' => $totalPlayXp,
            'total_prove_xp' => $totalProveXp,
            'learn_pct' => max(0, min(100, $learnPct)),
            'play_pct' => max(0, min(100, $playPct)),
            'prove_pct' => max(0, min(100, $provePct)),
            'mastery_pct' => max(0, min(100, $masteryPct)),
            'module_completed' => $progress && (bool) ($progress->learn_completed ?? false) && (bool) ($progress->play_completed ?? false) && (bool) ($progress->prove_completed ?? false),
            'learn_completed_steps' => $progress ? (array) ($progress->learn_completed_steps ?? []) : [],
        ];
    }

    public function submitLearn(Request $request, string $slug)
    {
        $data = $request->validate([
            'step_id' => ['required', 'string', 'max:50'],
            'answer' => ['required', 'string', 'max:1000'],
        ]);

        $module = DB::table('learning_modules')
            ->where('slug', $slug)
            ->where('is_active', true)
            ->select('id')
            ->first();
        abort_if(! $module, 404, 'Module not found.');

        $step = DB::table('learning_steps')
            ->where('id', $data['step_id'])
            ->where('learning_module_id', $module->id)
            ->select('id', 'step_order', 'type', 'correct_answer', 'xp_reward')
            ->first();
        abort_if(! $step, 404, 'Step not found.');

        $correct = false;
        if ($step->correct_answer !== null) {
            $correct = trim(strtolower((string) $data['answer'])) === trim(strtolower((string) $step->correct_answer));
        }

        $userId = $request->user()->id;

        $totalLearnXp = (int) DB::table('learning_steps')
            ->where('learning_module_id', $module->id)
            ->whereNotNull('correct_answer')
            ->sum('xp_reward');

        $progress = DB::table('learning_progress')
            ->where('user_id', $userId)
            ->where('learning_module_id', $module->id)
            ->first();

        $completedSteps = $progress ? (array) ($progress->learn_completed_steps ?? []) : [];
        $alreadyCompleted = in_array($step->id, $completedSteps, true) || ($totalLearnXp > 0 && $progress && (int) ($progress->learn_score ?? 0) >= $totalLearnXp);

        if (! $progress) {
            $progressId = 'lp_'.bin2hex(random_bytes(8));
            $learnScore = $correct && ! $alreadyCompleted ? (int) $step->xp_reward : 0;
            $learnCompleted = $correct && ! $alreadyCompleted && $totalLearnXp > 0 && $learnScore >= $totalLearnXp;
            $learnAccuracy = $correct ? 100 : 0;

            if ($correct && ! $alreadyCompleted) {
                $completedSteps[] = $step->id;
            }

            DB::table('learning_progress')->insert([
                'id' => $progressId,
                'user_id' => $userId,
                'learning_module_id' => $module->id,
                'learn_completed' => $learnCompleted,
                'play_completed' => false,
                'prove_completed' => false,
                'learn_score' => $learnScore,
                'play_score' => 0,
                'prove_score' => 0,
                'mastery_score' => $learnScore,
                'attempts' => 1,
                'hints_used' => 0,
                'learn_accuracy' => $learnAccuracy,
                'play_accuracy' => null,
                'prove_accuracy' => null,
                'concept_mastery' => $totalLearnXp > 0 ? (int) round(($learnScore / $totalLearnXp) * 100) : 0,
                'weakness_signal' => null,
                'repeated_failed_concepts' => null,
                'learn_attempts' => 1,
                'play_attempts' => 0,
                'prove_attempts' => 0,
                'learn_correct' => $correct ? 1 : 0,
                'play_correct' => 0,
                'prove_correct' => 0,
                'learn_completed_steps' => $completedSteps ?: null,
                'started_at' => now(),
                'completed_at' => $learnCompleted ? now() : null,
                'created_at' => now(),
            ]);
        } else {
            $newLearnScore = $correct && ! $alreadyCompleted
                ? (int) ($progress->learn_score ?? 0) + (int) $step->xp_reward
                : (int) ($progress->learn_score ?? 0);
            $learnCompleted = $totalLearnXp > 0 && $newLearnScore >= $totalLearnXp;

            $newLearnAttempts = (int) ($progress->learn_attempts ?? 0) + 1;
            $newLearnCorrect = $correct
                ? (int) ($progress->learn_correct ?? 0) + 1
                : (int) ($progress->learn_correct ?? 0);
            $newLearnAccuracy = $newLearnAttempts > 0
                ? (int) round(($newLearnCorrect / $newLearnAttempts) * 100)
                : 0;

            if ($correct && ! $alreadyCompleted) {
                $completedSteps[] = $step->id;
            }

            $learnUpdate = [
                'learn_score' => $newLearnScore,
                'learn_completed' => $learnCompleted,
                'learn_attempts' => $newLearnAttempts,
                'learn_correct' => $newLearnCorrect,
                'learn_accuracy' => $newLearnAccuracy,
                'mastery_score' => $newLearnScore + (int) ($progress->play_score ?? 0) + (int) ($progress->prove_score ?? 0),
                'learn_completed_steps' => $completedSteps ?: null,
            ];

            $playCompleted = (bool) ($progress->play_completed ?? false);
            $proveCompleted = (bool) ($progress->prove_completed ?? false);

            if ($learnCompleted && $playCompleted && $proveCompleted) {
                $learnUpdate['completed_at'] = now();
            }

            DB::table('learning_progress')
                ->where('id', $progress->id)
                ->update($learnUpdate);
        }

        $newLearnScore = $correct && ! $alreadyCompleted
            ? (int) ($progress->learn_score ?? 0) + (int) $step->xp_reward
            : (int) ($progress->learn_score ?? 0);
        $newLearnAttempts = $progress ? (int) ($progress->learn_attempts ?? 0) + 1 : 1;
        $newLearnCorrect = $progress && $correct ? (int) ($progress->learn_correct ?? 0) + 1 : ($correct ? 1 : 0);
        $newLearnAccuracy = $newLearnAttempts > 0
            ? (int) round(($newLearnCorrect / $newLearnAttempts) * 100)
            : 0;
        $learnPct = $totalLearnXp > 0 ? (int) round(($newLearnScore / $totalLearnXp) * 100) : 0;

        return [
            'correct' => $correct,
            'step_id' => $step->id,
            'xp_reward' => $correct && ! $alreadyCompleted ? (int) $step->xp_reward : 0,
            'learn_score' => $newLearnScore,
            'learn_pct' => max(0, min(100, $learnPct)),
            'learn_completed' => $learnCompleted,
            'learn_accuracy' => $newLearnAccuracy,
            'already_completed' => $alreadyCompleted,
        ];
    }

    public function submitPlay(Request $request, string $slug)
    {
        $data = $request->validate([
            'challenge_id' => ['required', 'string', 'max:50'],
            'action' => ['required', 'string', 'max:2000'],
        ]);

        $module = DB::table('learning_modules')
            ->where('slug', $slug)
            ->where('is_active', true)
            ->select('id')
            ->first();
        abort_if(! $module, 404, 'Module not found.');

        $challenge = DB::table('play_challenges')
            ->where('id', $data['challenge_id'])
            ->where('learning_module_id', $module->id)
            ->select('id', 'type', 'title', 'config', 'xp_reward')
            ->first();
        abort_if(! $challenge, 404, 'Challenge not found.');

        $config = new \stdClass;
        if ($challenge->config) {
            $decoded = json_decode($challenge->config, true);
            if (is_array($decoded)) {
                $config = (object) $decoded;
            }
        }
        $score = 0;

        $action = trim($data['action']);

        if ($challenge->type === 'fill_blank') {
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
            $score = $correct ? (int) $challenge->xp_reward : 0;
        } elseif ($challenge->type === 'coding') {
            $steps = $config->steps ?? [];
            $userSteps = json_decode($action, true) ?: [];
            if (! is_array($userSteps) || count($userSteps) !== count($steps)) {
                $score = 0;
            } else {
                $correctCount = 0;
                foreach ($steps as $i => $expected) {
                    $u = $userSteps[$i] ?? [];
                    if (isset($expected['expected_mid']) && isset($u['mid'])) {
                        if ((int) $u['mid'] === (int) $expected['expected_mid']) {
                            $correctCount++;
                        }
                    }
                    if (isset($expected['expected_next']) && isset($u['next'])) {
                        if (trim(strtolower((string) $u['next'])) === trim(strtolower((string) $expected['expected_next']))) {
                            $correctCount++;
                        }
                    }
                }
                $totalChecks = count($steps) * 2;
                $ratio = $totalChecks > 0 ? $correctCount / $totalChecks : 0;
                $score = (int) round((int) $challenge->xp_reward * $ratio);
            }
        } elseif ($challenge->type === 'trace') {
            $decoded = json_decode($action, true);
            $movesUsed = is_array($decoded) ? ($decoded['moves'] ?? null) : null;
            $found = is_array($decoded) ? ($decoded['found'] ?? false) : false;

            if ($movesUsed === null || ! is_numeric($movesUsed) || (int) $movesUsed < 0) {
                $score = 0;
            } else {
                $movesUsed = (int) $movesUsed;
                $maxMoves = (int) ($config->max_moves ?? 4);
                $scoring = $config->scoring ?? [];

                if (! $found) {
                    $score = (int) ($scoring->more_than_two ?? 0);
                } elseif ($movesUsed <= $maxMoves) {
                    $score = (int) ($scoring->perfect ?? $challenge->xp_reward);
                } elseif ($movesUsed === $maxMoves + 1) {
                    $score = (int) ($scoring->one_extra_move ?? 0);
                } elseif ($movesUsed === $maxMoves + 2) {
                    $score = (int) ($scoring->two_extra_moves ?? 0);
                } else {
                    $score = (int) ($scoring->more_than_two ?? 0);
                }
            }
        } elseif ($challenge->type === 'binary_search') {
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

            $low = 0;
            $high = count($arr) - 1;
            $score = 0;
            $movesUsed = 0;
            $found = false;

            $correctSequence = [];
            $simLow = 0;
            $simHigh = count($arr) - 1;
            while ($simLow <= $simHigh && $movesUsed < $maxMoves) {
                $simMid = (int) floor(($simLow + $simHigh) / 2);
                if (! isset($arr[$simMid])) {
                    break;
                }
                if ($arr[$simMid] === $target) {
                    $correctSequence[] = 'found';
                    $found = true;
                    break;
                }
                $correctDir = $target > $arr[$simMid] ? 'right' : 'left';
                $correctSequence[] = $correctDir;
                if ($correctDir === 'left') {
                    $simHigh = $simMid - 1;
                } else {
                    $simLow = $simMid + 1;
                }
            }

            $allCorrect = true;
            foreach ($directions as $i => $dir) {
                if ($i >= count($correctSequence)) {
                    break;
                }
                $expected = $correctSequence[$i];
                $playerDir = trim(strtolower((string) $dir));

                if ($playerDir === $expected) {
                    $score += (int) ($scoring->correct_decision ?? 100);
                } else {
                    $score += (int) ($scoring->mistake ?? -25);
                    $allCorrect = false;
                }

                if ($expected === 'found') {
                    $found = true;
                    $movesUsed = $i + 1;
                    break;
                }

                if ($playerDir === 'left') {
                    $high = $mid - 1;
                } elseif ($playerDir === 'right') {
                    $low = $mid + 1;
                }

                $movesUsed = $i + 1;
            }

            if ($found) {
                $score += (int) ($scoring->found_bonus ?? 50);
                $extraMoves = max(0, $movesUsed - count($correctSequence));
                if ($extraMoves === 0 && $allCorrect) {
                    $score += (int) ($scoring->efficiency_bonus ?? 25);
                }
            }

            $score = max(0, $score);
            $score = min($score, (int) $challenge->xp_reward);
        } elseif ($challenge->type === 'midpoint_master') {
            $rounds = $config->rounds ?? [];
            $userRounds = json_decode($action, true) ?: [];
            $totalScore = 0;
            foreach ($rounds as $i => $round) {
                $expectedMid = (int) ($round['expected_mid'] ?? -1);
                $userMid = isset($userRounds[$i]) ? (int) $userRounds[$i] : null;
                if ($userMid === null) {
                    continue;
                }
                if ($userMid === $expectedMid) {
                    $totalScore += (int) ($config->correct_score ?? 100);
                } else {
                    $totalScore += (int) ($config->incorrect_score ?? -25);
                }
            }
            $score = max(0, $totalScore);
        } elseif ($challenge->type === 'trace_race') {
            $decoded = json_decode($action, true) ?: [];
            $moves = $decoded['moves'] ?? [];
            $found = (bool) ($decoded['found'] ?? false);

            $arr = $config->array ?? [];
            $target = $config->target ?? 0;
            $baseScore = (int) ($config->base_score ?? 1000);
            $penaltyMid = (int) ($config->penalty_incorrect_mid ?? 100);
            $penaltyDir = (int) ($config->penalty_wrong_direction ?? 100);
            $penaltyExtra = (int) ($config->penalty_extra_move ?? 50);

            if (empty($arr) || !$found) {
                $score = 0;
            } else {
                $score = $baseScore;

                $correctSequence = [];
                $simLow = 0;
                $simHigh = count($arr) - 1;
                while ($simLow <= $simHigh) {
                    $simMid = (int) floor(($simLow + $simHigh) / 2);
                    if ($arr[$simMid] === $target) {
                        $correctSequence[] = ['mid' => $simMid, 'dir' => 'found'];
                        break;
                    }
                    $correctDir = $target > $arr[$simMid] ? 'right' : 'left';
                    $correctSequence[] = ['mid' => $simMid, 'dir' => $correctDir];
                    if ($correctDir === 'left') {
                        $simHigh = $simMid - 1;
                    } else {
                        $simLow = $simMid + 1;
                    }
                }

                foreach ($moves as $i => $userMove) {
                    if (!is_array($userMove)) {
                        continue;
                    }

                    if ($i >= count($correctSequence)) {
                        break;
                    }

                    $correct = $correctSequence[$i];
                    $userMid = isset($userMove['mid']) ? (int) $userMove['mid'] : null;
                    $userDir = strtolower((string) ($userMove['dir'] ?? ''));

                    if ($userMid !== null && $userMid !== $correct['mid']) {
                        $score -= $penaltyMid;
                    }

                    if ($userDir !== '' && $userDir !== $correct['dir']) {
                        $score -= $penaltyDir;
                    }
                }

                $extraMoves = max(0, count($moves) - count($correctSequence));
                $score -= $extraMoves * $penaltyExtra;
            }

            $score = max(0, $score);
        } else {
            $scoring = $config->scoring ?? [];
            $score = (int) ($scoring->partial ?? 0);
        }

        $userId = $request->user()->id;

        $totalPlayXp = (int) DB::table('play_challenges')
            ->where('learning_module_id', $module->id)
            ->sum('xp_reward');

        $progress = DB::table('learning_progress')
            ->where('user_id', $userId)
            ->where('learning_module_id', $module->id)
            ->first();

        if (! $progress) {
            $progressId = 'lp_'.bin2hex(random_bytes(8));
            $playCompleted = $totalPlayXp > 0 && $score >= $totalPlayXp;
            $playAccuracy = $score > 0 ? 100 : 0;

            DB::table('learning_progress')->insert([
                'id' => $progressId,
                'user_id' => $userId,
                'learning_module_id' => $module->id,
                'learn_completed' => false,
                'play_completed' => $playCompleted,
                'prove_completed' => false,
                'learn_score' => 0,
                'play_score' => $score,
                'prove_score' => 0,
                'mastery_score' => $score,
                'attempts' => 1,
                'hints_used' => 0,
                'learn_accuracy' => null,
                'play_accuracy' => $playAccuracy,
                'prove_accuracy' => null,
                'concept_mastery' => $totalPlayXp > 0 ? (int) round(($score / $totalPlayXp) * 100) : 0,
                'weakness_signal' => null,
                'repeated_failed_concepts' => null,
                'learn_attempts' => 0,
                'play_attempts' => 1,
                'prove_attempts' => 0,
                'learn_correct' => 0,
                'play_correct' => $score > 0 ? 1 : 0,
                'prove_correct' => 0,
                'started_at' => now(),
                'completed_at' => $playCompleted ? now() : null,
                'created_at' => now(),
            ]);
        } else {
            $newPlayScore = max((int) ($progress->play_score ?? 0), $score);
            $playCompleted = $totalPlayXp > 0 && $newPlayScore >= $totalPlayXp;

            $newPlayAttempts = (int) ($progress->play_attempts ?? 0) + 1;
            $newPlayCorrect = $score > 0
                ? (int) ($progress->play_correct ?? 0) + 1
                : (int) ($progress->play_correct ?? 0);
            $newPlayAccuracy = $newPlayAttempts > 0
                ? (int) round(($newPlayCorrect / $newPlayAttempts) * 100)
                : 0;

            $playUpdate = [
                'play_score' => $newPlayScore,
                'play_completed' => $playCompleted,
                'play_attempts' => $newPlayAttempts,
                'play_correct' => $newPlayCorrect,
                'play_accuracy' => $newPlayAccuracy,
                'mastery_score' => (int) ($progress->learn_score ?? 0) + $newPlayScore + (int) ($progress->prove_score ?? 0),
            ];

            $learnCompleted = (bool) ($progress->learn_completed ?? false);
            $proveCompleted = (bool) ($progress->prove_completed ?? false);

            if ($learnCompleted && $playCompleted && $proveCompleted) {
                $playUpdate['completed_at'] = now();
            }

            DB::table('learning_progress')
                ->where('id', $progress->id)
                ->update($playUpdate);
        }

        $newPlayScore = $progress ? max((int) ($progress->play_score ?? 0), $score) : $score;
        $newPlayAttempts = $progress ? (int) ($progress->play_attempts ?? 0) + 1 : 1;
        $newPlayCorrect = $progress && $score > 0 ? (int) ($progress->play_correct ?? 0) + 1 : ($score > 0 ? 1 : 0);
        $newPlayAccuracy = $newPlayAttempts > 0
            ? (int) round(($newPlayCorrect / $newPlayAttempts) * 100)
            : 0;
        $playPct = $totalPlayXp > 0 ? (int) round(($newPlayScore / $totalPlayXp) * 100) : 0;

        return [
            'score' => $score,
            'challenge_id' => $challenge->id,
            'xp_reward' => $score,
            'play_score' => $newPlayScore,
            'play_pct' => max(0, min(100, $playPct)),
            'play_completed' => $playCompleted,
            'play_accuracy' => $newPlayAccuracy,
        ];
    }

    public function overallMastery(Request $request): array
    {
        $userId = $request->user()->id;

        $result = DB::table('learning_progress as lp')
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

        $overallMasteryPct = 0;
        if ($result && $result->total_possible_score > 0) {
            $overallMasteryPct = (int) round(($result->total_mastery_score / $result->total_possible_score) * 100);
        }

        return [
            'modules_started' => (int) ($result->modules_started ?? 0),
            'learn_completed' => (int) ($result->learn_completed ?? 0),
            'play_completed' => (int) ($result->play_completed ?? 0),
            'prove_completed' => (int) ($result->prove_completed ?? 0),
            'mastered' => (int) ($result->mastered ?? 0),
            'total_mastery_score' => (int) ($result->total_mastery_score ?? 0),
            'total_possible_score' => (int) ($result->total_possible_score ?? 0),
            'overall_mastery_pct' => max(0, min(100, $overallMasteryPct)),
            'avg_concept_mastery' => (int) ($result->avg_concept_mastery ?? 0),
            'avg_learn_accuracy' => (int) ($result->avg_learn_accuracy ?? 0),
            'avg_play_accuracy' => (int) ($result->avg_play_accuracy ?? 0),
            'avg_prove_accuracy' => (int) ($result->avg_prove_accuracy ?? 0),
            'total_learn_attempts' => (int) ($result->total_learn_attempts ?? 0),
            'total_play_attempts' => (int) ($result->total_play_attempts ?? 0),
            'total_prove_attempts' => (int) ($result->total_prove_attempts ?? 0),
        ];
    }

    public function provenCompletion(Request $request, string $slug)
    {
        $module = DB::table('learning_modules')
            ->where('slug', $slug)
            ->where('is_active', true)
            ->select('id', 'xp_reward')
            ->first();
        abort_if(! $module, 404, 'Module not found.');

        $problems = DB::table('learning_problems as lp')
            ->join('problems as p', 'p.id', '=', 'lp.problem_id')
            ->where('lp.learning_module_id', $module->id)
            ->orderBy('lp.stage')
            ->orderBy('lp.sort_order')
            ->select('lp.id', 'lp.problem_id')
            ->get();

        $userId = $request->user()->id;
        $service = $this->service();
        $allSolved = true;
        $solvedScore = 0;
        $repeatedFailed = [];

        foreach ($problems as $problem) {
            if ($service->hasSolved($userId, $problem->problem_id)) {
                $solvedScore++;
            } else {
                $allSolved = false;

                $failedCount = DB::table('submissions')
                    ->where('user_id', $userId)
                    ->where('problem_id', $problem->problem_id)
                    ->where('verdict', '!=', 'AC')
                    ->count();

                if ($failedCount > 2) {
                    $repeatedFailed[] = $problem->problem_id;
                }
            }
        }

        $totalProveProblems = count($problems);
        $solvedProveProblems = 0;
        foreach ($problems as $problem) {
            if ($service->hasSolved($userId, $problem->problem_id)) {
                $solvedProveProblems++;
            }
        }
        $proveAccuracy = $totalProveProblems > 0 ? (int) round(($solvedProveProblems / $totalProveProblems) * 100) : 0;

        $progress = DB::table('learning_progress')
            ->where('user_id', $userId)
            ->where('learning_module_id', $module->id)
            ->first();

        $now = now();

        if (! $progress) {
            $progressId = 'lp_'.bin2hex(random_bytes(8));
            DB::table('learning_progress')->insert([
                'id' => $progressId,
                'user_id' => $userId,
                'learning_module_id' => $module->id,
                'learn_completed' => false,
                'play_completed' => false,
                'prove_completed' => $allSolved,
                'learn_score' => 0,
                'play_score' => 0,
                'prove_score' => $solvedScore,
                'mastery_score' => $solvedScore,
                'attempts' => 1,
                'hints_used' => 0,
                'learn_accuracy' => null,
                'play_accuracy' => null,
                'prove_accuracy' => $proveAccuracy,
                'concept_mastery' => 0,
                'weakness_signal' => null,
                'repeated_failed_concepts' => $repeatedFailed ?: null,
                'learn_attempts' => 0,
                'play_attempts' => 0,
                'prove_attempts' => 1,
                'learn_correct' => 0,
                'play_correct' => 0,
                'prove_correct' => $solvedProveProblems,
                'started_at' => $now,
                'completed_at' => $allSolved ? $now : null,
                'created_at' => $now,
            ]);
        } else {
            $proveScore = max((int) ($progress->prove_score ?? 0), $solvedScore);
            $masteryScore = (int) ($progress->learn_score ?? 0) + (int) ($progress->play_score ?? 0) + $proveScore;

            $newProveAttempts = (int) ($progress->prove_attempts ?? 0) + 1;
            $newProveCorrect = $allSolved
                ? max((int) ($progress->prove_correct ?? 0), $totalProveProblems)
                : (int) ($progress->prove_correct ?? 0);
            $newProveAccuracy = $totalProveProblems > 0
                ? (int) round(($newProveCorrect / max(1, $newProveAttempts)) * 100)
                : 0;

            $totalLearnXp = (int) DB::table('learning_steps')
                ->where('learning_module_id', $module->id)
                ->whereNotNull('correct_answer')
                ->sum('xp_reward');

            $totalPlayXp = (int) DB::table('play_challenges')
                ->where('learning_module_id', $module->id)
                ->sum('xp_reward');

            $totalPossible = $totalLearnXp + $totalPlayXp + $solvedScore;
            $conceptMastery = $totalPossible > 0 ? (int) round(($masteryScore / $totalPossible) * 100) : 0;

            $weakness = PerformanceProfileCalculator::weaknessSignal(
                (int) ($progress->learn_accuracy ?? 0),
                (int) ($progress->play_accuracy ?? 0),
                $newProveAccuracy
            );

            $update = [
                'prove_score' => $proveScore,
                'prove_accuracy' => $newProveAccuracy,
                'prove_attempts' => $newProveAttempts,
                'prove_correct' => $newProveCorrect,
                'mastery_score' => $masteryScore,
                'concept_mastery' => $conceptMastery,
                'weakness_signal' => $weakness,
                'repeated_failed_concepts' => $repeatedFailed ?: null,
            ];

            if ($allSolved) {
                $update['prove_completed'] = true;
            }

            $learnCompleted = (bool) ($progress->learn_completed ?? false);
            $playCompleted = (bool) ($progress->play_completed ?? false);

            if ($allSolved && $learnCompleted && $playCompleted) {
                $update['completed_at'] = $now;
            }

            DB::table('learning_progress')
                ->where('id', $progress->id)
                ->update($update);
        }

        $totalProveXp = (int) DB::table('learning_problems as lp')
            ->join('problems as p', 'p.id', '=', 'lp.problem_id')
            ->where('lp.learning_module_id', $module->id)
            ->sum(DB::raw('0'));

        $proveScore = $progress ? max((int) ($progress->prove_score ?? 0), $solvedScore) : $solvedScore;
        $masteryScore = (int) ($progress->learn_score ?? 0) + (int) ($progress->play_score ?? 0) + $proveScore;
        $totalXp = (int) ($progress->learn_score ?? 0) + (int) ($progress->play_score ?? 0) + $totalProveXp;

        $learnPct = $totalLearnXp > 0 ? (int) round((int) ($progress->learn_score ?? 0) / $totalLearnXp * 100) : 0;
        $playPct = $totalPlayXp > 0 ? (int) round((int) ($progress->play_score ?? 0) / $totalPlayXp * 100) : 0;
        $provePct = max(0, min(100, $proveAccuracy));
        $masteryPct = max(0, min(100, (int) round(($learnPct + $playPct + $provePct) / 3)));

        $moduleCompleted = $allSolved && $progress && (bool) ($progress->learn_completed ?? false) && (bool) ($progress->play_completed ?? false);

        return [
            'message' => $allSolved ? 'Prove stage completed.' : 'Prove progress updated.',
            'prove_completed' => $allSolved,
            'prove_score' => $solvedScore,
            'prove_pct' => max(0, min(100, $provePct)),
            'prove_accuracy' => $proveAccuracy,
            'mastery_score' => $masteryScore,
            'mastery_pct' => max(0, min(100, $masteryPct)),
            'concept_mastery' => $progress ? (int) ($progress->concept_mastery ?? 0) : 0,
            'weakness_signal' => $progress ? ($progress->weakness_signal ?? null) : null,
            'repeated_failed_concepts' => $repeatedFailed ?: null,
            'module_completed' => $moduleCompleted,
        ];
    }
}

