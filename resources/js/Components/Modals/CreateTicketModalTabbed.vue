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
      <!-- Basic Information Tab -->
      <div v-show="activeTab === 'basic'" class="space-y-6">
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

      <!-- Assignment Tab -->
      <div v-show="activeTab === 'assignment'" class="space-y-6">
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
              :class="{ 'border-red-300 focus:border-red-500 focus:ring-red-500': errors.agent_id }"
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
  { id: 'basic', name: 'Basic Info', icon: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z' },
  { id: 'assignment', name: 'Assignment', icon: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z' },
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
const { data: roleTemplates } = useRoleTemplatesQuery()

// Computed
const canAssignTickets = computed(() => {
  return user.value?.user_type === 'agent' || 
         user.value?.permissions?.includes('tickets.assign') ||
         user.value?.permissions?.includes('admin.write')
})

// Methods
const getCategoryName = (categoryKey) => {
  const category = availableCategories.value.find(c => c.key === categoryKey)
  return category?.name || categoryKey
}

const formatDate = (dateString) => {
  if (!dateString) return ''
  return new Date(dateString).toLocaleString()
}

const handleAccountSelected = (account) => {
  form.customer_id = null
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
    const response = await axios.get(`/api/accounts/${form.account_id}/users`)
    availableCustomers.value = response.data.data.filter(user => user.user_type === 'customer')
  } catch (error) {
    console.error('Failed to load customers:', error)
    availableCustomers.value = []
  } finally {
    isLoadingCustomers.value = false
  }
}

const loadAgents = async () => {
  try {
    isLoadingAgents.value = true
    const response = await axios.get('/api/users/agents')
    availableAgents.value = response.data.data
  } catch (error) {
    console.error('Failed to load agents:', error)
  } finally {
    isLoadingAgents.value = false
  }
}

const loadCategories = async () => {
  try {
    isLoadingCategories.value = true
    const response = await axios.get('/api/ticket-categories')
    availableCategories.value = response.data.data
  } catch (error) {
    console.error('Failed to load categories:', error)
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

    const result = await createTicketMutation.mutateAsync(form)
    
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
    if (error.response?.data?.errors) {
      errors.value = error.response.data.errors
    } else {
      errors.value = { general: ['Failed to create ticket. Please try again.'] }
    }
  } finally {
    isSubmitting.value = false
  }
}

// Watchers
watch(() => form.account_id, () => {
  if (form.account_id) {
    loadCustomers()
  } else {
    availableCustomers.value = []
    form.customer_id = null
  }
})

watch(() => props.show, (isOpen) => {
  if (isOpen) {
    activeTab.value = 'basic'
    errors.value = {}
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