<template>
  <StackedDialog
    :show="show"
    :title="mode === 'edit' ? 'Edit Timer' : 'Start New Timer'"
    max-width="2xl"
    :allow-dropdowns="true"
    @close="$emit('close')"
  >
    <!-- Error messages -->
    <template #errors>
      <div v-if="Object.keys(errors).length > 0" class="mb-4 bg-red-50 border border-red-200 rounded-md p-3">
        <div class="flex">
          <svg class="h-5 w-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
          <div class="ml-3 text-sm text-red-700">
            Please check the highlighted fields and try again.
          </div>
        </div>
      </div>
    </template>

    <!-- Content -->
    <template #default>
      <!-- Timer Information -->
      <div class="space-y-4">
        <!-- Description -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              Description <span class="text-red-500">*</span>
            </label>
            <textarea
              v-model="form.description"
              rows="3"
              placeholder="Describe the work you're timing..."
              class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              :class="{ 'border-red-500': errors.description }"
              required
            />
            <p v-if="errors.description" class="mt-1 text-sm text-red-600">
              {{ errors.description }}
            </p>
          </div>

        <!-- Assignment Section -->
        <div class="space-y-4">
          
          <!-- Account Selection -->
          <div>
            <UnifiedSelector
              v-model="form.accountId"
              type="account"
              :items="availableAccounts"
              label="Account"
              placeholder="Select account (optional for general timers)"
              :error="errors.accountId"
              :can-create="true"
              :nested="true"
              @item-selected="handleAccountSelected"
            />
          </div>

          <!-- Ticket Selection (if account selected) -->
          <div v-if="form.accountId">
            <UnifiedSelector
              v-model="form.ticketId"
              type="ticket"
              :items="availableTickets"
              :loading="ticketsLoading"
              label="Ticket"
              placeholder="Select ticket (optional)"
              :disabled="!form.accountId"
              :error="errors.ticketId"
              :can-create="true"
              :nested="true"
              :create-modal-props="{
                prefilledAccountId: form.accountId,
                availableAccounts: availableAccounts,
                canAssignTickets: canAssignToOthers
              }"
              @item-selected="handleTicketSelected"
              @item-created="handleTicketCreated"
            />
          </div>

          <!-- Agent Assignment (all users, but managers can reassign) -->
          <div v-if="canAssignToOthers">
            <UnifiedSelector
              v-model="form.userId"
              type="agent"
              :items="availableAgents"
              :loading="agentsLoading"
              label="Service Agent"
              placeholder="Select an agent to assign this timer..."
              :error="errors.userId"
              :agent-type="'timer'"
              @item-selected="handleAgentSelected"
            />
            <p class="mt-1 text-xs text-gray-500">The service agent who will perform this work (defaults to you)</p>
          </div>
          
          <!-- Current User Assignment (for regular users - read-only) -->
          <div v-else-if="user">
            <label class="block text-sm font-medium text-gray-700 mb-1">
              Service Agent
            </label>
            <div class="bg-gray-50 border border-gray-300 rounded-md px-3 py-2 text-sm text-gray-900">
              <div class="flex items-center space-x-2">
                <div class="w-6 h-6 bg-blue-100 rounded-full flex items-center justify-center">
                  <svg class="w-3 h-3 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                  </svg>
                </div>
                <span class="font-medium">{{ user.name }}</span>
                <span class="text-xs text-gray-500">(You)</span>
              </div>
            </div>
            <p class="mt-1 text-xs text-gray-500">You will be assigned as the service agent for this timer</p>
          </div>
        </div>

        <!-- Billing Section -->
        <div class="space-y-4">
          
          <!-- Billing Rate Selection -->
          <div>
            <UnifiedSelector
              v-model="form.billingRateId"
              type="billing-rate"
              :items="availableBillingRates"
              :loading="billingRatesLoading"
              label="Billing Rate"
              placeholder="No billing rate (non-billable)"
              :error="errors.billingRateId"
              :show-rate-hierarchy="true"
              @item-selected="handleRateSelected"
            />
          </div>

          <!-- Billing Info Display -->
          <div v-if="selectedBillingRate" class="bg-blue-50 rounded-lg p-3">
            <div class="text-sm text-blue-800">
              <p><strong>{{ selectedBillingRate.name }}</strong> - ${{ selectedBillingRate.rate }}/hour</p>
              <p v-if="selectedBillingRate.description" class="text-xs mt-1 text-blue-600">{{ selectedBillingRate.description }}</p>
            </div>
          </div>

          <div v-else class="bg-gray-50 rounded-lg p-3">
            <p class="text-sm text-gray-600">
              <strong>Non-billable timer</strong> - This timer will not be associated with any billing rate.
            </p>
          </div>
        </div>
      </div>


    </template>

    <!-- Footer with action buttons -->
    <template #footer>
      <div class="flex items-center justify-between">
        <div v-if="mode === 'edit'" class="flex items-center text-sm text-gray-500">
          <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
          Editing active timer
        </div>
        
        <div class="flex items-center space-x-3">
          <button
            type="button"
            @click="$emit('close')"
            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
          >
            Cancel
          </button>
          <button
            type="button"
            @click="submitTimer"
            :disabled="loading || !isFormValid"
            class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <span v-if="loading" class="flex items-center">
              <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              {{ mode === 'edit' ? 'Updating...' : 'Starting...' }}
            </span>
            <span v-else>
              {{ mode === 'edit' ? 'Update Timer' : 'Start Timer' }}
            </span>
          </button>
        </div>
      </div>
    </template>
  </StackedDialog>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { PlayIcon } from '@heroicons/vue/24/outline'
import { usePage } from '@inertiajs/vue3'
import StackedDialog from '@/Components/StackedDialog.vue'
import UnifiedSelector from '@/Components/UI/UnifiedSelector.vue'
import axios from 'axios'

const props = defineProps({
  show: {
    type: Boolean,
    default: false
  },
  mode: {
    type: String,
    default: 'create',
    validator: (value) => ['create', 'edit'].includes(value)
  },
  timer: {
    type: Object,
    default: null
  },
  prefilledAccountId: {
    type: [String, Number],
    default: null
  },
  prefilledTicketId: {
    type: [String, Number],
    default: null
  }
})

const emit = defineEmits(['close', 'timer-started', 'timer-updated'])


// State
const loading = ref(false)
const showAdvancedOptions = ref(false)
const errors = ref({})

// Data arrays
const availableAccounts = ref([])
const availableTickets = ref([])
const availableAgents = ref([])
const availableBillingRates = ref([])

// Loading states
const ticketsLoading = ref(false)
const agentsLoading = ref(false)
const billingRatesLoading = ref(false)

// Form data
const form = ref({
  description: '',
  accountId: props.prefilledAccountId,
  ticketId: props.prefilledTicketId,
  userId: null,
  billingRateId: null,
  stopOthers: true,
  autoStart: true
})

// Page data
const page = usePage()
const user = computed(() => page.props.auth?.user)

// Computed properties
const canAssignToOthers = computed(() => {
  return user.value?.permissions?.includes('timers.manage') ||
         user.value?.permissions?.includes('admin.manage') ||
         user.value?.permissions?.includes('admin.write')
})

const isFormValid = computed(() => {
  return form.value.description?.trim().length > 0
})

const selectedAccount = computed(() => {
  return availableAccounts.value.find(acc => acc.id == form.value.accountId)
})

const selectedTicket = computed(() => {
  return availableTickets.value.find(ticket => ticket.id == form.value.ticketId)
})

const selectedAgent = computed(() => {
  return availableAgents.value.find(agent => agent.id == form.value.userId)
})

const selectedBillingRate = computed(() => {
  return availableBillingRates.value.find(rate => rate.id == form.value.billingRateId)
})

// Methods
const handleAccountSelected = (account) => {
  form.value.ticketId = null
  form.value.billingRateId = null // Reset billing rate when account changes
  loadTickets()
  loadBillingRates() // This will auto-select appropriate billing rate
}

const handleTicketSelected = (ticket) => {
  // Handle ticket selection
}

const handleTicketCreated = (ticket) => {
  form.value.ticketId = ticket.id
  loadTickets()
}

const handleAgentSelected = (agent) => {
  // Handle agent selection
}

const handleRateSelected = (rate) => {
  // Handle billing rate selection
}

const loadAccounts = async () => {
  try {
    const response = await axios.get('/api/accounts')
    availableAccounts.value = response.data.data
  } catch (error) {
    console.error('Failed to load accounts:', error)
  }
}

const loadTickets = async () => {
  if (!form.value.accountId) {
    availableTickets.value = []
    return
  }

  try {
    ticketsLoading.value = true
    const response = await axios.get(`/api/tickets?account_id=${form.value.accountId}`)
    availableTickets.value = response.data.data
  } catch (error) {
    console.error('Failed to load tickets:', error)
  } finally {
    ticketsLoading.value = false
  }
}

const loadAgents = async () => {
  if (!canAssignToOthers.value) return

  try {
    agentsLoading.value = true
    const response = await axios.get('/api/users/agents?agent_type=timer')
    availableAgents.value = response.data.data
  } catch (error) {
    console.error('Failed to load agents:', error)
  } finally {
    agentsLoading.value = false
  }
}

const loadBillingRates = async () => {
  try {
    billingRatesLoading.value = true
    const params = form.value.accountId ? `?account_id=${form.value.accountId}` : ''
    const response = await axios.get(`/api/billing-rates${params}`)
    
    // Filter to only show rates for the selected account or global rates
    const allRates = response.data.data
    
    availableBillingRates.value = allRates.filter(rate => {
      // Always include global rates (no account_id)
      if (!rate.account_id) return true
      
      // Include account-specific rates only if they match the selected account
      if (form.value.accountId) {
        return rate.account_id === parseInt(form.value.accountId)
      }
      
      // If no account selected, exclude all account-specific rates
      return false
    })
    
    
    // Check if currently selected billing rate is still valid after filtering
    if (form.value.billingRateId) {
      const currentRateStillValid = availableBillingRates.value.find(rate => 
        rate.id === form.value.billingRateId
      )
      if (!currentRateStillValid) {
        form.value.billingRateId = null // Reset if current rate is no longer valid
      }
    }
    
    // Auto-select default billing rate if none is currently selected
    if (!form.value.billingRateId && availableBillingRates.value.length > 0) {
      // First, try to find an account-specific default rate
      let defaultRate = availableBillingRates.value.find(rate => 
        rate.account_id === form.value.accountId && rate.is_default
      )
      
      // If no account-specific default, try global default
      if (!defaultRate) {
        defaultRate = availableBillingRates.value.find(rate => 
          !rate.account_id && rate.is_default
        )
      }
      
      // If still no default found, take the first account-specific rate, then first global rate
      if (!defaultRate && form.value.accountId) {
        defaultRate = availableBillingRates.value.find(rate => rate.account_id === form.value.accountId)
      }
      
      if (!defaultRate) {
        defaultRate = availableBillingRates.value.find(rate => !rate.account_id)
      }
      
      // Set the auto-selected rate
      if (defaultRate) {
        form.value.billingRateId = defaultRate.id
      }
    }
  } catch (error) {
    console.error('Failed to load billing rates:', error)
  } finally {
    billingRatesLoading.value = false
  }
}

const submitTimer = async () => {
  try {
    loading.value = true
    errors.value = {}

    const payload = {
      description: form.value.description,
      account_id: form.value.accountId || null,
      ticket_id: form.value.ticketId || null,
      user_id: form.value.userId || null,
      billing_rate_id: form.value.billingRateId || null,
      stop_others: form.value.stopOthers,
      auto_start: form.value.autoStart
    }

    let response
    if (props.mode === 'edit' && props.timer) {
      response = await axios.put(`/api/timers/${props.timer.id}`, payload)
      emit('timer-updated', response.data.data)
    } else {
      response = await axios.post('/api/timers', payload)
      emit('timer-started', response.data.data)
    }

    emit('close')
    resetForm()

  } catch (error) {
    console.error('Failed to submit timer:', error)
    if (error.response?.data?.errors) {
      errors.value = error.response.data.errors
    } else {
      errors.value = { general: ['Failed to create timer. Please try again.'] }
    }
  } finally {
    loading.value = false
  }
}

const resetForm = () => {
  form.value = {
    description: '',
    accountId: props.prefilledAccountId,
    ticketId: props.prefilledTicketId,
    userId: user.value?.id, // Always default to current user
    billingRateId: null,
    stopOthers: true,
    autoStart: true
  }
}

// Watchers
watch(() => form.value.accountId, () => {
  if (form.value.accountId) {
    loadTickets()
    loadBillingRates()
  } else {
    availableTickets.value = []
    form.value.ticketId = null
    form.value.billingRateId = null // Reset billing rate when account is cleared
    loadBillingRates() // Load global rates and auto-select default global rate
  }
})

watch(() => props.show, async (isOpen) => {
  if (isOpen) {
    errors.value = {}
    
    // Always load basic data first
    await Promise.all([
      loadAccounts(),
      loadAgents()
    ])
    
    if (props.mode === 'edit' && props.timer) {
      // Populate form with timer data
      form.value = {
        description: props.timer.description || '',
        accountId: props.timer.account_id,
        ticketId: props.timer.ticket_id,
        userId: props.timer.user_id,
        billingRateId: props.timer.billing_rate_id,
        stopOthers: true,
        autoStart: true
      }
      
      // Load dependent data based on timer's account
      if (props.timer.account_id) {
        await Promise.all([
          loadTickets(),
          loadBillingRates()
        ])
      } else {
        // Load global billing rates for non-account timers
        await loadBillingRates()
      }
    } else {
      resetForm()
      
      // Load billing rates for create mode
      await loadBillingRates()
      
      // Always auto-select current user after agents are loaded
      if (user.value?.id) {
        form.value.userId = user.value.id
        console.log('StartTimerModal - Set userId to current user:', user.value.id)
      }
    }
  }
})

// Watch for timer prop changes (when editing different timers with same modal)
watch(() => props.timer, async (newTimer) => {
  if (newTimer && props.show && props.mode === 'edit') {
    // Update form data when timer changes
    form.value = {
      description: newTimer.description || '',
      accountId: newTimer.account_id,
      ticketId: newTimer.ticket_id,
      userId: newTimer.user_id,
      billingRateId: newTimer.billing_rate_id,
      stopOthers: true,
      autoStart: true
    }
    
    // Reload dependent data for the new timer
    if (newTimer.account_id) {
      await Promise.all([
        loadTickets(),
        loadBillingRates()
      ])
    } else {
      await loadBillingRates()
    }
  }
}, { deep: true })

// Watch for user data availability and ensure proper auto-selection
watch(() => user.value, (newUser) => {
  if (newUser && props.show && !form.value.userId) {
    form.value.userId = newUser.id
  }
}, { immediate: true })

// Load data on mount
onMounted(() => {
  loadAccounts()
  loadAgents()
  loadBillingRates()
})
</script>