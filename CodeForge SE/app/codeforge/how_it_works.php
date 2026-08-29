<?php
require_once __DIR__ . '/core/bootstrap.php';
require_login($pdo);
$pageTitle = 'How It Works';
include __DIR__ . '/includes/header.php';
?>
<main class="page page-sm">
    <div class="page-head">
        <div>
            <span class="eyebrow">Technical documentation</span>
            <h1 class="page-title">How CodeForge works</h1>
            <p class="page-subtitle">A viva-friendly map of the architecture, relational model, analytics, gamification, recommendation logic and safety boundaries used by the current build.</p>
        </div>
    </div>

    <section class="grid grid-2">
        <div class="card card-pad">
            <span class="eyebrow">01 · Architecture</span>
            <h2>Plain PHP with separated responsibilities</h2>
            <div class="code-box">Pages / Controllers
        ↓
Services (business rules)
        ↓
Repositories / PDO
        ↓
MariaDB / MySQL</div>
            <p class="muted small">Shared authentication, helpers and layout stay in core/includes. Feature logic lives in services so the UI can change without rewriting the database rules.</p>
        </div>

        <div class="card card-pad">
            <span class="eyebrow">02 · Relational core</span>
            <h2>Normalized event history</h2>
            <div class="code-box">users → problem_sessions → submissions ← problems
contests ↔ contest_problems ↔ problems
contests ↔ contest_participants ↔ users
users → gamification_profiles
users ↔ user_badges ↔ badges</div>
            <p class="muted small">Solved totals are derived from accepted submissions instead of being trusted as stale counters. Gamification is isolated in its own tables, so authentication records are not altered by the feature merge.</p>
        </div>

        <div class="card card-pad">
            <span class="eyebrow">03 · Authentication & security</span>
            <h2>Fail fast before rendering</h2>
            <ul class="doc-list">
                <li>Protected pages call <code>require_login()</code> before output.</li>
                <li>Admin pages use server-side role checks rather than hidden buttons.</li>
                <li>Writes use CSRF tokens and prepared PDO statements.</li>
                <li>Passwords use <code>password_hash()</code>/<code>password_verify()</code>.</li>
                <li>API authentication returns JSON 401 responses instead of HTML redirects.</li>
            </ul>
        </div>

        <div class="card card-pad">
            <span class="eyebrow">04 · Gamification</span>
            <h2>XP, levels, streaks and badges</h2>
            <div class="code-box">First accepted solve per problem:
Easy   → 10 XP
Medium → 25 XP
Hard   → 50 XP

XP → threshold level
accepted solve dates → streaks
history conditions → badges</div>
            <p class="muted small">The profile is synchronized from accepted-submission history after an AC. Repeated accepted submissions do not duplicate XP. Badge inserts are idempotent through the user/badge composite primary key.</p>
        </div>

        <div class="card card-pad">
            <span class="eyebrow">05 · Quest Advisor</span>
            <h2>Weak-area recommendations</h2>
            <div class="code-box">problems
LEFT JOIN distinct accepted problems
GROUP BY topic
ORDER BY solved_count ASC
        ↓
weakest topic
        ↓
NOT EXISTS accepted submission
        ↓
up to 3 unsolved recommendations</div>
            <p class="muted small">The original teammate idea used a set-difference query. This merge keeps the behavior but uses <code>NOT EXISTS</code> and a distinct accepted-problem subquery to avoid duplicate counting.</p>
        </div>

        <div class="card card-pad">
            <span class="eyebrow">06 · Skill Tree</span>
            <h2>Live topic mastery</h2>
            <div class="code-box">mastery % =
  distinct accepted problems in topic
  ─────────────────────────────────── × 100
       total problems in topic</div>
            <p class="muted small">One grouped query returns all topics. The dashboard does not run a correlated query once per topic, which keeps the feature efficient as the problem library grows.</p>
        </div>

        <div class="card card-pad" id="dna">
            <span class="eyebrow">07 · Code DNA</span>
            <h2>Explainable scoring</h2>
            <div class="code-box">Topic Score =
  45% submission accuracy
+ 25% solved difficulty
+ 20% normalized solve speed
+ 10% recency</div>
            <p class="muted small">The overall fingerprint combines problem solving, accuracy, speed, consistency, versatility and challenge handling. Archetypes are deterministic and explainable.</p>
        </div>

        <div class="card card-pad">
            <span class="eyebrow">08 · Ghost Race</span>
            <h2>Historical event replay</h2>
            <div class="code-box">completed problem_session
        ↓
submissions ordered by elapsed_seconds
        ↓
ghost timeline replay
        ↓
challenger session
        ↓
compare accepted solve time</div>
            <p class="muted small">Only timings and verdicts are replayed. Opponent source code remains private, and ordinary practice sessions are kept separate from active race sessions.</p>
        </div>

        <div class="card card-pad">
            <span class="eyebrow">09 · SQL Battle</span>
            <h2>Sandboxed query judging</h2>
            <div class="code-box">user query
  → SELECT/WITH validator
  → arena_* allowlist
  → execute with limits
  → compare result set
  → EXPLAIN efficiency
  → score</div>
            <p class="muted small">Production tables, destructive SQL, multi-statements, server identity functions and delay/file operations are blocked.</p>
        </div>

        <div class="card card-pad">
            <span class="eyebrow">10 · Search & joins</span>
            <h2>Readable relational results</h2>
            <div class="code-box">submission history:
submissions INNER JOIN problems
  ON problem_id = problems.id

search:
users / problems / universities
LIKE :query
LIMIT bounded results</div>
            <p class="muted small">Foreign IDs are joined to human-readable names, while global search caps both input length and result counts.</p>
        </div>

        <div class="card card-pad">
            <span class="eyebrow">11 · University analytics</span>
            <h2>Live leaderboard and top solver</h2>
            <div class="code-box">accepted solves per user
        ↓
user_stats CTE
        ↓
ROW_NUMBER() per university
        ↓
student count / avg rating /
total solves / top solver</div>
            <p class="muted small">The top-solver addition is calculated in the same query as the university aggregates, avoiding the teammate version's N+1 query pattern.</p>
        </div>

        <div class="card card-pad">
            <span class="eyebrow">12 · Prototype judge boundary</span>
            <h2>Safe by design</h2>
            <p class="muted small">This software-lab build still does not compile or execute arbitrary C++, Python, JavaScript or PHP from users. The deterministic prototype judge records verdicts and performance metadata so the database workflows can be demonstrated safely.</p>
            <div class="alert alert-info">A real isolated/containerized execution service is a later architectural upgrade, not part of this feature merge.</div>
        </div>
    </section>
</main>
<?php include __DIR__ . '/includes/footer.php'; ?>
