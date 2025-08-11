<template>
  <div class="ticket-timer-controls">
    <!-- Compact Mode for List View -->
    <div v-if="compact" class="flex items-center space-x-1">
      <!-- Debug info -->
      <!-- No Active Timer - Show Play Button -->
      <button
        v-if="!activeTimer"
        @click="startTimer"
        :disabled="loading"
        class="p-1.5 text-gray-500 hover:text-green-600 hover:bg-green-50 rounded transition-colors"
        title="Start Timer"
      >
        <PlayIcon class="w-4 h-4" />
      </button>
      
      <!-- Timer Running - Show Pause and Stop Buttons -->
      <template v-else-if="activeTimer && activeTimer.status === 'running' && userOwnsActiveTimer">
        <button
          @click="pauseTimer"
          :disabled="loading"
          class="p-1.5 text-gray-500 hover:text-yellow-600 hover:bg-yellow-50 rounded transition-colors"
          title="Pause Timer"
        >
          <PauseIcon class="w-4 h-4" />
        </button>
        <button
          @click="stopTimer"
          :disabled="loading"
          class="p-1.5 text-gray-500 hover:text-red-600 hover:bg-red-50 rounded transition-colors"
          title="Stop Timer"
        >
          <StopIcon class="w-4 h-4" />
        </button>
      </template>
      
      <!-- Timer Paused - Show Play and Stop Buttons -->
      <template v-else-if="activeTimer && activeTimer.status === 'paused' && userOwnsActiveTimer">
        <button
          @click="resumeTimer"
          :disabled="loading"
          class="p-1.5 text-gray-500 hover:text-green-600 hover:bg-green-50 rounded transition-colors"
          title="Resume Timer"
        >
          <PlayIcon class="w-4 h-4" />
        </button>
        <button
          @click="stopTimer"
          :disabled="loading"
          class="p-1.5 text-gray-500 hover:text-red-600 hover:bg-red-50 rounded transition-colors"
          title="Stop Timer"
        >
          <StopIcon class="w-4 h-4" />
        </button>
      </template>
      
      <!-- Other User's Timer - Show Status Indicator -->
      <div v-else-if="activeTimer && !userOwnsActiveTimer" class="flex items-center space-x-1">
        <div 
          :class="activeTimer.status === 'running' ? 'bg-green-500 animate-pulse' : 'bg-yellow-500'"
          class="w-2 h-2 rounded-full"
        ></div>
        <span class="text-xs text-gray-500">{{ activeTimer.user?.name || 'User' }}</span>
      </div>
    </div>
    
    <!-- Full Mode for Detail View -->
    <div v-else>
    <!-- Timer Status Display -->
    <div 
      v-if="activeTimer"
      class="bg-white border border-gray-200 rounded-lg p-3 shadow-sm"
    >
      <!-- Timer Header -->
      <div class="flex items-center justify-between mb-2">
        <div class="flex items-center space-x-2">
          <div 
            :class="timerStatusClasses"
            class="w-3 h-3 rounded-full flex-shrink-0"
          ></div>
          <span class="text-sm font-medium text-gray-900">
            {{ activeTimer.status === 'running' ? 'Timer Running' : 'Timer Paused' }}
          </span>
          <span v-if="activeTimer.user_id !== currentUser.id" class="text-xs text-gray-500">
            ({{ activeTimer.user?.name || 'Other User' }})
          </span>
        </div>
        
        <!-- Timer Duration and Amount -->
        <div class="text-right">
          <div class="text-lg font-mono font-bold text-gray-900">
            {{ formatDuration(currentDuration) }}
          </div>
          <div v-if="currentAmount > 0" class="text-sm text-green-600 font-medium">
            ${{ currentAmount.toFixed(2) }}
          </div>
        </div>
      </div>

      <!-- Timer Description -->
      <div v-if="activeTimer.description" class="mb-3">
        <p class="text-sm text-gray-700">{{ activeTimer.description }}</p>
      </div>

      <!-- Timer Controls (only for timer owner) -->
      <div 
        v-if="canControlTimer"
        class="flex space-x-2"
      >
        <button
          v-if="activeTimer.status === 'running'"
          @click="pauseTimer"
          :disabled="loading"
          class="flex items-center space-x-1 px-3 py-2 bg-yellow-500 hover:bg-yellow-600 disabled:opacity-50 text-white rounded-md text-sm font-medium transition-colors"
        >
          <PauseIcon class="w-4 h-4" />
          <span>Pause</span>
        </button>
        
        <button
          v-if="activeTimer.status === 'paused'"
          @click="resumeTimer"
          :disabled="loading"
          class="flex items-center space-x-1 px-3 py-2 bg-green-500 hover:bg-green-600 disabled:opacity-50 text-white rounded-md text-sm font-medium transition-colors"
        >
          <PlayIcon class="w-4 h-4" />
          <span>Resume</span>
        </button>
        
        <button
          @click="stopTimer"
          :disabled="loading"
          class="flex items-center space-x-1 px-3 py-2 bg-red-500 hover:bg-red-600 disabled:opacity-50 text-white rounded-md text-sm font-medium transition-colors"
        >
          <StopIcon class="w-4 h-4" />
          <span>Stop</span>
        </button>
        
        <button
          @click="commitTimer"
          :disabled="loading"
          class="flex items-center space-x-1 px-2 py-2 bg-blue-500 hover:bg-blue-600 disabled:opacity-50 text-white rounded-md text-sm font-medium transition-colors"
          title="Commit to Time Entry"
        >
          <CheckIcon class="w-4 h-4" />
        </button>
      </div>

      <!-- Read-only view for other users' timers -->
      <div v-else-if="activeTimer" class="text-xs text-gray-500">
        Timer owned by {{ activeTimer.user?.name || 'Another user' }}
      </div>
    </div>

    <!-- Start Timer Button (when no active timer) -->
    <div v-else class="space-y-2">
      <!-- Quick Start Button -->
      <button
        @click="startTimer"
        :disabled="loading"
        class="w-full flex items-center justify-center space-x-2 px-4 py-3 bg-green-500 hover:bg-green-600 disabled:opacity-50 text-white rounded-lg font-medium transition-colors"
      >
        <PlayIcon class="w-5 h-5" />
        <span>Start Timer for This Ticket</span>
      </button>

      <!-- Show existing user timers for this ticket (non-active) -->
      <div 
        v-if="pausedTimers.length > 0"
        class="space-y-2"
      >
        <div class="text-xs text-gray-500 font-medium">Paused Timers:</div>
        <div 
          v-for="timer in pausedTimers"
          :key="timer.id"
          class="bg-gray-50 border border-gray-200 rounded-md p-2"
        >
          <div class="flex items-center justify-between">
            <div class="flex-1">
              <div class="text-sm font-medium text-gray-900">
                {{ timer.description || 'No description' }}
              </div>
              <div class="text-xs text-gray-500">
                {{ formatDuration(timer.duration) }} elapsed
              </div>
            </div>
            
            <div class="flex space-x-1">
              <button
                @click="resumeSpecificTimer(timer.id)"
                :disabled="loading"
                class="px-2 py-1 bg-green-500 hover:bg-green-600 disabled:opacity-50 text-white rounded text-xs transition-colors"
              >
                Resume
              </button>
              <button
                @click="stopSpecificTimer(timer.id)"
                :disabled="loading"
                class="px-2 py-1 bg-red-500 hover:bg-red-600 disabled:opacity-50 text-white rounded text-xs transition-colors"
              >
                Stop
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Other Users' Active Timers (if any) -->
    <div 
      v-if="otherUsersTimers.length > 0"
      class="mt-4 space-y-2"
    >
      <div class="text-xs text-gray-500 font-medium">Other Active Timers:</div>
      <div 
        v-for="timer in otherUsersTimers"
        :key="timer.id"
        class="bg-blue-50 border border-blue-200 rounded-md p-2"
      >
        <div class="flex items-center justify-between">
          <div class="flex-1">
            <div class="text-sm font-medium text-blue-900">
              {{ timer.user?.name || 'Unknown User' }}
            </div>
            <div class="text-xs text-blue-700">
              {{ formatDuration(calculateDuration(timer)) }} - ${{ calculateAmount(timer).toFixed(2) }}
            </div>
          </div>
          <div 
            :class="timer.status === 'running' ? 'bg-green-500 animate-pulse' : 'bg-yellow-500'"
            class="w-2 h-2 rounded-full"
          ></div>
        </div>
      </div>
    </div>
    </div>

    <!-- CommitTimeEntryDialog -->
    <CommitTimeEntryDialog
      :show="showCommitDialog"
      :timerData="timerToCommit"
      :ticketData="ticket"
      :currentUser="currentUser"
      :availableTickets="[ticket]"
      :availableBillingRates="availableBillingRates"
      :assignableUsers="assignableUsers"
      @close="closeCommitDialog"
      @submitted="handleTimeEntryCommitted"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { 
  PlayIcon, 
  PauseIcon, 
  StopIcon, 
  CheckIcon 
} from '@heroicons/vue/24/outline'
import CommitTimeEntryDialog from '@/Components/CommitTimeEntryDialog.vue'

const props = defineProps({
  ticket: {
    type: Object,
    required: true
  },
  currentUser: {
    type: Object,
    required: true
  },
  availableBillingRates: {
    type: Array,
    default: () => []
  },
  assignableUsers: {
    type: Array,
    default: () => []
  },
  refreshInterval: {
    type: Number,
    default: 30000 // 30 seconds
  },
  compact: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['timerStarted', 'timerStopped', 'timeEntryCreated'])

// State
const allTimersForTicket = ref([])
const loading = ref(false)
const currentTime = ref(new Date())
const showCommitDialog = ref(false)
const timerToCommit = ref(null)

// Timer refresh interval
let refreshIntervalId = null
let timeUpdateIntervalId = null

// Computed properties
const activeTimer = computed(() => {
  return allTimersForTicket.value.find(timer => 
    ['running', 'paused'].includes(timer.status) && timer.user_id === props.currentUser.id
  )
})

const pausedTimers = computed(() => {
  return allTimersForTicket.value.filter(timer => 
    timer.status === 'paused' && timer.user_id === props.currentUser.id
  )
})

const otherUsersTimers = computed(() => {
  return allTimersForTicket.value.filter(timer => 
    ['running', 'paused'].includes(timer.status) && 
    timer.user_id !== props.currentUser.id
  )
})

const canControlTimer = computed(() => {
  return activeTimer.value && activeTimer.value.user_id === props.currentUser.id
})

const userOwnsActiveTimer = computed(() => {
  return activeTimer.value && activeTimer.value.user_id === props.currentUser.id
})

const timerStatusClasses = computed(() => ({
  'bg-green-500 animate-pulse': activeTimer.value?.status === 'running',
  'bg-yellow-500': activeTimer.value?.status === 'paused',
}))

const currentDuration = computed(() => {
  if (!activeTimer.value) return 0
  return calculateDuration(activeTimer.value)
})

const currentAmount = computed(() => {
  if (!activeTimer.value) return 0
  return calculateAmount(activeTimer.value)
})

// Methods
const formatDuration = (seconds) => {
  if (!seconds) return '0m'
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

const fetchTimersForTicket = async () => {
  try {
    const response = await fetch(`/api/tickets/${props.ticket.id}/timers/active`, {
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.head.querySelector('meta[name="csrf-token"]').content
      }
    })

    if (response.ok) {
      const data = await response.json()
      allTimersForTicket.value = data.data?.map(item => item.timer || item) || []
      console.log('Fetched timers for ticket:', props.ticket.id, allTimersForTicket.value)
    } else {
      console.error('Failed to fetch timers - HTTP', response.status)
    }
  } catch (error) {
    console.error('Failed to fetch timers for ticket:', error)
  }
}

const startTimer = async () => {
  loading.value = true
  try {
    const response = await fetch(`/api/tickets/${props.ticket.id}/timers/start`, {
      method: 'POST',
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.head.querySelector('meta[name="csrf-token"]').content
      },
      body: JSON.stringify({
        description: `Working on ticket ${props.ticket.ticket_number}`,
        billing_rate_id: props.availableBillingRates[0]?.id || null,
        device_id: generateDeviceId()
      })
    })

    if (response.ok) {
      const data = await response.json()
      emit('timerStarted', data.data)
      await fetchTimersForTicket()
    } else {
      const errorData = await response.json()
      if (errorData.existing_timer) {
        // Handle case where user already has an active timer
        alert(`${errorData.message}\n\nYou can manage your existing timer below.`)
        await fetchTimersForTicket() // Refresh to show the existing timer
      } else {
        throw new Error(errorData.message || 'Failed to start timer')
      }
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
      await fetchTimersForTicket()
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
      await fetchTimersForTicket()
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

const resumeSpecificTimer = async (timerId) => {
  loading.value = true
  try {
    const response = await fetch(`/api/timers/${timerId}/resume`, {
      method: 'POST',
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.head.querySelector('meta[name="csrf-token"]').content
      }
    })

    if (response.ok) {
      await fetchTimersForTicket()
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
      const data = await response.json()
      emit('timerStopped', data.data)
      await fetchTimersForTicket()
      
      // In compact mode, open time entry dialog immediately after stopping
      if (props.compact && data.data) {
        timerToCommit.value = {
          ...data.data,
          elapsed: data.data.duration || 0
        }
        showCommitDialog.value = true
      }
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

const stopSpecificTimer = async (timerId) => {
  loading.value = true
  try {
    const response = await fetch(`/api/timers/${timerId}/stop`, {
      method: 'POST',
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.head.querySelector('meta[name="csrf-token"]').content
      }
    })

    if (response.ok) {
      const data = await response.json()
      emit('timerStopped', data.data)
      await fetchTimersForTicket()
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

const handleTimeEntryCommitted = async (data) => {
  emit('timeEntryCreated', data.timeEntry)
  await fetchTimersForTicket()
}

// Utility functions
const generateDeviceId = () => {
  return 'ticket-' + Math.random().toString(36).substr(2, 9)
}

// Lifecycle
onMounted(async () => {
  // Only fetch timers if not in compact mode to avoid N+1 queries on list pages
  if (!props.compact) {
    await fetchTimersForTicket()
    
    // Set up periodic refresh
    refreshIntervalId = setInterval(fetchTimersForTicket, props.refreshInterval)
  }
  
  // Set up time update for duration calculations
  timeUpdateIntervalId = setInterval(() => {
    currentTime.value = new Date()
  }, 1000)
})

onUnmounted(() => {
  if (refreshIntervalId) {
    clearInterval(refreshIntervalId)
  }
  if (timeUpdateIntervalId) {
    clearInterval(timeUpdateIntervalId)
  }
})

// Watch for ticket changes
watch(() => props.ticket.id, async (newTicketId, oldTicketId) => {
  if (newTicketId !== oldTicketId) {
    await fetchTimersForTicket()
  }
})
</script>

<style scoped>
.ticket-timer-controls {
  /* Ensure proper spacing and layout */
}

/* Smooth transitions for all interactive elements */
button {
  transition: all 0.2s ease-in-out;
}

/* Loading state styling */
button:disabled {
  cursor: not-allowed;
}

/* Animation for timer status indicator */
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