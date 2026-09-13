<script lang="ts">
  import { onMount } from 'svelte';
  import { api } from '$lib/api';
  import Loading from '$lib/components/Loading.svelte';

  type ModuleCard = {
    id: string;
    title: string;
    slug: string;
    topic: string;
    description: string;
    difficulty: string;
    estimated_minutes: number;
    xp_reward: number;
    is_started: boolean;
    is_completed: boolean;
    mastery_pct: number;
    mastery_score: number;
    total_possible_xp: number;
    progress: {
      learn_score: number;
      play_score: number;
      prove_score: number;
      learn_pct: number;
      play_pct: number;
      prove_pct: number;
      concept_mastery: number;
    };
    started_at: string | null;
    completed_at: string | null;
  };

  type LearnData = {
    modules: ModuleCard[];
    continue_learning: ModuleCard[];
    recommended: ModuleCard[];
    completed: ModuleCard[];
    overall_mastery_pct: number;
  };

  let data: LearnData | null = null;
  let error = '';
  let loading = true;

  onMount(async () => {
    try {
      data = await api.get<LearnData>('/api/learn');
    } catch (e: any) {
      error = e.message;
    } finally {
      loading = false;
    }
  });

  async function retry() {
    loading = true;
    error = '';
    try {
      data = await api.get<LearnData>('/api/learn');
    } catch (e: any) {
      error = e.message;
    } finally {
      loading = false;
    }
  }

  function masteryColor(pct: number): string {
    if (pct >= 80) return 'var(--green)';
    if (pct >= 40) return 'var(--orange)';
    return 'var(--red)';
  }

  function difficultyClass(d: string): string {
    return d.toLowerCase();
  }
</script>

<svelte:head><title>Learn · CodeForge</title></svelte:head>

{#if loading}
  <Loading />
{:else if error}
  <div class="alert error">
    <b>Failed to load learning modules.</b>
    <p style="margin:6px 0 0">{error}</p>
    <button class="btn ghost" style="margin-top:10px" on:click={retry}>Retry</button>
  </div>
{:else if data}
  <div class="page-head">
    <div>
      <span class="eyebrow">LEARNING PATHS</span>
      <h1>Learn</h1>
      <p>Structured modules that take you from first principles to proven mastery.</p>
      {#if data.overall_mastery_pct != null}
        <div class="learn-mastery-row" style="display:flex;align-items:center;gap:14px;margin-top:10px;flex-wrap:wrap">
          <div style="display:flex;align-items:center;gap:8px">
            <span style="font:700 9px 'JetBrains Mono';letter-spacing:0.08em;color:#ff5c3e">OVERALL MASTERY</span>
            <strong style="font:700 18px 'JetBrains Mono';color:#ff5c3e">{data.overall_mastery_pct}%</strong>
          </div>
        </div>
        <div class="learn-progress-bar" style="margin-top:8px">
          <span style="width:{data.overall_mastery_pct}%;background:linear-gradient(90deg, var(--red), var(--orange))"></span>
        </div>
      {/if}
    </div>
  </div>

  {#if data.continue_learning.length}
    <section class="learn-section">
      <div class="learn-section-head">
        <h2>Continue Learning</h2>
        <span class="learn-count">{data.continue_learning.length} in progress</span>
      </div>
      <div class="card-grid">
        {#each data.continue_learning as m}
          <a class="panel learn-card" href={`/learn/${m.slug}`} style="display:block;text-decoration:none;color:inherit">
            <div class="learn-card-head">
              <span class="eyebrow">LEARN</span>
              <span class="pill {difficultyClass(m.difficulty)}">{m.difficulty}</span>
            </div>
            <h3 style="margin:10px 0 6px">{m.title}</h3>
            <p class="learn-topic">{m.topic}</p>
            <div class="learn-module-meta">
              <span>⏱ {m.estimated_minutes} min</span>
              <span>+{m.xp_reward} XP</span>
              <span class="learn-status-pill started">IN PROGRESS</span>
            </div>
            <div class="learn-mastery-block">
              <div class="learn-mastery-label">
                <span style="font:700 8px 'JetBrains Mono';letter-spacing:0.08em;color:#8d929b">MASTERY</span>
                <strong style="font:700 14px 'JetBrains Mono';color:{masteryColor(m.mastery_pct)}">{m.mastery_pct}%</strong>
              </div>
              <div class="learn-progress-bar">
                <span style="width:{m.mastery_pct}%;background:linear-gradient(90deg, var(--red), var(--orange))"></span>
              </div>
            </div>
            <div class="learn-stage-row">
              <div class="learn-stage">
                <small>Learn</small>
                <strong>{m.progress.learn_pct}%</strong>
              </div>
              <div class="learn-stage">
                <small>Play</small>
                <strong>{m.progress.play_pct}%</strong>
              </div>
              <div class="learn-stage">
                <small>Prove</small>
                <strong>{m.progress.prove_pct}%</strong>
              </div>
            </div>
          </a>
        {/each}
      </div>
    </section>
  {/if}

  {#if data.recommended.length}
    <section class="learn-section">
      <div class="learn-section-head">
        <h2>Recommended</h2>
        <span class="learn-count">{data.recommended.length} module{data.recommended.length === 1 ? '' : 's'}</span>
      </div>
      <div class="card-grid">
        {#each data.recommended as m}
          <a class="panel learn-card" href={`/learn/${m.slug}`} style="display:block;text-decoration:none;color:inherit">
            <div class="learn-card-head">
              <span class="eyebrow">LEARN</span>
              <span class="pill {difficultyClass(m.difficulty)}">{m.difficulty}</span>
            </div>
            <h3 style="margin:10px 0 6px">{m.title}</h3>
            <p class="learn-topic">{m.topic}</p>
            <div class="learn-module-meta">
              <span>⏱ {m.estimated_minutes} min</span>
              <span>+{m.xp_reward} XP</span>
              <span class="learn-status-pill unstarted">NOT STARTED</span>
            </div>
            <div class="learn-mastery-block">
              <div class="learn-mastery-label">
                <span style="font:700 8px 'JetBrains Mono';letter-spacing:0.08em;color:#8d929b">MASTERY</span>
                <strong style="font:700 14px 'JetBrains Mono';color:var(--muted)">0%</strong>
              </div>
              <div class="learn-progress-bar">
                <span style="width:0%;background:#2a2e38"></span>
              </div>
            </div>
            <div class="learn-stage-row">
              <div class="learn-stage">
                <small>Learn</small>
                <strong>0%</strong>
              </div>
              <div class="learn-stage">
                <small>Play</small>
                <strong>0%</strong>
              </div>
              <div class="learn-stage">
                <small>Prove</small>
                <strong>0%</strong>
              </div>
            </div>
          </a>
        {/each}
      </div>
    </section>
  {/if}

  {#if data.completed.length}
    <section class="learn-section">
      <div class="learn-section-head">
        <h2>Completed</h2>
        <span class="learn-count">{data.completed.length} module{data.completed.length === 1 ? '' : 's'}</span>
      </div>
      <div class="card-grid">
        {#each data.completed as m}
          <a class="panel learn-card" href={`/learn/${m.slug}`} style="display:block;text-decoration:none;color:inherit">
            <div class="learn-card-head">
              <span class="eyebrow">LEARN</span>
              <span class="pill {difficultyClass(m.difficulty)}">{m.difficulty}</span>
            </div>
            <h3 style="margin:10px 0 6px">{m.title}</h3>
            <p class="learn-topic">{m.topic}</p>
            <div class="learn-module-meta">
              <span>⏱ {m.estimated_minutes} min</span>
              <span>+{m.xp_reward} XP</span>
              <span class="learn-status-pill completed">COMPLETED</span>
            </div>
            <div class="learn-mastery-block">
              <div class="learn-mastery-label">
                <span style="font:700 8px 'JetBrains Mono';letter-spacing:0.08em;color:#8d929b">MASTERY</span>
                <strong style="font:700 14px 'JetBrains Mono';color:var(--green)">{m.mastery_pct}%</strong>
              </div>
              <div class="learn-progress-bar">
                <span style="width:{m.mastery_pct}%;background:linear-gradient(90deg, var(--green), var(--cyan))"></span>
              </div>
            </div>
            <div class="learn-stage-row">
              <div class="learn-stage">
                <small>Learn</small>
                <strong>{m.progress.learn_pct}%</strong>
              </div>
              <div class="learn-stage">
                <small>Play</small>
                <strong>{m.progress.play_pct}%</strong>
              </div>
              <div class="learn-stage">
                <small>Prove</small>
                <strong>{m.progress.prove_pct}%</strong>
              </div>
            </div>
          </a>
        {/each}
      </div>
    </section>
  {/if}

  {#if !data.continue_learning.length && !data.recommended.length && !data.completed.length}
    <div class="empty">
      <div class="empty-mark">∅</div>
      <p>No active modules right now.</p>
    </div>
  {/if}
{/if}
