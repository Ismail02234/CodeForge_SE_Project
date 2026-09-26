<script lang="ts">
  import { api } from '$lib/api';
  import type { PlayChallenge } from './types';

  export let challenge: PlayChallenge;
  export let slug: string = '';
  export let onComplete: (result: import('./types').GameResult) => void;

  const config = challenge.config || {};
  const arr: number[] = config.array || [];
  const target: number = config.target ?? 0;
  const timerSeconds: number = config.timer_seconds ?? (config.difficulty === 'easy' ? 0 : config.difficulty === 'hard' ? 45 : 60);
  const minMoves: number = config.min_moves ?? 0;

  type TRMove = { mid: number; dir: '' | 'left' | 'right' | 'found' };
  type TRPhase = 'picking-mid' | 'picking-dir' | 'feedback' | 'timeout' | 'complete';

  let moves: TRMove[] = [];
  let low = 0;
  let high = arr.length - 1;
  let mid = arr.length > 0 ? Math.floor((low + high) / 2) : 0;
  let midVal: number | null = arr.length > 0 ? arr[mid] : null;
  let found = false;
  let phase: TRPhase = arr.length > 0 ? 'picking-mid' : 'complete';
  let score = 0;
  let mistakes = 0;
  let submitting = false;
  let error = '';
  let feedbackText = '';
  let feedbackType: 'ok' | 'err' | '' = '';
  let feedbackTimer: ReturnType<typeof setTimeout> | null = null;

  let timerRemaining = timerSeconds;
  let timerActive = timerSeconds > 0;
  let timerInterval: ReturnType<typeof setInterval> | null = null;
  let elapsedSeconds = 0;
  let elapsedInterval: ReturnType<typeof setInterval> | null = null;

  let backendResult: { score: number; max_score: number; completed: boolean; accuracy: number; xp_reward: number; attempts: number; moves: number; mistakes: number; play_completed_challenges: Record<string, any> } | null = null;
  let backendAccuracy = 0;
  let gameCompleted = false;

  function reset() {
    if (feedbackTimer) clearTimeout(feedbackTimer);
    if (timerInterval) clearInterval(timerInterval);
    if (elapsedInterval) clearInterval(elapsedInterval);
    moves = [];
    low = 0;
    high = arr.length - 1;
    mid = arr.length > 0 ? Math.floor((low + high) / 2) : 0;
    midVal = arr.length > 0 ? arr[mid] : null;
    found = false;
    phase = arr.length > 0 ? 'picking-mid' : 'complete';
    score = 0;
    mistakes = 0;
    submitting = false;
    error = '';
    feedbackText = '';
    feedbackType = '';
    timerRemaining = timerSeconds;
    timerActive = timerSeconds > 0;
    elapsedSeconds = 0;
    backendResult = null;
    backendAccuracy = 0;
    gameCompleted = false;
  }

  function startTimer() {
    if (timerInterval) clearInterval(timerInterval);
    if (elapsedInterval) clearInterval(elapsedInterval);
    timerRemaining = timerSeconds;
    timerActive = true;
    elapsedSeconds = 0;
    if (timerSeconds > 0) {
      timerInterval = setInterval(() => {
        timerRemaining--;
        if (timerRemaining <= 0) {
          if (timerInterval) clearInterval(timerInterval);
          timerActive = false;
          phase = 'timeout';
        }
      }, 1000);
      elapsedInterval = setInterval(() => {
        elapsedSeconds++;
      }, 1000);
    }
  }

  function stopTimer() {
    if (timerInterval) clearInterval(timerInterval);
    if (elapsedInterval) clearInterval(elapsedInterval);
    timerActive = false;
  }

  function showFeedback(text: string, type: 'ok' | 'err') {
    feedbackText = text;
    feedbackType = type;
    if (feedbackTimer) clearTimeout(feedbackTimer);
    feedbackTimer = setTimeout(() => {
      feedbackText = '';
      feedbackType = '';
    }, 1500);
  }

  function selectMid(index: number) {
    if (phase !== 'picking-mid' || found || gameCompleted) return;
    if (index !== mid) {
      showFeedback(`mid should be ${mid} (you picked ${index})`, 'err');
      mistakes++;
      score = Math.max(0, score - (config.incorrect_mid_score ?? 25));
      moves = [...moves, { mid: index, dir: '' }];
      phase = 'feedback';
      if (feedbackTimer) clearTimeout(feedbackTimer);
      feedbackTimer = setTimeout(() => {
        feedbackText = '';
        feedbackType = '';
        phase = 'picking-mid';
      }, 1200);
      return;
    }
    moves = [...moves, { mid: index, dir: '' }];
    phase = 'picking-dir';
  }

  function chooseDirection(dir: 'left' | 'right' | 'found') {
    if (phase !== 'picking-dir' || found || gameCompleted) return;
    if (moves.length === 0 || moves[moves.length - 1].dir !== '') return;

    const currentMoves = moves.map(m => ({ mid: m.mid, dir: m.dir === '' ? (dir === 'found' ? 'found' : dir) : m.dir }));
    moves = currentMoves;
    submitMove(currentMoves);
  }

  async function submitMove(currentMoves: TRMove[]) {
    if (submitting) return;
    submitting = true;
    error = '';

    try {
      const action = JSON.stringify({ moves: currentMoves.map(m => ({ mid: m.mid, dir: m.dir })) });
      const res = await api.post<{
        score: number;
        max_score: number;
        completed: boolean;
        accuracy: number;
        xp_reward: number;
        attempts: number;
        moves: number;
        mistakes: number;
        low: number;
        high: number;
        mid: number;
        mid_val: number;
        found: boolean;
        min_moves: number;
        play_completed_challenges: Record<string, any>;
      }>(`/api/learn/${slug}/play`, {
        challenge_id: challenge.id,
        action,
      });

      backendAccuracy = res.accuracy ?? backendAccuracy;

      if (phase !== 'timeout') {
        low = res.low ?? low;
        high = res.high ?? high;
        mid = res.mid ?? mid;
        midVal = res.mid_val ?? midVal;
        found = res.found ?? found;
        score = res.score ?? score;
        mistakes = res.mistakes ?? mistakes;

        if (res.found) {
          stopTimer();
          phase = 'complete';
          moves = currentMoves;
          gameCompleted = true;
          backendResult = {
            score: res.score,
            max_score: res.max_score,
            completed: res.completed,
            accuracy: res.accuracy,
            xp_reward: res.xp_reward,
            attempts: res.attempts,
            moves: res.moves,
            mistakes: res.mistakes,
            play_completed_challenges: res.play_completed_challenges ?? {},
          };
        } else {
          phase = 'picking-mid';
          moves = currentMoves;
        }
      }
    } catch (e: any) {
      error = e.message;
      if (phase !== 'timeout') {
        moves = moves.map((m, i) => (i === moves.length - 1 ? { mid: m.mid, dir: '' } : m));
        phase = 'picking-dir';
      }
    } finally {
      submitting = false;
    }
  }

  async function finishChallenge() {
    if (submitting || phase !== 'complete') return;
    submitting = true;
    error = '';
    try {
      if (backendResult) {
        onComplete({
          score: backendResult.score,
          max_score: backendResult.max_score,
          completed: backendResult.completed,
          accuracy: backendResult.accuracy,
          xp_reward: backendResult.xp_reward,
          attempts: backendResult.attempts,
          moves: backendResult.moves,
          mistakes: backendResult.mistakes,
          play_completed_challenges: backendResult.play_completed_challenges,
        });
        return;
      }

      const action = JSON.stringify({ moves: moves.map(m => ({ mid: m.mid, dir: m.dir })) });
      const res = await api.post<{
        score: number;
        max_score: number;
        completed: boolean;
        accuracy: number;
        xp_reward: number;
        attempts: number;
        moves: number;
        mistakes: number;
        play_completed_challenges: Record<string, any>;
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
        play_completed_challenges: res.play_completed_challenges,
      });
    } catch (e: any) {
      error = e.message;
    } finally {
      submitting = false;
    }
  }

  function retryGame() {
    stopTimer();
    reset();
    startTimer();
  }

  function inBounds(i: number): boolean {
    return i >= low && i <= high;
  }

  function isLow(i: number): boolean {
    return i === low;
  }

  function isHigh(i: number): boolean {
    return i === high;
  }

  function isMid(i: number): boolean {
    return i === mid && phase === 'picking-mid';
  }

  function visitedMid(i: number): TRMove | undefined {
    return moves.find(m => m.mid === i);
  }

  function getPerformanceLabel(accuracy: number, userMoves: number, minM: number): string {
    if (accuracy >= 100 && userMoves <= minM) return 'Excellent';
    if (accuracy >= 80) return 'Good';
    return 'Practice Again';
  }

  $: completedMoves = moves.filter(m => m.dir !== '').length;
  $: displayAccuracy = backendAccuracy;
  $: performanceLabel = getPerformanceLabel(displayAccuracy, completedMoves, minMoves);
  $: performanceColor = performanceLabel === 'Excellent' ? 'var(--green)' : performanceLabel === 'Good' ? '#ffc268' : 'var(--red)';
  $: isTimeout = phase === 'timeout';
  $: isComplete = phase === 'complete';

  reset();
  startTimer();
</script>

<svelte:head><title>{challenge.title} · CodeForge</title></svelte:head>

<div class="tr-container">
  {#if error}
    <div class="alert error" style="margin-bottom:14px"><b>Error</b><p style="margin:4px 0 0">{error}</p></div>
  {/if}

  {#if arr.length === 0}
    <div class="empty"><p>Loading challenge...</p></div>
  {:else if isTimeout}
    <div class="panel" style="text-align:center;padding:40px 20px">
      <div style="font:700 28px 'JetBrains Mono';color:var(--orange);margin-bottom:8px">TIME UP</div>
      <p style="color:var(--muted);margin:0 0 20px">The countdown expired before you found the target.</p>
      <div class="page-actions" style="justify-content:center">
        <button class="btn primary" on:click={retryGame}>RETRY</button>
      </div>
    </div>
  {:else if isComplete}
    <div class="panel" style="margin-top:14px;text-align:center">
      <div style="background:linear-gradient(135deg,#ff321d,#ff7900);display:inline-block;padding:14px 28px;border-radius:10px;font:700 16px 'JetBrains Mono';color:#fff;box-shadow:0 12px 40px rgba(255,49,26,0.25)">
        TRACE COMPLETE
      </div>

      <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:16px;justify-content:center">
        <div class="tr-metric"><small>SCORE</small><strong class="mono" style="color:#ff5c3e">{Math.max(0, score)}</strong></div>
        <div class="tr-metric"><small>MOVES</small><strong class="mono">{completedMoves}</strong></div>
        <div class="tr-metric"><small>MINIMUM</small><strong class="mono" style="color:var(--cyan)">{minMoves}</strong></div>
        <div class="tr-metric"><small>MISTAKES</small><strong class="mono" style="color:var(--red)">{mistakes}</strong></div>
        <div class="tr-metric"><small>ACCURACY</small><strong class="mono" style="color:var(--green)">{displayAccuracy}%</strong></div>
        <div class="tr-metric"><small>TIME</small><strong class="mono">{timerSeconds > 0 ? `${timerSeconds - timerRemaining}s` : '—'}</strong></div>
      </div>

      <div style="margin-top:14px;padding:10px 16px;background:#090b0f;border:1px solid var(--line);border-radius:8px;display:inline-block">
        <span style="font:700 11px 'JetBrains Mono';letter-spacing:0.08em;color:var(--muted)">PERFORMANCE:</span>
        <span style="font:700 11px 'JetBrains Mono';letter-spacing:0.08em;margin-left:8px;color:{performanceColor}">
          {performanceLabel}
        </span>
      </div>

      <div style="margin-top:14px;text-align:left">
        <span style="font:700 9px 'JetBrains Mono';letter-spacing:0.08em;color:#8d929b;display:block;margin-bottom:8px">MOVE HISTORY</span>
        <div style="display:flex;flex-direction:column;gap:5px">
          {#each moves.filter(m => m.dir !== '') as move, i}
            <div style="display:flex;align-items:center;gap:8px;padding:7px 12px;background:#07090d;border:1px solid var(--line);border-radius:5px;font:700 10px 'JetBrains Mono'">
              <span style="color:#555b64;width:18px">M{i + 1}</span>
              <span style="color:#8d929b">mid={move.mid}</span>
              <span style="color:{move.dir === 'found' ? 'var(--green)' : move.dir === 'left' ? 'var(--cyan)' : 'var(--orange)'};margin-left:auto">
                {move.dir === 'found' ? 'FOUND ★' : move.dir === 'left' ? '← LEFT' : 'RIGHT →'}
              </span>
            </div>
          {/each}
        </div>
      </div>

      <div class="page-actions" style="margin-top:16px;justify-content:center">
        <button class="btn ghost" on:click={retryGame}>PLAY AGAIN</button>
        <button class="btn primary" on:click={finishChallenge} disabled={submitting}>
          {submitting ? 'Submitting...' : 'CONTINUE →'}
        </button>
      </div>
    </div>
  {:else}
    <div>
      <div style="display:flex;flex-wrap:wrap;gap:10px;margin-bottom:16px;align-items:center">
        <div style="background:linear-gradient(135deg,#ff321d,#ff7900);padding:8px 16px;border-radius:8px;font:700 13px 'JetBrains Mono';color:#fff;box-shadow:0 8px 28px rgba(255,49,26,0.2)">
          TARGET = {target}
        </div>

        <div class="tr-metric">
          <small>LOW</small>
          <strong class="mono" style="color:var(--cyan)">{low}</strong>
        </div>

        <div class="tr-metric">
          <small>MID</small>
          <strong class="mono" style="color:#ff5c3e">{mid}</strong>
        </div>

        <div class="tr-metric">
          <small>HIGH</small>
          <strong class="mono" style="color:var(--orange)">{high}</strong>
        </div>

        <div class="tr-metric">
          <small>MOVES</small>
          <strong class="mono">{completedMoves}</strong>
        </div>

        {#if minMoves > 0}
          <div class="tr-metric">
            <small>MINIMUM</small>
            <strong class="mono" style="color:var(--cyan)">{minMoves}</strong>
          </div>
        {/if}

        <div class="tr-metric">
          <small>SCORE</small>
          <strong class="mono" style="color:var(--green)">{Math.max(0, score)}</strong>
        </div>

        <div class="tr-metric">
          <small>MISTAKES</small>
          <strong class="mono" style="color:var(--red)">{mistakes}</strong>
        </div>

        {#if timerSeconds > 0}
          <div class="tr-metric" style="border-color:{timerRemaining <= 10 ? 'rgba(255,50,29,0.5)' : 'var(--line)'}">
            <small>TIME</small>
            <strong class="mono" style="color:{timerRemaining <= 10 ? 'var(--red)' : 'var(--text)'}">{timerRemaining}s</strong>
          </div>
        {/if}
      </div>

      <div style="margin-bottom:10px;font:600 11px 'JetBrains Mono';letter-spacing:0.08em;color:#8d929b">
        WINDOW: [{low} .. {high}] &nbsp;|&nbsp; MID = {mid} &nbsp;|&nbsp; arr[{mid}] = {midVal} &nbsp;|&nbsp; target = {target}
      </div>

      <div class="tr-array">
        {#each arr as val, i}
          {@const elim = !inBounds(i)}
          {@const lo = isLow(i)}
          {@const hi = isHigh(i)}
          {@const mi = isMid(i)}
          {@const vis = visitedMid(i)}
          <div
            class="tr-cell {elim ? 'tr-elim' : 'tr-in'} {lo ? 'tr-low' : ''} {hi ? 'tr-high' : ''} {mi ? 'tr-mid' : ''} {vis && vis.dir !== '' ? 'tr-visited' : ''}"
            style="transition:0.4s ease;opacity:{elim ? 0.25 : 1}"
          >
            <span class="tr-idx" style="color:{lo ? 'var(--cyan)' : hi ? 'var(--orange)' : '#555b64'}">{i}</span>
            <span class="tr-val" style="color:{elim ? '#3e424a' : '#f4f5f6'}">{val}</span>
            {#if lo}<span class="tr-badge tr-b-low">LOW</span>{/if}
            {#if hi}<span class="tr-badge tr-b-high">HIGH</span>{/if}
            {#if mi}<span class="tr-badge tr-b-mid">MID</span>{/if}
            {#if vis && vis.dir === 'found'}
              <span class="tr-badge tr-b-found">★</span>
            {:else if vis && vis.dir === 'left'}
              <span class="tr-badge tr-b-left">←</span>
            {:else if vis && vis.dir === 'right'}
              <span class="tr-badge tr-b-right">→</span>
            {/if}
          </div>
        {/each}
      </div>

      {#if feedbackText}
        <div class="tr-feedback {feedbackType === 'ok' ? 'tr-fb-ok' : 'tr-fb-err'}" style="margin-top:12px;padding:10px 14px;border-radius:7px;font:700 11px 'JetBrains Mono';animation:fadeIn 0.2s ease">
          {feedbackText}
        </div>
      {/if}

      {#if phase === 'picking-mid' && !found}
        <div class="page-actions" style="margin-top:14px">
          <span style="font:700 9px 'JetBrains Mono';letter-spacing:0.08em;color:#8d929b;margin-right:6px">SELECT MIDPOINT:</span>
          <button class="btn primary" on:click={() => selectMid(mid)} disabled={submitting} style="min-width:200px">
            Confirm mid = {mid}
          </button>
        </div>
      {/if}

      {#if phase === 'picking-dir' && !found}
        <div class="page-actions" style="margin-top:10px">
          <span style="font:700 9px 'JetBrains Mono';letter-spacing:0.08em;color:#8d929b;margin-right:6px">CHOOSE DIRECTION:</span>
          <button class="btn ghost" on:click={() => chooseDirection('left')} disabled={submitting}>
            ← LEFT (target &lt; arr[mid])
          </button>
          <button class="btn ghost" on:click={() => chooseDirection('right')} disabled={submitting}>
            RIGHT (target &gt; arr[mid]) →
          </button>
          {#if midVal === target}
            <button class="btn primary" on:click={() => chooseDirection('found')} disabled={submitting}>
              FOUND IT! ★
            </button>
          {/if}
        </div>
      {/if}
    </div>
  {/if}
</div>

<style>
  .tr-container {
    display: flex;
    flex-direction: column;
    gap: 14px;
  }
  .tr-array {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(64px, 1fr));
    gap: 8px;
    margin: 14px 0;
  }
  .tr-cell {
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
  .tr-cell.tr-in {
    border-color: rgba(255, 255, 255, 0.18);
    background: #0d1016;
    color: #fff;
  }
  .tr-cell.tr-elim {
    opacity: 0.25;
    background: #07090c;
    border-color: transparent;
  }
  .tr-cell.tr-low {
    border-color: rgba(43, 223, 245, 0.5);
    box-shadow: 0 0 12px rgba(43, 223, 245, 0.08);
  }
  .tr-cell.tr-high {
    border-color: rgba(255, 121, 0, 0.5);
    box-shadow: 0 0 12px rgba(255, 121, 0, 0.08);
  }
  .tr-cell.tr-mid {
    border-color: rgba(255, 49, 29, 0.7);
    background: rgba(255, 49, 29, 0.08);
    box-shadow: 0 0 18px rgba(255, 49, 29, 0.15);
    transform: scale(1.06);
    z-index: 2;
  }
  .tr-cell.tr-visited {
    border-color: rgba(255, 255, 255, 0.12);
  }
  .tr-idx {
    font: 700 8px 'JetBrains Mono';
    letter-spacing: 0.06em;
    color: #666b74;
    display: block;
  }
  .tr-val {
    font: 700 18px 'JetBrains Mono';
    display: block;
    margin: 2px 0;
  }
  .tr-badge {
    position: absolute;
    top: 3px;
    font: 700 7px 'JetBrains Mono';
    letter-spacing: 0.08em;
    padding: 1px 4px;
    border-radius: 3px;
    color: #fff;
  }
  .tr-b-low {
    left: 4px;
    background: rgba(43, 223, 245, 0.25);
    color: var(--cyan);
  }
  .tr-b-high {
    right: 4px;
    background: rgba(255, 121, 0, 0.25);
    color: var(--orange);
  }
  .tr-b-mid {
    right: 4px;
    background: rgba(255, 49, 29, 0.3);
    color: var(--red);
  }
  .tr-b-found {
    right: 4px;
    background: rgba(73, 224, 149, 0.25);
    color: var(--green);
  }
  .tr-b-left {
    right: 4px;
    background: rgba(43, 223, 245, 0.25);
    color: var(--cyan);
  }
  .tr-b-right {
    right: 4px;
    background: rgba(255, 121, 0, 0.25);
    color: var(--orange);
  }
  .tr-metric {
    padding: 8px 14px;
    background: #090b0f;
    border: 1px solid var(--line);
    border-radius: 7px;
    text-align: center;
    min-width: 64px;
  }
  .tr-metric small {
    display: block;
    color: #666b74;
    font-size: 8px;
    font-family: 'JetBrains Mono';
    letter-spacing: 0.08em;
    margin-bottom: 2px;
  }
  .tr-metric strong {
    font-family: 'JetBrains Mono';
    font-size: 14px;
  }
  .tr-feedback {
    transition: 0.3s ease;
  }
  .tr-fb-ok {
    background: rgba(89, 232, 162, 0.08);
    border: 1px solid rgba(89, 232, 162, 0.2);
    color: var(--green);
  }
  .tr-fb-err {
    background: rgba(255, 50, 29, 0.08);
    border: 1px solid rgba(255, 50, 29, 0.2);
    color: var(--red);
  }
  @keyframes fadeIn {
    from { opacity: 0; transform: translateY(-4px); }
    to { opacity: 1; transform: translateY(0); }
  }
</style>
