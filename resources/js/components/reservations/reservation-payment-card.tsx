import {
    CheckCircle2,
    CreditCard,
    LockKeyhole,
    ShieldCheck,
    TimerOff,
} from 'lucide-react';
import { useEffect, useState } from 'react';
import { formatPrice } from '@/components/reservations/format-price';
import type { ReservationShowProps } from '@/components/reservations/reservation-show-types';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';

type Props = Pick<
    ReservationShowProps,
    'reservation' | 'offering' | 'serverTime'
>;

function initialRemainingSeconds(
    expiresAt: string | null,
    serverTime: string,
): number {
    if (expiresAt === null) {
        return 0;
    }

    return Math.max(
        0,
        Math.floor(
            (new Date(expiresAt).getTime() - new Date(serverTime).getTime()) /
                1000,
        ),
    );
}

function splitTime(secondsRemaining: number) {
    return {
        hours: Math.floor(secondsRemaining / 3600),
        minutes: Math.floor((secondsRemaining % 3600) / 60),
        seconds: secondsRemaining % 60,
    };
}

function TimeUnit({ value, label }: { value: number; label: string }) {
    return (
        <div className="rounded-xl bg-white/10 px-3 py-3 text-center ring-1 ring-white/15 ring-inset">
            <span className="block font-mono text-2xl font-bold tracking-tight tabular-nums">
                {String(value).padStart(2, '0')}
            </span>
            <span className="mt-1 block text-[0.65rem] font-semibold tracking-wider text-amber-100 uppercase">
                {label}
            </span>
        </div>
    );
}

export default function ReservationPaymentCard({
    reservation,
    offering,
    serverTime,
}: Props) {
    const startingSeconds = initialRemainingSeconds(
        reservation.expired_at,
        serverTime,
    );
    const [remainingSeconds, setRemainingSeconds] = useState(startingSeconds);
    const isPending = reservation.status === 'pending';

    useEffect(() => {
        if (!isPending || startingSeconds === 0) {
            return;
        }

        const timerStartedAt = Date.now();
        const interval = window.setInterval(() => {
            const elapsedSeconds = Math.floor(
                (Date.now() - timerStartedAt) / 1000,
            );
            const nextRemainingSeconds = Math.max(
                0,
                startingSeconds - elapsedSeconds,
            );

            if (nextRemainingSeconds === 0) {
                window.clearInterval(interval);
            }

            setRemainingSeconds((currentRemainingSeconds) =>
                currentRemainingSeconds === nextRemainingSeconds
                    ? currentRemainingSeconds
                    : nextRemainingSeconds,
            );
        }, 1000);

        return () => window.clearInterval(interval);
    }, [isPending, startingSeconds]);

    const paymentWindowOpen = isPending && remainingSeconds > 0;
    const time = splitTime(remainingSeconds);
    const amountDue = Number(reservation.amount_due);

    return (
        <div className="space-y-5 lg:sticky lg:top-6">
            <Card className="gap-0 overflow-hidden py-0 shadow-md">
                {isPending ? (
                    <div
                        className={
                            paymentWindowOpen
                                ? 'bg-linear-to-br from-amber-500 to-orange-600 px-6 py-6 text-white'
                                : 'bg-linear-to-br from-red-600 to-rose-700 px-6 py-6 text-white'
                        }
                    >
                        <div className="flex items-center gap-2 text-sm font-semibold">
                            {paymentWindowOpen ? (
                                <LockKeyhole
                                    aria-hidden="true"
                                    className="size-4"
                                />
                            ) : (
                                <TimerOff
                                    aria-hidden="true"
                                    className="size-4"
                                />
                            )}
                            {paymentWindowOpen
                                ? 'Your spots are temporarily held'
                                : 'Payment window expired'}
                        </div>

                        {paymentWindowOpen ? (
                            <>
                                <p className="mt-2 text-sm/6 text-amber-50">
                                    Complete payment before the timer reaches
                                    zero.
                                </p>
                                <div
                                    className="mt-5 grid grid-cols-3 gap-2"
                                    role="timer"
                                    aria-label={`${time.hours} hours, ${time.minutes} minutes, and ${time.seconds} seconds remaining`}
                                >
                                    <TimeUnit
                                        value={time.hours}
                                        label="Hours"
                                    />
                                    <TimeUnit
                                        value={time.minutes}
                                        label="Minutes"
                                    />
                                    <TimeUnit
                                        value={time.seconds}
                                        label="Seconds"
                                    />
                                </div>
                            </>
                        ) : (
                            <p className="mt-2 text-sm/6 text-red-50">
                                These spots are no longer being held for this
                                reservation.
                            </p>
                        )}
                    </div>
                ) : reservation.status === 'confirmed' ? (
                    <div className="bg-linear-to-br from-emerald-600 to-teal-700 px-6 py-6 text-white">
                        <CheckCircle2 aria-hidden="true" className="size-8" />
                        <p className="mt-3 font-semibold">Payment complete</p>
                        <p className="mt-1 text-sm/6 text-emerald-50">
                            Your reservation is confirmed.
                        </p>
                    </div>
                ) : (
                    <div
                        className={
                            reservation.status === 'expired'
                                ? 'bg-linear-to-br from-red-600 to-rose-700 px-6 py-6 text-white'
                                : 'bg-linear-to-br from-gray-600 to-slate-700 px-6 py-6 text-white'
                        }
                    >
                        <TimerOff aria-hidden="true" className="size-8" />
                        <p className="mt-3 font-semibold">
                            {reservation.status === 'expired'
                                ? 'Payment window expired'
                                : 'Reservation cancelled'}
                        </p>
                        <p className="mt-1 text-sm/6 text-white/80">
                            Payment is no longer available for this reservation.
                        </p>
                    </div>
                )}

                <CardHeader className="border-b px-6 py-5">
                    <CardTitle>
                        {reservation.status === 'confirmed'
                            ? 'Payment summary'
                            : reservation.status === 'pending'
                              ? 'Complete payment'
                              : 'Payment unavailable'}
                    </CardTitle>
                    <CardDescription>
                        {reservation.quantity}{' '}
                        {reservation.quantity === 1 ? 'spot' : 'spots'} for{' '}
                        {offering.name}
                    </CardDescription>
                </CardHeader>

                <CardContent className="space-y-5 p-6">
                    <div className="flex items-end justify-between gap-4">
                        <span className="text-sm text-muted-foreground">
                            {reservation.status === 'confirmed'
                                ? 'Total paid'
                                : 'Total due'}
                        </span>
                        <span className="text-3xl font-bold tracking-tight text-foreground">
                            {formatPrice(amountDue, offering.currency)}
                        </span>
                    </div>

                    <div className="border-t pt-5">
                        <Button
                            type="button"
                            className="w-full"
                            size="lg"
                            disabled={!paymentWindowOpen}
                        >
                            <CreditCard aria-hidden="true" className="size-4" />
                            {paymentWindowOpen
                                ? 'Continue to payment'
                                : reservation.status === 'confirmed'
                                  ? 'Payment complete'
                                  : 'Payment unavailable'}
                        </Button>
                        {paymentWindowOpen && (
                            <p className="mt-3 flex items-center justify-center gap-1.5 text-center text-xs/5 text-muted-foreground">
                                <ShieldCheck
                                    aria-hidden="true"
                                    className="size-3.5"
                                />
                                Secure payment powered by Stripe
                            </p>
                        )}
                    </div>
                </CardContent>
            </Card>

            {paymentWindowOpen && (
                <div className="rounded-xl border bg-muted/40 p-4">
                    <p className="text-sm font-medium text-foreground">
                        What happens next?
                    </p>
                    <p className="mt-1 text-sm/6 text-muted-foreground">
                        After payment succeeds, your reservation will be
                        confirmed and receive its reference.
                    </p>
                </div>
            )}
        </div>
    );
}
