import { Link } from '@inertiajs/react';
import { index } from '@/routes/companies/offerings';
import type { TeamSummary } from '@/types';

type Props = {
    team: TeamSummary;
};

export default function CompanyPreviewCard({ team }: Props) {
    return (
        <>
            <Link
                href={index({ team: team.slug })}
                className="overflow-hidden rounded-xl outline outline-gray-200 hover:bg-sky-700 dark:-outline-offset-1 dark:outline-white/10"
            >
                <div className="flex items-center gap-x-4 border-b border-gray-900/5 bg-gray-50 p-6 dark:border-white/10 dark:bg-gray-800/50">
                    <img
                        alt={team.slug}
                        src={
                            'https://tailwindcss.com/plus-assets/img/logos/48x48/tuple.svg'
                        }
                        className="size-12 flex-none rounded-lg bg-white object-cover ring-1 ring-gray-900/10 dark:bg-gray-700 dark:ring-white/10"
                    />
                    <div className="text-sm/6 font-medium text-gray-900 dark:text-white">
                        {team.name}
                    </div>
                </div>
                <dl className="-my-3 divide-y divide-gray-100 px-6 py-4 text-sm/6 dark:divide-white/10">
                    <div className="flex justify-between gap-x-4 py-3">
                        <dt className="text-gray-500 dark:text-gray-400">
                            Offerings
                        </dt>
                        <dd className="flex items-start gap-x-2">
                            <div className="font-medium text-gray-900 dark:text-white">
                                {team.offerings_count}
                            </div>
                            {team.offerings_count > 0 ? (
                                <div className="rounded-md bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-green-600/20 ring-inset dark:bg-green-500/10 dark:text-green-500 dark:ring-green-500/10">
                                    ACTIVE
                                </div>
                            ) : (
                                <div className="rounded-md bg-red-50 px-2 py-1 text-xs font-medium text-red-700 ring-1 ring-red-600/10 ring-inset dark:bg-red-500/10 dark:text-red-400 dark:ring-red-500/10">
                                    INACTIVE
                                </div>
                            )}
                        </dd>
                    </div>
                    <div className="flex justify-between gap-x-4 py-3">
                        <dt className="text-gray-500 dark:text-gray-400">
                            Entry cost
                        </dt>
                        {team.offerings_count > 0 && (
                            <dd className="flex items-start gap-x-2">
                                <div className="font-medium text-gray-900 dark:text-white">
                                    {team.has_free_offerings
                                        ? 'Has free offerings'
                                        : 'Paid only'}
                                </div>
                            </dd>
                        )}
                    </div>
                </dl>
            </Link>
        </>
    );
}
