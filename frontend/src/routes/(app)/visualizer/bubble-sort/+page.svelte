<script lang="ts">
  import VisualizerControls from '$lib/visualizer/VisualizerControls.svelte';
  import CodeViewer from '$lib/visualizer/CodeViewer.svelte';
  import { barHeight, parseNumbers } from '$lib/visualizer/helpers';

  type Step = {
    values: number[];
    first: number;
    second: number;
    sortedFrom: number;
    message: string;
    codeLine: number;
  };

  let input = '8, 3, 6, 1, 9, 4';
  let steps: Step[] = [];
  let stepIndex = 0;
  let playing = false;
  let speed = 520;
  let error = '';

  const code = [
    'void bubbleSort(vector<int>& a) {',
    '    int n = a.size();',
    '    for (int i = 0; i < n - 1; i++) {',
    '        for (int j = 0; j < n - i - 1; j++) {',
    '            if (a[j] > a[j + 1]) {',
    '                swap(a[j], a[j + 1]);',
    '            }',
    '        }',
    '    }',
    '}',
  ];

  $: current = steps[stepIndex];

  function build() {
    const values = parseNumbers(input, 12);

    if (values.length < 2) {
      error = 'Enter at least two numbers.';
      return;
    }

    const a = [...values];
    const nextSteps: Step[] = [];

    for (let i = 0; i < a.length - 1; i++) {
      let swapped = false;

      for (let j = 0; j < a.length - i - 1; j++) {
        nextSteps.push({
          values: [...a], first: j, second: j + 1, sortedFrom: a.length - i,
          message: `Compare ${a[j]} and ${a[j + 1]}.`, codeLine: 5,
        });

        if (a[j] > a[j + 1]) {
          const left = a[j];
          const right = a[j + 1];
          [a[j], a[j + 1]] = [a[j + 1], a[j]];
          swapped = true;
          nextSteps.push({
            values: [...a], first: j, second: j + 1, sortedFrom: a.length - i,
            message: `${left} is larger than ${right}, so swap them.`, codeLine: 6,
          });
        }
      }

      nextSteps.push({
        values: [...a], first: -1, second: -1, sortedFrom: a.length - i - 1,
        message: `Pass ${i + 1} is complete. The value at the right is now in its final position.`,
        codeLine: 3,
      });

      if (!swapped) break;
    }

    nextSteps.push({
      values: [...a], first: -1, second: -1, sortedFrom: 0,
      message: 'The array is sorted.', codeLine: 9,
    });

    error = '';
    steps = nextSteps;
    stepIndex = 0;
    playing = false;
  }

  function previous() { playing = false; stepIndex = Math.max(0, stepIndex - 1); }
  function next() { stepIndex = Math.min(steps.length - 1, stepIndex + 1); }
  function reset() { playing = false; stepIndex = 0; }

  build();
</script>

<svelte:head><title>Bubble Sort - DSA Visualizer</title></svelte:head>

<div class="visualizer-page">
  <div class="page-head">
    <div><span class="eyebrow">SORTING</span><h1>Bubble Sort</h1><p>Compare neighbors and repeatedly move larger values toward the right side.</p></div>
    <a class="btn ghost" href="/visualizer">All topics</a>
  </div>

  <section class="panel">
    <div class="visualizer-inputs">
      <label>Array values<input bind:value={input} /></label>
      <button class="btn primary" on:click={build}>Sort</button>
    </div>
    {#if error}<div class="alert error">{error}</div>{/if}
  </section>

  <section class="panel visualizer-stage">
    {#if current}
      <div class="visualizer-bars">
        {#each current.values as value, index}
          <div
            class="visualizer-bar"
            class:compare={index === current.first || index === current.second}
            class:sorted={index >= current.sortedFrom}
            style:height={`${barHeight(value, current.values)}px`}
          >{value}</div>
        {/each}
      </div>
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
      <h2>Understanding Bubble Sort</h2>
      <p>Bubble Sort repeatedly compares adjacent values and swaps them when they are in the wrong order.</p>
    </div>
    <div class="visualizer-explanation-grid">
      <div><h3>What it is</h3><p>Bubble Sort is a simple comparison sort. Larger values gradually move toward the right side of the array after repeated adjacent comparisons.</p></div>
      <div><h3>How it works</h3><ol><li>Compare the first two adjacent values.</li><li>Swap them if the left value is larger.</li><li>Move one position to the right and compare again.</li><li>After one full pass, the largest unsorted value reaches its final position.</li><li>Repeat for the remaining unsorted part.</li></ol></div>
      <div><h3>What to watch</h3><p>The highlighted bars are the two values currently being compared. Green bars represent values that have reached their sorted position.</p></div>
      <div><h3>When to use</h3><p>Bubble Sort is useful for learning sorting logic and for very small inputs, but it is inefficient for large data because its average and worst-case time are O(n^2).</p></div>
    </div>
  </section>

  <section class="visualizer-complexity">
    <div><small>Average</small><strong>O(n^2)</strong></div>
    <div><small>Best case</small><strong>O(n)</strong></div>
    <div><small>Extra space</small><strong>O(1)</strong></div>
  </section>

  <CodeViewer {code} activeLine={current?.codeLine ?? 0} />
</div>
