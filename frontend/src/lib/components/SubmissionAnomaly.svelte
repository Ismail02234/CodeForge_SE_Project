<script lang="ts">
  export let data: {
    summary: {
      normal: number;
      rapid_attempts: number;
      repeated_failures: number;
      similarity_flags: number;
      total_flags: number;
      risk_level: string;
    };
    message: string;
  } | null = null;

  function getRiskClass(level: string): string {
    return level.toLowerCase();
  }
</script>

<section class="anomaly-card">
  <div class="header">
    <div>
      <span class="eyebrow">LEARNING INTELLIGENCE</span>
      <h2>Submission Behavior</h2>
      <p>
        Analyzes unusual submission patterns to help understand coding
        behavior.
      </p>
    </div>

    {#if data}
      <div class={`risk ${getRiskClass(data.summary.risk_level)}`}>
        {data.summary.risk_level}
      </div>
    {/if}
  </div>

  {#if !data}
    <div class="empty">
      No submission behavior data available yet.
    </div>
  {:else}
    <div class="stats">
      <div class="stat">
        <span class="label">Normal</span>
        <strong>{data.summary.normal}</strong>
      </div>

      <div class="stat">
        <span class="label">Rapid Attempts</span>
        <strong>{data.summary.rapid_attempts}</strong>
      </div>

      <div class="stat">
        <span class="label">Repeated Failures</span>
        <strong>{data.summary.repeated_failures}</strong>
      </div>

      <div class="stat">
        <span class="label">Similarity Flags</span>
        <strong>{data.summary.similarity_flags}</strong>
      </div>
    </div>

    <div class="message">
      <span>Behavior analysis</span>
      <p>{data.message}</p>
    </div>
  {/if}
</section>

<style>
  .anomaly-card {
    width: 100%;
  }

  .header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 20px;
    margin-bottom: 24px;
  }

  .eyebrow {
    font-size: 11px;
    letter-spacing: 0.12em;
    font-weight: 700;
    opacity: 0.65;
  }

  h2 {
    margin: 6px 0 8px;
    font-size: 24px;
  }

  p {
    margin: 0;
    opacity: 0.7;
    line-height: 1.5;
  }

  .risk {
    padding: 8px 14px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 800;
    letter-spacing: 0.05em;
  }

  .risk.normal {
    background: rgba(34, 197, 94, 0.12);
    color: #16a34a;
  }

  .risk.low {
    background: rgba(234, 179, 8, 0.14);
    color: #ca8a04;
  }

  .risk.medium {
    background: rgba(249, 115, 22, 0.14);
    color: #ea580c;
  }

  .risk.high {
    background: rgba(239, 68, 68, 0.12);
    color: #dc2626;
  }

  .stats {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 14px;
  }

  .stat {
    padding: 18px;
    border: 1px solid rgba(127, 127, 127, 0.18);
    border-radius: 14px;
  }

  .label {
    display: block;
    font-size: 11px;
    opacity: 0.6;
    margin-bottom: 8px;
  }

  .stat strong {
    font-size: 25px;
  }

  .message {
    margin-top: 18px;
    padding: 16px 18px;
    border-radius: 12px;
    background: rgba(127, 127, 127, 0.08);
  }

  .message span {
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    opacity: 0.6;
  }

  .message p {
    margin-top: 6px;
  }

  .empty {
    padding: 28px;
    text-align: center;
    opacity: 0.65;
    border: 1px dashed rgba(127, 127, 127, 0.3);
    border-radius: 12px;
  }

  @media (max-width: 700px) {
    .header {
      flex-direction: column;
    }

    .stats {
      grid-template-columns: repeat(2, 1fr);
    }
  }
</style>