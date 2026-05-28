import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

// Noop stub — used when Reverb isn't configured or is unreachable
const noopChannel = { listen: () => noopChannel, stopListening: () => noopChannel };
const noopEcho = { channel: () => noopChannel, leaveChannel: () => {}, leave: () => {} };

const apiBase = (import.meta.env.VITE_API_URL ?? '').replace(/\/$/, '');

let echo = noopEcho;

if (import.meta.env.VITE_REVERB_APP_KEY) {
    try {
        echo = new Echo({
            broadcaster:       'reverb',
            key:               import.meta.env.VITE_REVERB_APP_KEY,
            wsHost:            import.meta.env.VITE_REVERB_HOST ?? 'localhost',
            wsPort:            import.meta.env.VITE_REVERB_PORT  ?? 8080,
            wssPort:           import.meta.env.VITE_REVERB_PORT  ?? 8080,
            forceTLS:         (import.meta.env.VITE_REVERB_SCHEME ?? 'http') === 'https',
            enabledTransports: ['ws', 'wss'],
            authEndpoint:      `${apiBase}/broadcasting/auth`,
            auth: {
                headers: {
                    Authorization: `Bearer ${localStorage.getItem('icmis_token') ?? ''}`,
                },
            },
        });
    } catch (e) {
        console.warn('WebSocket unavailable:', e.message);
    }
}

export default echo;
