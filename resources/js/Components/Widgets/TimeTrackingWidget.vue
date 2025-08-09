<template>
  <div class="time-tracking-widget">
    <!-- Active Timers -->
    <div v-if="activeTimers.length > 0" class="active-timers">
      <div class="space-y-3">
        <div
          v-for="timer in activeTimers"
          :key="timer.id"
          class="timer-item p-3 bg-green-50 border border-green-200 rounded-lg"
        >
          <div class="flex items-center justify-between mb-2">
            <div class="flex items-center space-x-2">
              <div class="w-3 h-3 bg-green-500 rounded-full animate-pulse"></div>
              <span class="font-medium text-green-900">{{ timer.description || 'Untitled Task' }}</span>
            </div>
            <div class="text-sm text-green-700 font-mono">
              {{ formatDuration(timer.elapsed || 0) }}
            </div>
          </div>
          
          <div class="flex items-center justify-between text-xs text-green-600">
            <span>{{ timer.project?.name || 'No Project' }}</span>
            <div class="flex space-x-2">
              <button
                @click="pauseTimer(timer.id)"
                v-if="timer.status === 'running'"
                class="px-2 py-1 bg-yellow-100 text-yellow-700 rounded hover:bg-yellow-200"
              >
                Pause
              </button>
              <button
                @click="resumeTimer(timer.id)"
                v-else-if="timer.status === 'paused'"
                class="px-2 py-1 bg-green-100 text-green-700 rounded hover:bg-green-200"
              >
                Resume
              </button>
              <button
                @click="stopTimer(timer.id)"
                class="px-2 py-1 bg-red-100 text-red-700 rounded hover:bg-red-200"
              >
                Stop
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- No Active Timers -->
    <div v-else class="no-timers">
      <div class="text-center py-4">
        <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-gray-100 mb-3">
          <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
          </svg>
        </div>
        <p class="text-sm text-gray-600 mb-2">No active timers</p>
        <button 
          @click="startNewTimer"
          class="px-3 py-1 bg-indigo-100 text-indigo-700 rounded-md hover:bg-indigo-200 text-sm"
        >
          Start New Timer
        </button>
      </div>
    </div>

    <!-- Time Summary -->
    <div v-if="totalDurationToday > 0" class="time-summary mt-4 pt-3 border-t border-gray-100">
      <div class="text-center">
        <p class="text-xs text-gray-500">Time tracked today</p>
        <p class="text-lg font-semibold text-gray-900">{{ formatDuration(totalDurationToday) }}</p>
      </div>
    </div>

    <!-- Quick Actions -->
    <div class="quick-actions mt-4 pt-3 border-t border-gray-100">
      <div class="flex justify-between text-xs">
        <button 
          @click="viewAllTimers"
          class="text-indigo-600 hover:text-indigo-800"
        >
          View All Timers
        </button>
        <button 
          @click="refreshTimers"
          :disabled="isRefreshing"
          class="text-gray-500 hover:text-gray-700 disabled:opacity-50"
        >
          {{ isRefreshing ? 'Refreshing...' : 'Refresh' }}
        </button>
      </div>
    </div>
  </div>
</template>


<script setup>
import { computed, ref } from 'vue'
import { router } from '@inertiajs/vue3'

// Props
const props = defineProps({
  widgetData: {
    type: Object,
    default: () => ({})
  },
  widgetConfig: {
    type: Object,
    default: () => ({})
  },
  accountContext: {
    type: Object,
    default: () => ({})
  }
})

// Emits
const emit = defineEmits(['refresh', 'configure'])

// State
const isRefreshing = ref(false)

// Computed
const activeTimers = computed(() => {
  return props.widgetData?.active_timers || []
})

const totalDurationToday = computed(() => {
  return props.widgetData?.total_duration_today || 0
})

// Methods
const formatDuration = (seconds) => {
  if (!seconds) return '0:00'
  const hours = Math.floor(seconds / 3600)
  const minutes = Math.floor((seconds % 3600) / 60)
  const secs = seconds % 60
  
  if (hours > 0) {
    return `${hours}:${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`
  }
  return `${minutes}:${secs.toString().padStart(2, '0')}`
}

const pauseTimer = async (timerId) => {
  try {
    await fetch(route('api.timers.pause', timerId), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.head.querySelector('meta[name="csrf-token"]').content
      }
    })
    emit('refresh')
  } catch (error) {
    console.error('Failed to pause timer:', error)
  }
}

const resumeTimer = async (timerId) => {
  try {
    await fetch(route('api.timers.resume', timerId), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.head.querySelector('meta[name="csrf-token"]').content
      }
    })
    emit('refresh')
  } catch (error) {
    console.error('Failed to resume timer:', error)
  }
}

const stopTimer = async (timerId) => {
  try {
    await fetch(route('api.timers.stop', timerId), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.head.querySelector('meta[name="csrf-token"]').content
      }
    })
    emit('refresh')
  } catch (error) {
    console.error('Failed to stop timer:', error)
  }
}

const startNewTimer = () => {
  router.visit(route('timers.web.index'))
}

const viewAllTimers = () => {
  router.visit(route('timers.web.index'))
}

const refreshTimers = () => {
  isRefreshing.value = true
  emit('refresh')
  setTimeout(() => {
    isRefreshing.value = false
  }, 1000)
}
</script>

<style scoped>
.timer-item {
  transition: all 0.2s ease-in-out;
}

.timer-item:hover {
  transform: translateX(2px);
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.animate-pulse {
  animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

@keyframes pulse {
  0%, 100% {
    opacity: 1;
  }
  50% {
    opacity: 0.5;
  }
}
</style>