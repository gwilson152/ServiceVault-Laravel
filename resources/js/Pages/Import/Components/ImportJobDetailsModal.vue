<template>
  <StackedDialog 
    :show="show" 
    @close="$emit('close')"
    title="Import Job Details"
    max-width="4xl"
  >
    <div v-if="job" class="space-y-6">
      <!-- Job Header -->
      <div class="bg-gray-50 rounded-lg p-6">
        <div class="flex items-center justify-between">
          <div>
            <h2 class="text-xl font-semibold text-gray-900">{{ job.profile?.name }}</h2>
            <div class="flex items-center mt-1 space-x-3">
              <p class="text-sm text-gray-600">{{ formatImportType(job.profile) }}</p>
              <span v-if="getTargetType(job.profile)" class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
                {{ formatTargetType(getTargetType(job.profile)) }}
              </span>
            </div>
          </div>
          <span :class="getJobStatusClass(job.status)" class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium">
            {{ job.status }}
          </span>
        </div>
        
        <div class="mt-4 grid grid-cols-2 sm:grid-cols-4 gap-4">
          <div>
            <dt class="text-sm font-medium text-gray-500">Started</dt>
            <dd class="mt-1 text-sm text-gray-900">{{ formatDateTime(job.started_at) }}</dd>
          </div>
          <div v-if="job.completed_at">
            <dt class="text-sm font-medium text-gray-500">Completed</dt>
            <dd class="mt-1 text-sm text-gray-900">{{ formatDateTime(job.completed_at) }}</dd>
          </div>
          <div>
            <dt class="text-sm font-medium text-gray-500">Duration</dt>
            <dd class="mt-1 text-sm text-gray-900">{{ formatDuration(job.started_at, job.completed_at) }}</dd>
          </div>
          <div>
            <dt class="text-sm font-medium text-gray-500">Created By</dt>
            <dd class="mt-1 text-sm text-gray-900">{{ job.created_by?.name || 'System' }}</dd>
          </div>
        </div>
      </div>

      <!-- Progress -->
      <div class="bg-white border border-gray-200 rounded-lg p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Progress</h3>
        
        <div class="space-y-4">
          <div>
            <div class="flex items-center justify-between text-sm">
              <span class="font-medium text-gray-700">Overall Progress</span>
              <span class="text-gray-600">{{ job.progress_percentage || 0 }}%</span>
            </div>
            <div class="mt-2 w-full bg-gray-200 rounded-full h-2">
              <div
                :class="getProgressBarClass(job.status)"
                class="h-2 rounded-full transition-all duration-300"
                :style="{ width: `${job.progress_percentage || 0}%` }"
              ></div>
            </div>
            <p v-if="job.current_operation" class="mt-2 text-sm text-gray-600">
              {{ job.current_operation }}
            </p>
          </div>

          <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 pt-4 border-t border-gray-200">
            <div class="text-center">
              <div class="text-2xl font-bold text-green-600">{{ job.records_imported || 0 }}</div>
              <div class="text-sm text-gray-600">Imported</div>
            </div>
            <div class="text-center">
              <div class="text-2xl font-bold text-red-600">{{ job.records_failed || 0 }}</div>
              <div class="text-sm text-gray-600">Failed</div>
            </div>
            <div class="text-center">
              <div class="text-2xl font-bold text-yellow-600">{{ job.records_skipped || 0 }}</div>
              <div class="text-sm text-gray-600">Skipped</div>
            </div>
            <div class="text-center">
              <div class="text-2xl font-bold text-blue-600">{{ job.records_processed || 0 }}</div>
              <div class="text-sm text-gray-600">Processed</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Query Configuration -->
      <div v-if="job.profile?.configuration" class="bg-white border border-gray-200 rounded-lg p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Query Configuration</h3>
        
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <!-- Configuration Summary -->
          <div class="space-y-4">
            <div>
              <h4 class="text-sm font-medium text-gray-900">Base Table</h4>
              <p class="text-sm text-gray-700 font-mono bg-gray-50 px-2 py-1 rounded">
                {{ job.profile.configuration.base_table || 'Not specified' }}
              </p>
            </div>
            
            <div v-if="job.profile.configuration.joins?.length">
              <h4 class="text-sm font-medium text-gray-900">Joins ({{ job.profile.configuration.joins.length }})</h4>
              <div class="space-y-1">
                <div v-for="join in job.profile.configuration.joins.slice(0, 3)" :key="`${join.table}-${join.leftColumn}`" 
                     class="text-xs bg-gray-50 p-2 rounded font-mono">
                  {{ join.type }} JOIN {{ join.table }}
                </div>
                <div v-if="job.profile.configuration.joins.length > 3" class="text-xs text-gray-500">
                  +{{ job.profile.configuration.joins.length - 3 }} more joins
                </div>
              </div>
            </div>

            <div v-if="job.profile.configuration.fields?.length">
              <h4 class="text-sm font-medium text-gray-900">Field Mappings ({{ job.profile.configuration.fields.length }})</h4>
              <div class="text-xs text-gray-600">
                {{ job.profile.configuration.fields.length }} field{{ job.profile.configuration.fields.length === 1 ? '' : 's' }} mapped to {{ formatTargetType(job.profile.configuration.target_type) }}
              </div>
            </div>

            <div v-if="job.profile.configuration.filters?.length">
              <h4 class="text-sm font-medium text-gray-900">Filters ({{ job.profile.configuration.filters.length }})</h4>
              <div class="text-xs text-gray-600">
                {{ job.profile.configuration.filters.length }} filter condition{{ job.profile.configuration.filters.length === 1 ? '' : 's' }} applied
              </div>
            </div>
          </div>
          
          <!-- Raw Configuration (collapsed by default) -->
          <div>
            <details class="group">
              <summary class="cursor-pointer text-sm font-medium text-gray-900 hover:text-gray-700">
                Raw Configuration
              </summary>
              <div class="mt-2 bg-gray-50 rounded-md p-3">
                <pre class="text-xs text-gray-700 whitespace-pre-wrap overflow-x-auto">{{ JSON.stringify(job.profile.configuration, null, 2) }}</pre>
              </div>
            </details>
          </div>
        </div>
      </div>

      <!-- Import Options (fallback for old jobs) -->
      <div v-else-if="job.import_options" class="bg-white border border-gray-200 rounded-lg p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Import Options</h3>
        <div class="bg-gray-50 rounded-md p-3">
          <pre class="text-sm text-gray-700 whitespace-pre-wrap">{{ JSON.stringify(job.import_options, null, 2) }}</pre>
        </div>
      </div>

      <!-- Errors -->
      <div v-if="job.errors" class="bg-white border border-red-200 rounded-lg p-6">
        <h3 class="text-lg font-medium text-red-900 mb-4">Errors</h3>
        <div class="bg-red-50 border border-red-200 rounded-md p-3">
          <div class="text-sm text-red-700 whitespace-pre-wrap">{{ job.errors }}</div>
        </div>
      </div>

      <!-- Metadata -->
      <div v-if="job.metadata" class="bg-white border border-gray-200 rounded-lg p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Metadata</h3>
        <div class="bg-gray-50 rounded-md p-3">
          <pre class="text-sm text-gray-700 whitespace-pre-wrap">{{ JSON.stringify(job.metadata, null, 2) }}</pre>
        </div>
      </div>

      <!-- Live Updates for Running Jobs -->
      <div v-if="job.status === 'running'" class="bg-blue-50 border border-blue-200 rounded-lg p-4">
        <div class="flex">
          <div class="flex-shrink-0">
            <div class="w-5 h-5 animate-spin rounded-full border-b-2 border-blue-600"></div>
          </div>
          <div class="ml-3">
            <h3 class="text-sm font-medium text-blue-800">Import In Progress</h3>
            <p class="mt-1 text-sm text-blue-700">
              This import is currently running. Status updates automatically.
            </p>
          </div>
        </div>
      </div>

      <!-- Actions -->
      <div class="border-t border-gray-200 pt-6">
        <div class="flex items-center justify-between">
          <div>
            <button
              v-if="job.status === 'running'"
              @click="cancelJob"
              class="inline-flex items-center px-4 py-2 border border-red-300 shadow-sm text-sm font-medium rounded-md text-red-700 bg-red-50 hover:bg-red-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
            >
              <XMarkIcon class="w-4 h-4 mr-2" />
              Cancel Import
            </button>
          </div>
          
          <div class="flex items-center space-x-3">
            <button
              v-if="job.status === 'completed'"
              @click="downloadReport"
              class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
            >
              <ArrowDownTrayIcon class="w-4 h-4 mr-2" />
              Download Report
            </button>
            <button
              type="button"
              @click="$emit('close')"
              class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
            >
              Close
            </button>
          </div>
        </div>
      </div>
    </div>

    <div v-else class="text-center py-12">
      <ExclamationTriangleIcon class="mx-auto h-12 w-12 text-gray-400" />
      <h3 class="mt-2 text-sm font-medium text-gray-900">No job selected</h3>
      <p class="mt-1 text-sm text-gray-500">Select an import job to view details.</p>
    </div>
  </StackedDialog>
</template>

<script setup>
import { computed } from 'vue'
import StackedDialog from '@/Components/StackedDialog.vue'
import {
  XMarkIcon,
  ArrowDownTrayIcon,
  ExclamationTriangleIcon,
} from '@heroicons/vue/24/outline'
import { useImportQueries } from '@/Composables/queries/useImportQueries.js'

const props = defineProps({
  show: Boolean,
  job: {
    type: Object,
    default: null
  }
})

const emit = defineEmits(['close'])

// Composables
const { cancelJob: cancelJobMutation, useJobStatus } = useImportQueries()

// Live job status updates for running jobs
const { data: liveJobStatus } = useJobStatus(computed(() => props.job?.id))

// Use live status if available, otherwise use prop data
const job = computed(() => {
  if (props.job?.status === 'running' && liveJobStatus.value) {
    return { ...props.job, ...liveJobStatus.value }
  }
  return props.job
})

// Methods
const getJobStatusClass = (status) => {
  switch (status) {
    case 'completed':
      return 'bg-green-100 text-green-800'
    case 'failed':
      return 'bg-red-100 text-red-800'
    case 'running':
      return 'bg-blue-100 text-blue-800'
    case 'cancelled':
      return 'bg-gray-100 text-gray-800'
    default:
      return 'bg-yellow-100 text-yellow-800'
  }
}

const getProgressBarClass = (status) => {
  switch (status) {
    case 'completed':
      return 'bg-green-600'
    case 'failed':
      return 'bg-red-600'
    case 'running':
      return 'bg-blue-600'
    default:
      return 'bg-gray-600'
  }
}

const formatDateTime = (dateString) => {
  if (!dateString) return '-'
  const date = new Date(dateString)
  return date.toLocaleDateString() + ' ' + date.toLocaleTimeString()
}

const formatDuration = (startString, endString) => {
  if (!startString) return '-'
  
  const start = new Date(startString)
  const end = endString ? new Date(endString) : new Date()
  const diffMs = end - start
  
  if (diffMs < 1000) return '< 1 second'
  
  const diffSeconds = Math.floor(diffMs / 1000)
  const diffMinutes = Math.floor(diffSeconds / 60)
  const diffHours = Math.floor(diffMinutes / 60)
  
  if (diffHours > 0) {
    return `${diffHours}h ${diffMinutes % 60}m ${diffSeconds % 60}s`
  } else if (diffMinutes > 0) {
    return `${diffMinutes}m ${diffSeconds % 60}s`
  } else {
    return `${diffSeconds}s`
  }
}

const cancelJob = async () => {
  if (!props.job) return
  
  if (confirm('Are you sure you want to cancel this import? This action cannot be undone.')) {
    try {
      await cancelJobMutation(props.job.id)
      // Job status will update automatically via live updates
    } catch (error) {
      console.error('Failed to cancel job:', error)
    }
  }
}

const downloadReport = () => {
  // TODO: Implement report download
  console.log('Download report for job:', props.job.id)
}

const formatImportType = (profile) => {
  if (!profile) return 'Unknown'
  
  if (profile.configuration && Object.keys(profile.configuration).length > 0) {
    return 'Query Builder Import'
  } else if (profile.template_id) {
    return 'Template-Based Import'
  } else {
    return 'Legacy Import'
  }
}

const getTargetType = (profile) => {
  return profile?.configuration?.target_type || null
}

const formatTargetType = (targetType) => {
  if (!targetType) return 'Unknown'
  
  return targetType
    .replace(/_/g, ' ')
    .split(' ')
    .map(word => word.charAt(0).toUpperCase() + word.slice(1))
    .join(' ')
}
</script>