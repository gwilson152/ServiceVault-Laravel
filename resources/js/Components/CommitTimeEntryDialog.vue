<template>
  <Modal :show="show" @close="closeDialog" max-width="lg">
    <div class="p-6">
      <!-- Header -->
      <div class="flex items-center justify-between mb-6">
        <div>
          <h3 class="text-lg font-semibold text-gray-900">
            {{ isTimerCommit ? 'Commit Timer to Time Entry' : 'Add Manual Time Entry' }}
          </h3>
          <p class="text-sm text-gray-600 mt-1">
            {{ isTimerCommit 
                ? `Timer has been running for ${formatDuration(timerData?.elapsed || 0)}` 
                : 'Manually log time spent on this ticket' 
            }}
          </p>
        </div>
        <button @click="closeDialog" class="text-gray-400 hover:text-gray-600">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
          </svg>
        </button>
      </div>

      <!-- Form -->
      <form @submit.prevent="submitTimeEntry" class="space-y-6">
        <!-- Time Duration -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Time Duration <span class="text-red-500">*</span>
          </label>
          <div class="flex items-center space-x-3">
            <div class="flex items-center space-x-2">
              <input
                v-model.number="form.duration_hours"
                type="number"
                min="0"
                max="23"
                placeholder="0"
                class="w-20 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500"
              />
              <span class="text-sm text-gray-600">hours</span>
            </div>
            <div class="flex items-center space-x-2">
              <input
                v-model.number="form.duration_minutes"
                type="number"
                min="0"
                max="59"
                placeholder="0"
                class="w-20 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500"
              />
              <span class="text-sm text-gray-600">minutes</span>
            </div>
            <div class="text-sm text-gray-500">
              Total: {{ formatDuration(totalDurationSeconds) }}
            </div>
          </div>
          <div v-if="errors.duration" class="mt-1 text-sm text-red-600">{{ errors.duration }}</div>
        </div>

        <!-- Description -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Description <span class="text-red-500">*</span>
          </label>
          <textarea
            v-model="form.description"
            rows="3"
            placeholder="Describe the work performed..."
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500"
          ></textarea>
          <div v-if="errors.description" class="mt-1 text-sm text-red-600">{{ errors.description }}</div>
        </div>

        <!-- Context Selection -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <!-- Service Ticket -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Service Ticket <span class="text-red-500">*</span>
            </label>
            <select
              v-model="form.service_ticket_id"
              @change="onTicketChange"
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500"
            >
              <option value="">Select a ticket...</option>
              <option
                v-for="ticket in availableTickets"
                :key="ticket.id"
                :value="ticket.id"
              >
                {{ ticket.ticket_number }} - {{ ticket.title }}
              </option>
            </select>
            <div v-if="errors.service_ticket_id" class="mt-1 text-sm text-red-600">{{ errors.service_ticket_id }}</div>
          </div>

          <!-- Account (Auto-populated from ticket) -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Account
            </label>
            <input
              :value="selectedTicket?.account?.name || 'Select ticket first'"
              readonly
              class="w-full px-3 py-2 bg-gray-50 border border-gray-300 rounded-md text-gray-600"
            />
          </div>
        </div>

        <!-- Assignment and Billing -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <!-- Assigned User -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Assigned User
            </label>
            <select
              v-model="form.user_id"
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500"
            >
              <option :value="currentUser.id">{{ currentUser.name }} (Me)</option>
              <option
                v-for="user in assignableUsers"
                :key="user.id"
                :value="user.id"
              >
                {{ user.name }}
              </option>
            </select>
          </div>

          <!-- Billing Rate -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Billing Rate
            </label>
            <select
              v-model="form.billing_rate_id"
              @change="calculateBilledAmount"
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500"
            >
              <option value="">No billing rate</option>
              <option
                v-for="rate in availableBillingRates"
                :key="rate.id"
                :value="rate.id"
              >
                {{ rate.name }} - ${{ rate.rate }}/hour
              </option>
            </select>
          </div>
        </div>

        <!-- Billable and Amount -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div class="flex items-center">
            <input
              id="billable"
              v-model="form.billable"
              @change="calculateBilledAmount"
              type="checkbox"
              class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
            />
            <label for="billable" class="ml-2 block text-sm text-gray-700">
              Billable Time
            </label>
          </div>

          <div v-if="form.billable">
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Billed Amount
            </label>
            <div class="relative">
              <span class="absolute left-3 top-2 text-gray-500">$</span>
              <input
                v-model.number="form.billed_amount"
                type="number"
                step="0.01"
                min="0"
                readonly
                class="w-full pl-8 pr-3 py-2 bg-gray-50 border border-gray-300 rounded-md text-gray-600"
              />
            </div>
          </div>

          <div v-if="form.billable && selectedBillingRate">
            <div class="text-sm text-gray-600 pt-6">
              Rate: ${{ selectedBillingRate.rate }}/hour
              <br>
              Time: {{ formatDuration(totalDurationSeconds) }}
            </div>
          </div>
        </div>

        <!-- Approval Workflow -->
        <div v-if="requiresApproval" class="bg-yellow-50 border border-yellow-200 rounded-md p-4">
          <div class="flex items-start">
            <svg class="w-5 h-5 text-yellow-400 mt-0.5 mr-2" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
            </svg>
            <div>
              <h4 class="text-sm font-medium text-yellow-800">Approval Required</h4>
              <p class="text-sm text-yellow-700 mt-1">
                This time entry will require approval from a manager before being finalized.
              </p>
            </div>
          </div>
        </div>

        <!-- Notes -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Internal Notes (Optional)
          </label>
          <textarea
            v-model="form.notes"
            rows="2"
            placeholder="Add any internal notes about this time entry..."
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500"
          ></textarea>
        </div>

        <!-- Actions -->
        <div class="flex items-center justify-between pt-6 border-t border-gray-200">
          <button
            type="button"
            @click="closeDialog"
            class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-md hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500"
          >
            Cancel
          </button>

          <div class="flex items-center space-x-3">
            <div v-if="totalBilledAmount > 0" class="text-sm text-gray-600">
              Total: ${{ totalBilledAmount.toFixed(2) }}
            </div>
            <button
              type="submit"
              :disabled="!canSubmit || isSubmitting"
              class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <span v-if="isSubmitting">Saving...</span>
              <span v-else>{{ isTimerCommit ? 'Commit Time Entry' : 'Add Time Entry' }}</span>
            </button>
          </div>
        </div>
      </form>
    </div>
  </Modal>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { router } from '@inertiajs/vue3'
import Modal from '@/Components/Modal.vue'

// Props
const props = defineProps({
  show: {
    type: Boolean,
    default: false
  },
  timerData: {
    type: Object,
    default: null
  },
  ticketData: {
    type: Object,
    default: null
  },
  currentUser: {
    type: Object,
    required: true
  },
  availableTickets: {
    type: Array,
    default: () => []
  },
  availableBillingRates: {
    type: Array,
    default: () => []
  },
  assignableUsers: {
    type: Array,
    default: () => []
  }
})

// Emits
const emit = defineEmits(['close', 'submitted'])

// State
const form = ref({
  duration_hours: 0,
  duration_minutes: 0,
  description: '',
  service_ticket_id: '',
  user_id: '',
  billing_rate_id: '',
  billable: true,
  billed_amount: 0,
  notes: ''
})

const errors = ref({})
const isSubmitting = ref(false)

// Computed properties
const isTimerCommit = computed(() => !!props.timerData)

const totalDurationSeconds = computed(() => {
  return (form.value.duration_hours * 3600) + (form.value.duration_minutes * 60)
})

const selectedTicket = computed(() => {
  return props.availableTickets.find(ticket => ticket.id == form.value.service_ticket_id)
})

const selectedBillingRate = computed(() => {
  return props.availableBillingRates.find(rate => rate.id == form.value.billing_rate_id)
})

const requiresApproval = computed(() => {
  // This would be based on company policy, user role, amount, etc.
  return totalDurationSeconds.value > 28800 || // More than 8 hours
         (form.value.billed_amount > 1000) // High dollar amount
})

const totalBilledAmount = computed(() => {
  return form.value.billable ? form.value.billed_amount : 0
})

const canSubmit = computed(() => {
  return form.value.description.trim() && 
         form.value.service_ticket_id && 
         totalDurationSeconds.value > 0 &&
         !isSubmitting.value
})

// Methods
const formatDuration = (seconds) => {
  if (!seconds) return '0m'
  const hours = Math.floor(seconds / 3600)
  const minutes = Math.floor((seconds % 3600) / 60)
  return hours > 0 ? `${hours}h ${minutes}m` : `${minutes}m`
}

const onTicketChange = () => {
  // Auto-populate billing rate from ticket if available
  if (selectedTicket.value?.billing_rate_id) {
    form.value.billing_rate_id = selectedTicket.value.billing_rate_id
  }
  
  // Set assigned user to ticket's assigned user if available
  if (selectedTicket.value?.assigned_to) {
    form.value.user_id = selectedTicket.value.assigned_to
  }
  
  calculateBilledAmount()
}

const calculateBilledAmount = () => {
  if (form.value.billable && selectedBillingRate.value && totalDurationSeconds.value > 0) {
    const hours = totalDurationSeconds.value / 3600
    form.value.billed_amount = hours * selectedBillingRate.value.rate
  } else {
    form.value.billed_amount = 0
  }
}

const submitTimeEntry = async () => {
  if (!canSubmit.value) return
  
  errors.value = {}
  isSubmitting.value = true
  
  try {
    const payload = {
      duration: totalDurationSeconds.value,
      description: form.value.description,
      service_ticket_id: form.value.service_ticket_id,
      user_id: form.value.user_id || props.currentUser.id,
      billing_rate_id: form.value.billing_rate_id || null,
      billable: form.value.billable,
      billed_amount: form.value.billed_amount,
      notes: form.value.notes,
      requires_approval: requiresApproval.value,
      timer_id: props.timerData?.id || null
    }
    
    const response = await fetch('/api/time-entries', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.head.querySelector('meta[name="csrf-token"]').content,
      },
      body: JSON.stringify(payload)
    })
    
    if (!response.ok) {
      const errorData = await response.json()
      if (errorData.errors) {
        errors.value = errorData.errors
        return
      }
      throw new Error(errorData.message || 'Failed to create time entry')
    }
    
    const timeEntry = await response.json()
    
    emit('submitted', {
      timeEntry,
      wasTimerCommit: isTimerCommit.value
    })
    
    closeDialog()
    
  } catch (error) {
    console.error('Error creating time entry:', error)
    errors.value.general = error.message || 'An error occurred while creating the time entry'
  } finally {
    isSubmitting.value = false
  }
}

const closeDialog = () => {
  emit('close')
}

const resetForm = () => {
  form.value = {
    duration_hours: 0,
    duration_minutes: 0,
    description: '',
    service_ticket_id: '',
    user_id: props.currentUser.id,
    billing_rate_id: '',
    billable: true,
    billed_amount: 0,
    notes: ''
  }
  errors.value = {}
}

// Watchers
watch(() => props.show, (newValue) => {
  if (newValue) {
    resetForm()
    initializeForm()
  }
})

watch(() => [form.value.duration_hours, form.value.duration_minutes], () => {
  calculateBilledAmount()
})

// Initialize form with data
const initializeForm = () => {
  if (props.timerData) {
    // Timer commit mode
    const elapsed = props.timerData.elapsed || 0
    form.value.duration_hours = Math.floor(elapsed / 3600)
    form.value.duration_minutes = Math.floor((elapsed % 3600) / 60)
    form.value.description = props.timerData.description || ''
    form.value.service_ticket_id = props.timerData.service_ticket_id || ''
    form.value.billing_rate_id = props.timerData.billing_rate_id || ''
  } else if (props.ticketData) {
    // Manual entry mode with ticket context
    form.value.service_ticket_id = props.ticketData.id
    form.value.billing_rate_id = props.ticketData.billing_rate_id || ''
  }
  
  // Set default user
  form.value.user_id = props.currentUser.id
  
  // Calculate initial billed amount
  calculateBilledAmount()
}

// Initialize on mount if dialog is already open
onMounted(() => {
  if (props.show) {
    initializeForm()
  }
})
</script>

<style scoped>
/* Add any custom styles if needed */
</style>