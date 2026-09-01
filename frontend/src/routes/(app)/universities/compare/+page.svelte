<script lang="ts">
  import { onMount } from 'svelte';
  import { api } from '$lib/api';
  let data: any = null,
    a = '',
    b = '',
    error = '';
  async function load() {
    try {
      const p = new URLSearchParams();
      if (a) p.set('a', a);
      if (b) p.set('b', b);
      data = await api.get('/api/universities/compare?' + p.toString());
    } catch (e: any) {
      error = e.message;
    }
  }
  onMount(load);
</script>

<svelte:head><title>University comparison · CodeForge</title></svelte:head>
<div class="page-head">
  <div>
    <span class="eyebrow">INSTITUTION ANALYTICS</span>
    <h1>University Comparison</h1>
    <p>Compare live membership, rating and accepted-problem coverage.</p>
  </div>
  <a class="btn ghost" href="/universities">← Leaderboard</a>
</div>
<form class="filter-bar" on:submit|preventDefault={load}>
  <select bind:value={a}
    ><option value="">University A</option>{#each data?.universities || [] as u}<option
        value={u.name}>{u.name}</option
      >{/each}</select
  ><span>VS</span><select bind:value={b}
    ><option value="">University B</option>{#each data?.universities || [] as u}<option
        value={u.name}>{u.name}</option
      >{/each}</select
  ><button class="btn primary">Compare</button>
</form>
{#if error}<div class="alert error">{error}</div>{/if}{#if data?.left && data?.right}<section
    class="rivalry-grid"
  >
    <div class="rival-card">
      <small>{data.left.city}</small>
      <h2>{data.left.name}</h2>
      <strong>{data.left.avg_rating}</strong>
      <p>{data.left.members} members · {data.left.solved_problems} solved</p>
    </div>
    <div class="versus"><b>VS</b></div>
    <div class="rival-card">
      <small>{data.right.city}</small>
      <h2>{data.right.name}</h2>
      <strong>{data.right.avg_rating}</strong>
      <p>{data.right.members} members · {data.right.solved_problems} solved</p>
    </div>
  </section>{/if}
