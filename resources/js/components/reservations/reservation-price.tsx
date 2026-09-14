import { CircleDollarSign } from 'lucide-react';
import { formatPrice } from '@/components/reservations/format-price';

type Props = {
    price: string;
    currency: string | null;
};

export default function ReservationPrice({ price, currency }: Props) {
    return (
        <div className="rounded-xl border p-5">
            <dt className="flex items-center gap-2 text-sm text-muted-foreground">
                <CircleDollarSign aria-hidden="true" className="size-4" />
                Price
            </dt>
            <dd className="mt-4 text-2xl font-semibold tracking-tight text-foreground">
                {formatPrice(Number(price), currency)}
            </dd>
            <p className="mt-2 text-xs/5 text-muted-foreground">
                Price for one spot
            </p>
        </div>
    );
}
