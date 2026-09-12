<script lang="ts">
  import { onMount } from 'svelte';
  import { page } from '$app/stores';
  import { request } from '$lib/api';
  import Loading from '$lib/components/Loading.svelte';
  import PerformanceRadar from '$lib/components/PerformanceRadar.svelte';
  import SkillGraph from '$lib/components/SkillGraph.svelte';
  import SubmissionAnomaly from '$lib/components/SubmissionAnomaly.svelte';

  type DnaTopic = {
    score: number;
    accuracy: number;
    speed: number;
    difficulty: number;
    recency: number;
    attempted: number;
    solved: number;
  };

  type DnaResponse = {
    user: {
      id: string;
      username: string;
      rating: number;
      rank: string;
      university?: string | null;
    };
    dimensions: Record<string, number>;
    dimension_labels: Record<string, string>;
    overall: number;
    archetype: {
      name: string;
      tagline: string;
    };
    topics: Record<string, DnaTopic>;
    strengths: string[];
    growth_areas: string[];
    stats: {
      total_submissions: number;
      accepted_submissions: number;
      solved_problems: number;
      solved_topics: number;
    };
  };

  let PROFILE: DnaResponse | null = null;
  let error = '';
  let loading = true;
  let controller: AbortController | null = null;

  let dimensionEntries: Array<[string, number]> = [];
  let topicEntries: Array<[string, DnaTopic]> = [];

  $: dimensionEntries = PROFILE ? Object.entries(PROFILE.dimensions) : [];
  $: topicEntries = PROFILE ? Object.entries(PROFILE.topics) : [];

  async function loadDna() {
    controller?.abort();
    controller = new AbortController();

    loading = true;
    error = '';

    const timeout = window.setTimeout(() => controller?.abort(), 12000);

    try {
      const user = $page.url.searchParams.get('user');
      const path = user
        ? `/api/performance-profile/${encodeURIComponent(user)}`
        : '/api/performance-profile';

      PROFILE = await request<DnaResponse>(path, {
        signal: controller.signal,
      });
    } catch (e: any) {
      if (e?.name === 'AbortError') {
        error =
          'Performance Profile took too long to respond. The request was stopped so the page stays usable.';
      } else {
        error = e?.message || 'Could not load Performance Profile.';
      }
    } finally {
      window.clearTimeout(timeout);
      loading = false;
    }
  }

  onMount(() => {
    void loadDna();

    return () => {
      controller?.abort();
    };
  });
</script>

<svelte:head>
  <title>Performance Profile · CodeForge</title>
</svelte:head>

{#if loading}
  <Loading />
{:else if error}
  <section class="panel">
    <div class="alert error">{error}</div>
    <button class="btn primary" on:click={loadDna}>Retry Performance Profile →</button>
  </section>
{:else if PROFILE}
  <div class="page-head">
    <div>
      <span class="eyebrow">PERFORMANCE INTELLIGENCE</span>
      <h1>Performance Profile</h1>
      <p>{PROFILE.user.username} · {PROFILE.user.rank} · Rating {PROFILE.user.rating}</p>
    </div>
    <div class="profile-score-big">
      <strong>{PROFILE.overall}</strong>
      <span>/100</span>
    </div>
  </div>

  <section class="profile-layout">
    <div class="panel radar-panel">
      <PerformanceRadar dimensions={PROFILE.dimensions} labels={PROFILE.dimension_labels} />
    </div>

    <div class="panel archetype">
      <span class="eyebrow">ARCHETYPE</span>
      <h2>{PROFILE.archetype.name}</h2>
      <p>{PROFILE.archetype.tagline}</p>

      <h3>Strengths</h3>
      <div class="tag-row">
        {#each PROFILE.strengths as strength}
          <span>{strength}</span>
        {/each}
      </div>

      <h3>Growth areas</h3>
      <div class="tag-row">
        {#each PROFILE.growth_areas as area}
          <span>{area}</span>
        {/each}
      </div>
    </div>
  </section>

  <section class="metric-grid profile-metrics">
    {#each dimensionEntries as [key, value]}
      <div class="metric">
        <small>{PROFILE.dimension_labels[key] ?? key}</small>
        <strong>{value}%</strong>
        <div class="progress">
          <span style={`width:${value}%`}></span>
        </div>
      </div>
    {/each}
  </section>

  <section class="panel">
    <div class="panel-head">
      <h2>Topic mastery</h2>
      <span>{PROFILE.stats.solved_topics} active topics</span>
    </div>

    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Topic</th>
            <th>Score</th>
            <th>Accuracy</th>
            <th>Speed</th>
            <th>Difficulty</th>
            <th>Solved</th>
          </tr>
        </thead>
        <tbody>
          {#each topicEntries as [topic, row]}
            <tr>
              <td><b>{topic}</b></td>
              <td class="accent">{row.score}</td>
              <td>{row.accuracy}%</td>
              <td>{row.speed}%</td>
              <td>{row.difficulty}%</td>
              <td>{row.solved}/{row.attempted}</td>
            </tr>
          {/each}
        </tbody>
      </table>
    </div>
  </section>

  <!-- Ankita module: topic-wise live skill visualization -->
  <SkillGraph topics={PROFILE.topics} />

  <!-- Ankita module: unusual submission behavior signals -->
  <SubmissionAnomaly userId={PROFILE.user.id} />
{/if}
