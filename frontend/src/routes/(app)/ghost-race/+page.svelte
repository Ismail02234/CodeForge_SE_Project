<script lang="ts">
  import { onMount } from 'svelte';
  import { goto } from '$app/navigation';
  import { api } from '$lib/api';
  import Loading from '$lib/components/Loading.svelte';
  let ghosts: any[] = [],
    history: any[] = [],
    selected = '',
    speed = 4,
    error = '',
    ready = false;
  async function load() {
    try {
      [ghosts, history] = await Promise.all([
        api.get('/api/ghost-races/options'),
        api.get('/api/ghost-races/history'),
      ]);
    } catch (e: any) {
      error = e.message;
    } finally {
      ready = true;
    }
  }
  onMount(load);
  async function start() {
    try {
      const r = await api.post('/api/ghost-races', {
        ghost_session_id: selected,
        playback_speed: speed,
      });
      goto(`/ghost-race/${r.id}`);
    } catch (e: any) {
      error = e.message;
    }
  }
</script>

<svelte:head><title>Ghost Race · CodeForge</title></svelte:head>
<div class="page-head">
  <div>
    <span class="eyebrow">HISTORICAL REPLAY</span>
    <h1>Ghost Race</h1>
    <p>Race another coder's recorded solving timeline. Their source code stays hidden.</p>
  </div>
</div>
{#if !ready}<Loading />{:else}<section class="two-col">
    <div class="panel">
      <div class="panel-head">
        <h2>Choose a historical run</h2>
        <span>{ghosts.length} ghosts</span>
      </div>
      <div class="ghost-list">
        {#each ghosts as g}<label class:selected={selected === g.id} class="ghost-option"
            ><input type="radio" bind:group={selected} value={g.id} />
            <div>
              <b>{g.username}</b><span>{g.problem_title}</span><small
                >{g.topic} · {g.difficulty} · {g.solve_time_seconds}s · {g.attempts} attempts</small
              >
            </div></label
          >{/each}
      </div>
      <div class="filter-bar">
        <select bind:value={speed}
          ><option value={1}>1× replay</option><option value={2}>2× replay</option><option value={4}
            >4× replay</option
          ><option value={8}>8× replay</option></select
        ><button class="btn primary" disabled={!selected} on:click={start}>Start race →</button>
      </div>
      {#if error}<div class="alert error">{error}</div>{/if}
    </div>
    <div class="panel">
      <span class="eyebrow">PRIVACY</span>
      <h2>Replay, don't reveal</h2>
      <p>
        The ghost consists only of verdict timestamps, runtime metadata and the final solve time.
        Source code is never exposed.
      </p>
      <div class="code-box">START → WA/TLE/AC events → FINISH</div>
      <h3>Your race history</h3>
      {#each history as h}<a class="list-row link" href={`/ghost-race/${h.id}`}
          ><div><b>{h.problem_title}</b><small>vs {h.ghost_username}</small></div>
          <span class={`pill ${h.result}`}>{h.result}</span></a
        >{/each}
    </div>
  </section>{/if}
