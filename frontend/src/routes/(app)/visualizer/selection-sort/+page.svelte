<script lang="ts">
  import VisualizerControls from '$lib/visualizer/VisualizerControls.svelte';
  import CodeViewer from '$lib/visualizer/CodeViewer.svelte';
  import { barHeight, parseNumbers } from '$lib/visualizer/helpers';

  type Step = {
    values: number[];
    current: number;
    checking: number;
    minIndex: number;
    sortedUntil: number;
    message: string;
    codeLine: number;
  };

  let input = '29, 10, 14, 37, 13, 5';
  let steps: Step[] = [];
  let stepIndex = 0;
  let playing = false;
  let speed = 620;
  let error = '';

  const code = [
    'void selectionSort(vector<int>& a) {',
    '    for (int i = 0; i < a.size() - 1; i++) {',
    '        int minIndex = i;',
    '        for (int j = i + 1; j < a.size(); j++) {',
    '            if (a[j] < a[minIndex]) minIndex = j;',
    '        }',
    '        swap(a[i], a[minIndex]);',
    '    }',
    '}',
  ];

  $: currentStep = steps[stepIndex];

  function build() {
    const values = parseNumbers(input, 12);

    if (values.length < 2) {
      error = 'Enter at least two numbers.';
      return;
    }

    const a = [...values];
    const nextSteps: Step[] = [];

    for (let i = 0; i < a.length - 1; i++) {
      let minIndex = i;

      for (let j = i + 1; j < a.length; j++) {
        const isNewMin = a[j] < a[minIndex];
        if (isNewMin) minIndex = j;

        nextSteps.push({
          values: [...a], current: i, checking: j, minIndex, sortedUntil: i - 1,
          message: isNewMin
            ? `${a[j]} is the smallest value seen in this unsorted section.`
            : `Compare ${a[j]} with the current minimum ${a[minIndex]}.`,
          codeLine: 5,
        });
      }

      const chosen = a[minIndex];
      [a[i], a[minIndex]] = [a[minIndex], a[i]];
      nextSteps.push({
        values: [...a], current: i, checking: -1, minIndex: i, sortedUntil: i,
        message: `Place ${chosen} at index ${i}.`, codeLine: 7,
      });
    }

    nextSteps.push({
      values: [...a], current: -1, checking: -1, minIndex: -1, sortedUntil: a.length - 1,
      message: 'The array is sorted.', codeLine: 8,
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

<svelte:head><title>Selection Sort - DSA Visualizer</title></svelte:head>

<div class="visualizer-page">
  <div class="page-head">
    <div><span class="eyebrow">SORTING</span><h1>Selection Sort</h1><p>Find the smallest value in the unsorted area and move it into the next final position.</p></div>
    <a class="btn ghost" href="/visualizer">All topics</a>
  </div>

  <section class="panel">
    <div class="visualizer-inputs"><label>Array values<input bind:value={input} /></label><button class="btn primary" on:click={build}>Sort</button></div>
    {#if error}<div class="alert error">{error}</div>{/if}
  </section>

  <section class="panel visualizer-stage">
    {#if currentStep}
      <div class="visualizer-bars">
        {#each currentStep.values as value, index}
          <div class="visualizer-bar"
            class:compare={index === currentStep.checking || index === currentStep.minIndex}
            class:sorted={index <= currentStep.sortedUntil}
            style:height={`${barHeight(value, currentStep.values)}px`}>{value}</div>
        {/each}
      </div>
    {/if}
  </section>

  <section class="panel">
    <VisualizerControls currentStep={stepIndex} totalSteps={steps.length} {playing} {speed}
      onPrevious={previous} onNext={next} onReset={reset}
      onPlayingChange={(value) => (playing = value)} onSpeedChange={(value) => (speed = value)} />
  </section>

  {#if currentStep}
    <section class="panel visualizer-message"><div class="step-number">{stepIndex + 1}</div><div><span class="eyebrow">CURRENT STEP</span><p>{currentStep.message}</p></div></section>
  {/if}

  <section class="panel visualizer-explanation">
    <div class="visualizer-explanation-head">
      <span class="eyebrow">HOW THIS TOPIC WORKS</span>
      <h2>Understanding Selection Sort</h2>
      <p>Selection Sort repeatedly finds the smallest value in the unsorted part and places it in the next correct position.</p>
    </div>
    <div class="visualizer-explanation-grid">
      <div><h3>What it is</h3><p>Selection Sort divides the array into a sorted left side and an unsorted right side. Each pass selects one value for its final position.</p></div>
      <div><h3>How it works</h3><ol><li>Start at the first unsorted position.</li><li>Scan the remaining values to find the smallest one.</li><li>Swap that smallest value with the first unsorted value.</li><li>Move the sorted boundary one position to the right.</li><li>Repeat until the whole array is sorted.</li></ol></div>
      <div><h3>What to watch</h3><p>Watch which bar is considered the current minimum and how the sorted section grows from left to right after each selection.</p></div>
      <div><h3>When to use</h3><p>Selection Sort is easy to understand and performs few swaps, but it still takes O(n^2) comparisons, so it is mainly suitable for learning and small datasets.</p></div>
    </div>
  </section>

  <section class="visualizer-complexity">
    <div><small>Time</small><strong>O(n^2)</strong></div>
    <div><small>Swaps</small><strong>O(n)</strong></div>
    <div><small>Extra space</small><strong>O(1)</strong></div>
  </section>

  <CodeViewer {code} activeLine={currentStep?.codeLine ?? 0} />
</div>
