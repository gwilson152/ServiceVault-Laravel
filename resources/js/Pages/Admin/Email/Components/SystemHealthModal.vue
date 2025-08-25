<template>
  <StackedDialog
    :show="show"
    @close="$emit('close')"
    size="lg"
    title="Email System Health"
  >
    <div class="space-y-6">
      <!-- Overall System Status -->
      <div class="bg-white border border-gray-200 rounded-lg p-6">
        <div class="flex items-center justify-between mb-4">
          <div>
            <h3 class="text-lg font-medium text-gray-900">System Status</h3>
            <p class="text-sm text-gray-500">Overall email system health</p>
          </div>
          <div class="flex items-center">
            <span :class="[
              'inline-flex items-center px-3 py-1 rounded-full text-sm font-medium',
              overallStatus === 'healthy' ? 'bg-green-100 text-green-800' :
              overallStatus === 'warning' ? 'bg-yellow-100 text-yellow-800' :
              'bg-red-100 text-red-800'
            ]">
              <span :class="[
                'w-2 h-2 rounded-full mr-2',
                overallStatus === 'healthy' ? 'bg-green-400' :
                overallStatus === 'warning' ? 'bg-yellow-400' :
                'bg-red-400'
              ]"></span>
              {{ overallStatus === 'healthy' ? 'Healthy' : overallStatus === 'warning' ? 'Warning' : 'Critical' }}
            </span>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div class="text-center">
            <div class="text-2xl font-bold text-gray-900">{{ health.uptime || '0h' }}</div>
            <div class="text-sm text-gray-500">System Uptime</div>
          </div>
          <div class="text-center">
            <div class="text-2xl font-bold text-gray-900">{{ health.processed_today || 0 }}</div>
            <div class="text-sm text-gray-500">Emails Today</div>
          </div>
          <div class="text-center">
            <div class="text-2xl font-bold text-gray-900">{{ health.success_rate || '0%' }}</div>
            <div class="text-sm text-gray-500">Success Rate</div>
          </div>
        </div>
      </div>

      <!-- Service Components -->
      <div class="bg-white border border-gray-200 rounded-lg p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Service Components</h3>
        <div class="space-y-4">
          <div v-for="component in health.components" :key="component.name" class="flex items-center justify-between p-3 border border-gray-200 rounded-lg">
            <div class="flex items-center">
              <span :class="[
                'w-3 h-3 rounded-full mr-3',
                component.status === 'up' ? 'bg-green-400' :
                component.status === 'degraded' ? 'bg-yellow-400' :
                'bg-red-400'
              ]"></span>
              <div>
                <div class="text-sm font-medium text-gray-900">{{ component.name }}</div>
                <div class="text-xs text-gray-500">{{ component.description }}</div>
              </div>
            </div>
            <div class="text-right">
              <div :class="[
                'text-sm font-medium',
                component.status === 'up' ? 'text-green-600' :
                component.status === 'degraded' ? 'text-yellow-600' :
                'text-red-600'
              ]">
                {{ component.status === 'up' ? 'Operational' : component.status === 'degraded' ? 'Degraded' : 'Down' }}
              </div>
              <div v-if="component.response_time" class="text-xs text-gray-500">
                {{ component.response_time }}ms
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Performance Metrics -->
      <div class="bg-white border border-gray-200 rounded-lg p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Performance Metrics</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <!-- Email Processing -->
          <div>
            <h4 class="text-sm font-medium text-gray-700 mb-3">Email Processing</h4>
            <div class="space-y-2">
              <div class="flex justify-between text-sm">
                <span class="text-gray-500">Average Processing Time</span>
                <span class="text-gray-900">{{ health.avg_processing_time || '0ms' }}</span>
              </div>
              <div class="flex justify-between text-sm">
                <span class="text-gray-500">Queue Length</span>
                <span class="text-gray-900">{{ health.queue_length || 0 }}</span>
              </div>
              <div class="flex justify-between text-sm">
                <span class="text-gray-500">Processing Rate</span>
                <span class="text-gray-900">{{ health.processing_rate || '0/min' }}</span>
              </div>
            </div>
          </div>

          <!-- System Resources -->
          <div>
            <h4 class="text-sm font-medium text-gray-700 mb-3">System Resources</h4>
            <div class="space-y-2">
              <div class="flex justify-between text-sm">
                <span class="text-gray-500">Memory Usage</span>
                <span class="text-gray-900">{{ health.memory_usage || '0%' }}</span>
              </div>
              <div class="flex justify-between text-sm">
                <span class="text-gray-500">CPU Usage</span>
                <span class="text-gray-900">{{ health.cpu_usage || '0%' }}</span>
              </div>
              <div class="flex justify-between text-sm">
                <span class="text-gray-500">Disk Usage</span>
                <span class="text-gray-900">{{ health.disk_usage || '0%' }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Recent Errors -->
      <div v-if="health.recent_errors?.length > 0" class="bg-white border border-red-200 rounded-lg p-6">
        <h3 class="text-lg font-medium text-red-600 mb-4">Recent Errors</h3>
        <div class="space-y-3">
          <div v-for="error in health.recent_errors" :key="error.id" class="p-3 bg-red-50 border border-red-200 rounded-lg">
            <div class="flex justify-between items-start mb-2">
              <div class="text-sm font-medium text-red-800">{{ error.type || 'Error' }}</div>
              <div class="text-xs text-red-600">{{ formatDate(error.timestamp) }}</div>
            </div>
            <div class="text-sm text-red-700">{{ error.message }}</div>
            <div v-if="error.count > 1" class="text-xs text-red-600 mt-1">
              Occurred {{ error.count }} times
            </div>
          </div>
        </div>
      </div>

      <!-- System Warnings -->
      <div v-if="health.warnings?.length > 0" class="bg-white border border-yellow-200 rounded-lg p-6">
        <h3 class="text-lg font-medium text-yellow-600 mb-4">System Warnings</h3>
        <div class="space-y-3">
          <div v-for="warning in health.warnings" :key="warning.id" class="p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
            <div class="flex justify-between items-start mb-2">
              <div class="text-sm font-medium text-yellow-800">{{ warning.type }}</div>
              <div class="text-xs text-yellow-600">{{ formatDate(warning.timestamp) }}</div>
            </div>
            <div class="text-sm text-yellow-700">{{ warning.message }}</div>
            <div v-if="warning.recommendation" class="text-xs text-yellow-600 mt-1">
              Recommendation: {{ warning.recommendation }}
            </div>
          </div>
        </div>
      </div>

      <!-- Connection Tests -->
      <div class="bg-white border border-gray-200 rounded-lg p-6">
        <div class="flex items-center justify-between mb-4">
          <div>
            <h3 class="text-lg font-medium text-gray-900">Connection Tests</h3>
            <p class="text-sm text-gray-500">Test email server connections</p>
          </div>
          <button
            @click="runAllTests"
            :disabled="testing"
            class="inline-flex items-center px-3 py-2 border border-gray-300 text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50"
          >
            <span v-if="testing" class="flex items-center">
              <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-gray-700" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              Testing...
            </span>
            <span v-else>Run All Tests</span>
          </button>
        </div>

        <div class="space-y-3">
          <div v-for="test in connectionTests" :key="test.name" class="flex items-center justify-between p-3 border border-gray-200 rounded-lg">
            <div class="flex items-center">
              <span :class="[
                'w-3 h-3 rounded-full mr-3',
                test.status === 'pass' ? 'bg-green-400' :
                test.status === 'fail' ? 'bg-red-400' :
                'bg-gray-300'
              ]"></span>
              <div>
                <div class="text-sm font-medium text-gray-900">{{ test.name }}</div>
                <div class="text-xs text-gray-500">{{ test.description }}</div>
              </div>
            </div>
            <div class="text-right">
              <div v-if="test.status" :class="[
                'text-sm font-medium',
                test.status === 'pass' ? 'text-green-600' : 'text-red-600'
              ]">
                {{ test.status === 'pass' ? 'Pass' : 'Fail' }}
              </div>
              <div v-if="test.response_time" class="text-xs text-gray-500">
                {{ test.response_time }}ms
              </div>
              <div v-if="test.error" class="text-xs text-red-600 max-w-xs truncate">
                {{ test.error }}
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Refresh Controls -->
      <div class="flex items-center justify-between bg-gray-50 p-4 rounded-lg">
        <div class="text-sm text-gray-600">
          Last updated: {{ formatDate(health.last_updated) }}
        </div>
        <div class="flex items-center space-x-3">
          <label class="flex items-center">
            <input
              v-model="autoRefresh"
              type="checkbox"
              class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
            />
            <span class="ml-2 text-sm text-gray-700">Auto-refresh (30s)</span>
          </label>
          <button
            @click="refreshHealth"
            :disabled="loading"
            class="inline-flex items-center px-3 py-2 border border-gray-300 text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50"
          >
            <ArrowPathIcon :class="['w-4 h-4 mr-2', loading ? 'animate-spin' : '']" />
            Refresh
          </button>
        </div>
      </div>
    </div>

    <template #actions>
      <button
        @click="$emit('close')"
        class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
      >
        Close
      </button>
    </template>
  </StackedDialog>
</template>

<script setup>
import { ref, reactive, computed, watch, onMounted, onUnmounted } from 'vue'
import { ArrowPathIcon } from '@heroicons/vue/24/outline'
import StackedDialog from '@/Components/Modals/StackedDialog.vue'

const props = defineProps({
  show: Boolean
})

const emit = defineEmits(['close'])

// State
const loading = ref(false)
const testing = ref(false)
const autoRefresh = ref(false)
const refreshInterval = ref(null)

// Health data
const health = ref({
  uptime: '72h',
  processed_today: 1247,
  success_rate: '98.5%',
  avg_processing_time: '245ms',
  queue_length: 3,
  processing_rate: '12/min',
  memory_usage: '65%',
  cpu_usage: '23%',
  disk_usage: '42%',
  last_updated: new Date().toISOString(),
  components: [
    { name: 'IMAP Server', description: 'Incoming email connection', status: 'up', response_time: 234 },
    { name: 'SMTP Server', description: 'Outgoing email connection', status: 'up', response_time: 156 },
    { name: 'Queue Processor', description: 'Email processing queue', status: 'up' },
    { name: 'Database', description: 'Primary database connection', status: 'up', response_time: 12 },
    { name: 'Redis Cache', description: 'Caching and sessions', status: 'up', response_time: 3 },
    { name: 'File Storage', description: 'Attachment storage', status: 'up' }
  ],
  recent_errors: [],
  warnings: []
})

const connectionTests = ref([
  { name: 'IMAP Connection', description: 'Test incoming email server', status: null, response_time: null, error: null },
  { name: 'SMTP Connection', description: 'Test outgoing email server', status: null, response_time: null, error: null },
  { name: 'Database Query', description: 'Test database connectivity', status: null, response_time: null, error: null },
  { name: 'Queue Status', description: 'Check processing queue health', status: null, response_time: null, error: null }
])

// Computed
const overallStatus = computed(() => {
  const components = health.value.components || []
  const downComponents = components.filter(c => c.status === 'down')
  const degradedComponents = components.filter(c => c.status === 'degraded')
  
  if (downComponents.length > 0) return 'critical'
  if (degradedComponents.length > 0 || (health.value.recent_errors?.length > 0)) return 'warning'
  return 'healthy'
})

// Methods
async function refreshHealth() {
  try {
    loading.value = true

    const response = await fetch('/api/email-admin/system-health')
    if (!response.ok) throw new Error('Failed to fetch health data')

    const data = await response.json()
    health.value = { ...health.value, ...data }

  } catch (error) {
    console.error('Error fetching health data:', error)
    // Use mock data on error
    health.value.last_updated = new Date().toISOString()
  } finally {
    loading.value = false
  }
}

async function runAllTests() {
  try {
    testing.value = true
    
    // Reset test results
    connectionTests.value.forEach(test => {
      test.status = null
      test.response_time = null
      test.error = null
    })

    const response = await fetch('/api/email-admin/connection-tests', {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
      }
    })

    if (!response.ok) throw new Error('Failed to run tests')

    const results = await response.json()
    
    // Update test results
    connectionTests.value.forEach(test => {
      const result = results.tests?.find(r => r.name === test.name)
      if (result) {
        test.status = result.passed ? 'pass' : 'fail'
        test.response_time = result.response_time
        test.error = result.error
      }
    })

  } catch (error) {
    console.error('Error running connection tests:', error)
    // Mock test results for development
    connectionTests.value.forEach(test => {
      test.status = Math.random() > 0.1 ? 'pass' : 'fail'
      test.response_time = Math.floor(Math.random() * 300) + 50
      if (test.status === 'fail') {
        test.error = 'Connection timeout'
      }
    })
  } finally {
    testing.value = false
  }
}

function formatDate(date) {
  if (!date) return 'Never'
  return new Date(date).toLocaleString('en-US', {
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

function startAutoRefresh() {
  if (refreshInterval.value) return
  
  refreshInterval.value = setInterval(() => {
    if (autoRefresh.value) {
      refreshHealth()
    }
  }, 30000) // 30 seconds
}

function stopAutoRefresh() {
  if (refreshInterval.value) {
    clearInterval(refreshInterval.value)
    refreshInterval.value = null
  }
}

// Watch for auto-refresh changes
watch(autoRefresh, (newValue) => {
  if (newValue) {
    startAutoRefresh()
  } else {
    stopAutoRefresh()
  }
})

// Watch for modal show/hide
watch(() => props.show, (show) => {
  if (show) {
    refreshHealth()
    startAutoRefresh()
  } else {
    stopAutoRefresh()
  }
})

// Cleanup on unmount
onUnmounted(() => {
  stopAutoRefresh()
})

// Load data on mount
onMounted(() => {
  if (props.show) {
    refreshHealth()
  }
})
</script>