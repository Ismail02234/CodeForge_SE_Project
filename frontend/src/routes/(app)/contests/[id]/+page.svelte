<script lang="ts">
  import { onMount } from 'svelte';
  import { page } from '$app/stores';
  import { api } from '$lib/api';
  import { auth } from '$lib/stores/auth';
  import Loading from '$lib/components/Loading.svelte';
  let data: any = null,
    error = '';
  async function load() {
    try {
      data = await api.get(`/api/contests/${$page.params.id}`);
    } catch (e: any) {
      error = e.message;
    }
  }
  onMount(load);
  async function join() {
    await api.post(`/api/contests/${$page.params.id}/join`);
    await load();
  }
  async function close() {
    await api.post(`/api/contests/${$page.params.id}/close`);
    await load();
  }
</script>

<svelte:head><title>{data?.contest?.name || 'Contest'} · CodeForge</title></svelte:head
>{#if !data && !error}<Loading />{:else if error}<div class="alert error">{error}</div>{:else}<div
    class="page-head"
  >
    <div>
      <span class="eyebrow">{data.contest.type} · {data.contest.status}</span>
      <h1>{data.contest.name}</h1>
      <p>Starts {data.contest.starts_at}</p>
    </div>
    <div class="page-actions">
      {#if !data.joined}<button class="btn primary" on:click={join}>Join contest</button
        >{/if}{#if $auth.user?.role === 'admin' && data.contest.status !== 'Past'}<button
          class="btn danger"
          on:click={close}>Close contest</button
        >{/if}
    </div>
  </div>
  <section class="two-col">
    <div class="panel">
      <div class="panel-head">
        <h2>Problem set</h2>
        <span>{data.problems.length}</span>
      </div>
      {#each data.problems as p}<div class="list-row">
          <div><b>{p.title}</b><small>{p.topic} · {p.difficulty} · {p.points} pts</small></div>
          <a
            class="btn tiny ghost"
            href={data.joined
              ? `/problems/${p.id}?contest=${data.contest.id}`
              : `/problems/${p.id}`}>{data.joined ? 'Compete' : 'Practice'}</a
          >
        </div>{/each}
    </div>
    <div class="panel">
      <div class="panel-head"><h2>Leaderboard</h2></div>
      {#each data.leaderboard as row, i}<a class="list-row link" href={`/profile/${row.id}`}
          ><div><b>#{i + 1} {row.username}</b><small>Rating {row.rating} · {row.rank}</small></div>
          <strong>{row.score}</strong></a
        >{/each}
    </div>
  </section>{/if}
