# CodeForge Master Handoff Context
**Project:** CodeForge — Competitive Programming / Software Engineering Project  
**Primary user:** Md Nafin Rahman / Nafiz  
**Last updated:** 2026-09-13  
**Purpose:** Give any future model enough context to continue work on the current CodeForge ZIP without forcing the user to re-explain the project, architecture, workflow, contribution history, patch process, testing setup, and preferred working style.

---

# 0. READ THIS FIRST — HOW TO WORK WITH THIS USER

The user prefers a **practical patch-based workflow**, not long theory-first answers.

## Core working style
- Prefer **PowerShell/BAT patch files** over asking the user to manually edit many files.
- Before modifying project files:
  - make backups,
  - use safe/targeted replacements,
  - preserve the existing database unless explicitly asked to change it.
- After changes, run relevant checks:
  - Laravel/PHP syntax,
  - Pint,
  - Prettier,
  - contract tests,
  - `npm run check`,
  - `npm run build`.
- For Svelte route-group paths such as `frontend/src/routes/(app)/...`, use PowerShell `-LiteralPath` when needed.
- Prefer explicit XAMPP PHP: `D:\xampp\php\php.exe`.
- The user has said long answers can be confusing. Default to concise, direct, command-focused replies.
- For source tutoring use: **function → input → checks/calculation → output → why it exists → one viva sentence**.
- For viva, describe what the code **actually does**. Do not overstate prototype features.
- If something is not verified from the current code, say so.
- Avoid adding unnecessary comments to production-facing source that advertise AI/tool-assisted development.

## Critical accuracy rule
Do **not** claim the normal coding judge compiles/runs arbitrary submitted programs. It currently uses a **prototype/simulated judge**.


---

# 1. PROJECT IDENTITY

CodeForge is a university Software Engineering / DBMS-style competitive-programming platform.

Current feature set includes:
- landing/authentication,
- dashboard,
- problems,
- prototype coding judge,
- contests and duels,
- rivalry,
- universities,
- profiles/search,
- Performance Profile,
- Ghost Race,
- SQL Battle Arena,
- gamification/topic analytics,
- database/admin tools,
- SQL Lab,
- personalized problem recommendations,
- contest win-probability prediction,
- Live Skill Graph,
- Submission Anomaly Detection,
- weakest-field TARGET mode on Problems,
- Selenium UI tests.

---

# 2. CURRENT TECHNOLOGY STACK

## Frontend
- SvelteKit
- Svelte 5
- TypeScript
- URL: `http://localhost:5173`

## Backend
- Laravel 11
- historically pinned around Laravel `11.56.1`
- URL: `http://localhost:8000`
- Sanctum/session-based authentication

## Database
- MySQL / MariaDB via XAMPP
- DB: `project`
- Host: `127.0.0.1`
- Port: `3307`
- User: `root`
- Password: blank

## Development environment
- Windows
- XAMPP 8.2.12
- XAMPP path: `D:\xampp`
- project path: `D:\xampp\htdocs\codeforge`
- PHP 8.2.12
- Node v24.19.0
- npm 11.17.0
- Python 3.13.5
- Selenium browser: Microsoft Edge


---

# 3. HIGH-LEVEL ARCHITECTURE

```text
User
  ↓
SvelteKit page/component
  ↓
frontend/src/lib/api.ts
  ↓
Laravel route
  ↓
Controller
  ↓
Service
  ↓
MySQL
  ↓
JSON
  ↓
Svelte UI
```

Viva mental model:

```text
Page / Component
    ↓
API request
    ↓
Route
    ↓
Controller
    ↓
Service
    ↓
Database
```

Important source areas:

```text
frontend/src/lib/api.ts
backend/routes/api.php
backend/routes/web.php
backend/app/Http/Middleware/
backend/app/Http/Controllers/
backend/app/Services/
frontend/src/routes/(app)/
frontend/src/lib/components/
backend/database/migrations/
backend/database/seeders/
```


---

# 4. RUNNING CODEFORGE

Start MySQL in XAMPP first.

Recommended:

```powershell
cd D:\xampp\htdocs\codeforge
powershell -ExecutionPolicy Bypass -File .\start-codeforge.ps1
```

or:

```text
START-CODEFORGE.bat
```

Manual backend:

```powershell
cd D:\xampp\htdocs\codeforge\backend
D:\xampp\php\php.exe artisan serve --host=localhost --port=8000
```

Manual frontend:

```powershell
cd D:\xampp\htdocs\codeforge\frontend
npm run dev -- --host localhost
```

Open:

```text
http://localhost:5173
```

## Transfer to another PC
Transfer:
- the CodeForge project ZIP,
- a MySQL database export.

Expected `.env` DB settings:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3307
DB_DATABASE=project
DB_USERNAME=root
DB_PASSWORD=
```

If the new PC uses `C:\xampp` rather than `D:\xampp`, hard-coded helper-script paths may need adjustment.


---

# 5. KNOWN ACCOUNTS

Known demo accounts:

```text
nafiz   / 123456
ismail  / 123456
tamjid  / 123456
ankita  / 123456
```

Historical ratings:

```text
nafiz   4950
ismail  4900
tamjid  4850
ankita  4800
```

Team/university context:
- team accounts historically aligned with UIU.

Do not assume a specific one is admin until the current `users.role` is checked.


---

# 6. ADMIN SYSTEM — VERIFIED

Admin middleware supplied by the user:

```php
class EnsureAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user()?->role === 'admin', 403, 'Admin access required.');

        return $next($request);
    }
}
```

Therefore:

```text
Authenticated user
    ↓
EnsureAdmin
    ↓
role === 'admin' ?
    ├── YES → allowed
    └── NO  → HTTP 403
```

Admin API:

```text
GET    /api/admin/tables
GET    /api/admin/tables/{table}
POST   /api/admin/users
PATCH  /api/admin/users/{id}
DELETE /api/admin/users/{id}
POST   /api/admin/sql-lab
```

Also admin-protected:

```text
POST /api/contests
POST /api/contests/{id}/close
```

Current admin username is **not yet verified**.

Check:

```sql
SELECT id, username, role
FROM users;
```

Admin requires:

```text
role = admin
```

If intentionally making Nafiz admin:

```sql
UPDATE users
SET role = 'admin'
WHERE username = 'nafiz';
```

Only state admin credentials after verifying the role.


---

# 7. IMPORTANT API ROUTES — VERIFIED FROM CURRENT ROUTE FILE

Public:

```text
GET /api/public/stats
GET /api/universities/options
```

Authenticated:

```text
GET  /api/me
GET  /api/users/options
GET  /api/dashboard

GET  /api/recommendations/problems

GET  /api/problems
GET  /api/problems/{id}
POST /api/problems/{id}/session
POST /api/problems/{id}/submit

GET  /api/performance-profile
GET  /api/performance-profile/{userId}

GET  /api/submission-anomaly
GET  /api/submission-anomaly/{userId}

GET  /api/profiles/{id}
GET  /api/gamification
GET  /api/rivalry
GET  /api/contest-prediction

GET  /api/universities
GET  /api/universities/compare
GET  /api/search

GET  /api/contests
POST /api/contests                  [admin]
GET  /api/contests/{id}
POST /api/contests/{id}/join
POST /api/contests/{id}/close       [admin]
POST /api/duels

GET  /api/ghost-races/options
GET  /api/ghost-races/history
POST /api/ghost-races
GET  /api/ghost-races/{id}
POST /api/ghost-races/{id}/submit
POST /api/ghost-races/{id}/forfeit

GET  /api/sql/challenges
GET  /api/sql/opponents
GET  /api/sql/leaderboard
GET  /api/sql/battles
POST /api/sql/battles
GET  /api/sql/battles/{id}
POST /api/sql/challenges/{id}/submit

GET  /api/database/users
```

Admin prefix:

```text
GET    /api/admin/tables
GET    /api/admin/tables/{table}
POST   /api/admin/users
PATCH  /api/admin/users/{id}
DELETE /api/admin/users/{id}
POST   /api/admin/sql-lab
```

Note: `/api/database/users` is under `auth:sanctum` in the supplied route file, not visibly under `admin`; inspect the controller for any extra authorization.


---

# 8. AUTHENTICATION MODEL

Intended standard:

```text
GET /sanctum/csrf-cookie
POST /login
GET /api/me
```

Frontend:
- `credentials: 'include'`
- `X-XSRF-TOKEN`
- use `localhost` consistently to avoid cookie/session mismatch.

Auth is Laravel Sanctum + session/cookie based.


---

# 9. NAFIZ'S MAIN IMPLEMENTED CONTRIBUTIONS

The user's current implemented contribution focus:

1. **Performance Profile**
2. **Ghost Race**
3. **SQL Battle Arena**

Older planning material may use names such as Code DNA or other early concepts. For the current project, use the implemented names above.


---

# 10. PERFORMANCE PROFILE

Originally: **Code DNA**  
Current route: `/performance-profile`

API:

```text
GET /api/performance-profile
GET /api/performance-profile/{userId}
```

Relevant historical classes/components:

```text
PerformanceProfileController
PerformanceProfileService
PerformanceProfileCalculator
PerformanceRadar
```

Dimensions:

```text
Problem Solving
Accuracy
Speed
Consistency
Versatility
Challenge Handling
```

Speed targets:

```text
Easy    180 sec
Medium  360 sec
Hard    600 sec
Other   300 sec
```

Speed:

```text
clamp((target / solve_seconds) * 82)
```

Difficulty:

```text
Easy    55
Medium  78
Hard    100
Other   50
```

Recency:

```text
<= 14 days    100
<= 30 days     90
<= 90 days     75
<= 180 days    60
older           45
```

Topic Score:

```text
Accuracy * 0.45
+ Difficulty * 0.25
+ Speed * 0.20
+ Recency * 0.10
```

Consistency:
- recent accepted outcomes as 1/0,
- 70% recent success mean,
- 30% stability from low variance.

Problem Solving:

```text
Average Topic Score * 0.45
+ Accuracy * 0.30
+ Challenge * 0.25
```

Overall:
- arithmetic mean of the 6 dimensions.

Archetypes:

```text
Fast Strategist
Precision Solver
Challenge Hunter
Versatile Explorer
Steady Climber
Developing Coder
```

Top 2 dimensions = strengths.  
Bottom 2 = growth areas.


---

# 11. GHOST RACE

Ghost Race is an **asynchronous race against a historical problem-solving session**, not live multiplayer.

Core historical service:

```text
backend/app/Services/GhostRaceService.php
```

Important tables/concepts:

```text
problem_sessions
submissions
ghost_races
```

Ghost source code is not exposed.

Concept:

```text
Current user starts race
        ↓
select historical ghost session
        ↓
timer starts
        ↓
current user solves
        ↓
compare progress/time against historical session
```

Historical fixes:
- ghost selection radio values:
  - `session_id`
  - `ghost_username`
- clearer verdict/result UI,
- timer freezes after race finishes.


---

# 12. SQL BATTLE ARENA

Core historical services:

```text
backend/app/Services/SqlJudgeService.php
backend/app/Services/SqlBattleService.php
```

Sandbox tables:

```text
arena_users
arena_universities
arena_problems
arena_submissions
```

Allowed:
- `SELECT`
- `WITH`

Blocked:
- multiple statements,
- destructive SQL,
- sensitive SQL,
- production tables.

Limits:

```text
MAX_QUERY_LENGTH      = 3000
MAX_RESULT_ROWS       = 1000
MAX_STATEMENT_SECONDS = 1
```

Uses `EXPLAIN` for efficiency.

Correctness concept:
- compare result values,
- preserve column count/order,
- optionally sort rows for order-insensitive challenges,
- alias differences should not falsely cause WA if values/shape match.

Score concept:

```text
700 + speed bonus + efficiency * 1.5
```

Pipeline:

```text
Student SQL
    ↓
Safety validation
    ↓
MariaDB execution
    ↓
syntax/runtime/timeout handling
    ↓
compare with expected result
    ↓
AC / WA
    ↓
EXPLAIN / efficiency
    ↓
score
```

Reference-query design is intended to live in the SQL challenge DB structure, historically likely something like `sql_challenges.expected_query`. Do not assert the exact column without inspecting current schema.


---

# 13. NORMAL CODING JUDGE — IMPORTANT REALITY

Current service supplied by the user:

```text
backend/app/Services/PrototypeJudgeService.php
```

It does **not** compile or execute arbitrary submitted programs.

It:
- trims source,
- recognizes demo markers such as:
  - `simulate: wa`
  - `simulate: tle`
  - `simulate: ce`
  - `simulate: re`
- treats very short source as CE,
- checks simple language-specific text patterns,
- uses code length to choose WA/AC,
- simulates runtime,
- simulates memory,
- simulates failed test number,
- returns feedback.

Most important normal decision:

```php
(! $ok || strlen($source) < 70) ? 'WA' : 'AC'
```

Accurate viva wording:

> The current normal-problem judge is a prototype that models judge verdict and performance behavior. A production judge would compile and run source code in isolated runtimes against hidden test cases. SQL Battle, however, does execute real SQL in a restricted sandbox.


---

# 14. TAMJID CONTRIBUTIONS

## Personalized Problem Recommendations

Historical files:

```text
backend/app/Services/ProblemRecommendationService.php
backend/app/Http/Controllers/ProblemRecommendationController.php
frontend/src/lib/components/ProblemRecommendations.svelte
```

API:

```text
GET /api/recommendations/problems
```

Integrated on `/problems`.

Original teammate concept:

```text
k = 3
global prior = 50%

shrunkAccuracy =
(solved + k * globalAcc)
/
(attempted + k)

frictionPenalty = 0.5

weaknessScore =
0.7 * (1 - shrunkAccuracy)
+ 0.3 * 0.5
```

Integrated version derives topic data from existing submissions/problems rather than duplicating prototype tables.

Historical smoke:
- user: nafiz
- weakest field: Math
- recommended problems: 0 at that moment because no eligible unsolved Math problem was available.

## Contest Win Probability

Historical files:

```text
backend/app/Services/ContestWinProbabilityService.php
backend/app/Http/Controllers/ContestPredictionController.php
frontend/src/lib/components/ContestWinProbability.svelte
```

API:

```text
GET /api/contest-prediction
```

Integrated in `/rivalry`.

Strength:

```text
rating / 50
+ Performance Profile overall * 1.5
+ consistency * 0.45
```

Prediction:

```text
A% = A / (A + B) * 100
B% = 100 - A%
```

Historical smoke:
- CodeForge Round #45
- nafiz 48.65%
- ismail 51.35%

Current contest score was shown as context, not used as the prediction input.


---

# 15. ANKITA CONTRIBUTIONS

1. **Live Skill Graph**
2. **Submission Anomaly Detection**

Live Skill Graph belongs inside `/performance-profile`, not on a separate page.

Historical files:

```text
backend/app/Services/SubmissionAnomalyService.php
backend/app/Http/Controllers/SubmissionAnomalyController.php
frontend/src/lib/components/SkillGraph.svelte
frontend/src/lib/components/SubmissionAnomaly.svelte
```

APIs:

```text
GET /api/submission-anomaly
GET /api/submission-anomaly/{userId}
```

Live Skill Graph thresholds:

```text
>= 75   Strong
>= 50   Developing
< 50    Weak
```

Later UI:
- rectangular vertical bars,
- y-axis 0–100,
- grid lines at 0/25/50/75/100,
- score above,
- topic below,
- Strong/Developing/Weak,
- solved/attempted,
- horizontal scrolling.

Anomaly logic:

Rapid submission:
```text
gap <= 60 sec
```

Repeated failure:
```text
>= 3 non-AC attempts on a problem
```

Similarity:
- strip block comments,
- strip line comments,
- remove whitespace,
- lowercase,
- PHP `similar_text()`,
- threshold `>= 92%`.

Important:
- current comparison is against the **same user's prior submissions**,
- not cross-user plagiarism detection.

Risk:

```text
0      NORMAL
1–3    LOW
4–7    MEDIUM
8+     HIGH
```

Viva wording:

> The anomaly module flags unusual submission behavior; it does not prove cheating.

Historical smoke:
- skill topics: 9
- risk: HIGH
- rapid attempts: 299
- repeated: 0
- similarity: 0


---

# 16. WEAKEST-FIELD TARGET FEATURE

Current page:

```text
/problems
```

UI:
- `TARGET`
- `CLEAR TARGET`

Behavior:

```text
Existing recommendation logic
        ↓
detect weakest field
        ↓
load problems
        ↓
filter to weakest field
        ↓
show focused problems
```

It reuses Tamjid's existing recommendation API instead of creating a duplicate weakness algorithm.

No DB change was required.

Later Selenium output showed both TARGET tests as **PASSED**, which is good evidence the feature is present in the current working project.


---

# 17. COMMENT CLEANUP REQUEST

The user requested removing unnecessary comments from actual Svelte pages/components that could visibly advertise AI/tool-assisted development.

Cleanup patch targeted:

```text
frontend/src/routes/**/*.svelte
frontend/src/lib/components/**/*.svelte
```

Designed to remove obvious meta comments containing terms such as:
- AI
- ChatGPT
- generated
- assistant
- patch
- teammate
- integration
- module
- helper
- copilot
- LLM

while preserving directives such as:
- `@ts-ignore`
- `@ts-expect-error`
- ESLint
- Prettier
- source map directives.

Execution of that exact patch was not later confirmed. Inspect current source if this matters.


---

# 18. SELENIUM TESTING — CURRENT SETUP

Location:

```text
D:\xampp\htdocs\codeforge\codeforge_selenium_tests
```

Python:
```text
3.13.5
```

Installed during current session:
- Selenium 4.49.0
- pytest 9.1.1

Browser:
- Microsoft Edge
- Chrome is not installed
- Brave exists, but Edge is the chosen WebDriver.

Test files:

```text
requirements.txt
pytest.ini
config.py
helpers.py
conftest.py

tests/test_01_public_pages.py
tests/test_02_login.py
tests/test_03_navigation.py
tests/test_04_problems_target.py
tests/test_05_performance_profile.py
tests/test_06_rivalry.py
tests/test_07_ghost_race.py
tests/test_08_sql_battle.py
tests/test_09_logout.py
```

Default test login:

```text
nafiz / 123456
```

The setup was changed to:
- use Edge,
- auto-login,
- reuse one authenticated browser session for authenticated tests.

Run all:

```powershell
cd D:\xampp\htdocs\codeforge\codeforge_selenium_tests
python -m pytest -v
```

Examples:

```powershell
python -m pytest tests/test_02_login.py -v
python -m pytest tests/test_04_problems_target.py -v
python -m pytest tests/test_05_performance_profile.py -v
python -m pytest tests/test_07_ghost_race.py -v
python -m pytest tests/test_08_sql_battle.py -v
```

## Selenium debugging history

### Browser
Initial suite used Chrome. User does not have Chrome. Converted to Edge.

### Public pages
Original generic tests over-assumed visible body text. Simplified to smoke checks.

### Login
Original selectors did not match current form reliably. Helpers were made more flexible and auto-fill `nafiz / 123456`.

### Navigation
All 10 navigation tests initially reached the correct routes but failed because they expected specific visible words. They were rewritten to check:
- route opens,
- no redirect to `/login`,
- DOM exists.

### Latest confirmed full run before final async fix
Collected:

```text
26 tests
```

Result:

```text
16 passed
10 failed
```

Confirmed passed included:
- public tests,
- valid login test,
- all 10 navigation tests,
- both Problems TARGET tests.

The remaining failures repeatedly showed:

```text
Loading System
```

which means Selenium was asserting before Svelte finished rendering.

A later fix introduced:

```text
wait_for_app_ready()
```

to wait until the page is no longer blank or only:
- `Loading`
- `Loading...`
- `Loading System`
- `Loading System...`

That fix also updated:
- dashboard,
- Performance Profile,
- Rivalry,
- Ghost Race,
- SQL Battle,
- logout tests.

**No final output has yet been recorded proving the latest async fix produced 26/26.**
Future model should ask for or inspect the next `python -m pytest -v` output.

Teacher explanation:

> Selenium automates a real web browser. Our tests open CodeForge, perform user actions such as login or navigation, and verify that the expected page or feature appears.


---

# 19. CONTRACT / BUILD HEALTH HISTORY

A mature framework baseline historically passed:

```text
43 backend contracts
27 frontend contracts
```

These are historical baselines, not guaranteed current counts. Run the current project's own scripts before claiming exact current totals.


---

# 20. IMPORTANT PATCH HISTORY

Early:
- PATCH-001 to PATCH-008: old plain-PHP fixes.

Framework migration:
- PATCH-009 family: Laravel 11 + SvelteKit/Svelte 5 migration.
- PATCH-010 / 010B.
- PATCH-011 A–D: readability, Pint, Prettier, contracts.

Ghost Race:
- PATCH-012: ghost selection radio values.
- PATCH-013: verdict/result UI.
- PATCH-014: freeze timer after race completion.

SQL:
- PATCH-015: SQL Battle integration.
- PATCH-016: SQL judge accuracy/self-tests.

Universities:
- PATCH-017.

Landing:
- PATCH-018.

Performance Profile rename:
- PATCH-019 / 019B.
- PATCH-021 / 021B.

Sidebar:
- PATCH-022B.

Tamjid:
- PATCH-023.
- PATCH-023B.

Ankita:
- PATCH-024.
- PATCH-024B.
- PATCH-024C.
- PATCH-024D.

Recent:
- PATCH-025: frontend comment cleanup.
- PATCH-026: weakest-field TARGET button.

Recent Selenium helper patches:
- Edge conversion,
- auto-login,
- navigation-test fix,
- async-page wait fix.

If patch history conflicts with the current ZIP, **trust the current ZIP**.


---

# 21. GIT / GITHUB HISTORY AND UNCERTAINTY

Repository:

```text
https://github.com/Ismail02234/CodeForge_SE_Project.git
```

Main branch used.

Historical local commit:

```text
5698c37 Integrate teammate features and performance profile modules
```

Safety branch:

```text
backup/local-features-before-main-sync-Sun09-13-2026-00-08-01-37
```

Historical remote-only commit:

```text
3bd3c81 Implement skill graph and submission anomaly detection
```

A merge conflicted in Ankita-related files.

Helpers were created for:
- force-push current local version,
- cleanup of unnecessary patch/instruction files from Git.

**Do not assume those scripts were successfully executed.**

If Git state matters, first check:

```powershell
git status
git branch --show-current
git log --oneline -10
git fetch origin
git log --oneline --decorate --graph --all -15
```

Never overwrite remote history without explicit user intent.


---

# 22. UI / DESIGN STYLE

General CodeForge visual direction:

```text
black
red
orange
cyan
```

Fonts:

```text
Space Grotesk
JetBrains Mono
```

Desired feel:
- aggressive,
- futuristic,
- readable,
- student-built but polished.

When adding features:
- preserve the existing theme,
- integrate into existing pages where appropriate,
- avoid unrelated template styling.


---

# 23. UNIVERSITY / TEAM CONTEXT

Historically seeded universities:

```text
UIU
NSU
BRAC
IUB
AIUB
EWU
DIU
UAP
AUST
ULAB
```

Team accounts were associated with UIU.


---

# 24. VIVA TRAPS / DO-NOT-SAY LIST

Do not say:
- normal coding judge really compiles/runs submitted languages,
- anomaly detection proves cheating,
- anomaly similarity is cross-user plagiarism detection,
- Ghost Race is live multiplayer,
- contest prediction uses machine learning unless current code proves it,
- a specific user is admin without checking `role`,
- latest Git cleanup/force push definitely succeeded,
- latest Selenium async fix definitely produced 26/26,
- exact SQL reference-query field without checking schema.

Prefer:
- prototype,
- simulated,
- heuristic,
- rule-based,
- asynchronous historical race,
- restricted SQL sandbox,
- role-based admin,
- current source is the final authority.


---

# 25. CURRENT UNRESOLVED / VERIFY-NEXT ITEMS

Future model should verify from current ZIP or fresh output:

1. Which user currently has:
   ```text
   role = admin
   ```

2. Whether latest Selenium async fix results in:
   ```text
   26 passed
   ```

3. Current Git status and remote-main status.

4. Whether PATCH-025 comment cleanup was executed.

5. Whether earlier sidebar/UI patches remain partially applied.

6. Exact current SQL challenge field holding the reference query.

7. Exact frontend UI location for role-protected admin tooling if user asks for an admin portal.

8. If current source differs from this handoff, trust source.


---

# 26. RECOMMENDED FIRST ACTION WITH THE CURRENT ZIP

When receiving the ZIP, inspect:

```text
backend/routes/api.php
backend/routes/web.php
backend/app/Http/Middleware/
backend/app/Http/Controllers/
backend/app/Services/

frontend/src/lib/api.ts
frontend/src/lib/components/

frontend/src/routes/(app)/problems/
frontend/src/routes/(app)/performance-profile/
frontend/src/routes/(app)/ghost-race/
frontend/src/routes/(app)/sql-battle/
frontend/src/routes/(app)/rivalry/

backend/database/migrations/
backend/database/seeders/

codeforge_selenium_tests/
```

Then verify environment:

```powershell
D:\xampp\php\php.exe -v
node -v
npm -v
python --version
```

Then verify:
- DB connectivity,
- backend/frontend tests/checks/build,
- Selenium.

Do not alter the DB destructively unless requested.


---

# 27. RECOMMENDED PATCH FORMAT

A good patch should:

1. verify the project location,
2. verify target files exist,
3. create timestamped backup,
4. make only required changes,
5. preserve UTF-8 cleanly,
6. avoid destructive DB actions,
7. format changed files,
8. run relevant tests/check/build,
9. print a compact success summary.

Preferred backup structure:

```text
patch-backups/PATCH-XXX-yyyyMMdd-HHmmss/
```

Preferred output style:

```text
[OK] backup created
[OK] files changed
[OK] tests pass
[OK] svelte-check passes
[OK] build passes
```


---

# 28. USER RESPONSE STYLE

Default:
- short,
- direct,
- actionable.

Good format:

```text
Run this:
<command>

Expected:
<result>

If it fails, send:
<specific output>
```

Avoid:
- giant conceptual explanations unless requested,
- asking the user to manually make many edits when a patch can do it,
- unverified implementation claims,
- too many alternatives at once.


---

# 29. TEACHER / VIVA-FRIENDLY PROJECT SUMMARY

> CodeForge is a SvelteKit and Laravel competitive-programming platform backed by MySQL. It includes problem solving, contests, profiles, performance analytics, Ghost Race, SQL Battle, recommendations, rivalry prediction, university analytics, admin/database tools, and Selenium UI testing. The normal coding judge is currently a safe prototype that simulates verdict behavior, while SQL Battle executes restricted SQL in a sandbox. Nafiz's main implemented contributions are Performance Profile, Ghost Race, and SQL Battle Arena.

## Nafiz
- Performance Profile
- Ghost Race
- SQL Battle Arena

## Tamjid
- Personalized problem recommendation
- Contest win probability

## Ankita
- Live Skill Graph
- Submission Anomaly Detection


---

# 30. CURRENT SELENIUM TEACHER DEMO ORDER

Once stable:

```powershell
python -m pytest tests/test_02_login.py -v
python -m pytest tests/test_04_problems_target.py -v
python -m pytest tests/test_05_performance_profile.py -v
python -m pytest tests/test_07_ghost_race.py -v
python -m pytest tests/test_08_sql_battle.py -v
```

Then optionally:

```powershell
python -m pytest -v
```

Simple explanation:

```text
1. Selenium opens Edge.
2. It logs in automatically.
3. It accesses protected routes.
4. It interacts with features.
5. pytest reports pass/fail.
```


---

# 31. KEY FACTS TO PRESERVE

```text
Project:
CodeForge

Path:
D:\xampp\htdocs\codeforge

Frontend:
http://localhost:5173

Backend:
http://localhost:8000

Database:
project

MySQL port:
3307

Known demo login:
nafiz / 123456

Python:
3.13.5

Selenium:
Microsoft Edge

Nafiz main contributions:
Performance Profile
Ghost Race
SQL Battle Arena
```

---

# 32. FINAL HANDOFF RULE

If this Markdown and the current CodeForge ZIP disagree:

> **Trust the current source code first.**

This handoff is the context/history/workflow layer. Use it to understand:
- why the code looks the way it does,
- feature ownership,
- safe viva explanations,
- patch history,
- testing history,
- user workflow preferences,
- current uncertainties.

Do not make the user repeat this context unless the current ZIP proves something has materially changed.
