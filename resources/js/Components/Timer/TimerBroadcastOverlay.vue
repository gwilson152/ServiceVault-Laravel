<template>
  <div 
    v-if="timers.length > 0" 
    class="fixed bottom-4 right-4 z-50"
  >
    <!-- Connection Status and Controls -->
    <div class="flex items-center justify-end mb-2 space-x-2">
      <!-- Connection Status Indicator -->
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

      <!-- Expand/Collapse All -->
      <button
        v-if="timers.length > 1"
        @click="toggleAllTimers"
        class="p-1 bg-gray-500 hover:bg-gray-600 text-white rounded-md transition-colors"
        :title="allExpanded ? 'Minimize All' : 'Expand All'"
      >
        <svg v-if="allExpanded" class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/>
        </svg>
        <svg v-else class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
      </button>
      
      <!-- New Timer Button -->
      <button
        @click="showQuickStart = !showQuickStart"
        class="p-1 bg-green-500 hover:bg-green-600 text-white rounded-md transition-colors"
        title="New Timer"
      >
        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
        </svg>
      </button>
    </div>

    <!-- Quick Start Form -->
    <div 
      v-if="showQuickStart"
      class="mb-2 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 p-3"
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
          class="flex-1 p-2 bg-green-500 hover:bg-green-600 text-white rounded-md transition-colors"
          title="Start Timer"
        >
          <svg class="w-4 h-4 mx-auto" fill="currentColor" viewBox="0 0 24 24">
            <path d="M8 5v14l11-7z"/>
          </svg>
        </button>
        <button
          @click="showQuickStart = false"
          class="p-2 bg-gray-500 hover:bg-gray-600 text-white rounded-md transition-colors"
          title="Cancel"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
          </svg>
        </button>
      </div>
    </div>

    <!-- Timer Settings Modal -->
    <div 
      v-if="showTimerSettings"
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
      @click="closeTimerSettings"
    >
      <div 
        class="bg-white dark:bg-gray-800 rounded-lg shadow-xl border border-gray-200 dark:border-gray-700 p-6 max-w-md w-full mx-4"
        @click.stop
      >
        <!-- Settings Header -->
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
            Timer Settings
          </h3>
          <button
            @click="closeTimerSettings"
            class="text-gray-400 hover:text-gray-600 transition-colors"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
          </button>
        </div>

        <!-- Settings Form -->
        <div class="space-y-4">
          <!-- Description -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
              Description
            </label>
            <input
              v-model="timerSettingsForm.description"
              type="text"
              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
              placeholder="Enter timer description..."
            />
          </div>

          <!-- Billing Rate -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
              Billing Rate
            </label>
            <select
              v-model="timerSettingsForm.billing_rate_id"
              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
            >
              <option value="">No billing rate</option>
              <option value="26ce1500-974a-44e3-8fb4-7bc0b94eafbd">Standard Rate - $75/hr</option>
              <option value="368f0f35-0fd8-4dc0-b762-361529e22651">Senior Rate - $125/hr</option>
              <option value="d7b9f798-9f0e-4885-b3b8-e6ddace5c334">Premium Rate - $175/hr</option>
            </select>
          </div>

          <!-- Current Duration (Display Only) -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
              Current Duration
            </label>
            <div class="px-3 py-2 bg-gray-50 dark:bg-gray-600 rounded-md text-sm text-gray-900 dark:text-gray-100">
              {{ formatDuration(calculateDuration(currentTimerSettings)) }}
            </div>
          </div>

          <!-- Current Value (Display Only) -->
          <div v-if="timerSettingsForm.billing_rate_id">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
              Current Value
            </label>
            <div class="px-3 py-2 bg-gray-50 dark:bg-gray-600 rounded-md text-sm text-green-600 dark:text-green-400 font-medium">
              ${{ calculateAmount({ ...currentTimerSettings, billing_rate: { rate: getBillingRateValue(timerSettingsForm.billing_rate_id) } }).toFixed(2) }}
            </div>
          </div>
        </div>

        <!-- Settings Actions -->
        <div class="flex space-x-3 mt-6">
          <button
            @click="saveTimerSettings"
            class="flex-1 px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-md text-sm font-medium transition-colors"
          >
            Save Changes
          </button>
          <button
            @click="closeTimerSettings"
            class="px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white rounded-md text-sm font-medium transition-colors"
          >
            Cancel
          </button>
        </div>
      </div>
    </div>

    <!-- Timer Cards Container - Horizontal Right-to-Left -->
    <div class="flex flex-row-reverse space-x-reverse space-x-2">
      <!-- Individual Timer -->
      <div 
        v-for="timer in timers" 
        :key="timer.id"
        class="relative"
      >
        <!-- Mini Badge (Default State) -->
        <div 
          v-if="!expandedTimers[timer.id]"
          @click="toggleTimerExpansion(timer.id)"
          class="bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 p-2 cursor-pointer hover:shadow-xl transition-all flex items-center space-x-2 min-w-48"
        >
          <!-- Status Indicator (Left) -->
          <div 
            :class="timerStatusClasses(timer.status)"
            class="w-2 h-2 rounded-full flex-shrink-0"
          ></div>
          
          <!-- Duration Display (Center) -->
          <div class="text-sm font-mono font-bold text-gray-900 dark:text-gray-100">
            {{ formatDuration(timer.duration || calculateDuration(timer), true) }}
          </div>
          
          <!-- Price Display (Right) -->
          <div 
            v-if="timer.billing_rate"
            class="text-xs text-green-600 dark:text-green-400 font-medium flex-shrink-0"
          >
            ${{ (calculateAmount(timer) || 0).toFixed(2) }}
          </div>
        </div>

        <!-- Expanded Panel -->
        <div 
          v-else
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
            <div class="flex items-center space-x-1">
              <button
                @click="openTimerSettings(timer)"
                class="text-gray-400 hover:text-blue-600 transition-colors"
                title="Timer Settings"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
              </button>
              <button
                @click="toggleTimerExpansion(timer.id)"
                class="text-gray-400 hover:text-gray-600 transition-colors"
                title="Minimize"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path>
                </svg>
              </button>
            </div>
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
              class="flex-1 p-2 bg-yellow-500 hover:bg-yellow-600 text-white rounded-md transition-colors"
              title="Pause Timer"
            >
              <svg class="w-4 h-4 mx-auto" fill="currentColor" viewBox="0 0 24 24">
                <path d="M6 4h4v16H6V4zm8 0h4v16h-4V4z"/>
              </svg>
            </button>
            <button
              v-if="timer.status === 'paused'"
              @click="resumeTimer(timer.id)"
              class="flex-1 p-2 bg-green-500 hover:bg-green-600 text-white rounded-md transition-colors"
              title="Resume Timer"
            >
              <svg class="w-4 h-4 mx-auto" fill="currentColor" viewBox="0 0 24 24">
                <path d="M8 5v14l11-7z"/>
              </svg>
            </button>
            <button
              @click="handleStopTimer(timer)"
              class="flex-1 p-2 bg-red-500 hover:bg-red-600 text-white rounded-md transition-colors"
              title="Stop Timer"
            >
              <svg class="w-4 h-4 mx-auto" fill="currentColor" viewBox="0 0 24 24">
                <path d="M6 6h12v12H6z"/>
              </svg>
            </button>
          </div>

          <!-- Ticket Info -->
          <div 
            v-if="timer.ticket"
            class="mt-2 text-xs text-gray-500 dark:text-gray-400"
          >
            <span>{{ timer.ticket.ticket_number }} - {{ timer.ticket.title }}</span>
          </div>
        </div>
      </div>
    </div>


    <!-- Totals (if multiple timers) -->
    <div 
      v-if="timers.length > 1"
      class="mt-2 bg-blue-50 dark:bg-blue-900/20 rounded-lg p-2 text-xs border border-blue-200 dark:border-blue-800"
    >
      <div class="flex justify-between items-center">
        <span class="font-medium text-blue-900 dark:text-blue-100">
          Total ({{ timers.length }}):
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

    <!-- Commit Time Entry Dialog -->
    <div 
      v-if="showCommitDialog && timerToCommit"
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
      @click="closeCommitDialog"
    >
      <div 
        class="bg-white dark:bg-gray-800 rounded-lg shadow-xl border border-gray-200 dark:border-gray-700 p-6 max-w-md w-full mx-4"
        @click.stop
      >
        <!-- Commit Header -->
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
            Commit Time Entry
          </h3>
          <button
            @click="closeCommitDialog"
            class="text-gray-400 hover:text-gray-600 transition-colors"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
          </button>
        </div>

        <!-- Commit Form -->
        <div class="space-y-4">
          <!-- Timer Info (Read-only) -->
          <div class="bg-gray-50 dark:bg-gray-600 rounded-md p-3">
            <div class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Timer</div>
            <div class="text-sm text-gray-900 dark:text-gray-100">{{ timerToCommit.description || 'Timer' }}</div>
            <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
              Duration: {{ formatDuration(calculateDuration(timerToCommit)) }}
              <span v-if="timerToCommit.billing_rate" class="ml-2">
                Value: ${{ calculateAmount(timerToCommit).toFixed(2) }}
              </span>
            </div>
          </div>

          <!-- Notes -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
              Notes
            </label>
            <textarea
              v-model="commitForm.notes"
              rows="3"
              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
              placeholder="Add any notes about this time entry..."
            ></textarea>
          </div>

          <!-- Round Duration -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
              Round up to nearest
            </label>
            <select
              v-model="commitForm.roundTo"
              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
            >
              <option value="0">No rounding</option>
              <option value="5">5 minutes</option>
              <option value="10">10 minutes</option>
              <option value="15">15 minutes</option>
            </select>
          </div>
        </div>

        <!-- Commit Actions -->
        <div class="flex space-x-3 mt-6">
          <button
            @click="commitTimeEntry"
            class="flex-1 px-4 py-2 bg-green-500 hover:bg-green-600 text-white rounded-md text-sm font-medium transition-colors"
          >
            Commit Entry
          </button>
          <button
            @click="closeCommitDialog"
            class="px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white rounded-md text-sm font-medium transition-colors"
          >
            Cancel
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref, onMounted, onUnmounted, reactive } from 'vue'
import { useTimerBroadcasting } from '@/Composables/useTimerBroadcasting.js'

const {
  timers,
  connectionStatus,
  pauseTimer,
  resumeTimer,
  stopTimer,
  startTimer,
  addOrUpdateTimer,
  removeTimer
} = useTimerBroadcasting()

// Timer expansion state
const expandedTimers = reactive({})
const showQuickStart = ref(false)
const quickStartDescription = ref('')

// Real-time update state
const currentTime = ref(new Date())
let updateInterval = null

// Timer settings state
const showTimerSettings = ref(false)
const currentTimerSettings = ref(null)
const timerSettingsForm = reactive({
  description: '',
  billing_rate_id: ''
})

// Commit dialog state
const showCommitDialog = ref(false)
const timerToCommit = ref(null)
const commitForm = reactive({
  notes: '',
  roundTo: 5
})

// Computed properties for timer stats
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

// Timer utility functions
const calculateDuration = (timer) => {
  if (!timer) return 0
  if (timer.status !== 'running') return timer.duration || 0
  
  const startedAt = new Date(timer.started_at)
  const now = currentTime.value
  const totalPaused = timer.total_paused_duration || 0
  
  return Math.max(0, Math.floor((now - startedAt) / 1000) - totalPaused)
}

const calculateAmount = (timer) => {
  if (!timer || !timer.billing_rate) return 0
  
  const duration = calculateDuration(timer)
  const hours = duration / 3600
  return hours * timer.billing_rate.rate
}

// Delete timer function
const deleteTimer = async (timerId) => {
  try {
    await window.axios.delete(`/api/timers/${timerId}?force=true`)
    removeTimer(timerId)
  } catch (error) {
    console.error('Failed to delete timer:', error)
  }
}

// Toggle expansion of individual timer
const toggleTimerExpansion = (timerId) => {
  expandedTimers[timerId] = !expandedTimers[timerId]
}

// Toggle all timers expansion
const toggleAllTimers = () => {
  const shouldExpand = !allExpanded.value
  timers.value.forEach(timer => {
    expandedTimers[timer.id] = shouldExpand
  })
}

// Computed properties
const allExpanded = computed(() => 
  timers.value.length > 0 && timers.value.every(timer => expandedTimers[timer.id])
)

const connectionStatusClasses = computed(() => ({
  'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400': connectionStatus.value === 'connected',
  'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400': connectionStatus.value === 'connecting',
  'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400': connectionStatus.value === 'disconnected',
  'bg-gray-100 text-gray-800 dark:bg-gray-900/20 dark:text-gray-400': connectionStatus.value === 'disabled'
}))

const connectionDotClasses = computed(() => ({
  'bg-green-500 animate-pulse': connectionStatus.value === 'connected',
  'bg-yellow-500 animate-pulse': connectionStatus.value === 'connecting',
  'bg-red-500': connectionStatus.value === 'disconnected',
  'bg-gray-500': connectionStatus.value === 'disabled'
}))

const connectionStatusText = computed(() => {
  switch (connectionStatus.value) {
    case 'connected': return 'Live'
    case 'connecting': return 'Connecting'
    case 'disconnected': return 'Offline'
    case 'disabled': return 'Mock'
    default: return 'Unknown'
  }
})

// Timer status classes
const timerStatusClasses = (status) => ({
  'bg-green-500 animate-pulse': status === 'running',
  'bg-yellow-500': status === 'paused',
  'bg-red-500': status === 'stopped'
})

// Quick start timer
const startQuickTimer = async () => {
  if (!quickStartDescription.value.trim()) return
  
  try {
    await startTimer({
      description: quickStartDescription.value
    })
    quickStartDescription.value = ''
    showQuickStart.value = false
  } catch (error) {
    console.error('Failed to start quick timer:', error)
  }
}

// Timer settings functions
const openTimerSettings = (timer) => {
  currentTimerSettings.value = timer
  timerSettingsForm.description = timer.description || ''
  timerSettingsForm.billing_rate_id = timer.billing_rate_id || ''
  showTimerSettings.value = true
}

const closeTimerSettings = () => {
  showTimerSettings.value = false
  currentTimerSettings.value = null
  timerSettingsForm.description = ''
  timerSettingsForm.billing_rate_id = ''
}

const saveTimerSettings = async () => {
  if (!currentTimerSettings.value) return
  
  try {
    const response = await window.axios.put(`/api/timers/${currentTimerSettings.value.id}`, {
      description: timerSettingsForm.description,
      billing_rate_id: timerSettingsForm.billing_rate_id || null
    })
    
    // Update the timer in the list
    addOrUpdateTimer(response.data.data)
    closeTimerSettings()
  } catch (error) {
    console.error('Failed to save timer settings:', error)
  }
}

const getBillingRateValue = (billingRateId) => {
  const rates = {
    '26ce1500-974a-44e3-8fb4-7bc0b94eafbd': 75,   // Standard Rate
    '368f0f35-0fd8-4dc0-b762-361529e22651': 125,  // Senior Rate
    'd7b9f798-9f0e-4885-b3b8-e6ddace5c334': 175   // Premium Rate
  }
  return rates[billingRateId] || 0
}

// Timer commit workflow functions
const handleStopTimer = async (timer) => {
  try {
    // Only pause if the timer is currently running
    if (timer.status === 'running') {
      await pauseTimer(timer.id)
    }
    
    // Populate commit dialog with timer information
    timerToCommit.value = timer
    commitForm.notes = timer.description || ''
    commitForm.roundTo = 5 // Default rounding
    showCommitDialog.value = true
  } catch (error) {
    console.error('Failed to pause timer for commit:', error)
  }
}

const closeCommitDialog = () => {
  showCommitDialog.value = false
  timerToCommit.value = null
  commitForm.notes = ''
  commitForm.roundTo = 5
}

const commitTimeEntry = async () => {
  if (!timerToCommit.value) return
  
  try {
    // Commit the timer to a time entry via the API
    const response = await window.axios.post(`/api/timers/${timerToCommit.value.id}/commit`, {
      notes: commitForm.notes,
      round_to: commitForm.roundTo
    })
    
    // The API should return success message and data
    if (response.data && response.data.message) {
      // Remove the timer from the overlay since it's now committed
      removeTimer(timerToCommit.value.id)
      
      // Close the dialog
      closeCommitDialog()
      
      console.log('Timer committed successfully:', response.data.data)
    } else {
      console.error('Unexpected API response:', response.data)
    }
  } catch (error) {
    console.error('Failed to commit time entry:', error.response?.data?.message || error.message)
  }
}

// Format duration function with compact mode support
const formatDuration = (seconds, compact = false) => {
  if (!seconds || seconds < 0) return compact ? '0:00' : '0s'
  
  const hours = Math.floor(seconds / 3600)
  const minutes = Math.floor((seconds % 3600) / 60)
  const secs = seconds % 60
  
  if (compact) {
    // Compact format: show just minutes and seconds for mini badges
    if (hours > 0) {
      return `${hours}:${minutes.toString().padStart(2, '0')}`
    } else {
      return `${minutes}:${secs.toString().padStart(2, '0')}`
    }
  } else {
    // Full format: show human readable format
    if (hours > 0) {
      return `${hours}h ${minutes}m ${secs}s`
    } else if (minutes > 0) {
      return `${minutes}m ${secs}s`
    } else {
      return `${secs}s`
    }
  }
}

// Lifecycle hooks
onMounted(() => {
  // Start real-time timer updates every second
  updateInterval = setInterval(() => {
    currentTime.value = new Date()
  }, 1000)
})

onUnmounted(() => {
  // Clean up the timer update interval
  if (updateInterval) {
    clearInterval(updateInterval)
    updateInterval = null
  }
})
</script>

<style scoped>
/* Smooth transitions for all interactive elements */
.transition-all {
  transition: all 0.2s ease-in-out;
}

/* Ensure proper z-index stacking */
.relative {
  position: relative;
}
</style>