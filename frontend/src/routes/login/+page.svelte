<script lang="ts">
  import { goto } from '$app/navigation';
  import { login, loadUser } from '$lib/stores/auth';
  import { onMount } from 'svelte';
  import ThemeToggle from '$lib/components/ThemeToggle.svelte';
  let username = '';
  let password = '';
  let error = '';
  let busy = false;
  onMount(async () => {
    if (await loadUser()) goto('/dashboard');
  });
  async function submit() {
    error = '';
    busy = true;
    try {
      await login(username, password);
      await goto('/dashboard');
    } catch (e: any) {
      error = e.message || 'Login failed.';
    } finally {
      busy = false;
    }
  }
</script>

<svelte:head><title>Log in · CodeForge</title></svelte:head>
<div class="auth-page">
  <section class="auth-art">
    <a class="brand" href="/"><span>&lt;/&gt;</span><strong>CODE<b>FORGE</b></strong></a>
    <div class="auth-copy">
      <span class="eyebrow live"><i></i>ACCESS TERMINAL</span>
      <h1>GET<br />BACK<br /><b>IN.</b></h1>
      <p>Continue your performance history, Performance Profile, Ghost Races and SQL battles.</p>
    </div>
  </section>
  <section class="auth-form-wrap">
    <div class="auth-top-row">
      <a class="back" href="/">← Back to landing</a>
      <ThemeToggle />
    </div>
    <form class="auth-form" on:submit|preventDefault={submit}>
      <span class="eyebrow">LOG IN</span>
      <h2>Access CodeForge.</h2>
      <p>New here? <a href="/register">Create an account.</a></p>
      {#if error}<div class="alert error">{error}</div>{/if}<label
        >USERNAME<input bind:value={username} autocomplete="username" required /></label
      ><label
        >PASSWORD<input
          type="password"
          bind:value={password}
          autocomplete="current-password"
          required
        /></label
      ><button class="btn primary big" disabled={busy}
        >{busy ? 'AUTHENTICATING...' : 'LOG IN →'}</button
      >
      <div class="demo">DEMO: Ismail / 123456 · Admin / admin123</div>
    </form>
  </section>
</div>
