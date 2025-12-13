import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

let echoInstance = null;

export function initEcho(authToken) {
    if (echoInstance) {
        return echoInstance;
    }

    window.Pusher = Pusher;

    const apiBase = import.meta.env.VITE_API_BASE || '';
    // Convert http://localhost/api -> http://localhost
    const backendBase = apiBase.replace(/\/api\/?$/, '');

    echoInstance = new Echo({
        broadcaster: 'pusher',
        key: import.meta.env.VITE_PUSHER_APP_KEY,
        cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER || 'mt1',
        forceTLS: (import.meta.env.VITE_PUSHER_SCHEME || 'https') === 'https',

        // Pusher Cloud websocket host/ports
        wsHost: import.meta.env.VITE_PUSHER_HOST || `ws-${import.meta.env.VITE_PUSHER_APP_CLUSTER || 'mt1'}.pusher.com`,
        wsPort: import.meta.env.VITE_PUSHER_PORT || 443,
        wssPort: import.meta.env.VITE_PUSHER_PORT || 443,
        enabledTransports: ['ws', 'wss'],

        authEndpoint: `${backendBase}/broadcasting/auth`,

        auth: {
            headers: authToken
                ? { Authorization: `Bearer ${authToken}` }
                : {},
        },
    });

    return echoInstance;
}

export function getEcho() {
    return echoInstance;
}

export function leaveChannel(channelName) {
    if (echoInstance) {
        echoInstance.leave(channelName);
    }
}
