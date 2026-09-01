# CodeForge Framework Migration Audit

## Source-to-framework mapping

| Plain PHP feature | SvelteKit route | Laravel endpoint/service |
|---|---|---|
| `index.php` landing | `/` | `GET /api/public/stats` |
| `login.php` | `/login` | `POST /login` |
| `register.php` | `/register` | `POST /register` |
| `dashboard.php` | `/dashboard` | `GET /api/dashboard` |
| `problems.php` | `/problems` | `GET /api/problems` |
| `solve.php` | `/problems/[id]` | problem session + submit API |
| `contests.php` | `/contests` | contests + duel API |
| `contest_view.php` | `/contests/[id]` | contest detail/join/close API |
| `rivalry.php` | `/rivalry` | `GET /api/rivalry` |
| `university.php` | `/universities` | `GET /api/universities` |
| `university_compare.php` | `/universities/compare` | compare API |
| `search.php` | `/search` | `GET /api/search` |
| `profile.php` | `/profile/[id]` | profile API |
| `code_dna.php` | `/code-dna` | `CodeDnaService` |
| `ghost_race.php` | `/ghost-race` | Ghost Race options/history |
| `ghost_race_play.php` | `/ghost-race/[id]` | race state/submit/forfeit |
| `sql_battle.php` | `/sql-battle` | SQL challenge/battle API |
| `sql_battle_play.php` | `/sql-battle/[id]` | battle attempts API |
| `database.php` + `edit_user.php` | `/database` | database read + admin user CRUD |
| `sql_lab.php` | `/sql-lab` | admin read-only SQL Lab |
| `how_it_works.php` | `/how-it-works` | static SvelteKit documentation |
| `includes/gamification.php` | dashboard/profile UI | `GamificationService` |

## Regression fixes made during migration

- Preserved `index.php` behavior as a dedicated public SvelteKit landing route instead of redirecting authenticated users automatically.
- Kept the application dashboard separate and protected.
- Preserved visible logout controls.
- Preserved independent sidebar scrolling with scroll-chain containment.
- Preserved Space Grotesk + JetBrains Mono typography.
- Preserved the mouse-reactive landing animation with visibility/reduced-motion optimizations.
- Fixed a migrated controller bug where the problem ID and session ID were passed to `ProblemPracticeService::submit()` in the wrong order.
- Restored user search by both username **and university**, matching the original search behavior.
- Preserved contest scoring on the first accepted contest submission only.
- Kept Ghost Race practice sessions isolated from normal practice sessions.
- Kept source code hidden in Ghost Race replay events.
- Preserved SQL Arena table isolation and deterministic result comparison.
- Added both MariaDB and MySQL statement-timeout support paths.
- Preserved read access to the database view for authenticated users while keeping write operations admin-only.
- Restored admin create/edit/delete user operations.
- Restored SHOW/DESCRIBE/EXPLAIN support in the admin read-only SQL Lab.

## Database optimization

The framework migration adds missing compound indexes for the heaviest access paths, including:

- user/verdict/problem submission analytics;
- session timeline playback;
- contest score checks;
- Ghost Race history;
- SQL Battle attempt and player history;
- activity log history.

The migration is idempotent and does not drop current CodeForge tables.

## Validation completed before packaging

- All backend PHP files linted successfully.
- 43 backend contract/logic checks passed.
- 27 frontend route/API/UI contract checks passed.
- Every Laravel route/controller reference was checked against an existing controller method.
- Every expected feature route from the plain-PHP project is represented in the SvelteKit application.
- Svelte component block structure was checked for unbalanced `{#if}` / `{#each}` directives and markup-level TypeScript annotations.

Full Svelte compilation (`npm run check` / `npm run build`) is performed by `setup-codeforge.ps1` after npm dependencies are downloaded on the target machine.

## Final packaging status

The completed migration package includes:

- one-click `PATCH-009-Framework-Migration.ps1` installer;
- automatic archival of the plain-PHP source;
- non-destructive reuse of the existing MySQL CodeForge database;
- automatic Composer/npm setup and framework build on the target Windows machine;
- `verify-codeforge.ps1` for source or full-build regression verification;
- development and compiled-production start scripts;
- a root XAMPP bridge redirecting `/codeforge/` to the SvelteKit frontend.

Final dependency-free validation before packaging:

- all PHP source files: syntax clean;
- backend contract suite: **43 passed, 0 failed**;
- frontend route/API/UI contract suite: **27 passed, 0 failed**.

The sandbox used to prepare the package could not finish downloading npm dependencies within its network timeout. Therefore the final Svelte compiler/type check and production build are intentionally executed by `setup-codeforge.ps1` on the target machine after `npm install`; the setup stops immediately if either check fails.
