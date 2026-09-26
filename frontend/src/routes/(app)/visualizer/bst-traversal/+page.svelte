<script lang="ts">
  import VisualizerControls from '$lib/visualizer/VisualizerControls.svelte';
  import CodeViewer from '$lib/visualizer/CodeViewer.svelte';
  import { parseNumbers } from '$lib/visualizer/helpers';

  type TreeNode = {
    value: number;
    left: TreeNode | null;
    right: TreeNode | null;
    x?: number;
    y?: number;
  };

  type FlatNode = { value: number; x: number; y: number; parent: number | null };
  type Step = { active: number; visited: number[]; message: string; codeLine: number };

  let input = '50, 30, 70, 20, 40, 60, 80';
  let mode = 'Inorder';
  let root: TreeNode | null = null;
  let nodes: FlatNode[] = [];
  let steps: Step[] = [];
  let stepIndex = 0;
  let playing = false;
  let speed = 750;
  let error = '';

  const code = [
    'void preorder(Node* root) {',
    '    if (!root) return;',
    '    visit(root);',
    '    preorder(root->left);',
    '    preorder(root->right);',
    '}',
    '',
    'void inorder(Node* root) {',
    '    if (!root) return;',
    '    inorder(root->left);',
    '    visit(root);',
    '    inorder(root->right);',
    '}',
    '',
    'void postorder(Node* root) {',
    '    if (!root) return;',
    '    postorder(root->left);',
    '    postorder(root->right);',
    '    visit(root);',
    '}',
  ];

  $: current = steps[stepIndex];

  function insert(node: TreeNode | null, value: number): TreeNode {
    if (!node) return { value, left: null, right: null };
    if (value < node.value) node.left = insert(node.left, value);
    if (value > node.value) node.right = insert(node.right, value);
    return node;
  }

  function layout(node: TreeNode | null, minX: number, maxX: number, depth: number, parent: number | null) {
    if (!node) return;
    const x = (minX + maxX) / 2;
    const y = 55 + depth * 90;
    node.x = x;
    node.y = y;
    nodes.push({ value: node.value, x, y, parent });
    layout(node.left, minX, x, depth + 1, node.value);
    layout(node.right, x, maxX, depth + 1, node.value);
  }

  function visit(node: TreeNode | null, order: number[]) {
    if (!node) return;

    if (mode === 'Preorder') order.push(node.value);
    visit(node.left, order);
    if (mode === 'Inorder') order.push(node.value);
    visit(node.right, order);
    if (mode === 'Postorder') order.push(node.value);
  }

  function activeLine() {
    if (mode === 'Preorder') return 3;
    if (mode === 'Postorder') return 19;
    return 11;
  }

  function build() {
    const values = parseNumbers(input, 15);

    if (!values.length) {
      error = 'Enter at least one number.';
      return;
    }

    root = null;
    for (const value of values) root = insert(root, value);

    nodes = [];
    layout(root, 40, 960, 0, null);

    const order: number[] = [];
    visit(root, order);
    const visited: number[] = [];
    steps = order.map((value, index) => {
      visited.push(value);
      return {
        active: value,
        visited: [...visited],
        message: `${mode} visit ${index + 1}: process node ${value}.`,
        codeLine: activeLine(),
      };
    });

    error = '';
    stepIndex = 0;
    playing = false;
  }

  function lineFor(node: FlatNode) {
    if (node.parent === null) return null;
    const parent = nodes.find((item) => item.value === node.parent);
    return parent ? { x1: parent.x, y1: parent.y, x2: node.x, y2: node.y } : null;
  }

  function previous() { playing = false; stepIndex = Math.max(0, stepIndex - 1); }
  function next() { stepIndex = Math.min(steps.length - 1, stepIndex + 1); }
  function reset() { playing = false; stepIndex = 0; }

  build();
</script>

<svelte:head><title>BST Traversal - DSA Visualizer</title></svelte:head>

<div class="visualizer-page">
  <div class="page-head">
    <div><span class="eyebrow">TREES</span><h1>BST Traversal</h1><p>Build a Binary Search Tree from your values and watch Preorder, Inorder or Postorder traversal.</p></div>
    <a class="btn ghost" href="/visualizer">All topics</a>
  </div>

  <section class="panel">
    <div class="visualizer-inputs">
      <label>Tree values<input bind:value={input} /></label>
      <label>Traversal
        <select bind:value={mode}>
          <option>Preorder</option>
          <option>Inorder</option>
          <option>Postorder</option>
        </select>
      </label>
      <button class="btn primary" on:click={build}>Build traversal</button>
    </div>
    <p class="visualizer-note muted">Duplicate values are ignored when the BST is built.</p>
    {#if error}<div class="alert error">{error}</div>{/if}
  </section>

  <section class="panel visualizer-stage">
    {#if nodes.length}
      <svg class="visualizer-tree-svg" viewBox="0 0 1000 380" role="img" aria-label="Binary search tree visualization">
        {#each nodes as node}
          {@const line = lineFor(node)}
          {#if line}<line class="tree-edge-svg" x1={line.x1} y1={line.y1} x2={line.x2} y2={line.y2} />{/if}
        {/each}
        {#each nodes as node}
          <circle class="tree-circle-svg" class:active={current?.active === node.value} class:visited={current?.visited.includes(node.value)} cx={node.x} cy={node.y} r="27" />
          <text class="tree-text-svg" x={node.x} y={node.y}>{node.value}</text>
        {/each}
      </svg>
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
      <h2>Understanding BST Traversal</h2>
      <p>A Binary Search Tree keeps smaller values on the left and larger values on the right, then traversal decides the order in which nodes are visited.</p>
    </div>
    <div class="visualizer-explanation-grid">
      <div><h3>What it is</h3><p>A Binary Search Tree is a binary tree with an ordering rule. Traversal means visiting every node in a chosen sequence.</p></div>
      <div><h3>How it works</h3><ol><li>Preorder visits Root, Left, Right.</li><li>Inorder visits Left, Root, Right and produces sorted values in a BST.</li><li>Postorder visits Left, Right, Root.</li><li>The recursion repeats the same rule for every subtree.</li></ol></div>
      <div><h3>What to watch</h3><p>The highlighted node is the one currently being visited. Follow the growing visited order and compare how it changes when you switch between Preorder, Inorder, and Postorder.</p></div>
      <div><h3>When to use</h3><p>Tree traversal is used for searching, printing, copying, evaluating, and deleting tree structures. Visiting every node takes O(n) time.</p></div>
    </div>
  </section>

  <section class="visualizer-complexity">
    <div><small>Traversal</small><strong>O(n)</strong></div>
    <div><small>Recursive space</small><strong>O(h)</strong></div>
    <div><small>Inorder BST</small><strong>Sorted order</strong></div>
  </section>

  <CodeViewer {code} activeLine={current?.codeLine ?? 0} />
</div>
