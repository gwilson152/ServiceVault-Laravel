<template>
  <Modal :show="show" @close="$emit('close')" max-width="2xl">
    <div class="p-6 relative overflow-visible">
          <!-- Header -->
          <div class="flex items-center justify-between mb-4">
            <div class="flex items-center">
              <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-green-100 sm:mx-0 sm:h-10 sm:w-10">
                <PlayIcon class="h-6 w-6 text-green-600" aria-hidden="true" />
              </div>
              <div class="ml-4">
                <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                  {{ mode === 'edit' ? 'Edit Timer' : 'Start New Timer' }}
                </h3>
                <p class="text-sm text-gray-500">
                  {{ mode === 'edit' ? 'Update timer configuration' : 'Create a new timer for tracking your work time' }}
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
          <form @submit.prevent="submitTimer" class="space-y-6">
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

            <!-- Account Selection -->
            <div>
              <HierarchicalAccountSelector
                v-model="form.accountId"
                label="Account"
                placeholder="Select account (optional for general timers)"
                :error="errors.accountId"
                @account-selected="handleAccountSelected"
              />
            </div>

            <!-- Ticket Selection (if account selected) -->
            <div v-if="form.accountId">
              <TicketSelector
                v-model="form.ticketId"
                label="Ticket"
                :tickets="availableTickets"
                :is-loading="ticketsLoading"
                placeholder="Select ticket (optional)"
                :disabled="!form.accountId"
                :error="errors.ticketId"
                :show-create-option="true"
                :prefilled-account-id="form.accountId"
                @ticket-selected="handleTicketSelected"
                @ticket-created="handleTicketCreated"
              />
            </div>

            <!-- User Assignment (for managers/admins) -->
            <div v-if="canAssignToOthers">
              <UserSelector
                v-model="form.userId"
                label="Assign To"
                :users="availableUsers"
                :is-loading="usersLoading"
                :show-create-option="false"
                :error="errors.userId"
                @user-selected="handleUserSelected"
              />
              <p class="mt-1 text-xs text-gray-500">The agent who will run this timer</p>
            </div>

            <!-- Billing Rate Selection -->
            <div>
              <BillingRateSelector
                v-model="form.billingRateId"
                :rates="availableBillingRates"
                :is-loading="billingRatesLoading"
                placeholder="No billing rate (non-billable)"
                :error="errors.billingRateId"
                @rate-selected="handleRateSelected"
              />
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
  </Modal>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { 
  PlayIcon, 
  XMarkIcon, 
  ChevronDownIcon 
} from '@heroicons/vue/24/outline'
import { usePage } from '@inertiajs/vue3'
import Modal from '@/Components/Modal.vue'
import HierarchicalAccountSelector from '@/Components/UI/HierarchicalAccountSelector.vue'
import TicketSelector from '@/Components/UI/TicketSelector.vue'
import UserSelector from '@/Components/UI/UserSelector.vue'
import BillingRateSelector from '@/Components/UI/BillingRateSelector.vue'
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
const availableUsers = ref([])
const ticketsLoading = ref(false)
const billingRatesLoading = ref(false)
const usersLoading = ref(false)

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
         user.value?.permissions?.includes('admin.manage')
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
  } catch (error) {
    console.error('Failed to load billing rates:', error)
    availableBillingRates.value = []
  } finally {
    billingRatesLoading.value = false
  }
}

const loadUsersForAccount = async (accountId) => {
  if (!accountId) {
    availableUsers.value = []
    return
  }
  
  usersLoading.value = true
  try {
    const response = await axios.get('/api/users', {
      params: {
        account_id: accountId,
        user_type: 'agent', // Only load agent users for timer assignment
        per_page: 100
      }
    })
    availableUsers.value = response.data.data || []
  } catch (error) {
    console.error('Failed to load users:', error)
    availableUsers.value = []
  } finally {
    usersLoading.value = false
  }
}

// Event handlers
const handleAccountSelected = (account) => {
  // Clear dependent selections
  form.value.ticketId = null
  form.value.billingRateId = null
  
  // Load dependent data
  if (account) {
    loadTicketsForAccount(account.id)
    loadBillingRatesForAccount(account.id)
    if (canAssignToOthers.value) {
      loadUsersForAccount(account.id)
    }
  } else {
    availableTickets.value = []
    loadBillingRatesForAccount(null)
    availableUsers.value = []
  }
}

const handleTicketSelected = (ticket) => {
  // Auto-set account from ticket if needed
  if (ticket?.account_id && !form.value.accountId) {
    form.value.accountId = ticket.account_id
    loadBillingRatesForAccount(ticket.account_id)
    if (canAssignToOthers.value) {
      loadUsersForAccount(ticket.account_id)
    }
  }
  
  // Auto-set billing rate from ticket if available
  if (ticket?.billing_rate_id && !form.value.billingRateId) {
    form.value.billingRateId = ticket.billing_rate_id
  }
}

const handleTicketCreated = (newTicket) => {
  // The TicketSelector will handle the selection automatically
}

const handleUserSelected = (user) => {
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
      await loadUsersForAccount(props.contextAccount.id)
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
      await loadUsersForAccount(props.contextTicket.account_id)
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
        await loadUsersForAccount(props.timer.account_id)
      }
    }
  }
  
  // Load initial billing rates if no account context
  if (!form.value.accountId) {
    await loadBillingRatesForAccount(null)
  }
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