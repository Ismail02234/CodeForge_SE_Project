<script lang="ts">
  import { onMount } from 'svelte';
  import { page } from '$app/stores';
  import { api } from '$lib/api';
  import Loading from '$lib/components/Loading.svelte';
  import VerdictBadge from '$lib/components/VerdictBadge.svelte';
  let data: any = null;
  let code = '';
  let language = 'C++';
  let result: any = null;
  let error = '';
  let busy = false;
  let aiFeedback: any = null;
  let aiBusy = false;
  let aiError = '';

  async function loadAIFeedback() {
    if (!result?.id || result.verdict === 'AC') return;

    aiBusy = true;
    aiFeedback = null;
    aiError = '';

    try {
      const response = await api.get(`/api/submissions/${result.id}/ai-feedback`);
      aiFeedback = response.feedback;
    } catch (e: any) {
      aiError = e.message;
    } finally {
      aiBusy = false;
    }
  }
  $: contest = $page.url.searchParams.get('contest');
  onMount(async () => {
    try {
      data = await api.get(`/api/problems/${$page.params.id}`);
      code = data.problem.starter_code || '';
    } catch (e: any) {
      error = e.message;
    }
  });
  async function submit() {
    busy = true;
    error = '';
    aiError = '';
    aiFeedback = null;
    try {
      result = await api.post(`/api/problems/${$page.params.id}/submit`, {
        source_code: code,
        language,
        contest_id: contest || null,
      });
      if (result.verdict !== 'AC') {
        void loadAIFeedback();
      }
      data = await api.get(`/api/problems/${$page.params.id}`);
    } catch (e: any) {
      error = e.message;
    } finally {
      busy = false;
    }
  }
</script>

<svelte:head><title>{data?.problem?.title || 'Problem'} · CodeForge</title></svelte:head
>{#if !data && !error}<Loading />{:else if error && !data}<div class="alert error">
    {error}
  </div>{:else}<div class="page-head">
    <div>
      <span class="eyebrow"
        >{contest ? 'CONTEST RUN' : `${data.problem.topic} · ${data.problem.difficulty}`}</span
      >
      <h1>{data.problem.title}</h1>
      <p>{data.problem.description}</p>
    </div>
    <a class="btn ghost" href={contest ? `/contests/${contest}` : '/problems'}>← Back</a>
  </div>
  <div class="solve-grid">
    <section class="panel">
      <div class="panel-head">
        <h2>Problem brief</h2>
        <span class={`pill ${data.problem.difficulty.toLowerCase()}`}
          >{data.problem.difficulty}</span
        >
      </div>
      <p>{data.problem.description}</p>
      <div class="code-box">Tags: {data.problem.tags || '—'}</div>
      <h3>Session attempts</h3>
      {#if data.attempts.length}{#each data.attempts as a}<div class="list-row">
            <div><b>{a.language}</b><small>{a.elapsed_seconds}s · {a.runtime_ms}ms</small></div>
            <VerdictBadge verdict={a.verdict} />
          </div>{/each}{:else}<p class="muted">No attempts in this session yet.</p>{/if}
    </section>
    <section class="panel editor-panel">
      <div class="panel-head">
        <h2>Code workspace</h2>
        <select bind:value={language}
          ><option>C++</option><option>Python</option><option>Java</option></select
        >
      </div>
      <div class="code-box">
        <strong>&#x1F916; AI Judge</strong>
        <p class="tiny muted">
          Submit normally. If your solution fails, Groq-powered AI Judge feedback will appear here automatically with diagnosis and hints.
        </p>
      </div>
      <textarea class="code-editor" bind:value={code} spellcheck="false"></textarea>{#if result}<div
          class={`alert ${result.verdict === 'AC' ? 'success' : 'error'}`}
        >
          <VerdictBadge verdict={result.verdict} /> Runtime {result.runtime_ms}ms · Memory {result.memory_kb}KB{#if result.failed_test_case}
            · Failed test #{result.failed_test_case}{/if}
        </div>{/if}{#if result && result.verdict !== 'AC'}
        <div class="panel ai-feedback-panel">
          <div class="panel-head">
            <div>
              <span class="eyebrow">GROQ-POWERED GUIDANCE</span>
              <h2>&#x1F916; AI Judge Feedback</h2>
            </div>
            <button class="btn ghost" on:click={loadAIFeedback} disabled={aiBusy}>
              {aiBusy ? 'Analyzing...' : aiFeedback ? 'Analyze again' : 'Analyze with AI Judge'}
            </button>
          </div>

          {#if aiBusy}
            <p class="muted">AI Judge is analyzing your failed submission...</p>
          {:else if aiError}
            <div class="alert error">
              {aiError}
            </div>
            <div class="page-actions">
              <button class="btn ghost" on:click={loadAIFeedback}>Retry AI analysis</button>
            </div>
          {:else if aiFeedback?.available}
            <div class="editor-panel">
              <div class="code-box"><strong>Diagnosis</strong><p>{aiFeedback.diagnosis}</p></div>
              <div class="code-box"><strong>&#x1F4A1; Hint 1 &mdash; Concept</strong><p>{aiFeedback.hint_1}</p></div>
              <div class="code-box"><strong>&#x1F4A1; Hint 2 &mdash; Algorithm</strong><p>{aiFeedback.hint_2}</p></div>
              <div class="code-box"><strong>&#x1F41B; Hint 3 &mdash; Likely bug</strong><p>{aiFeedback.hint_3}</p></div>
              <div class="code-box"><strong>&#x1F4D8; What to review</strong><p>{aiFeedback.explanation}</p></div>
            </div>
          {:else}
            <p class="muted">Use AI Judge to analyze this failed attempt.</p>
          {/if}
        </div>
      {/if}

{#if error}<div class="alert error">{error}</div>{/if}
      <div class="page-actions">
        <button class="btn primary" on:click={submit} disabled={busy}
          >{busy ? 'JUDGING...' : 'Submit solution →'}</button
        >
      </div>
      <p class="tiny muted">
        Current framework build preserves the safe deterministic prototype judge. Arbitrary code is
        not executed on the server.
      </p>
    </section>
  </div>{/if}
