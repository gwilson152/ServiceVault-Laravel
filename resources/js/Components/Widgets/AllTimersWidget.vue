<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { 
  ClockIcon, 
  UserIcon, 
  PlayIcon, 
  PauseIcon, 
  StopIcon,
  ChevronRightIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
  widgetId: {
    type: String,
    required: true
  },
  accountContext: {
    type: Object,
    default: () => ({})
  }
})

// State
const allTimers = ref([])
const loading = ref(true)
const error = ref(null)
const totalStats = ref({
  active_timers: 0,
  total_amount: 0,
  total_duration: 0
})

let refreshInterval = null

// Computed
const formattedTimers = computed(() => {
  return allTimers.value.map(timerData => {
    const timer = timerData.timer
    const user = timerData.user
    
    return {
      ...timer,
      user_name: user?.name || 'Unknown User',
      user_id: user?.id,
      formatted_duration: formatDuration(timerData.calculations?.duration_seconds || 0),
      running_amount: timerData.calculations?.running_amount || 0,
      hourly_rate: timerData.calculations?.hourly_rate || 0
    }
  })
})

const statusColor = computed(() => (status) => {
  switch (status) {
    case 'running':
      return 'text-green-600 bg-green-100'
    case 'paused':
      return 'text-yellow-600 bg-yellow-100'
    default:
      return 'text-gray-600 bg-gray-100'
  }
})

// Methods
const formatDuration = (seconds) => {
  if (!seconds) return '0m'
  const hours = Math.floor(seconds / 3600)
  const minutes = Math.floor((seconds % 3600) / 60)
  
  if (hours > 0) {
    return `${hours}h ${minutes}m`
  }
  return `${minutes}m`
}

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD'
  }).format(amount || 0)
}

const fetchAllTimers = async () => {
  try {
    loading.value = true
    error.value = null
    
    const response = await fetch('/api/admin/timers/all-active', {
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.head.querySelector('meta[name="csrf-token"]').content
      }
    })

    if (!response.ok) {
      throw new Error(`HTTP ${response.status}: ${response.statusText}`)
    }

    const data = await response.json()
    allTimers.value = data.data || []
    totalStats.value = data.totals || {
      active_timers: 0,
      total_amount: 0,
      total_duration: 0
    }
    
  } catch (err) {
    console.error('Failed to fetch all timers:', err)
    error.value = err.message
    allTimers.value = []
  } finally {
    loading.value = false
  }
}

const pauseTimer = async (timerId) => {
  try {
    await fetch(`/api/admin/timers/${timerId}/pause`, {
      method: 'POST',
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.head.querySelector('meta[name="csrf-token"]').content
      }
    })
    
    await fetchAllTimers() // Refresh data
  } catch (err) {
    console.error('Failed to pause timer:', err)
    alert('Failed to pause timer: ' + err.message)
  }
}

const resumeTimer = async (timerId) => {
  try {
    await fetch(`/api/admin/timers/${timerId}/resume`, {
      method: 'POST',
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.head.querySelector('meta[name="csrf-token"]').content
      }
    })
    
    await fetchAllTimers() // Refresh data
  } catch (err) {
    console.error('Failed to resume timer:', err)
    alert('Failed to resume timer: ' + err.message)
  }
}

const stopTimer = async (timerId) => {
  if (!confirm('Are you sure you want to stop this timer?')) return
  
  try {
    await fetch(`/api/admin/timers/${timerId}/stop`, {
      method: 'POST',
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.head.querySelector('meta[name="csrf-token"]').content
      }
    })
    
    await fetchAllTimers() // Refresh data
  } catch (err) {
    console.error('Failed to stop timer:', err)
    alert('Failed to stop timer: ' + err.message)
  }
}

const navigateToUser = (userId) => {
  // Navigate to user profile or management page
  window.open(`/admin/users/${userId}`, '_blank')
}

// Lifecycle
onMounted(async () => {
  await fetchAllTimers()
  
  // Set up auto-refresh (default 30 seconds)
  const refreshIntervalMs = (props.widgetConfig?.refreshInterval || 30) * 1000
  refreshInterval = setInterval(fetchAllTimers, refreshIntervalMs)
})

onUnmounted(() => {
  if (refreshInterval) {
    clearInterval(refreshInterval)
  }
})
</script>

<template>
  <div class="h-full flex flex-col">
    <!-- Widget Header -->
    <div class="flex items-center justify-between mb-4">
      <div class="flex items-center space-x-2">
        <ClockIcon class="w-5 h-5 text-blue-600" />
        <h3 class="text-lg font-semibold text-gray-900">All Active Timers</h3>
      </div>
      
      <!-- Stats Summary -->
      <div class="text-sm text-gray-600">
        {{ totalStats.active_timers }} active timers
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex-1 flex items-center justify-center">
      <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="flex-1 flex items-center justify-center text-center">
      <div>
        <div class="text-red-600 mb-2">⚠️ Failed to load timers</div>
        <div class="text-sm text-gray-500">{{ error }}</div>
        <button 
          @click="fetchAllTimers"
          class="mt-2 px-3 py-1 bg-blue-600 text-white rounded text-sm hover:bg-blue-700"
        >
          Retry
        </button>
      </div>
    </div>

    <!-- No Timers State -->
    <div v-else-if="formattedTimers.length === 0" class="flex-1 flex items-center justify-center text-center text-gray-500">
      <div>
        <ClockIcon class="w-12 h-12 mx-auto mb-3 text-gray-400" />
        <div class="text-lg font-medium">No Active Timers</div>
        <div class="text-sm">All users are currently offline</div>
      </div>
    </div>

    <!-- Timers List -->
    <div v-else class="flex-1 overflow-hidden">
      <!-- Totals Row -->
      <div class="bg-blue-50 rounded-lg p-3 mb-4">
        <div class="grid grid-cols-3 gap-4 text-center">
          <div>
            <div class="text-2xl font-bold text-blue-600">{{ totalStats.active_timers }}</div>
            <div class="text-xs text-blue-700">Active Timers</div>
          </div>
          <div>
            <div class="text-2xl font-bold text-green-600">{{ formatCurrency(totalStats.total_amount) }}</div>
            <div class="text-xs text-green-700">Total Value</div>
          </div>
          <div>
            <div class="text-2xl font-bold text-gray-600">{{ formatDuration(totalStats.total_duration) }}</div>
            <div class="text-xs text-gray-700">Total Time</div>
          </div>
        </div>
      </div>

      <!-- Timers List -->
      <div class="space-y-2 overflow-y-auto max-h-64">
        <div 
          v-for="timer in formattedTimers" 
          :key="timer.id"
          class="bg-white border border-gray-200 rounded-lg p-3 hover:shadow-md transition-shadow"
        >
          <!-- Timer Header -->
          <div class="flex items-center justify-between mb-2">
            <div class="flex items-center space-x-2">
              <!-- User Info -->
              <button
                @click="navigateToUser(timer.user_id)"
                class="flex items-center space-x-2 hover:text-blue-600 transition-colors"
              >
                <UserIcon class="w-4 h-4" />
                <span class="font-medium text-sm">{{ timer.user_name }}</span>
                <ChevronRightIcon class="w-3 h-3" />
              </button>
              
              <!-- Status Badge -->
              <span :class="`px-2 py-1 rounded-full text-xs font-medium ${statusColor(timer.status)}`">
                {{ timer.status }}
              </span>
            </div>
            
            <!-- Duration and Amount -->
            <div class="text-right text-sm">
              <div class="font-mono font-semibold">{{ timer.formatted_duration }}</div>
              <div v-if="timer.running_amount > 0" class="text-green-600 font-medium">
                {{ formatCurrency(timer.running_amount) }}
              </div>
            </div>
          </div>

          <!-- Timer Description -->
          <div class="mb-2">
            <p class="text-sm text-gray-700 truncate" :title="timer.description">
              {{ timer.description || 'No description' }}
            </p>
            <div v-if="timer.service_ticket" class="text-xs text-gray-500">
              Ticket: {{ timer.service_ticket.ticket_number }}
            </div>
          </div>

          <!-- Admin Controls -->
          <div class="flex space-x-2">
            <button
              v-if="timer.status === 'running'"
              @click="pauseTimer(timer.id)"
              class="flex items-center space-x-1 px-2 py-1 bg-yellow-600 text-white rounded text-xs hover:bg-yellow-700 transition-colors"
            >
              <PauseIcon class="w-3 h-3" />
              <span>Pause</span>
            </button>
            
            <button
              v-if="timer.status === 'paused'"
              @click="resumeTimer(timer.id)"
              class="flex items-center space-x-1 px-2 py-1 bg-green-600 text-white rounded text-xs hover:bg-green-700 transition-colors"
            >
              <PlayIcon class="w-3 h-3" />
              <span>Resume</span>
            </button>
            
            <button
              @click="stopTimer(timer.id)"
              class="flex items-center space-x-1 px-2 py-1 bg-red-600 text-white rounded text-xs hover:bg-red-700 transition-colors"
            >
              <StopIcon class="w-3 h-3" />
              <span>Stop</span>
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>