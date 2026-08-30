import { Link } from '@inertiajs/react';
import { useMemo } from 'react';
import { show } from '@/routes/offerings';
import type { OfferingSummary } from '@/types/offerings';

type Props = {
    offering: OfferingSummary;
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

export default function OfferingPreviewCard({ offering }: Props) {
    const stats = useMemo(() => {
        return [
            {
                name: 'Start Date',
                value: formatDateTime(offering.starts_at, offering.timezone),
                fullWidth: true,
            },
            {
                name: 'End Date',
                value: formatDateTime(offering.ends_at, offering.timezone),
                fullWidth: true,
            },
            {
                name: 'Timezone',
                value: offering.timezone,
                fullWidth: false,
            },
            {
                name: 'Capacity',
                value: offering.capacity,
                fullWidth: false,
            },
            {
                name: 'Price',
                value: offering.price,
                fullWidth: false,
            },
            {
                name: 'Currency',
                value: offering.currency,
                fullWidth: false,
            },
        ];
    }, [offering]);

    return (
        <>
            <Link
                href={show({ offering: offering.id })}
                className="group flex h-full flex-col overflow-hidden rounded-xl outline outline-gray-200 hover:bg-sky-700 dark:-outline-offset-1 dark:outline-white/10"
            >
                <div className="flex flex-1 items-center gap-x-4 border-b border-gray-900/5 bg-gray-50 p-6 dark:border-white/10 dark:bg-gray-800/50">
                    <img
                        alt={`${offering.name} logo`}
                        src={
                            'https://tailwindcss.com/plus-assets/img/logos/48x48/tuple.svg'
                        }
                        className="size-12 flex-none rounded-lg bg-white object-cover ring-1 ring-gray-900/10 dark:bg-gray-700 dark:ring-white/10"
                    />
                    <div className={'max-w-xs'}>
                        <div className="text-sm/6 font-medium text-gray-900 dark:text-white">
                            {offering.name}
                        </div>
                        <div className="line-clamp-2 text-sm/6 font-medium text-gray-900 dark:text-white">
                            {offering.description}
                        </div>
                    </div>
                </div>
                <dl className="grid grid-cols-2 gap-px bg-gray-900/5 dark:bg-white/10">
                    {stats
                        .filter((stat) => stat.value !== null)
                        .map((stat) => (
                            <div
                                key={stat.name}
                                className={`flex min-w-0 flex-col items-center gap-y-1 bg-white px-3 py-4 text-center group-hover:bg-sky-500 dark:bg-gray-900 dark:group-hover:bg-sky-700 ${stat.fullWidth ? 'col-span-2' : ''} ${stat.name === 'Price' && stat.value === '0.00' ? 'col-span-2' : ''} `}
                            >
                                <dt className="text-sm/6 font-medium text-gray-500 dark:text-gray-400">
                                    {stat.name}
                                </dt>
                                <dd className="w-full text-sm leading-5 text-balance break-words text-gray-900 dark:text-white">
                                    {stat.name === 'Price' &&
                                    stat.value === '0.00'
                                        ? 'Free'
                                        : stat.value}
                                </dd>
                            </div>
                        ))}
                </dl>
            </Link>
        </>
    );
}
