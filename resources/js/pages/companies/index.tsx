import { Head } from '@inertiajs/react';
import { CompanyPagination } from '@/components/companies/company-pagination';
import CompanyPreviewCard from '@/components/companies/company-preview-card';
import { index as companiesIndex } from '@/routes/companies';
import type { TeamSummary } from '@/types';
import type { PaginatedData } from '@/types/pagination';

type Props = {
    companies: PaginatedData<TeamSummary>;
};

export default function Index({ companies }: Props) {
    const companiesData = companies.data;

    return (
        <>
            <Head title="Companies" />
            <div className="flex flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <div className="grid auto-rows-min gap-4 md:grid-cols-3">
                    {companiesData && companiesData.length > 0 ? (
                        companiesData.map((team: TeamSummary) => (
                            <CompanyPreviewCard key={team.slug} team={team} />
                        ))
                    ) : (
                        <h1 className={'text-xl font-bold'}>
                            There are no active companies on this platform yet.
                        </h1>
                    )}
                </div>
                <br />
                <CompanyPagination
                    links={companies.links}
                    meta={companies.meta}
                />
            </div>
        </>
    );
}

Index.layout = () => ({
    breadcrumbs: [
        {
            title: 'Companies',
            href: companiesIndex(),
        },
    ],
});
