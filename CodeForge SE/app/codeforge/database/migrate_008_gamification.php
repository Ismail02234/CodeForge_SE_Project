<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../services/GamificationService.php';

$statements = [
    "CREATE TABLE IF NOT EXISTS levels (
        level INT UNSIGNED PRIMARY KEY,
        title VARCHAR(80) NOT NULL,
        xp_required INT UNSIGNED NOT NULL,
        UNIQUE KEY uq_levels_xp (xp_required)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    "CREATE TABLE IF NOT EXISTS badges (
        id VARCHAR(50) PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        description VARCHAR(255) NOT NULL,
        icon VARCHAR(80) NOT NULL,
        sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
        INDEX idx_badges_sort (sort_order, name)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    "CREATE TABLE IF NOT EXISTS gamification_profiles (
        user_id VARCHAR(50) PRIMARY KEY,
        xp INT UNSIGNED NOT NULL DEFAULT 0,
        current_streak INT UNSIGNED NOT NULL DEFAULT 0,
        longest_streak INT UNSIGNED NOT NULL DEFAULT 0,
        last_solved_date DATE NULL,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        CONSTRAINT fk_gp_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_gp_xp (xp),
        INDEX idx_gp_last_solved (last_solved_date)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    "CREATE TABLE IF NOT EXISTS user_badges (
        user_id VARCHAR(50) NOT NULL,
        badge_id VARCHAR(50) NOT NULL,
        earned_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (user_id, badge_id),
        CONSTRAINT fk_ub_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        CONSTRAINT fk_ub_badge FOREIGN KEY (badge_id) REFERENCES badges(id) ON DELETE CASCADE,
        INDEX idx_ub_badge (badge_id, earned_at),
        INDEX idx_ub_earned (earned_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
];

try {
    foreach ($statements as $sql) {
        $pdo->exec($sql);
    }

    $levels = [
        [1, 'Code Sprout', 0],
        [2, 'Byte Explorer', 50],
        [3, 'Logic Wizard', 120],
        [4, 'Syntax Seeker', 250],
        [5, 'Algorithm Apprentice', 450],
        [6, 'Data Dynamo', 700],
        [7, 'Code Commander', 1000],
        [8, 'Binary Baron', 1400],
        [9, 'Pixel Paladin', 1900],
        [10, 'CodeForge Champion', 2500],
    ];
    $levelStmt = $pdo->prepare(
        'INSERT INTO levels(level, title, xp_required)
         VALUES(:level, :title, :xp)
         ON DUPLICATE KEY UPDATE title = VALUES(title), xp_required = VALUES(xp_required)'
    );
    foreach ($levels as [$level, $title, $xp]) {
        $levelStmt->execute(['level' => $level, 'title' => $title, 'xp' => $xp]);
    }

    $badges = [
        ['first_steps', 'First Steps', 'Solved your first problem.', 'bi-star-fill', 10],
        ['on_a_roll', 'On a Roll', 'Reached a 3-day solving streak.', 'bi-fire', 20],
        ['century', 'Century', 'Earned at least 100 XP.', 'bi-trophy-fill', 30],
        ['explorer', 'Explorer', 'Solved problems across 3 or more topics.', 'bi-compass', 40],
        ['topic_master', 'Topic Master', 'Solved every problem in at least one topic.', 'bi-graph-up-arrow', 50],
        ['sprout_master', 'Sprout Master', 'Solved 10 Easy problems.', 'bi-patch-check-fill', 60],
    ];
    $badgeStmt = $pdo->prepare(
        'INSERT INTO badges(id, name, description, icon, sort_order)
         VALUES(:id, :name, :description, :icon, :sort_order)
         ON DUPLICATE KEY UPDATE
            name = VALUES(name),
            description = VALUES(description),
            icon = VALUES(icon),
            sort_order = VALUES(sort_order)'
    );
    foreach ($badges as [$id, $name, $description, $icon, $sortOrder]) {
        $badgeStmt->execute([
            'id' => $id,
            'name' => $name,
            'description' => $description,
            'icon' => $icon,
            'sort_order' => $sortOrder,
        ]);
    }

    $result = (new GamificationService($pdo))->backfillAll();
    echo "PATCH-008 gamification migration complete.\n";
    echo "Profiles synchronized: {$result['users']}\n";
    echo "New badges awarded: {$result['badges_awarded']}\n";
} catch (Throwable $error) {
    fwrite(STDERR, "Migration failed: {$error->getMessage()}\n");
    exit(1);
}
