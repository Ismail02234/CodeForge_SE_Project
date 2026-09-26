export function parseNumbers(value: string, limit = 14) {
  return value
    .split(/[\s,]+/)
    .map((item) => Number(item.trim()))
    .filter((item) => Number.isFinite(item))
    .slice(0, limit);
}

export function barHeight(value: number, values: number[]) {
  const biggest = Math.max(...values.map((item) => Math.abs(item)), 1);
  return 45 + (Math.abs(value) / biggest) * 145;
}
