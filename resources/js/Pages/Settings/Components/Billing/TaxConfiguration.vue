<template>
  <div class="space-y-8">
    <!-- Master Tax Configuration -->
    <div class="bg-white shadow-sm sm:rounded-lg border border-gray-200">
      <div class="px-6 py-4 border-b border-gray-200">
        <h3 class="text-lg font-medium text-gray-900 flex items-center">
          <DocumentTextIcon class="w-5 h-5 mr-2 text-indigo-600" />
          Master Tax Configuration
        </h3>
        <p class="text-sm text-gray-600 mt-1">
          System-wide default tax settings that apply to all accounts and invoices unless overridden.
        </p>
      </div>

      <div class="px-6 py-6 space-y-6">
        <!-- Tax System Enable/Disable -->
        <div class="flex items-center justify-between">
          <div class="flex-1">
            <label class="text-sm font-medium text-gray-700">
              Enable Tax System
            </label>
            <p class="text-xs text-gray-500 mt-1">
              When disabled, no taxes will be calculated system-wide
            </p>
          </div>
          <div class="ml-4">
            <button
              @click="toggleTaxSystem"
              :disabled="saving"
              :class="[
                settings.tax_enabled
                  ? 'bg-indigo-600 focus:ring-indigo-500'
                  : 'bg-gray-200 focus:ring-gray-500',
                'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed'
              ]"
            >
              <span
                :class="[
                  settings.tax_enabled ? 'translate-x-5' : 'translate-x-0',
                  'inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out'
                ]"
              />
            </button>
          </div>
        </div>

        <div v-if="settings.tax_enabled" class="space-y-6">
          <!-- Default Tax Rate -->
          <div>
            <label for="default_tax_rate" class="block text-sm font-medium text-gray-700">
              Default Tax Rate (%)
            </label>
            <div class="mt-1 relative rounded-md shadow-sm">
              <input
                id="default_tax_rate"
                v-model.number="settings.default_rate"
                type="number"
                step="0.01"
                min="0"
                max="100"
                class="block w-full pl-3 pr-12 border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                placeholder="0.00"
              />
              <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                <span class="text-gray-500 sm:text-sm">%</span>
              </div>
            </div>
            <p class="text-xs text-gray-500 mt-1">
              Default tax rate applied to all taxable items unless overridden at account or invoice level
            </p>
          </div>

          <!-- Default Tax Application Mode -->
          <div>
            <label for="tax_application_mode" class="block text-sm font-medium text-gray-700">
              Default Tax Application
            </label>
            <select
              id="tax_application_mode"
              v-model="settings.default_application_mode"
              class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md"
            >
              <option value="all_items">All Taxable Items</option>
              <option value="non_service_items">Products Only (No Services)</option>
              <option value="custom">Custom (Per Item)</option>
            </select>
            <p class="text-xs text-gray-500 mt-1">
              <span v-if="settings.default_application_mode === 'all_items'">Tax applies to both time entries (services) and addons (products)</span>
              <span v-else-if="settings.default_application_mode === 'non_service_items'">Tax applies only to addons/products, not time entries/services</span>
              <span v-else>Tax application determined by individual item settings</span>
            </p>
          </div>

        </div>

        <!-- Save Actions -->
        <div class="flex items-center justify-between pt-4 border-t border-gray-200">
          <div class="flex items-center">
            <div v-if="saving" class="flex items-center text-sm text-gray-600">
              <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-indigo-600 mr-2"></div>
              Saving...
            </div>
            <div v-else-if="lastSaved" class="flex items-center text-sm text-green-600">
              <CheckCircleIcon class="w-4 h-4 mr-2" />
              Last saved {{ formatTime(lastSaved) }}
            </div>
            <div v-else class="text-sm text-gray-500">
              Click "Save Changes" to save your settings
            </div>
          </div>
          
          <div class="flex items-center space-x-3">
            <button
              @click="saveTaxSettings"
              :disabled="saving"
              class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <span v-if="saving" class="mr-2">
                <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-white"></div>
              </span>
              {{ saving ? 'Saving...' : 'Save Changes' }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Account-Level Overrides Info -->
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
      <div class="flex">
        <InformationCircleIcon class="w-5 h-5 text-blue-400 mt-0.5 mr-3 flex-shrink-0" />
        <div>
          <h4 class="text-sm font-medium text-blue-900">Tax Settings Hierarchy</h4>
          <div class="mt-2 text-sm text-blue-800">
            <p class="mb-2">Tax settings are applied in the following order (highest to lowest priority):</p>
            <ol class="list-decimal list-inside space-y-1 ml-4">
              <li><strong>Invoice-specific settings</strong> - Override everything for that specific invoice</li>
              <li><strong>Account-specific settings</strong> - Can be configured per account to override system defaults</li>
              <li><strong>System defaults</strong> - The settings configured above apply when no overrides exist</li>
            </ol>
            <p class="mt-3 text-xs">
              Account tax overrides can be configured in individual account settings, and invoice tax overrides are set when creating or editing invoices.
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { DocumentTextIcon, CheckCircleIcon, InformationCircleIcon } from '@heroicons/vue/24/outline'

// Reactive state
const saving = ref(false)
const lastSaved = ref(null)

const settings = reactive({
  tax_enabled: true,
  default_rate: 0,
  default_application_mode: 'all_items'
})

// Load tax settings from API
const loadTaxSettings = async () => {
  try {
    const response = await window.axios.get('/api/settings/tax')
    const data = response.data.data
    
    Object.assign(settings, {
      tax_enabled: data.enabled ?? true,
      default_rate: parseFloat(data.default_rate ?? 0),
      default_application_mode: data.default_application_mode ?? 'all_items'
    })
  } catch (error) {
    console.error('Failed to load tax settings:', error)
  }
}

// Save tax settings to API
const saveTaxSettings = async () => {
  if (saving.value) return
  
  saving.value = true
  try {
    await window.axios.put('/api/settings/tax', {
      enabled: settings.tax_enabled,
      default_rate: settings.default_rate,
      default_application_mode: settings.default_application_mode
    })
    
    lastSaved.value = new Date()
  } catch (error) {
    console.error('Failed to save tax settings:', error)
    // TODO: Show error notification
  } finally {
    saving.value = false
  }
}

// Removed debounced save - now only saves when button is clicked

// Toggle tax system (no auto-save)
const toggleTaxSystem = () => {
  settings.tax_enabled = !settings.tax_enabled
}


// Format time for display
const formatTime = (date) => {
  return new Intl.DateTimeFormat('en-US', {
    hour: 'numeric',
    minute: '2-digit',
    second: '2-digit'
  }).format(date)
}

// Load settings on mount
onMounted(() => {
  loadTaxSettings()
})
</script>