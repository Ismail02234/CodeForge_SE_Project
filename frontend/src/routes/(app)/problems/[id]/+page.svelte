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
  let proveNote: string = '';
  $: contest = $page.url.searchParams.get('contest');
  $: learningModule = $page.url.searchParams.get('learning_module');
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
    proveNote = '';
    try {
      result = await api.post(`/api/problems/${$page.params.id}/submit`, {
        source_code: code,
        language,
        contest_id: contest || null,
      });
      data = await api.get(`/api/problems/${$page.params.id}`);

      if (result.verdict === 'AC' && learningModule) {
        try {
          const proveResult = await api.post(`/api/learn/${learningModule}/proven-completion`);
          if (proveResult.module_completed) {
            proveNote = `Module complete! +${proveResult.prove_score} XP · Mastery ${proveResult.mastery_score}`;
          } else if (proveResult.prove_completed) {
            proveNote = `Prove stage complete! +${proveResult.prove_score} XP · Mastery ${proveResult.mastery_score}`;
          } else {
            proveNote = `Prove progress updated. ${proveResult.prove_score} XP earned so far.`;
          }
        } catch (e: any) {
          proveNote = '';
        }
      }
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
    <a class="btn ghost" href={contest ? `/contests/${contest}` : learningModule ? `/learn/${learningModule}` : '/problems'}>← Back</a>
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
      <textarea class="code-editor" bind:value={code} spellcheck="false"></textarea>      {#if result}<div
          class={`alert ${result.verdict === 'AC' ? 'success' : 'error'}`}
        >
          <VerdictBadge verdict={result.verdict} /> Runtime {result.runtime_ms}ms · Memory {result.memory_kb}KB{#if result.failed_test_case}
            · Failed test #{result.failed_test_case}{/if}
        </div>{/if}{#if proveNote}<div class="alert success">{proveNote}</div>{/if}{#if error}<div class="alert error">{error}</div>{/if}
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
