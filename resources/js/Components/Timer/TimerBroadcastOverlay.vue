<template>
  <div 
    v-if="timers.length > 0" 
    class="fixed bottom-4 right-4 z-50"
  >
    <!-- Connection Status Indicator -->
    <div class="flex items-center justify-end mb-2">
      <div 
        :class="connectionStatusClasses"
        class="px-2 py-1 rounded-full text-xs font-medium flex items-center space-x-1"
      >
        <div 
          :class="connectionDotClasses"
          class="w-2 h-2 rounded-full"
        ></div>
        <span>{{ connectionStatusText }}</span>
      </div>
    </div>

    <!-- Timer Cards Container -->
    <div class="flex flex-row-reverse space-x-reverse space-x-2">
      <!-- Active Timers -->
      <div 
        v-for="timer in timers" 
        :key="timer.id"
        class="bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 p-4 min-w-80"
      >
      <!-- Timer Header -->
      <div class="flex items-center justify-between mb-2">
        <div class="flex items-center space-x-2">
          <div 
            :class="timerStatusClasses(timer.status)"
            class="w-3 h-3 rounded-full"
          ></div>
          <span class="text-sm font-medium text-gray-900 dark:text-gray-100">
            {{ timer.description || 'Timer' }}
          </span>
        </div>
        <button
          @click="deleteTimer(timer.id)"
          class="text-gray-400 hover:text-red-500 transition-colors"
          title="Delete Timer"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
          </svg>
        </button>
      </div>

      <!-- Timer Display -->
      <div class="text-center mb-3">
        <div class="text-2xl font-mono font-bold text-gray-900 dark:text-gray-100">
          {{ formatDuration(timer.duration || calculateDuration(timer)) }}
        </div>
        <div 
          v-if="timer.billing_rate"
          class="text-sm text-gray-600 dark:text-gray-400"
        >
          ${{ (calculateAmount(timer) || 0).toFixed(2) }} @ ${{ timer.billing_rate.rate }}/hr
        </div>
      </div>

      <!-- Timer Controls -->
      <div class="flex space-x-2">
        <button
          v-if="timer.status === 'running'"
          @click="pauseTimer(timer.id)"
          class="flex-1 px-3 py-2 bg-yellow-500 hover:bg-yellow-600 text-white rounded-md text-sm font-medium transition-colors"
        >
          Pause
        </button>
        <button
          v-if="timer.status === 'paused'"
          @click="resumeTimer(timer.id)"
          class="flex-1 px-3 py-2 bg-green-500 hover:bg-green-600 text-white rounded-md text-sm font-medium transition-colors"
        >
          Resume
        </button>
        <button
          @click="stopTimer(timer.id)"
          class="flex-1 px-3 py-2 bg-red-500 hover:bg-red-600 text-white rounded-md text-sm font-medium transition-colors"
        >
          Stop
        </button>
      </div>

      <!-- Project/Task Info -->
      <div 
        v-if="timer.project || timer.task"
        class="mt-2 text-xs text-gray-500 dark:text-gray-400"
      >
        <span v-if="timer.project">{{ timer.project.name }}</span>
        <span v-if="timer.project && timer.task"> • </span>
        <span v-if="timer.task">{{ timer.task.name }}</span>
      </div>
    </div>
    </div>

    <!-- Totals (if multiple timers) -->
    <div 
      v-if="timers.length > 1"
      class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-3 text-sm border border-blue-200 dark:border-blue-800"
    >
      <div class="flex justify-between items-center">
        <span class="font-medium text-blue-900 dark:text-blue-100">
          Total ({{ timers.length }} timers):
        </span>
        <div class="text-right">
          <div class="font-mono font-bold text-blue-900 dark:text-blue-100">
            {{ formatDuration(totalDuration) }}
          </div>
          <div class="text-blue-700 dark:text-blue-300">
            ${{ totalAmount.toFixed(2) }}
          </div>
        </div>
      </div>
    </div>

    <!-- Quick Start Timer Button -->
    <button
      @click="showQuickStart = !showQuickStart"
      class="w-full px-4 py-2 bg-green-500 hover:bg-green-600 text-white rounded-md text-sm font-medium transition-colors flex items-center justify-center space-x-2"
    >
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
      </svg>
      <span>Start New Timer</span>
    </button>

    <!-- Quick Start Form -->
    <div 
      v-if="showQuickStart"
      class="bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 p-4"
    >
      <input
        v-model="quickStartDescription"
        type="text"
        placeholder="Timer description..."
        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
        @keyup.enter="startQuickTimer"
      />
      <div class="flex space-x-2 mt-2">
        <button
          @click="startQuickTimer"
          class="flex-1 px-3 py-2 bg-green-500 hover:bg-green-600 text-white rounded-md text-sm font-medium transition-colors"
        >
          Start
        </button>
        <button
          @click="showQuickStart = false"
          class="px-3 py-2 bg-gray-500 hover:bg-gray-600 text-white rounded-md text-sm font-medium transition-colors"
        >
          Cancel
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref, onMounted, onUnmounted } from 'vue'
import { useTimerBroadcasting } from '@/Composables/useTimerBroadcasting.js'

const {
  timers,
  connectionStatus,
  lastSync,
  startTimer,
  stopTimer,
  pauseTimer,
  resumeTimer,
} = useTimerBroadcasting()

const showQuickStart = ref(false)
const quickStartDescription = ref('')
const currentTime = ref(new Date())

// Update current time every second for real-time calculations
let timeInterval
onMounted(() => {
  timeInterval = setInterval(() => {
    currentTime.value = new Date()
  }, 1000)
})

onUnmounted(() => {
  if (timeInterval) {
    clearInterval(timeInterval)
  }
})

// Computed properties
const connectionStatusClasses = computed(() => ({
  'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400': connectionStatus.value === 'connected',
  'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400': connectionStatus.value === 'connecting',
  'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400': connectionStatus.value === 'error',
  'bg-gray-100 text-gray-800 dark:bg-gray-900/20 dark:text-gray-400': connectionStatus.value === 'disconnected',
}))

const connectionDotClasses = computed(() => ({
  'bg-green-500 animate-pulse': connectionStatus.value === 'connected',
  'bg-yellow-500 animate-spin': connectionStatus.value === 'connecting',
  'bg-red-500': connectionStatus.value === 'error',
  'bg-gray-500': connectionStatus.value === 'disconnected',
}))

const connectionStatusText = computed(() => {
  switch (connectionStatus.value) {
    case 'connected': return 'Live'
    case 'connecting': return 'Connecting...'
    case 'error': return 'Connection Error'
    default: return 'Offline'
  }
})

const totalDuration = computed(() => {
  return timers.value.reduce((total, timer) => {
    return total + (timer.duration || calculateDuration(timer))
  }, 0)
})

const totalAmount = computed(() => {
  return timers.value.reduce((total, timer) => {
    return total + (calculateAmount(timer) || 0)
  }, 0)
})

// Methods
const timerStatusClasses = (status) => ({
  'bg-green-500 animate-pulse': status === 'running',
  'bg-yellow-500': status === 'paused',
  'bg-gray-500': status === 'stopped',
})

const calculateDuration = (timer) => {
  if (timer.status !== 'running') return timer.duration || 0
  
  const startedAt = new Date(timer.started_at)
  const now = currentTime.value
  const totalPaused = timer.total_paused_duration || 0
  
  return Math.max(0, Math.floor((now - startedAt) / 1000) - totalPaused)
}

const calculateAmount = (timer) => {
  if (!timer.billing_rate) return 0
  
  const duration = timer.duration || calculateDuration(timer)
  const hours = duration / 3600
  return hours * timer.billing_rate.rate
}

const formatDuration = (seconds) => {
  const hours = Math.floor(seconds / 3600)
  const minutes = Math.floor((seconds % 3600) / 60)
  const secs = seconds % 60
  
  if (hours > 0) {
    return `${hours}:${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`
  } else {
    return `${minutes}:${secs.toString().padStart(2, '0')}`
  }
}

const startQuickTimer = async () => {
  if (!quickStartDescription.value.trim()) return
  
  try {
    await startTimer({
      description: quickStartDescription.value,
      device_id: generateDeviceId(),
    })
    quickStartDescription.value = ''
    showQuickStart.value = false
  } catch (error) {
    alert('Failed to start timer: ' + error.message)
  }
}

const deleteTimer = async (timerId) => {
  if (!confirm('Are you sure you want to delete this timer?')) return
  
  try {
    await window.axios.delete(`/api/timers/${timerId}?force=true`)
  } catch (error) {
    console.error('Failed to delete timer:', error)
    alert('Failed to delete timer: ' + error.message)
  }
}

const generateDeviceId = () => {
  return 'web-' + Math.random().toString(36).substr(2, 9)
}
</script>