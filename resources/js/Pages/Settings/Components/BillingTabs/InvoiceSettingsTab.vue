<template>
  <div class="space-y-6">
    <!-- Header -->
    <div>
      <h3 class="text-lg font-medium text-gray-900">Invoice Settings</h3>
      <p class="text-sm text-gray-600">Configure invoice numbering, payment terms, and tax settings.</p>
    </div>

    <!-- Loading State -->
    <div v-if="billingConfigQuery.isLoading.value" class="flex justify-center py-8">
      <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
    </div>

    <!-- Settings Form -->
    <div v-else class="space-y-6">
      <!-- Invoice Settings -->
      <div class="bg-white border border-gray-200 rounded-lg p-6">
        <h4 class="text-lg font-medium text-gray-900 mb-6">Invoice Configuration</h4>
        <form @submit.prevent="saveSettings" class="space-y-6">
          <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
            <div>
              <label for="invoice_prefix" class="block text-sm font-medium text-gray-700">
                Invoice Number Prefix
              </label>
              <input
                id="invoice_prefix"
                v-model="settings.invoice_prefix"
                type="text"
                placeholder="INV-"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
              />
              <p class="mt-1 text-xs text-gray-500">Prefix for auto-generated invoice numbers</p>
            </div>
            <div>
              <label for="default_payment_terms" class="block text-sm font-medium text-gray-700">
                Default Payment Terms (days)
              </label>
              <input
                id="default_payment_terms"
                v-model.number="settings.default_payment_terms"
                type="number"
                min="1"
                max="365"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
              />
              <p class="mt-1 text-xs text-gray-500">Default due date for new invoices</p>
            </div>
          </div>
        </form>
      </div>

      <!-- Tax Settings -->
      <div class="bg-white border border-gray-200 rounded-lg p-6">
        <h4 class="text-lg font-medium text-gray-900 mb-6">Tax Configuration</h4>
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
          <div>
            <label for="default_tax_rate" class="block text-sm font-medium text-gray-700">
              Default Tax Rate (%)
            </label>
            <input
              id="default_tax_rate"
              v-model.number="settings.default_tax_rate"
              type="number"
              min="0"
              max="100"
              step="0.01"
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            />
            <p class="mt-1 text-xs text-gray-500">Default tax rate for taxable items</p>
          </div>
          <div>
            <label for="tax_name" class="block text-sm font-medium text-gray-700">
              Tax Name
            </label>
            <input
              id="tax_name"
              v-model="settings.tax_name"
              type="text"
              placeholder="VAT, Sales Tax, etc."
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            />
            <p class="mt-1 text-xs text-gray-500">Name displayed on invoices</p>
          </div>
        </div>
      </div>

      <!-- Save Button -->
      <div class="flex justify-end">
        <button
          @click="saveSettings"
          :disabled="updateSettingsMutation.isPending.value"
          class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50"
        >
          <span v-if="updateSettingsMutation.isPending.value" class="mr-2">
            <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-white"></div>
          </span>
          {{ updateSettingsMutation.isPending.value ? 'Saving...' : 'Save Settings' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'
import { useBillingConfigQuery, useUpdateBillingSettingsMutation } from '@/Composables/queries/useBillingQuery'

// TanStack Query hooks
const billingConfigQuery = useBillingConfigQuery()
const updateSettingsMutation = useUpdateBillingSettingsMutation()

// Local state
const settings = ref({
  invoice_prefix: 'INV-',
  default_payment_terms: 30,
  default_tax_rate: 0,
  tax_name: 'Tax',
})

// Watch for loaded config and update settings
watch(() => billingConfigQuery.data.value, (config) => {
  if (config?.billing_settings) {
    settings.value = {
      invoice_prefix: config.billing_settings.invoice_prefix || 'INV-',
      default_payment_terms: config.billing_settings.default_payment_terms || 30,
      default_tax_rate: config.billing_settings.default_tax_rate || 0,
      tax_name: config.billing_settings.tax_name || 'Tax',
    }
  }
}, { immediate: true })

// Save settings
const saveSettings = async () => {
  try {
    await updateSettingsMutation.mutateAsync(settings.value)
    // Success feedback could be added here
  } catch (error) {
    console.error('Failed to save invoice settings:', error)
    alert('Failed to save settings. Please try again.')
  }
}
</script>