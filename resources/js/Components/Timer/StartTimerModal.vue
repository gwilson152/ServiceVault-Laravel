<template>
  <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
      <!-- Background overlay -->
      <div 
        class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" 
        aria-hidden="true"
        @click="$emit('close')"
      />

      <!-- Modal panel -->
      <div class="inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
        <div>
          <!-- Header -->
          <div class="flex items-center justify-between mb-4">
            <div class="flex items-center">
              <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-green-100 sm:mx-0 sm:h-10 sm:w-10">
                <PlayIcon class="h-6 w-6 text-green-600" aria-hidden="true" />
              </div>
              <div class="ml-4">
                <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                  Start New Timer
                </h3>
                <p class="text-sm text-gray-500">
                  Create a new timer for tracking your work time
                </p>
              </div>
            </div>
            <button
              @click="$emit('close')"
              class="text-gray-400 hover:text-gray-600"
            >
              <XMarkIcon class="h-6 w-6" />
            </button>
          </div>

          <!-- Form -->
          <form @submit.prevent="startTimer" class="space-y-4">
            <!-- Timer Type Selection -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Timer Type</label>
              <div class="grid grid-cols-2 gap-2">
                <button
                  type="button"
                  @click="timerType = 'general'"
                  :class="[
                    'p-3 rounded-lg border-2 transition-colors text-left',
                    timerType === 'general' 
                      ? 'border-blue-500 bg-blue-50' 
                      : 'border-gray-200 hover:border-gray-300'
                  ]"
                >
                  <div class="font-medium text-sm">General Timer</div>
                  <div class="text-xs text-gray-500">For general work tracking</div>
                </button>
                <button
                  type="button"
                  @click="timerType = 'ticket'"
                  :class="[
                    'p-3 rounded-lg border-2 transition-colors text-left',
                    timerType === 'ticket' 
                      ? 'border-blue-500 bg-blue-50' 
                      : 'border-gray-200 hover:border-gray-300'
                  ]"
                >
                  <div class="font-medium text-sm">Ticket Timer</div>
                  <div class="text-xs text-gray-500">Link to a ticket</div>
                </button>
              </div>
            </div>

            <!-- Ticket Selection (if ticket type) -->
            <div v-if="timerType === 'ticket'">
              <label for="ticket_id" class="block text-sm font-medium text-gray-700 mb-1">
                Ticket
              </label>
              <select
                id="ticket_id"
                v-model="form.ticket_id"
                class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                :class="{ 'border-red-500': errors.ticket_id }"
              >
                <option value="">Select a ticket...</option>
                <option
                  v-for="ticket in availableTickets"
                  :key="ticket.id"
                  :value="ticket.id"
                >
                  #{{ ticket.ticket_number }} - {{ ticket.title }}
                </option>
              </select>
              <p v-if="errors.ticket_id" class="mt-1 text-sm text-red-600">
                {{ errors.ticket_id }}
              </p>
            </div>

            <!-- Description -->
            <div>
              <label for="description" class="block text-sm font-medium text-gray-700 mb-1">
                Description
                <span v-if="timerType === 'general'" class="text-red-500">*</span>
              </label>
              <input
                id="description"
                v-model="form.description"
                type="text"
                :placeholder="timerType === 'ticket' ? 'Optional timer description...' : 'Enter timer description...'"
                class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                :class="{ 'border-red-500': errors.description }"
              />
              <p v-if="errors.description" class="mt-1 text-sm text-red-600">
                {{ errors.description }}
              </p>
            </div>

            <!-- Billing Rate Selection -->
            <div v-if="availableBillingRates.length > 0">
              <label for="billing_rate_id" class="block text-sm font-medium text-gray-700 mb-1">
                Billing Rate
              </label>
              <select
                id="billing_rate_id"
                v-model="form.billing_rate_id"
                class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
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

            <!-- Advanced Options -->
            <div class="border-t pt-4">
              <button
                type="button"
                @click="showAdvancedOptions = !showAdvancedOptions"
                class="flex items-center text-sm text-gray-600 hover:text-gray-900"
              >
                <span>Advanced Options</span>
                <ChevronDownIcon 
                  :class="[
                    'ml-1 h-4 w-4 transition-transform',
                    showAdvancedOptions ? 'rotate-180' : ''
                  ]" 
                />
              </button>

              <div v-if="showAdvancedOptions" class="mt-3 space-y-3">
                <!-- Stop Other Timers -->
                <div class="flex items-center">
                  <input
                    id="stop_others"
                    v-model="form.stop_others"
                    type="checkbox"
                    class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                  />
                  <label for="stop_others" class="ml-2 block text-sm text-gray-700">
                    Stop other running timers
                  </label>
                </div>

                <!-- Auto-start -->
                <div class="flex items-center">
                  <input
                    id="auto_start"
                    v-model="form.auto_start"
                    type="checkbox"
                    class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                  />
                  <label for="auto_start" class="ml-2 block text-sm text-gray-700">
                    Start timer immediately
                  </label>
                </div>
              </div>
            </div>
          </form>
        </div>

        <!-- Actions -->
        <div class="mt-6 flex items-center justify-end space-x-3">
          <button
            type="button"
            @click="$emit('close')"
            class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
          >
            Cancel
          </button>
          <button
            @click="startTimer"
            :disabled="loading || !isFormValid"
            class="px-4 py-2 bg-green-600 text-white rounded-md text-sm font-medium hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 disabled:opacity-50 disabled:cursor-not-allowed flex items-center space-x-2"
          >
            <PlayIcon v-if="!loading" class="h-4 w-4" />
            <div v-else class="h-4 w-4 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
            <span>{{ loading ? 'Starting...' : 'Start Timer' }}</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { 
  PlayIcon, 
  XMarkIcon, 
  ChevronDownIcon 
} from '@heroicons/vue/24/outline'

const emit = defineEmits(['close', 'started'])

// Reactive state
const loading = ref(false)
const timerType = ref('general')
const showAdvancedOptions = ref(false)
const availableTickets = ref([])
const availableBillingRates = ref([])
const errors = ref({})

const form = ref({
  ticket_id: '',
  description: '',
  billing_rate_id: '',
  stop_others: false,
  auto_start: true
})

// Form validation
const isFormValid = computed(() => {
  if (timerType.value === 'general') {
    return form.value.description.trim() !== ''
  } else {
    return form.value.ticket_id !== ''
  }
})

// Load initial data
const loadTickets = async () => {
  try {
    const response = await axios.get('/api/tickets', {
      params: {
        status: ['open', 'in_progress', 'waiting_customer'],
        limit: 50
      }
    })
    availableTickets.value = response.data.data || []
  } catch (error) {
    console.error('Failed to load tickets:', error)
  }
}

const loadBillingRates = async () => {
  try {
    // This endpoint would need to be implemented
    const response = await axios.get('/api/billing-rates')
    availableBillingRates.value = response.data.data || []
  } catch (error) {
    console.error('Failed to load billing rates:', error)
    // Fallback - no billing rates available
    availableBillingRates.value = []
  }
}

// Start timer
const startTimer = async () => {
  if (loading.value || !isFormValid.value) return

  // Clear previous errors
  errors.value = {}
  loading.value = true

  try {
    const payload = {
      description: form.value.description.trim(),
      stop_others: form.value.stop_others,
      auto_start: form.value.auto_start
    }

    if (timerType.value === 'ticket' && form.value.ticket_id) {
      payload.ticket_id = parseInt(form.value.ticket_id)
    }

    if (form.value.billing_rate_id) {
      payload.billing_rate_id = parseInt(form.value.billing_rate_id)
    }

    const response = await axios.post('/api/timers', payload)
    
    emit('started', response.data.data)
  } catch (error) {
    console.error('Failed to start timer:', error)
    
    if (error.response?.data?.errors) {
      errors.value = error.response.data.errors
    } else {
      errors.value = {
        general: 'Failed to start timer. Please try again.'
      }
    }
  } finally {
    loading.value = false
  }
}

// Lifecycle
onMounted(() => {
  loadTickets()
  loadBillingRates()
})
</script>