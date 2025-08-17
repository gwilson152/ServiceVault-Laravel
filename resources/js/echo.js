import Echo from 'laravel-echo'
import Pusher from 'pusher-js'

// Configure Pusher
window.Pusher = Pusher

// Create Echo instance for WebSocket connections
window.Echo = new Echo({
    broadcaster: 'pusher',
    key: import.meta.env.VITE_PUSHER_APP_KEY,
    cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER ?? 'mt1',
    wsHost: import.meta.env.VITE_PUSHER_HOST ?? '127.0.0.1',
    wsPort: import.meta.env.VITE_PUSHER_PORT ?? 6001,
    wssPort: import.meta.env.VITE_PUSHER_PORT ?? 6001,
    forceTLS: false,
    encrypted: false,
    disableStats: true,
    enabledTransports: ['ws', 'wss'],
    auth: {
        headers: {
            Authorization: 'Bearer ' + document.querySelector('meta[name="api-token"]')?.getAttribute('content'),
        },
    },
})

// Mock implementation disabled - using real Echo WebSocket connection above
// const createMockChannel = () => ({
//     listen: (event, callback) => {
//         console.log('Mock Echo: Listening for event', event);
//         return createMockChannel();
//     },
//     stopListening: () => createMockChannel(),
//     subscribed: (callback) => {
//         setTimeout(() => callback && callback(), 100)
//         return createMockChannel()
//     },
//     error: (callback) => createMockChannel()
// })
// 
// window.Echo = {
//     channel: () => createMockChannel(),
//     private: () => createMockChannel(),
//     join: () => createMockChannel(),
//     leave: () => {},
// }

export default window.Echo