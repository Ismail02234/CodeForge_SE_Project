<script lang="ts">
  import { page } from '$app/stores';
  import { onMount } from 'svelte';
  import Loading from '$lib/components/Loading.svelte';
  import VerdictBadge from '$lib/components/VerdictBadge.svelte';
  import { api } from '$lib/api';

  type Battle = {
    id: string;
    challenge_id: string;
    player1_id: string;
    player2_id: string;
    player1_name: string;
    player2_name: string;
    player1_rating: number;
    player2_rating: number;
    winner_id: string | null;
    winner_name: string | null;
    status: 'active' | 'completed' | 'cancelled';
    title: string;
    description: string;
    difficulty: string;
    max_score: number;
    order_sensitive: number | boolean;
    created_at: string;
    completed_at: string | null;
  };

  type Attempt = {
    id: string;
    user_id: string;
    username: string;
    status: string;
    score: number;
    execution_time_ms: number | null;
    efficiency_score: number;
    feedback: string;
    submitted_at: string;
  };

  type BattleData = {
    current_user_id: string;
    battle: Battle;
    attempts: Attempt[];
  };

  type SubmitResult = {
    status: string;
    correct: boolean;
    score: number;
    execution_time_ms: number | null;
    efficiency_score: number;
    feedback: string;
    rows: Record<string, unknown>[];
  };

  let data: BattleData | null = null;
  let query = 'SELECT ';
  let result: SubmitResult | null = null;
  let error = '';
  let submitting = false;
  let pollTimer: ReturnType<typeof setInterval> | null = null;

  $: player1Attempts =
    data?.attempts.filter((item) => item.user_id === data?.battle.player1_id) ?? [];
  $: player2Attempts =
    data?.attempts.filter((item) => item.user_id === data?.battle.player2_id) ?? [];
  $: player1Score = bestScore(player1Attempts);
  $: player2Score = bestScore(player2Attempts);
  $: myId = data?.current_user_id ?? '';
  $: myAccepted =
    data?.attempts.some((item) => item.user_id === myId && item.status === 'accepted') ?? false;
  $: opponentAccepted =
    data?.attempts.some((item) => item.user_id !== myId && item.status === 'accepted') ?? false;

  function stopPolling() {
    if (pollTimer) {
      clearInterval(pollTimer);
      pollTimer = null;
    }
  }

  async function load() {
    try {
      data = await api.get<BattleData>(`/api/sql/battles/${$page.params.id}`);
      error = '';

      if (data.battle.status !== 'active') {
        stopPolling();
      }
    } catch (e: any) {
      error = e.message;
    }
  }

  onMount(() => {
    void load();

    pollTimer = setInterval(() => {
      void load();
    }, 2500);

    return stopPolling;
  });

  async function submit() {
    if (!data || submitting || !query.trim()) return;

    submitting = true;
    error = '';
    result = null;

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
      error = e.message;
    } finally {
      submitting = false;
    }
  }

  function bestScore(attempts: Attempt[]) {
    return attempts.reduce((best, item) => Math.max(best, Number(item.score || 0)), 0);
  }

  function playerLabel(id: string, username: string) {
    return id === myId ? `${username} · YOU` : username;
  }

  function battleHeadline() {
    if (!data) return '';

    if (data.battle.status === 'active') {
      if (myAccepted && !opponentAccepted) {
        return 'Your result is accepted — waiting for your opponent';
      }

      if (!myAccepted && opponentAccepted) {
        return 'Your opponent has accepted — your turn to answer';
      }

      if (myAccepted && opponentAccepted) {
        return 'Finalizing battle result...';
      }

      return 'Battle is live';
    }

    if (data.battle.winner_id === null) {
      return 'Battle finished in a draw';
    }

    if (data.battle.winner_id === myId) {
      return 'You won the SQL Battle';
    }

    return `${data.battle.winner_name} won the SQL Battle`;
  }

  function battleMessage() {
    if (!data) return '';

    if (data.battle.status === 'active') {
      return 'Both players need at least one accepted query. Your best accepted score is used when the battle completes.';
    }

    if (data.battle.winner_id === null) {
      return `Both players finished with ${player1Score} points.`;
    }

    return `Final score: ${data.battle.player1_name} ${player1Score} · ${data.battle.player2_name} ${player2Score}.`;
  }

  function statusTone() {
    if (!data) return 'active';
    if (data.battle.status === 'active') return 'active';
    if (data.battle.winner_id === myId) return 'success';
    if (data.battle.winner_id === null) return 'info';
    return 'danger';
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
      <span class="eyebrow">
        SQL BATTLE · {data.battle.difficulty} · {data.battle.status.toUpperCase()}
      </span>
      <h1>{data.battle.title}</h1>
      <p>{data.battle.description}</p>
    </div>

    <a class="btn ghost" href="/sql-battle">← SQL Arena</a>
  </div>

  {#if error}
    <div class="alert error">{error}</div>
  {/if}

  <section class={`sql-battle-status ${statusTone()}`}>
    <div class="sql-battle-status-icon">
      {#if data.battle.status === 'active'}
        ◉
      {:else if data.battle.winner_id === myId}
        ✓
      {:else if data.battle.winner_id === null}
        =
      {:else}
        ×
      {/if}
    </div>

    <div>
      <span class="eyebrow">
        {data.battle.status === 'active' ? 'LIVE MATCH' : 'FINAL RESULT'}
      </span>
      <h2>{battleHeadline()}</h2>
      <p>{battleMessage()}</p>
    </div>

    <div class="sql-battle-max">
      <span>MAX SCORE</span>
      <strong>{data.battle.max_score}</strong>
    </div>
  </section>

  <section class="sql-versus-board">
    <div
      class="sql-player-score"
      class:me={data.battle.player1_id === myId}
      class:leader={player1Score > player2Score}
    >
      <span>{playerLabel(data.battle.player1_id, data.battle.player1_name)}</span>
      <strong>{player1Score}</strong>
      <small>
        {data.battle.player1_rating} rating · {player1Attempts.length} attempt{player1Attempts.length ===
        1
          ? ''
          : 's'}
      </small>

      {#if player1Attempts.some((item) => item.status === 'accepted')}
        <b class="sql-accepted-marker">✓ ACCEPTED</b>
      {:else}
        <b class="sql-pending-marker">WAITING FOR AC</b>
      {/if}
    </div>

    <div class="sql-versus-mark">
      <span>VS</span>
      <small>best accepted score wins</small>
    </div>

    <div
      class="sql-player-score"
      class:me={data.battle.player2_id === myId}
      class:leader={player2Score > player1Score}
    >
      <span>{playerLabel(data.battle.player2_id, data.battle.player2_name)}</span>
      <strong>{player2Score}</strong>
      <small>
        {data.battle.player2_rating} rating · {player2Attempts.length} attempt{player2Attempts.length ===
        1
          ? ''
          : 's'}
      </small>

      {#if player2Attempts.some((item) => item.status === 'accepted')}
        <b class="sql-accepted-marker">✓ ACCEPTED</b>
      {:else}
        <b class="sql-pending-marker">WAITING FOR AC</b>
      {/if}
    </div>
  </section>

  {#if data.battle.status === 'active'}
    <section class="panel editor-panel sql-battle-workspace">
      <div class="panel-head">
        <div>
          <span class="eyebrow">YOUR QUERY</span>
          <h2>Compete on the selected challenge</h2>
        </div>

        <span class="sql-order-rule">
          {data.battle.order_sensitive ? 'ORDER MATTERS' : 'ORDER FLEXIBLE'}
        </span>
      </div>

      <div class="sql-sandbox-note">
        <b>Read-only battle sandbox</b>
        <span>
          Use SELECT or WITH against arena_users, arena_universities, arena_problems and
          arena_submissions.
        </span>
        <small>
          Both players may submit multiple times while the battle is active. The best accepted score
          is kept.
        </small>
      </div>

      <textarea
        class="code-editor sql sql-editor-large"
        bind:value={query}
        spellcheck="false"
        aria-label="SQL Battle query"></textarea>

      <div class="sql-editor-actions">
        <div>
          {#if myAccepted}
            <b>Your query has been accepted.</b>
            <span>
              You may submit again to try for a higher score while waiting for the opponent.
            </span>
          {:else}
            <b>You still need an accepted result.</b>
            <span>Correctness comes first; speed and efficiency determine the score.</span>
          {/if}
        </div>

        <button class="btn primary" disabled={submitting || !query.trim()} on:click={submit}>
          {submitting ? 'JUDGING...' : 'Submit battle query →'}
        </button>
      </div>

      {#if result}
        <section class={`sql-result-card ${result.correct ? 'success' : 'danger'}`}>
          <div class="sql-result-main">
            <VerdictBadge verdict={result.status} />
            <div>
              <span class="eyebrow">LATEST ATTEMPT</span>
              <h3>{result.correct ? 'Accepted' : 'Not accepted'}</h3>
              <p>{result.feedback}</p>
            </div>
          </div>

          <div class="sql-result-metrics">
            <div>
              <span>SCORE</span>
              <strong>{result.score}</strong>
            </div>
            <div>
              <span>EXECUTION</span>
              <strong>
                {result.execution_time_ms === null ? '—' : `${result.execution_time_ms} ms`}
              </strong>
            </div>
            <div>
              <span>EFFICIENCY</span>
              <strong>{result.efficiency_score}</strong>
            </div>
          </div>
        </section>

        {#if result.rows?.length}
          <div class="table-wrap compact sql-result-table">
            <table>
              <thead>
                <tr>
                  {#each Object.keys(result.rows[0]) as key}
                    <th>{key}</th>
                  {/each}
                </tr>
              </thead>
              <tbody>
                {#each result.rows as row}
                  <tr>
                    {#each Object.values(row) as value}
                      <td>{value === null ? 'NULL' : String(value)}</td>
                    {/each}
                  </tr>
                {/each}
              </tbody>
            </table>
          </div>
        {/if}
      {/if}
    </section>
  {:else}
    <section class="panel sql-complete-actions">
      <div>
        <span class="eyebrow">BATTLE COMPLETE</span>
        <h2>{battleHeadline()}</h2>
        <p>{battleMessage()}</p>
      </div>

      <div class="page-actions">
        <a class="btn primary" href="/sql-battle">Start another battle →</a>
        <a class="btn ghost" href="/sql-lab">Open SQL Lab</a>
      </div>
    </section>
  {/if}

  <section class="panel sql-attempt-feed">
    <div class="panel-head">
      <div>
        <span class="eyebrow">LIVE ATTEMPT FEED</span>
        <h2>Battle activity</h2>
      </div>
      <span>{data.attempts.length} attempts</span>
    </div>

    {#if data.attempts.length === 0}
      <div class="sql-opponent-empty">
        No attempts yet. Submit the first query to put a score on the board.
      </div>
    {:else}
      <div class="sql-attempt-list">
        {#each [...data.attempts].reverse() as attempt, index (attempt.id)}
          <div
            class="sql-attempt-row"
            class:mine={attempt.user_id === myId}
            class:latest={index === 0}
          >
            <div class="sql-attempt-user">
              <span>{attempt.user_id === myId ? 'YOU' : 'OPPONENT'}</span>
              <b>{attempt.username}</b>
              <small>{attempt.submitted_at}</small>
            </div>

            <VerdictBadge verdict={attempt.status} />

            <div class="sql-attempt-metric">
              <span>SCORE</span>
              <strong>{attempt.score}</strong>
            </div>

            <div class="sql-attempt-metric">
              <span>TIME</span>
              <strong>
                {attempt.execution_time_ms === null ? '—' : `${attempt.execution_time_ms} ms`}
              </strong>
            </div>

            <div class="sql-attempt-metric">
              <span>EFF.</span>
              <strong>{attempt.efficiency_score}</strong>
            </div>

            {#if index === 0}
              <span class="latest-marker">LATEST</span>
            {/if}
          </div>
        {/each}
      </div>
    {/if}
  </section>
{/if}
