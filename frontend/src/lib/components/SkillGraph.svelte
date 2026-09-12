<script lang="ts">
  type SkillTopic = {
    score: number;
    accuracy: number;
    speed: number;
    difficulty: number;
    recency: number;
    attempted: number;
    solved: number;
  };

  export let topics: Record<string, SkillTopic> = {};

  function level(score: number): 'Strong' | 'Developing' | 'Weak' {
    if (score >= 75) return 'Strong';
    if (score >= 50) return 'Developing';
    return 'Weak';
  }

  function levelClass(score: number): string {
    return level(score).toLowerCase();
  }

  function barHeight(score: number): string {
    return `${Math.max(0, Math.min(100, score))}%`;
  }

  function displayTopic(topic: string): string {
    return topic.replace(/_/g, ' ');
  }

  $: topicEntries = Object.entries(topics);
</script>

<section class="panel skill-graph-panel">
  <div class="panel-head skill-head">
    <div>
      <span class="eyebrow">LIVE SKILL GRAPH</span>
      <h2>Topic skill development</h2>
      <p>Topic scores combine accuracy, problem difficulty, solve speed and recent performance.</p>
    </div>

    <div class="legend" aria-label="Skill levels">
      <span><i class="dot strong"></i>Strong</span>
      <span><i class="dot developing"></i>Developing</span>
      <span><i class="dot weak"></i>Weak</span>
    </div>
  </div>

  {#if topicEntries.length === 0}
    <div class="empty-state">No topic performance data is available yet.</div>
  {:else}
    <div class="chart-shell">
      <div class="chart-y-axis" aria-hidden="true">
        <span style="top: 0%">100</span>
        <span style="top: 25%">75</span>
        <span style="top: 50%">50</span>
        <span style="top: 75%">25</span>
        <span class="axis-zero">0</span>
      </div>

      <div class="chart-viewport">
        <div
          class="rect-chart"
          style={`--topic-count:${Math.max(topicEntries.length, 1)}`}
          aria-label="Topic skill score bar graph"
        >
          <div class="grid-line line-100"></div>
          <div class="grid-line line-75"></div>
          <div class="grid-line line-50"></div>
          <div class="grid-line line-25"></div>
          <div class="grid-line line-0"></div>

          <div class="threshold threshold-strong">
            <span>Strong</span>
          </div>

          <div class="threshold threshold-developing">
            <span>Developing</span>
          </div>

          <div class="threshold threshold-weak">
            <span>Weak</span>
          </div>

          <div class="bars">
            {#each topicEntries as [topic, data]}
              <div
                class="bar-column"
                title={`${displayTopic(topic)} — ${data.score}% · Accuracy ${data.accuracy}% · Difficulty ${data.difficulty}% · Speed ${data.speed}% · Recency ${data.recency}%`}
              >
                <div class="score-label">{data.score}</div>

                <div class="bar-space">
                  <div
                    class={`skill-bar ${levelClass(data.score)}`}
                    style={`height:${barHeight(data.score)}`}
                    aria-label={`${displayTopic(topic)} skill score ${data.score} percent`}
                  >
                    <span class="bar-glow"></span>
                  </div>
                </div>

                <div class="topic-label">
                  <strong>{displayTopic(topic)}</strong>
                  <span class={`level ${levelClass(data.score)}`}>{level(data.score)}</span>
                  <small>{data.solved}/{data.attempted} solved</small>
                </div>
              </div>
            {/each}
          </div>
        </div>
      </div>
    </div>

    <div class="formula-note">
      <span>Score model</span>
      <strong>45% Accuracy</strong>
      <i>+</i>
      <strong>25% Difficulty</strong>
      <i>+</i>
      <strong>20% Speed</strong>
      <i>+</i>
      <strong>10% Recency</strong>
    </div>
  {/if}
</section>

<style>
  .skill-graph-panel {
    margin-top: 20px;
  }

  .skill-head {
    align-items: flex-start;
    gap: 18px;
  }

  .skill-head h2 {
    margin: 5px 0 7px;
  }

  .skill-head p {
    margin: 0;
    max-width: 680px;
    color: var(--muted, #8b939e);
    line-height: 1.55;
  }

  .legend {
    display: flex;
    flex-wrap: wrap;
    justify-content: flex-end;
    gap: 10px 14px;
    font-size: 11px;
    color: var(--muted, #8b939e);
  }

  .legend span {
    display: inline-flex;
    align-items: center;
    gap: 6px;
  }

  .dot {
    width: 7px;
    height: 7px;
    border-radius: 50%;
  }

  .dot.strong,
  .skill-bar.strong {
    background: #22c55e;
  }

  .dot.developing,
  .skill-bar.developing {
    background: #eab308;
  }

  .dot.weak,
  .skill-bar.weak {
    background: #ef4444;
  }

  .chart-shell {
    display: grid;
    grid-template-columns: 42px minmax(0, 1fr);
    min-height: 390px;
    margin-top: 22px;
    border: 1px solid rgba(255, 255, 255, 0.11);
    border-radius: 13px;
    background:
      linear-gradient(180deg, rgba(255, 255, 255, 0.018), transparent 35%), rgba(4, 6, 9, 0.42);
    overflow: hidden;
  }

  .chart-y-axis {
    position: relative;
    height: 310px;
    margin-top: 22px;
    border-right: 1px solid rgba(255, 255, 255, 0.08);
    color: var(--muted, #8b939e);
    font-family: 'JetBrains Mono', monospace;
    font-size: 10px;
  }

  .chart-y-axis span {
    position: absolute;
    right: 10px;
    transform: translateY(-50%);
  }

  .chart-y-axis .axis-zero {
    top: auto;
    bottom: -5px;
    transform: none;
  }

  .chart-viewport {
    min-width: 0;
    overflow-x: auto;
    overflow-y: hidden;
  }

  .rect-chart {
    position: relative;
    min-width: max(720px, calc(var(--topic-count) * 108px));
    height: 390px;
    padding: 22px 18px 0;
  }

  .grid-line {
    position: absolute;
    left: 18px;
    right: 18px;
    height: 1px;
    background: rgba(255, 255, 255, 0.07);
    pointer-events: none;
  }

  .line-100 {
    top: 22px;
  }

  .line-75 {
    top: 99.5px;
  }

  .line-50 {
    top: 177px;
  }

  .line-25 {
    top: 254.5px;
  }

  .line-0 {
    top: 332px;
    background: rgba(255, 255, 255, 0.13);
  }

  .threshold {
    position: absolute;
    right: 20px;
    z-index: 1;
    font-family: 'JetBrains Mono', monospace;
    font-size: 9px;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    pointer-events: none;
  }

  .threshold span {
    display: block;
    padding: 2px 5px;
    border-radius: 4px;
    background: rgba(5, 7, 10, 0.82);
  }

  .threshold-strong {
    top: 29px;
    color: rgba(34, 197, 94, 0.66);
  }

  .threshold-developing {
    top: 106px;
    color: rgba(234, 179, 8, 0.66);
  }

  .threshold-weak {
    top: 184px;
    color: rgba(239, 68, 68, 0.66);
  }

  .bars {
    position: relative;
    z-index: 2;
    display: grid;
    grid-template-columns: repeat(var(--topic-count), minmax(86px, 1fr));
    gap: 18px;
    height: 100%;
  }

  .bar-column {
    display: grid;
    grid-template-rows: 22px 288px 58px;
    min-width: 86px;
    text-align: center;
  }

  .score-label {
    align-self: start;
    color: #f4f5f6;
    font-family: 'JetBrains Mono', monospace;
    font-size: 11px;
    font-weight: 800;
  }

  .bar-space {
    position: relative;
    display: flex;
    align-items: flex-end;
    justify-content: center;
  }

  .skill-bar {
    position: relative;
    width: min(54px, 66%);
    min-height: 2px;
    border-radius: 5px 5px 1px 1px;
    box-shadow: 0 0 20px color-mix(in srgb, currentColor 15%, transparent);
    opacity: 0.9;
    transition:
      height 450ms cubic-bezier(0.22, 1, 0.36, 1),
      opacity 180ms ease,
      transform 180ms ease;
  }

  .bar-column:hover .skill-bar {
    opacity: 1;
    transform: scaleX(1.06);
  }

  .skill-bar.strong {
    color: #22c55e;
  }

  .skill-bar.developing {
    color: #eab308;
  }

  .skill-bar.weak {
    color: #ef4444;
  }

  .bar-glow {
    position: absolute;
    inset: 0;
    border-radius: inherit;
    box-shadow: 0 0 18px color-mix(in srgb, currentColor 25%, transparent);
  }

  .topic-label {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 3px;
    padding-top: 8px;
  }

  .topic-label strong {
    max-width: 96px;
    overflow: hidden;
    color: #dfe3e8;
    font-size: 11px;
    line-height: 1.25;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  .topic-label small {
    color: var(--muted, #8b939e);
    font-size: 9px;
  }

  .level {
    padding: 2px 6px;
    border-radius: 999px;
    font-size: 8px;
    font-weight: 900;
    letter-spacing: 0.04em;
    text-transform: uppercase;
  }

  .level.strong {
    color: #22c55e;
    background: rgba(34, 197, 94, 0.09);
  }

  .level.developing {
    color: #eab308;
    background: rgba(234, 179, 8, 0.09);
  }

  .level.weak {
    color: #ef4444;
    background: rgba(239, 68, 68, 0.09);
  }

  .formula-note {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 7px 10px;
    margin-top: 13px;
    padding: 11px 13px;
    border: 1px solid rgba(255, 255, 255, 0.07);
    border-radius: 8px;
    color: var(--muted, #8b939e);
    font-size: 10px;
  }

  .formula-note span {
    margin-right: 4px;
    font-weight: 800;
    letter-spacing: 0.08em;
    text-transform: uppercase;
  }

  .formula-note strong {
    color: #c9ced5;
    font-weight: 700;
  }

  .formula-note i {
    opacity: 0.45;
    font-style: normal;
  }

  .empty-state {
    padding: 28px;
    border: 1px dashed rgba(255, 255, 255, 0.13);
    border-radius: 12px;
    color: var(--muted, #8b939e);
    text-align: center;
  }

  @media (max-width: 760px) {
    .skill-head {
      display: block;
    }

    .legend {
      justify-content: flex-start;
      margin-top: 14px;
    }

    .chart-shell {
      grid-template-columns: 36px minmax(0, 1fr);
    }

    .rect-chart {
      min-width: max(620px, calc(var(--topic-count) * 96px));
    }

    .chart-y-axis span {
      right: 7px;
    }
  }
</style>
