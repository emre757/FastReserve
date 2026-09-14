export function formatPrice(amount: number, currency: string | null): string {
    if (amount === 0) {
        return 'Free';
    }

    if (currency === null) {
        return amount.toFixed(2);
    }

    return new Intl.NumberFormat(undefined, {
        style: 'currency',
        currency,
    }).format(amount);
}
