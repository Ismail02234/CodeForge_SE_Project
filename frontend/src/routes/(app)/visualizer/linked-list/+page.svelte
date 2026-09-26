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

  let initialInput = '7, 14, 21, 28';
  let valueInput = 35;
  let steps: Step[] = [];
  let stepIndex = 0;
  let playing = false;
  let speed = 700;
  let error = '';

  const code = [
    'struct Node { int data; Node* next; };',
    '',
    'void search(Node* head, int target) {',
    '    Node* current = head;',
    '    while (current != nullptr) {',
    '        if (current->data == target) return;',
    '        current = current->next;',
    '    }',
    '}',
    '',
    '// Insert at end: last->next = newNode;',
    '// Delete: previous->next = current->next;',
  ];

  $: current = steps[stepIndex];

  function build() {
    const values = parseNumbers(initialInput, 10);
    error = '';
    steps = [{ values, active: -1, found: -1, message: 'Each node stores a value and a pointer to the next node.', codeLine: 1 }];
    stepIndex = 0;
    playing = false;
  }

  function addSteps(newSteps: Step[]) {
    steps = [...steps.slice(0, stepIndex + 1), ...newSteps];
    stepIndex = steps.length - newSteps.length;
    playing = newSteps.length > 1;
  }

  function insertEnd() {
    const value = Number(valueInput);
    if (!Number.isFinite(value)) { error = 'Enter a valid value.'; return; }

    const values = [...(current?.values ?? []), value];
    error = '';
    addSteps([{ values, active: values.length - 1, found: -1, message: `Insert ${value} at the end and point the old tail to it.`, codeLine: 11 }]);
  }

  function searchValue() {
    const value = Number(valueInput);
    if (!Number.isFinite(value)) { error = 'Enter a valid value.'; return; }

    const values = [...(current?.values ?? [])];
    const nextSteps: Step[] = [];
    let found = false;

    for (let i = 0; i < values.length; i++) {
      const match = values[i] === value;
      nextSteps.push({
        values: [...values], active: i, found: match ? i : -1,
        message: match ? `Found ${value} in this node.` : `${values[i]} is not ${value}. Follow next.`,
        codeLine: match ? 6 : 7,
      });
      if (match) { found = true; break; }
    }

    if (!found) nextSteps.push({ values, active: -1, found: -1, message: `${value} is not in the list.`, codeLine: 8 });
    error = '';
    addSteps(nextSteps.length ? nextSteps : [{ values, active: -1, found: -1, message: 'The list is empty.', codeLine: 8 }]);
  }

  function deleteValue() {
    const value = Number(valueInput);
    if (!Number.isFinite(value)) { error = 'Enter a valid value.'; return; }

    const values = [...(current?.values ?? [])];
    const nextSteps: Step[] = [];
    const index = values.indexOf(value);

    for (let i = 0; i < (index >= 0 ? index + 1 : values.length); i++) {
      nextSteps.push({ values: [...values], active: i, found: i === index ? i : -1, message: `Check node ${values[i]}.`, codeLine: 5 });
    }

    if (index >= 0) {
      values.splice(index, 1);
      nextSteps.push({ values: [...values], active: -1, found: -1, message: `Delete ${value} and reconnect the surrounding link.`, codeLine: 12 });
    } else {
      nextSteps.push({ values: [...values], active: -1, found: -1, message: `${value} is not in the list, so nothing is deleted.`, codeLine: 8 });
    }

    error = '';
    addSteps(nextSteps);
  }

  function previous() { playing = false; stepIndex = Math.max(0, stepIndex - 1); }
  function next() { stepIndex = Math.min(steps.length - 1, stepIndex + 1); }
  function reset() { build(); }

  build();
</script>

<svelte:head><title>Linked List - DSA Visualizer</title></svelte:head>

<div class="visualizer-page">
  <div class="page-head">
    <div><span class="eyebrow">LINEAR STRUCTURES</span><h1>Singly Linked List</h1><p>Follow next pointers while inserting, deleting and searching through connected nodes.</p></div>
    <a class="btn ghost" href="/visualizer">All topics</a>
  </div>

  <section class="panel">
    <div class="visualizer-inputs">
      <label>Starting nodes<input bind:value={initialInput} /></label>
      <button class="btn ghost" on:click={build}>Load list</button>
      <label>Value<input type="number" bind:value={valueInput} /></label>
      <button class="btn primary" on:click={insertEnd}>Insert end</button>
      <button class="btn ghost" on:click={searchValue}>Search</button>
      <button class="btn ghost" on:click={deleteValue}>Delete</button>
    </div>
    {#if error}<div class="alert error">{error}</div>{/if}
  </section>

  <section class="panel visualizer-stage">
    {#if current?.values.length}
      <div class="visualizer-structure">
        <span class="tiny muted">HEAD</span>
        {#each current.values as value, index}
          <div class="visualizer-node" class:active={index === current.active} class:visited={index === current.found}>{value}</div>
          <span class="visualizer-arrow">{index === current.values.length - 1 ? '-> NULL' : '->'}</span>
        {/each}
      </div>
    {:else}
      <p class="muted">HEAD -> NULL</p>
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
      <h2>Understanding a Singly Linked List</h2>
      <p>A linked list stores values inside separate nodes, and each node uses a next pointer to connect to the following node.</p>
    </div>
    <div class="visualizer-explanation-grid">
      <div><h3>What it is</h3><p>A Singly Linked List is a chain of nodes rather than one continuous array. Each node contains data and a pointer to the next node.</p></div>
      <div><h3>How it works</h3><ol><li>The head points to the first node.</li><li>Follow each next pointer to move through the list.</li><li>Insertion changes pointers so a new node joins the chain.</li><li>Deletion reconnects the surrounding pointer so the removed node is skipped.</li><li>The final node points to null.</li></ol></div>
      <div><h3>What to watch</h3><p>Watch the arrows between nodes. During search, the active node moves along the pointers. During insertion or deletion, focus on how those links change.</p></div>
      <div><h3>When to use</h3><p>Linked lists are useful when frequent insertion or deletion matters more than random index access. Traversal and search are O(n), while some pointer-based insertions can be O(1).</p></div>
    </div>
  </section>

  <section class="visualizer-complexity">
    <div><small>Search</small><strong>O(n)</strong></div>
    <div><small>Insert at tail</small><strong>O(n)</strong></div>
    <div><small>Node link</small><strong>next pointer</strong></div>
  </section>

  <CodeViewer {code} activeLine={current?.codeLine ?? 0} />
</div>
