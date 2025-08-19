<template>
  <div class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center p-4 z-50">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
      <!-- Modal header -->
      <div class="px-6 py-4 border-b border-gray-200">
        <h3 class="text-lg font-medium text-gray-900">
          {{ currentRate ? 'Update Billing Rate' : 'Set Billing Rate' }}
        </h3>
        <p class="text-sm text-gray-600 mt-1">
          {{ ticket.title }} ({{ ticket.ticket_number }})
        </p>
      </div>

      <!-- Modal body -->
      <form @submit.prevent="submitForm" class="px-6 py-4 space-y-4">
        <!-- Current Rate Display -->
        <div v-if="currentRate" class="bg-gray-50 rounded-lg p-4">
          <label class="block text-sm font-medium text-gray-700 mb-2">Current Rate</label>
          <div class="space-y-1">
            <p class="text-sm font-medium text-gray-900">{{ currentRate.name }}</p>
            <p class="text-sm text-gray-600">${{ currentRate.rate }}/hour</p>
            <p v-if="currentRate.description" class="text-xs text-gray-500">{{ currentRate.description }}</p>
          </div>
        </div>

        <!-- Rate Selection Method -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Rate Setup Method</label>
          <div class="space-y-2">
            <label class="flex items-center">
              <input 
                v-model="rateMethod" 
                type="radio" 
                value="existing"
                class="h-4 w-4 text-blue-600 border-gray-300 focus:ring-blue-500"
              />
              <span class="ml-2 text-sm text-gray-700">Choose from existing rates</span>
            </label>
            <label class="flex items-center">
              <input 
                v-model="rateMethod" 
                type="radio" 
                value="custom"
                class="h-4 w-4 text-blue-600 border-gray-300 focus:ring-blue-500"
              />
              <span class="ml-2 text-sm text-gray-700">Create custom rate</span>
            </label>
          </div>
        </div>

        <!-- Existing Rate Selection -->
        <div v-if="rateMethod === 'existing'">
          <label class="block text-sm font-medium text-gray-700 mb-1">
            Select Billing Rate <span class="text-red-500">*</span>
          </label>
          <select 
            v-model="form.billing_rate_id" 
            required
            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
          >
            <option value="">Choose a rate...</option>
            <option v-for="rate in availableRates" :key="rate.id" :value="rate.id">
              {{ rate.name }} - ${{ rate.rate }}/hour
            </option>
          </select>
          <p v-if="errors.billing_rate_id" class="text-red-500 text-xs mt-1">{{ errors.billing_rate_id }}</p>
        </div>

        <!-- Custom Rate Form -->
        <div v-if="rateMethod === 'custom'" class="space-y-4">
          <!-- Rate Name -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              Rate Name <span class="text-red-500">*</span>
            </label>
            <input 
              v-model="customRate.name" 
              type="text" 
              required
              placeholder="e.g., Senior Developer Rate"
              class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            />
            <p v-if="errors.name" class="text-red-500 text-xs mt-1">{{ errors.name }}</p>
          </div>

          <!-- Hourly Rate -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              Hourly Rate <span class="text-red-500">*</span>
            </label>
            <div class="relative">
              <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500">$</span>
              <input 
                v-model.number="customRate.rate" 
                type="number" 
                min="0"
                step="0.01"
                required
                placeholder="0.00"
                class="w-full border border-gray-300 rounded-md pl-8 pr-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              />
            </div>
            <p v-if="errors.rate" class="text-red-500 text-xs mt-1">{{ errors.rate }}</p>
          </div>


          <!-- Description -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              Description (optional)
            </label>
            <textarea 
              v-model="customRate.description" 
              rows="2"
              placeholder="Optional description of this billing rate..."
              class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            ></textarea>
          </div>

          <!-- Save as Template -->
          <div class="flex items-center">
            <input 
              v-model="customRate.save_as_template" 
              type="checkbox" 
              id="save_template"
              class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
            />
            <label for="save_template" class="ml-2 text-sm text-gray-700">
              Save as rate template for future use
            </label>
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

        <!-- Apply to Future Time Entries -->
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

        <!-- Retroactive Application -->
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
          {{ submitting ? 'Saving...' : (currentRate ? 'Update Rate' : 'Set Rate') }}
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
  },
  currentRate: {
    type: Object,
    default: null
  }
})

// Emits
const emit = defineEmits(['saved', 'cancelled'])

// Reactive data
const submitting = ref(false)
const availableRates = ref([])
const hasUnbilledEntries = ref(false)
const rateMethod = ref('existing')

// Form data
const form = ref({
  billing_rate_id: '',
  effective_date: new Date().toISOString().split('T')[0],
  apply_to_future: true,
  apply_retroactive: false
})

// Custom rate data
const customRate = ref({
  name: '',
  rate: 0,
  description: '',
  save_as_template: false
})

// Form errors
const errors = ref({})

// Computed properties
const today = computed(() => {
  return new Date().toISOString().split('T')[0]
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

const validateForm = () => {
  errors.value = {}

  if (rateMethod.value === 'existing') {
    if (!form.value.billing_rate_id) {
      errors.value.billing_rate_id = 'Please select a billing rate'
    }
  } else if (rateMethod.value === 'custom') {
    if (!customRate.value.name.trim()) {
      errors.value.name = 'Rate name is required'
    }
    if (!customRate.value.rate || customRate.value.rate <= 0) {
      errors.value.rate = 'Rate must be greater than 0'
    }
  }

  return Object.keys(errors.value).length === 0
}

const submitForm = async () => {
  if (!validateForm()) return

  submitting.value = true

  try {
    let payload = {
      ticket_id: props.ticket.id,
      effective_date: form.value.effective_date,
      apply_to_future: form.value.apply_to_future,
      apply_retroactive: form.value.apply_retroactive
    }

    if (rateMethod.value === 'existing') {
      payload.billing_rate_id = form.value.billing_rate_id
    } else {
      // Create custom rate first if needed
      const ratePayload = {
        name: customRate.value.name.trim(),
        rate: customRate.value.rate,
        description: customRate.value.description.trim() || null,
        is_template: customRate.value.save_as_template
      }

      const rateResponse = await axios.post('/api/billing-rates', ratePayload)
      payload.billing_rate_id = rateResponse.data.data.id
    }

    await axios.post(`/api/tickets/${props.ticket.id}/billing-rate`, payload)
    emit('saved')
  } catch (error) {
    if (error.response?.data?.errors) {
      errors.value = error.response.data.errors
    } else {
      console.error('Failed to set billing rate:', error)
      errors.value = { general: 'Failed to set billing rate. Please try again.' }
    }
  } finally {
    submitting.value = false
  }
}

// Watchers
watch(() => props.currentRate, (newRate) => {
  if (newRate) {
    form.value.billing_rate_id = newRate.id
  }
})

// Lifecycle
onMounted(() => {
  loadAvailableRates()
  checkUnbilledEntries()
  
  if (props.currentRate) {
    form.value.billing_rate_id = props.currentRate.id
  }
})
</script>