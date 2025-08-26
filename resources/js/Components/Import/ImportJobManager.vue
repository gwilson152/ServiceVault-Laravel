<template>
  <div class="space-y-6">
    <!-- Header with filters and actions -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
      <div>
        <h2 class="text-lg font-medium text-gray-900">Import Jobs</h2>
        <p class="mt-1 text-sm text-gray-500">
          Manage and monitor all FreeScout import jobs
        </p>
      </div>
      <div class="mt-4 sm:mt-0 flex space-x-3">
        <button
          @click="refreshJobs"
          :disabled="isRefreshing"
          class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50"
        >
          <ArrowPathIcon class="w-4 h-4 mr-1" :class="{ 'animate-spin': isRefreshing }" />
          Refresh
        </button>
      </div>
    </div>

    <!-- Filters -->
    <div class="bg-white shadow-sm border border-gray-200 rounded-lg">
      <div class="p-4 border-b border-gray-200">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
          <!-- Status Filter -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
            <select 
              v-model="filters.status"
              @change="applyFilters"
              class="block w-full text-sm border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500"
            >
              <option value="">All Statuses</option>
              <option value="pending">Pending</option>
              <option value="running">Running</option>
              <option value="completed">Completed</option>
              <option value="failed">Failed</option>
              <option value="cancelled">Cancelled</option>
            </select>
          </div>

          <!-- Profile Filter -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Profile</label>
            <select 
              v-model="filters.profile_id"
              @change="applyFilters"
              class="block w-full text-sm border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500"
            >
              <option value="">All Profiles</option>
              <option v-for="profile in profiles" :key="profile.id" :value="profile.id">
                {{ profile.name }}
              </option>
            </select>
          </div>

          <!-- Date Range Filter -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">From Date</label>
            <input 
              v-model="filters.date_from"
              @change="applyFilters"
              type="date"
              class="block w-full text-sm border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500"
            >
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">To Date</label>
            <input 
              v-model="filters.date_to"
              @change="applyFilters"
              type="date"
              class="block w-full text-sm border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500"
            >
          </div>
        </div>
      </div>
    </div>

    <!-- Jobs Table -->
    <div class="bg-white shadow-sm border border-gray-200 rounded-lg overflow-hidden">
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Job
              </th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Profile
              </th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Status
              </th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Progress
              </th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Records
              </th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Duration
              </th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Started
              </th>
              <th scope="col" class="relative px-6 py-3">
                <span class="sr-only">Actions</span>
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-if="isLoading">
              <td colspan="8" class="px-6 py-12 text-center">
                <div class="flex items-center justify-center">
                  <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
                  <span class="ml-3 text-sm text-gray-500">Loading import jobs...</span>
                </div>
              </td>
            </tr>
            <tr v-else-if="filteredJobs.length === 0">
              <td colspan="8" class="px-6 py-12 text-center text-sm text-gray-500">
                No import jobs found matching your criteria.
              </td>
            </tr>
            <tr v-else v-for="job in filteredJobs" :key="job.id" class="hover:bg-gray-50">
              <td class="px-6 py-4 whitespace-nowrap">
                <div>
                  <div class="text-sm font-medium text-gray-900">
                    Job #{{ job.id }}
                  </div>
                  <div class="text-sm text-gray-500">
                    {{ job.import_type || 'FreeScout Import' }}
                  </div>
                </div>
              </td>
              
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm text-gray-900">{{ job.profile?.name || 'Unknown Profile' }}</div>
                <div class="text-sm text-gray-500">{{ job.profile?.instance_url }}</div>
              </td>
              
              <td class="px-6 py-4 whitespace-nowrap">
                <span 
                  class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                  :class="getStatusClasses(job.status)"
                >
                  <component :is="getStatusIcon(job.status)" class="w-3 h-3 mr-1" />
                  {{ formatStatus(job.status) }}
                </span>
              </td>
              
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center space-x-2">
                  <div class="flex-1">
                    <div class="w-full bg-gray-200 rounded-full h-2">
                      <div 
                        class="h-2 rounded-full transition-all duration-300"
                        :class="getProgressBarColor(job.status)"
                        :style="{ width: (job.progress || 0) + '%' }"
                      ></div>
                    </div>
                  </div>
                  <span class="text-xs text-gray-500 w-8">{{ job.progress || 0 }}%</span>
                </div>
                <div class="mt-1 text-xs text-gray-500">
                  {{ job.message || 'No status message' }}
                </div>
              </td>
              
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                <div class="space-y-1">
                  <div class="flex justify-between">
                    <span class="text-gray-500">Processed:</span>
                    <span class="font-medium">{{ (job.records_processed || 0).toLocaleString() }}</span>
                  </div>
                  <div class="flex justify-between">
                    <span class="text-gray-500">Imported:</span>
                    <span class="font-medium text-green-600">{{ (job.records_imported || 0).toLocaleString() }}</span>
                  </div>
                  <div v-if="job.records_failed && job.records_failed > 0" class="flex justify-between">
                    <span class="text-gray-500">Failed:</span>
                    <span class="font-medium text-red-600">{{ job.records_failed.toLocaleString() }}</span>
                  </div>
                </div>
              </td>
              
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                {{ formatDuration(job) }}
              </td>
              
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                <div>{{ formatTimestamp(job.started_at) }}</div>
                <div class="text-xs">by {{ job.creator?.name || 'Unknown' }}</div>
              </td>
              
              <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                <div class="flex items-center space-x-2">
                  <button
                    @click="viewJobDetails(job)"
                    class="text-indigo-600 hover:text-indigo-900 focus:outline-none"
                    title="View Details"
                  >
                    <EyeIcon class="w-4 h-4" />
                  </button>
                  
                  <button
                    v-if="job.status === 'running'"
                    @click="cancelJob(job)"
                    class="text-red-600 hover:text-red-900 focus:outline-none"
                    title="Cancel Job"
                  >
                    <StopIcon class="w-4 h-4" />
                  </button>
                  
                  <button
                    v-if="job.status === 'completed'"
                    @click="downloadJobResults(job)"
                    class="text-green-600 hover:text-green-900 focus:outline-none"
                    title="Download Results"
                  >
                    <ArrowDownTrayIcon class="w-4 h-4" />
                  </button>
                  
                  <button
                    @click="retryJob(job)"
                    v-if="job.status === 'failed'"
                    class="text-yellow-600 hover:text-yellow-900 focus:outline-none"
                    title="Retry Import"
                  >
                    <ArrowPathIcon class="w-4 h-4" />
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      
      <!-- Pagination -->
      <div v-if="pagination.total > pagination.per_page" class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
        <div class="flex items-center justify-between">
          <div class="flex-1 flex justify-between sm:hidden">
            <button
              @click="goToPage(pagination.current_page - 1)"
              :disabled="pagination.current_page <= 1"
              class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              Previous
            </button>
            <button
              @click="goToPage(pagination.current_page + 1)"
              :disabled="pagination.current_page >= pagination.last_page"
              class="relative ml-3 inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              Next
            </button>
          </div>
          <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
            <div>
              <p class="text-sm text-gray-700">
                Showing
                <span class="font-medium">{{ (pagination.current_page - 1) * pagination.per_page + 1 }}</span>
                to
                <span class="font-medium">{{ Math.min(pagination.current_page * pagination.per_page, pagination.total) }}</span>
                of
                <span class="font-medium">{{ pagination.total }}</span>
                results
              </p>
            </div>
            <div>
              <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                <button
                  @click="goToPage(pagination.current_page - 1)"
                  :disabled="pagination.current_page <= 1"
                  class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  <ChevronLeftIcon class="h-5 w-5" />
                </button>
                
                <button
                  v-for="page in getVisiblePages()"
                  :key="page"
                  @click="goToPage(page)"
                  :class="[
                    page === pagination.current_page 
                      ? 'z-10 bg-indigo-50 border-indigo-500 text-indigo-600' 
                      : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50',
                    'relative inline-flex items-center px-4 py-2 border text-sm font-medium'
                  ]"
                >
                  {{ page }}
                </button>
                
                <button
                  @click="goToPage(pagination.current_page + 1)"
                  :disabled="pagination.current_page >= pagination.last_page"
                  class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  <ChevronRightIcon class="h-5 w-5" />
                </button>
              </nav>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Job Details Modal -->
    <ImportJobDetailsModal
      :show="showDetailsModal"
      :job="selectedJob"
      @close="closeDetailsModal"
      @retry="handleRetryJob"
      @download="handleDownloadResults"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import {
  ArrowPathIcon,
  EyeIcon,
  StopIcon,
  ArrowDownTrayIcon,
  ChevronLeftIcon,
  ChevronRightIcon,
  CheckCircleIcon,
  XCircleIcon,
  ClockIcon,
  ExclamationTriangleIcon,
  PlayIcon
} from '@heroicons/vue/24/outline'
import ImportJobDetailsModal from './ImportJobDetailsModal.vue'

// Props
const props = defineProps({
  profiles: {
    type: Array,
    default: () => []
  }
})

// Emits
const emit = defineEmits(['job-selected', 'job-cancelled', 'job-retried'])

// Reactive data
const jobs = ref([])
const isLoading = ref(false)
const isRefreshing = ref(false)
const showDetailsModal = ref(false)
const selectedJob = ref(null)

// Filters
const filters = ref({
  status: '',
  profile_id: '',
  date_from: '',
  date_to: ''
})

// Pagination
const pagination = ref({
  current_page: 1,
  per_page: 20,
  total: 0,
  last_page: 1
})

// Computed
const filteredJobs = computed(() => {
  // Note: In a real implementation, filtering would be done server-side
  return jobs.value
})

// Methods
const fetchJobs = async (page = 1) => {
  try {
    isLoading.value = true
    
    const params = new URLSearchParams({
      page: page.toString(),
      per_page: pagination.value.per_page.toString(),
      ...filters.value
    })

    const response = await fetch(`/api/import/jobs?${params}`, {
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
      }
    })

    if (!response.ok) {
      throw new Error('Failed to fetch jobs')
    }

    const result = await response.json()
    
    if (result.success) {
      jobs.value = result.data
      pagination.value = {
        current_page: result.current_page,
        per_page: result.per_page,
        total: result.total,
        last_page: result.last_page
      }
    }
  } catch (error) {
    console.error('Error fetching import jobs:', error)
    // Show error toast or notification
  } finally {
    isLoading.value = false
    isRefreshing.value = false
  }
}

const refreshJobs = () => {
  isRefreshing.value = true
  fetchJobs(pagination.value.current_page)
}

const applyFilters = () => {
  fetchJobs(1) // Reset to first page when applying filters
}

const goToPage = (page) => {
  if (page >= 1 && page <= pagination.value.last_page) {
    fetchJobs(page)
  }
}

const getVisiblePages = () => {
  const current = pagination.value.current_page
  const last = pagination.value.last_page
  const delta = 2
  
  const left = Math.max(1, current - delta)
  const right = Math.min(last, current + delta)
  
  const pages = []
  for (let i = left; i <= right; i++) {
    pages.push(i)
  }
  
  return pages
}

const viewJobDetails = (job) => {
  selectedJob.value = job
  showDetailsModal.value = true
}

const closeDetailsModal = () => {
  showDetailsModal.value = false
  selectedJob.value = null
}

const cancelJob = async (job) => {
  if (confirm(`Are you sure you want to cancel import job #${job.id}?`)) {
    try {
      const response = await fetch(`/api/import/jobs/${job.id}/cancel`, {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
      })
      
      if (response.ok) {
        emit('job-cancelled', job)
        refreshJobs()
      }
    } catch (error) {
      console.error('Error cancelling job:', error)
    }
  }
}

const retryJob = (job) => {
  emit('job-retried', job)
}

const downloadJobResults = (job) => {
  // Implementation would download CSV/Excel export of job results
  console.log('Downloading results for job:', job.id)
}

const handleRetryJob = (job) => {
  retryJob(job)
  closeDetailsModal()
}

const handleDownloadResults = (job) => {
  downloadJobResults(job)
}

// Status helpers
const getStatusClasses = (status) => {
  const classes = {
    pending: 'bg-yellow-100 text-yellow-800',
    running: 'bg-blue-100 text-blue-800',
    completed: 'bg-green-100 text-green-800',
    failed: 'bg-red-100 text-red-800',
    cancelled: 'bg-gray-100 text-gray-800'
  }
  return classes[status] || classes.pending
}

const getStatusIcon = (status) => {
  const icons = {
    pending: ClockIcon,
    running: PlayIcon,
    completed: CheckCircleIcon,
    failed: XCircleIcon,
    cancelled: StopIcon
  }
  return icons[status] || ClockIcon
}

const getProgressBarColor = (status) => {
  const colors = {
    pending: 'bg-yellow-400',
    running: 'bg-blue-500',
    completed: 'bg-green-500',
    failed: 'bg-red-500',
    cancelled: 'bg-gray-400'
  }
  return colors[status] || colors.pending
}

const formatStatus = (status) => {
  const statusMap = {
    pending: 'Pending',
    running: 'Running',
    completed: 'Completed',
    failed: 'Failed',
    cancelled: 'Cancelled'
  }
  return statusMap[status] || status
}

const formatTimestamp = (timestamp) => {
  if (!timestamp) return 'N/A'
  return new Date(timestamp).toLocaleString()
}

const formatDuration = (job) => {
  if (!job.started_at) return 'N/A'
  
  const endTime = job.completed_at ? new Date(job.completed_at) : new Date()
  const startTime = new Date(job.started_at)
  const seconds = Math.floor((endTime - startTime) / 1000)
  
  if (seconds < 60) return `${seconds}s`
  
  const minutes = Math.floor(seconds / 60)
  if (minutes < 60) return `${minutes}m ${seconds % 60}s`
  
  const hours = Math.floor(minutes / 60)
  return `${hours}h ${minutes % 60}m`
}

// Lifecycle
onMounted(() => {
  fetchJobs()
})

// Watchers
watch(() => props.profiles, () => {
  // Refresh jobs when profiles change
  if (props.profiles.length > 0) {
    refreshJobs()
  }
})
</script>