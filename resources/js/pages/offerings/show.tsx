import { Head, Link } from '@inertiajs/react';
import { Building2 } from 'lucide-react';
import OfferingAboutCard from '@/components/offerings/offering-about-card';
import OfferingBookingCard from '@/components/offerings/offering-booking-card';
import OfferingHeader from '@/components/offerings/offering-header';
import type {
    FormattedOfferingDates,
    OfferingShowProps,
} from '@/components/offerings/offering-show-types';
import { useEchoConnectionStatus } from '@/hooks/use-echo-connection-status';
import { useOfferingCapacity } from '@/hooks/use-offering-capacity';
import { index as companiesIndex } from '@/routes/companies';
import { index as offeringsIndex } from '@/routes/companies/offerings';

function formatDateTime(value: string | null, timezone: string): string {
    if (value === null) {
        return 'No deadline';
    }

    return new Intl.DateTimeFormat(undefined, {
        dateStyle: 'medium',
        timeStyle: 'short',
        timeZone: timezone,
    }).format(new Date(value));
}

export default function Show({
    company,
    offering,
    permissions,
    reservedSpots,
    activeReservationId = null,
}: OfferingShowProps) {
    const dates: FormattedOfferingDates = {
        startsAt: formatDateTime(offering.starts_at, offering.timezone),
        endsAt: formatDateTime(offering.ends_at, offering.timezone),
        bookingDeadline: formatDateTime(
            offering.booking_deadline_at,
            offering.timezone,
        ),
        cancellationDeadline: formatDateTime(
            offering.cancellation_deadline_at,
            offering.timezone,
        ),
    };

    const initialAvailableSpots = offering.capacity - reservedSpots;
    const availableSpots = useOfferingCapacity(
        offering.id,
        initialAvailableSpots,
        offering.broadcast_version,
    );

    const connectionStatus = useEchoConnectionStatus();

    return (
        <>
            {/* no name as it may be too long */}
            <Head title={'Offering Details'} />
            <div className="m-5">
                <div className="mx-auto max-w-7xl space-y-6">
                    <section className="relative overflow-hidden rounded-2xl border bg-card p-6 shadow-sm sm:p-8">
                        <div
                            aria-hidden="true"
                            className="absolute inset-x-0 top-0 h-1 bg-linear-to-r from-sky-500 via-blue-500 to-indigo-500"
                        />
                        <div
                            aria-hidden="true"
                            className="absolute -top-24 -right-16 size-64 rounded-full bg-sky-500/5 blur-3xl"
                        />

                        <div className="relative">
                            <Link
                                href={offeringsIndex({ team: company.slug })}
                                className="mb-5 inline-flex items-center gap-2 text-sm font-medium text-sky-700 transition-colors hover:text-sky-900 dark:text-sky-400 dark:hover:text-sky-200"
                            >
                                <Building2
                                    aria-hidden="true"
                                    className="size-4"
                                />
                                {company.name}
                            </Link>

                            <OfferingHeader
                                id={offering.id}
                                name={offering.name}
                                timezone={offering.timezone}
                                capacity={offering.capacity}
                                starts_at={dates.startsAt}
                                ends_at={dates.endsAt}
                                cancellation_deadline_at={
                                    dates.cancellationDeadline
                                }
                                booking_deadline_at={dates.bookingDeadline}
                                can_edit={permissions.canUpdateOffering}
                                can_delete={permissions.canDeleteOffering}
                            />
                        </div>
                    </section>

                    <div className="grid gap-6 lg:grid-cols-[minmax(0,1fr)_23rem] lg:items-start">
                        <OfferingAboutCard
                            offering={offering}
                            dates={dates}
                            showBookingTerms={permissions.canBook}
                        />

                        <OfferingBookingCard
                            offering={offering}
                            availableSpots={availableSpots}
                            connectionStatus={connectionStatus}
                            activeReservationId={activeReservationId}
                            canBook={permissions.canBook}
                        />
                    </div>
                </div>
            </div>
        </>
    );
}

Show.layout = ({ company, offering }: OfferingShowProps) => ({
    breadcrumbs: [
        {
            title: 'Companies',
            href: companiesIndex(),
        },
        {
            title: company.name,
            href: offeringsIndex(company.slug),
        },
        {
            title: offering.name,
        },
    ],
});
