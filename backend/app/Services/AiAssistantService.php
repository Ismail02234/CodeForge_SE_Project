<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

final class AiAssistantService
{
    private ?string $lastProvider = null;
    private ?string $lastModel = null;

    public function __construct(
        private readonly ProblemRecommendationService $recommendations,
        private readonly PerformanceProfileService $performanceProfile,
    ) {
    }

    public function respond(
        User $user,
        string $message,
        string $currentPath = '',
        array $history = []
    ): array {
        $message = trim($message);
        $this->lastProvider = null;
        $this->lastModel = null;

        if ($message === '') {
            return $this->response(
                'Tell me what you want to do in CodeForge.',
                null,
                $this->defaultSuggestions(),
                'local'
            );
        }

        if ($this->isDirectCommand($message)) {
            $local = $this->resolveLocalIntent($user, $message);

            if ($local !== null) {
                return $local;
            }
        }

        if (! (bool) config('ai_assistant.enabled', true)) {
            $local = $this->resolveLocalIntent($user, $message);

            return $local ?? $this->fallbackUnavailable();
        }

        try {
            $cacheKey = 'codeforge:copilot:decision:'.sha1(
                (string) $user->id.'|'.$currentPath.'|'.$message
            );

            $decision = Cache::get($cacheKey);

            if (is_array($decision)) {
                $this->lastProvider = 'cache';
                $this->lastModel = (string) config('ai_assistant.model', 'openai/gpt-oss-120b');
            } else {
                $decision = $this->askModel($user, $message, $currentPath, $history);

                if (is_array($decision)) {
                    Cache::put($cacheKey, $decision, 300);
                }
            }

            if ($decision !== null) {
                return $this->executeModelDecision($user, $decision);
            }
        } catch (Throwable $exception) {
            Log::warning('CodeForge AI assistant provider call failed.', [
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);
        }

        $local = $this->resolveLocalIntent($user, $message);
        if ($local !== null) {
            return $local;
        }

        return $this->response(
            'The AI provider is temporarily unavailable. CodeForge itself is still working, and I can continue to handle direct navigation and built-in practice/performance commands.',
            null,
            $this->defaultSuggestions(),
            'local'
        );
    }

    private function resolveLocalIntent(User $user, string $message): ?array
    {
        $normalized = $this->normalize($message);

        $practice = $this->practiceProblemsResponse($user, $message);
        if ($practice !== null) {
            return $practice;
        }

        if ($this->looksLikeHelp($normalized)) {
            return $this->response(
                'I can navigate CodeForge, search users/problems/universities, open a user profile, compare you with another coder, open a problem or contest, identify your weakest practice field, and summarize your Performance Profile.',
                null,
                $this->defaultSuggestions(),
                'local'
            );
        }

        if ($this->isMyProfileCommand($normalized)) {
            return $this->response(
                'Opening your profile.',
                $this->navigationAction('/profile/'.rawurlencode((string) $user->id), 'Open profile'),
                ['Open Performance Profile', 'What should I practice?'],
                'local'
            );
        }

        $featureInfo = $this->featureInfoResponse($user, $normalized);
        if ($featureInfo !== null) {
            return $featureInfo;
        }

        if (preg_match('/\b(?:compare(?:\s+me)?\s+(?:with|to)|versus|vs)\s+([a-z0-9_.-]{2,50})\b/i', $message, $match)) {
            return $this->compareWithUser($user, (string) $match[1]);
        }

        if (preg_match('/\b(?:profile\s+of|open\s+profile(?:\s+of)?|show\s+profile(?:\s+of)?)\s+([a-z0-9_.-]{2,50})\b/i', $message, $match)) {
            return $this->openUserProfile((string) $match[1]);
        }

        if (preg_match('/\b(?:open|find|show|go to|take me to)\s+(?:the\s+)?problem\s+(.+)$/i', $message, $match)) {
            return $this->openProblem(trim((string) $match[1]));
        }

        if (preg_match('/\b(?:open|find|show|go to|take me to)\s+(?:the\s+)?contest\s+(.+)$/i', $message, $match)) {
            return $this->openContest(trim((string) $match[1]));
        }

        $directPage = $this->findDirectPage($user, $normalized);
        if ($directPage !== null) {
            return $this->response(
                'Opening '.$directPage['label'].'.',
                $this->navigationAction($directPage['path'], 'Open '.$directPage['label']),
                $this->suggestionsForPage($directPage['key']),
                'local'
            );
        }

        if ($this->looksLikeWeaknessRequest($normalized)) {
            return $this->weaknessResponse($user, $normalized);
        }

        if ($this->looksLikePerformanceRequest($normalized)) {
            return $this->performanceResponse($user);
        }

        if (preg_match('/^(?:search(?:\s+for)?|find)\s+(.+)$/i', $message, $match)) {
            $query = trim((string) $match[1]);
            if (mb_strlen($query) >= 2) {
                return $this->response(
                    'Searching CodeForge for "'.$query.'".',
                    $this->navigationAction('/search?q='.rawurlencode($query), 'Search CodeForge'),
                    ['Open Problems', 'Open Universities'],
                    'local'
                );
            }
        }

        return null;
    }

    private function askModel(
        User $user,
        string $message,
        string $currentPath,
        array $history
    ): ?array {
        $primary = mb_strtolower(trim((string) config('ai_assistant.provider', 'groq')));
        $fallback = mb_strtolower(trim((string) config('ai_assistant.fallback_provider', 'gemini')));

        $providers = array_values(array_unique(array_filter([$primary, $fallback])));

        foreach ($providers as $provider) {
            $apiKey = $this->providerApiKey($provider);
            if ($apiKey === '') {
                continue;
            }

            $model = $this->providerModel($provider, $provider === $primary);

            $decision = match ($provider) {
                'groq' => $this->askGroqModel(
                    $user,
                    $message,
                    $currentPath,
                    $history,
                    $apiKey,
                    $model
                ),
                'deepseek' => $this->askDeepSeekModel(
                    $user,
                    $message,
                    $currentPath,
                    $history,
                    $apiKey,
                    $model
                ),
                'gemini' => $this->askGeminiModel(
                    $user,
                    $message,
                    $currentPath,
                    $history,
                    $apiKey,
                    $model
                ),
                default => null,
            };

            if ($decision !== null) {
                $this->lastProvider = $provider;
                if ($this->lastModel === null) {
                    $this->lastModel = $model;
                }

                return $decision;
            }
        }

        return null;
    }

    private function executeModelDecision(User $user, array $decision): array
    {
        $intent = (string) ($decision['intent'] ?? 'none');
        $target = trim((string) ($decision['target'] ?? ''));
        $reply = trim((string) ($decision['reply'] ?? ''));
        $suggestions = $this->cleanSuggestions((array) ($decision['suggestions'] ?? []));

        return match ($intent) {
            'navigate' => $this->modelNavigationResponse($user, (string) ($decision['page'] ?? ''), $reply, $suggestions),
            'search' => $target !== ''
                ? $this->response(
                    $reply !== '' ? $reply : 'Searching CodeForge.',
                    $this->navigationAction('/search?q='.rawurlencode($target), 'Search CodeForge'),
                    $suggestions,
                    'ai'
                )
                : $this->response($reply ?: 'What should I search for?', null, $suggestions, 'ai'),
            'profile' => $target !== '' ? $this->openUserProfile($target, 'ai') : $this->response($reply ?: 'Which user profile?', null, $suggestions, 'ai'),
            'compare' => $target !== '' ? $this->compareWithUser($user, $target, 'ai') : $this->response($reply ?: 'Who should I compare you with?', null, $suggestions, 'ai'),
            'problem' => $target !== '' ? $this->openProblem($target, 'ai') : $this->response($reply ?: 'Which problem should I open?', null, $suggestions, 'ai'),
            'contest' => $target !== '' ? $this->openContest($target, 'ai') : $this->response($reply ?: 'Which contest should I open?', null, $suggestions, 'ai'),
            'weakest' => $this->weaknessResponse($user, $this->normalize($target), 'ai'),
            'performance' => $this->performanceResponse($user, 'ai'),
            'coach' => $this->response(
                $reply !== '' ? $reply : 'I can help you build a practical improvement plan from your CodeForge activity.',
                null,
                $suggestions ?: ['What should I practice?', 'Open Performance Profile', 'Open Problems'],
                'ai'
            ),
            default => $this->response(
                $reply !== '' ? $reply : 'I can help you navigate CodeForge or inspect your coding data.',
                null,
                $suggestions ?: $this->defaultSuggestions(),
                'ai'
            ),
        };
    }

    private function modelNavigationResponse(User $user, string $page, string $reply, array $suggestions): array
    {
        $routes = $this->availableRoutesFor($user);
        if (! isset($routes[$page])) {
            return $this->response(
                $reply !== '' ? $reply : 'That destination is not available for your account.',
                null,
                $suggestions ?: $this->defaultSuggestions(),
                'ai'
            );
        }

        $route = $routes[$page];

        return $this->response(
            $reply !== '' ? $reply : 'Opening '.$route['label'].'.',
            $this->navigationAction($route['path'], 'Open '.$route['label']),
            $suggestions ?: $this->suggestionsForPage($page),
            'ai'
        );
    }

    private function compareWithUser(User $user, string $username, string $source = 'local'): array
    {
        $match = $this->findUser($username);

        if ($match === null) {
            return $this->response(
                'I could not find a coder named "'.$username.'". I opened search so you can choose the right result.',
                $this->navigationAction('/search?q='.rawurlencode($username), 'Search users'),
                ['Open Rivalry', 'Open Search', 'Open Performance Profile'],
                $source
            );
        }

        if ((string) $match->id === (string) $user->id) {
            return $this->response(
                'Choose another coder to compare with yourself.',
                $this->navigationAction('/rivalry', 'Open Rivalry'),
                ['Compare me with ismail', 'Open Performance Profile'],
                $source
            );
        }

        $path = '/rivalry?a='.rawurlencode((string) $user->id).'&b='.rawurlencode((string) $match->id);

        return $this->response(
            'Opening your Rivalry comparison with '.$match->username.'.',
            $this->navigationAction($path, 'Compare with '.$match->username),
            ['Open Performance Profile', 'Compare with another coder'],
            $source
        );
    }

    private function openUserProfile(string $username, string $source = 'local'): array
    {
        $match = $this->findUser($username);

        if ($match === null) {
            return $this->response(
                'I could not find a coder named "'.$username.'".',
                $this->navigationAction('/search?q='.rawurlencode($username), 'Search users'),
                ['Open Search', 'Open Rivalry', 'Open Performance Profile'],
                $source
            );
        }

        return $this->response(
            "Opening {$match->username}'s profile.",
            $this->navigationAction('/profile/'.rawurlencode((string) $match->id), 'Open '.$match->username),
            ['Compare me with '.$match->username, 'Open Performance Profile', 'Open Problems'],
            $source
        );
    }

    private function openProblem(string $query, string $source = 'local'): array
    {
        $query = $this->cleanLookup($query);
        if ($query === '') {
            return $this->response('Tell me the problem name or ID.', null, ['Open Problems'], $source);
        }

        $problem = DB::table('problems')
            ->where('id', $query)
            ->orWhereRaw('LOWER(title) = ?', [mb_strtolower($query)])
            ->first(['id', 'title']);

        if (! $problem) {
            $problem = DB::table('problems')
                ->where('title', 'like', '%'.$this->escapeLike($query).'%')
                ->orderBy('title')
                ->first(['id', 'title']);
        }

        if (! $problem) {
            return $this->response(
                'I could not match that problem exactly, so I opened search.',
                $this->navigationAction('/search?q='.rawurlencode($query), 'Search problems'),
                ['Open Problems', 'What should I practice?'],
                $source
            );
        }

        return $this->response(
            'Opening "'.$problem->title.'".',
            $this->navigationAction('/problems/'.rawurlencode((string) $problem->id), 'Open problem'),
            ['What should I practice?', 'Open Problems'],
            $source
        );
    }

    private function openContest(string $query, string $source = 'local'): array
    {
        $query = $this->cleanLookup($query);
        if ($query === '') {
            return $this->response('Tell me the contest name or ID.', null, ['Open Contests'], $source);
        }

        $contest = DB::table('contests')
            ->where('id', $query)
            ->orWhereRaw('LOWER(name) = ?', [mb_strtolower($query)])
            ->first(['id', 'name']);

        if (! $contest) {
            $contest = DB::table('contests')
                ->where('name', 'like', '%'.$this->escapeLike($query).'%')
                ->orderByDesc('starts_at')
                ->first(['id', 'name']);
        }

        if (! $contest) {
            return $this->response(
                'I could not find that contest.',
                $this->navigationAction('/contests', 'Open Contests'),
                ['Open Contests', 'Open Rivalry'],
                $source
            );
        }

        return $this->response(
            'Opening "'.$contest->name.'".',
            $this->navigationAction('/contests/'.rawurlencode((string) $contest->id), 'Open contest'),
            ['Open Contests', 'Open Rivalry'],
            $source
        );
    }

    private function practiceProblemsResponse(User $user, string $message): ?array
    {
        $normalized = $this->normalize($message);

        $mentionsProblems = preg_match('/\bproblems?\b/i', $message) === 1;
        $asksForPractice = preg_match('/\b(practice|recommend|recommendation|recommendations|suggest|suggestion|suggestions|list|give me|show me|specific)\b/i', $message) === 1;

        if (! $mentionsProblems || ! $asksForPractice) {
            return null;
        }

        try {
            $topics = DB::table('problems')
                ->whereNotNull('topic')
                ->where('topic', '<>', '')
                ->distinct()
                ->orderBy('topic')
                ->pluck('topic')
                ->map(fn ($topic) => trim((string) $topic))
                ->filter()
                ->values()
                ->all();

            $matchedTopic = null;
            $bestLength = 0;

            foreach ($topics as $topic) {
                $topicNormalized = $this->normalize($topic);
                if ($topicNormalized !== '' && str_contains($normalized, $topicNormalized)) {
                    $length = mb_strlen($topicNormalized);
                    if ($length > $bestLength) {
                        $matchedTopic = $topic;
                        $bestLength = $length;
                    }
                }
            }

            if ($matchedTopic === null) {
                if (preg_match('/\b(?:give me|show me|list|recommend|suggest)\b/i', $message) === 1) {
                    return $this->weaknessResponse($user, $normalized, 'local');
                }

                return null;
            }

            $problems = DB::table('problems as p')
                ->whereRaw('LOWER(p.topic) = ?', [mb_strtolower($matchedTopic)])
                ->whereNotExists(function ($query) use ($user): void {
                    $query->selectRaw('1')
                        ->from('submissions as s')
                        ->whereColumn('s.problem_id', 'p.id')
                        ->where('s.user_id', (string) $user->id)
                        ->whereRaw("UPPER(COALESCE(s.verdict, '')) = 'AC'");
                })
                ->orderByRaw("FIELD(p.difficulty, 'Easy', 'Medium', 'Hard')")
                ->orderBy('p.title')
                ->limit(6)
                ->get(['p.id', 'p.title', 'p.difficulty']);

            if ($problems->isEmpty()) {
                return $this->response(
                    'I could not find any unsolved '.$matchedTopic.' problems for you in CodeForge right now.',
                    $this->navigationAction('/problems', 'Open Problems'),
                    ['Open Problems', 'What should I practice?', 'Show my performance'],
                    'local'
                );
            }

            $lines = [];
            foreach ($problems as $index => $problem) {
                $difficulty = trim((string) ($problem->difficulty ?? ''));
                $suffix = $difficulty !== '' ? ' ('.$difficulty.')' : '';
                $lines[] = ($index + 1).'. '.(string) $problem->title.$suffix;
            }

            return $this->response(
                $matchedTopic." problems to practice from CodeForge:\n".implode("\n", $lines),
                $this->navigationAction('/problems', 'Open Problems'),
                ['Open Problems', 'What should I practice?', 'Show my performance'],
                'local'
            );
        } catch (Throwable $exception) {
            Log::warning('CodeForge AI assistant could not load topic practice problems.', [
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    private function weaknessResponse(User $user, string $normalizedMessage = '', string $source = 'local'): array
    {
        try {
            $data = $this->recommendations->forUser((string) $user->id, 3);
            $weakest = $data['weakest'] ?? null;

            if (! is_array($weakest) || empty($weakest['topic'])) {
                return $this->response(
                    'There is not enough submission history yet to identify a reliable weakest field. Keep solving problems and I will be able to target one.',
                    $this->navigationAction('/problems', 'Open Problems'),
                    ['Open Problems', 'Open Performance Profile'],
                    $source
                );
            }

            $topic = (string) $weakest['topic'];
            $attempted = (int) ($weakest['attempted'] ?? 0);
            $solved = (int) ($weakest['solved'] ?? 0);
            $problemNames = array_values(array_filter(array_map(
                fn ($problem) => is_array($problem) ? ($problem['title'] ?? null) : null,
                (array) ($data['problems'] ?? [])
            )));

            $reply = $topic.' is currently your weakest practice field based on your CodeForge submission history. You have '.$solved.' accepted attempts from '.$attempted.' attempts in that field.';
            if ($problemNames) {
                $reply .= ' Suggested practice: '.implode(', ', array_slice($problemNames, 0, 3)).'.';
            }

            $explicitTarget = str_contains($normalizedMessage, 'target')
                || str_contains($normalizedMessage, 'show problems')
                || str_contains($normalizedMessage, 'take me')
                || str_contains($normalizedMessage, 'practice my weakest');

            return $this->response(
                $reply,
                $explicitTarget
                    ? $this->navigationAction('/problems?ai_target=weakest', 'Target '.$topic)
                    : null,
                ['Target my weakest field', 'Open Performance Profile', 'Open Problems'],
                $source
            );
        } catch (Throwable $exception) {
            Log::warning('CodeForge AI assistant could not load recommendation data.', [
                'exception' => $exception::class,
            ]);

            return $this->response(
                'I could not load your recommendation data right now.',
                $this->navigationAction('/problems', 'Open Problems'),
                ['Open Problems', 'Open Performance Profile'],
                $source
            );
        }
    }

    private function performanceResponse(User $user, string $source = 'local'): array
    {
        try {
            $profile = $this->performanceProfile->calculate((string) $user->id);
            $overall = (int) round((float) ($profile['overall'] ?? 0));
            $archetype = (string) ($profile['archetype']['name'] ?? 'Developing Coder');
            $strengths = implode(', ', array_slice((array) ($profile['strengths'] ?? []), 0, 2));
            $growth = implode(', ', array_slice((array) ($profile['growth_areas'] ?? []), 0, 2));

            $parts = ["Your Performance Profile score is {$overall}% and your current archetype is {$archetype}."];
            if ($strengths !== '') {
                $parts[] = 'Strengths: '.$strengths.'.';
            }
            if ($growth !== '') {
                $parts[] = 'Growth areas: '.$growth.'.';
            }

            return $this->response(
                implode(' ', $parts),
                null,
                ['Open Performance Profile', 'What should I practice?', 'Open Problems'],
                $source
            );
        } catch (Throwable $exception) {
            Log::warning('CodeForge AI assistant could not load performance data.', [
                'exception' => $exception::class,
            ]);

            return $this->response(
                'I could not load your Performance Profile data right now.',
                $this->navigationAction('/performance-profile', 'Open Performance Profile'),
                ['Open Performance Profile', 'Open Problems'],
                $source
            );
        }
    }

    private function findUser(string $username): ?object
    {
        $username = $this->cleanLookup($username);
        if ($username === '') {
            return null;
        }

        $user = DB::table('users')
            ->whereRaw('LOWER(username) = ?', [mb_strtolower($username)])
            ->first(['id', 'username']);

        if ($user) {
            return $user;
        }

        return DB::table('users')
            ->where('username', 'like', $this->escapeLike($username).'%')
            ->orderByDesc('rating')
            ->first(['id', 'username']);
    }

    private function featureInfoResponse(User $user, string $normalized): ?array
    {
        if (! preg_match('/^(?:what is|what are|tell me about|explain|describe)\b/', $normalized)) {
            return null;
        }

        $matches = [];
        foreach ($this->availableRoutesFor($user) as $key => $route) {
            foreach ((array) ($route['aliases'] ?? []) as $alias) {
                $alias = $this->normalize((string) $alias);
                if ($alias !== '' && str_contains($normalized, $alias)) {
                    $matches[] = [
                        'key' => $key,
                        'route' => $route,
                        'alias_length' => mb_strlen($alias),
                    ];
                }
            }
        }

        if (! $matches) {
            return null;
        }

        usort($matches, fn ($left, $right) => $right['alias_length'] <=> $left['alias_length']);
        $route = $matches[0]['route'];

        return $this->response(
            $route['label'].' - '.$route['description'],
            null,
            ['Open '.$route['label'], ...$this->suggestionsForPage((string) $matches[0]['key'])],
            'local'
        );
    }

    private function findDirectPage(User $user, string $normalized): ?array
    {
        if (! preg_match('/\b(open|go to|take me to|navigate to|show me|visit|launch|start|begin|enter)\b/', $normalized)) {
            return null;
        }

        $routes = $this->availableRoutesFor($user);
        $candidates = [];

        foreach ($routes as $key => $route) {
            foreach ((array) ($route['aliases'] ?? []) as $alias) {
                $alias = $this->normalize((string) $alias);
                if ($alias !== '' && str_contains($normalized, $alias)) {
                    $candidates[] = [
                        'key' => $key,
                        'label' => $route['label'],
                        'path' => $route['path'],
                        'alias_length' => mb_strlen($alias),
                    ];
                }
            }
        }

        if (! $candidates) {
            return null;
        }

        usort($candidates, fn ($a, $b) => $b['alias_length'] <=> $a['alias_length']);

        return $candidates[0];
    }

    private function availableRoutesFor(User $user): array
    {
        $routes = (array) config('ai_assistant.routes', []);

        return array_filter($routes, function (array $route) use ($user): bool {
            $roles = (array) ($route['roles'] ?? []);

            return $roles === [] || in_array((string) $user->role, $roles, true);
        });
    }

    private function extractResponseText(array $payload): string
    {
        foreach ((array) ($payload['output'] ?? []) as $item) {
            if (($item['type'] ?? '') !== 'message') {
                continue;
            }

            foreach ((array) ($item['content'] ?? []) as $content) {
                if (($content['type'] ?? '') === 'output_text' && isset($content['text'])) {
                    return trim((string) $content['text']);
                }
            }
        }

        return '';
    }

    private function response(
        string $reply,
        ?array $action = null,
        array $suggestions = [],
        string $source = 'local'
    ): array {
        return [
            'reply' => mb_substr(trim($reply), 0, 6000),
            'action' => $action,
            'suggestions' => $this->cleanSuggestions($suggestions),
            'source' => $source,
            'model' => $source === 'ai' ? ($this->lastModel ?: (string) config('ai_assistant.model', '')) : null,
        ];
    }

    private function navigationAction(string $path, string $label): array
    {
        return [
            'type' => 'navigate',
            'path' => $path,
            'label' => $label,
            'auto_execute' => true,
        ];
    }

    private function cleanSuggestions(array $suggestions): array
    {
        $clean = [];

        foreach ($suggestions as $suggestion) {
            $value = trim((string) $suggestion);
            if ($value !== '' && ! in_array($value, $clean, true)) {
                $clean[] = mb_substr($value, 0, 90);
            }
            if (count($clean) >= 3) {
                break;
            }
        }

        return $clean;
    }

    private function defaultSuggestions(): array
    {
        return ['Open Ghost Race', 'What should I practice?', 'Open SQL Battle'];
    }

    private function suggestionsForPage(string $page): array
    {
        return match ($page) {
            'problems' => ['What should I practice?', 'Open Performance Profile', 'Open Contests'],
            'performance_profile' => ['What should I practice?', 'Open Problems', 'Open Rivalry'],
            'ghost_race' => ['Open Problems', 'Open Performance Profile', 'Open SQL Battle'],
            'sql_battle' => ['Open Problems', 'Open Ghost Race', 'Open Performance Profile'],
            'rivalry' => ['Compare me with ismail', 'Open Performance Profile', 'Open Contests'],
            default => $this->defaultSuggestions(),
        };
    }

private function isDirectCommand(string $message): bool
{
    $message = $this->normalize($message);

    return str_starts_with($message, 'open ')
        || str_starts_with($message, 'go to ')
        || str_starts_with($message, 'take me to ')
        || str_starts_with($message, 'navigate ')
        || str_starts_with($message, 'search ')
        || str_starts_with($message, 'find ');
}

    private function looksLikeHelp(string $message): bool
    {
        return in_array($message, ['help', 'what can you do', 'what can u do', 'commands', 'assistant help'], true)
            || str_contains($message, 'how can you help');
    }

    private function isMyProfileCommand(string $message): bool
    {
        return $message === 'my profile'
            || preg_match('/\b(open|show|go to|take me to)\s+(?:me\s+)?my\s+profile\b/', $message) === 1;
    }

    private function looksLikeWeaknessRequest(string $message): bool
    {
        return str_contains($message, 'weakest')
            || str_contains($message, 'what should i practice')
            || str_contains($message, 'what do i practice')
            || str_contains($message, 'recommend problems')
            || str_contains($message, 'practice recommendation');
    }

    private function looksLikePerformanceRequest(string $message): bool
    {
        return str_contains($message, 'how am i doing')
            || str_contains($message, 'performance summary')
            || str_contains($message, 'summarize my performance')
            || str_contains($message, 'my coding stats')
            || str_contains($message, 'my strengths')
            || str_contains($message, 'my growth areas');
    }

    private function fallbackUnavailable(): array
    {
        return $this->response(
            'The external AI model is not configured. The built-in CodeForge command assistant is still active. Try "open Ghost Race", "search for graphs", "compare me with ismail", "open my profile", or "what should I practice?".',
            null,
            $this->defaultSuggestions(),
            'local'
        );
    }

    private function normalize(string $value): string
    {
        $value = mb_strtolower(trim($value));
        $value = preg_replace('/\s+/u', ' ', $value) ?: $value;

        return trim($value, " \t\n\r\0\x0B?!.,");
    }

    private function cleanLookup(string $value): string
    {
        $value = trim($value);
        $value = trim($value, " \t\n\r\0\x0B?!.,\"'");

        return mb_substr($value, 0, 120);
    }

    private function escapeLike(string $value): string
    {
        return addcslashes($value, '%_\\');
    }

    private function looksLikeCodingQuestion(string $message): bool
    {
        $normalized = $this->normalize($message);

        return str_contains($message, '```')
            || preg_match('/\b(debug|bug|error|exception|stack trace|complexity|time complexity|space complexity|tle|runtime|compile|compiler|optimize|algorithm|implementation|code review|refactor|segmentation fault)\b/i', $message) === 1
            || preg_match('/\b(c\+\+|cpp|java|python|php|javascript|typescript|sql|laravel|svelte|sveltekit|flutter|dart)\b/i', $message) === 1
            || str_contains($normalized, 'my code')
            || str_contains($normalized, 'this code');
    }

    private function providerApiKey(string $provider): string
    {
        return match ($provider) {
            'groq' => trim((string) config('ai_assistant.groq_api_key', '')),
            'deepseek' => trim((string) config('ai_assistant.deepseek_api_key', '')),
            'gemini' => trim((string) config('ai_assistant.gemini_api_key', '')),
            default => '',
        };
    }

    private function providerModel(string $provider, bool $primary): string
    {
        if ($primary) {
            $default = match ($provider) {
                'groq' => 'openai/gpt-oss-120b',
                'deepseek' => 'deepseek-flash',
                default => 'gemini-3.6-flash',
            };

            return trim((string) config('ai_assistant.model', $default));
        }

        $default = match ($provider) {
            'groq' => 'openai/gpt-oss-20b',
            'deepseek' => 'deepseek-flash',
            default => 'gemini-3.6-flash',
        };

        return trim((string) config('ai_assistant.fallback_model', $default));
    }

    private function buildModelMessages(
        User $user,
        string $message,
        string $currentPath,
        array $history
    ): array {
        $routes = $this->availableRoutesFor($user);
        $routeSummary = [];

        foreach ($routes as $key => $route) {
            $routeSummary[] = sprintf(
                '%s: %s - %s',
                $key,
                $route['label'],
                $route['description']
            );
        }

        $historyLines = [];
        foreach (array_slice($history, -4) as $item) {
            $role = ($item['role'] ?? '') === 'assistant' ? 'ASSISTANT' : 'USER';
            $content = trim((string) ($item['content'] ?? ''));

            if ($content !== '') {
                $historyLines[] = $role.': '.mb_substr($content, 0, 1000);
            }
        }

        $context = $this->buildUserContext($user);

        $system = implode("\n", [
            'You are CodeForge Copilot, an in-product competitive-programming and coding assistant.',
            'Return ONE valid JSON object only. Do not wrap JSON in markdown fences.',
            'Required keys: reply, intent, target, page, suggestions.',
            'reply must be a helpful answer for the user. suggestions must be an array with at most 3 short follow-up prompts.',
            'Write reply as clean plain text. Do not use Markdown emphasis, escaped asterisks, HTML, or decorative emoji. Simple numbered lines are okay.',
            'Do not claim that you can set calendar reminders, modify calendars, automatically log practice, or update the Performance Profile. Those actions are not available.',
            'If recommending named CodeForge problems, use only problem titles supplied in codeforge_context.recommended_problems. Never invent CodeForge problem titles.',
            'Supported intents: none, navigate, search, profile, compare, problem, contest, weakest, performance, coach.',
            'Use navigate only for one of the listed page keys.',
            'Use weakest only when the user explicitly asks what to practice, for their weakest topic, or for targeted practice.',
            'Use performance for a personal CodeForge performance summary.',
            'Use coach for improvement plans, learning strategy, skill development, training plans, or questions such as "how can I improve?".',
            'For algorithm questions, debugging, code review, implementation help, complexity analysis, or pasted source code, use intent none and answer the coding question directly in reply.',
            'Do not repeatedly turn general coaching questions into the weakest-topic response.',
            'Use supplied CodeForge user context when relevant, but do not invent user statistics.',
            'Never invent a URL. For site navigation choose only a listed page key or use a supported lookup intent.',
            'Never request, reveal, or modify passwords, API keys, database credentials, or private secrets.',
            'Do not perform destructive/admin/database mutations.',
            'Important product facts: Ghost Race is asynchronous against historical sessions. The normal coding judge is a prototype/simulated judge. SQL Battle executes restricted SQL in a sandbox. Submission anomaly detection flags unusual behavior and does not prove cheating.',
            'Available CodeForge pages:',
            implode("\n", $routeSummary),
        ]);

        $userPayload = [
            'signed_in_user' => [
                'username' => (string) $user->username,
                'role' => (string) $user->role,
            ],
            'codeforge_context' => $context,
            'current_page' => $currentPath !== '' ? $currentPath : 'unknown',
            'recent_conversation' => $historyLines,
            'latest_user_request' => $message,
            'response_schema' => [
                'reply' => 'string',
                'intent' => 'none|navigate|search|profile|compare|problem|contest|weakest|performance|coach',
                'target' => 'string',
                'page' => 'one available page key or empty string',
                'suggestions' => 'array of at most 3 strings',
            ],
        ];

        return [
            ['role' => 'system', 'content' => $system],
            [
                'role' => 'user',
                'content' => json_encode(
                    $userPayload,
                    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                ) ?: $message,
            ],
        ];
    }

    private function buildUserContext(User $user): array
    {
        $context = [
            'rating' => $user->rating ?? null,
        ];

        try {
            $data = $this->recommendations->forUser((string) $user->id, 3);
            $weakest = $data['weakest'] ?? null;

            if (is_array($weakest)) {
                $context['weakest'] = [
                    'topic' => $weakest['topic'] ?? null,
                    'attempted' => (int) ($weakest['attempted'] ?? 0),
                    'solved' => (int) ($weakest['solved'] ?? 0),
                ];
            }

            $context['recommended_problems'] = array_values(array_filter(array_map(
                fn ($problem) => is_array($problem) ? ($problem['title'] ?? null) : null,
                (array) ($data['problems'] ?? [])
            )));
        } catch (Throwable) {
            // Personalized context is optional.
        }

        try {
            $profile = $this->performanceProfile->calculate((string) $user->id);

            $context['performance'] = [
                'overall' => isset($profile['overall']) ? (int) round((float) $profile['overall']) : null,
                'archetype' => $profile['archetype']['name'] ?? null,
                'strengths' => array_values((array) ($profile['strengths'] ?? [])),
                'growth_areas' => array_values((array) ($profile['growth_areas'] ?? [])),
            ];
        } catch (Throwable) {
            // Personalized context is optional.
        }

        return $context;
    }

    private function normalizeModelDecision(string $text): ?array
    {
        $text = trim($text);
        $text = preg_replace('/^```(?:json)?\s*|\s*```$/i', '', $text) ?? $text;

        if ($text === '') {
            return null;
        }

        $decoded = json_decode($text, true);

        if (! is_array($decoded)) {
            return [
                'reply' => $text,
                'intent' => 'none',
                'target' => '',
                'page' => '',
                'suggestions' => [],
            ];
        }

        $allowed = [
            'none',
            'navigate',
            'search',
            'profile',
            'compare',
            'problem',
            'contest',
            'weakest',
            'performance',
            'coach',
        ];

        $intent = (string) ($decoded['intent'] ?? 'none');
        if (! in_array($intent, $allowed, true)) {
            $intent = 'none';
        }

        return [
            'reply' => trim((string) ($decoded['reply'] ?? '')),
            'intent' => $intent,
            'target' => trim((string) ($decoded['target'] ?? '')),
            'page' => trim((string) ($decoded['page'] ?? '')),
            'suggestions' => $this->cleanSuggestions((array) ($decoded['suggestions'] ?? [])),
        ];
    }

    private function askGroqModel(
        User $user,
        string $message,
        string $currentPath,
        array $history,
        string $apiKey,
        string $model
    ): ?array {
        $messages = $this->buildModelMessages($user, $message, $currentPath, $history);
        $primaryModel = $model !== '' ? $model : 'openai/gpt-oss-120b';
        $secondaryModel = trim((string) config('ai_assistant.groq_fallback_model', 'openai/gpt-oss-20b'));
        $models = array_values(array_unique(array_filter([$primaryModel, $secondaryModel])));

        $codingQuestion = $this->looksLikeCodingQuestion($message);

        foreach ($models as $candidateModel) {
            $payload = [
                'model' => $candidateModel,
                'messages' => $messages,
                'response_format' => [
                    'type' => 'json_schema',
                    'json_schema' => [
                        'name' => 'codeforge_copilot_decision',
                        'strict' => true,
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'reply' => ['type' => 'string'],
                                'intent' => [
                                    'type' => 'string',
                                    'enum' => ['none', 'navigate', 'search', 'profile', 'compare', 'problem', 'contest', 'weakest', 'performance', 'coach'],
                                ],
                                'target' => ['type' => 'string'],
                                'page' => ['type' => 'string'],
                                'suggestions' => [
                                    'type' => 'array',
                                    'items' => ['type' => 'string'],
                                    'maxItems' => 3,
                                ],
                            ],
                            'required' => ['reply', 'intent', 'target', 'page', 'suggestions'],
                            'additionalProperties' => false,
                        ],
                    ],
                ],
                'reasoning_effort' => $codingQuestion ? 'high' : 'medium',
                'max_completion_tokens' => $codingQuestion ? 1800 : 900,
                'temperature' => 0.4,
                'stream' => false,
            ];

            if (str_starts_with($candidateModel, 'openai/gpt-oss-')) {
                $payload['include_reasoning'] = false;
            } elseif (str_starts_with($candidateModel, 'qwen/')) {
                $payload['reasoning_format'] = 'hidden';
            }

            try {
                $response = Http::withToken($apiKey)
                    ->acceptJson()
                    ->connectTimeout(5)
                    ->timeout(max(15, min(90, (int) config('ai_assistant.timeout', 35))))
                    ->post('https://api.groq.com/openai/v1/chat/completions', $payload);
            } catch (Throwable $exception) {
                Log::warning('Groq request connection failed.', [
                    'exception' => $exception::class,
                    'message' => $exception->getMessage(),
                    'model' => $candidateModel,
                ]);

                continue;
            }

            if (! $response->successful()) {
                Log::warning('Groq request failed.', [
                    'status' => $response->status(),
                    'body' => mb_substr($response->body(), 0, 2000),
                    'model' => $candidateModel,
                    'has_key' => $apiKey !== '',
                ]);

                if (in_array($response->status(), [401, 403], true)) {
                    return null;
                }

                continue;
            }

            $decision = $this->normalizeModelDecision(
                (string) data_get($response->json(), 'choices.0.message.content', '')
            );

            if ($decision !== null) {
                $this->lastModel = $candidateModel;

                return $decision;
            }

            Log::warning('Groq returned an empty or unusable assistant response.', [
                'model' => $candidateModel,
            ]);
        }

        return null;
    }

    private function askDeepSeekModel(
        User $user,
        string $message,
        string $currentPath,
        array $history,
        string $apiKey,
        string $model
    ): ?array {
        $messages = $this->buildModelMessages($user, $message, $currentPath, $history);

        $response = Http::withToken($apiKey)
            ->acceptJson()
            ->connectTimeout(5)
            ->timeout(max(15, min(90, (int) config('ai_assistant.timeout', 35))))
            ->post(
                'https://api.deepseek.com/chat/completions',
                [
                    'model' => $model !== '' ? $model : 'deepseek-flash',
                    'messages' => $messages,
                    'response_format' => ['type' => 'json_object'],
                    'thinking' => ['type' => 'enabled'],
                    'reasoning_effort' => 'high',
                    'max_tokens' => 1400,
                    'stream' => false,
                ]
            );

        if (! $response->successful()) {
            Log::warning('DeepSeek request failed.', [
                'status' => $response->status(),
                'body' => mb_substr($response->body(), 0, 2000),
                'model' => $model,
                'has_key' => $apiKey !== '',
            ]);

            return null;
        }

        return $this->normalizeModelDecision(
            (string) data_get($response->json(), 'choices.0.message.content', '')
        );
    }

    private function askGeminiModel(
        User $user,
        string $message,
        string $currentPath,
        array $history,
        string $apiKey,
        string $model
    ): ?array {
        $messages = $this->buildModelMessages($user, $message, $currentPath, $history);
        $prompt = $messages[0]['content']."\n\n".$messages[1]['content'];

        $response = Http::acceptJson()
            ->connectTimeout(5)
            ->timeout(max(10, min(60, (int) config('ai_assistant.timeout', 35))))
            ->withHeaders([
                'x-goog-api-key' => $apiKey,
                'Content-Type' => 'application/json',
            ])
            ->post(
                "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent",
                [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $prompt],
                            ],
                        ],
                    ],
                    'generationConfig' => [
                        'temperature' => 0.2,
                        'maxOutputTokens' => 1400,
                        'responseMimeType' => 'application/json',
                    ],
                ]
            );

        if (! $response->successful()) {
            Log::warning('Gemini request failed.', [
                'status' => $response->status(),
                'body' => mb_substr($response->body(), 0, 2000),
                'model' => $model,
                'has_key' => $apiKey !== '',
            ]);

            return null;
        }

        return $this->normalizeModelDecision(
            (string) data_get($response->json(), 'candidates.0.content.parts.0.text', '')
        );
    }
}