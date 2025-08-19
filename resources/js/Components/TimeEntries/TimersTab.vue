<template>
  <div class="space-y-6">
    <!-- Statistics Cards -->
    <div v-if="stats" class="grid grid-cols-1 md:grid-cols-3 gap-6">
      <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <svg class="h-6 w-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
            <div class="ml-5 w-0 flex-1">
              <dl>
                <dt class="text-sm font-medium text-gray-500 truncate">Active Timers</dt>
                <dd class="text-lg font-medium text-gray-900">{{ stats.active_timers || 0 }}</dd>
              </dl>
            </div>
          </div>
        </div>
      </div>

      <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <svg class="h-6 w-6 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
              </svg>
            </div>
            <div class="ml-5 w-0 flex-1">
              <dl>
                <dt class="text-sm font-medium text-gray-500 truncate">Total Value</dt>
                <dd class="text-lg font-medium text-gray-900">${{ formatCurrency(stats.total_amount || 0) }}</dd>
              </dl>
            </div>
          </div>
        </div>
      </div>

      <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <svg class="h-6 w-6 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
              </svg>
            </div>
            <div class="ml-5 w-0 flex-1">
              <dl>
                <dt class="text-sm font-medium text-gray-500 truncate">Total Time</dt>
                <dd class="text-lg font-medium text-gray-900">{{ formatDuration(stats.total_duration || 0) }}</dd>
              </dl>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Timers List -->
    <div class="bg-white shadow overflow-hidden sm:rounded-md">
      <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
        <div class="flex items-center justify-between">
          <div>
            <h3 class="text-lg leading-6 font-medium text-gray-900">
              {{ canViewAllTimers ? 'All Active Timers' : 'My Active Timers' }}
            </h3>
            <p class="mt-1 max-w-2xl text-sm text-gray-500">
              {{ timers.length }} active timer{{ timers.length === 1 ? '' : 's' }}
            </p>
          </div>
          <div class="flex space-x-3">
            <button
              @click="refreshTimers"
              class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
            >
              <svg class="-ml-1 mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
              </svg>
              Refresh
            </button>
          </div>
        </div>
      </div>
      
      <div v-if="loading" class="flex justify-center py-12">
        <svg class="animate-spin h-8 w-8 text-indigo-600" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
      </div>
      
      <ul v-else-if="timers.length > 0" class="divide-y divide-gray-200">
        <li v-for="timer in timers" :key="timer.id" class="px-4 py-4 sm:px-6 hover:bg-gray-50">
          <div class="flex items-center justify-between">
            <div class="flex items-center min-w-0 flex-1">
              <div class="min-w-0 flex-1">
                <div class="flex items-center">
                  <p class="text-sm font-medium text-gray-900 truncate">
                    {{ timer.description || 'No description' }}
                  </p>
                  <span 
                    :class="[
                      'ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                      timer.status === 'running' ? 'bg-green-100 text-green-800' :
                      timer.status === 'paused' ? 'bg-yellow-100 text-yellow-800' :
                      'bg-gray-100 text-gray-800'
                    ]"
                  >
                    {{ timer.status }}
                  </span>
                  <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800">
                    {{ formatDuration(timer.duration_seconds) }}
                  </span>
                </div>
                <div class="mt-1 flex items-center text-sm text-gray-500">
                  <p>Started: {{ formatDate(timer.started_at) }}</p>
                  <span v-if="timer.ticket" class="mx-2">•</span>
                  <p v-if="timer.ticket">Ticket: #{{ timer.ticket.ticket_number }}</p>
                  <span v-if="timer.user_name && canViewAllTimers" class="mx-2">•</span>
                  <p v-if="timer.user_name && canViewAllTimers">{{ timer.user_name }}</p>
                  <span v-if="timer.account" class="mx-2">•</span>
                  <p v-if="timer.account">{{ timer.account.name }}</p>
                  <span v-if="timer.running_amount > 0" class="mx-2">•</span>
                  <p v-if="timer.running_amount > 0" class="font-medium text-green-600">${{ formatCurrency(timer.running_amount) }}</p>
                </div>
              </div>
            </div>
            <div class="flex items-center space-x-2">
              <button
                v-if="timer.can_pause"
                @click="pauseTimer(timer.id)"
                class="text-yellow-600 hover:text-yellow-900 text-sm font-medium"
              >
                Pause
              </button>
              <button
                v-if="timer.can_resume"
                @click="resumeTimer(timer.id)"
                class="text-green-600 hover:text-green-900 text-sm font-medium"
              >
                Resume
              </button>
              <button
                v-if="timer.can_cancel"
                @click="cancelTimer(timer.id)"
                class="text-orange-600 hover:text-orange-900 text-sm font-medium"
              >
                Cancel
              </button>
              <button
                v-if="timer.can_control"
                @click="stopTimer(timer.id)"
                class="text-red-600 hover:text-red-900 text-sm font-medium"
              >
                Stop
              </button>
              <button
                v-if="timer.can_commit"
                @click="commitTimer(timer.id)"
                class="text-indigo-600 hover:text-indigo-900 text-sm font-medium"
              >
                Commit
              </button>
            </div>
          </div>
        </li>
      </ul>
      
      <div v-else class="text-center py-12">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <h3 class="mt-2 text-sm font-medium text-gray-900">No active timers</h3>
        <p class="mt-1 text-sm text-gray-500">
          {{ canViewAllTimers ? 'No users have active timers right now.' : 'You don\'t have any active timers.' }}
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { useTimersQuery } from '@/Composables/queries/useTimersQuery.js'

const page = usePage()
const user = computed(() => page.props.auth?.user)

// Initialize TanStack Query
const {
  activeTimers,
  loadingActiveTimers,
  pauseTimer: pauseTimerMutation,
  resumeTimer: resumeTimerMutation,
  stopTimer: stopTimerMutation,
  commitTimer: commitTimerMutation,
  deleteTimer: deleteTimerMutation,
  refetchActiveTimers
} = useTimersQuery()

// Reactive state for backwards compatibility
const loading = computed(() => loadingActiveTimers.value)
const timers = computed(() => activeTimers.value || [])

// Calculate stats from active timers
const stats = computed(() => {
  const timerList = timers.value || []
  return {
    active_timers: timerList.length,
    total_amount: timerList.reduce((total, timer) => {
      return total + (timer.current_amount || timer.running_amount || 0)
    }, 0),
    total_duration: timerList.reduce((total, timer) => {
      return total + (timer.duration_seconds || timer.duration || 0)
    }, 0)
  }
})

let refreshInterval = null

// Computed properties for ABAC permissions
const isAdmin = computed(() => {
  return user.value?.permissions?.includes('admin.read') || user.value?.permissions?.includes('admin.manage')
})

const canViewMyTimers = computed(() => {
  // Users can always view their own timers
  return user.value?.permissions?.includes('timers.read') || 
         user.value?.permissions?.includes('timers.write')
})

const canViewAllTimers = computed(() => {
  // Admins and managers can view all timers
  return isAdmin.value || 
         user.value?.permissions?.includes('timers.admin') ||
         user.value?.permissions?.includes('teams.manage')
})

const canManageTimers = computed(() => {
  // Admins can manage any timer, users can manage their own
  return isAdmin.value || user.value?.permissions?.includes('timers.admin')
})

const canControlTimers = computed(() => {
  // Users can control (pause/resume/stop) their own timers
  return user.value?.permissions?.includes('timers.write') ||
         user.value?.permissions?.includes('timers.admin')
})

const canCommitTimers = computed(() => {
  // Users can commit their own timers to time entries
  return user.value?.permissions?.includes('timers.write') ||
         user.value?.permissions?.includes('timers.admin')
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
  if (!amount) return '0.00'
  return (amount / 100).toFixed(2)
}

const formatDate = (dateString) => {
  if (!dateString) return ''
  const date = new Date(dateString)
  return date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
}

const loadTimers = async () => {
  loading.value = true
  try {
    // Use the new enhanced endpoint with permission-based controls
    const endpoint = '/api/timers/active-with-controls'
    
    const response = await axios.get(endpoint)
    
    // Handle the new enhanced response format
    if (response.data.data) {
      timers.value = response.data.data.map(timer => ({
        ...timer,
        // Map permissions to old format for compatibility
        can_control: timer.permissions?.can_control || false,
        can_pause: timer.permissions?.can_pause || false,
        can_resume: timer.permissions?.can_resume || false,
        can_cancel: timer.permissions?.can_cancel || false,
        can_commit: timer.permissions?.can_commit || false,
        can_edit: timer.permissions?.can_edit || false
      }))
      
      // Set stats from the new stats format
      if (response.data.stats) {
        stats.value = {
          active_timers: response.data.stats.active_timers || 0,
          total_amount: response.data.stats.total_amount || 0,
          total_duration: response.data.stats.total_duration || 0
        }
      }
      
      // Store user permissions for UI display
      if (response.data.user_permissions) {
        canViewAllTimers.value = response.data.user_permissions.can_view_all_timers
        canManageTimers.value = response.data.user_permissions.can_manage_timers
      }
    } else {
      timers.value = []
      stats.value = {
        active_timers: 0,
        total_amount: 0,
        total_duration: 0
      }
    }
    
  } catch (error) {
    console.error('Error loading timers:', error)
    timers.value = []
    stats.value = { active_timers: 0, total_amount: 0, total_duration: 0 }
  } finally {
    loading.value = false
  }
}

const refreshTimers = () => {
  loadTimers()
}

const pauseTimer = async (timerId) => {
  try {
    // Find the timer to check permissions
    const timer = timers.value.find(t => t.id === timerId)
    const isOwn = timer?.user_id === user.value?.id
    
    const endpoint = isOwn ? 
      `/api/timers/${timerId}/pause` : 
      `/api/admin/timers/${timerId}/pause`
      
    await axios.post(endpoint)
    await loadTimers() // Refresh data
  } catch (error) {
    console.error('Error pausing timer:', error)
    alert('Failed to pause timer: ' + (error.response?.data?.message || error.message))
  }
}

const resumeTimer = async (timerId) => {
  try {
    // Find the timer to check permissions
    const timer = timers.value.find(t => t.id === timerId)
    const isOwn = timer?.user_id === user.value?.id
    
    const endpoint = isOwn ? 
      `/api/timers/${timerId}/resume` : 
      `/api/admin/timers/${timerId}/resume`
      
    await axios.post(endpoint)
    await loadTimers() // Refresh data
  } catch (error) {
    console.error('Error resuming timer:', error)
    alert('Failed to resume timer: ' + (error.response?.data?.message || error.message))
  }
}

const stopTimer = async (timerId) => {
  if (!confirm('Are you sure you want to stop this timer?')) return
  
  try {
    // Find the timer to check permissions
    const timer = timers.value.find(t => t.id === timerId)
    const isOwn = timer?.user_id === user.value?.id
    
    const endpoint = isOwn ? 
      `/api/timers/${timerId}/stop` : 
      `/api/admin/timers/${timerId}/stop`
      
    await axios.post(endpoint)
    await loadTimers() // Refresh data
  } catch (error) {
    console.error('Error stopping timer:', error)
    alert('Failed to stop timer: ' + (error.response?.data?.message || error.message))
  }
}

const cancelTimer = async (timerId) => {
  if (!confirm('Are you sure you want to cancel this timer? This will mark it as canceled without creating a time entry.')) return
  
  try {
    // Find the timer to check permissions
    const timer = timers.value.find(t => t.id === timerId)
    const isOwn = timer?.user_id === user.value?.id
    
    const endpoint = isOwn ? 
      `/api/timers/${timerId}/cancel` : 
      `/api/admin/timers/${timerId}/cancel`
      
    await axios.post(endpoint)
    await loadTimers() // Refresh data
  } catch (error) {
    console.error('Error canceling timer:', error)
    alert('Failed to cancel timer: ' + (error.response?.data?.message || error.message))
  }
}

const commitTimer = async (timerId) => {
  if (!confirm('Are you sure you want to commit this timer to a time entry?')) return
  
  try {
    await axios.post(`/api/timers/${timerId}/commit`)
    await loadTimers() // Refresh data
    // Emit event to parent to refresh time entries tab
    emit('timer-committed')
  } catch (error) {
    console.error('Error committing timer:', error)
    alert('Failed to commit timer: ' + (error.response?.data?.message || error.message))
  }
}

// Emits
const emit = defineEmits(['timer-committed'])

// Lifecycle
onMounted(async () => {
  await loadTimers()
  
  // Set up auto-refresh every 30 seconds
  refreshInterval = setInterval(loadTimers, 30000)
})

onUnmounted(() => {
  if (refreshInterval) {
    clearInterval(refreshInterval)
  }
})
</script>