<template>
  <div class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center p-4 z-50">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
      <!-- Modal header -->
      <div class="px-6 py-4 border-b border-gray-200">
        <h3 class="text-lg font-medium text-gray-900">Assign Billing Rate</h3>
        <p class="text-sm text-gray-600 mt-1">
          {{ ticket.title }} ({{ ticket.ticket_number }})
        </p>
      </div>

      <!-- Modal body -->
      <form @submit.prevent="submitForm" class="px-6 py-4 space-y-4">
        <!-- No Current Rate Notice -->
        <div class="bg-yellow-50 border border-yellow-200 rounded-md p-4">
          <div class="flex">
            <svg class="w-5 h-5 text-yellow-400 mr-2 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <div class="text-sm">
              <p class="text-yellow-700 font-medium">No Billing Rate Assigned</p>
              <p class="text-yellow-600 mt-1">This ticket needs a billing rate for accurate invoicing.</p>
            </div>
          </div>
        </div>

        <!-- Quick Rate Selection -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">
            Select Billing Rate <span class="text-red-500">*</span>
          </label>
          <select 
            v-model="form.billing_rate_id" 
            required
            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
          >
            <option value="">Choose a rate...</option>
            <optgroup v-if="defaultRates.length > 0" label="Default Rates">
              <option v-for="rate in defaultRates" :key="rate.id" :value="rate.id">
                {{ rate.name }} - ${{ rate.rate }}/hour
              </option>
            </optgroup>
            <optgroup v-if="customRates.length > 0" label="Custom Rates">
              <option v-for="rate in customRates" :key="rate.id" :value="rate.id">
                {{ rate.name }} - ${{ rate.rate }}/hour
              </option>
            </optgroup>
          </select>
          <p v-if="errors.billing_rate_id" class="text-red-500 text-xs mt-1">{{ errors.billing_rate_id }}</p>
        </div>

        <!-- Selected Rate Preview -->
        <div v-if="selectedRate" class="bg-blue-50 border border-blue-200 rounded-md p-4">
          <h4 class="text-sm font-medium text-blue-900 mb-2">Selected Rate Details</h4>
          <div class="space-y-1 text-sm">
            <div class="flex justify-between">
              <span class="text-blue-700">Name:</span>
              <span class="font-medium text-blue-900">{{ selectedRate.name }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-blue-700">Rate:</span>
              <span class="font-medium text-blue-900">${{ selectedRate.rate }}/hour</span>
            </div>
            <div v-if="selectedRate.description" class="mt-2">
              <span class="text-blue-700">Description:</span>
              <p class="text-blue-600 text-xs mt-1">{{ selectedRate.description }}</p>
            </div>
          </div>
        </div>

        <!-- Effective Date -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">
            Effective Date
          </label>
          <input 
            v-model="form.effective_date" 
            type="date" 
            :max="today"
            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
          />
          <p class="text-xs text-gray-500 mt-1">When this rate becomes effective for billing calculations</p>
        </div>

        <!-- Options -->
        <div class="space-y-3">
          <!-- Apply to Future -->
          <div class="flex items-center">
            <input 
              v-model="form.apply_to_future" 
              type="checkbox" 
              id="apply_future"
              class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
            />
            <label for="apply_future" class="ml-2 text-sm text-gray-700">
              Apply this rate to future time entries automatically
            </label>
          </div>

          <!-- Apply Retroactively -->
          <div v-if="hasUnbilledEntries" class="flex items-center">
            <input 
              v-model="form.apply_retroactive" 
              type="checkbox" 
              id="apply_retro"
              class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
            />
            <label for="apply_retro" class="ml-2 text-sm text-gray-700">
              Apply this rate to existing unbilled time entries
            </label>
          </div>

          <!-- Set as Account Default -->
          <div v-if="canSetAccountDefault" class="flex items-center">
            <input 
              v-model="form.set_as_account_default" 
              type="checkbox" 
              id="account_default"
              class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
            />
            <label for="account_default" class="ml-2 text-sm text-gray-700">
              Set as default rate for this account
            </label>
          </div>
        </div>

        <!-- Create New Rate Link -->
        <div class="text-center pt-2">
          <button
            type="button"
            @click="showCreateRateForm = true"
            class="text-blue-600 hover:text-blue-700 text-sm underline"
          >
            Don't see the right rate? Create a new one
          </button>
        </div>

        <!-- Quick Rate Creation Form -->
        <div v-if="showCreateRateForm" class="border-t border-gray-200 pt-4 space-y-4">
          <h4 class="text-sm font-medium text-gray-900">Create New Rate</h4>
          
          <!-- Rate Name -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              Rate Name <span class="text-red-500">*</span>
            </label>
            <input 
              v-model="newRate.name" 
              type="text" 
              placeholder="e.g., Senior Developer Rate"
              class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            />
          </div>

          <!-- Hourly Rate -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              Hourly Rate <span class="text-red-500">*</span>
            </label>
            <div class="relative">
              <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500">$</span>
              <input 
                v-model.number="newRate.rate" 
                type="number" 
                min="0"
                step="0.01"
                placeholder="0.00"
                class="w-full border border-gray-300 rounded-md pl-8 pr-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              />
            </div>
          </div>

          <!-- Actions -->
          <div class="flex items-center justify-end space-x-2">
            <button
              type="button"
              @click="showCreateRateForm = false"
              class="px-3 py-1 text-xs text-gray-600 hover:text-gray-700"
            >
              Cancel
            </button>
            <button
              type="button"
              @click="createNewRate"
              :disabled="!newRate.name || !newRate.rate"
              class="px-3 py-1 text-xs bg-blue-600 text-white rounded hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              Create & Use
            </button>
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
          :disabled="submitting || !form.billing_rate_id"
          class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          {{ submitting ? 'Assigning...' : 'Assign Rate' }}
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
const emit = defineEmits(['assigned', 'cancelled'])

// Reactive data
const submitting = ref(false)
const availableRates = ref([])
const hasUnbilledEntries = ref(false)
const showCreateRateForm = ref(false)

// Form data
const form = ref({
  billing_rate_id: '',
  effective_date: new Date().toISOString().split('T')[0],
  apply_to_future: true,
  apply_retroactive: false,
  set_as_account_default: false
})

// New rate data
const newRate = ref({
  name: '',
  rate: 0
})

// Form errors
const errors = ref({})

// Computed properties
const today = computed(() => {
  return new Date().toISOString().split('T')[0]
})

const defaultRates = computed(() => {
  return availableRates.value.filter(rate => rate.is_default || rate.is_template)
})

const customRates = computed(() => {
  return availableRates.value.filter(rate => !rate.is_default && !rate.is_template)
})

const selectedRate = computed(() => {
  return availableRates.value.find(rate => rate.id == form.value.billing_rate_id)
})

const canSetAccountDefault = computed(() => {
  // TODO: Implement proper permission checking
  return true
})

// Methods
const loadAvailableRates = async () => {
  try {
    const response = await axios.get('/api/billing-rates')
    availableRates.value = response.data.data || []
  } catch (error) {
    console.error('Failed to load billing rates:', error)
    availableRates.value = []
  }
}

const checkUnbilledEntries = async () => {
  try {
    const response = await axios.get(`/api/tickets/${props.ticket.id}/time-entries?billable=true&status=unbilled`)
    hasUnbilledEntries.value = (response.data.data || []).length > 0
  } catch (error) {
    console.error('Failed to check unbilled entries:', error)
    hasUnbilledEntries.value = false
  }
}

const createNewRate = async () => {
  if (!newRate.value.name || !newRate.value.rate) return

  try {
    const ratePayload = {
      name: newRate.value.name.trim(),
      rate: newRate.value.rate,
      currency: 'USD',
      is_template: true
    }

    const response = await axios.post('/api/billing-rates', ratePayload)
    const createdRate = response.data.data

    // Add to available rates and select it
    availableRates.value.push(createdRate)
    form.value.billing_rate_id = createdRate.id

    // Reset form and hide creation form
    newRate.value = { name: '', rate: 0 }
    showCreateRateForm.value = false
  } catch (error) {
    console.error('Failed to create billing rate:', error)
  }
}

const validateForm = () => {
  errors.value = {}

  if (!form.value.billing_rate_id) {
    errors.value.billing_rate_id = 'Please select a billing rate'
  }

  return Object.keys(errors.value).length === 0
}

const submitForm = async () => {
  if (!validateForm()) return

  submitting.value = true

  try {
    const payload = {
      ticket_id: props.ticket.id,
      billing_rate_id: form.value.billing_rate_id,
      effective_date: form.value.effective_date,
      apply_to_future: form.value.apply_to_future,
      apply_retroactive: form.value.apply_retroactive,
      set_as_account_default: form.value.set_as_account_default
    }

    await axios.post(`/api/tickets/${props.ticket.id}/billing-rate`, payload)
    emit('assigned')
  } catch (error) {
    if (error.response?.data?.errors) {
      errors.value = error.response.data.errors
    } else {
      console.error('Failed to assign billing rate:', error)
      errors.value = { general: 'Failed to assign billing rate. Please try again.' }
    }
  } finally {
    submitting.value = false
  }
}

// Lifecycle
onMounted(() => {
  loadAvailableRates()
  checkUnbilledEntries()
})
</script>