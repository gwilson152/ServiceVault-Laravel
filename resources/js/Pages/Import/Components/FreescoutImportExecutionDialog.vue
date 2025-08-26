<template>
  <StackedDialog :show="show" @close="handleClose" size="large" :closable="!isExecuting">
    <template #title>
      {{ isExecuting ? 'Importing FreeScout Data' : executionComplete ? 'Import Complete' : 'Import Failed' }}
    </template>

    <template #content>
      <div v-if="profile && config" class="space-y-6">
        <!-- Progress Overview -->
        <div class="bg-gray-50 rounded-lg p-4">
          <div class="flex items-center justify-between mb-3">
            <h3 class="text-sm font-medium text-gray-900">Import Progress</h3>
            <span class="text-sm text-gray-500">
              {{ formatElapsedTime(elapsedTime) }}
            </span>
          </div>
          
          <!-- Overall Progress Bar -->
          <div class="w-full bg-gray-200 rounded-full h-3 mb-3">
            <div 
              class="bg-indigo-600 h-3 rounded-full transition-all duration-300 ease-out"
              :style="{ width: `${overallProgress}%` }"
            ></div>
          </div>
          
          <div class="flex justify-between text-sm text-gray-600">
            <span>{{ overallProgress }}% Complete</span>
            <span>{{ processedItems }} / {{ totalItems }} items</span>
          </div>
        </div>

        <!-- Individual Progress Sections -->
        <div class="space-y-4">
          <!-- Conversations Progress -->
          <div class="border rounded-lg p-4">
            <div class="flex items-center justify-between mb-2">
              <div class="flex items-center">
                <div 
                  :class="[
                    'w-3 h-3 rounded-full mr-3',
                    progressSections.conversations.status === 'completed' ? 'bg-green-500' :
                    progressSections.conversations.status === 'in_progress' ? 'bg-blue-500 animate-pulse' :
                    progressSections.conversations.status === 'error' ? 'bg-red-500' : 'bg-gray-300'
                  ]"
                ></div>
                <span class="font-medium text-gray-900">Importing Conversations</span>
              </div>
              <span class="text-sm text-gray-500">
                {{ progressSections.conversations.processed }} / {{ progressSections.conversations.total }}
              </span>
            </div>
            
            <div class="w-full bg-gray-200 rounded-full h-2 mb-2">
              <div 
                class="bg-blue-600 h-2 rounded-full transition-all duration-300"
                :style="{ width: `${progressSections.conversations.percentage}%` }"
              ></div>
            </div>
            
            <div v-if="progressSections.conversations.current_item" class="text-xs text-gray-600">
              Processing: {{ progressSections.conversations.current_item }}
            </div>
            
            <div v-if="progressSections.conversations.errors.length > 0" class="mt-2">
              <div class="text-xs text-red-600">
                {{ progressSections.conversations.errors.length }} errors
                <button 
                  @click="toggleErrorDetails('conversations')"
                  class="ml-2 text-red-700 hover:text-red-800 underline"
                >
                  {{ showErrorDetails.conversations ? 'Hide' : 'Show' }} details
                </button>
              </div>
              <div v-if="showErrorDetails.conversations" class="mt-2 space-y-1">
                <div 
                  v-for="error in progressSections.conversations.errors.slice(0, 3)"
                  :key="error.id"
                  class="text-xs bg-red-50 text-red-700 p-2 rounded"
                >
                  <strong>ID {{ error.id }}:</strong> {{ error.message }}
                </div>
                <div 
                  v-if="progressSections.conversations.errors.length > 3"
                  class="text-xs text-red-600"
                >
                  +{{ progressSections.conversations.errors.length - 3 }} more errors
                </div>
              </div>
            </div>
          </div>

          <!-- Customers Progress -->
          <div class="border rounded-lg p-4">
            <div class="flex items-center justify-between mb-2">
              <div class="flex items-center">
                <div 
                  :class="[
                    'w-3 h-3 rounded-full mr-3',
                    progressSections.customers.status === 'completed' ? 'bg-green-500' :
                    progressSections.customers.status === 'in_progress' ? 'bg-blue-500 animate-pulse' :
                    progressSections.customers.status === 'error' ? 'bg-red-500' : 'bg-gray-300'
                  ]"
                ></div>
                <span class="font-medium text-gray-900">Importing Customers</span>
              </div>
              <span class="text-sm text-gray-500">
                {{ progressSections.customers.processed }} / {{ progressSections.customers.total }}
              </span>
            </div>
            
            <div class="w-full bg-gray-200 rounded-full h-2 mb-2">
              <div 
                class="bg-green-600 h-2 rounded-full transition-all duration-300"
                :style="{ width: `${progressSections.customers.percentage}%` }"
              ></div>
            </div>
            
            <div v-if="progressSections.customers.current_item" class="text-xs text-gray-600">
              Processing: {{ progressSections.customers.current_item }}
            </div>
            
            <div v-if="progressSections.customers.duplicates_found > 0" class="text-xs text-yellow-600 mt-1">
              {{ progressSections.customers.duplicates_found }} duplicates found and skipped
            </div>
          </div>

          <!-- Accounts Progress -->
          <div class="border rounded-lg p-4">
            <div class="flex items-center justify-between mb-2">
              <div class="flex items-center">
                <div 
                  :class="[
                    'w-3 h-3 rounded-full mr-3',
                    progressSections.accounts.status === 'completed' ? 'bg-green-500' :
                    progressSections.accounts.status === 'in_progress' ? 'bg-blue-500 animate-pulse' :
                    progressSections.accounts.status === 'error' ? 'bg-red-500' : 'bg-gray-300'
                  ]"
                ></div>
                <span class="font-medium text-gray-900">
                  {{ config.account_strategy === 'map_mailboxes' ? 'Creating Accounts from Mailboxes' : 'Applying Domain Mappings' }}
                </span>
              </div>
              <span class="text-sm text-gray-500">
                {{ progressSections.accounts.processed }} / {{ progressSections.accounts.total }}
              </span>
            </div>
            
            <div class="w-full bg-gray-200 rounded-full h-2 mb-2">
              <div 
                class="bg-purple-600 h-2 rounded-full transition-all duration-300"
                :style="{ width: `${progressSections.accounts.percentage}%` }"
              ></div>
            </div>
            
            <div v-if="progressSections.accounts.current_item" class="text-xs text-gray-600">
              Processing: {{ progressSections.accounts.current_item }}
            </div>
          </div>
        </div>

        <!-- Live Activity Log -->
        <div class="bg-gray-50 rounded-lg p-4 max-h-48 overflow-y-auto">
          <h4 class="text-sm font-medium text-gray-900 mb-3">Activity Log</h4>
          <div class="space-y-1">
            <div 
              v-for="log in activityLogs.slice().reverse().slice(0, 10)"
              :key="log.id"
              :class="[
                'text-xs p-2 rounded flex items-start',
                log.level === 'error' ? 'bg-red-100 text-red-800' :
                log.level === 'warning' ? 'bg-yellow-100 text-yellow-800' :
                log.level === 'success' ? 'bg-green-100 text-green-800' :
                'bg-gray-100 text-gray-700'
              ]"
            >
              <span class="font-mono text-gray-400 mr-2 flex-shrink-0">
                {{ log.timestamp.toLocaleTimeString() }}
              </span>
              <span>{{ log.message }}</span>
            </div>
          </div>
        </div>

        <!-- Final Results (shown when complete) -->
        <div v-if="executionComplete" class="bg-green-50 border border-green-200 rounded-lg p-4">
          <div class="flex items-center mb-3">
            <CheckCircleIcon class="w-5 h-5 text-green-600 mr-2" />
            <h4 class="font-medium text-green-900">Import Completed Successfully</h4>
          </div>
          
          <div class="grid grid-cols-3 gap-4 mb-4">
            <div class="text-center">
              <div class="text-2xl font-bold text-green-700">{{ finalStats.conversations_created }}</div>
              <div class="text-sm text-green-600">Conversations Imported</div>
            </div>
            <div class="text-center">
              <div class="text-2xl font-bold text-green-700">{{ finalStats.customers_created }}</div>
              <div class="text-sm text-green-600">Customers Created</div>
            </div>
            <div class="text-center">
              <div class="text-2xl font-bold text-green-700">{{ finalStats.accounts_created }}</div>
              <div class="text-sm text-green-600">Accounts Created</div>
            </div>
          </div>
          
          <div class="text-sm text-green-700">
            <div>• Total processing time: {{ formatElapsedTime(elapsedTime) }}</div>
            <div>• {{ finalStats.duplicates_skipped }} duplicate records skipped</div>
            <div>• {{ finalStats.errors_count }} errors encountered</div>
          </div>
        </div>

        <!-- Error Summary (shown if failed) -->
        <div v-if="executionFailed" class="bg-red-50 border border-red-200 rounded-lg p-4">
          <div class="flex items-center mb-3">
            <XCircleIcon class="w-5 h-5 text-red-600 mr-2" />
            <h4 class="font-medium text-red-900">Import Failed</h4>
          </div>
          
          <div class="text-sm text-red-700 space-y-1">
            <div>{{ failureReason }}</div>
            <div>Processed {{ processedItems }} of {{ totalItems }} items before failure</div>
          </div>
        </div>
      </div>
      <div v-else class="flex items-center justify-center py-8">
        <div class="text-gray-500">Initializing import...</div>
      </div>
    </template>

    <template #actions>
      <div class="flex justify-between">
        <div class="flex space-x-2">
          <button
            v-if="isExecuting"
            @click="pauseExecution"
            :disabled="isPaused"
            class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50"
          >
            {{ isPaused ? 'Paused' : 'Pause' }}
          </button>
          <button
            v-if="isPaused"
            @click="resumeExecution"
            class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
          >
            Resume
          </button>
        </div>
        <div class="flex space-x-3">
          <button
            v-if="executionFailed"
            @click="retryExecution"
            class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
          >
            Retry Import
          </button>
          <button
            @click="handleClose"
            :disabled="isExecuting && !isPaused"
            :class="[
              'px-4 py-2 border rounded-md shadow-sm text-sm font-medium focus:outline-none focus:ring-2 focus:ring-offset-2',
              (isExecuting && !isPaused) 
                ? 'border-gray-300 text-gray-400 bg-gray-100 cursor-not-allowed'
                : 'border-gray-300 text-gray-700 bg-white hover:bg-gray-50 focus:ring-indigo-500'
            ]"
          >
            {{ executionComplete ? 'Close' : isExecuting ? 'Running...' : 'Cancel' }}
          </button>
        </div>
      </div>
    </template>
  </StackedDialog>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import StackedDialog from '@/Components/StackedDialog.vue'
import { CheckCircleIcon, XCircleIcon } from '@heroicons/vue/24/outline'

// Props
const props = defineProps({
  show: {
    type: Boolean,
    default: false
  },
  profile: {
    type: Object,
    default: null
  },
  config: {
    type: Object,
    default: null
  }
})

// Emits
const emit = defineEmits(['close', 'complete', 'failed'])

// State
const isExecuting = ref(true)
const isPaused = ref(false)
const executionComplete = ref(false)
const executionFailed = ref(false)
const failureReason = ref('')
const startTime = ref(Date.now())
const elapsedTime = ref(0)

// Progress tracking
const progressSections = ref({
  conversations: {
    status: 'pending', // pending, in_progress, completed, error
    processed: 0,
    total: 247,
    percentage: 0,
    current_item: '',
    errors: []
  },
  customers: {
    status: 'pending',
    processed: 0,
    total: 156,
    percentage: 0,
    current_item: '',
    duplicates_found: 0
  },
  accounts: {
    status: 'pending',
    processed: 0,
    total: 8,
    percentage: 0,
    current_item: ''
  }
})

const showErrorDetails = ref({
  conversations: false,
  customers: false,
  accounts: false
})

const activityLogs = ref([
  {
    id: 1,
    timestamp: new Date(),
    level: 'info',
    message: 'Starting FreeScout API import...'
  }
])

const finalStats = ref({
  conversations_created: 0,
  customers_created: 0,
  accounts_created: 0,
  duplicates_skipped: 0,
  errors_count: 0
})

// Intervals
let progressInterval = null
let elapsedTimeInterval = null

// Computed
const totalItems = computed(() => {
  return progressSections.value.conversations.total + 
         progressSections.value.customers.total + 
         progressSections.value.accounts.total
})

const processedItems = computed(() => {
  return progressSections.value.conversations.processed + 
         progressSections.value.customers.processed + 
         progressSections.value.accounts.processed
})

const overallProgress = computed(() => {
  if (totalItems.value === 0) return 0
  return Math.round((processedItems.value / totalItems.value) * 100)
})

// Methods
const simulateProgress = () => {
  // Simulate conversations import
  if (progressSections.value.conversations.status === 'pending') {
    progressSections.value.conversations.status = 'in_progress'
    addLog('info', 'Starting conversation import...')
  }

  if (progressSections.value.conversations.status === 'in_progress') {
    if (progressSections.value.conversations.processed < progressSections.value.conversations.total) {
      progressSections.value.conversations.processed += Math.floor(Math.random() * 3) + 1
      progressSections.value.conversations.percentage = Math.round(
        (progressSections.value.conversations.processed / progressSections.value.conversations.total) * 100
      )
      progressSections.value.conversations.current_item = `Conversation #${12000 + progressSections.value.conversations.processed}`
      
      // Occasionally add an error
      if (Math.random() < 0.05) {
        progressSections.value.conversations.errors.push({
          id: 12000 + progressSections.value.conversations.processed,
          message: 'Failed to parse conversation thread data'
        })
        addLog('error', `Error importing conversation #${12000 + progressSections.value.conversations.processed}`)
      } else {
        addLog('info', `Imported conversation #${12000 + progressSections.value.conversations.processed}`)
      }
    } else {
      progressSections.value.conversations.status = 'completed'
      progressSections.value.conversations.current_item = ''
      progressSections.value.customers.status = 'in_progress'
      addLog('success', `Completed conversation import: ${progressSections.value.conversations.processed} conversations`)
    }
  }

  // Simulate customers import
  if (progressSections.value.customers.status === 'in_progress') {
    if (progressSections.value.customers.processed < progressSections.value.customers.total) {
      progressSections.value.customers.processed += Math.floor(Math.random() * 2) + 1
      progressSections.value.customers.percentage = Math.round(
        (progressSections.value.customers.processed / progressSections.value.customers.total) * 100
      )
      progressSections.value.customers.current_item = `customer${5000 + progressSections.value.customers.processed}@example.com`
      
      // Simulate duplicate detection
      if (Math.random() < 0.1) {
        progressSections.value.customers.duplicates_found++
        addLog('warning', `Duplicate customer found: ${progressSections.value.customers.current_item}`)
      } else {
        addLog('info', `Created customer: ${progressSections.value.customers.current_item}`)
      }
    } else {
      progressSections.value.customers.status = 'completed'
      progressSections.value.customers.current_item = ''
      progressSections.value.accounts.status = 'in_progress'
      addLog('success', `Completed customer import: ${progressSections.value.customers.processed} customers`)
    }
  }

  // Simulate accounts import
  if (progressSections.value.accounts.status === 'in_progress') {
    if (progressSections.value.accounts.processed < progressSections.value.accounts.total) {
      progressSections.value.accounts.processed++
      progressSections.value.accounts.percentage = Math.round(
        (progressSections.value.accounts.processed / progressSections.value.accounts.total) * 100
      )
      
      if (props.config.account_strategy === 'map_mailboxes') {
        progressSections.value.accounts.current_item = `Account from Mailbox #${progressSections.value.accounts.processed}`
        addLog('info', `Created account from mailbox: ${progressSections.value.accounts.current_item}`)
      } else {
        progressSections.value.accounts.current_item = 'Applying domain mappings...'
        addLog('info', 'Applied domain mapping for customer batch')
      }
    } else {
      progressSections.value.accounts.status = 'completed'
      progressSections.value.accounts.current_item = ''
      completeExecution()
    }
  }
}

const completeExecution = () => {
  isExecuting.value = false
  executionComplete.value = true
  
  // Set final stats
  finalStats.value = {
    conversations_created: progressSections.value.conversations.processed,
    customers_created: progressSections.value.customers.processed,
    accounts_created: progressSections.value.accounts.processed,
    duplicates_skipped: progressSections.value.customers.duplicates_found,
    errors_count: progressSections.value.conversations.errors.length
  }
  
  addLog('success', 'Import completed successfully!')
  emit('complete', finalStats.value)
}

const addLog = (level, message) => {
  activityLogs.value.push({
    id: Date.now() + Math.random(),
    timestamp: new Date(),
    level,
    message
  })
}

const formatElapsedTime = (ms) => {
  const seconds = Math.floor(ms / 1000)
  const minutes = Math.floor(seconds / 60)
  const remainingSeconds = seconds % 60
  
  if (minutes > 0) {
    return `${minutes}m ${remainingSeconds}s`
  }
  return `${remainingSeconds}s`
}

const toggleErrorDetails = (section) => {
  showErrorDetails.value[section] = !showErrorDetails.value[section]
}

const pauseExecution = () => {
  isPaused.value = true
  addLog('warning', 'Import paused by user')
}

const resumeExecution = () => {
  isPaused.value = false
  addLog('info', 'Import resumed')
}

const retryExecution = () => {
  // Reset all progress
  executionFailed.value = false
  isExecuting.value = true
  startTime.value = Date.now()
  
  Object.keys(progressSections.value).forEach(key => {
    progressSections.value[key].status = 'pending'
    progressSections.value[key].processed = 0
    progressSections.value[key].percentage = 0
    progressSections.value[key].current_item = ''
    if (progressSections.value[key].errors) {
      progressSections.value[key].errors = []
    }
  })
  
  activityLogs.value = [{
    id: Date.now(),
    timestamp: new Date(),
    level: 'info',
    message: 'Retrying FreeScout API import...'
  }]
}

const handleClose = () => {
  if (isExecuting.value && !isPaused.value) {
    return // Can't close while running
  }
  
  emit('close')
}

// Lifecycle
onMounted(() => {
  // Start progress simulation
  progressInterval = setInterval(() => {
    if (isExecuting.value && !isPaused.value) {
      simulateProgress()
    }
  }, 800)
  
  // Update elapsed time
  elapsedTimeInterval = setInterval(() => {
    elapsedTime.value = Date.now() - startTime.value
  }, 1000)
})

onUnmounted(() => {
  if (progressInterval) clearInterval(progressInterval)
  if (elapsedTimeInterval) clearInterval(elapsedTimeInterval)
})
</script>