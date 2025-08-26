<template>
  <Head title="Email System Monitoring" />

  <StandardPageLayout 
    title="Email System Monitoring" 
    subtitle="Monitor email processing, performance metrics, and system health"
    :show-sidebar="true"
    :show-filters="true"
  >
    <template #header-actions>
      <div class="flex items-center space-x-3">
        <!-- Refresh Data -->
        <button
          @click="refreshData"
          :disabled="refreshing"
          class="inline-flex items-center px-3 py-2 border border-gray-300 text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
        >
          <ArrowPathIcon :class="['w-4 h-4 mr-2', refreshing ? 'animate-spin' : '']" />
          Refresh
        </button>

        <!-- System Health Check -->
        <button
          @click="runHealthCheck"
          :disabled="healthChecking"
          class="inline-flex items-center px-3 py-2 border border-gray-300 text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
        >
          <HeartIcon class="w-4 h-4 mr-2" />
          Health Check
        </button>

        <!-- Email System Configuration -->
        <a
          :href="route('settings.index', 'email')"
          class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
        >
          <CogIcon class="w-4 h-4 mr-2" />
          Configure Email System
        </a>
      </div>
    </template>

    <template #filters>
      <FilterSection>
        <!-- Time Range Filter -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Time Range</label>
          <select
            v-model="filters.timeRange"
            @change="loadDashboardData"
            class="block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
          >
            <option value="1h">Last Hour</option>
            <option value="24h">Last 24 Hours</option>
            <option value="7d">Last 7 Days</option>
            <option value="30d">Last 30 Days</option>
          </select>
        </div>

        <!-- Status Filter -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
          <MultiSelect
            v-model="filters.statuses"
            :options="statusOptions"
            placeholder="All statuses"
            value-key="value"
            label-key="label"
          />
        </div>

        <!-- Email Service Filter -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Service Type</label>
          <select
            v-model="filters.serviceType"
            @change="loadDashboardData"
            class="block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
          >
            <option value="">All Services</option>
            <option value="incoming">Incoming Email</option>
            <option value="outgoing">Outgoing Email</option>
          </select>
        </div>
      </FilterSection>
    </template>

    <template #main-content>
      <!-- System Status Alerts -->
      <div v-if="systemAlerts.length > 0" class="mb-6 space-y-3">
        <div v-for="alert in systemAlerts" :key="alert.id" 
          :class="[
            'p-4 rounded-lg border-l-4',
            alert.severity === 'error' ? 'bg-red-50 border-red-400' :
            alert.severity === 'warning' ? 'bg-yellow-50 border-yellow-400' :
            'bg-blue-50 border-blue-400'
          ]">
          <div class="flex items-start">
            <div class="flex-shrink-0">
              <ExclamationTriangleIcon v-if="alert.severity === 'error'" class="h-5 w-5 text-red-400" />
              <ExclamationTriangleIcon v-else-if="alert.severity === 'warning'" class="h-5 w-5 text-yellow-400" />
              <InformationCircleIcon v-else class="h-5 w-5 text-blue-400" />
            </div>
            <div class="ml-3 flex-1">
              <h3 :class="[
                'text-sm font-medium',
                alert.severity === 'error' ? 'text-red-800' :
                alert.severity === 'warning' ? 'text-yellow-800' :
                'text-blue-800'
              ]">
                {{ alert.title }}
              </h3>
              <p :class="[
                'text-sm mt-1',
                alert.severity === 'error' ? 'text-red-700' :
                alert.severity === 'warning' ? 'text-yellow-700' :
                'text-blue-700'
              ]">
                {{ alert.message }}
              </p>
            </div>
            <button
              @click="dismissAlert(alert.id)"
              class="flex-shrink-0 ml-4 text-gray-400 hover:text-gray-600"
            >
              <XMarkIcon class="h-4 w-4" />
            </button>
          </div>
        </div>
      </div>

      <!-- Key Metrics Overview -->
      <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4 mb-8">
        <!-- Total Emails -->
        <div class="bg-white overflow-hidden shadow rounded-lg">
          <div class="p-5">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <EnvelopeIcon class="h-6 w-6 text-gray-400" />
              </div>
              <div class="ml-5 w-0 flex-1">
                <dl>
                  <dt class="text-sm font-medium text-gray-500 truncate">Total Emails</dt>
                  <dd class="flex items-baseline">
                    <div class="text-2xl font-semibold text-gray-900">
                      {{ dashboardData.metrics?.total_emails?.toLocaleString() || 0 }}
                    </div>
                    <div class="ml-2 flex items-baseline text-sm font-semibold">
                      <span :class="[
                        dashboardData.metrics?.email_change >= 0 ? 'text-green-600' : 'text-red-600'
                      ]">
                        {{ dashboardData.metrics?.email_change >= 0 ? '+' : '' }}{{ dashboardData.metrics?.email_change || 0 }}%
                      </span>
                      <span class="ml-1 text-gray-500">vs yesterday</span>
                    </div>
                  </dd>
                </dl>
              </div>
            </div>
          </div>
        </div>

        <!-- Success Rate -->
        <div class="bg-white overflow-hidden shadow rounded-lg">
          <div class="p-5">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <CheckCircleIcon class="h-6 w-6 text-green-400" />
              </div>
              <div class="ml-5 w-0 flex-1">
                <dl>
                  <dt class="text-sm font-medium text-gray-500 truncate">Success Rate</dt>
                  <dd class="text-2xl font-semibold text-gray-900">
                    {{ dashboardData.metrics?.success_rate || 0 }}%
                  </dd>
                </dl>
              </div>
            </div>
          </div>
        </div>

        <!-- Commands Executed -->
        <div class="bg-white overflow-hidden shadow rounded-lg">
          <div class="p-5">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <CommandLineIcon class="h-6 w-6 text-indigo-400" />
              </div>
              <div class="ml-5 w-0 flex-1">
                <dl>
                  <dt class="text-sm font-medium text-gray-500 truncate">Commands Executed</dt>
                  <dd class="text-2xl font-semibold text-gray-900">
                    {{ dashboardData.metrics?.commands_executed?.toLocaleString() || 0 }}
                  </dd>
                </dl>
              </div>
            </div>
          </div>
        </div>

        <!-- Queue Status -->
        <div class="bg-white overflow-hidden shadow rounded-lg">
          <div class="p-5">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <QueueListIcon class="h-6 w-6 text-blue-400" />
              </div>
              <div class="ml-5 w-0 flex-1">
                <dl>
                  <dt class="text-sm font-medium text-gray-500 truncate">Pending Jobs</dt>
                  <dd class="flex items-center">
                    <div class="text-2xl font-semibold text-gray-900">
                      {{ dashboardData.queue?.pending || 0 }}
                    </div>
                    <div v-if="dashboardData.queue?.failed > 0" class="ml-2">
                      <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                        {{ dashboardData.queue.failed }} failed
                      </span>
                    </div>
                  </dd>
                </dl>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Email Processing Chart -->
      <div class="bg-white shadow rounded-lg mb-8">
        <div class="px-4 py-5 sm:p-6">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg leading-6 font-medium text-gray-900">Email Processing Overview</h3>
            <div class="flex items-center space-x-2">
              <button
                v-for="period in chartPeriods"
                :key="period.value"
                @click="chartPeriod = period.value; loadChartData()"
                :class="[
                  'px-3 py-1 text-sm font-medium rounded-md',
                  chartPeriod === period.value
                    ? 'bg-indigo-100 text-indigo-700'
                    : 'text-gray-500 hover:text-gray-700'
                ]"
              >
                {{ period.label }}
              </button>
            </div>
          </div>
          
          <!-- Chart Container -->
          <div class="h-64 w-full">
            <canvas ref="emailChart" class="w-full h-full"></canvas>
          </div>
        </div>
      </div>

      <!-- Recent Activity & Queue Management -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
        <!-- Recent Processing Activity -->
        <div class="bg-white shadow rounded-lg">
          <div class="px-4 py-5 sm:p-6">
            <div class="flex items-center justify-between mb-4">
              <h3 class="text-lg leading-6 font-medium text-gray-900">Recent Activity</h3>
              <button
                @click="showProcessingLogs"
                class="text-sm text-indigo-600 hover:text-indigo-500"
              >
                View All Logs
              </button>
            </div>
            
            <div class="space-y-3">
              <div v-for="activity in recentActivity" :key="activity.id" 
                class="flex items-center space-x-3 p-3 bg-gray-50 rounded-md">
                <div class="flex-shrink-0">
                  <div :class="[
                    'w-2 h-2 rounded-full',
                    activity.status === 'success' ? 'bg-green-400' :
                    activity.status === 'failed' ? 'bg-red-400' :
                    activity.status === 'processing' ? 'bg-blue-400' :
                    'bg-gray-400'
                  ]"></div>
                </div>
                <div class="min-w-0 flex-1">
                  <p class="text-sm text-gray-900 truncate">{{ activity.subject || 'Email processed' }}</p>
                  <p class="text-xs text-gray-500">
                    {{ activity.from }} • {{ formatTimeAgo(activity.created_at) }}
                  </p>
                </div>
                <div class="flex-shrink-0">
                  <span :class="[
                    'inline-flex px-2 py-1 text-xs font-medium rounded-full',
                    activity.status === 'success' ? 'bg-green-100 text-green-800' :
                    activity.status === 'failed' ? 'bg-red-100 text-red-800' :
                    activity.status === 'processing' ? 'bg-blue-100 text-blue-800' :
                    'bg-gray-100 text-gray-800'
                  ]">
                    {{ activity.status }}
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Queue Management -->
        <div class="bg-white shadow rounded-lg">
          <div class="px-4 py-5 sm:p-6">
            <div class="flex items-center justify-between mb-4">
              <h3 class="text-lg leading-6 font-medium text-gray-900">Queue Management</h3>
              <button
                @click="refreshQueueData"
                :disabled="queueRefreshing"
                class="text-sm text-indigo-600 hover:text-indigo-500"
              >
                Refresh
              </button>
            </div>

            <div class="space-y-4">
              <!-- Queue Status -->
              <div class="grid grid-cols-3 gap-4 text-sm">
                <div class="text-center">
                  <div class="text-2xl font-bold text-blue-600">{{ queueData.pending || 0 }}</div>
                  <div class="text-gray-500">Pending</div>
                </div>
                <div class="text-center">
                  <div class="text-2xl font-bold text-green-600">{{ queueData.processing || 0 }}</div>
                  <div class="text-gray-500">Processing</div>
                </div>
                <div class="text-center">
                  <div class="text-2xl font-bold text-red-600">{{ queueData.failed || 0 }}</div>
                  <div class="text-gray-500">Failed</div>
                </div>
              </div>

              <!-- Queue Actions -->
              <div class="flex space-x-2">
                <button
                  @click="retryFailedJobs"
                  :disabled="!queueData.failed || retryingJobs"
                  class="flex-1 inline-flex justify-center items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 disabled:opacity-50"
                >
                  <span v-if="retryingJobs" class="flex items-center">
                    <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Retrying...
                  </span>
                  <span v-else>Retry Failed</span>
                </button>
                <button
                  @click="clearFailedJobs"
                  :disabled="!queueData.failed || clearingJobs"
                  class="flex-1 inline-flex justify-center items-center px-3 py-2 border border-gray-300 text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50"
                >
                  Clear Failed
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Command Execution Stats -->
      <div class="bg-white shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
          <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Command Execution Statistics</h3>
          
          <div class="overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
              <thead>
                <tr>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Command</th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Uses</th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Success Rate</th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Avg Processing Time</th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Used</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-200">
                <tr v-for="command in commandStats" :key="command.name" class="hover:bg-gray-50">
                  <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                    {{ command.name }}
                  </td>
                  <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">
                    {{ command.total_uses?.toLocaleString() || 0 }}
                  </td>
                  <td class="px-4 py-4 whitespace-nowrap">
                    <div class="flex items-center">
                      <div class="text-sm text-gray-900">{{ command.success_rate || 0 }}%</div>
                      <div class="ml-2 w-16 bg-gray-200 rounded-full h-2">
                        <div 
                          class="bg-green-500 h-2 rounded-full" 
                          :style="{ width: `${command.success_rate || 0}%` }"
                        ></div>
                      </div>
                    </div>
                  </td>
                  <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">
                    {{ command.avg_processing_time || 0 }}ms
                  </td>
                  <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">
                    {{ command.last_used ? formatTimeAgo(command.last_used) : 'Never' }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </template>

    <template #sidebar>
      <!-- System Status Summary -->
      <div class="bg-white overflow-hidden shadow rounded-lg mb-6">
        <div class="p-5">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg leading-6 font-medium text-gray-900">System Status</h3>
            <div :class="[
              'flex items-center px-3 py-1 rounded-full text-xs font-medium',
              systemHealth.system_active ? 'bg-green-100 text-green-800' :
              systemHealth.fully_configured ? 'bg-yellow-100 text-yellow-800' :
              'bg-red-100 text-red-800'
            ]">
              <div :class="[
                'w-2 h-2 rounded-full mr-2',
                systemHealth.system_active ? 'bg-green-400' :
                systemHealth.fully_configured ? 'bg-yellow-400' :
                'bg-red-400'
              ]"></div>
              {{ systemHealth.system_active ? 'Active' : systemHealth.fully_configured ? 'Configured' : 'Inactive' }}
            </div>
          </div>
          
          <div class="text-sm text-gray-600">
            <p v-if="systemHealth.system_active">
              Email system is active and processing emails with 
              <strong>{{ systemHealth.active_domain_mappings || 0 }}</strong> domain mappings configured.
            </p>
            <p v-else-if="systemHealth.fully_configured">
              Email system is configured but inactive. 
              <a :href="route('settings.index', 'email')" class="text-indigo-600 hover:text-indigo-500">Activate in settings</a> to start processing.
            </p>
            <p v-else>
              Email system requires configuration. 
              <a :href="route('settings.index', 'email')" class="text-indigo-600 hover:text-indigo-500">Complete setup</a> to begin processing emails.
            </p>
          </div>
        </div>
      </div>

      <!-- Quick Actions -->
      <div class="bg-white overflow-hidden shadow rounded-lg mb-6">
        <div class="px-4 py-5 sm:p-6">
          <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Quick Actions</h3>
          <div class="space-y-3">
            <button
              @click="showProcessingLogs"
              class="w-full text-left px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 rounded-md"
            >
              📋 View Processing Logs
            </button>
            <button
              @click="showSystemHealth"
              class="w-full text-left px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 rounded-md"
            >
              ❤️ System Health Details
            </button>
            <button
              @click="exportLogs"
              class="w-full text-left px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 rounded-md"
            >
              📊 Export Analytics
            </button>
            <button
              @click="testEmailSystem"
              class="w-full text-left px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 rounded-md"
            >
              🧪 Test Email System
            </button>
          </div>
        </div>
      </div>

      <!-- Recent Errors -->
      <div v-if="recentErrors.length > 0" class="bg-white overflow-hidden shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
          <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Recent Errors</h3>
          <div class="space-y-3">
            <div v-for="error in recentErrors" :key="error.id" class="p-3 bg-red-50 rounded-md">
              <p class="text-sm font-medium text-red-800">{{ error.type }}</p>
              <p class="text-xs text-red-600 mt-1">{{ error.message }}</p>
              <p class="text-xs text-gray-500 mt-1">{{ formatTimeAgo(error.occurred_at) }}</p>
            </div>
          </div>
        </div>
      </div>
    </template>
  </StandardPageLayout>

  <!-- Modals -->
  <EmailSystemSettingsModal
    :show="showSettingsModal"
    @close="showSettingsModal = false"
  />

  <ProcessingLogsModal
    :show="showLogsModal"
    @close="showLogsModal = false"
  />

  <SystemHealthModal
    :show="showHealthModal"
    :health-data="systemHealth"
    @close="showHealthModal = false"
  />
</template>

<script setup>
import { Head } from '@inertiajs/vue3'
import { ref, reactive, computed, onMounted, onUnmounted } from 'vue'
import { useQuery, useQueryClient } from '@tanstack/vue-query'
import {
  ArrowPathIcon,
  HeartIcon,
  CogIcon,
  EnvelopeIcon,
  CheckCircleIcon,
  CommandLineIcon,
  QueueListIcon,
  ExclamationTriangleIcon,
  InformationCircleIcon,
  XMarkIcon
} from '@heroicons/vue/24/outline'

// Components
import AppLayout from '@/Layouts/AppLayout.vue'
import StandardPageLayout from '@/Layouts/StandardPageLayout.vue'
import FilterSection from '@/Components/Layout/FilterSection.vue'
import MultiSelect from '@/Components/UI/MultiSelect.vue'
import UnifiedSelector from '@/Components/UI/UnifiedSelector.vue'
import EmailSystemSettingsModal from './Components/EmailSystemSettingsModal.vue'
import ProcessingLogsModal from './Components/ProcessingLogsModal.vue'
import SystemHealthModal from './Components/SystemHealthModal.vue'

// Define persistent layout
defineOptions({
  layout: (h, page) => h(AppLayout, () => page)
})

// State
const refreshing = ref(false)
const healthChecking = ref(false)
const queueRefreshing = ref(false)
const retryingJobs = ref(false)
const clearingJobs = ref(false)
const chartPeriod = ref('24h')

const showSettingsModal = ref(false)
const showLogsModal = ref(false)
const showHealthModal = ref(false)

// Chart reference
const emailChart = ref(null)

// Filters
const filters = reactive({
  timeRange: '24h',
  statuses: [],
  serviceType: ''
})

// Filter Options
const statusOptions = [
  { value: 'success', label: 'Success' },
  { value: 'failed', label: 'Failed' },
  { value: 'processing', label: 'Processing' },
  { value: 'pending', label: 'Pending' }
]

const chartPeriods = [
  { value: '1h', label: '1H' },
  { value: '24h', label: '24H' },
  { value: '7d', label: '7D' },
  { value: '30d', label: '30D' }
]

// Query Client
const queryClient = useQueryClient()

// API Queries
const { data: dashboardDataRaw } = useQuery({
  queryKey: ['email-admin-dashboard', filters.timeRange],
  queryFn: () => fetchDashboardData(),
  refetchInterval: 30000, // Refresh every 30 seconds
  retry: false, // Don't retry on 404/500 errors
  onError: (error) => {
    console.warn('Dashboard data API not available:', error)
  }
})

const { data: queueData = {} } = useQuery({
  queryKey: ['email-queue-status'],
  queryFn: fetchQueueStatus,
  refetchInterval: 10000, // Refresh every 10 seconds
  retry: false,
  onError: (error) => {
    console.warn('Queue status API not available:', error)
  }
})

// Use system health from dashboard data instead of separate query
const systemHealth = computed(() => {
  if (dashboardDataRaw.value?.data?.system_health) {
    return dashboardDataRaw.value.data.system_health
  }
  return { 
    system_active: false, 
    fully_configured: false, 
    incoming_enabled: false, 
    outgoing_enabled: false, 
    incoming_provider: 'none',
    outgoing_provider: 'none',
    domain_mappings_count: 0,
    active_domain_mappings: 0
  }
})

// Disable command stats for now since the endpoint doesn't exist
const commandStats = ref([])

// Safe data accessors with proper defaults
const dashboardData = computed(() => {
  if (!dashboardDataRaw.value) {
    return {
      metrics: {
        total_emails: 0,
        email_change: 0,
        success_rate: 0,
        commands_executed: 0
      },
      queue: {
        pending: 0,
        failed: 0
      }
    }
  }
  return {
    metrics: dashboardDataRaw.value.metrics || {
      total_emails: 0,
      email_change: 0,
      success_rate: 0,
      commands_executed: 0
    },
    queue: dashboardDataRaw.value.queue || {
      pending: 0,
      failed: 0
    }
  }
})

// Computed/Reactive Data
const recentActivity = ref([])
const systemAlerts = ref([])
const recentErrors = ref([])

// API Functions
async function fetchDashboardData() {
  const params = new URLSearchParams({ 
    time_range: filters.timeRange,
    service_type: filters.serviceType,
    statuses: filters.statuses.join(',')
  })
  const response = await fetch(`/api/email-admin/dashboard?${params}`)
  if (!response.ok) throw new Error('Failed to fetch dashboard data')
  return response.json()
}

async function fetchQueueStatus() {
  const response = await fetch('/api/email-admin/queue-status')
  if (!response.ok) throw new Error('Failed to fetch queue status')
  return response.json()
}


async function fetchCommandStats() {
  const response = await fetch('/api/email-commands/stats')
  if (!response.ok) throw new Error('Failed to fetch command stats')
  return response.json()
}

// Methods
async function refreshData() {
  refreshing.value = true
  try {
    await queryClient.invalidateQueries({ queryKey: ['email-admin-dashboard'] })
    await queryClient.invalidateQueries({ queryKey: ['email-queue-status'] })
    await queryClient.invalidateQueries({ queryKey: ['email-system-health'] })
  } finally {
    refreshing.value = false
  }
}

async function runHealthCheck() {
  healthChecking.value = true
  try {
    const response = await fetch('/api/email-admin/system-health', { 
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
      }
    })
    if (response.ok) {
      await queryClient.invalidateQueries({ queryKey: ['email-system-health'] })
    }
  } finally {
    healthChecking.value = false
  }
}

async function refreshQueueData() {
  queueRefreshing.value = true
  try {
    await queryClient.invalidateQueries({ queryKey: ['email-queue-status'] })
  } finally {
    queueRefreshing.value = false
  }
}

async function retryFailedJobs() {
  retryingJobs.value = true
  try {
    const response = await fetch('/api/email-admin/retry-processing', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
      },
      body: JSON.stringify({ retry_all: true })
    })
    
    if (response.ok) {
      await refreshQueueData()
      // Show success notification
    }
  } catch (error) {
    console.error('Error retrying failed jobs:', error)
    // Show error notification
  } finally {
    retryingJobs.value = false
  }
}

async function clearFailedJobs() {
  if (!confirm('Are you sure you want to clear all failed jobs? This action cannot be undone.')) {
    return
  }

  clearingJobs.value = true
  try {
    const response = await fetch('/api/email-admin/failed-jobs', {
      method: 'DELETE',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
      }
    })
    
    if (response.ok) {
      await refreshQueueData()
      // Show success notification
    }
  } catch (error) {
    console.error('Error clearing failed jobs:', error)
    // Show error notification
  } finally {
    clearingJobs.value = false
  }
}

function loadDashboardData() {
  queryClient.invalidateQueries({ queryKey: ['email-admin-dashboard'] })
}

function loadChartData() {
  // Load chart data based on chartPeriod
  // This would integrate with a charting library like Chart.js
}

function showProcessingLogs() {
  showLogsModal.value = true
}

function showSystemHealth() {
  showHealthModal.value = true
}

function exportLogs() {
  // Implement log export functionality
  const params = new URLSearchParams(filters)
  window.open(`/api/email-admin/export-logs?${params}`, '_blank')
}

function testEmailSystem() {
  // Implement email system testing
  // This could open a modal for testing email configurations
}

function dismissAlert(alertId) {
  systemAlerts.value = systemAlerts.value.filter(alert => alert.id !== alertId)
}

function formatTimeAgo(date) {
  if (!date) return ''
  const now = new Date()
  const past = new Date(date)
  const diffInSeconds = Math.floor((now - past) / 1000)
  
  if (diffInSeconds < 60) return 'Just now'
  if (diffInSeconds < 3600) return `${Math.floor(diffInSeconds / 60)}m ago`
  if (diffInSeconds < 86400) return `${Math.floor(diffInSeconds / 3600)}h ago`
  return `${Math.floor(diffInSeconds / 86400)}d ago`
}

// Initialize with mock data for development
onMounted(() => {
  // Mock recent activity
  recentActivity.value = [
    {
      id: 1,
      subject: 'Server maintenance completed - time:2h status:resolved',
      from: 'admin@company.com',
      status: 'success',
      created_at: new Date(Date.now() - 300000).toISOString() // 5 minutes ago
    },
    {
      id: 2,
      subject: 'Database backup - priority:high',
      from: 'system@company.com',
      status: 'processing',
      created_at: new Date(Date.now() - 900000).toISOString() // 15 minutes ago
    },
    {
      id: 3,
      subject: 'Email configuration issue',
      from: 'support@company.com',
      status: 'failed',
      created_at: new Date(Date.now() - 1800000).toISOString() // 30 minutes ago
    }
  ]

  // Mock system alerts
  systemAlerts.value = [
    {
      id: 1,
      severity: 'warning',
      title: 'High Queue Volume',
      message: 'Email queue has 45 pending jobs. Consider adding more workers.'
    }
  ]

  // Initialize chart if canvas is available
  if (emailChart.value) {
    loadChartData()
  }
})
</script>