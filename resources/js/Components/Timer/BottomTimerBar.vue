<template>
  <!-- Bottom Timer Bar - Fixed at bottom of screen -->
  <div class="fixed bottom-0 left-0 right-0 z-40 bg-white border-t border-gray-200 shadow-lg">
    <div class="max-w-full overflow-x-auto">
      <div class="flex items-center justify-between px-4 py-2 min-w-fit">
        
        <!-- Active Timer Badges -->
        <div class="flex items-center space-x-2 flex-1 min-w-0">
          <template v-if="activeTimers.length > 0">
            <TransitionGroup name="timer-badge" tag="div" class="flex items-center space-x-2">
              <div
                v-for="timer in activeTimers"
                :key="timer.id"
                @click="toggleTimerDetails(timer.id)"
                class="bg-gradient-to-r from-blue-500 to-blue-600 text-white px-3 py-2 rounded-full cursor-pointer hover:from-blue-600 hover:to-blue-700 transition-all duration-200 flex items-center space-x-2 shadow-md hover:shadow-lg"
              >
                <!-- Status Indicator -->
                <div 
                  :class="getStatusIndicatorClass(timer.status)"
                  class="w-2 h-2 rounded-full flex-shrink-0"
                />
                
                <!-- Timer Info -->
                <div class="flex items-center space-x-2 text-sm font-medium">
                  <span class="font-mono">{{ formatDuration(getTimerDuration(timer)) }}</span>
                  <span v-if="timer.ticket" class="opacity-90">
                    #{{ timer.ticket.ticket_number }}
                  </span>
                  <span v-else-if="timer.description" class="opacity-90 max-w-24 truncate">
                    {{ timer.description }}
                  </span>
                </div>
                
                <!-- Amount (if calculated) -->
                <span v-if="getTimerAmount(timer)" class="text-xs font-semibold bg-white/20 px-2 py-0.5 rounded-full">
                  ${{ getTimerAmount(timer) }}
                </span>
              </div>
            </TransitionGroup>
          </template>
          
          <!-- No Active Timers Message -->
          <div v-else class="text-gray-500 text-sm">
            No active timers
          </div>
        </div>
        
        <!-- Action Buttons -->
        <div class="flex items-center space-x-2 ml-4">
          <!-- Quick Actions for Multiple Timers -->
          <div v-if="activeTimers.length > 1" class="flex items-center space-x-1">
            <button
              @click="pauseAllTimers"
              :disabled="loading"
              class="p-2 text-gray-600 hover:text-orange-600 hover:bg-orange-50 rounded-full transition-colors duration-200 disabled:opacity-50"
              title="Pause All Timers"
            >
              <PauseIcon class="w-4 h-4" />
            </button>
            <button
              @click="stopAllTimers"
              :disabled="loading"
              class="p-2 text-gray-600 hover:text-red-600 hover:bg-red-50 rounded-full transition-colors duration-200 disabled:opacity-50"
              title="Stop All Timers"
            >
              <StopIcon class="w-4 h-4" />
            </button>
            <div class="w-px h-6 bg-gray-300 mx-2" />
          </div>
          
          <!-- Start New Timer Button -->
          <button
            @click="showStartTimerModal = true"
            :disabled="loading"
            class="bg-green-500 hover:bg-green-600 text-white p-2 rounded-full shadow-md hover:shadow-lg transition-all duration-200 disabled:opacity-50"
            title="Start New Timer"
          >
            <PlayIcon class="w-5 h-5" />
          </button>
        </div>
      </div>
    </div>
    
    <!-- Expanded Timer Details -->
    <TimerDetailsExpanded
      v-if="expandedTimerId"
      :timer="getTimerById(expandedTimerId)"
      @close="expandedTimerId = null"
      @update="fetchActiveTimers"
    />
  </div>
  
  <!-- Start Timer Modal -->
  <StartTimerModal
    v-if="showStartTimerModal"
    @close="showStartTimerModal = false"
    @started="onTimerStarted"
  />
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { PlayIcon, PauseIcon, StopIcon } from '@heroicons/vue/24/solid'
import TimerDetailsExpanded from './TimerDetailsExpanded.vue'
import StartTimerModal from './StartTimerModal.vue'

// Reactive state
const activeTimers = ref([])
const expandedTimerId = ref(null)
const showStartTimerModal = ref(false)
const loading = ref(false)
const durations = ref({}) // Store calculated durations per timer
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

// Status indicator classes
const getStatusIndicatorClass = (status) => {
  switch (status) {
    case 'running':
      return 'bg-green-400 animate-pulse'
    case 'paused':
      return 'bg-yellow-400'
    default:
      return 'bg-gray-400'
  }
}

// Get timer duration (live calculated for running timers)
const getTimerDuration = (timer) => {
  if (timer.status === 'running') {
    return durations.value[timer.id] || 0
  }
  return timer.duration || 0
}

// Get timer amount if available
const getTimerAmount = (timer) => {
  if (!timer.billing_rate) return null
  const hours = getTimerDuration(timer) / 3600
  const amount = hours * timer.billing_rate.rate
  return amount > 0 ? amount.toFixed(2) : null
}

// Get timer by ID
const getTimerById = (timerId) => {
  return activeTimers.value.find(timer => timer.id === timerId)
}

// Toggle timer details expansion
const toggleTimerDetails = (timerId) => {
  expandedTimerId.value = expandedTimerId.value === timerId ? null : timerId
}

// Calculate live durations for running timers
const calculateDurations = () => {
  const now = Date.now()
  
  activeTimers.value.forEach(timer => {
    if (timer.status === 'running') {
      const startTime = new Date(timer.started_at).getTime()
      const pausedDuration = (timer.total_paused_duration || 0) * 1000
      durations.value[timer.id] = Math.floor((now - startTime - pausedDuration) / 1000)
    } else {
      durations.value[timer.id] = timer.duration || 0
    }
  })
}

// API calls
const fetchActiveTimers = async () => {
  try {
    const response = await axios.get('/api/timers/active/current')
    if (response.data.data && Array.isArray(response.data.data)) {
      activeTimers.value = response.data.data
    } else if (response.data.data) {
      // Single timer response
      activeTimers.value = [response.data.data]
    } else {
      activeTimers.value = []
    }
    calculateDurations()
  } catch (error) {
    console.error('Failed to fetch active timers:', error)
    activeTimers.value = []
  }
}

const pauseAllTimers = async () => {
  if (loading.value) return
  
  loading.value = true
  try {
    const runningTimers = activeTimers.value.filter(timer => timer.status === 'running')
    await Promise.all(
      runningTimers.map(timer => 
        axios.post(`/api/timers/${timer.id}/pause`)
      )
    )
    await fetchActiveTimers()
  } catch (error) {
    console.error('Failed to pause all timers:', error)
  } finally {
    loading.value = false
  }
}

const stopAllTimers = async () => {
  if (loading.value) return
  if (!confirm('Are you sure you want to stop all active timers?')) return
  
  loading.value = true
  try {
    await Promise.all(
      activeTimers.value.map(timer => 
        axios.post(`/api/timers/${timer.id}/stop`)
      )
    )
    await fetchActiveTimers()
    expandedTimerId.value = null
  } catch (error) {
    console.error('Failed to stop all timers:', error)
  } finally {
    loading.value = false
  }
}

const onTimerStarted = (newTimer) => {
  showStartTimerModal.value = false
  fetchActiveTimers()
}

// Lifecycle management
onMounted(() => {
  fetchActiveTimers()
  
  // Update durations every second
  updateInterval = setInterval(() => {
    calculateDurations()
  }, 1000)
  
  // Sync with server every 30 seconds
  setInterval(fetchActiveTimers, 30000)
})

onUnmounted(() => {
  if (updateInterval) {
    clearInterval(updateInterval)
  }
})
</script>

<style scoped>
/* Timer badge animations */
.timer-badge-enter-active,
.timer-badge-leave-active {
  transition: all 0.3s ease;
}

.timer-badge-enter-from {
  opacity: 0;
  transform: translateX(-20px) scale(0.8);
}

.timer-badge-leave-to {
  opacity: 0;
  transform: translateX(20px) scale(0.8);
}

.timer-badge-move {
  transition: transform 0.3s ease;
}
</style>