import Echo from 'laravel-echo'
import Pusher from 'pusher-js'

// Configure Pusher
window.Pusher = Pusher

// Laravel Reverb WebSocket real-time broadcasting
try {
    // Get current host to match WebSocket connection
    const currentHost = window.location.hostname
    const isSecure = window.location.protocol === 'https:'
    const wsPort = import.meta.env.VITE_REVERB_PORT ?? 8080
    
    window.Echo = new Echo({
        broadcaster: 'reverb',
        key: import.meta.env.VITE_REVERB_APP_KEY,
        wsHost: currentHost,
        wsPort: wsPort,
        wssPort: wsPort,
        forceTLS: isSecure,
        encrypted: isSecure,
        enabledTransports: ['ws', 'wss'],
        auth: {
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
            },
        },
    })
    console.log(`Laravel Reverb WebSocket initialized for ${currentHost}:${wsPort}`)
} catch (error) {
    console.log('WebSocket initialization failed:', error)
    window.Echo = null
}

export default window.Echo