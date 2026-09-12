<script lang="ts">
  export let topics: Record<
    string,
    {
      score: number;
      accuracy: number;
      speed: number;
      difficulty: number;
      recency: number;
      attempted: number;
      solved: number;
    }
  > = {};

  function getLevel(score: number): string {
    if (score >= 75) return 'Strong';
    if (score >= 50) return 'Developing';
    return 'Weak';
  }

  function getLevelClass(score: number): string {
    if (score >= 75) return 'strong';
    if (score >= 50) return 'developing';
    return 'weak';
  }

  function getBarWidth(score: number): string {
    return `${Math.max(0, Math.min(100, score))}%`;
  }

  $: topicEntries = Object.entries(topics);
</script>

<section class="skill-card">
  <div class="skill-header">
    <div>
      <span class="eyebrow">LEARNING INTELLIGENCE</span>
      <h2>Live Skill Map</h2>
      <p>
        Your topic strength is calculated from accuracy, difficulty,
        speed and recent performance.
      </p>
    </div>

    <div class="legend">
      <span class="legend-item">
        <i class="dot strong"></i>
        Strong
      </span>

      <span class="legend-item">
        <i class="dot developing"></i>
        Developing
      </span>

      <span class="legend-item">
        <i class="dot weak"></i>
        Weak
      </span>
    </div>
  </div>

  {#if topicEntries.length === 0}
    <div class="empty">
      No topic performance data available yet.
    </div>
  {:else}
    <div class="skill-list">
      {#each topicEntries as [topic, data]}
        <div class="skill-row">
          <div class="skill-info">
            <div class="topic-name">
              <strong>{topic}</strong>

              <span class={`level ${getLevelClass(data.score)}`}>
                {getLevel(data.score)}
              </span>
            </div>

            <span class="topic-meta">
              {data.solved}/{data.attempted} solved
            </span>
          </div>

          <div class="bar-area">
            <div class="bar-track">
              <div
                class={`bar-fill ${getLevelClass(data.score)}`}
                style={`width:${getBarWidth(data.score)}`}
              ></div>
            </div>
          </div>

          <div class="score">
            {data.score}%
          </div>
        </div>
      {/each}
    </div>
  {/if}
</section>

<style>
  .skill-card {
    width: 100%;
  }

  .skill-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 24px;
    margin-bottom: 28px;
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
    max-width: 650px;
    opacity: 0.7;
    line-height: 1.5;
  }

  .legend {
    display: flex;
    gap: 14px;
    flex-wrap: wrap;
    justify-content: flex-end;
  }

  .legend-item {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 12px;
    opacity: 0.8;
  }

  .dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    display: inline-block;
  }

  .dot.strong {
    background: #22c55e;
  }

  .dot.developing {
    background: #eab308;
  }

  .dot.weak {
    background: #ef4444;
  }

  .skill-list {
    display: flex;
    flex-direction: column;
    gap: 18px;
  }

  .skill-row {
    display: grid;
    grid-template-columns: 180px 1fr 60px;
    align-items: center;
    gap: 18px;
  }

  .skill-info {
    min-width: 0;
  }

  .topic-name {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
  }

  .topic-meta {
    display: block;
    margin-top: 4px;
    font-size: 11px;
    opacity: 0.55;
  }

  .level {
    font-size: 10px;
    padding: 3px 7px;
    border-radius: 999px;
    font-weight: 700;
  }

  .level.strong {
    background: rgba(34, 197, 94, 0.12);
    color: #16a34a;
  }

  .level.developing {
    background: rgba(234, 179, 8, 0.14);
    color: #ca8a04;
  }

  .level.weak {
    background: rgba(239, 68, 68, 0.12);
    color: #dc2626;
  }

  .bar-track {
    width: 100%;
    height: 10px;
    border-radius: 999px;
    background: rgba(127, 127, 127, 0.16);
    overflow: hidden;
  }

  .bar-fill {
    height: 100%;
    border-radius: inherit;
    transition: width 0.5s ease;
  }

  .bar-fill.strong {
    background: #22c55e;
  }

  .bar-fill.developing {
    background: #eab308;
  }

  .bar-fill.weak {
    background: #ef4444;
  }

  .score {
    text-align: right;
    font-size: 16px;
    font-weight: 800;
  }

  .empty {
    padding: 28px;
    text-align: center;
    opacity: 0.65;
    border: 1px dashed rgba(127, 127, 127, 0.3);
    border-radius: 12px;
  }

  @media (max-width: 700px) {
    .skill-header {
      flex-direction: column;
    }

    .legend {
      justify-content: flex-start;
    }

    .skill-row {
      grid-template-columns: 1fr 55px;
      gap: 10px;
    }

    .bar-area {
      grid-column: 1 / -1;
      grid-row: 2;
    }

    .score {
      grid-column: 2;
      grid-row: 1;
    }
  }
</style>