import fs from 'node:fs';
import path from 'node:path';

const root = process.cwd();

const expectedRoutes = [
  '/',
  '/login',
  '/register',
  '/dashboard',
  '/problems',
  '/problems/[id]',
  '/contests',
  '/contests/[id]',
  '/rivalry',
  '/universities',
  '/universities/compare',
  '/search',
  '/profile/[id]',
  '/performance-profile',
  '/ghost-race',
  '/ghost-race/[id]',
  '/sql-battle',
  '/sql-battle/[id]',
  '/database',
  '/sql-lab',
  '/how-it-works',
];

const routeRoot = path.join(root, 'src', 'routes');

function routeExists(route) {
  const clean = route === '/' ? '' : route.slice(1);

  const candidates = [
    path.join(routeRoot, clean, '+page.svelte'),
    path.join(routeRoot, '(app)', clean, '+page.svelte'),
  ];

  return candidates.some((candidate) => fs.existsSync(candidate));
}

function check(condition, label) {
  if (condition) {
    console.log('[OK]', label);
    return 0;
  }

  console.error('[FAIL]', label);
  return 1;
}

let failed = 0;

for (const route of expectedRoutes) {
  failed += check(routeExists(route), `route ${route}`);
}

const api = fs.readFileSync(path.join(root, 'src', 'lib', 'api.ts'), 'utf8');

failed += check(/credentials\s*:\s*['"]include['"]/.test(api), "API marker credentials: 'include'");

failed += check(/X-XSRF-TOKEN/.test(api), 'API marker X-XSRF-TOKEN');

failed += check(/sanctum\/csrf-cookie/.test(api), 'API marker sanctum/csrf-cookie');

const css = fs.readFileSync(path.join(root, 'src', 'app.css'), 'utf8');

failed += check(/Space Grotesk/.test(css), 'UI marker Space Grotesk');

failed += check(/JetBrains Mono/.test(css), 'UI marker JetBrains Mono');

failed += check(
  /overscroll-behavior-y\s*:\s*contain\b/.test(css),
  'UI marker overscroll-behavior-y: contain'
);

if (failed > 0) {
  console.error(`\n${failed} contract checks failed`);
  process.exit(1);
}

console.log(`\n${expectedRoutes.length + 6} contract checks passed`);
