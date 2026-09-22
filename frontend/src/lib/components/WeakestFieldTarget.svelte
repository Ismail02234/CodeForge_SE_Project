<script lang="ts">
  import { onMount } from 'svelte';
  import { page } from '$app/stores';
  import { request } from '$lib/api';

  type AnyRecord = Record<string, any>;

  let recommendation: AnyRecord | null = null;
  let weakestField = '';
  let targetActive = false;
  let loading = false;
  let error = '';
  let targetedProblems: AnyRecord[] = [];

  function normalizeText(value: unknown): string {
    return String(value ?? '')
      .trim()
      .toLowerCase()
      .replace(/[_-]+/g, ' ');
  }

  function getWeakestField(payload: AnyRecord | null): string {
    if (!payload) return '';

    const candidates = [
      payload.weakest_field,
      payload.weakest_topic,
      payload.weak_topic,
      payload.topic,
      payload?.weakest?.topic,
      payload?.weakness?.topic,
      payload?.weakness?.field,
      payload?.data?.weakest_field,
      payload?.data?.weakest_topic,
      payload?.data?.topic,
    ];

    return String(candidates.find((value) => String(value ?? '').trim()) ?? '').trim();
  }

  function extractProblems(payload: any): AnyRecord[] {
    if (Array.isArray(payload)) return payload;
    if (Array.isArray(payload?.data)) return payload.data;
    if (Array.isArray(payload?.problems)) return payload.problems;
    if (Array.isArray(payload?.items)) return payload.items;
    if (Array.isArray(payload?.data?.data)) return payload.data.data;
    if (Array.isArray(payload?.data?.problems)) return payload.data.problems;
    return [];
  }

  function extractRecommendations(payload: AnyRecord | null): AnyRecord[] {
    if (!payload) return [];

    const candidates = [
      payload.recommendations,
      payload.problems,
      payload?.data?.recommendations,
      payload?.data?.problems,
    ];

    const list = candidates.find((value) => Array.isArray(value));
    return Array.isArray(list) ? list : [];
  }

  function topicValues(problem: AnyRecord): string[] {
    const values: unknown[] = [
      problem.topic,
      problem.category,
      problem.tag,
      problem.problem_type,
      problem.field,
    ];

    if (Array.isArray(problem.tags)) {
      for (const tag of problem.tags) {
        if (typeof tag === 'string') {
          values.push(tag);
        } else if (tag && typeof tag === 'object') {
          values.push(tag.name, tag.tag, tag.title);
        }
      }
    }

    return values.map((value) => normalizeText(value)).filter(Boolean);
  }

  function matchesWeakestField(problem: AnyRecord, field: string): boolean {
    const needle = normalizeText(field);
    if (!needle) return false;

    return topicValues(problem).some(
      (value) => value === needle || value.includes(needle) || needle.includes(value)
    );
  }

  function problemId(problem: AnyRecord): string | number | null {
    return problem.id ?? problem.problem_id ?? null;
  }

  function problemTitle(problem: AnyRecord): string {
    return String(problem.title ?? problem.name ?? `Problem ${problemId(problem) ?? ''}`).trim();
  }

  function problemDifficulty(problem: AnyRecord): string {
    return String(problem.difficulty ?? problem.level ?? problem.rating ?? '').trim();
  }

  async function preloadWeakness() {
    try {
      recommendation = await request<AnyRecord>('/api/recommendations/problems');
      weakestField = getWeakestField(recommendation);
    } catch {
      // The button can retry when the user clicks it.
    }
  }

  async function activateTarget() {
    if (targetActive) {
      targetActive = false;
      error = '';
      return;
    }

    loading = true;
    error = '';

    try {
      recommendation ??= await request<AnyRecord>('/api/recommendations/problems');
      weakestField = getWeakestField(recommendation);

      if (!weakestField) {
        throw new Error('Your weakest field could not be identified yet.');
      }

      const allProblemsResponse = await request<any>('/api/problems');
      const allProblems = extractProblems(allProblemsResponse);

      const matches = allProblems.filter((problem) => matchesWeakestField(problem, weakestField));
      const recommended = extractRecommendations(recommendation);

      targetedProblems = matches.length > 0 ? matches : recommended;
      targetActive = true;

      if (targetedProblems.length === 0) {
        error = `No unsolved ${weakestField} problems are available right now.`;
      }
    } catch (e: any) {
      error = e?.message || 'Could not target your weakest field.';
      targetActive = true;
    } finally {
      loading = false;
    }
  }

  onMount(async () => {
    await preloadWeakness();

    if ($page.url.searchParams.get('ai_target') === 'weakest') {
      await activateTarget();
    }
  });
</script>

<section class="target-wrap" aria-label="Weakest field target">
  <div class="target-bar">
    <div class="target-copy">
      <span class="target-kicker">FOCUS MODE</span>
      <strong
        >{weakestField ? `Weakest field: ${weakestField}` : 'Target your weakest field'}</strong
      >
      <small>One click shows problems from the area where you need the most practice.</small>
    </div>

    <button
      class:active={targetActive}
      class="target-button"
      type="button"
      on:click={activateTarget}
      disabled={loading}
    >
      <span class="target-icon" aria-hidden="true">
        <i></i>
      </span>
      {loading ? 'TARGETING…' : targetActive ? 'CLEAR TARGET' : 'TARGET'}
    </button>
  </div>

  {#if targetActive}
    <div class="target-panel">
      <div class="target-panel-head">
        <div>
          <span class="eyebrow">TARGET LOCKED</span>
          <h3>{weakestField || 'Weakest field'}</h3>
        </div>
        <span class="count"
          >{targetedProblems.length} problem{targetedProblems.length === 1 ? '' : 's'}</span
        >
      </div>

      {#if error}
        <div class="target-message">{error}</div>
      {/if}

      {#if targetedProblems.length > 0}
        <div class="target-list">
          {#each targetedProblems as problem}
            {@const id = problemId(problem)}
            {#if id !== null}
              <a class="target-problem" href={`/problems/${id}`}>
                <div>
                  <strong>{problemTitle(problem)}</strong>
                  <small>
                    {weakestField}
                    {#if problemDifficulty(problem)}
                      · {problemDifficulty(problem)}
                    {/if}
                  </small>
                </div>
                <span aria-hidden="true">→</span>
              </a>
            {/if}
          {/each}
        </div>
      {/if}
    </div>
  {/if}
</section>

<style>
  .target-wrap {
    margin: 16px 0 20px;
  }

  .target-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 18px;
    padding: 16px 18px;
    border: 1px solid rgba(255, 255, 255, 0.09);
    border-radius: 13px;
    background:
      linear-gradient(110deg, rgba(255, 91, 59, 0.055), transparent 45%), rgba(255, 255, 255, 0.018);
  }

  .target-copy {
    min-width: 0;
  }

  .target-kicker {
    display: block;
    margin-bottom: 5px;
    color: #ff6b4a;
    font-family: 'JetBrains Mono', monospace;
    font-size: 9px;
    font-weight: 900;
    letter-spacing: 0.12em;
  }

  .target-copy strong {
    display: block;
    color: #f2f4f7;
    font-size: 14px;
  }

  .target-copy small {
    display: block;
    margin-top: 4px;
    color: var(--muted, #8b939e);
    line-height: 1.45;
  }

  .target-button {
    display: inline-flex;
    min-width: 126px;
    min-height: 44px;
    align-items: center;
    justify-content: center;
    gap: 9px;
    padding: 0 15px;
    border: 1px solid rgba(255, 91, 59, 0.6);
    border-radius: 9px;
    background: rgba(255, 91, 59, 0.08);
    color: #ff7658;
    font-family: 'JetBrains Mono', monospace;
    font-size: 10px;
    font-weight: 900;
    letter-spacing: 0.08em;
    cursor: pointer;
    transition:
      transform 160ms ease,
      background 160ms ease,
      border-color 160ms ease;
  }

  .target-button:hover:not(:disabled) {
    transform: translateY(-1px);
    border-color: rgba(255, 91, 59, 0.95);
    background: rgba(255, 91, 59, 0.14);
  }

  .target-button.active {
    border-color: rgba(34, 197, 94, 0.62);
    background: rgba(34, 197, 94, 0.08);
    color: #5fdb87;
  }

  .target-button:disabled {
    cursor: wait;
    opacity: 0.65;
  }

  .target-icon {
    position: relative;
    width: 18px;
    height: 18px;
    border: 2px solid currentColor;
    border-radius: 50%;
  }

  .target-icon::before,
  .target-icon::after {
    position: absolute;
    content: '';
    background: currentColor;
  }

  .target-icon::before {
    top: 6px;
    left: -4px;
    width: 22px;
    height: 2px;
  }

  .target-icon::after {
    top: -4px;
    left: 6px;
    width: 2px;
    height: 22px;
  }

  .target-icon i {
    position: absolute;
    top: 5px;
    left: 5px;
    width: 4px;
    height: 4px;
    border-radius: 50%;
    background: currentColor;
  }

  .target-panel {
    margin-top: 10px;
    padding: 17px;
    border: 1px solid rgba(255, 91, 59, 0.18);
    border-radius: 13px;
    background: rgba(5, 7, 10, 0.6);
  }

  .target-panel-head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 14px;
    margin-bottom: 13px;
  }

  .target-panel-head h3 {
    margin: 4px 0 0;
    font-size: 18px;
  }

  .count {
    padding: 5px 8px;
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 999px;
    color: var(--muted, #8b939e);
    font-family: 'JetBrains Mono', monospace;
    font-size: 9px;
  }

  .target-list {
    display: grid;
    gap: 8px;
  }

  .target-problem {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    padding: 12px 13px;
    border: 1px solid rgba(255, 255, 255, 0.07);
    border-radius: 9px;
    color: inherit;
    text-decoration: none;
    background: rgba(255, 255, 255, 0.012);
    transition:
      border-color 150ms ease,
      background 150ms ease,
      transform 150ms ease;
  }

  .target-problem:hover {
    transform: translateX(2px);
    border-color: rgba(255, 91, 59, 0.3);
    background: rgba(255, 91, 59, 0.045);
  }

  .target-problem strong,
  .target-problem small {
    display: block;
  }

  .target-problem strong {
    color: #e9edf2;
    font-size: 12px;
  }

  .target-problem small {
    margin-top: 3px;
    color: var(--muted, #8b939e);
    font-size: 9px;
  }

  .target-problem > span {
    color: #ff6b4a;
    font-family: 'JetBrains Mono', monospace;
  }

  .target-message {
    padding: 12px;
    border: 1px dashed rgba(255, 255, 255, 0.11);
    border-radius: 9px;
    color: var(--muted, #8b939e);
    font-size: 11px;
  }

  @media (max-width: 680px) {
    .target-bar {
      align-items: stretch;
      flex-direction: column;
    }

    .target-button {
      width: 100%;
    }
  }
</style>
