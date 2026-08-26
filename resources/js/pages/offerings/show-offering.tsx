import { Head } from '@inertiajs/react';

type Props = {
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
};

// function formatDateTime(value: string | null, timezone: string): string {
//     if (value === null) {
//         return 'No deadline';
//     }
//
//     return new Intl.DateTimeFormat(undefined, {
//         dateStyle: 'medium',
//         timeStyle: 'short',
//         timeZone: timezone,
//     }).format(new Date(value));
// }

export default function ShowOffering({ offering }: Props) {
    // const {
    //     id,
    //     name,
    //     description,
    //     starts_at,
    //     ends_at,
    //     timezone,
    //     capacity,
    //     price,
    //     currency,
    //     booking_deadline_at,
    //     cancellation_deadline_at,
    //     hold_duration_minutes,
    //     status,
    // } = offering;

    return (
        <>
            {/* no name as it may be too long */}
            <Head title={'Offering Details'} />
            <div className={'m-5'}>
                <pre className="whitespace-pre-wrap">
                    {JSON.stringify(offering, null, 2)}
                </pre>
            </div>
        </>
    );
}
