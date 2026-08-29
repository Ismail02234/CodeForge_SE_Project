<?php

declare(strict_types=1);

require_once __DIR__ . '/GamificationCalculator.php';

final class GamificationService
{
    private ?bool $schemaReady = null;
    private ?array $levelsCache = null;

    public function __construct(private PDO $pdo)
    {
    }

    public function summary(string $userId, bool $withBadges = true): array
    {
        if (!$this->schemaReady()) {
            return $this->disabledSummary();
        }

        $profile = $this->profileRow($userId);
        if ($profile === null) {
            $this->syncUserFromHistory($userId);
            $profile = $this->profileRow($userId);
        }

        if ($profile === null) {
            return $this->disabledSummary();
        }

        $xp = (int) $profile['xp'];
        $level = GamificationCalculator::levelProgress($xp, $this->levels());
        $currentStreak = $this->effectiveCurrentStreak(
            (int) $profile['current_streak'],
            $profile['last_solved_date'] ?? null
        );
        $badges = $withBadges ? $this->badgesWithStatus($userId) : [];
        $earnedBadges = array_values(array_filter(
            $badges,
            static fn (array $badge): bool => !empty($badge['earned_at'])
        ));

        return [
            'enabled' => true,
            'xp' => $xp,
            'current_streak' => $currentStreak,
            'longest_streak' => (int) $profile['longest_streak'],
            'last_solved_date' => $profile['last_solved_date'],
            'level' => $level['current'],
            'next_level' => $level['next'],
            'level_progress' => $level['percent'],
            'badges' => $badges,
            'earned_badges' => $earnedBadges,
            'earned_badge_count' => count($earnedBadges),
        ];
    }

    public function syncUserFromHistory(string $userId): array
    {
        if (!$this->schemaReady()) {
            return [
                'enabled' => false,
                'xp_earned' => 0,
                'new_badges' => [],
            ];
        }

        $startedTransaction = !$this->pdo->inTransaction();
        if ($startedTransaction) {
            $this->pdo->beginTransaction();
        }

        try {
            $this->pdo->prepare(
                'INSERT IGNORE INTO gamification_profiles(user_id) VALUES(:uid)'
            )->execute(['uid' => $userId]);

            $lock = $this->pdo->prepare(
                'SELECT xp FROM gamification_profiles WHERE user_id = :uid FOR UPDATE'
            );
            $lock->execute(['uid' => $userId]);
            $oldXp = (int) ($lock->fetchColumn() ?: 0);

            $stats = $this->historyStats($userId);
            $dates = $this->solveDates($userId);
            $streak = GamificationCalculator::streakStats($dates);
            $xp = (int) $stats['xp'];

            $update = $this->pdo->prepare(
                'UPDATE gamification_profiles
                 SET xp = :xp,
                     current_streak = :current_streak,
                     longest_streak = :longest_streak,
                     last_solved_date = :last_solved_date,
                     updated_at = NOW()
                 WHERE user_id = :uid'
            );
            $update->execute([
                'xp' => $xp,
                'current_streak' => $streak['current_streak'],
                'longest_streak' => $streak['longest_streak'],
                'last_solved_date' => $streak['last_solved_date'],
                'uid' => $userId,
            ]);

            $newBadges = $this->syncBadges($userId, $stats, $streak, $xp);

            if ($startedTransaction) {
                $this->pdo->commit();
            }

            return [
                'enabled' => true,
                'xp' => $xp,
                'xp_earned' => max(0, $xp - $oldXp),
                'current_streak' => $streak['current_streak'],
                'longest_streak' => $streak['longest_streak'],
                'new_badges' => $newBadges,
            ];
        } catch (Throwable $error) {
            if ($startedTransaction && $this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $error;
        }
    }

    public function backfillAll(): array
    {
        if (!$this->schemaReady()) {
            return ['users' => 0, 'badges_awarded' => 0];
        }

        $userIds = $this->pdo->query('SELECT id FROM users ORDER BY id')->fetchAll(PDO::FETCH_COLUMN);
        $badgesAwarded = 0;

        foreach ($userIds as $userId) {
            $result = $this->syncUserFromHistory((string) $userId);
            $badgesAwarded += count($result['new_badges'] ?? []);
        }

        return [
            'users' => count($userIds),
            'badges_awarded' => $badgesAwarded,
        ];
    }

    public function schemaReady(): bool
    {
        if ($this->schemaReady !== null) {
            return $this->schemaReady;
        }

        try {
            $this->pdo->query('SELECT 1 FROM gamification_profiles LIMIT 1')->closeCursor();
            $this->pdo->query('SELECT 1 FROM levels LIMIT 1')->closeCursor();
            $this->pdo->query('SELECT 1 FROM badges LIMIT 1')->closeCursor();
            $this->pdo->query('SELECT 1 FROM user_badges LIMIT 1')->closeCursor();
            $this->schemaReady = true;
        } catch (PDOException) {
            $this->schemaReady = false;
        }

        return $this->schemaReady;
    }

    private function profileRow(string $userId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT user_id, xp, current_streak, longest_streak, last_solved_date
             FROM gamification_profiles
             WHERE user_id = :uid
             LIMIT 1'
        );
        $stmt->execute(['uid' => $userId]);
        return $stmt->fetch() ?: null;
    }

    private function historyStats(string $userId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) AS solved_count,
                    COALESCE(SUM(CASE solved.difficulty
                        WHEN 'Easy' THEN 10
                        WHEN 'Medium' THEN 25
                        WHEN 'Hard' THEN 50
                        ELSE 10 END), 0) AS xp,
                    COALESCE(SUM(CASE WHEN solved.difficulty = 'Easy' THEN 1 ELSE 0 END), 0) AS easy_solved,
                    COUNT(DISTINCT solved.topic) AS solved_topics
             FROM (
                 SELECT s.problem_id, p.difficulty, p.topic
                 FROM submissions s
                 INNER JOIN problems p ON p.id = s.problem_id
                 WHERE s.user_id = :uid AND s.verdict = 'AC'
                 GROUP BY s.problem_id, p.difficulty, p.topic
             ) solved"
        );
        $stmt->execute(['uid' => $userId]);
        $stats = $stmt->fetch() ?: [];

        $master = $this->pdo->prepare(
            "SELECT 1
             FROM problems p
             LEFT JOIN (
                 SELECT DISTINCT problem_id
                 FROM submissions
                 WHERE user_id = :uid AND verdict = 'AC'
             ) solved ON solved.problem_id = p.id
             GROUP BY p.topic
             HAVING COUNT(p.id) > 0 AND COUNT(solved.problem_id) = COUNT(p.id)
             LIMIT 1"
        );
        $master->execute(['uid' => $userId]);

        return [
            'solved_count' => (int) ($stats['solved_count'] ?? 0),
            'xp' => (int) ($stats['xp'] ?? 0),
            'easy_solved' => (int) ($stats['easy_solved'] ?? 0),
            'solved_topics' => (int) ($stats['solved_topics'] ?? 0),
            'topic_master' => (bool) $master->fetchColumn(),
        ];
    }

    private function solveDates(string $userId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT DISTINCT DATE(submitted_at) AS solve_date
             FROM submissions
             WHERE user_id = :uid AND verdict = 'AC'
             ORDER BY solve_date ASC"
        );
        $stmt->execute(['uid' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    private function syncBadges(string $userId, array $stats, array $streak, int $xp): array
    {
        $eligible = [];
        if ($stats['solved_count'] >= 1) $eligible[] = 'first_steps';
        if ($streak['longest_streak'] >= 3) $eligible[] = 'on_a_roll';
        if ($xp >= 100) $eligible[] = 'century';
        if ($stats['solved_topics'] >= 3) $eligible[] = 'explorer';
        if ($stats['topic_master']) $eligible[] = 'topic_master';
        if ($stats['easy_solved'] >= 10) $eligible[] = 'sprout_master';

        if ($eligible === []) {
            return [];
        }

        $existingStmt = $this->pdo->prepare(
            'SELECT badge_id FROM user_badges WHERE user_id = :uid'
        );
        $existingStmt->execute(['uid' => $userId]);
        $existing = array_fill_keys($existingStmt->fetchAll(PDO::FETCH_COLUMN), true);
        $newIds = array_values(array_filter(
            $eligible,
            static fn (string $badgeId): bool => !isset($existing[$badgeId])
        ));

        if ($newIds === []) {
            return [];
        }

        $values = [];
        $params = [];
        foreach ($newIds as $i => $badgeId) {
            $values[] = "(:uid{$i}, :badge{$i}, NOW())";
            $params["uid{$i}"] = $userId;
            $params["badge{$i}"] = $badgeId;
        }
        $this->pdo->prepare(
            'INSERT IGNORE INTO user_badges(user_id, badge_id, earned_at) VALUES ' . implode(',', $values)
        )->execute($params);

        $placeholders = implode(',', array_fill(0, count($newIds), '?'));
        $badgeStmt = $this->pdo->prepare(
            "SELECT id, name, description, icon FROM badges WHERE id IN ({$placeholders}) ORDER BY id"
        );
        $badgeStmt->execute($newIds);
        return $badgeStmt->fetchAll();
    }

    private function badgesWithStatus(string $userId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT b.id, b.name, b.description, b.icon, ub.earned_at
             FROM badges b
             LEFT JOIN user_badges ub
               ON ub.badge_id = b.id AND ub.user_id = :uid
             ORDER BY b.sort_order ASC, b.name ASC'
        );
        $stmt->execute(['uid' => $userId]);
        return $stmt->fetchAll();
    }

    private function levels(): array
    {
        if ($this->levelsCache !== null) {
            return $this->levelsCache;
        }

        $this->levelsCache = $this->pdo
            ->query('SELECT level, title, xp_required FROM levels ORDER BY xp_required ASC')
            ->fetchAll();
        return $this->levelsCache;
    }

    private function effectiveCurrentStreak(int $storedStreak, mixed $lastSolvedDate): int
    {
        if (!$lastSolvedDate || $storedStreak <= 0) {
            return 0;
        }

        $last = substr((string) $lastSolvedDate, 0, 10);
        $today = new DateTimeImmutable('today', new DateTimeZone('UTC'));
        $activeDates = [
            $today->format('Y-m-d'),
            $today->modify('-1 day')->format('Y-m-d'),
        ];
        return in_array($last, $activeDates, true) ? $storedStreak : 0;
    }

    private function disabledSummary(): array
    {
        return [
            'enabled' => false,
            'xp' => 0,
            'current_streak' => 0,
            'longest_streak' => 0,
            'last_solved_date' => null,
            'level' => ['level' => 1, 'title' => 'Code Sprout', 'xp_required' => 0],
            'next_level' => null,
            'level_progress' => 0,
            'badges' => [],
            'earned_badges' => [],
            'earned_badge_count' => 0,
        ];
    }
}
