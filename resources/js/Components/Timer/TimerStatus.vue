<template>
  <div v-if="currentTimer" class="flex items-center space-x-2 text-sm">
    <!-- Status indicator -->
    <div
      :class="[
        'w-2 h-2 rounded-full',
        currentTimer.status === 'running' ? 'bg-green-500 animate-pulse' : 'bg-yellow-500'
      ]"
    />
    
    <!-- Duration display -->
    <span class="font-mono font-medium text-gray-900">
      {{ formatDuration(duration) }}
    </span>
    
    <!-- Amount display -->
    <span v-if="currentTimer.calculated_amount" class="text-green-600 font-medium">
      ${{ currentTimer.calculated_amount.toFixed(2) }}
    </span>
    
    <!-- Quick controls -->
    <div class="flex items-center space-x-1 ml-2">
      <button
        v-if="currentTimer.status === 'running'"
        @click="pauseTimer"
        class="p-1 text-gray-400 hover:text-yellow-600 rounded"
        title="Pause Timer"
      >
        <PauseIcon class="w-4 h-4" />
      </button>
      <button
        v-else
        @click="resumeTimer"
        class="p-1 text-gray-400 hover:text-green-600 rounded"
        title="Resume Timer"
      >
        <PlayIcon class="w-4 h-4" />
      </button>
      
      <button
        @click="stopTimer"
        class="p-1 text-gray-400 hover:text-red-600 rounded"
        title="Stop Timer"
      >
        <StopIcon class="w-4 h-4" />
      </button>
    </div>
  </div>
  
  <div v-else class="text-sm text-gray-500">
    No active timer
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import {
  PlayIcon,
  PauseIcon,
  StopIcon,
} from '@heroicons/vue/24/outline'

// Reactive state
const currentTimer = ref(null)
const duration = ref(0)
let updateInterval = null

// Timer duration formatting
const formatDuration = (seconds) => {
  const hours = Math.floor(seconds / 3600)
  const minutes = Math.floor((seconds % 3600) / 60)
  const secs = seconds % 60

  if (hours > 0) {
    return `${hours}:${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`
  }
  return `${minutes}:${secs.toString().padStart(2, '0')}`
}

// API calls
const fetchCurrentTimer = async () => {
  try {
    const response = await axios.get('/api/timers/active/current')
    if (response.data.data) {
      currentTimer.value = response.data.data
      
      if (currentTimer.value.status === 'running') {
        calculateDuration()
        startDurationUpdate()
      } else {
        duration.value = currentTimer.value.duration
        stopDurationUpdate()
      }
    } else {
      currentTimer.value = null
      stopDurationUpdate()
    }
  } catch (error) {
    console.error('Failed to fetch current timer:', error)
  }
}

const calculateDuration = () => {
  if (!currentTimer.value || currentTimer.value.status !== 'running') return
  
  const startTime = new Date(currentTimer.value.started_at).getTime()
  const now = Date.now()
  const pausedDuration = (currentTimer.value.total_paused_duration || 0) * 1000
  
  duration.value = Math.floor((now - startTime - pausedDuration) / 1000)
  
  // Update calculated amount
  if (currentTimer.value.billing_rate) {
    const hours = duration.value / 3600
    currentTimer.value.calculated_amount = hours * currentTimer.value.billing_rate.rate
  }
}

const startDurationUpdate = () => {
  if (updateInterval) clearInterval(updateInterval)
  updateInterval = setInterval(calculateDuration, 1000)
}

const stopDurationUpdate = () => {
  if (updateInterval) {
    clearInterval(updateInterval)
    updateInterval = null
  }
}

// Timer actions
const pauseTimer = async () => {
  try {
    await axios.post(`/api/timers/${currentTimer.value.id}/pause`)
    await fetchCurrentTimer()
  } catch (error) {
    console.error('Failed to pause timer:', error)
  }
}

const resumeTimer = async () => {
  try {
    await axios.post(`/api/timers/${currentTimer.value.id}/resume`)
    await fetchCurrentTimer()
  } catch (error) {
    console.error('Failed to resume timer:', error)
  }
}

const stopTimer = async () => {
  try {
    await axios.post(`/api/timers/${currentTimer.value.id}/stop`)
    currentTimer.value = null
    stopDurationUpdate()
  } catch (error) {
    console.error('Failed to stop timer:', error)
  }
}

// Lifecycle
onMounted(() => {
  fetchCurrentTimer()
  setInterval(fetchCurrentTimer, 60000) // Sync every minute in header
})

onUnmounted(() => {
  stopDurationUpdate()
})
</script>