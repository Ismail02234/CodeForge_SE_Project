<script lang="ts">
  import { onMount } from 'svelte';
  import { page } from '$app/stores';
  import { api } from '$lib/api';
  import Loading from '$lib/components/Loading.svelte';
  import MidpointMaster from '$lib/play-games/midpoint-master.svelte';
  import TraceRace from '$lib/play-games/trace-race.svelte';
  import GameSelector from '$lib/play-games/game-selector.svelte';
  let data: any = null;
  let error = '';
  let loading = true;

  $: slug = ($page.params as any).slug;
  $: module = data?.module || null;
  $: steps = data?.learn_steps || [];
  $: challenges = data?.play_challenges || [];
  $: proveProblems = data?.prove_problems || [];
  $: progress = data?.progress || null;

  $: totalLearnXp = data?.total_learn_xp ?? steps.reduce((sum: number, s: any) => sum + (s.xp_reward || 0), 0);
  $: earnedLearnXp = progress?.learn_score || 0;
  $: learnPct = data?.learn_pct ?? (totalLearnXp > 0 ? Math.min(100, Math.round((earnedLearnXp / totalLearnXp) * 100)) : 0);
  $: learnCompleted = progress?.learn_completed || false;

  $: totalPlayXp = data?.total_play_xp ?? challenges.reduce((sum: number, c: any) => sum + (c.xp_reward || 0), 0);
  $: earnedPlayXp = Object.values(challengeScores).reduce((sum: number, s: number) => sum + s, 0);
  $: playPct = data?.play_pct ?? (totalPlayXp > 0 ? Math.min(100, Math.round((earnedPlayXp / totalPlayXp) * 100)) : 0);
  $: playCompleted = challenges.length > 0 && Object.keys(challengeScores).length === challenges.length;
  $: bestPlayScore = Math.max(...Object.values(challengeScores), 0);

  $: totalProveXp = data?.total_prove_xp ?? proveProblems.reduce((sum: number, p: any) => sum + (p.xp_reward || 0), 0);
  $: earnedProveXp = progress?.prove_score || 0;
  $: provePct = data?.prove_pct ?? (totalProveXp > 0 ? Math.min(100, Math.round((earnedProveXp / totalProveXp) * 100)) : 0);
  $: proveCompleted = progress?.prove_completed || false;

  $: masteryPct = data?.mastery_pct ?? 0;
  $: moduleCompleted = data?.module_completed ?? false;

  let gamification: { xp: number; level: string; progress: number } | null = null;

  let stage: 'learn' | 'play' | 'prove' = 'learn';

  // Learn stage state
  let currentStep = 0;
  let answer = '';
  let feedback: { correct: boolean; xp: number; already_completed?: boolean } | null = null;
  let submitting = false;

  // Play stage state
  let currentChallenge = 0;
  let challengeScores: Record<string, number> = {};
  let challengeFeedback: { score: number; xp: number } | null = null;
  let playSubmitting = false;
  let gameSelectorOpen = !playCompleted;
  let midpointMasterRef: any = null;
  let traceRaceRef: any = null;

  // fill_blank state
  let blankValues: string[] = [];

  // coding state
  let codingAnswers: Array<{ mid: string; next: string }> = [];

  // trace state
  let traceState: {
    array: number[];
    target: number;
    low: number;
    high: number;
    movesUsed: number;
    found: boolean;
    history: Array<{ mid: number; result: string }>;
  } | null = null;

  $: step = steps[currentStep];
  $: hasOptions = step && Array.isArray(step.options) && step.options.length > 0;
  $: allDone = currentStep >= steps.length && steps.length > 0;
  $: currentChallengeData = challenges[currentChallenge];
  $: completedStepIds = new Set(data?.learn_completed_steps ?? progress?.learn_completed_steps ?? []);

  $: fillBlankSegments = (() => {
    if (!currentChallengeData || currentChallengeData.type !== 'fill_blank') return [];
    const starter = (currentChallengeData.config || {}).starter || '';
    const segments: Array<{ type: 'text'; value: string } | { type: 'input'; blankIndex: number } | { type: 'newline' }> = [];
    let blankIndex = 0;
    const lines = starter.split('\n');
    lines.forEach((line: string, lineIdx: number, arr: string[]) => {
      line.split('______').forEach((part: string, partIdx: number, parts: string[]) => {
        segments.push({ type: 'text', value: part });
        if (partIdx < parts.length - 1) {
          segments.push({ type: 'input', blankIndex: blankIndex++ });
        }
      });
      if (lineIdx < arr.length - 1) {
        segments.push({ type: 'newline' });
      }
    });
    return segments;
  })();

  function initFillBlank(challenge: any) {
    const config = challenge.config || {};
    const starter = config.starter || '';
    const blanks = config.blanks || [];
    blankValues = Array(blanks.length).fill('');
    challengeFeedback = null;
  }

  function initCoding(challenge: any) {
    const config = challenge.config || {};
    const steps = config.steps || [];
    codingAnswers = steps.map(() => ({ mid: '', next: '' }));
    challengeFeedback = null;
  }

  function initTrace(challenge: any) {
    const config = challenge.config || {};
    const length = config.array_length || 15;
    const seed = challenge.id.split('').reduce((a: number, c: string) => a + c.charCodeAt(0), 0);
    const arr: number[] = [];
    let val = 10 + (seed % 50);
    for (let i = 0; i < length; i++) {
      arr.push(val);
      val += 3 + ((seed * (i + 1)) % 5);
    }
    const targetIndex = seed % length;
    traceState = {
      array: arr,
      target: arr[targetIndex],
      low: 0,
      high: length - 1,
      movesUsed: 0,
      found: false,
      history: [],
    };
    challengeFeedback = null;
  }

  // binary_search state
  let bsState: {
    array: number[];
    target: number;
    low: number;
    high: number;
    mid: number;
    movesUsed: number;
    found: boolean;
    history: Array<{ mid: number; midVal: number; direction: string; correct: boolean }>;
    score: number;
    mistakes: number;
    phase: 'playing' | 'feedback' | 'complete';
    feedbackDir: string | null;
    feedbackCorrect: boolean;
  } | null = null;

  function initBinarySearch(challenge: any) {
    const config = challenge.config || {};
    const arr = config.array || [];
    const target = config.target || arr[Math.floor(arr.length / 2)] || 0;
    const low = 0;
    const high = arr.length - 1;
    const mid = arr.length > 0 ? Math.floor((low + high) / 2) : 0;
    bsState = {
      array: arr,
      target,
      low,
      high,
      mid,
      movesUsed: 0,
      found: false,
      history: [],
      score: 0,
      mistakes: 0,
      phase: 'playing',
      feedbackDir: null,
      feedbackCorrect: false,
    };
    challengeFeedback = null;
  }

  function bsPickDirection(dir: 'left' | 'right') {
    if (!bsState || bsState.phase !== 'playing' || bsState.found) return;
    const state = bsState;
    if (state.target === state.array[state.mid]) {
      bsState = {
        ...state,
        found: true,
        phase: 'complete',
        movesUsed: state.movesUsed + 1,
      };
      return;
    }
    const midVal = state.array[state.mid];
    const correctDir = state.target > midVal ? 'right' : 'left';
    const isCorrect = dir === correctDir;
    const newHistory = [
      ...state.history,
      { mid: state.mid, midVal, direction: dir, correct: isCorrect },
    ];
    const newScore = isCorrect ? state.score + 100 : state.score - 25;
    const newMistakes = isCorrect ? state.mistakes : state.mistakes + 1;

    bsState = {
      ...state,
      history: newHistory,
      score: Math.max(0, newScore),
      mistakes: newMistakes,
      phase: 'feedback',
      feedbackDir: dir,
      feedbackCorrect: isCorrect,
    };

    setTimeout(() => {
      if (!bsState) return;
      const s = bsState;
      let newLow = s.low;
      let newHigh = s.high;
      if (correctDir === 'left') {
        newHigh = s.mid - 1;
      } else {
        newLow = s.mid + 1;
      }
      const found = newLow <= newHigh && s.target === s.array[Math.floor((newLow + newHigh) / 2)];
      const newMid = newLow <= newHigh ? Math.floor((newLow + newHigh) / 2) : s.mid;
      bsState = {
        ...s,
        low: newLow,
        high: newHigh,
        mid: newMid,
        movesUsed: s.movesUsed + 1,
        found,
        phase: found ? 'complete' : 'playing',
        feedbackDir: null,
        feedbackCorrect: false,
      };
    }, 700);
  }

  async function submitBinarySearch() {
    if (!currentChallengeData || playSubmitting || !bsState) return;
    playSubmitting = true;
    challengeFeedback = null;
    error = '';
    try {
      const directions = bsState.history.map(h => h.direction);
      if (bsState.found) {
        directions.push('found');
      }
      const action = JSON.stringify(directions);
      const res = await api.post<{ score: number; xp_reward: number; play_score: number; play_pct: number; play_completed: boolean }>(`/api/learn/${slug}/play`, {
        challenge_id: currentChallengeData.id,
        action,
      });
      challengeFeedback = { score: res.score, xp: res.xp_reward };
      challengeScores[currentChallengeData.id] = res.score;
      if (progress) {
        data.progress.play_score = res.play_score;
      }
    } catch (e: any) {
      error = e.message;
    } finally {
      playSubmitting = false;
    }
  }

  function bsRetry() {
    challengeFeedback = null;
    const c = challenges[currentChallenge];
    if (c.type === 'binary_search') initBinarySearch(c);
  }
  function selectChallenge(index: number) {
    if (playSubmitting || challengeFeedback) return;
    currentChallenge = index;
    challengeFeedback = null;
    gameSelectorOpen = false;
    const c = challenges[index];
    if (c.type === 'fill_blank') initFillBlank(c);
    else if (c.type === 'coding') initCoding(c);
    else if (c.type === 'trace') initTrace(c);
    else if (c.type === 'binary_search') initBinarySearch(c);
  }

  function retryChallenge() {
    challengeFeedback = null;
    const c = challenges[currentChallenge];
    if (c.type === 'fill_blank') initFillBlank(c);
    else if (c.type === 'coding') initCoding(c);
    else if (c.type === 'trace') initTrace(c);
    else if (c.type === 'binary_search') initBinarySearch(c);
    else if (c.type === 'midpoint_master') midpointMasterRef?.retryGame?.();
    else if (c.type === 'trace_race') traceRaceRef?.retryGame?.();
  }
  function openGameSelector() {
    gameSelectorOpen = !playCompleted;
    challengeFeedback = null;
  }

  onMount(async () => {
    try {
      data = await api.get(`/api/learn/${slug}`);
      if (data?.progress?.learn_completed) {
      }
      if (progress?.play_score) {
        bestPlayScore = progress.play_score;
      }
      try {
        gamification = await api.get('/api/gamification');
      } catch {
      }
    } catch (e: any) {
      error = e.message;
    } finally {
      loading = false;
    }
  });

  async function retryLoad() {
    loading = true;
    error = '';
    try {
      data = await api.get(`/api/learn/${slug}`);
      if (data?.progress?.learn_completed) {
      }
      if (progress?.play_score) {
        bestPlayScore = progress.play_score;
      }
      try {
        gamification = await api.get('/api/gamification');
      } catch {
      }
    } catch (e: any) {
      error = e.message;
    } finally {
      loading = false;
    }
  }

  async function submitAnswer() {
    if (!step || submitting) return;
    submitting = true;
    feedback = null;
    error = '';
    try {
      const res = await api.post<{ correct: boolean; xp_reward: number; learn_score: number; learn_pct: number; learn_completed: boolean; already_completed: boolean }>(`/api/learn/${slug}/learn`, {
        step_id: step.id,
        answer: answer.trim(),
      });
      feedback = { correct: res.correct, xp: res.xp_reward };
      if (res.correct) {
        earnedLearnXp = res.learn_score;
        if (progress) {
          data.progress.learn_score = res.learn_score;
        }
        if (!completedStepIds.has(step.id)) {
          completedStepIds = new Set([...completedStepIds, step.id]);
        }
      }
    } catch (e: any) {
      error = e.message;
    } finally {
      submitting = false;
    }
  }

  function pickOption(key: string) {
    if (feedback || submitting) return;
    answer = key;
    submitAnswer();
  }

  function nextStep() {
    feedback = null;
    answer = '';
    if (currentStep < steps.length - 1) {
      currentStep++;
    }
  }

  function goToStep(index: number) {
    if (submitting) return;
    feedback = null;
    answer = '';
    currentStep = index;
  }

  async function submitFillBlank() {
    if (!currentChallengeData || playSubmitting) return;
    playSubmitting = true;
    challengeFeedback = null;
    error = '';
    try {
      const action = blankValues.join('\n');
      const res = await api.post<{ score: number; xp_reward: number; play_score: number; play_pct: number; play_completed: boolean }>(`/api/learn/${slug}/play`, {
        challenge_id: currentChallengeData.id,
        action,
      });
      challengeFeedback = { score: res.score, xp: res.xp_reward };
      challengeScores[currentChallengeData.id] = res.score;
      if (progress) {
        data.progress.play_score = res.play_score;
      }
    } catch (e: any) {
      error = e.message;
    } finally {
      playSubmitting = false;
    }
  }

  async function submitCoding() {
    if (!currentChallengeData || playSubmitting) return;
    playSubmitting = true;
    challengeFeedback = null;
    error = '';
    try {
      const action = JSON.stringify(codingAnswers.map(a => ({ mid: a.mid ? parseInt(a.mid) : null, next: a.next })));
      const res = await api.post<{ score: number; xp_reward: number; play_score: number; play_pct: number; play_completed: boolean }>(`/api/learn/${slug}/play`, {
        challenge_id: currentChallengeData.id,
        action,
      });
      challengeFeedback = { score: res.score, xp: res.xp_reward };
      challengeScores[currentChallengeData.id] = res.score;
      if (progress) {
        data.progress.play_score = res.play_score;
      }
    } catch (e: any) {
      error = e.message;
    } finally {
      playSubmitting = false;
    }
  }

  function tracePickMid(index: number) {
    if (!traceState || traceState.found || traceState.movesUsed >= (currentChallengeData?.config?.max_moves || 4)) return;
    const val = traceState.array[index];
    const newHistory = [...traceState.history, { mid: index, result: '' }];
    traceState = {
      ...traceState,
      history: newHistory,
      movesUsed: traceState.movesUsed + 1,
    };
  }

  function traceDirection(dir: 'low' | 'high' | 'found') {
    const state = traceState;
    if (!state || !state.history.length) return;
    const last = state.history[state.history.length - 1];
    const result = dir === 'found' ? 'found' : dir;
    const newHistory = state.history.map((h, i) => i === state.history.length - 1 ? { ...h, result } : h);

    if (dir === 'found') {
      traceState = { ...state, history: newHistory, found: true };
    } else {
      const newLow = dir === 'high' ? state.low : last.mid + 1;
      const newHigh = dir === 'low' ? state.high : last.mid - 1;
      traceState = { ...state, history: newHistory, low: newLow, high: newHigh };
    }
  }

  async function submitTrace() {
    if (!currentChallengeData || playSubmitting || !traceState) return;
    playSubmitting = true;
    challengeFeedback = null;
    error = '';
    try {
      const action = JSON.stringify({ moves: traceState.movesUsed, found: traceState.found });
      const res = await api.post<{ score: number; xp_reward: number; play_score: number; play_pct: number; play_completed: boolean }>(`/api/learn/${slug}/play`, {
        challenge_id: currentChallengeData.id,
        action,
      });
      challengeFeedback = { score: res.score, xp: res.xp_reward };
      challengeScores[currentChallengeData.id] = res.score;
      if (progress) {
        data.progress.play_score = res.play_score;
      }
    } catch (e: any) {
      error = e.message;
    } finally {
      playSubmitting = false;
    }
  }

  function switchStage(s: 'learn' | 'play' | 'prove') {
    stage = s;
    if (s === 'play') {
      gameSelectorOpen = !playCompleted;
      challengeFeedback = null;
    }
  }

  function handleKeydown(e: KeyboardEvent) {
    if (e.target instanceof HTMLInputElement || e.target instanceof HTMLTextAreaElement || e.target instanceof HTMLSelectElement) {
      return;
    }
    if (e.key === '1') switchStage('learn');
    else if (e.key === '2') switchStage('play');
    else if (e.key === '3') switchStage('prove');
  }
</script>

<svelte:head><title>{module?.title || 'Module'} Â· CodeForge</title></svelte:head>

{#if loading}
  <Loading />
{:else if error && !data}
  <div class="alert error">
    <b>Failed to load module.</b>
    <p style="margin:6px 0 0">{error}</p>
    <button class="btn ghost" style="margin-top:10px" on:click={retryLoad}>Retry</button>
  </div>
{:else if module}
  {#if error}
    <div class="alert error" style="margin-bottom:14px">
      <b>Something went wrong.</b>
      <p style="margin:6px 0 0">{error}</p>
      <button class="btn ghost" style="margin-top:10px" on:click={retryLoad}>Retry</button>
    </div>
  {/if}
  <div class="page-head">
    <div>
      <span class="eyebrow">LEARNING PATH</span>
      <h1>{module.title}</h1>
      <p>{module.description}</p>
      <div class="module-meta-row">
        <span class={`pill ${module.difficulty.toLowerCase()}`}>{module.difficulty}</span>
        <span>{(module.topic || 'General').toUpperCase()}</span>
        <span>â± {module.estimated_minutes} min</span>
        <span>+{module.xp_reward} XP</span>
      </div>
      {#if gamification}
        <div class="xp-badge-row" style="display:flex;align-items:center;gap:10px;margin-top:8px;flex-wrap:wrap">
          <span class="xp-badge">YOUR XP</span>
          <strong class="xp-badge">{gamification.xp}</strong>
          <span class="pill active" style="font:600 8px 'JetBrains Mono';letter-spacing:0.06em">{gamification.level}</span>
          <div class="learn-progress-bar" style="width:120px;margin-top:0">
            <span style="width:{gamification.progress}%"></span>
          </div>
        </div>
      {/if}
      <div class="mastery-grid">
        <div>
          <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px">
            <span style="font:700 9px 'JetBrains Mono';letter-spacing:0.08em;color:#8d929b">LEARN</span>
            <span style="font:700 9px 'JetBrains Mono';letter-spacing:0.08em;color:{learnCompleted ? 'var(--green)' : '#8d929b'}">{learnPct}%</span>
          </div>
          <div class="learn-progress-bar"><span style="width:{learnPct}%"></span></div>
        </div>
        <div>
          <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px">
            <span style="font:700 9px 'JetBrains Mono';letter-spacing:0.08em;color:#8d929b">PLAY</span>
            <span style="font:700 9px 'JetBrains Mono';letter-spacing:0.08em;color:{playCompleted ? 'var(--green)' : '#8d929b'}">{playPct}%</span>
          </div>
          <div class="learn-progress-bar"><span style="width:{playPct}%"></span></div>
        </div>
        <div>
          <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px">
            <span style="font:700 9px 'JetBrains Mono';letter-spacing:0.08em;color:#8d929b">PROVE</span>
            <span style="font:700 9px 'JetBrains Mono';letter-spacing:0.08em;color:{proveCompleted ? 'var(--green)' : '#8d929b'}">{provePct}%</span>
          </div>
          <div class="learn-progress-bar"><span style="width:{provePct}%"></span></div>
        </div>
        <div style="border-top:1px solid var(--line);padding-top:10px;margin-top:2px">
          <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px">
            <span style="font:700 9px 'JetBrains Mono';letter-spacing:0.08em;color:#ff5c3e">OVERALL MASTERY</span>
            <span style="font:700 11px 'JetBrains Mono';letter-spacing:0.08em;color:#ff5c3e">{masteryPct}%</span>
          </div>
          <div class="learn-progress-bar"><span style="width:{masteryPct}%;background:linear-gradient(90deg, var(--red), var(--orange))"></span></div>
        </div>
      </div>
      <div class="stage-pills" style="display:flex;gap:8px;margin-top:10px;flex-wrap:wrap">
        <span class="pill {learnCompleted ? 'active' : ''}" style="font:600 8px 'JetBrains Mono';letter-spacing:0.06em">{learnCompleted ? 'âœ“' : 'â—‹'} Learn</span>
        <span class="pill {playCompleted ? 'active' : ''}" style="font:600 8px 'JetBrains Mono';letter-spacing:0.06em">{playCompleted ? 'âœ“' : 'â—‹'} Play</span>
        <span class="pill {proveCompleted ? 'active' : ''}" style="font:600 8px 'JetBrains Mono';letter-spacing:0.06em">{proveCompleted ? 'âœ“' : 'â—‹'} Prove</span>
        {#if moduleCompleted}
          <span class="pill won" style="font:600 8px 'JetBrains Mono';letter-spacing:0.06em">â˜… Mastered</span>
        {/if}
      </div>
    </div>
  </div>

  <div class="learn-stage-track" role="tablist" aria-label="Learning stages" tabindex="0" on:keydown={handleKeydown}>
    <button
      id="learn-tab"
      class="learn-stage-step {stage === 'learn' ? 'active' : ''} {learnCompleted && stage !== 'learn' ? 'done' : ''}"
      on:click={() => switchStage('learn')}
      role="tab"
      aria-selected={stage === 'learn'}
      aria-controls="stage-panel"
      type="button"
    >LEARN</button>
    <button
      id="play-tab"
      class="learn-stage-step {stage === 'play' ? 'active' : ''} {playCompleted && stage !== 'play' ? 'done' : ''}"
      on:click={() => switchStage('play')}
      role="tab"
      aria-selected={stage === 'play'}
      aria-controls="stage-panel"
      type="button"
    >PLAY</button>
    <button
      id="prove-tab"
      class="learn-stage-step {stage === 'prove' ? 'active' : ''} {proveCompleted && stage !== 'prove' ? 'done' : ''}"
      on:click={() => switchStage('prove')}
      role="tab"
      aria-selected={stage === 'prove'}
      aria-controls="stage-panel"
      type="button"
    >PROVE</button>
  </div>

  {#if stage === 'learn'}
    <div id="stage-panel" role="tabpanel" aria-labelledby="learn-tab" class="stage-panel">
      <div class="learn-step-layout">
      <aside class="learn-nav">
        {#each steps as s, i}
          <button
            class="learn-nav-item {i === currentStep ? 'active' : ''} {(feedback && i === currentStep) || completedStepIds.has(s.id) ? 'done' : ''}"
            on:click={() => goToStep(i)}
            disabled={submitting}
            aria-current={i === currentStep ? 'step' : undefined}
          >
            <b>Step {i + 1}</b>
            <small>{s.title} Â· +{s.xp_reward} XP</small>
          </button>
        {/each}
      </aside>

      <section class="panel">
        {#if step}
          <div class="panel-head">
            <h2>{step.title}</h2>
            <span class="eyebrow">{step.type.toUpperCase()} Â· STEP {currentStep + 1} / {steps.length}</span>
          </div>

          {#if step.content}
            <p style="white-space:pre-wrap;line-height:1.7">{step.content}</p>
          {/if}

          {#if step.question}
            <h3 style="margin:18px 0 8px">{step.question}</h3>

            {#if hasOptions}
              <div class="learn-options">
                {#each step.options as option, i}
                  {@const key = String.fromCharCode(65 + i)}
                  {@const isCorrect = feedback && key === step.correct_answer}
                  {@const isWrong = feedback && answer === key && !feedback.correct}
                  <button
                    class="learn-option {isCorrect ? 'correct-pick' : ''} {isWrong ? 'wrong-pick' : ''}"
                    on:click={() => pickOption(key)}
                    disabled={!!feedback || submitting}
                  >
                    {key}. {option}
                  </button>
                {/each}
              </div>
            {:else}
              <input
                bind:value={answer}
                placeholder="Type your answer..."
                disabled={!!feedback || submitting}
                on:keydown={(e) => e.key === 'Enter' && !feedback && submitAnswer()}
              />
              <div class="page-actions" style="margin-top:10px">
                <button class="btn primary" on:click={submitAnswer} disabled={!answer.trim() || !!feedback || submitting}>
                  {submitting ? 'Checking...' : 'Submit answer'}
                </button>
              </div>
            {/if}
          {/if}

          {#if feedback}
            {#if feedback.correct}
              <div class="step-feedback success">
                {#if feedback.already_completed}
                  <b>Already correct.</b> No additional XP for repeating this step.
                {:else}
                  <div class="xp-pop">+{feedback.xp} XP</div>
                  <b>Correct!</b> Well done.
                {/if}
              </div>
            {:else}
              <div class="step-feedback error">
                <b>Not quite.</b> Review the material above and try again.
              </div>
            {/if}
            {#if !allDone}
              <div class="page-actions" style="margin-top:10px">
                <button class="btn primary" on:click={nextStep}>Next step â†’</button>
              </div>
            {/if}
          {:else if !step.question}
            <div class="page-actions" style="margin-top:14px">
              <button class="btn primary" on:click={nextStep} disabled={allDone}>
                {allDone ? 'Learn complete' : 'Next step â†’'}
              </button>
            </div>
          {/if}
        {:else if allDone}
          <div class="empty">
            <div class="empty-mark">âœ“</div>
            <h2>Learn stage complete</h2>
            <p>You earned {earnedLearnXp} XP in this module.</p>
          </div>
    </div>
  </div>
{/if}

{#if stage === 'play'}
  <div id="stage-panel" role="tabpanel" aria-labelledby="play-tab" class="stage-panel">
    {#if playCompleted}
        <div class="panel">
          <div class="empty" style="padding:40px">
            <div class="empty-mark">âœ“</div>
            <h2 style="margin:10px 0 6px">PLAY COMPLETE</h2>
            <p>You've mastered the Play stage! +{bestPlayScore} XP earned.</p>
            <p style="margin-top:8px;font-size:12px;color:#8d929b">You're ready to prove your skills.</p>
            {#if proveProblems.length > 0}
              <a class="btn primary" style="margin-top:14px" href={`/problems/${proveProblems[0].problem_id}?learning_module=${slug}`}>
                Start Prove Problems â†’
              </a>
            {/if}
          </div>
        </div>
      {:else if gameSelectorOpen}
        <div class="panel">
          <div class="panel-head">
            <h2>Choose a Game</h2>
            <span class="eyebrow">{challenges.length} GAMES Â· +{totalPlayXp} XP TOTAL</span>
          </div>
          <p style="margin:0 0 16px;font-size:12px;color:#8d929b">Select a minigame to begin. Complete all {challenges.length} games to finish the Play stage.</p>

          <div style="display:flex;flex-direction:column;gap:10px">
            {#each challenges as c, i}
              {@const done = !!challengeScores[c.id]}
              {@const score = challengeScores[c.id] ?? 0}
              {@const isBS = c.type === 'binary_search'}
              {@const isMM = c.type === 'midpoint_master'}
              {@const isTR = c.type === 'trace_race'}
              {@const accentColor = isBS ? '#ff321d' : isMM ? '#2bdff5' : isTR ? '#ff7900' : '#ffc268'}
              {@const conceptLabel = isBS ? 'LEFT / RIGHT DECISIONS' : isMM ? 'MIDPOINT FORMULA' : isTR ? 'FULL BINARY SEARCH TRACE' : c.type.toUpperCase()}
              {@const icon = isBS ? 'âŠ•' : isMM ? 'Ã·' : isTR ? 'âŸ³' : '?'}
              {@const difficulty = c.type === 'midpoint_master' ? 'Easy' : c.type === 'trace_race' ? 'Medium' : 'Medium'}
              {@const est = isMM ? 5 : isTR ? 8 : 6}
              <button
                class="game-card-btn"
                on:click={() => selectChallenge(i)}
                disabled={done || playSubmitting}
                style="
                  display:flex;align-items:center;gap:14px;padding:14px 16px;
                  background:#090b0f;border:1px solid {done ? 'rgba(73,224,149,0.25)' : 'var(--line)'};
                  border-radius:8px;cursor:{done ? 'default' : 'pointer'};
                  text-align:left;width:100%;transition:0.15s ease;opacity:{done ? 0.75 : 1};
                "
              >
                <div style="
                  width:44px;height:44px;border-radius:7px;
                  background:{done ? 'rgba(73,224,149,0.1)' : accentColor + '15'};
                  border:1px solid {done ? 'rgba(73,224,149,0.3)' : accentColor + '33'};
                  display:flex;align-items:center;justify-content:center;
                  font:700 18px 'JetBrains Mono';color:{done ? 'var(--green)' : accentColor};flex-shrink:0;
                ">
                  {done ? 'âœ“' : icon}
                </div>
                <div style="flex:1;min-width:0">
                  <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
                    <b style="font-size:13px;font-weight:700;font-family:'JetBrains Mono'">{c.title}</b>
                    <span class="pill {difficulty.toLowerCase()}" style="font:700 7px 'JetBrains Mono';letter-spacing:0.08em">{difficulty.toUpperCase()}</span>
                    {#if done}<span class="pill active" style="font:700 7px 'JetBrains Mono';letter-spacing:0.08em">+{score} XP</span>{/if}
                  </div>
                  <p style="margin:5px 0 0;font-size:11px;color:#8d929b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{c.instructions}</p>
                  <div style="display:flex;gap:10px;margin-top:6px;flex-wrap:wrap">
                    <span style="font:600 8px 'JetBrains Mono';color:#666b74;letter-spacing:0.08em">CONCEPT: {conceptLabel}</span>
                    <span style="font:600 8px 'JetBrains Mono';color:#666b74;letter-spacing:0.08em">â± {est} MIN</span>
                    <span style="font:600 8px 'JetBrains Mono';color:#666b74;letter-spacing:0.08em">+{c.xp_reward} XP</span>
                  </div>
                </div>
                <div style="flex-shrink:0;font:700 11px 'JetBrains Mono';color:{done ? 'var(--green)' : '#555b64'}">
                  {done ? 'DONE' : 'START â†’'}
                </div>
              </button>
            {/each}
          </div>
        </div>
       {:else}
        <div class="panel">
         <div class="learn-step-layout">
        <aside class="learn-nav">
          {#each challenges as c, i}
            <button
              class="learn-nav-item {i === currentChallenge ? 'active' : ''} {challengeScores[c.id] ? 'done' : ''}"
              on:click={() => selectChallenge(i)}
              disabled={playSubmitting}
            >
              <b>{c.title}</b>
              <small>{c.type} Â· +{c.xp_reward} XP {challengeScores[c.id] ? `Â· ${challengeScores[c.id]} XP` : ''}</small>
            </button>
          {/each}
          <button class="learn-nav-item" on:click={openGameSelector} style="margin-top:8px;border-style:dashed;color:#8d929b">
            <b>â† Back to games</b>
          </button>
        </aside>

        <section class="panel">
          {#if currentChallengeData}
            <div class="panel-head">
              <h2>{currentChallengeData.title}</h2>
              <span class="eyebrow">{currentChallengeData.type.toUpperCase()} Â· +{currentChallengeData.xp_reward} XP</span>
            </div>

            <p style="margin:0 0 12px">{currentChallengeData.instructions}</p>

            {#if currentChallengeData.type === 'midpoint_master'}
              <MidpointMaster
                bind:this={midpointMasterRef}
                challenge={currentChallengeData}
                slug={slug}
                onComplete={(score, xp) => {
                  challengeFeedback = { score, xp };
                  challengeScores[currentChallengeData.id] = score;
                  if (progress) data.progress.play_score = Math.max((progress?.play_score ?? 0), score);
                }}
              />

            {:else if currentChallengeData.type === 'trace_race'}
              <TraceRace
                bind:this={traceRaceRef}
                challenge={currentChallengeData}
                slug={slug}
                onComplete={(score, xp) => {
                  challengeFeedback = { score, xp };
                  challengeScores[currentChallengeData.id] = score;
                  if (progress) data.progress.play_score = Math.max((progress?.play_score ?? 0), score);
                }}
              />

            {:else if currentChallengeData.type === 'binary_search'}
             {@const config = currentChallengeData.config || {}}
             {#if bsState}
               {@const arr = bsState.array}
               {@const target = bsState.target}
               {@const low = bsState.low}
               {@const high = bsState.high}
               {@const mid = bsState.mid}
               {@const inBounds = (i: number) => i >= low && i <= high}
               {@const isLow = (i: number) => i === low}
               {@const isHigh = (i: number) => i === high}
               {@const isMid = (i: number) => i === mid}
               {@const eliminated = (i: number) => i < low || i > high}
               {@const lastHistory = bsState.history.length > 0 ? bsState.history[bsState.history.length - 1] : null}

               <div class="bs-header" style="display:flex;flex-wrap:wrap;gap:12px;margin-bottom:16px;align-items:center">
                 <div class="bs-target-badge" style="background:linear-gradient(135deg,#ff321d,#ff7900);padding:8px 16px;border-radius:8px;font:700 13px 'JetBrains Mono';color:#fff">
                   TARGET = {target}
                 </div>
                 <div class="bs-metric">
                   <small>LOW</small>
                   <strong class="mono">{low}</strong>
                 </div>
                 <div class="bs-metric">
                   <small>HIGH</small>
                   <strong class="mono">{high}</strong>
                 </div>
                 <div class="bs-metric">
                   <small>MID</small>
                   <strong class="mono" style="color:#ff5c3e">{mid}</strong>
                 </div>
                 <div class="bs-metric">
                   <small>arr[mid]</small>
                   <strong class="mono">{arr[mid]}</strong>
                 </div>
                 <div class="bs-metric">
                   <small>SCORE</small>
                   <strong class="mono" style="color:var(--green)">{bsState.score}</strong>
                 </div>
                 <div class="bs-metric">
                   <small>MISTAKES</small>
                   <strong class="mono" style="color:var(--red)">{bsState.mistakes}</strong>
                 </div>
               </div>

               {#if bsState.phase === 'playing' && !bsState.found}
                 <div style="margin-bottom:10px;font:600 11px 'JetBrains Mono';letter-spacing:0.08em;color:#8d929b">
                   CURRENT WINDOW: [{low} .. {high}] &nbsp;|&nbsp; MID = {mid} &nbsp;|&nbsp; arr[{mid}] = {arr[mid]} &nbsp;|&nbsp; target = {target}
                 </div>
                 <div class="bs-array">
                   {#each arr as val, i}
                     {@const inB = inBounds(i)}
                     {@const elim = eliminated(i)}
                     {@const isLo = isLow(i)}
                     {@const isHi = isHigh(i)}
                     {@const isMi = isMid(i)}
                     {@const wasMid = bsState.history.some(h => h.mid === i)}
                     {@const lastForIdx = bsState.history.filter(h => h.mid === i).pop()}
                     <div class="bs-cell {inB ? 'in-bounds' : ''} {elim ? 'eliminated' : ''} {isLo ? 'is-low' : ''} {isHi ? 'is-high' : ''} {isMi ? 'is-mid' : ''} {wasMid ? 'was-mid' : ''}"
                          class:correct-pick={lastForIdx?.correct}
                          class:wrong-pick={lastForIdx && !lastForIdx.correct}>
                       <span class="bs-idx">{i}</span>
                       <span class="bs-val">{val}</span>
                       {#if isLo}<span class="bs-badge low-badge">LOW</span>{/if}
                       {#if isHi}<span class="bs-badge high-badge">HIGH</span>{/if}
                       {#if isMi}<span class="bs-badge mid-badge">MID</span>{/if}
                       {#if lastForIdx && !isMi}
                         <span class="bs-arrow">{lastForIdx.direction === 'left' ? 'â†' : 'â†’'}</span>
                       {/if}
                     </div>
                   {/each}
                 </div>

                 {#if lastHistory && bsState.phase === 'feedback'}
                   <div class="bs-feedback {lastHistory.correct ? 'correct' : 'wrong'}" style="margin-top:12px;padding:10px 14px;border-radius:7px;font:700 12px 'JetBrains Mono'">
                     {#if lastHistory.correct}
                       <span style="color:var(--green)">+100</span> &nbsp; CORRECT &nbsp;|&nbsp; arr[{lastHistory.mid}] = {lastHistory.midVal} &nbsp;â†’&nbsp; target {target > lastHistory.midVal ? '>' : '<'} {lastHistory.midVal} &nbsp;=&gt;&nbsp; SEARCH {lastHistory.direction.toUpperCase()}
                     {:else}
                       <span style="color:var(--red)">-25</span> &nbsp; MISTAKE &nbsp;|&nbsp; You chose {lastHistory.direction.toUpperCase()}, but target {target} {target > lastHistory.midVal ? '>' : '<'} {lastHistory.midVal} = arr[{lastHistory.mid}] &nbsp;=&gt;&nbsp; should be {target > lastHistory.midVal ? 'RIGHT' : 'LEFT'}
                     {/if}
                   </div>
                 {/if}

                 <div class="page-actions" style="margin-top:16px">
                   <button class="btn ghost big" on:click={() => bsPickDirection('left')} disabled={bsState.phase !== 'playing'}>
                     SEARCH LEFT
                   </button>
                   <button class="btn primary big" on:click={() => bsPickDirection('right')} disabled={bsState.phase !== 'playing'}>
                     SEARCH RIGHT
                   </button>
                 </div>

               {:else if bsState.found || bsState.phase === 'complete'}
                 <div class="bs-found" style="margin-top:14px">
                   <div class="bs-array" style="opacity:0.35">
                     {#each arr as val, i}
                       <div class="bs-cell eliminated">
                         <span class="bs-val">{val}</span>
                       </div>
                     {/each}
                   </div>
                   <div style="position:relative;margin-top:-160px;margin-bottom:140px;text-align:center">
                     <div style="background:linear-gradient(135deg,#ff321d,#ff7900);display:inline-block;padding:14px 28px;border-radius:10px;font:700 16px 'JetBrains Mono';color:#fff;box-shadow:0 12px 40px rgba(255,49,26,0.25)">
                       TARGET FOUND: {target}
                     </div>
                   </div>
                   <div class="bs-summary" style="display:flex;gap:12px;flex-wrap:wrap;margin-top:16px">
                     <div class="bs-metric">
                       <small>MOVES USED</small>
                       <strong class="mono">{bsState.movesUsed}</strong>
                     </div>
                     <div class="bs-metric">
                       <small>CORRECT</small>
                       <strong class="mono" style="color:var(--green)">{bsState.history.filter(h => h.correct).length}</strong>
                     </div>
                     <div class="bs-metric">
                       <small>MISTAKES</small>
                       <strong class="mono" style="color:var(--red)">{bsState.mistakes}</strong>
                     </div>
                     <div class="bs-metric">
                       <small>ROUND SCORE</small>
                       <strong class="mono" style="color:#ff5c3e">{bsState.score}</strong>
                     </div>
                   </div>
                   <div class="page-actions" style="margin-top:16px">
                     <button class="btn ghost" on:click={bsRetry}>Retry</button>
                     <button class="btn primary" on:click={submitBinarySearch} disabled={playSubmitting}>
                       {playSubmitting ? 'Submitting...' : 'Finish challenge'}
                     </button>
                   </div>
                 </div>
               {/if}

                {#if bsState && !bsState.found && bsState.phase !== 'complete'}
                  <div style="margin-top:14px;font:600 9px 'JetBrains Mono';letter-spacing:0.08em;color:#666b74">
                    ROUNDS: {bsState.movesUsed} / {config.max_moves || 5}
                  </div>
                {/if}
              {/if}

            {:else if currentChallengeData.type === 'trace'}
             {@const config = currentChallengeData.config || {}}
             {@const maxMoves = config.max_moves || 4}
             {@const movesLeft = maxMoves - (traceState?.movesUsed || 0)}
             {#if traceState}
              <div class="trace-header">
                <div class="trace-metric">
                  <small>MOVES LEFT</small>
                  <strong class={movesLeft <= 1 ? 'accent' : ''}>{movesLeft}</strong>
                </div>
                <div class="trace-metric">
                  <small>TARGET</small>
                  <strong>{traceState.target}</strong>
                </div>
                <div class="trace-metric">
                  <small>BOUNDS</small>
                  <strong>{traceState.low} â†’ {traceState.high}</strong>
                </div>
              </div>

              <div class="trace-array">
                {#each traceState.array as val, i}
                  {@const inBounds = i >= traceState.low && i <= traceState.high}
                  {@const isMid = traceState.history.some(h => h.mid === i)}
                  {@const last = traceState.history.find(h => h.mid === i)}
                  <button
                    class="trace-cell {inBounds ? 'in-bounds' : 'out-bounds'} {isMid ? 'visited' : ''}"
                    on:click={() => tracePickMid(i)}
                    disabled={!!challengeFeedback || playSubmitting || traceState.found || traceState.movesUsed >= maxMoves || isMid}
                    aria-label="Index {i}, value {val}{inBounds ? ', in bounds' : ', out of bounds'}{isMid ? ', visited' : ''}"
                  >
                    <span class="trace-idx">{i}</span>
                    <span class="trace-val">{val}</span>
                    {#if last}
                      <span class="trace-badge">{last.result === 'found' ? 'â˜…' : last.result === 'low' ? 'â†' : 'â†’'}</span>
                    {/if}
                  </button>
                {/each}
              </div>

              {#if traceState.history.length > 0 && !traceState.found && traceState.movesUsed < maxMoves}
                <div class="page-actions" style="margin-top:12px">
                  <span class="muted" style="font:600 9px 'JetBrains Mono';letter-spacing:0.08em">CHOOSE DIRECTION</span>
                  <div style="display:flex;gap:8px;margin-top:6px">
                    <button class="btn ghost" on:click={() => traceDirection('low')} disabled={playSubmitting || !!challengeFeedback}>Left (target &lt; arr[mid])</button>
                    <button class="btn ghost" on:click={() => traceDirection('high')} disabled={playSubmitting || !!challengeFeedback}>Right (target &gt; arr[mid])</button>
                    <button class="btn primary" on:click={() => traceDirection('found')} disabled={playSubmitting || !!challengeFeedback}>Found it!</button>
                  </div>
                </div>
              {/if}

               {#if (traceState.found || traceState.movesUsed >= maxMoves) && !challengeFeedback}
                 <div class="page-actions" style="margin-top:14px">
                   <button class="btn primary" on:click={submitTrace} disabled={playSubmitting}>
                     {playSubmitting ? 'Judging...' : 'Finish challenge'}
                   </button>
                 </div>
               {/if}
             {/if}

           {/if}

           {#if challengeFeedback}
             <div class="step-feedback {challengeFeedback.score > 0 ? 'success' : 'error'}" style="margin-top:14px">
               {#if challengeFeedback.score > 0}
                 <div class="xp-pop">+{challengeFeedback.xp} XP</div>
                 <b>Score: {challengeFeedback.score}</b>
                 {#if currentChallengeData.type === 'trace'}
                   {traceState?.found ? 'Found in ' + traceState.movesUsed + ' moves!' : 'Completed'}
                 {:else}
                   Challenge complete.
                 {/if}
               {:else}
                 <b>Not quite right.</b> Review the instructions and try again.
              </div>
            {/if}
          {/if}
        </section>
      </div>
    </div>
  {/if}
{/if}

{#if stage === 'prove'}
  <div id="stage-panel" role="tabpanel" aria-labelledby="prove-tab" class="stage-panel">
    {#if proveCompleted}
             </div>
    <div id="stage-panel" role="tabpanel" aria-labelledby="prove-tab" class="stage-panel">
      {#if proveCompleted}
        <div class="alert success" style="margin-bottom:14px">
          Prove stage complete! +{earnedProveXp} XP earned. Mastery: {progress?.mastery_score || 0}
        </div>
      {/if}
      <div class="panel">
      <div class="panel-head">
        <h2>Prove Your Skills</h2>
        <span class="eyebrow">{proveProblems.length} PROBLEM{proveProblems.length === 1 ? '' : 'S'} Â· +{totalProveXp} XP</span>
      </div>
      <p style="margin:0 0 12px">Solve these problems to complete the module. Progress is tracked automatically on Accepted submissions.</p>
      <div style="display:flex;flex-direction:column;gap:8px">
        {#each proveProblems as p, i}
          <a class="list-row" style="display:flex;align-items:center;justify-content:space-between;text-decoration:none;color:inherit" href={`/problems/${p.problem_id}?learning_module=${slug}`}>
            <div>
              <b>{i + 1}. {p.title}</b>
              <small style="margin-left:8px">{p.topic} Â· {p.difficulty}</small>
            </div>
            <span class="pill {p.difficulty.toLowerCase()}">+{p.xp_reward || 0} XP</span>
          </a>
        {/each}
      </div>
      {#if proveProblems.length > 0 && !proveCompleted}
        <div class="page-actions" style="margin-top:14px">
          <a class="btn primary" href={`/problems/${proveProblems[0].problem_id}?learning_module=${slug}`}>Start First Problem â†’</a>
        </div>
      {/if}
      {#if proveProblems.length === 0 && !proveCompleted}
        <div class="empty" style="margin-top:14px">
    </div>
  </div>
{/if}
          </div>
     {/if}
   {:else if stage === 'prove'}
