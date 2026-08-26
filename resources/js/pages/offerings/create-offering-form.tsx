import { Head, useForm, usePage } from '@inertiajs/react';
import { useMemo } from 'react';
import type { SubmitEvent } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectLabel,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { store } from '@/routes/offerings';
import type { Team } from '@/types/teams';

type OfferingFormData = {
    team_context: string;
    name: string;
    description: string;
    starts_at: string;
    ends_at: string;
    timezone: string;
    capacity: string;
    price: string;
    currency: string;
    booking_deadline_at: string;
    cancellation_deadline_at: string;
    hold_duration_minutes: string;
};

export default function CreateOfferingForm({
    timezones,
    currencies,
}: {
    timezones: string[];
    currencies: string[];
}) {
    const { currentTeam } = usePage().props;

    if (!currentTeam) {
        return <p>Select a company first.</p>;
    }

    return (
        <OfferingForm
            key={currentTeam.slug}
            currentTeam={currentTeam}
            timezones={timezones}
            currencies={currencies}
        />
    );
}

function OfferingForm({
    currentTeam,
    timezones,
    currencies,
}: {
    currentTeam: Team;
    timezones: string[];
    currencies: string[];
}) {
    const form = useForm<OfferingFormData>(
        `CreateOffering:${currentTeam.slug}`,
        {
            team_context: currentTeam.slug,
            name: '',
            description: '',
            starts_at: '',
            ends_at: '',
            timezone: 'Europe/Amsterdam',
            capacity: '',
            price: '0',
            currency: '',
            booking_deadline_at: '',
            cancellation_deadline_at: '',
            hold_duration_minutes: '',
        },
    );

    const timezoneGroups = useMemo(() => {
        const groups = new Map<string, string[]>();

        for (const timezone of timezones) {
            const region = timezone.split('/')[0];
            const group = groups.get(region) ?? [];

            group.push(timezone);
            groups.set(region, group);
        }

        return Array.from(groups);
    }, [timezones]);

    function submit(event: SubmitEvent<HTMLFormElement>) {
        event.preventDefault();
        form.post(store.url());
    }

    return (
        <>
            <Head title={'Create Offering'} />
            <div className="m-5">
                <header className="mb-10">
                    <h1 className="text-2xl font-semibold">Create offering</h1>
                    <p className="text-sm text-muted-foreground">
                        Creating this offering for {currentTeam.name}
                    </p>
                </header>

                <form onSubmit={submit} className="max-w-xs space-y-6">
                    <div className="grid gap-2">
                        <Label htmlFor="name">Name</Label>
                        <Input
                            id="name"
                            name="name"
                            maxLength={254}
                            value={form.data.name}
                            onChange={(event) =>
                                form.setData('name', event.target.value)
                            }
                            required
                        />
                        <InputError message={form.errors.name} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="description">Description</Label>
                        <Textarea
                            id="description"
                            name="description"
                            value={form.data.description}
                            onChange={(event) =>
                                form.setData('description', event.target.value)
                            }
                        />
                        <InputError message={form.errors.description} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="starts_at">Starts at</Label>
                        <Input
                            id="starts_at"
                            name="starts_at"
                            type="datetime-local"
                            value={form.data.starts_at}
                            onChange={(event) =>
                                form.setData('starts_at', event.target.value)
                            }
                            required
                        />
                        <InputError message={form.errors.starts_at} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="ends_at">Ends at</Label>
                        <Input
                            id="ends_at"
                            name="ends_at"
                            type="datetime-local"
                            value={form.data.ends_at}
                            onChange={(event) =>
                                form.setData('ends_at', event.target.value)
                            }
                        />
                        <InputError message={form.errors.ends_at} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="timezone">Timezone</Label>
                        <select
                            id="timezone"
                            name="timezone"
                            value={form.data.timezone}
                            onChange={(event) =>
                                form.setData('timezone', event.target.value)
                            }
                            className="h-9 w-full rounded-md border border-input bg-background px-3 text-sm"
                            required
                        >
                            {timezoneGroups.map(([region, timezones]) => (
                                <optgroup key={region} label={region}>
                                    {timezones.map((timezone) => (
                                        <option key={timezone} value={timezone}>
                                            {timezone.replace(`${region}/`, '')}
                                        </option>
                                    ))}
                                </optgroup>
                            ))}
                        </select>
                        <InputError message={form.errors.timezone} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="capacity">Capacity</Label>
                        <Input
                            id="capacity"
                            name="capacity"
                            type="number"
                            min={1}
                            value={form.data.capacity}
                            onChange={(event) => {
                                // check if non-negative whole number
                                if (
                                    event.target.value === '' ||
                                    /^\d+$/.test(event.target.value)
                                ) {
                                    form.setData(
                                        'capacity',
                                        event.target.value,
                                    );
                                }
                            }}
                            required
                        />
                        <InputError message={form.errors.capacity} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="price">Price</Label>
                        <Input
                            id="price"
                            name="price"
                            type="number"
                            min={0}
                            step={'0.01'}
                            value={form.data.price}
                            onChange={(event) => {
                                if (
                                    /^\d*(\.\d{0,2})?$/.test(event.target.value)
                                ) {
                                    form.setData('price', event.target.value);
                                }
                            }}
                            required
                        />
                        <InputError message={form.errors.price} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="currency">Currency</Label>
                        <Select
                            name="currency"
                            value={form.data.currency}
                            onValueChange={(currency) =>
                                form.setData('currency', currency)
                            }
                        >
                            <SelectTrigger id="currency">
                                <SelectValue placeholder="Select a currency" />
                            </SelectTrigger>

                            <SelectContent>
                                <SelectGroup>
                                    <SelectLabel>
                                        Available Currencies
                                    </SelectLabel>
                                    {currencies.map((currency) => (
                                        <SelectItem
                                            key={currency}
                                            value={currency}
                                        >
                                            {currency}
                                        </SelectItem>
                                    ))}
                                </SelectGroup>
                            </SelectContent>
                        </Select>

                        <InputError message={form.errors.currency} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="booking_deadline_at">
                            Booking deadline
                        </Label>
                        <Input
                            id="booking_deadline_at"
                            name="booking_deadline_at"
                            type="datetime-local"
                            value={form.data.booking_deadline_at}
                            onChange={(event) =>
                                form.setData(
                                    'booking_deadline_at',
                                    event.target.value,
                                )
                            }
                        />
                        <InputError message={form.errors.booking_deadline_at} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="cancellation_deadline_at">
                            Cancellation deadline
                        </Label>
                        <Input
                            id="cancellation_deadline_at"
                            name="cancellation_deadline_at"
                            type="datetime-local"
                            value={form.data.cancellation_deadline_at}
                            onChange={(event) =>
                                form.setData(
                                    'cancellation_deadline_at',
                                    event.target.value,
                                )
                            }
                        />
                        <InputError
                            message={form.errors.cancellation_deadline_at}
                        />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="hold_duration_minutes">
                            Hold duration (in minutes)
                        </Label>
                        <Input
                            id="hold_duration_minutes"
                            name="hold_duration_minutes"
                            type="number"
                            value={form.data.hold_duration_minutes}
                            onChange={(event) => {
                                if (
                                    event.target.value === '' ||
                                    /^\d+$/.test(event.target.value)
                                ) {
                                    form.setData(
                                        'hold_duration_minutes',
                                        event.target.value,
                                    );
                                }
                            }}
                        />
                        <InputError
                            message={form.errors.hold_duration_minutes}
                        />
                    </div>

                    <InputError message={form.errors.team_context} />

                    <Button type="submit" disabled={form.processing}>
                        {form.processing ? 'Creating…' : 'Create offering'}
                    </Button>
                </form>
            </div>
        </>
    );
}
