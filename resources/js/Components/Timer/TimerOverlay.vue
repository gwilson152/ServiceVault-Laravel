<template>
  <!-- Fixed Timer Overlay -->
  <div
    v-if="currentTimer"
    class="fixed bottom-4 right-4 z-50 bg-white rounded-lg shadow-lg border border-gray-200 p-4 min-w-80"
  >
    <!-- Timer Header -->
    <div class="flex items-center justify-between mb-3">
      <div class="flex items-center space-x-2">
        <div
          :class="[
            'w-3 h-3 rounded-full',
            currentTimer.status === 'running' ? 'bg-green-500 animate-pulse' : 'bg-yellow-500'
          ]"
        />
        <span class="text-sm font-medium text-gray-900">
          {{ currentTimer.status === 'running' ? 'Timer Running' : 'Timer Paused' }}
        </span>
      </div>
      <button
        @click="toggleExpanded"
        class="p-1 text-gray-400 hover:text-gray-600"
      >
        <ChevronUpIcon
          v-if="expanded"
          class="w-4 h-4"
        />
        <ChevronDownIcon
          v-else
          class="w-4 h-4"
        />
      </button>
    </div>

    <!-- Timer Display -->
    <div class="text-center mb-3">
      <div class="text-2xl font-mono font-bold text-gray-900">
        {{ formatDuration(duration) }}
      </div>
      <div v-if="currentTimer.calculated_amount" class="text-lg font-semibold text-green-600">
        ${{ currentTimer.calculated_amount.toFixed(2) }}
      </div>
    </div>

    <!-- Expanded Controls -->
    <div v-if="expanded" class="space-y-3">
      <!-- Description Input -->
      <div>
        <label class="block text-xs font-medium text-gray-700 mb-1">Description</label>
        <input
          v-model="description"
          @blur="updateDescription"
          type="text"
          placeholder="What are you working on?"
          class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500"
        />
      </div>

      <!-- Project & Rate Selection -->
      <div class="grid grid-cols-2 gap-2">
        <div>
          <label class="block text-xs font-medium text-gray-700 mb-1">Project</label>
          <select
            v-model="selectedProject"
            @change="updateProject"
            class="w-full px-2 py-1.5 text-xs border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-indigo-500"
          >
            <option value="">No Project</option>
            <option v-for="project in projects" :key="project.id" :value="project.id">
              {{ project.name }}
            </option>
          </select>
        </div>
        <div>
          <label class="block text-xs font-medium text-gray-700 mb-1">Rate</label>
          <select
            v-model="selectedBillingRate"
            @change="updateBillingRate"
            class="w-full px-2 py-1.5 text-xs border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-indigo-500"
          >
            <option value="">No Rate</option>
            <option v-for="rate in billingRates" :key="rate.id" :value="rate.id">
              {{ rate.name }} (${{ rate.rate }}/hr)
            </option>
          </select>
        </div>
      </div>

      <!-- Duration Adjustment -->
      <div>
        <label class="block text-xs font-medium text-gray-700 mb-1">Adjust Duration</label>
        <div class="flex space-x-1">
          <input
            v-model="manualDuration"
            type="text"
            placeholder="1h 30m"
            class="flex-1 px-2 py-1.5 text-xs border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-indigo-500"
          />
          <button
            @click="adjustDuration"
            class="px-3 py-1.5 text-xs bg-indigo-600 text-white rounded-md hover:bg-indigo-700"
          >
            Set
          </button>
        </div>
      </div>
    </div>

    <!-- Action Buttons -->
    <div class="flex space-x-2 mt-3">
      <button
        v-if="currentTimer.status === 'running'"
        @click="pauseTimer"
        class="flex-1 px-3 py-2 text-sm bg-yellow-600 text-white rounded-md hover:bg-yellow-700 focus:outline-none focus:ring-2 focus:ring-yellow-500"
      >
        <PauseIcon class="w-4 h-4 mr-1 inline" />
        Pause
      </button>
      <button
        v-else
        @click="resumeTimer"
        class="flex-1 px-3 py-2 text-sm bg-green-600 text-white rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500"
      >
        <PlayIcon class="w-4 h-4 mr-1 inline" />
        Resume
      </button>

      <button
        @click="stopTimer"
        class="flex-1 px-3 py-2 text-sm bg-red-600 text-white rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500"
      >
        <StopIcon class="w-4 h-4 mr-1 inline" />
        Stop
      </button>

      <!-- More Actions Menu -->
      <Menu as="div" class="relative">
        <MenuButton class="p-2 text-gray-400 hover:text-gray-600 rounded-md hover:bg-gray-100">
          <EllipsisVerticalIcon class="w-4 h-4" />
        </MenuButton>
        <transition
          enter-active-class="transition ease-out duration-100"
          enter-from-class="transform opacity-0 scale-95"
          enter-to-class="transform opacity-100 scale-100"
          leave-active-class="transition ease-in duration-75"
          leave-from-class="transform opacity-100 scale-100"
          leave-to-class="transform opacity-0 scale-95"
        >
          <MenuItems class="absolute right-0 bottom-full mb-1 z-10 w-48 origin-bottom-right bg-white rounded-md shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none">
            <div class="py-1">
              <MenuItem v-slot="{ active }">
                <button
                  @click="commitTimer"
                  :class="[active ? 'bg-gray-100' : '', 'block w-full text-left px-4 py-2 text-sm text-gray-700']"
                >
                  Commit to Time Entry
                </button>
              </MenuItem>
              <MenuItem v-slot="{ active }">
                <button
                  @click="deleteTimer"
                  :class="[active ? 'bg-gray-100' : '', 'block w-full text-left px-4 py-2 text-sm text-red-600']"
                >
                  Delete Timer
                </button>
              </MenuItem>
            </div>
          </MenuItems>
        </transition>
      </Menu>
    </div>
  </div>

  <!-- Start Timer Button (when no active timer) -->
  <div
    v-else
    class="fixed bottom-4 right-4 z-50"
  >
    <button
      @click="startTimer"
      class="bg-indigo-600 text-white rounded-full p-4 shadow-lg hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-colors"
    >
      <PlayIcon class="w-6 h-6" />
    </button>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { router } from '@inertiajs/vue3'
import { Menu, MenuButton, MenuItem, MenuItems } from '@headlessui/vue'
import {
  PlayIcon,
  PauseIcon,
  StopIcon,
  ChevronUpIcon,
  ChevronDownIcon,
  EllipsisVerticalIcon,
} from '@heroicons/vue/24/outline'

// Reactive state
const currentTimer = ref(null)
const duration = ref(0)
const expanded = ref(false)
const description = ref('')
const selectedProject = ref('')
const selectedBillingRate = ref('')
const manualDuration = ref('')

// Mock data - will be replaced with real API calls
const projects = ref([
  { id: 1, name: 'Project Alpha' },
  { id: 2, name: 'Project Beta' },
])

const billingRates = ref([
  { id: 1, name: 'Standard Rate', rate: 75 },
  { id: 2, name: 'Premium Rate', rate: 100 },
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
      description.value = currentTimer.value.description || ''
      selectedProject.value = currentTimer.value.project?.id || ''
      selectedBillingRate.value = currentTimer.value.billing_rate?.id || ''
      
      // Update duration
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
const startTimer = async () => {
  try {
    const response = await axios.post('/api/timers', {
      description: 'New timer',
      device_id: generateDeviceId(),
    })
    
    if (response.data.data) {
      currentTimer.value = response.data.data
      expanded.value = true
      startDurationUpdate()
    }
  } catch (error) {
    console.error('Failed to start timer:', error)
    alert('Failed to start timer')
  }
}

const pauseTimer = async () => {
  try {
    await axios.post(`/api/timers/${currentTimer.value.id}/pause`)
    await fetchCurrentTimer()
  } catch (error) {
    console.error('Failed to pause timer:', error)
    alert('Failed to pause timer')
  }
}

const resumeTimer = async () => {
  try {
    await axios.post(`/api/timers/${currentTimer.value.id}/resume`)
    await fetchCurrentTimer()
  } catch (error) {
    console.error('Failed to resume timer:', error)
    alert('Failed to resume timer')
  }
}

const stopTimer = async () => {
  try {
    await axios.post(`/api/timers/${currentTimer.value.id}/stop`)
    currentTimer.value = null
    stopDurationUpdate()
  } catch (error) {
    console.error('Failed to stop timer:', error)
    alert('Failed to stop timer')
  }
}

const commitTimer = async () => {
  try {
    await axios.post(`/api/timers/${currentTimer.value.id}/commit`, {
      description: description.value,
    })
    currentTimer.value = null
    stopDurationUpdate()
    alert('Timer committed to time entry')
  } catch (error) {
    console.error('Failed to commit timer:', error)
    alert('Failed to commit timer')
  }
}

const deleteTimer = async () => {
  if (!confirm('Are you sure you want to delete this timer?')) return
  
  try {
    await axios.delete(`/api/timers/${currentTimer.value.id}`, {
      params: { force: true }
    })
    currentTimer.value = null
    stopDurationUpdate()
  } catch (error) {
    console.error('Failed to delete timer:', error)
    alert('Failed to delete timer')
  }
}

// Update functions
const updateDescription = async () => {
  if (!currentTimer.value) return
  
  try {
    await axios.put(`/api/timers/${currentTimer.value.id}`, {
      description: description.value,
    })
  } catch (error) {
    console.error('Failed to update description:', error)
  }
}

const updateProject = async () => {
  if (!currentTimer.value) return
  
  try {
    await axios.put(`/api/timers/${currentTimer.value.id}`, {
      project_id: selectedProject.value || null,
    })
    currentTimer.value.project = selectedProject.value ? 
      projects.value.find(p => p.id == selectedProject.value) : null
  } catch (error) {
    console.error('Failed to update project:', error)
  }
}

const updateBillingRate = async () => {
  if (!currentTimer.value) return
  
  try {
    await axios.put(`/api/timers/${currentTimer.value.id}`, {
      billing_rate_id: selectedBillingRate.value || null,
    })
    currentTimer.value.billing_rate = selectedBillingRate.value ?
      billingRates.value.find(r => r.id == selectedBillingRate.value) : null
  } catch (error) {
    console.error('Failed to update billing rate:', error)
  }
}

const adjustDuration = async () => {
  if (!currentTimer.value || !manualDuration.value) return
  
  // Parse duration (simple implementation)
  const seconds = parseDurationToSeconds(manualDuration.value)
  if (seconds < 0) {
    alert('Invalid duration format')
    return
  }
  
  try {
    await axios.patch(`/api/timers/${currentTimer.value.id}/duration`, {
      duration: seconds,
      adjustment_type: 'set',
    })
    duration.value = seconds
    manualDuration.value = ''
  } catch (error) {
    console.error('Failed to adjust duration:', error)
    alert('Failed to adjust duration')
  }
}

// Utility functions
const toggleExpanded = () => {
  expanded.value = !expanded.value
}

const generateDeviceId = () => {
  return `device-${Date.now()}-${Math.random().toString(36).substr(2, 9)}`
}

const parseDurationToSeconds = (durationStr) => {
  // Simple parser for formats like "1h 30m", "90m", "1:30", etc.
  const hourMatch = durationStr.match(/(\d+)h/)
  const minuteMatch = durationStr.match(/(\d+)m/)
  const colonMatch = durationStr.match(/^(\d+):(\d+)$/)
  
  let totalSeconds = 0
  
  if (colonMatch) {
    totalSeconds = parseInt(colonMatch[1]) * 3600 + parseInt(colonMatch[2]) * 60
  } else {
    if (hourMatch) totalSeconds += parseInt(hourMatch[1]) * 3600
    if (minuteMatch) totalSeconds += parseInt(minuteMatch[1]) * 60
  }
  
  return totalSeconds
}

// Lifecycle
onMounted(() => {
  fetchCurrentTimer()
  
  // Set up periodic sync
  setInterval(fetchCurrentTimer, 30000) // Sync every 30 seconds
})

onUnmounted(() => {
  stopDurationUpdate()
})

// Set up echo for real-time updates (will be implemented later)
// Echo.private(`user.${userId}`)
//   .listen('.timer.updated', (e) => {
//     currentTimer.value = e.timer
//   })
//   .listen('.timer.stopped', (e) => {
//     currentTimer.value = null
//     stopDurationUpdate()
//   })
</script>