<template>
  <div class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center p-4 z-50">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
      <!-- Modal header -->
      <div class="px-6 py-4 border-b border-gray-200">
        <h3 class="text-lg font-medium text-gray-900">Change Ticket Status</h3>
        <p class="text-sm text-gray-600 mt-1">{{ ticket.title }} ({{ ticket.ticket_number }})</p>
      </div>

      <!-- Modal body -->
      <form @submit.prevent="submitForm" class="px-6 py-4 space-y-4">
        <!-- Current Status Display -->
        <div class="bg-gray-50 rounded-lg p-3">
          <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Current Status</label>
          <div class="flex items-center space-x-2">
            <span :class="getStatusClasses(ticket.status)" class="px-2 py-1 rounded-full text-xs font-medium">
              {{ formatStatus(ticket.status) }}
            </span>
            <span v-if="ticket.status_changed_at" class="text-xs text-gray-500">
              since {{ formatDateTime(ticket.status_changed_at) }}
            </span>
          </div>
        </div>

        <!-- New Status Selection -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">
            New Status <span class="text-red-500">*</span>
          </label>
          <select 
            v-model="form.status" 
            required
            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
          >
            <option value="">Select new status...</option>
            <option 
              v-for="status in availableStatuses" 
              :key="status.value" 
              :value="status.value"
              :disabled="status.value === ticket.status"
            >
              {{ status.label }}
              <span v-if="status.value === ticket.status">(Current)</span>
            </option>
          </select>
          <p v-if="errors.status" class="text-red-500 text-xs mt-1">{{ errors.status }}</p>
        </div>

        <!-- Status Preview -->
        <div v-if="form.status && form.status !== ticket.status" class="bg-blue-50 border border-blue-200 rounded-md p-4">
          <h4 class="text-sm font-medium text-blue-900 mb-2">Status Change Preview</h4>
          <div class="flex items-center space-x-3 text-sm">
            <span :class="getStatusClasses(ticket.status)" class="px-2 py-1 rounded-full text-xs font-medium">
              {{ formatStatus(ticket.status) }}
            </span>
            <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
            </svg>
            <span :class="getStatusClasses(form.status)" class="px-2 py-1 rounded-full text-xs font-medium">
              {{ formatStatus(form.status) }}
            </span>
          </div>
        </div>

        <!-- Status Change Reason -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">
            Reason for Change <span v-if="requiresReason" class="text-red-500">*</span>
          </label>
          <textarea 
            v-model="form.reason" 
            rows="3"
            :required="requiresReason"
            placeholder="Explain why the status is being changed..."
            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
          ></textarea>
          <p v-if="errors.reason" class="text-red-500 text-xs mt-1">{{ errors.reason }}</p>
        </div>

        <!-- Resolution Details (for resolved/closed status) -->
        <div v-if="isResolutionStatus" class="space-y-3">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              Resolution Type
            </label>
            <select 
              v-model="form.resolution_type" 
              class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            >
              <option value="">Select resolution type...</option>
              <option value="solved">Solved</option>
              <option value="workaround">Workaround Provided</option>
              <option value="not_reproducible">Not Reproducible</option>
              <option value="duplicate">Duplicate</option>
              <option value="wont_fix">Won't Fix</option>
              <option value="customer_closed">Customer Closed</option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              Resolution Summary
            </label>
            <textarea 
              v-model="form.resolution_summary" 
              rows="2"
              placeholder="Brief summary of how the ticket was resolved..."
              class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            ></textarea>
          </div>
        </div>

        <!-- Notification Options -->
        <div class="space-y-2">
          <div class="flex items-center">
            <input 
              v-model="form.notify_assigned_user" 
              type="checkbox" 
              id="notify_assigned"
              class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
            />
            <label for="notify_assigned" class="ml-2 text-sm text-gray-700">
              Notify assigned user of status change
            </label>
          </div>
          
          <div class="flex items-center">
            <input 
              v-model="form.notify_customer" 
              type="checkbox" 
              id="notify_customer"
              class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
            />
            <label for="notify_customer" class="ml-2 text-sm text-gray-700">
              Notify customer of status change
            </label>
          </div>

          <div v-if="isCustomerFacingStatus" class="flex items-center">
            <input 
              v-model="form.send_status_email" 
              type="checkbox" 
              id="status_email"
              class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
            />
            <label for="status_email" class="ml-2 text-sm text-gray-700">
              Send automated status update email
            </label>
          </div>
        </div>

        <!-- Status Warnings -->
        <div v-if="statusWarning" class="bg-yellow-50 border border-yellow-200 rounded-md p-4">
          <div class="flex">
            <svg class="w-5 h-5 text-yellow-400 mr-2 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <div class="text-sm">
              <p class="text-yellow-700 font-medium">{{ statusWarning.title }}</p>
              <p class="text-yellow-600 mt-1">{{ statusWarning.message }}</p>
            </div>
          </div>
        </div>
      </form>

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
          @click="submitForm"
          :disabled="submitting || !form.status || form.status === ticket.status"
          class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          {{ submitting ? 'Updating...' : 'Update Status' }}
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
  ticket: {
    type: Object,
    required: true
  }
})

// Emits
const emit = defineEmits(['updated', 'cancelled'])

// Reactive data
const submitting = ref(false)
const availableStatuses = ref([])

// Form data
const form = ref({
  status: '',
  reason: '',
  resolution_type: '',
  resolution_summary: '',
  notify_assigned_user: true,
  notify_customer: true,
  send_status_email: false
})

// Form errors
const errors = ref({})

// Computed properties
const requiresReason = computed(() => {
  const reasonRequiredStatuses = ['on_hold', 'cancelled', 'resolved', 'closed']
  return reasonRequiredStatuses.includes(form.value.status)
})

const isResolutionStatus = computed(() => {
  return ['resolved', 'closed'].includes(form.value.status)
})

const isCustomerFacingStatus = computed(() => {
  const customerStatuses = ['resolved', 'closed', 'waiting_customer']
  return customerStatuses.includes(form.value.status)
})

const statusWarning = computed(() => {
  if (!form.value.status) return null

  const warnings = {
    'on_hold': {
      title: 'Ticket On Hold',
      message: 'This ticket will be paused and no work will be performed until status changes.'
    },
    'cancelled': {
      title: 'Ticket Cancellation',
      message: 'This ticket will be permanently cancelled and cannot be reopened.'
    },
    'resolved': {
      title: 'Ticket Resolution',
      message: 'Customer will be notified that the ticket has been resolved and solution provided.'
    },
    'closed': {
      title: 'Ticket Closure',
      message: 'This ticket will be permanently closed and archived.'
    }
  }

  return warnings[form.value.status] || null
})

// Methods
const formatStatus = (status) => {
  return status?.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase()) || 'Unknown'
}

const formatDateTime = (date) => {
  if (!date) return 'N/A'
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const getStatusClasses = (status) => {
  const statusMap = {
    'open': 'bg-blue-100 text-blue-800',
    'in_progress': 'bg-yellow-100 text-yellow-800',
    'waiting_customer': 'bg-purple-100 text-purple-800',
    'on_hold': 'bg-gray-100 text-gray-800',
    'resolved': 'bg-green-100 text-green-800',
    'closed': 'bg-gray-100 text-gray-800',
    'cancelled': 'bg-red-100 text-red-800'
  }
  
  return statusMap[status] || 'bg-gray-100 text-gray-800'
}

const loadAvailableStatuses = async () => {
  try {
    const response = await axios.get('/api/ticket-statuses')
    availableStatuses.value = response.data.data || [
      { value: 'open', label: 'Open' },
      { value: 'in_progress', label: 'In Progress' },
      { value: 'waiting_customer', label: 'Waiting for Customer' },
      { value: 'on_hold', label: 'On Hold' },
      { value: 'resolved', label: 'Resolved' },
      { value: 'closed', label: 'Closed' },
      { value: 'cancelled', label: 'Cancelled' }
    ]
  } catch (error) {
    console.error('Failed to load available statuses:', error)
    // Fallback to default statuses
    availableStatuses.value = [
      { value: 'open', label: 'Open' },
      { value: 'in_progress', label: 'In Progress' },
      { value: 'waiting_customer', label: 'Waiting for Customer' },
      { value: 'on_hold', label: 'On Hold' },
      { value: 'resolved', label: 'Resolved' },
      { value: 'closed', label: 'Closed' },
      { value: 'cancelled', label: 'Cancelled' }
    ]
  }
}

const validateForm = () => {
  errors.value = {}

  if (!form.value.status) {
    errors.value.status = 'Please select a new status'
  }

  if (form.value.status === props.ticket.status) {
    errors.value.status = 'Please select a different status'
  }

  if (requiresReason.value && !form.value.reason.trim()) {
    errors.value.reason = 'Reason is required for this status change'
  }

  return Object.keys(errors.value).length === 0
}

const submitForm = async () => {
  if (!validateForm()) return

  submitting.value = true

  try {
    const payload = {
      status: form.value.status,
      status_change_reason: form.value.reason.trim() || null,
      resolution_type: form.value.resolution_type || null,
      resolution_summary: form.value.resolution_summary.trim() || null,
      notify_assigned_user: form.value.notify_assigned_user,
      notify_customer: form.value.notify_customer,
      send_status_email: form.value.send_status_email
    }

    await axios.put(`/api/tickets/${props.ticket.id}/status`, payload)
    emit('updated')
  } catch (error) {
    if (error.response?.data?.errors) {
      errors.value = error.response.data.errors
    } else {
      console.error('Failed to update ticket status:', error)
      errors.value = { general: 'Failed to update ticket status. Please try again.' }
    }
  } finally {
    submitting.value = false
  }
}

// Lifecycle
onMounted(() => {
  loadAvailableStatuses()
})
</script>