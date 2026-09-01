<script lang="ts">
  import { onMount } from 'svelte';
  import { goto } from '$app/navigation';
  import { api } from '$lib/api';
  import VerdictBadge from '$lib/components/VerdictBadge.svelte';
  let challenges: any[] = [],
    leaderboard: any[] = [],
    battles: any[] = [],
    users: any[] = [],
    selected = '',
    query = 'SELECT ',
    result: any = null,
    opponent = '',
    error = '';
  async function load() {
    try {
      [challenges, leaderboard, battles, users] = await Promise.all([
        api.get('/api/sql/challenges'),
        api.get('/api/sql/leaderboard'),
        api.get('/api/sql/battles'),
        api.get('/api/users/options'),
      ]);
      if (!selected && challenges.length) selected = challenges[0].id;
    } catch (e: any) {
      error = e.message;
    }
  }
  onMount(load);
  $: challenge = challenges.find((c) => c.id === selected);
  async function practice() {
    try {
      result = await api.post(`/api/sql/challenges/${selected}/submit`, { query });
    } catch (e: any) {
      error = e.message;
    }
  }
  async function battle() {
    try {
      const r = await api.post('/api/sql/battles', {
        challenge_id: selected,
        opponent_id: opponent,
      });
      goto(`/sql-battle/${r.id}`);
    } catch (e: any) {
      error = e.message;
    }
  }
</script>

<svelte:head><title>SQL Battle · CodeForge</title></svelte:head>
<div class="page-head">
  <div>
    <span class="eyebrow">READ-ONLY DBMS COMBAT</span>
    <h1>SQL Battle Arena</h1>
    <p>Correctness first. Then speed and estimated efficiency.</p>
  </div>
</div>
{#if error}<div class="alert error">{error}</div>{/if}
<section class="sql-layout">
  <aside class="panel challenge-list">
    <h2>Challenges</h2>
    {#each challenges as c}<button
        class:active={selected === c.id}
        on:click={() => (selected = c.id)}
        ><b>{c.title}</b><small>{c.difficulty} · {c.max_score} pts · {c.accepted_attempts} AC</small
        ></button
      >{/each}
  </aside>
  <section class="panel editor-panel">
    {#if challenge}<span class="eyebrow">{challenge.difficulty}</span>
      <h2>{challenge.title}</h2>
      <p>{challenge.description}</p>
      <div class="code-box">
        Allowed tables: arena_users, arena_universities, arena_problems, arena_submissions
      </div>
      <textarea class="code-editor sql" bind:value={query}></textarea><button
        class="btn primary"
        on:click={practice}>Run practice →</button
      >{#if result}<div class={`alert ${result.correct ? 'success' : 'error'}`}>
          <VerdictBadge verdict={result.status} /> Score {result.score} · {result.execution_time_ms}ms
          · Efficiency {result.efficiency_score}
        </div>
        {#if result.rows?.length}<div class="table-wrap compact">
            <table>
              <thead
                ><tr
                  >{#each Object.keys(result.rows[0]) as k}<th>{k}</th>{/each}</tr
                ></thead
              ><tbody
                >{#each result.rows as row}<tr
                    >{#each Object.values(row) as v}<td>{v}</td>{/each}</tr
                  >{/each}</tbody
              >
            </table>
          </div>{/if}{/if}{/if}
  </section>
</section>
<section class="two-col">
  <div class="panel">
    <h2>Challenge another coder</h2>
    <select bind:value={opponent}
      ><option value="">Choose opponent</option>{#each users as u}<option value={u.id}
          >{u.username} · {u.rating}</option
        >{/each}</select
    ><button class="btn primary" disabled={!selected || !opponent} on:click={battle}
      >Create battle</button
    >
    <h3>Recent battles</h3>
    {#each battles as b}<a class="list-row link" href={`/sql-battle/${b.id}`}
        ><div><b>{b.title}</b><small>{b.player1_name} vs {b.player2_name}</small></div>
        <span class={`pill ${b.status}`}>{b.status}</span></a
      >{/each}
  </div>
  <div class="panel">
    <h2>SQL leaderboard</h2>
    {#each leaderboard as row, i}<a class="list-row link" href={`/profile/${row.id}`}
        ><div><b>#{i + 1} {row.username}</b><small>{row.accepted_runs} accepted</small></div>
        <strong>{row.best_score}</strong></a
      >{/each}
  </div>
</section>
