import { Head } from '@inertiajs/react';
import OfferingCapacityCount from '@/components/offerings/offering-capacity-count';
import OfferingHeader from '@/components/offerings/offering-header';
import { useEchoConnectionStatus } from '@/hooks/use-echo-connection-status';
import { useOfferingCapacity } from '@/hooks/use-offering-capacity';
import { index as companiesIndex } from '@/routes/companies';
import { index as offeringsIndex } from '@/routes/companies/offerings';

type Props = {
    company: {
        name: string;
        slug: string;
    };
    offering: {
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
    };
    permissions: {
        canUpdateOffering: boolean;
        canDeleteOffering: boolean;
    };
    reservedSpots: number;
};

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

export default function Show({ offering, permissions, reservedSpots }: Props) {
    const startsAtFormatted = formatDateTime(
        offering.starts_at,
        offering.timezone,
    );

    const initialAvailableSpots = offering.capacity - reservedSpots;
    const availableSpots = useOfferingCapacity(
        offering.id,
        initialAvailableSpots,
    );

    const connectionStatus = useEchoConnectionStatus();
    const isConnected = connectionStatus === 'connected';

    const stats = [
        {
            name: 'Price',
            stat: offering.price
                ? `${offering.price.toString()} ${offering.currency}`
                : 'N/A',
        },
        { name: 'Available Spots', stat: availableSpots },
        { name: 'Offering Status', stat: offering.status },
    ];

    return (
        <>
            {/* no name as it may be too long */}
            <Head title={'Offering Details'} />
            <div className={'m-5'}>
                <OfferingHeader
                    id={offering.id}
                    name={offering.name}
                    timezone={offering.timezone}
                    capacity={offering.capacity}
                    starts_at={startsAtFormatted}
                    ends_at={formatDateTime(
                        offering.ends_at,
                        offering.timezone,
                    )}
                    cancellation_deadline_at={formatDateTime(
                        offering.cancellation_deadline_at,
                        offering.timezone,
                    )}
                    booking_deadline_at={formatDateTime(
                        offering.booking_deadline_at,
                        offering.timezone,
                    )}
                    can_edit={permissions.canUpdateOffering}
                    can_delete={permissions.canDeleteOffering}
                />

                {/*stats (price, spots & status)*/}
                <div>
                    <dl className="mt-5 grid grid-cols-1 gap-5 sm:grid-cols-3">
                        {stats.map((item) => (
                            <div
                                key={item.name}
                                className="relative overflow-hidden rounded-lg bg-white px-4 py-5 shadow-sm sm:p-6 dark:bg-gray-800/75 dark:inset-ring dark:inset-ring-white/10"
                            >
                                {item.name === 'Available Spots' &&
                                    (isConnected ? (
                                        <span className="absolute top-4 right-4 flex size-3">
                                            <span className="absolute inline-flex size-full animate-ping rounded-full bg-sky-400 opacity-75" />
                                            <span className="relative inline-flex size-3 rounded-full bg-sky-500" />
                                        </span>
                                    ) : (
                                        <span className="absolute top-4 right-4 flex size-3">
                                            <span className="relative inline-flex size-3 rounded-full bg-red-500" />
                                        </span>
                                    ))}
                                <dt className="truncate text-sm font-medium text-gray-500 dark:text-gray-400">
                                    {item.name}
                                </dt>
                                <dd className="mt-1 text-3xl font-semibold tracking-tight text-gray-900 dark:text-white">
                                    {item.name !== 'Available Spots' ? (
                                        item.stat
                                    ) : isConnected ? (
                                        <OfferingCapacityCount
                                            key={'capacity-' + offering.id}
                                            availableSpots={availableSpots}
                                        />
                                    ) : (
                                        `${availableSpots} (live: ${connectionStatus})`
                                    )}
                                </dd>
                            </div>
                        ))}
                    </dl>
                </div>
            </div>
        </>
    );
}

Show.layout = ({ company, offering }: Props) => ({
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
