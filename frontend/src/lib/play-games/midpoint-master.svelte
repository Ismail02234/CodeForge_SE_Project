<script lang="ts">
  import { api } from '$lib/api';
  import type { PlayChallenge, MidpointMasterRound } from './types';

  export let challenge: PlayChallenge;
  export let slug: string = '';
  export let onComplete: (score: number, xp: number) => void;

  let rounds: MidpointMasterRound[] = [];
  let currentRound = 0;
  let selectedIndex: number | null = null;
  let feedback: { correct: boolean; mid: number } | null = null;
  let totalScore = 0;
  let roundResults: Array<{ expected: number; got: number; correct: boolean }> = [];
  let gamePhase: 'playing' | 'complete' = 'playing';
  let submitting = false;
  let error = '';

  $: config = challenge.config || {};
  $: roundConfigs = config.rounds || [];
  $: allRoundsComplete = currentRound >= roundConfigs.length && roundConfigs.length > 0;

  function initGame() {
    rounds = roundConfigs.map((r: any) => ({
      low: r.low ?? 0,
      high: r.high ?? 0,
      expected_mid: r.expected_mid ?? Math.floor(((r.low ?? 0) + (r.high ?? 0)) / 2),
      user_answer: null,
      submitted: false,
    }));
    currentRound = 0;
    selectedIndex = null;
    feedback = null;
    totalScore = 0;
    roundResults = [];
    gamePhase = 'playing';
    error = '';
  }

  function getAvailableIndices(): number[] {
    if (currentRound >= rounds.length) return [];
    const { low, high } = rounds[currentRound];
    const indices: number[] = [];
    for (let i = low; i <= high; i++) {
      indices.push(i);
    }
    return indices;
  }

  function pickIndex(idx: number) {
    if (gamePhase !== 'playing' || feedback || submitting) return;
    selectedIndex = idx;
  }

  function submitChoice() {
    if (selectedIndex === null || gamePhase !== 'playing' || feedback) return;
    const round = rounds[currentRound];
    const correct = selectedIndex === round.expected_mid;
    feedback = { correct, mid: round.expected_mid };
    round.user_answer = selectedIndex;
    round.submitted = true;

    if (correct) {
      totalScore += config.correct_score ?? 100;
    } else {
      totalScore += config.incorrect_score ?? -25;
    }
    totalScore = Math.max(0, totalScore);

    roundResults.push({ expected: round.expected_mid, got: selectedIndex, correct });

    setTimeout(() => {
      feedback = null;
      selectedIndex = null;
      currentRound++;
      if (currentRound >= rounds.length) {
        gamePhase = 'complete';
      }
    }, 900);
  }

  function getCalculation(round: MidpointMasterRound): string {
    const sum = round.low + round.high;
    const mid = Math.floor(sum / 2);
    return `⌊(${round.low} + ${round.high}) / 2⌋ = ⌊${sum} / 2⌋ = ${mid}`;
  }

  async function finishChallenge() {
    if (submitting || gamePhase !== 'complete') return;
    submitting = true;
    error = '';
    try {
      const res = await api.post<{ score: number; xp_reward: number }>(`/api/learn/${slug}/play`, {
        challenge_id: challenge.id,
        action: JSON.stringify(roundResults.map((r) => r.got)),
      });
      onComplete(res.score, res.xp_reward);
    } catch (e: any) {
      error = e.message;
    } finally {
      submitting = false;
    }
  }

  function retry() {
    initGame();
  }

  export function retryGame() {
    initGame();
  }

  initGame();
</script>

<svelte:head><title>{challenge.title} · CodeForge</title></svelte:head>

<div class="mm-container">
  <div class="mm-header">
    <div>
      <span class="eyebrow">MIDPOINT MASTER · +{challenge.xp_reward} XP</span>
      <h2 style="margin:8px 0 4px;font-size:20px">Find the midpoint: <code class="mono" style="color:#ff5c3e">mid = ⌊(low + high) / 2⌋</code></h2>
      <p style="margin:0;font-size:11px;color:#8d929b">Round {Math.min(currentRound + 1, roundConfigs.length)} / {roundConfigs.length}</p>
    </div>
    <div style="display:flex;gap:10px;flex-wrap:wrap">
      <div class="mm-metric"><small>SCORE</small><strong class="mono" style="color:var(--green)">{totalScore}</strong></div>
      <div class="mm-metric"><small>ACCURACY</small><strong class="mono" style="color:var(--cyan)">{roundResults.length > 0 ? Math.round((roundResults.filter(r => r.correct).length / roundResults.length) * 100) : 0}%</strong></div>
    </div>
  </div>

  {#if error}
    <div class="alert error" style="margin-bottom:14px">
      <b>Error</b>
      <p style="margin:4px 0 0">{error}</p>
    </div>
  {/if}

  {#if gamePhase === 'playing' && currentRound < rounds.length}
    {@const round = rounds[currentRound]}
    {@const avail = getAvailableIndices()}

    <div class="panel" style="margin-bottom:14px">
      <p style="margin:0 0 12px;font-size:13px">
        Given <code class="mono" style="color:#ff5c3e">low = {round.low}</code> and <code class="mono" style="color:#2bdff5">high = {round.high}</code>,
        what is the midpoint index?
      </p>

      <div style="background:#06070a;border:1px solid #20232a;border-radius:7px;padding:12px 14px;margin-bottom:14px">
        <code class="mono" style="font-size:12px;color:#aeb1b7">{getCalculation(round)}</code>
      </div>

      <div class="mm-array-row" style="display:flex;gap:6px;flex-wrap:wrap;justify-content:center">
        {#each avail as idx}
          {@const selected = selectedIndex === idx}
          {@const wasCorrect = round.submitted && idx === round.expected_mid}
          {@const wasWrong = round.submitted && idx === round.user_answer && idx !== round.expected_mid}
          <button
            class="mm-idx-cell {selected ? 'mm-selected' : ''} {wasCorrect ? 'mm-correct' : ''} {wasWrong ? 'mm-wrong' : ''}"
            on:click={() => pickIndex(idx)}
            disabled={!!feedback || submitting}
            style="
              width:52px;height:52px;
              border:1px solid {selected ? '#ff5c3e' : wasCorrect ? 'var(--green)' : wasWrong ? 'var(--red)' : '#242832'};
              border-radius:7px;
              background:{selected ? 'rgba(255,90,50,0.15)' : wasCorrect ? 'rgba(73,224,149,0.1)' : wasWrong ? 'rgba(255,49,26,0.1)' : '#07090d'};
              cursor:pointer;
              font:700 18px 'JetBrains Mono';
              color:{selected ? '#ff5c3e' : wasCorrect ? 'var(--green)' : wasWrong ? 'var(--red)' : '#c5c8ce'};
              transition:0.12s ease;
            "
          >
            {idx}
          </button>
        {/each}
      </div>

      {#if feedback}
        <div class="mm-feedback {feedback.correct ? 'mm-fb-correct' : 'mm-fb-wrong'}" style="margin-top:14px;padding:10px 14px;border-radius:7px;font:700 11px 'JetBrains Mono'">
          {#if feedback.correct}
            <span style="color:var(--green)">+{config.correct_score ?? 100}</span> &nbsp; CORRECT! &nbsp; mid = {feedback.mid}
          {:else}
            <span style="color:var(--red)">{config.incorrect_score ?? -25}</span> &nbsp; WRONG — correct answer is <strong>{feedback.mid}</strong>
          {/if}
        </div>
      {:else}
        <div class="page-actions" style="margin-top:14px">
          <button class="btn primary" on:click={submitChoice} disabled={selectedIndex === null || submitting}>
            Confirm answer
          </button>
        </div>
      {/if}
    </div>

  {:else if gamePhase === 'complete'}
    <div class="panel">
      <div style="text-align:center;margin-bottom:16px">
        <div style="font:700 12px 'JetBrains Mono';letter-spacing:0.12em;color:#8d929b;margin-bottom:8px">ROUND COMPLETE</div>
        <div style="font:700 36px 'JetBrains Mono';color:#ff5c3e">{totalScore}</div>
        <div style="font:600 9px 'JetBrains Mono';letter-spacing:0.08em;color:#666b74;margin-top:4px">TOTAL SCORE</div>
      </div>

      <div style="margin-bottom:14px">
        <span style="font:700 9px 'JetBrains Mono';letter-spacing:0.08em;color:#8d929b;display:block;margin-bottom:8px">ROUND RESULTS</span>
        <div style="display:flex;flex-direction:column;gap:6px">
          {#each roundResults as result, i}
            {@const r = rounds[i]}
            <div style="display:flex;align-items:center;gap:10px;padding:8px 12px;background:#07090d;border:1px solid var(--line);border-radius:5px">
              <span style="font:700 9px 'JetBrains Mono';color:#555b64;width:20px">R{i + 1}</span>
              <span class="mono" style="font-size:12px;color:#8d929b">⌊({r.low}+{r.high})/2⌋ = {result.expected}</span>
              <span style="margin-left:auto;font:700 9px 'JetBrains Mono';color:{result.correct ? 'var(--green)' : 'var(--red)'}">
                {result.correct ? '+100' : `-25 (you: ${result.got})`}
              </span>
            </div>
          {/each}
        </div>
      </div>

      <div class="mm-summary-row" style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:16px">
        <div class="mm-metric">
          <small>ROUNDS</small>
          <strong class="mono">{roundConfigs.length}</strong>
        </div>
        <div class="mm-metric">
          <small>CORRECT</small>
          <strong class="mono" style="color:var(--green)">{roundResults.filter(r => r.correct).length}</strong>
        </div>
        <div class="mm-metric">
          <small>ACCURACY</small>
          <strong class="mono" style="color:#ffc268">{roundConfigs.length > 0 ? Math.round((roundResults.filter(r => r.correct).length / roundConfigs.length) * 100) : 0}%</strong>
        </div>
        <div class="mm-metric">
          <small>FINAL SCORE</small>
          <strong class="mono" style="color:#ff5c3e">{totalScore}</strong>
        </div>
      </div>

      <div class="page-actions">
        <button class="btn ghost" on:click={retry}>Retry</button>
        <button class="btn primary" on:click={finishChallenge} disabled={submitting}>
          {submitting ? 'Submitting...' : 'Finish challenge'}
        </button>
      </div>
    </div>
  {/if}
</div>

<style>
  .mm-container {
    display: flex;
    flex-direction: column;
    gap: 14px;
  }
  .mm-header {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    align-items: flex-start;
    justify-content: space-between;
  }
  .mm-metric {
    padding: 10px 14px;
    background: #07090d;
    border: 1px solid var(--line);
    border-radius: 7px;
    min-width: 80px;
    text-align: center;
  }
  .mm-metric small {
    display: block;
    font: 700 7px 'JetBrains Mono';
    letter-spacing: 0.1em;
    color: #555b64;
  }
  .mm-metric strong {
    font-size: 18px;
    margin-top: 4px;
  }
  .mm-array-row {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    justify-content: center;
  }
  .mm-idx-cell {
    transition: transform 0.12s ease, border-color 0.12s ease;
  }
  .mm-idx-cell:hover:not(:disabled) {
    transform: translateY(-2px);
    border-color: rgba(255, 90, 50, 0.5) !important;
  }
  .mm-feedback {
    animation: fadeIn 0.2s ease;
  }
  .mm-fb-correct {
    background: rgba(73, 224, 149, 0.08);
    border: 1px solid rgba(73, 224, 149, 0.22);
    color: #8ff0bd;
  }
  .mm-fb-wrong {
    background: rgba(255, 48, 29, 0.08);
    border: 1px solid rgba(255, 70, 45, 0.22);
    color: #ff8b75;
  }
  @keyframes fadeIn {
    from { opacity: 0; transform: translateY(-4px); }
    to { opacity: 1; transform: translateY(0); }
  }
</style>
