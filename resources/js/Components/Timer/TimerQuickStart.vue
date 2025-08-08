<template>
  <div class="flex items-center space-x-3">
    <!-- Current Timer Display -->
    <div v-if="currentTimer" class="flex items-center space-x-2 bg-gray-50 rounded-md px-3 py-2">
      <div
        :class="[
          'w-2 h-2 rounded-full',
          currentTimer.status === 'running' ? 'bg-green-500 animate-pulse' : 'bg-yellow-500'
        ]"
      />
      <span class="text-sm font-mono">{{ formatDuration(duration) }}</span>
      <span v-if="currentTimer.calculated_amount" class="text-sm text-green-600 font-medium">
        ${{ currentTimer.calculated_amount.toFixed(2) }}
      </span>
    </div>

    <!-- Quick Start Form -->
    <div v-else class="flex items-center space-x-2">
      <input
        v-model="quickDescription"
        @keyup.enter="startQuickTimer"
        type="text"
        placeholder="What are you working on?"
        class="flex-1 min-w-64 px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500"
      />
      <select
        v-model="quickProject"
        class="px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-indigo-500"
      >
        <option value="">Select Project</option>
        <option v-for="project in projects" :key="project.id" :value="project.id">
          {{ project.name }}
        </option>
      </select>
    </div>

    <!-- Action Button -->
    <button
      @click="currentTimer ? stopCurrentTimer() : startQuickTimer()"
      :class="[
        'px-4 py-2 text-sm font-medium rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2',
        currentTimer
          ? 'bg-red-600 text-white hover:bg-red-700 focus:ring-red-500'
          : 'bg-indigo-600 text-white hover:bg-indigo-700 focus:ring-indigo-500'
      ]"
    >
      <PlayIcon v-if="!currentTimer" class="w-4 h-4 mr-1 inline" />
      <StopIcon v-else class="w-4 h-4 mr-1 inline" />
      {{ currentTimer ? 'Stop Timer' : 'Start Timer' }}
    </button>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import {
  PlayIcon,
  StopIcon,
} from '@heroicons/vue/24/outline'

// Reactive state
const currentTimer = ref(null)
const duration = ref(0)
const quickDescription = ref('')
const quickProject = ref('')

// Mock data - will be replaced with real API calls
const projects = ref([
  { id: 1, name: 'Project Alpha' },
  { id: 2, name: 'Project Beta' },
])

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

// API calls
const fetchCurrentTimer = async () => {
  try {
    const response = await axios.get('/api/timers/active/current')
    if (response.data.data) {
      currentTimer.value = response.data.data
      
      if (currentTimer.value.status === 'running') {
        calculateDuration()
        startDurationUpdate()
      } else {
        duration.value = currentTimer.value.duration
        stopDurationUpdate()
      }
    } else {
      currentTimer.value = null
      stopDurationUpdate()
    }
  } catch (error) {
    console.error('Failed to fetch current timer:', error)
  }
}

const calculateDuration = () => {
  if (!currentTimer.value || currentTimer.value.status !== 'running') return
  
  const startTime = new Date(currentTimer.value.started_at).getTime()
  const now = Date.now()
  const pausedDuration = (currentTimer.value.total_paused_duration || 0) * 1000
  
  duration.value = Math.floor((now - startTime - pausedDuration) / 1000)
  
  // Update calculated amount
  if (currentTimer.value.billing_rate) {
    const hours = duration.value / 3600
    currentTimer.value.calculated_amount = hours * currentTimer.value.billing_rate.rate
  }
}

const startDurationUpdate = () => {
  if (updateInterval) clearInterval(updateInterval)
  updateInterval = setInterval(calculateDuration, 1000)
}

const stopDurationUpdate = () => {
  if (updateInterval) {
    clearInterval(updateInterval)
    updateInterval = null
  }
}

// Timer actions
const startQuickTimer = async () => {
  if (!quickDescription.value.trim()) {
    quickDescription.value = 'Working on project'
  }

  try {
    const response = await axios.post('/api/timers', {
      description: quickDescription.value,
      project_id: quickProject.value || null,
      device_id: generateDeviceId(),
    })
    
    if (response.data.data) {
      currentTimer.value = response.data.data
      quickDescription.value = ''
      quickProject.value = ''
      startDurationUpdate()
    }
  } catch (error) {
    console.error('Failed to start timer:', error)
    alert('Failed to start timer')
  }
}

const stopCurrentTimer = async () => {
  try {
    await axios.post(`/api/timers/${currentTimer.value.id}/stop`)
    currentTimer.value = null
    stopDurationUpdate()
  } catch (error) {
    console.error('Failed to stop timer:', error)
    alert('Failed to stop timer')
  }
}

// Utility functions
const generateDeviceId = () => {
  return `device-${Date.now()}-${Math.random().toString(36).substr(2, 9)}`
}

// Lifecycle
onMounted(() => {
  fetchCurrentTimer()
  setInterval(fetchCurrentTimer, 30000) // Sync every 30 seconds
})

onUnmounted(() => {
  stopDurationUpdate()
})
</script>