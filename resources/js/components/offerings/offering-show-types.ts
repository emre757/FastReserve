export type OfferingShowCompany = {
    name: string;
    slug: string;
};

export type OfferingShowData = {
    id: number;
    name: string;
    description: string | null;
    starts_at: string;
    ends_at: string | null;
    timezone: string;
    capacity: number;
    price: string | null;
    currency: string | null;
    booking_deadline_at: string | null;
    cancellation_deadline_at: string | null;
    hold_duration_minutes: number;
    status: string;
    broadcast_version: number;
};

export type OfferingShowProps = {
    company: OfferingShowCompany;
    offering: OfferingShowData;
    permissions: {
        canUpdateOffering: boolean;
        canDeleteOffering: boolean;
        canBook: boolean;
    };
    reservedSpots: number;
    activeReservationId: number | null;
    canBook: boolean;
};

export type FormattedOfferingDates = {
    startsAt: string;
    endsAt: string;
    bookingDeadline: string;
    cancellationDeadline: string;
};
