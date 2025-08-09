import axios from 'axios';

// Make axios available globally
window.axios = axios;

// Set default headers
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
window.axios.defaults.headers.common['Accept'] = 'application/json';

// Add CSRF token to all requests
const token = document.head.querySelector('meta[name="csrf-token"]');
if (token) {
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content;
}

// Configure axios for Sanctum
window.axios.defaults.withCredentials = true;

// Initialize CSRF cookie for API requests
window.initializeCSRF = async () => {
    try {
        await axios.get('/sanctum/csrf-cookie');
    } catch (error) {
        console.error('Failed to initialize CSRF cookie:', error);
    }
};

// Add response interceptor for error handling
window.axios.interceptors.response.use(
    response => response,
    error => {
        if (error.response && error.response.status === 401) {
            window.location.href = '/login';
        }
        return Promise.reject(error);
    }
);

// Initialize Laravel Echo for real-time broadcasting (configured for future use)
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

// Configure for development - will be activated when WebSocket server is set up
if (import.meta.env.VITE_ENABLE_BROADCASTING === 'true') {
    window.Pusher = Pusher;
    
    window.Echo = new Echo({
        broadcaster: 'pusher',
        key: import.meta.env.VITE_PUSHER_APP_KEY || 'local-dev-key',
        cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER || 'mt1',
        wsHost: import.meta.env.VITE_PUSHER_HOST || '127.0.0.1',
        wsPort: import.meta.env.VITE_PUSHER_PORT || 6001,
        wssPort: import.meta.env.VITE_PUSHER_PORT || 6001,
        forceTLS: (import.meta.env.VITE_PUSHER_SCHEME || 'http') === 'https',
        encrypted: true,
        enabledTransports: ['ws', 'wss'],
        authEndpoint: '/broadcasting/auth',
        auth: {
            headers: {
                Accept: 'application/json',
            }
        }
    });
} else {
    // Mock Echo for development when broadcasting is disabled
    console.log('Broadcasting disabled - using mock Echo');
    window.Echo = {
        private: () => ({
            listen: () => ({ listen: () => ({ listen: () => ({}) }) }),
            subscribed: () => ({}),
            error: () => ({})
        }),
        leave: () => ({})
    };
}

// Helper function to get user ID for channels
window.getCurrentUserId = () => {
    // This would typically come from a global user object or meta tag
    const userMeta = document.head.querySelector('meta[name="user-id"]');
    return userMeta ? userMeta.content : null;
};

console.log('Service Vault - Frontend & Broadcasting Initialized');