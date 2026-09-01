<script lang="ts">
  import { onMount } from 'svelte';
  import { page } from '$app/stores';
  import { api } from '$lib/api';
  import Loading from '$lib/components/Loading.svelte';
  let data: any = null;
  let a = '';
  let b = '';
  let error = '';
  async function load() {
    try {
      const p = new URLSearchParams();
      if (a) p.set('a', a);
      if (b) p.set('b', b);
      data = await api.get('/api/rivalry?' + p.toString());
      if (!a && data.left) a = data.left.user.id;
    } catch (e: any) {
      error = e.message;
    }
  }
  onMount(() => {
    a = $page.url.searchParams.get('a') || '';
    b = $page.url.searchParams.get('b') || '';
    load();
  });
</script>

<svelte:head><title>Rivalry · CodeForge</title></svelte:head>
<div class="page-head">
  <div>
    <span class="eyebrow">HEAD TO HEAD</span>
    <h1>Rivalry Lab</h1>
    <p>Compare two live Performance Profiles and estimate the competitive edge.</p>
  </div>
</div>
{#if !data && !error}<Loading />{:else}<form class="filter-bar" on:submit|preventDefault={load}>
    <select bind:value={a}
      >{#each data?.users || [] as u}<option value={u.id}>{u.username} · {u.rating}</option
        >{/each}</select
    ><span>VS</span><select bind:value={b}
      ><option value="">Choose opponent</option>{#each data?.users || [] as u}<option value={u.id}
          >{u.username} · {u.rating}</option
        >{/each}</select
    ><button class="btn primary">Compare</button>
  </form>
  {#if error}<div class="alert error">{error}</div>{/if}{#if data?.right}<section
      class="rivalry-grid"
    >
      <div class="rival-card">
        <small>LEFT</small>
        <h2>{data.left.user.username}</h2>
        <strong>{data.left.user.rating}</strong>
        <p>{data.left.archetype.name}</p>
        <div class="profile-score-small">PROFILE SCORE {data.left.overall}%</div>
      </div>
      <div class="versus">
        <span>{data.prediction.left_probability}%</span><b>VS</b><span
          >{data.prediction.right_probability}%</span
        ><small>predicted edge: {data.prediction.edge}</small>
      </div>
      <div class="rival-card">
        <small>RIGHT</small>
        <h2>{data.right.user.username}</h2>
        <strong>{data.right.user.rating}</strong>
        <p>{data.right.archetype.name}</p>
        <div class="profile-score-small">PROFILE SCORE {data.right.overall}%</div>
      </div>
    </section>
    <section class="panel">
      <h2>Dimension comparison</h2>
      {#each Object.keys(data.left.dimensions) as key}<div class="compare-row">
          <span>{data.left.dimension_labels[key]}</span><b>{data.left.dimensions[key]}</b>
          <div class="dual-bar">
            <i style={`width:${data.left.dimensions[key]}%`}></i><em
              style={`width:${data.right.dimensions[key]}%`}
            ></em>
          </div>
          <b>{data.right.dimensions[key]}</b>
        </div>{/each}
    </section>{/if}{/if}
