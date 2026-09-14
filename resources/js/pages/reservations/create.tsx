import { Head, useForm } from '@inertiajs/react';
import type { SubmitEvent } from 'react';
import InputError from '@/components/input-error';
import { formatPrice } from '@/components/reservations/format-price';
import ReservationDetailsCard from '@/components/reservations/reservation-details-card';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useOfferingCapacity } from '@/hooks/use-offering-capacity';
import { index as companiesIndex } from '@/routes/companies';
import { index as companyIndex } from '@/routes/companies/offerings';
import { show as offeringShow } from '@/routes/offerings';
import { store } from '@/routes/offerings/reservations';

type OfferingFormData = {
    spots: string;
};

type Props = {
    offering: {
        id: number;
        name: string;
        capacity: number;
        price: string;
        currency: string | null;
        broadcast_version: number;
    };
    company: {
        name: string;
        slug: string;
    };
    availableSpots: number;
};

export default function Create({ offering, company, availableSpots }: Props) {
    const form = useForm<OfferingFormData>(
        `CreateReservation:${offering.name}`,
        {
            spots: '',
        },
    );

    const pricePerSpot = Number(offering.price);
    const selectedSpots = Number(form.data.spots);
    const currentAvailableSpots = useOfferingCapacity(
        offering.id,
        availableSpots,
        offering.broadcast_version,
    );
    const totalPrice =
        Number.isFinite(selectedSpots) && selectedSpots > 0
            ? selectedSpots * pricePerSpot
            : null;

    function submit(event: SubmitEvent<HTMLFormElement>) {
        event.preventDefault();
        form.post(store.url(offering.id));
    }

    return (
        <>
            <Head title="Create Reservation" />
            <div className="m-5">
                <div className="mx-auto max-w-6xl">
                    <header className="mb-8 max-w-2xl">
                        <p className="mb-2 text-sm font-medium text-sky-600 dark:text-sky-400">
                            New reservation
                        </p>
                        <h1 className="text-3xl font-bold tracking-tight text-gray-900 dark:text-white">
                            Confirm your reservation
                        </h1>
                        <p className="mt-3 text-sm/6 text-muted-foreground sm:text-base">
                            Review the offering details and make sure you are
                            reserving with the right company before continuing.
                        </p>
                    </header>

                    <div className="grid gap-6 lg:grid-cols-[minmax(0,1fr)_22rem] lg:items-start">
                        <ReservationDetailsCard
                            offering={offering}
                            company={company}
                            availableSpots={currentAvailableSpots}
                        />

                        <form onSubmit={submit}>
                            <Card className="gap-0 overflow-hidden py-0 shadow-md lg:sticky lg:top-6">
                                <CardHeader className="border-b px-6 py-5">
                                    <CardTitle>Choose your spots</CardTitle>
                                    <CardDescription>
                                        Enter how many places you want to
                                        reserve.
                                    </CardDescription>
                                </CardHeader>

                                <CardContent className="space-y-6 p-6">
                                    <div className="grid gap-2">
                                        <Label htmlFor="spots">Spots</Label>
                                        <Input
                                            id="spots"
                                            name="spots"
                                            type="number"
                                            min={0}
                                            max={currentAvailableSpots}
                                            value={form.data.spots}
                                            onChange={(event) =>
                                                form.setData(
                                                    'spots',
                                                    event.target.value,
                                                )
                                            }
                                            aria-describedby="spots-help"
                                            required
                                        />
                                        <p
                                            id="spots-help"
                                            className="text-xs/5 text-muted-foreground"
                                        >
                                            This offering has a total capacity
                                            of {offering.capacity}{' '}
                                            {offering.capacity === 1
                                                ? 'spot'
                                                : 'spots'}
                                            .
                                        </p>
                                        <InputError
                                            message={form.errors.spots}
                                        />
                                    </div>

                                    <div className="rounded-xl border bg-muted/40 p-4">
                                        <div className="flex items-center justify-between gap-4">
                                            <span className="text-sm font-medium text-muted-foreground">
                                                Total cost
                                            </span>
                                            <span className="text-xl font-semibold tracking-tight text-foreground">
                                                {totalPrice === null
                                                    ? '—'
                                                    : formatPrice(
                                                          totalPrice,
                                                          offering.currency,
                                                      )}
                                            </span>
                                        </div>
                                        <p className="mt-2 text-xs/5 text-muted-foreground">
                                            {totalPrice === null
                                                ? 'Choose the number of spots to see your total.'
                                                : pricePerSpot === 0
                                                  ? 'No payment is required for this reservation.'
                                                  : `${selectedSpots} × ${formatPrice(pricePerSpot, offering.currency)} per spot`}
                                        </p>
                                    </div>

                                    <Button
                                        type="submit"
                                        className="w-full"
                                        disabled={form.processing}
                                    >
                                        {form.processing
                                            ? 'Creating…'
                                            : 'Create reservation'}
                                    </Button>

                                    <p className="text-center text-xs/5 text-muted-foreground">
                                        Check the details one last time before
                                        confirming.
                                    </p>
                                </CardContent>
                            </Card>
                        </form>
                    </div>
                </div>
            </div>
        </>
    );
}

Create.layout = ({ offering, company }: Props) => ({
    breadcrumbs: [
        {
            title: 'Companies',
            href: companiesIndex(),
        },
        {
            title: company.name,
            href: companyIndex({ team: company.slug }),
        },
        {
            title: offering.name,
            href: offeringShow(offering.id),
        },
        {
            title: 'Reserve',
        },
    ],
});
