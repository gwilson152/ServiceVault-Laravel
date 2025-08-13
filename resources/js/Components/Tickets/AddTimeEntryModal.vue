<template>
  <div class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center p-4 z-50">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
      <!-- Modal header -->
      <div class="px-6 py-4 border-b border-gray-200">
        <h3 class="text-lg font-medium text-gray-900">Add Time Entry</h3>
      </div>

      <!-- Modal body -->
      <form @submit.prevent="submitForm" class="px-6 py-4 space-y-4">
        <!-- User Selection (if can assign to others) -->
        <div v-if="canAssignToOthers">
          <label class="block text-sm font-medium text-gray-700 mb-1">
            Agent <span class="text-red-500">*</span>
          </label>
          <select 
            v-model="form.user_id" 
            required
            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
          >
            <option value="">Select Agent</option>
            <option v-for="agent in availableAgents" :key="agent.id" :value="agent.id">
              {{ agent.name }}
            </option>
          </select>
          <p v-if="errors.user_id" class="text-red-500 text-xs mt-1">{{ errors.user_id }}</p>
        </div>

        <!-- Billing Rate Selection -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">
            Billing Rate <span class="text-red-500">*</span>
          </label>
          <select 
            v-model="form.billing_rate_id" 
            required
            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
          >
            <option value="">Select Billing Rate</option>
            <option v-for="rate in availableBillingRates" :key="rate.id" :value="rate.id">
              {{ rate.name }} - ${{ rate.rate }}/hr
            </option>
          </select>
          <p v-if="errors.billing_rate_id" class="text-red-500 text-xs mt-1">{{ errors.billing_rate_id }}</p>
        </div>

        <!-- Date -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">
            Date <span class="text-red-500">*</span>
          </label>
          <input 
            v-model="form.date" 
            type="date" 
            required
            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
          />
          <p v-if="errors.date" class="text-red-500 text-xs mt-1">{{ errors.date }}</p>
        </div>

        <!-- Start Time -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">
            Start Time <span class="text-red-500">*</span>
          </label>
          <input 
            v-model="form.start_time" 
            type="time" 
            required
            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
          />
          <p v-if="errors.start_time" class="text-red-500 text-xs mt-1">{{ errors.start_time }}</p>
        </div>

        <!-- Duration -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">
            Duration <span class="text-red-500">*</span>
          </label>
          <div class="grid grid-cols-2 gap-2">
            <div>
              <input 
                v-model.number="form.hours" 
                type="number" 
                min="0" 
                max="23"
                placeholder="Hours"
                class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              />
            </div>
            <div>
              <input 
                v-model.number="form.minutes" 
                type="number" 
                min="0" 
                max="59"
                placeholder="Minutes"
                class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              />
            </div>
          </div>
          <p v-if="errors.duration" class="text-red-500 text-xs mt-1">{{ errors.duration }}</p>
          <p v-if="estimatedCost" class="text-sm text-gray-600 mt-1">
            Estimated cost: ${{ estimatedCost }}
          </p>
        </div>

        <!-- Description -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">
            Description <span class="text-red-500">*</span>
          </label>
          <textarea 
            v-model="form.description" 
            rows="3"
            required
            placeholder="Describe the work performed..."
            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
          ></textarea>
          <p v-if="errors.description" class="text-red-500 text-xs mt-1">{{ errors.description }}</p>
        </div>

        <!-- Billable Checkbox -->
        <div class="flex items-center">
          <input 
            v-model="form.billable" 
            type="checkbox" 
            id="billable"
            class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
          />
          <label for="billable" class="ml-2 text-sm text-gray-700">
            This time is billable to the client
          </label>
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
          :disabled="submitting"
          class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          {{ submitting ? 'Adding...' : 'Add Time Entry' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import axios from 'axios'

// Props
const props = defineProps({
  ticket: {
    type: Object,
    required: true
  }
})

// Emits
const emit = defineEmits(['saved', 'cancelled'])

// Reactive data
const submitting = ref(false)
const availableAgents = ref([])
const availableBillingRates = ref([])
const currentUser = ref(null)

// Form data
const form = ref({
  user_id: '',
  billing_rate_id: '',
  date: new Date().toISOString().split('T')[0],
  start_time: '',
  hours: 0,
  minutes: 0,
  description: '',
  billable: true
})

// Form errors
const errors = ref({})

// Computed properties
const canAssignToOthers = computed(() => {
  // Only show user selection if current user has permission to assign to others
  return currentUser.value?.permissions?.includes('time.admin') || 
         currentUser.value?.permissions?.includes('admin.manage')
})

const totalDurationMinutes = computed(() => {
  return (form.value.hours * 60) + form.value.minutes
})

const selectedBillingRate = computed(() => {
  return availableBillingRates.value.find(rate => rate.id == form.value.billing_rate_id)
})

const estimatedCost = computed(() => {
  if (!selectedBillingRate.value || totalDurationMinutes.value <= 0) return null
  const hours = totalDurationMinutes.value / 60
  return (hours * selectedBillingRate.value.rate).toFixed(2)
})

// Methods
const loadCurrentUser = async () => {
  try {
    const response = await axios.get('/api/auth/user')
    currentUser.value = response.data.data
    
    // If user cannot assign to others, set their ID as default
    if (!canAssignToOthers.value) {
      form.value.user_id = currentUser.value.id
    }
  } catch (error) {
    console.error('Failed to load current user:', error)
  }
}

const loadAvailableAgents = async () => {
  try {
    const response = await axios.get('/api/users', {
      params: {
        filter: 'can_create_time_entries',
        account_id: props.ticket.account_id
      }
    })
    availableAgents.value = response.data.data || []
  } catch (error) {
    console.error('Failed to load available agents:', error)
    availableAgents.value = []
  }
}

const loadBillingRates = async () => {
  try {
    const response = await axios.get('/api/billing-rates', {
      params: {
        account_id: props.ticket.account_id
      }
    })
    availableBillingRates.value = response.data.data || []
  } catch (error) {
    console.error('Failed to load billing rates:', error)
    availableBillingRates.value = []
  }
}

const validateForm = () => {
  errors.value = {}

  if (!form.value.user_id) {
    errors.value.user_id = 'Agent is required'
  }

  if (!form.value.billing_rate_id) {
    errors.value.billing_rate_id = 'Billing rate is required'
  }

  if (!form.value.date) {
    errors.value.date = 'Date is required'
  }

  if (!form.value.start_time) {
    errors.value.start_time = 'Start time is required'
  }

  if (totalDurationMinutes.value <= 0) {
    errors.value.duration = 'Duration must be greater than 0'
  }

  if (!form.value.description.trim()) {
    errors.value.description = 'Description is required'
  }

  return Object.keys(errors.value).length === 0
}

const submitForm = async () => {
  if (!validateForm()) return

  submitting.value = true

  try {
    const selectedRate = selectedBillingRate.value
    const payload = {
      user_id: form.value.user_id,
      account_id: props.ticket.account_id,
      ticket_id: props.ticket.id,
      billing_rate_id: form.value.billing_rate_id,
      rate_at_time: selectedRate.rate, // Capture current rate
      started_at: `${form.value.date} ${form.value.start_time}:00`,
      duration: totalDurationMinutes.value, // Duration in minutes
      description: form.value.description.trim(),
      billable: form.value.billable,
      status: 'pending'
    }

    await axios.post('/api/time-entries', payload)
    emit('saved')
  } catch (error) {
    if (error.response?.data?.errors) {
      errors.value = error.response.data.errors
    } else {
      console.error('Failed to create time entry:', error)
      errors.value = { general: 'Failed to create time entry. Please try again.' }
    }
  } finally {
    submitting.value = false
  }
}

// Watchers
watch(() => form.value.user_id, (newUserId) => {
  // Reset billing rate when user changes
  form.value.billing_rate_id = ''
  if (newUserId) {
    loadBillingRates()
  }
})

// Lifecycle
onMounted(async () => {
  await loadCurrentUser()
  
  if (canAssignToOthers.value) {
    await loadAvailableAgents()
  }
  
  // Load billing rates for initial user
  if (form.value.user_id) {
    await loadBillingRates()
  }

  // Set default start time to current time
  const now = new Date()
  form.value.start_time = now.toTimeString().slice(0, 5)
})
</script>