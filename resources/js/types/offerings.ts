export type OfferingStatus = 'active' | 'cancelled' | 'completed';

export type OfferingCurrency = 'EUR' | 'USD';

// for preview cards
export type OfferingSummary = {
    id: number;
    name: string;
    description: string | null;
    starts_at: string;
    ends_at: string | null;
    timezone: string;
    capacity: number;
    price: string;
    currency: OfferingCurrency | null;
    status: OfferingStatus;
};
