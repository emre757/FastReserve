import { Head } from '@inertiajs/react';
import { useEffect, useState } from 'react';
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
    paymentMethods = [],
}: ReservationShowProps) {
    const isFree = Number(reservation.amount_due) === 0;
    const timerKey = `${reservation.id}-${reservation.expired_at}-${serverTime}`;
    const [elapsed, setElapsed] = useState({ key: timerKey, seconds: 0 });
    const elapsedSeconds = elapsed.key === timerKey ? elapsed.seconds : 0;
    const initialSeconds = reservation.expired_at
        ? Math.max(
              0,
              Math.floor(
                  (new Date(reservation.expired_at).getTime() -
                      new Date(serverTime).getTime()) /
                      1000,
              ),
          )
        : 0;
    const remainingSeconds = Math.max(0, initialSeconds - elapsedSeconds);

    useEffect(() => {
        if (reservation.status !== 'pending' || initialSeconds === 0) {
            return;
        }

        const startedAt = Date.now();
        const interval = window.setInterval(() => {
            const elapsed = Math.floor((Date.now() - startedAt) / 1000);
            setElapsed({ key: timerKey, seconds: elapsed });

            if (elapsed >= initialSeconds) {
                window.clearInterval(interval);
            }
        }, 1000);

        return () => window.clearInterval(interval);
    }, [timerKey, reservation.status, initialSeconds]);

    const displayReservation =
        reservation.status === 'pending' && remainingSeconds === 0
            ? { ...reservation, status: 'expired' as const }
            : reservation;

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
                            {displayReservation.status === 'expired'
                                ? 'Your reservation expired because the hold ended. Your spots are no longer reserved.'
                                : displayReservation.status === 'cancelled'
                                  ? 'This reservation has been cancelled and can no longer be confirmed.'
                                  : displayReservation.status === 'pending'
                                    ? isFree
                                        ? 'Review your spots and confirm your free reservation before the hold expires.'
                                        : 'Review your reserved spots and complete payment before the hold expires.'
                                    : 'Review the offering and status of your reservation.'}
                        </p>
                    </header>

                    <div className="grid gap-6 lg:grid-cols-[minmax(0,1fr)_23rem] lg:items-start">
                        <ReservationOverviewCard
                            reservation={displayReservation}
                            offering={offering}
                            company={company}
                        />

                        <ReservationPaymentCard
                            key={`${reservation.id}-${reservation.status}-${reservation.expired_at}`}
                            reservation={displayReservation}
                            offering={offering}
                            remainingSeconds={remainingSeconds}
                            paymentMethods={paymentMethods}
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
