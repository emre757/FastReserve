import { Link } from '@inertiajs/react';
import type { ConnectionStatus } from '@laravel/echo-react';
import {
    ArrowRight,
    CalendarX2,
    Clock3,
    Radio,
    ShieldCheck,
    Ticket,
    Users,
} from 'lucide-react';
import type { OfferingShowData } from '@/components/offerings/offering-show-types';
import { formatPrice } from '@/components/reservations/format-price';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { cn } from '@/lib/utils';
import { create as createReservation } from '@/routes/offerings/reservations';
import { show as showReservation } from '@/routes/reservations';

type Props = {
    offering: OfferingShowData;
    availableSpots: number;
    connectionStatus: ConnectionStatus;
    activeReservationId: number | null;
    canBook: boolean;
};

const statusStyles: Record<string, string> = {
    active: 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900 dark:bg-emerald-950/60 dark:text-emerald-300',
    cancelled: 'border-destructive/25 bg-destructive/10 text-destructive',
    completed:
        'border-slate-200 bg-slate-100 text-slate-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300',
};

export default function OfferingBookingCard({
    offering,
    availableSpots,
    connectionStatus,
    activeReservationId,
    canBook,
}: Props) {
    if (!canBook) {
        const closedMessage =
            offering.status === 'cancelled'
                ? 'This offering has been cancelled and is no longer accepting reservations.'
                : offering.status === 'completed'
                  ? 'This offering has ended and is no longer accepting reservations.'
                  : 'New reservations are no longer available for this offering.';

        return (
            <Card className="order-first gap-0 overflow-hidden border-amber-200 py-0 shadow-md lg:sticky lg:top-6 lg:order-last dark:border-amber-900">
                <CardHeader className="gap-4 bg-linear-to-br from-amber-50 to-orange-50 p-6 dark:from-amber-950/40 dark:to-orange-950/30">
                    <div className="flex size-12 items-center justify-center rounded-2xl bg-white text-amber-700 shadow-sm ring-1 ring-amber-200 dark:bg-amber-950 dark:text-amber-300 dark:ring-amber-800">
                        <CalendarX2 aria-hidden="true" className="size-6" />
                    </div>
                    <div className="space-y-2">
                        <CardTitle className="text-xl leading-snug">
                            Reservations closed
                        </CardTitle>
                        <CardDescription className="text-sm/6">
                            {closedMessage}
                        </CardDescription>
                    </div>
                </CardHeader>
                {activeReservationId !== null && (
                    <CardContent className="space-y-4 border-t border-amber-100 p-6 dark:border-amber-900">
                        <p className="text-sm/6 text-muted-foreground">
                            You have an existing reservation. View it to check
                            its status and available options.
                        </p>
                        <Button
                            asChild
                            size="lg"
                            className="w-full bg-violet-600 text-white hover:bg-violet-700 focus-visible:ring-violet-500/50 dark:bg-violet-500 dark:hover:bg-violet-600"
                        >
                            <Link href={showReservation(activeReservationId)}>
                                View existing reservation
                                <ArrowRight
                                    aria-hidden="true"
                                    className="size-4"
                                />
                            </Link>
                        </Button>
                    </CardContent>
                )}
            </Card>
        );
    }

    if (activeReservationId !== null) {
        return (
            <Card className="order-first gap-0 overflow-hidden border-violet-200 py-0 shadow-md lg:sticky lg:top-6 lg:order-last dark:border-violet-900">
                <CardHeader className="gap-4 border-b border-violet-100 bg-linear-to-br from-violet-50 to-indigo-50 p-6 dark:border-violet-900 dark:from-violet-950/50 dark:to-indigo-950/30">
                    <div className="flex items-center justify-between gap-3">
                        <div className="flex size-12 items-center justify-center rounded-2xl bg-white text-violet-600 shadow-sm ring-1 ring-violet-100 dark:bg-violet-950 dark:text-violet-300 dark:ring-violet-800">
                            <Ticket aria-hidden="true" className="size-6" />
                        </div>
                        <Badge className="gap-1.5 border-amber-200 bg-amber-50 text-amber-800 dark:border-amber-800 dark:bg-amber-950 dark:text-amber-200">
                            <Clock3 aria-hidden="true" className="size-3.5" />
                            Payment pending
                        </Badge>
                    </div>
                    <div className="space-y-2">
                        <CardTitle className="text-xl leading-snug">
                            You already have a reservation
                        </CardTitle>
                        <CardDescription className="text-sm/6">
                            Complete payment for your existing reservation
                            before making another reservation for this offering.
                        </CardDescription>
                    </div>
                </CardHeader>
                <CardContent className="space-y-4 p-6">
                    <p className="text-sm/6 text-muted-foreground">
                        View your reservation to review the details and continue
                        to payment.
                    </p>
                    <Button
                        asChild
                        size="lg"
                        className="w-full bg-violet-600 text-white hover:bg-violet-700 focus-visible:ring-violet-500/50 dark:bg-violet-500 dark:hover:bg-violet-600"
                    >
                        <Link href={showReservation(activeReservationId)}>
                            View existing reservation
                            <ArrowRight aria-hidden="true" className="size-4" />
                        </Link>
                    </Button>
                </CardContent>
            </Card>
        );
    }

    const isLive = connectionStatus === 'connected';
    const isConnecting =
        connectionStatus === 'connecting' ||
        connectionStatus === 'reconnecting';
    const canReserve = offering.status === 'active' && availableSpots > 0;
    const availablePercentage =
        offering.capacity > 0
            ? Math.min(
                  100,
                  Math.max(0, (availableSpots / offering.capacity) * 100),
              )
            : 0;
    const formattedPrice =
        offering.price === null
            ? 'Unavailable'
            : formatPrice(Number(offering.price), offering.currency);

    return (
        <Card className="gap-0 overflow-hidden py-0 shadow-md lg:sticky lg:top-6">
            <div className="relative overflow-hidden bg-linear-to-br from-sky-600 via-blue-600 to-indigo-700 px-6 py-7 text-white">
                <div
                    aria-hidden="true"
                    className="absolute -top-16 -right-12 size-40 rounded-full bg-white/10 blur-2xl"
                />
                <div className="relative flex items-start justify-between gap-4">
                    <div>
                        <p className="text-xs font-medium tracking-wide text-sky-100 uppercase">
                            Price per spot
                        </p>
                        <p className="mt-2 text-3xl font-bold tracking-tight">
                            {formattedPrice}
                        </p>
                    </div>
                    <Badge
                        variant="outline"
                        className={cn(
                            'capitalize',
                            statusStyles[offering.status] ??
                                'border-white/25 bg-white/10 text-white',
                        )}
                    >
                        {offering.status}
                    </Badge>
                </div>
            </div>

            <CardHeader className="border-b px-6 py-5">
                <CardTitle>Reserve your spot</CardTitle>
                <CardDescription>
                    Availability can change while other people are booking.
                </CardDescription>
            </CardHeader>

            <CardContent className="space-y-6 p-6">
                <div className="rounded-xl border border-amber-200 bg-linear-to-br from-amber-50 to-orange-50 p-5 dark:border-amber-900/80 dark:from-amber-950/40 dark:to-orange-950/30">
                    <div className="flex items-center justify-between gap-3">
                        <span className="flex items-center gap-2 text-sm font-medium text-amber-950 dark:text-amber-100">
                            <Users
                                aria-hidden="true"
                                className="size-4 text-amber-600 dark:text-amber-400"
                            />
                            Available now
                        </span>
                        <span
                            role="status"
                            aria-live="polite"
                            className={cn(
                                'inline-flex items-center gap-2 rounded-full px-2.5 py-1 text-xs font-semibold',
                                isLive &&
                                    'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300',
                                isConnecting &&
                                    'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300',
                                !isLive &&
                                    !isConnecting &&
                                    'bg-red-100 text-red-700 dark:bg-red-950 dark:text-red-300',
                            )}
                        >
                            {isLive ? (
                                <span
                                    className="relative flex size-2"
                                    aria-hidden="true"
                                >
                                    <span className="absolute inline-flex size-full animate-ping rounded-full bg-emerald-400 opacity-75" />
                                    <span className="relative inline-flex size-2 rounded-full bg-emerald-500" />
                                </span>
                            ) : (
                                <Radio aria-hidden="true" className="size-3" />
                            )}
                            {isLive
                                ? 'Live'
                                : isConnecting
                                  ? 'Connecting'
                                  : 'Not live'}
                        </span>
                    </div>

                    <div className="mt-4 flex items-baseline gap-2 text-amber-950 dark:text-amber-50">
                        <span className="text-4xl font-bold tracking-tight">
                            {availableSpots}
                        </span>
                        <span className="text-sm font-medium text-amber-700 dark:text-amber-300">
                            {availableSpots === 1 ? 'spot left' : 'spots left'}
                        </span>
                    </div>

                    <div className="mt-4 h-2 overflow-hidden rounded-full bg-amber-200/70 dark:bg-amber-950">
                        <div
                            className="h-full rounded-full bg-linear-to-r from-amber-500 to-orange-500 transition-[width] duration-500"
                            style={{ width: `${availablePercentage}%` }}
                        />
                    </div>
                    <p className="mt-2 text-xs/5 text-amber-700 dark:text-amber-300">
                        {availableSpots} of {offering.capacity} total spots are
                        currently available.
                    </p>
                </div>

                {canReserve ? (
                    <Button asChild size="lg" className="w-full">
                        <Link href={createReservation(offering.id)}>
                            Reserve now
                            <ArrowRight aria-hidden="true" className="size-4" />
                        </Link>
                    </Button>
                ) : (
                    <Button size="lg" className="w-full" disabled>
                        {availableSpots <= 0
                            ? 'No spots available'
                            : 'Reservations unavailable'}
                    </Button>
                )}

                <div className="flex items-start gap-3 rounded-lg bg-muted/50 p-3.5">
                    <ShieldCheck
                        aria-hidden="true"
                        className="mt-0.5 size-4 shrink-0 text-sky-600 dark:text-sky-400"
                    />
                    <p className="text-xs/5 text-muted-foreground">
                        Your selected spots will be held for{' '}
                        {offering.hold_duration_minutes} minutes while you
                        complete payment.
                    </p>
                </div>
            </CardContent>
        </Card>
    );
}
