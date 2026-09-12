# CodeForge 3.0 — Master Project Handoff & Conversation Context

**Purpose:** This document is a continuity/handoff file for future ChatGPT/model sessions.  
Give a future model **this Markdown file together with the latest CodeForge project ZIP/folder**.  
The model should read this file first, then inspect the actual current project files before making changes.

> **Critical rule for future models:** The source code supplied with this handoff is always the source of truth.  
> This document records the project history, architecture, decisions, fixes, known bugs, commands, and user preferences, but some patches near the end of the conversation were created without a confirmed successful run. Always inspect the latest project before assuming a patch is applied.

---

## 1. Project Identity

**Project name:** CodeForge  
**Version name used during migration:** CodeForge 3.0  
**Project type:** Software Lab / DBMS project  
**Concept:** Competitive programming platform inspired by systems such as Codeforces, with custom analytics and competition features.

### Main architecture

```text
Browser / User
      |
      v
SvelteKit + Svelte 5 + TypeScript
Frontend: http://localhost:5173
      |
      | HTTP + JSON
      v
Laravel 11 REST/API Backend + Sanctum
Backend: http://localhost:8000
      |
      | SQL / Query Builder / Eloquent
      v
MySQL / MariaDB
Database: project
Port: 3307
```

### Current intended stack

- **Frontend:** SvelteKit + Svelte 5 + TypeScript
- **Backend:** Laravel 11.56.1
- **Authentication:** Laravel session authentication + Sanctum/stateful SPA + CSRF
- **Database:** MySQL/MariaDB through XAMPP
- **PHP:** 8.2.12
- **Node:** 24.19.0 was used during the project work
- **npm:** 11.17.0 was used during the project work
- **UI fonts:** Space Grotesk + JetBrains Mono
- **Primary UI theme:** futuristic/aggressive dark CodeForge aesthetic using black/red/orange, with some cyan accents

### Windows project path

```text
D:\xampp\htdocs\codeforge
```

### Important local URLs

```text
Frontend:
http://localhost:5173

Backend:
http://localhost:8000

Laravel health:
http://localhost:8000/up

XAMPP convenience URL:
http://localhost/codeforge/
```

The root `index.php` is only a bridge/redirect to the SvelteKit frontend.

---

# 2. Repository / Git Context

### GitHub repository

```text
https://github.com/Ismail02234/CodeForge_SE_Project
```

### Final intended branch

```text
main
```

The project originally used a framework migration branch:

```text
nafiz/framework-migration
```

That branch was later merged into `main`.

A safe exact-tree merge was used because Windows had locked `backend/public` during a normal checkout.

### Important merge event

The framework version was successfully pushed to GitHub `main` and verified:

```text
Final merge commit created during main publish:
2e32ef71a2764c4860aef5cf7dac70dfbe8a5321
```

The log confirmed:

```text
GitHub main file tree exactly matches finalized source.
Previous main history preserved.
Finalized framework history preserved.
```

The temporary remote branch:

```text
nafiz/framework-migration
```

was deleted after verification.

### Later Git workflow

After more local fixes, the user asked to:
1. remove unnecessary PATCH/maintenance `.ps1` and `.bat` files,
2. push the cleaned/fixed current version directly to `main`.

A final helper was created:

```text
PUSH-CODEFORGE-CURRENT-MAIN.bat
```

Its intended behavior:
- require current local branch = `main`
- fetch `origin/main`
- refuse to force-push
- stage current changes/deletions
- keep the helper itself untracked
- commit
- push to `origin/main`
- verify remote commit equals local HEAD

**Important:** There was no final user log confirming that this very last push completed.  
A future model should check:

```powershell
git status
git branch --show-current
git log -5 --oneline
git fetch origin
git rev-parse HEAD
git rev-parse origin/main
```

before assuming GitHub contains every late patch.

---

# 3. User Workflow Preferences

The user strongly prefers **downloadable patch artifacts** instead of long manual editing instructions.

## Preferred change workflow

When fixing or changing CodeForge:

1. Create a patch file such as:
   ```text
   PATCH-023-Descriptive-Name.ps1
   ```
2. Put it in `/mnt/data` for download.
3. Patch should:
   - back up every changed file first,
   - be non-destructive where possible,
   - avoid resetting MySQL data,
   - preserve existing features,
   - run tests/compiler/build,
   - print clear success/failure messages.
4. User runs:
   ```powershell
   cd D:\xampp\htdocs\codeforge
   powershell -ExecutionPolicy Bypass -File .\PATCH-xxx.ps1
   ```
5. User pastes the output back if it fails.

## PowerShell-specific rules learned during the project

### Svelte bracket routes

For paths like:

```text
frontend/src/routes/(app)/ghost-race/[id]/+page.svelte
```

PowerShell can interpret `[id]` as wildcard syntax.

Use:

```powershell
-LiteralPath
```

for bracket-route files.

### PHP path

Prefer explicit XAMPP PHP:

```text
D:\xampp\php\php.exe
```

instead of assuming `php` is available in PATH.

### Node/npm

Scripts should search common paths:

```text
C:\Program Files\nodejs\npm.cmd
C:\Program Files (x86)\nodejs\npm.cmd
```

and refresh PATH when necessary.

### Encoding

Prefer UTF-8 without BOM for source files where possible.

### Backups

Patch backups were conventionally stored under:

```text
D:\xampp\htdocs\codeforge\patch-backups\
```

Example:

```text
patch-backups\PATCH-017-20260902-012030
```

---

# 4. Original Project Before Framework Migration

CodeForge began as a **plain PHP + MySQL/MariaDB** project.

The plain-PHP source was later archived under a folder similar to:

```text
legacy-plain-php-20260901-223705
```

Original pages included:

```text
index.php
login.php
register.php
dashboard.php
problems.php
solve.php
contests.php
contest_view.php
rivalry.php
university.php
university_compare.php
search.php
profile.php
code_dna.php
ghost_race.php
ghost_race_play.php
sql_battle.php
sql_battle_play.php
database.php
edit_user.php
sql_lab.php
how_it_works.php
logout.php
```

### Why migrate?

The old PHP version often mixed:
- HTML
- SQL
- authentication/session logic
- business logic
- calculations
- JavaScript

inside the same page.

The framework migration reorganized responsibilities into:

```text
Svelte page/component
        |
        v
Laravel API route
        |
        v
Controller
        |
        v
Service
        |
        v
MySQL
```

The migration philosophy was **reorganize/adapt the working project**, not destroy the application and start from zero.

---

# 5. Framework Migration Structure

## Frontend

```text
frontend/
├── src/
│   ├── routes/
│   ├── lib/
│   │   ├── api.ts
│   │   ├── stores/
│   │   └── components/
│   ├── app.css
│   └── app.html
├── tools/
│   └── contract-test.mjs
├── package.json
└── svelte.config.js
```

Important frontend routes currently known:

```text
/
login/
register/

(app)/dashboard/
(app)/problems/
(app)/problems/[id]/
(app)/contests/
(app)/contests/[id]/
(app)/rivalry/
(app)/universities/
(app)/universities/compare/
(app)/performance-profile/
(app)/profile/[id]/
(app)/ghost-race/
(app)/ghost-race/[id]/
(app)/sql-battle/
(app)/sql-battle/[id]/
(app)/database/
(app)/sql-lab/
(app)/search/
(app)/how-it-works/
```

Important components:

```text
AppShell.svelte
ForgeCanvas.svelte
PerformanceRadar.svelte
Loading.svelte
Empty.svelte
VerdictBadge.svelte
```

## Backend

```text
backend/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   └── Middleware/
│   ├── Models/
│   ├── Services/
│   └── Support/
├── bootstrap/
├── config/
├── database/
│   ├── migrations/
│   └── seeders/
├── routes/
│   ├── web.php
│   └── api.php
├── tools/
│   └── contract_test.php
└── artisan
```

Known controllers:

```text
AdminController.php
AuthController.php
ContestController.php
DashboardController.php
DatabaseController.php
GamificationController.php
GhostRaceController.php
PerformanceProfileController.php
ProblemController.php
ProfileController.php
PublicController.php
RivalryController.php
SearchController.php
SqlBattleController.php
UniversityController.php
UserController.php
```

Known services:

```text
GamificationService.php
GhostRaceService.php
PerformanceProfileCalculator.php
PerformanceProfileService.php
ProblemPracticeService.php
PrototypeJudgeService.php
SqlBattleService.php
SqlJudgeService.php
```

---

# 6. Main Feature Set

The intended framework version contains:

- public animated landing page
- registration
- login/logout
- protected dashboard
- problem library
- practice sessions
- deterministic prototype programming judge
- contests
- contest participation
- contest scoreboards
- quick duels
- Rivalry comparison
- university leaderboard
- university comparison
- user search
- user profiles
- **Performance Profile**
- Ghost Race
- SQL Battle
- SQL practice
- SQL Battle leaderboard
- gamification / XP / levels / badges
- authenticated database view
- admin user management
- admin read-only SQL Lab
- How It Works technical page

---

# 7. Laravel API Map

The intended `routes/api.php` contains public endpoints:

```text
GET /api/public/stats
GET /api/universities/options
```

Authenticated `auth:sanctum` endpoints:

```text
GET /api/me
GET /api/users/options
GET /api/dashboard

GET /api/problems
GET /api/problems/{id}
POST /api/problems/{id}/session
POST /api/problems/{id}/submit

GET /api/performance-profile
GET /api/performance-profile/{userId}
GET /api/profiles/{id}

GET /api/gamification
GET /api/rivalry

GET /api/universities
GET /api/universities/compare
GET /api/search

GET /api/contests
POST /api/contests
GET /api/contests/{id}
POST /api/contests/{id}/join
POST /api/contests/{id}/close
POST /api/duels

GET /api/ghost-races/options
GET /api/ghost-races/history
POST /api/ghost-races
GET /api/ghost-races/{id}
POST /api/ghost-races/{id}/submit
POST /api/ghost-races/{id}/forfeit

GET /api/sql/challenges
GET /api/sql/opponents
GET /api/sql/leaderboard
GET /api/sql/battles
POST /api/sql/battles
GET /api/sql/battles/{id}
POST /api/sql/challenges/{id}/submit

GET /api/database/users
```

Admin prefix:

```text
GET /api/admin/tables
GET /api/admin/tables/{table}
POST /api/admin/users
PATCH /api/admin/users/{id}
DELETE /api/admin/users/{id}
POST /api/admin/sql-lab
```

Web routes:

```text
POST /login
POST /register
POST /logout
```

Authentication uses web sessions and Sanctum.

---

# 8. Database Context

## Database configuration

```env
DB_HOST=127.0.0.1
DB_PORT=3307
DB_DATABASE=project
DB_USERNAME=root
DB_PASSWORD=
```

The migration was deliberately **non-destructive**.

The Laravel adoption migration checks whether existing application tables already exist and creates only missing ones.

An optimization migration adds indexes instead of replacing tables.

## Known application tables

```text
users
universities
problems
submissions
problem_sessions
contests
contest_problems
contest_participants
ghost_races
sql_challenges
sql_battles
sql_attempts
activity_logs
arena_users
arena_universities
arena_problems
arena_submissions
topicstats
```

Laravel support tables also exist/are created as needed, including session/cache/job-related tables depending on migration state.

## Why `arena_*` tables exist

SQL Battle should never query production CodeForge application tables freely.

The SQL judge allows only a sandbox dataset:

```text
arena_users
arena_universities
arena_problems
arena_submissions
```

This makes SQL Battle demonstrable as a DBMS feature while reducing destructive/security risk.

---

# 9. Important Accounts / Seed Data

## User's main/team accounts

Known intended project accounts:

```text
nafiz   / 123456
ismail  / 123456
tamjid  / 123456
ankita  / 123456
```

Intended ratings:

```text
nafiz   4950  Legendary Grandmaster
ismail  4900  Legendary Grandmaster
tamjid  4850  Legendary Grandmaster
ankita  4800  Legendary Grandmaster
```

The team accounts should use:

```text
United International University
```

## Clean-database seeder accounts

The framework README also defines clean-database demo accounts:

```text
Ismail / 123456
Admin  / admin123
```

The seed behavior is designed to avoid overwriting an existing populated CodeForge database.

---

# 10. University Data Decision

The user requested real/top private universities in Bangladesh.

The `UniversityProfileSeeder` list:

```text
United International University
North South University
BRAC University
Independent University, Bangladesh
American International University-Bangladesh
East West University
Daffodil International University
University of Asia Pacific
Ahsanullah University of Science and Technology
University of Liberal Arts Bangladesh
```

Team username matching:

```text
nafiz
nafin
ismail
tamjid
ankita
```

is explicitly assigned to:

```text
United International University
```

Other users receive a deterministic pseudo-random assignment based on `crc32(username)` so the demo distribution stays stable across machines.

A confirmed PATCH-017 seeding run showed:

```text
ankita -> United International University
ismail -> United International University
nafiz  -> United International University
tamjid -> United International University
```

At that time only seven users existed, so the resulting distribution only used a few of the university names.

---

# 11. Authentication Architecture

## Intended standard flow

```text
SvelteKit browser
    |
    | GET /sanctum/csrf-cookie
    v
Laravel / Sanctum session bootstrap
    |
    | POST /login
    v
AuthController
    |
    | session regenerated after successful login
    v
GET /api/me
    |
    v
Authenticated SvelteKit UI
```

Frontend API requests use:

```ts
credentials: 'include'
```

and state-changing requests send:

```text
X-XSRF-TOKEN
```

from the `XSRF-TOKEN` cookie.

### AuthController behavior

Login:
- validates `username` and `password`
- calls `Auth::attempt`
- on failure returns `422`
- on success regenerates session and returns the user

Register:
- username validation
- password confirmation
- optional valid university
- new user starts around rating 1200 / Newbie
- login occurs after registration

Logout:
- logs out web guard
- invalidates session
- regenerates CSRF token

### CORS

Intended configuration allows:

```text
api/*
sanctum/csrf-cookie
login
logout
register
```

with credentials enabled.

Origins include:

```text
http://localhost:5173
http://127.0.0.1:5173
```

### Authentication troubleshooting history

A long issue occurred after Git recovery:

1. Browser displayed:
   ```text
   Failed to fetch
   ```
2. Local source was discovered incomplete because Git synchronization had failed and:
   ```text
   backend\artisan
   ```
   was missing.
3. Local project was restored from `origin/main`.
4. CSRF route verification became confusing because a patch searched raw route JSON incorrectly.
5. A custom `/api/csrf-cookie` workaround was attempted.
6. Browser later displayed:
   ```text
   CSRF token mismatch
   ```
7. The final recommended direction was to return to the **official Sanctum flow** and use fresh cookies.

### Relevant late patches

```text
PATCH-020-Fix-Login-Failed-To-Fetch.ps1
PATCH-020B-Restore-Sanctum-CSRF-Route.ps1
PATCH-020C-Fix-Login-With-Explicit-CSRF-Endpoint.ps1
PATCH-020D-Verify-And-Fix-Real-Login-Flow.ps1
PATCH-020E-Restore-Official-Sanctum-Login-And-Fresh-Cookies.ps1
```

**Important:** Future models should inspect the actual current `api.ts`, `routes/web.php`, `routes/api.php`, `config/cors.php`, `bootstrap/app.php`, and `.env` before changing auth again.

Do not blindly reapply 020B/020C.

---

# 12. Performance Profile — Formerly “Code DNA”

The original feature was called:

```text
Code DNA
```

The teacher did not like that name.

The user requested a name that directly matches the feature.

It was renamed everywhere to:

```text
Performance Profile
```

## Rename mapping

```text
Code DNA
→ Performance Profile

/code-dna
→ /performance-profile

/api/code-dna
→ /api/performance-profile

CodeDnaController
→ PerformanceProfileController

CodeDnaService
→ PerformanceProfileService

CodeDnaCalculator
→ PerformanceProfileCalculator

DnaRadar.svelte
→ PerformanceRadar.svelte
```

The purpose is to measure a programmer's historical performance rather than use a vague “DNA” metaphor.

## Performance dimensions

Current intended dimensions:

```text
Problem Solving
Accuracy
Speed
Consistency
Versatility
Challenge Handling
```

## Formula details from `PerformanceProfileCalculator`

### Speed target by difficulty

```text
Easy   target = 180 seconds
Medium target = 360 seconds
Hard   target = 600 seconds
Other  target = 300 seconds
```

Speed score:

```text
clamp((target / solve_seconds) * 82)
```

### Difficulty scores

```text
Easy   = 55
Medium = 78
Hard   = 100
Other  = 50
```

### Recency score

```text
<= 14 days   = 100
<= 30 days   = 90
<= 90 days   = 75
<= 180 days  = 60
older         = 45
```

### Topic score

```text
Topic Score =
  Accuracy   * 0.45
+ Difficulty * 0.25
+ Speed      * 0.20
+ Recency    * 0.10
```

### Consistency

Uses recent submission outcomes as 1/0 accepted/not accepted.

Conceptually:

```text
70% recent success mean
+
30% stability from low variance
```

### Problem-solving dimension

```text
Problem Solving =
  Average Topic Score * 0.45
+ Accuracy            * 0.30
+ Challenge           * 0.25
```

### Overall score

Arithmetic mean of the six performance dimensions.

## Archetypes

Examples:

```text
Fast Strategist
Precision Solver
Challenge Hunter
Versatile Explorer
Steady Climber
Developing Coder
```

Example criteria include:
- Speed >= 80 and Accuracy >= 72 → Fast Strategist
- Accuracy >= 82 → Precision Solver
- Challenge >= 78 → Challenge Hunter
- Versatility >= 72 → Versatile Explorer
- Consistency >= 75 → Steady Climber

## Strengths / growth areas

The two highest dimensions become strengths.

The two lowest dimensions become growth areas.

---

# 13. Rename Regression: Dashboard / Rivalry / Profile

The broad Code DNA → Performance Profile rename caused multiple follow-up issues.

## Dashboard

After PATCH-019, dashboard stopped working due to a frontend/backend response-property mismatch.

The intended standard response key became:

```text
performance_profile
```

not mixed variants such as:
- `PROFILE`
- `profile`
- older DNA keys

`PATCH-019B` was created to normalize dashboard use to:

```php
'performance_profile' => $performanceProfile->calculate($userId)
```

and frontend:

```ts
data.performance_profile
```

## Rivalry bug

A concrete PHP case-sensitivity bug appeared:

```php
PerformanceProfileService $PROFILE
```

while the body used:

```php
$profile->calculate(...)
```

PHP variable names are case-sensitive.

That caused:

```text
Undefined variable $profile
```

### PATCH-021 behavior

PATCH-021 rewrote `RivalryController` and `ProfileController` with consistent:

```php
PerformanceProfileService $performanceProfile
```

and:

```php
$performanceProfile->calculate(...)
```

but then PATCH-021 itself falsely reported:

```text
Uppercase $PROFILE rename residue still exists.
```

because PowerShell:

```powershell
-match
```

is case-insensitive by default.

### Correct verification

Use:

```powershell
-cmatch
```

for case-sensitive PowerShell matching.

`PATCH-021B-Verify-Rivalry-Fix-And-Correct-Audit.ps1` was created to verify the real fix and runtime.

---

# 14. Ghost Race

## Concept

Ghost Race is asynchronous competition.

The current player races a **historical solving session** from another user rather than racing another live user.

The ghost's source code is not exposed.

Example ghost timeline:

```text
00:00 Started
04:12 Wrong Answer
07:53 TLE
10:28 Accepted
```

The challenger sees the historical event timing and tries to beat it.

## Core data

Uses:
- `problem_sessions`
- `submissions`
- `ghost_races`

## Main backend service

```text
GhostRaceService.php
```

Known methods include:

```text
availableGhosts()
createRace()
getRace()
ghostEvents()
challengerEvents()
virtualElapsed()
submit()
forfeit()
history()
```

## Important fixes

### Ghost option selection bug

Frontend originally expected fields such as:

```text
g.id
g.username
```

but backend returned:

```text
session_id
ghost_user_id
ghost_username
problem_id
problem_title
topic
difficulty
solve_time_seconds
attempts
```

Because `g.id` was undefined, selecting one radio option appeared to select all rows and Start Race remained disabled.

Fix:

```text
ghost.session_id
ghost.ghost_username
```

Patch:

```text
PATCH-012-Ghost-Race-Selection-Fix.ps1
```

### Submission UI improvement

Patch:

```text
PATCH-013-Ghost-Race-Clear-Submission-UI.ps1
```

Added:
- latest verdict
- judge feedback
- race time/runtime/memory
- failed test
- YOU vs GHOST timing
- progress
- numbered timelines
- finished actions
- safer buttons

### Finished timer bug

Ghost Race timer kept increasing after final submission.

Root cause:

```text
virtualElapsed()
```

always used current time even for finished races.

Patch:

```text
PATCH-014-Ghost-Race-Stop-Finished-Timer.ps1
```

Intended behavior:
- active / non-final attempts → timer keeps moving
- won/lost/draw → freezes at challenger time
- forfeit → freezes at finish time if challenger time absent
- historical race remains frozen

---

# 15. SQL Battle

SQL Battle became one of the strongest DBMS features in the project.

## Concept

Two users solve the same SQL challenge.

Scoring considers:
- correctness
- execution time
- efficiency

The feature also supports solo/practice behavior.

## Core backend

```text
SqlBattleController.php
SqlBattleService.php
SqlJudgeService.php
```

## SQL Battle overhaul

Patch:

```text
PATCH-015-SQL-Battle-Full-Fix-And-UI.ps1
```

Important improvements:

### Backend
- opponent endpoint
- leaderboard
- recent battles
- opponent stats
- active battle detection
- battle resume behavior
- player authorization
- battle completion logic
- attempts feed

### Frontend
- challenge cards
- dedicated opponent cards
- username/university/rank/rating search
- SQL stats
- active battle resume
- practice results
- leaderboard
- history
- detailed live battle page

## SQL judge safety

`SqlJudgeService` restricts user SQL.

### Allowed

```text
SELECT
WITH
```

One statement only.

### Sandbox tables

```text
arena_users
arena_universities
arena_problems
arena_submissions
```

### Blocked concepts

Includes destructive/security-sensitive SQL such as:

```text
INSERT
UPDATE
DELETE
DROP
ALTER
CREATE
TRUNCATE
REPLACE
GRANT
REVOKE
CALL
PROCEDURE
FUNCTION
TRIGGER
EVENT
LOAD_FILE
LOAD DATA
OUTFILE
DUMPFILE
INFILE
SLEEP
BENCHMARK
INFORMATION_SCHEMA
PERFORMANCE_SCHEMA
MYSQL.
SYS.
GET_LOCK
RELEASE_LOCK
CURRENT_USER
DATABASE(
VERSION(
```

Also blocks:
- comments
- SQL variables
- recursive CTEs
- multiple statements
- SELECT INTO
- tables outside sandbox

### Limits

Known judge constants:

```text
MAX_QUERY_LENGTH      = 3000
MAX_RESULT_ROWS       = 1000
MAX_STATEMENT_SECONDS = 1
```

Supports MariaDB `max_statement_time` or MySQL `MAX_EXECUTION_TIME` where available.

## SQL result comparison bug/fix

Originally, query result comparison included associative column names.

That could incorrectly reject equivalent answers such as:

```sql
COUNT(*) AS accepted_count
```

vs:

```sql
COUNT(*) AS total
```

even though result values were identical.

Patch:

```text
PATCH-016-SQL-Judge-Accuracy-And-Self-Test.ps1
```

changed canonicalization to compare values by **column position** rather than alias name.

Still preserves:
- column count
- result values
- row order when `order_sensitive = true`

For order-insensitive challenges, normalized rows are sorted before comparison.

## SQL score structure

When correct, current judge logic conceptually uses:

```text
700
+ speed bonus
+ efficiency * 1.5
```

capped at challenge `max_score`.

Example speed bonus tiers:

```text
ratio <= 1.25 → 150
ratio <= 2.0  → 130
ratio <= 4.0  → 100
ratio <= 8.0  → 70
otherwise     → 40
```

Efficiency is estimated with `EXPLAIN`.

---

# 16. Prototype Programming Judge

The framework deliberately keeps a **safe deterministic prototype judge**.

It does **not** execute arbitrary submitted C++/Python/Java source on the host system.

Instead, it produces deterministic verdict/performance metadata suitable for the Software Lab workflow.

This is important if a teacher asks why no sandboxed compiler infrastructure exists.

A future production architecture could replace:

```text
PrototypeJudgeService
```

with a containerized execution judge while keeping the API and database contract.

---

# 17. Contests / Duels

The framework preserves:
- contest list
- contest detail
- admin contest creation
- joining
- closing
- scoreboards
- quick duels

One migration regression explicitly fixed contest scoring so only the **first accepted contest submission** contributes the intended score behavior.

---

# 18. Gamification

Backend service:

```text
GamificationService.php
```

Used by:
- dashboard
- profile

The application exposes:
- XP
- levels
- badges / progress concepts

Do not assume gamification data has a separate complex schema; inspect current service implementation if changing formulas.

---

# 19. Landing Page / Animation History

The user cares strongly about the landing animation.

## Desired feeling

The animation should:
- feel smooth
- natural
- eye-soothing
- fluid
- not look sloppy or chaotic
- respond subtly to mouse movement

The user specifically preferred the feel of the **pre-framework landing page**.

## PATCH-017

Attempted a more dramatic fluid canvas:
- particles
- red/orange/cyan
- pointer trails
- swirl effects
- glow

The user disliked it as:
- sloppy
- not fluid
- not eye-soothing

## PATCH-018

Patch:

```text
PATCH-018-Restore-Original-Landing-Interaction.ps1
```

This intentionally returned closer to the pre-framework style:
- slow ambient particles
- gentle pointer attraction
- thin proximity connections
- large soft cursor glow
- subtle hero parallax
- subtle Forge Core parallax/tilt
- magnetic buttons
- gentle 3D feature-card tilt
- terminal typing animation
- softer noise texture

Important motion values used:

```text
pointer easing      ~ 0.08
particle attraction ~ 0.014
interaction radius  ~ 175 px
connection radius   ~ 145 px
line width          ~ 0.55 px
DPR cap             ~ 1.5
particle count      ~ 28–72
```

If future UI work changes the landing animation, preserve this calm style.

---

# 20. Sidebar Requirement

Near the end of the conversation, the user requested:

> The sidebar should minimize into a three-bar hamburger in the top-left. Clicking should bring the sidebar out; clicking again should minimize it. It must animate smoothly.

## Existing behavior before the change

`AppShell.svelte` originally had:
- `mobileOpen`
- a hamburger button that only toggled the mobile drawer

Desktop sidebar was fixed/open.

## PATCH-022

First patch attempted exact-text insertion and failed:

```text
Could not locate submitSearch() in AppShell.svelte.
```

Cause:
- formatting-sensitive exact text matching

## PATCH-022B

A robust full AppShell replacement was created:

```text
PATCH-022B-Robust-Smooth-Collapsible-Sidebar.ps1
```

Intended desktop behavior:
- sidebar open by default
- top-left toggle always available
- click → sidebar slides away
- main content expands smoothly
- click again → sidebar returns
- hamburger morphs into an X while open

Intended motion:

```text
360ms cubic-bezier(0.22, 1, 0.36, 1)
```

Mobile drawer behavior should remain.

**Important:** There was no user log confirming PATCH-022B completed successfully.  
Future model should inspect the actual latest `AppShell.svelte` and `app.css`.

---

# 21. Cleanup of Old Patch / BAT Files

The root CodeForge directory accumulated many one-time patch scripts.

The user asked to remove unnecessary `.ps1` / `.bat` files from:

```text
D:\xampp\htdocs\codeforge
```

A cleanup helper was created:

```text
CLEANUP-CODEFORGE-UNNECESSARY-SCRIPTS.bat
```

Intended deletions:

```text
PATCH-*.ps1
PUSH-*.bat
REMOVE-*.bat
RECOVER-*.bat
PUBLISH-*.bat
CLEANUP-*.bat
```

Preserved essential scripts:

```text
START-CODEFORGE.bat
start-codeforge.ps1
setup-codeforge.ps1
SETUP-CODEFORGE.bat   (if present)
```

It only targets the project root.

**No user output confirmed the cleanup result**, so inspect actual directory before assuming it was run.

---

# 22. Framework Migration Patch History

This section records the important patch timeline.

## Plain-PHP phase

Older patch files included concepts such as:
- aggressive landing/auth
- dedicated landing/auth
- BOM fixes
- sidebar scrolling/logout
- high-level member seed
- first-name/max-profile work
- final optimization/polish
- student-style refactor
- teammate merge
- dashboard/footer/navigation fixes
- profile stats warning fixes

Those old sources were archived in `legacy-plain-php-*`.

---

## PATCH-009 — framework migration series

Main migration:

```text
PATCH-009-Framework-Migration.ps1
```

Sub-fixes included:

```text
PATCH-009B-Prerequisites-And-Setup.ps1
PATCH-009C-Fix-Composer-Node-And-Resume.ps1
PATCH-009D-Laravel11-Composer-Policy-MySQL.ps1
PATCH-009E-Enable-PHP-Zip-And-Resume.ps1
PATCH-009F-Fix-Node-Path-And-Resume.ps1
PATCH-009G-Fix-Svelte-Compiler-Errors.ps1
PATCH-009H-Fix-Literal-Route-Paths-And-Svelte.ps1
```

Important lessons:
- PHP zip extension had to be enabled
- Node/npm path detection mattered
- Svelte syntax/compiler issues required targeted fixes
- PowerShell `[id]` routes require `-LiteralPath`

---

## PATCH-010 — original Code DNA stability/performance

```text
PATCH-010-Code-DNA-Stability-Performance.ps1
PATCH-010B-Code-DNA-Smoke-Test-And-Finalize.ps1
```

Before rename, this optimized Code DNA calculations and frontend stability.

---

## PATCH-011 — readability / maintainability

```text
PATCH-011-Readable-Student-Maintainable-Source.ps1
PATCH-011B-Fix-Pint-And-Resume-Readable-Refactor.ps1
PATCH-011C-Fix-Formatting-Sensitive-Test-And-Resume.ps1
PATCH-011D-Fix-Frontend-Formatting-Contract-And-Finalize.ps1
```

Goal:
- idiomatic code
- readable student-maintainable source
- Laravel Pint
- Prettier

Important lesson:
tests that check exact formatting are brittle. Prefer semantic/regex contract tests.

---

## PATCH-012 to 014 — Ghost Race

```text
PATCH-012-Ghost-Race-Selection-Fix.ps1
PATCH-013-Ghost-Race-Clear-Submission-UI.ps1
PATCH-014-Ghost-Race-Stop-Finished-Timer.ps1
```

---

## PATCH-015 / 016 — SQL Battle / judge

```text
PATCH-015-SQL-Battle-Full-Fix-And-UI.ps1
PATCH-016-SQL-Judge-Accuracy-And-Self-Test.ps1
```

---

## PATCH-017 series — universities + landing

```text
PATCH-017-Universities-And-Landing-Fluid.ps1
PATCH-017B-Fix-ForgeCanvas-CanvasContext-Type.ps1
PATCH-017C-Robust-ForgeCanvas-Context-Fix.ps1
```

Confirmed PATCH-017 results before TypeScript failure:
- university seeding succeeded
- 43 backend contract checks passed
- 27 frontend contract checks passed
- Svelte check failed with `ctx is possibly null` in `ForgeCanvas.svelte`

PATCH-017B failed because it could not match the exact context initialization text.

PATCH-017C was created using a formatting-insensitive approach.

---

## PATCH-018 — restore old landing feel

```text
PATCH-018-Restore-Original-Landing-Interaction.ps1
```

---

## PATCH-019 — rename Code DNA

```text
PATCH-019-Rename-Code-DNA-To-Performance-Profile.ps1
PATCH-019B-Fix-Dashboard-After-Performance-Profile-Rename.ps1
```

The rename was applied enough to create runtime follow-up problems, so this was not merely theoretical.

---

## PATCH-020 series — login / CSRF

```text
PATCH-020-Fix-Login-Failed-To-Fetch.ps1
PATCH-020B-Restore-Sanctum-CSRF-Route.ps1
PATCH-020C-Fix-Login-With-Explicit-CSRF-Endpoint.ps1
PATCH-020D-Verify-And-Fix-Real-Login-Flow.ps1
PATCH-020E-Restore-Official-Sanctum-Login-And-Fresh-Cookies.ps1
```

See Authentication section for details.

---

## PATCH-021 series — Rivalry/Profile variable bug

```text
PATCH-021-Fix-Rivalry-Performance-Profile-Variable.ps1
PATCH-021B-Verify-Rivalry-Fix-And-Correct-Audit.ps1
```

PATCH-021 file rewrite likely occurred before its false-positive audit failure.

---

## PATCH-022 series — collapsible sidebar

```text
PATCH-022-Smooth-Collapsible-Sidebar.ps1
PATCH-022B-Robust-Smooth-Collapsible-Sidebar.ps1
```

---

# 23. Test / Verification Commands

## Backend contract

From project root:

```powershell
D:\xampp\php\php.exe backend\tools\contract_test.php
```

or:

```powershell
cd backend
D:\xampp\php\php.exe tools\contract_test.php
```

Historically the mature test suite reported:

```text
43 passed, 0 failed
```

at one point after migration work.

## Frontend contract

```powershell
cd frontend
npm run test:contract
```

Historically:

```text
27 contract checks passed
```

at one point.

## Svelte / TypeScript

```powershell
npm run check
```

## Production build

```powershell
npm run build
```

## Laravel syntax

Useful for changed PHP:

```powershell
D:\xampp\php\php.exe -l path\to\File.php
```

## Laravel cache clear

```powershell
cd backend
D:\xampp\php\php.exe artisan optimize:clear
```

## Laravel routes

Use JSON and **parse it**, rather than raw string matching:

```powershell
D:\xampp\php\php.exe artisan route:list --json
```

PowerShell raw JSON comparisons caused false route failures because slash escaping can differ.

---

# 24. Startup

## Main development launcher

```powershell
cd D:\xampp\htdocs\codeforge
powershell -ExecutionPolicy Bypass -File .\start-codeforge.ps1
```

It is intended to launch:
- Laravel backend
- SvelteKit frontend

in separate PowerShell windows.

## Development endpoints

```text
Frontend: http://localhost:5173
Backend:  http://localhost:8000
```

If Chrome/Brave shows:

```text
ERR_CONNECTION_REFUSED
```

on `localhost:5173`, that means the Svelte dev server is not running.

It is different from:
- 401
- 419
- CSRF mismatch
- backend application errors

---

# 25. Uploaded ZIP Snapshot That Was Inspected During This Handoff

A file named:

```text
codeforge_3.0.zip
```

was uploaded during the conversation and inspected.

## Git snapshot inside that ZIP

```text
branch: main
HEAD: 2e32ef7
origin/main: 2e32ef7
```

The ZIP contained many **working-tree modifications** beyond that commit.

Therefore:

> Do not treat the ZIP's Git commit alone as the complete state of its files.

## Important state found in that uploaded ZIP

### `frontend/src/lib/api.ts`

Uses:

```text
/sanctum/csrf-cookie
```

with:
- `credentials: include`
- `X-XSRF-TOKEN`

### `backend/routes/web.php`

Contained a manual fallback route:

```text
GET /sanctum/csrf-cookie
```

named:

```text
codeforge.csrf-cookie
```

in addition to normal login/register/logout routes.

### `backend/bootstrap/app.php`

Uses:

```php
$middleware->statefulApi();
```

and registers `api.php` + `web.php`.

### `RivalryController.php`

The uploaded ZIP still showed the rename defect:

```php
PerformanceProfileService $PROFILE
```

but then:

```php
$profile->calculate(...)
```

### `ProfileController.php`

The uploaded ZIP also showed:
- `$PROFILE` parameter
- `$profile->calculate(...)`
- response key `'PROFILE'`

### `AppShell.svelte`

The uploaded ZIP still had only:

```text
mobileOpen
```

and no confirmed desktop `desktopCollapsed` implementation.

Therefore this ZIP predates or does not contain confirmed application of:
- PATCH-021B
- PATCH-022B
- possibly the final auth cleanup state

If the **same ZIP** is provided to a future model, those defects should be checked immediately.

If a **newer ZIP** is provided, inspect it first because those issues may already be resolved.

---

# 26. Known Mistakes / Lessons From Previous Patches

Future models should avoid repeating these mistakes.

## 1. Exact multiline search/replace is fragile

Failed examples:
- PATCH-017B could not locate canvas initialization
- PATCH-022 could not locate `submitSearch()`

Use:
- regex tolerant of whitespace
- AST-aware edits when possible
- full targeted file replacement when source is small and well-understood

## 2. PowerShell regex matching is case-insensitive by default

```powershell
-match
```

matched `$profile` when looking for `$PROFILE`.

Use:

```powershell
-cmatch
```

when case matters.

## 3. Raw JSON string search is unreliable

Laravel route JSON can escape `/` as `\/`.

Parse:

```powershell
ConvertFrom-Json
```

then inspect `.uri`.

## 4. Do not invent a new auth flow too quickly

The project is designed for standard Laravel session/Sanctum authentication.

Before adding custom CSRF endpoints:
- inspect route list
- inspect middleware
- inspect cookies
- run real HTTP smoke tests

## 5. Git checkout can fail when Windows processes lock files

A previous `git switch main` repeatedly failed deleting:

```text
backend/public
```

Safe workaround used Git plumbing/exact-tree merge rather than force.

## 6. `index.lock`

After an interrupted Git process, local repo had:

```text
.git/index.lock
```

Only delete it after verifying no Git process is still running.

## 7. Do not rerun seeders casually

Existing database history is important.

Patches should not reset/drop database unless the user explicitly requests it.

---

# 27. UI / Design Preferences

The CodeForge visual identity matters to the user.

## Preferred characteristics

- dark
- premium
- aggressive but polished
- black / red / orange
- cyan accents where appropriate
- futuristic
- not generic Bootstrap-like
- smooth animation
- visually impressive for teacher/demo
- readable and not over-animated

## Landing

Mouse interaction should be subtle/natural, not chaotic.

## Sidebar

Should be:
- independently scrollable
- smoothly collapsible
- accessible from top-left hamburger
- responsive/mobile-friendly

## General

The user prefers full code updates/patches rather than being told to manually edit many files.

---

# 28. Teacher / Viva Explanation Context

The user asked how to explain the framework integration to a teacher who may ask to see source code.

## Simple architecture explanation

Use:

```text
USER
 |
 v
SVELTEKIT FRONTEND
 |
 | REST API / JSON
 v
LARAVEL BACKEND
 |
 | SQL
 v
MYSQL
```

### What is a framework?

Explain:

> A framework is a structured way to organize software. Instead of mixing UI, SQL, authentication and calculations in the same file, responsibilities are separated into predictable layers.

## SvelteKit explanation

Tell teacher:
- `routes` = website pages
- `components` = reusable UI
- `api.ts` = bridge to Laravel
- `app.css` = global design

## Laravel explanation

Tell teacher:
- `routes/api.php` = URL → backend action mapping
- Controller = receives HTTP request and coordinates work
- Service = business logic/calculation
- Model/query builder = database access
- Middleware = authentication/authorization/security
- Migration = database schema changes
- Seeder = repeatable demo data

## Best feature to trace end-to-end

Use **Performance Profile**.

Show in this order:

```text
frontend/src/routes/(app)/performance-profile/+page.svelte
frontend/src/lib/api.ts
backend/routes/api.php
backend/app/Http/Controllers/PerformanceProfileController.php
backend/app/Services/PerformanceProfileService.php
backend/app/Services/PerformanceProfileCalculator.php
```

Explain:

```text
page
→ API request
→ Laravel route
→ controller
→ service
→ MySQL
→ JSON
→ Svelte UI
```

## Strong DBMS demonstration

Use SQL Battle.

Explain:
- controlled SQL sandbox
- SELECT/WITH only
- joins/aggregation/grouping/subqueries possible
- restricted table set
- correctness comparison
- efficiency with EXPLAIN
- read-only protection

## Ghost Race demo explanation

Explain:
- historical session replay
- asynchronous competition
- timestamps
- submissions/events
- source code of ghost hidden
- current user competes against timeline rather than a live connection

---

# 29. Suggested 10-Minute Teacher Demo Flow

1. **Landing page**
   - explain SvelteKit public route
   - show visual polish

2. **Login**
   - explain Laravel session/Sanctum
   - show `api.ts`
   - show `AuthController`

3. **Dashboard**
   - explain protected API
   - stats come from MySQL

4. **Performance Profile**
   - show end-to-end framework flow
   - show calculator formulas

5. **Ghost Race**
   - explain historical session data and race state

6. **SQL Battle**
   - show DBMS-specific security and judge

7. **Database/Admin**
   - show data tables and CRUD/read-only SQL Lab

8. **Source tree**
   - show frontend/backend separation

---

# 30. Future Model Startup Checklist

When the user comes back with:
- this Markdown file
- latest CodeForge project ZIP

the future model should do this before modifying anything:

## Step 1 — inspect actual project

Check:

```text
git status
git branch
git log
frontend/package.json
backend/composer.json
backend/routes/api.php
backend/routes/web.php
backend/bootstrap/app.php
frontend/src/lib/api.ts
frontend/src/lib/components/AppShell.svelte
backend/app/Http/Controllers/RivalryController.php
backend/app/Http/Controllers/ProfileController.php
```

## Step 2 — compare with late known fixes

Check whether:
- Rivalry uses `$performanceProfile`
- Profile response uses `performance_profile`
- sidebar has desktop collapse state
- auth uses one consistent CSRF path
- old patch scripts are cleaned up
- local `main` equals `origin/main`

## Step 3 — run verification

Backend:

```powershell
D:\xampp\php\php.exe backend\tools\contract_test.php
```

Frontend:

```powershell
cd frontend
npm run test:contract
npm run check
npm run build
```

## Step 4 — start project

```powershell
powershell -ExecutionPolicy Bypass -File .\start-codeforge.ps1
```

Then test:
- `/`
- `/login`
- `/dashboard`
- `/performance-profile`
- `/rivalry`
- `/ghost-race`
- `/sql-battle`

## Step 5 — only then patch

Do not infer that every late patch is applied.

---

# 31. Important “Do Not” Rules

Future model should NOT:

- reset or drop the user's MySQL database without explicit request
- force-push GitHub unless the user explicitly understands/approves history rewrite
- push directly to an unrelated teammate branch
- expose or execute arbitrary untrusted programming submissions
- weaken SQL Battle sandbox safety
- disable CSRF just to make login work
- convert authentication to localStorage tokens unless explicitly redesigning the architecture
- rename `Performance Profile` back to Code DNA
- reintroduce `DNA` terminology unless the user asks
- replace the landing animation with chaotic heavy trails
- assume old patch scripts still exist after cleanup
- use normal PowerShell path handling on `[id]` routes
- rely on formatting-sensitive contract assertions when a semantic check is possible

---

# 32. Important User Requests That Define the Final Product

These are product requirements established during the conversation:

1. **Framework version should be the main CodeForge version.**
2. **GitHub final version should live on `main`.**
3. **Performance Profile** replaces Code DNA everywhere.
4. Team accounts should be **United International University**.
5. University names should use real well-known private universities in Bangladesh.
6. Landing animation should feel like the original pre-framework version:
   smooth, calm, eye-soothing, mouse-reactive.
7. Ghost Race selection and final timer must behave correctly.
8. SQL Battle must allow opponent selection and provide strong UI.
9. SQL judge should accept equivalent value results even with different aliases.
10. Sidebar should be smoothly collapsible from a top-left hamburger.
11. Old patch/maintenance scripts should be removed from the final project directory when no longer needed.
12. Final project source should be readable/maintainable and suitable to explain to a teacher.
13. Future fixes should preferably be delivered as downloadable PowerShell/BAT artifacts.

---

# 33. Chronological Narrative of the Main Conversation

This is a condensed history so a future model understands *why* the project looks the way it does.

### Phase A — plain PHP stabilization
The project started as a Codeforces-style PHP/MySQL application. Multiple patches fixed landing/auth, BOM/encoding, navigation, sidebar behavior, seed data, profile warnings, optimization and teammate feature merge.

### Phase B — framework migration
The user decided to move to:
- SvelteKit frontend
- Laravel 11 backend
- MySQL existing database

The migration preserved the database and feature set rather than starting with empty data.

### Phase C — compiler/setup stabilization
Composer, PHP extension, Node path, Svelte compiler and route path issues were fixed through PATCH-009 variants.

### Phase D — advanced feature stabilization
Performance analytics, Ghost Race and SQL Battle were stabilized and improved.

### Phase E — source readability
Laravel Pint / Prettier and more readable student-maintainable code were introduced. Formatting-sensitive tests were converted to more robust checks.

### Phase F — university/demo polish
Team members were assigned UIU and other users distributed across real private universities in Bangladesh.

### Phase G — landing animation
A dramatic new fluid animation was disliked. The user explicitly preferred the old natural motion, so PATCH-018 recreated the original-style interaction.

### Phase H — feature rename
Teacher feedback led to:
- Code DNA → Performance Profile

This broad rename created some response-key and PHP variable regressions that required follow-up patches.

### Phase I — GitHub main consolidation
The framework branch was safely merged to `main`. A Windows file-lock issue required a special merge strategy that preserved history without force push.

### Phase J — authentication debugging
After local Git recovery, login showed:
- Failed to fetch
- then CSRF token mismatch

Multiple diagnostic patches narrowed the problem. The final direction is to preserve standard Laravel/Sanctum session authentication with a consistent `localhost` origin and fresh cookies.

### Phase K — Rivalry regression
Rivalry showed:
- `Undefined variable $profile`

Cause:
- PHP case-sensitive variable mismatch from the broad rename.

PATCH-021 rewrote the files but its verifier had a PowerShell case-insensitive false positive. PATCH-021B corrected the audit.

### Phase L — collapsible sidebar
The user requested a desktop hamburger-collapse interaction. PATCH-022 failed due exact formatting matching. PATCH-022B was created as a robust full-file implementation.

### Phase M — cleanup and final push
The user asked to remove old patch/BAT files and push the newest current version to GitHub `main`. Cleanup and safe-main-push helpers were created, but the final successful push log was not shown in the conversation.

---

# 34. Current Confidence / Unknowns at End of Conversation

## High confidence

- project architecture is SvelteKit + Laravel 11 + MySQL
- GitHub repo and main branch exist
- framework migration was successfully merged to main at least once
- Code DNA was renamed to Performance Profile in substantial parts of source
- SQL Battle and Ghost Race received major improvements
- UIU university seeding was confirmed
- the user wants smooth original-style landing animation
- the user wants collapsible desktop sidebar
- project path / ports / XAMPP setup are known

## Must be verified in latest actual source

- whether PATCH-020E completed
- exact current CSRF route implementation
- whether PATCH-021B completed
- whether Rivalry/Profile files are fully corrected
- whether PATCH-022B completed
- whether the collapsible desktop sidebar is actually present
- whether cleanup removed all old patches
- whether the very latest changes were pushed to GitHub `main`
- whether local `main` equals `origin/main`

---

# 35. Recommended Prompt to Give a Future Model

Copy/paste this together with this file and the current project ZIP:

> I am continuing my CodeForge Software Lab / DBMS project. Read `CODEFORGE_MASTER_HANDOFF_CONTEXT.md` completely first, then inspect the project ZIP I attached. The ZIP/source is the source of truth, because some late patches in the handoff may not have been confirmed as applied.  
>
> My project is SvelteKit + Laravel 11 + MySQL on Windows/XAMPP at `D:\xampp\htdocs\codeforge`. I prefer downloadable PowerShell/BAT patches with backups and verification rather than manual editing. Preserve my existing database and features.  
>
> Before changing anything, inspect Git status, auth flow, Rivalry/Profile controllers, AppShell/sidebar, Performance Profile naming, and run/plan the backend/frontend contract checks. Then tell me the actual current state and continue from there.

---

# 36. Quick Reference

```text
PROJECT
CodeForge 3.0

PATH
D:\xampp\htdocs\codeforge

FRONTEND
SvelteKit + Svelte 5 + TypeScript
http://localhost:5173

BACKEND
Laravel 11.56.1 + Sanctum
http://localhost:8000

DATABASE
MySQL/MariaDB
project
127.0.0.1:3307
root / blank password

REPO
https://github.com/Ismail02234/CodeForge_SE_Project

FINAL BRANCH
main

TEAM
nafiz
ismail
tamjid
ankita

TEAM UNIVERSITY
United International University

MAIN FEATURE NAME
Performance Profile

KEY CUSTOM FEATURES
Performance Profile
Ghost Race
SQL Battle
Rivalry
University analytics
Gamification
Contests / duels
SQL Lab / database tools

START
powershell -ExecutionPolicy Bypass -File .\start-codeforge.ps1

BACKEND TEST
D:\xampp\php\php.exe backend\tools\contract_test.php

FRONTEND TEST
cd frontend
npm run test:contract
npm run check
npm run build
```

---

# 37. Final Handoff Note

This file is intentionally more detailed than a normal README.

The repository README explains **what CodeForge is**.

This file explains:
- why the architecture changed,
- what the user requested,
- what problems occurred,
- which patches were created,
- which fixes are confirmed or unconfirmed,
- how the user prefers to work,
- what a future model must inspect first,
- and how to continue without repeating earlier mistakes.

When a future session starts, **do not spend many turns rediscovering the project**.  
Read this document, inspect the latest attached source, run targeted verification, and continue the work from the actual current state.
