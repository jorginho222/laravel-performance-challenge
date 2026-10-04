/**
 * Prices travel as decimal strings ("19.99") and are added up in cents, so totals never
 * suffer floating point errors (the backend does the same).
 */
export function toCents(amount: string): number {
    const [whole = '0', fraction = ''] = amount.split('.');

    return Number(whole) * 100 + Number(fraction.padEnd(2, '0').slice(0, 2));
}

export function formatCents(cents: number): string {
    return `${Math.trunc(cents / 100)}.${String(cents % 100).padStart(2, '0')}`;
}
