import { writable } from 'svelte/store';
import { api } from '$lib/api';

export type User = {
  id: string;
  username: string;
  role: 'user' | 'admin';
  rating: number;
  university?: string | null;
  rank: string;
};

type AuthState = { user: User | null; loaded: boolean; loading: boolean };

export const auth = writable<AuthState>({ user: null, loaded: false, loading: false });
let pending: Promise<User | null> | null = null;

export function loadUser(force = false): Promise<User | null> {
  if (pending && !force) return pending;
  pending = (async () => {
    auth.update((state) => ({ ...state, loading: true }));
    try {
      const user = await api.get<User>('/api/me');
      auth.set({ user, loaded: true, loading: false });
      return user;
    } catch (error: any) {
      if (error?.status !== 401) console.error(error);
      auth.set({ user: null, loaded: true, loading: false });
      return null;
    } finally {
      pending = null;
    }
  })();
  return pending;
}

export async function login(username: string, password: string): Promise<User> {
  const result = await api.post<{ user: User }>('/login', { username, password });
  auth.set({ user: result.user, loaded: true, loading: false });
  return result.user;
}

export async function register(payload: {
  username: string;
  password: string;
  password_confirmation: string;
  university?: string;
}): Promise<User> {
  const result = await api.post<{ user: User }>('/register', payload);
  auth.set({ user: result.user, loaded: true, loading: false });
  return result.user;
}

export async function logout(): Promise<void> {
  try {
    await api.post('/logout');
  } finally {
    auth.set({ user: null, loaded: true, loading: false });
  }
}
