<script lang="ts">
  import WeakestFieldTarget from '$lib/components/WeakestFieldTarget.svelte';
  import ProblemRecommendations from '$lib/components/ProblemRecommendations.svelte';
  import { onMount } from 'svelte';
  import { api } from '$lib/api';
  import Loading from '$lib/components/Loading.svelte';
  let data: any = null;
  let q = '';
  let topic = '';
  let difficulty = '';
  let error = '';
  async function load() {
    try {
      const params = new URLSearchParams();
      if (q) params.set('q', q);
      if (topic) params.set('topic', topic);
      if (difficulty) params.set('difficulty', difficulty);
      data = await api.get('/api/problems?' + params.toString());
    } catch (e: any) {
      error = e.message;
    }
  }
  onMount(load);
</script>

<svelte:head><title>Problems · CodeForge</title></svelte:head>
<div class="page-head">
  <div>
    <span class="eyebrow">PRACTICE LIBRARY</span>
    <h1>Problems</h1>
    <p>
      Every attempt contributes to your Performance Profile and can later become a Ghost Race
      timeline.
    </p>
  </div>
</div>
<ProblemRecommendations />
<WeakestFieldTarget />

<form class="filter-bar" on:submit|preventDefault={load}>
  <input bind:value={q} placeholder="Search title, topic or tag" /><select bind:value={topic}
    ><option value="">All topics</option>{#each data?.topics || [] as t}<option>{t}</option
      >{/each}</select
  ><select bind:value={difficulty}
    ><option value="">All difficulty</option><option>Easy</option><option>Medium</option><option
      >Hard</option
    ></select
  ><button class="btn primary">Filter</button><button
    type="button"
    class="btn ghost"
    on:click={() => {
      q = '';
      topic = '';
      difficulty = '';
      load();
    }}>Reset</button
  >
</form>
{#if !data && !error}<Loading />{:else if error}<div class="alert error">{error}</div>{:else}<div
    class="table-wrap"
  >
    <table>
      <thead
        ><tr
          ><th>ID</th><th>Problem</th><th>Topic</th><th>Difficulty</th><th>Status</th><th></th></tr
        ></thead
      ><tbody
        >{#each data.problems as p}<tr
            ><td class="mono">{p.id}</td><td><b>{p.title}</b><small>{p.tags || ''}</small></td><td
              >{p.topic}</td
            ><td><span class={`pill ${p.difficulty.toLowerCase()}`}>{p.difficulty}</span></td><td
              >{#if p.solved}<span class="green">● SOLVED</span>{:else}<span class="muted"
                  >UNSOLVED</span
                >{/if}</td
            ><td><a class="btn tiny ghost" href={`/problems/${p.id}`}>Open →</a></td></tr
          >{/each}</tbody
      >
    </table>
  </div>{/if}
