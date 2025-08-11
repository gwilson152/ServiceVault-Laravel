<template>
  <Modal :show="show" @close="$emit('close')" max-width="2xl">
    <div class="p-6">
      <div class="flex items-center justify-between mb-6">
        <h3 class="text-lg font-semibold text-gray-900">Create New Ticket</h3>
        <button
          @click="$emit('close')"
          class="text-gray-400 hover:text-gray-600 transition-colors"
        >
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

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

        <!-- Account & Agent Selection -->
        <SimpleAccountUserSelector
          v-model:account-id="form.account_id"
          v-model:user-id="form.agent_id"
          :account-error="errors.account_id"
          :user-error="errors.agent_id"
          :show-user-selector="canAssignTickets"
        />

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
  </Modal>
</template>

<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue'
import { usePage } from '@inertiajs/vue3'
import Modal from '@/Components/Modal.vue'
import SimpleAccountUserSelector from '@/Components/UI/SimpleAccountUserSelector.vue'
import axios from 'axios'

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
  }
})

// Emits
const emit = defineEmits(['close', 'created'])

// State
const isSubmitting = ref(false)
const errors = ref({})
const isLoadingCategories = ref(false)
const availableCategories = ref([])

// Form data
const form = reactive({
  title: '',
  description: '',
  priority: 'normal',
  account_id: '',
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

// Methods
const resetForm = () => {
  form.title = ''
  form.description = ''
  form.priority = 'normal'
  form.account_id = user.value?.account_id || ''
  form.category = ''
  form.due_date = ''
  form.agent_id = ''
  form.tags = ''
  form.start_timer = false
  form.send_notifications = true
  errors.value = {}
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

    // If no account selected but only one available, use it
    if (!payload.account_id && props.availableAccounts.length === 1) {
      payload.account_id = props.availableAccounts[0].id
    }
    
    // Remove empty account_id to let backend handle auto-assignment
    if (!payload.account_id) {
      delete payload.account_id
    }

    const response = await axios.post('/api/tickets', payload)
    
    // Success
    const newTicket = response.data.data
    
    // Start timer if requested
    if (form.start_timer) {
      await startTimerForTicket(newTicket.id)
    }

    emit('created', newTicket)
    resetForm()
  } catch (error) {
    console.error('Error creating ticket:', error)
    errors.value = { general: 'Network error. Please try again.' }
  } finally {
    isSubmitting.value = false
  }
}

const startTimerForTicket = async (ticketId) => {
  try {
    await axios.post('/api/timers', {
      ticket_id: ticketId,
      description: `Working on ticket: ${form.title}`
    })
  } catch (error) {
    console.error('Failed to start timer:', error)
    // Don't fail the entire ticket creation for this
  }
}

// Watchers & Lifecycle
onMounted(() => {
  loadCategories()
})

// Watch for modal show/hide
watch(() => props.show, (show) => {
  if (show) {
    resetForm()
    loadCategories()
  }
}, { immediate: true })

</script>