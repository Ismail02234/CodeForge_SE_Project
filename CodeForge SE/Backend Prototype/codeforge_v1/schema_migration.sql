-- Schema migration for Adaptive AI Problem Recommendation
-- Adds problem_tags and user_tag_stats tables.
-- NOTE: the legacy `topicstats` table is now unused and can be removed later.

CREATE TABLE IF NOT EXISTS problem_tags (
    problemId VARCHAR(50) NOT NULL,
    tag VARCHAR(100) NOT NULL,
    PRIMARY KEY (problemId, tag),
    KEY tag_idx (tag),
    CONSTRAINT problem_tags_ibfk_1 FOREIGN KEY (problemId)
        REFERENCES problems (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS user_tag_stats (
    userId VARCHAR(50) NOT NULL,
    tag VARCHAR(100) NOT NULL,
    attempts INT DEFAULT 0,
    problemsAttempted INT DEFAULT 0,
    problemsSolved INT DEFAULT 0,
    totalFailedSubmissions INT DEFAULT 0,
    avgFailedPerProblem DECIMAL(6,2) DEFAULT 0,
    accuracy DECIMAL(6,4) DEFAULT 0,
    weaknessScore DECIMAL(6,4) DEFAULT 0,
    lastAttemptAt DATETIME DEFAULT NULL,
    updatedAt DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (userId, tag),
    KEY userId_idx (userId),
    CONSTRAINT user_tag_stats_ibfk_1 FOREIGN KEY (userId)
        REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
