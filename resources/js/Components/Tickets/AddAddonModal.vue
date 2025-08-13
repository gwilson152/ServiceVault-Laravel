<template>
  <div class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center p-4 z-50">
    <div class="bg-white rounded-lg shadow-xl max-w-lg w-full max-h-[90vh] overflow-y-auto">
      <!-- Modal header -->
      <div class="px-6 py-4 border-b border-gray-200">
        <h3 class="text-lg font-medium text-gray-900">Add Ticket Add-on</h3>
        <p class="text-sm text-gray-600 mt-1">Request additional work or services for this ticket</p>
      </div>

      <!-- Modal body -->
      <form @submit.prevent="submitForm" class="px-6 py-4 space-y-4">
        <!-- Add-on Template Selection -->
        <div v-if="addonTemplates.length > 0">
          <label class="block text-sm font-medium text-gray-700 mb-1">
            Quick Start Template (optional)
          </label>
          <select 
            v-model="selectedTemplate" 
            @change="applyTemplate"
            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
          >
            <option value="">Custom Add-on</option>
            <option v-for="template in addonTemplates" :key="template.id" :value="template">
              {{ template.title }} - {{ template.type_label }}
            </option>
          </select>
        </div>

        <!-- Title -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">
            Title <span class="text-red-500">*</span>
          </label>
          <input 
            v-model="form.title" 
            type="text" 
            required
            placeholder="Brief description of the additional work"
            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
          />
          <p v-if="errors.title" class="text-red-500 text-xs mt-1">{{ errors.title }}</p>
        </div>

        <!-- Type -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">
            Type <span class="text-red-500">*</span>
          </label>
          <select 
            v-model="form.type" 
            required
            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
          >
            <option value="">Select Type</option>
            <option value="additional_work">Additional Work</option>
            <option value="emergency_support">Emergency Support</option>
            <option value="consultation">Consultation</option>
            <option value="training">Training</option>
            <option value="custom">Custom</option>
          </select>
          <p v-if="errors.type" class="text-red-500 text-xs mt-1">{{ errors.type }}</p>
        </div>

        <!-- Priority -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">
            Priority <span class="text-red-500">*</span>
          </label>
          <select 
            v-model="form.priority" 
            required
            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
          >
            <option value="low">Low</option>
            <option value="normal">Normal</option>
            <option value="high">High</option>
            <option value="urgent">Urgent</option>
          </select>
          <p v-if="errors.priority" class="text-red-500 text-xs mt-1">{{ errors.priority }}</p>
        </div>

        <!-- Description -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">
            Description <span class="text-red-500">*</span>
          </label>
          <textarea 
            v-model="form.description" 
            rows="4"
            required
            placeholder="Detailed description of the additional work or services needed..."
            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
          ></textarea>
          <p v-if="errors.description" class="text-red-500 text-xs mt-1">{{ errors.description }}</p>
        </div>

        <!-- Justification -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">
            Business Justification
          </label>
          <textarea 
            v-model="form.justification" 
            rows="2"
            placeholder="Why is this additional work necessary? How does it benefit the project?"
            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
          ></textarea>
        </div>

        <!-- Estimated Hours -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">
            Estimated Hours <span class="text-red-500">*</span>
          </label>
          <input 
            v-model.number="form.estimated_hours" 
            type="number" 
            min="0"
            step="0.5"
            required
            placeholder="0"
            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
          />
          <p v-if="errors.estimated_hours" class="text-red-500 text-xs mt-1">{{ errors.estimated_hours }}</p>
        </div>

        <!-- Estimated Cost -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">
            Estimated Cost <span class="text-red-500">*</span>
          </label>
          <div class="relative">
            <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500">$</span>
            <input 
              v-model.number="form.estimated_cost" 
              type="number" 
              min="0"
              step="0.01"
              required
              placeholder="0.00"
              class="w-full border border-gray-300 rounded-md pl-8 pr-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            />
          </div>
          <p v-if="errors.estimated_cost" class="text-red-500 text-xs mt-1">{{ errors.estimated_cost }}</p>
        </div>

        <!-- Expected Delivery Date -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">
            Expected Delivery Date
          </label>
          <input 
            v-model="form.expected_completion_date" 
            type="date" 
            :min="today"
            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
          />
        </div>

        <!-- Approval Required Notice -->
        <div class="bg-blue-50 border border-blue-200 rounded-md p-4">
          <div class="flex">
            <svg class="w-5 h-5 text-blue-400 mr-2 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <div class="text-sm">
              <p class="text-blue-700 font-medium">Approval Required</p>
              <p class="text-blue-600 mt-1">This add-on request will be submitted for approval before work begins.</p>
            </div>
          </div>
        </div>
      </form>

      <!-- Modal footer -->
      <div class="px-6 py-4 border-t border-gray-200 flex items-center justify-end space-x-3">
        <button
          @click="$emit('cancelled')"
          type="button"
          class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
        >
          Cancel
        </button>
        <button
          @click="submitForm"
          :disabled="submitting"
          class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          {{ submitting ? 'Submitting...' : 'Submit Add-on Request' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'

// Props
const props = defineProps({
  ticket: {
    type: Object,
    required: true
  }
})

// Emits
const emit = defineEmits(['saved', 'cancelled'])

// Reactive data
const submitting = ref(false)
const addonTemplates = ref([])
const selectedTemplate = ref('')

// Form data
const form = ref({
  title: '',
  type: '',
  priority: 'normal',
  description: '',
  justification: '',
  estimated_hours: 0,
  estimated_cost: 0,
  expected_completion_date: ''
})

// Form errors
const errors = ref({})

// Computed properties
const today = computed(() => {
  return new Date().toISOString().split('T')[0]
})

// Methods
const loadAddonTemplates = async () => {
  try {
    const response = await axios.get('/api/addon-templates')
    addonTemplates.value = response.data.data || []
  } catch (error) {
    console.error('Failed to load addon templates:', error)
    addonTemplates.value = []
  }
}

const applyTemplate = () => {
  if (!selectedTemplate.value) return

  const template = selectedTemplate.value
  form.value = {
    title: template.title || '',
    type: template.type || '',
    priority: template.priority || 'normal',
    description: template.description || '',
    justification: template.justification || '',
    estimated_hours: template.estimated_hours || 0,
    estimated_cost: template.estimated_cost || 0,
    expected_completion_date: form.value.expected_completion_date
  }
}

const validateForm = () => {
  errors.value = {}

  if (!form.value.title.trim()) {
    errors.value.title = 'Title is required'
  }

  if (!form.value.type) {
    errors.value.type = 'Type is required'
  }

  if (!form.value.priority) {
    errors.value.priority = 'Priority is required'
  }

  if (!form.value.description.trim()) {
    errors.value.description = 'Description is required'
  }

  if (!form.value.estimated_hours || form.value.estimated_hours <= 0) {
    errors.value.estimated_hours = 'Estimated hours must be greater than 0'
  }

  if (!form.value.estimated_cost || form.value.estimated_cost <= 0) {
    errors.value.estimated_cost = 'Estimated cost must be greater than 0'
  }

  return Object.keys(errors.value).length === 0
}

const submitForm = async () => {
  if (!validateForm()) return

  submitting.value = true

  try {
    const payload = {
      ticket_id: props.ticket.id,
      title: form.value.title.trim(),
      type: form.value.type,
      priority: form.value.priority,
      description: form.value.description.trim(),
      justification: form.value.justification.trim(),
      estimated_hours: form.value.estimated_hours,
      estimated_cost: form.value.estimated_cost,
      expected_completion_date: form.value.expected_completion_date || null
    }

    await axios.post('/api/ticket-addons', payload)
    emit('saved')
  } catch (error) {
    if (error.response?.data?.errors) {
      errors.value = error.response.data.errors
    } else {
      console.error('Failed to create addon:', error)
      errors.value = { general: 'Failed to create add-on request. Please try again.' }
    }
  } finally {
    submitting.value = false
  }
}

// Lifecycle
onMounted(() => {
  loadAddonTemplates()
})
</script>