import { Users } from 'lucide-react';
import { useEchoConnectionStatus } from '@/hooks/use-echo-connection-status';

type Props = {
    availableSpots: number;
};

export default function ReservationAvailability({ availableSpots }: Props) {
    const connectionStatus = useEchoConnectionStatus();
    const isLive = connectionStatus === 'connected';
    const isConnecting =
        connectionStatus === 'connecting' ||
        connectionStatus === 'reconnecting';

    return (
        <div className="relative overflow-hidden rounded-xl border border-amber-200 bg-linear-to-br from-amber-50 to-orange-50 p-5 dark:border-amber-900/80 dark:from-amber-950/40 dark:to-orange-950/30">
            <div
                aria-hidden="true"
                className="absolute -right-8 -bottom-10 size-28 rounded-full bg-amber-300/20 blur-2xl dark:bg-amber-500/10"
            />

            <dt className="relative flex items-center justify-between gap-3">
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
                    className={
                        isLive
                            ? 'inline-flex items-center gap-2 rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300'
                            : isConnecting
                              ? 'inline-flex items-center gap-2 rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-700 dark:bg-amber-950 dark:text-amber-300'
                              : 'inline-flex items-center gap-2 rounded-full bg-red-100 px-2.5 py-1 text-xs font-semibold text-red-700 dark:bg-red-950 dark:text-red-300'
                    }
                >
                    {isLive ? (
                        <span className="relative flex size-2">
                            <span className="absolute inline-flex size-full animate-ping rounded-full bg-emerald-400 opacity-75" />
                            <span className="relative inline-flex size-2 rounded-full bg-emerald-500" />
                        </span>
                    ) : (
                        <span
                            aria-hidden="true"
                            className={
                                isConnecting
                                    ? 'size-2 rounded-full bg-amber-500'
                                    : 'size-2 rounded-full bg-red-500'
                            }
                        />
                    )}
                    {isLive ? 'Live' : isConnecting ? 'Connecting' : 'Not live'}
                </span>
            </dt>
            <dd className="relative mt-4 flex items-baseline gap-2 text-amber-950 dark:text-amber-50">
                <span className="text-4xl font-bold tracking-tight">
                    {availableSpots}
                </span>
                <span className="text-sm font-medium text-amber-700 dark:text-amber-300">
                    spots left
                </span>
            </dd>
            <p className="relative mt-2 text-xs/5 text-amber-700 dark:text-amber-300">
                {isLive
                    ? 'Availability is updating in real time. Spots can go quickly.'
                    : 'Showing the latest known availability while live updates are unavailable.'}
            </p>
        </div>
    );
}
