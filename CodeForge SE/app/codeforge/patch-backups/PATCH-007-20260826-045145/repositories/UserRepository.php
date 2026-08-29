<?php

declare(strict_types=1);

final class UserRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function all(bool $includeAdmin = true): array
    {
        $sql = 'SELECT id, username, role, rating, university, `rank`, created_at FROM users';
        if (!$includeAdmin) {
            $sql .= " WHERE role = 'user'";
        }
        $sql .= ' ORDER BY username ASC';
        return $this->pdo->query($sql)->fetchAll();
    }

    public function find(string $id): ?array
    {
        // Aggregate the user's submission statistics once and join the result.
        // This avoids running two correlated subqueries for every profile lookup.
        $stmt = $this->pdo->prepare(
            "SELECT u.id, u.username, u.role, u.rating, u.university, u.`rank`, u.created_at,
                    COALESCE(stats.solved_count, 0) AS solved_count,
                    COALESCE(stats.submission_count, 0) AS submission_count
             FROM users u
             LEFT JOIN (
                 SELECT user_id,
                        COUNT(DISTINCT CASE WHEN verdict = 'AC' THEN problem_id END) AS solved_count,
                        COUNT(*) AS submission_count
                 FROM submissions
                 WHERE user_id = :stats_uid
                 GROUP BY user_id
             ) stats ON stats.user_id = u.id
             WHERE u.id = :id
             LIMIT 1"
        );
        $stmt->execute(['stats_uid' => $id, 'id' => $id]);
        return $stmt->fetch() ?: null;
    }
}
