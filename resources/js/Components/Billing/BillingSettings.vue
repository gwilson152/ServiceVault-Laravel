<template>
  <div class="bg-white shadow rounded-lg">
    <div class="px-6 py-4 border-b border-gray-200">
      <h3 class="text-lg font-medium text-gray-900">Billing Settings</h3>
    </div>
    <div class="p-6">
      <form @submit.prevent="handleSubmit" class="space-y-6">
        <!-- Company Information -->
        <div>
          <h4 class="text-md font-medium text-gray-900 mb-4">Company Information</h4>
          <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
            <div>
              <label for="company_name" class="block text-sm font-medium text-gray-700">
                Company Name
              </label>
              <input
                id="company_name"
                v-model="form.company_name"
                type="text"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
              />
            </div>
            <div>
              <label for="company_email" class="block text-sm font-medium text-gray-700">
                Email
              </label>
              <input
                id="company_email"
                v-model="form.company_email"
                type="email"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
              />
            </div>
          </div>
          <div class="mt-4">
            <label for="company_address" class="block text-sm font-medium text-gray-700">
              Address
            </label>
            <textarea
              id="company_address"
              v-model="form.company_address"
              rows="3"
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            />
          </div>
        </div>

        <!-- Invoice Settings -->
        <div>
          <h4 class="text-md font-medium text-gray-900 mb-4">Invoice Settings</h4>
          <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
            <div>
              <label for="invoice_prefix" class="block text-sm font-medium text-gray-700">
                Invoice Number Prefix
              </label>
              <input
                id="invoice_prefix"
                v-model="form.invoice_prefix"
                type="text"
                placeholder="INV-"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
              />
            </div>
            <div>
              <label for="default_payment_terms" class="block text-sm font-medium text-gray-700">
                Default Payment Terms (days)
              </label>
              <input
                id="default_payment_terms"
                v-model.number="form.default_payment_terms"
                type="number"
                min="1"
                max="365"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
              />
            </div>
          </div>
        </div>

        <!-- Tax Settings -->
        <div>
          <h4 class="text-md font-medium text-gray-900 mb-4">Tax Settings</h4>
          <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
            <div>
              <label for="default_tax_rate" class="block text-sm font-medium text-gray-700">
                Default Tax Rate (%)
              </label>
              <input
                id="default_tax_rate"
                v-model.number="form.default_tax_rate"
                type="number"
                min="0"
                max="100"
                step="0.01"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
              />
            </div>
            <div>
              <label for="tax_name" class="block text-sm font-medium text-gray-700">
                Tax Name
              </label>
              <input
                id="tax_name"
                v-model="form.tax_name"
                type="text"
                placeholder="VAT, Sales Tax, etc."
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
              />
            </div>
          </div>
        </div>

        <!-- Save Button -->
        <div class="flex justify-end">
          <button
            type="submit"
            :disabled="saving"
            class="inline-flex justify-center rounded-md border border-transparent bg-indigo-600 py-2 px-4 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-50"
          >
            <span v-if="saving" class="mr-2">
              <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-white"></div>
            </span>
            {{ saving ? 'Saving...' : 'Save Settings' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, watch } from 'vue'

const props = defineProps({
  settings: {
    type: Object,
    default: () => ({})
  }
})

const emit = defineEmits(['update'])

const saving = ref(false)

const form = reactive({
  company_name: '',
  company_email: '',
  company_address: '',
  invoice_prefix: 'INV-',
  default_payment_terms: 30,
  default_tax_rate: 0,
  tax_name: 'Tax'
})

// Watch for settings changes
watch(() => props.settings, (newSettings) => {
  if (newSettings) {
    Object.assign(form, {
      company_name: newSettings.company_name || '',
      company_email: newSettings.company_email || '',
      company_address: newSettings.company_address || '',
      invoice_prefix: newSettings.invoice_prefix || 'INV-',
      default_payment_terms: newSettings.default_payment_terms || 30,
      default_tax_rate: newSettings.default_tax_rate || 0,
      tax_name: newSettings.tax_name || 'Tax'
    })
  }
}, { immediate: true })

const handleSubmit = async () => {
  saving.value = true
  try {
    emit('update', { ...form })
  } finally {
    saving.value = false
  }
}
</script>