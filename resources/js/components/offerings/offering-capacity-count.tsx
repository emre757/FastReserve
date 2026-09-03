import { useConnectionStatus, useEcho } from '@laravel/echo-react';
import { useState } from 'react';

type AvailabilityChanged = {
    offeringId: number;
    remainingCapacity: number;
};

type Props = {
    offeringId: number;
    availableSpots: number;
};

export default function OfferingCapacityCount({
    offeringId,
    availableSpots,
}: Props) {
    const [remainingCapacity, setRemainingCapacity] = useState(availableSpots);
    const connectionStatus = useConnectionStatus();

    useEcho<AvailabilityChanged>(
        `offerings.${offeringId}`,
        'Offerings.OfferingAvailabilityChanged',
        (event) => {
            setRemainingCapacity(event.remainingCapacity);
        },
        [offeringId],
    );

    const liveUpdatesUnavailable =
        connectionStatus === 'disconnected' || connectionStatus === 'failed';

    return (
        <div>
            <p>{remainingCapacity}</p>

            {liveUpdatesUnavailable && (
                <p role="status">
                    Live updates are unavailable, available spots may be
                    outdated.
                </p>
            )}

            {(connectionStatus === 'connecting' ||
                connectionStatus === 'reconnecting') && (
                <p role="status">Connecting to live updates…</p>
            )}
        </div>
    );
}
