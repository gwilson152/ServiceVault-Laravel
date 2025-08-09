<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { 
  ClockIcon,
  CheckCircleIcon,
  XCircleIcon,
  ExclamationCircleIcon,
  CurrencyDollarIcon,
  UserIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
  widgetData: {
    type: [Object, Array],
    default: null
  },
  widgetConfig: {
    type: Object,
    default: () => ({})
  },
  accountContext: {
    type: Object,
    default: () => ({})
  }
})

// Emits
const emit = defineEmits(['refresh', 'configure'])

// State
const timeEntries = ref([])
const loading = ref(true)
const error = ref(null)
const stats = ref({
  total_entries: 0,
  pending_approval: 0,
  total_hours: 0,
  total_amount: 0
})

let refreshInterval = null

// Computed
const recentEntries = computed(() => {
  return timeEntries.value.slice(0, 6)
})

const pendingEntries = computed(() => {
  return timeEntries.value.filter(entry => entry.status === 'pending')
})

// Methods
const getStatusIcon = (status) => {
  switch (status) {
    case 'approved':
      return CheckCircleIcon
    case 'rejected':
      return XCircleIcon
    case 'pending':
      return ExclamationCircleIcon
    default:
      return ClockIcon
  }
}

const getStatusColor = (status) => {
  const colors = {
    'draft': 'text-gray-500 bg-gray-100',
    'pending': 'text-yellow-700 bg-yellow-100',
    'approved': 'text-green-700 bg-green-100',
    'rejected': 'text-red-700 bg-red-100'
  }
  return colors[status] || 'text-gray-500 bg-gray-100'
}

const formatDuration = (seconds) => {
  if (!seconds) return '0h 0m'
  
  const hours = Math.floor(seconds / 3600)
  const minutes = Math.floor((seconds % 3600) / 60)
  
  if (hours > 0) {
    return `${hours}h ${minutes}m`
  }
  return `${minutes}m`
}

const formatCurrency = (amount) => {
  if (!amount) return '$0.00'
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD'
  }).format(amount)
}

const formatDate = (dateString) => {
  if (!dateString) return ''
  
  const date = new Date(dateString)
  const now = new Date()
  const diffDays = Math.floor((now - date) / (1000 * 60 * 60 * 24))
  
  if (diffDays === 0) return 'Today'
  if (diffDays === 1) return 'Yesterday'
  if (diffDays < 7) return `${diffDays} days ago`
  
  return date.toLocaleDateString()
}

const fetchTimeEntries = async () => {
  try {
    loading.value = true
    error.value = null
    
    const response = await fetch('/api/time-entries?per_page=10&sort=-created_at', {
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.head.querySelector('meta[name="csrf-token"]').content
      }
    })

    if (!response.ok) {
      throw new Error(`HTTP ${response.status}: ${response.statusText}`)
    }

    const data = await response.json()
    timeEntries.value = data.data || []
    
    // Calculate stats
    stats.value = {
      total_entries: timeEntries.value.length,
      pending_approval: timeEntries.value.filter(entry => entry.status === 'pending').length,
      total_hours: timeEntries.value.reduce((sum, entry) => sum + (entry.duration / 3600), 0),
      total_amount: timeEntries.value.reduce((sum, entry) => sum + (entry.billed_amount || 0), 0)
    }
    
  } catch (err) {
    console.error('Failed to fetch time entries:', err)
    error.value = err.message
    timeEntries.value = []
  } finally {
    loading.value = false
  }
}

const approveEntry = async (entryId) => {
  try {
    const response = await fetch(`/api/time-entries/${entryId}/approve`, {
      method: 'POST',
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.head.querySelector('meta[name="csrf-token"]').content
      }
    })

    if (response.ok) {
      await fetchTimeEntries() // Refresh data
    }
  } catch (err) {
    console.error('Failed to approve entry:', err)
    alert('Failed to approve time entry: ' + err.message)
  }
}

const rejectEntry = async (entryId) => {
  const reason = prompt('Reason for rejection (optional):')
  if (reason === null) return // User cancelled
  
  try {
    const response = await fetch(`/api/time-entries/${entryId}/reject`, {
      method: 'POST',
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.head.querySelector('meta[name="csrf-token"]').content
      },
      body: JSON.stringify({
        notes: reason
      })
    })

    if (response.ok) {
      await fetchTimeEntries() // Refresh data
    }
  } catch (err) {
    console.error('Failed to reject entry:', err)
    alert('Failed to reject time entry: ' + err.message)
  }
}

const navigateToEntry = (entryId) => {
  window.location.href = `/time-entries/${entryId}`
}

// Lifecycle
onMounted(async () => {
  await fetchTimeEntries()
  
  // Set up auto-refresh (default 30 seconds)
  const refreshIntervalMs = (props.widgetConfig?.refreshInterval || 30) * 1000
  refreshInterval = setInterval(fetchTimeEntries, refreshIntervalMs)
})

onUnmounted(() => {
  if (refreshInterval) {
    clearInterval(refreshInterval)
  }
})
</script>

<template>
  <div class="h-full flex flex-col">
    <!-- Widget Header -->
    <div class="flex items-center justify-between mb-4">
      <div class="flex items-center space-x-2">
        <ClockIcon class="w-5 h-5 text-blue-600" />
        <h3 class="text-lg font-semibold text-gray-900">Recent Time Entries</h3>
      </div>
      
      <!-- Stats Summary -->
      <div class="text-sm text-gray-600">
        {{ stats.pending_approval }} pending approval
      </div>
    </div>

    <!-- Summary Stats -->
    <div class="grid grid-cols-3 gap-4 mb-4 text-center">
      <div class="bg-blue-50 rounded-lg p-2">
        <div class="text-lg font-bold text-blue-600">{{ stats.total_entries }}</div>
        <div class="text-xs text-blue-700">Total Entries</div>
      </div>
      <div class="bg-green-50 rounded-lg p-2">
        <div class="text-lg font-bold text-green-600">{{ stats.total_hours.toFixed(1) }}h</div>
        <div class="text-xs text-green-700">Total Hours</div>
      </div>
      <div class="bg-purple-50 rounded-lg p-2">
        <div class="text-lg font-bold text-purple-600">{{ formatCurrency(stats.total_amount) }}</div>
        <div class="text-xs text-purple-700">Total Value</div>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex-1 flex items-center justify-center">
      <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="flex-1 flex items-center justify-center text-center">
      <div>
        <div class="text-red-600 mb-2">⚠️ Failed to load time entries</div>
        <div class="text-sm text-gray-500">{{ error }}</div>
        <button 
          @click="fetchTimeEntries"
          class="mt-2 px-3 py-1 bg-blue-600 text-white rounded text-sm hover:bg-blue-700"
        >
          Retry
        </button>
      </div>
    </div>

    <!-- No Entries State -->
    <div v-else-if="recentEntries.length === 0" class="flex-1 flex items-center justify-center text-center text-gray-500">
      <div>
        <ClockIcon class="w-12 h-12 mx-auto mb-3 text-gray-400" />
        <div class="text-lg font-medium">No Time Entries</div>
        <div class="text-sm">No time entries found</div>
      </div>
    </div>

    <!-- Time Entries List -->
    <div v-else class="flex-1 overflow-hidden">
      <div class="space-y-2 overflow-y-auto max-h-64">
        <div 
          v-for="entry in recentEntries" 
          :key="entry.id"
          class="bg-white border border-gray-200 rounded-lg p-3 hover:shadow-sm transition-shadow"
        >
          <!-- Entry Header -->
          <div class="flex items-center justify-between mb-2">
            <div class="flex items-center space-x-2">
              <div class="flex items-center space-x-1">
                <UserIcon class="w-4 h-4 text-gray-500" />
                <span class="text-sm font-medium text-gray-900">
                  {{ entry.user?.name || 'Unknown User' }}
                </span>
              </div>
              
              <!-- Status Badge -->
              <span :class="`px-2 py-1 rounded-full text-xs font-medium ${getStatusColor(entry.status)}`">
                {{ entry.status.toUpperCase() }}
              </span>
            </div>
            
            <div class="text-right text-sm">
              <div class="font-mono font-semibold">
                {{ formatDuration(entry.duration) }}
              </div>
              <div v-if="entry.billed_amount" class="text-green-600 font-medium">
                {{ formatCurrency(entry.billed_amount) }}
              </div>
            </div>
          </div>

          <!-- Entry Description -->
          <div class="mb-2">
            <p class="text-sm text-gray-700 line-clamp-2">
              {{ entry.description || 'No description' }}
            </p>
            <div v-if="entry.service_ticket" class="text-xs text-gray-500 mt-1">
              Ticket: {{ entry.service_ticket.ticket_number }}
            </div>
          </div>

          <!-- Entry Meta and Actions -->
          <div class="flex items-center justify-between">
            <div class="text-xs text-gray-500">
              {{ formatDate(entry.started_at) }}
            </div>
            
            <!-- Approval Actions for Pending Entries -->
            <div v-if="entry.status === 'pending'" class="flex space-x-1">
              <button
                @click="approveEntry(entry.id)"
                class="px-2 py-1 bg-green-600 text-white rounded text-xs hover:bg-green-700 transition-colors"
                title="Approve"
              >
                ✓
              </button>
              <button
                @click="rejectEntry(entry.id)"
                class="px-2 py-1 bg-red-600 text-white rounded text-xs hover:bg-red-700 transition-colors"
                title="Reject"
              >
                ✗
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- View All Link -->
      <div class="mt-3 text-center">
        <a 
          href="/time-entries" 
          class="text-sm text-blue-600 hover:text-blue-700 font-medium"
        >
          View all time entries →
        </a>
      </div>
    </div>
  </div>
</template>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>