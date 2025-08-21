<template>
  <StackedDialog
    :show="show"
    :title="mode === 'edit' ? 'Edit Addon' : 'Create Addon'"
    max-width="2xl"
    @close="$emit('close')"
  >
    <template #header-subtitle v-if="mode === 'edit' && addon">
      <p class="mt-1 text-sm text-gray-600">
        Editing addon for {{ addon.ticket?.ticket_number || 'ticket' }}
      </p>
    </template>

    <form @submit.prevent="save" class="space-y-6">
      <!-- Ticket Selection (Create mode only) -->
      <div v-if="mode === 'create'">
        <label class="block text-sm font-medium text-gray-700 mb-2">
          Ticket <span class="text-red-500">*</span>
        </label>
        <UnifiedSelector
          v-model="form.ticket_id"
          type="ticket"
          placeholder="Select a ticket..."
          :clearable="false"
          @item-selected="onTicketSelected"
        />
        <p v-if="errors.ticket_id" class="mt-1 text-sm text-red-600">{{ errors.ticket_id }}</p>
      </div>

      <!-- Basic Information -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Name <span class="text-red-500">*</span>
          </label>
          <input
            v-model="form.name"
            type="text"
            :disabled="!canEditBasicFields"
            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm disabled:bg-gray-100 disabled:text-gray-600"
            placeholder="e.g., SSL Certificate"
            required
          />
          <p v-if="errors.name" class="mt-1 text-sm text-red-600">{{ errors.name }}</p>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Category <span class="text-red-500">*</span>
          </label>
          <select
            v-model="form.category"
            :disabled="!canEditBasicFields"
            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm disabled:bg-gray-100 disabled:text-gray-600"
            required
          >
            <option value="">Select category...</option>
            <option value="product">Product</option>
            <option value="service">Service</option>
            <option value="expense">Expense</option>
            <option value="license">License</option>
            <option value="hardware">Hardware</option>
            <option value="software">Software</option>
            <option value="other">Other</option>
          </select>
          <p v-if="errors.category" class="mt-1 text-sm text-red-600">{{ errors.category }}</p>
        </div>
      </div>

      <!-- Description -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">
          Description
        </label>
        <textarea
          v-model="form.description"
          rows="3"
          class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
          placeholder="Additional details about this addon..."
        ></textarea>
        <p v-if="errors.description" class="mt-1 text-sm text-red-600">{{ errors.description }}</p>
      </div>

      <!-- Pricing -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Unit Price <span class="text-red-500">*</span>
          </label>
          <div class="mt-1 relative rounded-md shadow-sm">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
              <span class="text-gray-500 sm:text-sm">$</span>
            </div>
            <input
              v-model="form.unit_price"
              type="number"
              step="0.01"
              min="0"
              :disabled="!canEditBasicFields"
              class="pl-7 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm disabled:bg-gray-100 disabled:text-gray-600"
              placeholder="0.00"
              required
            />
          </div>
          <p v-if="errors.unit_price" class="mt-1 text-sm text-red-600">{{ errors.unit_price }}</p>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Quantity <span class="text-red-500">*</span>
          </label>
          <input
            v-model="form.quantity"
            type="number"
            step="0.01"
            min="0.01"
            :disabled="!canEditBasicFields"
            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm disabled:bg-gray-100 disabled:text-gray-600"
            placeholder="1"
            required
          />
          <p v-if="errors.quantity" class="mt-1 text-sm text-red-600">{{ errors.quantity }}</p>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Discount Amount
          </label>
          <div class="mt-1 relative rounded-md shadow-sm">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
              <span class="text-gray-500 sm:text-sm">$</span>
            </div>
            <input
              v-model="form.discount_amount"
              type="number"
              step="0.01"
              min="0"
              :disabled="!canEditBasicFields"
              class="pl-7 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm disabled:bg-gray-100 disabled:text-gray-600"
              placeholder="0.00"
            />
          </div>
          <p v-if="errors.discount_amount" class="mt-1 text-sm text-red-600">{{ errors.discount_amount }}</p>
        </div>
      </div>

      <!-- Additional Fields -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            SKU
          </label>
          <input
            v-model="form.sku"
            type="text"
            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
            placeholder="Product SKU or identifier"
          />
          <p v-if="errors.sku" class="mt-1 text-sm text-red-600">{{ errors.sku }}</p>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Billing Category <span class="text-red-500">*</span>
          </label>
          <select
            v-model="form.billing_category"
            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
            required
          >
            <option value="">Select billing category...</option>
            <option value="addon">Addon</option>
            <option value="expense">Expense</option>
            <option value="product">Product</option>
            <option value="service">Service</option>
          </select>
          <p v-if="errors.billing_category" class="mt-1 text-sm text-red-600">{{ errors.billing_category }}</p>
        </div>
      </div>

      <!-- Options -->
      <div class="space-y-4">
        <div class="flex items-center">
          <input
            id="billable"
            v-model="form.billable"
            type="checkbox"
            :disabled="!canEditBasicFields"
            class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded disabled:opacity-50"
          />
          <label for="billable" class="ml-2 block text-sm text-gray-900">
            Billable (include in invoices)
          </label>
        </div>

        <div class="flex items-center">
          <input
            id="is_taxable"
            v-model="form.is_taxable"
            type="checkbox"
            :disabled="!canEditBasicFields"
            class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded disabled:opacity-50"
          />
          <label for="is_taxable" class="ml-2 block text-sm text-gray-900">
            Taxable item
          </label>
        </div>
      </div>

      <!-- Tax information note -->
      <div v-if="form.is_taxable" class="bg-blue-50 border border-blue-200 rounded-md p-3">
        <div class="flex">
          <svg class="w-5 h-5 text-blue-400 mr-2 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
          <div class="text-sm">
            <p class="text-blue-700 font-medium">Tax Information</p>
            <p class="text-blue-600 mt-1">Tax rates will be applied at the invoice level based on account or global tax configuration.</p>
          </div>
        </div>
      </div>

      <!-- Status Display (Edit mode only) -->
      <div v-if="mode === 'edit' && addon">
        <label class="block text-sm font-medium text-gray-700 mb-2">Current Status</label>
        <div class="p-3 bg-gray-50 rounded-md">
          <span :class="getStatusBadgeClass(addon.status)" class="px-2 py-1 rounded-full text-xs font-medium">
            {{ formatStatus(addon.status) }}
          </span>
          <p v-if="addon.approval_notes" class="text-sm text-gray-600 mt-2">
            <span class="font-medium">Notes:</span> {{ addon.approval_notes }}
          </p>
        </div>
      </div>

      <!-- Edit Restrictions Notice -->
      <div v-if="mode === 'edit' && !canEditBasicFields" class="bg-yellow-50 border border-yellow-200 rounded-md p-4">
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

      <!-- Total Calculation Display -->
      <div class="bg-gray-50 p-4 rounded-lg">
        <h4 class="text-sm font-medium text-gray-900 mb-2">Total Calculation</h4>
        <div class="space-y-1 text-sm">
          <div class="flex justify-between">
            <span>Subtotal:</span>
            <span>${{ subtotal.toFixed(2) }}</span>
          </div>
          <div v-if="form.discount_amount > 0" class="flex justify-between text-orange-600">
            <span>Discount:</span>
            <span>-${{ parseFloat(form.discount_amount || 0).toFixed(2) }}</span>
          </div>
          <div class="flex justify-between">
            <span>After Discount:</span>
            <span>${{ afterDiscount.toFixed(2) }}</span>
          </div>
          <div v-if="form.is_taxable" class="flex justify-between text-blue-600">
            <span>Tax:</span>
            <span>Applied at invoice level</span>
          </div>
          <div class="flex justify-between font-medium text-lg border-t pt-1">
            <span>Total:</span>
            <span>${{ totalAmount.toFixed(2) }}</span>
          </div>
        </div>
      </div>

      <!-- Actions -->
      <div class="flex justify-end space-x-3 pt-6 border-t">
        <button
          type="button"
          @click="$emit('close')"
          class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
        >
          Cancel
        </button>
        <button
          type="submit"
          :disabled="saving"
          class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50"
        >
          <span v-if="saving" class="mr-2">
            <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-white inline-block"></div>
          </span>
          {{ saving ? 'Saving...' : (mode === 'edit' ? 'Update Addon' : 'Create Addon') }}
        </button>
      </div>
    </form>
  </StackedDialog>
</template>

<script setup>
import { ref, reactive, computed, watch, nextTick } from 'vue'
import StackedDialog from '@/Components/StackedDialog.vue'
import UnifiedSelector from '@/Components/UI/UnifiedSelector.vue'

const props = defineProps({
  show: {
    type: Boolean,
    default: false
  },
  addon: {
    type: Object,
    default: null
  },
  mode: {
    type: String,
    default: 'create',
    validator: (value) => ['create', 'edit'].includes(value)
  }
})

const emit = defineEmits(['close', 'saved'])

// Form data
const form = reactive({
  ticket_id: '',
  name: '',
  description: '',
  category: '',
  sku: '',
  unit_price: '',
  quantity: 1,
  discount_amount: 0,
  billable: true,
  is_taxable: true,
  billing_category: 'addon'
})

// Form state
const saving = ref(false)
const errors = ref({})

// Computed values for total calculation
const subtotal = computed(() => {
  const price = parseFloat(form.unit_price || 0)
  const qty = parseFloat(form.quantity || 0)
  return price * qty
})

const afterDiscount = computed(() => {
  return Math.max(0, subtotal.value - parseFloat(form.discount_amount || 0))
})

const totalAmount = computed(() => {
  // Tax will be calculated at invoice level, so total here is just after discount
  return afterDiscount.value
})

const canEditBasicFields = computed(() => {
  if (props.mode === 'create') return true
  return props.addon?.status === 'pending' || props.addon?.status === 'rejected'
})

// Methods
const resetForm = () => {
  Object.assign(form, {
    ticket_id: '',
    name: '',
    description: '',
    category: '',
    sku: '',
    unit_price: '',
    quantity: 1,
    discount_amount: 0,
      billable: true,
    is_taxable: true,
    billing_category: 'addon'
  })
  errors.value = {}
}

const populateForm = (addon) => {
  if (!addon) return
  
  Object.assign(form, {
    ticket_id: addon.ticket_id || '',
    name: addon.name || '',
    description: addon.description || '',
    category: addon.category || '',
    sku: addon.sku || '',
    unit_price: addon.unit_price || '',
    quantity: addon.quantity || 1,
    discount_amount: addon.discount_amount || 0,
    billable: addon.billable !== undefined ? addon.billable : true,
    is_taxable: addon.is_taxable !== undefined ? addon.is_taxable : true,
    billing_category: addon.billing_category || 'addon'
  })
}

const onTicketSelected = (ticket) => {
  form.ticket_id = ticket?.id || ''
}

const formatStatus = (status) => {
  return status?.replace('_', ' ').replace(/\b\w/g, (l) => l.toUpperCase()) || 'Unknown'
}

const getStatusBadgeClass = (status) => {
  const classes = {
    pending: 'bg-yellow-100 text-yellow-800',
    approved: 'bg-green-100 text-green-800',
    rejected: 'bg-red-100 text-red-800',
    completed: 'bg-blue-100 text-blue-800'
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
}

const save = async () => {
  saving.value = true
  errors.value = {}

  try {
    const url = props.mode === 'edit' 
      ? `/api/ticket-addons/${props.addon.id}`
      : '/api/ticket-addons'
    
    const method = props.mode === 'edit' ? 'PUT' : 'POST'

    const response = await fetch(url, {
      method,
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
      },
      body: JSON.stringify(form)
    })

    const data = await response.json()

    if (response.ok) {
      emit('saved', data.data || data)
      resetForm()
    } else {
      if (data.errors) {
        errors.value = data.errors
      } else {
        console.error('Addon save failed:', data.message || 'Unknown error')
      }
    }
  } catch (error) {
    console.error('Error saving addon:', error)
  } finally {
    saving.value = false
  }
}

// Watchers
watch(() => props.show, (isShowing) => {
  if (isShowing) {
    if (props.mode === 'edit' && props.addon) {
      populateForm(props.addon)
    } else {
      resetForm()
    }
  }
})

watch(() => props.addon, (newAddon) => {
  if (props.mode === 'edit' && newAddon) {
    populateForm(newAddon)
  }
})
</script>