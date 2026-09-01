<script lang="ts">
  import { onMount } from 'svelte';
  import Loading from '$lib/components/Loading.svelte';
  import VerdictBadge from '$lib/components/VerdictBadge.svelte';
  import { api } from '$lib/api';

  type DashboardData = {
    user: {
      id: string;
      username: string;
      rating: number;
      rank: string;
    };
    stats: {
      rating: number;
      solved: number;
      submissions: number;
      contests: number;
    };
    weakest_topic: {
      topic: string;
      solved: number;
      total: number;
    } | null;
    recommended: Array<{
      id: string;
      title: string;
      topic: string;
      difficulty: string;
    }>;
    recent: Array<{
      id: string;
      title: string;
      verdict: string;
      language: string;
      submitted_at: string;
    }>;
    performance_profile: {
      overall: number;
      archetype: {
        name: string;
        tagline: string;
      };
      strengths: string[];
      growth_areas: string[];
    };
    gamification: {
      xp: number;
      level: string | number;
    };
  };

  let data: DashboardData | null = null;
  let error = '';

  onMount(async () => {
    try {
      data = await api.get<DashboardData>('/api/dashboard');
    } catch (e: any) {
      error = e.message;
    }
  });
</script>

<svelte:head>
  <title>Dashboard · CodeForge</title>
</svelte:head>

{#if !data && !error}
  <Loading />
{:else if error}
  <div class="alert error">{error}</div>
{:else if data}
  <div class="page-head">
    <div>
      <span class="eyebrow">COMMAND CENTER</span>
      <h1>Welcome back, {data.user.username}.</h1>
      <p>{data.performance_profile.archetype.tagline}</p>
    </div>

    <div class="page-actions">
      <a class="btn primary" href="/problems">Start practice</a>
      <a class="btn ghost" href="/performance-profile">View Performance Profile</a>
    </div>
  </div>

  <section class="metric-grid">
    <div class="metric">
      <small>RATING</small>
      <strong>{data.stats.rating}</strong>
      <span>{data.user.rank}</span>
    </div>

    <div class="metric">
      <small>SOLVED</small>
      <strong>{data.stats.solved}</strong>
      <span>unique problems</span>
    </div>

    <div class="metric">
      <small>SUBMISSIONS</small>
      <strong>{data.stats.submissions}</strong>
      <span>tracked attempts</span>
    </div>

    <div class="metric">
      <small>XP / LEVEL</small>
      <strong>{data.gamification.xp}</strong>
      <span>{data.gamification.level}</span>
    </div>
  </section>

  <section class="feature-links">
    <a href="/performance-profile">
      <i>⬡</i>
      <h3>Performance Profile</h3>
      <p>Performance dimensions, strengths and topic mastery.</p>
      <b>OPEN PROFILE →</b>
    </a>

    <a href="/ghost-race">
      <i>◉</i>
      <h3>Ghost Race</h3>
      <p>Race a real historical solve timeline.</p>
      <b>CHOOSE A GHOST →</b>
    </a>

    <a href="/sql-battle">
      <i>▦</i>
      <h3>SQL Battle</h3>
      <p>Correctness, speed and efficiency.</p>
      <b>ENTER ARENA →</b>
    </a>
  </section>

  <section class="two-col">
    <div class="panel">
      <div class="panel-head">
        <h2>Performance snapshot</h2>
        <a href="/performance-profile">Details →</a>
      </div>

      <div class="profile-summary">
        <strong>{data.performance_profile.overall}%</strong>

        <div>
          <b>{data.performance_profile.archetype.name}</b>
          <p>{data.performance_profile.strengths.join(' · ')}</p>
        </div>
      </div>

      <div class="progress">
        <span style={`width:${data.performance_profile.overall}%`}></span>
      </div>
    </div>

    <div class="panel">
      <div class="panel-head">
        <h2>Recent activity</h2>
        <span class="live-text">● LIVE DB</span>
      </div>

      {#if data.recent.length}
        {#each data.recent as row (row.id)}
          <div class="list-row">
            <div>
              <b>{row.title}</b>
              <small>{row.language} · {row.submitted_at}</small>
            </div>

            <VerdictBadge verdict={row.verdict} />
          </div>
        {/each}
      {:else}
        <p class="muted">No submissions yet.</p>
      {/if}
    </div>
  </section>

  {#if data.weakest_topic}
    <section class="panel">
      <div class="panel-head">
        <h2>Recommended next</h2>
        <span class="eyebrow">
          WEAKEST TOPIC: {data.weakest_topic.topic}
        </span>
      </div>

      <div class="card-grid">
        {#each data.recommended as problem (problem.id)}
          <a class="mini-card" href={`/problems/${problem.id}`}>
            <b>{problem.title}</b>
            <span>{problem.topic} · {problem.difficulty}</span>
          </a>
        {/each}
      </div>
    </section>
  {/if}
{/if}
