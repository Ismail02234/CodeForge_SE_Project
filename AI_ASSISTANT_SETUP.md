# CodeForge AI Copilot — Setup and Verification

The AI Copilot is implemented as an isolated authenticated feature. It does not require a database migration and does not replace any existing CodeForge service.

## What works without an API key

The built-in deterministic command layer remains available with no external model. It can:

- navigate to CodeForge pages,
- open the signed-in user's profile,
- search users/problems/universities,
- open user profiles,
- open problems and contests by name/ID,
- compare the signed-in user with another coder,
- identify the weakest practice field,
- summarize the Performance Profile,
- open TARGET mode for the weakest field.

Examples:

```text
open ghost race
open sql battle
open my profile
compare me with ismail
search for graphs
open problem Two Sum
what should I practice?
target my weakest field
how am I doing?
```

## Enable the external AI model

Add these values to `backend/.env`:

```env
CODEFORGE_AI_ENABLED=true
CODEFORGE_AI_PROVIDER=openai
CODEFORGE_AI_MODEL=gpt-5.6-luna
CODEFORGE_AI_TIMEOUT=20
OPENAI_API_KEY=your_server_side_key_here
```

Then clear Laravel's cached configuration:

```powershell
cd D:\xampp\htdocs\codeforge\backend
D:\xampp\php\php.exe artisan config:clear
```

The API key stays in Laravel. It is never sent to Svelte/browser code.

## Safety design

- The assistant endpoint requires the existing Sanctum-authenticated session.
- The endpoint is rate-limited.
- The model cannot return arbitrary executable URLs; Laravel maps intents to a route allowlist.
- Admin-only destinations are excluded for non-admin users.
- No delete/update/admin/database-write tool is exposed to the model.
- OpenAI response storage is disabled in the request payload.
- Conversation UI history is kept in browser `sessionStorage`, not a new database table.
- No database migration is required.
- If the external model is unavailable, direct CodeForge commands continue to work locally.

## Verification

Backend route:

```powershell
cd D:\xampp\htdocs\codeforge\backend
D:\xampp\php\php.exe artisan route:list --path=assistant
```

Frontend contracts:

```powershell
cd D:\xampp\htdocs\codeforge\frontend
node .\tools\assistant-contract-test.mjs
npm run test:contract
npm run check
npm run build
```

Selenium assistant smoke test:

```powershell
cd D:\xampp\htdocs\codeforge\codeforge_selenium_tests
python -m pytest tests/test_10_ai_assistant.py -v
```

Full Selenium suite:

```powershell
python -m pytest -v
```
