<script lang="ts">
  import { onMount } from 'svelte';
  import { goto } from '$app/navigation';
  import { api } from '$lib/api';
  import { loadUser, register } from '$lib/stores/auth';
  let username = '';
  let university = '';
  let password = '';
  let confirm = '';
  let universities: any[] = [];
  let error = '';
  let busy = false;
  onMount(async () => {
    if (await loadUser()) return goto('/dashboard');
    try {
      universities = await api.get('/api/universities/options');
    } catch {}
  });
  async function submit() {
    error = '';
    busy = true;
    try {
      await register({
        username,
        password,
        password_confirmation: confirm,
        university: university || undefined,
      });
      await goto('/dashboard');
    } catch (e: any) {
      error = e.message || 'Registration failed.';
    } finally {
      busy = false;
    }
  }
</script>

<svelte:head><title>Create account · CodeForge</title></svelte:head>
<div class="auth-page">
  <section class="auth-art">
    <a class="brand" href="/"><span>&lt;/&gt;</span><strong>CODE<b>FORGE</b></strong></a>
    <div class="auth-copy">
      <span class="eyebrow live"><i></i>NEW COMBATANT</span>
      <h1>ENTER<br />THE<br /><b>FORGE.</b></h1>
      <p>
        Every solve, failed attempt, race and SQL battle starts building your competitive identity.
      </p>
    </div>
  </section>
  <section class="auth-form-wrap">
    <a class="back" href="/">← Back to landing</a>
    <form class="auth-form" on:submit|preventDefault={submit}>
      <span class="eyebrow">CREATE ACCOUNT</span>
      <h2>Forge your identity.</h2>
      <p>Already registered? <a href="/login">Log in.</a></p>
      {#if error}<div class="alert error">{error}</div>{/if}<label
        >USERNAME<input bind:value={username} minlength="3" maxlength="32" required /></label
      ><label
        >UNIVERSITY<select bind:value={university}
          ><option value="">No university selected</option>{#each universities as u}<option
              value={u.name}>{u.name}</option
            >{/each}</select
        ></label
      >
      <div class="form-grid">
        <label>PASSWORD<input type="password" bind:value={password} minlength="8" required /></label
        ><label>CONFIRM<input type="password" bind:value={confirm} minlength="8" required /></label>
      </div>
      <button class="btn primary big" disabled={busy}
        >{busy ? 'CREATING...' : 'CREATE ACCOUNT →'}</button
      >
    </form>
  </section>
</div>
