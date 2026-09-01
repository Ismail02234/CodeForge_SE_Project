export function duration(seconds: number | null | undefined): string {
  const value = Math.max(0, Number(seconds || 0));
  const minutes = Math.floor(value / 60);
  const rest = value % 60;
  return `${minutes}:${String(rest).padStart(2, '0')}`;
}

export function dateTime(value: string | null | undefined): string {
  if (!value) return '—';
  const date = new Date(value.replace(' ', 'T'));
  return Number.isNaN(date.getTime()) ? value : date.toLocaleString();
}

export function number(value: number | string | null | undefined): string {
  return Number(value || 0).toLocaleString();
}
