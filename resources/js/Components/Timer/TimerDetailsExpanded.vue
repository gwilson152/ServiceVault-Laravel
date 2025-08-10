<template>
  <div class="border-t border-gray-300 bg-gray-50 px-4 py-3">
    <div class="flex items-center justify-between mb-3">
      <div class="flex items-center space-x-3">
        <div 
          :class="getStatusIndicatorClass(timer.status)"
          class="w-3 h-3 rounded-full flex-shrink-0"
        />
        <h3 class="text-sm font-semibold text-gray-900">
          Timer Details
        </h3>
      </div>
      
      <button
        @click="$emit('close')"
        class="p-1 text-gray-400 hover:text-gray-600 rounded"
      >
        <XMarkIcon class="w-4 h-4" />
      </button>
    </div>
    
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <!-- Timer Information -->
      <div class="space-y-3">
        <!-- Description/Ticket -->
        <div>
          <label class="block text-xs font-medium text-gray-700 mb-1">
            {{ timer.ticket ? 'Ticket' : 'Description' }}
          </label>
          <div v-if="timer.ticket" class="flex items-center space-x-2">
            <span class="text-sm font-medium text-blue-600">
              #{{ timer.ticket.ticket_number }}
            </span>
            <span class="text-sm text-gray-600">
              {{ timer.ticket.title }}
            </span>
          </div>
          <div v-else-if="editingDescription" class="flex items-center space-x-2">
            <input
              v-model="tempDescription"
              @keyup.enter="saveDescription"
              @keyup.escape="cancelEditDescription"
              type="text"
              class="flex-1 text-sm border border-gray-300 rounded px-2 py-1"
              placeholder="Enter timer description..."
              ref="descriptionInput"
            />
            <button
              @click="saveDescription"
              class="text-green-600 hover:text-green-700"
            >
              <CheckIcon class="w-4 h-4" />
            </button>
            <button
              @click="cancelEditDescription"
              class="text-gray-400 hover:text-gray-600"
            >
              <XMarkIcon class="w-4 h-4" />
            </button>
          </div>
          <div v-else class="flex items-center justify-between">
            <span class="text-sm text-gray-900">
              {{ timer.description || 'No description' }}
            </span>
            <button
              @click="startEditDescription"
              class="text-gray-400 hover:text-blue-600"
            >
              <PencilIcon class="w-4 h-4" />
            </button>
          </div>
        </div>
        
        <!-- Duration and Amount -->
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-xs font-medium text-gray-700 mb-1">Duration</label>
            <div class="text-lg font-mono font-semibold text-gray-900">
              {{ formatDuration(getTimerDuration()) }}
            </div>
          </div>
          <div v-if="timer.billing_rate">
            <label class="block text-xs font-medium text-gray-700 mb-1">Amount</label>
            <div class="text-lg font-semibold text-green-600">
              ${{ getCalculatedAmount() }}
            </div>
          </div>
        </div>
        
        <!-- Billing Rate -->
        <div v-if="timer.billing_rate">
          <label class="block text-xs font-medium text-gray-700 mb-1">Billing Rate</label>
          <div class="text-sm text-gray-600">
            ${{ timer.billing_rate.rate }}/hour
            <span v-if="timer.billing_rate.name" class="ml-1">
              ({{ timer.billing_rate.name }})
            </span>
          </div>
        </div>
        
        <!-- Started At -->
        <div>
          <label class="block text-xs font-medium text-gray-700 mb-1">Started At</label>
          <div class="text-sm text-gray-600">
            {{ formatDateTime(timer.started_at) }}
          </div>
        </div>
      </div>
      
      <!-- Timer Controls -->
      <div class="space-y-3">
        <div class="flex flex-col space-y-2">
          <!-- Primary Actions -->
          <div class="flex items-center space-x-2">
            <button
              v-if="timer.status === 'running'"
              @click="pauseTimer"
              :disabled="loading"
              class="flex-1 bg-yellow-500 hover:bg-yellow-600 text-white px-3 py-2 rounded text-sm font-medium transition-colors disabled:opacity-50 flex items-center justify-center space-x-2"
            >
              <PauseIcon class="w-4 h-4" />
              <span>Pause Timer</span>
            </button>
            <button
              v-else
              @click="resumeTimer"
              :disabled="loading"
              class="flex-1 bg-green-500 hover:bg-green-600 text-white px-3 py-2 rounded text-sm font-medium transition-colors disabled:opacity-50 flex items-center justify-center space-x-2"
            >
              <PlayIcon class="w-4 h-4" />
              <span>Resume Timer</span>
            </button>
            
            <button
              @click="stopTimer"
              :disabled="loading"
              class="flex-1 bg-red-500 hover:bg-red-600 text-white px-3 py-2 rounded text-sm font-medium transition-colors disabled:opacity-50 flex items-center justify-center space-x-2"
            >
              <StopIcon class="w-4 h-4" />
              <span>Stop Timer</span>
            </button>
          </div>
          
          <!-- Secondary Actions -->
          <div class="flex items-center space-x-2">
            <button
              @click="commitTimer"
              :disabled="loading"
              class="flex-1 bg-blue-500 hover:bg-blue-600 text-white px-3 py-2 rounded text-sm font-medium transition-colors disabled:opacity-50 flex items-center justify-center space-x-2"
            >
              <DocumentCheckIcon class="w-4 h-4" />
              <span>Commit to Time Entry</span>
            </button>
            
            <button
              @click="deleteTimer"
              :disabled="loading"
              class="px-3 py-2 border border-red-300 text-red-600 hover:bg-red-50 rounded text-sm font-medium transition-colors disabled:opacity-50"
              title="Delete Timer"
            >
              <TrashIcon class="w-4 h-4" />
            </button>
          </div>
        </div>
        
        <!-- Timer Stats -->
        <div v-if="timer.total_paused_duration" class="bg-yellow-50 border border-yellow-200 rounded p-2">
          <div class="text-xs font-medium text-yellow-800">Timer Statistics</div>
          <div class="text-xs text-yellow-700 mt-1">
            Total paused time: {{ formatDuration(timer.total_paused_duration) }}
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, nextTick } from 'vue'
import {
  PlayIcon,
  PauseIcon,
  StopIcon,
  XMarkIcon,
  PencilIcon,
  CheckIcon,
  DocumentCheckIcon,
  TrashIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
  timer: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['close', 'update'])

// Reactive state
const loading = ref(false)
const editingDescription = ref(false)
const tempDescription = ref('')
const descriptionInput = ref(null)

// Status indicator classes
const getStatusIndicatorClass = (status) => {
  switch (status) {
    case 'running':
      return 'bg-green-500 animate-pulse'
    case 'paused':
      return 'bg-yellow-500'
    default:
      return 'bg-gray-400'
  }
}

// Timer duration calculation
const getTimerDuration = () => {
  if (props.timer.status === 'running') {
    const startTime = new Date(props.timer.started_at).getTime()
    const now = Date.now()
    const pausedDuration = (props.timer.total_paused_duration || 0) * 1000
    return Math.floor((now - startTime - pausedDuration) / 1000)
  }
  return props.timer.duration || 0
}

// Calculate amount
const getCalculatedAmount = () => {
  if (!props.timer.billing_rate) return '0.00'
  const hours = getTimerDuration() / 3600
  const amount = hours * props.timer.billing_rate.rate
  return amount.toFixed(2)
}

// Format duration
const formatDuration = (seconds) => {
  const hours = Math.floor(seconds / 3600)
  const minutes = Math.floor((seconds % 3600) / 60)
  const secs = seconds % 60

  if (hours > 0) {
    return `${hours}:${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`
  }
  return `${minutes}:${secs.toString().padStart(2, '0')}`
}

// Format date time
const formatDateTime = (dateStr) => {
  const date = new Date(dateStr)
  return date.toLocaleString()
}

// Description editing
const startEditDescription = () => {
  tempDescription.value = props.timer.description || ''
  editingDescription.value = true
  nextTick(() => {
    descriptionInput.value?.focus()
  })
}

const saveDescription = async () => {
  if (loading.value) return
  
  loading.value = true
  try {
    await axios.put(`/api/timers/${props.timer.id}`, {
      description: tempDescription.value
    })
    editingDescription.value = false
    emit('update')
  } catch (error) {
    console.error('Failed to update timer description:', error)
  } finally {
    loading.value = false
  }
}

const cancelEditDescription = () => {
  editingDescription.value = false
  tempDescription.value = ''
}

// Timer actions
const pauseTimer = async () => {
  if (loading.value) return
  
  loading.value = true
  try {
    await axios.post(`/api/timers/${props.timer.id}/pause`)
    emit('update')
  } catch (error) {
    console.error('Failed to pause timer:', error)
  } finally {
    loading.value = false
  }
}

const resumeTimer = async () => {
  if (loading.value) return
  
  loading.value = true
  try {
    await axios.post(`/api/timers/${props.timer.id}/resume`)
    emit('update')
  } catch (error) {
    console.error('Failed to resume timer:', error)
  } finally {
    loading.value = false
  }
}

const stopTimer = async () => {
  if (loading.value) return
  
  loading.value = true
  try {
    await axios.post(`/api/timers/${props.timer.id}/stop`)
    emit('update')
    emit('close')
  } catch (error) {
    console.error('Failed to stop timer:', error)
  } finally {
    loading.value = false
  }
}

const commitTimer = async () => {
  if (loading.value) return
  
  loading.value = true
  try {
    await axios.post(`/api/timers/${props.timer.id}/commit`)
    emit('update')
    emit('close')
  } catch (error) {
    console.error('Failed to commit timer:', error)
  } finally {
    loading.value = false
  }
}

const deleteTimer = async () => {
  if (loading.value) return
  if (!confirm('Are you sure you want to delete this timer? This action cannot be undone.')) return
  
  loading.value = true
  try {
    await axios.delete(`/api/timers/${props.timer.id}?force=true`)
    emit('update')
    emit('close')
  } catch (error) {
    console.error('Failed to delete timer:', error)
  } finally {
    loading.value = false
  }
}
</script>