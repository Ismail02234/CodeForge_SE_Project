<script lang="ts">
  import { onMount } from 'svelte';
  import { page } from '$app/stores';
  import { api } from '$lib/api';
  import VerdictBadge from '$lib/components/VerdictBadge.svelte';
  import Loading from '$lib/components/Loading.svelte';

  type Battle = {
    id: string;
    challenge_id: string;
    player1_id: string;
    player2_id: string;
    player1_name: string;
    player2_name: string;
    title: string;
    description: string;
    status: string;
    max_score: number;
  };

  type Attempt = {
    id: string;
    user_id: string;
    username: string;
    status: string;
    execution_time_ms: number;
    efficiency_score: number;
    score: number;
  };

  type BattleResponse = {
    battle: Battle;
    attempts: Attempt[];
  };

  type SubmitResult = {
    correct?: boolean;
    status: string;
    feedback: string;
    score: number;
  };

  let data: BattleResponse | null = null;
  let query = 'SELECT ';
  let result: SubmitResult | null = null;
  let error = '';
  let submitting = false;

  function bestScore(userId: string): number {
    if (!data) return 0;

    const scores = data.attempts
      .filter((attempt) => attempt.user_id === userId)
      .map((attempt) => Number(attempt.score) || 0);

    return scores.length ? Math.max(...scores) : 0;
  }

  async function load() {
    try {
      data = await api.get<BattleResponse>(`/api/sql/battles/${$page.params.id}`);
      error = '';
    } catch (e: any) {
      error = e?.message || 'Could not load SQL battle.';
    }
  }

  onMount(() => {
    void load();
  });

  async function submit() {
    if (!data || submitting) return;

    submitting = true;
    error = '';

    try {
      result = await api.post<SubmitResult>(
        `/api/sql/challenges/${data.battle.challenge_id}/submit`,
        {
          query,
          battle_id: data.battle.id,
        }
      );
      await load();
    } catch (e: any) {
      error = e?.message || 'Query submission failed.';
    } finally {
      submitting = false;
    }
  }
</script>

<svelte:head>
  <title>SQL Battle · CodeForge</title>
</svelte:head>

{#if !data && !error}
  <Loading />
{:else if error && !data}
  <div class="alert error">{error}</div>
{:else if data}
  <div class="page-head">
    <div>
      <span class="eyebrow">SQL BATTLE · {data.battle.status}</span>
      <h1>{data.battle.title}</h1>
      <p>{data.battle.player1_name} vs {data.battle.player2_name}</p>
    </div>
    <a class="btn ghost" href="/sql-battle">← Arena</a>
  </div>

  <section class="race-score">
    <div>
      <small>{data.battle.player1_name}</small>
      <strong>{bestScore(data.battle.player1_id)}</strong>
    </div>
    <div class="race-vs">VS</div>
    <div>
      <small>{data.battle.player2_name}</small>
      <strong>{bestScore(data.battle.player2_id)}</strong>
    </div>
  </section>

  {#if data.battle.status === 'active'}
    <section class="panel editor-panel">
      <h2>{data.battle.description}</h2>
      <div class="code-box">SELECT / WITH only · max {data.battle.max_score} points</div>
      <textarea class="code-editor sql" bind:value={query}></textarea>
      <button class="btn primary" on:click={submit} disabled={submitting || !query.trim()}>
        {submitting ? 'Submitting...' : 'Submit query →'}
      </button>

      {#if error}
        <div class="alert error">{error}</div>
      {/if}

      {#if result}
        <div class={`alert ${result.correct ? 'success' : 'error'}`}>
          {result.feedback} · Score {result.score}
        </div>
      {/if}
    </section>
  {/if}

  <section class="panel">
    <h2>Attempt feed</h2>
    {#each data.attempts as attempt}
      <div class="list-row">
        <div>
          <b>{attempt.username}</b>
          <small>{attempt.execution_time_ms}ms · efficiency {attempt.efficiency_score}</small>
        </div>
        <div>
          <VerdictBadge verdict={attempt.status} />
          <strong>{attempt.score}</strong>
        </div>
      </div>
    {/each}
  </section>
{/if}
