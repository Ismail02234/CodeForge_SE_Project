# Migration notes from old CodeForge

This version intentionally uses a corrected schema rather than patching the inconsistent old dump in place.

Major changes:

- `users.password` and `users.role` are first-class schema fields.
- All application queries use lowercase table names and PDO.
- Redundant `users.solvedCount`, `problems.solvedBy`, and university aggregate counters were removed; those values are calculated from submission history.
- `problem_sessions` records solve timelines for Code DNA and Ghost Race.
- `submissions` now stores `session_id`, `source_code`, `elapsed_seconds`, runtime/memory metadata, and proper timestamps.
- Added `ghost_races`, `sql_challenges`, `sql_battles`, `sql_attempts`, and isolated `arena_*` SQL practice tables.
- Added `activity_logs` for auditable feature events.

Because the old schema contained contradictory PHP expectations, the recommended local migration is:

1. Export anything you actually need from the old `project` database.
2. Drop/recreate `project`.
3. Import the new root `data.sql`.
