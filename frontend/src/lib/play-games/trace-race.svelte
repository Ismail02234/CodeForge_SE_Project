<script lang="ts">
  import { api } from '$lib/api';
  import type { PlayChallenge } from './types';

  export let challenge: PlayChallenge;
  export let slug: string = '';
  export let onComplete: (score: number, xp: number) => void;

  const config = challenge.config || {};
  const arr: number[] = config.array || [];
  const target: number = config.target ?? 0;
  const baseScore: number = config.base_score ?? 1000;
  const penaltyMid: number = config.penalty_incorrect_mid ?? 100;
  const penaltyDir: number = config.penalty_wrong_direction ?? 100;
  const penaltyExtra: number = config.penalty_extra_move ?? 50;

  type TRMove = { mid: number; dir: '' | 'left' | 'right' | 'found' };
  type TRPhase = 'playing' | 'complete';

  let moves: TRMove[] = [];
  let low = 0;
  let high = arr.length - 1;
  let found = false;
  let phase: TRPhase = 'playing';
  let totalScore = baseScore;
  let roundResults: Array<{ expectedMid: number; gotMid: number; correctMid: boolean; dir: string; expectedDir: string; correctDir: boolean }> = [];
  let submitting = false;
  let error = '';
  let feedbackFlash: { text: string; type: 'ok' | 'err' } | null = null;

  function reset() {
    moves = [];
    low = 0;
    high = arr.length - 1;
    found = false;
    phase = 'playing';
    totalScore = baseScore;
    roundResults = [];
    error = '';
    feedbackFlash = null;
  }

  function expectedMid(): number {
    return Math.floor((low + high) / 2);
  }

  function expectedDir(): 'left' | 'right' {
    return target > arr[expectedMid()] ? 'right' : 'left';
  }

  function showFlash(text: string, type: 'ok' | 'err') {
    feedbackFlash = { text, type };
    setTimeout(() => { feedbackFlash = null; }, 1500);
  }

  function pickMid() {
    if (phase !== 'playing' || found) return;
    const mid = expectedMid();
    moves = [...moves, { mid, dir: '' }];
  }

  function chooseDirection(dir: 'left' | 'right') {
    if (phase !== 'playing') return;
    if (!moves.length || moves[moves.length - 1].dir !== '') return;

    const last = moves[moves.length - 1];
    const expMid = expectedMid();
    const expDir = expectedDir();

    const correctMid = last.mid === expMid;
    const correctDir = dir === expDir;

    if (!correctMid) totalScore = Math.max(0, totalScore - penaltyMid);
    if (!correctDir) totalScore = Math.max(0, totalScore - penaltyDir);

    roundResults.push({ expectedMid: expMid, gotMid: last.mid, correctMid, dir, expectedDir: expDir, correctDir });

    const midVal = arr[last.mid];
    const parts: string[] = [];
    if (correctMid) parts.push(`✓ mid=${last.mid} (arr[mid]=${midVal})`);
    else parts.push(`✗ mid should be ${expMid}, you picked ${last.mid}`);

    if (midVal === target) {
      found = true;
      phase = 'complete';
      parts.push('🎯 TARGET FOUND!');
      moves = moves.map(m => ({ ...m, dir: m.dir || 'found' as const }));
    } else {
      parts.push(correctDir ? `✓ go ${dir.toUpperCase()}` : `✗ should go ${expDir.toUpperCase()}`);
      moves = moves.map(m => m.dir ? m : ({ ...m, dir } as TRMove));
      if (dir === 'left') high = last.mid - 1;
      else low = last.mid + 1;
    }

    showFlash(parts.join(' | '), correctMid && correctDir ? 'ok' : 'err');
  }

  function inBounds(i: number): boolean { return i >= low && i <= high; }
  function isLow(i: number): boolean { return i === low; }
  function isHigh(i: number): boolean { return i === high; }

  async function finishChallenge() {
    if (submitting || phase !== 'complete') return;
    submitting = true;
    error = '';
    try {
      const action = JSON.stringify({ moves: moves.map(m => ({ mid: m.mid, dir: m.dir })), found });
      const res = await api.post<{ score: number; xp_reward: number }>(`/api/learn/${slug}/play`, {
        challenge_id: challenge.id,
        action,
      });
      onComplete(res.score, res.xp_reward);
    } catch (e: any) {
      error = e.message;
    } finally {
      submitting = false;
    }
  }

  export function retryGame() {
    reset();
  }

  reset();
</script>

<svelte:head><title>{challenge.title} · CodeForge</title></svelte:head>

<div class="tr-container">
  <div class="tr-header">
    <div>
      <span class="eyebrow">TRACE RACE · +{challenge.xp_reward} XP</span>
      <h2 style="margin:8px 0 4px;font-size:20px">Find target = <code class="mono" style="color:#ff5c3e">{target}</code> in minimum moves?</h2>
    </div>
    <div style="display:flex;gap:10px;flex-wrap:wrap">
      <div class="tr-metric"><small>MOVES</small><strong class="mono">{moves.length}</strong></div>
      <div class="tr-metric"><small>BOUNDS</small><strong class="mono">{low} → {high}</strong></div>
      <div class="tr-metric"><small>SCORE</small><strong class="mono" style="color:var(--green)">{Math.max(0, totalScore)}</strong></div>
    </div>
  </div>

  {#if error}
    <div class="alert error" style="margin-bottom:14px"><b>Error</b><p style="margin:4px 0 0">{error}</p></div>
  {/if}

  {#if phase === 'playing'}
    <div class="tr-array">
      {#each arr as val, i}
        {@const elim = !inBounds(i)}
        {@const iLow = isLow(i)}
        {@const iHigh = isHigh(i)}
        {@const visited = moves.some(m => m.mid === i)}
        {@const lastVisit = moves.filter(m => m.mid === i).pop()}
        {@const dirColor = lastVisit?.dir === 'left' ? 'var(--cyan)' : lastVisit?.dir === 'right' ? 'var(--orange)' : lastVisit?.dir === 'found' ? 'var(--green)' : elim ? 'transparent' : 'var(--line)'}
        <div class="tr-cell {elim ? 'tr-elim' : 'tr-in'} {iLow ? 'tr-low' : ''} {iHigh ? 'tr-high' : ''} {visited ? 'tr-visited' : ''}"
          style="border-color:{dirColor};opacity:{elim ? 0.3 : 1}">
          <span class="tr-idx" style="color:{iLow ? 'var(--cyan)' : iHigh ? 'var(--orange)' : '#555b64'}">{i}</span>
          <span class="tr-val" style="color:{elim ? '#3e424a' : '#f4f5f6'}">{val}</span>
          {#if iLow}<span class="tr-badge tr-b-low">LOW</span>{/if}
          {#if iHigh}<span class="tr-badge tr-b-high">HIGH</span>{/if}
          {#if visited && lastVisit && lastVisit.dir}
            <span class="tr-badge" style="background:{lastVisit.dir === 'found' ? 'rgba(73,224,149,0.2)' : lastVisit.dir === 'left' ? 'rgba(43,223,245,0.2)' : 'rgba(255,121,0,0.2)'};color:{lastVisit.dir === 'found' ? 'var(--green)' : lastVisit.dir === 'left' ? 'var(--cyan)' : 'var(--orange)'}">
              {lastVisit.dir === 'found' ? '★' : lastVisit.dir === 'left' ? '←' : '→'}
            </span>
          {/if}
        </div>
      {/each}
    </div>

    <div style="margin-top:14px;padding:12px 16px;background:#090b0f;border:1px solid var(--line);border-radius:8px">
      <div style="display:flex;flex-wrap:wrap;gap:8px;align-items:center;justify-content:space-between">
        <div>
          <span style="font:700 9px 'JetBrains Mono';letter-spacing:0.08em;color:#8d929b">CURRENT WINDOW: [{low} .. {high}]</span>
          {#if moves.length > 0}
            {@const last = moves[moves.length - 1]}
            <span style="margin-left:10px;font:700 9px 'JetBrains Mono';letter-spacing:0.08em;color:#ff5c3e">MID = {last.mid} (arr[{last.mid}] = {arr[last.mid]})</span>
          {/if}
        </div>
        <span style="font:700 9px 'JetBrains Mono';letter-spacing:0.08em;color:#666b74">TARGET = {target}</span>
      </div>
    </div>

    <div class="page-actions" style="margin-top:14px">
      <button class="btn primary big" on:click={pickMid} disabled={found} style="min-width:200px">
        {moves.length === 0 || moves[moves.length - 1]?.dir ? 'PICK MIDPOINT' : 'CONFIRM MIDPOINT'}
      </button>
    </div>

    {#if moves.length > 0 && moves[moves.length - 1]?.dir === '' && !found}
      <div class="page-actions" style="margin-top:10px">
        <span style="font:700 9px 'JetBrains Mono';letter-spacing:0.08em;color:#8d929b;margin-right:6px">CHOOSE DIRECTION:</span>
        <button class="btn ghost" on:click={() => chooseDirection('left')} disabled={phase !== 'playing'}>
          ← LEFT (target &lt; arr[mid])
        </button>
        <button class="btn ghost" on:click={() => chooseDirection('right')} disabled={phase !== 'playing'}>
          RIGHT (target &gt; arr[mid]) →
        </button>
        {#if arr[moves[moves.length - 1].mid] === target}
          <button class="btn primary" on:click={() => { chooseDirection('right'); }} disabled={phase !== 'playing'}>
            FOUND IT! ★
          </button>
        {/if}
      </div>
    {/if}

  {:else if phase === 'complete'}
    <div class="panel" style="margin-top:14px;text-align:center">
      <div style="background:linear-gradient(135deg,#ff321d,#ff7900);display:inline-block;padding:14px 28px;border-radius:10px;font:700 16px 'JetBrains Mono';color:#fff;box-shadow:0 12px 40px rgba(255,49,26,0.25)">
        TARGET FOUND: {target}
      </div>

      <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:16px;justify-content:center">
        <div class="tr-metric"><small>MOVES USED</small><strong class="mono">{moves.length}</strong></div>
        <div class="tr-metric"><small>CORRECT</small><strong class="mono" style="color:var(--green)">{roundResults.filter(r => r.correctMid && r.correctDir).length}</strong></div>
        <div class="tr-metric"><small>MISTAKES</small><strong class="mono" style="color:var(--red)">{roundResults.filter(r => !r.correctMid || !r.correctDir).length}</strong></div>
        <div class="tr-metric"><small>FINAL SCORE</small><strong class="mono" style="color:#ff5c3e">{Math.max(0, totalScore)}</strong></div>
      </div>

      <div style="margin-top:14px;text-align:left">
        <span style="font:700 9px 'JetBrains Mono';letter-spacing:0.08em;color:#8d929b;display:block;margin-bottom:8px">MOVE HISTORY</span>
        <div style="display:flex;flex-direction:column;gap:5px">
          {#each roundResults as result, i}
            <div style="display:flex;align-items:center;gap:8px;padding:7px 12px;background:#07090d;border:1px solid var(--line);border-radius:5px;font:700 10px 'JetBrains Mono'">
              <span style="color:#555b64;width:18px">M{i + 1}</span>
              <span style="color:#8d929b">mid={result.gotMid}{result.correctMid ? ' ✓' : ` ✗ (exp ${result.expectedMid})`}</span>
              <span style="color:{result.correctDir ? 'var(--green)' : 'var(--red)'};margin-left:auto">
                {result.correctDir ? `→ ${result.dir.toUpperCase()} ✓` : `✗ go ${result.expectedDir.toUpperCase()} (you chose ${result.dir})`}
              </span>
            </div>
          {/each}
        </div>
      </div>

      <div class="page-actions" style="margin-top:16px;justify-content:center">
        <button class="btn ghost" on:click={reset}>Retry</button>
        <button class="btn primary" on:click={finishChallenge} disabled={submitting}>
          {submitting ? 'Submitting...' : 'Finish challenge'}
        </button>
      </div>
    </div>
  {/if}

  {#if feedbackFlash}
    <div class="tr-feedback {feedbackFlash.type === 'ok' ? 'tr-fb-ok' : 'tr-fb-err'}" style="margin-top:12px;padding:10px 14px;border-radius:7px;font:700 11px 'JetBrains Mono';animation:fadeIn 0.2s ease">
      {feedbackFlash.text}
    </div>
  {/if}
</div>
