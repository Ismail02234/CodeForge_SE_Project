<svelte:head><title>How CodeForge Works</title></svelte:head>
<div class="page-head">
  <div>
    <span class="eyebrow">TECHNICAL DOCUMENTATION</span>
    <h1>How CodeForge 3.0 works</h1>
    <p>
      The framework migration keeps the existing data model and feature behavior while separating
      frontend and backend cleanly.
    </p>
  </div>
</div>
<section class="docs-grid">
  <article class="panel">
    <span class="eyebrow">ARCHITECTURE</span>
    <h2>SvelteKit → Laravel 11 → MySQL</h2>
    <div class="code-box">
      SvelteKit SPA ↓ Sanctum session + JSON API Laravel Controllers ↓ Services / Query Builder /
      Eloquent MySQL
    </div>
  </article>
  <article class="panel">
    <span class="eyebrow">Performance Profile</span>
    <h2>Explainable scoring</h2>
    <div class="code-box">
      Topic Score = 45% accuracy + 25% difficulty + 20% speed + 10% recency
    </div>
    <p>
      Overall PROFILE combines problem solving, accuracy, speed, consistency, versatility and
      challenge handling.
    </p>
  </article>
  <article class="panel">
    <span class="eyebrow">GHOST RACE</span>
    <h2>Historical event replay</h2>
    <div class="code-box">
      problem_session → submission timeline → virtual playback → challenger AC vs ghost AC time
    </div>
    <p>Only event metadata is replayed; source code remains private.</p>
  </article>
  <article class="panel">
    <span class="eyebrow">SQL BATTLE</span>
    <h2>Read-only arena</h2>
    <div class="code-box">
      SELECT/WITH validation → isolated arena_* tables → result comparison → speed + EXPLAIN
      efficiency → score
    </div>
  </article>
  <article class="panel">
    <span class="eyebrow">AUTH</span>
    <h2>Laravel Sanctum SPA session</h2>
    <p>
      CSRF cookie, session regeneration, credentialed CORS, route middleware and server-side admin
      authorization.
    </p>
  </article>
  <article class="panel">
    <span class="eyebrow">PROTOTYPE JUDGE</span>
    <h2>Safe by design</h2>
    <p>
      Programming submissions still use the deterministic structural judge from the working project.
      Arbitrary submitted code is not executed. It can later be swapped for a containerized judge
      without changing frontend contracts.
    </p>
  </article>
</section>
