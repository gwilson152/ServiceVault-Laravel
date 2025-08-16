<template>
  <TabbedDialog
    :show="show"
    :title="mode === 'edit' ? 'Edit Timer' : 'Start New Timer'"
    :tabs="tabs"
    default-tab="basic"
    max-width="2xl"
    :saving="loading"
    :save-label="loading ? (mode === 'edit' ? 'Updating...' : 'Starting...') : (mode === 'edit' ? 'Update Timer' : 'Start Timer')"
    @close="$emit('close')"
    @save="submitTimer"
    @tab-change="activeTab = $event"
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

    <!-- Tab Content -->
    <template #default="{ activeTab }">
      <!-- Basic Information & Assignment Tab -->
      <div v-show="activeTab === 'basic'" class="space-y-6">
        <!-- Header Icon and Description -->
        <div class="flex items-center mb-4">
          <div class="flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-green-100">
            <PlayIcon class="h-6 w-6 text-green-600" aria-hidden="true" />
          </div>
          <div class="ml-4">
            <p class="text-sm text-gray-600">
              {{ mode === 'edit' ? 'Update timer configuration' : 'Create a new timer for tracking your work time' }}
            </p>
          </div>
        </div>

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
        </div>
      </div>

      <!-- Billing Tab -->
      <div v-show="activeTab === 'billing'" class="space-y-6">
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
        <div v-if="selectedBillingRate" class="bg-blue-50 rounded-lg p-4">
          <h4 class="text-sm font-medium text-blue-900 mb-2">Selected Billing Rate</h4>
          <div class="text-sm text-blue-800">
            <p><strong>{{ selectedBillingRate.name }}</strong></p>
            <p>${{ selectedBillingRate.hourly_rate }}/hour</p>
            <p v-if="selectedBillingRate.description" class="text-xs mt-1">{{ selectedBillingRate.description }}</p>
          </div>
        </div>

        <div v-else class="bg-gray-50 rounded-lg p-4">
          <p class="text-sm text-gray-600">
            <strong>Non-billable timer</strong><br>
            This timer will not be associated with any billing rate.
          </p>
        </div>
      </div>

      <!-- Options Tab -->
      <div v-show="activeTab === 'options'" class="space-y-6">
        <div v-if="mode === 'create'" class="space-y-4">
          <h4 class="text-sm font-medium text-gray-900">Timer Options</h4>
          
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

        <!-- Timer Summary -->
        <div class="bg-green-50 rounded-lg p-4">
          <h4 class="text-sm font-medium text-green-900 mb-2">Timer Summary</h4>
          <div class="text-sm text-green-800 space-y-1">
            <p><strong>Description:</strong> {{ form.description || 'Not specified' }}</p>
            <p v-if="selectedAccount"><strong>Account:</strong> {{ selectedAccount.name }}</p>
            <p v-if="selectedTicket"><strong>Ticket:</strong> {{ selectedTicket.title }}</p>
            <p v-if="selectedAgent"><strong>Agent:</strong> {{ selectedAgent.name }}</p>
            <p><strong>Billing:</strong> {{ selectedBillingRate ? `$${selectedBillingRate.hourly_rate}/hour` : 'Non-billable' }}</p>
          </div>
        </div>
      </div>
    </template>

    <!-- Custom footer for timer actions -->
    <template #footer-start>
      <div v-if="mode === 'edit'" class="flex items-center text-sm text-gray-500">
        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        Editing active timer
      </div>
    </template>
  </TabbedDialog>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { PlayIcon } from '@heroicons/vue/24/outline'
import { usePage } from '@inertiajs/vue3'
import TabbedDialog from '@/Components/TabbedDialog.vue'
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

// Tab configuration
const tabs = [
  { id: 'basic', name: 'Basic Info & Assignment', icon: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z' },
  { id: 'billing', name: 'Billing', icon: 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z' },
  { id: 'options', name: 'Options', icon: 'M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4' }
]

const activeTab = ref('basic')

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
  return user.value?.permissions?.includes('timers.act_as_agent') ||
         user.value?.permissions?.includes('timers.admin') ||
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
  loadTickets()
  loadBillingRates()
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
    availableBillingRates.value = response.data.data
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
    userId: null,
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
  }
})

watch(() => props.show, (isOpen) => {
  if (isOpen) {
    activeTab.value = 'basic'
    errors.value = {}
    
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
    } else {
      resetForm()
    }
  }
})

// Load data on mount
onMounted(() => {
  loadAccounts()
  loadAgents()
  loadBillingRates()
})
</script>