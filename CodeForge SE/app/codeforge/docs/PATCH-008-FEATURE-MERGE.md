# PATCH-008 — Teammate Feature Merge

## Comparison result

The teammate archive was compared against the PATCH-007 baseline by file list, schema, queries, page behavior and reusable functions. Its Bootstrap/"coding adventure" visual layer was intentionally not copied. Existing CodeForge routes, landing page, authentication, Code DNA, Ghost Race, SQL Battle, contest logic, search and admin security remain the baseline implementation.

### Functional additions found and merged

1. **Gamification engine**
   - XP from accepted solves: Easy 10, Medium 25, Hard 50.
   - Ten threshold-based progression levels.
   - Current and longest daily solve streaks.
   - Six badges: First Steps, On a Roll, Century, Explorer, Topic Master, Sprout Master.
   - Locked/earned badge cabinet on profiles.
   - XP and newly unlocked badge feedback after accepted submissions.

2. **Quest Advisor**
   - Finds the topic with the lowest accepted-problem completion count.
   - Recommends up to three unsolved problems from that topic.
   - Uses `NOT EXISTS` rather than the teammate version's `NOT IN` pattern.

3. **Skill Tree / topic mastery**
   - Live solved/total counts for every topic.
   - Completion percentages shown on the dashboard.
   - Implemented with one grouped query instead of a correlated subquery per topic.

4. **Expanded profile history**
   - Recent submission history increased to 20 entries.
   - Language is shown with verdict, difficulty and timestamp.

5. **University top solver**
   - Adds the teammate version's top-coder idea to each university card.
   - Uses one CTE/window-function query instead of one extra query per university.

6. **Expanded technical guide**
   - Documents relational design, authentication, gamification, recommendation logic, skill mastery, joins/search, Code DNA, Ghost Race, SQL Battle, university analytics and the prototype judge boundary.

## Existing baseline features that were already stronger

The teammate archive also contains rivalry/duel pages, university comparison, global search, SQL Lab, database CRUD, problems, contests and contest view. Those are not copied because PATCH-007 already has the same feature set with stronger authentication, CSRF protection, bounded queries, safer SQL handling, live aggregates and the existing futuristic UI.

## Source-only stubs not presented as completed teammate features

The teammate SQL file contains unused Phase 2 schema stubs for `coins`, `hints`, `hint_purchases`, `classrooms` and `classroom_members`. No teammate controller/page/service uses them. They are therefore not exposed as fake completed features in PATCH-008.

## Isolation and optimization choices

- Gamification uses separate `gamification_profiles`, `levels`, `badges` and `user_badges` tables. No authentication column in `users` is changed.
- No database trigger is used. A profile is synchronized from accepted-submission history, making repeated AC submissions idempotent and repairing drift automatically.
- Gamification synchronization runs **after** the core practice or Ghost Race submission transaction commits. A reward-system error cannot invalidate a valid solve or race result.
- Existing accepted-solve indexes are reused and one history-oriented index is added for user/verdict/date/problem access.
- Dashboard recommendations use bounded result counts.
- University top-solver ranking avoids the teammate version's N+1 query pattern.
- Existing landing page, app header/sidebar, typography, CSS system and JavaScript are retained.

## Verification

- Full PHP lint: **53 PHP files, 0 syntax errors**.
- Automated regression/feature tests: **45 passed, 0 failed**.
- The tests continue checking the public `index.php`, authenticated `dashboard.php`, logout controls, font system, sidebar scrolling, SQL sandbox restrictions, prototype judge behavior and route integrity.
- Sandbox limitation: this environment has PHP/PDO but no MariaDB server or `pdo_mysql` driver, so the live migration could not be executed here. The patch runs the migration and optimizer on the user's XAMPP installation after source validation; if MySQL is stopped, it exits with a clear migration-pending status instead of claiming full success.
