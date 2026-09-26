<?php

namespace App\Http\Controllers;

use App\Services\PlayGameService;
use App\Services\ProblemPracticeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LearningController extends Controller
{
    private function service(): ProblemPracticeService
    {
        return new ProblemPracticeService(DB::connection()->getPdo());
    }

    private function jsonArray(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (! is_string($value) || $value === '') {
            return [];
        }

        $decoded = json_decode($value, true);
        return is_array($decoded) ? $decoded : [];
    }

    private function weaknessSignal(int $learn, int $play, int $prove): ?string
    {
        $scores = ['learn' => $learn, 'play' => $play, 'prove' => $prove];
        $lowest = min($scores);

        if ($lowest >= 70) {
            return null;
        }

        return array_search($lowest, $scores, true) ?: null;
    }

    private function isPlayCompleted(array $allChallengeIds, array $completedChallenges): bool
    {
        if (empty($allChallengeIds)) {
            return false;
        }

        foreach ($allChallengeIds as $challengeId) {
            $entry = $completedChallenges[$challengeId] ?? [];
            if (empty($entry['completed'])) {
                return false;
            }
        }

        return true;
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
                    'learn_completed_steps' => $progress ? $this->jsonArray($progress->learn_completed_steps ?? null) : [],
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

        $userId = $request->user()->id;
        $progress = DB::table('learning_progress')
            ->where('user_id', $userId)
            ->where('learning_module_id', $moduleId)
            ->first();

        $challengesQuery = DB::table('play_challenges as c')
            ->where('c.learning_module_id', $moduleId)
            ->orderBy('c.id')
            ->select('c.id', 'c.type', 'c.title', 'c.instructions', 'c.config', 'c.xp_reward', 'c.time_limit');

        $isBSModule = $slug === 'binary-search-basics';
        if ($isBSModule) {
            $challengesQuery->whereIn('c.type', ['binary_search', 'midpoint_master', 'trace_race', 'half_hunt']);
        }

        $challenges = $challengesQuery->get();

        $bsProgression = [
            'half_hunt' => false,
            'midpoint_master' => false,
            'trace_race' => false,
        ];

        if ($isBSModule && $progress) {
            $completedChallenges = $this->jsonArray($progress->play_completed_challenges ?? null);
            foreach ($challenges as $c) {
                $entry = $completedChallenges[$c->id] ?? [];
                if (! empty($entry['completed'])) {
                    if ($c->type === 'half_hunt') {
                        $bsProgression['half_hunt'] = true;
                    }
                    if ($c->type === 'midpoint_master') {
                        $bsProgression['midpoint_master'] = true;
                    }
                    if ($c->type === 'trace_race') {
                        $bsProgression['trace_race'] = true;
                    }
                }
            }
        }

        $challenges = $challenges->map(function ($c) use ($progress, $isBSModule, $bsProgression) {
            $config = [];
            if ($c->config) {
                $decoded = json_decode($c->config, true);
                if (is_array($decoded)) {
                    $config = $decoded;
                }
            }
            $c->config = $config;

            if ($c->type === 'half_hunt' && (! isset($config['rounds']) || empty($config['rounds']))) {
                $gameService = new PlayGameService;
                $configObj = (object) $config;
                $config['rounds'] = $gameService->generateHalfHuntRounds($configObj);
                $c->config = $config;
            }

            if ($c->type === 'trace_race' && (! isset($config['array']) || empty($config['array']))) {
                $gameService = new PlayGameService;
                $configObj = (object) $config;
                $generated = $gameService->generateTraceRaceConfig($configObj);
                $config['array'] = $generated['array'];
                $config['target'] = $generated['target'];
                $config['min_moves'] = $generated['min_moves'];
                $c->config = $config;
            }

            $completedChallenges = $progress ? $this->jsonArray($progress->play_completed_challenges ?? null) : [];
            $entry = $completedChallenges[$c->id] ?? [];
            $c->completed = (bool) ($entry['completed'] ?? false);
            $c->best_score = (int) ($entry['best_score'] ?? 0);
            $c->attempts = (int) ($entry['attempts'] ?? 0);

            if ($isBSModule) {
                if ($c->type === 'half_hunt') {
                    $c->unlocked = true;
                } elseif ($c->type === 'midpoint_master') {
                    $c->unlocked = $bsProgression['half_hunt'];
                } elseif ($c->type === 'trace_race') {
                    $c->unlocked = $bsProgression['midpoint_master'];
                } else {
                    $c->unlocked = true;
                }
            } else {
                $c->unlocked = true;
            }

            return $c;
        });

        $problems = DB::table('learning_problems as lp')
            ->join('problems as p', 'p.id', '=', 'lp.problem_id')
            ->where('lp.learning_module_id', $moduleId)
            ->orderBy('lp.stage')
            ->orderBy('lp.sort_order')
            ->select('lp.id', 'lp.stage', 'lp.sort_order', 'p.id as problem_id', 'p.title', 'p.topic', 'p.difficulty', 'p.tags')
            ->get();

        $totalLearnXp = (int) DB::table('learning_steps')
            ->where('learning_module_id', $moduleId)
            ->whereNotNull('correct_answer')
            ->sum('xp_reward');

        $totalPlayXp = (int) DB::table('play_challenges')
            ->where('learning_module_id', $moduleId)
            ->when($isBSModule, function ($q) {
                $q->whereIn('type', ['binary_search', 'midpoint_master', 'trace_race', 'half_hunt']);
            })
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

        $playAccuracy = $progress ? max(0, min(100, (int) ($progress->play_accuracy ?? 0))) : 0;

        $gamesTotal = 0;
        $gamesCompleted = 0;
        $proveUnlocked = false;
        if ($isBSModule) {
            $gamesTotal = 3;
            if ($bsProgression['half_hunt']) {
                $gamesCompleted++;
            }
            if ($bsProgression['midpoint_master']) {
                $gamesCompleted++;
            }
            if ($bsProgression['trace_race']) {
                $gamesCompleted++;
            }
            $proveUnlocked = $gamesCompleted === $gamesTotal;
        }

        $playCompleted = $isBSModule
            ? ($gamesCompleted === $gamesTotal && $gamesTotal > 0)
            : ($progress && (bool) ($progress->play_completed ?? false));

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
            'learn_completed_steps' => $progress ? $this->jsonArray($progress->learn_completed_steps ?? null) : [],
            'play_completed_challenges' => $progress ? $this->jsonArray($progress->play_completed_challenges ?? null) : [],
            'play_score' => $playScore,
            'play_accuracy' => $playAccuracy,
            'games_completed' => $gamesCompleted,
            'games_total' => $gamesTotal,
            'play_completed' => $playCompleted,
            'prove_unlocked' => $proveUnlocked,
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

        $completedSteps = $progress ? $this->jsonArray($progress->learn_completed_steps ?? null) : [];
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
                'learn_completed_steps' => $completedSteps ? json_encode(array_values($completedSteps)) : null,
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
                'learn_completed_steps' => $completedSteps ? json_encode(array_values($completedSteps)) : null,
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

        $moduleId = $module->id;

        $challenge = DB::table('play_challenges')
            ->where('id', $data['challenge_id'])
            ->where('learning_module_id', $module->id)
            ->select('id', 'type', 'title', 'config', 'xp_reward')
            ->first();
        abort_if(! $challenge, 404, 'Challenge not found.');

        $isBSModule = $slug === 'binary-search-basics';
        if ($isBSModule) {
            $userId = $request->user()->id;
            $progress = DB::table('learning_progress')
                ->where('user_id', $userId)
                ->where('learning_module_id', $module->id)
                ->first();

            $completedChallenges = $progress ? $this->jsonArray($progress->play_completed_challenges ?? null) : [];
            $halfHuntIds = DB::table('play_challenges')
                ->where('learning_module_id', $module->id)
                ->where('type', 'half_hunt')
                ->pluck('id')
                ->all();
            $midpointMasterIds = DB::table('play_challenges')
                ->where('learning_module_id', $module->id)
                ->where('type', 'midpoint_master')
                ->pluck('id')
                ->all();
            $traceRaceIds = DB::table('play_challenges')
                ->where('learning_module_id', $module->id)
                ->where('type', 'trace_race')
                ->pluck('id')
                ->all();

            $halfHuntCompleted = false;
            foreach ($halfHuntIds as $hid) {
                $entry = $completedChallenges[$hid] ?? [];
                if (! empty($entry['completed'])) {
                    $halfHuntCompleted = true;
                    break;
                }
            }

            $midpointMasterCompleted = false;
            foreach ($midpointMasterIds as $mid) {
                $entry = $completedChallenges[$mid] ?? [];
                if (! empty($entry['completed'])) {
                    $midpointMasterCompleted = true;
                    break;
                }
            }

            $unlocked = true;
            if ($challenge->type === 'midpoint_master') {
                $unlocked = $halfHuntCompleted;
            } elseif ($challenge->type === 'trace_race') {
                $unlocked = $midpointMasterCompleted;
            }

            if (! $unlocked) {
                abort(403, 'This challenge is locked. Complete the previous challenge first.');
            }
        }

        $config = new \stdClass;
        if ($challenge->config) {
            $decoded = json_decode($challenge->config, true);
            if (is_array($decoded)) {
                $config = (object) $decoded;
            }
        }

        $action = trim($data['action']);
        $gameResult = (new PlayGameService)->validate($challenge->type, $action, $config, $challenge->xp_reward);
        $score = (int) ($gameResult['score'] ?? 0);

        $userId = $request->user()->id;

        $totalPlayXp = (int) DB::table('play_challenges')
            ->where('learning_module_id', $moduleId)
            ->when($isBSModule, function ($q) {
                $q->whereIn('type', ['binary_search', 'midpoint_master', 'trace_race', 'half_hunt']);
            })
            ->sum('xp_reward');

        $progress = DB::table('learning_progress')
            ->where('user_id', $userId)
            ->where('learning_module_id', $module->id)
            ->first();

        $completedChallenges = $progress ? $this->jsonArray($progress->play_completed_challenges ?? null) : [];
        $existing = $completedChallenges[$challenge->id] ?? [];
        $existingBest = (int) ($existing['best_score'] ?? 0);
        $existingCompleted = (bool) ($existing['completed'] ?? false);
        $existingAttempts = (int) ($existing['attempts'] ?? 0);
        $newAttempts = $existingAttempts + 1;
        $newCompleted = $existingCompleted || (bool) ($gameResult['completed'] ?? false);

        if ($challenge->type === 'half_hunt') {
            $existingRounds = $existing['rounds'] ?? [];
            $roundScore = max(0, $score);
            $roundIndex = (int) ($gameResult['round_index'] ?? 0);
            $newRounds = $existingRounds;
            if ($roundIndex >= count($newRounds)) {
                $newRounds[] = $roundScore;
            } else {
                $newRounds[$roundIndex] = $roundScore;
            }
            $newBest = array_sum($newRounds);

            $completedChallenges[$challenge->id] = [
                'score' => $newBest,
                'attempts' => $newAttempts,
                'best_score' => $newBest,
                'max_score' => (int) ($gameResult['max_score'] ?? $challenge->xp_reward),
                'completed' => $newCompleted,
                'accuracy' => (int) ($gameResult['accuracy'] ?? 0),
                'rounds' => $newRounds,
            ];
        } else {
            $newBest = max($existingBest, $score);
            $completedChallenges[$challenge->id] = [
                'score' => $score,
                'attempts' => $newAttempts,
                'best_score' => $newBest,
                'max_score' => (int) ($gameResult['max_score'] ?? $challenge->xp_reward),
                'completed' => $newCompleted,
                'accuracy' => (int) ($gameResult['accuracy'] ?? 0),
            ];
        }
        $completedChallengesJson = json_encode($completedChallenges);

        $allChallengeIds = DB::table('play_challenges')
            ->where('learning_module_id', $module->id)
            ->when($isBSModule, function ($q) {
                $q->whereIn('type', ['binary_search', 'midpoint_master', 'trace_race', 'half_hunt']);
            })
            ->pluck('id')
            ->all();

        $bsProgression = [
            'half_hunt' => false,
            'midpoint_master' => false,
            'trace_race' => false,
        ];
        if ($isBSModule) {
            foreach ($completedChallenges as $cid => $entry) {
                if (! empty($entry['completed'])) {
                    $cType = DB::table('play_challenges')->where('id', $cid)->value('type');
                    if ($cType === 'half_hunt') {
                        $bsProgression['half_hunt'] = true;
                    }
                    if ($cType === 'midpoint_master') {
                        $bsProgression['midpoint_master'] = true;
                    }
                    if ($cType === 'trace_race') {
                        $bsProgression['trace_race'] = true;
                    }
                }
            }
        }

        if ($isBSModule) {
            $gamesTotal = 3;
            $gamesCompleted = 0;
            if ($bsProgression['half_hunt']) {
                $gamesCompleted++;
            }
            if ($bsProgression['midpoint_master']) {
                $gamesCompleted++;
            }
            if ($bsProgression['trace_race']) {
                $gamesCompleted++;
            }
            $playCompleted = $gamesCompleted === $gamesTotal && $gamesTotal > 0;
        } else {
            $playCompleted = $this->isPlayCompleted($allChallengeIds, $completedChallenges);
        }

        $playAccuracy = $score > 0 ? 100 : 0;
        $isNewlyCompleted = ! $existingCompleted && $newCompleted;

        if (! $progress) {
            $progressId = 'lp_'.bin2hex(random_bytes(8));

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
                'play_correct' => $isNewlyCompleted ? 1 : 0,
                'prove_correct' => 0,
                'play_completed_challenges' => $completedChallengesJson,
                'started_at' => now(),
                'completed_at' => $playCompleted ? now() : null,
                'created_at' => now(),
            ]);
        } else {
            $halfHuntTotal = 0;
            if ($challenge->type === 'half_hunt') {
                foreach ($completedChallenges as $entry) {
                    if (isset($entry['rounds']) && is_array($entry['rounds'])) {
                        $halfHuntTotal += array_sum($entry['rounds']);
                    }
                }
                $newPlayScore = max((int) ($progress->play_score ?? 0), $halfHuntTotal);
            } else {
                $newPlayScore = max((int) ($progress->play_score ?? 0), $score);
            }
            $newPlayAttempts = (int) ($progress->play_attempts ?? 0) + 1;
            $isNewlyCompleted = ! $existingCompleted && $newCompleted;
            $newPlayCorrect = (int) ($progress->play_correct ?? 0) + ($isNewlyCompleted ? 1 : 0);
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
                'play_completed_challenges' => $completedChallengesJson,
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
        if ($challenge->type === 'half_hunt') {
            $halfHuntTotal = 0;
            foreach ($completedChallenges as $entry) {
                if (isset($entry['rounds']) && is_array($entry['rounds'])) {
                    $halfHuntTotal += array_sum($entry['rounds']);
                }
            }
            $newPlayScore = max((int) ($progress->play_score ?? 0), $halfHuntTotal);
        }
        $newPlayAttempts = $progress ? (int) ($progress->play_attempts ?? 0) + 1 : 1;
        $newPlayCorrect = $progress ? (int) ($progress->play_correct ?? 0) + ($isNewlyCompleted ? 1 : 0) : ($isNewlyCompleted ? 1 : 0);
        $newPlayAccuracy = $newPlayAttempts > 0
            ? (int) round(($newPlayCorrect / $newPlayAttempts) * 100)
            : 0;
        $playPct = $totalPlayXp > 0 ? (int) round(($newPlayScore / $totalPlayXp) * 100) : 0;

        return [
            'score' => $score,
            'challenge_id' => $challenge->id,
            'xp_reward' => $isNewlyCompleted ? $score : 0,
            'play_score' => $newPlayScore,
            'play_pct' => max(0, min(100, $playPct)),
            'play_completed' => $playCompleted,
            'play_accuracy' => $newPlayAccuracy,
            'max_score' => (int) ($gameResult['max_score'] ?? $challenge->xp_reward),
            'completed' => (bool) ($gameResult['completed'] ?? false),
            'accuracy' => (int) ($gameResult['accuracy'] ?? 0),
            'attempts' => (int) ($gameResult['attempts'] ?? 0),
            'moves' => isset($gameResult['moves']) ? (int) $gameResult['moves'] : null,
            'mistakes' => isset($gameResult['mistakes']) ? (int) $gameResult['mistakes'] : null,
            'play_completed_challenges' => $completedChallenges,
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
                'repeated_failed_concepts' => $repeatedFailed ? json_encode(array_values($repeatedFailed)) : null,
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

            $weakness = $this->weaknessSignal(
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
                'repeated_failed_concepts' => $repeatedFailed ? json_encode(array_values($repeatedFailed)) : null,
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
            'repeated_failed_concepts' => $repeatedFailed ? json_encode(array_values($repeatedFailed)) : null,
            'module_completed' => $moduleCompleted,
        ];
    }
}
