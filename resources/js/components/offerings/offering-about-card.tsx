import { CalendarClock, CircleDollarSign, TimerReset } from 'lucide-react';
import type {
    FormattedOfferingDates,
    OfferingShowData,
} from '@/components/offerings/offering-show-types';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';

type Props = {
    offering: OfferingShowData;
    dates: FormattedOfferingDates;
    showBookingTerms: boolean;
};

export default function OfferingAboutCard({
    offering,
    dates,
    showBookingTerms,
}: Props) {
    const bookingTerms = [
        {
            label: 'Booking deadline',
            value: dates.bookingDeadline,
            description: 'Complete your reservation before this time.',
            icon: CalendarClock,
        },
        {
            label: 'Cancellation deadline',
            value: dates.cancellationDeadline,
            description: 'The final moment to cancel an eligible reservation.',
            icon: CircleDollarSign,
        },
        {
            label: 'Payment hold',
            value: `${offering.hold_duration_minutes} minutes`,
            description: 'Reserved spots are held while payment is completed.',
            icon: TimerReset,
        },
    ];

    return (
        <div className="space-y-6">
            <Card className="gap-0 overflow-hidden py-0 shadow-sm">
                <CardHeader className="border-b bg-muted/30 px-6 py-5 sm:px-8">
                    <CardTitle>About this offering</CardTitle>
                    <CardDescription>
                        Details about this offering.
                    </CardDescription>
                </CardHeader>

                <CardContent className="p-6 sm:p-8">
                    <div
                        className="max-h-[32rem] overflow-y-auto overscroll-contain pr-3 focus-visible:ring-2 focus-visible:ring-ring/50 focus-visible:outline-none"
                        tabIndex={0}
                        aria-label="Offering description"
                    >
                        <p className="text-sm/7 [overflow-wrap:anywhere] break-words whitespace-pre-line text-foreground sm:text-base/7">
                            {offering.description?.trim() ||
                                'No description has been provided for this offering yet.'}
                        </p>
                    </div>
                </CardContent>
            </Card>

            {showBookingTerms && (
                <Card className="gap-0 overflow-hidden py-0 shadow-sm">
                    <CardHeader className="border-b bg-muted/30 px-6 py-5 sm:px-8">
                        <CardTitle>Booking terms</CardTitle>
                        <CardDescription>
                            Deadlines and timing for this reservation.
                        </CardDescription>
                    </CardHeader>

                    <CardContent className="p-6 sm:p-8">
                        <dl className="grid gap-4 md:grid-cols-3">
                            {bookingTerms.map((term) => {
                                const Icon = term.icon;

                                return (
                                    <div
                                        key={term.label}
                                        className="rounded-xl border bg-background p-4"
                                    >
                                        <dt className="flex items-center gap-2 text-sm font-medium text-muted-foreground">
                                            <Icon
                                                aria-hidden="true"
                                                className="size-4 text-sky-600 dark:text-sky-400"
                                            />
                                            {term.label}
                                        </dt>
                                        <dd className="mt-3 text-sm font-semibold text-foreground">
                                            {term.value}
                                        </dd>
                                        <p className="mt-2 text-xs/5 text-muted-foreground">
                                            {term.description}
                                        </p>
                                    </div>
                                );
                            })}
                        </dl>
                    </CardContent>
                </Card>
            )}
        </div>
    );
}
