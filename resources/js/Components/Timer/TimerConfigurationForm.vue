<template>
  <div class="space-y-4">
    <!-- Description Input -->
    <div>
      <label 
        v-if="showLabels" 
        class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
      >
        Work Description
        <span v-if="isDescriptionRequired" class="text-red-500">*</span>
      </label>
      <textarea
        v-model="form.description"
        :rows="compactMode ? 2 : 3"
        :placeholder="descriptionPlaceholder || 'Describe the work performed...'"
        :required="isDescriptionRequired"
        :class="[
          'w-full px-3 py-2 border rounded-md text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors resize-none',
          compactMode ? 'text-sm' : 'text-base',
          'border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white',
          errors.description ? 'border-red-500 focus:ring-red-500 focus:border-red-500' : ''
        ]"
      ></textarea>
      <p v-if="errors.description" class="mt-1 text-sm text-red-600">{{ errors.description }}</p>
    </div>
    
    <!-- Account Selection -->
    <div v-if="showAccountSelection">
      <HierarchicalAccountSelector
        v-model="form.accountId"
        :label="showLabels ? 'Account' : null"
        :placeholder="accountPlaceholder"
        :required="false"
        :error="errors.accountId"
        @account-selected="handleAccountSelected"
      />
    </div>
    
    <!-- Ticket Selection (only if account selected) -->
    <div v-if="showTicketSelection && form.accountId">
      <TicketSelector
        v-model="form.ticketId"
        :label="showLabels ? 'Ticket' : null"
        :tickets="availableTickets"
        :is-loading="ticketsLoading"
        :placeholder="ticketPlaceholder"
        :disabled="!form.accountId"
        :error="errors.ticketId"
        :show-create-option="true"
        :prefilled-account-id="form.accountId"
        @ticket-selected="handleTicketSelected"
        @ticket-created="handleTicketCreated"
      />
    </div>
    
    <!-- Billing Rate Selection -->
    <div v-if="showBillingRateSelection">
      <label 
        v-if="showLabels" 
        class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
      >
        Billing Rate
      </label>
      <BillingRateSelector
        v-model="form.billingRateId"
        :rates="availableBillingRates"
        :is-loading="billingRatesLoading"
        :placeholder="billingRatePlaceholder"
        :error="errors.billingRateId"
        @rate-selected="handleRateSelected"
      />
    </div>

    <!-- User Assignment (for editing timers) -->
    <div v-if="showUserSelection && canAssignToOthers">
      <label 
        v-if="showLabels" 
        class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
      >
        Assigned User
      </label>
      <UserSelector
        v-model="form.userId"
        :users="availableUsers"
        :is-loading="usersLoading"
        :placeholder="userPlaceholder"
        :show-create-option="false"
        :error="errors.userId"
        @user-selected="handleUserSelected"
      />
    </div>

    <!-- Current Duration Display (edit mode only) -->
    <div v-if="mode === 'edit' && timerData">
      <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
        Current Duration
      </label>
      <div class="px-3 py-2 bg-gray-50 dark:bg-gray-600 rounded-md text-sm text-gray-900 dark:text-gray-100">
        {{ formatDuration(currentDuration) }}
      </div>
    </div>

    <!-- Current Value Display (if billing rate selected) -->
    <div v-if="showCurrentValue && currentValue > 0">
      <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
        Current Value
      </label>
      <div class="px-3 py-2 bg-gray-50 dark:bg-gray-600 rounded-md text-sm text-green-600 dark:text-green-400 font-medium">
        ${{ currentValue.toFixed(2) }}
      </div>
    </div>

    <!-- Action Buttons -->
    <div v-if="showActions" class="flex space-x-2 mt-4">
      <button
        type="button"
        @click="handleSubmit"
        :disabled="!isValid || isSubmitting"
        :class="[
          'flex-1 p-2 text-white rounded-md font-medium transition-colors',
          compactMode ? 'text-sm' : 'text-base',
          isValid && !isSubmitting 
            ? (mode === 'create' ? 'bg-green-500 hover:bg-green-600' : 'bg-blue-500 hover:bg-blue-600')
            : 'bg-gray-400 cursor-not-allowed'
        ]"
        :title="submitButtonTitle"
      >
        <span v-if="isSubmitting">{{ loadingText }}</span>
        <span v-else-if="mode === 'create'">
          <svg v-if="compactMode" class="w-4 h-4 mx-auto" fill="currentColor" viewBox="0 0 24 24">
            <path d="M8 5v14l11-7z"/>
          </svg>
          <span v-else>Start Timer</span>
        </span>
        <span v-else>Save Changes</span>
      </button>
      
      <button
        v-if="showCancelButton"
        type="button"
        @click="handleCancel"
        :class="[
          'p-2 bg-gray-500 hover:bg-gray-600 text-white rounded-md transition-colors',
          compactMode ? 'text-sm' : 'text-base font-medium'
        ]"
        title="Cancel"
      >
        <svg v-if="compactMode" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
        </svg>
        <span v-else>Cancel</span>
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { usePage } from '@inertiajs/vue3'
import HierarchicalAccountSelector from '@/Components/UI/HierarchicalAccountSelector.vue'
import TicketSelector from '@/Components/UI/TicketSelector.vue'
import BillingRateSelector from '@/Components/UI/BillingRateSelector.vue'
import UserSelector from '@/Components/UI/UserSelector.vue'
import axios from 'axios'

// Props
const props = defineProps({
  mode: {
    type: String,
    default: 'create', // 'create' | 'edit'
    validator: value => ['create', 'edit'].includes(value)
  },
  timerData: {
    type: Object,
    default: null
  },
  initialValues: {
    type: Object,
    default: () => ({})
  },
  showAccountSelection: {
    type: Boolean,
    default: true
  },
  showTicketSelection: {
    type: Boolean,
    default: true
  },
  showBillingRateSelection: {
    type: Boolean,
    default: true
  },
  showUserSelection: {
    type: Boolean,
    default: false
  },
  showLabels: {
    type: Boolean,
    default: false
  },
  showActions: {
    type: Boolean,
    default: true
  },
  showCancelButton: {
    type: Boolean,
    default: true
  },
  compactMode: {
    type: Boolean,
    default: false
  },
  descriptionPlaceholder: {
    type: String,
    default: 'Timer description...'
  },
  accountPlaceholder: {
    type: String,
    default: 'No account (general timer)'
  },
  ticketPlaceholder: {
    type: String,
    default: 'No specific ticket'
  },
  billingRatePlaceholder: {
    type: String,
    default: 'No billing rate'
  },
  userPlaceholder: {
    type: String,
    default: 'Select user...'
  },
  loadingText: {
    type: String,
    default: 'Processing...'
  }
})

// Emits
const emit = defineEmits([
  'submit', 
  'cancel', 
  'account-changed', 
  'ticket-changed', 
  'rate-changed',
  'user-changed'
])

// State
const form = ref({
  description: '',
  accountId: null,
  ticketId: null,
  billingRateId: null,
  userId: null
})

const errors = ref({})
const isSubmitting = ref(false)

// Data loading states
const availableTickets = ref([])
const availableBillingRates = ref([])
const availableUsers = ref([])
const ticketsLoading = ref(false)
const billingRatesLoading = ref(false)
const usersLoading = ref(false)

// Settings and permissions
const page = usePage()
const user = computed(() => page.props.auth?.user)
const timerSettings = ref({
  require_description: true,
  default_billable: true,
  time_display_format: 'hms'
})

// Computed properties
const isDescriptionRequired = computed(() => {
  return timerSettings.value.require_description
})

const canAssignToOthers = computed(() => {
  return user.value?.permissions?.includes('timers.manage.all') || 
         user.value?.permissions?.includes('admin.manage')
})

const currentDuration = computed(() => {
  if (!props.timerData) return 0
  
  const startTime = new Date(props.timerData.started_at)
  const now = new Date()
  const elapsed = Math.floor((now - startTime) / 1000)
  
  // Factor in pause periods if available
  const pauseDuration = props.timerData.pause_duration || 0
  return Math.max(0, elapsed - pauseDuration)
})

const currentValue = computed(() => {
  if (!form.value.billingRateId || !availableBillingRates.value.length) return 0
  
  const selectedRate = availableBillingRates.value.find(rate => rate.id == form.value.billingRateId)
  if (!selectedRate) return 0
  
  const hours = currentDuration.value / 3600
  return hours * selectedRate.rate
})

const showCurrentValue = computed(() => {
  return props.mode === 'edit' && form.value.billingRateId && currentValue.value > 0
})

const isValid = computed(() => {
  // Description is required if settings say so
  if (isDescriptionRequired.value && !form.value.description.trim()) {
    return false
  }
  
  // No other fields are strictly required
  return true
})

const submitButtonTitle = computed(() => {
  if (!isValid.value) {
    if (isDescriptionRequired.value && !form.value.description.trim()) {
      return 'Description is required'
    }
    return 'Please fix validation errors'
  }
  
  return props.mode === 'create' ? 'Start Timer' : 'Save Timer Settings'
})

// Methods
const formatDuration = (seconds) => {
  if (!seconds) return '0m'
  
  const hours = Math.floor(seconds / 3600)
  const minutes = Math.floor((seconds % 3600) / 60)
  const secs = seconds % 60
  
  // Use settings to determine format
  const format = timerSettings.value.time_display_format || 'hms'
  
  switch (format) {
    case 'hms':
      if (hours > 0) {
        return `${hours}:${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`
      } else {
        return `${minutes}:${secs.toString().padStart(2, '0')}`
      }
    case 'hm':
      if (hours > 0) {
        return `${hours}h ${minutes}m`
      } else {
        return `${minutes}m`
      }
    case 'decimal':
      return `${(seconds / 3600).toFixed(2)}h`
    default:
      return `${hours}h ${minutes}m`
  }
}

const loadTimerSettings = async () => {
  try {
    const response = await axios.get('/api/settings/timer')
    timerSettings.value = { ...timerSettings.value, ...response.data.data }
  } catch (error) {
    console.error('Failed to load timer settings:', error)
  }
}

const loadTicketsForAccount = async (accountId) => {
  if (!accountId) {
    availableTickets.value = []
    return
  }
  
  ticketsLoading.value = true
  try {
    const response = await axios.get('/api/tickets', {
      params: {
        account_id: accountId,
        // Remove restrictive status filter - let users log time to any ticket
        per_page: 100
      }
    })
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

const handleAccountSelected = async (account) => {
  // Clear dependent selections
  form.value.ticketId = null
  
  // Only clear billing rate in create mode (preserve it in edit mode)
  if (props.mode === 'create') {
    form.value.billingRateId = null
  }
  
  // Load dependent data
  if (account) {
    loadTicketsForAccount(account.id)
    await loadBillingRatesForAccount(account.id) // Wait for billing rates to load
    
    // Auto-select default billing rate for account (only in create mode)
    if (props.mode === 'create') {
      selectDefaultBillingRate()
    }
    
    if (props.showUserSelection) {
      loadUsersForAccount(account.id)
    }
  } else {
    availableTickets.value = []
    await loadBillingRatesForAccount(null) // Load global billing rates and wait
    
    // Auto-select default global billing rate (only in create mode)
    if (props.mode === 'create') {
      selectDefaultBillingRate()
    }
    
    availableUsers.value = []
  }
  
  emit('account-changed', account)
}

const handleTicketSelected = (ticket) => {
  // Auto-set billing rate from ticket if available
  if (ticket?.billing_rate_id && !form.value.billingRateId) {
    form.value.billingRateId = ticket.billing_rate_id
  }
  
  emit('ticket-changed', ticket)
}

const handleTicketCreated = (newTicket) => {
  // Add the newly created ticket to the available tickets list
  availableTickets.value.push(newTicket)
  // Auto-select the new ticket
  form.value.ticketId = newTicket.id
  handleTicketSelected(newTicket)
}

const handleRateSelected = (rate) => {
  emit('rate-changed', rate)
}

const selectDefaultBillingRate = () => {
  // Find the default billing rate from available rates
  // Priority: Account default > Parent default > Global default > First account rate
  const defaultRate = availableBillingRates.value.find(rate => 
    rate.is_default && (rate.inheritance_source === 'account' || rate.inheritance_source === 'parent')
  ) || availableBillingRates.value.find(rate => 
    rate.is_default && rate.inheritance_source === 'global'
  ) || availableBillingRates.value.find(rate => 
    rate.inheritance_source === 'account'
  )
  
  if (defaultRate) {
    form.value.billingRateId = defaultRate.id
  }
}

const handleUserSelected = (user) => {
  emit('user-changed', user)
}

const handleEnterKey = () => {
  if (isValid.value && !isSubmitting.value) {
    handleSubmit()
  }
}

const handleSubmit = () => {
  if (!isValid.value || isSubmitting.value) return
  
  errors.value = {}
  
  // Validate required fields
  if (isDescriptionRequired.value && !form.value.description.trim()) {
    errors.value.description = 'Description is required'
    return
  }
  
  isSubmitting.value = true
  
  // Emit submit event with form data
  emit('submit', {
    ...form.value,
    description: form.value.description.trim()
  })
}

const handleCancel = () => {
  emit('cancel')
}

const initializeForm = () => {
  // Initialize from timer data (edit mode)
  if (props.mode === 'edit' && props.timerData) {
    form.value = {
      description: props.timerData.description || '',
      accountId: props.timerData.account_id || null,
      ticketId: props.timerData.ticket_id || null,
      billingRateId: props.timerData.billing_rate_id || null,
      userId: props.timerData.user_id || null
    }
    
    // Load dependent data
    if (props.timerData.account_id) {
      loadTicketsForAccount(props.timerData.account_id)
      loadBillingRatesForAccount(props.timerData.account_id)
      if (props.showUserSelection) {
        loadUsersForAccount(props.timerData.account_id)
      }
    }
  }
  
  // Apply initial values
  if (props.initialValues) {
    Object.assign(form.value, props.initialValues)
  }
  
  // Set defaults from timer settings
  if (props.mode === 'create') {
    // Load billing rates and select default if no account is set
    if (!form.value.accountId) {
      loadBillingRatesForAccount(null).then(() => {
        selectDefaultBillingRate()
      })
    }
  }
}

const resetSubmitState = () => {
  isSubmitting.value = false
}

// Expose methods for parent components
defineExpose({
  resetSubmitState,
  form
})

// Lifecycle
onMounted(async () => {
  await loadTimerSettings()
  initializeForm()
  
  // Load initial billing rates
  await loadBillingRatesForAccount(form.value.accountId)
})

// Watchers
watch(() => props.timerData, () => {
  if (props.mode === 'edit') {
    initializeForm()
  }
}, { deep: true })

watch(() => props.initialValues, () => {
  if (props.initialValues) {
    Object.assign(form.value, props.initialValues)
  }
}, { deep: true })
</script>