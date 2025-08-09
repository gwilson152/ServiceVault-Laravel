import { ref, onMounted, onUnmounted } from 'vue'
import { usePage } from '@inertiajs/vue3'

export function useTimerBroadcasting() {
    const page = usePage()
    const timers = ref([])
    const connectionStatus = ref('disconnected')
    const lastSync = ref(null)
    
    let userChannel = null
    let timerChannel = null
    
    const user = page.props.auth?.user
    
    const connectToChannels = () => {
        if (!user || !window.Echo) {
            console.warn('Echo or user not available for timer broadcasting')
            return
        }
        
        try {
            // Connect to user-specific timer channel
            timerChannel = window.Echo.private(`user.${user.id}.timers`)
            
            // Listen for timer events
            timerChannel
                .listen('TimerStarted', (e) => {
                    console.log('Timer started:', e.timer)
                    addOrUpdateTimer(e.timer)
                })
                .listen('TimerStopped', (e) => {
                    console.log('Timer stopped:', e.timer)
                    removeTimer(e.timer.id)
                })
                .listen('TimerUpdated', (e) => {
                    console.log('Timer updated:', e.timer)
                    addOrUpdateTimer(e.timer)
                })
                .listen('TimerDeleted', (e) => {
                    console.log('Timer deleted:', e.timer)
                    removeTimer(e.timer.id)
                })
                .listen('TimerPaused', (e) => {
                    console.log('Timer paused:', e.timer)
                    addOrUpdateTimer(e.timer)
                })
                .listen('TimerResumed', (e) => {
                    console.log('Timer resumed:', e.timer)
                    addOrUpdateTimer(e.timer)
                })
            
            // Handle connection events
            timerChannel
                .subscribed(() => {
                    console.log('Connected to timer channel')
                    connectionStatus.value = 'connected'
                    syncTimers()
                })
                .error((error) => {
                    console.error('Timer channel error:', error)
                    connectionStatus.value = 'error'
                })
            
            // General user channel for other events
            userChannel = window.Echo.private(`user.${user.id}`)
                .subscribed(() => {
                    console.log('Connected to user channel')
                })
                .error((error) => {
                    console.error('User channel error:', error)
                })
            
        } catch (error) {
            console.error('Failed to connect to broadcasting channels:', error)
            connectionStatus.value = 'error'
        }
    }
    
    const disconnectFromChannels = () => {
        if (timerChannel) {
            window.Echo.leave(`user.${user.id}.timers`)
            timerChannel = null
        }
        if (userChannel) {
            window.Echo.leave(`user.${user.id}`)
            userChannel = null
        }
        connectionStatus.value = 'disconnected'
    }
    
    const addOrUpdateTimer = (timer) => {
        const index = timers.value.findIndex(t => t.id === timer.id)
        if (index >= 0) {
            timers.value[index] = timer
        } else {
            timers.value.push(timer)
        }
        lastSync.value = new Date()
    }
    
    const removeTimer = (timerId) => {
        const index = timers.value.findIndex(t => t.id === timerId)
        if (index >= 0) {
            timers.value.splice(index, 1)
        }
        lastSync.value = new Date()
    }
    
    const syncTimers = async () => {
        if (!user) return
        
        try {
            const response = await window.axios.get('/api/timers/active/current')
            if (response.data.data) {
                timers.value = Array.isArray(response.data.data) 
                    ? response.data.data.map(item => item.timer || item)
                    : [response.data.data.timer || response.data.data]
            }
            lastSync.value = new Date()
        } catch (error) {
            console.error('Failed to sync timers:', error)
        }
    }
    
    const startTimer = async (timerData) => {
        try {
            const response = await window.axios.post('/api/timers', {
                ...timerData,
                stop_others: false // Support multiple concurrent timers
            })
            // Timer will be added via broadcasting event
            return response.data
        } catch (error) {
            console.error('Failed to start timer:', error)
            throw error
        }
    }
    
    const stopTimer = async (timerId) => {
        try {
            const response = await window.axios.post(`/api/timers/${timerId}/stop`)
            // Timer will be removed via broadcasting event
            return response.data
        } catch (error) {
            console.error('Failed to stop timer:', error)
            throw error
        }
    }
    
    const pauseTimer = async (timerId) => {
        try {
            const response = await window.axios.post(`/api/timers/${timerId}/pause`)
            return response.data
        } catch (error) {
            console.error('Failed to pause timer:', error)
            throw error
        }
    }
    
    const resumeTimer = async (timerId) => {
        try {
            const response = await window.axios.post(`/api/timers/${timerId}/resume`)
            return response.data
        } catch (error) {
            console.error('Failed to resume timer:', error)
            throw error
        }
    }
    
    // Lifecycle
    onMounted(() => {
        // Wait a bit for Echo to be fully initialized
        setTimeout(() => {
            connectToChannels()
            syncTimers()
        }, 1000)
    })
    
    onUnmounted(() => {
        disconnectFromChannels()
    })
    
    return {
        timers,
        connectionStatus,
        lastSync,
        connectToChannels,
        disconnectFromChannels,
        syncTimers,
        startTimer,
        stopTimer,
        pauseTimer,
        resumeTimer,
        addOrUpdateTimer,
        removeTimer
    }
}