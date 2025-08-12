<template>
  <div class="space-y-8">
    <!-- Billing Configuration Header -->
    <div>
      <h2 class="text-2xl font-semibold text-gray-900">Billing & Invoicing</h2>
      <p class="text-gray-600 mt-2">Configure company information, invoice settings, tax configuration, and billing rates.</p>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex justify-center py-8">
      <svg class="animate-spin h-8 w-8 text-indigo-600" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
      </svg>
    </div>

    <template v-else>
      <!-- Company Information -->
      <div class="bg-white border border-gray-200 rounded-lg p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-6">Company Information</h3>
        <form @submit.prevent="saveBillingSettings" class="space-y-6">
          <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
            <div>
              <label for="company_name" class="block text-sm font-medium text-gray-700">
                Company Name
              </label>
              <input
                id="company_name"
                v-model="billingSettings.company_name"
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
                v-model="billingSettings.company_email"
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
              v-model="billingSettings.company_address"
              rows="3"
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            />
          </div>
        </form>
      </div>

      <!-- Invoice Settings -->
      <div class="bg-white border border-gray-200 rounded-lg p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-6">Invoice Settings</h3>
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
          <div>
            <label for="invoice_prefix" class="block text-sm font-medium text-gray-700">
              Invoice Number Prefix
            </label>
            <input
              id="invoice_prefix"
              v-model="billingSettings.invoice_prefix"
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
              v-model.number="billingSettings.default_payment_terms"
              type="number"
              min="1"
              max="365"
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            />
          </div>
        </div>
      </div>

      <!-- Tax Settings -->
      <div class="bg-white border border-gray-200 rounded-lg p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-6">Tax Settings</h3>
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
          <div>
            <label for="default_tax_rate" class="block text-sm font-medium text-gray-700">
              Default Tax Rate (%)
            </label>
            <input
              id="default_tax_rate"
              v-model.number="billingSettings.default_tax_rate"
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
              v-model="billingSettings.tax_name"
              type="text"
              placeholder="VAT, Sales Tax, etc."
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            />
          </div>
        </div>
      </div>

      <!-- Save Button -->
      <div class="flex justify-end space-x-3">
        <button
          type="button"
          @click="$emit('refresh')"
          class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
        >
          <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
          </svg>
          Refresh
        </button>
        <button
          type="button"
          @click="saveBillingSettings"
          :disabled="saving"
          class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50"
        >
          <span v-if="saving" class="mr-2">
            <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-white"></div>
          </span>
          {{ saving ? 'Saving...' : 'Save Settings' }}
        </button>
      </div>
      <!-- Billing Rates -->
      <div class="bg-white border border-gray-200 rounded-lg p-6">
        <div class="flex items-center justify-between mb-6">
          <h3 class="text-lg font-medium text-gray-900">Billing Rates</h3>
          <button
            type="button"
            class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-indigo-700 bg-indigo-100 hover:bg-indigo-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
          >
            Add New Rate
          </button>
        </div>
        
        <div v-if="billingRates && billingRates.length > 0" class="space-y-4">
          <div 
            v-for="rate in billingRates" 
            :key="rate.id"
            class="flex items-center justify-between p-4 border rounded-lg bg-gray-50"
          >
            <div>
              <h4 class="text-sm font-medium text-gray-900">{{ rate.name }}</h4>
              <p class="text-xs text-gray-500">{{ rate.description }}</p>
              <div class="text-xs text-gray-500 mt-1">
                <span v-if="rate.account">Account: {{ rate.account.name }}</span>
                <span v-else-if="rate.user">User: {{ rate.user.name }}</span>
                <span v-else>Global Rate</span>
              </div>
            </div>
            <div class="text-right">
              <div class="text-lg font-semibold text-gray-900">${{ rate.rate }}</div>
              <div class="text-xs text-gray-500">per hour</div>
              <div class="mt-1">
                <span :class="[
                  'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                  rate.is_active 
                    ? 'bg-green-100 text-green-800' 
                    : 'bg-gray-100 text-gray-800'
                ]">
                  {{ rate.is_active ? 'Active' : 'Inactive' }}
                </span>
              </div>
            </div>
          </div>
        </div>
        
        <div v-else class="text-sm text-gray-500 text-center py-4">
          No billing rates configured. 
          <button class="text-indigo-600 hover:text-indigo-500 ml-1">
            Create your first billing rate
          </button>
        </div>
      </div>

      <!-- Addon Templates -->
      <div class="bg-white border border-gray-200 rounded-lg p-6">
        <div class="flex items-center justify-between mb-6">
          <h3 class="text-lg font-medium text-gray-900">Addon Templates</h3>
          <button
            type="button"
            class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-indigo-700 bg-indigo-100 hover:bg-indigo-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
          >
            Add New Template
          </button>
        </div>
        
        <!-- Addon Categories -->
        <div v-if="addonCategories" class="mb-6">
          <div class="text-sm font-medium text-gray-700 mb-3">Categories:</div>
          <div class="flex flex-wrap gap-2">
            <span 
              v-for="(label, key) in addonCategories" 
              :key="key"
              class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800"
            >
              {{ label }}
            </span>
          </div>
        </div>
        
        <div v-if="addonTemplates && addonTemplates.length > 0" class="space-y-4">
          <div 
            v-for="template in addonTemplates" 
            :key="template.id"
            class="flex items-center justify-between p-4 border rounded-lg bg-gray-50"
          >
            <div class="flex-1">
              <div class="flex items-center mb-1">
                <h4 class="text-sm font-medium text-gray-900">{{ template.name }}</h4>
                <span class="ml-2 inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                  {{ getCategoryLabel(template.category) }}
                </span>
              </div>
              <p class="text-xs text-gray-500">{{ template.description }}</p>
              <div class="text-xs text-gray-500 mt-1 space-x-4">
                <span v-if="template.sku">SKU: {{ template.sku }}</span>
                <span>Qty: {{ template.default_quantity }}</span>
                <span v-if="template.is_billable" class="text-green-600">Billable</span>
                <span v-if="template.is_taxable" class="text-blue-600">Taxable</span>
              </div>
            </div>
            <div class="text-right">
              <div class="text-lg font-semibold text-gray-900">${{ template.default_unit_price }}</div>
              <div class="text-xs text-gray-500">per unit</div>
              <div class="mt-1">
                <span :class="[
                  'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                  template.is_active 
                    ? 'bg-green-100 text-green-800' 
                    : 'bg-gray-100 text-gray-800'
                ]">
                  {{ template.is_active ? 'Active' : 'Inactive' }}
                </span>
              </div>
            </div>
          </div>
        </div>
        
        <div v-else class="text-sm text-gray-500 text-center py-4">
          No addon templates configured. 
          <button class="text-indigo-600 hover:text-indigo-500 ml-1">
            Create your first addon template
          </button>
        </div>
      </div>

      <!-- Billing Configuration Summary -->
      <div class="bg-white border border-gray-200 rounded-lg p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-6">Configuration Summary</h3>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
          <div class="text-center">
            <div class="text-2xl font-bold text-gray-900">{{ billingRates?.length || 0 }}</div>
            <div class="text-sm text-gray-500">Billing Rates</div>
          </div>
          <div class="text-center">
            <div class="text-2xl font-bold text-green-600">{{ activeAddonTemplates }}</div>
            <div class="text-sm text-gray-500">Active Addons</div>
          </div>
          <div class="text-center">
            <div class="text-2xl font-bold text-blue-600">{{ Object.keys(addonCategories || {}).length }}</div>
            <div class="text-sm text-gray-500">Addon Categories</div>
          </div>
        </div>
      </div>

      <!-- Refresh Button -->
      <div class="flex justify-end">
        <button
          type="button"
          @click="$emit('refresh')"
          class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
        >
          <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
          </svg>
          Refresh Configuration
        </button>
      </div>
    </template>
  </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue'

const props = defineProps({
  config: {
    type: Object,
    default: () => ({})
  },
  loading: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['refresh'])

// Reactive billing settings with defaults
const billingSettings = ref({
  company_name: '',
  company_email: '',
  company_address: '',
  invoice_prefix: 'INV-',
  default_payment_terms: 30,
  default_tax_rate: 0,
  tax_name: 'Tax',
  ...props.config.billing_settings
})

// State
const saving = ref(false)

const billingRates = computed(() => props.config.billing_rates || [])
const addonTemplates = computed(() => props.config.addon_templates || [])
const addonCategories = computed(() => props.config.addon_categories || {})

const activeAddonTemplates = computed(() => {
  return addonTemplates.value.filter(template => template.is_active).length
})

const getCategoryLabel = (categoryKey) => {
  return addonCategories.value[categoryKey] || categoryKey
}

// Methods
const saveBillingSettings = async () => {
  saving.value = true
  try {
    const response = await fetch('/api/settings/billing-settings', {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
      },
      body: JSON.stringify(billingSettings.value)
    })
    
    if (!response.ok) {
      throw new Error('Failed to save billing settings')
    }
    
    // Emit refresh to parent component
    emit('refresh')
    
    // Show success feedback (you could add a toast notification here)
    console.log('Billing settings saved successfully')
    
  } catch (error) {
    console.error('Error saving billing settings:', error)
    // You could add error notification here
  } finally {
    saving.value = false
  }
}

// Watch for config changes and update billing settings
watch(() => props.config.billing_settings, (newSettings) => {
  if (newSettings) {
    billingSettings.value = {
      company_name: '',
      company_email: '',
      company_address: '',
      invoice_prefix: 'INV-',
      default_payment_terms: 30,
      default_tax_rate: 0,
      tax_name: 'Tax',
      ...newSettings
    }
  }
}, { deep: true, immediate: true })
</script>