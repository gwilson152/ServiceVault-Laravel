<template>
  <StackedDialog
    :show="true"
    title="Edit Add-on"
    max-width="lg"
    @close="$emit('cancelled')"
  >
    <div class="space-y-4">
      <p class="text-sm text-gray-600">Update the add-on item details</p>

      <form @submit.prevent="submitForm" class="space-y-4">
        <!-- Current Status Display -->
        <div v-if="addon">
          <label class="block text-sm font-medium text-gray-700 mb-1">Current Status</label>
          <div class="p-3 bg-gray-50 rounded-md">
            <span :class="getStatusClasses(addon.status)" class="px-2 py-1 rounded-full text-xs font-medium">
              {{ formatStatus(addon.status) }}
            </span>
            <p v-if="addon.approval_notes" class="text-sm text-gray-600 mt-2">
              <span class="font-medium">Notes:</span> {{ addon.approval_notes }}
            </p>
          </div>
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
            placeholder="Name of the add-on item"
            :disabled="!canEditBasicFields"
            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 disabled:bg-gray-100 disabled:text-gray-600"
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
            :disabled="!canEditBasicFields"
            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 disabled:bg-gray-100 disabled:text-gray-600"
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
              :disabled="!canEditBasicFields"
              class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 disabled:bg-gray-100 disabled:text-gray-600"
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
                :disabled="!canEditBasicFields"
                class="w-full border border-gray-300 rounded-md pl-8 pr-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 disabled:bg-gray-100 disabled:text-gray-600"
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
              :disabled="!canEditBasicFields"
              class="w-full border border-gray-300 rounded-md pl-8 pr-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 disabled:bg-gray-100 disabled:text-gray-600"
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
              :disabled="!canEditBasicFields"
              class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded disabled:opacity-50"
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
              :disabled="!canEditBasicFields"
              class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded disabled:opacity-50"
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

        <!-- Edit Restrictions Notice -->
        <div v-if="!canEditBasicFields" class="bg-yellow-50 border border-yellow-200 rounded-md p-4">
          <div class="flex">
            <svg class="w-5 h-5 text-yellow-400 mr-2 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <div class="text-sm">
              <p class="text-yellow-700 font-medium">Limited Editing</p>
              <p class="text-yellow-600 mt-1">
                Basic item details cannot be changed because this add-on has been {{ addon?.status }}.
              </p>
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
          {{ submitting ? 'Updating...' : 'Update Add-on' }}
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
  },
  addon: {
    type: Object,
    required: true
  }
})

// Emits
const emit = defineEmits(['saved', 'cancelled'])

// Reactive data
const submitting = ref(false)

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

const canEditBasicFields = computed(() => {
  return props.addon?.status === 'pending' || props.addon?.status === 'rejected'
})

// Methods
const formatStatus = (status) => {
  return status?.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase()) || 'Unknown'
}

const getStatusClasses = (status) => {
  const statusMap = {
    'pending': 'bg-yellow-100 text-yellow-800',
    'approved': 'bg-green-100 text-green-800',
    'rejected': 'bg-red-100 text-red-800',
    'completed': 'bg-blue-100 text-blue-800'
  }
  
  return statusMap[status] || 'bg-gray-100 text-gray-800'
}

const populateForm = () => {
  if (!props.addon) return

  form.value = {
    name: props.addon.name || '',
    category: props.addon.category || '',
    description: props.addon.description || '',
    quantity: props.addon.quantity || 1,
    unit_price: props.addon.unit_price || 0,
    discount_amount: props.addon.discount_amount || 0,
    is_billable: props.addon.is_billable !== undefined ? props.addon.is_billable : true,
    is_taxable: props.addon.is_taxable !== undefined ? props.addon.is_taxable : true,
    billing_category: props.addon.billing_category || 'addon'
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

    await axios.put(`/api/ticket-addons/${props.addon.id}`, payload)
    emit('saved')
  } catch (error) {
    if (error.response?.data?.errors) {
      errors.value = error.response.data.errors
    } else {
      console.error('Failed to update addon:', error)
      errors.value = { general: 'Failed to update add-on. Please try again.' }
    }
  } finally {
    submitting.value = false
  }
}

// Lifecycle
onMounted(() => {
  populateForm()
})
</script>