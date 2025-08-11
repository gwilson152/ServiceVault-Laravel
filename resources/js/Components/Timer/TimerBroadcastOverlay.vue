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
          class="bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 p-3 cursor-pointer hover:shadow-xl transition-all min-w-32"
        >
          <!-- Status Indicator -->
          <div class="flex items-center justify-between mb-1">
            <div 
              :class="timerStatusClasses(timer.status)"
              class="w-2 h-2 rounded-full"
            ></div>
            <button
              @click.stop="deleteTimer(timer.id)"
              class="text-gray-400 hover:text-red-500 transition-colors"
              title="Delete Timer"
            >
              <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
              </svg>
            </button>
          </div>
          
          <!-- Duration Display -->
          <div class="text-lg font-mono font-bold text-gray-900 dark:text-gray-100 text-center">
            {{ formatDuration(timer.duration || calculateDuration(timer), true) }}
          </div>
          
          <!-- Price Display -->
          <div 
            v-if="timer.billing_rate"
            class="text-xs text-green-600 dark:text-green-400 text-center font-medium"
          >
            ${{ (calculateAmount(timer) || 0).toFixed(2) }}
          </div>
          
          <!-- Click indicator -->
          <div class="text-xs text-gray-400 text-center mt-1">
            ⊙
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
                @click="toggleTimerExpansion(timer.id)"
                class="text-gray-400 hover:text-gray-600 transition-colors"
                title="Minimize"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path>
                </svg>
              </button>
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
              @click="stopTimer(timer.id)"
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

    <!-- Global Controls -->
    <div class="mt-2 flex justify-end space-x-2">
      <!-- Expand/Collapse All -->
      <button
        v-if="timers.length > 1"
        @click="toggleAllTimers"
        class="p-2 bg-gray-500 hover:bg-gray-600 text-white rounded-md transition-colors"
        :title="allExpanded ? 'Minimize All' : 'Expand All'"
      >
        <svg v-if="allExpanded" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/>
        </svg>
        <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
      </button>
      
      <!-- Quick Start Timer Button -->
      <button
        @click="showQuickStart = !showQuickStart"
        class="p-2 bg-green-500 hover:bg-green-600 text-white rounded-md transition-colors"
        title="New Timer"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
        </svg>
      </button>
    </div>

    <!-- Quick Start Form -->
    <div 
      v-if="showQuickStart"
      class="mt-2 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 p-3"
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
  const now = new Date()
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