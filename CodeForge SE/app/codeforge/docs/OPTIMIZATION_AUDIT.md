# CodeForge 2.0 — Optimization Audit

This audit documents the final plain-PHP optimization pass before framework migration.

## Runtime and database

- Code DNA now aggregates historical submission data in SQL instead of loading the entire history into PHP memory.
- Problem, profile, university, contest and SQL-challenge statistics use grouped/derived aggregates instead of repeated correlated queries where practical.
- Ghost selection returns one representative fastest run per user/problem, preventing large seeded histories from flooding the UI.
- Practice sessions are isolated from active Ghost Race sessions.
- SQL Arena and SQL Lab have query-length, statement, table, result-count and execution-time safeguards.
- Purpose-built indexes were added for recent submissions, verdict/problem lookups, ghost-race state, battle discovery and activity history.
- `database/optimize_cli.php` applies missing indexes idempotently to an existing local database and refreshes optimizer statistics.

## Security and correctness

- Strict cookie-only sessions, session ID rotation, CSRF tokens, role guards and JSON-native API auth responses are used.
- Basic browser security headers are emitted centrally.
- External/header-injection redirects are rejected.
- Unexpected database exceptions are logged instead of exposing raw internals to normal users.
- University averages no longer weight users according to how many submissions they have.
- SQL Battle input is restricted to non-recursive read-only sandbox queries.

## Front end

- `index.php` remains the dedicated public landing page; `dashboard.php` remains the authenticated command center.
- Space Grotesk is the primary UI/display face, with JetBrains Mono for code and telemetry.
- Assets are cache-busted automatically with file modification timestamps.
- Sidebar scrolling is independent and prevents scroll chaining into the main page.
- Landing particles, parallax and tilt effects are frame-capped/visibility-aware and honor reduced-motion preferences.
- Code DNA radar rendering is responsive to container resizing and device pixel ratio.
- Keyboard search (`Ctrl/Cmd + K`), improved focus states and mobile navigation behavior were added.

## Verification

- Full PHP syntax pass: all PHP files lint cleanly in the generated build.
- Automated local test suite: 27 passed, 0 failed.
- The patch validates that `index.php` is public, `dashboard.php` is protected, logout controls exist, the new font system is active and sidebar independent scrolling remains installed.

## Intentional limitations at this stage

- Programming submissions still use the safe deterministic prototype judge; arbitrary C++/Python/Java is not executed locally.
- Ghost Race replay is polling/frame-driven rather than WebSocket-driven.
- SQL Battle is an isolated training sandbox, not a general SQL execution console.
- No framework has been introduced yet, by design.

These limitations should be addressed during the later framework/containerized-judge phase rather than by weakening the current local safety model.
