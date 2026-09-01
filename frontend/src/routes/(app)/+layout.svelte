<script lang="ts">
  import { onMount } from 'svelte';
  import { goto } from '$app/navigation';
  import { auth, loadUser } from '$lib/stores/auth';
  import AppShell from '$lib/components/AppShell.svelte';
  import Loading from '$lib/components/Loading.svelte';
  let ready = false;
  onMount(async () => {
    const user = await loadUser();
    if (!user) {
      await goto('/login');
      return;
    }
    ready = true;
  });
</script>

{#if ready && $auth.user}<AppShell><slot /></AppShell>{:else}<div class="full-loading">
    <Loading />
  </div>{/if}
