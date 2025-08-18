<template>
  <StackedDialog
    :show="true"
    title="Add Ticket Add-on"
    max-width="lg"
    @close="$emit('cancelled')"
  >
    <div class="space-y-4">
      <p class="text-sm text-gray-600">Add additional items, services, or charges to this ticket</p>

      <form @submit.prevent="submitForm" class="space-y-4">
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
              {{ template.name }} - {{ template.category }}
            </option>
          </select>
        </div>

        <!-- Name -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">
            Item Name <span class="text-red-500">*</span>
          </label>
          <input 
            v-model="form.name" 
            type="text" 
            required
            placeholder="Name of the additional item or service"
            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
          />
          <p v-if="errors.name" class="text-red-500 text-xs mt-1">{{ errors.name }}</p>
        </div>

        <!-- Category -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">
            Category
          </label>
          <select 
            v-model="form.category"
            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
          >
            <option value="">Select Category</option>
            <option value="product">Product</option>
            <option value="service">Service</option>
            <option value="license">License</option>
            <option value="hardware">Hardware</option>
            <option value="software">Software</option>
            <option value="expense">Expense</option>
            <option value="other">Other</option>
          </select>
          <p v-if="errors.category" class="text-red-500 text-xs mt-1">{{ errors.category }}</p>
        </div>

        <!-- Description -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">
            Description
          </label>
          <textarea 
            v-model="form.description" 
            rows="3"
            placeholder="Detailed description of the add-on item..."
            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
          ></textarea>
          <p v-if="errors.description" class="text-red-500 text-xs mt-1">{{ errors.description }}</p>
        </div>

        <!-- Quantity and Unit Price Row -->
        <div class="grid grid-cols-2 gap-4">
          <!-- Quantity -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              Quantity <span class="text-red-500">*</span>
            </label>
            <input 
              v-model.number="form.quantity" 
              type="number" 
              min="0"
              step="0.01"
              required
              placeholder="1"
              class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            />
            <p v-if="errors.quantity" class="text-red-500 text-xs mt-1">{{ errors.quantity }}</p>
          </div>

          <!-- Unit Price -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              Unit Price <span class="text-red-500">*</span>
            </label>
            <div class="relative">
              <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500">$</span>
              <input 
                v-model.number="form.unit_price" 
                type="number" 
                min="0"
                step="0.01"
                required
                placeholder="0.00"
                class="w-full border border-gray-300 rounded-md pl-8 pr-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              />
            </div>
            <p v-if="errors.unit_price" class="text-red-500 text-xs mt-1">{{ errors.unit_price }}</p>
          </div>
        </div>

        <!-- Discount Amount -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">
            Discount Amount
          </label>
          <div class="relative">
            <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500">$</span>
            <input 
              v-model.number="form.discount_amount" 
              type="number" 
              min="0"
              step="0.01"
              placeholder="0.00"
              class="w-full border border-gray-300 rounded-md pl-8 pr-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            />
          </div>
        </div>

        <!-- Checkboxes Row -->
        <div class="grid grid-cols-2 gap-4">
          <div class="flex items-center">
            <input 
              v-model="form.is_billable" 
              type="checkbox" 
              id="billable"
              class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
            />
            <label for="billable" class="ml-2 block text-sm text-gray-900">
              Billable
            </label>
          </div>
          
          <div class="flex items-center">
            <input 
              v-model="form.is_taxable" 
              type="checkbox" 
              id="taxable"
              class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
            />
            <label for="taxable" class="ml-2 block text-sm text-gray-900">
              Taxable
            </label>
          </div>
        </div>

        <!-- Total Preview -->
        <div v-if="totalPreview > 0" class="bg-gray-50 rounded-lg p-4">
          <div class="flex items-center justify-between">
            <span class="text-sm font-medium text-gray-700">Estimated Total:</span>
            <span class="text-lg font-bold text-gray-900">${{ totalPreview.toFixed(2) }}</span>
          </div>
        </div>

        <!-- Status Notice -->
        <div class="bg-blue-50 border border-blue-200 rounded-md p-4">
          <div class="flex">
            <svg class="w-5 h-5 text-blue-400 mr-2 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <div class="text-sm">
              <p class="text-blue-700 font-medium">Add-on Status</p>
              <p class="text-blue-600 mt-1">New add-ons will be created with "{{ form.status }}" status and may require approval before billing.</p>
            </div>
          </div>
        </div>
      </form>
    </div>


    <template #footer>
      <div class="flex items-center justify-end space-x-3">
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
          {{ submitting ? 'Adding...' : 'Add Item' }}
        </button>
      </div>
    </template>
  </StackedDialog>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'
import StackedDialog from '@/Components/StackedDialog.vue'

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
  name: '',
  category: '',
  description: '',
  quantity: 1,
  unit_price: 0,
  discount_amount: 0,
  is_billable: true,
  is_taxable: true,
  billing_category: 'addon'
})

// Form errors
const errors = ref({})

// Computed properties
const totalPreview = computed(() => {
  const subtotal = (form.value.quantity || 0) * (form.value.unit_price || 0)
  return Math.max(0, subtotal - (form.value.discount_amount || 0))
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
    name: template.name || '',
    category: template.category || '',
    description: template.description || '',
    quantity: template.default_quantity || 1,
    unit_price: template.default_price || 0,
    discount_amount: 0,
    is_billable: true,
    is_taxable: template.is_taxable || true,
    billing_category: 'addon'
  }
}

const validateForm = () => {
  errors.value = {}

  if (!form.value.name.trim()) {
    errors.value.name = 'Item name is required'
  }

  if (!form.value.quantity || form.value.quantity <= 0) {
    errors.value.quantity = 'Quantity must be greater than 0'
  }

  if (!form.value.unit_price || form.value.unit_price <= 0) {
    errors.value.unit_price = 'Unit price must be greater than 0'
  }

  return Object.keys(errors.value).length === 0
}

const submitForm = async () => {
  if (!validateForm()) return

  submitting.value = true

  try {
    const payload = {
      ticket_id: props.ticket.id,
      name: form.value.name.trim(),
      category: form.value.category || 'other',
      description: form.value.description.trim() || null,
      quantity: form.value.quantity,
      unit_price: form.value.unit_price,
      discount_amount: form.value.discount_amount || 0,
      is_billable: form.value.is_billable,
      is_taxable: form.value.is_taxable,
      billing_category: form.value.billing_category
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