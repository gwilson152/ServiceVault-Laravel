<template>
  <div class="space-y-8">
    <!-- Billing Configuration Header -->
    <div>
      <h2 class="text-2xl font-semibold text-gray-900">Billing & Addons</h2>
      <p class="text-gray-600 mt-2">Manage billing rates, addon templates, and pricing configuration.</p>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex justify-center py-8">
      <svg class="animate-spin h-8 w-8 text-indigo-600" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
      </svg>
    </div>

    <template v-else>
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
import { computed } from 'vue'

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

defineEmits(['refresh'])

const billingRates = computed(() => props.config.billing_rates || [])
const addonTemplates = computed(() => props.config.addon_templates || [])
const addonCategories = computed(() => props.config.addon_categories || {})

const activeAddonTemplates = computed(() => {
  return addonTemplates.value.filter(template => template.is_active).length
})

const getCategoryLabel = (categoryKey) => {
  return addonCategories.value[categoryKey] || categoryKey
}
</script>