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
        if ($topic) {
            $where[] = 'p.topic = :topic';
            $params['topic'] = $topic;
        }
        if ($difficulty) {
            $where[] = 'p.difficulty = :difficulty';
            $params['difficulty'] = $difficulty;
        }
        if ($search) {
            $where[] = '(p.title LIKE :search OR p.tags LIKE :search OR p.topic LIKE :search)';
            $params['search'] = '%' . $search . '%';
        }

        $sql = "SELECT p.*,
                       (SELECT COUNT(DISTINCT s.user_id) FROM submissions s WHERE s.problem_id = p.id AND s.verdict = 'AC') AS solved_by
                FROM problems p";
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= " ORDER BY FIELD(p.difficulty,'Easy','Medium','Hard'), p.title";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function topics(): array
    {
        return $this->pdo->query('SELECT DISTINCT topic FROM problems ORDER BY topic')->fetchAll(PDO::FETCH_COLUMN);
    }
}
