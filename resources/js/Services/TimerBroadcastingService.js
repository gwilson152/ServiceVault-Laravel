import { ref, computed } from 'vue'
import { useTimersQuery } from '@/Composables/queries/useTimersQuery.js'

// Global singleton service for timer broadcasting
class TimerBroadcastingService {
    constructor() {
        this.connectionStatus = ref('disconnected')
        this.lastSync = ref(null)
        this.isInitialized = false
        
        // Track active connections
        this.userChannel = null
        this.timerChannel = null
        this.user = null
        
        // Component reference count
        this.componentCount = 0
        
        // Timer queries instance (will be initialized when first component mounts)
        this.timerQueries = null
    }
    
    // Initialize the service with user data and timer queries
    initialize(user, timerQueriesInstance) {
        const wasAlreadyInitialized = this.isInitialized && this.user?.id === user?.id
        
        if (wasAlreadyInitialized && this.timerQueries) {
            console.log('TimerBroadcastingService: Already initialized for user', user?.id)
            return
        }
        
        console.log('TimerBroadcastingService: Setting timer queries instance')
        this.user = user
        this.timerQueries = timerQueriesInstance
        this.isInitialized = true
        
        // Connect to channels if not already connected
        if (!this.timerChannel) {
            this.connectToChannels()
        }
    }
    
    // Global initialization at app level (without timer queries initially)
    initializeGlobally(user) {
        if (this.isInitialized && this.user?.id === user?.id) {
            console.log('TimerBroadcastingService: Already globally initialized for user', user?.id)
            return
        }
        
        console.log('TimerBroadcastingService: Global initialization for user', user?.id)
        this.user = user
        this.isInitialized = true
        
        // Don't connect to channels yet - wait for timer queries to be available
        // This just sets up the user and marks as initialized
    }
    
    // Register a component (increment reference count)
    registerComponent() {
        this.componentCount++
        console.log(`TimerBroadcastingService: Component registered. Count: ${this.componentCount}`)
        return this.componentCount
    }
    
    // Unregister a component (decrement reference count)
    unregisterComponent() {
        this.componentCount = Math.max(0, this.componentCount - 1)
        console.log(`TimerBroadcastingService: Component unregistered. Count: ${this.componentCount}`)
        
        // Don't disconnect during navigation - keep connection alive
        // Only disconnect if user explicitly logs out or closes browser
        // This prevents the "connecting" flicker during Inertia navigation
        
        return this.componentCount
    }
    
    connectToChannels() {
        if (!this.user || !window.Echo) {
            console.warn('TimerBroadcastingService: Echo or user not available')
            return
        }
        
        // If already connected, don't reconnect
        if (this.timerChannel && this.connectionStatus.value === 'connected') {
            console.log('TimerBroadcastingService: Already connected, skipping reconnection')
            return
        }
        
        console.log('TimerBroadcastingService: Connecting to channels...')
        
        try {
            this.connectionStatus.value = 'connecting'
            
            // Connect to user-specific timer channel
            this.timerChannel = window.Echo.private(`user.${this.user.id}.timers`)
            
            // Listen for timer events
            this.timerChannel
                .listen('TimerStarted', (e) => {
                    console.log('TimerBroadcastingService: Timer started:', e.timer)
                    this.timerQueries?.addOrUpdateTimer(e.timer)
                })
                .listen('TimerStopped', (e) => {
                    console.log('TimerBroadcastingService: Timer stopped:', e.timer)
                    this.timerQueries?.removeTimer(e.timer.id)
                })
                .listen('TimerUpdated', (e) => {
                    console.log('TimerBroadcastingService: Timer updated:', e.timer)
                    this.timerQueries?.addOrUpdateTimer(e.timer)
                })
                .listen('TimerDeleted', (e) => {
                    console.log('TimerBroadcastingService: Timer deleted:', e.timer)
                    this.timerQueries?.removeTimer(e.timer.id)
                })
                .listen('TimerPaused', (e) => {
                    console.log('TimerBroadcastingService: Timer paused:', e.timer)
                    this.timerQueries?.addOrUpdateTimer(e.timer)
                })
                .listen('TimerResumed', (e) => {
                    console.log('TimerBroadcastingService: Timer resumed:', e.timer)
                    this.timerQueries?.addOrUpdateTimer(e.timer)
                })
            
            // Handle connection events
            this.timerChannel
                .subscribed(() => {
                    console.log('TimerBroadcastingService: Connected to timer channel')
                    this.connectionStatus.value = 'connected'
                    this.syncTimers()
                })
                .error((error) => {
                    console.error('TimerBroadcastingService: Channel error:', error)
                    this.connectionStatus.value = 'error'
                })
            
            // General user channel for other events
            this.userChannel = window.Echo.private(`user.${this.user.id}`)
                .subscribed(() => {
                    console.log('TimerBroadcastingService: Connected to user channel')
                })
                .error((error) => {
                    console.error('TimerBroadcastingService: User channel error:', error)
                })
            
        } catch (error) {
            console.error('TimerBroadcastingService: Failed to connect:', error)
            this.connectionStatus.value = 'error'
        }
    }
    
    disconnectFromChannels() {
        console.log('TimerBroadcastingService: Disconnecting from channels')
        
        if (this.timerChannel && window.Echo) {
            window.Echo.leave(`user.${this.user?.id}.timers`)
            this.timerChannel = null
        }
        if (this.userChannel && window.Echo) {
            window.Echo.leave(`user.${this.user?.id}`)
            this.userChannel = null
        }
        
        this.connectionStatus.value = 'disconnected'
    }
    
    syncTimers() {
        this.lastSync.value = new Date()
        return this.timerQueries?.syncTimers?.()
    }
    
    // Expose connection status as computed
    getConnectionStatus() {
        return computed(() => this.connectionStatus.value)
    }
    
    // Expose last sync as computed
    getLastSync() {
        return computed(() => this.lastSync.value)
    }
}

// Create a global singleton instance
const timerBroadcastingService = new TimerBroadcastingService()

export default timerBroadcastingService