<template>
  <StackedDialog 
    :show="show" 
    @close="$emit('close')"
    class="max-w-4xl"
  >
    <template #title>
      <div class="flex items-center space-x-3">
        <div class="flex items-center justify-center w-8 h-8 rounded-full"
             :class="statusIconClasses">
          <component :is="statusIcon" class="w-5 h-5" />
        </div>
        <div>
          <h3 class="text-lg font-medium text-gray-900">Import Job #{{ job?.id }}</h3>
          <p class="text-sm text-gray-500">{{ job?.import_type || 'FreeScout Import' }}</p>
        </div>
      </div>
    </template>

    <div v-if="job" class="space-y-6">
      <!-- Job Overview -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Status and Progress -->
        <div class="lg:col-span-2 space-y-6">
          <!-- Status Card -->
          <div class="bg-white border border-gray-200 rounded-lg p-4">
            <div class="flex items-center justify-between mb-4">
              <h4 class="text-sm font-medium text-gray-900">Status & Progress</h4>
              <span 
                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                :class="getStatusClasses(job.status)"
              >
                {{ formatStatus(job.status) }}
              </span>
            </div>
            
            <div class="space-y-4">
              <!-- Progress Bar -->
              <div>
                <div class="flex justify-between text-sm text-gray-600 mb-2">
                  <span>{{ job.message || 'No status message' }}</span>
                  <span>{{ job.progress || 0 }}%</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2">
                  <div 
                    class="h-2 rounded-full transition-all duration-300"
                    :class="getProgressBarColor(job.status)"
                    :style="{ width: (job.progress || 0) + '%' }"
                  ></div>
                </div>
              </div>

              <!-- Timestamps -->
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                <div>
                  <span class="text-gray-500">Started:</span>
                  <span class="ml-2 font-medium">{{ formatTimestamp(job.started_at) }}</span>
                </div>
                <div v-if="job.completed_at">
                  <span class="text-gray-500">Completed:</span>
                  <span class="ml-2 font-medium">{{ formatTimestamp(job.completed_at) }}</span>
                </div>
                <div>
                  <span class="text-gray-500">Duration:</span>
                  <span class="ml-2 font-medium">{{ formatDuration(job) }}</span>
                </div>
                <div>
                  <span class="text-gray-500">Created by:</span>
                  <span class="ml-2 font-medium">{{ job.creator?.name || 'Unknown' }}</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Record Statistics -->
          <div class="bg-white border border-gray-200 rounded-lg p-4">
            <h4 class="text-sm font-medium text-gray-900 mb-4">Import Statistics</h4>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
              <div class="text-center">
                <div class="text-2xl font-bold text-gray-900">
                  {{ (job.records_processed || 0).toLocaleString() }}
                </div>
                <div class="text-xs text-gray-500">Processed</div>
              </div>
              <div class="text-center">
                <div class="text-2xl font-bold text-green-600">
                  {{ (job.records_imported || 0).toLocaleString() }}
                </div>
                <div class="text-xs text-gray-500">Imported</div>
              </div>
              <div class="text-center">
                <div class="text-2xl font-bold text-yellow-600">
                  {{ (job.records_skipped || 0).toLocaleString() }}
                </div>
                <div class="text-xs text-gray-500">Skipped</div>
              </div>
              <div class="text-center">
                <div class="text-2xl font-bold text-red-600">
                  {{ (job.records_failed || 0).toLocaleString() }}
                </div>
                <div class="text-xs text-gray-500">Failed</div>
              </div>
            </div>
          </div>
        </div>

        <!-- Profile Information -->
        <div class="space-y-6">
          <div class="bg-white border border-gray-200 rounded-lg p-4">
            <h4 class="text-sm font-medium text-gray-900 mb-4">Import Profile</h4>
            <div class="space-y-3 text-sm">
              <div>
                <span class="text-gray-500">Name:</span>
                <span class="ml-2 font-medium">{{ job.profile?.name || 'Unknown' }}</span>
              </div>
              <div>
                <span class="text-gray-500">URL:</span>
                <span class="ml-2 font-medium text-blue-600">{{ job.profile?.instance_url || 'N/A' }}</span>
              </div>
              <div v-if="job.profile?.description">
                <span class="text-gray-500">Description:</span>
                <p class="mt-1 text-gray-900">{{ job.profile.description }}</p>
              </div>
            </div>
          </div>

          <!-- Quick Actions -->
          <div class="bg-white border border-gray-200 rounded-lg p-4">
            <h4 class="text-sm font-medium text-gray-900 mb-4">Actions</h4>
            <div class="space-y-2">
              <button
                v-if="job.status === 'completed'"
                @click="$emit('download', job)"
                class="w-full inline-flex items-center justify-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
              >
                <ArrowDownTrayIcon class="w-4 h-4 mr-2" />
                Download Results
              </button>
              
              <button
                v-if="job.status === 'failed'"
                @click="$emit('retry', job)"
                class="w-full inline-flex items-center justify-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
              >
                <ArrowPathIcon class="w-4 h-4 mr-2" />
                Retry Import
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Import Configuration -->
      <div v-if="job.import_options" class="bg-white border border-gray-200 rounded-lg p-4">
        <h4 class="text-sm font-medium text-gray-900 mb-4">Import Configuration</h4>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 text-sm">
          <div v-if="job.import_options.account_strategy">
            <span class="text-gray-500">Account Strategy:</span>
            <span class="ml-2 font-medium">{{ formatAccountStrategy(job.import_options.account_strategy) }}</span>
          </div>
          <div v-if="job.import_options.agent_access">
            <span class="text-gray-500">Agent Access:</span>
            <span class="ml-2 font-medium">{{ formatAgentAccess(job.import_options.agent_access) }}</span>
          </div>
          <div v-if="job.import_options.billing_rate_strategy">
            <span class="text-gray-500">Billing Strategy:</span>
            <span class="ml-2 font-medium">{{ formatBillingStrategy(job.import_options.billing_rate_strategy) }}</span>
          </div>
          <div v-if="job.import_options.sync_strategy">
            <span class="text-gray-500">Sync Mode:</span>
            <span class="ml-2 font-medium">{{ formatSyncStrategy(job.import_options.sync_strategy) }}</span>
          </div>
          <div v-if="job.import_options.duplicate_detection">
            <span class="text-gray-500">Duplicate Detection:</span>
            <span class="ml-2 font-medium">{{ formatDuplicateDetection(job.import_options.duplicate_detection) }}</span>
          </div>
          <div v-if="job.import_options.time_entry_defaults">
            <span class="text-gray-500">Time Entry Defaults:</span>
            <span class="ml-2 font-medium">
              {{ job.import_options.time_entry_defaults.billable ? 'Billable' : 'Non-billable' }}, 
              {{ job.import_options.time_entry_defaults.approved ? 'Auto-approved' : 'Pending' }}
            </span>
          </div>
        </div>
      </div>

      <!-- Error Log -->
      <div v-if="job.error_log && job.error_log.length > 0" 
           class="bg-red-50 border border-red-200 rounded-lg p-4">
        <div class="flex items-center mb-3">
          <ExclamationTriangleIcon class="w-5 h-5 text-red-600 mr-2" />
          <h4 class="text-sm font-medium text-red-900">Import Errors</h4>
        </div>
        <div class="space-y-2 max-h-60 overflow-y-auto">
          <div v-for="(error, index) in job.error_log" :key="index" 
               class="text-sm text-red-800 bg-red-100 rounded p-2">
            {{ error }}
          </div>
        </div>
      </div>

      <!-- Detailed Breakdown (if available) -->
      <div v-if="job.import_breakdown" class="bg-white border border-gray-200 rounded-lg p-4">
        <h4 class="text-sm font-medium text-gray-900 mb-4">Import Breakdown</h4>
        <div class="space-y-4">
          <div v-for="(breakdown, type) in job.import_breakdown" :key="type" 
               class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
            <div class="flex items-center space-x-3">
              <div class="w-8 h-8 bg-indigo-100 rounded-full flex items-center justify-center">
                <component :is="getBreakdownIcon(type)" class="w-4 h-4 text-indigo-600" />
              </div>
              <div>
                <div class="text-sm font-medium text-gray-900">{{ formatBreakdownType(type) }}</div>
                <div class="text-xs text-gray-500">{{ breakdown.description || '' }}</div>
              </div>
            </div>
            <div class="text-right">
              <div class="text-lg font-bold text-gray-900">{{ breakdown.count.toLocaleString() }}</div>
              <div class="text-xs text-gray-500">{{ breakdown.count === 1 ? 'record' : 'records' }}</div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <template #actions>
      <div class="flex justify-end space-x-3">
        <button
          @click="$emit('close')"
          class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
        >
          Close
        </button>
        
        <button
          v-if="job?.status === 'completed'"
          @click="$emit('download', job)"
          class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
        >
          Download Results
        </button>
        
        <button
          v-if="job?.status === 'failed'"
          @click="$emit('retry', job)"
          class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
        >
          Retry Import
        </button>
      </div>
    </template>
  </StackedDialog>
</template>

<script setup>
import { computed } from 'vue'
import {
  CheckCircleIcon,
  XCircleIcon,
  ClockIcon,
  ExclamationTriangleIcon,
  StopIcon,
  PlayIcon,
  ArrowPathIcon,
  ArrowDownTrayIcon,
  BuildingOfficeIcon,
  UserGroupIcon,
  TicketIcon,
  ChatBubbleLeftRightIcon,
  ClockIcon as TimeIcon
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
  }
})

// Emits
defineEmits(['close', 'retry', 'download'])

// Computed
const statusIcon = computed(() => {
  const iconMap = {
    pending: ClockIcon,
    running: PlayIcon,
    completed: CheckCircleIcon,
    failed: XCircleIcon,
    cancelled: StopIcon
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

// Methods
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
  if (!job?.started_at) return 'N/A'
  
  const endTime = job.completed_at ? new Date(job.completed_at) : new Date()
  const startTime = new Date(job.started_at)
  const seconds = Math.floor((endTime - startTime) / 1000)
  
  if (seconds < 60) return `${seconds}s`
  
  const minutes = Math.floor(seconds / 60)
  if (minutes < 60) return `${minutes}m ${seconds % 60}s`
  
  const hours = Math.floor(minutes / 60)
  return `${hours}h ${minutes % 60}m`
}

const formatAccountStrategy = (strategy) => {
  const strategies = {
    map_mailboxes: 'Map Mailboxes to Accounts',
    domain_mapping: 'Use Domain Mapping'
  }
  return strategies[strategy] || strategy
}

const formatAgentAccess = (access) => {
  const accessTypes = {
    all_accounts: 'All Accounts',
    primary_account: 'Primary Account Only'
  }
  return accessTypes[access] || access
}

const formatBillingStrategy = (strategy) => {
  const strategies = {
    auto_select: 'Auto-select Rates',
    no_rate: 'No Billing Rates',
    fixed_rate: 'Fixed Rate'
  }
  return strategies[strategy] || strategy
}

const formatSyncStrategy = (strategy) => {
  const strategies = {
    create_only: 'Create Only',
    update_only: 'Update Only',
    upsert: 'Create or Update'
  }
  return strategies[strategy] || strategy
}

const formatDuplicateDetection = (detection) => {
  const detectionTypes = {
    external_id: 'External ID Matching',
    content_match: 'Content Matching'
  }
  return detectionTypes[detection] || detection
}

const getBreakdownIcon = (type) => {
  const icons = {
    accounts: BuildingOfficeIcon,
    users: UserGroupIcon,
    tickets: TicketIcon,
    comments: ChatBubbleLeftRightIcon,
    time_entries: TimeIcon
  }
  return icons[type] || TicketIcon
}

const formatBreakdownType = (type) => {
  const types = {
    accounts: 'Accounts',
    users: 'Users',
    tickets: 'Tickets',
    comments: 'Comments',
    time_entries: 'Time Entries'
  }
  return types[type] || type
}
</script>