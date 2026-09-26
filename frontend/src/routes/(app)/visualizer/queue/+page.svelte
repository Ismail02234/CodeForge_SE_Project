<script lang="ts">
  import VisualizerControls from '$lib/visualizer/VisualizerControls.svelte';
  import CodeViewer from '$lib/visualizer/CodeViewer.svelte';
  import { parseNumbers } from '$lib/visualizer/helpers';

  type Step = { values: number[]; message: string; codeLine: number };

  let initialInput = '11, 22, 33';
  let valueInput = 44;
  let steps: Step[] = [];
  let stepIndex = 0;
  let playing = false;
  let speed = 700;
  let error = '';

  const code = [
    'queue<int> q;',
    '',
    'q.push(11);',
    'q.push(22);',
    'q.push(33);',
    '',
    'q.pop();',
    'cout << q.front();',
  ];

  $: current = steps[stepIndex];

  function build() {
    const values = parseNumbers(initialInput, 10);
    error = '';
    steps = [{ values, message: 'Front leaves first and new values join at the rear.', codeLine: 1 }];
    stepIndex = 0;
    playing = false;
  }

  function addStep(step: Step) {
    steps = [...steps.slice(0, stepIndex + 1), step];
    stepIndex = steps.length - 1;
    playing = false;
  }

  function enqueue() {
    const value = Number(valueInput);
    if (!Number.isFinite(value)) { error = 'Enter a valid value.'; return; }

    const values = [...(current?.values ?? []), value];
    error = '';
    addStep({ values, message: `Enqueue ${value} at the rear of the queue.`, codeLine: 4 });
  }

  function dequeue() {
    const values = [...(current?.values ?? [])];
    if (!values.length) { error = 'The queue is already empty.'; return; }

    const removed = values.shift();
    error = '';
    addStep({ values, message: `Dequeue ${removed} from the front.`, codeLine: 7 });
  }

  function previous() { playing = false; stepIndex = Math.max(0, stepIndex - 1); }
  function next() { stepIndex = Math.min(steps.length - 1, stepIndex + 1); }
  function reset() { build(); }

  build();
</script>

<svelte:head><title>Queue - DSA Visualizer</title></svelte:head>

<div class="visualizer-page">
  <div class="page-head">
    <div><span class="eyebrow">LINEAR STRUCTURES</span><h1>Queue</h1><p>Enqueue at the rear and dequeue from the front to see First In, First Out behavior.</p></div>
    <a class="btn ghost" href="/visualizer">All topics</a>
  </div>

  <section class="panel">
    <div class="visualizer-inputs">
      <label>Starting values<input bind:value={initialInput} /></label>
      <button class="btn ghost" on:click={build}>Load queue</button>
      <label>Value<input type="number" bind:value={valueInput} /></label>
      <button class="btn primary" on:click={enqueue}>Enqueue</button>
      <button class="btn ghost" on:click={dequeue}>Dequeue</button>
    </div>
    {#if error}<div class="alert error">{error}</div>{/if}
  </section>

  <section class="panel visualizer-stage">
    {#if current?.values.length}
      <div class="visualizer-queue">
        <span class="tiny muted">FRONT</span>
        {#each current.values as value}<div class="visualizer-queue-item">{value}</div>{/each}
        <span class="tiny muted">REAR</span>
      </div>
    {:else}
      <p class="muted">The queue is empty.</p>
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
      <h2>Understanding a Queue</h2>
      <p>A Queue follows First In, First Out (FIFO): the oldest waiting item is removed first.</p>
    </div>
    <div class="visualizer-explanation-grid">
      <div><h3>What it is</h3><p>A Queue is a linear data structure with two important ends: values enter at the rear and leave from the front.</p></div>
      <div><h3>How it works</h3><ol><li>Enqueue adds a value at the rear.</li><li>The front points to the oldest value.</li><li>Dequeue removes the value at the front.</li><li>The next waiting value becomes the new front.</li></ol></div>
      <div><h3>What to watch</h3><p>Watch the FRONT and REAR positions. New values join at the rear, but removal always happens from the front.</p></div>
      <div><h3>When to use</h3><p>Queues are common in BFS, task scheduling, request handling, and waiting-line systems. With a proper queue structure, enqueue and dequeue are O(1).</p></div>
    </div>
  </section>

  <section class="visualizer-complexity">
    <div><small>Enqueue</small><strong>O(1)</strong></div>
    <div><small>Dequeue</small><strong>O(1)</strong></div>
    <div><small>Rule</small><strong>FIFO</strong></div>
  </section>

  <CodeViewer {code} activeLine={current?.codeLine ?? 0} />
</div>
