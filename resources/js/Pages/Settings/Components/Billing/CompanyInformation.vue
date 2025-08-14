<template>
  <div class="space-y-8">
    <!-- Company Information Header -->
    <div>
      <h3 class="text-lg font-medium text-gray-900">Company Information</h3>
      <p class="text-gray-600 mt-1">Configure your company details for invoices and billing</p>
    </div>

    <!-- Loading State -->
    <div v-if="isLoading" class="flex justify-center py-8">
      <svg class="animate-spin h-8 w-8 text-indigo-600" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
      </svg>
    </div>

    <!-- Company Information Form -->
    <div v-else class="bg-white border border-gray-200 rounded-lg p-6">
      <form @submit.prevent="saveSettings" class="space-y-6">
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
        
        <div>
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

        <!-- Invoice Settings -->
        <div class="border-t border-gray-200 pt-6">
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
        <div class="border-t border-gray-200 pt-6">
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
        <div class="flex justify-end border-t border-gray-200 pt-6">
          <button
            type="submit"
            :disabled="updateMutation.isPending.value"
            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50"
          >
            <span v-if="updateMutation.isPending.value" class="mr-2">
              <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-white"></div>
            </span>
            {{ updateMutation.isPending.value ? 'Saving...' : 'Save Settings' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, watch } from 'vue'
import { useBillingConfigQuery, useUpdateBillingSettingsMutation } from '@/Composables/queries/useBillingQuery'

// TanStack Query hooks
const { data: billingConfig, isLoading } = useBillingConfigQuery()
const updateMutation = useUpdateBillingSettingsMutation()

// Form data
const form = reactive({
  company_name: '',
  company_email: '',
  company_address: '',
  invoice_prefix: 'INV-',
  default_payment_terms: 30,
  default_tax_rate: 0,
  tax_name: 'Tax',
})

// Watch for config changes and update form
watch(() => billingConfig.value?.billing_settings, (newSettings) => {
  if (newSettings) {
    Object.assign(form, {
      company_name: '',
      company_email: '',
      company_address: '',
      invoice_prefix: 'INV-',
      default_payment_terms: 30,
      default_tax_rate: 0,
      tax_name: 'Tax',
      ...newSettings
    })
  }
}, { deep: true, immediate: true })

// Save settings
const saveSettings = async () => {
  try {
    await updateMutation.mutateAsync(form)
    // Success feedback could be added here
    console.log('Company information saved successfully')
  } catch (error) {
    console.error('Failed to save company information:', error)
    // Error feedback could be added here
  }
}
</script>