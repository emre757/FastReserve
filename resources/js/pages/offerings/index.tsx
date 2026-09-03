import { Head } from '@inertiajs/react';
import OfferingPreviewCard from '@/components/offerings/offering-preview-card';
import { index as companiesIndex } from '@/routes/companies';
import { index as offeringsIndex } from '@/routes/companies/offerings';
import type { OfferingSummary } from '@/types/offerings';

type Props = {
    company: {
        name: string;
        slug: string;
    };
    offerings: {
        data: OfferingSummary[];
    };
};

export default function Index({ offerings }: Props) {
    return (
        <>
            <Head title="Company Offerings" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <div className="grid auto-rows-min gap-4 md:grid-cols-5">
                    {offerings.data?.length > 0 ? (
                        offerings.data.map((offering: OfferingSummary) => (
                            <OfferingPreviewCard
                                key={offering.id}
                                offering={offering}
                            />
                        ))
                    ) : (
                        <h1 className={'text-xl font-bold'}>
                            This company has no active offerings.
                        </h1>
                    )}
                </div>
            </div>
        </>
    );
}

Index.layout = ({ company }: Props) => ({
    breadcrumbs: [
        {
            title: 'Companies',
            href: companiesIndex(),
        },
        {
            title: company.name,
            href: offeringsIndex(company.slug),
        },
    ],
});
