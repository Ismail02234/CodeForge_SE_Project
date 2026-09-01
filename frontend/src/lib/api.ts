import { PUBLIC_API_URL } from '$env/static/public';

export const API_URL = (PUBLIC_API_URL || 'http://localhost:8000').replace(/\/$/, '');

export class ApiError extends Error {
  status: number;
  errors: Record<string, string[]>;

  constructor(message: string, status = 500, errors: Record<string, string[]> = {}) {
    super(message);
    this.name = 'ApiError';
    this.status = status;
    this.errors = errors;
  }
}

function cookie(name: string): string {
  if (typeof document === 'undefined') return '';
  const part = document.cookie.split('; ').find((item) => item.startsWith(`${name}=`));
  return part ? decodeURIComponent(part.split('=').slice(1).join('=')) : '';
}

async function csrf(): Promise<void> {
  await fetch(`${API_URL}/sanctum/csrf-cookie`, {
    credentials: 'include',
    headers: { Accept: 'application/json' },
  });
}

type RequestOptions = RequestInit & { csrf?: boolean };

export async function request<T = any>(path: string, options: RequestOptions = {}): Promise<T> {
  const method = (options.method || 'GET').toUpperCase();
  const needsCsrf = options.csrf ?? !['GET', 'HEAD', 'OPTIONS'].includes(method);
  if (needsCsrf) await csrf();

  const headers = new Headers(options.headers || {});
  headers.set('Accept', 'application/json');
  if (options.body && !(options.body instanceof FormData))
    headers.set('Content-Type', 'application/json');
  const token = cookie('XSRF-TOKEN');
  if (needsCsrf && token) headers.set('X-XSRF-TOKEN', token);

  const response = await fetch(`${API_URL}${path}`, {
    ...options,
    method,
    headers,
    credentials: 'include',
  });

  const type = response.headers.get('content-type') || '';
  const data = type.includes('application/json') ? await response.json() : await response.text();

  if (!response.ok) {
    const message =
      typeof data === 'object' && data?.message
        ? data.message
        : `Request failed (${response.status})`;
    throw new ApiError(message, response.status, typeof data === 'object' ? data.errors || {} : {});
  }

  return data as T;
}

export const api = {
  get: <T = any>(path: string) => request<T>(path),
  post: <T = any>(path: string, data: any = {}) =>
    request<T>(path, { method: 'POST', body: JSON.stringify(data) }),
  patch: <T = any>(path: string, data: any = {}) =>
    request<T>(path, { method: 'PATCH', body: JSON.stringify(data) }),
  delete: <T = any>(path: string) => request<T>(path, { method: 'DELETE' }),
};
