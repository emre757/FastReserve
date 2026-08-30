import { BanknotesIcon } from '@heroicons/react/24/outline';
import { Head } from '@inertiajs/react';
import { useState } from 'react';
import PendingInvitationsModal from '@/components/pending-invitations-modal';
import { dashboard } from '@/routes';
import { create } from '@/routes/offerings';
import type { DashboardInvitation } from '@/types';


type Props = {
    pendingInvitations?: DashboardInvitation[];
};

const actions = [
    {
        title: 'Create Offering',
        href: create().url,
        icon: BanknotesIcon,
        iconForeground: 'text-yellow-700 dark:text-yellow-400',
        iconBackground: 'bg-yellow-50 dark:bg-yellow-500/10',
    },
];

function classNames(...classes: string[]) {
    return classes.filter(Boolean).join(' ');
}

export default function Dashboard({ pendingInvitations = [] }: Props) {
    const [showInvitations, setShowInvitations] = useState(
        pendingInvitations.length > 0,
    );

    return (
        <>
            <Head title="Dashboard" />
            <PendingInvitationsModal
                invitations={pendingInvitations}
                open={pendingInvitations.length > 0 && showInvitations}
                onOpenChange={setShowInvitations}
            />

            {/*TODO: show actions only related to user's permissions and fix design*/}
            <div className="divide-y divide-gray-200 overflow-hidden rounded-lg bg-gray-200 shadow-sm sm:grid sm:grid-cols-2 sm:divide-y-0 dark:divide-white/10 dark:bg-gray-900 dark:shadow-none dark:outline dark:-outline-offset-1 dark:outline-white/20">
                {actions.map((action, actionIdx) => (
                    <div
                        key={action.title}
                        className={classNames(
                            actionIdx === 0
                                ? 'rounded-tl-lg rounded-tr-lg sm:rounded-tr-none'
                                : '',
                            actionIdx === 1 ? 'sm:rounded-tr-lg' : '',
                            actionIdx === actions.length - 2
                                ? 'sm:rounded-bl-lg'
                                : '',
                            actionIdx === actions.length - 1
                                ? 'rounded-br-lg rounded-bl-lg sm:rounded-bl-none'
                                : '',
                            'group relative border-gray-200 bg-white p-6 focus-within:outline-2 focus-within:-outline-offset-2 focus-within:outline-indigo-600 sm:odd:not-nth-last-2:border-b sm:even:border-l sm:even:not-last:border-b dark:border-white/10 dark:bg-gray-800/50 dark:focus-within:outline-indigo-500',
                        )}
                    >
                        <div>
                            <span
                                className={classNames(
                                    action.iconBackground,
                                    action.iconForeground,
                                    'inline-flex rounded-lg p-3',
                                )}
                            >
                                <action.icon
                                    aria-hidden="true"
                                    className="size-6"
                                />
                            </span>
                        </div>
                        <div className="mt-8">
                            <h3 className="text-base font-semibold text-gray-900 dark:text-white">
                                <a
                                    href={action.href}
                                    className="focus:outline-hidden"
                                >
                                    {/* Extend touch target to entire panel */}
                                    <span
                                        aria-hidden="true"
                                        className="absolute inset-0"
                                    />
                                    {action.title}
                                </a>
                            </h3>
                            <p className="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                Doloribus dolores nostrum quia qui natus officia
                                quod et dolorem. Sit repellendus qui ut at
                                blanditiis et quo et molestiae.
                            </p>
                        </div>
                        <span
                            aria-hidden="true"
                            className="pointer-events-none absolute top-6 right-6 text-gray-300 group-hover:text-gray-400 dark:text-gray-500 dark:group-hover:text-gray-200"
                        >
                            <svg
                                fill="currentColor"
                                viewBox="0 0 24 24"
                                className="size-6"
                            >
                                <path d="M20 4h1a1 1 0 00-1-1v1zm-1 12a1 1 0 102 0h-2zM8 3a1 1 0 000 2V3zM3.293 19.293a1 1 0 101.414 1.414l-1.414-1.414zM19 4v12h2V4h-2zm1-1H8v2h12V3zm-.707.293l-16 16 1.414 1.414 16-16-1.414-1.414z" />
                            </svg>
                        </span>
                    </div>
                ))}
            </div>
        </>
    );
}

Dashboard.layout = () => ({
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: dashboard(),
        },
    ],
});
