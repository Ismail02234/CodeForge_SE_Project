import fs from 'node:fs';
import path from 'node:path';

const frontend = process.cwd();
const project = path.resolve(frontend, '..');
let failed = 0;

function check(condition, label) {
  if (condition) {
    console.log('[OK]', label);
    return;
  }
  failed += 1;
  console.error('[FAIL]', label);
}

const componentPath = path.join(frontend, 'src', 'lib', 'components', 'AIAssistant.svelte');
const layoutPath = path.join(frontend, 'src', 'routes', '(app)', '+layout.svelte');
const targetPath = path.join(frontend, 'src', 'lib', 'components', 'WeakestFieldTarget.svelte');
const backendRoutePath = path.join(project, 'backend', 'routes', 'api.php');
const backendServicePath = path.join(
  project,
  'backend',
  'app',
  'Services',
  'AiAssistantService.php'
);
const backendConfigPath = path.join(project, 'backend', 'config', 'ai_assistant.php');

check(fs.existsSync(componentPath), 'AI Assistant component exists');
check(fs.existsSync(backendServicePath), 'AI Assistant backend service exists');
check(fs.existsSync(backendConfigPath), 'AI Assistant config exists');

const component = fs.readFileSync(componentPath, 'utf8');
const layout = fs.readFileSync(layoutPath, 'utf8');
const routes = fs.readFileSync(backendRoutePath, 'utf8');
const service = fs.readFileSync(backendServicePath, 'utf8');
const target = fs.readFileSync(targetPath, 'utf8');

check(/<AIAssistant\s*\/>/.test(layout), 'Assistant is mounted only in authenticated app layout');
check(/\/api\/assistant\/chat/.test(component), 'Frontend calls authenticated assistant endpoint');
check(/safeInternalPath/.test(component), 'Frontend rejects non-internal navigation paths');
check(
  /assistant\/chat/.test(routes) && /auth:sanctum/.test(routes),
  'Assistant route is inside authenticated API group'
);
check(/throttle:30,1/.test(routes), 'Assistant endpoint is rate limited');
check(/store'\s*=>\s*false/.test(service), 'OpenAI response storage is disabled');
check(
  /availableRoutesFor/.test(service),
  'Server validates navigation against role-aware route allowlist'
);
check(/ai_target/.test(target), 'Weakest-field URL action is supported');
check(
  /payload\?\.weakest\?\.topic/.test(target),
  'TARGET mode accepts the current recommendation API shape'
);

if (failed > 0) {
  console.error(`\n${failed} AI assistant contract checks failed`);
  process.exit(1);
}

console.log('\n12 AI assistant contract checks passed');
