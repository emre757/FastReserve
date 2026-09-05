export type ReservationStatus =
    'pending' | 'confirmed' | 'expired' | 'cancelled';

export type ReservationShowProps = {
    reservation: {
        id: number;
        reference: string | null;
        status: ReservationStatus;
        quantity: number;
        amount_due: string;
        created_at: string;
        expired_at: string | null;
        confirmed_at: string | null;
        cancelled_at: string | null;
    };
    offering: {
        id: number;
        name: string;
        price: string;
        currency: string | null;
        starts_at: string;
        timezone: string;
    };
    company: {
        name: string;
        slug: string;
    };
    serverTime: string;
};
