import { Form, Link } from '@inertiajs/react';
import {
    CheckCircle2,
    CircleX,
    CreditCard,
    LockKeyhole,
    ShieldCheck,
    TimerOff,
} from 'lucide-react';
import { useState } from 'react';
import { store } from '@/actions/App/Http/Controllers/Payments/ReservationPaymentController';
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
import { Spinner } from '@/components/ui/spinner';
import { cancel } from '@/routes/reservations';

type Props = Pick<
    ReservationShowProps,
    'reservation' | 'offering' | 'paymentMethods'
> & { remainingSeconds: number };

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
            <span className="mt-1 block text-[0.65rem] font-semibold tracking-wider text-white/80 uppercase">
                {label}
            </span>
        </div>
    );
}

export default function ReservationPaymentCard({
    reservation,
    offering,
    remainingSeconds,
    paymentMethods = [],
}: Props) {
    const isPending = reservation.status === 'pending';

    const paymentWindowOpen = isPending && remainingSeconds > 0;
    const time = splitTime(remainingSeconds);
    const amountDue = Number(reservation.amount_due);
    const isFree = amountDue === 0;
    const hasStripe = paymentMethods.includes('stripe');
    const canProceed = paymentWindowOpen && (isFree || hasStripe);
    const isConfirmed = reservation.status === 'confirmed';
    const isCancelled = reservation.status === 'cancelled';
    const unavailableLabel = isCancelled
        ? 'Reservation cancelled'
        : 'Reservation expired';

    const [processing, setProcessing] = useState(false);

    const buttonLabel = isConfirmed
        ? 'Reservation confirmed'
        : !paymentWindowOpen
          ? unavailableLabel
          : processing
            ? 'Opening checkout…'
            : isFree
              ? 'Confirm reservation'
              : hasStripe
                ? 'Pay with Stripe'
                : 'Payment unavailable';

    return (
        <div className="space-y-5 lg:sticky lg:top-6">
            <Card className="gap-0 overflow-hidden py-0 shadow-md">
                {isPending ? (
                    <div
                        className={
                            paymentWindowOpen
                                ? isFree
                                    ? 'bg-linear-to-br from-emerald-600 to-teal-700 px-6 py-6 text-white'
                                    : 'bg-linear-to-br from-amber-500 to-orange-600 px-6 py-6 text-white'
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
                                : 'Reservation hold expired'}
                        </div>

                        {paymentWindowOpen ? (
                            <>
                                <p className="mt-2 text-sm/6 text-white/90">
                                    {isFree
                                        ? 'Confirm your free reservation before the timer reaches zero. No payment is needed.'
                                        : 'Complete payment before the timer reaches zero.'}
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
                        <p className="mt-3 font-semibold">
                            {isFree
                                ? 'Reservation confirmed'
                                : 'Payment complete'}
                        </p>
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
                        {isCancelled ? (
                            <CircleX aria-hidden="true" className="size-8" />
                        ) : (
                            <TimerOff aria-hidden="true" className="size-8" />
                        )}
                        <p className="mt-3 font-semibold">{unavailableLabel}</p>
                        <p className="mt-1 text-sm/6 text-white/80">
                            {isCancelled
                                ? 'This reservation was cancelled. Payment and confirmation are no longer available.'
                                : 'The reservation hold ended. Your spots are no longer reserved.'}
                        </p>
                    </div>
                )}

                <CardHeader className="border-b px-6 py-5">
                    <CardTitle>
                        {isConfirmed
                            ? isFree
                                ? 'Reservation summary'
                                : 'Payment summary'
                            : !paymentWindowOpen
                              ? unavailableLabel
                              : isFree
                                ? 'Confirm your reservation'
                                : hasStripe
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
                            {isFree
                                ? 'Reservation total'
                                : reservation.status === 'confirmed'
                                  ? 'Total paid'
                                  : 'Total due'}
                        </span>
                        <span className="text-3xl font-bold tracking-tight text-foreground">
                            {isFree
                                ? 'Free'
                                : formatPrice(amountDue, offering.currency)}
                        </span>
                    </div>

                    <div className="border-t pt-5">
                        <Button
                            type="button"
                            className={
                                isFree
                                    ? 'w-full bg-emerald-600 text-white hover:bg-emerald-700 dark:bg-emerald-500 dark:text-white dark:hover:bg-emerald-600'
                                    : canProceed
                                      ? 'w-full bg-violet-600 text-white hover:bg-violet-700 focus-visible:ring-violet-500/50 dark:bg-violet-500 dark:hover:bg-violet-600'
                                      : 'w-full'
                            }
                            size="lg"
                            disabled={!canProceed || processing}
                            asChild
                        >
                            <Link
                                href={store(reservation.id)}
                                as="button"
                                onStart={() => setProcessing(true)}
                                onFinish={() => setProcessing(false)}
                            >
                                {isCancelled ? (
                                    <CircleX
                                        aria-hidden="true"
                                        className="size-4"
                                    />
                                ) : !paymentWindowOpen && !isConfirmed ? (
                                    <TimerOff
                                        aria-hidden="true"
                                        className="size-4"
                                    />
                                ) : isFree ? (
                                    <CheckCircle2
                                        aria-hidden="true"
                                        className="size-4"
                                    />
                                ) : processing ? (
                                    <Spinner className={'animate-spin'} />
                                ) : (
                                    <CreditCard
                                        aria-hidden="true"
                                        className="size-4"
                                    />
                                )}
                                {buttonLabel}
                            </Link>
                        </Button>
                        {canProceed && (
                            <p className="mt-3 flex items-center justify-center gap-1.5 text-center text-xs/5 text-muted-foreground">
                                <ShieldCheck
                                    aria-hidden="true"
                                    className="size-3.5"
                                />
                                {isFree
                                    ? 'No payment details required'
                                    : 'Secure payment powered by Stripe'}
                            </p>
                        )}
                        {paymentWindowOpen && !isFree && !hasStripe && (
                            <p className="mt-3 text-center text-xs/5 text-muted-foreground">
                                Online payment is currently unavailable for this
                                offering. Please contact the organizer.
                            </p>
                        )}
                        {isPending && (
                            <Form
                                action={cancel(reservation.id)}
                                options={{ preserveScroll: true }}
                                className="mt-4"
                            >
                                {({ processing, errors }) => (
                                    <>
                                        <Button
                                            type="submit"
                                            variant="outline"
                                            className="w-full border-red-200 text-red-700 hover:bg-red-50 hover:text-red-800 dark:border-red-900 dark:text-red-400 dark:hover:bg-red-950/40 dark:hover:text-red-300"
                                            disabled={processing}
                                        >
                                            {processing ? (
                                                <Spinner className="size-4" />
                                            ) : (
                                                <CircleX
                                                    aria-hidden="true"
                                                    className="size-4"
                                                />
                                            )}
                                            {processing
                                                ? 'Cancelling…'
                                                : 'Cancel reservation'}
                                        </Button>
                                        {Object.keys(errors).length > 0 && (
                                            <p
                                                role="alert"
                                                className="mt-2 text-sm text-destructive"
                                            >
                                                {Object.values(errors).join(
                                                    ' ',
                                                )}
                                            </p>
                                        )}
                                    </>
                                )}
                            </Form>
                        )}
                    </div>
                </CardContent>
            </Card>

            {canProceed && (
                <div className="rounded-xl border bg-muted/40 p-4">
                    <p className="text-sm font-medium text-foreground">
                        What happens next?
                    </p>
                    <p className="mt-1 text-sm/6 text-muted-foreground">
                        {isFree
                            ? 'Confirm your reservation to secure your spots and receive your reference.'
                            : 'After payment succeeds, your reservation will be confirmed and receive its reference.'}
                    </p>
                </div>
            )}
        </div>
    );
}
