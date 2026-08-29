<?php

declare(strict_types=1);

final class ProblemRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function all(?string $topic = null, ?string $difficulty = null, ?string $search = null): array
    {
        $where = [];
        $params = [];

        if ($topic !== null && $topic !== '') {
            $where[] = 'p.topic = :topic';
            $params['topic'] = $topic;
        }

        if ($difficulty !== null && $difficulty !== '') {
            $where[] = 'p.difficulty = :difficulty';
            $params['difficulty'] = $difficulty;
        }

        if ($search !== null && $search !== '') {
            $where[] = '(p.title LIKE :search OR p.tags LIKE :search OR p.topic LIKE :search)';
            $params['search'] = '%' . $search . '%';
        }

        $sql = "SELECT p.id, p.title, p.topic, p.difficulty, p.description, p.tags, p.starter_code, p.created_at,
                       COALESCE(stats.solved_by, 0) AS solved_by
                FROM problems p
                LEFT JOIN (
                    SELECT problem_id, COUNT(DISTINCT user_id) AS solved_by
                    FROM submissions
                    WHERE verdict = 'AC'
                    GROUP BY problem_id
                ) stats ON stats.problem_id = p.id";

        if ($where !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= " ORDER BY FIELD(p.difficulty,'Easy','Medium','Hard'), p.title";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function topics(): array
    {
        return $this->pdo
            ->query('SELECT DISTINCT topic FROM problems ORDER BY topic')
            ->fetchAll(PDO::FETCH_COLUMN);
    }
}
