<script lang="ts">
  import VisualizerControls from '$lib/visualizer/VisualizerControls.svelte';
  import CodeViewer from '$lib/visualizer/CodeViewer.svelte';
  import { parseNumbers } from '$lib/visualizer/helpers';

  type Step = {
    values: number[];
    low: number;
    mid: number;
    high: number;
    found: number;
    message: string;
    codeLine: number;
  };

  let input = '18, 2, 31, 9, 25, 5, 12';
  let target = 18;
  let steps: Step[] = [];
  let stepIndex = 0;
  let playing = false;
  let speed = 800;
  let error = '';

  const code = [
    'int binarySearch(vector<int>& a, int target) {',
    '    int low = 0, high = a.size() - 1;',
    '',
    '    while (low <= high) {',
    '        int mid = low + (high - low) / 2;',
    '',
    '        if (a[mid] == target) return mid;',
    '        if (a[mid] < target) low = mid + 1;',
    '        else high = mid - 1;',
    '    }',
    '',
    '    return -1;',
    '}',
  ];

  $: current = steps[stepIndex];

  function build() {
    const values = parseNumbers(input).sort((a, b) => a - b);

    if (!values.length || !Number.isFinite(Number(target))) {
      error = 'Enter a valid array and target.';
      return;
    }

    const wanted = Number(target);
    const nextSteps: Step[] = [];
    let low = 0;
    let high = values.length - 1;

    while (low <= high) {
      const mid = low + Math.floor((high - low) / 2);

      if (values[mid] === wanted) {
        nextSteps.push({
          values: [...values], low, mid, high, found: mid,
          message: `Middle value ${values[mid]} equals ${wanted}. Target found at index ${mid}.`,
          codeLine: 7,
        });
        break;
      }

      if (values[mid] < wanted) {
        nextSteps.push({
          values: [...values], low, mid, high, found: -1,
          message: `${values[mid]} is smaller than ${wanted}. Discard the left half.`,
          codeLine: 8,
        });
        low = mid + 1;
      } else {
        nextSteps.push({
          values: [...values], low, mid, high, found: -1,
          message: `${values[mid]} is larger than ${wanted}. Discard the right half.`,
          codeLine: 9,
        });
        high = mid - 1;
      }
    }

    if (!nextSteps.some((step) => step.found >= 0)) {
      nextSteps.push({
        values: [...values], low, mid: -1, high, found: -1,
        message: `${wanted} is not in the array. The search range is now empty.`,
        codeLine: 12,
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

<svelte:head><title>Binary Search - DSA Visualizer</title></svelte:head>

<div class="visualizer-page">
  <div class="page-head">
    <div>
      <span class="eyebrow">SEARCHING</span>
      <h1>Binary Search</h1>
      <p>Repeatedly cut a sorted search range in half. Your values are sorted automatically before the animation starts.</p>
    </div>
    <a class="btn ghost" href="/visualizer">All topics</a>
  </div>

  <section class="panel">
    <div class="visualizer-inputs">
      <label>Array values<input bind:value={input} /></label>
      <label>Target<input type="number" bind:value={target} /></label>
      <button class="btn primary" on:click={build}>Search</button>
    </div>
    <p class="visualizer-note muted">Binary Search needs sorted data, so the visualizer sorts your input first.</p>
    {#if error}<div class="alert error">{error}</div>{/if}
  </section>

  <section class="panel visualizer-stage">
    {#if current}
      <div class="visualizer-array">
        {#each current.values as value, index}
          <div
            class="visualizer-cell"
            class:active={index === current.mid}
            class:found={index === current.found}
            class:dimmed={index < current.low || index > current.high}
          >
            <strong>{value}</strong>
            <small>
              {index === current.mid ? 'mid' : index === current.low ? 'low' : index === current.high ? 'high' : `i ${index}`}
            </small>
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
      <h2>Understanding Binary Search</h2>
      <p>Binary Search finds a value by repeatedly cutting a sorted search range in half.</p>
    </div>
    <div class="visualizer-explanation-grid">
      <div><h3>What it is</h3><p>Binary Search is a fast searching algorithm for sorted data. Instead of checking every element, it compares the target with the middle value.</p></div>
      <div><h3>How it works</h3><ol><li>Set low to the first index and high to the last index.</li><li>Find the middle index.</li><li>If the middle value is the target, stop.</li><li>If the target is larger, move low to the right half.</li><li>If the target is smaller, move high to the left half.</li><li>Repeat while low is not greater than high.</li></ol></div>
      <div><h3>What to watch</h3><p>Watch the low, mid, and high labels. Dimmed values are outside the remaining search range, so every step removes about half of the possibilities.</p></div>
      <div><h3>When to use</h3><p>Use Binary Search when the data is already sorted or can be sorted first. It runs in O(log n) time, which is much faster than Linear Search on large sorted collections.</p></div>
    </div>
  </section>

  <section class="visualizer-complexity">
    <div><small>Best case</small><strong>O(1)</strong></div>
    <div><small>Worst case</small><strong>O(log n)</strong></div>
    <div><small>Requirement</small><strong>Sorted array</strong></div>
  </section>

  <CodeViewer {code} activeLine={current?.codeLine ?? 0} />
</div>
