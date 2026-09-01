<script lang="ts">
  import { onMount } from 'svelte';
  import { goto } from '$app/navigation';
  import { api } from '$lib/api';
  import { auth } from '$lib/stores/auth';
  import Loading from '$lib/components/Loading.svelte';
  let data: any = null,
    error = '',
    name = '',
    starts_at = '',
    problem_ids: string[] = [];
  let opponent = '',
    problem = '';
  async function load() {
    try {
      data = await api.get('/api/contests');
    } catch (e: any) {
      error = e.message;
    }
  }
  onMount(load);
  async function create() {
    try {
      const r = await api.post('/api/contests', { name, starts_at, problem_ids });
      goto(`/contests/${r.id}`);
    } catch (e: any) {
      error = e.message;
    }
  }
  async function duel() {
    try {
      const r = await api.post('/api/duels', { opponent_id: opponent, problem_id: problem });
      goto(`/contests/${r.id}`);
    } catch (e: any) {
      error = e.message;
    }
  }
</script>

<svelte:head><title>Contests · CodeForge</title></svelte:head>
<div class="page-head">
  <div>
    <span class="eyebrow">COMPETITION</span>
    <h1>Contests</h1>
    <p>Local contests, quick duels and live scoreboards.</p>
  </div>
</div>
{#if !data && !error}<Loading />{:else if error && !data}<div class="alert error">
    {error}
  </div>{:else}<section class="two-col contest-tools">
    <div class="panel">
      <span class="eyebrow">QUICK DUEL</span>
      <h2>Challenge a coder</h2>
      <select bind:value={opponent}
        ><option value="">Opponent</option>{#each data.users as u}<option value={u.id}
            >{u.username} · {u.rating}</option
          >{/each}</select
      ><select bind:value={problem}
        ><option value="">Problem</option>{#each data.problems as p}<option value={p.id}
            >{p.title} · {p.difficulty}</option
          >{/each}</select
      ><button class="btn primary" on:click={duel} disabled={!opponent || !problem}
        >Create duel</button
      >
    </div>
    {#if $auth.user?.role === 'admin'}<form class="panel" on:submit|preventDefault={create}>
        <span class="eyebrow">ADMIN</span>
        <h2>Create local contest</h2>
        <input bind:value={name} placeholder="Contest name" required /><input
          type="datetime-local"
          bind:value={starts_at}
          required
        /><select multiple bind:value={problem_ids} size="5"
          >{#each data.problems as p}<option value={p.id}>{p.title}</option>{/each}</select
        ><button class="btn primary">Create contest</button>
      </form>{:else}<div class="panel">
        <span class="eyebrow">STRUCTURE</span>
        <h2>Contest model</h2>
        <p>
          Contests reuse the same problem and submission history. Accepted contest solves update
          participant score transactionally.
        </p>
        <a class="btn ghost" href="/how-it-works">See architecture</a>
      </div>{/if}
  </section>
  <section class="panel">
    <div class="panel-head">
      <h2>Contest feed</h2>
      <span>{data.contests.length} events</span>
    </div>
    <div class="table-wrap">
      <table>
        <thead
          ><tr
            ><th>Contest</th><th>Type</th><th>Status</th><th>Starts</th><th>Participants</th><th
            ></th></tr
          ></thead
        ><tbody
          >{#each data.contests as c}<tr
              ><td><b>{c.name}</b></td><td>{c.type}</td><td
                ><span class={`pill ${c.status.toLowerCase()}`}>{c.status}</span></td
              ><td>{c.starts_at}</td><td>{c.participants}</td><td
                ><a class="btn tiny ghost" href={`/contests/${c.id}`}>View →</a></td
              ></tr
            >{/each}</tbody
        >
      </table>
    </div>
  </section>{/if}
