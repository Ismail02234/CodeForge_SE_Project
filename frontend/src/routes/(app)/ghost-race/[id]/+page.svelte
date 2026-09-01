<script lang="ts">
  import { onMount } from 'svelte';
  import { page } from '$app/stores';
  import { api } from '$lib/api';
  import { duration } from '$lib/format';
  import Loading from '$lib/components/Loading.svelte';
  import VerdictBadge from '$lib/components/VerdictBadge.svelte';
  let data: any = null,
    error = '',
    code = '',
    language = 'C++',
    busy = false,
    timer: any;
  async function load() {
    try {
      data = await api.get(`/api/ghost-races/${$page.params.id}`);
      if (data.race.result !== 'active' && timer) clearInterval(timer);
    } catch (e: any) {
      error = e.message;
    }
  }
  onMount(() => {
    load();
    timer = setInterval(load, 1500);
    return () => clearInterval(timer);
  });
  async function submit() {
    busy = true;
    try {
      await api.post(`/api/ghost-races/${$page.params.id}/submit`, { source_code: code, language });
      await load();
    } catch (e: any) {
      error = e.message;
    } finally {
      busy = false;
    }
  }
  async function forfeit() {
    await api.post(`/api/ghost-races/${$page.params.id}/forfeit`);
    await load();
  }
</script>

<svelte:head><title>Ghost Race · CodeForge</title></svelte:head>{#if !data && !error}<Loading
  />{:else if error && !data}<div class="alert error">{error}</div>{:else}<div class="page-head">
    <div>
      <span class="eyebrow">GHOST RACE · {data.race.result}</span>
      <h1>{data.race.problem_title}</h1>
      <p>{data.race.challenger_username} vs {data.race.ghost_username}</p>
    </div>
    <a class="btn ghost" href="/ghost-race">← Race lobby</a>
  </div>
  <section class="race-score">
    <div><small>YOU</small><strong>{duration(data.virtual_elapsed)}</strong></div>
    <div class="race-vs">VS</div>
    <div><small>GHOST TARGET</small><strong>{duration(data.race.ghost_time)}</strong></div>
    {#if data.race.result !== 'active'}<div class="race-result">
        <small>RESULT</small><strong>{data.race.result.toUpperCase()}</strong>
      </div>{/if}
  </section>
  <section class="two-col">
    <div class="panel">
      <div class="panel-head">
        <h2>Ghost timeline</h2>
        <span>source hidden</span>
      </div>
      {#each data.ghost_events as e}<div class="timeline-row">
          <span>{duration(e.elapsed_seconds)}</span><VerdictBadge verdict={e.verdict} /><small
            >{e.language} · {e.runtime_ms}ms</small
          >
        </div>{/each}
    </div>
    <div class="panel">
      <div class="panel-head">
        <h2>Your timeline</h2>
        <span>{data.challenger_events.length} attempts</span>
      </div>
      {#each data.challenger_events as e}<div class="timeline-row">
          <span>{duration(e.elapsed_seconds)}</span><VerdictBadge verdict={e.verdict} /><small
            >{e.language} · {e.runtime_ms}ms</small
          >
        </div>{/each}
    </div>
  </section>
  {#if data.race.result === 'active'}<section class="panel editor-panel">
      <div class="panel-head">
        <h2>Race workspace</h2>
        <select bind:value={language}
          ><option>C++</option><option>Python</option><option>Java</option></select
        >
      </div>
      <p>{data.race.description}</p>
      <textarea
        class="code-editor"
        bind:value={code}
        placeholder={data.race.starter_code || '// write solution'}></textarea>{#if error}<div
          class="alert error"
        >
          {error}
        </div>{/if}
      <div class="page-actions">
        <button class="btn primary" on:click={submit} disabled={busy}
          >{busy ? 'JUDGING...' : 'Submit →'}</button
        ><button class="btn danger" on:click={forfeit}>Forfeit</button>
      </div>
    </section>{/if}{/if}
