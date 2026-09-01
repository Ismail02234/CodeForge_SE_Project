<script lang="ts">
  import { onMount } from 'svelte';
  import { api } from '$lib/api';
  import Loading from '$lib/components/Loading.svelte';
  let rows: any[] = [],
    error = '';
  onMount(async () => {
    try {
      rows = await api.get('/api/universities');
    } catch (e: any) {
      error = e.message;
    }
  });
</script>

<svelte:head><title>Universities · CodeForge</title></svelte:head>
<div class="page-head">
  <div>
    <span class="eyebrow">INSTITUTION ANALYTICS</span>
    <h1>University Leaderboard</h1>
    <p>Live aggregates from users and accepted submissions.</p>
  </div>
  <a class="btn ghost" href="/universities/compare">Compare universities</a>
</div>
{#if !rows.length && !error}<Loading />{:else if error}<div class="alert error">
    {error}
  </div>{:else}<div class="card-grid">
    {#each rows as u, i}<article class="uni-card">
        <small>#{i + 1} · {u.city || '—'}</small>
        <h2>{u.name}</h2>
        <div class="uni-metrics">
          <div><b>{u.avg_rating}</b><span>AVG RATING</span></div>
          <div><b>{u.members}</b><span>MEMBERS</span></div>
          <div><b>{u.solved_problems}</b><span>PROBLEMS</span></div>
        </div>
      </article>{/each}
  </div>{/if}
