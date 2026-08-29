<?php

declare(strict_types=1);

final class QuestAdvisorService
{
    public function __construct(private PDO $pdo)
    {
    }

    public function dashboardData(string $userId, int $recommendationLimit = 3): array
    {
        $mastery = $this->topicMastery($userId);
        $weakest = $mastery[0] ?? null;
        $recommendations = $weakest
            ? $this->recommendations($userId, (string) $weakest['topic'], $recommendationLimit)
            : [];

        return [
            'weakest' => $weakest,
            'mastery' => $mastery,
            'recommendations' => $recommendations,
        ];
    }

    public function topicMastery(string $userId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT p.topic,
                    COUNT(p.id) AS total_problems,
                    COUNT(solved.problem_id) AS solved_count
             FROM problems p
             LEFT JOIN (
                 SELECT DISTINCT problem_id
                 FROM submissions
                 WHERE user_id = :uid AND verdict = 'AC'
             ) solved ON solved.problem_id = p.id
             GROUP BY p.topic
             ORDER BY solved_count ASC, total_problems DESC, p.topic ASC"
        );
        $stmt->execute(['uid' => $userId]);
        $rows = $stmt->fetchAll();

        foreach ($rows as &$row) {
            $total = max(1, (int) $row['total_problems']);
            $solved = (int) $row['solved_count'];
            $row['mastery_percent'] = (int) round(($solved / $total) * 100);
        }
        unset($row);

        return $rows;
    }

    public function recommendations(string $userId, string $topic, int $limit = 3): array
    {
        $limit = max(1, min(6, $limit));
        $sql = "SELECT p.id, p.title, p.topic, p.difficulty
                FROM problems p
                WHERE p.topic = :topic
                  AND NOT EXISTS (
                      SELECT 1
                      FROM submissions s
                      WHERE s.user_id = :uid
                        AND s.problem_id = p.id
                        AND s.verdict = 'AC'
                  )
                ORDER BY FIELD(p.difficulty, 'Easy', 'Medium', 'Hard'), p.title ASC
                LIMIT {$limit}";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['topic' => $topic, 'uid' => $userId]);
        return $stmt->fetchAll();
    }
}
