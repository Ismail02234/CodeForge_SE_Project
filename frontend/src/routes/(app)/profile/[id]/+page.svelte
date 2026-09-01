<script lang="ts">
  import { onMount } from 'svelte';
  import { page } from '$app/stores';
  import { api } from '$lib/api';
  import Loading from '$lib/components/Loading.svelte';
  import VerdictBadge from '$lib/components/VerdictBadge.svelte';
  let data: any = null;
  let error = '';
  onMount(async () => {
    try {
      data = await api.get(`/api/profiles/${$page.params.id}`);
    } catch (e: any) {
      error = e.message;
    }
  });
</script>

<svelte:head><title>{data?.user?.username || 'Profile'} · CodeForge</title></svelte:head
>{#if !data && !error}<Loading />{:else if error}<div class="alert error">{error}</div>{:else}<div
    class="profile-hero"
  >
    <div class="profile-avatar">{data.user.username.slice(0, 1).toUpperCase()}</div>
    <div>
      <span class="eyebrow">CODER PROFILE</span>
      <h1>{data.user.username}</h1>
      <p>{data.user.university || 'Independent'} · {data.user.rank}</p>
    </div>
    <div class="profile-rating"><small>RATING</small><strong>{data.user.rating}</strong></div>
  </div>
  <section class="metric-grid">
    <div class="metric"><small>SUBMISSIONS</small><strong>{data.stats.submissions}</strong></div>
    <div class="metric"><small>ACCEPTED</small><strong>{data.stats.accepted}</strong></div>
    <div class="metric"><small>SOLVED</small><strong>{data.stats.solved}</strong></div>
    <div class="metric">
      <small>XP</small><strong>{data.gamification.xp}</strong><span>{data.gamification.level}</span>
    </div>
  </section>
  <div class="page-actions">
    <a class="btn primary" href={`/code-dna?user=${data.user.id}`}>View Code DNA</a><a
      class="btn ghost"
      href={`/rivalry?a=${data.user.id}`}>Compare</a
    >
  </div>
  <section class="two-col">
    <div class="panel">
      <div class="panel-head">
        <h2>Achievements</h2>
        <span>{data.gamification.badges.length}</span>
      </div>
      <div class="tag-row">
        {#each data.gamification.badges as badge}<span>{badge}</span
          >{/each}{#if !data.gamification.badges.length}<span>Build activity to unlock badges.</span
          >{/if}
      </div>
      <div class="progress"><span style={`width:${data.gamification.progress}%`}></span></div>
      <p class="muted">
        {data.gamification.level}{data.gamification.next_level
          ? ` → ${data.gamification.next_level}`
          : ''}
      </p>
    </div>
    <div class="panel">
      <div class="panel-head"><h2>Recent submissions</h2></div>
      {#each data.recent_submissions as row}<div class="list-row">
          <div><b>{row.title}</b><small>{row.topic} · {row.language}</small></div>
          <VerdictBadge verdict={row.verdict} />
        </div>{/each}
    </div>
  </section>{/if}
