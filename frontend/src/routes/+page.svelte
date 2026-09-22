<script lang="ts">
  import { onMount } from 'svelte';
  import { auth, loadUser, logout } from '$lib/stores/auth';
  import { api } from '$lib/api';
  import ForgeCanvas from '$lib/components/ForgeCanvas.svelte';
  import ThemeToggle from '$lib/components/ThemeToggle.svelte';

  let stats = {
    users: 0,
    problems: 0,
    submissions: 0,
  };

  onMount(() => {
    void loadUser();

    void api
      .get<typeof stats>('/api/public/stats')
      .then((response) => {
        stats = response;
      })
      .catch(() => {
        // Landing statistics are non-critical.
      });
  });

  async function signOut() {
    await logout();
  }
</script>

<svelte:head>
  <title>CodeForge — Forge Your Edge</title>
  <meta
    name="description"
    content="Competitive programming intelligence, Ghost Race and SQL Battle Arena."
  />
</svelte:head>

<div class="landing">
  <ForgeCanvas />
  <div class="cursor-glow" id="cursorGlow" aria-hidden="true"></div>
  <div class="landing-noise" aria-hidden="true"></div>

  <header class="landing-nav">
    <a class="brand" href="/">
      <span>&lt;/&gt;</span>
      <strong>CODE<b>FORGE</b></strong>
    </a>

    <nav>
      <a href="#features">Features</a>
      <a href="#system">System</a>
      <a href="#arena">Arena</a>
    </nav>

    <div class="landing-auth">
      {#if $auth.user}
        <a class="btn ghost magnetic" href="/dashboard">Dashboard</a>
        <button class="btn danger magnetic" on:click={signOut}>Log out</button>
      {:else}
        <a class="btn ghost magnetic" href="/login">Log in</a>
        <a class="btn primary magnetic" href="/register">Create account</a>
      {/if}
      <ThemeToggle />
    </div>
  </header>

  <section class="landing-hero" id="system">
    <div class="hero-copy" data-parallax="0.018">
      <div class="eyebrow live">
        <i></i>
        COMPETITIVE PROGRAMMING // INTELLIGENCE SYSTEM
      </div>

      <h1>
        FORGE<br />
        <span>YOUR EDGE.</span>
      </h1>

      <p>
        Train harder. Review your performance profile. Race recorded solving sessions. Fight
        deterministic SQL battles. Turn every submission into an advantage.
      </p>

      <div class="hero-actions">
        {#if $auth.user}
          <a class="btn primary big magnetic" href="/dashboard"> ENTER COMMAND CENTER → </a>
        {:else}
          <a class="btn primary big magnetic" href="/register"> CREATE YOUR ACCOUNT → </a>
          <a class="btn ghost big magnetic" href="/login">LOG IN</a>
        {/if}
      </div>

      <div class="hero-sequence">
        <span>01 ANALYZE</span>
        <span>02 COMPETE</span>
        <span>03 EVOLVE</span>
      </div>
    </div>

    <div class="forge-visual" data-parallax="-0.024">
      <div class="halo h1"></div>
      <div class="halo h2"></div>
      <div class="halo h3"></div>

      <div class="forge-core" id="forgeCore">
        <div class="core-ring"></div>

        <div class="core-center">
          <strong>&lt;/&gt;</strong>
          <small>FORGE CORE</small>
          <b>ONLINE</b>
        </div>
      </div>

      <div class="float-card fc1">
        <small>Performance Profile</small>
        <strong>97%</strong>
        <span>ELITE SIGNAL</span>
      </div>

      <div class="float-card fc2">
        <small>GHOST DELTA</small>
        <strong>-00:42</strong>
        <span>YOU ARE AHEAD</span>
      </div>

      <div class="float-card fc3">
        <small>SQL ARENA</small>
        <strong>920</strong>
        <span>BATTLE SCORE</span>
      </div>
    </div>
  </section>

  <section class="landing-stats">
    <div>
      <small>REGISTERED CODERS</small>
      <strong>{stats.users}</strong>
    </div>

    <div>
      <small>PROBLEMS ONLINE</small>
      <strong>{stats.problems}</strong>
    </div>

    <div>
      <small>SUBMISSIONS TRACKED</small>
      <strong>{stats.submissions}</strong>
    </div>

    <div>
      <small>SYSTEM STATUS</small>
      <strong class="green">● LIVE</strong>
    </div>
  </section>

  <section class="landing-section" id="features">
    <div class="section-title">
      <span>/01</span>

      <h2>
        BUILT TO<br />
        <b>HIT HARDER.</b>
      </h2>

      <p>
        One competitive profile drives every system. Performance history becomes analytics,
        replayable races and measurable progression.
      </p>
    </div>

    <div class="feature-grid">
      <a class="feature-card red tilt-card" href={$auth.user ? '/performance-profile' : '/login'}>
        <small>01</small>
        <i>⬡</i>
        <h3>Performance Profile</h3>
        <p>
          Accuracy, speed, consistency, versatility, challenge handling, topic mastery and an
          explainable archetype.
        </p>
        <b>READ YOUR PROFILE →</b>
      </a>

      <a class="feature-card orange tilt-card" href={$auth.user ? '/ghost-race' : '/login'}>
        <small>02</small>
        <i>◉</i>
        <h3>GHOST RACE</h3>
        <p>Race against real historical timelines without exposing another coder's source code.</p>
        <b>CHASE THE GHOST →</b>
      </a>

      <a class="feature-card cyan tilt-card" href={$auth.user ? '/sql-battle' : '/login'}>
        <small>03</small>
        <i>▦</i>
        <h3>SQL BATTLE</h3>
        <p>
          SELECT-only battles scored by correctness, speed and query efficiency inside isolated
          arena tables.
        </p>
        <b>ENTER THE ARENA →</b>
      </a>
    </div>
  </section>

  <section class="landing-terminal" id="arena">
    <div class="terminal terminal-shell tilt-card">
      <div class="terminal-head">
        <span>● ● ●</span>
        <b>codeforge://system/boot</b>
        <em>LIVE</em>
      </div>

      <pre><span>01</span> $ initialize codeforge --mode=aggressive
<span>02</span> <code data-terminal-line>loading performance intelligence...</code>
<span>03</span> <code data-terminal-line>mounting ghost-race timeline engine...</code>
<span>04</span> <code data-terminal-line>isolating SQL battle sandbox...</code>
<span>05</span> <b>READY.</b> choose your next move_</pre>
    </div>

    <div>
      <span class="eyebrow">/02</span>

      <h2>
        YOUR<br />
        COMMAND<br />
        <b>CENTER.</b>
      </h2>

      <p>
        Profiles, practice, contests, rivalry, universities, Performance Profile, Ghost Race and SQL
        Battle all operate on the same MySQL history.
      </p>
    </div>
  </section>

  <footer class="landing-footer">
    <a class="brand" href="/">
      <span>&lt;/&gt;</span>
      <strong>CODE<b>FORGE</b></strong>
    </a>

    <p>SvelteKit + Laravel 11 + MySQL</p>
    <span>CODEFORGE // 3.0</span>
  </footer>
</div>
