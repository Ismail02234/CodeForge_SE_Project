<script lang="ts">
  import VisualizerControls from '$lib/visualizer/VisualizerControls.svelte';
  import CodeViewer from '$lib/visualizer/CodeViewer.svelte';
  import { parseNumbers } from '$lib/visualizer/helpers';

  type Step = { values: number[]; message: string; codeLine: number };

  let initialInput = '10, 20, 30';
  let valueInput = 40;
  let steps: Step[] = [];
  let stepIndex = 0;
  let playing = false;
  let speed = 700;
  let error = '';

  const code = [
    'stack<int> st;',
    '',
    'st.push(10);',
    'st.push(20);',
    'st.push(30);',
    '',
    'st.pop();',
    'cout << st.top();',
  ];

  $: current = steps[stepIndex];

  function build() {
    const values = parseNumbers(initialInput, 10);
    error = '';
    steps = [{ values, message: 'This is the current stack. The rightmost visible value is the top.', codeLine: 1 }];
    stepIndex = 0;
    playing = false;
  }

  function addStep(step: Step) {
    steps = [...steps.slice(0, stepIndex + 1), step];
    stepIndex = steps.length - 1;
    playing = false;
  }

  function pushValue() {
    const value = Number(valueInput);
    if (!Number.isFinite(value)) { error = 'Enter a valid value.'; return; }

    const values = [...(current?.values ?? []), value];
    error = '';
    addStep({ values, message: `Push ${value}. It becomes the new top of the stack.`, codeLine: 4 });
  }

  function popValue() {
    const values = [...(current?.values ?? [])];
    if (!values.length) { error = 'The stack is already empty.'; return; }

    const removed = values.pop();
    error = '';
    addStep({ values, message: `Pop ${removed}. The value below it becomes the new top.`, codeLine: 7 });
  }

  function previous() { playing = false; stepIndex = Math.max(0, stepIndex - 1); }
  function next() { stepIndex = Math.min(steps.length - 1, stepIndex + 1); }
  function reset() { build(); }

  build();
</script>

<svelte:head><title>Stack - DSA Visualizer</title></svelte:head>

<div class="visualizer-page">
  <div class="page-head">
    <div><span class="eyebrow">LINEAR STRUCTURES</span><h1>Stack</h1><p>Use push and pop to see Last In, First Out behavior and replay your operation history.</p></div>
    <a class="btn ghost" href="/visualizer">All topics</a>
  </div>

  <section class="panel">
    <div class="visualizer-inputs">
      <label>Starting values<input bind:value={initialInput} /></label>
      <button class="btn ghost" on:click={build}>Load stack</button>
      <label>Value<input type="number" bind:value={valueInput} /></label>
      <button class="btn primary" on:click={pushValue}>Push</button>
      <button class="btn ghost" on:click={popValue}>Pop</button>
    </div>
    {#if error}<div class="alert error">{error}</div>{/if}
  </section>

  <section class="panel visualizer-stage">
    {#if current?.values.length}
      <div class="visualizer-stack">
        {#each current.values as value, index}
          <div class="visualizer-stack-item">{value}{index === current.values.length - 1 ? '  <- TOP' : ''}</div>
        {/each}
      </div>
    {:else}
      <p class="muted">The stack is empty.</p>
    {/if}
  </section>

  <section class="panel">
    <VisualizerControls currentStep={stepIndex} totalSteps={steps.length} {playing} {speed}
      onPrevious={previous} onNext={next} onReset={reset}
      onPlayingChange={(value) => (playing = value)} onSpeedChange={(value) => (speed = value)} />
  </section>

  {#if current}
    <section class="panel visualizer-message"><div class="step-number">{stepIndex + 1}</div><div><span class="eyebrow">CURRENT STEP</span><p>{current.message}</p></div></section>
  {/if}

  <section class="panel visualizer-explanation">
    <div class="visualizer-explanation-head">
      <span class="eyebrow">HOW THIS TOPIC WORKS</span>
      <h2>Understanding a Stack</h2>
      <p>A Stack follows Last In, First Out (LIFO): the most recently added item is the first one removed.</p>
    </div>
    <div class="visualizer-explanation-grid">
      <div><h3>What it is</h3><p>A Stack is a linear data structure where insertion and removal happen at one end called the top.</p></div>
      <div><h3>How it works</h3><ol><li>Push adds a new value to the top.</li><li>Top reads the current top value without removing it.</li><li>Pop removes the current top value.</li><li>After a pop, the value below becomes the new top.</li></ol></div>
      <div><h3>What to watch</h3><p>The top label always follows the newest visible item. Push adds above the old top, while Pop removes that same newest item first.</p></div>
      <div><h3>When to use</h3><p>Stacks are useful for function calls, undo operations, expression evaluation, DFS, and bracket matching. Push and Pop are normally O(1).</p></div>
    </div>
  </section>

  <section class="visualizer-complexity">
    <div><small>Push</small><strong>O(1)</strong></div>
    <div><small>Pop</small><strong>O(1)</strong></div>
    <div><small>Rule</small><strong>LIFO</strong></div>
  </section>

  <CodeViewer {code} activeLine={current?.codeLine ?? 0} />
</div>
