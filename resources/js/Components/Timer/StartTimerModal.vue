<template>
  <StackedDialog :show="show" :title="mode === 'edit' ? 'Edit Timer' : 'Start New Timer'" max-width="2xl" @close="$emit('close')"
    :show-footer="false">
    <div class="p-6 relative overflow-visible">

          <!-- Form -->
          <form @submit.prevent="submitTimer" class="space-y-6">
            <!-- Basic Information Section -->
            <div class="space-y-4">
              <h4 class="text-base font-medium text-gray-900 border-b border-gray-200 pb-2">Basic Information</h4>
              
              <!-- Description -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                  Description <span class="text-red-500">*</span>
                </label>
                <textarea
                  v-model="form.description"
                  rows="2"
                  placeholder="Describe the work you're timing..."
                  class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                  :class="{ 'border-red-500': errors.description }"
                  required
                />
                <p v-if="errors.description" class="mt-1 text-sm text-red-600">
                  {{ errors.description }}
                </p>
              </div>
            </div>

            <!-- Assignment Section -->
            <div class="space-y-4">
              <h4 class="text-base font-medium text-gray-900 border-b border-gray-200 pb-2">Assignment</h4>
              
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
                  :hierarchical="true"
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

              <!-- Agent Assignment (for managers/admins) -->
              <div v-if="canAssignToOthers">
                <UnifiedSelector
                  v-model="form.userId"
                  type="agent"
                  :items="availableAgents"
                  :loading="agentsLoading"
                  label="Assign Service Agent"
                  placeholder="Select an agent to assign this timer..."
                  :error="errors.userId"
                  :agent-type="'timer'"
                  @item-selected="handleAgentSelected"
                />
                <p class="mt-1 text-xs text-gray-500">The service agent who will perform this work</p>
              </div>

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
            </div>

            <!-- Advanced Options -->
            <div v-if="mode === 'create'" class="border-t pt-4">
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
                    v-model="form.stopOthers"
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
                    v-model="form.autoStart"
                    type="checkbox"
                    class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                  />
                  <label for="auto_start" class="ml-2 block text-sm text-gray-700">
                    {{ mode === 'edit' ? 'Apply changes and continue running' : 'Start timer immediately' }}
                  </label>
                </div>
              </div>
            </div>
          </form>
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
          @click="submitTimer"
          :disabled="loading || !isFormValid"
          class="px-4 py-2 bg-green-600 text-white rounded-md text-sm font-medium hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 disabled:opacity-50 disabled:cursor-not-allowed flex items-center space-x-2"
        >
          <PlayIcon v-if="!loading" class="h-4 w-4" />
          <div v-else class="h-4 w-4 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
          <span>{{ loading ? (mode === 'edit' ? 'Updating...' : 'Starting...') : (mode === 'edit' ? 'Update Timer' : 'Start Timer') }}</span>
        </button>
      </div>
    </div>
  </StackedDialog>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { 
  PlayIcon, 
  XMarkIcon, 
  ChevronDownIcon 
} from '@heroicons/vue/24/outline'
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
    default: 'create', // 'create' | 'edit'
    validator: value => ['create', 'edit'].includes(value)
  },
  timer: {
    type: Object,
    default: null
  },
  contextAccount: {
    type: Object,
    default: null
  },
  contextTicket: {
    type: Object,
    default: null
  },
  contextUser: {
    type: Object,
    default: null
  },
  contextBillingRate: {
    type: Object,
    default: null
  }
})

const emit = defineEmits(['close', 'started', 'updated'])

// Page data
const page = usePage()
const user = computed(() => page.props.auth?.user)

// Reactive state
const loading = ref(false)
const showAdvancedOptions = ref(false)
const errors = ref({})

// Data loading states
const availableTickets = ref([])
const availableBillingRates = ref([])
const availableAgents = ref([])
const availableAccounts = ref([])
const ticketsLoading = ref(false)
const billingRatesLoading = ref(false)
const agentsLoading = ref(false)

const form = ref({
  description: '',
  accountId: null,
  ticketId: null,
  userId: null,
  billingRateId: null,
  stopOthers: false,
  autoStart: true
})

// Computed properties
const isFormValid = computed(() => {
  return form.value.description.trim() !== ''
})

const canAssignToOthers = computed(() => {
  return user.value?.permissions?.includes('timers.admin') || 
         user.value?.permissions?.includes('time.admin') ||
         user.value?.permissions?.includes('admin.write')
})

// Data loading functions
const loadTicketsForAccount = async (accountId, includeTicketId = null) => {
  if (!accountId) {
    availableTickets.value = []
    return
  }
  
  ticketsLoading.value = true
  try {
    const params = {
      account_id: accountId,
      status: ['open', 'in_progress', 'assigned', 'pending', 'new'], // Expanded status list for timers
      per_page: 100
    }
    
    if (includeTicketId) {
      params.include_ticket_id = includeTicketId
    }
    
    console.log('Loading tickets with params:', params)
    const response = await axios.get('/api/tickets', { params })
    console.log('Loaded tickets response:', response.data)
    availableTickets.value = response.data.data || []
  } catch (error) {
    console.error('Failed to load tickets:', error)
    availableTickets.value = []
  } finally {
    ticketsLoading.value = false
  }
}

const loadBillingRatesForAccount = async (accountId) => {
  billingRatesLoading.value = true
  try {
    const params = accountId ? { account_id: accountId } : {}
    const response = await axios.get('/api/billing-rates', { params })
    availableBillingRates.value = response.data.data || []
    
    // Auto-select default billing rate if no rate is currently selected
    if (!form.value.billingRateId && availableBillingRates.value.length > 0) {
      const defaultRate = availableBillingRates.value.find(rate => rate.is_default)
      if (defaultRate) {
        form.value.billingRateId = defaultRate.id
        console.log('Auto-selected default billing rate:', defaultRate.name)
      }
    }
  } catch (error) {
    console.error('Failed to load billing rates:', error)
    availableBillingRates.value = []
  } finally {
    billingRatesLoading.value = false
  }
}

const loadAgentsForAccount = async (accountId) => {
  agentsLoading.value = true
  try {
    const params = {
      per_page: 100,
      agent_type: 'timer' // Specify timer agent type
    }
    
    // Only filter by account if one is specified
    if (accountId) {
      params.account_id = accountId
    }
    
    const response = await axios.get('/api/users/agents', { params })
    availableAgents.value = response.data.data || []
    
    console.log('Loaded timer agents for assignment:', {
      count: availableAgents.value.length,
      accountId
    })
  } catch (error) {
    console.error('Failed to load timer agents:', error)
    availableAgents.value = []
  } finally {
    agentsLoading.value = false
  }
}

const loadAvailableAccounts = async () => {
  try {
    const response = await axios.get('/api/accounts', {
      params: {
        per_page: 100
      }
    })
    availableAccounts.value = response.data.data || []
  } catch (error) {
    console.error('Failed to load available accounts:', error)
    availableAccounts.value = []
  }
}

// Event handlers
const handleAccountSelected = (account) => {
  // Clear dependent selections
  form.value.ticketId = null
  form.value.billingRateId = null
  
  // Load dependent data
  if (account && account.id) {
    loadTicketsForAccount(account.id)
    loadBillingRatesForAccount(account.id)
    if (canAssignToOthers.value) {
      loadAgentsForAccount(account.id)
    }
  } else {
    availableTickets.value = []
    loadBillingRatesForAccount(null)
    availableAgents.value = []
  }
}

const handleTicketSelected = (ticket) => {
  // Auto-set account from ticket if needed
  if (ticket?.account_id && !form.value.accountId) {
    form.value.accountId = ticket.account_id
    loadBillingRatesForAccount(ticket.account_id)
    if (canAssignToOthers.value) {
      loadAgentsForAccount(ticket.account_id)
    }
  }
  
  // Auto-set billing rate from ticket if available
  if (ticket?.billing_rate_id && !form.value.billingRateId) {
    form.value.billingRateId = ticket.billing_rate_id
  }
}

const handleTicketCreated = (newTicket) => {
  // The UnifiedSelector will handle the selection automatically
}

const handleAgentSelected = (agent) => {
  // Additional logic if needed
}

const handleRateSelected = (rate) => {
  // Additional logic if needed
}

// Submit timer (create or update)
const submitTimer = async () => {
  if (loading.value || !isFormValid.value) return

  errors.value = {}
  loading.value = true

  try {
    const payload = {
      description: form.value.description.trim(),
      account_id: form.value.accountId,
      ticket_id: form.value.ticketId,
      user_id: form.value.userId || user.value.id,
      billing_rate_id: form.value.billingRateId,
      stop_others: form.value.stopOthers,
      auto_start: form.value.autoStart
    }

    let response
    if (props.mode === 'edit' && props.timer) {
      // Update existing timer
      response = await axios.put(`/api/timers/${props.timer.id}`, payload)
      emit('updated', response.data.data)
    } else {
      // Create new timer
      response = await axios.post('/api/timers', payload)
      emit('started', response.data.data)
    }
    
    emit('close')
  } catch (error) {
    console.error('Failed to submit timer:', error)
    
    if (error.response?.data?.errors) {
      errors.value = error.response.data.errors
    } else {
      errors.value = {
        general: `Failed to ${props.mode === 'edit' ? 'update' : 'start'} timer. Please try again.`
      }
    }
  } finally {
    loading.value = false
  }
}

// Form initialization
const initializeForm = async () => {
  // Reset form
  form.value = {
    description: '',
    accountId: null,
    ticketId: null,
    userId: user.value?.id,
    billingRateId: null,
    stopOthers: false,
    autoStart: true
  }
  
  // Apply context prefills
  if (props.contextAccount) {
    form.value.accountId = props.contextAccount.id
    await loadTicketsForAccount(props.contextAccount.id)
    await loadBillingRatesForAccount(props.contextAccount.id)
    if (canAssignToOthers.value) {
      await loadAgentsForAccount(props.contextAccount.id)
    }
  }
  
  if (props.contextTicket) {
    form.value.accountId = props.contextTicket.account_id
    form.value.ticketId = props.contextTicket.id
    if (props.contextTicket.billing_rate_id) {
      form.value.billingRateId = props.contextTicket.billing_rate_id
    }
    await loadTicketsForAccount(props.contextTicket.account_id, props.contextTicket.id)
    await loadBillingRatesForAccount(props.contextTicket.account_id)
    if (canAssignToOthers.value) {
      await loadAgentsForAccount(props.contextTicket.account_id)
    }
  }
  
  if (props.contextUser) {
    form.value.userId = props.contextUser.id
  }
  
  if (props.contextBillingRate) {
    form.value.billingRateId = props.contextBillingRate.id
  }
  
  // Edit mode initialization
  if (props.mode === 'edit' && props.timer) {
    form.value.description = props.timer.description || ''
    form.value.accountId = props.timer.account_id
    form.value.ticketId = props.timer.ticket_id
    form.value.userId = props.timer.user_id
    form.value.billingRateId = props.timer.billing_rate_id
    
    // Load related data for editing
    if (props.timer.account_id) {
      await loadTicketsForAccount(props.timer.account_id, props.timer.ticket_id)
      await loadBillingRatesForAccount(props.timer.account_id)
      if (canAssignToOthers.value) {
        await loadAgentsForAccount(props.timer.account_id)
      }
    }
  }
  
  // Load initial billing rates if no account context
  if (!form.value.accountId) {
    await loadBillingRatesForAccount(null)
  }
  
  // Load initial agents if user can assign to others (regardless of account context)
  if (canAssignToOthers.value) {
    await loadAgentsForAccount(null)
  }
  
  // Load available accounts for the CreateTicketModal
  await loadAvailableAccounts()
}

// Watchers
watch(() => props.show, async (newValue) => {
  if (newValue) {
    await initializeForm()
  }
})

// Initialize on mount if dialog is already open
onMounted(async () => {
  if (props.show) {
    await initializeForm()
  }
})
</script>