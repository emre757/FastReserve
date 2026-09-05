import { Link } from '@inertiajs/react';
import { Building2, CheckCircle2, TicketCheck } from 'lucide-react';
import ReservationAvailability from '@/components/reservations/reservation-availability';
import ReservationPrice from '@/components/reservations/reservation-price';
import { Card, CardContent } from '@/components/ui/card';
import { index } from '@/routes/companies/offerings';
import { show } from '@/routes/offerings';

type Props = {
    offering: {
        id: number;
        name: string;
        price: string;
        currency: string | null;
    };
    company: {
        name: string;
        slug: string;
    };
    availableSpots: number;
};

export default function ReservationDetailsCard({
    offering,
    company,
    availableSpots,
}: Props) {
    return (
        <Card className="gap-0 overflow-hidden py-0 shadow-md">
            <div className="relative overflow-hidden bg-linear-to-br from-sky-600 via-blue-600 to-indigo-700 px-6 py-8 text-white sm:px-8 sm:py-10">
                <div
                    aria-hidden="true"
                    className="absolute -top-20 -right-16 size-52 rounded-full bg-white/10 blur-2xl"
                />
                <div
                    aria-hidden="true"
                    className="absolute -bottom-24 -left-12 size-56 rounded-full bg-sky-300/20 blur-3xl"
                />

                <div className="relative">
                    <span className="inline-flex items-center rounded-full bg-white/15 px-3 py-1 text-xs font-medium ring-1 ring-white/25 ring-inset">
                        Reservation details
                    </span>

                    <div className="mt-8 flex items-start gap-4">
                        <div className="flex size-12 shrink-0 items-center justify-center rounded-xl bg-white/15 ring-1 ring-white/25 ring-inset">
                            <TicketCheck
                                aria-hidden="true"
                                className="size-6"
                            />
                        </div>
                        <div className="min-w-0">
                            <p className="text-sm font-medium text-sky-100">
                                Offering
                            </p>
                            <Link
                                href={show(offering.id)}
                                className="mt-1 text-2xl font-semibold tracking-tight text-balance break-words hover:text-sky-400 hover:underline hover:underline-offset-4 sm:text-3xl"
                            >
                                {offering.name}
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <CardContent className="space-y-6 p-6 sm:p-8">
                <Link
                    href={index({ team: company.slug })}
                    className="flex items-center gap-4 rounded-xl border bg-muted/40 p-4 hover:bg-sky-800"
                >
                    <div className="flex size-11 shrink-0 items-center justify-center rounded-lg bg-background shadow-xs ring-1 ring-border">
                        <Building2
                            aria-hidden="true"
                            className="size-5 text-sky-600 dark:text-sky-400"
                        />
                    </div>
                    <div className="min-w-0">
                        <p className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                            Provided by
                        </p>
                        <p className="truncate font-semibold text-foreground">
                            {company.name}
                        </p>
                        <p className="truncate text-sm text-muted-foreground">
                            {company.slug}
                        </p>
                    </div>
                </Link>

                <dl className="grid gap-4 sm:grid-cols-[1.35fr_1fr]">
                    <ReservationAvailability availableSpots={availableSpots} />
                    <ReservationPrice
                        price={offering.price}
                        currency={offering.currency}
                    />
                </dl>

                <div className="flex gap-3 rounded-xl border border-sky-200 bg-sky-50 p-4 text-sky-950 dark:border-sky-900 dark:bg-sky-950/40 dark:text-sky-100">
                    <CheckCircle2
                        aria-hidden="true"
                        className="mt-0.5 size-5 shrink-0 text-sky-600 dark:text-sky-400"
                    />
                    <div>
                        <p className="text-sm font-medium">Final check</p>
                        <p className="mt-1 text-sm/6 text-sky-800 dark:text-sky-200">
                            You are reserving {offering.name} with{' '}
                            {company.name}. Your reservation will be pending
                            while you complete payment.
                        </p>
                    </div>
                </div>
            </CardContent>
        </Card>
    );
}
