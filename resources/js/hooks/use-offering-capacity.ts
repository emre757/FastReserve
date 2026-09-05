import { useEcho } from '@laravel/echo-react';
import { useState } from 'react';

type AvailabilityChanged = {
    offeringId: number;
    remainingCapacity: number;
};

type LiveCapacity = {
    offeringId: number;
    remainingCapacity: number;
};

export function useOfferingCapacity(
    offeringId: number,
    initialAvailableSpots: number,
): number {
    const [liveCapacity, setLiveCapacity] = useState<LiveCapacity | null>(null);

    useEcho<AvailabilityChanged>(
        `offerings.${offeringId}`,
        'Offerings.OfferingAvailabilityChanged',
        (event) => {
            setLiveCapacity({
                offeringId: event.offeringId,
                remainingCapacity: event.remainingCapacity,
            });
        },
        [offeringId],
    );

    return liveCapacity?.offeringId === offeringId
        ? liveCapacity.remainingCapacity
        : initialAvailableSpots;
}
