<script lang="ts">
  import { api } from '$lib/api';
  import { auth } from '$lib/stores/auth';

  type SqlLabResult = {
    rows: Array<Record<string, unknown>>;
    row_count?: number;
    elapsed_ms?: number;
  };

  let query = `SELECT p.topic, COUNT(*) AS problems
FROM problems p
GROUP BY p.topic
ORDER BY problems DESC`;

  let result: SqlLabResult | null = null;
  let error = '';
  let running = false;

  async function run() {
    if (running || !query.trim()) return;

    running = true;
    error = '';

    try {
      result = await api.post<SqlLabResult>('/api/admin/sql-lab', { query });
    } catch (e: any) {
      result = null;
      error = e?.message || 'SQL query failed.';
    } finally {
      running = false;
    }
  }

  function display(value: unknown): string {
    if (value === null) return 'NULL';
    if (typeof value === 'object') return JSON.stringify(value);
    return String(value);
  }
</script>

<svelte:head>
  <title>SQL Lab · CodeForge</title>
</svelte:head>

{#if $auth.user?.role !== 'admin'}
  <div class="alert error">Administrator access required.</div>
{:else}
  <div class="page-head">
    <div>
      <span class="eyebrow">ADMINISTRATOR · DBMS DEMONSTRATION</span>
      <h1>SQL Lab</h1>
      <p>
        Read-only SELECT, WITH, SHOW, DESCRIBE and EXPLAIN exploration with strict result limits.
      </p>
    </div>
  </div>

  <section class="panel editor-panel">
    <div class="code-box">EXPLAIN SELECT * FROM submissions WHERE user_id = 'u1';</div>
    <textarea class="code-editor sql" bind:value={query}></textarea>
    <button class="btn primary" on:click={run} disabled={running || !query.trim()}>
      {running ? 'Running...' : 'Run query →'}
    </button>

    {#if error}
      <div class="alert error">{error}</div>
    {/if}

    {#if result?.rows?.length}
      <div class="table-wrap">
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
                  <td>{display(value)}</td>
                {/each}
              </tr>
            {/each}
          </tbody>
        </table>
      </div>
    {:else if result}
      <div class="empty-state">Query completed. No rows returned.</div>
    {/if}
  </section>
{/if}
