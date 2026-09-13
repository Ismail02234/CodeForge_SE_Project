<script lang="ts">
  import { type PlayChallenge } from './types';

  export let challenges: PlayChallenge[] = [];
  export let selectedId: string | null = null;
  export let completedIds: Set<string> = new Set();
  export let scores: Record<string, number> = {};

  function gameMeta(type: string) {
    switch (type) {
      case 'midpoint_master':
        return {
          concept: 'Midpoint formula',
          difficulty: 'Easy',
          est: 5,
          icon: '÷',
          color: '#2bdff5',
        };
      case 'trace_race':
        return {
          concept: 'Full binary search trace',
          difficulty: 'Medium',
          est: 8,
          icon: '⟳',
          color: '#ff7900',
        };
      case 'binary_search':
        return {
          concept: 'Left / Right decisions',
          difficulty: 'Medium',
          est: 6,
          icon: '⊕',
          color: '#ff321d',
        };
      case 'fill_blank':
        return {
          concept: 'Boundary updates',
          difficulty: 'Easy',
          est: 4,
          icon: '▭',
          color: '#59e8a2',
        };
      case 'coding':
        return {
          concept: 'Mid prediction & steps',
          difficulty: 'Medium',
          est: 6,
          icon: '⟨⟩',
          color: '#ffc268',
        };
      case 'trace':
        return {
          concept: 'Minimum moves',
          difficulty: 'Medium',
          est: 5,
          icon: '→',
          color: '#a78bfa',
        };
      default:
        return {
          concept: 'Practice',
          difficulty: 'Medium',
          est: 5,
          icon: '?',
          color: '#8d929b',
        };
    }
  }

  function select(id: string) {
    if (completedIds.has(id)) return;
    selectedId = id;
  }
</script>

<div class="game-selector" style="display:flex;flex-direction:column;gap:10px">
  {#each challenges as c}
    {@const meta = gameMeta(c.type)}
    {@const done = completedIds.has(c.id)}
    {@const score = scores[c.id] ?? 0}
    <button
      class="game-card {selectedId === c.id ? 'selected' : ''} {done ? 'completed' : ''}"
      on:click={() => select(c.id)}
      disabled={done}
      style="
        display:flex;
        align-items:center;
        gap:14px;
        padding:14px 16px;
        background:#090b0f;
        border:1px solid {done ? 'rgba(73,224,149,0.25)' : selectedId === c.id ? 'rgba(255,70,40,0.42)' : 'var(--line)'};
        border-radius:8px;
        cursor:{done ? 'default' : 'pointer'};
        text-align:left;
        width:100%;
        transition:0.15s ease;
        opacity:{done ? 0.75 : 1};
      "
    >
      <div style="
        width:42px;height:42px;border-radius:7px;
        background:{done ? 'rgba(73,224,149,0.1)' : meta.color + '15'};
        border:1px solid {done ? 'rgba(73,224,149,0.3)' : meta.color + '33'};
        display:flex;align-items:center;justify-content:center;
        font:700 16px 'JetBrains Mono';color:{done ? 'var(--green)' : meta.color};flex-shrink:0;
      ">
        {done ? '✓' : meta.icon}
      </div>

      <div style="flex:1;min-width:0">
        <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
          <b style="font-size:13px;font-weight:700;font-family:'JetBrains Mono'">{c.title}</b>
          <span class="pill {meta.difficulty.toLowerCase()}" style="font:700 7px 'JetBrains Mono';letter-spacing:0.08em">{meta.difficulty.toUpperCase()}</span>
          {#if done}
            <span class="pill active" style="font:700 7px 'JetBrains Mono';letter-spacing:0.08em">COMPLETED</span>
          {/if}
        </div>
        <p style="margin:5px 0 0;font-size:11px;color:#8d929b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{c.instructions}</p>
        <div style="display:flex;gap:10px;margin-top:7px;flex-wrap:wrap">
          <span style="font:600 8px 'JetBrains Mono';color:#666b74;letter-spacing:0.08em">CONCEPT: {meta.concept.toUpperCase()}</span>
          <span style="font:600 8px 'JetBrains Mono';color:#666b74;letter-spacing:0.08em">⏱ {meta.est} MIN</span>
          <span style="font:600 8px 'JetBrains Mono';color:#666b74;letter-spacing:0.08em">+{c.xp_reward} XP</span>
          {#if done && score > 0}
            <span style="font:600 8px 'JetBrains Mono';color:var(--green);letter-spacing:0.08em">{score} PTS</span>
          {/if}
        </div>
      </div>

      <div style="flex-shrink:0;font:700 10px 'JetBrains Mono';color:#555b64">
        {done ? '→' : '▶'}
      </div>
    </button>
  {/each}
</div>

<style>
  .game-card:hover:not(:disabled) {
    border-color: rgba(255, 70, 40, 0.45) !important;
    background: #0c0e13 !important;
  }
  .game-card.selected {
    box-shadow: 0 0 0 1px rgba(255, 70, 40, 0.25);
  }
</style>
