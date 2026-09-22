<script lang="ts">
  import { onMount } from 'svelte';

  type Theme = 'dark' | 'light';

  let theme: Theme = 'dark';
  let mounted = false;

  function applyTheme(next: Theme, persist = true): void {
    theme = next;

    if (typeof document !== 'undefined') {
      document.documentElement.dataset.theme = next;
      document.documentElement.style.colorScheme = next;
    }

    if (persist && typeof localStorage !== 'undefined') {
      localStorage.setItem('codeforge-theme', next);
    }
  }

  function toggleTheme(): void {
    applyTheme(theme === 'dark' ? 'light' : 'dark');
  }

  onMount(() => {
    const saved = localStorage.getItem('codeforge-theme');
    applyTheme(saved === 'light' ? 'light' : 'dark', false);
    mounted = true;
  });
</script>

<button
  class="theme-toggle"
  class:ready={mounted}
  type="button"
  on:click={toggleTheme}
  aria-label={theme === 'dark' ? 'Switch to light mode' : 'Switch to dark mode'}
  title={theme === 'dark' ? 'Light mode' : 'Dark mode'}
>
  <span class="theme-icon" aria-hidden="true">{theme === 'dark' ? '☀' : '☾'}</span>
  <span class="theme-label">{theme === 'dark' ? 'LIGHT' : 'DARK'}</span>
</button>

<style>
  .theme-toggle {
    display: inline-flex;
    min-width: 38px;
    height: 38px;
    align-items: center;
    justify-content: center;
    gap: 7px;
    padding: 0 10px;
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 9px;
    background: rgba(255, 255, 255, 0.025);
    color: #aeb4bc;
    cursor: pointer;
    opacity: 0;
    transform: translateY(-2px);
    transition:
      opacity 140ms ease,
      transform 160ms ease,
      border-color 160ms ease,
      background 160ms ease,
      color 160ms ease;
  }

  .theme-toggle.ready {
    opacity: 1;
    transform: translateY(0);
  }

  .theme-toggle:hover {
    border-color: rgba(255, 91, 55, 0.38);
    background: rgba(255, 91, 55, 0.055);
    color: #f4f6f7;
  }

  .theme-icon {
    font-size: 15px;
    line-height: 1;
  }

  .theme-label {
    font:
      700 8px 'JetBrains Mono',
      monospace;
    letter-spacing: 0.09em;
  }

  :global(.top-actions) .theme-toggle {
    padding-inline: 9px;
  }

  :global(.landing-auth) .theme-toggle {
    height: 38px;
    margin-left: 2px;
  }

  :global(.auth-top-row) .theme-toggle {
    flex: 0 0 auto;
  }

  :global(html[data-theme='light']) .theme-toggle {
    border-color: rgba(20, 28, 38, 0.13);
    background: rgba(20, 28, 38, 0.025);
    color: #56606b;
  }

  :global(html[data-theme='light']) .theme-toggle:hover {
    border-color: rgba(205, 68, 40, 0.3);
    background: rgba(255, 68, 35, 0.06);
    color: #171a1f;
  }

  @media (max-width: 720px) {
    .theme-toggle {
      width: 38px;
      min-width: 38px;
      padding: 0;
    }

    .theme-label {
      display: none;
    }
  }

  @media (prefers-reduced-motion: reduce) {
    .theme-toggle {
      transition: none;
    }
  }
</style>
