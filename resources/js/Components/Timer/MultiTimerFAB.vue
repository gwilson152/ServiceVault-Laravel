<template>
  <!-- Multi-Timer FAB System -->
  <div class="fixed bottom-4 right-4 z-50">
    <!-- Active Timers Stack -->
    <div 
      v-if="timers.length > 0" 
      class="space-y-2 mb-4"
    >
      <!-- Connection Status -->
      <div 
        v-if="showConnectionStatus"
        class="flex justify-end"
      >
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

      <!-- Timer Cards -->
      <div 
        v-for="(timer, index) in timers" 
        :key="timer.id"
        :style="{ 
          transform: expanded ? 'translateY(0)' : `translateY(-${(timers.length - 1 - index) * 8}px)`,
          zIndex: timers.length - index 
        }"
        class="bg-white rounded-lg shadow-lg border border-gray-200 p-3 transition-transform duration-300 ease-in-out"
        :class="expanded ? 'w-80' : 'w-64 cursor-pointer hover:shadow-xl'"
        @click="!expanded && toggleExpanded()"
      >
        <!-- Timer Header -->
        <div class="flex items-center justify-between mb-2">
          <div class="flex items-center space-x-2">
            <!-- Status Indicator -->
            <div 
              :class="timerStatusClasses(timer.status)"
              class="w-3 h-3 rounded-full flex-shrink-0"
            ></div>
            
            <!-- Timer Description -->
            <div class="flex-1 min-w-0">
              <input
                v-if="expanded && editingTimer === timer.id"
                v-model="timer.description"
                @blur="updateTimerDescription(timer)"
                @keyup.enter="updateTimerDescription(timer)"
                class="w-full text-sm font-medium text-gray-900 bg-transparent border-none outline-none focus:ring-1 focus:ring-blue-500 rounded px-1"
                placeholder="Timer description..."
              />
              <span 
                v-else
                @click.stop="expanded && startEditingDescription(timer.id)"
                :class="expanded ? 'cursor-pointer hover:bg-gray-50 px-1 rounded' : ''"
                class="text-sm font-medium text-gray-900 truncate block"
                :title="timer.description || 'Click to add description'"
              >
                {{ timer.description || 'New Timer' }}
              </span>
            </div>
          </div>

          <!-- Close/Delete Button -->
          <button
            v-if="expanded"
            @click.stop="deleteTimer(timer.id)"
            class="text-gray-400 hover:text-red-500 transition-colors p-1"
            title="Delete Timer"
          >
            <XMarkIcon class="w-4 h-4" />
          </button>
        </div>

        <!-- Timer Display -->
        <div class="flex items-center justify-between mb-3">
          <div>
            <div class="text-lg font-mono font-bold text-gray-900">
              {{ formatDuration(calculateDuration(timer)) }}
            </div>
            <div v-if="timer.service_ticket" class="text-xs text-gray-500">
              {{ timer.service_ticket.ticket_number }}
            </div>
          </div>
          
          <div v-if="timer.billing_rate" class="text-right">
            <div class="text-sm font-semibold text-green-600">
              ${{ calculateAmount(timer).toFixed(2) }}
            </div>
            <div class="text-xs text-gray-500">
              ${{ timer.billing_rate.rate }}/hr
            </div>
          </div>
        </div>

        <!-- Timer Controls -->
        <div v-if="expanded" class="flex space-x-2 mb-3">
          <button
            v-if="timer.status === 'running'"
            @click.stop="pauseTimer(timer.id)"
            class="flex-1 px-3 py-2 bg-yellow-500 hover:bg-yellow-600 text-white rounded-md text-sm font-medium transition-colors flex items-center justify-center space-x-1"
          >
            <PauseIcon class="w-4 h-4" />
            <span>Pause</span>
          </button>
          
          <button
            v-if="timer.status === 'paused'"
            @click.stop="resumeTimer(timer.id)"
            class="flex-1 px-3 py-2 bg-green-500 hover:bg-green-600 text-white rounded-md text-sm font-medium transition-colors flex items-center justify-center space-x-1"
          >
            <PlayIcon class="w-4 h-4" />
            <span>Resume</span>
          </button>
          
          <button
            @click.stop="stopTimer(timer.id)"
            class="flex-1 px-3 py-2 bg-red-500 hover:bg-red-600 text-white rounded-md text-sm font-medium transition-colors flex items-center justify-center space-x-1"
          >
            <StopIcon class="w-4 h-4" />
            <span>Stop</span>
          </button>
          
          <button
            @click.stop="commitTimer(timer.id)"
            class="px-3 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-md text-sm font-medium transition-colors"
            title="Commit to Time Entry"
          >
            <CheckIcon class="w-4 h-4" />
          </button>
        </div>

        <!-- Quick Edit Fields (Expanded Mode) -->
        <div v-if="expanded" class="space-y-2">
          <!-- Service Ticket Selection -->
          <div class="flex space-x-2">
            <select
              v-model="timer.service_ticket_id"
              @change="updateTimerTicket(timer)"
              class="flex-1 px-2 py-1.5 text-xs border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500"
            >
              <option value="">No Ticket</option>
              <option v-for="ticket in availableTickets" :key="ticket.id" :value="ticket.id">
                {{ ticket.ticket_number }} - {{ ticket.title }}
              </option>
            </select>
            
            <select
              v-model="timer.billing_rate_id"
              @change="updateTimerBillingRate(timer)"
              class="flex-1 px-2 py-1.5 text-xs border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500"
            >
              <option value="">No Rate</option>
              <option v-for="rate in availableBillingRates" :key="rate.id" :value="rate.id">
                ${{ rate.rate }}/hr
              </option>
            </select>
          </div>
        </div>
      </div>

      <!-- Collapse/Expand Toggle -->
      <div v-if="timers.length > 0" class="flex justify-end">
        <button
          @click="toggleExpanded"
          class="bg-gray-600 hover:bg-gray-700 text-white rounded-full p-2 shadow-lg transition-colors"
        >
          <ChevronUpIcon v-if="expanded" class="w-4 h-4" />
          <ChevronDownIcon v-else class="w-4 h-4" />
        </button>
      </div>

      <!-- Total Summary (Multiple Timers) -->
      <div 
        v-if="expanded && timers.length > 1"
        class="bg-blue-50 rounded-lg p-3 text-sm border border-blue-200"
      >
        <div class="flex justify-between items-center">
          <span class="font-medium text-blue-900">
            Your Timers ({{ timers.length }}):
          </span>
          <div class="text-right">
            <div class="font-mono font-bold text-blue-900">
              {{ formatDuration(totalDuration) }}
            </div>
            <div class="text-blue-700">
              ${{ totalAmount.toFixed(2) }}
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Admin All-Timers Overlay -->
    <div 
      v-if="expanded && canViewAdminTimerOverlay && activeAllTimers.length > 0"
      class="space-y-2 mb-4"
    >
      <div class="text-xs text-gray-500 font-medium">All Active Timers (Admin View):</div>
      
      <div 
        v-for="timer in activeAllTimers.slice(0, 5)" 
        :key="`admin-${timer.id}`"
        class="bg-orange-50 border border-orange-200 rounded-lg p-3 text-sm"
      >
        <!-- Admin Timer Header -->
        <div class="flex items-center justify-between mb-2">
          <div class="flex items-center space-x-2">
            <div 
              :class="timerStatusClasses(timer.status)"
              class="w-2 h-2 rounded-full flex-shrink-0"
            ></div>
            <span class="font-medium text-orange-900">
              {{ timer.user?.name || 'Unknown User' }}
            </span>
          </div>
          
          <div class="text-right">
            <div class="font-mono font-semibold text-orange-900">
              {{ formatDuration(calculateDuration(timer)) }}
            </div>
            <div v-if="timer.billing_rate" class="text-orange-700 text-xs">
              ${{ calculateAmount(timer).toFixed(2) }}
            </div>
          </div>
        </div>

        <!-- Timer Description -->
        <div class="mb-2">
          <p class="text-orange-800 text-xs truncate">
            {{ timer.description || 'No description' }}
          </p>
          <div v-if="timer.service_ticket" class="text-orange-600 text-xs">
            {{ timer.service_ticket.ticket_number }}
          </div>
        </div>

        <!-- Admin Controls -->
        <div class="flex space-x-1">
          <button
            v-if="timer.status === 'running'"
            @click="adminPauseTimerAction(timer.id)"
            class="px-2 py-1 bg-yellow-600 hover:bg-yellow-700 text-white rounded text-xs transition-colors"
          >
            Pause
          </button>
          
          <button
            v-if="timer.status === 'paused'"
            @click="adminResumeTimerAction(timer.id)"
            class="px-2 py-1 bg-green-600 hover:bg-green-700 text-white rounded text-xs transition-colors"
          >
            Resume
          </button>
          
          <button
            @click="adminStopTimerAction(timer.id)"
            class="px-2 py-1 bg-red-600 hover:bg-red-700 text-white rounded text-xs transition-colors"
          >
            Stop
          </button>
        </div>
      </div>

      <!-- Show more indicator -->
      <div 
        v-if="activeAllTimers.length > 5" 
        class="text-xs text-gray-500 text-center"
      >
        ... and {{ activeAllTimers.length - 5 }} more timers
      </div>
    </div>

    <!-- Start New Timer FAB -->
    <button
      @click="startNewTimer"
      class="bg-green-500 hover:bg-green-600 text-white rounded-full shadow-lg transition-colors relative"
      :class="timers.length > 0 ? 'p-3' : 'p-4'"
    >
      <PlusIcon class="w-6 h-6" />
      
      <!-- Timer Count Badge -->
      <div 
        v-if="timers.length > 0"
        class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center font-bold"
      >
        {{ timers.length }}
      </div>
    </button>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { 
  PlusIcon, 
  PlayIcon, 
  PauseIcon, 
  StopIcon, 
  XMarkIcon,
  CheckIcon,
  ChevronUpIcon,
  ChevronDownIcon
} from '@heroicons/vue/24/outline'
import { useAppWideTimers } from '@/Composables/useAppWideTimers.js'

// App-wide timer management
const {
  activeUserTimers: timers,
  activeAllTimers,
  connectionStatus,
  canViewAppWideTimers,
  canViewAdminTimerOverlay,
  canViewAllTimers,
  isAdmin,
  startTimer,
  stopTimer,
  pauseTimer,
  resumeTimer,
  adminPauseTimer,
  adminResumeTimer,
  adminStopTimer,
  calculateDuration: calcDuration,
  calculateAmount: calcAmount,
} = useAppWideTimers()

// Component state
const expanded = ref(false)
const editingTimer = ref(null)
const currentTime = ref(new Date())
const showConnectionStatus = ref(false)

// Mock data - replace with real API calls
const availableTickets = ref([])
const availableBillingRates = ref([])

// Update current time every second
let timeInterval
onMounted(() => {
  timeInterval = setInterval(() => {
    currentTime.value = new Date()
  }, 1000)
  
  // Load available tickets and billing rates
  fetchAvailableOptions()
  
  // Show connection status when there are timers
  showConnectionStatus.value = timers.value.length > 0
})

onUnmounted(() => {
  if (timeInterval) {
    clearInterval(timeInterval)
  }
})

// Computed properties
const connectionStatusClasses = computed(() => ({
  'bg-green-100 text-green-800': connectionStatus.value === 'connected',
  'bg-yellow-100 text-yellow-800': connectionStatus.value === 'connecting',
  'bg-red-100 text-red-800': connectionStatus.value === 'error',
  'bg-gray-100 text-gray-800': connectionStatus.value === 'disconnected',
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
    return total + calculateDuration(timer)
  }, 0)
})

const totalAmount = computed(() => {
  return timers.value.reduce((total, timer) => {
    return total + calculateAmount(timer)
  }, 0)
})

// Methods
const timerStatusClasses = (status) => ({
  'bg-green-500 animate-pulse': status === 'running',
  'bg-yellow-500': status === 'paused',
  'bg-gray-500': status === 'stopped',
})

const calculateDuration = (timer) => {
  return calcDuration(timer)
}

const calculateAmount = (timer) => {
  return calcAmount(timer)
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

const toggleExpanded = () => {
  expanded.value = !expanded.value
}

const startNewTimer = async () => {
  try {
    await startTimer({
      description: '',
      device_id: generateDeviceId(),
      stop_others: false // Allow multiple concurrent timers
    })
    
    // Auto-expand when starting a new timer
    expanded.value = true
  } catch (error) {
    console.error('Failed to start timer:', error)
    alert('Failed to start timer: ' + error.message)
  }
}

const commitTimer = async (timerId) => {
  try {
    await window.axios.post(`/api/timers/${timerId}/commit`)
    // Timer will be removed via broadcasting
  } catch (error) {
    console.error('Failed to commit timer:', error)
    alert('Failed to commit timer: ' + error.message)
  }
}

const deleteTimer = async (timerId) => {
  if (!confirm('Are you sure you want to delete this timer?')) return
  
  try {
    await window.axios.delete(`/api/timers/${timerId}?force=true`)
    // Timer will be removed via broadcasting
  } catch (error) {
    console.error('Failed to delete timer:', error)
    alert('Failed to delete timer: ' + error.message)
  }
}

// Edit functions
const startEditingDescription = (timerId) => {
  editingTimer.value = timerId
}

const updateTimerDescription = async (timer) => {
  try {
    await window.axios.put(`/api/timers/${timer.id}`, {
      description: timer.description
    })
    editingTimer.value = null
  } catch (error) {
    console.error('Failed to update timer description:', error)
  }
}

const updateTimerTicket = async (timer) => {
  try {
    await window.axios.put(`/api/timers/${timer.id}`, {
      service_ticket_id: timer.service_ticket_id || null
    })
    
    // Update local timer object
    if (timer.service_ticket_id) {
      timer.service_ticket = availableTickets.value.find(t => t.id == timer.service_ticket_id)
    } else {
      timer.service_ticket = null
    }
  } catch (error) {
    console.error('Failed to update timer ticket:', error)
  }
}

const updateTimerBillingRate = async (timer) => {
  try {
    await window.axios.put(`/api/timers/${timer.id}`, {
      billing_rate_id: timer.billing_rate_id || null
    })
    
    // Update local timer object
    if (timer.billing_rate_id) {
      timer.billing_rate = availableBillingRates.value.find(r => r.id == timer.billing_rate_id)
    } else {
      timer.billing_rate = null
    }
  } catch (error) {
    console.error('Failed to update timer billing rate:', error)
  }
}

// Admin actions
const adminPauseTimerAction = async (timerId) => {
  try {
    await adminPauseTimer(timerId)
    // Timer will be updated via broadcasting
  } catch (error) {
    console.error('Failed to admin pause timer:', error)
    alert('Failed to pause timer: ' + error.message)
  }
}

const adminResumeTimerAction = async (timerId) => {
  try {
    await adminResumeTimer(timerId)
    // Timer will be updated via broadcasting
  } catch (error) {
    console.error('Failed to admin resume timer:', error)
    alert('Failed to resume timer: ' + error.message)
  }
}

const adminStopTimerAction = async (timerId) => {
  if (!confirm('Are you sure you want to stop this user\'s timer?')) return
  
  try {
    await adminStopTimer(timerId)
    // Timer will be removed via broadcasting
  } catch (error) {
    console.error('Failed to admin stop timer:', error)
    alert('Failed to stop timer: ' + error.message)
  }
}

// Utility functions
const generateDeviceId = () => {
  return 'fab-' + Math.random().toString(36).substr(2, 9)
}

const fetchAvailableOptions = async () => {
  try {
    // Fetch service tickets
    const ticketResponse = await window.axios.get('/api/service-tickets?per_page=100')
    availableTickets.value = ticketResponse.data.data || []
    
    // Fetch billing rates (you'll need to implement this endpoint)
    // const rateResponse = await window.axios.get('/api/billing-rates')
    // availableBillingRates.value = rateResponse.data.data || []
    
    // Mock billing rates for now
    availableBillingRates.value = [
      { id: 1, name: 'Standard Rate', rate: 75 },
      { id: 2, name: 'Premium Rate', rate: 100 },
      { id: 3, name: 'Senior Rate', rate: 125 },
    ]
  } catch (error) {
    console.error('Failed to fetch available options:', error)
  }
}
</script>

<style scoped>
/* Custom animations and transitions */
.timer-card-enter-active,
.timer-card-leave-active {
  transition: all 0.3s ease;
}

.timer-card-enter-from,
.timer-card-leave-to {
  opacity: 0;
  transform: translateY(30px);
}

/* Improved focus styles */
.timer-input:focus {
  box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.5);
}
</style>