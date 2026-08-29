<?php
require_once __DIR__ . '/core/bootstrap.php';require_login($pdo);$pageTitle='How It Works';include __DIR__.'/includes/header.php';
?>
<main class="page page-sm"><div class="page-head"><div><span class="eyebrow">Technical documentation</span><h1 class="page-title">How CodeForge 2.0 works</h1><p class="page-subtitle">Architecture, relational design, security decisions and the formulas behind the three new features.</p></div></div>
<section class="grid grid-2">
<div class="card card-pad"><span class="eyebrow">Architecture</span><h2>Plain PHP, structured like a future framework app</h2><div class="code-box">Pages / Controllers
        ↓
Services (business rules)
        ↓
Repositories / PDO
        ↓
MySQL / MariaDB</div><p class="muted small">New business logic is separated from HTML so migration to Laravel/React later is adaptation rather than a full rewrite.</p></div>
<div class="card card-pad"><span class="eyebrow">Relational core</span><h2>Normalized event history</h2><p class="muted small">Solved counts are no longer trusted as duplicated static fields. They are calculated from accepted submissions. Problem sessions capture START → attempts → AC, which becomes reusable analytical data.</p><div class="code-box">users → problem_sessions → submissions ← problems
contests ↔ contest_problems ↔ problems
contests ↔ contest_participants ↔ users</div></div>
<div class="card card-pad" id="dna"><span class="eyebrow">Code DNA</span><h2>Explainable scoring</h2><p class="muted small">Each topic score uses four deterministic components:</p><div class="code-box">Topic Score =
  45% submission accuracy
+ 25% solved difficulty
+ 20% normalized solve speed
+ 10% recency</div><p class="muted small">The overall DNA combines Problem Solving, Accuracy, Speed, Consistency, Versatility and Challenge Handling. Archetypes are rule-based and viva-friendly—not fake machine learning.</p></div>
<div class="card card-pad"><span class="eyebrow">Ghost Race</span><h2>Historical event replay</h2><div class="code-box">Completed problem_session
        ↓
submissions ordered by elapsed_seconds
        ↓
Ghost timeline replay
        ↓
new challenger session
        ↓
compare AC time</div><p class="muted small">Only event timing and verdicts are replayed; opponent source code stays private. 4× playback is available to make classroom demonstrations fast.</p></div>
<div class="card card-pad"><span class="eyebrow">SQL Battle</span><h2>Deterministic query judging</h2><div class="code-box">User query
  → SELECT/WITH validator
  → arena_* table allowlist
  → execute safely
  → compare result set
  → EXPLAIN efficiency
  → score</div><p class="muted small">Correctness is worth 700 points, speed up to 150, and efficiency up to 150. Destructive SQL, multiple statements, file operations and delay functions are blocked.</p></div>
<div class="card card-pad"><span class="eyebrow">Prototype judge</span><h2>Safe by design</h2><p class="muted small">The current software-lab prototype does not compile or execute arbitrary user source code. Instead, a deterministic structural evaluator produces verdicts and performance metadata. A real isolated code-execution service can replace this service after framework migration without changing the database/session model.</p><div class="alert alert-info">This limitation is intentional and should be stated clearly during demonstrations.</div></div>
<div class="card card-pad span-2"><span class="eyebrow">Security cleanup</span><h2>Fixed from the old project</h2><div class="grid grid-3"><div><strong>Authentication</strong><p class="muted small">Password hashes, session regeneration, no u1 fallback, guards before output.</p></div><div><strong>Authorization</strong><p class="muted small">Admin CRUD and SQL Lab are server-side protected; edit/delete cannot be accessed by URL alone.</p></div><div><strong>Database</strong><p class="muted small">PDO everywhere, prepared statements, CSRF on writes, lowercase table names, no mixed MySQLi.</p></div><div><strong>Missing flows</strong><p class="muted small">Profile, contest view and duel logic are implemented; string contest IDs are preserved.</p></div><div><strong>SQL safety</strong><p class="muted small">Admin SQL Lab is read-only; SQL Battle uses a dedicated arena table allowlist.</p></div><div><strong>Configuration</strong><p class="muted small">config/.env support plus 3307/3306 probing for XAMPP portability.</p></div></div></div>
</section></main><?php include __DIR__.'/includes/footer.php'; ?>
