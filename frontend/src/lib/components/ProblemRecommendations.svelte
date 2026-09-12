<script lang="ts">
  import { onMount } from 'svelte';
  import { api } from '$lib/api';

  type TopicStat = {
    topic: string;
    attempted: number;
    solved: number;
    global_accuracy: number;
    shrunk_accuracy: number | null;
    friction_penalty: number;
    weakness_score: number | null;
    eligible: boolean;
    fallback?: boolean;
  };

  type RecommendationData = {
    weakest: TopicStat | null;
    topic_stats: TopicStat[];
    problems: Array<{
      id: string;
      title: string;
      topic: string;
      difficulty: string;
      tags?: string | null;
    }>;
    explanation: string;
  };

  let data: RecommendationData | null = null;
  let error = '';

  onMount(async () => {
    try {
      data = await api.get<RecommendationData>('/api/recommendations/problems?limit=3');
    } catch (e: any) {
      error = e.message;
    }
  });

  $: weaknessPercent =
    data?.weakest?.weakness_score == null ? null : Math.round(data.weakest.weakness_score * 100);
</script>

<section class="panel recommendation-panel">
  <div class="panel-head">
    <div>
      <span class="eyebrow">PERSONALIZED PRACTICE</span>
      <h2>Target your weakest field</h2>
    </div>

    {#if data?.weakest}
      <div class="weakness-chip">
        <small>WEAKEST</small>
        <b>{data.weakest.topic}</b>
        {#if weaknessPercent !== null}
          <span>{weaknessPercent}% weakness</span>
        {/if}
      </div>
    {/if}
  </div>

  {#if error}
    <div class="alert error">{error}</div>
  {:else if !data}
    <p class="muted">Analyzing your recent problem history...</p>
  {:else}
    <p class="advisor-copy">{data.explanation}</p>

    {#if data.weakest}
      <div class="advisor-metrics">
        <div>
          <small>ATTEMPTS</small>
          <strong>{data.weakest.attempted}</strong>
        </div>

        <div>
          <small>ACCEPTED ATTEMPTS</small>
          <strong>{data.weakest.solved}</strong>
        </div>

        <div>
          <small>SHRUNK ACCURACY</small>
          <strong>
            {data.weakest.shrunk_accuracy == null ? 'BUILDING' : `${data.weakest.shrunk_accuracy}%`}
          </strong>
        </div>
      </div>
    {/if}

    {#if data.problems.length}
      <div class="recommendation-grid">
        {#each data.problems as problem (problem.id)}
          <a class="recommendation-card" href={`/problems/${problem.id}`}>
            <div>
              <small>{problem.topic} · {problem.difficulty}</small>
              <h3>{problem.title}</h3>
              {#if problem.tags}
                <p>{problem.tags}</p>
              {/if}
            </div>

            <b>Practice →</b>
          </a>
        {/each}
      </div>
    {:else}
      <div class="recommendation-empty">
        No unsolved problem is currently available in this field.
      </div>
    {/if}
  {/if}
</section>

<style>
  .recommendation-panel {
    overflow: hidden;
  }

  .panel-head > div:first-child {
    min-width: 0;
  }

  .panel-head h2 {
    margin-top: 5px;
  }

  .weakness-chip {
    display: grid;
    min-width: 150px;
    gap: 2px;
    padding: 10px 12px;
    border: 1px solid rgba(255, 86, 56, 0.28);
    border-radius: 8px;
    background: rgba(255, 68, 42, 0.05);
    text-align: right;
  }

  .weakness-chip small {
    color: #777e89;
    font:
      600 10px 'JetBrains Mono',
      monospace;
    letter-spacing: 0.12em;
  }

  .weakness-chip b {
    color: #ff6546;
  }

  .weakness-chip span {
    color: #8f969f;
    font-size: 12px;
  }

  .advisor-copy {
    max-width: 780px;
    margin: 14px 0 18px;
    color: #aeb4bd;
    line-height: 1.65;
  }

  .advisor-metrics {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 10px;
    margin-bottom: 16px;
  }

  .advisor-metrics > div {
    display: grid;
    gap: 6px;
    padding: 12px;
    border: 1px solid rgba(255, 255, 255, 0.07);
    background: rgba(255, 255, 255, 0.018);
  }

  .advisor-metrics small {
    color: #707781;
    font:
      600 10px 'JetBrains Mono',
      monospace;
    letter-spacing: 0.08em;
  }

  .advisor-metrics strong {
    font:
      700 17px 'JetBrains Mono',
      monospace;
  }

  .recommendation-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 10px;
  }

  .recommendation-card {
    display: flex;
    min-height: 132px;
    flex-direction: column;
    justify-content: space-between;
    gap: 14px;
    padding: 15px;
    border: 1px solid rgba(255, 255, 255, 0.075);
    background: linear-gradient(145deg, rgba(255, 77, 45, 0.045), rgba(255, 255, 255, 0.012));
    color: inherit;
    text-decoration: none;
    transition:
      transform 160ms ease,
      border-color 160ms ease,
      background 160ms ease;
  }

  .recommendation-card:hover {
    transform: translateY(-2px);
    border-color: rgba(255, 84, 54, 0.35);
    background: linear-gradient(145deg, rgba(255, 77, 45, 0.08), rgba(255, 255, 255, 0.018));
  }

  .recommendation-card small {
    color: #8a919b;
    font:
      600 11px 'JetBrains Mono',
      monospace;
  }

  .recommendation-card h3 {
    margin: 7px 0 5px;
    font-size: 16px;
  }

  .recommendation-card p {
    margin: 0;
    color: #777e87;
    font-size: 12px;
  }

  .recommendation-card > b {
    color: #ff6546;
    font-size: 12px;
  }

  .recommendation-empty {
    padding: 14px;
    border: 1px dashed rgba(255, 255, 255, 0.1);
    color: #818893;
  }

  @media (max-width: 900px) {
    .recommendation-grid,
    .advisor-metrics {
      grid-template-columns: 1fr;
    }

    .weakness-chip {
      text-align: left;
    }
  }
</style>
