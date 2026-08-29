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
        $stmt = $this->pdo->prepare(
            "SELECT u.id, u.username, u.role, u.rating, u.university, u.`rank`, u.created_at,
                    (SELECT COUNT(DISTINCT s.problem_id) FROM submissions s WHERE s.user_id = u.id AND s.verdict = 'AC') AS solved_count,
                    (SELECT COUNT(*) FROM submissions s WHERE s.user_id = u.id) AS submission_count
             FROM users u WHERE u.id = :id"
        );
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }
}
