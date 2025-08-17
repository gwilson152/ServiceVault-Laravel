<template>
  <TabbedDialog
    :show="show"
    title="Create New Ticket"
    :tabs="tabs"
    default-tab="basic"
    max-width="2xl"
    :saving="isSubmitting"
    save-label="Create Ticket"
    @close="$emit('close')"
    @save="submitForm"
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
        <!-- Basic Information Section -->
        <div class="space-y-6">
          <h4 class="text-base font-medium text-gray-900 border-b border-gray-200 pb-2">Basic Information</h4>
          
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
              :class="{ 'border-red-300 focus:border-red-500 focus:ring-red-500': errors.title }"
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
              :class="{ 'border-red-300 focus:border-red-500 focus:ring-red-500': errors.description }"
              placeholder="Detailed description of the issue, steps to reproduce, or requirements"
            ></textarea>
            <p v-if="errors.description" class="mt-1 text-sm text-red-600">{{ errors.description }}</p>
          </div>
        </div>

        <!-- Assignment Section -->
        <div class="space-y-6">
          <h4 class="text-base font-medium text-gray-900 border-b border-gray-200 pb-2">Assignment</h4>
          
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
              :can-create="true"
              :nested="true"
              :error="errors.account_id"
              @item-selected="handleAccountSelected"
              @item-created="handleAccountCreated"
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
            <UnifiedSelector
              v-model="form.agent_id"
              type="agent"
              :items="availableAgents"
              :agent-type="'ticket'"
              label="Assign Agent"
              placeholder="Select an agent (optional)..."
              :can-create="true"
              :nested="true"
              :error="errors.agent_id"
              @item-selected="handleAgentSelected"
              @item-created="handleAgentCreated"
            />
            <p class="mt-1 text-xs text-gray-500">Select the agent to assign this ticket to (optional)</p>
          </div>
        </div>
      </div>

      <!-- Classification Tab -->
      <div v-show="activeTab === 'classification'" class="space-y-6">
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
              :class="{ 'border-red-300 focus:border-red-500 focus:ring-red-500': errors.priority }"
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
              :class="{ 'border-red-300 focus:border-red-500 focus:ring-red-500': errors.category }"
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
            :class="{ 'border-red-300 focus:border-red-500 focus:ring-red-500': errors.due_date }"
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
            :class="{ 'border-red-300 focus:border-red-500 focus:ring-red-500': errors.tags }"
            placeholder="Enter tags separated by commas (e.g., server, database, urgent)"
          >
          <p class="mt-1 text-sm text-gray-500">Separate multiple tags with commas</p>
          <p v-if="errors.tags" class="mt-1 text-sm text-red-600">{{ errors.tags }}</p>
        </div>
      </div>

      <!-- Options Tab -->
      <div v-show="activeTab === 'options'" class="space-y-6">
        <!-- Additional Options -->
        <div class="bg-gray-50 rounded-lg p-4 space-y-4">
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

        <!-- Summary -->
        <div class="bg-blue-50 rounded-lg p-4">
          <h4 class="text-sm font-medium text-blue-900 mb-2">Ticket Summary</h4>
          <div class="text-sm text-blue-800 space-y-1">
            <p><strong>Title:</strong> {{ form.title || 'Not specified' }}</p>
            <p><strong>Priority:</strong> {{ form.priority || 'Not specified' }}</p>
            <p><strong>Category:</strong> {{ getCategoryName(form.category) || 'Not specified' }}</p>
            <p v-if="form.due_date"><strong>Due:</strong> {{ formatDate(form.due_date) }}</p>
          </div>
        </div>
      </div>
    </template>
  </TabbedDialog>
</template>

<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue'
import { usePage } from '@inertiajs/vue3'
import TabbedDialog from '@/Components/TabbedDialog.vue'
import UnifiedSelector from '@/Components/UI/UnifiedSelector.vue'
import UserSelector from '@/Components/UI/UserSelector.vue'
import axios from 'axios'
import { useCreateTicketMutation } from '@/Composables/queries/useTicketsQuery'
import { useRoleTemplatesQuery } from '@/Composables/queries/useUsersQuery'

// Props
const props = defineProps({
  show: {
    type: Boolean,
    default: false
  },
  nested: {
    type: Boolean,
    default: false
  }
})

// Emits
const emit = defineEmits(['close', 'ticket-created'])

// Tab configuration
const tabs = [
  { id: 'basic', name: 'Basic Info & Assignment', icon: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z' },
  { id: 'classification', name: 'Details', icon: 'M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z' },
  { id: 'options', name: 'Options', icon: 'M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4' }
]

const activeTab = ref('basic')

// State
const isSubmitting = ref(false)
const availableAccounts = ref([])
const availableCustomers = ref([])
const availableAgents = ref([])
const availableCategories = ref([])
const isLoadingCustomers = ref(false)
const isLoadingAgents = ref(false)
const isLoadingCategories = ref(false)
const flatAccounts = ref([])

// Form data
const form = reactive({
  title: '',
  description: '',
  account_id: null,
  customer_id: null,
  agent_id: null,
  priority: 'normal',
  category: '',
  due_date: '',
  tags: '',
  start_timer: false,
  send_notifications: true
})

// Errors
const errors = ref({})

// Page data
const page = usePage()
const user = computed(() => page.props.auth?.user)

// TanStack Query
const createTicketMutation = useCreateTicketMutation()
const { data: roleTemplatesData } = useRoleTemplatesQuery()

// Computed
const canAssignTickets = computed(() => {
  // Simplified ABAC logic - check if user can assign tickets to others
  return user.value?.permissions?.includes('tickets.assign') ||
         user.value?.permissions?.includes('admin.write')
})

const roleTemplates = computed(() => roleTemplatesData.value?.data || [])

// Methods
const getCategoryName = (categoryKey) => {
  const category = availableCategories.value.find(c => c.key === categoryKey)
  return category?.name || categoryKey
}

const resetForm = () => {
  form.title = ''
  form.description = ''
  form.account_id = null
  form.customer_id = null
  form.agent_id = null
  form.priority = 'normal'
  form.category = ''
  form.due_date = ''
  form.tags = ''
  form.start_timer = false
  form.send_notifications = true
  errors.value = {}
}

const handleAgentSelected = (agent) => {
  // Agent selection is automatically handled by v-model
  console.log('Agent selected:', agent)
}

const handleAgentCreated = (newAgent) => {
  // Add the newly created agent to the available agents list
  availableAgents.value.push(newAgent)
  console.log('New agent created and added to list:', newAgent)
}

const formatDate = (dateString) => {
  if (!dateString) return ''
  return new Date(dateString).toLocaleString()
}

const handleAccountSelected = (account) => {
  form.customer_id = null
  loadCustomers()
}

const handleAccountCreated = (newAccount) => {
  // Refresh the accounts list to include the new account
  loadAccounts()
  // The UnifiedSelector will automatically select the newly created account
  // Clear customer selection since we're switching accounts
  form.customer_id = null
  // Load customers for the new account
  loadCustomers()
}

const handleCustomerSelected = (customer) => {
  // Handle customer selection
}

const handleUserCreated = (user) => {
  form.customer_id = user.id
  loadCustomers()
}

const loadAccounts = async () => {
  try {
    const response = await axios.get('/api/accounts')
    availableAccounts.value = response.data.data
    flatAccounts.value = flattenAccounts(response.data.data)
  } catch (error) {
    console.error('Failed to load accounts:', error)
  }
}

const loadCustomers = async () => {
  if (!form.account_id) {
    availableCustomers.value = []
    return
  }

  try {
    isLoadingCustomers.value = true
    const response = await axios.get(`/api/accounts/${form.account_id}/users`, {
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

const loadAgents = async (accountId = null) => {
  try {
    isLoadingAgents.value = true
    const params = {
      agent_type: 'ticket',
      per_page: 100
    }
    
    // Don't filter by account for now - let the backend handle permissions
    // The API should return all agents with tickets.act_as_agent permission
    // including super admins who can be assigned to any account
    
    const response = await axios.get('/api/users/agents', { params })
    availableAgents.value = response.data.data || []
    console.log('CreateTicketModalTabbed - Loaded ticket agents:', {
      count: availableAgents.value.length,
      accountId: accountId,
      agents: availableAgents.value.map(a => ({ id: a.id, name: a.name, email: a.email, user_type: a.user_type, permissions: a.permissions?.includes?.('tickets.act_as_agent') }))
    })
  } catch (error) {
    console.error('Failed to load agents:', error)
    availableAgents.value = []
  } finally {
    isLoadingAgents.value = false
  }
}

const loadCategories = async () => {
  try {
    isLoadingCategories.value = true
    const response = await axios.get('/api/ticket-categories/options')
    availableCategories.value = response.data.options || []
    
    console.log('CreateTicketModalTabbed - Categories loaded:', {
      categoriesCount: availableCategories.value.length,
      defaultCategory: response.data.default_category,
      currentFormCategory: form.category
    })
    
    // Set default category if available and form category is empty
    if (response.data.default_category && (!form.category || form.category === '')) {
      form.category = response.data.default_category
      console.log('CreateTicketModalTabbed - Set default category:', response.data.default_category)
    } else if (availableCategories.value.length > 0 && (!form.category || form.category === '')) {
      // Fallback: if no explicit default but categories exist, use the first one
      form.category = availableCategories.value[0].key
      console.log('CreateTicketModalTabbed - Set first category as default:', availableCategories.value[0].key)
    }
  } catch (error) {
    console.error('Failed to load categories:', error)
    availableCategories.value = []
  } finally {
    isLoadingCategories.value = false
  }
}

const flattenAccounts = (accounts, depth = 0) => {
  let result = []
  for (const account of accounts) {
    result.push({
      ...account,
      display_name: '  '.repeat(depth) + account.name,
      depth
    })
    if (account.children && account.children.length > 0) {
      result.push(...flattenAccounts(account.children, depth + 1))
    }
  }
  return result
}

const submitForm = async () => {
  try {
    isSubmitting.value = true
    errors.value = {}

    // Prepare form data
    const payload = { ...form }
    
    console.log('CreateTicketModalTabbed - Form data before processing:', payload)
    
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
    
    // Remove empty agent_id to allow tickets without agents
    if (!payload.agent_id || payload.agent_id === '') {
      delete payload.agent_id
      console.log('Creating ticket without agent assignment')
    }
    
    // Remove empty customer_id
    if (!payload.customer_id) {
      delete payload.customer_id
    }
    
    console.log('CreateTicketModalTabbed - Processed payload:', payload)

    const result = await createTicketMutation.mutateAsync(payload)
    
    emit('ticket-created', result.data.data)
    emit('close')
    
    // Reset form
    Object.assign(form, {
      title: '',
      description: '',
      account_id: null,
      customer_id: null,
      agent_id: null,
      priority: 'normal',
      category: '',
      due_date: '',
      tags: '',
      start_timer: false,
      send_notifications: true
    })
    
  } catch (error) {
    console.error('Failed to create ticket:', error)
    console.error('Response data:', error.response?.data)
    console.error('Form data sent:', form)
    
    if (error.response?.data?.errors) {
      console.error('Validation errors:', error.response.data.errors)
      errors.value = error.response.data.errors
    } else {
      errors.value = { general: ['Failed to create ticket. Please try again.'] }
    }
  } finally {
    isSubmitting.value = false
  }
}

// Watchers
watch(() => form.account_id, (newAccountId) => {
  if (newAccountId) {
    loadCustomers()
    // Don't reload agents - keep all ticket agents available regardless of account
    // Super admins and users with tickets.act_as_agent should be assignable to any account
  } else {
    availableCustomers.value = []
    form.customer_id = null
    // Don't clear agents when account is deselected
  }
})

watch(() => props.show, async (isOpen) => {
  if (isOpen) {
    activeTab.value = 'basic'
    resetForm()
    await loadCategories() // Reload categories and set default when modal opens
  }
})

// Load data on mount
onMounted(() => {
  loadAccounts()
  if (canAssignTickets.value) {
    loadAgents()
  }
  loadCategories()
})
</script>