<template>
  <div class="space-y-8">
    <!-- System Configuration Header -->
    <div>
      <h2 class="text-2xl font-semibold text-gray-900">System Configuration</h2>
      <p class="text-gray-600 mt-2">Configure core system settings including company information, localization, and system limits.</p>
    </div>

    <!-- Company Information -->
    <div class="bg-white border border-gray-200 rounded-lg p-6">
      <h3 class="text-lg font-medium text-gray-900 mb-6">Company Information</h3>
      
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
          <label class="block text-sm font-medium text-gray-700">Company Name</label>
          <input 
            type="text" 
            v-model="form.company_name"
            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
          />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700">Company Email</label>
          <input 
            type="email" 
            v-model="form.company_email"
            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
          />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700">Website</label>
          <input 
            type="url" 
            v-model="form.company_website"
            placeholder="https://www.example.com"
            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
          />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700">Phone</label>
          <input 
            type="tel" 
            v-model="form.company_phone"
            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
          />
        </div>

        <div class="md:col-span-2">
          <label class="block text-sm font-medium text-gray-700">Address</label>
          <textarea
            v-model="form.company_address"
            rows="3"
            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
          ></textarea>
        </div>
      </div>
    </div>

    <!-- Localization Settings -->
    <div class="bg-white border border-gray-200 rounded-lg p-6">
      <h3 class="text-lg font-medium text-gray-900 mb-6">Localization</h3>
      
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div>
          <label class="block text-sm font-medium text-gray-700">Timezone</label>
          <select 
            v-model="form.timezone"
            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
          >
            <option value="UTC">UTC</option>
            <option value="America/New_York">Eastern Time</option>
            <option value="America/Chicago">Central Time</option>
            <option value="America/Denver">Mountain Time</option>
            <option value="America/Los_Angeles">Pacific Time</option>
            <option value="Europe/London">London</option>
            <option value="Europe/Paris">Paris</option>
            <option value="Europe/Berlin">Berlin</option>
            <option value="Asia/Tokyo">Tokyo</option>
            <option value="Asia/Shanghai">Shanghai</option>
            <option value="Australia/Sydney">Sydney</option>
          </select>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700">Currency</label>
          <select 
            v-model="form.currency"
            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
          >
            <option value="USD">USD - US Dollar</option>
            <option value="EUR">EUR - Euro</option>
            <option value="GBP">GBP - British Pound</option>
            <option value="CAD">CAD - Canadian Dollar</option>
            <option value="AUD">AUD - Australian Dollar</option>
            <option value="JPY">JPY - Japanese Yen</option>
            <option value="CHF">CHF - Swiss Franc</option>
          </select>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700">Language</label>
          <select 
            v-model="form.language"
            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
          >
            <option value="en">English</option>
            <option value="es">Spanish</option>
            <option value="fr">French</option>
            <option value="de">German</option>
            <option value="ja">Japanese</option>
            <option value="zh">Chinese</option>
          </select>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700">Date Format</label>
          <select 
            v-model="form.date_format"
            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
          >
            <option value="Y-m-d">2024-12-31 (ISO)</option>
            <option value="m/d/Y">12/31/2024 (US)</option>
            <option value="d/m/Y">31/12/2024 (EU)</option>
            <option value="d-m-Y">31-12-2024</option>
            <option value="j M Y">31 Dec 2024</option>
          </select>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700">Time Format</label>
          <select 
            v-model="form.time_format"
            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
          >
            <option value="H:i">24 Hour (14:30)</option>
            <option value="g:i A">12 Hour (2:30 PM)</option>
          </select>
        </div>
      </div>
    </div>

    <!-- System Limits -->
    <div class="bg-white border border-gray-200 rounded-lg p-6">
      <h3 class="text-lg font-medium text-gray-900 mb-6">System Limits</h3>
      
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div>
          <label class="block text-sm font-medium text-gray-700">Maximum Users</label>
          <input 
            type="number" 
            v-model.number="form.max_users"
            min="1" 
            max="10000"
            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
          />
          <p class="mt-1 text-xs text-gray-500">Maximum number of users allowed in the system</p>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700">Timer Sync Interval</label>
          <div class="relative">
            <input 
              type="number" 
              v-model.number="form.timer_sync_interval"
              min="1" 
              max="60"
              class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 pr-12"
            />
            <div class="absolute inset-y-0 right-0 flex items-center pr-3">
              <span class="text-gray-500 text-sm">sec</span>
            </div>
          </div>
          <p class="mt-1 text-xs text-gray-500">How often timers sync across devices</p>
        </div>

        <div class="md:col-span-3">
          <label class="block text-sm font-medium text-gray-700">Permission Cache TTL</label>
          <div class="relative max-w-xs">
            <input 
              type="number" 
              v-model.number="form.permission_cache_ttl"
              min="60" 
              max="3600"
              class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 pr-12"
            />
            <div class="absolute inset-y-0 right-0 flex items-center pr-3">
              <span class="text-gray-500 text-sm">sec</span>
            </div>
          </div>
          <p class="mt-1 text-xs text-gray-500">How long permission data is cached (60-3600 seconds)</p>
        </div>
      </div>
    </div>

    <!-- Advanced Features -->
    <div class="bg-white border border-gray-200 rounded-lg p-6">
      <h3 class="text-lg font-medium text-gray-900 mb-6">Advanced Features</h3>
      
      <div class="space-y-4">
        <div class="flex items-center">
          <input 
            type="checkbox" 
            id="enable_real_time"
            v-model="form.enable_real_time"
            class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
          />
          <label for="enable_real_time" class="ml-3">
            <span class="text-sm font-medium text-gray-700">Enable Real-time Features</span>
            <p class="text-xs text-gray-500">Real-time updates via WebSocket connections</p>
          </label>
        </div>

        <div class="flex items-center">
          <input 
            type="checkbox" 
            id="enable_notifications"
            v-model="form.enable_notifications"
            class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
          />
          <label for="enable_notifications" class="ml-3">
            <span class="text-sm font-medium text-gray-700">Enable Notifications</span>
            <p class="text-xs text-gray-500">Email and browser notifications for system events</p>
          </label>
        </div>
      </div>
    </div>

    <!-- Actions -->
    <div class="flex justify-end">
      <button
        type="button"
        @click="saveSettings"
        :disabled="loading"
        :class="[
          'inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md shadow-sm',
          loading
            ? 'bg-gray-300 text-gray-500 cursor-not-allowed'
            : 'bg-indigo-600 text-white hover:bg-indigo-700 focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500'
        ]"
      >
        <span v-if="loading" class="mr-2">
          <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
        </span>
        {{ loading ? 'Saving...' : 'Save System Settings' }}
      </button>
    </div>
  </div>
</template>

<script setup>
import { reactive, watch } from 'vue'

const props = defineProps({
  settings: {
    type: Object,
    default: () => ({})
  },
  loading: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['update'])

// Form data with defaults
const form = reactive({
  // Company Information
  company_name: '',
  company_email: '',
  company_website: '',
  company_address: '',
  company_phone: '',
  
  // System Configuration
  timezone: 'UTC',
  currency: 'USD',
  date_format: 'Y-m-d',
  time_format: 'H:i',
  language: 'en',
  
  // Features & Limits
  enable_real_time: true,
  enable_notifications: true,
  timer_sync_interval: 5,
  permission_cache_ttl: 300,
  max_users: 250,
})

// Watch for settings prop changes
watch(() => props.settings, (newSettings) => {
  if (newSettings && Object.keys(newSettings).length > 0) {
    Object.assign(form, newSettings)
  }
}, { immediate: true, deep: true })

// Save settings
const saveSettings = () => {
  emit('update', { ...form })
}
</script>