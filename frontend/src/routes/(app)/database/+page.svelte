<script lang="ts">
  import { onMount } from 'svelte';
  import { api } from '$lib/api';
  import { auth } from '$lib/stores/auth';
  import Loading from '$lib/components/Loading.svelte';
  let users: any[] = [],
    universities: any[] = [],
    error = '',
    editing: any = null,
    newUser = {
      username: '',
      password: '',
      rating: 1200,
      rank: 'Newbie',
      role: 'user',
      university: '',
    };
  async function load() {
    try {
      const d = await api.get('/api/database/users');
      users = d.users;
      universities = d.universities;
    } catch (e: any) {
      error = e.message;
    }
  }
  onMount(load);
  async function create() {
    try {
      await api.post('/api/admin/users', newUser);
      newUser = {
        username: '',
        password: '',
        rating: 1200,
        rank: 'Newbie',
        role: 'user',
        university: '',
      };
      await load();
    } catch (e: any) {
      error = e.message;
    }
  }
  async function save() {
    try {
      await api.patch(`/api/admin/users/${editing.id}`, editing);
      editing = null;
      await load();
    } catch (e: any) {
      error = e.message;
    }
  }
  async function remove(id: string) {
    if (!confirm('Delete this user and dependent history?')) return;
    try {
      await api.delete(`/api/admin/users/${id}`);
      await load();
    } catch (e: any) {
      error = e.message;
    }
  }
</script>

<svelte:head><title>Database · CodeForge</title></svelte:head>
<div class="page-head">
  <div>
    <span class="eyebrow">RELATIONAL DATA MANAGEMENT</span>
    <h1>Database</h1>
    <p>
      Authenticated read view. Admin-only create, edit and delete operations remain server
      protected.
    </p>
  </div>
  {#if $auth.user?.role === 'admin'}<a class="btn ghost" href="/sql-lab">Open SQL Lab</a>{/if}
</div>
{#if error}<div class="alert error">{error}</div>{/if}{#if !users.length && !error}<Loading
  />{:else}<section class="panel">
    <div class="panel-head">
      <h2>Users</h2>
      <span>{users.length} rows</span>
    </div>
    <div class="table-wrap">
      <table>
        <thead
          ><tr
            ><th>User</th><th>Role</th><th>Rating</th><th>University</th><th>Solved</th><th
              >Submissions</th
            >{#if $auth.user?.role === 'admin'}<th></th>{/if}</tr
          ></thead
        ><tbody
          >{#each users as u}<tr
              ><td><a href={`/profile/${u.id}`}><b>{u.username}</b></a><small>{u.rank}</small></td
              ><td>{u.role}</td><td>{u.rating}</td><td>{u.university || '—'}</td><td>{u.solved}</td
              ><td>{u.submissions}</td>{#if $auth.user?.role === 'admin'}<td
                  ><button class="btn tiny ghost" on:click={() => (editing = { ...u })}>Edit</button
                  >{#if u.id !== $auth.user.id}<button
                      class="btn tiny danger"
                      on:click={() => remove(u.id)}>Delete</button
                    >{/if}</td
                >{/if}</tr
            >{/each}</tbody
        >
      </table>
    </div>
  </section>
  {#if $auth.user?.role === 'admin'}<section class="two-col">
      <form class="panel" on:submit|preventDefault={create}>
        <span class="eyebrow">ADMIN</span>
        <h2>Create user</h2>
        <input bind:value={newUser.username} placeholder="Username" required /><input
          type="password"
          bind:value={newUser.password}
          placeholder="Password (min 8)"
          required
        />
        <div class="form-grid">
          <input type="number" bind:value={newUser.rating} /><select bind:value={newUser.role}
            ><option>user</option><option>admin</option></select
          >
        </div>
        <input bind:value={newUser.rank} placeholder="Rank" /><select
          bind:value={newUser.university}
          ><option value="">No university</option>{#each universities as u}<option value={u.name}
              >{u.name}</option
            >{/each}</select
        ><button class="btn primary">Create</button>
      </form>
      {#if editing}<form class="panel" on:submit|preventDefault={save}>
          <span class="eyebrow">EDIT</span>
          <h2>{editing.username}</h2>
          <div class="form-grid">
            <input type="number" bind:value={editing.rating} /><select bind:value={editing.role}
              ><option>user</option><option>admin</option></select
            >
          </div>
          <input bind:value={editing.rank} /><select bind:value={editing.university}
            ><option value="">No university</option>{#each universities as u}<option value={u.name}
                >{u.name}</option
              >{/each}</select
          >
          <div class="page-actions">
            <button class="btn primary">Save</button><button
              type="button"
              class="btn ghost"
              on:click={() => (editing = null)}>Cancel</button
            >
          </div>
        </form>{/if}
    </section>{/if}{/if}
