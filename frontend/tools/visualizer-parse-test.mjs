import fs from 'node:fs';
import path from 'node:path';
import { compile } from 'svelte/compiler';

const files = [
  'src/lib/visualizer/VisualizerControls.svelte',
  'src/lib/visualizer/CodeViewer.svelte',
  'src/routes/(app)/visualizer/+layout.svelte',
  'src/routes/(app)/visualizer/+page.svelte',
  'src/routes/(app)/visualizer/array-traversal/+page.svelte',
  'src/routes/(app)/visualizer/linear-search/+page.svelte',
  'src/routes/(app)/visualizer/binary-search/+page.svelte',
  'src/routes/(app)/visualizer/bubble-sort/+page.svelte',
  'src/routes/(app)/visualizer/selection-sort/+page.svelte',
  'src/routes/(app)/visualizer/insertion-sort/+page.svelte',
  'src/routes/(app)/visualizer/stack/+page.svelte',
  'src/routes/(app)/visualizer/queue/+page.svelte',
  'src/routes/(app)/visualizer/linked-list/+page.svelte',
  'src/routes/(app)/visualizer/bst-traversal/+page.svelte',
];

let failed = 0;

for (const file of files) {
  try {
    const source = fs.readFileSync(path.join(process.cwd(), file), 'utf8');
    compile(source, { filename: file, generate: 'client' });
    console.log('[OK]', file);
  } catch (error) {
    failed += 1;
    console.error('[FAIL]', file);
    console.error(error.message);
  }
}

if (failed > 0) {
  console.error(`\n${failed} Svelte files failed to parse`);
  process.exit(1);
}

console.log(`\n${files.length} Svelte files parsed successfully`);
