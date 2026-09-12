<script lang="ts">
  import { onMount } from 'svelte';
  import { request } from '$lib/api';

  type AnomalyResponse = {
    summary: {
      normal: number;
      rapid_attempts: number;
      repeated_failures: number;
      similarity_flags: number;
      total_flags: number;
      risk_level: 'NORMAL' | 'LOW' | 'MEDIUM' | 'HIGH' | string;
    };
    message: string;
  };

  export let userId: string | null = null;

  let data: AnomalyResponse | null = null;
  let loading = true;
  let error = '';

  function riskClass(level: string): string {
    return level.toLowerCase();
  }

  async function loadAnomaly() {
    loading = true;
    error = '';

    try {
      const path = userId
        ? `/api/submission-anomaly/${encodeURIComponent(userId)}`
        : '/api/submission-anomaly';

      data = await request<AnomalyResponse>(path);
    } catch (e: any) {
      error = e?.message || 'Could not load submission behavior analysis.';
    } finally {
      loading = false;
    }
  }

  onMount(() => {
    void loadAnomaly();
  });
</script>

<section class="panel anomaly-panel">
  <div class="panel-head anomaly-head">
    <div>
      <span class="eyebrow">SUBMISSION ANOMALY DETECTION</span>
      <h2>Submission behavior</h2>
      <p>Flags unusual submission patterns for review. A flag is not proof of cheating.</p>
    </div>

    {#if data}
      <span class={`risk ${riskClass(data.summary.risk_level)}`}>
        {data.summary.risk_level}
      </span>
    {/if}
  </div>

  {#if loading}
    <div class="empty-state">Analyzing submission behavior…</div>
  {:else if error}
    <div class="alert error">{error}</div>
    <button class="btn ghost" on:click={loadAnomaly}>Retry analysis</button>
  {:else if data}
    <div class="anomaly-grid">
      <div class="anomaly-stat">
        <small>Normal</small>
        <strong>{data.summary.normal}</strong>
      </div>
      <div class="anomaly-stat">
        <small>Rapid attempts</small>
        <strong>{data.summary.rapid_attempts}</strong>
      </div>
      <div class="anomaly-stat">
        <small>Repeated failures</small>
        <strong>{data.summary.repeated_failures}</strong>
      </div>
      <div class="anomaly-stat">
        <small>Similarity flags</small>
        <strong>{data.summary.similarity_flags}</strong>
      </div>
    </div>

    <div class="analysis-note">
      <small>Behavior analysis</small>
      <p>{data.message}</p>
    </div>
  {/if}
</section>

<style>
  .anomaly-panel {
    margin-top: 20px;
  }

  .anomaly-head {
    align-items: flex-start;
    gap: 18px;
  }

  .anomaly-head h2 {
    margin: 5px 0 7px;
  }

  .anomaly-head p {
    margin: 0;
    max-width: 680px;
    color: var(--muted, #8b939e);
    line-height: 1.55;
  }

  .risk {
    padding: 8px 13px;
    border: 1px solid currentColor;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 900;
    letter-spacing: 0.08em;
  }

  .risk.normal {
    color: #22c55e;
    background: rgba(34, 197, 94, 0.08);
  }

  .risk.low {
    color: #eab308;
    background: rgba(234, 179, 8, 0.08);
  }

  .risk.medium {
    color: #f97316;
    background: rgba(249, 115, 22, 0.09);
  }

  .risk.high {
    color: #ef4444;
    background: rgba(239, 68, 68, 0.09);
  }

  .anomaly-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 12px;
  }

  .anomaly-stat {
    padding: 17px;
    border: 1px solid rgba(255, 255, 255, 0.09);
    border-radius: 12px;
    background: rgba(255, 255, 255, 0.015);
  }

  .anomaly-stat small {
    display: block;
    margin-bottom: 7px;
    color: var(--muted, #8b939e);
  }

  .anomaly-stat strong {
    font-size: 25px;
  }

  .analysis-note {
    margin-top: 16px;
    padding: 15px 17px;
    border-left: 2px solid rgba(255, 91, 59, 0.7);
    background: rgba(255, 91, 59, 0.045);
  }

  .analysis-note small {
    font-weight: 800;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: var(--muted, #8b939e);
  }

  .analysis-note p {
    margin: 5px 0 0;
    line-height: 1.55;
  }

  .empty-state {
    padding: 26px;
    border: 1px dashed rgba(255, 255, 255, 0.13);
    border-radius: 12px;
    color: var(--muted, #8b939e);
    text-align: center;
  }

  @media (max-width: 760px) {
    .anomaly-head {
      display: block;
    }

    .risk {
      display: inline-block;
      margin-top: 13px;
    }

    .anomaly-grid {
      grid-template-columns: repeat(2, minmax(0, 1fr));
    }
  }
</style>
