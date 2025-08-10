<template>
    <div class="bg-white shadow rounded-lg">
        <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg leading-6 font-medium text-gray-900">
                        Ticket Timers
                    </h3>
                    <p class="mt-1 max-w-2xl text-sm text-gray-500">
                        Active timers on tickets
                    </p>
                </div>
                <div class="flex items-center space-x-2">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                          :class="activeTimers.length > 0 ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'">
                        {{ activeTimers.length }} Active
                    </span>
                    <button
                        @click="refreshData"
                        :disabled="loading"
                        class="inline-flex items-center p-1 border border-transparent rounded-full text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50"
                    >
                        <ArrowPathIcon class="h-4 w-4" :class="{ 'animate-spin': loading }" />
                    </button>
                </div>
            </div>
        </div>

        <div class="px-4 py-5 sm:p-6">
            <!-- Loading State -->
            <div v-if="loading && !timerStats" class="flex justify-center py-4">
                <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-indigo-600"></div>
            </div>

            <!-- No Active Timers -->
            <div v-else-if="activeTimers.length === 0" class="text-center py-6">
                <ClockIcon class="mx-auto h-8 w-8 text-gray-400" />
                <h4 class="mt-2 text-sm font-medium text-gray-900">No active timers</h4>
                <p class="mt-1 text-sm text-gray-500">Start a timer on a ticket to track time.</p>
            </div>

            <!-- Active Timers -->
            <div v-else class="space-y-3">
                <div
                    v-for="timer in activeTimers"
                    :key="timer.id"
                    class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors"
                >
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center space-x-2">
                            <div class="flex-shrink-0">
                                <div class="h-2 w-2 bg-green-400 rounded-full animate-pulse"></div>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 truncate">
                                    {{ timer.ticket?.title || 'Untitled Ticket' }}
                                </p>
                                <p class="text-xs text-gray-500">
                                    {{ timer.ticket?.ticket_number }}
                                    <span v-if="timer.description">• {{ timer.description }}</span>
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex items-center space-x-3 text-sm">
                        <div class="text-right">
                            <div class="font-medium text-gray-900">
                                {{ formatDuration(timer.elapsed_seconds) }}
                            </div>
                            <div class="text-xs text-gray-500">
                                {{ formatTime(timer.start_time) }}
                            </div>
                        </div>
                        <div class="flex items-center space-x-1">
                            <!-- Pause/Resume Button -->
                            <button
                                v-if="timer.status === 'running'"
                                @click="pauseTimer(timer)"
                                class="inline-flex items-center p-1 text-orange-600 hover:text-orange-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500"
                                title="Pause timer"
                            >
                                <PauseIcon class="h-4 w-4" />
                            </button>
                            <button
                                v-else-if="timer.status === 'paused'"
                                @click="resumeTimer(timer)"
                                class="inline-flex items-center p-1 text-green-600 hover:text-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                                title="Resume timer"
                            >
                                <PlayIcon class="h-4 w-4" />
                            </button>
                            
                            <!-- Stop Button -->
                            <button
                                @click="stopTimer(timer)"
                                class="inline-flex items-center p-1 text-red-600 hover:text-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
                                title="Stop timer"
                            >
                                <StopIcon class="h-4 w-4" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Summary Stats -->
            <div v-if="timerStats && activeTimers.length > 0" class="mt-4 pt-4 border-t border-gray-200">
                <div class="grid grid-cols-2 gap-4 text-center">
                    <div>
                        <div class="text-sm font-medium text-gray-500">Total Time</div>
                        <div class="mt-1 text-lg font-semibold text-gray-900">
                            {{ formatDuration(timerStats.total_elapsed) }}
                        </div>
                    </div>
                    <div>
                        <div class="text-sm font-medium text-gray-500">Est. Value</div>
                        <div class="mt-1 text-lg font-semibold text-green-600">
                            {{ formatCurrency(timerStats.estimated_value) }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { 
    ClockIcon, 
    ArrowPathIcon, 
    PlayIcon, 
    PauseIcon, 
    StopIcon 
} from '@heroicons/vue/24/outline'
import axios from 'axios'

const props = defineProps({
    widgetData: { type: [Object, Array], default: null },
    widgetConfig: { type: Object, default: () => ({}) },
    accountContext: { type: Object, default: () => ({}) }
})

const loading = ref(false)
const timerStats = ref(null)
let refreshInterval = null

// Computed values
const activeTimers = computed(() => {
    return timerStats.value?.active_timers || []
})

// Load timer statistics
const loadTimerStats = async () => {
    loading.value = true
    try {
        const response = await axios.get('/api/timers/user/statistics', {
            params: {
                include_tickets: true,
                status: 'active'
            }
        })
        timerStats.value = response.data
    } catch (error) {
        console.error('Failed to load timer statistics:', error)
    } finally {
        loading.value = false
    }
}

// Refresh data
const refreshData = () => {
    loadTimerStats()
}

// Timer control functions
const pauseTimer = async (timer) => {
    try {
        await axios.post(`/api/timers/${timer.id}/pause`)
        refreshData()
    } catch (error) {
        console.error('Failed to pause timer:', error)
    }
}

const resumeTimer = async (timer) => {
    try {
        await axios.post(`/api/timers/${timer.id}/resume`)
        refreshData()
    } catch (error) {
        console.error('Failed to resume timer:', error)
    }
}

const stopTimer = async (timer) => {
    if (!confirm('Are you sure you want to stop this timer?')) return
    
    try {
        await axios.post(`/api/timers/${timer.id}/stop`)
        refreshData()
    } catch (error) {
        console.error('Failed to stop timer:', error)
    }
}

// Format helpers
const formatDuration = (seconds) => {
    if (!seconds) return '0:00'
    
    const hours = Math.floor(seconds / 3600)
    const minutes = Math.floor((seconds % 3600) / 60)
    const remainingSeconds = Math.floor(seconds % 60)
    
    if (hours > 0) {
        return `${hours}:${minutes.toString().padStart(2, '0')}:${remainingSeconds.toString().padStart(2, '0')}`
    }
    return `${minutes}:${remainingSeconds.toString().padStart(2, '0')}`
}

const formatTime = (timestamp) => {
    return new Date(timestamp).toLocaleTimeString('en-US', {
        hour: 'numeric',
        minute: '2-digit',
        hour12: true
    })
}

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD'
    }).format(amount || 0)
}

// Auto-refresh functionality
const startAutoRefresh = () => {
    refreshInterval = setInterval(() => {
        if (activeTimers.value.length > 0) {
            loadTimerStats()
        }
    }, 30000) // Refresh every 30 seconds
}

const stopAutoRefresh = () => {
    if (refreshInterval) {
        clearInterval(refreshInterval)
        refreshInterval = null
    }
}

onMounted(() => {
    loadTimerStats()
    startAutoRefresh()
})

onUnmounted(() => {
    stopAutoRefresh()
})
</script>