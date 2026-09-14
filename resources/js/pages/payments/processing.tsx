import { Head, router, usePage } from '@inertiajs/react';
import { useEchoNotification } from '@laravel/echo-react';
import { Check, Clock3, LockKeyhole, ShieldCheck, WifiOff } from 'lucide-react';
import { useEffect } from 'react';
import type { ReservationStatus } from '@/components/reservations/reservation-show-types';
import { Card, CardContent } from '@/components/ui/card';
import { Spinner } from '@/components/ui/spinner';
import { useEchoConnectionStatus } from '@/hooks/use-echo-connection-status';
import { show } from '@/routes/reservations';

type ReservationConfirmedPayload = {
    reservation_id: number;
};

type Props = {
    reservation: {
        id: number;
        status: ReservationStatus;
    };
    offering: {
        name: string;
    };
    company: {
        name: string;
    };
};

export default function Processing({ reservation, offering, company }: Props) {
    useEffect(() => {
        if (reservation.status !== 'pending') {
            router.visit(show(reservation.id), { replace: true });
        }
    }, [reservation.id, reservation.status]);

    const connectionStatus = useEchoConnectionStatus();
    const isConnected = connectionStatus === 'connected';
    const isConnecting =
        connectionStatus === 'connecting' ||
        connectionStatus === 'reconnecting';
    const isConnectionUnavailable = !isConnected && !isConnecting;
    const connectionLabel =
        connectionStatus === 'connecting'
            ? 'Connecting to live updates…'
            : connectionStatus === 'reconnecting'
              ? 'Reconnecting to live updates…'
              : 'Live updates are unavailable';

    const { auth } = usePage().props;

    useEchoNotification<ReservationConfirmedPayload>(
        `App.Models.User.${auth.user.id}`,
        (notification) => {
            if (notification.reservation_id !== reservation.id) {
                return;
            }

            router.visit(show(reservation.id), { replace: true });
        },
        'App\\Notifications\\Reservations\\ReservationConfirmed',
        [reservation.id],
    );

    return (
        <>
            <Head title="Confirming payment" />

            <main className="relative flex min-h-[calc(100vh-8rem)] items-center justify-center overflow-hidden px-4 py-10 sm:px-6">
                <div
                    aria-hidden="true"
                    className="absolute top-1/4 left-1/2 size-80 -translate-x-1/2 rounded-full bg-sky-400/10 blur-3xl dark:bg-sky-500/10"
                />
                <div
                    aria-hidden="true"
                    className="absolute right-0 bottom-0 size-64 rounded-full bg-indigo-400/10 blur-3xl dark:bg-indigo-500/10"
                />

                <Card className="relative w-full max-w-2xl gap-0 overflow-hidden py-0 shadow-xl">
                    <div className="relative overflow-hidden bg-linear-to-br from-sky-600 via-blue-600 to-indigo-700 px-6 py-10 text-center text-white sm:px-10 sm:py-12">
                        <div
                            aria-hidden="true"
                            className="absolute -top-20 -right-16 size-56 rounded-full bg-white/10 blur-2xl"
                        />
                        <div
                            aria-hidden="true"
                            className="absolute -bottom-24 -left-16 size-64 rounded-full bg-sky-300/15 blur-3xl"
                        />

                        <div className="relative flex flex-col items-center">
                            <div className="relative grid size-20 place-items-center">
                                {!isConnectionUnavailable && (
                                    <span className="absolute inset-0 animate-ping rounded-full bg-white/15" />
                                )}
                                <span className="absolute inset-1 rounded-full bg-white/10 ring-1 ring-white/20" />
                                <span className="relative grid size-14 place-items-center rounded-full bg-white text-blue-600 shadow-lg">
                                    {isConnectionUnavailable ? (
                                        <WifiOff
                                            aria-hidden="true"
                                            className="size-7"
                                        />
                                    ) : (
                                        <Spinner className="size-7" />
                                    )}
                                </span>
                            </div>

                            <div className="mt-7 inline-flex items-center gap-2 rounded-full bg-white/10 px-3 py-1.5 text-xs font-semibold text-sky-50 ring-1 ring-white/20 ring-inset">
                                <LockKeyhole
                                    aria-hidden="true"
                                    className="size-3.5"
                                />
                                {isConnectionUnavailable
                                    ? 'Live updates unavailable'
                                    : 'Secure confirmation in progress'}
                            </div>

                            <h1 className="mt-5 text-3xl font-bold tracking-tight text-balance sm:text-4xl">
                                {isConnectionUnavailable
                                    ? 'Payment updates are unavailable'
                                    : 'We’re confirming your payment'}
                            </h1>
                            <p className="mt-4 max-w-lg text-sm/6 text-sky-100 sm:text-base/7">
                                {isConnectionUnavailable
                                    ? 'We can’t receive live updates right now. Your payment may still be processing.'
                                    : 'Stripe is sending us the final payment result. This usually takes only a few seconds.'}
                            </p>
                        </div>
                    </div>

                    <CardContent className="space-y-7 p-6 sm:p-8">
                        <div
                            role="status"
                            aria-live="polite"
                            aria-atomic="true"
                        >
                            {!isConnected && (
                                <div className="flex items-start gap-3 rounded-xl border border-amber-200 bg-amber-50 p-4 text-amber-950 dark:border-amber-900 dark:bg-amber-950/30 dark:text-amber-100">
                                    {isConnecting ? (
                                        <Spinner
                                            aria-hidden="true"
                                            className="mt-0.5 size-4 shrink-0"
                                        />
                                    ) : (
                                        <WifiOff
                                            aria-hidden="true"
                                            className="mt-0.5 size-4 shrink-0"
                                        />
                                    )}
                                    <div>
                                        <p className="text-sm font-semibold">
                                            {connectionLabel}
                                        </p>
                                        <p className="mt-1 text-xs/5 text-amber-800 dark:text-amber-200">
                                            {isConnecting
                                                ? 'Establishing a connection to receive your payment result.'
                                                : 'Check your connection and refresh this page if updates do not resume.'}{' '}
                                            This does not mean your payment
                                            failed. Please don’t pay again.
                                        </p>
                                    </div>
                                </div>
                            )}
                            {isConnected && (
                                <span className="sr-only">
                                    Live connection restored.
                                </span>
                            )}
                        </div>
                        <div className="min-w-0 rounded-xl border bg-muted/30 p-4">
                            <div className="flex items-center gap-2 text-xs font-medium tracking-wide text-muted-foreground uppercase">
                                <ShieldCheck
                                    aria-hidden="true"
                                    className="size-4"
                                />
                                Booking
                            </div>
                            <p className="mt-2 truncate font-semibold text-foreground">
                                {offering.name}
                            </p>
                            <p className="mt-0.5 truncate text-xs text-muted-foreground">
                                {company.name}
                            </p>
                        </div>

                        <ol className="space-y-1" aria-label="Payment progress">
                            <li className="flex gap-4 rounded-xl p-3">
                                <span className="grid size-9 shrink-0 place-items-center rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300">
                                    <Check
                                        aria-hidden="true"
                                        className="size-4"
                                    />
                                </span>
                                <div className="pt-0.5">
                                    <p className="text-sm font-semibold text-foreground">
                                        Payment submitted
                                    </p>
                                    <p className="mt-0.5 text-xs/5 text-muted-foreground">
                                        Your checkout details were sent
                                        securely.
                                    </p>
                                </div>
                            </li>

                            <li className="flex gap-4 rounded-xl bg-sky-50 p-3 dark:bg-sky-950/30">
                                <span className="grid size-9 shrink-0 place-items-center rounded-full bg-sky-100 text-sky-700 dark:bg-sky-950 dark:text-sky-300">
                                    {isConnectionUnavailable ? (
                                        <WifiOff
                                            aria-hidden="true"
                                            className="size-4"
                                        />
                                    ) : (
                                        <Spinner className="size-4" />
                                    )}
                                </span>
                                <div className="pt-0.5">
                                    <p className="text-sm font-semibold text-sky-950 dark:text-sky-100">
                                        {isConnectionUnavailable
                                            ? 'Waiting for connection'
                                            : 'Verifying with Stripe'}
                                    </p>
                                    <p className="mt-0.5 text-xs/5 text-sky-700 dark:text-sky-300">
                                        {isConnectionUnavailable
                                            ? 'Live payment updates are currently unavailable.'
                                            : 'Waiting for Stripe’s secure confirmation.'}
                                    </p>
                                </div>
                            </li>

                            <li className="flex gap-4 rounded-xl p-3 opacity-60">
                                <span className="grid size-9 shrink-0 place-items-center rounded-full bg-muted text-muted-foreground">
                                    <Clock3
                                        aria-hidden="true"
                                        className="size-4"
                                    />
                                </span>
                                <div className="pt-0.5">
                                    <p className="text-sm font-semibold text-foreground">
                                        Confirming your reservation
                                    </p>
                                    <p className="mt-0.5 text-xs/5 text-muted-foreground">
                                        Your spots will be finalized after
                                        verification.
                                    </p>
                                </div>
                            </li>
                        </ol>

                        <div className="rounded-xl border border-dashed bg-muted/20 px-4 py-3 text-center">
                            <p className="text-xs/5 text-muted-foreground">
                                Please don’t submit another payment while
                                confirmation is in progress.
                            </p>
                        </div>
                    </CardContent>
                </Card>
            </main>
        </>
    );
}
