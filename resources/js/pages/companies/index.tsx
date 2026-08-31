import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import { CompanyPagination } from '@/components/companies/company-pagination';
import CompanyPreviewCard from '@/components/companies/company-preview-card';
import type { FilterOption } from '@/components/ui/search-bar';
import SearchBar from '@/components/ui/search-bar';
import { index, index as companiesIndex } from '@/routes/companies';
import type { TeamSummary } from '@/types';
import type { PaginatedData } from '@/types/pagination';

type Props = {
    companies: PaginatedData<TeamSummary>;
    filters: {
        search: string;
        filters: string[];
    };
};

const filterOptions: FilterOption[] = [
    {
        label: 'Active',
        value: 'active',
    },
    {
        label: 'Free',
        value: 'free',
    },
];

export default function Index({ companies, filters }: Props) {
    const companiesData = companies.data;

    const [search, setSearch] = useState<string>(filters.search);
    const [selectedFilters, setSelectedFilters] = useState<string[]>(
        filters.filters,
    );

    function makeSearchRequest(nextSearch: string, nextFilters: string[]) {
        router.get(
            index({
                query: {
                    search: nextSearch || undefined,
                    filters: nextFilters.length > 0 ? nextFilters : undefined,
                },
            }),
            {},
            {
                preserveState: true,
                replace: true,
            },
        );
    }

    return (
        <>
            <Head title="Companies" />
            <div className="flex flex-1 flex-col gap-10 overflow-x-auto rounded-xl p-4">
                <SearchBar
                    initialSearch={filters.search}
                    resultCount={companies.meta.total}
                    onSearch={(nextSearch) => {
                        setSearch(nextSearch);
                        makeSearchRequest(nextSearch, selectedFilters);
                    }}
                    filterProps={{
                        options: filterOptions,
                        selected: selectedFilters,
                        onChange: (nextFilters) => {
                            setSelectedFilters(nextFilters);
                            makeSearchRequest(search, nextFilters);
                        },
                    }}
                />
                <div className="grid auto-rows-min gap-4 md:grid-cols-3">
                    {companiesData && companiesData.length > 0 ? (
                        companiesData.map((team: TeamSummary) => (
                            <CompanyPreviewCard key={team.slug} team={team} />
                        ))
                    ) : (
                        <h1 className={'text-xl font-bold'}>
                            No companies found.
                        </h1>
                    )}
                </div>
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
