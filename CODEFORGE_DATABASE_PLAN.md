# CodeForge Database Architecture Plan

> **Status:** Planning only — no database or application logic has been modified.

---

## 1. Database Engine and Version

| Property | Value |
|----------|-------|
| **Engine** | MariaDB 10.4+ / MySQL 8+ |
| **Charset** | `utf8mb4` |
| **Collation** | `utf8mb4_unicode_ci` (SQL dump) vs `utf8mb4_general_ci` (`.env` mismatch) |
| **Default storage** | InnoDB |

**Source:** `backend/database/legacy_schema_and_seed.sql` (line 2)  
**Configured in:** `backend/.env` (`DB_COLLATION=utf8mb4_general_ci`)

---

## 2. Database Name

| Property | Value |
|----------|-------|
| **Configured name** | `project` |
| **Host** | `127.0.0.1` |
| **Port** | `3307` (XAMPP) |
| **Username** | `root` |
| **Password** | *(empty)* |

**Source:** `backend/.env` (lines 12–16)  
**Dump instruction:** "Import into an empty database named `project`" (`legacy_schema_and_seed.sql`, line 3)

---

## 3. SQL Tables — Full Schema Reference

The SQL dump (`backend/database/legacy_schema_and_seed.sql`) is the **source of truth** for the core CodeForge tables. Laravel migrations also define additional learning-management tables.

### 3.1 Core Tables (from SQL dump)

#### `universities`
| Column | Type | Nullable | Default | Key |
|--------|------|----------|---------|-----|
| `name` | `VARCHAR(255)` | NO | — | **PK** |
| `city` | `VARCHAR(120)` | YES | NULL | — |
| `created_at` | `DATETIME` | NO | `CURRENT_TIMESTAMP` | — |

**Indexes:** `name` (PK)

#### `users`
| Column | Type | Nullable | Default | Key |
|--------|------|----------|---------|-----|
| `id` | `VARCHAR(50)` | NO | — | **PK** |
| `username` | `VARCHAR(100)` | NO | — | UNIQUE |
| `password` | `VARCHAR(255)` | NO | — | — |
| `role` | `ENUM('user','admin')` | NO | `'user'` | — |
| `rating` | `INT` | NO | `1200` | INDEX (`idx_users_rating`) |
| `university` | `VARCHAR(255)` | YES | NULL | FK → `universities.name)`; INDEX (`idx_users_university`) |
| `rank` | `VARCHAR(50)` | NO | `'Newbie'` | — |
| `created_at` | `DATETIME` | NO | `CURRENT_TIMESTAMP` | — |

**Foreign Keys:**
- `fk_users_university`: `university` → `universities(name)` ON UPDATE CASCADE ON DELETE SET NULL

#### `problems`
| Column | Type | Nullable | Default | Key |
|--------|------|----------|---------|-----|
| `id` | `VARCHAR(50)` | NO | — | **PK** |
| `title` | `VARCHAR(255)` | NO | — | — |
| `topic` | `VARCHAR(100)` | NO | — | INDEX (`idx_problems_topic`) |
| `difficulty` | `ENUM('Easy','Medium','Hard')` | NO | `'Easy'` | INDEX (`idx_problems_difficulty`) |
| `description` | `TEXT` | NO | — | — |
| `tags` | `VARCHAR(255)` | YES | NULL | — |
| `starter_code` | `TEXT` | YES | NULL | — |
| `created_at` | `DATETIME` | NO | `CURRENT_TIMESTAMP` | — |

#### `contests`
| Column | Type | Nullable | Default | Key |
|--------|------|----------|---------|-----|
| `id` | `VARCHAR(50)` | NO | — | **PK** |
| `name` | `VARCHAR(255)` | NO | — | — |
| `type` | `ENUM('Global','Local','Duel')` | NO | `'Local'` | — |
| `starts_at` | `DATETIME` | NO | — | INDEX (`idx_contests_starts_at`) |
| `status` | `ENUM('Upcoming','Active','Past')` | NO | `'Upcoming'` | INDEX (`idx_contests_status`) |
| `created_by` | `VARCHAR(50)` | YES | NULL | FK → `users(id)` ON DELETE SET NULL |
| `created_at` | `DATETIME` | NO | `CURRENT_TIMESTAMP` | — |

**Foreign Keys:**
- `fk_contests_creator`: `created_by` → `users(id)` ON DELETE SET NULL

#### `contest_problems` (junction / pivot)
| Column | Type | Nullable | Default | Key |
|--------|------|----------|---------|-----|
| `contest_id` | `VARCHAR(50)` | NO | — | **PK part 1** |
| `problem_id` | `VARCHAR(50)` | NO | — | **PK part 2** |
| `points` | `INT` | NO | `100` | — |

**Foreign Keys:**
- `fk_cp_contest`: `contest_id` → `contests(id)` ON DELETE CASCADE
- `fk_cp_problem`: `problem_id` → `problems(id)` ON DELETE CASCADE

#### `contest_participants` (junction / pivot)
| Column | Type | Nullable | Default | Key |
|--------|------|----------|---------|-----|
| `contest_id` | `VARCHAR(50)` | NO | — | **PK part 1** |
| `user_id` | `VARCHAR(50)` | NO | — | **PK part 2** |
| `score` | `INT` | NO | `0` | — |
| `joined_at` | `DATETIME` | NO | `CURRENT_TIMESTAMP` | — |

**Indexes:** `idx_cpa_user` on `user_id`

**Foreign Keys:**
- `fk_cpa_contest`: `contest_id` → `contests(id)` ON DELETE CASCADE
- `fk_cpa_user`: `user_id` → `users(id)` ON DELETE CASCADE

#### `problem_sessions`
| Column | Type | Nullable | Default | Key |
|--------|------|----------|---------|-----|
| `id` | `VARCHAR(64)` | NO | — | **PK** |
| `user_id` | `VARCHAR(50)` | NO | — | FK → `users(id)` ON DELETE CASCADE |
| `problem_id` | `VARCHAR(50)` | NO | — | FK → `problems(id)` ON DELETE CASCADE |
| `started_at` | `DATETIME` | NO | — | — |
| `completed_at` | `DATETIME` | YES | NULL | — |
| `solve_time_seconds` | `INT` | YES | NULL | — |
| `status` | `ENUM('active','solved','abandoned')` | NO | `'active'` | — |
| `created_at` | `DATETIME` | NO | `CURRENT_TIMESTAMP` | — |

**Indexes:**
- `idx_ps_user_problem` (`user_id`, `problem_id`)
- `idx_ps_status` (`status`)
- `idx_ps_user_problem_status_started` (`user_id`, `problem_id`, `status`, `started_at`)
- `idx_ps_status_user_problem_solve` (`status`, `user_id`, `problem_id`, `solve_time_seconds`)

#### `submissions`
| Column | Type | Nullable | Default | Key |
|--------|------|----------|---------|-----|
| `id` | `VARCHAR(64)` | NO | — | **PK** |
| `session_id` | `VARCHAR(64)` | YES | NULL | FK → `problem_sessions(id)` ON DELETE SET NULL |
| `problem_id` | `VARCHAR(50)` | NO | — | FK → `problems(id)` ON DELETE CASCADE |
| `user_id` | `VARCHAR(50)` | NO | — | FK → `users(id)` ON DELETE CASCADE |
| `contest_id` | `VARCHAR(50)` | YES | NULL | FK → `contests(id)` ON DELETE SET NULL |
| `verdict` | `ENUM('AC','WA','TLE','MLE','RE','CE')` | NO | — | — |
| `submitted_at` | `DATETIME` | NO | `CURRENT_TIMESTAMP` | — |
| `elapsed_seconds` | `INT` | YES | NULL | — |
| `runtime_ms` | `INT` | YES | NULL | — |
| `memory_kb` | `INT` | YES | NULL | — |
| `language` | `VARCHAR(50)` | NO | `'C++'` | — |
| `source_code` | `MEDIUMTEXT` | YES | NULL | — |
| `failed_test_case` | `INT` | YES | NULL | — |

**Indexes:**
- `idx_sub_user` (`user_id`)
- `idx_sub_problem` (`problem_id`)
- `idx_sub_user_verdict` (`user_id`, `verdict`)
- `idx_sub_session_elapsed` (`session_id`, `elapsed_seconds`)
- `idx_sub_user_time` (`user_id`, `submitted_at`, `id`)
- `idx_sub_user_verdict_problem` (`user_id`, `verdict`, `problem_id`)
- `idx_sub_problem_verdict_user` (`problem_id`, `verdict`, `user_id`)
- `idx_sub_contest_user_problem_verdict` (`contest_id`, `user_id`, `problem_id`, `verdict`)

#### `ghost_races`
| Column | Type | Nullable | Default | Key |
|--------|------|----------|---------|-----|
| `id` | `VARCHAR(64)` | NO | — | **PK** |
| `challenger_id` | `VARCHAR(50)` | NO | — | FK → `users(id)` ON DELETE CASCADE |
| `ghost_user_id` | `VARCHAR(50)` | NO | — | FK → `users(id)` ON DELETE CASCADE |
| `problem_id` | `VARCHAR(50)` | NO | — | FK → `problems(id)` ON DELETE CASCADE |
| `ghost_session_id` | `VARCHAR(64)` | NO | — | FK → `problem_sessions(id)` ON DELETE CASCADE |
| `challenger_session_id` | `VARCHAR(64)` | NO | — | FK → `problem_sessions(id)` ON DELETE CASCADE |
| `playback_speed` | `TINYINT` | NO | `4` | — |
| `started_at` | `DATETIME` | NO | `CURRENT_TIMESTAMP` | — |
| `finished_at` | `DATETIME` | YES | NULL | — |
| `result` | `ENUM('active','won','lost','draw','forfeit')` | NO | `'active'` | — |
| `challenger_time` | `INT` | YES | NULL | — |
| `ghost_time` | `INT` | NO | — | — |

**Indexes:**
- `idx_gr_challenger` (`challenger_id`, `started_at`)
- `idx_gr_challenger_result_started` (`challenger_id`, `result`, `started_at`)
- `idx_gr_challenger_session_result` (`challenger_session_id`, `result`)

#### `sql_challenges`
| Column | Type | Nullable | Default | Key |
|--------|------|----------|---------|-----|
| `id` | `VARCHAR(50)` | NO | — | **PK** |
| `title` | `VARCHAR(255)` | NO | — | — |
| `description` | `TEXT` | NO | — | — |
| `difficulty` | `ENUM('Easy','Medium','Hard')` | NO | — | INDEX (`idx_sql_challenge_difficulty`) |
| `reference_query` | `TEXT` | NO | — | — |
| `max_score` | `INT` | NO | `1000` | — |
| `order_sensitive` | `TINYINT(1)` | NO | `0` | — |
| `created_at` | `DATETIME` | NO | `CURRENT_TIMESTAMP` | — |

#### `sql_battles`
| Column | Type | Nullable | Default | Key |
|--------|------|----------|---------|-----|
| `id` | `VARCHAR(64)` | NO | — | **PK** |
| `challenge_id` | `VARCHAR(50)` | NO | — | FK → `sql_challenges(id)` ON DELETE CASCADE |
| `player1_id` | `VARCHAR(50)` | NO | — | FK → `users(id)` ON DELETE CASCADE |
| `player2_id` | `VARCHAR(50)` | NO | — | FK → `users(id)` ON DELETE CASCADE |
| `status` | `ENUM('active','completed','cancelled')` | NO | `'active'` | — |
| `winner_id` | `VARCHAR(50)` | YES | NULL | FK → `users(id)` ON DELETE SET NULL |
| `created_at` | `DATETIME` | NO | `CURRENT_TIMESTAMP` | — |
| `completed_at` | `DATETIME` | YES | NULL | — |

**Indexes:**
- `idx_sb_players` (`player1_id`, `player2_id`)
- `idx_sb_challenge_status_players` (`challenge_id`, `status`, `player1_id`, `player2_id`)
- `idx_sb_status` (`status`)
- `idx_sb_player1_created` (`player1_id`, `created_at`)
- `idx_sb_player2_created` (`player2_id`, `created_at`)

#### `sql_attempts`
| Column | Type | Nullable | Default | Key |
|--------|------|----------|---------|-----|
| `id` | `VARCHAR(64)` | NO | — | **PK** |
| `battle_id` | `VARCHAR(64)` | YES | NULL | FK → `sql_battles(id)` ON DELETE CASCADE |
| `challenge_id` | `VARCHAR(50)` | NO | — | FK → `sql_challenges(id)` ON DELETE CASCADE |
| `user_id` | `VARCHAR(50)` | NO | — | FK → `users(id)` ON DELETE CASCADE |
| `submitted_query` | `TEXT` | NO | — | — |
| `status` | `ENUM('accepted','wrong_answer','rejected','error')` | NO | — | — |
| `execution_time_ms` | `DECIMAL(10,3)` | YES | NULL | — |
| `efficiency_score` | `INT` | NO | `0` | — |
| `score` | `INT` | NO | `0` | — |
| `feedback` | `VARCHAR(500)` | YES | NULL | — |
| `submitted_at` | `DATETIME` | NO | `CURRENT_TIMESTAMP` | — |

**Indexes:**
- `idx_sa_user` (`user_id`, `submitted_at`)
- `idx_sa_battle_user` (`battle_id`, `user_id`)
- `idx_sa_challenge_status` (`challenge_id`, `status`)
- `idx_sa_battle_status_user_score` (`battle_id`, `status`, `user_id`, `score`)

#### `activity_logs`
| Column | Type | Nullable | Default | Key |
|--------|------|----------|---------|-----|
| `id` | `BIGINT UNSIGNED` | NO | AUTO_INCREMENT | **PK** |
| `user_id` | `VARCHAR(50)` | YES | NULL | FK → `users(id)` ON DELETE SET NULL |
| `action` | `VARCHAR(100)` | NO | — | — |
| `details` | `VARCHAR(500)` | YES | NULL | — |
| `created_at` | `DATETIME` | NO | `CURRENT_TIMESTAMP` | — |

**Indexes:**
- `idx_activity_created` (`created_at`)
- `idx_activity_user_created` (`user_id`, `created_at`)

### 3.2 Arena Sandbox Tables (from SQL dump)

#### `arena_universities`
| Column | Type | Nullable | Default | Key |
|--------|------|----------|---------|-----|
| `name` | `VARCHAR(120)` | NO | — | **PK** |

#### `arena_users`
| Column | Type | Nullable | Default | Key |
|--------|------|----------|---------|-----|
| `user_id` | `VARCHAR(20)` | NO | — | **PK** |
| `username` | `VARCHAR(100)` | NO | — | — |
| `university` | `VARCHAR(120)` | NO | — | FK → `arena_universities(name)` ON UPDATE CASCADE ON DELETE CASCADE |
| `rating` | `INT` | NO | — | — |

#### `arena_problems`
| Column | Type | Nullable | Default | Key |
|--------|------|----------|---------|-----|
| `problem_id` | `VARCHAR(20)` | NO | — | **PK** |
| `title` | `VARCHAR(120)` | NO | — | — |
| `topic` | `VARCHAR(80)` | NO | — | — |
| `difficulty` | `VARCHAR(20)` | NO | — | — |

#### `arena_submissions`
| Column | Type | Nullable | Default | Key |
|--------|------|----------|---------|-----|
| `submission_id` | `VARCHAR(20)` | NO | — | **PK** |
| `user_id` | `VARCHAR(20)` | NO | — | FK → `arena_users(user_id)` ON DELETE CASCADE |
| `problem_id` | `VARCHAR(20)` | NO | — | FK → `arena_problems(problem_id)` ON DELETE CASCADE |
| `verdict` | `VARCHAR(10)` | NO | — | — |
| `runtime_ms` | `INT` | NO | — | — |

**Indexes:**
- `idx_as_user` (`user_id`)
- `idx_as_problem` (`problem_id`)
- `idx_as_verdict` (`verdict`)

### 3.3 Learning-Management Tables (from migrations — NOT in SQL dump)

#### `learning_modules`
| Column | Type | Nullable | Default | Key |
|--------|------|----------|---------|-----|
| `id` | `VARCHAR(50)` | NO | — | **PK** |
| `title` | `VARCHAR(255)` | NO | — | — |
| `slug` | `VARCHAR(255)` | NO | — | UNIQUE |
| `topic` | `VARCHAR(100)` | NO | — | INDEX |
| `description` | `TEXT` | YES | NULL | — |
| `difficulty` | `ENUM('Beginner','Easy','Medium','Hard')` | NO | `'Easy'` | INDEX |
| `estimated_minutes` | `INT` | NO | `15` | — |
| `xp_reward` | `INT` | NO | `50` | — |
| `is_active` | `BOOLEAN` | NO | `true` | INDEX |
| `created_at` | `DATETIME` | NO | `CURRENT_TIMESTAMP` | — |

> **Note:** The `Beginner` difficulty value was added by migration `2026_09_11_000350`. The SQL dump only contains `Easy`, `Medium`, `Hard`.

#### `learning_steps`
| Column | Type | Nullable | Default | Key |
|--------|------|----------|---------|-----|
| `id` | `VARCHAR(50)` | NO | — | **PK** |
| `learning_module_id` | `VARCHAR(50)` | NO | — | FK → `learning_modules(id)` ON DELETE CASCADE |
| `step_order` | `INT` | NO | — | — |
| `type` | `ENUM('explanation','mcq','prediction','visual','code_trace')` | NO | — | INDEX |
| `title` | `VARCHAR(255)` | NO | — | — |
| `content` | `TEXT` | YES | NULL | — |
| `question` | `TEXT` | YES | NULL | — |
| `options` | `JSON` | YES | NULL | — |
| `correct_answer` | `VARCHAR(255)` | YES | NULL | — |
| `xp_reward` | `INT` | NO | `10` | — |
| `created_at` | `DATETIME` | NO | `CURRENT_TIMESTAMP` | — |

**Indexes:** `idx_ls_module_order` (`learning_module_id`, `step_order`)

#### `play_challenges`
| Column | Type | Nullable | Default | Key |
|--------|------|----------|---------|-----|
| `id` | `VARCHAR(50)` | NO | — | **PK** |
| `learning_module_id` | `VARCHAR(50)` | NO | — | FK → `learning_modules(id)` ON DELETE CASCADE |
| `type` | `ENUM('coding','debug','optimize','fill_blank','trace')` | NO | `'coding'` | INDEX |
| `title` | `VARCHAR(255)` | NO | — | — |
| `instructions` | `TEXT` | YES | NULL | — |
| `config` | `JSON` | YES | NULL | — |
| `xp_reward` | `INT` | NO | `25` | — |
| `time_limit` | `INT` | YES | NULL | — |
| `created_at` | `DATETIME` | NO | `CURRENT_TIMESTAMP` | — |

**Indexes:** `idx_pc_module_type` (`learning_module_id`, `type`)

#### `learning_problems`
| Column | Type | Nullable | Default | Key |
|--------|------|----------|---------|-----|
| `id` | `VARCHAR(50)` | NO | — | **PK** |
| `learning_module_id` | `VARCHAR(50)` | NO | — | FK → `learning_modules(id)` ON DELETE CASCADE |
| `problem_id` | `VARCHAR(50)` | YES | NULL | FK → `problems(id)` ON DELETE SET NULL |
| `stage` | `ENUM('practice','challenge','boss')` | NO | `'practice'` | INDEX |
| `sort_order` | `INT` | NO | `0` | — |
| `created_at` | `DATETIME` | NO | `CURRENT_TIMESTAMP` | — |

**Indexes:** `idx_lp_module_stage_order` (`learning_module_id`, `stage`, `sort_order`)

#### `learning_progress`
| Column | Type | Nullable | Default | Key |
|--------|------|----------|---------|-----|
| `id` | `VARCHAR(50)` | NO | — | **PK** |
| `user_id` | `VARCHAR(50)` | NO | — | FK → `users(id)` ON DELETE CASCADE |
| `learning_module_id` | `VARCHAR(50)` | NO | — | FK → `learning_modules(id)` ON DELETE CASCADE |
| `learn_completed` | `BOOLEAN` | NO | `false` | INDEX |
| `play_completed` | `BOOLEAN` | NO | `false` | INDEX |
| `prove_completed` | `BOOLEAN` | NO | `false` | INDEX |
| `learn_score` | `INT` | YES | NULL | — |
| `play_score` | `INT` | YES | NULL | — |
| `prove_score` | `INT` | YES | NULL | — |
| `mastery_score` | `INT` | YES | NULL | — |
| `attempts` | `INT` | NO | `0` | — |
| `hints_used` | `INT` | NO | `0` | — |
| `started_at` | `DATETIME` | YES | NULL | — |
| `completed_at` | `DATETIME` | YES | NULL | — |
| `created_at` | `DATETIME` | NO | `CURRENT_TIMESTAMP` | — |
| `learn_accuracy` | `INT` | YES | NULL | — |
| `play_accuracy` | `INT` | YES | NULL | — |
| `prove_accuracy` | `INT` | YES | NULL | — |
| `concept_mastery` | `INT` | YES | NULL | — |
| `weakness_signal` | `VARCHAR(255)` | YES | NULL | — |
| `repeated_failed_concepts` | `JSON` | YES | NULL | — |
| `learn_attempts` | `INT` | NO | `0` | — |
| `play_attempts` | `INT` | NO | `0` | — |
| `prove_attempts` | `INT` | NO | `0` | — |
| `learn_correct` | `INT` | NO | `0` | — |
| `play_correct` | `INT` | NO | `0` | — |
| `prove_correct` | `INT` | NO | `0` | — |
| `learn_completed_steps` | `JSON` | YES | NULL | — |

**Unique Constraints:** `uniq_user_module_progress` (`user_id`, `learning_module_id`)  
**Indexes:** `idx_lp_user_completion` (`user_id`, `learn_completed`, `play_completed`, `prove_completed`), `idx_lp_user_mastery` (`user_id`, `mastery_score`)

### 3.4 Laravel Framework Tables (from migrations)

These are standard Laravel support tables created by `0001_01_01_000000_create_laravel_support_tables.php`.

#### `sessions`
| Column | Type | Nullable | Default | Key |
|--------|------|----------|---------|-----|
| `id` | `VARCHAR(255)` | NO | — | **PK** |
| `user_id` | `VARCHAR(50)` | YES | NULL | INDEX |
| `ip_address` | `VARCHAR(45)` | YES | NULL | — |
| `user_agent` | `TEXT` | YES | NULL | — |
| `payload` | `LONGTEXT` | NO | — | — |
| `last_activity` | `INT` | NO | — | INDEX |

#### `cache`
| Column | Type | Nullable | Default | Key |
|--------|------|----------|---------|-----|
| `key` | `VARCHAR(255)` | NO | — | **PK** |
| `value` | `MEDIUMTEXT` | NO | — | — |
| `expiration` | `INT` | NO | — | — |

#### `cache_locks`
| Column | Type | Nullable | Default | Key |
|--------|------|----------|---------|-----|
| `key` | `VARCHAR(255)` | NO | — | **PK** |
| `owner` | `VARCHAR(255)` | NO | — | — |
| `expiration` | `INT` | NO | — | — |

#### `jobs`
| Column | Type | Nullable | Default | Key |
|--------|------|----------|---------|-----|
| `id` | `BIGINT UNSIGNED` | NO | AUTO_INCREMENT | **PK** |
| `queue` | `VARCHAR(255)` | NO | — | INDEX |
| `payload` | `LONGTEXT` | NO | — | — |
| `attempts` | `TINYINT UNSIGNED` | NO | — | — |
| `reserved_at` | `INT UNSIGNED` | YES | NULL | — |
| `available_at` | `INT UNSIGNED` | NO | — | — |
| `created_at` | `INT UNSIGNED` | NO | — | — |

#### `job_batches`
| Column | Type | Nullable | Default | Key |
|--------|------|----------|---------|-----|
| `id` | `VARCHAR(255)` | NO | — | **PK** |
| `name` | `VARCHAR(255)` | NO | — | — |
| `total_jobs` | `INT` | NO | — | — |
| `pending_jobs` | `INT` | NO | — | — |
| `failed_jobs` | `INT` | NO | — | — |
| `failed_job_ids` | `LONGTEXT` | NO | — | — |
| `options` | `MEDIUMTEXT` | YES | NULL | — |
| `cancelled_at` | `INT` | YES | NULL | — |
| `created_at` | `INT` | NO | — | — |
| `finished_at` | `INT` | YES | NULL | — |

#### `failed_jobs`
| Column | Type | Nullable | Default | Key |
|--------|------|----------|---------|-----|
| `id` | `BIGINT UNSIGNED` | NO | AUTO_INCREMENT | **PK** |
| `uuid` | `VARCHAR(255)` | NO | — | UNIQUE |
| `connection` | `TEXT` | NO | — | — |
| `queue` | `TEXT` | NO | — | — |
| `payload` | `LONGTEXT` | NO | — | — |
| `exception` | `LONGTEXT` | NO | — | — |
| `failed_at` | `TIMESTAMP` | NO | `CURRENT_TIMESTAMP` | — |

#### `personal_access_tokens`
| Column | Type | Nullable | Default | Key |
|--------|------|----------|---------|-----|
| `id` | `BIGINT UNSIGNED` | NO | AUTO_INCREMENT | **PK** |
| `tokenable_type` | `VARCHAR(255)` | NO | — | — |
| `tokenable_id` | `BIGINT UNSIGNED` | NO | — | — |
| `name` | `VARCHAR(255)` | NO | — | — |
| `token` | `VARCHAR(64)` | NO | — | UNIQUE |
| `abilities` | `TEXT` | YES | NULL | — |
| `last_used_at` | `TIMESTAMP` | YES | NULL | — |
| `expires_at` | `TIMESTAMP` | YES | NULL | INDEX |
| `created_at` | `TIMESTAMP` | NO | — | — |
| `updated_at` | `TIMESTAMP` | NO | — | — |

---

## 4. Table Relationships Summary

```
universities
    └── 1:N → users (via users.university → universities.name)

users
    ├── 1:N → contests (created_by)
    ├── 1:N → problem_sessions
    ├── 1:N → submissions
    ├── 1:N → contest_participants
    ├── 1:N → ghost_races (challenger_id)
    ├── 1:N → ghost_races (ghost_user_id)
    ├── 1:N → sql_battles (player1_id)
    ├── 1:N → sql_battles (player2_id)
    ├── 1:N → sql_battles (winner_id)
    ├── 1:N → sql_attempts
    ├── 1:N → activity_logs
    └── 1:N → learning_progress

problems
    ├── 1:N → problem_sessions
    ├── 1:N → submissions
    ├── 1:N → contest_problems
    ├── 1:N → ghost_races
    ├── 1:N → learning_problems
    └── N:M → contests (via contest_problems)

contests
    ├── 1:N → contest_problems
    ├── 1:N → contest_participants
    └── 1:N → submissions

problem_sessions
    └── 1:N → submissions
    └── 1:N → ghost_races (ghost_session_id)
    └── 1:N → ghost_races (challenger_session_id)

sql_challenges
    ├── 1:N → sql_battles
    └── 1:N → sql_attempts

sql_battles
    ├── 1:N → sql_attempts
    └── winner → users (nullable)

arena_universities
    └── 1:N → arena_users

arena_users
    └── 1:N → arena_submissions

arena_problems
    └── 1:N → arena_submissions

learning_modules
    ├── 1:N → learning_steps
    ├── 1:N → play_challenges
    ├── 1:N → learning_problems
    └── 1:N → learning_progress

users
    └── 1:N → learning_progress
```

---

## 5. Existing Backend Models

| Model | File | Table | Primary Key | Key Type | Timestamps | Notes |
|-------|------|-------|-------------|----------|------------|-------|
| `User` | `app/Models/User.php` | `users` | `id` | `string` | No | Uses `HasApiTokens` (Sanctum) |
| `University` | `app/Models/University.php` | `universities` | `name` | `string` | No | — |
| `Problem` | `app/Models/Problem.php` | `problems` | `id` | `string` | No | — |
| `Contest` | `app/Models/Contest.php` | `contests` | `id` | `string` | No | — |
| `ContestProblem` | `app/Models/ContestProblem.php` | `contest_problems` | `(contest_id, problem_id)` | composite | No | `$primaryKey = null` |
| `ContestParticipant` | `app/Models/ContestParticipant.php` | `contest_participants` | `(contest_id, user_id)` | composite | No | `$primaryKey = null` |
| `ProblemSession` | `app/Models/ProblemSession.php` | `problem_sessions` | `id` | `string` | No | — |
| `Submission` | `app/Models/Submission.php` | `submissions` | `id` | `string` | No | — |
| `GhostRace` | `app/Models/GhostRace.php` | `ghost_races` | `id` | `string` | No | — |
| `SqlChallenge` | `app/Models/SqlChallenge.php` | `sql_challenges` | `id` | `string` | No | `order_sensitive` cast to `boolean` |
| `SqlBattle` | `app/Models/SqlBattle.php` | `sql_battles` | `id` | `string` | No | — |
| `SqlAttempt` | `app/Models/SqlAttempt.php` | `sql_attempts` | `id` | `string` | No | — |
| `ActivityLog` | `app/Models/ActivityLog.php` | `activity_logs` | `id` | `int` (auto) | No | — |
| `ArenaUniversity` | `app/Models/ArenaUniversity.php` | `arena_universities` | `name` | `string` | No | — |
| `ArenaUser` | `app/Models/ArenaUser.php` | `arena_users` | `user_id` | `string` | No | — |
| `ArenaProblem` | `app/Models/ArenaProblem.php` | `arena_problems` | `problem_id` | `string` | No | — |
| `ArenaSubmission` | `app/Models/ArenaSubmission.php` | `arena_submissions` | `submission_id` | `string` | No | — |
| `LearningModule` | `app/Models/LearningModule.php` | `learning_modules` | `id` | `string` | No | `is_active` cast to `boolean` |
| `LearningStep` | `app/Models/LearningStep.php` | `learning_steps` | `id` | `string` | No | `options` cast to `array` |
| `PlayChallenge` | `app/Models/PlayChallenge.php` | `play_challenges` | `id` | `string` | No | `config` cast to `array` |
| `LearningProblem` | `app/Models/LearningProblem.php` | `learning_problems` | `id` | `string` | No | — |
| `LearningProgress` | `app/Models/LearningProgress.php` | `learning_progress` | `id` | `string` | No | Many boolean/cast fields |

---

## 6. Existing Migrations

| Migration File | Purpose |
|----------------|---------|
| `0001_01_01_000000_create_laravel_support_tables.php` | Creates Laravel framework tables: `sessions`, `cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs`, `personal_access_tokens` |
| `2026_09_01_000100_adopt_or_create_codeforge_schema.php` | Creates all core CodeForge tables if they do not exist (idempotent / adoption migration) |
| `2026_09_01_000150_add_user_id_to_submissions_table.php` | Adds `user_id` column to `submissions` if missing |
| `2026_09_01_000200_optimize_existing_codeforge_indexes.php` | Adds missing composite indexes to `submissions`, `problem_sessions`, `ghost_races`, `sql_attempts`, `sql_battles`, `activity_logs` |
| `2026_09_11_000300_create_learning_modules_table.php` | Creates `learning_modules` |
| `2026_09_11_000310_create_learning_steps_table.php` | Creates `learning_steps` |
| `2026_09_11_000320_create_play_challenges_table.php` | Creates `play_challenges` |
| `2026_09_11_000330_create_learning_problems_table.php` | Creates `learning_problems` |
| `2026_09_11_000340_create_learning_progress_table.php` | Creates `learning_progress` |
| `2026_09_11_000350_add_beginner_to_learning_modules_difficulty.php` | Adds `Beginner` to `learning_modules.difficulty` ENUM |
| `2026_09_11_000360_add_learning_metrics_to_progress_table.php` | Adds accuracy / attempts / correctness metrics to `learning_progress` |
| `2026_09_11_000370_add_learn_completed_steps_to_progress_table.php` | Adds `learn_completed_steps` JSON column to `learning_progress` |

---

## 7. Existing Seeders

| Seeder | File | Purpose |
|--------|------|---------|
| `DatabaseSeeder` | `database/seeders/DatabaseSeeder.php` | Orchestrator — calls `CodeForgeSeeder`, `LearningModuleSeeder`, `UniversityProfileSeeder` |
| `CodeForgeSeeder` | `database/seeders/CodeForgeSeeder.php` | Seeds core demo data: universities, users, problems, contests, sessions, submissions, arena data, sql_challenges. Skips if `users` table is not empty |
| `UniversityProfileSeeder` | `database/seeders/UniversityProfileSeeder.php` | Upserts 10 universities, assigns users to universities deterministically, syncs `arena_universities` and `arena_users` |
| `LearningModuleSeeder` | `database/seeders/LearningModuleSeeder.php` | Seeds one learning module (`lm1` — Binary Search), its steps, play challenges, and linked problem |

---

## 8. Enums / Status Fields

| Table | Column | Values |
|-------|--------|--------|
| `users` | `role` | `user`, `admin` |
| `problems` | `difficulty` | `Easy`, `Medium`, `Hard` |
| `contests` | `type` | `Global`, `Local`, `Duel` |
| `contests` | `status` | `Upcoming`, `Active`, `Past` |
| `problem_sessions` | `status` | `active`, `solved`, `abandoned` |
| `submissions` | `verdict` | `AC`, `WA`, `TLE`, `MLE`, `RE`, `CE` |
| `ghost_races` | `result` | `active`, `won`, `lost`, `draw`, `forfeit` |
| `sql_challenges` | `difficulty` | `Easy`, `Medium`, `Hard` |
| `sql_battles` | `status` | `active`, `completed`, `cancelled` |
| `sql_attempts` | `status` | `accepted`, `wrong_answer`, `rejected`, `error` |
| `learning_modules` | `difficulty` | `Beginner`, `Easy`, `Medium`, `Hard` |
| `learning_steps` | `type` | `explanation`, `mcq`, `prediction`, `visual`, `code_trace` |
| `play_challenges` | `type` | `coding`, `debug`, `optimize`, `fill_blank`, `trace` |
| `learning_problems` | `stage` | `practice`, `challenge`, `boss` |

---

## 9. Nullable Fields

| Table | Nullable Columns |
|-------|------------------|
| `universities` | `city` |
| `users` | `university` |
| `problems` | `tags`, `starter_code` |
| `contests` | `created_by` |
| `problem_sessions` | `completed_at`, `solve_time_seconds` |
| `submissions` | `session_id`, `contest_id`, `elapsed_seconds`, `runtime_ms`, `memory_kb`, `source_code`, `failed_test_case` |
| `ghost_races` | `finished_at`, `challenger_time` |
| `sql_battles` | `winner_id`, `completed_at` |
| `sql_attempts` | `battle_id`, `execution_time_ms`, `feedback` |
| `activity_logs` | `user_id`, `details` |
| `arena_problems` | *(none)* |
| `arena_submissions` | *(none)* |
| `learning_modules` | `description` |
| `learning_steps` | `content`, `question`, `options`, `correct_answer` |
| `play_challenges` | `instructions`, `config`, `time_limit` |
| `learning_problems` | `problem_id` |
| `learning_progress` | `learn_score`, `play_score`, `prove_score`, `mastery_score`, `started_at`, `completed_at`, `learn_accuracy`, `play_accuracy`, `prove_accuracy`, `concept_mastery`, `weakness_signal`, `repeated_failed_concepts`, `learn_completed_steps` |

---

## 10. Timestamp Fields

| Table | Timestamp Columns | Default |
|-------|-------------------|---------|
| `universities` | `created_at` | `CURRENT_TIMESTAMP` |
| `users` | `created_at` | `CURRENT_TIMESTAMP` |
| `problems` | `created_at` | `CURRENT_TIMESTAMP` |
| `contests` | `starts_at`, `created_at` | `starts_at` = none; `created_at` = `CURRENT_TIMESTAMP` |
| `contest_participants` | `joined_at` | `CURRENT_TIMESTAMP` |
| `problem_sessions` | `started_at`, `created_at` | `started_at` = none; `created_at` = `CURRENT_TIMESTAMP` |
| `submissions` | `submitted_at` | `CURRENT_TIMESTAMP` |
| `ghost_races` | `started_at` | `CURRENT_TIMESTAMP` |
| `sql_challenges` | `created_at` | `CURRENT_TIMESTAMP` |
| `sql_battles` | `created_at` | `CURRENT_TIMESTAMP` |
| `sql_attempts` | `submitted_at` | `CURRENT_TIMESTAMP` |
| `activity_logs` | `created_at` | `CURRENT_TIMESTAMP` |
| `learning_modules` | `created_at` | `CURRENT_TIMESTAMP` |
| `learning_steps` | `created_at` | `CURRENT_TIMESTAMP` |
| `play_challenges` | `created_at` | `CURRENT_TIMESTAMP` |
| `learning_problems` | `created_at` | `CURRENT_TIMESTAMP` |
| `learning_progress` | `started_at`, `completed_at`, `created_at` | `created_at` = `CURRENT_TIMESTAMP`; others nullable |

> **Note:** All models set `public $timestamps = false`, so Laravel does not manage `updated_at` automatically.

---

## 11. Indexes (Composite and Named)

| Table | Index Name | Columns |
|-------|------------|---------|
| `users` | `idx_users_university` | `university` |
| `users` | `idx_users_rating` | `rating` |
| `problems` | `idx_problems_topic` | `topic` |
| `problems` | `idx_problems_difficulty` | `difficulty` |
| `contests` | `idx_contests_status` | `status` |
| `contests` | `idx_contests_starts_at` | `starts_at` |
| `contest_participants` | `idx_cpa_user` | `user_id` |
| `problem_sessions` | `idx_ps_user_problem` | `user_id`, `problem_id` |
| `problem_sessions` | `idx_ps_status` | `status` |
| `problem_sessions` | `idx_ps_user_problem_status_started` | `user_id`, `problem_id`, `status`, `started_at` |
| `problem_sessions` | `idx_ps_status_user_problem_solve` | `status`, `user_id`, `problem_id`, `solve_time_seconds` |
| `submissions` | `idx_sub_user` | `user_id` |
| `submissions` | `idx_sub_problem` | `problem_id` |
| `submissions` | `idx_sub_user_verdict` | `user_id`, `verdict` |
| `submissions` | `idx_sub_session_elapsed` | `session_id`, `elapsed_seconds` |
| `submissions` | `idx_sub_user_time` | `user_id`, `submitted_at`, `id` |
| `submissions` | `idx_sub_user_verdict_problem` | `user_id`, `verdict`, `problem_id` |
| `submissions` | `idx_sub_problem_verdict_user` | `problem_id`, `verdict`, `user_id` |
| `submissions` | `idx_sub_contest_user_problem_verdict` | `contest_id`, `user_id`, `problem_id`, `verdict` |
| `ghost_races` | `idx_gr_challenger` | `challenger_id`, `started_at` |
| `ghost_races` | `idx_gr_challenger_result_started` | `challenger_id`, `result`, `started_at` |
| `ghost_races` | `idx_gr_challenger_session_result` | `challenger_session_id`, `result` |
| `sql_challenges` | `idx_sql_challenge_difficulty` | `difficulty` |
| `sql_battles` | `idx_sb_players` | `player1_id`, `player2_id` |
| `sql_battles` | `idx_sb_challenge_status_players` | `challenge_id`, `status`, `player1_id`, `player2_id` |
| `sql_battles` | `idx_sb_status` | `status` |
| `sql_battles` | `idx_sb_player1_created` | `player1_id`, `created_at` |
| `sql_battles` | `idx_sb_player2_created` | `player2_id`, `created_at` |
| `sql_attempts` | `idx_sa_user` | `user_id`, `submitted_at` |
| `sql_attempts` | `idx_sa_battle_user` | `battle_id`, `user_id` |
| `sql_attempts` | `idx_sa_challenge_status` | `challenge_id`, `status` |
| `sql_attempts` | `idx_sa_battle_status_user_score` | `battle_id`, `status`, `user_id`, `score` |
| `activity_logs` | `idx_activity_created` | `created_at` |
| `activity_logs` | `idx_activity_user_created` | `user_id`, `created_at` |
| `arena_submissions` | `idx_as_user` | `user_id` |
| `arena_submissions` | `idx_as_problem` | `problem_id` |
| `arena_submissions` | `idx_as_verdict` | `verdict` |
| `learning_modules` | *(topic)* | `topic` |
| `learning_modules` | *(difficulty)* | `difficulty` |
| `learning_modules` | *(is_active)* | `is_active` |
| `learning_steps` | `idx_ls_module_order` | `learning_module_id`, `step_order` |
| `learning_steps` | *(type)* | `type` |
| `play_challenges` | `idx_pc_module_type` | `learning_module_id`, `type` |
| `play_challenges` | *(type)* | `type` |
| `learning_problems` | `idx_lp_module_stage_order` | `learning_module_id`, `stage`, `sort_order` |
| `learning_problems` | *(stage)* | `stage` |
| `learning_progress` | `idx_lp_user_completion` | `user_id`, `learn_completed`, `play_completed`, `prove_completed` |
| `learning_progress` | `idx_lp_user_mastery` | `user_id`, `mastery_score` |
| `learning_progress` | `uniq_user_module_progress` | `user_id`, `learning_module_id` (UNIQUE) |
| `learning_progress` | *(learn_completed)* | `learn_completed` |
| `learning_progress` | *(play_completed)* | `play_completed` |
| `learning_progress` | *(prove_completed)* | `prove_completed` |

---

## 12. Schema / Backend Incompatibilities

### 12.1 Collation Mismatch
| Location | Value |
|----------|-------|
| **SQL dump** | `utf8mb4_unicode_ci` |
| **`.env`** | `utf8mb4_general_ci` |

**Impact:** The `.env` overrides the dump collation for new connections. Existing tables created from the dump retain their original collation unless explicitly altered.

### 12.2 `sql_challenges.order_sensitive` Type
| Location | Definition |
|----------|------------|
| **SQL dump** | `TINYINT(1) NOT NULL DEFAULT 0` |
| **Migration (`2026_09_01_000100`)** | `$t->boolean('order_sensitive')->default(false)` |

Laravel maps `boolean` to `TINYINT(1)`, so these are functionally equivalent. The model casts it to `boolean`.

### 12.3 `ghost_races.playback_speed` Type
| Location | Definition |
|----------|------------|
| **SQL dump** | `TINYINT NOT NULL DEFAULT 4` |
| **Migration (`2026_09_01_000100`)** | `$t->unsignedTinyInteger('playback_speed')->default(4)` |

Equivalent in storage.

### 12.4 `learning_modules.difficulty` ENUM Extension
| Location | Definition |
|----------|------------|
| **SQL dump** | Does not include `learning_modules` table |
| **Migration base (`2026_09_11_000300`)** | `ENUM('Easy','Medium','Hard') DEFAULT 'Easy'` |
| **Migration alter (`2026_09_11_000350`)** | `ENUM('Beginner','Easy','Medium','Hard') DEFAULT 'Easy'` |

A fresh import of the SQL dump **will not** create `learning_modules`. Running migrations after the import will create it with `Beginner` included.

### 12.5 Missing Indexes in Migration vs. SQL Dump
The migration `2026_09_01_000100` does **not** create several indexes that exist in the SQL dump:
- `idx_sub_user_verdict` on `submissions(user_id, verdict)` — added by `2026_09_01_000200`
- `idx_sub_user_verdict_problem` on `submissions(user_id, verdict, problem_id)` — added by `2026_09_01_000200`
- `idx_sub_problem_verdict_user` on `submissions(problem_id, verdict, user_id)` — added by `2026_09_01_000200`
- `idx_sub_user_time` on `submissions(user_id, submitted_at, id)` — added by `2026_09_01_000200`
- `idx_sub_contest_user_problem_verdict` on `submissions(contest_id, user_id, problem_id, verdict)` — added by `2026_09_01_000200`
- `idx_gr_challenger_result_started` on `ghost_races(challenger_id, result, started_at)` — added by `2026_09_01_000200`
- `idx_sb_challenge_status_players` on `sql_battles(challenge_id, status, player1_id, player2_id)` — added by `2026_09_01_000200`
- `idx_sb_player1_created` on `sql_battles(player1_id, created_at)` — added by `2026_09_01_000200`
- `idx_sb_player2_created` on `sql_battles(player2_id, created_at)` — added by `2026_09_01_000200`
- `idx_sa_battle_status_user_score` on `sql_attempts(battle_id, status, user_id, score)` — added by `2026_09_01_000200`
- `idx_activity_user_created` on `activity_logs(user_id, created_at)` — added by `2026_09_01_000200`

**Resolution:** Run `2026_09_01_000200` after the SQL import.

### 12.6 `activity_logs` Primary Key Difference
| Location | Definition |
|----------|------------|
| **SQL dump** | `id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY` |
| **Migration** | `$t->id()` |

Equivalent.

### 12.7 `universities` Primary Key as Foreign Key Target
`users.university` references `universities(name)`. This is a **string-to-string foreign key** using a non-PK column (`name`) as the target. This is valid but uncommon.

### 12.8 `submissions.user_id` Column Addition
The migration `2026_09_01_000150` adds `user_id` to `submissions` only if the column is missing. The SQL dump already includes `user_id`, so this migration is a no-op when the dump is imported first.

---

## 13. Safest Fresh-Database Procedure

> **CRITICAL RULES:**
> - Do **not** destroy any existing production database.
> - Do **not** modify an existing database automatically.
> - Do **not** delete user data.
> - Do **not** change `project (1).sql` (i.e., `legacy_schema_and_seed.sql`).
> - Do **not** change backend models yet.

### Recommended Steps for a Fresh Development Database

1. **Create a new, separate database** (e.g., `project_dev` or `codeforge_dev`).
   - Do **not** use the existing `project` database if it contains production data.

2. **Import the SQL dump** into the new database.
   ```bash
   mysql -u root -p project_dev < backend/database/legacy_schema_and_seed.sql
   ```

3. **Update `.env`** (or create a `.env.dev`) to point to the new database:
   ```
   DB_DATABASE=project_dev
   DB_COLLATION=utf8mb4_unicode_ci
   ```

4. **Run Laravel migrations** to add any missing tables/columns/indexes that the dump does not cover:
   ```bash
   cd backend
   php artisan migrate --force
   ```

5. **Run seeders** (optional, for demo data beyond what the dump provides):
   ```bash
   php artisan db:seed --force
   ```
   > **Warning:** `CodeForgeSeeder` skips seeding if `users` already exists. The SQL dump already seeds data, so seeders will only add learning modules and university profile updates.

6. **Verify:**
   - Confirm all tables exist.
   - Confirm indexes are present (especially those added by `2026_09_01_000200`).
   - Confirm collation consistency.

### Alternative: Pure Laravel Approach (No SQL Import)

If you prefer to let Laravel build the schema entirely:

1. Create an empty database.
2. Point `.env` to it.
3. Run `php artisan migrate --force`.
4. Run `php artisan db:seed --force`.

**Caveat:** This will **not** include the SQL-arena demo data or the specific demo rows from the dump. It also does not preserve the exact `created_at` timestamps from the dump.

---

## 14. Open Questions / Risks

| # | Question / Risk |
|---|-----------------|
| 1 | Is the `project` database currently in production use? If yes, never import the dump into it. |
| 2 | The `.env` specifies `DB_COLLATION=utf8mb4_general_ci`. Should this be aligned with the dump's `utf8mb4_unicode_ci`? |
| 3 | The SQL dump uses `TINYINT(1)` for boolean-like columns; Laravel migrations use `boolean()` / `unsignedTinyInteger()`. Both map to `TINYINT(1)` in MySQL/MariaDB, but strict mode may treat them differently. |
| 4 | `learning_modules` and related tables are **not** in the SQL dump. If the dump is the sole source of truth, should these be added to the dump, or should the backend rely solely on migrations? |
| 5 | `contest_participants` and `contest_problems` are pivot tables with composite primary keys. Laravel Eloquent models for these set `$primaryKey = null`, which requires careful handling. |
| 6 | `users.password` stores bcrypt hashes. The SQL dump contains specific hashes (`$2y$12$...`). If the dump is imported and then seeders run, the seeders generate fresh hashes — no conflict, but timestamps will differ. |

---

*Document generated by inspecting: `legacy_schema_and_seed.sql`, `backend/.env`, all migration files, all model files, and all seeder files.*
*No files were modified during this analysis.*
