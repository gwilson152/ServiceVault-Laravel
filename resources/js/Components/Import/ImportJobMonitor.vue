<template>
  <div v-if="showMonitor" class="fixed bottom-4 right-4 z-40">
    <!-- Minimized State -->
    <div v-if="minimized" class="bg-white rounded-lg shadow-lg border border-gray-200 p-3 cursor-pointer" @click="minimized = false">
      <div class="flex items-center space-x-3">
        <div class="flex-shrink-0">
          <div class="w-3 h-3 rounded-full" :class="statusColor"></div>
        </div>
        <div class="text-sm font-medium text-gray-900">
          {{ activeJobsCount }} active import{{ activeJobsCount === 1 ? '' : 's' }}
        </div>
        <ChevronUpIcon class="w-4 h-4 text-gray-400" />
      </div>
    </div>

    <!-- Expanded State -->
    <div v-else class="bg-white rounded-lg shadow-lg border border-gray-200 w-96 max-h-96 overflow-hidden">
      <!-- Header -->
      <div class="bg-gray-50 px-4 py-3 border-b border-gray-200 flex items-center justify-between">
        <div class="flex items-center space-x-2">
          <div class="w-3 h-3 rounded-full" :class="statusColor"></div>
          <h3 class="text-sm font-medium text-gray-900">Import Jobs</h3>
        </div>
        <div class="flex items-center space-x-2">
          <button @click="refreshJobs" class="text-gray-400 hover:text-gray-600">
            <ArrowPathIcon class="w-4 h-4" :class="{ 'animate-spin': refreshing }" />
          </button>
          <button @click="minimized = true" class="text-gray-400 hover:text-gray-600">
            <ChevronDownIcon class="w-4 h-4" />
          </button>
          <button @click="closeMonitor" class="text-gray-400 hover:text-gray-600">
            <XMarkIcon class="w-4 h-4" />
          </button>
        </div>
      </div>

      <!-- Jobs List -->
      <div class="max-h-80 overflow-y-auto">
        <div v-if="activeJobsCount === 0" class="p-6 text-center">
          <ClockIcon class="mx-auto h-8 w-8 text-gray-400" />
          <p class="mt-2 text-sm text-gray-500">No active import jobs</p>
        </div>

        <div v-else class="divide-y divide-gray-200">
          <div
            v-for="job in sortedActiveJobs"
            :key="job.id"
            class="p-4 hover:bg-gray-50"
          >
            <!-- Job Header -->
            <div class="flex items-center justify-between mb-2">
              <div class="flex items-center space-x-2">
                <div class="text-sm font-medium text-gray-900 truncate" :title="job.profile?.name">
                  {{ job.profile?.name || 'Import Job' }}
                </div>
                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium" :class="getStatusBadgeClass(job.status)">
                  {{ job.status }}
                </span>
              </div>
              <div class="flex items-center space-x-2">
                <button
                  v-if="job.status === 'running'"
                  @click="cancelJob(job.id)"
                  class="text-xs text-red-600 hover:text-red-800"
                  :disabled="cancelling.has(job.id)"
                >
                  {{ cancelling.has(job.id) ? 'Cancelling...' : 'Cancel' }}
                </button>
                <button
                  @click="viewJobDetails(job)"
                  class="text-xs text-indigo-600 hover:text-indigo-800"
                >
                  Details
                </button>
              </div>
            </div>

            <!-- Progress Bar -->
            <div class="mb-2">
              <div class="flex items-center justify-between text-xs text-gray-600 mb-1">
                <span>{{ job.current_operation || 'Processing...' }}</span>
                <span>{{ job.progress_percentage || 0 }}%</span>
              </div>
              <div class="w-full bg-gray-200 rounded-full h-2">
                <div
                  class="h-2 rounded-full transition-all duration-300"
                  :class="getProgressBarClass(job.status)"
                  :style="{ width: `${job.progress_percentage || 0}%` }"
                ></div>
              </div>
            </div>

            <!-- Job Stats -->
            <div class="grid grid-cols-3 gap-2 text-xs">
              <div class="text-center">
                <div class="font-medium text-green-600">{{ job.records_imported || 0 }}</div>
                <div class="text-gray-500">Imported</div>
              </div>
              <div class="text-center">
                <div class="font-medium text-yellow-600">{{ job.records_skipped || 0 }}</div>
                <div class="text-gray-500">Skipped</div>
              </div>
              <div class="text-center">
                <div class="font-medium text-red-600">{{ job.records_failed || 0 }}</div>
                <div class="text-gray-500">Failed</div>
              </div>
            </div>

            <!-- Last Updated -->
            <div class="mt-2 text-xs text-gray-400">
              Last updated: {{ formatRelativeTime(job.last_updated) }}
            </div>
          </div>
        </div>
      </div>

      <!-- Connection Status -->
      <div v-if="connectionStatus !== 'connected'" class="bg-yellow-50 border-t border-yellow-200 px-4 py-2">
        <div class="flex items-center space-x-2 text-xs text-yellow-800">
          <ExclamationTriangleIcon class="w-4 h-4" />
          <span>Real-time updates unavailable</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useImportJobMonitoring } from '@/Composables/useImportJobMonitoring.js'
import {
  ChevronUpIcon,
  ChevronDownIcon,
  XMarkIcon,
  ArrowPathIcon,
  ClockIcon,
  ExclamationTriangleIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
  autoShow: {
    type: Boolean,
    default: true
  }
})

const emit = defineEmits(['job-details', 'monitor-closed'])

// Job monitoring composable
const {
  activeJobs,
  connectionStatus,
  getAllActiveJobs,
  getActiveJobsCount,
  fetchActiveJobs,
  cancelJob: cancelJobAPI
} = useImportJobMonitoring()

// Component state
const showMonitor = ref(false)
const minimized = ref(false)
const refreshing = ref(false)
const cancelling = ref(new Set())

// Computed properties
const activeJobsCount = computed(() => getActiveJobsCount())
const sortedActiveJobs = computed(() => {
  return getAllActiveJobs().sort((a, b) => {
    // Sort by status (running first) then by start time
    const statusPriority = { running: 0, pending: 1, completed: 2, failed: 3 }
    const aPriority = statusPriority[a.status] || 4
    const bPriority = statusPriority[b.status] || 4
    
    if (aPriority !== bPriority) {
      return aPriority - bPriority
    }
    
    return new Date(b.started_at || b.created_at) - new Date(a.started_at || a.created_at)
  })
})

const statusColor = computed(() => {
  if (activeJobsCount.value === 0) return 'bg-gray-300'
  if (connectionStatus.value !== 'connected') return 'bg-yellow-400'
  
  const jobs = getAllActiveJobs()
  const hasRunning = jobs.some(job => job.status === 'running')
  const hasFailed = jobs.some(job => job.status === 'failed')
  
  if (hasFailed) return 'bg-red-400'
  if (hasRunning) return 'bg-green-400'
  return 'bg-gray-300'
})

// Methods
const getStatusBadgeClass = (status) => {
  const classes = {
    pending: 'bg-yellow-100 text-yellow-800',
    running: 'bg-blue-100 text-blue-800',
    completed: 'bg-green-100 text-green-800',
    failed: 'bg-red-100 text-red-800',
    cancelled: 'bg-gray-100 text-gray-800'
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
}

const getProgressBarClass = (status) => {
  const classes = {
    pending: 'bg-yellow-400',
    running: 'bg-blue-500',
    completed: 'bg-green-500',
    failed: 'bg-red-500',
    cancelled: 'bg-gray-400'
  }
  return classes[status] || 'bg-gray-400'
}

const formatRelativeTime = (timestamp) => {
  if (!timestamp) return 'Unknown'
  
  const now = new Date()
  const time = new Date(timestamp)
  const diff = now - time
  const seconds = Math.floor(diff / 1000)
  
  if (seconds < 60) return `${seconds}s ago`
  if (seconds < 3600) return `${Math.floor(seconds / 60)}m ago`
  if (seconds < 86400) return `${Math.floor(seconds / 3600)}h ago`
  return time.toLocaleDateString()
}

const refreshJobs = async () => {
  refreshing.value = true
  try {
    await fetchActiveJobs()
  } finally {
    refreshing.value = false
  }
}

const cancelJob = async (jobId) => {
  cancelling.value.add(jobId)
  try {
    await cancelJobAPI(jobId)
  } catch (error) {
    console.error('Failed to cancel job:', error)
    // Could show an error notification here
  } finally {
    cancelling.value.delete(jobId)
  }
}

const viewJobDetails = (job) => {
  emit('job-details', job)
}

const closeMonitor = () => {
  showMonitor.value = false
  emit('monitor-closed')
}

// Auto-show logic
const checkAutoShow = () => {
  if (props.autoShow && activeJobsCount.value > 0 && !showMonitor.value) {
    showMonitor.value = true
    minimized.value = false
  }
}

// Event listeners for external events
const handleProgressUpdate = () => {
  checkAutoShow()
}

const handleStatusChange = () => {
  checkAutoShow()
}

// Lifecycle
onMounted(() => {
  // Listen for custom events from the monitoring composable
  document.addEventListener('import-progress-updated', handleProgressUpdate)
  document.addEventListener('import-status-changed', handleStatusChange)
  
  // Check if we should auto-show
  setTimeout(checkAutoShow, 1000)
})

onUnmounted(() => {
  document.removeEventListener('import-progress-updated', handleProgressUpdate)
  document.removeEventListener('import-status-changed', handleStatusChange)
})

// Expose methods for parent components
defineExpose({
  show: () => { showMonitor.value = true },
  hide: () => { showMonitor.value = false },
  minimize: () => { minimized.value = true },
  expand: () => { minimized.value = false }
})
</script>