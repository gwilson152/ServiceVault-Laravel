<template>
  <StackedDialog 
    :show="show" 
    @close="handleClose"
    :closable="!isImportRunning"
    class="max-w-4xl"
  >
    <template #title>
      <div class="flex items-center space-x-3">
        <div class="flex items-center justify-center w-8 h-8 rounded-full"
             :class="statusIconClasses">
          <component :is="statusIcon" class="w-5 h-5" />
        </div>
        <div>
          <h3 class="text-lg font-medium text-gray-900">FreeScout Import Progress</h3>
          <p class="text-sm text-gray-500">{{ jobStatusMessage }}</p>
        </div>
      </div>
    </template>

    <div class="space-y-6">
      <!-- Overall Progress Bar -->
      <div class="bg-white rounded-lg border border-gray-200 p-6">
        <div class="flex items-center justify-between mb-4">
          <div class="flex items-center space-x-3">
            <h4 class="text-sm font-medium text-gray-900">Overall Progress</h4>
            <div class="flex items-center space-x-2">
              <div v-if="isImportRunning" class="animate-spin rounded-full h-4 w-4 border-b-2 border-indigo-600"></div>
              <span class="text-sm text-gray-500">{{ progressPercentage }}% complete</span>
            </div>
          </div>
          <div class="text-sm text-gray-500">
            {{ formatDuration(estimatedDuration) }} {{ estimatedDuration ? 'estimated' : '' }}
          </div>
        </div>
        
        <div class="w-full bg-gray-200 rounded-full h-2 mb-4">
          <div 
            class="h-2 rounded-full transition-all duration-300 ease-out"
            :class="progressBarClasses"
            :style="{ width: progressPercentage + '%' }"
          ></div>
        </div>
        
        <div class="flex items-center justify-between text-sm text-gray-600">
          <span>{{ currentStepMessage }}</span>
          <span v-if="job?.records_processed">
            {{ job.records_processed.toLocaleString() }} records processed
          </span>
        </div>
      </div>

      <!-- Step-by-step Progress -->
      <div class="bg-white rounded-lg border border-gray-200 p-6">
        <h4 class="text-sm font-medium text-gray-900 mb-4">Import Steps</h4>
        <div class="space-y-4">
          <div 
            v-for="step in importSteps" 
            :key="step.key"
            class="flex items-start space-x-3"
          >
            <div class="flex items-center justify-center w-6 h-6 rounded-full flex-shrink-0 mt-0.5"
                 :class="getStepStatusClasses(step)">
              <component :is="getStepIcon(step)" class="w-3 h-3" />
            </div>
            <div class="flex-1 min-w-0">
              <div class="flex items-center justify-between">
                <p class="text-sm font-medium text-gray-900">{{ step.title }}</p>
                <span v-if="step.count !== null" class="text-xs text-gray-500">
                  {{ step.count.toLocaleString() }} {{ step.count === 1 ? step.singular : step.plural }}
                </span>
              </div>
              <p class="text-xs text-gray-500 mt-1">{{ step.description }}</p>
              <div v-if="step.details && Object.keys(step.details).length > 0" 
                   class="mt-2 text-xs text-gray-600 space-y-1">
                <div v-for="(value, key) in step.details" :key="key" class="flex items-center space-x-2">
                  <span class="font-medium">{{ formatDetailKey(key) }}:</span>
                  <span>{{ formatDetailValue(value) }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Live Statistics -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
        <div v-for="stat in liveStats" :key="stat.label" 
             class="bg-white rounded-lg border border-gray-200 p-4 text-center">
          <div class="text-2xl font-bold" :class="stat.colorClass">
            {{ stat.value.toLocaleString() }}
          </div>
          <div class="text-sm text-gray-600">{{ stat.label }}</div>
        </div>
      </div>

      <!-- Configuration Summary -->
      <div class="bg-gray-50 rounded-lg p-4">
        <h4 class="text-sm font-medium text-gray-900 mb-3">Import Configuration</h4>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 text-xs">
          <div>
            <span class="font-medium text-gray-700">Account Strategy:</span>
            <span class="ml-2 text-gray-600">{{ formatAccountStrategy(importConfig?.account_strategy) }}</span>
          </div>
          <div>
            <span class="font-medium text-gray-700">Agent Access:</span>
            <span class="ml-2 text-gray-600">{{ formatAgentAccess(importConfig?.agent_access) }}</span>
          </div>
          <div>
            <span class="font-medium text-gray-700">Billing Strategy:</span>
            <span class="ml-2 text-gray-600">{{ formatBillingStrategy(importConfig?.billing_rate_strategy) }}</span>
          </div>
          <div>
            <span class="font-medium text-gray-700">Sync Mode:</span>
            <span class="ml-2 text-gray-600">{{ formatSyncStrategy(importConfig?.sync_strategy) }}</span>
          </div>
          <div>
            <span class="font-medium text-gray-700">Duplicate Detection:</span>
            <span class="ml-2 text-gray-600">{{ formatDuplicateDetection(importConfig?.duplicate_detection) }}</span>
          </div>
          <div>
            <span class="font-medium text-gray-700">Time Entry Defaults:</span>
            <span class="ml-2 text-gray-600">
              {{ importConfig?.time_entry_defaults?.billable ? 'Billable' : 'Non-billable' }}, 
              {{ importConfig?.time_entry_defaults?.approved ? 'Auto-approved' : 'Pending approval' }}
            </span>
          </div>
        </div>
      </div>

      <!-- Error Log (if any) -->
      <div v-if="job?.error_log && job.error_log.length > 0" 
           class="bg-red-50 border border-red-200 rounded-lg p-4">
        <h4 class="text-sm font-medium text-red-900 mb-2">Import Issues</h4>
        <div class="space-y-2">
          <div v-for="(error, index) in job.error_log" :key="index" 
               class="text-sm text-red-800 flex items-start space-x-2">
            <ExclamationTriangleIcon class="w-4 h-4 text-red-600 mt-0.5 flex-shrink-0" />
            <span>{{ error }}</span>
          </div>
        </div>
      </div>
    </div>

    <template #actions>
      <div class="flex items-center justify-between w-full">
        <div class="flex items-center space-x-4 text-sm text-gray-500">
          <span>Started: {{ formatTimestamp(job?.started_at) }}</span>
          <span v-if="job?.completed_at">Completed: {{ formatTimestamp(job?.completed_at) }}</span>
          <span v-if="importDuration">Duration: {{ formatDuration(importDuration) }}</span>
        </div>
        <div class="flex items-center space-x-3">
          <button
            v-if="job?.status === 'completed'"
            @click="viewImportResults"
            class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
          >
            View Results
          </button>
          <button
            @click="handleClose"
            :disabled="isImportRunning"
            class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
            :class="isImportRunning ? 
              'text-gray-400 bg-gray-100 cursor-not-allowed' : 
              'text-white bg-indigo-600 hover:bg-indigo-700'"
          >
            {{ isImportRunning ? 'Import Running...' : 'Close' }}
          </button>
        </div>
      </div>
    </template>
  </StackedDialog>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { 
  CheckIcon, 
  ClockIcon, 
  ExclamationTriangleIcon, 
  XMarkIcon,
  ArrowPathIcon,
  PlayIcon,
  CheckCircleIcon,
  XCircleIcon
} from '@heroicons/vue/24/outline'
import StackedDialog from '@/Components/StackedDialog.vue'

// Props
const props = defineProps({
  show: {
    type: Boolean,
    default: false
  },
  job: {
    type: Object,
    default: null
  },
  importConfig: {
    type: Object,
    default: null
  }
})

// Emits
const emit = defineEmits(['close', 'view-results', 'job-updated'])

// Reactive data
const currentStep = ref('')
const stepDetails = ref({})
const progressPercentage = ref(0)
const estimatedDuration = ref(null)
const websocketChannel = ref(null)

// Get current user for WebSocket authentication
const page = usePage()
const user = computed(() => page.props.auth?.user)

// Import steps configuration
const importSteps = computed(() => [
  {
    key: 'accounts',
    title: 'Create Accounts',
    description: 'Setting up Service Vault accounts from FreeScout mailboxes or domain mappings',
    status: getStepStatus('accounts'),
    count: stepDetails.value.accounts?.accounts_created ?? null,
    details: stepDetails.value.accounts || {},
    singular: 'account',
    plural: 'accounts'
  },
  {
    key: 'users',
    title: 'Import Users',
    description: 'Processing FreeScout agents and customers as Service Vault users',
    status: getStepStatus('users'),
    count: stepDetails.value.users?.total_users ?? null,
    details: stepDetails.value.users || {},
    singular: 'user',
    plural: 'users'
  },
  {
    key: 'tickets',
    title: 'Convert Conversations',
    description: 'Transforming FreeScout conversations into Service Vault tickets',
    status: getStepStatus('tickets'),
    count: stepDetails.value.tickets?.tickets_created ?? null,
    details: stepDetails.value.tickets || {},
    singular: 'ticket',
    plural: 'tickets'
  },
  {
    key: 'comments',
    title: 'Import Comments',
    description: 'Converting conversation threads to ticket comments with proper attribution',
    status: getStepStatus('comments'),
    count: stepDetails.value.comments?.comments_created ?? null,
    details: stepDetails.value.comments || {},
    singular: 'comment',
    plural: 'comments'
  },
  {
    key: 'time_entries',
    title: 'Process Time Entries',
    description: 'Importing time tracking data with billing rate assignments',
    status: getStepStatus('time_entries'),
    count: stepDetails.value.time_entries?.time_entries_created ?? null,
    details: stepDetails.value.time_entries || {},
    singular: 'time entry',
    plural: 'time entries'
  }
])

// Computed properties
const isImportRunning = computed(() => {
  return props.job?.status === 'running' || props.job?.status === 'pending'
})

const jobStatusMessage = computed(() => {
  if (!props.job) return 'No import job selected'
  
  const statusMessages = {
    pending: 'Import job is queued and waiting to start',
    running: 'Import is currently in progress',
    completed: 'Import completed successfully',
    failed: 'Import failed with errors',
    cancelled: 'Import was cancelled'
  }
  
  return statusMessages[props.job.status] || props.job.status
})

const currentStepMessage = computed(() => {
  if (!props.job?.message) return 'Waiting for import to start...'
  return props.job.message
})

const statusIcon = computed(() => {
  const iconMap = {
    pending: ClockIcon,
    running: ArrowPathIcon,
    completed: CheckCircleIcon,
    failed: XCircleIcon,
    cancelled: XMarkIcon
  }
  return iconMap[props.job?.status] || ClockIcon
})

const statusIconClasses = computed(() => {
  const classMap = {
    pending: 'bg-yellow-100 text-yellow-600',
    running: 'bg-blue-100 text-blue-600',
    completed: 'bg-green-100 text-green-600',
    failed: 'bg-red-100 text-red-600',
    cancelled: 'bg-gray-100 text-gray-600'
  }
  return classMap[props.job?.status] || 'bg-gray-100 text-gray-600'
})

const progressBarClasses = computed(() => {
  const classMap = {
    pending: 'bg-yellow-500',
    running: 'bg-blue-500',
    completed: 'bg-green-500',
    failed: 'bg-red-500',
    cancelled: 'bg-gray-500'
  }
  return classMap[props.job?.status] || 'bg-blue-500'
})

const liveStats = computed(() => [
  {
    label: 'Accounts',
    value: stepDetails.value.accounts?.accounts_created ?? 0,
    colorClass: 'text-indigo-600'
  },
  {
    label: 'Users',
    value: stepDetails.value.users?.total_users ?? 0,
    colorClass: 'text-blue-600'
  },
  {
    label: 'Tickets',
    value: stepDetails.value.tickets?.tickets_created ?? 0,
    colorClass: 'text-green-600'
  },
  {
    label: 'Comments',
    value: stepDetails.value.comments?.comments_created ?? 0,
    colorClass: 'text-yellow-600'
  },
  {
    label: 'Time Entries',
    value: stepDetails.value.time_entries?.time_entries_created ?? 0,
    colorClass: 'text-purple-600'
  }
])

const importDuration = computed(() => {
  if (!props.job?.started_at) return null
  
  const endTime = props.job?.completed_at ? new Date(props.job.completed_at) : new Date()
  const startTime = new Date(props.job.started_at)
  
  return Math.floor((endTime - startTime) / 1000) // Duration in seconds
})

// Watch for job progress updates
watch(() => props.job?.progress, (newProgress) => {
  if (newProgress !== null) {
    progressPercentage.value = newProgress
  }
})

// Methods
function getStepStatus(stepKey) {
  if (currentStep.value === stepKey) {
    return 'active'
  }
  
  if (stepDetails.value[stepKey] && stepDetails.value[stepKey].step === 'completed') {
    return 'completed'
  }
  
  // Check if step has started based on having details
  if (stepDetails.value[stepKey] && Object.keys(stepDetails.value[stepKey]).length > 0) {
    return 'active'
  }
  
  return 'pending'
}

function getStepStatusClasses(step) {
  const classMap = {
    pending: 'bg-gray-100 text-gray-400',
    active: 'bg-blue-100 text-blue-600',
    completed: 'bg-green-100 text-green-600'
  }
  return classMap[step.status] || classMap.pending
}

function getStepIcon(step) {
  const iconMap = {
    pending: ClockIcon,
    active: ArrowPathIcon,
    completed: CheckIcon
  }
  return iconMap[step.status] || ClockIcon
}

function formatDetailKey(key) {
  return key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())
}

function formatDetailValue(value) {
  if (typeof value === 'boolean') {
    return value ? 'Yes' : 'No'
  }
  if (typeof value === 'number') {
    return value.toLocaleString()
  }
  return value
}

function formatAccountStrategy(strategy) {
  const strategies = {
    map_mailboxes: 'Map Mailboxes to Accounts',
    domain_mapping: 'Use Domain Mapping'
  }
  return strategies[strategy] || strategy
}

function formatAgentAccess(access) {
  const accessTypes = {
    all_accounts: 'All Accounts',
    primary_account: 'Primary Account Only'
  }
  return accessTypes[access] || access
}

function formatBillingStrategy(strategy) {
  const strategies = {
    auto_select: 'Auto-select Rates',
    no_rate: 'No Billing Rates',
    fixed_rate: 'Fixed Rate'
  }
  return strategies[strategy] || strategy
}

function formatSyncStrategy(strategy) {
  const strategies = {
    create_only: 'Create Only',
    update_only: 'Update Only',
    upsert: 'Create or Update'
  }
  return strategies[strategy] || strategy
}

function formatDuplicateDetection(detection) {
  const detectionTypes = {
    external_id: 'External ID Matching',
    content_match: 'Content Matching'
  }
  return detectionTypes[detection] || detection
}

function formatTimestamp(timestamp) {
  if (!timestamp) return 'N/A'
  return new Date(timestamp).toLocaleString()
}

function formatDuration(seconds) {
  if (!seconds) return null
  
  const hours = Math.floor(seconds / 3600)
  const minutes = Math.floor((seconds % 3600) / 60)
  const secs = seconds % 60
  
  if (hours > 0) {
    return `${hours}h ${minutes}m ${secs}s`
  } else if (minutes > 0) {
    return `${minutes}m ${secs}s`
  } else {
    return `${secs}s`
  }
}

function handleClose() {
  if (!isImportRunning.value) {
    emit('close')
  }
}

function viewImportResults() {
  emit('view-results', props.job)
}

// WebSocket Integration
const connectToWebSocket = () => {
  if (!props.job?.id || !window.Echo) return
  
  // Connect to the FreeScout import progress channel
  websocketChannel.value = window.Echo.private(`import.freescout.job.${props.job.id}`)
    .listen('freescout.import.progress', (data) => {
      handleProgressUpdate(data)
    })
    .listen('import.job.status.changed', (data) => {
      handleStatusUpdate(data)
    })

  // Also listen on user channel for general updates
  if (user.value?.id) {
    window.Echo.private(`user.${user.value.id}`)
      .listen('freescout.import.progress', (data) => {
        if (data.job_id === props.job?.id) {
          handleProgressUpdate(data)
        }
      })
  }
}

const disconnectFromWebSocket = () => {
  if (websocketChannel.value) {
    websocketChannel.value.stopListening('freescout.import.progress')
    websocketChannel.value.stopListening('import.job.status.changed')
    window.Echo.leave(`import.freescout.job.${props.job?.id}`)
    websocketChannel.value = null
  }
}

const handleProgressUpdate = (data) => {
  // Update progress from WebSocket event
  if (data.progress !== undefined) {
    progressPercentage.value = data.progress
  }
  
  if (data.current_step) {
    currentStep.value = data.current_step
  }
  
  if (data.step_details) {
    stepDetails.value[data.current_step] = { 
      ...stepDetails.value[data.current_step], 
      ...data.step_details 
    }
  }
  
  // Update job data if provided
  if (data.records_processed !== undefined) {
    // Emit event to parent to update job data
    emit('job-updated', {
      ...props.job,
      progress: data.progress,
      message: data.message,
      records_processed: data.records_processed,
      records_imported: data.records_imported,
      records_updated: data.records_updated,
      records_skipped: data.records_skipped,
      records_failed: data.records_failed
    })
  }
}

const handleStatusUpdate = (data) => {
  // Handle job status changes
  if (data.job_id === props.job?.id) {
    emit('job-updated', {
      ...props.job,
      status: data.status,
      progress: data.progress_percentage,
      message: data.current_operation,
      completed_at: data.completed_at
    })
    
    // If job is completed or failed, we can stop listening
    if (data.status === 'completed' || data.status === 'failed') {
      setTimeout(() => {
        disconnectFromWebSocket()
      }, 5000) // Keep listening for a few more seconds for final updates
    }
  }
}

// Watch for job changes to connect/disconnect WebSocket
watch(() => props.job?.id, (newJobId, oldJobId) => {
  if (oldJobId && oldJobId !== newJobId) {
    disconnectFromWebSocket()
  }
  
  if (newJobId && props.show) {
    connectToWebSocket()
  }
})

// Watch for show prop to connect/disconnect
watch(() => props.show, (isShown) => {
  if (isShown && props.job?.id) {
    connectToWebSocket()
  } else if (!isShown) {
    disconnectFromWebSocket()
  }
})

// Lifecycle hooks
onMounted(() => {
  if (props.show && props.job?.id) {
    connectToWebSocket()
  }
})

onUnmounted(() => {
  disconnectFromWebSocket()
})

// Public method to update progress (called from parent component or WebSocket)
function updateProgress(step, details) {
  currentStep.value = step
  if (details) {
    stepDetails.value[step] = { ...stepDetails.value[step], ...details }
  }
}

// Expose method for parent component
defineExpose({
  updateProgress
})
</script>