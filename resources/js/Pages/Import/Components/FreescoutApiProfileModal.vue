<template>
  <StackedDialog :show="show" @close="$emit('close')">
    <template #title>
      {{ profile ? 'Edit API Profile' : 'Add FreeScout API Profile' }}
    </template>

    <template #content>
      <form @submit.prevent="handleSubmit">
        <!-- Profile Name -->
        <div class="mb-6">
          <label for="profile-name" class="block text-sm font-medium text-gray-700 mb-2">
            Profile Name
          </label>
          <input
            id="profile-name"
            v-model="form.name"
            type="text"
            placeholder="e.g., Production FreeScout"
            class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
            required
          />
          <p class="mt-1 text-sm text-gray-500">
            A descriptive name to identify this FreeScout instance.
          </p>
        </div>

        <!-- Instance URL -->
        <div class="mb-6">
          <label for="instance-url" class="block text-sm font-medium text-gray-700 mb-2">
            FreeScout Instance URL
          </label>
          <input
            id="instance-url"
            v-model="form.instance_url"
            type="url"
            placeholder="https://support.yourcompany.com"
            class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
            required
          />
          <p class="mt-1 text-sm text-gray-500">
            The full URL to your FreeScout installation (without trailing slash). 
            Example: <code class="text-xs bg-gray-100 px-1 rounded">https://support.yourcompany.com</code>
          </p>
          <div v-if="form.instance_url && !isValidUrl" class="mt-1 text-sm text-red-600">
            Please enter a valid URL starting with http:// or https://
          </div>
        </div>

        <!-- API Key -->
        <div class="mb-6">
          <label for="api-key" class="block text-sm font-medium text-gray-700 mb-2">
            API Key
          </label>
          <div class="relative">
            <input
              id="api-key"
              v-model="form.api_key"
              :type="showApiKey ? 'text' : 'password'"
              placeholder="Enter your FreeScout API key"
              class="mt-1 block w-full px-3 py-2 pr-10 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
              :class="{ 'font-mono text-sm': showApiKey }"
              required
            />
            <button
              type="button"
              @click="showApiKey = !showApiKey"
              class="absolute inset-y-0 right-0 pr-3 flex items-center"
            >
              <EyeIcon v-if="!showApiKey" class="h-5 w-5 text-gray-400 hover:text-gray-500" />
              <EyeSlashIcon v-else class="h-5 w-5 text-gray-400 hover:text-gray-500" />
            </button>
          </div>
          <div class="mt-2 text-sm text-gray-500">
            <p class="mb-1">Find your API key in FreeScout:</p>
            <ol class="list-decimal list-inside space-y-1 ml-2 text-xs">
              <li>Go to <strong>Manage → API</strong></li>
              <li>Click <strong>"Generate API Key"</strong> if you don't have one</li>
              <li>Copy the generated key and paste it here</li>
            </ol>
          </div>
        </div>

        <!-- Connection Test Section -->
        <div class="mb-6 p-4 bg-gray-50 rounded-lg">
          <div class="flex items-center justify-between mb-3">
            <h4 class="text-sm font-medium text-gray-900">Connection Test</h4>
            <button
              type="button"
              @click="testConnection"
              :disabled="!canTestConnection || testing"
              class="inline-flex items-center px-3 py-1 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <span v-if="testing" class="flex items-center">
                <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-gray-500" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Testing...
              </span>
              <span v-else>Test Connection</span>
            </button>
          </div>

          <!-- Connection Status -->
          <div v-if="connectionTest.status" class="flex items-start space-x-3">
            <div class="flex-shrink-0 mt-0.5">
              <CheckCircleIcon 
                v-if="connectionTest.status === 'success'" 
                class="h-5 w-5 text-green-500" 
              />
              <XCircleIcon 
                v-else-if="connectionTest.status === 'error'" 
                class="h-5 w-5 text-red-500" 
              />
              <ExclamationTriangleIcon 
                v-else 
                class="h-5 w-5 text-yellow-500" 
              />
            </div>
            <div class="flex-1">
              <p 
                :class="[
                  'text-sm font-medium',
                  connectionTest.status === 'success' ? 'text-green-800' :
                  connectionTest.status === 'error' ? 'text-red-800' : 'text-yellow-800'
                ]"
              >
                {{ connectionTest.message }}
              </p>
              <div v-if="connectionTest.details" class="mt-2">
                <!-- Success/Warning Details -->
                <div v-if="connectionTest.details.api_version" class="text-xs text-gray-600">
                  <div><strong>API Version:</strong> {{ connectionTest.details.api_version }}</div>
                  <div><strong>FreeScout Version:</strong> {{ connectionTest.details.app_version }}</div>
                  <div>
                    <strong>Response Time:</strong> 
                    <span :class="{ 'text-red-600': connectionTest.details.response_time > 1000, 'text-yellow-600': connectionTest.details.response_time > 500 }">
                      {{ connectionTest.details.response_time }}ms
                    </span>
                  </div>
                </div>
                
                <!-- Error Details -->
                <div v-if="connectionTest.details.error_code" class="text-xs text-red-700 bg-red-50 p-2 rounded mt-2">
                  <div><strong>Error Code:</strong> {{ connectionTest.details.error_code }}</div>
                  <div class="mt-1">{{ connectionTest.details.error_detail }}</div>
                </div>
                
                <!-- Data Stats -->
                <div v-if="connectionTest.details.stats" class="mt-2 grid grid-cols-3 gap-2 text-center">
                  <div class="bg-white rounded px-2 py-1">
                    <div class="text-xs font-medium text-gray-900">{{ connectionTest.details.stats.conversations }}</div>
                    <div class="text-xs text-gray-500">Conversations</div>
                  </div>
                  <div class="bg-white rounded px-2 py-1">
                    <div class="text-xs font-medium text-gray-900">{{ connectionTest.details.stats.customers }}</div>
                    <div class="text-xs text-gray-500">Customers</div>
                  </div>
                  <div class="bg-white rounded px-2 py-1">
                    <div class="text-xs font-medium text-gray-900">{{ connectionTest.details.stats.mailboxes }}</div>
                    <div class="text-xs text-gray-500">Mailboxes</div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <p v-if="!connectionTest.status" class="text-sm text-gray-500">
            Test the connection to verify your API key and instance URL before saving.
          </p>
        </div>

        <!-- Advanced Options -->
        <div class="mb-6">
          <button
            type="button"
            @click="showAdvanced = !showAdvanced"
            class="flex items-center text-sm font-medium text-gray-700 hover:text-gray-900"
          >
            <ChevronRightIcon 
              :class="['w-4 h-4 mr-1 transition-transform', { 'rotate-90': showAdvanced }]"
            />
            Advanced Options
          </button>

          <div v-if="showAdvanced" class="mt-4 space-y-4 pl-5 border-l-2 border-gray-200">
            <!-- Request Timeout -->
            <div>
              <label for="timeout" class="block text-sm font-medium text-gray-700 mb-1">
                Request Timeout (seconds)
              </label>
              <input
                id="timeout"
                v-model.number="form.timeout"
                type="number"
                min="5"
                max="300"
                class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
              />
              <p class="mt-1 text-xs text-gray-500">Default: 30 seconds</p>
            </div>

            <!-- Rate Limiting -->
            <div>
              <label for="rate-limit" class="block text-sm font-medium text-gray-700 mb-1">
                Requests per Minute
              </label>
              <input
                id="rate-limit"
                v-model.number="form.rate_limit"
                type="number"
                min="1"
                max="300"
                class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
              />
              <p class="mt-1 text-xs text-gray-500">Default: 60 requests per minute</p>
            </div>

            <!-- Verify SSL -->
            <div class="flex items-center">
              <input
                id="verify-ssl"
                v-model="form.verify_ssl"
                type="checkbox"
                class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
              />
              <label for="verify-ssl" class="ml-2 text-sm text-gray-700">
                Verify SSL certificate
              </label>
            </div>
            <p class="ml-6 text-xs text-gray-500">Disable only for development/testing environments with self-signed certificates</p>

            <!-- Quick Presets -->
            <div class="mt-4 pt-4 border-t border-gray-200">
              <label class="block text-sm font-medium text-gray-700 mb-2">Quick Configuration Presets</label>
              <div class="grid grid-cols-2 gap-2">
                <button
                  type="button"
                  @click="applyPreset('development')"
                  class="px-3 py-1 text-xs border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                >
                  Development
                </button>
                <button
                  type="button"
                  @click="applyPreset('production')"
                  class="px-3 py-1 text-xs border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                >
                  Production
                </button>
                <button
                  type="button"
                  @click="applyPreset('high_volume')"
                  class="px-3 py-1 text-xs border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                >
                  High Volume
                </button>
                <button
                  type="button"
                  @click="applyPreset('slow_server')"
                  class="px-3 py-1 text-xs border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                >
                  Slow Server
                </button>
              </div>
            </div>
          </div>
        </div>
      </form>
    </template>

    <template #actions>
      <div class="flex justify-end space-x-3">
        <button
          type="button"
          @click="$emit('close')"
          class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
        >
          Cancel
        </button>
        <button
          type="button"
          @click="handleSubmit"
          :disabled="!canSave"
          class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          {{ profile ? 'Update Profile' : 'Create Profile' }}
        </button>
      </div>
    </template>
  </StackedDialog>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import StackedDialog from '@/Components/StackedDialog.vue'
import {
  EyeIcon,
  EyeSlashIcon,
  CheckCircleIcon,
  XCircleIcon,
  ExclamationTriangleIcon,
  ChevronRightIcon
} from '@heroicons/vue/24/outline'

// Props
const props = defineProps({
  show: Boolean,
  profile: {
    type: Object,
    default: null
  }
})


// Emits
const emit = defineEmits(['close', 'save'])

// Reactive data
const show = ref(true)
const showApiKey = ref(false)
const showAdvanced = ref(false)
const testing = ref(false)

const form = ref({
  name: '',
  instance_url: '',
  api_key: '',
  timeout: 30,
  rate_limit: 60,
  verify_ssl: true
})

const connectionTest = ref({
  status: null,
  message: '',
  details: null
})

// Mock data for connection testing with realistic scenarios
const mockConnectionResponses = [
  {
    status: 'success',
    message: 'Connection successful! API is working correctly.',
    details: {
      api_version: 'v1.0',
      app_version: '1.8.92',
      response_time: 245,
      stats: {
        conversations: '2,847',
        customers: '1,523',
        mailboxes: '12'
      }
    }
  },
  {
    status: 'success',
    message: 'Connected successfully with high data volume.',
    details: {
      api_version: 'v1.0',
      app_version: '1.8.89',
      response_time: 156,
      stats: {
        conversations: '15,234',
        customers: '8,456',
        mailboxes: '25'
      }
    }
  },
  {
    status: 'success',
    message: 'Connection successful. Small instance detected.',
    details: {
      api_version: 'v1.0',
      app_version: '1.8.95',
      response_time: 98,
      stats: {
        conversations: '342',
        customers: '156',
        mailboxes: '4'
      }
    }
  },
  {
    status: 'error',
    message: 'Authentication failed. Invalid API key provided.',
    details: {
      error_code: 401,
      error_detail: 'The provided API key is invalid or has been revoked. Please generate a new key from your FreeScout admin panel.'
    }
  },
  {
    status: 'error',
    message: 'Connection failed. Cannot reach FreeScout instance.',
    details: {
      error_code: 'CONNECTION_TIMEOUT',
      error_detail: 'The FreeScout instance at this URL is not responding. Please check the URL and ensure your FreeScout installation is accessible.'
    }
  },
  {
    status: 'error',
    message: 'API endpoint not found. Please check your FreeScout version.',
    details: {
      error_code: 404,
      error_detail: 'The API endpoint was not found. This may indicate an older FreeScout version that doesn\'t support API access, or an incorrect URL.'
    }
  },
  {
    status: 'warning',
    message: 'Connected, but API response is slow. Consider checking your server performance.',
    details: {
      api_version: 'v1.0',
      app_version: '1.7.15',
      response_time: 2150,
      stats: {
        conversations: '5,432',
        customers: '2,156',
        mailboxes: '8'
      }
    }
  },
  {
    status: 'warning',
    message: 'Connected to older FreeScout version. Some features may be limited.',
    details: {
      api_version: 'v1.0',
      app_version: '1.6.45',
      response_time: 320,
      stats: {
        conversations: '1,890',
        customers: '743',
        mailboxes: '6'
      }
    }
  }
]

// Computed
const canTestConnection = computed(() => {
  return form.value.instance_url && form.value.api_key
})

const isValidUrl = computed(() => {
  const url = form.value.instance_url
  return !url || url.startsWith('https://') || url.startsWith('http://')
})

const canSave = computed(() => {
  return form.value.name && 
         form.value.instance_url && 
         form.value.api_key &&
         isValidUrl.value &&
         (!connectionTest.value.status || connectionTest.value.status !== 'error')
})

// Initialize form with profile data if editing
if (props.profile) {
  form.value = {
    name: props.profile.name || '',
    instance_url: props.profile.instance_url || '',
    api_key: '****-****-****-****', // Masked for editing
    timeout: props.profile.timeout || 30,
    rate_limit: props.profile.rate_limit || 60,
    verify_ssl: props.profile.verify_ssl !== undefined ? props.profile.verify_ssl : true
  }
  
  // Set connection test status for existing profiles
  if (props.profile.status === 'connected') {
    connectionTest.value = {
      status: 'success',
      message: 'Previously tested successfully',
      details: null
    }
  } else if (props.profile.status === 'error') {
    connectionTest.value = {
      status: 'error',
      message: 'Previous test failed',
      details: null
    }
  }
}

// Methods
const testConnection = async () => {
  if (!canTestConnection.value) return
  
  testing.value = true
  connectionTest.value = { status: null, message: '', details: null }
  
  // Simulate realistic API call delay
  const delay = Math.random() * 2000 + 500 // 500-2500ms
  await new Promise(resolve => setTimeout(resolve, delay))
  
  // Intelligent mock response based on input patterns
  let mockIndex = 0
  const url = form.value.instance_url.toLowerCase()
  const apiKey = form.value.api_key
  
  if (!url.startsWith('https://') && !url.startsWith('http://')) {
    // Invalid URL format
    mockIndex = 4 // Connection timeout
  } else if (apiKey.length < 10 || apiKey === 'test' || apiKey === 'demo') {
    // Invalid API key
    mockIndex = 3 // Auth failed
  } else if (url.includes('localhost') || url.includes('127.0.0.1') || url.includes('demo')) {
    // Local or demo instance - likely successful
    mockIndex = Math.random() > 0.8 ? 0 : 2 // Mostly success, sometimes small instance
  } else if (url.includes('test') || url.includes('staging')) {
    // Test/staging environment - might have issues
    mockIndex = Math.random() > 0.5 ? Math.floor(Math.random() * 3) : 6 // Success, small, or slow response
  } else {
    // Production-like URL - mixed results
    const rand = Math.random()
    if (rand > 0.6) {
      mockIndex = Math.floor(Math.random() * 3) // Success variants
    } else if (rand > 0.3) {
      mockIndex = Math.floor(Math.random() * 2) + 6 // Warning variants  
    } else {
      mockIndex = Math.floor(Math.random() * 3) + 3 // Error variants
    }
  }
  
  connectionTest.value = { ...mockConnectionResponses[mockIndex] }
  testing.value = false
}

const applyPreset = (presetType) => {
  const presets = {
    development: {
      timeout: 60,
      rate_limit: 120,
      verify_ssl: false
    },
    production: {
      timeout: 30,
      rate_limit: 60,
      verify_ssl: true
    },
    high_volume: {
      timeout: 15,
      rate_limit: 180,
      verify_ssl: true
    },
    slow_server: {
      timeout: 120,
      rate_limit: 30,
      verify_ssl: true
    }
  }
  
  if (presets[presetType]) {
    Object.assign(form.value, presets[presetType])
    // Clear connection test when settings change
    connectionTest.value = { status: null, message: '', details: null }
  }
}

const handleSubmit = () => {
  if (!canSave.value) return
  
  const profileData = {
    name: form.value.name,
    instance_url: form.value.instance_url,
    api_key: form.value.api_key === '****-****-****-****' 
      ? props.profile?.api_key  // Keep existing key if masked
      : form.value.api_key,
    api_key_masked: form.value.api_key.length > 4 
      ? `****-****-****-${form.value.api_key.slice(-4)}`
      : '****-****-****-****',
    timeout: form.value.timeout,
    rate_limit: form.value.rate_limit,
    verify_ssl: form.value.verify_ssl,
    status: connectionTest.value.status === 'success' ? 'connected' : 'pending'
  }
  
  emit('save', profileData)
}

// Clear connection test when form changes
watch([() => form.value.instance_url, () => form.value.api_key], () => {
  if (connectionTest.value.status && 
      !(props.profile && form.value.api_key === '****-****-****-****')) {
    connectionTest.value = { status: null, message: '', details: null }
  }
})
</script>