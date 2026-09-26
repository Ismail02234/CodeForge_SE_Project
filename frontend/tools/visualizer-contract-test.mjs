import fs from 'node:fs';
import path from 'node:path';

const root = process.cwd();
let failed = 0;

function check(condition, label) {
  if (condition) {
    console.log('[OK]', label);
    return;
  }

  failed += 1;
  console.error('[FAIL]', label);
}

function read(relativePath) {
  const full = path.join(root, relativePath);
  return fs.existsSync(full) ? fs.readFileSync(full, 'utf8') : '';
}

const routes = [
  'visualizer',
  'visualizer/array-traversal',
  'visualizer/linear-search',
  'visualizer/binary-search',
  'visualizer/bubble-sort',
  'visualizer/selection-sort',
  'visualizer/insertion-sort',
  'visualizer/stack',
  'visualizer/queue',
  'visualizer/linked-list',
  'visualizer/bst-traversal',
];

for (const route of routes) {
  const page = path.join(root, 'src', 'routes', '(app)', route, '+page.svelte');
  check(fs.existsSync(page), `route /${route}`);
}

check(
  fs.existsSync(path.join(root, 'src', 'lib', 'visualizer', 'VisualizerControls.svelte')),
  'shared VisualizerControls component'
);
check(
  fs.existsSync(path.join(root, 'src', 'lib', 'visualizer', 'CodeViewer.svelte')),
  'shared CodeViewer component'
);
check(
  fs.existsSync(path.join(root, 'src', 'lib', 'visualizer', 'visualizer.css')),
  'shared visualizer stylesheet'
);

const shell = read('src/lib/components/AppShell.svelte');
check(/\/visualizer['"],\s*['"]DSA Visualizer/.test(shell), 'top-level DSA Visualizer navigation');

const hub = read('src/routes/(app)/visualizer/+page.svelte');
for (const route of routes.slice(1)) {
  check(hub.includes(`/${route}`), `hub link /${route}`);
}

const stepPages = [
  'array-traversal',
  'linear-search',
  'binary-search',
  'bubble-sort',
  'selection-sort',
  'insertion-sort',
  'stack',
  'queue',
  'linked-list',
  'bst-traversal',
];

for (const topic of stepPages) {
  const source = read(`src/routes/(app)/visualizer/${topic}/+page.svelte`);
  check(source.includes('CodeViewer'), `${topic} has C++ code viewer`);
  check(source.includes('VisualizerControls'), `${topic} has playback controls`);
}

const binary = read('src/routes/(app)/visualizer/binary-search/+page.svelte');
check(/sort\(\(a, b\) => a - b\)/.test(binary), 'binary search normalizes values into sorted order');

const bst = read('src/routes/(app)/visualizer/bst-traversal/+page.svelte');
check(bst.includes('Preorder') && bst.includes('Inorder') && bst.includes('Postorder'), 'BST supports three traversal modes');

const sourceFiles = [
  'src/lib/visualizer/VisualizerControls.svelte',
  'src/lib/visualizer/CodeViewer.svelte',
  'src/lib/visualizer/helpers.ts',
  'src/lib/visualizer/visualizer.css',
  ...routes.map((route) => `src/routes/(app)/${route}/+page.svelte`),
];

for (const file of sourceFiles) {
  const source = read(file);
  if (source) {
    check(!/AlgorithmEngine|VisualizationStrategy|StepFactory|AnimationManager|AbstractVisualizer/.test(source), `${file} stays straightforward`);
  }
}

if (failed > 0) {
  console.error(`\n${failed} visualizer contract checks failed`);
  process.exit(1);
}

console.log('\nAll visualizer contract checks passed');
