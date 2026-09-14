import { Link } from '@inertiajs/react';
import { show } from '@/routes/reservations';

type Reservation = {
    id: number;
};

type Props = {
    Reservations: Reservation[];
};

export default function Index({ Reservations }: Props) {
    return (
        <div>
            <h1>Reservations</h1>
            <ul>
                {Reservations &&
                    Reservations.map((reservation) => (
                        <Link
                            key={reservation.id}
                            href={show(reservation.id).url}
                        >
                            <li>Reservation: {reservation.id}</li>
                        </Link>
                    ))}
            </ul>
        </div>
    );
}

Index.layout = () => ({
    breadcrumbs: [
        {
            title: 'Reservations',
        },
    ],
});
