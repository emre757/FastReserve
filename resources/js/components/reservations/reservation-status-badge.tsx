import { CheckCircle2, CircleX, Clock3, TimerOff } from 'lucide-react';
import type { ReservationStatus } from '@/components/reservations/reservation-show-types';

type Props = {
    status: ReservationStatus;
    isFree?: boolean;
};

const statuses = {
    pending: {
        label: 'Awaiting payment',
        icon: Clock3,
        className:
            'bg-amber-100 text-amber-800 ring-amber-600/20 dark:bg-amber-950 dark:text-amber-300 dark:ring-amber-400/20',
    },
    confirmed: {
        label: 'Confirmed',
        icon: CheckCircle2,
        className:
            'bg-emerald-100 text-emerald-800 ring-emerald-600/20 dark:bg-emerald-950 dark:text-emerald-300 dark:ring-emerald-400/20',
    },
    expired: {
        label: 'Expired',
        icon: TimerOff,
        className:
            'bg-red-100 text-red-800 ring-red-600/20 dark:bg-red-950 dark:text-red-300 dark:ring-red-400/20',
    },
    cancelled: {
        label: 'Cancelled',
        icon: CircleX,
        className:
            'bg-gray-100 text-gray-700 ring-gray-600/20 dark:bg-gray-800 dark:text-gray-300 dark:ring-gray-400/20',
    },
} satisfies Record<
    ReservationStatus,
    {
        label: string;
        icon: typeof Clock3;
        className: string;
    }
>;

export default function ReservationStatusBadge({
    status,
    isFree = false,
}: Props) {
    const statusDetails = statuses[status];
    const Icon = statusDetails.icon;

    return (
        <span
            className={`inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold ring-1 ring-inset ${statusDetails.className}`}
        >
            <Icon aria-hidden="true" className="size-3.5" />
            {status === 'pending' && isFree
                ? 'Awaiting confirmation'
                : statusDetails.label}
        </span>
    );
}
