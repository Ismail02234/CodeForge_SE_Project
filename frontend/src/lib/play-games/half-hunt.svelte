<script lang="ts">
  import { onMount } from 'svelte';
  import { api } from '$lib/api';
  import type { PlayChallenge } from './types';

  export let challenge: PlayChallenge;
  export let slug: string = '';
  export let onComplete: (result: import('./types').GameResult) => void;

  const config = challenge.config || {};
  const rounds: Array<{ array: number[]; target: number; initial_low: number; initial_high: number; min_moves: number }> = config.rounds || [];
  const maxRounds = rounds.length;

  let roundIndex = 0;
  let low = 0;
  let high = 0;
  let mid = 0;
  let midVal: number | null = null;
  let arr: number[] = [];
  let target = 0;
  let found = false;
  let movesUsed = 0;
  let mistakes = 0;
  let score = 0;
  let phase: 'playing' | 'feedback' | 'complete' = 'playing';
  let moveHistory: string[] = [];
  let playSubmitting = false;
  let feedbackTimer: ReturnType<typeof setTimeout> | null = null;
  let lastMoveDir: string | null = null;
  let lastMoveCorrect = false;
  let lastMoveComparison = '';
  let lastMoveCorrectDir = '';
  let lastMoveDelta = 0;
  let lastMoveFound = false;
  let roundFeedback: { score: number; moves: number; mistakes: number; found: boolean; accuracy: number; xp: number } | null = null;
  let roundScores: number[] = [];
  let gameComplete = false;
  let totalGameScore = 0;
  let gameSubmitted = false;
  let apiError = '';

  function currentRound() {
    return rounds[roundIndex];
  }

  function initRound() {
    const r = currentRound();
    if (!r) return;
    if (feedbackTimer) clearTimeout(feedbackTimer);
    arr = [...r.array];
    target = r.target;
    low = r.initial_low;
    high = r.initial_high;
    mid = Math.floor((low + high) / 2);
    midVal = arr[mid] ?? null;
    found = false;
    movesUsed = 0;
    mistakes = 0;
    score = 0;
    phase = 'playing';
    moveHistory = [];
    lastMoveDir = null;
    lastMoveCorrect = false;
    lastMoveComparison = '';
    roundFeedback = null;
    apiError = '';
  }

  export function initGame() {
    if (feedbackTimer) clearTimeout(feedbackTimer);
    roundIndex = 0;
    roundScores = [];
    totalGameScore = 0;
    gameComplete = false;
    gameSubmitted = false;
    roundFeedback = null;
    apiError = '';
    initRound();
  }

  export function retryGame() {
    initGame();
  }

  onMount(() => {
    initGame();
  });

  function buildAction(): string {
    if (challenge.type === 'binary_search') {
      return JSON.stringify(moveHistory);
    }
    return JSON.stringify({
      round_index: roundIndex,
      directions: moveHistory,
    });
  }

  async function pickDirection(dir: 'left' | 'right') {
    if (phase !== 'playing' || found || playSubmitting) return;
    if (feedbackTimer) clearTimeout(feedbackTimer);

    moveHistory = [...moveHistory, dir];
    playSubmitting = true;
    apiError = '';

    try {
      const action = buildAction();
      const res = await api.post<{
        score_delta: number;
        score: number;
        max_score: number;
        completed: boolean;
        accuracy: number;
        xp_reward: number;
        round_index: number;
        rounds_total: number;
        current_low: number;
        current_high: number;
        current_mid: number;
        mid_val: number;
        found: boolean;
        moves: number;
        mistakes: number;
        correct_dir: string;
      }>(`/api/learn/${slug}/play`, {
        challenge_id: challenge.id,
        action,
      });

      score = res.score;
      found = res.found;
      low = res.current_low;
      high = res.current_high;
      mid = res.current_mid;
      midVal = res.mid_val;
      movesUsed = res.moves;
      mistakes = res.mistakes;

      lastMoveDir = dir;
      lastMoveCorrect = res.found || dir === res.correct_dir;
      lastMoveCorrectDir = res.correct_dir;
      lastMoveDelta = res.score_delta;
      lastMoveFound = res.found;
      lastMoveComparison = `arr[${mid}] = ${midVal}  vs  target = ${target}`;
      phase = 'feedback';

      if (res.found) {
        roundScores = [...roundScores, Math.max(0, score)];
        totalGameScore += Math.max(0, score);
        roundFeedback = {
          score: Math.max(0, score),
          moves: res.moves,
          mistakes: res.mistakes,
          found: true,
          accuracy: res.accuracy,
          xp: res.xp_reward,
        };
      }

      feedbackTimer = setTimeout(() => {
        if (res.found) {
          phase = 'complete';
        } else {
          phase = 'playing';
        }
        lastMoveDir = null;
        lastMoveComparison = '';
        feedbackTimer = null;
        playSubmitting = false;
      }, 700);
    } catch (e: any) {
      moveHistory = moveHistory.slice(0, -1);
      playSubmitting = false;
      phase = 'playing';
      apiError = e?.message || 'Move failed. Please try again.';
      console.error('HalfHunt move error:', e);
    }
  }

  async function finishGame() {
    if (gameSubmitted || !gameComplete) return;
    gameSubmitted = true;
    playSubmitting = true;
    apiError = '';

    try {
      const action = JSON.stringify({
        game_complete: true,
        round_scores: roundScores,
        total_score: totalGameScore,
        total_moves: movesUsed,
        total_mistakes: mistakes,
      });
      const res = await api.post<{
        score: number;
        max_score: number;
        completed: boolean;
        accuracy: number;
        xp_reward: number;
        attempts: number;
        moves?: number;
        mistakes?: number;
        play_completed_challenges?: Record<string, any>;
      }>(`/api/learn/${slug}/play`, {
        challenge_id: challenge.id,
        action,
      });

      onComplete({
        score: res.score,
        max_score: res.max_score,
        completed: res.completed,
        accuracy: res.accuracy,
        xp_reward: res.xp_reward,
        attempts: res.attempts,
        moves: res.moves,
        mistakes: res.mistakes,
        correct_rounds: roundScores.length,
        total_rounds: maxRounds,
        play_completed_challenges: res.play_completed_challenges,
      });
    } catch (e: any) {
      gameSubmitted = false;
      playSubmitting = false;
      apiError = e?.message || 'Failed to finish game. Please try again.';
      console.error('HalfHunt finish error:', e);
    }
  }

  function advanceRound() {
    roundIndex++;
    if (roundIndex >= maxRounds) {
      gameComplete = true;
    } else {
      initRound();
      roundFeedback = null;
    }
  }

  function inBounds(i: number): boolean {
    return i >= low && i <= high;
  }

  function eliminated(i: number): boolean {
    return i < low || i > high;
  }

  function isLow(i: number): boolean {
    return i === low;
  }

  function isHigh(i: number): boolean {
    return i === high;
  }

  function isMid(i: number): boolean {
    return i === mid && phase === 'playing';
  }

  $: r = currentRound();

  $: if (r && !found && !gameComplete) {
    if (phase === 'playing' && midVal !== null) {
      // live state
    }
  }

  $: allRoundsComplete = gameComplete;
  $: gamePhase = gameComplete ? 'complete' : (found ? 'round-complete' : 'playing');
</script>

{#if !r || arr.length === 0}
  <div class="empty">
    <p>Loading challenge...</p>
  </div>
{:else if gameComplete}
  <div class="hh-complete">
    <div
      class="hh-complete-badge"
      style="
        background:linear-gradient(135deg,#ff321d,#ff7900);
        padding:16px 32px;border-radius:12px;text-align:center;
        box-shadow:0 12px 40px rgba(255,49,26,0.25)
      "
    >
      <div style="font:700 22px 'JetBrains Mono';color:#fff;letter-spacing:0.04em">
        ROUNDS COMPLETE
      </div>
      <div style="font:600 11px 'JetBrains Mono';color:rgba(255,255,255,0.7);margin-top:6px">
        ALL {maxRounds} ROUNDS
      </div>
    </div>

    <div class="hh-summary-grid">
      <div class="hh-summary-item">
        <small>TOTAL SCORE</small>
        <strong class="mono" style="color:#ff5c3e;font-size:20px">{totalGameScore}</strong>
      </div>
      <div class="hh-summary-item">
        <small>ROUNDS CLEARED</small>
        <strong class="mono" style="color:var(--green);font-size:20px">{roundScores.length}/{maxRounds}</strong>
      </div>
      <div class="hh-summary-item">
        <small>TOTAL MOVES</small>
        <strong class="mono" style="font-size:20px">{movesUsed}</strong>
      </div>
      <div class="hh-summary-item">
        <small>TOTAL MISTAKES</small>
        <strong class="mono" style="color:var(--red);font-size:20px">{mistakes}</strong>
      </div>
    </div>

    <div class="hh-round-results">
      {#each roundScores as rs, i}
        <div class="hh-round-result-item">
          <span class="mono">R{i + 1}</span>
          <span class="mono" style="color:var(--green)">+{rs}</span>
        </div>
      {/each}
    </div>

    {#if apiError}
      <div class="alert error" style="margin-top:14px">
        <b>Error</b>
        <p style="margin:4px 0 0">{apiError}</p>
      </div>
    {/if}

    <div class="page-actions" style="margin-top:18px">
      <button class="btn ghost" on:click={retryGame}>PLAY AGAIN</button>
      <button class="btn primary" on:click={finishGame} disabled={playSubmitting || gameSubmitted}>
        {gameSubmitted ? 'Submitting...' : 'FINISH →'}
      </button>
    </div>
  </div>
{:else if found || gamePhase === 'round-complete'}
  <div class="hh-found-panel">
    <div
      style="
        background:linear-gradient(135deg,#ff321d,#ff7900);
        padding:14px 28px;border-radius:10px;text-align:center;
        font:700 15px 'JetBrains Mono';color:#fff;
        box-shadow:0 12px 40px rgba(255,49,26,0.25)
      "
    >
      TARGET FOUND: {target}
    </div>

    <div class="hh-summary-grid" style="margin-top:16px">
      <div class="hh-summary-item">
        <small>MOVES</small>
        <strong class="mono">{movesUsed}</strong>
      </div>
      <div class="hh-summary-item">
        <small>MISTAKES</small>
        <strong class="mono" style="color:var(--red)">{mistakes}</strong>
      </div>
      <div class="hh-summary-item">
        <small>ROUND SCORE</small>
        <strong class="mono" style="color:#ff5c3e">{score}</strong>
      </div>
    </div>

    {#if roundFeedback}
      <div
        class="hh-round-feedback"
        style="
          margin-top:14px;padding:10px 14px;border-radius:7px;
          font:700 12px 'JetBrains Mono';
          background:rgba(89,232,162,0.08);border:1px solid rgba(89,232,162,0.2);
          color:var(--green)
        "
      >
        +{roundFeedback.xp} XP &nbsp;·&nbsp; Accuracy: {roundFeedback.accuracy}% &nbsp;·&nbsp; Moves: {roundFeedback.moves}
      </div>
    {/if}

    <div class="page-actions" style="margin-top:16px">
      <button class="btn ghost" on:click={retryGame}>RETRY ROUND</button>
      <button class="btn primary" on:click={advanceRound}>
        {roundIndex + 1 < maxRounds ? 'NEXT ROUND →' : 'FINISH GAME →'}
      </button>
    </div>
  </div>
{:else}
  <div>
    <div
      class="hh-header"
      style="
        display:flex;flex-wrap:wrap;gap:10px;
        margin-bottom:16px;align-items:center
      "
    >
      <div
        style="
          background:linear-gradient(135deg,#ff321d,#ff7900);
          padding:8px 16px;border-radius:8px;
          font:700 13px 'JetBrains Mono';color:#fff;
          box-shadow:0 8px 28px rgba(255,49,26,0.2)
        "
      >
        TARGET = {target}
      </div>

      <div class="hh-metric">
        <small>ROUND</small>
        <strong class="mono">{roundIndex + 1}/{maxRounds}</strong>
      </div>

      <div class="hh-metric">
        <small>LOW</small>
        <strong class="mono" style="color:var(--cyan)">{low}</strong>
      </div>

      <div class="hh-metric">
        <small>HIGH</small>
        <strong class="mono" style="color:var(--orange)">{high}</strong>
      </div>

      <div class="hh-metric">
        <small>MID</small>
        <strong class="mono" style="color:#ff5c3e">{mid}</strong>
      </div>

      <div class="hh-metric">
        <small>arr[MID]</small>
        <strong class="mono">{midVal}</strong>
      </div>

      <div class="hh-metric">
        <small>SCORE</small>
        <strong class="mono" style="color:var(--green)">{score}</strong>
      </div>

      <div class="hh-metric">
        <small>MISTAKES</small>
        <strong class="mono" style="color:var(--red)">{mistakes}</strong>
      </div>
    </div>

    <div
      style="
        margin-bottom:10px;font:600 11px 'JetBrains Mono';
        letter-spacing:0.08em;color:#8d929b
      "
    >
      WINDOW: [{low} .. {high}] &nbsp;|&nbsp; MID = {mid} &nbsp;|&nbsp; arr[{mid}] = {midVal} &nbsp;|&nbsp; target = {target}
    </div>

    <div class="hh-array">
      {#each arr as val, i}
        {@const elim = eliminated(i)}
        {@const lo = isLow(i)}
        {@const hi = isHigh(i)}
        {@const mi = isMid(i)}

        <div
          class="hh-cell {elim ? 'hh-eliminated' : 'hh-active'} {lo ? 'hh-low' : ''} {hi ? 'hh-high' : ''} {mi ? 'hh-mid' : ''}"
          style="transition:0.4s ease"
        >
          <span class="hh-idx">{i}</span>
          <span class="hh-val">{val}</span>

          {#if lo}
            <span class="hh-badge hh-badge-low">LOW</span>
          {/if}
          {#if hi}
            <span class="hh-badge hh-badge-high">HIGH</span>
          {/if}
          {#if mi}
            <span class="hh-badge hh-badge-mid">MID</span>
          {/if}
        </div>
      {/each}
    </div>

    {#if phase === 'feedback' && lastMoveDir}
      <div
        class="hh-feedback {lastMoveCorrect ? 'hh-feedback-ok' : 'hh-feedback-err'}"
        style="
          margin-top:12px;padding:10px 14px;border-radius:7px;
          font:700 12px 'JetBrains Mono'
        "
      >
        {#if lastMoveCorrect}
          {#if lastMoveFound}
            <span style="color:var(--green)">+{lastMoveDelta}</span>
            &nbsp; TARGET FOUND &nbsp;|&nbsp;
            {lastMoveComparison}
          {:else}
            <span style="color:var(--green)">+100</span>
            &nbsp; CORRECT &nbsp;|&nbsp;
            {lastMoveComparison}
            &nbsp;=&gt;&nbsp;
            SEARCH {lastMoveDir.toUpperCase()} ✓
          {/if}
        {:else}
          <span style="color:var(--red)">-25</span>
          &nbsp; MISTAKE &nbsp;|&nbsp;
          {lastMoveComparison}
          &nbsp;=&gt;&nbsp;
          should be {lastMoveCorrectDir?.toUpperCase()}
          &nbsp;(you chose {lastMoveDir.toUpperCase()})
        {/if}
      </div>
    {/if}

    {#if apiError}
      <div class="alert error" style="margin-top:12px">
        <b>Error</b>
        <p style="margin:4px 0 0">{apiError}</p>
      </div>
    {/if}

    {#if phase === 'playing' && !found}
      <div class="page-actions" style="margin-top:16px">
        <button
          class="btn ghost big"
          on:click={() => pickDirection('left')}
          disabled={phase !== 'playing' || playSubmitting}
          style="flex:1"
        >
          ← SEARCH LEFT
        </button>

        <button
          class="btn primary big"
          on:click={() => pickDirection('right')}
          disabled={phase !== 'playing' || playSubmitting}
          style="flex:1"
        >
          SEARCH RIGHT →
        </button>
      </div>
    {/if}
  </div>
{/if}

<style>
  .hh-array {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(64px, 1fr));
    gap: 8px;
    margin: 14px 0;
  }
  .hh-cell {
    position: relative;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 12px 4px;
    border: 1px solid var(--line);
    border-radius: 8px;
    background: #090b0f;
    color: #aaaeb5;
    text-align: center;
    transition: 0.4s ease;
    min-height: 64px;
  }
  .hh-cell.hh-active {
    border-color: rgba(255, 255, 255, 0.18);
    background: #0d1016;
    color: #fff;
  }
  .hh-cell.hh-eliminated {
    opacity: 0.25;
    background: #07090c;
    border-color: transparent;
  }
  .hh-cell.hh-low {
    border-color: rgba(43, 223, 245, 0.5);
    box-shadow: 0 0 12px rgba(43, 223, 245, 0.08);
  }
  .hh-cell.hh-high {
    border-color: rgba(255, 121, 0, 0.5);
    box-shadow: 0 0 12px rgba(255, 121, 0, 0.08);
  }
  .hh-cell.hh-mid {
    border-color: rgba(255, 49, 29, 0.7);
    background: rgba(255, 49, 29, 0.08);
    box-shadow: 0 0 18px rgba(255, 49, 29, 0.15);
    transform: scale(1.06);
    z-index: 2;
  }
  .hh-idx {
    font: 700 8px 'JetBrains Mono';
    letter-spacing: 0.06em;
    color: #666b74;
    display: block;
  }
  .hh-val {
    font: 700 18px 'JetBrains Mono';
    display: block;
    margin: 2px 0;
  }
  .hh-badge {
    position: absolute;
    top: 3px;
    font: 700 7px 'JetBrains Mono';
    letter-spacing: 0.08em;
    padding: 1px 4px;
    border-radius: 3px;
    color: #fff;
  }
  .hh-badge-low {
    left: 4px;
    background: rgba(43, 223, 245, 0.25);
    color: var(--cyan);
  }
  .hh-badge-high {
    right: 4px;
    background: rgba(255, 121, 0, 0.25);
    color: var(--orange);
  }
  .hh-badge-mid {
    right: 4px;
    background: rgba(255, 49, 29, 0.3);
    color: var(--red);
  }
  .hh-metric {
    padding: 8px 14px;
    background: #090b0f;
    border: 1px solid var(--line);
    border-radius: 7px;
    text-align: center;
    min-width: 64px;
  }
  .hh-metric small {
    display: block;
    color: #666b74;
    font-size: 8px;
    font-family: 'JetBrains Mono';
    letter-spacing: 0.08em;
    margin-bottom: 2px;
  }
  .hh-metric strong {
    font-family: 'JetBrains Mono';
    font-size: 14px;
  }
  .hh-feedback {
    transition: 0.3s ease;
  }
  .hh-feedback-ok {
    background: rgba(89, 232, 162, 0.08);
    border: 1px solid rgba(89, 232, 162, 0.2);
    color: var(--green);
  }
  .hh-feedback-err {
    background: rgba(255, 50, 29, 0.08);
    border: 1px solid rgba(255, 50, 29, 0.2);
    color: var(--red);
  }
  .hh-summary-grid {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
    margin-top: 18px;
  }
  .hh-summary-item {
    padding: 12px 18px;
    background: #090b0f;
    border: 1px solid var(--line);
    border-radius: 8px;
    text-align: center;
    min-width: 90px;
    flex: 1;
  }
  .hh-summary-item small {
    display: block;
    color: #666b74;
    font-size: 8px;
    font-family: 'JetBrains Mono';
    letter-spacing: 0.08em;
    margin-bottom: 4px;
  }
  .hh-summary-item strong {
    font-family: 'JetBrains Mono';
  }
  .hh-round-results {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    margin-top: 14px;
  }
  .hh-round-result-item {
    padding: 6px 14px;
    background: #0d1016;
    border: 1px solid var(--line);
    border-radius: 6px;
    font: 700 11px 'JetBrains Mono';
    display: flex;
    gap: 10px;
    align-items: center;
    color: #8d929b;
  }
  .hh-complete-badge {
    display: inline-block;
  }
  .hh-complete {
    text-align: center;
    padding: 20px 0;
  }
  .hh-found-panel {
    text-align: center;
  }
  .hh-round-feedback {
    transition: 0.3s ease;
  }
</style>
