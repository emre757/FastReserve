import { Link } from '@inertiajs/react';
import {
    Building2,
    CalendarDays,
    Hash,
    MapPinned,
    ReceiptText,
    TicketCheck,
    Users,
} from 'lucide-react';
import { formatPrice } from '@/components/reservations/format-price';
import type { ReservationShowProps } from '@/components/reservations/reservation-show-types';
import ReservationStatusBadge from '@/components/reservations/reservation-status-badge';
import { Card, CardContent } from '@/components/ui/card';
import { index as companyOfferings } from '@/routes/companies/offerings';
import { show as offeringShow } from '@/routes/offerings';

type Props = Pick<ReservationShowProps, 'reservation' | 'offering' | 'company'>;

function formatDateTime(value: string, timezone: string): string {
    return new Intl.DateTimeFormat(undefined, {
        dateStyle: 'medium',
        timeStyle: 'short',
        timeZone: timezone,
    }).format(new Date(value));
}

function statusMessage(status: ReservationShowProps['reservation']['status']) {
    switch (status) {
        case 'confirmed':
            return 'Your payment is complete and your spots are confirmed.';
        case 'expired':
            return 'The payment window for this reservation has ended.';
        case 'cancelled':
            return 'This reservation has been cancelled.';
        default:
            return 'Your spots are held while you complete payment.';
    }
}

export default function ReservationOverviewCard({
    reservation,
    offering,
    company,
}: Props) {
    const pricePerSpot = Number(offering.price);

    return (
        <Card className="gap-0 overflow-hidden py-0 shadow-md">
            <div className="relative overflow-hidden bg-linear-to-br from-sky-600 via-blue-600 to-indigo-700 px-6 py-8 text-white sm:px-8 sm:py-10">
                <div
                    aria-hidden="true"
                    className="absolute -top-24 -right-20 size-60 rounded-full bg-white/10 blur-2xl"
                />
                <div
                    aria-hidden="true"
                    className="absolute -bottom-28 -left-16 size-64 rounded-full bg-sky-300/20 blur-3xl"
                />

                <div className="relative">
                    <div className="flex flex-wrap items-center justify-between gap-3">
                        <span className="text-sm font-medium text-sky-100">
                            Reservation #{reservation.id}
                        </span>
                        <ReservationStatusBadge status={reservation.status} />
                    </div>

                    <div className="mt-8 flex items-start gap-4">
                        <div className="flex size-12 shrink-0 items-center justify-center rounded-xl bg-white/15 ring-1 ring-white/25 ring-inset">
                            <TicketCheck
                                aria-hidden="true"
                                className="size-6"
                            />
                        </div>
                        <div className="min-w-0">
                            <p className="text-sm font-medium text-sky-100">
                                You reserved
                            </p>
                            <Link
                                href={offeringShow(offering.id)}
                                className="mt-1 block text-2xl font-semibold tracking-tight text-balance break-words hover:text-sky-100 hover:underline hover:underline-offset-4 sm:text-3xl"
                            >
                                {offering.name}
                            </Link>
                            <p className="mt-3 text-sm/6 text-sky-100">
                                {statusMessage(reservation.status)}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <CardContent className="space-y-6 p-6 sm:p-8">
                <Link
                    href={companyOfferings({ team: company.slug })}
                    className="flex items-center gap-4 rounded-xl border bg-muted/40 p-4 transition-colors hover:bg-muted"
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

                <dl className="grid gap-3 sm:grid-cols-2">
                    <div className="rounded-xl border p-4">
                        <dt className="flex items-center gap-2 text-sm text-muted-foreground">
                            <Users aria-hidden="true" className="size-4" />
                            Reserved spots
                        </dt>
                        <dd className="mt-2 text-xl font-semibold text-foreground">
                            {reservation.quantity}
                        </dd>
                    </div>

                    <div className="rounded-xl border p-4">
                        <dt className="flex items-center gap-2 text-sm text-muted-foreground">
                            <ReceiptText
                                aria-hidden="true"
                                className="size-4"
                            />
                            Price per spot
                        </dt>
                        <dd className="mt-2 text-xl font-semibold text-foreground">
                            {formatPrice(pricePerSpot, offering.currency)}
                        </dd>
                    </div>

                    <div className="rounded-xl border p-4">
                        <dt className="flex items-center gap-2 text-sm text-muted-foreground">
                            <CalendarDays
                                aria-hidden="true"
                                className="size-4"
                            />
                            Reserved on
                        </dt>
                        <dd className="mt-2 text-sm font-semibold text-foreground">
                            {formatDateTime(
                                reservation.created_at,
                                offering.timezone,
                            )}
                        </dd>
                    </div>

                    <div className="rounded-xl border p-4">
                        <dt className="flex items-center gap-2 text-sm text-muted-foreground">
                            <MapPinned aria-hidden="true" className="size-4" />
                            Offering starts
                        </dt>
                        <dd className="mt-2 text-sm font-semibold text-foreground">
                            {formatDateTime(
                                offering.starts_at,
                                offering.timezone,
                            )}
                        </dd>
                    </div>
                </dl>

                <div className="flex items-start gap-3 rounded-xl border border-dashed p-4">
                    <Hash
                        aria-hidden="true"
                        className="mt-0.5 size-4 shrink-0 text-muted-foreground"
                    />
                    <div>
                        <p className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                            Reference
                        </p>
                        <p className="mt-1 font-mono text-sm font-semibold text-foreground">
                            {reservation.reference ?? 'Assigned after payment'}
                        </p>
                    </div>
                </div>
            </CardContent>
        </Card>
    );
}
