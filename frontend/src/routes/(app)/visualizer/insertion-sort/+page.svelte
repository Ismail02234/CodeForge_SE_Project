<script lang="ts">
  import VisualizerControls from '$lib/visualizer/VisualizerControls.svelte';
  import CodeViewer from '$lib/visualizer/CodeViewer.svelte';
  import { barHeight, parseNumbers } from '$lib/visualizer/helpers';

  type Step = {
    values: number[];
    first: number;
    second: number;
    sortedUntil: number;
    message: string;
    codeLine: number;
  };

  let input = '7, 4, 9, 2, 6, 1';
  let steps: Step[] = [];
  let stepIndex = 0;
  let playing = false;
  let speed = 620;
  let error = '';

  const code = [
    'void insertionSort(vector<int>& a) {',
    '    for (int i = 1; i < a.size(); i++) {',
    '        int key = a[i];',
    '        int j = i - 1;',
    '',
    '        while (j >= 0 && a[j] > key) {',
    '            a[j + 1] = a[j];',
    '            j--;',
    '        }',
    '        a[j + 1] = key;',
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
    const nextSteps: Step[] = [{
      values: [...a], first: 0, second: -1, sortedUntil: 0,
      message: 'The first value starts as the sorted section.', codeLine: 2,
    }];

    for (let i = 1; i < a.length; i++) {
      const key = a[i];
      let j = i - 1;

      nextSteps.push({
        values: [...a], first: i, second: j, sortedUntil: i - 1,
        message: `Take ${key} and find its place inside the sorted left section.`, codeLine: 3,
      });

      while (j >= 0 && a[j] > key) {
        const moved = a[j];
        a[j + 1] = a[j];
        nextSteps.push({
          values: [...a], first: j, second: j + 1, sortedUntil: i - 1,
          message: `${moved} is larger than ${key}, so shift it one position right.`, codeLine: 7,
        });
        j--;
      }

      a[j + 1] = key;
      nextSteps.push({
        values: [...a], first: j + 1, second: -1, sortedUntil: i,
        message: `Insert ${key} at index ${j + 1}.`, codeLine: 10,
      });
    }

    nextSteps.push({
      values: [...a], first: -1, second: -1, sortedUntil: a.length - 1,
      message: 'The array is sorted.', codeLine: 11,
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

<svelte:head><title>Insertion Sort - DSA Visualizer</title></svelte:head>

<div class="visualizer-page">
  <div class="page-head">
    <div><span class="eyebrow">SORTING</span><h1>Insertion Sort</h1><p>Grow a sorted left section by taking one value and inserting it into the correct place.</p></div>
    <a class="btn ghost" href="/visualizer">All topics</a>
  </div>

  <section class="panel">
    <div class="visualizer-inputs"><label>Array values<input bind:value={input} /></label><button class="btn primary" on:click={build}>Sort</button></div>
    {#if error}<div class="alert error">{error}</div>{/if}
  </section>

  <section class="panel visualizer-stage">
    {#if current}
      <div class="visualizer-bars">
        {#each current.values as value, index}
          <div class="visualizer-bar"
            class:compare={index === current.first || index === current.second}
            class:sorted={index <= current.sortedUntil && index !== current.first && index !== current.second}
            style:height={`${barHeight(value, current.values)}px`}>{value}</div>
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
      <h2>Understanding Insertion Sort</h2>
      <p>Insertion Sort grows a sorted portion by taking one value at a time and inserting it into the correct place.</p>
    </div>
    <div class="visualizer-explanation-grid">
      <div><h3>What it is</h3><p>Insertion Sort treats the beginning of the array as already sorted. Each next value is compared backward until its correct position is found.</p></div>
      <div><h3>How it works</h3><ol><li>Begin with the second element as the current key.</li><li>Compare the key with values on its left.</li><li>Shift larger values one position to the right.</li><li>Insert the key into the empty correct position.</li><li>Repeat with the next unsorted value.</li></ol></div>
      <div><h3>What to watch</h3><p>Watch the current key and the sorted portion on the left. The key moves left while larger elements shift right to make space.</p></div>
      <div><h3>When to use</h3><p>Insertion Sort works well for small or nearly sorted arrays. Its best case is O(n), while its average and worst cases are O(n^2).</p></div>
    </div>
  </section>

  <section class="visualizer-complexity">
    <div><small>Average</small><strong>O(n^2)</strong></div>
    <div><small>Best case</small><strong>O(n)</strong></div>
    <div><small>Extra space</small><strong>O(1)</strong></div>
  </section>

  <CodeViewer {code} activeLine={current?.codeLine ?? 0} />
</div>
