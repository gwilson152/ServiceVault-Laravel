<template>
  <div class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center p-4 z-50">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
      <!-- Modal header -->
      <div class="px-6 py-4 border-b border-gray-200">
        <h3 class="text-lg font-medium text-gray-900">
          {{ action === 'approve' ? 'Approve Add-on' : 'Reject Add-on' }}
        </h3>
        <p class="text-sm text-gray-600 mt-1">{{ addon?.title }}</p>
      </div>

      <!-- Modal body -->
      <div class="px-6 py-4 space-y-4">
        <!-- Add-on Summary -->
        <div class="bg-gray-50 rounded-lg p-4 space-y-2">
          <div class="flex justify-between text-sm">
            <span class="text-gray-600">Type:</span>
            <span class="font-medium text-gray-900">{{ formatAddonType(addon?.type) }}</span>
          </div>
          <div class="flex justify-between text-sm">
            <span class="text-gray-600">Priority:</span>
            <span :class="getPriorityClasses(addon?.priority)" class="px-2 py-1 rounded-full text-xs font-medium">
              {{ formatPriority(addon?.priority) }}
            </span>
          </div>
          <div class="flex justify-between text-sm">
            <span class="text-gray-600">Estimated Hours:</span>
            <span class="font-medium text-gray-900">{{ addon?.estimated_hours }}h</span>
          </div>
          <div class="flex justify-between text-sm">
            <span class="text-gray-600">Estimated Cost:</span>
            <span class="font-medium text-green-600">${{ addon?.estimated_cost }}</span>
          </div>
          <div v-if="addon?.expected_completion_date" class="flex justify-between text-sm">
            <span class="text-gray-600">Expected Delivery:</span>
            <span class="font-medium text-gray-900">{{ formatDate(addon?.expected_completion_date) }}</span>
          </div>
        </div>

        <!-- Description -->
        <div v-if="addon?.description">
          <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
          <div class="p-3 bg-gray-50 rounded-md text-sm text-gray-700">
            {{ addon.description }}
          </div>
        </div>

        <!-- Justification -->
        <div v-if="addon?.justification">
          <label class="block text-sm font-medium text-gray-700 mb-1">Business Justification</label>
          <div class="p-3 bg-gray-50 rounded-md text-sm text-gray-700">
            {{ addon.justification }}
          </div>
        </div>

        <!-- Approval Form -->
        <form @submit.prevent="submitApproval">
          <!-- Approval Notes -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              {{ action === 'approve' ? 'Approval' : 'Rejection' }} Notes
              <span v-if="action === 'reject'" class="text-red-500">*</span>
            </label>
            <textarea 
              v-model="form.notes" 
              rows="3"
              :required="action === 'reject'"
              :placeholder="action === 'approve' 
                ? 'Optional notes about the approval...' 
                : 'Please explain why this add-on is being rejected...'"
              class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            ></textarea>
            <p v-if="errors.notes" class="text-red-500 text-xs mt-1">{{ errors.notes }}</p>
          </div>

          <!-- Adjusted Estimates (for approval only) -->
          <div v-if="action === 'approve'" class="space-y-3">
            <div class="border-t border-gray-200 pt-4">
              <h4 class="text-sm font-medium text-gray-900 mb-3">Adjust Estimates (Optional)</h4>
              
              <!-- Approved Hours -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                  Approved Hours
                </label>
                <input 
                  v-model.number="form.approved_hours" 
                  type="number" 
                  min="0"
                  step="0.5"
                  :placeholder="addon?.estimated_hours?.toString()"
                  class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                />
                <p class="text-xs text-gray-500 mt-1">Leave blank to use estimated hours ({{ addon?.estimated_hours }}h)</p>
              </div>
              
              <!-- Approved Cost -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                  Approved Cost
                </label>
                <div class="relative">
                  <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500">$</span>
                  <input 
                    v-model.number="form.approved_cost" 
                    type="number" 
                    min="0"
                    step="0.01"
                    :placeholder="addon?.estimated_cost?.toString()"
                    class="w-full border border-gray-300 rounded-md pl-8 pr-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                  />
                </div>
                <p class="text-xs text-gray-500 mt-1">Leave blank to use estimated cost (${{ addon?.estimated_cost }})</p>
              </div>
              
              <!-- Approved Delivery Date -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                  Approved Delivery Date
                </label>
                <input 
                  v-model="form.approved_completion_date" 
                  type="date" 
                  :min="today"
                  :value="addon?.expected_completion_date"
                  class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                />
              </div>
            </div>
          </div>
        </form>

        <!-- Confirmation Warning -->
        <div :class="action === 'approve' ? 'bg-green-50 border-green-200' : 'bg-red-50 border-red-200'" class="border rounded-md p-4">
          <div class="flex">
            <svg v-if="action === 'approve'" class="w-5 h-5 text-green-400 mr-2 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <svg v-else class="w-5 h-5 text-red-400 mr-2 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <div class="text-sm">
              <p :class="action === 'approve' ? 'text-green-700' : 'text-red-700'" class="font-medium">
                {{ action === 'approve' ? 'Approve Add-on' : 'Reject Add-on' }}
              </p>
              <p :class="action === 'approve' ? 'text-green-600' : 'text-red-600'" class="mt-1">
                {{ action === 'approve' 
                  ? 'This will authorize work to begin on this add-on.' 
                  : 'This will permanently reject this add-on request.' }}
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- Modal footer -->
      <div class="px-6 py-4 border-t border-gray-200 flex items-center justify-end space-x-3">
        <button
          @click="$emit('cancelled')"
          type="button"
          class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
        >
          Cancel
        </button>
        <button
          @click="submitApproval"
          :disabled="submitting"
          :class="action === 'approve' 
            ? 'bg-green-600 hover:bg-green-700 focus:ring-green-500' 
            : 'bg-red-600 hover:bg-red-700 focus:ring-red-500'"
          class="px-4 py-2 text-sm font-medium text-white border border-transparent rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          {{ submitting ? 'Processing...' : (action === 'approve' ? 'Approve Add-on' : 'Reject Add-on') }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'

// Props
const props = defineProps({
  addon: {
    type: Object,
    required: true
  },
  action: {
    type: String,
    required: true,
    validator: value => ['approve', 'reject'].includes(value)
  }
})

// Emits
const emit = defineEmits(['approved', 'cancelled'])

// Reactive data
const submitting = ref(false)

// Form data
const form = ref({
  notes: '',
  approved_hours: null,
  approved_cost: null,
  approved_completion_date: ''
})

// Form errors
const errors = ref({})

// Computed properties
const today = computed(() => {
  return new Date().toISOString().split('T')[0]
})

// Methods
const formatAddonType = (type) => {
  const typeMap = {
    'additional_work': 'Additional Work',
    'emergency_support': 'Emergency Support',
    'consultation': 'Consultation',
    'training': 'Training',
    'custom': 'Custom'
  }
  return typeMap[type] || type
}

const formatPriority = (priority) => {
  return priority?.charAt(0).toUpperCase() + priority?.slice(1) || 'Normal'
}

const getPriorityClasses = (priority) => {
  const priorityMap = {
    'low': 'bg-gray-100 text-gray-800',
    'normal': 'bg-blue-100 text-blue-800',
    'high': 'bg-orange-100 text-orange-800',
    'urgent': 'bg-red-100 text-red-800'
  }
  
  return priorityMap[priority] || 'bg-gray-100 text-gray-800'
}

const formatDate = (date) => {
  if (!date) return 'N/A'
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

const validateForm = () => {
  errors.value = {}

  if (props.action === 'reject' && !form.value.notes.trim()) {
    errors.value.notes = 'Rejection reason is required'
  }

  return Object.keys(errors.value).length === 0
}

const submitApproval = async () => {
  if (!validateForm()) return

  submitting.value = true

  try {
    const endpoint = props.action === 'approve' 
      ? `/api/ticket-addons/${props.addon.id}/approve`
      : `/api/ticket-addons/${props.addon.id}/reject`

    const payload = {
      notes: form.value.notes.trim()
    }

    // Include approval adjustments if approving
    if (props.action === 'approve') {
      if (form.value.approved_hours) {
        payload.approved_hours = form.value.approved_hours
      }
      if (form.value.approved_cost) {
        payload.approved_cost = form.value.approved_cost
      }
      if (form.value.approved_completion_date) {
        payload.approved_completion_date = form.value.approved_completion_date
      }
    }

    await axios.post(endpoint, payload)
    emit('approved')
  } catch (error) {
    if (error.response?.data?.errors) {
      errors.value = error.response.data.errors
    } else {
      console.error(`Failed to ${props.action} addon:`, error)
      errors.value = { general: `Failed to ${props.action} add-on. Please try again.` }
    }
  } finally {
    submitting.value = false
  }
}

// Lifecycle
onMounted(() => {
  // Pre-populate approved values with estimates
  if (props.action === 'approve' && props.addon) {
    form.value.approved_completion_date = props.addon.expected_completion_date || ''
  }
})
</script>