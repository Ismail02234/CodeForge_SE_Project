<script lang="ts">
  import { onMount } from 'svelte';
  import { auth, loadUser, logout } from '$lib/stores/auth';
  import { api } from '$lib/api';
  import ForgeCanvas from '$lib/components/ForgeCanvas.svelte';

  type PublicStats = {
    users: number;
    problems: number;
    submissions: number;
  };

  let stats: PublicStats = { users: 0, problems: 0, submissions: 0 };
  let core: HTMLDivElement | undefined;

  onMount(() => {
    void loadUser();

    void api
      .get<PublicStats>('/api/public/stats')
      .then((value) => {
        stats = value;
      })
      .catch(() => {
        // The landing page should still work if public stats are unavailable.
      });

    const move = (event: PointerEvent) => {
      if (!core) return;

      const x = (event.clientX / window.innerWidth - 0.5) * 2;
      const y = (event.clientY / window.innerHeight - 0.5) * 2;

      core.style.transform = `rotateX(${-y * 7}deg) rotateY(${x * 9}deg) translate3d(${x * 7}px, ${y * 7}px, 0)`;
    };

    window.addEventListener('pointermove', move, { passive: true });

    return () => {
      window.removeEventListener('pointermove', move);
    };
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
  <div class="landing-noise"></div>

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
        <a class="btn ghost" href="/dashboard">Dashboard</a>
        <button class="btn danger" on:click={signOut}>Log out</button>
      {:else}
        <a class="btn ghost" href="/login">Log in</a>
        <a class="btn primary" href="/register">Create account</a>
      {/if}
    </div>
  </header>

  <section class="landing-hero" id="system">
    <div class="hero-copy">
      <div class="eyebrow live"><i></i>COMPETITIVE PROGRAMMING // INTELLIGENCE SYSTEM</div>
      <h1>FORGE<br /><span>YOUR EDGE.</span></h1>
      <p>
        Train harder. Read your coding DNA. Race recorded solving sessions. Fight deterministic SQL
        battles. Turn every submission into an advantage.
      </p>

      <div class="hero-actions">
        {#if $auth.user}
          <a class="btn primary big" href="/dashboard">ENTER COMMAND CENTER →</a>
        {:else}
          <a class="btn primary big" href="/register">CREATE YOUR ACCOUNT →</a>
          <a class="btn ghost big" href="/login">LOG IN</a>
        {/if}
      </div>

      <div class="hero-sequence">
        <span>01 ANALYZE</span>
        <span>02 COMPETE</span>
        <span>03 EVOLVE</span>
      </div>
    </div>

    <div class="forge-visual">
      <div class="halo h1"></div>
      <div class="halo h2"></div>
      <div class="halo h3"></div>
      <div class="forge-core" bind:this={core}>
        <div class="core-ring"></div>
        <div class="core-center">
          <strong>&lt;/&gt;</strong>
          <small>FORGE CORE</small>
          <b>ONLINE</b>
        </div>
      </div>
      <div class="float-card fc1">
        <small>CODE DNA</small><strong>97%</strong><span>ELITE SIGNAL</span>
      </div>
      <div class="float-card fc2">
        <small>GHOST DELTA</small><strong>-00:42</strong><span>YOU ARE AHEAD</span>
      </div>
      <div class="float-card fc3">
        <small>SQL ARENA</small><strong>920</strong><span>BATTLE SCORE</span>
      </div>
    </div>
  </section>

  <section class="landing-stats">
    <div><small>REGISTERED CODERS</small><strong>{stats.users}</strong></div>
    <div><small>PROBLEMS ONLINE</small><strong>{stats.problems}</strong></div>
    <div><small>SUBMISSIONS TRACKED</small><strong>{stats.submissions}</strong></div>
    <div><small>SYSTEM STATUS</small><strong class="green">● LIVE</strong></div>
  </section>

  <section class="landing-section" id="features">
    <div class="section-title">
      <span>/01</span>
      <h2>BUILT TO<br /><b>HIT HARDER.</b></h2>
      <p>
        One competitive profile drives every system. Performance history becomes analytics,
        replayable races and measurable progression.
      </p>
    </div>

    <div class="feature-grid">
      <a class="feature-card red" href={$auth.user ? '/code-dna' : '/login'}>
        <small>01</small><i>⬡</i>
        <h3>CODE DNA</h3>
        <p>
          Accuracy, speed, consistency, versatility, challenge handling, topic mastery and an
          explainable archetype.
        </p>
        <b>READ YOUR PROFILE →</b>
      </a>
      <a class="feature-card orange" href={$auth.user ? '/ghost-race' : '/login'}>
        <small>02</small><i>◉</i>
        <h3>GHOST RACE</h3>
        <p>Race against real historical timelines without exposing another coder's source code.</p>
        <b>CHASE THE GHOST →</b>
      </a>
      <a class="feature-card cyan" href={$auth.user ? '/sql-battle' : '/login'}>
        <small>03</small><i>▦</i>
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
    <div class="terminal">
      <div class="terminal-head"><span>● ● ●</span><b>codeforge://system/boot</b><em>LIVE</em></div>
      <pre><span>01</span> $ initialize codeforge --mode=aggressive
<span>02</span> loading performance intelligence...
<span>03</span> mounting ghost-race timeline engine...
<span>04</span> isolating SQL battle sandbox...
<span>05</span> <b>READY.</b> choose your next move_</pre>
    </div>
    <div>
      <span class="eyebrow">/02</span>
      <h2>YOUR<br />COMMAND<br /><b>CENTER.</b></h2>
      <p>
        Profiles, practice, contests, rivalry, universities, Code DNA, Ghost Race and SQL Battle all
        operate on the same MySQL history.
      </p>
    </div>
  </section>

  <footer class="landing-footer">
    <a class="brand" href="/"><span>&lt;/&gt;</span><strong>CODE<b>FORGE</b></strong></a>
    <p>SvelteKit + Laravel 11 + MySQL</p>
    <span>CODEFORGE // 3.0</span>
  </footer>
</div>
