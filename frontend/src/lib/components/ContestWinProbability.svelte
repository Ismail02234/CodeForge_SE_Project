<script lang="ts">
  import { onMount } from 'svelte';
  import { api } from '$lib/api';

  type Contest = {
    id: string;
    name: string;
    type: string;
    status: string;
    starts_at: string;
    participant_count: number;
  };

  type Participant = {
    id: string;
    username: string;
    rating: number;
    rank: string;
    university?: string | null;
    contest_score: number;
  };

  type Prediction = {
    left: {
      user: Participant;
      contest_score: number;
      probability: number;
      strength: {
        rating_component: number;
        profile_component: number;
        consistency_component: number;
        total: number;
      };
    };
    right: {
      user: Participant;
      contest_score: number;
      probability: number;
      strength: {
        rating_component: number;
        profile_component: number;
        consistency_component: number;
        total: number;
      };
    };
    edge: 'left' | 'right' | 'even';
    basis: Record<string, string>;
    note: string;
  };

  type ContestPredictionData = {
    contests: Contest[];
    contest: Contest | null;
    participants: Participant[];
    prediction: Prediction | null;
  };

  let data: ContestPredictionData | null = null;
  let contestId = '';
  let a = '';
  let b = '';
  let loading = false;
  let error = '';

  function query(extra: Record<string, string> = {}) {
    const params = new URLSearchParams();

    if (contestId) {
      params.set('contest_id', contestId);
    }

    for (const [key, value] of Object.entries(extra)) {
      if (value) {
        params.set(key, value);
      }
    }

    const suffix = params.toString();

    return `/api/contest-prediction${suffix ? `?${suffix}` : ''}`;
  }

  async function loadBase() {
    loading = true;
    error = '';

    try {
      data = await api.get<ContestPredictionData>('/api/contest-prediction');
    } catch (e: any) {
      error = e.message;
    } finally {
      loading = false;
    }
  }

  async function loadContest() {
    a = '';
    b = '';
    error = '';

    if (!contestId) {
      await loadBase();
      return;
    }

    loading = true;

    try {
      data = await api.get<ContestPredictionData>(query());

      if ((data?.participants?.length || 0) >= 2) {
        a = data!.participants[0].id;
        b = data!.participants[1].id;
        await predict();
      }
    } catch (e: any) {
      error = e.message;
    } finally {
      loading = false;
    }
  }

  async function predict() {
    if (!contestId || !a || !b) {
      return;
    }

    if (a === b) {
      error = 'Choose two different contest participants.';
      return;
    }

    loading = true;
    error = '';

    try {
      data = await api.get<ContestPredictionData>(query({ a, b }));
    } catch (e: any) {
      error = e.message;
    } finally {
      loading = false;
    }
  }

  onMount(loadBase);
</script>

<section class="panel contest-prediction-panel">
  <div class="panel-head">
    <div>
      <span class="eyebrow">CONTEST RIVALRY</span>
      <h2>Participant win probability</h2>
    </div>

    <span class="module-label">PREDICTION MODULE</span>
  </div>

  <p class="prediction-intro">
    Choose a contest and two registered participants. The calculation keeps CodeForge's existing
    rivalry strength logic and applies it to that contest matchup.
  </p>

  {#if error}
    <div class="alert error">{error}</div>
  {/if}

  <div class="prediction-controls">
    <label>
      <span>Contest</span>
      <select bind:value={contestId} on:change={loadContest}>
        <option value="">Choose contest</option>
        {#each data?.contests || [] as contest}
          <option value={contest.id}>
            {contest.name} · {contest.status} · {contest.participant_count}
            participants
          </option>
        {/each}
      </select>
    </label>

    <label>
      <span>Participant A</span>
      <select bind:value={a} disabled={!data?.participants?.length}>
        <option value="">Choose participant</option>
        {#each data?.participants || [] as participant}
          <option value={participant.id}>
            {participant.username} · {participant.rating}
          </option>
        {/each}
      </select>
    </label>

    <label>
      <span>Participant B</span>
      <select bind:value={b} disabled={!data?.participants?.length}>
        <option value="">Choose participant</option>
        {#each data?.participants || [] as participant}
          <option value={participant.id}>
            {participant.username} · {participant.rating}
          </option>
        {/each}
      </select>
    </label>

    <button
      class="btn primary"
      type="button"
      on:click={predict}
      disabled={loading || !contestId || !a || !b}
    >
      {loading ? 'Calculating...' : 'Calculate win %'}
    </button>
  </div>

  {#if data?.contest}
    <div class="contest-context">
      <b>{data.contest.name}</b>
      <span>{data.contest.type}</span>
      <span>{data.contest.status}</span>
    </div>
  {/if}

  {#if data?.prediction}
    <div class="prediction-matchup">
      <article>
        <small>PARTICIPANT A</small>
        <h3>{data.prediction.left.user.username}</h3>
        <strong>{data.prediction.left.probability}%</strong>
        <span>rating {data.prediction.left.user.rating}</span>
        <span>contest score {data.prediction.left.contest_score}</span>
      </article>

      <div class="prediction-vs">
        <b>VS</b>
        <small>
          edge:
          {data.prediction.edge === 'even'
            ? 'even'
            : data.prediction.edge === 'left'
              ? data.prediction.left.user.username
              : data.prediction.right.user.username}
        </small>
      </div>

      <article>
        <small>PARTICIPANT B</small>
        <h3>{data.prediction.right.user.username}</h3>
        <strong>{data.prediction.right.probability}%</strong>
        <span>rating {data.prediction.right.user.rating}</span>
        <span>contest score {data.prediction.right.contest_score}</span>
      </article>
    </div>

    <div class="probability-track" aria-label="Predicted win probability">
      <span class="left-share" style={`width:${data.prediction.left.probability}%`}></span>
      <span class="right-share" style={`width:${data.prediction.right.probability}%`}></span>
    </div>

    <div class="prediction-breakdown">
      <div>
        <span>Rating component</span>
        <b>
          {data.prediction.left.strength.rating_component}
          :
          {data.prediction.right.strength.rating_component}
        </b>
      </div>

      <div>
        <span>Performance Profile component</span>
        <b>
          {data.prediction.left.strength.profile_component}
          :
          {data.prediction.right.strength.profile_component}
        </b>
      </div>

      <div>
        <span>Consistency component</span>
        <b>
          {data.prediction.left.strength.consistency_component}
          :
          {data.prediction.right.strength.consistency_component}
        </b>
      </div>
    </div>

    <p class="prediction-note">{data.prediction.note}</p>
  {/if}
</section>

<style>
  .contest-prediction-panel {
    margin-top: 18px;
  }

  .module-label {
    color: #ff7659;
    font:
      600 10px 'JetBrains Mono',
      monospace;
    letter-spacing: 0.1em;
  }

  .prediction-intro,
  .prediction-note {
    max-width: 860px;
    color: #9299a3;
    line-height: 1.6;
  }

  .prediction-controls {
    display: grid;
    grid-template-columns:
      minmax(180px, 1.2fr)
      minmax(150px, 1fr)
      minmax(150px, 1fr)
      auto;
    gap: 10px;
    align-items: end;
    margin: 16px 0;
  }

  .prediction-controls label {
    display: grid;
    gap: 6px;
  }

  .prediction-controls label > span {
    color: #737a84;
    font:
      600 10px 'JetBrains Mono',
      monospace;
    letter-spacing: 0.08em;
  }

  .prediction-controls select {
    min-height: 40px;
    padding: 0 10px;
    border: 1px solid rgba(255, 255, 255, 0.09);
    background: #101216;
    color: #e8e9eb;
  }

  .contest-context {
    display: flex;
    flex-wrap: wrap;
    gap: 9px;
    align-items: center;
    margin: 14px 0;
    color: #808791;
  }

  .contest-context b {
    color: #e8e9eb;
  }

  .contest-context span {
    padding: 4px 7px;
    border: 1px solid rgba(255, 255, 255, 0.07);
    font-size: 11px;
  }

  .prediction-matchup {
    display: grid;
    grid-template-columns: 1fr auto 1fr;
    gap: 18px;
    align-items: center;
    margin-top: 20px;
  }

  .prediction-matchup article {
    display: grid;
    gap: 5px;
    padding: 16px;
    border: 1px solid rgba(255, 255, 255, 0.075);
    background: rgba(255, 255, 255, 0.016);
  }

  .prediction-matchup article:last-child {
    text-align: right;
  }

  .prediction-matchup small {
    color: #737a84;
    font:
      600 10px 'JetBrains Mono',
      monospace;
    letter-spacing: 0.08em;
  }

  .prediction-matchup h3 {
    margin: 0;
  }

  .prediction-matchup strong {
    color: #ff6546;
    font:
      700 32px 'JetBrains Mono',
      monospace;
  }

  .prediction-matchup article:last-child strong {
    color: #43c8ff;
  }

  .prediction-matchup article > span {
    color: #8c939d;
    font-size: 12px;
  }

  .prediction-vs {
    display: grid;
    gap: 4px;
    min-width: 80px;
    text-align: center;
  }

  .prediction-vs b {
    font:
      800 18px 'JetBrains Mono',
      monospace;
  }

  .prediction-vs small {
    color: #777e88;
  }

  .probability-track {
    display: flex;
    height: 7px;
    margin: 14px 0 18px;
    overflow: hidden;
    background: rgba(255, 255, 255, 0.05);
  }

  .left-share {
    background: #ff6546;
  }

  .right-share {
    background: #43c8ff;
  }

  .prediction-breakdown {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 8px;
  }

  .prediction-breakdown > div {
    display: grid;
    gap: 5px;
    padding: 11px;
    border: 1px solid rgba(255, 255, 255, 0.06);
  }

  .prediction-breakdown span {
    color: #757c86;
    font-size: 11px;
  }

  .prediction-breakdown b {
    font:
      600 13px 'JetBrains Mono',
      monospace;
  }

  @media (max-width: 980px) {
    .prediction-controls {
      grid-template-columns: 1fr 1fr;
    }
  }

  @media (max-width: 700px) {
    .prediction-controls,
    .prediction-matchup,
    .prediction-breakdown {
      grid-template-columns: 1fr;
    }

    .prediction-matchup article:last-child {
      text-align: left;
    }
  }
</style>
