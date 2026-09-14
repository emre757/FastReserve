import { echo } from '@laravel/echo-react';
import type { ConnectionStatus } from '@laravel/echo-react';
import { useSyncExternalStore } from 'react';

function subscribe(onStoreChange: () => void): () => void {
    if (typeof window === 'undefined') {
        return () => {};
    }

    return echo().connector.onConnectionChange(() => {
        onStoreChange();
    });
}

function getConnectionStatus(): ConnectionStatus {
    if (typeof window === 'undefined') {
        return 'disconnected';
    }

    return echo().connectionStatus();
}

function getServerConnectionStatus(): ConnectionStatus {
    return 'disconnected';
}

export function useEchoConnectionStatus(): ConnectionStatus {
    return useSyncExternalStore(
        subscribe,
        getConnectionStatus,
        getServerConnectionStatus,
    );
}
