import { useEcho } from '@laravel/echo-react';
import { useState } from 'react';

type AvailabilityChanged = {
    offeringId: number;
    remainingCapacity: number;
    version: number;
};

type LiveCapacity = AvailabilityChanged;

export function useOfferingCapacity(
    offeringId: number,
    initialAvailableSpots: number,
    initialVersion: number,
): number {
    const [liveCapacity, setLiveCapacity] = useState<LiveCapacity | null>(null);

    useEcho<AvailabilityChanged>(
        `offerings.${offeringId}`,
        'Offerings.OfferingAvailabilityChanged',
        (event) => {
            if (event.offeringId !== offeringId) {
                return;
            }

            setLiveCapacity((current) => {
                const currentVersion =
                    current?.offeringId === offeringId
                        ? Math.max(current.version, initialVersion)
                        : initialVersion;

                return event.version > currentVersion ? event : current;
            });
        },
        [offeringId, initialVersion],
    );

    return liveCapacity?.offeringId === offeringId &&
        liveCapacity.version > initialVersion
        ? liveCapacity.remainingCapacity
        : initialAvailableSpots;
}
