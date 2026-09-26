<script lang="ts">
  import VisualizerControls from '$lib/visualizer/VisualizerControls.svelte';
  import CodeViewer from '$lib/visualizer/CodeViewer.svelte';
  import { parseNumbers } from '$lib/visualizer/helpers';

  type Step = {
    values: number[];
    active: number;
    found: number;
    message: string;
    codeLine: number;
  };

  let input = '8, 3, 14, 6, 21, 10';
  let target = 21;
  let steps: Step[] = [];
  let stepIndex = 0;
  let playing = false;
  let speed = 700;
  let error = '';

  const code = [
    'int linearSearch(vector<int>& a, int target) {',
    '    for (int i = 0; i < a.size(); i++) {',
    '        if (a[i] == target) {',
    '            return i;',
    '        }',
    '    }',
    '',
    '    return -1;',
    '}',
  ];

  $: current = steps[stepIndex];

  function build() {
    const values = parseNumbers(input);

    if (!values.length || !Number.isFinite(Number(target))) {
      error = 'Enter a valid array and target.';
      return;
    }

    const wanted = Number(target);
    const nextSteps: Step[] = [];
    let found = -1;

    for (let i = 0; i < values.length; i++) {
      const match = values[i] === wanted;
      nextSteps.push({
        values: [...values],
        active: i,
        found: match ? i : -1,
        message: match
          ? `${values[i]} matches the target. Search stops at index ${i}.`
          : `${values[i]} is not ${wanted}, so move to the next index.`,
        codeLine: match ? 4 : 3,
      });

      if (match) {
        found = i;
        break;
      }
    }

    if (found === -1) {
      nextSteps.push({
        values: [...values],
        active: -1,
        found: -1,
        message: `${wanted} was not found after checking every element.`,
        codeLine: 8,
      });
    }

    error = '';
    steps = nextSteps;
    stepIndex = 0;
    playing = false;
  }

  function previous() {
    playing = false;
    stepIndex = Math.max(0, stepIndex - 1);
  }

  function next() {
    stepIndex = Math.min(steps.length - 1, stepIndex + 1);
  }

  function reset() {
    playing = false;
    stepIndex = 0;
  }

  build();
</script>

<svelte:head><title>Linear Search - DSA Visualizer</title></svelte:head>

<div class="visualizer-page">
  <div class="page-head">
    <div>
      <span class="eyebrow">SEARCHING</span>
      <h1>Linear Search</h1>
      <p>Check values one by one until the target appears or the array ends.</p>
    </div>
    <a class="btn ghost" href="/visualizer">All topics</a>
  </div>

  <section class="panel">
    <div class="visualizer-inputs">
      <label>Array values<input bind:value={input} /></label>
      <label>Target<input type="number" bind:value={target} /></label>
      <button class="btn primary" on:click={build}>Search</button>
    </div>
    {#if error}<div class="alert error">{error}</div>{/if}
  </section>

  <section class="panel visualizer-stage">
    {#if current}
      <div class="visualizer-array">
        {#each current.values as value, index}
          <div
            class="visualizer-cell"
            class:active={index === current.active}
            class:found={index === current.found}
          >
            <strong>{value}</strong>
            <small>index {index}</small>
          </div>
        {/each}
      </div>
    {/if}
  </section>

  <section class="panel">
    <VisualizerControls
      currentStep={stepIndex}
      totalSteps={steps.length}
      {playing}
      {speed}
      onPrevious={previous}
      onNext={next}
      onReset={reset}
      onPlayingChange={(value) => (playing = value)}
      onSpeedChange={(value) => (speed = value)}
    />
  </section>

  {#if current}
    <section class="panel visualizer-message">
      <div class="step-number">{stepIndex + 1}</div>
      <div><span class="eyebrow">CURRENT STEP</span><p>{current.message}</p></div>
    </section>
  {/if}

  <section class="panel visualizer-explanation">
    <div class="visualizer-explanation-head">
      <span class="eyebrow">HOW THIS TOPIC WORKS</span>
      <h2>Understanding Linear Search</h2>
      <p>Linear Search checks values one by one until it finds the target or reaches the end of the array.</p>
    </div>
    <div class="visualizer-explanation-grid">
      <div><h3>What it is</h3><p>Linear Search is the simplest searching method. It does not need the array to be sorted because it checks each position directly.</p></div>
      <div><h3>How it works</h3><ol><li>Start from the first element.</li><li>Compare the current value with the target.</li><li>If they match, stop and return the index.</li><li>If they do not match, move to the next element.</li><li>If the array ends, the target is not present.</li></ol></div>
      <div><h3>What to watch</h3><p>The active cell is the value currently being compared. When a match is found, that cell changes to the found state and the search stops.</p></div>
      <div><h3>When to use</h3><p>Use it for small or unsorted collections, or when you only need a quick simple search. The best case is O(1), while the worst case is O(n).</p></div>
    </div>
  </section>

  <section class="visualizer-complexity">
    <div><small>Best case</small><strong>O(1)</strong></div>
    <div><small>Worst case</small><strong>O(n)</strong></div>
    <div><small>Extra space</small><strong>O(1)</strong></div>
  </section>

  <CodeViewer {code} activeLine={current?.codeLine ?? 0} />
</div>
