import { onMounted, onUnmounted } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { useTimersQuery } from '@/Composables/queries/useTimersQuery.js'
import timerBroadcastingService from '@/Services/TimerBroadcastingService.js'

export function useTimerBroadcasting() {
    const page = usePage()
    const user = page.props?.auth?.user
    
    // Use TanStack Query for timer data management
    const timerQueries = useTimersQuery()
    const {
        activeTimers: timers,
        startTimer: startTimerMutation,
        stopTimer: stopTimerMutation,
        pauseTimer: pauseTimerMutation,
        resumeTimer: resumeTimerMutation,
        addOrUpdateTimer,
        removeTimer
    } = timerQueries
    
    // Get connection status and sync time from global service
    const connectionStatus = timerBroadcastingService.getConnectionStatus()
    const lastSync = timerBroadcastingService.getLastSync()
    
    const syncTimers = () => {
        return timerBroadcastingService.syncTimers()
    }
    
    // Timer actions are now handled by TanStack Query with optimistic updates
    const startTimer = (timerData) => {
        return startTimerMutation(timerData)
    }
    
    const stopTimer = (timerId) => {
        return stopTimerMutation(timerId)
    }
    
    const pauseTimer = (timerId) => {
        return pauseTimerMutation(timerId)
    }
    
    const resumeTimer = (timerId) => {
        return resumeTimerMutation(timerId)
    }
    
    // Lifecycle - use global service for connection management
    onMounted(() => {
        console.log('TimerBroadcastOverlay: onMounted - registering component')
        // Register this component with the global service
        timerBroadcastingService.registerComponent()
        // Initialize the service with user data and timer queries immediately (no delay)
        timerBroadcastingService.initialize(user, timerQueries)
    })
    
    onUnmounted(() => {
        console.log('TimerBroadcastOverlay: onUnmounted - unregistering component')
        // Unregister this component from the global service
        timerBroadcastingService.unregisterComponent()
    })
    
    return {
        timers,
        connectionStatus,
        lastSync,
        syncTimers,
        startTimer,
        stopTimer,
        pauseTimer,
        resumeTimer,
        addOrUpdateTimer,
        removeTimer
    }
}