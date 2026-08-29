-- CodeForge 2.0 - Complete local database
-- Target: MariaDB 10.4+ / MySQL 8+
-- Import into an empty database named `project`.

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS sql_attempts;
DROP TABLE IF EXISTS sql_battles;
DROP TABLE IF EXISTS sql_challenges;
DROP TABLE IF EXISTS ghost_races;
DROP TABLE IF EXISTS submissions;
DROP TABLE IF EXISTS problem_sessions;
DROP TABLE IF EXISTS contest_participants;
DROP TABLE IF EXISTS contest_problems;
DROP TABLE IF EXISTS contests;
DROP TABLE IF EXISTS problems;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS universities;
DROP TABLE IF EXISTS arena_submissions;
DROP TABLE IF EXISTS arena_problems;
DROP TABLE IF EXISTS arena_users;
DROP TABLE IF EXISTS arena_universities;
DROP TABLE IF EXISTS activity_logs;

CREATE TABLE universities (
    name VARCHAR(255) PRIMARY KEY,
    city VARCHAR(120) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE users (
    id VARCHAR(50) PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('user','admin') NOT NULL DEFAULT 'user',
    rating INT NOT NULL DEFAULT 1200,
    university VARCHAR(255) NULL,
    `rank` VARCHAR(50) NOT NULL DEFAULT 'Newbie',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_users_university FOREIGN KEY (university)
        REFERENCES universities(name) ON UPDATE CASCADE ON DELETE SET NULL,
    INDEX idx_users_university (university),
    INDEX idx_users_rating (rating)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE problems (
    id VARCHAR(50) PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    topic VARCHAR(100) NOT NULL,
    difficulty ENUM('Easy','Medium','Hard') NOT NULL DEFAULT 'Easy',
    description TEXT NOT NULL,
    tags VARCHAR(255) NULL,
    starter_code TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_problems_topic (topic),
    INDEX idx_problems_difficulty (difficulty)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE contests (
    id VARCHAR(50) PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    type ENUM('Global','Local','Duel') NOT NULL DEFAULT 'Local',
    starts_at DATETIME NOT NULL,
    status ENUM('Upcoming','Active','Past') NOT NULL DEFAULT 'Upcoming',
    created_by VARCHAR(50) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_contests_creator FOREIGN KEY (created_by)
        REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_contests_status (status),
    INDEX idx_contests_starts_at (starts_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE contest_problems (
    contest_id VARCHAR(50) NOT NULL,
    problem_id VARCHAR(50) NOT NULL,
    points INT NOT NULL DEFAULT 100,
    PRIMARY KEY (contest_id, problem_id),
    CONSTRAINT fk_cp_contest FOREIGN KEY (contest_id)
        REFERENCES contests(id) ON DELETE CASCADE,
    CONSTRAINT fk_cp_problem FOREIGN KEY (problem_id)
        REFERENCES problems(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE contest_participants (
    contest_id VARCHAR(50) NOT NULL,
    user_id VARCHAR(50) NOT NULL,
    score INT NOT NULL DEFAULT 0,
    joined_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (contest_id, user_id),
    INDEX idx_cpa_user (user_id),
    CONSTRAINT fk_cpa_contest FOREIGN KEY (contest_id)
        REFERENCES contests(id) ON DELETE CASCADE,
    CONSTRAINT fk_cpa_user FOREIGN KEY (user_id)
        REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE problem_sessions (
    id VARCHAR(64) PRIMARY KEY,
    user_id VARCHAR(50) NOT NULL,
    problem_id VARCHAR(50) NOT NULL,
    started_at DATETIME NOT NULL,
    completed_at DATETIME NULL,
    solve_time_seconds INT NULL,
    status ENUM('active','solved','abandoned') NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_ps_user FOREIGN KEY (user_id)
        REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_ps_problem FOREIGN KEY (problem_id)
        REFERENCES problems(id) ON DELETE CASCADE,
    INDEX idx_ps_user_problem (user_id, problem_id),
    INDEX idx_ps_status (status),
    INDEX idx_ps_user_problem_status_started (user_id, problem_id, status, started_at),
    INDEX idx_ps_status_user_problem_solve (status, user_id, problem_id, solve_time_seconds)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE submissions (
    id VARCHAR(64) PRIMARY KEY,
    session_id VARCHAR(64) NULL,
    problem_id VARCHAR(50) NOT NULL,
    user_id VARCHAR(50) NOT NULL,
    contest_id VARCHAR(50) NULL,
    verdict ENUM('AC','WA','TLE','MLE','RE','CE') NOT NULL,
    submitted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    elapsed_seconds INT NULL,
    runtime_ms INT NULL,
    memory_kb INT NULL,
    language VARCHAR(50) NOT NULL DEFAULT 'C++',
    source_code MEDIUMTEXT NULL,
    failed_test_case INT NULL,
    CONSTRAINT fk_sub_session FOREIGN KEY (session_id)
        REFERENCES problem_sessions(id) ON DELETE SET NULL,
    CONSTRAINT fk_sub_problem FOREIGN KEY (problem_id)
        REFERENCES problems(id) ON DELETE CASCADE,
    CONSTRAINT fk_sub_user FOREIGN KEY (user_id)
        REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_sub_contest FOREIGN KEY (contest_id)
        REFERENCES contests(id) ON DELETE SET NULL,
    INDEX idx_sub_user (user_id),
    INDEX idx_sub_problem (problem_id),
    INDEX idx_sub_user_verdict (user_id, verdict),
    INDEX idx_sub_session_elapsed (session_id, elapsed_seconds),
    INDEX idx_sub_user_time (user_id, submitted_at, id),
    INDEX idx_sub_user_verdict_problem (user_id, verdict, problem_id),
    INDEX idx_sub_user_verdict_time_problem (user_id, verdict, submitted_at, problem_id),
    INDEX idx_sub_problem_verdict_user (problem_id, verdict, user_id),
    INDEX idx_sub_contest_user_problem_verdict (contest_id, user_id, problem_id, verdict)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- Gamification is isolated from the core user table so it can be added or rebuilt without changing authentication data.
CREATE TABLE levels (
    level INT UNSIGNED PRIMARY KEY,
    title VARCHAR(80) NOT NULL,
    xp_required INT UNSIGNED NOT NULL,
    UNIQUE KEY uq_levels_xp (xp_required)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE badges (
    id VARCHAR(50) PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description VARCHAR(255) NOT NULL,
    icon VARCHAR(80) NOT NULL,
    sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    INDEX idx_badges_sort (sort_order, name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE gamification_profiles (
    user_id VARCHAR(50) PRIMARY KEY,
    xp INT UNSIGNED NOT NULL DEFAULT 0,
    current_streak INT UNSIGNED NOT NULL DEFAULT 0,
    longest_streak INT UNSIGNED NOT NULL DEFAULT 0,
    last_solved_date DATE NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_gp_user FOREIGN KEY (user_id)
        REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_gp_xp (xp),
    INDEX idx_gp_last_solved (last_solved_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE user_badges (
    user_id VARCHAR(50) NOT NULL,
    badge_id VARCHAR(50) NOT NULL,
    earned_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, badge_id),
    CONSTRAINT fk_ub_user FOREIGN KEY (user_id)
        REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_ub_badge FOREIGN KEY (badge_id)
        REFERENCES badges(id) ON DELETE CASCADE,
    INDEX idx_ub_badge (badge_id, earned_at),
    INDEX idx_ub_earned (earned_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE ghost_races (
    id VARCHAR(64) PRIMARY KEY,
    challenger_id VARCHAR(50) NOT NULL,
    ghost_user_id VARCHAR(50) NOT NULL,
    problem_id VARCHAR(50) NOT NULL,
    ghost_session_id VARCHAR(64) NOT NULL,
    challenger_session_id VARCHAR(64) NOT NULL,
    playback_speed TINYINT NOT NULL DEFAULT 4,
    started_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    finished_at DATETIME NULL,
    result ENUM('active','won','lost','draw','forfeit') NOT NULL DEFAULT 'active',
    challenger_time INT NULL,
    ghost_time INT NOT NULL,
    CONSTRAINT fk_gr_challenger FOREIGN KEY (challenger_id)
        REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_gr_ghost_user FOREIGN KEY (ghost_user_id)
        REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_gr_problem FOREIGN KEY (problem_id)
        REFERENCES problems(id) ON DELETE CASCADE,
    CONSTRAINT fk_gr_ghost_session FOREIGN KEY (ghost_session_id)
        REFERENCES problem_sessions(id) ON DELETE CASCADE,
    CONSTRAINT fk_gr_challenger_session FOREIGN KEY (challenger_session_id)
        REFERENCES problem_sessions(id) ON DELETE CASCADE,
    INDEX idx_gr_challenger (challenger_id, started_at),
    INDEX idx_gr_challenger_result_started (challenger_id, result, started_at),
    INDEX idx_gr_challenger_session_result (challenger_session_id, result)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE sql_challenges (
    id VARCHAR(50) PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    difficulty ENUM('Easy','Medium','Hard') NOT NULL,
    reference_query TEXT NOT NULL,
    max_score INT NOT NULL DEFAULT 1000,
    order_sensitive TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_sql_challenge_difficulty (difficulty)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE sql_battles (
    id VARCHAR(64) PRIMARY KEY,
    challenge_id VARCHAR(50) NOT NULL,
    player1_id VARCHAR(50) NOT NULL,
    player2_id VARCHAR(50) NOT NULL,
    status ENUM('active','completed','cancelled') NOT NULL DEFAULT 'active',
    winner_id VARCHAR(50) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    completed_at DATETIME NULL,
    CONSTRAINT fk_sb_challenge FOREIGN KEY (challenge_id)
        REFERENCES sql_challenges(id) ON DELETE CASCADE,
    CONSTRAINT fk_sb_player1 FOREIGN KEY (player1_id)
        REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_sb_player2 FOREIGN KEY (player2_id)
        REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_sb_winner FOREIGN KEY (winner_id)
        REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_sb_players (player1_id, player2_id),
    INDEX idx_sb_challenge_status_players (challenge_id, status, player1_id, player2_id),
    INDEX idx_sb_status (status),
    INDEX idx_sb_player1_created (player1_id, created_at),
    INDEX idx_sb_player2_created (player2_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE sql_attempts (
    id VARCHAR(64) PRIMARY KEY,
    battle_id VARCHAR(64) NULL,
    challenge_id VARCHAR(50) NOT NULL,
    user_id VARCHAR(50) NOT NULL,
    submitted_query TEXT NOT NULL,
    status ENUM('accepted','wrong_answer','rejected','error') NOT NULL,
    execution_time_ms DECIMAL(10,3) NULL,
    efficiency_score INT NOT NULL DEFAULT 0,
    score INT NOT NULL DEFAULT 0,
    feedback VARCHAR(500) NULL,
    submitted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_sa_battle FOREIGN KEY (battle_id)
        REFERENCES sql_battles(id) ON DELETE CASCADE,
    CONSTRAINT fk_sa_challenge FOREIGN KEY (challenge_id)
        REFERENCES sql_challenges(id) ON DELETE CASCADE,
    CONSTRAINT fk_sa_user FOREIGN KEY (user_id)
        REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_sa_user (user_id, submitted_at),
    INDEX idx_sa_battle_user (battle_id, user_id),
    INDEX idx_sa_challenge_status (challenge_id, status),
    INDEX idx_sa_battle_status_user_score (battle_id, status, user_id, score)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE activity_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(50) NULL,
    action VARCHAR(100) NOT NULL,
    details VARCHAR(500) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_activity_user FOREIGN KEY (user_id)
        REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_activity_created (created_at),
    INDEX idx_activity_user_created (user_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- SQL Arena sandbox tables: deliberately contain no passwords or sensitive columns.
CREATE TABLE arena_universities (
    name VARCHAR(120) PRIMARY KEY
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE arena_users (
    user_id VARCHAR(20) PRIMARY KEY,
    username VARCHAR(100) NOT NULL,
    university VARCHAR(120) NOT NULL,
    rating INT NOT NULL,
    CONSTRAINT fk_au_university FOREIGN KEY (university)
        REFERENCES arena_universities(name) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE arena_problems (
    problem_id VARCHAR(20) PRIMARY KEY,
    title VARCHAR(120) NOT NULL,
    topic VARCHAR(80) NOT NULL,
    difficulty VARCHAR(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE arena_submissions (
    submission_id VARCHAR(20) PRIMARY KEY,
    user_id VARCHAR(20) NOT NULL,
    problem_id VARCHAR(20) NOT NULL,
    verdict VARCHAR(10) NOT NULL,
    runtime_ms INT NOT NULL,
    CONSTRAINT fk_as_user FOREIGN KEY (user_id) REFERENCES arena_users(user_id) ON DELETE CASCADE,
    CONSTRAINT fk_as_problem FOREIGN KEY (problem_id) REFERENCES arena_problems(problem_id) ON DELETE CASCADE,
    INDEX idx_as_user (user_id),
    INDEX idx_as_problem (problem_id),
    INDEX idx_as_verdict (verdict)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO universities (name, city) VALUES
('National Institute', 'Dhaka'),
('State College of Engineering', 'Chattogram'),
('Tech University of Science', 'Dhaka'),
('Metropolitan Computing Academy', 'Khulna');

-- Demo passwords:
-- Ismail / Tamjid / David / Anon => 123456
-- Admin => admin123
INSERT INTO users (id, username, password, role, rating, university, `rank`, created_at) VALUES
('u1','Ismail','$2y$12$zmZDLq/UGFX.SjY.bRWDFOOXakNXxwHv0q6uHLhnyXAbWSrfejX56','user',1451,'Tech University of Science','Specialist','2025-09-01 09:00:00'),
('u2','Tamjid','$2y$12$zmZDLq/UGFX.SjY.bRWDFOOXakNXxwHv0q6uHLhnyXAbWSrfejX56','user',1520,'State College of Engineering','Expert','2025-09-01 09:05:00'),
('u3','David','$2y$12$zmZDLq/UGFX.SjY.bRWDFOOXakNXxwHv0q6uHLhnyXAbWSrfejX56','user',1300,'National Institute','Pupil','2025-09-01 09:10:00'),
('u4','Anon','$2y$12$zmZDLq/UGFX.SjY.bRWDFOOXakNXxwHv0q6uHLhnyXAbWSrfejX56','user',980,'Tech University of Science','Newbie','2025-09-01 09:15:00'),
('u_admin','Admin','$2y$12$kmk5/Ms7pzfYScGw0ZDWGOj7Ryl7vSTKD.gPyH7apyMdgBvtkHE7S','admin',1680,'Metropolitan Computing Academy','Expert','2025-09-01 08:00:00');


INSERT INTO levels (level,title,xp_required) VALUES
(1,'Code Sprout',0),
(2,'Byte Explorer',50),
(3,'Logic Wizard',120),
(4,'Syntax Seeker',250),
(5,'Algorithm Apprentice',450),
(6,'Data Dynamo',700),
(7,'Code Commander',1000),
(8,'Binary Baron',1400),
(9,'Pixel Paladin',1900),
(10,'CodeForge Champion',2500);

INSERT INTO badges (id,name,description,icon,sort_order) VALUES
('first_steps','First Steps','Solved your first problem.','bi-star-fill',10),
('on_a_roll','On a Roll','Reached a 3-day solving streak.','bi-fire',20),
('century','Century','Earned at least 100 XP.','bi-trophy-fill',30),
('explorer','Explorer','Solved problems across 3 or more topics.','bi-compass',40),
('topic_master','Topic Master','Solved every problem in at least one topic.','bi-graph-up-arrow',50),
('sprout_master','Sprout Master','Solved 10 Easy problems.','bi-patch-check-fill',60);

INSERT INTO problems (id,title,topic,difficulty,description,tags,starter_code) VALUES
('p1','Hello World Logic','Basic','Easy','Read a name and print a friendly greeting. The goal is to verify input/output fundamentals.','intro,io,syntax','#include <bits/stdc++.h>\nusing namespace std;\n\nint main() {\n    // your code\n    return 0;\n}'),
('p2','Even or Odd','Math','Easy','Given an integer n, print EVEN if n is divisible by 2; otherwise print ODD.','math,condition','#include <bits/stdc++.h>\nusing namespace std;\n\nint main() {\n    long long n; cin >> n;\n    // your code\n}'),
('p3','Array Sum','Arrays','Easy','Given n integers, compute their sum using a linear scan.','arrays,loops','#include <bits/stdc++.h>\nusing namespace std;\n\nint main() {\n    int n; cin >> n;\n    // your code\n}'),
('p4','Tree Query','Trees','Hard','Answer ancestor-distance queries on a rooted tree. Design an efficient preprocessing strategy.','trees,lca,binary-lifting','#include <bits/stdc++.h>\nusing namespace std;\n\nint main() {\n    // build tree and answer queries\n}'),
('p5','String Hashing','Strings','Medium','Answer substring equality queries using a rolling-hash style technique.','strings,hashing','#include <bits/stdc++.h>\nusing namespace std;\n\nint main() {\n    string s; cin >> s;\n    // your code\n}'),
('p6','Shortest Path Sprint','Graphs','Medium','Find shortest distances from node 1 in a positively weighted graph.','graphs,dijkstra','#include <bits/stdc++.h>\nusing namespace std;\n\nint main() {\n    // dijkstra\n}'),
('p7','Knapsack Core','DP','Hard','Maximize value under a capacity constraint using dynamic programming.','dp,knapsack','#include <bits/stdc++.h>\nusing namespace std;\n\nint main() {\n    // dynamic programming\n}'),
('p8','Binary Search Boundaries','Binary Search','Medium','For each query, find the first element greater than or equal to x.','binary-search,lower-bound','#include <bits/stdc++.h>\nusing namespace std;\n\nint main() {\n    // binary search\n}'),
('p9','Interval Merge','Greedy','Medium','Merge overlapping intervals and print the resulting disjoint ranges.','greedy,sorting,intervals','#include <bits/stdc++.h>\nusing namespace std;\n\nint main() {\n    // sort then merge\n}'),
('p10','Prefix Frequency','Arrays','Medium','Preprocess an array so frequency queries over prefixes can be answered quickly.','arrays,prefix-sum','#include <bits/stdc++.h>\nusing namespace std;\n\nint main() {\n    // prefix frequency\n}'),
('p11','Palindrome Lab','Strings','Easy','Determine whether a string is a palindrome after normalizing its case.','strings,two-pointers','#include <bits/stdc++.h>\nusing namespace std;\n\nint main() {\n    string s; cin >> s;\n    // your code\n}'),
('p12','Tree Diameter','Trees','Medium','Compute the diameter of an unweighted tree with two graph traversals.','trees,bfs,dfs','#include <bits/stdc++.h>\nusing namespace std;\n\nint main() {\n    // two traversals\n}'),
('p13','Modular Power','Math','Medium','Compute a^b mod m using binary exponentiation.','math,fast-power','#include <bits/stdc++.h>\nusing namespace std;\n\nint main() {\n    // fast exponentiation\n}'),
('p14','DAG Routes','Graphs','Hard','Count paths in a directed acyclic graph using topological order and dynamic programming.','graphs,dag,dp','#include <bits/stdc++.h>\nusing namespace std;\n\nint main() {\n    // topo + dp\n}');

INSERT INTO contests (id,name,type,starts_at,status,created_by) VALUES
('c1','CodeForge Round #45','Global','2026-09-12 18:00:00','Upcoming','u_admin'),
('c2','Local University Clash','Local','2026-07-20 16:00:00','Past','u_admin'),
('c3','Head-to-Head Sprint','Duel','2026-08-26 12:00:00','Active','u1'),
('c4','Algorithm Night #12','Global','2026-08-30 20:00:00','Upcoming','u_admin');

INSERT INTO contest_problems (contest_id,problem_id,points) VALUES
('c1','p6',300),('c1','p7',500),('c1','p10',300),
('c2','p3',100),('c2','p5',250),('c2','p12',350),
('c3','p5',500),('c4','p8',250),('c4','p9',300),('c4','p14',500);

INSERT INTO contest_participants (contest_id,user_id,score,joined_at) VALUES
('c2','u1',450,'2026-07-20 15:55:00'),('c2','u2',700,'2026-07-20 15:56:00'),('c2','u3',250,'2026-07-20 15:58:00'),
('c3','u1',100,'2026-08-26 11:55:00'),('c3','u3',0,'2026-08-26 11:56:00');

INSERT INTO problem_sessions (id,user_id,problem_id,started_at,completed_at,solve_time_seconds,status) VALUES
('ps1','u1','p1','2026-07-03 10:00:00','2026-07-03 10:01:10',70,'solved'),
('ps2','u1','p3','2026-07-08 11:00:00','2026-07-08 11:02:00',120,'solved'),
('ps3','u1','p5','2026-07-15 20:00:00','2026-07-15 20:03:00',180,'solved'),
('ps4','u1','p6','2026-07-22 19:00:00','2026-07-22 19:04:00',240,'solved'),
('ps5','u1','p8','2026-07-30 18:30:00','2026-07-30 18:33:30',210,'solved'),
('ps6','u1','p9','2026-08-05 21:00:00','2026-08-05 21:05:20',320,'solved'),
('ps7','u1','p11','2026-08-14 15:00:00','2026-08-14 15:01:30',90,'solved'),
('ps8','u1','p13','2026-08-22 16:00:00','2026-08-22 16:04:20',260,'solved'),
('ps9','u2','p1','2026-07-02 10:00:00','2026-07-02 10:00:40',40,'solved'),
('ps10','u2','p3','2026-07-06 11:00:00','2026-07-06 11:01:20',80,'solved'),
('ps11','u2','p5','2026-07-12 20:00:00','2026-07-12 20:02:20',140,'solved'),
('ps12','u2','p6','2026-07-18 19:00:00','2026-07-18 19:03:00',180,'solved'),
('ps13','u2','p7','2026-07-26 19:00:00','2026-07-26 19:07:00',420,'solved'),
('ps14','u2','p8','2026-08-02 18:00:00','2026-08-02 18:02:40',160,'solved'),
('ps15','u2','p12','2026-08-11 17:00:00','2026-08-11 17:04:20',260,'solved'),
('ps16','u2','p14','2026-08-20 21:00:00','2026-08-20 21:08:20',500,'solved'),
('ps17','u3','p1','2026-07-04 09:00:00','2026-07-04 09:01:50',110,'solved'),
('ps18','u3','p2','2026-07-10 09:00:00','2026-07-10 09:01:40',100,'solved'),
('ps19','u3','p3','2026-07-17 09:00:00','2026-07-17 09:03:10',190,'solved'),
('ps20','u3','p11','2026-07-28 09:00:00','2026-07-28 09:02:10',130,'solved'),
('ps21','u3','p13','2026-08-10 09:00:00','2026-08-10 09:06:40',400,'solved'),
('ps22','u3','p5','2026-08-18 09:00:00','2026-08-18 09:05:50',350,'solved');

INSERT INTO submissions (id,session_id,problem_id,user_id,contest_id,verdict,submitted_at,elapsed_seconds,runtime_ms,memory_kb,language,source_code,failed_test_case) VALUES
('s1','ps1','p1','u1',NULL,'WA','2026-07-03 10:00:30',30,14,1024,'C++','// historical submission',1),
('s2','ps1','p1','u1',NULL,'AC','2026-07-03 10:01:10',70,10,1024,'C++','// historical submission',NULL),
('s3','ps2','p3','u1',NULL,'AC','2026-07-08 11:02:00',120,18,1400,'C++','// historical submission',NULL),
('s4','ps3','p5','u1','c2','WA','2026-07-15 20:01:00',60,32,2200,'C++','// historical submission',4),
('s5','ps3','p5','u1','c2','TLE','2026-07-15 20:02:00',120,1600,2400,'C++','// historical submission',8),
('s6','ps3','p5','u1','c2','AC','2026-07-15 20:03:00',180,44,2300,'C++','// historical submission',NULL),
('s7','ps4','p6','u1',NULL,'AC','2026-07-22 19:04:00',240,58,3100,'C++','// historical submission',NULL),
('s8','ps5','p8','u1',NULL,'WA','2026-07-30 18:31:40',100,24,1700,'C++','// historical submission',3),
('s9','ps5','p8','u1',NULL,'AC','2026-07-30 18:33:30',210,19,1650,'C++','// historical submission',NULL),
('s10','ps6','p9','u1',NULL,'WA','2026-08-05 21:02:30',150,41,1800,'C++','// historical submission',6),
('s11','ps6','p9','u1',NULL,'AC','2026-08-05 21:05:20',320,31,1750,'C++','// historical submission',NULL),
('s12','ps7','p11','u1',NULL,'AC','2026-08-14 15:01:30',90,12,1200,'C++','// historical submission',NULL),
('s13','ps8','p13','u1',NULL,'AC','2026-08-22 16:04:20',260,22,1500,'C++','// historical submission',NULL),
('s14','ps9','p1','u2',NULL,'AC','2026-07-02 10:00:40',40,9,1000,'C++','// historical submission',NULL),
('s15','ps10','p3','u2',NULL,'AC','2026-07-06 11:01:20',80,15,1300,'C++','// historical submission',NULL),
('s16','ps11','p5','u2',NULL,'WA','2026-07-12 20:00:50',50,30,2150,'C++','// historical submission',2),
('s17','ps11','p5','u2',NULL,'AC','2026-07-12 20:02:20',140,38,2100,'C++','// historical submission',NULL),
('s18','ps12','p6','u2',NULL,'AC','2026-07-18 19:03:00',180,49,2900,'C++','// historical submission',NULL),
('s19','ps13','p7','u2',NULL,'WA','2026-07-26 19:03:20',200,75,4100,'C++','// historical submission',5),
('s20','ps13','p7','u2',NULL,'AC','2026-07-26 19:07:00',420,64,4000,'C++','// historical submission',NULL),
('s21','ps14','p8','u2',NULL,'AC','2026-08-02 18:02:40',160,17,1600,'C++','// historical submission',NULL),
('s22','ps15','p12','u2',NULL,'AC','2026-08-11 17:04:20',260,35,2400,'C++','// historical submission',NULL),
('s23','ps16','p14','u2',NULL,'TLE','2026-08-20 21:04:10',250,1700,5100,'C++','// historical submission',7),
('s24','ps16','p14','u2',NULL,'AC','2026-08-20 21:08:20',500,92,4800,'C++','// historical submission',NULL),
('s25','ps17','p1','u3',NULL,'AC','2026-07-04 09:01:50',110,11,1050,'C++','// historical submission',NULL),
('s26','ps18','p2','u3',NULL,'WA','2026-07-10 09:00:40',40,10,1100,'C++','// historical submission',2),
('s27','ps18','p2','u3',NULL,'AC','2026-07-10 09:01:40',100,8,1080,'C++','// historical submission',NULL),
('s28','ps19','p3','u3',NULL,'WA','2026-07-17 09:01:20',80,20,1400,'C++','// historical submission',4),
('s29','ps19','p3','u3',NULL,'AC','2026-07-17 09:03:10',190,16,1380,'C++','// historical submission',NULL),
('s30','ps20','p11','u3',NULL,'AC','2026-07-28 09:02:10',130,14,1250,'C++','// historical submission',NULL),
('s31','ps21','p13','u3',NULL,'WA','2026-08-10 09:02:30',150,35,1550,'C++','// historical submission',3),
('s32','ps21','p13','u3',NULL,'WA','2026-08-10 09:04:40',280,30,1500,'C++','// historical submission',5),
('s33','ps21','p13','u3',NULL,'AC','2026-08-10 09:06:40',400,25,1480,'C++','// historical submission',NULL),
('s34','ps22','p5','u3',NULL,'TLE','2026-08-18 09:02:30',150,1500,2400,'C++','// historical submission',7),
('s35','ps22','p5','u3',NULL,'AC','2026-08-18 09:05:50',350,50,2250,'C++','// historical submission',NULL);

-- One completed race so the history panel is populated.
INSERT INTO problem_sessions (id,user_id,problem_id,started_at,completed_at,solve_time_seconds,status) VALUES
('ps_race_seed','u1','p1','2026-08-23 14:00:00','2026-08-23 14:00:35',35,'solved');
INSERT INTO submissions (id,session_id,problem_id,user_id,verdict,submitted_at,elapsed_seconds,runtime_ms,memory_kb,language,source_code) VALUES
('s_race_seed','ps_race_seed','p1','u1','AC','2026-08-23 14:00:35',35,9,1000,'C++','// ghost race historical win');
INSERT INTO ghost_races (id,challenger_id,ghost_user_id,problem_id,ghost_session_id,challenger_session_id,playback_speed,started_at,finished_at,result,challenger_time,ghost_time) VALUES
('gr_seed','u1','u2','p1','ps9','ps_race_seed',4,'2026-08-23 14:00:00','2026-08-23 14:00:35','won',35,40);

INSERT INTO arena_universities (name) VALUES
('National Institute'),('State College of Engineering'),('Tech University of Science');
INSERT INTO arena_users (user_id,username,university,rating) VALUES
('u1','Ismail','Tech University of Science',1451),
('u2','Tamjid','State College of Engineering',1520),
('u3','David','National Institute',1300),
('u4','Anon','Tech University of Science',980),
('u5','Nadia','National Institute',1610),
('u6','Rafi','State College of Engineering',1390);
INSERT INTO arena_problems (problem_id,title,topic,difficulty) VALUES
('p1','Array Sum','Arrays','Easy'),('p2','String Hashing','Strings','Medium'),('p3','Shortest Path','Graphs','Medium'),('p4','Tree Query','Trees','Hard'),('p5','Knapsack','DP','Hard'),('p6','Binary Search','Binary Search','Medium');
INSERT INTO arena_submissions (submission_id,user_id,problem_id,verdict,runtime_ms) VALUES
('a1','u1','p1','AC',18),('a2','u1','p2','WA',55),('a3','u1','p2','AC',41),('a4','u1','p3','AC',62),
('a5','u2','p1','AC',15),('a6','u2','p2','AC',35),('a7','u2','p3','AC',49),('a8','u2','p4','AC',88),('a9','u2','p5','WA',120),
('a10','u3','p1','AC',22),('a11','u3','p2','WA',70),('a12','u3','p2','AC',54),
('a13','u4','p1','WA',31),('a14','u4','p6','WA',45),
('a15','u5','p1','AC',12),('a16','u5','p3','AC',42),('a17','u5','p4','AC',80),('a18','u5','p5','AC',91),('a19','u5','p6','AC',24),
('a20','u6','p1','AC',20),('a21','u6','p3','WA',65),('a22','u6','p6','AC',29);

INSERT INTO sql_challenges (id,title,description,difficulty,reference_query,max_score,order_sensitive) VALUES
('sql1','Rating Gate','Return username and rating for every arena user whose rating is greater than 1400. Sort by rating from highest to lowest.','Easy','SELECT username, rating FROM arena_users WHERE rating > 1400 ORDER BY rating DESC',1000,1),
('sql2','Accepted Count','Return each username and the number of accepted submissions they have. Include only users with at least one accepted submission and sort by accepted_count descending, then username ascending.','Easy','SELECT u.username, COUNT(*) AS accepted_count FROM arena_users u JOIN arena_submissions s ON s.user_id = u.user_id WHERE s.verdict = ''AC'' GROUP BY u.user_id, u.username HAVING COUNT(*) > 0 ORDER BY accepted_count DESC, u.username ASC',1000,1),
('sql3','University Rating Board','Return university and average rating for all universities. Round the average rating to 2 decimal places and sort highest first.','Medium','SELECT university, ROUND(AVG(rating), 2) AS avg_rating FROM arena_users GROUP BY university ORDER BY avg_rating DESC',1000,1),
('sql4','Unsolved Problems','Return problem_id and title for problems that have never received an accepted submission. Sort by problem_id.','Medium','SELECT p.problem_id, p.title FROM arena_problems p LEFT JOIN arena_submissions s ON s.problem_id = p.problem_id AND s.verdict = ''AC'' WHERE s.submission_id IS NULL ORDER BY p.problem_id',1000,1),
('sql5','Above-Average Solvers','Return usernames whose number of distinct accepted problems is greater than the average distinct accepted-problem count across all users. Sort by username.','Hard','SELECT u.username FROM arena_users u JOIN arena_submissions s ON s.user_id = u.user_id AND s.verdict = ''AC'' GROUP BY u.user_id, u.username HAVING COUNT(DISTINCT s.problem_id) > (SELECT AVG(solved_count) FROM (SELECT au.user_id, COUNT(DISTINCT ase.problem_id) AS solved_count FROM arena_users au LEFT JOIN arena_submissions ase ON ase.user_id = au.user_id AND ase.verdict = ''AC'' GROUP BY au.user_id) x) ORDER BY u.username',1000,1),
('sql6','Fastest Accepted Run','Return each problem title, the fastest accepted runtime, and the username that achieved it. Sort by problem title.','Hard','SELECT p.title, s.runtime_ms AS fastest_runtime, u.username FROM arena_submissions s JOIN arena_problems p ON p.problem_id = s.problem_id JOIN arena_users u ON u.user_id = s.user_id WHERE s.verdict = ''AC'' AND s.runtime_ms = (SELECT MIN(s2.runtime_ms) FROM arena_submissions s2 WHERE s2.problem_id = s.problem_id AND s2.verdict = ''AC'') ORDER BY p.title',1000,1);

INSERT INTO sql_battles (id,challenge_id,player1_id,player2_id,status,winner_id,created_at,completed_at) VALUES
('sb_seed','sql1','u1','u2','completed','u2','2026-08-24 19:00:00','2026-08-24 19:04:00');
INSERT INTO sql_attempts (id,battle_id,challenge_id,user_id,submitted_query,status,execution_time_ms,efficiency_score,score,feedback,submitted_at) VALUES
('sa_seed1','sb_seed','sql1','u1','SELECT username, rating FROM arena_users WHERE rating > 1400 ORDER BY rating DESC','accepted',0.720,100,930,'Correct result set.','2026-08-24 19:02:00'),
('sa_seed2','sb_seed','sql1','u2','SELECT username, rating FROM arena_users WHERE rating > 1400 ORDER BY rating DESC','accepted',0.510,100,955,'Correct result set.','2026-08-24 19:04:00');

INSERT INTO activity_logs (user_id,action,details,created_at) VALUES
('u1','ghost_race.completed','Beat Tamjid ghost on Hello World Logic','2026-08-23 14:00:35'),
('u2','sql_battle.won','Won SQL Battle sb_seed','2026-08-24 19:04:00');

SET FOREIGN_KEY_CHECKS = 1;
