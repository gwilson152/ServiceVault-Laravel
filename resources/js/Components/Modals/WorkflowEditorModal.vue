<template>
  <Modal :show="show" @close="close" max-width="4xl">
    <div class="px-6 py-4">
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-semibold text-gray-900">
          Workflow Transition Editor
        </h3>
        <button @click="close" class="text-gray-400 hover:text-gray-600">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <div class="space-y-6">
        <!-- Instructions -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
          <h4 class="text-sm font-medium text-blue-900 mb-2">How to use this editor:</h4>
          <ul class="text-sm text-blue-700 space-y-1">
            <li>• Select a status below to configure which statuses it can transition to</li>
            <li>• Use checkboxes to allow/disallow transitions between statuses</li>
            <li>• Changes are applied when you click "Save Workflow"</li>
          </ul>
        </div>

        <!-- Status Selection -->
        <div>
          <label for="selectedStatus" class="block text-sm font-medium text-gray-700 mb-2">
            Configure transitions from:
          </label>
          <select
            id="selectedStatus"
            v-model="selectedStatus"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
          >
            <option value="">Select a status to configure...</option>
            <option 
              v-for="status in statuses" 
              :key="status.key" 
              :value="status.key"
            >
              {{ status.name }}
            </option>
          </select>
        </div>

        <!-- Transition Configuration -->
        <div v-if="selectedStatus" class="space-y-4">
          <div class="flex items-center justify-between">
            <h4 class="text-lg font-medium text-gray-900">
              From: <span 
                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-sm font-medium"
                :style="{ 
                  backgroundColor: getStatusColor(selectedStatus) + '20',
                  color: getStatusColor(selectedStatus)
                }"
              >
                <div 
                  class="w-2 h-2 rounded-full mr-2"
                  :style="{ backgroundColor: getStatusColor(selectedStatus) }"
                ></div>
                {{ getStatusName(selectedStatus) }}
              </span>
            </h4>
            <div class="flex items-center space-x-2 text-sm text-gray-500">
              <span>{{ getSelectedTransitions().length }} transitions allowed</span>
            </div>
          </div>

          <!-- Available Transitions -->
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
            <div 
              v-for="status in statuses" 
              :key="status.key"
              class="flex items-center p-3 border rounded-lg"
              :class="[
                status.key === selectedStatus 
                  ? 'border-yellow-200 bg-yellow-50' 
                  : 'border-gray-200 bg-white hover:bg-gray-50'
              ]"
            >
              <input
                :id="`transition-${status.key}`"
                :checked="isTransitionAllowed(selectedStatus, status.key)"
                :disabled="status.key === selectedStatus"
                @change="toggleTransition(selectedStatus, status.key, $event.target.checked)"
                type="checkbox"
                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded disabled:opacity-50"
              >
              <label 
                :for="`transition-${status.key}`" 
                class="ml-3 flex items-center flex-1 cursor-pointer"
                :class="{ 'cursor-not-allowed': status.key === selectedStatus }"
              >
                <div 
                  class="w-3 h-3 rounded-full mr-2"
                  :style="{ backgroundColor: status.color }"
                ></div>
                <div class="flex-1">
                  <div class="text-sm font-medium text-gray-900">
                    {{ status.name }}
                    <span v-if="status.key === selectedStatus" class="text-yellow-600 text-xs">
                      (Current)
                    </span>
                  </div>
                  <div v-if="status.is_closed" class="text-xs text-gray-500">
                    Final state
                  </div>
                </div>
              </label>
            </div>
          </div>

          <!-- Quick Actions -->
          <div class="flex items-center space-x-2 pt-4 border-t border-gray-200">
            <button
              @click="selectAllTransitions"
              type="button"
              class="text-sm text-blue-600 hover:text-blue-700 font-medium"
            >
              Select All
            </button>
            <span class="text-gray-300">|</span>
            <button
              @click="clearAllTransitions"
              type="button"
              class="text-sm text-red-600 hover:text-red-700 font-medium"
            >
              Clear All
            </button>
            <span class="text-gray-300">|</span>
            <button
              @click="resetToDefaults"
              type="button"
              class="text-sm text-gray-600 hover:text-gray-700 font-medium"
            >
              Reset to Defaults
            </button>
          </div>
        </div>

        <!-- Current Workflow Overview -->
        <div v-if="Object.keys(workflowTransitions).length > 0" class="space-y-4">
          <h4 class="text-lg font-medium text-gray-900">Current Workflow Overview</h4>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4 max-h-64 overflow-y-auto">
            <div 
              v-for="(transitions, fromStatus) in workflowTransitions" 
              :key="fromStatus"
              class="p-3 bg-gray-50 rounded-lg"
            >
              <div class="flex items-center mb-2">
                <div 
                  class="w-3 h-3 rounded-full mr-2"
                  :style="{ backgroundColor: getStatusColor(fromStatus) }"
                ></div>
                <div class="text-sm font-medium text-gray-900">
                  {{ getStatusName(fromStatus) }}
                </div>
              </div>
              <div v-if="transitions.length > 0" class="flex flex-wrap gap-1">
                <span 
                  v-for="toStatus in transitions" 
                  :key="toStatus"
                  class="inline-flex items-center px-2 py-1 rounded text-xs font-medium"
                  :style="{ 
                    backgroundColor: getStatusColor(toStatus) + '20',
                    color: getStatusColor(toStatus)
                  }"
                >
                  {{ getStatusName(toStatus) }}
                </span>
              </div>
              <div v-else class="text-xs text-gray-500 italic">
                No transitions (final state)
              </div>
            </div>
          </div>
        </div>

        <!-- Form Actions -->
        <div class="flex items-center justify-end space-x-3 pt-6 border-t border-gray-200">
          <button
            type="button"
            @click="close"
            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
          >
            Cancel
          </button>
          <button
            @click="handleSave"
            :disabled="processing"
            class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <span v-if="processing" class="flex items-center">
              <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              Saving...
            </span>
            <span v-else>
              Save Workflow
            </span>
          </button>
        </div>
      </div>
    </div>
  </Modal>
</template>

<script setup>
import { ref, reactive, computed, watch } from 'vue'
import Modal from '@/Components/Modal.vue'

const props = defineProps({
  show: {
    type: Boolean,
    default: false
  },
  statuses: {
    type: Array,
    default: () => []
  },
  initialTransitions: {
    type: Object,
    default: () => ({})
  }
})

const emit = defineEmits(['close', 'saved'])

const processing = ref(false)
const selectedStatus = ref('')
const workflowTransitions = reactive({})

// Initialize workflow transitions when modal opens
watch(() => props.show, (show) => {
  if (show) {
    Object.assign(workflowTransitions, props.initialTransitions)
    selectedStatus.value = ''
  }
}, { immediate: true })

// Helper methods
const getStatusName = (statusKey) => {
  const status = props.statuses.find(s => s.key === statusKey)
  return status?.name || statusKey
}

const getStatusColor = (statusKey) => {
  const status = props.statuses.find(s => s.key === statusKey)
  return status?.color || '#6B7280'
}

const isTransitionAllowed = (fromStatus, toStatus) => {
  return workflowTransitions[fromStatus]?.includes(toStatus) || false
}

const getSelectedTransitions = () => {
  return workflowTransitions[selectedStatus.value] || []
}

const toggleTransition = (fromStatus, toStatus, allowed) => {
  if (!workflowTransitions[fromStatus]) {
    workflowTransitions[fromStatus] = []
  }
  
  if (allowed) {
    if (!workflowTransitions[fromStatus].includes(toStatus)) {
      workflowTransitions[fromStatus].push(toStatus)
    }
  } else {
    const index = workflowTransitions[fromStatus].indexOf(toStatus)
    if (index > -1) {
      workflowTransitions[fromStatus].splice(index, 1)
    }
  }
}

const selectAllTransitions = () => {
  if (!selectedStatus.value) return
  
  workflowTransitions[selectedStatus.value] = props.statuses
    .filter(s => s.key !== selectedStatus.value)
    .map(s => s.key)
}

const clearAllTransitions = () => {
  if (!selectedStatus.value) return
  
  workflowTransitions[selectedStatus.value] = []
}

const resetToDefaults = () => {
  if (!selectedStatus.value) return
  
  // Default workflow: open -> in_progress -> resolved -> closed
  const defaults = {
    'open': ['in_progress', 'closed'],
    'in_progress': ['open', 'resolved', 'closed'],
    'resolved': ['open', 'in_progress', 'closed'],
    'closed': []
  }
  
  workflowTransitions[selectedStatus.value] = defaults[selectedStatus.value] || []
}

const close = () => {
  emit('close')
}

const handleSave = async () => {
  processing.value = true
  
  try {
    const response = await fetch('/api/settings/workflow-transitions', {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.head.querySelector('meta[name="csrf-token"]').content
      },
      body: JSON.stringify({
        workflow_transitions: workflowTransitions
      })
    })
    
    const data = await response.json()
    
    if (response.ok) {
      emit('saved', workflowTransitions)
      close()
    } else {
      console.error('Failed to save workflow transitions:', data.message || 'Unknown error')
    }
  } catch (error) {
    console.error('Error saving workflow transitions:', error)
  } finally {
    processing.value = false
  }
}
</script>