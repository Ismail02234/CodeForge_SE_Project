<script lang="ts">
  import VisualizerControls from '$lib/visualizer/VisualizerControls.svelte';
  import CodeViewer from '$lib/visualizer/CodeViewer.svelte';
  import { parseNumbers } from '$lib/visualizer/helpers';

  type Step = {
    values: number[];
    active: number;
    message: string;
    codeLine: number;
  };

  let input = '12, 7, 19, 4, 25, 9';
  let steps: Step[] = [];
  let stepIndex = 0;
  let playing = false;
  let speed = 700;
  let error = '';

  const code = [
    '#include <iostream>',
    '#include <vector>',
    'using namespace std;',
    '',
    'int main() {',
    '    vector<int> a = {12, 7, 19, 4, 25, 9};',
    '',
    '    for (int i = 0; i < a.size(); i++) {',
    '        cout << a[i] << " ";',
    '    }',
    '',
    '    return 0;',
    '}',
  ];

  $: current = steps[stepIndex];

  function build() {
    const values = parseNumbers(input);

    if (!values.length) {
      error = 'Enter at least one number.';
      return;
    }

    error = '';
    playing = false;
    stepIndex = 0;
    steps = values.map((value, index) => ({
      values: [...values],
      active: index,
      message: `Visit index ${index}. The value here is ${value}.`,
      codeLine: 9,
    }));
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

<svelte:head><title>Array Traversal - DSA Visualizer</title></svelte:head>

<div class="visualizer-page">
  <div class="page-head">
    <div>
      <span class="eyebrow">ARRAYS</span>
      <h1>Array Traversal</h1>
      <p>Walk through every element from left to right and connect each visited index with the loop in C++.</p>
    </div>
    <a class="btn ghost" href="/visualizer">All topics</a>
  </div>

  <section class="panel">
    <div class="visualizer-inputs">
      <label>
        Array values
        <input bind:value={input} placeholder="12, 7, 19, 4" />
      </label>
      <button class="btn primary" on:click={build}>Visualize</button>
    </div>
    {#if error}<div class="alert error">{error}</div>{/if}
  </section>

  <section class="panel visualizer-stage">
    {#if current}
      <div class="visualizer-array">
        {#each current.values as value, index}
          <div class="visualizer-cell" class:active={index === current.active}>
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
      <div>
        <span class="eyebrow">CURRENT STEP</span>
        <p>{current.message}</p>
      </div>
    </section>
  {/if}

  <section class="panel visualizer-explanation">
    <div class="visualizer-explanation-head">
      <span class="eyebrow">HOW THIS TOPIC WORKS</span>
      <h2>Understanding Array Traversal</h2>
      <p>Traversal means moving through an array from one position to another so every required element can be processed.</p>
    </div>
    <div class="visualizer-explanation-grid">
      <div><h3>What it is</h3><p>Array traversal is the basic process of visiting array elements in sequence. Most simple array algorithms start by moving an index from the beginning toward the end.</p></div>
      <div><h3>How it works</h3><ol><li>Start at index 0.</li><li>Read or process the value at the current index.</li><li>Move to the next index.</li><li>Continue until the last required element has been visited.</li></ol></div>
      <div><h3>What to watch</h3><p>The highlighted cell shows the current index. Press Next or Play and notice how the active position moves one step at a time while the C++ loop advances.</p></div>
      <div><h3>When to use</h3><p>Use traversal whenever you need to visit each element, such as finding a sum, counting values, printing an array, or checking a condition. A full traversal takes O(n) time.</p></div>
    </div>
  </section>

  <section class="visualizer-complexity">
    <div><small>Time</small><strong>O(n)</strong></div>
    <div><small>Extra space</small><strong>O(1)</strong></div>
    <div><small>Main idea</small><strong>Visit each index once</strong></div>
  </section>

  <CodeViewer {code} activeLine={current?.codeLine ?? 0} />
</div>
