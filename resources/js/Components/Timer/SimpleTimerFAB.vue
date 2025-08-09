<template>
  <!-- Simple Timer FAB for all users -->
  <div class="fixed bottom-4 right-4 z-50">
    <!-- Active Timer Display -->
    <div 
      v-if="activeTimer"
      class="bg-white rounded-lg shadow-lg border border-gray-200 p-4 mb-4 min-w-64"
    >
      <!-- Timer Header -->
      <div class="flex items-center justify-between mb-2">
        <div class="flex items-center space-x-2">
          <div 
            :class="activeTimer.status === 'running' ? 'bg-green-500 animate-pulse' : 'bg-yellow-500'"
            class="w-3 h-3 rounded-full"
          ></div>
          <span class="text-sm font-medium text-gray-900">
            {{ activeTimer.status === 'running' ? 'Timer Running' : 'Timer Paused' }}
          </span>
        </div>
        <button
          @click="expanded = !expanded"
          class="text-gray-400 hover:text-gray-600"
        >
          <ChevronUpIcon v-if="expanded" class="w-4 h-4" />
          <ChevronDownIcon v-else class="w-4 h-4" />
        </button>
      </div>

      <!-- Timer Display -->
      <div class="text-center mb-3">
        <div class="text-2xl font-mono font-bold text-gray-900">
          {{ formatDuration(currentDuration) }}
        </div>
        <div v-if="activeTimer.billing_rate" class="text-sm text-green-600 font-medium">
          ${{ calculateAmount(activeTimer).toFixed(2) }}
        </div>
      </div>

      <!-- Timer Controls -->
      <div class="flex space-x-2">
        <button
          v-if="activeTimer.status === 'running'"
          @click="pauseTimer"
          :disabled="loading"
          class="flex-1 px-3 py-2 bg-yellow-500 hover:bg-yellow-600 disabled:opacity-50 text-white rounded-md text-sm font-medium transition-colors"
        >
          <PauseIcon class="w-4 h-4 mr-1 inline" />
          Pause
        </button>
        
        <button
          v-else
          @click="resumeTimer"
          :disabled="loading"
          class="flex-1 px-3 py-2 bg-green-500 hover:bg-green-600 disabled:opacity-50 text-white rounded-md text-sm font-medium transition-colors"
        >
          <PlayIcon class="w-4 h-4 mr-1 inline" />
          Resume
        </button>
        
        <button
          @click="stopTimer"
          :disabled="loading"
          class="flex-1 px-3 py-2 bg-red-500 hover:bg-red-600 disabled:opacity-50 text-white rounded-md text-sm font-medium transition-colors"
        >
          <StopIcon class="w-4 h-4 mr-1 inline" />
          Stop
        </button>
      </div>

      <!-- Expanded Options -->
      <div v-if="expanded" class="mt-3 space-y-2">
        <input
          v-model="activeTimer.description"
          @blur="updateTimerDescription"
          type="text"
          placeholder="Timer description..."
          class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-green-500"
        />
        
        <div class="flex space-x-2">
          <button
            @click="commitTimer"
            :disabled="loading"
            class="flex-1 px-3 py-2 bg-blue-500 hover:bg-blue-600 disabled:opacity-50 text-white rounded-md text-sm font-medium transition-colors"
          >
            <CheckIcon class="w-4 h-4 mr-1 inline" />
            Commit to Time Entry
          </button>
        </div>
      </div>
    </div>

    <!-- Start Timer FAB -->
    <button
      v-else
      @click="startTimer"
      :disabled="loading"
      class="bg-green-500 hover:bg-green-600 disabled:opacity-50 text-white rounded-full p-4 shadow-lg transition-colors"
    >
      <PlayIcon class="w-6 h-6" />
    </button>

    <!-- CommitTimeEntryDialog -->
    <CommitTimeEntryDialog
      :show="showCommitDialog"
      :timerData="timerToCommit"
      :currentUser="currentUser"
      :availableTickets="[]"
      :availableBillingRates="[]"
      :assignableUsers="[]"
      @close="closeCommitDialog"
      @submitted="handleTimeEntryCommitted"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { usePage } from '@inertiajs/vue3'
import {
  PlayIcon,
  PauseIcon,
  StopIcon,
  CheckIcon,
  ChevronUpIcon,
  ChevronDownIcon
} from '@heroicons/vue/24/outline'
import CommitTimeEntryDialog from '@/Components/CommitTimeEntryDialog.vue'

// State
const page = usePage()
const activeTimer = ref(null)
const loading = ref(false)
const currentTime = ref(new Date())
const expanded = ref(false)
const showCommitDialog = ref(false)
const timerToCommit = ref(null)

// Intervals
let updateInterval = null
let timeInterval = null

// Computed
const currentUser = computed(() => page.props.auth?.user)

const currentDuration = computed(() => {
  if (!activeTimer.value) return 0
  return calculateDuration(activeTimer.value)
})

// Methods
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

const calculateDuration = (timer) => {
  if (timer.status !== 'running') return timer.duration || 0
  
  const startedAt = new Date(timer.started_at)
  const now = currentTime.value
  const totalPaused = timer.total_paused_duration || 0
  
  return Math.max(0, Math.floor((now - startedAt) / 1000) - totalPaused)
}

const calculateAmount = (timer) => {
  if (!timer.billing_rate) return 0
  
  const duration = calculateDuration(timer)
  const hours = duration / 3600
  return hours * timer.billing_rate.rate
}

const fetchCurrentTimer = async () => {
  try {
    const response = await fetch('/api/timers/active/current', {
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.head.querySelector('meta[name="csrf-token"]').content
      }
    })

    if (response.ok) {
      const data = await response.json()
      if (data.data && data.data.length > 0) {
        // Get the first active timer
        activeTimer.value = Array.isArray(data.data) ? data.data[0] : data.data
      } else {
        activeTimer.value = null
      }
    }
  } catch (error) {
    console.error('Failed to fetch current timer:', error)
  }
}

const startTimer = async () => {
  loading.value = true
  try {
    const response = await fetch('/api/timers', {
      method: 'POST',
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.head.querySelector('meta[name="csrf-token"]').content
      },
      body: JSON.stringify({
        description: 'New timer',
        device_id: generateDeviceId(),
        stop_others: false
      })
    })

    if (response.ok) {
      await fetchCurrentTimer()
    } else {
      throw new Error('Failed to start timer')
    }
  } catch (error) {
    console.error('Failed to start timer:', error)
    alert('Failed to start timer: ' + error.message)
  } finally {
    loading.value = false
  }
}

const pauseTimer = async () => {
  if (!activeTimer.value) return
  
  loading.value = true
  try {
    const response = await fetch(`/api/timers/${activeTimer.value.id}/pause`, {
      method: 'POST',
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.head.querySelector('meta[name="csrf-token"]').content
      }
    })

    if (response.ok) {
      await fetchCurrentTimer()
    } else {
      throw new Error('Failed to pause timer')
    }
  } catch (error) {
    console.error('Failed to pause timer:', error)
    alert('Failed to pause timer: ' + error.message)
  } finally {
    loading.value = false
  }
}

const resumeTimer = async () => {
  if (!activeTimer.value) return
  
  loading.value = true
  try {
    const response = await fetch(`/api/timers/${activeTimer.value.id}/resume`, {
      method: 'POST',
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.head.querySelector('meta[name="csrf-token"]').content
      }
    })

    if (response.ok) {
      await fetchCurrentTimer()
    } else {
      throw new Error('Failed to resume timer')
    }
  } catch (error) {
    console.error('Failed to resume timer:', error)
    alert('Failed to resume timer: ' + error.message)
  } finally {
    loading.value = false
  }
}

const stopTimer = async () => {
  if (!activeTimer.value) return
  
  loading.value = true
  try {
    const response = await fetch(`/api/timers/${activeTimer.value.id}/stop`, {
      method: 'POST',
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.head.querySelector('meta[name="csrf-token"]').content
      }
    })

    if (response.ok) {
      activeTimer.value = null
    } else {
      throw new Error('Failed to stop timer')
    }
  } catch (error) {
    console.error('Failed to stop timer:', error)
    alert('Failed to stop timer: ' + error.message)
  } finally {
    loading.value = false
  }
}

const updateTimerDescription = async () => {
  if (!activeTimer.value) return
  
  try {
    await fetch(`/api/timers/${activeTimer.value.id}`, {
      method: 'PUT',
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.head.querySelector('meta[name="csrf-token"]').content
      },
      body: JSON.stringify({
        description: activeTimer.value.description
      })
    })
  } catch (error) {
    console.error('Failed to update timer description:', error)
  }
}

const commitTimer = () => {
  if (!activeTimer.value) return
  
  timerToCommit.value = {
    ...activeTimer.value,
    elapsed: currentDuration.value
  }
  showCommitDialog.value = true
}

const closeCommitDialog = () => {
  showCommitDialog.value = false
  timerToCommit.value = null
}

const handleTimeEntryCommitted = async () => {
  activeTimer.value = null
  await fetchCurrentTimer()
}

const generateDeviceId = () => {
  return 'simple-timer-' + Math.random().toString(36).substr(2, 9)
}

// Lifecycle
onMounted(async () => {
  await fetchCurrentTimer()
  
  // Set up periodic refresh
  updateInterval = setInterval(fetchCurrentTimer, 30000)
  
  // Set up time update
  timeInterval = setInterval(() => {
    currentTime.value = new Date()
  }, 1000)
})

onUnmounted(() => {
  if (updateInterval) {
    clearInterval(updateInterval)
  }
  if (timeInterval) {
    clearInterval(timeInterval)
  }
})
</script>

<style scoped>
/* Smooth transitions */
.transition-colors {
  transition-property: background-color, border-color, color, fill, stroke;
  transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
  transition-duration: 150ms;
}

/* Pulse animation */
@keyframes pulse {
  0%, 100% {
    opacity: 1;
  }
  50% {
    opacity: 0.5;
  }
}

.animate-pulse {
  animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}
</style>