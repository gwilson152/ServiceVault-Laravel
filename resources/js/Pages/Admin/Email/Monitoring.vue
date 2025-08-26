<template>
  <StandardPageLayout
    title="Email Processing Monitor"
    :show-sidebar="true"
    :show-filters="true"
  >
    <template #header-actions>
      <div class="flex items-center space-x-3">
        <button
          @click="refreshData"
          :disabled="isRefreshing"
          class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50"
        >
          <ArrowPathIcon :class="['h-4 w-4 mr-2', isRefreshing ? 'animate-spin' : '']" />
          Refresh
        </button>
        <button
          @click="openSystemHealthModal"
          class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
        >
          <ChartBarIcon class="h-4 w-4 mr-2" />
          System Health
        </button>
      </div>
    </template>

    <template #filters>
      <FilterSection>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
          <div>
            <label for="time-range" class="block text-sm font-medium text-gray-700 mb-1">Time Range</label>
            <select
              id="time-range"
              v-model="selectedTimeRange"
              @change="loadData"
              class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
            >
              <option value="1h">Last Hour</option>
              <option value="6h">Last 6 Hours</option>
              <option value="24h">Last 24 Hours</option>
              <option value="7d">Last 7 Days</option>
              <option value="30d">Last 30 Days</option>
            </select>
          </div>

          <div>
            <label for="status-filter" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
            <MultiSelect
              v-model="selectedStatuses"
              :options="statusOptions"
              value-key="value"
              label-key="label"
              placeholder="All Statuses"
              @change="loadData"
            />
          </div>

          <div>
            <label for="email-account" class="block text-sm font-medium text-gray-700 mb-1">Email Account</label>
            <select
              id="email-account"
              v-model="selectedEmailAccount"
              @change="loadData"
              class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
            >
              <option value="">All Accounts</option>
              <option v-for="account in emailAccounts" :key="account.id" :value="account.id">
                {{ account.name }} ({{ account.from_address }})
              </option>
            </select>
          </div>

          <div>
            <label for="direction-filter" class="block text-sm font-medium text-gray-700 mb-1">Direction</label>
            <select
              id="direction-filter"
              v-model="selectedDirection"
              @change="loadData"
              class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
            >
              <option value="">All</option>
              <option value="inbound">Inbound</option>
              <option value="outbound">Outbound</option>
            </select>
          </div>
        </div>
      </FilterSection>
    </template>

    <template #main-content>
      <!-- Real-time Metrics Cards -->
      <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4 mb-8">
        <div class="bg-white overflow-hidden shadow rounded-lg">
          <div class="p-5">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <EnvelopeIcon class="h-6 w-6 text-gray-400" />
              </div>
              <div class="ml-5 w-0 flex-1">
                <dl>
                  <dt class="text-sm font-medium text-gray-500 truncate">Total Processed</dt>
                  <dd class="text-lg font-medium text-gray-900">{{ metrics.totalProcessed?.toLocaleString() || 0 }}</dd>
                </dl>
              </div>
            </div>
          </div>
          <div class="bg-gray-50 px-5 py-3">
            <div class="text-sm">
              <span class="font-medium text-green-600">{{ metrics.successRate || 0 }}%</span>
              <span class="text-gray-500"> success rate</span>
            </div>
          </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg">
          <div class="p-5">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <CheckCircleIcon class="h-6 w-6 text-green-400" />
              </div>
              <div class="ml-5 w-0 flex-1">
                <dl>
                  <dt class="text-sm font-medium text-gray-500 truncate">Successful</dt>
                  <dd class="text-lg font-medium text-gray-900">{{ metrics.successful?.toLocaleString() || 0 }}</dd>
                </dl>
              </div>
            </div>
          </div>
          <div class="bg-gray-50 px-5 py-3">
            <div class="text-sm">
              <span class="font-medium text-green-600">+{{ metrics.successfulChange || 0 }}%</span>
              <span class="text-gray-500"> from last period</span>
            </div>
          </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg">
          <div class="p-5">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <ExclamationTriangleIcon class="h-6 w-6 text-yellow-400" />
              </div>
              <div class="ml-5 w-0 flex-1">
                <dl>
                  <dt class="text-sm font-medium text-gray-500 truncate">Failed</dt>
                  <dd class="text-lg font-medium text-gray-900">{{ metrics.failed?.toLocaleString() || 0 }}</dd>
                </dl>
              </div>
            </div>
          </div>
          <div class="bg-gray-50 px-5 py-3">
            <div class="text-sm">
              <span class="font-medium text-red-600">{{ metrics.failedChange || 0 }}%</span>
              <span class="text-gray-500"> from last period</span>
            </div>
          </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg">
          <div class="p-5">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <ClockIcon class="h-6 w-6 text-blue-400" />
              </div>
              <div class="ml-5 w-0 flex-1">
                <dl>
                  <dt class="text-sm font-medium text-gray-500 truncate">Avg Processing Time</dt>
                  <dd class="text-lg font-medium text-gray-900">{{ metrics.avgProcessingTime || '0s' }}</dd>
                </dl>
              </div>
            </div>
          </div>
          <div class="bg-gray-50 px-5 py-3">
            <div class="text-sm">
              <span class="font-medium text-blue-600">{{ metrics.processingTimeChange || 0 }}%</span>
              <span class="text-gray-500"> from last period</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Processing Activity Feed -->
      <div class="bg-white shadow overflow-hidden sm:rounded-md">
        <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
          <h3 class="text-lg leading-6 font-medium text-gray-900">Recent Email Activity</h3>
          <p class="mt-1 max-w-2xl text-sm text-gray-500">
            Real-time feed of email processing events
          </p>
        </div>
        
        <div v-if="isLoading" class="px-4 py-8 text-center">
          <div class="inline-flex items-center">
            <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span class="text-gray-500">Loading email activity...</span>
          </div>
        </div>

        <ul v-else-if="emailLogs.length > 0" role="list" class="divide-y divide-gray-200">
          <li v-for="log in emailLogs" :key="log.id" class="px-4 py-4">
            <div class="flex items-center justify-between">
              <div class="flex items-center">
                <div class="flex-shrink-0">
                  <span 
                    :class="[
                      'inline-flex items-center justify-center h-8 w-8 rounded-full text-sm font-medium',
                      getStatusColor(log.status)
                    ]"
                  >
                    <component :is="getStatusIcon(log.status)" class="h-4 w-4" />
                  </span>
                </div>
                <div class="ml-4 min-w-0 flex-1">
                  <div class="flex items-center">
                    <p class="text-sm font-medium text-gray-900 truncate">
                      {{ log.subject || 'No Subject' }}
                    </p>
                    <span 
                      :class="[
                        'ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                        getStatusBadgeColor(log.status)
                      ]"
                    >
                      {{ log.status }}
                    </span>
                  </div>
                  <div class="mt-1 flex items-center text-sm text-gray-500">
                    <span class="flex items-center">
                      <component :is="getDirectionIcon(log.direction)" class="h-4 w-4 mr-1" />
                      {{ log.direction === 'inbound' ? 'From' : 'To' }}: {{ log.recipient_email || log.sender_email }}
                    </span>
                    <span class="mx-2">•</span>
                    <span>{{ formatTimestamp(log.created_at) }}</span>
                    <span v-if="log.processing_time_ms" class="mx-2">•</span>
                    <span v-if="log.processing_time_ms">{{ log.processing_time_ms }}ms</span>
                  </div>
                </div>
              </div>
              <div class="flex items-center space-x-2">
                <button
                  @click="viewLogDetails(log)"
                  class="inline-flex items-center px-3 py-1 border border-gray-300 shadow-sm text-xs font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                >
                  View Details
                </button>
                <button
                  v-if="log.status === 'failed' && log.retry_count < 3"
                  @click="retryEmail(log.id)"
                  :disabled="retryingEmails.has(log.id)"
                  class="inline-flex items-center px-3 py-1 border border-transparent text-xs font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50"
                >
                  <ArrowPathIcon v-if="retryingEmails.has(log.id)" class="h-3 w-3 mr-1 animate-spin" />
                  <ArrowPathIcon v-else class="h-3 w-3 mr-1" />
                  Retry
                </button>
              </div>
            </div>
            <div v-if="log.error_message" class="mt-3 ml-12">
              <div class="bg-red-50 border border-red-200 rounded-md p-3">
                <div class="flex">
                  <div class="flex-shrink-0">
                    <ExclamationTriangleIcon class="h-5 w-5 text-red-400" />
                  </div>
                  <div class="ml-3">
                    <p class="text-sm text-red-800">
                      {{ log.error_message }}
                    </p>
                  </div>
                </div>
              </div>
            </div>
          </li>
        </ul>

        <div v-else class="px-4 py-8 text-center">
          <EnvelopeIcon class="mx-auto h-12 w-12 text-gray-400" />
          <h3 class="mt-2 text-sm font-medium text-gray-900">No email activity</h3>
          <p class="mt-1 text-sm text-gray-500">No emails have been processed in the selected time range.</p>
        </div>
      </div>

      <!-- Pagination -->
      <div v-if="pagination.total > pagination.per_page" class="mt-6">
        <nav class="flex items-center justify-between border-t border-gray-200 bg-white px-4 py-3 sm:px-6">
          <div class="hidden sm:block">
            <p class="text-sm text-gray-700">
              Showing <span class="font-medium">{{ pagination.from || 0 }}</span> to 
              <span class="font-medium">{{ pagination.to || 0 }}</span> of 
              <span class="font-medium">{{ pagination.total || 0 }}</span> results
            </p>
          </div>
          <div class="flex flex-1 justify-between sm:justify-end">
            <button
              @click="previousPage"
              :disabled="!pagination.prev_page_url"
              class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              Previous
            </button>
            <button
              @click="nextPage"
              :disabled="!pagination.next_page_url"
              class="relative ml-3 inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              Next
            </button>
          </div>
        </nav>
      </div>
    </template>

    <template #sidebar>
      <!-- Quick Stats Widget -->
      <div class="bg-white shadow rounded-lg p-6 mb-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Queue Status</h3>
        <div class="space-y-4">
          <div class="flex justify-between items-center">
            <span class="text-sm text-gray-600">Pending</span>
            <span class="text-sm font-medium text-yellow-600">{{ queueStats.pending || 0 }}</span>
          </div>
          <div class="flex justify-between items-center">
            <span class="text-sm text-gray-600">Processing</span>
            <span class="text-sm font-medium text-blue-600">{{ queueStats.processing || 0 }}</span>
          </div>
          <div class="flex justify-between items-center">
            <span class="text-sm text-gray-600">Failed Jobs</span>
            <span class="text-sm font-medium text-red-600">{{ queueStats.failed || 0 }}</span>
          </div>
        </div>
      </div>

      <!-- System Performance Widget -->
      <div class="bg-white shadow rounded-lg p-6 mb-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Performance</h3>
        <div class="space-y-4">
          <div>
            <div class="flex justify-between items-center mb-1">
              <span class="text-sm text-gray-600">CPU Usage</span>
              <span class="text-sm font-medium">{{ systemStats.cpu_usage || 0 }}%</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2">
              <div 
                class="bg-blue-600 h-2 rounded-full transition-all duration-300"
                :style="{ width: `${systemStats.cpu_usage || 0}%` }"
              ></div>
            </div>
          </div>
          <div>
            <div class="flex justify-between items-center mb-1">
              <span class="text-sm text-gray-600">Memory Usage</span>
              <span class="text-sm font-medium">{{ systemStats.memory_usage || 0 }}%</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2">
              <div 
                class="bg-green-600 h-2 rounded-full transition-all duration-300"
                :style="{ width: `${systemStats.memory_usage || 0}%` }"
              ></div>
            </div>
          </div>
          <div>
            <div class="flex justify-between items-center mb-1">
              <span class="text-sm text-gray-600">Queue Health</span>
              <span class="text-sm font-medium">{{ systemStats.queue_health || 0 }}%</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2">
              <div 
                :class="[
                  'h-2 rounded-full transition-all duration-300',
                  systemStats.queue_health >= 80 ? 'bg-green-600' : 
                  systemStats.queue_health >= 60 ? 'bg-yellow-600' : 'bg-red-600'
                ]"
                :style="{ width: `${systemStats.queue_health || 0}%` }"
              ></div>
            </div>
          </div>
        </div>
      </div>

      <!-- Recent Errors Widget -->
      <div v-if="recentErrors.length > 0" class="bg-white shadow rounded-lg p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Recent Errors</h3>
        <div class="space-y-3">
          <div 
            v-for="error in recentErrors.slice(0, 5)" 
            :key="error.id"
            class="p-3 bg-red-50 border border-red-200 rounded-md"
          >
            <p class="text-sm font-medium text-red-800 truncate">
              {{ error.error_message }}
            </p>
            <p class="text-xs text-red-600 mt-1">
              {{ formatTimestamp(error.created_at) }}
            </p>
          </div>
        </div>
      </div>
    </template>
  </StandardPageLayout>

  <!-- Log Details Modal -->
  <LogDetailsModal
    v-if="selectedLog"
    :log="selectedLog"
    @close="selectedLog = null"
  />

  <!-- System Health Modal -->
  <SystemHealthModal
    v-if="showSystemHealthModal"
    @close="showSystemHealthModal = false"
  />
</template>

<script setup>
import { ref, reactive, computed, onMounted, onUnmounted } from 'vue'
import { usePage } from '@inertiajs/vue3'
import StandardPageLayout from '@/Layouts/StandardPageLayout.vue'
import FilterSection from '@/Components/Layout/FilterSection.vue'
import MultiSelect from '@/Components/UI/MultiSelect.vue'
import LogDetailsModal from './Components/LogDetailsModal.vue'
import SystemHealthModal from './Components/SystemHealthModal.vue'
import {
  EnvelopeIcon,
  CheckCircleIcon,
  ExclamationTriangleIcon,
  ClockIcon,
  ChartBarIcon,
  ArrowPathIcon,
  ArrowUpIcon,
  ArrowDownIcon,
} from '@heroicons/vue/24/outline'

// Props from Laravel
defineProps({
  emailAccounts: {
    type: Array,
    default: () => []
  },
  initialMetrics: {
    type: Object,
    default: () => ({})
  }
})

// Reactive state
const isLoading = ref(false)
const isRefreshing = ref(false)
const selectedTimeRange = ref('24h')
const selectedStatuses = ref([])
const selectedEmailAccount = ref('')
const selectedDirection = ref('')
const emailLogs = ref([])
const metrics = reactive({})
const pagination = reactive({})
const queueStats = reactive({})
const systemStats = reactive({})
const recentErrors = ref([])
const selectedLog = ref(null)
const showSystemHealthModal = ref(false)
const retryingEmails = ref(new Set())

// WebSocket connection
let wsConnection = null

// Filter options
const statusOptions = [
  { value: 'pending', label: 'Pending' },
  { value: 'processing', label: 'Processing' },
  { value: 'sent', label: 'Sent' },
  { value: 'delivered', label: 'Delivered' },
  { value: 'failed', label: 'Failed' },
  { value: 'bounced', label: 'Bounced' },
  { value: 'complained', label: 'Complained' }
]

// Computed properties
const page = usePage()

// Methods
const loadData = async () => {
  isLoading.value = true
  
  try {
    const params = new URLSearchParams({
      time_range: selectedTimeRange.value,
      page: 1,
      per_page: 25
    })

    if (selectedStatuses.value.length > 0) {
      selectedStatuses.value.forEach(status => params.append('status[]', status))
    }
    if (selectedEmailAccount.value) {
      params.append('email_account_id', selectedEmailAccount.value)
    }
    if (selectedDirection.value) {
      params.append('direction', selectedDirection.value)
    }

    const response = await fetch(`/api/email-admin/monitoring?${params}`)
    const data = await response.json()

    if (data.success) {
      emailLogs.value = data.data.logs.data
      Object.assign(pagination, data.data.logs)
      Object.assign(metrics, data.data.metrics)
      Object.assign(queueStats, data.data.queue_stats)
      Object.assign(systemStats, data.data.system_stats)
      recentErrors.value = data.data.recent_errors
    }
  } catch (error) {
    console.error('Error loading monitoring data:', error)
  } finally {
    isLoading.value = false
  }
}

const refreshData = async () => {
  isRefreshing.value = true
  await loadData()
  isRefreshing.value = false
}

const previousPage = () => {
  if (pagination.prev_page_url) {
    loadPage(pagination.current_page - 1)
  }
}

const nextPage = () => {
  if (pagination.next_page_url) {
    loadPage(pagination.current_page + 1)
  }
}

const loadPage = async (page) => {
  const params = new URLSearchParams({
    time_range: selectedTimeRange.value,
    page: page,
    per_page: 25
  })

  if (selectedStatuses.value.length > 0) {
    selectedStatuses.value.forEach(status => params.append('status[]', status))
  }
  if (selectedEmailAccount.value) {
    params.append('email_account_id', selectedEmailAccount.value)
  }
  if (selectedDirection.value) {
    params.append('direction', selectedDirection.value)
  }

  const response = await fetch(`/api/email-admin/monitoring?${params}`)
  const data = await response.json()

  if (data.success) {
    emailLogs.value = data.data.logs.data
    Object.assign(pagination, data.data.logs)
  }
}

const viewLogDetails = (log) => {
  selectedLog.value = log
}

const retryEmail = async (logId) => {
  retryingEmails.value.add(logId)
  
  try {
    const response = await fetch(`/api/email-admin/retry/${logId}`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
      }
    })

    if (response.ok) {
      // Refresh data to show updated status
      await refreshData()
    }
  } catch (error) {
    console.error('Error retrying email:', error)
  } finally {
    retryingEmails.value.delete(logId)
  }
}

const openSystemHealthModal = () => {
  showSystemHealthModal.value = true
}

// Status helpers
const getStatusColor = (status) => {
  const colors = {
    pending: 'bg-yellow-100 text-yellow-800',
    processing: 'bg-blue-100 text-blue-800',
    sent: 'bg-green-100 text-green-800',
    delivered: 'bg-green-100 text-green-800',
    failed: 'bg-red-100 text-red-800',
    bounced: 'bg-red-100 text-red-800',
    complained: 'bg-red-100 text-red-800'
  }
  return colors[status] || 'bg-gray-100 text-gray-800'
}

const getStatusBadgeColor = (status) => {
  const colors = {
    pending: 'bg-yellow-100 text-yellow-800',
    processing: 'bg-blue-100 text-blue-800',
    sent: 'bg-green-100 text-green-800',
    delivered: 'bg-green-100 text-green-800',
    failed: 'bg-red-100 text-red-800',
    bounced: 'bg-red-100 text-red-800',
    complained: 'bg-red-100 text-red-800'
  }
  return colors[status] || 'bg-gray-100 text-gray-800'
}

const getStatusIcon = (status) => {
  const icons = {
    pending: ClockIcon,
    processing: ArrowPathIcon,
    sent: CheckCircleIcon,
    delivered: CheckCircleIcon,
    failed: ExclamationTriangleIcon,
    bounced: ExclamationTriangleIcon,
    complained: ExclamationTriangleIcon
  }
  return icons[status] || ClockIcon
}

const getDirectionIcon = (direction) => {
  return direction === 'inbound' ? ArrowDownIcon : ArrowUpIcon
}

const formatTimestamp = (timestamp) => {
  const date = new Date(timestamp)
  const now = new Date()
  const diffMs = now - date
  const diffMins = Math.floor(diffMs / 60000)
  const diffHours = Math.floor(diffMins / 60)
  const diffDays = Math.floor(diffHours / 24)

  if (diffMins < 1) return 'Just now'
  if (diffMins < 60) return `${diffMins}m ago`
  if (diffHours < 24) return `${diffHours}h ago`
  if (diffDays < 7) return `${diffDays}d ago`
  
  return date.toLocaleDateString()
}

// WebSocket connection for real-time updates
const connectWebSocket = () => {
  if (typeof window !== 'undefined' && window.Echo) {
    wsConnection = window.Echo.channel('email-monitoring')
      .listen('EmailProcessed', (event) => {
        // Add new log to the top of the list
        emailLogs.value.unshift(event.log)
        
        // Update metrics
        Object.assign(metrics, event.metrics)
        
        // Keep list at reasonable size
        if (emailLogs.value.length > 50) {
          emailLogs.value = emailLogs.value.slice(0, 50)
        }
      })
      .listen('QueueStatsUpdated', (event) => {
        Object.assign(queueStats, event.stats)
      })
      .listen('SystemStatsUpdated', (event) => {
        Object.assign(systemStats, event.stats)
      })
  }
}

const disconnectWebSocket = () => {
  if (wsConnection) {
    wsConnection.stopListening('EmailProcessed')
    wsConnection.stopListening('QueueStatsUpdated') 
    wsConnection.stopListening('SystemStatsUpdated')
    wsConnection = null
  }
}

// Lifecycle
onMounted(() => {
  loadData()
  connectWebSocket()
  
  // Auto-refresh every 30 seconds
  const refreshInterval = setInterval(refreshData, 30000)
  
  onUnmounted(() => {
    clearInterval(refreshInterval)
    disconnectWebSocket()
  })
})
</script>