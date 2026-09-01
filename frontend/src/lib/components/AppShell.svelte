<script lang="ts">
  import { goto } from '$app/navigation';
  import { page } from '$app/stores';
  import { auth, logout } from '$lib/stores/auth';
  let mobileOpen = false;
  let search = '';
  const nav = [
    ['/dashboard', 'Dashboard', '⌁'],
    ['/problems', 'Problems', '<>'],
    ['/contests', 'Contests', '◆'],
    ['/rivalry', 'Rivalry', '⚡'],
    ['/universities', 'Universities', '⌂'],
    ['/code-dna', 'Code DNA', '⬡'],
    ['/ghost-race', 'Ghost Race', '◉'],
    ['/sql-battle', 'SQL Battle', '▦'],
    ['/database', 'Database', '▤'],
    ['/how-it-works', 'How it works', '?'],
  ];
  async function signOut() {
    await logout();
    await goto('/');
  }
  function submitSearch() {
    const q = search.trim();
    if (q) goto(`/search?q=${encodeURIComponent(q)}`);
  }
</script>

<div class="app-shell">
  <aside class:open={mobileOpen} class="sidebar">
    <a class="brand" href="/dashboard"><span>&lt;/&gt;</span><strong>CODE<b>FORGE</b></strong></a>
    <div class="side-caption">COMMAND SYSTEM</div>
    <nav>
      {#each nav as item}
        <a
          href={item[0]}
          class:active={$page.url.pathname === item[0] ||
            ($page.url.pathname.startsWith(item[0] + '/') && item[0] !== '/dashboard')}
          on:click={() => (mobileOpen = false)}
        >
          <i>{item[2]}</i><span>{item[1]}</span>
        </a>
      {/each}
      {#if $auth.user?.role === 'admin'}<a
          href="/sql-lab"
          class:active={$page.url.pathname === '/sql-lab'}><i>SQL</i><span>SQL Lab</span></a
        >{/if}
    </nav>
    <div class="sidebar-user">
      <div class="avatar">{$auth.user?.username?.slice(0, 1).toUpperCase()}</div>
      <div><strong>{$auth.user?.username}</strong><small>{$auth.user?.rank}</small></div>
    </div>
  </aside>
  {#if mobileOpen}<button
      class="sidebar-scrim"
      aria-label="Close menu"
      on:click={() => (mobileOpen = false)}
    ></button>{/if}
  <section class="app-main">
    <header class="topbar">
      <button class="menu-button" on:click={() => (mobileOpen = !mobileOpen)} aria-label="Menu"
        >☰</button
      >
      <form class="global-search" on:submit|preventDefault={submitSearch}>
        <span>⌕</span><input bind:value={search} placeholder="Search CodeForge..." />
      </form>
      <div class="top-actions">
        <a class="top-profile" href={$auth.user ? `/profile/${$auth.user.id}` : '/dashboard'}
          ><span class="status-dot"></span>{$auth.user?.username}</a
        >
        <button class="logout-button" on:click={signOut} title="Log out">↗</button>
      </div>
    </header>
    <main class="content"><slot /></main>
  </section>
</div>
