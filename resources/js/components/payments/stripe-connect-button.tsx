import { Form } from '@inertiajs/react';
import {
    ArrowUpRight,
    Check,
    CircleAlert,
    Clock3,
    Link2,
    Unlink,
} from 'lucide-react';
import { useId, useState } from 'react';
import {
    AlertDialog,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogMedia,
    AlertDialogTitle,
    AlertDialogTrigger,
} from '@/components/ui/alert-dialog';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { cn } from '@/lib/utils';
import { destroy, store } from '@/routes/companies/payment-account';

export type StripeConnectionStatus = 'pending' | 'active' | 'closed';

type StripeConnectButtonProps = {
    team: string | { slug: string };
    status?: StripeConnectionStatus | null;
    className?: string;
};

const statusDetails = {
    disconnected: {
        label: 'Connect with Stripe',
        description: 'Connect securely to start receiving payments.',
        icon: Link2,
        indicatorClassName: 'bg-muted-foreground/60',
    },
    pending: {
        label: 'Continue Stripe setup',
        description: 'Your account setup is waiting to be completed.',
        icon: Clock3,
        indicatorClassName: 'bg-amber-500',
    },
    active: {
        label: 'Stripe connected',
        description: 'Your account is ready to receive payments.',
        icon: Check,
        indicatorClassName: 'bg-emerald-500',
    },
    closed: {
        label: 'Stripe account closed',
        description: 'This account can no longer receive payments.',
        icon: CircleAlert,
        indicatorClassName: 'bg-destructive',
    },
} as const;

export default function StripeConnectButton({
    team,
    status = null,
    className,
}: StripeConnectButtonProps) {
    const statusId = useId();
    const [disconnectDialogOpen, setDisconnectDialogOpen] = useState(false);
    const currentStatus = status ?? 'disconnected';
    const details = statusDetails[currentStatus];
    const StatusIcon = details.icon;
    const canConnect =
        currentStatus === 'disconnected' || currentStatus === 'pending';
    const canDisconnect = currentStatus === 'active';

    const button = (processing = false) => (
        <Button
            type={canConnect ? 'submit' : 'button'}
            disabled={!canConnect || processing}
            aria-describedby={statusId}
            className={cn(
                'h-11 min-w-56 justify-between rounded-lg px-3.5 shadow-sm transition-all',
                !canConnect && 'cursor-default disabled:opacity-100',
                canConnect &&
                    'bg-[#635bff] text-white hover:bg-[#5851e5] focus-visible:ring-[#635bff]/30 dark:bg-[#7a73ff] dark:hover:bg-[#6962ef]',
                currentStatus === 'active' &&
                    'border border-emerald-600/25 bg-emerald-500/10 text-emerald-700 opacity-100 dark:border-emerald-400/25 dark:text-emerald-300',
                currentStatus === 'closed' &&
                    'border border-destructive/25 bg-destructive/10 text-destructive opacity-100',
            )}
        >
            <span className="flex items-center gap-2.5">
                <span
                    className={cn(
                        'grid size-7 place-items-center rounded-md',
                        canConnect ? 'bg-white/15' : 'bg-background/70',
                    )}
                >
                    {processing ? (
                        <Spinner className="size-4" />
                    ) : (
                        <StatusIcon aria-hidden="true" className="size-4" />
                    )}
                </span>

                <span>{processing ? 'Opening Stripe…' : details.label}</span>
            </span>

            {canConnect && !processing && (
                <ArrowUpRight
                    aria-hidden="true"
                    className="size-4 opacity-80"
                />
            )}
        </Button>
    );

    return (
        <div
            className={cn('inline-flex flex-col items-start gap-2', className)}
        >
            <div className="flex flex-wrap items-center gap-2">
                {canConnect ? (
                    <Form {...store.form(team)}>
                        {({ processing }) => button(processing)}
                    </Form>
                ) : (
                    button()
                )}

                {canDisconnect && (
                    <AlertDialog
                        open={disconnectDialogOpen}
                        onOpenChange={setDisconnectDialogOpen}
                    >
                        <AlertDialogTrigger asChild>
                            <Button
                                type="button"
                                variant="outline"
                                className="h-11 gap-2 border-destructive/30 text-destructive hover:border-destructive/50 hover:bg-destructive/10 hover:text-destructive dark:border-destructive/40"
                            >
                                <Unlink aria-hidden="true" className="size-4" />
                                Disconnect
                            </Button>
                        </AlertDialogTrigger>

                        <AlertDialogContent size="sm">
                            <Form
                                {...destroy.form(team)}
                                onSuccess={() => setDisconnectDialogOpen(false)}
                            >
                                {({ processing }) => (
                                    <div className="space-y-6">
                                        <AlertDialogHeader>
                                            <AlertDialogMedia className="bg-destructive/10 text-destructive dark:bg-destructive/20 dark:text-destructive">
                                                <Unlink aria-hidden="true" />
                                            </AlertDialogMedia>
                                            <AlertDialogTitle>
                                                Disconnect Stripe?
                                            </AlertDialogTitle>
                                            <AlertDialogDescription>
                                                This company will stop receiving
                                                payments through this Stripe
                                                account. You will need to
                                                connect and complete setup for a
                                                new account before accepting
                                                payments again.
                                            </AlertDialogDescription>
                                        </AlertDialogHeader>

                                        <AlertDialogFooter>
                                            <AlertDialogCancel
                                                type="button"
                                                disabled={processing}
                                            >
                                                Keep connected
                                            </AlertDialogCancel>
                                            <Button
                                                type="submit"
                                                variant="destructive"
                                                disabled={processing}
                                            >
                                                {processing && (
                                                    <Spinner className="size-4" />
                                                )}
                                                {processing
                                                    ? 'Disconnecting…'
                                                    : 'Disconnect Stripe'}
                                            </Button>
                                        </AlertDialogFooter>
                                    </div>
                                )}
                            </Form>
                        </AlertDialogContent>
                    </AlertDialog>
                )}
            </div>

            <p
                id={statusId}
                role="status"
                aria-live="polite"
                className="flex items-center gap-2 text-xs text-muted-foreground"
            >
                <span className="relative flex size-2" aria-hidden="true">
                    {currentStatus === 'pending' && (
                        <span className="absolute inline-flex size-full animate-ping rounded-full bg-amber-400 opacity-60" />
                    )}
                    <span
                        className={cn(
                            'relative inline-flex size-2 rounded-full',
                            details.indicatorClassName,
                        )}
                    />
                </span>
                {details.description}
            </p>
        </div>
    );
}
