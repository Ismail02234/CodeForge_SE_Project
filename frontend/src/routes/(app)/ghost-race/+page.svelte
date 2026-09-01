<script lang="ts">
  import { goto } from '$app/navigation';
  import { onMount } from 'svelte';
  import Loading from '$lib/components/Loading.svelte';
  import { api } from '$lib/api';

  type GhostOption = {
    session_id: string;
    ghost_user_id: string;
    ghost_username: string;
    problem_id: string;
    problem_title: string;
    topic: string;
    difficulty: string;
    solve_time_seconds: number;
    attempts: number;
  };

  type RaceHistoryItem = {
    id: string;
    problem_title: string;
    ghost_username: string;
    result: string;
  };

  let ghosts: GhostOption[] = [];
  let history: RaceHistoryItem[] = [];
  let selectedSessionId = '';
  let speed = 4;
  let error = '';
  let ready = false;
  let starting = false;

  async function load() {
    try {
      const [ghostOptions, raceHistory] = await Promise.all([
        api.get<GhostOption[]>('/api/ghost-races/options'),
        api.get<RaceHistoryItem[]>('/api/ghost-races/history'),
      ]);

      ghosts = ghostOptions;
      history = raceHistory;

      // If the available options changed after a refresh, do not keep a
      // selection that no longer exists.
      if (selectedSessionId && !ghosts.some((ghost) => ghost.session_id === selectedSessionId)) {
        selectedSessionId = '';
      }
    } catch (e: any) {
      error = e.message;
    } finally {
      ready = true;
    }
  }

  onMount(() => {
    void load();
  });

  async function start() {
    if (!selectedSessionId || starting) {
      return;
    }

    starting = true;
    error = '';

    try {
      const race = await api.post<{ id: string }>('/api/ghost-races', {
        ghost_session_id: selectedSessionId,
        playback_speed: speed,
      });

      await goto(`/ghost-race/${race.id}`);
    } catch (e: any) {
      error = e.message;
    } finally {
      starting = false;
    }
  }
</script>

<svelte:head>
  <title>Ghost Race · CodeForge</title>
</svelte:head>

<div class="page-head">
  <div>
    <span class="eyebrow">HISTORICAL REPLAY</span>
    <h1>Ghost Race</h1>
    <p>Race another coder's recorded solving timeline. Their source code stays hidden.</p>
  </div>
</div>

{#if !ready}
  <Loading />
{:else}
  <section class="two-col">
    <div class="panel">
      <div class="panel-head">
        <h2>Choose a historical run</h2>
        <span>{ghosts.length} ghosts</span>
      </div>

      {#if ghosts.length === 0}
        <div class="alert">No historical runs are currently available from other users.</div>
      {:else}
        <div class="ghost-list">
          {#each ghosts as ghost (ghost.session_id)}
            <label class="ghost-option" class:selected={selectedSessionId === ghost.session_id}>
              <input
                type="radio"
                name="ghost-session"
                bind:group={selectedSessionId}
                value={ghost.session_id}
              />

              <div>
                <b>{ghost.ghost_username}</b>
                <span>{ghost.problem_title}</span>
                <small>
                  {ghost.topic} · {ghost.difficulty} · {ghost.solve_time_seconds}s ·
                  {ghost.attempts} attempts
                </small>
              </div>
            </label>
          {/each}
        </div>
      {/if}

      <div class="filter-bar">
        <select bind:value={speed} aria-label="Replay speed">
          <option value={1}>1× replay</option>
          <option value={2}>2× replay</option>
          <option value={4}>4× replay</option>
        </select>

        <button class="btn primary" disabled={!selectedSessionId || starting} on:click={start}>
          {starting ? 'STARTING...' : 'Start race →'}
        </button>
      </div>

      {#if error}
        <div class="alert error">{error}</div>
      {/if}
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

      {#if history.length === 0}
        <p>No completed Ghost Races yet.</p>
      {:else}
        {#each history as race (race.id)}
          <a class="list-row link" href={`/ghost-race/${race.id}`}>
            <div>
              <b>{race.problem_title}</b>
              <small>vs {race.ghost_username}</small>
            </div>
            <span class={`pill ${race.result}`}>{race.result}</span>
          </a>
        {/each}
      {/if}
    </div>
  </section>
{/if}
