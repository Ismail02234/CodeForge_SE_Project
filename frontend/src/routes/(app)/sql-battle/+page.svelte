<script lang="ts">
  import { goto } from '$app/navigation';
  import { onMount } from 'svelte';
  import Loading from '$lib/components/Loading.svelte';
  import VerdictBadge from '$lib/components/VerdictBadge.svelte';
  import { api } from '$lib/api';

  type Challenge = {
    id: string;
    title: string;
    description: string;
    difficulty: 'Easy' | 'Medium' | 'Hard';
    max_score: number;
    accepted_attempts: number;
    total_attempts: number;
    order_sensitive: number | boolean;
  };

  type Opponent = {
    id: string;
    username: string;
    rating: number;
    rank: string;
    university: string;
    accepted_runs: number;
    best_score: number;
    active_battle_id: string | null;
  };

  type PracticeResult = {
    status: string;
    correct: boolean;
    score: number;
    execution_time_ms: number | null;
    efficiency_score: number;
    feedback: string;
    rows: Record<string, unknown>[];
  };

  type BattleSummary = {
    id: string;
    title: string;
    difficulty: string;
    status: string;
    player1_name: string;
    player2_name: string;
    player1_score: number;
    player2_score: number;
    player1_attempts: number;
    player2_attempts: number;
    winner_name: string | null;
  };

  type LeaderboardRow = {
    id: string;
    username: string;
    rating: number;
    rank: string;
    best_score: number;
    accepted_runs: number;
    total_runs: number;
  };

  let challenges: Challenge[] = [];
  let opponents: Opponent[] = [];
  let leaderboard: LeaderboardRow[] = [];
  let battles: BattleSummary[] = [];

  let selectedChallengeId = '';
  let selectedOpponentId = '';
  let opponentSearch = '';
  let query = 'SELECT ';
  let result: PracticeResult | null = null;
  let error = '';
  let ready = false;
  let loadingOpponents = false;
  let runningPractice = false;
  let creatingBattle = false;

  $: challenge = challenges.find((item) => item.id === selectedChallengeId) ?? null;
  $: selectedOpponent = opponents.find((item) => item.id === selectedOpponentId) ?? null;
  $: filteredOpponents = opponents.filter((item) => {
    const search = opponentSearch.trim().toLowerCase();

    if (!search) return true;

    return [item.username, item.rank, item.university, String(item.rating)]
      .join(' ')
      .toLowerCase()
      .includes(search);
  });

  async function load() {
    error = '';

    try {
      [challenges, leaderboard, battles] = await Promise.all([
        api.get<Challenge[]>('/api/sql/challenges'),
        api.get<LeaderboardRow[]>('/api/sql/leaderboard'),
        api.get<BattleSummary[]>('/api/sql/battles'),
      ]);

      if (!selectedChallengeId && challenges.length > 0) {
        selectedChallengeId = challenges[0].id;
      }

      if (selectedChallengeId) {
        await loadOpponents();
      }
    } catch (e: any) {
      error = e.message;
    } finally {
      ready = true;
    }
  }

  async function loadOpponents() {
    if (!selectedChallengeId) {
      opponents = [];
      selectedOpponentId = '';
      return;
    }

    loadingOpponents = true;

    try {
      opponents = await api.get<Opponent[]>(
        `/api/sql/opponents?challenge_id=${encodeURIComponent(selectedChallengeId)}`
      );

      if (selectedOpponentId && !opponents.some((item) => item.id === selectedOpponentId)) {
        selectedOpponentId = '';
      }
    } catch (e: any) {
      error = e.message;
      opponents = [];
    } finally {
      loadingOpponents = false;
    }
  }

  onMount(() => {
    void load();
  });

  async function chooseChallenge(id: string) {
    if (selectedChallengeId === id) return;

    selectedChallengeId = id;
    selectedOpponentId = '';
    result = null;
    error = '';
    await loadOpponents();
  }

  function chooseOpponent(id: string) {
    selectedOpponentId = id;
    error = '';
  }

  async function practice() {
    if (!selectedChallengeId || !query.trim() || runningPractice) return;

    runningPractice = true;
    error = '';
    result = null;

    try {
      result = await api.post<PracticeResult>(`/api/sql/challenges/${selectedChallengeId}/submit`, {
        query,
      });
    } catch (e: any) {
      error = e.message;
    } finally {
      runningPractice = false;
    }
  }

  async function battle() {
    if (!selectedChallengeId || !selectedOpponent || creatingBattle) return;

    if (selectedOpponent.active_battle_id) {
      await goto(`/sql-battle/${selectedOpponent.active_battle_id}`);
      return;
    }

    creatingBattle = true;
    error = '';

    try {
      const created = await api.post<{ id: string; created: boolean; message: string }>(
        '/api/sql/battles',
        {
          challenge_id: selectedChallengeId,
          opponent_id: selectedOpponent.id,
        }
      );

      await goto(`/sql-battle/${created.id}`);
    } catch (e: any) {
      error = e.message;
    } finally {
      creatingBattle = false;
    }
  }

  function difficultyClass(value: string) {
    return value.toLowerCase();
  }

  function score(value: number | string | null | undefined) {
    return Number(value || 0);
  }
</script>

<svelte:head>
  <title>SQL Battle · CodeForge</title>
</svelte:head>

<div class="page-head">
  <div>
    <span class="eyebrow">READ-ONLY DBMS COMBAT</span>
    <h1>SQL Battle Arena</h1>
    <p>
      Solve safely inside the arena database, choose a real opponent and compete on correctness,
      speed and query efficiency.
    </p>
  </div>

  <div class="sql-arena-rules">
    <span>SELECT / WITH only</span>
    <span>Sandbox tables</span>
    <span>1000 row limit</span>
  </div>
</div>

{#if error}
  <div class="alert error">{error}</div>
{/if}

{#if !ready}
  <Loading />
{:else}
  <section class="sql-arena-grid">
    <aside class="panel sql-challenge-panel">
      <div class="panel-head">
        <div>
          <span class="eyebrow">STEP 1</span>
          <h2>Choose challenge</h2>
        </div>
        <span>{challenges.length} available</span>
      </div>

      <div class="sql-challenge-list">
        {#each challenges as item (item.id)}
          <button
            class="sql-challenge-card"
            class:selected={selectedChallengeId === item.id}
            on:click={() => void chooseChallenge(item.id)}
          >
            <div>
              <span class={`difficulty-dot ${difficultyClass(item.difficulty)}`}></span>
              <b>{item.title}</b>
            </div>

            <p>{item.description}</p>

            <div class="sql-card-meta">
              <span>{item.difficulty}</span>
              <span>{item.max_score} pts</span>
              <span>{item.accepted_attempts} AC</span>
            </div>
          </button>
        {/each}
      </div>
    </aside>

    <section class="panel editor-panel sql-practice-panel">
      {#if challenge}
        <div class="panel-head">
          <div>
            <span class="eyebrow">PRACTICE MODE · {challenge.difficulty}</span>
            <h2>{challenge.title}</h2>
          </div>
          <strong class="sql-max-score">{challenge.max_score} PTS</strong>
        </div>

        <p class="sql-description">{challenge.description}</p>

        <div class="sql-sandbox-note">
          <b>SQL Arena sandbox</b>
          <span>
            Allowed tables: arena_users, arena_universities, arena_problems, arena_submissions
          </span>
          <small>
            {challenge.order_sensitive
              ? 'Result row order matters for this challenge.'
              : 'Result row order does not matter for this challenge.'}
          </small>
        </div>

        <textarea
          class="code-editor sql sql-editor-large"
          bind:value={query}
          spellcheck="false"
          aria-label="SQL query"></textarea>

        <div class="sql-editor-actions">
          <span>
            Practice attempts do not affect a battle. Use them to understand the challenge.
          </span>

          <button
            class="btn primary"
            disabled={runningPractice || !query.trim()}
            on:click={practice}
          >
            {runningPractice ? 'RUNNING...' : 'Run practice →'}
          </button>
        </div>

        {#if result}
          <section class={`sql-result-card ${result.correct ? 'success' : 'danger'}`}>
            <div class="sql-result-main">
              <VerdictBadge verdict={result.status} />
              <div>
                <span class="eyebrow">PRACTICE RESULT</span>
                <h3>{result.correct ? 'Correct result' : 'Query needs work'}</h3>
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
      {/if}
    </section>
  </section>

  <section class="sql-matchmaking-grid">
    <div class="panel sql-opponent-panel">
      <div class="panel-head">
        <div>
          <span class="eyebrow">STEP 2</span>
          <h2>Choose your opponent</h2>
        </div>

        {#if selectedOpponent}
          <span class="live-text">● READY</span>
        {:else}
          <span>{opponents.length} coders</span>
        {/if}
      </div>

      <div class="sql-opponent-toolbar">
        <input
          bind:value={opponentSearch}
          placeholder="Search username, university, rank or rating..."
          aria-label="Search SQL Battle opponents"
        />

        <button class="btn ghost" disabled={loadingOpponents} on:click={() => void loadOpponents()}>
          {loadingOpponents ? 'LOADING...' : 'Refresh'}
        </button>
      </div>

      {#if loadingOpponents}
        <div class="sql-opponent-empty">Loading opponents...</div>
      {:else if opponents.length === 0}
        <div class="sql-opponent-empty">
          <b>No opponents available</b>
          <span>
            The opponent list is loaded from registered CodeForge users other than your current
            account.
          </span>
        </div>
      {:else if filteredOpponents.length === 0}
        <div class="sql-opponent-empty">
          No coder matches “{opponentSearch}”.
        </div>
      {:else}
        <div class="sql-opponent-list">
          {#each filteredOpponents as user (user.id)}
            <button
              class="sql-opponent-card"
              class:selected={selectedOpponentId === user.id}
              on:click={() => chooseOpponent(user.id)}
            >
              <div class="sql-opponent-avatar">
                {user.username.slice(0, 2).toUpperCase()}
              </div>

              <div class="sql-opponent-info">
                <div>
                  <b>{user.username}</b>
                  {#if user.active_battle_id}
                    <span class="sql-active-battle">ACTIVE BATTLE</span>
                  {/if}
                </div>

                <small>{user.rank} · {user.university || 'No university'}</small>

                <div class="sql-opponent-stats">
                  <span><b>{user.rating}</b> rating</span>
                  <span><b>{score(user.best_score)}</b> SQL best</span>
                  <span><b>{score(user.accepted_runs)}</b> accepted</span>
                </div>
              </div>

              <span class="sql-select-mark">
                {selectedOpponentId === user.id ? '✓' : '→'}
              </span>
            </button>
          {/each}
        </div>
      {/if}

      <div class="sql-battle-launch">
        {#if selectedOpponent && challenge}
          <div class="sql-match-summary">
            <span class="eyebrow">MATCH READY</span>
            <b>{selectedOpponent.username}</b>
            <small>
              {challenge.title} · {challenge.difficulty} · {challenge.max_score} pts
            </small>
          </div>
        {:else}
          <div class="sql-match-summary">
            <span class="eyebrow">MATCH SETUP</span>
            <b>Select an opponent</b>
            <small>Choose one coder above to enable the battle button.</small>
          </div>
        {/if}

        <button
          class="btn primary sql-launch-button"
          disabled={!challenge || !selectedOpponent || creatingBattle}
          on:click={battle}
        >
          {#if creatingBattle}
            CREATING...
          {:else if selectedOpponent?.active_battle_id}
            Resume active battle →
          {:else}
            Start SQL Battle →
          {/if}
        </button>
      </div>
    </div>

    <div class="panel sql-leaderboard-panel">
      <div class="panel-head">
        <div>
          <span class="eyebrow">ARENA RANKING</span>
          <h2>SQL leaderboard</h2>
        </div>
        <span>Best scores</span>
      </div>

      {#if leaderboard.length === 0}
        <div class="sql-opponent-empty">No ranked SQL attempts yet.</div>
      {:else}
        <div class="sql-leaderboard-list">
          {#each leaderboard as row, index (row.id)}
            <a class="sql-leaderboard-row" href={`/profile/${row.id}`}>
              <strong>#{index + 1}</strong>

              <div>
                <b>{row.username}</b>
                <small>{row.rank} · {row.rating} rating</small>
              </div>

              <div class="sql-leaderboard-score">
                <b>{score(row.best_score)}</b>
                <small>{score(row.accepted_runs)} AC</small>
              </div>
            </a>
          {/each}
        </div>
      {/if}
    </div>
  </section>

  <section class="panel sql-recent-panel">
    <div class="panel-head">
      <div>
        <span class="eyebrow">YOUR ARENA HISTORY</span>
        <h2>Recent battles</h2>
      </div>
      <span>{battles.length} battles</span>
    </div>

    {#if battles.length === 0}
      <div class="sql-opponent-empty">
        <b>No SQL Battles yet</b>
        <span>Choose a challenge and opponent above to create your first battle.</span>
      </div>
    {:else}
      <div class="sql-battle-history">
        {#each battles as item (item.id)}
          <a class="sql-history-card" href={`/sql-battle/${item.id}`}>
            <div>
              <span class={`difficulty-dot ${difficultyClass(item.difficulty)}`}></span>
              <div>
                <b>{item.title}</b>
                <small>{item.player1_name} vs {item.player2_name}</small>
              </div>
            </div>

            <div class="sql-history-score">
              <strong>{score(item.player1_score)} : {score(item.player2_score)}</strong>
              <small>
                {score(item.player1_attempts) + score(item.player2_attempts)} attempts
              </small>
            </div>

            <span class={`pill ${item.status}`}>{item.status}</span>
          </a>
        {/each}
      </div>
    {/if}
  </section>
{/if}
