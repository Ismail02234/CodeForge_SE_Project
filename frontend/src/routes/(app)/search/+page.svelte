<script lang="ts">
  import { onMount } from 'svelte';
  import { page } from '$app/stores';
  import { api } from '$lib/api';
  let q = '';
  let data: any = null;
  let error = '';
  async function load() {
    if (q.trim().length < 2) {
      data = { users: [], problems: [], universities: [] };
      return;
    }
    try {
      data = await api.get(`/api/search?q=${encodeURIComponent(q)}`);
    } catch (e: any) {
      error = e.message;
    }
  }
  onMount(() => {
    q = $page.url.searchParams.get('q') || '';
    load();
  });
</script>

<svelte:head><title>Search · CodeForge</title></svelte:head>
<div class="page-head">
  <div>
    <span class="eyebrow">GLOBAL DISCOVERY</span>
    <h1>Search</h1>
    <p>Find coders, problems and universities.</p>
  </div>
</div>
<form class="filter-bar" on:submit|preventDefault={load}>
  <input class="grow" bind:value={q} placeholder="Search CodeForge..." /><button class="btn primary"
    >Search</button
  >
</form>
{#if error}<div class="alert error">{error}</div>{/if}{#if data}<section class="search-grid">
    <div class="panel">
      <h2>Users</h2>
      {#each data.users as u}<a class="list-row link" href={`/profile/${u.id}`}
          ><div><b>{u.username}</b><small>{u.university || 'Independent'} · {u.rank}</small></div>
          <strong>{u.rating}</strong></a
        >{/each}
    </div>
    <div class="panel">
      <h2>Problems</h2>
      {#each data.problems as p}<a class="list-row link" href={`/problems/${p.id}`}
          ><div><b>{p.title}</b><small>{p.topic}</small></div>
          <span class={`pill ${p.difficulty.toLowerCase()}`}>{p.difficulty}</span></a
        >{/each}
    </div>
    <div class="panel">
      <h2>Universities</h2>
      {#each data.universities as u}<div class="list-row">
          <div><b>{u.name}</b><small>{u.city || '—'}</small></div>
        </div>{/each}
    </div>
  </section>{/if}
