# CodeForge 2.0 implementation notes

## What was repaired from the old project

- Unified database access on PDO.
- Removed reliance on undefined `$conn` MySQLi connections.
- Synced `users` schema with `password` and `role` used by authentication.
- Standardized table names to lowercase.
- Fixed password hashing and verification.
- Added reusable authentication and admin guards.
- Added CSRF protection for state-changing forms.
- Added transactions for submission/race flows.
- Replaced broken/missing page routes with working implementations or safe redirects.
- Removed unrestricted raw SQL execution from the user-facing product.
- Added responsive reusable UI shell and modern dark visual system.

## New features

### Code DNA
Weighted topic score: 45% accuracy + 25% difficulty + 20% speed + 10% recency. Core dimensions include problem solving, accuracy, speed, consistency, versatility, and challenge handling.

### Ghost Race
A ghost is a completed historical `problem_session`. Its submissions are replayed by `elapsed_seconds`. A new challenger session records current attempts and resolves won/lost/draw transactionally.

### SQL Battle Arena
Queries are validated as single SELECT/CTE statements, comments/destructive keywords are rejected, table access is restricted to `arena_*`, result sets are compared to deterministic reference queries, and accepted runs receive speed + estimated efficiency bonuses.
