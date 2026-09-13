# CodeForge Backend Schema Compatibility Audit Report

## 1. Backend Framework / Version

| Item | Value |
|------|-------|
| **Framework** | Laravel 11.56.1 |
| **PHP** | ^8.2 |
| **Auth** | Laravel Sanctum ^4.0 |
| **Database driver** | MySQL / MariaDB (utf8mb4, utf8mb4_unicode_ci) |
| **Target DB** | MariaDB 10.4+ / MySQL 8+ (per SQL dump header) |
| **Timezone** | Asia/Dhaka (`backend/config/app.php`) |

---

## 2. Backend File Inventory

### Models (`backend/app/Models/`)
| Model | Table |
|-------|-------|
| `User.php` | `users` |
| `University.php` | `universities` |
| `Problem.php` | `problems` |
| `Submission.php` | `submissions` |
| `ProblemSession.php` | `problem_sessions` |
| `Contest.php` | `contests` |
| `SqlChallenge.php` | `sql_challenges` |
| `SqlBattle.php` | `sql_battles` |
| `SqlAttempt.php` | `sql_attempts` |
| `GhostRace.php` | `ghost_races` |
| `ActivityLog.php` | `activity_logs` |
| `LearningModule.php` | `learning_modules` |
| `LearningStep.php` | `learning_steps` |
| `PlayChallenge.php` | `play_challenges` |
| `LearningProblem.php` | `learning_problems` |
| `LearningProgress.php` | `learning_progress` |

**Missing Eloquent models** (tables used in code but no model):
- `contest_problems`
- `contest_participants`
- `arena_universities`
- `arena_users`
- `arena_problems`
- `arena_submissions`
- `topicstats`

### Controllers (`backend/app/Http/Controllers/`)
`AuthController`, `AdminController`, `DashboardController`, `DatabaseController`, `ProblemController`, `LearningController`, `SqlBattleController`, `GhostRaceController`, `ContestController`, `ProfileController`, `PerformanceProfileController`, `GamificationController`, `UniversityController`, `SearchController`, `RivalryController`, `PublicController`

### Services (`backend/app/Services/`)
`SqlJudgeService`, `SqlBattleService`, `PrototypeJudgeService`, `ProblemPracticeService`, `PerformanceProfileService`, `PerformanceProfileCalculator`, `GhostRaceService`, `GamificationService`

### Migrations (`backend/database/migrations/`)
- `0001_01_01_000000_create_laravel_support_tables.php`
- `2026_09_01_000100_adopt_or_create_codeforge_schema.php`
- `2026_09_01_000150_add_user_id_to_submissions_table.php`
- `2026_09_01_000200_optimize_existing_codeforge_indexes.php`
- `2026_09_11_000300_create_learning_modules_table.php`
- `2026_09_11_000310_create_learning_steps_table.php`
- `2026_09_11_000320_create_play_challenges_table.php`
- `2026_09_11_000330_create_learning_problems_table.php`
- `2026_09_11_000340_create_learning_progress_table.php`
- `2026_09_11_000350_add_beginner_to_learning_modules_difficulty.php`
- `2026_09_11_000360_add_learning_metrics_to_progress_table.php`
- `2026_09_11_000370_add_learn_completed_steps_to_progress_table.php`

### Seeders
- `DatabaseSeeder.php`
- `CodeForgeSeeder.php`
- `LearningModuleSeeder.php`
- `UniversityProfileSeeder.php`

---

## 3. Database Tables & Columns Referenced in PHP Code

### Core tables (referenced across models, controllers, services)
- **`users`**: `id`, `username`, `password`, `role`, `rating`, `university`, `rank`, `created_at`
- **`universities`**: `name`, `city`, `created_at`
- **`problems`**: `id`, `title`, `topic`, `difficulty`, `description`, `tags`, `starter_code`, `created_at` — also `xp_reward` (referenced in queries)
- **`contests`**: `id`, `name`, `type`, `starts_at`, `status`, `created_by`, `created_at`
- **`contest_problems`**: `contest_id`, `problem_id`, `points`
- **`contest_participants`**: `contest_id`, `user_id`, `score`, `joined_at`
- **`problem_sessions`**: `id`, `user_id`, `problem_id`, `started_at`, `completed_at`, `solve_time_seconds`, `status`, `created_at`
- **`submissions`**: `id`, `session_id`, `problem_id`, `user_id`, `contest_id`, `verdict`, `submitted_at`, `elapsed_seconds`, `runtime_ms`, `memory_kb`, `language`, `source_code`, `failed_test_case`
- **`ghost_races`**: `id`, `challenger_id`, `ghost_user_id`, `problem_id`, `ghost_session_id`, `challenger_session_id`, `playback_speed`, `started_at`, `finished_at`, `result`, `challenger_time`, `ghost_time`
- **`sql_challenges`**: `id`, `title`, `description`, `difficulty`, `reference_query`, `max_score`, `order_sensitive`, `created_at`
- **`sql_battles`**: `id`, `challenge_id`, `player1_id`, `player2_id`, `status`, `winner_id`, `created_at`, `completed_at`
- **`sql_attempts`**: `id`, `battle_id`, `challenge_id`, `user_id`, `submitted_query`, `status`, `execution_time_ms`, `efficiency_score`, `score`, `feedback`, `submitted_at`
- **`activity_logs`**: `id`, `user_id`, `action`, `details`, `created_at`

### Learning tables (migrations + code only, not in SQL dump)
- **`learning_modules`**: `id`, `title`, `slug`, `topic`, `description`, `difficulty`, `estimated_minutes`, `xp_reward`, `is_active`, `created_at`
- **`learning_steps`**: `id`, `learning_module_id`, `step_order`, `type`, `title`, `content`, `question`, `options`, `correct_answer`, `xp_reward`, `created_at`
- **`play_challenges`**: `id`, `learning_module_id`, `type`, `title`, `instructions`, `config`, `xp_reward`, `time_limit`, `created_at`
- **`learning_problems`**: `id`, `learning_module_id`, `problem_id`, `stage`, `sort_order`, `created_at`
- **`learning_progress`**: `id`, `user_id`, `learning_module_id`, `learn_completed`, `play_completed`, `prove_completed`, `learn_score`, `play_score`, `prove_score`, `mastery_score`, `attempts`, `hints_used`, `started_at`, `completed_at`, `created_at`, `learn_accuracy`, `play_accuracy`, `prove_accuracy`, `concept_mastery`, `weakness_signal`, `repeated_failed_concepts`, `learn_attempts`, `play_attempts`, `prove_attempts`, `learn_correct`, `play_correct`, `prove_correct`, `learn_completed_steps`

### Arena tables (in SQL dump + migration)
- **`arena_universities`**: `name`
- **`arena_users`**: `user_id`, `username`, `university`, `rating`
- **`arena_problems`**: `problem_id`, `title`, `topic`, `difficulty`
- **`arena_submissions`**: `submission_id`, `user_id`, `problem_id`, `verdict`, `runtime_ms`

### `topicstats` (migration only, not in SQL dump)
- **`topicstats`**: `topic`, `solved`, `total`, `weaknessScore`, `updated_at`

---

## 4. Full SQL Dump Schema (`backend/database/legacy_schema_and_seed.sql`)

### Tables, Columns, Keys, Indexes

| Table | Columns | PK | FK | Indexes |
|-------|---------|----|----|---------|
| `universities` | `name`(VARCHAR 255), `city`(VARCHAR 120 NULL), `created_at`(DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP) | `name` | — | — |
| `users` | `id`(VARCHAR 50), `username`(VARCHAR 100 UNIQUE), `password`(VARCHAR 255), `role`(ENUM), `rating`(INT), `university`(VARCHAR 255 NULL), `rank`(VARCHAR 50), `created_at`(DATETIME) | `id` | `university` → `universities(name)` ON UPDATE CASCADE ON DELETE SET NULL | `idx_users_university(university)`, `idx_users_rating(rating)` |
| `problems` | `id`(VARCHAR 50), `title`(VARCHAR 255), `topic`(VARCHAR 100), `difficulty`(ENUM), `description`(TEXT), `tags`(VARCHAR 255 NULL), `starter_code`(TEXT NULL), `created_at`(DATETIME) | `id` | — | `idx_problems_topic(topic)`, `idx_problems_difficulty(difficulty)` |
| `contests` | `id`(VARCHAR 50), `name`(VARCHAR 255), `type`(ENUM), `starts_at`(DATETIME), `status`(ENUM), `created_by`(VARCHAR 50 NULL), `created_at`(DATETIME) | `id` | `created_by` → `users(id)` ON DELETE SET NULL | `idx_contests_status(status)`, `idx_contests_starts_at(starts_at)` |
| `contest_problems` | `contest_id`(VARCHAR 50), `problem_id`(VARCHAR 50), `points`(INT) | PK(`contest_id`, `problem_id`) | `contest_id` → `contests(id)` CASCADE, `problem_id` → `problems(id)` CASCADE | — |
| `contest_participants` | `contest_id`(VARCHAR 50), `user_id`(VARCHAR 50), `score`(INT), `joined_at`(DATETIME) | PK(`contest_id`, `user_id`) | `contest_id` → `contests(id)` CASCADE, `user_id` → `users(id)` CASCADE | `idx_cpa_user(user_id)` |
| `problem_sessions` | `id`(VARCHAR 64), `user_id`(VARCHAR 50), `problem_id`(VARCHAR 50), `started_at`(DATETIME), `completed_at`(DATETIME NULL), `solve_time_seconds`(INT NULL), `status`(ENUM), `created_at`(DATETIME) | `id` | `user_id` → `users(id)` CASCADE, `problem_id` → `problems(id)` CASCADE | `idx_ps_user_problem(user_id,problem_id)`, `idx_ps_status(status)`, `idx_ps_user_problem_status_started`, `idx_ps_status_user_problem_solve` |
| `submissions` | `id`(VARCHAR 64), `session_id`(VARCHAR 64 NULL), `problem_id`(VARCHAR 50), `user_id`(VARCHAR 50), `contest_id`(VARCHAR 50 NULL), `verdict`(ENUM), `submitted_at`(DATETIME), `elapsed_seconds`(INT NULL), `runtime_ms`(INT NULL), `memory_kb`(INT NULL), `language`(VARCHAR 50), `source_code`(MEDIUMTEXT NULL), `failed_test_case`(INT NULL) | `id` | `session_id` → `problem_sessions(id)` SET NULL, `problem_id` → `problems(id)` CASCADE, `user_id` → `users(id)` CASCADE, `contest_id` → `contests(id)` SET NULL | 6 indexes including `idx_sub_user_verdict_problem`, `idx_sub_contest_user_problem_verdict` |
| `ghost_races` | `id`(VARCHAR 64), `challenger_id`(VARCHAR 50), `ghost_user_id`(VARCHAR 50), `problem_id`(VARCHAR 50), `ghost_session_id`(VARCHAR 64), `challenger_session_id`(VARCHAR 64), `playback_speed`(TINYINT), `started_at`(DATETIME), `finished_at`(DATETIME NULL), `result`(ENUM), `challenger_time`(INT NULL), `ghost_time`(INT) | `id` | 5 FKs to `users` and `problem_sessions` | 3 indexes |
| `sql_challenges` | `id`(VARCHAR 50), `title`(VARCHAR 255), `description`(TEXT), `difficulty`(ENUM), `reference_query`(TEXT), `max_score`(INT), `order_sensitive`(TINYINT 1), `created_at`(DATETIME) | `id` | — | `idx_sql_challenge_difficulty(difficulty)` |
| `sql_battles` | `id`(VARCHAR 64), `challenge_id`(VARCHAR 50), `player1_id`(VARCHAR 50), `player2_id`(VARCHAR 50), `status`(ENUM), `winner_id`(VARCHAR 50 NULL), `created_at`(DATETIME), `completed_at`(DATETIME NULL) | `id` | FKs to `sql_challenges` and `users` | 4 indexes |
| `sql_attempts` | `id`(VARCHAR 64), `battle_id`(VARCHAR 64 NULL), `challenge_id`(VARCHAR 50), `user_id`(VARCHAR 50), `submitted_query`(TEXT), `status`(ENUM), `execution_time_ms`(DECIMAL 10,3 NULL), `efficiency_score`(INT), `score`(INT), `feedback`(VARCHAR 500 NULL), `submitted_at`(DATETIME) | `id` | FKs to `sql_battles`, `sql_challenges`, `users` | 4 indexes |
| `activity_logs` | `id`(BIGINT UNSIGNED AUTO_INCREMENT), `user_id`(VARCHAR 50 NULL), `action`(VARCHAR 100), `details`(VARCHAR 500 NULL), `created_at`(DATETIME) | `id` | `user_id` → `users(id)` SET NULL | `idx_activity_created(created_at)`, `idx_activity_user_created(user_id, created_at)` |
| `arena_universities` | `name`(VARCHAR 120) | `name` | — | — |
| `arena_users` | `user_id`(VARCHAR 20), `username`(VARCHAR 100), `university`(VARCHAR 120), `rating`(INT) | `user_id` | `university` → `arena_universities(name)` CASCADE | — |
| `arena_problems` | `problem_id`(VARCHAR 20), `title`(VARCHAR 120), `topic`(VARCHAR 80), `difficulty`(VARCHAR 20) | `problem_id` | — | — |
| `arena_submissions` | `submission_id`(VARCHAR 20), `user_id`(VARCHAR 20), `problem_id`(VARCHAR 20), `verdict`(VARCHAR 10), `runtime_ms`(INT) | `submission_id` | FKs to `arena_users`, `arena_problems` CASCADE | `idx_as_user`, `idx_as_problem`, `idx_as_verdict` |

---

## 5. SQL Tables With No Corresponding Backend Model

| Table | Used via |
|-------|----------|
| `contest_problems` | Raw `DB::table()` in `ContestController` |
| `contest_participants` | Raw `DB::table()` in `ContestController`, `ProblemPracticeService`, `GamificationService` |
| `arena_universities` | Raw `DB::table()` in `PublicController`, seeders |
| `arena_users` | Raw `DB::table()` in `SqlBattleService`, seeders |
| `arena_problems` | Raw `DB::table()` in `SqlJudgeService`, seeders |
| `arena_submissions` | Raw `DB::table()` in `SqlJudgeService`, seeders |
| `topicstats` | Referenced in `AdminController::$safeTables`; created by migration only |

---

## 6. Backend Models / Tables Not Present in SQL Dump

| Table | Source |
|-------|--------|
| `learning_modules` | Migration only |
| `learning_steps` | Migration only |
| `play_challenges` | Migration only |
| `learning_problems` | Migration only |
| `learning_progress` | Migration only |
| `topicstats` | Migration only |

The SQL dump is explicitly labeled **legacy** and does not include the learning/topicstats tables. These are created solely by backend migrations.

---

## 7. Naming Differences

| Issue | PHP / Migration | SQL Dump |
|--------|----------------|----------|
| **camelCase vs snake_case** | Migration `2026_09_01_000200_optimize_existing_codeforge_indexes.php` references `problemId` | Actual column is `problem_id` |
| **`created_by`** | `created_by` (snake_case) in SQL dump, migration, seeders, and code | ✅ Consistent |
| **`updated_at`** | Migration `2026_09_01_000100_adopt_or_create_codeforge_schema.php` adds `topicstats.updated_at` | Not present in SQL dump; no `updated_at` on any legacy table |
| **`activity_logs.id`** | Migration uses `$t->id()` (BIGINT auto-increment) | `BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY` ✅ |
| **PK key type** | All models set `$keyType = 'string'` and `$incrementing = false` | All PKs are VARCHAR except `activity_logs.id` |
| **`rank` column** | Referenced normally | Reserved word; backtick-quoted in SQL dump inserts |

---

## 8. Potentially Broken Relationships

| Relationship | Risk |
|-------------|------|
| `users.university` → `universities(name)` | Valid FK, but uses string key instead of integer surrogate. Deleting a university sets user's `university` to NULL. |
| `arena_users.university` → `arena_universities(name)` | Same string-key pattern. |
| `learning_problems.problem_id` → `problems(id)` | FK is `NULL ON DELETE`. If a problem is deleted, `learning_problems.problem_id` becomes NULL. Backend `LearningProblem` model defines `belongsTo(Problem::class)`. |
| `problem_sessions.id` → `submissions.session_id` | `SET NULL` on delete. If a session is deleted, submissions keep their data but `session_id` becomes NULL. |
| `ghost_races.ghost_session_id` / `challenger_session_id` → `problem_sessions(id)` | `CASCADE` on delete. Deleting a session deletes the ghost race. |

---

## 9. Potentially Broken Raw SQL Queries / Column References

### A. `problemId` (camelCase) in index migration — **SILENT FAILURE**
`backend/database/migrations/2026_09_01_000200_optimize_existing_codeforge_indexes.php:16,27,40,48`

This migration references `problemId` instead of `problem_id` when building index column lists for `submissions` and `problem_sessions`. Each iteration checks `Schema::hasColumn($table, $column)`, so the migration **won't crash** — it will silently skip creating those indexes. The intended indexes (`idx_sub_user_verdict_problem`, etc.) are never created.

### B. `problems.xp_reward` — **RUNTIME SQL ERROR**
The following code references `problems.xp_reward`, but the `problems` table in the SQL dump and in migration `2026_09_01_000100_adopt_or_create_codeforge_schema.php` has **no `xp_reward` column**:

- `backend/app/Http/Controllers/LearningController.php:80`
  ```php
  DB::raw('SUM(p.xp_reward) as total')
  ```
- `backend/app/Http/Controllers/LearningController.php:224`
  ```php
  ->sum('p.xp_reward');
  ```
- `backend/app/Services/PerformanceProfileService.php:252-254`
  ```sql
  SELECT COALESCE(SUM(p.xp_reward), 0) FROM learning_problems lp2 JOIN problems p ON p.id = lp2.problem_id
  ```
- `backend/app/Services/ProblemPracticeService.php:245-248`
  ```sql
  SELECT lp.learning_module_id, p.xp_reward FROM learning_problems lp INNER JOIN problems p ON p.id = lp2.problem_id
  ```

**Impact**: Any code path hitting these queries will throw an `SQLSTATE[42S22]: Column not found` error when `learning_problems` rows exist.

### C. Timezone mismatch in `getOrCreateSession`
`backend/app/Services/ProblemPracticeService.php:83`
```php
'started_at' => gmdate('Y-m-d H:i:s'),
```
`gmdate()` returns UTC, while the SQL dump uses `DATETIME DEFAULT CURRENT_TIMESTAMP` (server time = Asia/Dhaka per `config/app.php`). Session start times inserted via this service will be offset by the UTC-to-Dhaka difference.

### D. `activity_logs` model missing primary key / incrementing config
`backend/app/Models/ActivityLog.php` does not define `$primaryKey`, `$incrementing`, or `$keyType`. Eloquent defaults to `id` / auto-increment / int, which happens to match the SQL schema, but any Eloquent-based create/update on this model is fragile and implicit.

---

## 10. Highest-Risk Incompatibilities Summary

1. **`problems.xp_reward` column is missing in the SQL dump but referenced in 4 code locations** — This is the highest-severity issue. Queries in `LearningController`, `PerformanceProfileService`, and `ProblemPracticeService` will fail with "unknown column" errors when the learning module code paths are exercised against a database imported from the legacy SQL dump.

2. **Migration `2026_09_01_000200_optimize_existing_codeforge_indexes.php` uses `problemId` (camelCase)** — The column guard (`Schema::hasColumn`) prevents crashes, but critical composite indexes (`idx_sub_user_verdict_problem`, `idx_sub_contest_user_problem_verdict`, `idx_ps_user_problem_status_started`, `idx_ps_status_user_problem_solve`) are **never created**. This degrades query performance on `submissions` and `problem_sessions`.

3. **Learning tables (`learning_*`, `play_challenges`) do not exist in the SQL dump** — Importing only the SQL dump without running migrations will cause the entire learning module API (`/learn`, `/learn/{slug}/learn`, etc.) to fail with "table not found" errors.

4. **No Eloquent models for 7 tables** — `contest_problems`, `contest_participants`, `arena_universities`, `arena_users`, `arena_problems`, `arena_submissions`, and `topicstats` are accessed only via raw query builder. This is manageable but means no type-safe ORM layer, no relationship eager-loading, and no model events for these tables.

5. **Timezone inconsistency** — `ProblemPracticeService::getOrCreateSession()` writes `started_at` in UTC (`gmdate`), while the rest of the schema uses `CURRENT_TIMESTAMP` (server local time, Asia/Dhaka). This can cause incorrect elapsed-time calculations and confusing session timestamps.

6. **`AdminController::$safeTables` lists `topicstats`** — If the SQL dump is imported and the `2026_09_01_000100` migration hasn't run yet, `/admin/tables/topicstats` returns 404. The reverse is also true: if only migrations run, the legacy tables exist but `topicstats` may not be present in the legacy data context.
