import { Head } from '@inertiajs/react';
import OfferingCapacityCount from '@/components/offerings/offering-capacity-count';
import OfferingHeader from '@/components/offerings/offering-header';
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

export default function Show({ offering, permissions }: Props) {
    const startsAtFormatted = formatDateTime(
        offering.starts_at,
        offering.timezone,
    );

    return (
        <>
            {/* no name as it may be too long */}
            <Head title={'Offering Details'} />
            <div className={'m-5'}>
                <OfferingHeader
                    id={offering.id}
                    name={offering.name}
                    timezone={offering.timezone}
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

                {/*TODO: remove hardcoded spot value*/}
                <OfferingCapacityCount
                    key={'capacity-' + offering.id}
                    offeringId={offering.id}
                    availableSpots={0}
                />
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
