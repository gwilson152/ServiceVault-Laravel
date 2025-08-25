<template>
  <StackedDialog
    :show="show"
    @close="$emit('close')"
    size="max"
    title="Email Processing Logs"
  >
    <div class="space-y-6">
      <!-- Filters -->
      <div class="bg-gray-50 p-4 rounded-lg">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <!-- Date Range -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Date Range</label>
            <select
              v-model="filters.dateRange"
              @change="loadLogs"
              class="block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
            >
              <option value="today">Today</option>
              <option value="yesterday">Yesterday</option>
              <option value="week">Last 7 Days</option>
              <option value="month">Last 30 Days</option>
            </select>
          </div>

          <!-- Status Filter -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
            <select
              v-model="filters.status"
              @change="loadLogs"
              class="block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
            >
              <option value="">All Statuses</option>
              <option value="success">Success</option>
              <option value="failed">Failed</option>
              <option value="processing">Processing</option>
              <option value="pending">Pending</option>
            </select>
          </div>

          <!-- Direction Filter -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Direction</label>
            <select
              v-model="filters.direction"
              @change="loadLogs"
              class="block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
            >
              <option value="">Both</option>
              <option value="incoming">Incoming</option>
              <option value="outgoing">Outgoing</option>
            </select>
          </div>

          <!-- Search -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
            <input
              v-model="filters.search"
              @input="debouncedSearch"
              type="text"
              placeholder="Subject, email, or content..."
              class="block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
            />
          </div>
        </div>

        <div class="mt-4 flex items-center justify-between">
          <div class="flex items-center space-x-4">
            <button
              @click="refreshLogs"
              :disabled="loading"
              class="inline-flex items-center px-3 py-2 border border-gray-300 text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
            >
              <ArrowPathIcon :class="['w-4 h-4 mr-2', loading ? 'animate-spin' : '']" />
              Refresh
            </button>

            <button
              @click="exportLogs"
              class="inline-flex items-center px-3 py-2 border border-gray-300 text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
            >
              <ArrowDownTrayIcon class="w-4 h-4 mr-2" />
              Export
            </button>

            <div class="flex items-center">
              <input
                id="auto-refresh"
                v-model="autoRefresh"
                type="checkbox"
                class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
              />
              <label for="auto-refresh" class="ml-2 text-sm text-gray-700">
                Auto-refresh (30s)
              </label>
            </div>
          </div>

          <div class="text-sm text-gray-500">
            {{ logs.meta?.total || 0 }} total logs
          </div>
        </div>
      </div>

      <!-- Logs Table -->
      <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 rounded-lg">
        <table class="min-w-full divide-y divide-gray-300">
          <thead class="bg-gray-50">
            <tr>
              <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Status
              </th>
              <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Direction
              </th>
              <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                From/To
              </th>
              <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Subject
              </th>
              <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Commands
              </th>
              <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Processed
              </th>
              <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Duration
              </th>
              <th scope="col" class="relative px-4 py-3">
                <span class="sr-only">Actions</span>
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="log in logs.data" :key="log.id" class="hover:bg-gray-50">
              <td class="px-4 py-4 whitespace-nowrap">
                <span :class="[
                  'inline-flex px-2 py-1 text-xs font-medium rounded-full',
                  log.status === 'success' ? 'bg-green-100 text-green-800' :
                  log.status === 'failed' ? 'bg-red-100 text-red-800' :
                  log.status === 'processing' ? 'bg-blue-100 text-blue-800' :
                  'bg-gray-100 text-gray-800'
                ]">
                  {{ log.status }}
                </span>
              </td>
              <td class="px-4 py-4 whitespace-nowrap">
                <div class="flex items-center">
                  <ArrowUpIcon v-if="log.direction === 'outgoing'" class="h-4 w-4 text-blue-500 mr-1" />
                  <ArrowDownIcon v-else class="h-4 w-4 text-green-500 mr-1" />
                  <span class="text-sm text-gray-900 capitalize">{{ log.direction }}</span>
                </div>
              </td>
              <td class="px-4 py-4">
                <div class="text-sm text-gray-900">{{ log.from_address }}</div>
                <div v-if="log.to_addresses" class="text-xs text-gray-500">
                  to: {{ Array.isArray(log.to_addresses) ? log.to_addresses.join(', ') : log.to_addresses }}
                </div>
              </td>
              <td class="px-4 py-4">
                <div class="text-sm text-gray-900 max-w-xs truncate" :title="log.subject">
                  {{ log.subject || '(No subject)' }}
                </div>
                <div v-if="log.ticket_number" class="text-xs text-indigo-600">
                  {{ log.ticket_number }}
                </div>
              </td>
              <td class="px-4 py-4">
                <div v-if="log.commands_executed > 0" class="flex flex-wrap gap-1">
                  <span 
                    v-for="command in log.executed_commands" 
                    :key="command"
                    class="inline-flex px-2 py-1 text-xs bg-indigo-100 text-indigo-700 rounded-md"
                  >
                    {{ command }}
                  </span>
                </div>
                <div v-else class="text-xs text-gray-400">None</div>
              </td>
              <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">
                {{ formatDate(log.processed_at || log.created_at) }}
              </td>
              <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">
                {{ log.processing_duration_ms ? `${log.processing_duration_ms}ms` : '-' }}
              </td>
              <td class="px-4 py-4 whitespace-nowrap text-right text-sm font-medium">
                <button
                  @click="viewLogDetails(log)"
                  class="text-indigo-600 hover:text-indigo-900"
                >
                  View
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Empty State -->
      <div v-if="!logs.data?.length && !loading" class="text-center py-12">
        <DocumentTextIcon class="mx-auto h-12 w-12 text-gray-400" />
        <h3 class="mt-2 text-sm font-medium text-gray-900">No logs found</h3>
        <p class="mt-1 text-sm text-gray-500">Try adjusting your filters or date range.</p>
      </div>

      <!-- Pagination -->
      <div v-if="logs.meta" class="flex items-center justify-between">
        <div class="text-sm text-gray-700">
          Showing {{ logs.meta.from || 0 }} to {{ logs.meta.to || 0 }} of {{ logs.meta.total || 0 }} results
        </div>
        <div class="flex items-center space-x-2">
          <button
            @click="previousPage"
            :disabled="!logs.meta.prev_page_url"
            class="px-3 py-2 border border-gray-300 text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50"
          >
            Previous
          </button>
          <span class="text-sm text-gray-700">
            Page {{ logs.meta.current_page || 1 }} of {{ logs.meta.last_page || 1 }}
          </span>
          <button
            @click="nextPage"
            :disabled="!logs.meta.next_page_url"
            class="px-3 py-2 border border-gray-300 text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50"
          >
            Next
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

  <!-- Log Details Modal -->
  <LogDetailsModal
    :show="showDetailsModal"
    :log="selectedLog"
    @close="closeDetailsModal"
  />
</template>

<script setup>
import { ref, reactive, computed, onMounted, onUnmounted, watch } from 'vue'
import {
  ArrowPathIcon,
  ArrowDownTrayIcon,
  ArrowUpIcon,
  ArrowDownIcon,
  DocumentTextIcon
} from '@heroicons/vue/24/outline'
import StackedDialog from '@/Components/Modals/StackedDialog.vue'
import LogDetailsModal from './LogDetailsModal.vue'

const props = defineProps({
  show: Boolean
})

const emit = defineEmits(['close'])

// State
const loading = ref(false)
const autoRefresh = ref(false)
const autoRefreshInterval = ref(null)
const showDetailsModal = ref(false)
const selectedLog = ref(null)

// Data
const logs = ref({ data: [], meta: null })

// Filters
const filters = reactive({
  dateRange: 'today',
  status: '',
  direction: '',
  search: '',
  page: 1
})

// Methods
async function loadLogs() {
  try {
    loading.value = true
    
    const params = new URLSearchParams({
      date_range: filters.dateRange,
      page: filters.page.toString(),
      per_page: '25'
    })
    
    if (filters.status) params.append('status', filters.status)
    if (filters.direction) params.append('direction', filters.direction)
    if (filters.search) params.append('search', filters.search)

    const response = await fetch(`/api/email-admin/processing-logs?${params}`)
    if (!response.ok) throw new Error('Failed to fetch logs')
    
    const data = await response.json()
    logs.value = data

  } catch (error) {
    console.error('Error loading logs:', error)
    // Use mock data for development
    logs.value = generateMockLogs()
  } finally {
    loading.value = false
  }
}

async function refreshLogs() {
  await loadLogs()
}

async function exportLogs() {
  try {
    const params = new URLSearchParams({
      date_range: filters.dateRange,
      format: 'csv'
    })
    
    if (filters.status) params.append('status', filters.status)
    if (filters.direction) params.append('direction', filters.direction)
    if (filters.search) params.append('search', filters.search)

    const response = await fetch(`/api/email-admin/export-logs?${params}`)
    if (!response.ok) throw new Error('Failed to export logs')
    
    const blob = await response.blob()
    const url = window.URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = `email-logs-${new Date().toISOString().split('T')[0]}.csv`
    document.body.appendChild(a)
    a.click()
    document.body.removeChild(a)
    window.URL.revokeObjectURL(url)

  } catch (error) {
    console.error('Error exporting logs:', error)
    // Show error notification
  }
}

function viewLogDetails(log) {
  selectedLog.value = log
  showDetailsModal.value = true
}

function closeDetailsModal() {
  showDetailsModal.value = false
  selectedLog.value = null
}

function previousPage() {
  if (logs.value.meta?.prev_page_url) {
    filters.page--
    loadLogs()
  }
}

function nextPage() {
  if (logs.value.meta?.next_page_url) {
    filters.page++
    loadLogs()
  }
}

function formatDate(date) {
  if (!date) return '-'
  return new Date(date).toLocaleString('en-US', {
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

// Debounced search
let searchTimeout = null
function debouncedSearch() {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    filters.page = 1
    loadLogs()
  }, 500)
}

// Auto-refresh functionality
function startAutoRefresh() {
  if (autoRefreshInterval.value) return
  
  autoRefreshInterval.value = setInterval(() => {
    if (autoRefresh.value) {
      refreshLogs()
    }
  }, 30000) // 30 seconds
}

function stopAutoRefresh() {
  if (autoRefreshInterval.value) {
    clearInterval(autoRefreshInterval.value)
    autoRefreshInterval.value = null
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
    loadLogs()
    startAutoRefresh()
  } else {
    stopAutoRefresh()
  }
})

// Cleanup on unmount
onUnmounted(() => {
  stopAutoRefresh()
})

// Mock data generator for development
function generateMockLogs() {
  const mockLogs = []
  const statuses = ['success', 'failed', 'processing', 'pending']
  const directions = ['incoming', 'outgoing']
  
  for (let i = 0; i < 25; i++) {
    mockLogs.push({
      id: i + 1,
      email_id: `email-${i + 1}`,
      status: statuses[Math.floor(Math.random() * statuses.length)],
      direction: directions[Math.floor(Math.random() * directions.length)],
      from_address: `user${i + 1}@example.com`,
      to_addresses: ['support@company.com'],
      subject: `Test Email ${i + 1} - time:${15 + (i * 5)} status:resolved`,
      ticket_number: Math.random() > 0.5 ? `TKT-2025-${String(i + 1).padStart(4, '0')}` : null,
      commands_executed: Math.floor(Math.random() * 3),
      executed_commands: ['time', 'status'],
      processed_at: new Date(Date.now() - (i * 300000)).toISOString(),
      processing_duration_ms: 150 + Math.floor(Math.random() * 500)
    })
  }

  return {
    data: mockLogs,
    meta: {
      current_page: 1,
      last_page: 3,
      per_page: 25,
      total: 75,
      from: 1,
      to: 25,
      prev_page_url: null,
      next_page_url: '/api/email-admin/processing-logs?page=2'
    }
  }
}
</script>