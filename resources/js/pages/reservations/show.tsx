import { Head } from '@inertiajs/react';
import ReservationOverviewCard from '@/components/reservations/reservation-overview-card';
import ReservationPaymentCard from '@/components/reservations/reservation-payment-card';
import type { ReservationShowProps } from '@/components/reservations/reservation-show-types';
import { index as companiesIndex } from '@/routes/companies';
import { index as companyOfferings } from '@/routes/companies/offerings';
import { show as offeringShow } from '@/routes/offerings';

export default function Show({
    reservation,
    offering,
    company,
    serverTime,
}: ReservationShowProps) {
    return (
        <>
            <Head
                title={
                    reservation.reference === null
                        ? `Reservation #${reservation.id}`
                        : `Reservation ${reservation.reference}`
                }
            />

            <div className="m-5">
                <div className="mx-auto max-w-6xl">
                    <header className="mb-8 max-w-2xl">
                        <p className="mb-2 text-sm font-medium text-sky-600 dark:text-sky-400">
                            Your reservation
                        </p>
                        <h1 className="text-3xl font-bold tracking-tight text-gray-900 dark:text-white">
                            Reservation details
                        </h1>
                        <p className="mt-3 text-sm/6 text-muted-foreground sm:text-base">
                            {reservation.status === 'pending'
                                ? 'Review your reserved spots and complete payment before the hold expires.'
                                : 'Review the offering, payment, and status of your reservation.'}
                        </p>
                    </header>

                    <div className="grid gap-6 lg:grid-cols-[minmax(0,1fr)_23rem] lg:items-start">
                        <ReservationOverviewCard
                            reservation={reservation}
                            offering={offering}
                            company={company}
                        />

                        <ReservationPaymentCard
                            key={`${reservation.id}-${reservation.status}-${reservation.expired_at}`}
                            reservation={reservation}
                            offering={offering}
                            serverTime={serverTime}
                        />
                    </div>
                </div>
            </div>
        </>
    );
}

Show.layout = ({ reservation, offering, company }: ReservationShowProps) => ({
    breadcrumbs: [
        {
            title: 'Companies',
            href: companiesIndex(),
        },
        {
            title: company.name,
            href: companyOfferings({ team: company.slug }),
        },
        {
            title: offering.name,
            href: offeringShow(offering.id),
        },
        {
            title: reservation.reference ?? `Reservation #${reservation.id}`,
        },
    ],
});
