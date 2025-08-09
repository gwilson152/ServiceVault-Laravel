import Echo from 'laravel-echo'
import Pusher from 'pusher-js'

// Configure Pusher
window.Pusher = Pusher

// Create Echo instance for WebSocket connections
// TODO: Enable when broadcasting server is configured (Phase 8)
// window.Echo = new Echo({
//     broadcaster: 'pusher',
//     key: import.meta.env.VITE_PUSHER_APP_KEY,
//     cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER ?? 'mt1',
//     wsHost: import.meta.env.VITE_PUSHER_HOST ?? '127.0.0.1',
//     wsPort: import.meta.env.VITE_PUSHER_PORT ?? 6379,
//     wssPort: import.meta.env.VITE_PUSHER_PORT ?? 6379,
//     forceTLS: false,
//     encrypted: false,
//     disableStats: true,
//     enabledTransports: ['ws', 'wss'],
//     auth: {
//         headers: {
//             Authorization: 'Bearer ' + document.querySelector('meta[name="api-token"]')?.getAttribute('content'),
//         },
//     },
// })

// Temporary mock for development until broadcasting is implemented
const createMockChannel = () => ({
    listen: (event, callback) => createMockChannel(),
    stopListening: () => createMockChannel(),
    subscribed: (callback) => {
        // Simulate successful connection after a delay
        setTimeout(() => callback && callback(), 100)
        return createMockChannel()
    },
    error: (callback) => createMockChannel()
})

window.Echo = {
    channel: () => createMockChannel(),
    private: () => createMockChannel(),
    join: () => createMockChannel(),
    leave: () => {},
}

export default window.Echo