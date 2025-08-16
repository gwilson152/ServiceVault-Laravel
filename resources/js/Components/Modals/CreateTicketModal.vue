<template>
  <StackedDialog :show="show" title="Create New Ticket" max-width="2xl" @close="$emit('close')" :show-footer="false">
    <div class="p-6">

      <form @submit.prevent="submitForm" class="space-y-6">
        <!-- Title -->
        <div>
          <label for="title" class="block text-sm font-medium text-gray-700 mb-2">
            Title <span class="text-red-500">*</span>
          </label>
          <input
            id="title"
            v-model="form.title"
            type="text"
            required
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            placeholder="Brief description of the issue or request"
          >
          <p v-if="errors.title" class="mt-1 text-sm text-red-600">{{ errors.title }}</p>
        </div>

        <!-- Description -->
        <div>
          <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
            Description <span class="text-red-500">*</span>
          </label>
          <textarea
            id="description"
            v-model="form.description"
            required
            rows="4"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            placeholder="Detailed description of the issue, steps to reproduce, or requirements"
          ></textarea>
          <p v-if="errors.description" class="mt-1 text-sm text-red-600">{{ errors.description }}</p>
        </div>

        <!-- Account Selection -->
        <div>
          <UnifiedSelector
            v-model="form.account_id"
            type="account"
            :items="availableAccounts"
            label="Account"
            placeholder="Select account for this ticket..."
            required
            :hierarchical="true"
            :error="errors.account_id"
            @item-selected="handleAccountSelected"
          />
        </div>
        
        <!-- Customer User Selection (optional) -->
        <div v-if="form.account_id">
          <UserSelector
            v-model="form.customer_id"
            :users="availableCustomers"
            :is-loading="isLoadingCustomers"
            :accounts="flatAccounts"
            :role-templates="roleTemplates"
            :preselected-account-id="form.account_id"
            label="Customer User"
            placeholder="No specific customer user"
            :error="errors.customer_id"
            no-users-message="No customer users available for this account"
            @user-selected="handleCustomerSelected"
            @user-created="handleUserCreated"
          />
          <p class="mt-1 text-xs text-gray-500">Select the customer user this ticket is for (if applicable)</p>
        </div>
        
        <!-- Agent Assignment (optional) -->
        <div v-if="canAssignTickets">
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Assign Agent <span class="text-gray-500 text-xs">(optional)</span>
          </label>
          
          <div class="relative">
            <select
              v-model="form.agent_id"
              :disabled="isLoadingAgents"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 disabled:bg-gray-100"
            >
              <option value="">{{ isLoadingAgents ? 'Loading agents...' : 'Select an agent...' }}</option>
              <option
                v-for="agent in availableAgents"
                :key="agent.id"
                :value="agent.id"
              >
                {{ agent.name }} ({{ agent.email }})
              </option>
            </select>
            
            <div v-if="isLoadingAgents" class="absolute inset-y-0 right-0 flex items-center pr-3">
              <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-blue-600"></div>
            </div>
          </div>
          
          <p v-if="errors.agent_id" class="mt-1 text-sm text-red-600">{{ errors.agent_id }}</p>
        </div>

        <!-- Form Row: Priority & Category -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <!-- Priority -->
          <div>
            <label for="priority" class="block text-sm font-medium text-gray-700 mb-2">
              Priority <span class="text-red-500">*</span>
            </label>
            <select
              id="priority"
              v-model="form.priority"
              required
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            >
              <option value="">Select Priority</option>
              <option value="low">Low</option>
              <option value="normal">Normal</option>
              <option value="high">High</option>
              <option value="urgent">Urgent</option>
            </select>
            <p v-if="errors.priority" class="mt-1 text-sm text-red-600">{{ errors.priority }}</p>
          </div>

          <!-- Category -->
          <div>
            <label for="category" class="block text-sm font-medium text-gray-700 mb-2">
              Category
            </label>
            <select
              id="category"
              v-model="form.category"
              :disabled="isLoadingCategories"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 disabled:bg-gray-100"
            >
              <option value="">{{ isLoadingCategories ? 'Loading categories...' : 'Select Category' }}</option>
              <option
                v-for="category in availableCategories"
                :key="category.key"
                :value="category.key"
              >
                {{ category.name }}
              </option>
            </select>
            <p v-if="errors.category" class="mt-1 text-sm text-red-600">{{ errors.category }}</p>
          </div>
        </div>

        <!-- Due Date -->
        <div>
          <label for="due_date" class="block text-sm font-medium text-gray-700 mb-2">
            Due Date
          </label>
          <input
            id="due_date"
            v-model="form.due_date"
            type="datetime-local"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
          >
          <p v-if="errors.due_date" class="mt-1 text-sm text-red-600">{{ errors.due_date }}</p>
        </div>

        <!-- Tags -->
        <div>
          <label for="tags" class="block text-sm font-medium text-gray-700 mb-2">
            Tags
          </label>
          <input
            id="tags"
            v-model="form.tags"
            type="text"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            placeholder="Enter tags separated by commas (e.g., server, database, urgent)"
          >
          <p class="mt-1 text-sm text-gray-500">Separate multiple tags with commas</p>
          <p v-if="errors.tags" class="mt-1 text-sm text-red-600">{{ errors.tags }}</p>
        </div>

        <!-- Additional Options -->
        <div class="bg-gray-50 rounded-lg p-4 space-y-3">
          <h4 class="text-sm font-medium text-gray-900">Additional Options</h4>
          
          <!-- Start Timer -->
          <label class="flex items-center">
            <input
              v-model="form.start_timer"
              type="checkbox"
              class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500"
            >
            <span class="ml-2 text-sm text-gray-700">Start timer immediately after creating ticket</span>
          </label>
          
          <!-- Send Notifications -->
          <label class="flex items-center">
            <input
              v-model="form.send_notifications"
              type="checkbox"
              checked
              class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500"
            >
            <span class="ml-2 text-sm text-gray-700">Send email notifications to relevant users</span>
          </label>
        </div>

        <!-- Form Actions -->
        <div class="flex items-center justify-end space-x-3 pt-6 border-t border-gray-200">
          <button
            type="button"
            @click="$emit('close')"
            class="px-4 py-2 text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors"
          >
            Cancel
          </button>
          <button
            type="submit"
            :disabled="isSubmitting"
            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
          >
            <span v-if="isSubmitting">Creating...</span>
            <span v-else>Create Ticket</span>
          </button>
        </div>
      </form>
    </div>
  </StackedDialog>
</template>

<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue'
import { usePage } from '@inertiajs/vue3'
import StackedDialog from '@/Components/StackedDialog.vue'
import UnifiedSelector from '@/Components/UI/UnifiedSelector.vue'
import UserSelector from '@/Components/UI/UserSelector.vue'
import axios from 'axios'
import { useCreateTicketMutation } from '@/Composables/queries/useTicketsQuery'
import { useRoleTemplatesQuery } from '@/Composables/queries/useUsersQuery'

// Props
const props = defineProps({
  show: Boolean,
  availableAccounts: {
    type: Array,
    default: () => []
  },
  canAssignTickets: {
    type: Boolean,
    default: false
  },
  prefilledAccountId: {
    type: [String, Number],
    default: null
  },
  nested: {
    type: Boolean,
    default: false
  }
})

// Emits
const emit = defineEmits(['close', 'created'])

// TanStack Query Mutation & Queries
const createTicketMutation = useCreateTicketMutation()
const { data: roleTemplatesData } = useRoleTemplatesQuery()

// State
const isSubmitting = ref(false)
const errors = ref({})
const isLoadingCategories = ref(false)
const availableCategories = ref([])
const isLoadingAgents = ref(false)
const availableAgents = ref([])
const isLoadingCustomers = ref(false)
const availableCustomers = ref([])

// Form data
const form = reactive({
  title: '',
  description: '',
  priority: 'normal',
  account_id: '',
  customer_id: '', // Added customer user selection
  category: '',
  due_date: '',
  agent_id: '',
  tags: '',
  start_timer: false,
  send_notifications: true
})

// Page data
const page = usePage()
const user = computed(() => page.props.auth?.user)

// Computed properties for UserSelector
const flatAccounts = computed(() => {
  if (!props.availableAccounts) return []
  return props.availableAccounts.map(account => ({
    id: account.id,
    name: account.name,
    display_name: account.display_name || account.name,
    company_name: account.company_name,
    account_type: account.account_type
  }))
})

const roleTemplates = computed(() => roleTemplatesData.value?.data || [])

// Methods
const resetForm = () => {
  form.title = ''
  form.description = ''
  form.priority = 'normal'
  form.account_id = props.prefilledAccountId || ''
  form.customer_id = ''
  form.category = ''
  form.due_date = ''
  form.agent_id = ''
  form.tags = ''
  form.start_timer = false
  form.send_notifications = true
  errors.value = {}
  availableAgents.value = []
  availableCustomers.value = []
}

const loadCategories = async () => {
  isLoadingCategories.value = true
  try {
    const response = await axios.get('/api/ticket-categories/options')
    availableCategories.value = response.data.options || []
    
    // Set default category if available
    if (response.data.default_category && !form.category) {
      form.category = response.data.default_category
    }
  } catch (error) {
    console.error('Failed to load categories:', error)
    availableCategories.value = []
  } finally {
    isLoadingCategories.value = false
  }
}


const submitForm = async () => {
  if (isSubmitting.value) return

  isSubmitting.value = true
  errors.value = {}

  try {
    // Prepare form data
    const payload = { ...form }
    
    // Process tags
    if (payload.tags) {
      payload.tags = payload.tags.split(',').map(tag => tag.trim()).filter(tag => tag)
    } else {
      payload.tags = []
    }

    // Convert due_date to proper format if provided
    if (payload.due_date) {
      payload.due_date = new Date(payload.due_date).toISOString()
    }

    
    // Remove empty account_id to let backend handle auto-assignment
    if (!payload.account_id) {
      delete payload.account_id
    }

    // Use TanStack Query mutation
    const newTicket = await createTicketMutation.mutateAsync(payload)
    
    // Start timer if requested
    if (form.start_timer) {
      await startTimerForTicket(newTicket.id)
    }

    emit('created', newTicket)
    resetForm()
  } catch (error) {
    console.error('Error creating ticket:', error)
    if (error.response?.data?.errors) {
      errors.value = error.response.data.errors
    } else {
      errors.value = { general: 'Failed to create ticket. Please try again.' }
    }
  } finally {
    isSubmitting.value = false
  }
}

const startTimerForTicket = async (ticketId) => {
  try {
    // TODO: Add billing rate selection to timer creation
    // Currently creating basic timer without billing rate
    // Should integrate with UnifiedSelector component for proper rate selection
    await axios.post('/api/timers', {
      ticket_id: ticketId,
      account_id: form.account_id,
      description: `Working on ticket: ${form.title}`
      // TODO: billing_rate_id: selectedBillingRateId
    })
  } catch (error) {
    console.error('Failed to start timer:', error)
    // Don't fail the entire ticket creation for this - timer can be started manually later
  }
}

const handleAccountSelected = (account) => {
  // Load agents for the selected account if needed
  if (props.canAssignTickets && account) {
    loadAgentsForAccount(account.id)
  }
}

const handleCustomerSelected = (customer) => {
  // Handle customer selection if needed
  // Currently just updates the form value via v-model
  console.log('Customer selected:', customer)
}

const handleUserCreated = (newUser) => {
  // Add the newly created user to the available customers list
  availableCustomers.value.push(newUser)
  console.log('New user created and added to customer list:', newUser)
}

const loadAgentsForAccount = async (accountId) => {
  if (!accountId) {
    availableAgents.value = []
    return
  }
  
  isLoadingAgents.value = true
  try {
    // Use the same endpoint as StartTimerModal for consistency
    const params = {
      per_page: 100,
      agent_type: 'ticket' // Specify ticket agent type
    }
    
    // Only filter by account if one is specified
    if (accountId) {
      params.account_id = accountId
    }
    
    const response = await axios.get('/api/users/agents', { params })
    availableAgents.value = response.data.data || []
    
    console.log('Loaded ticket agents for assignment:', {
      count: availableAgents.value.length,
      accountId
    })
  } catch (error) {
    console.error('Failed to load ticket agents:', error)
    availableAgents.value = []
  } finally {
    isLoadingAgents.value = false
  }
}

const loadCustomersForAccount = async (accountId) => {
  if (!accountId) {
    availableCustomers.value = []
    return
  }
  
  isLoadingCustomers.value = true
  try {
    const response = await axios.get(`/api/accounts/${accountId}/users`, {
      params: {
        per_page: 100,
        role_context: 'account_user' // Filter for customer users only
      }
    })
    
    availableCustomers.value = response.data.data || []
  } catch (error) {
    console.error('Failed to load customers:', error)
    availableCustomers.value = []
  } finally {
    isLoadingCustomers.value = false
  }
}

// Watchers & Lifecycle
onMounted(() => {
  loadCategories()
})

// Watch for account changes to load agents and customers
watch(() => form.account_id, (newAccountId) => {
  if (newAccountId) {
    // Load customers for any account
    loadCustomersForAccount(newAccountId)
    // Clear customer selection when account changes
    form.customer_id = ''
    
    // Load agents only if user can assign tickets
    if (props.canAssignTickets) {
      loadAgentsForAccount(newAccountId)
      // Clear agent selection when account changes
      form.agent_id = ''
    }
  } else {
    availableCustomers.value = []
    availableAgents.value = []
    form.customer_id = ''
    form.agent_id = ''
  }
})

// Watch for modal show/hide
watch(() => props.show, (show) => {
  if (show) {
    resetForm()
    loadCategories()
    // Load data if account is already selected
    if (form.account_id) {
      loadCustomersForAccount(form.account_id)
      if (props.canAssignTickets) {
        loadAgentsForAccount(form.account_id)
      }
    }
  }
}, { immediate: true })

</script>