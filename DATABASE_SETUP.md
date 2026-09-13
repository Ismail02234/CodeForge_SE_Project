# CodeForge Development Database Setup

## Prerequisites

- MySQL 8.0+ or MariaDB 10.4+ running locally
- MySQL client available in PATH
- XAMPP MySQL default port: `3307`

## Step 1: Create the Development Database

```bash
mysql -u root -e "CREATE DATABASE IF NOT EXISTS codeforge_dev DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

## Step 2: Import the Schema

Import the exact schema from `backend/database/legacy_schema_and_seed.sql`:

```bash
mysql -u root codeforge_dev < backend/database/legacy_schema_and_seed.sql
```

## Step 3: Configure Environment

The development database configuration is stored in `backend/.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3307
DB_DATABASE=codeforge_dev
DB_USERNAME=root
DB_PASSWORD=
DB_COLLATION=utf8mb4_unicode_ci
```

**Production credentials are preserved in `backend/.env.production` and are never overwritten by the development setup.**

To switch to production, update `backend/.env`:

```env
DB_DATABASE=project
```

## Step 4: Verify the Setup

Run the following commands against the `codeforge_dev` database:

```bash
mysql -u root codeforge_dev -e "
-- 1. List all tables
SELECT TABLE_NAME FROM information_schema.tables WHERE table_schema = 'codeforge_dev' ORDER BY TABLE_NAME;

-- 2. Verify table count (expected: 17)
SELECT COUNT(*) AS table_count FROM information_schema.tables WHERE table_schema = 'codeforge_dev';

-- 3. Verify columns (expected: 112 total)
SELECT COUNT(*) AS total_columns FROM information_schema.columns WHERE table_schema = 'codeforge_dev';

-- 4. Verify primary keys
SELECT TABLE_NAME, COLUMN_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = 'codeforge_dev' AND CONSTRAINT_NAME = 'PRIMARY' ORDER BY TABLE_NAME;

-- 5. Verify foreign keys
SELECT TABLE_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = 'codeforge_dev' AND REFERENCED_TABLE_NAME IS NOT NULL ORDER BY TABLE_NAME, COLUMN_NAME;

-- 6. Verify indexes
SELECT TABLE_NAME, INDEX_NAME, GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX) AS columns FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = 'codeforge_dev' AND INDEX_NAME != 'PRIMARY' GROUP BY TABLE_NAME, INDEX_NAME ORDER BY TABLE_NAME, INDEX_NAME;

-- 7. Verify unique constraints
SELECT TABLE_NAME, COLUMN_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = 'codeforge_dev' AND CONSTRAINT_NAME != 'PRIMARY' AND REFERENCED_TABLE_NAME IS NULL AND EXISTS (SELECT 1 FROM information_schema.TABLE_CONSTRAINTS tc WHERE tc.TABLE_SCHEMA = 'codeforge_dev' AND tc.TABLE_NAME = KEY_COLUMN_USAGE.TABLE_NAME AND tc.CONSTRAINT_NAME = KEY_COLUMN_USAGE.CONSTRAINT_NAME AND tc.CONSTRAINT_TYPE = 'UNIQUE') ORDER BY TABLE_NAME, COLUMN_NAME;
"
```

### Expected Verification Results

#### Tables (17 total)

| Table Name |
|------------|
| activity_logs |
| arena_problems |
| arena_submissions |
| arena_universities |
| arena_users |
| contest_participants |
| contest_problems |
| contests |
| ghost_races |
| problems |
| problem_sessions |
| sql_attempts |
| sql_battles |
| sql_challenges |
| submissions |
| universities |
| users |

#### Primary Keys

| Table | Column |
|-------|--------|
| activity_logs | id |
| arena_problems | problem_id |
| arena_submissions | submission_id |
| arena_universities | name |
| arena_users | user_id |
| contests | id |
| contest_participants | contest_id, user_id |
| contest_problems | contest_id, problem_id |
| ghost_races | id |
| problems | id |
| problem_sessions | id |
| sql_attempts | id |
| sql_battles | id |
| sql_challenges | id |
| submissions | id |
| universities | name |
| users | id |

#### Foreign Keys (27 total)

| Table | Column | References |
|-------|--------|------------|
| activity_logs | user_id | users(id) |
| arena_submissions | problem_id | arena_problems(problem_id) |
| arena_submissions | user_id | arena_users(user_id) |
| arena_users | university | arena_universities(name) |
| contests | created_by | users(id) |
| contest_participants | contest_id | contests(id) |
| contest_participants | user_id | users(id) |
| contest_problems | contest_id | contests(id) |
| contest_problems | problem_id | problems(id) |
| ghost_races | challenger_id | users(id) |
| ghost_races | challenger_session_id | problem_sessions(id) |
| ghost_races | ghost_session_id | problem_sessions(id) |
| ghost_races | ghost_user_id | users(id) |
| ghost_races | problem_id | problems(id) |
| problem_sessions | problem_id | problems(id) |
| problem_sessions | user_id | users(id) |
| sql_attempts | battle_id | sql_battles(id) |
| sql_attempts | challenge_id | sql_challenges(id) |
| sql_attempts | user_id | users(id) |
| sql_battles | challenge_id | sql_challenges(id) |
| sql_battles | player1_id | users(id) |
| sql_battles | player2_id | users(id) |
| sql_battles | winner_id | users(id) |
| submissions | contest_id | contests(id) |
| submissions | problem_id | problems(id) |
| submissions | session_id | problem_sessions(id) |
| submissions | user_id | users(id) |
| users | university | universities(name) |

#### Unique Constraints

| Table | Column |
|-------|--------|
| users | username |

#### Indexes (42 total)

| Table | Index Name | Columns |
|-------|------------|---------|
| activity_logs | idx_activity_created | created_at |
| activity_logs | idx_activity_user_created | user_id, created_at |
| arena_submissions | idx_as_problem | problem_id |
| arena_submissions | idx_as_user | user_id |
| arena_submissions | idx_as_verdict | verdict |
| arena_users | fk_au_university | university |
| contests | fk_contests_creator | created_by |
| contests | idx_contests_starts_at | starts_at |
| contests | idx_contests_status | status |
| contest_participants | idx_cpa_user | user_id |
| contest_problems | fk_cp_problem | problem_id |
| ghost_races | fk_gr_ghost_session | ghost_session_id |
| ghost_races | fk_gr_ghost_user | ghost_user_id |
| ghost_races | fk_gr_problem | problem_id |
| ghost_races | idx_gr_challenger | challenger_id, started_at |
| ghost_races | idx_gr_challenger_result_started | challenger_id, result, started_at |
| ghost_races | idx_gr_challenger_session_result | challenger_session_id, result |
| problems | idx_problems_difficulty | difficulty |
| problems | idx_problems_topic | topic |
| problem_sessions | fk_ps_problem | problem_id |
| problem_sessions | idx_ps_status | status |
| problem_sessions | idx_ps_status_user_problem_solve | status, user_id, problem_id, solve_time_seconds |
| problem_sessions | idx_ps_user_problem | user_id, problem_id |
| problem_sessions | idx_ps_user_problem_status_started | user_id, problem_id, status, started_at |
| sql_attempts | idx_sa_battle_status_user_score | battle_id, status, user_id, score |
| sql_attempts | idx_sa_battle_user | battle_id, user_id |
| sql_attempts | idx_sa_challenge_status | challenge_id, status |
| sql_attempts | idx_sa_user | user_id, submitted_at |
| sql_battles | fk_sb_winner | winner_id |
| sql_battles | idx_sb_challenge_status_players | challenge_id, status, player1_id, player2_id |
| sql_battles | idx_sb_player1_created | player1_id, created_at |
| sql_battles | idx_sb_player2_created | player2_id, created_at |
| sql_battles | idx_sb_players | player1_id, player2_id |
| sql_battles | idx_sb_status | status |
| sql_challenges | idx_sql_challenge_difficulty | difficulty |
| submissions | idx_sub_contest_user_problem_verdict | contest_id, user_id, problem_id, verdict |
| submissions | idx_sub_problem | problem_id |
| submissions | idx_sub_problem_verdict_user | problem_id, verdict, user_id |
| submissions | idx_sub_session_elapsed | session_id, elapsed_seconds |
| submissions | idx_sub_user | user_id |
| submissions | idx_sub_user_time | user_id, submitted_at, id |
| submissions | idx_sub_user_verdict | user_id, verdict |
| submissions | idx_sub_user_verdict_problem | user_id, verdict, problem_id |
| users | idx_users_rating | rating |
| users | idx_users_university | university |
| users | username | username |

## Switching to Production

To switch from the development database to the production database, update `backend/.env`:

```env
DB_DATABASE=project
```

The production database `project` is never modified by the development setup.

## Schema Source

The exact schema source is `backend/database/legacy_schema_and_seed.sql`. Do not modify table names, column names, or add invented columns. All PKs, FKs, indexes, and constraints are preserved as defined in the source file.
