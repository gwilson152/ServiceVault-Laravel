<template>
  <StackedDialog
    :show="show"
    @close="$emit('close')"
    size="xl"
    :title="`Email Processing Details - ${log?.email_id || 'Log Entry'}`"
  >
    <div v-if="log" class="space-y-6">
      <!-- Header Summary -->
      <div class="bg-gray-50 rounded-lg p-4">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
          <div>
            <span class="block text-sm font-medium text-gray-500">Status</span>
            <span :class="[
              'inline-flex px-2 py-1 text-xs font-medium rounded-full mt-1',
              log.status === 'success' ? 'bg-green-100 text-green-800' :
              log.status === 'failed' ? 'bg-red-100 text-red-800' :
              log.status === 'processing' ? 'bg-blue-100 text-blue-800' :
              'bg-gray-100 text-gray-800'
            ]">
              {{ log.status }}
            </span>
          </div>
          <div>
            <span class="block text-sm font-medium text-gray-500">Direction</span>
            <div class="flex items-center mt-1">
              <ArrowUpIcon v-if="log.direction === 'outgoing'" class="h-4 w-4 text-blue-500 mr-1" />
              <ArrowDownIcon v-else class="h-4 w-4 text-green-500 mr-1" />
              <span class="text-sm text-gray-900 capitalize">{{ log.direction }}</span>
            </div>
          </div>
          <div>
            <span class="block text-sm font-medium text-gray-500">Duration</span>
            <span class="text-sm text-gray-900 mt-1">
              {{ log.processing_duration_ms ? `${log.processing_duration_ms}ms` : 'N/A' }}
            </span>
          </div>
          <div>
            <span class="block text-sm font-medium text-gray-500">Commands</span>
            <span class="text-sm text-gray-900 mt-1">
              {{ log.commands_executed || 0 }} executed
            </span>
          </div>
        </div>
      </div>

      <!-- Email Information -->
      <div>
        <h3 class="text-lg font-medium text-gray-900 mb-4">Email Information</h3>
        <div class="bg-white border border-gray-200 rounded-lg">
          <dl class="divide-y divide-gray-200">
            <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4">
              <dt class="text-sm font-medium text-gray-500">From</dt>
              <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">
                {{ log.from_address }}
              </dd>
            </div>
            <div v-if="log.to_addresses" class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4">
              <dt class="text-sm font-medium text-gray-500">To</dt>
              <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">
                {{ Array.isArray(log.to_addresses) ? log.to_addresses.join(', ') : log.to_addresses }}
              </dd>
            </div>
            <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4">
              <dt class="text-sm font-medium text-gray-500">Subject</dt>
              <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">
                {{ log.subject || '(No subject)' }}
              </dd>
            </div>
            <div v-if="log.ticket_number" class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4">
              <dt class="text-sm font-medium text-gray-500">Ticket Number</dt>
              <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">
                <span class="font-mono text-indigo-600">{{ log.ticket_number }}</span>
              </dd>
            </div>
          </dl>
        </div>
      </div>

      <!-- Commands Executed -->
      <div v-if="log.executed_commands?.length > 0">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Commands Executed</h3>
        <div class="bg-white border border-gray-200 rounded-lg p-4">
          <div class="flex flex-wrap gap-2">
            <span 
              v-for="command in log.executed_commands" 
              :key="command"
              class="inline-flex items-center px-3 py-1 rounded-full text-sm bg-indigo-100 text-indigo-800"
            >
              <CommandLineIcon class="w-4 h-4 mr-2" />
              {{ command }}
            </span>
          </div>
        </div>
      </div>

      <!-- Processing Timeline -->
      <div v-if="log.processing_steps?.length > 0">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Processing Timeline</h3>
        <div class="flow-root">
          <ul class="-mb-8">
            <li v-for="(step, stepIdx) in log.processing_steps" :key="stepIdx">
              <div class="relative pb-8">
                <span v-if="stepIdx !== log.processing_steps.length - 1" class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-gray-200"></span>
                <div class="relative flex space-x-3">
                  <div>
                    <span :class="[
                      'h-8 w-8 rounded-full flex items-center justify-center ring-8 ring-white',
                      step.status === 'success' ? 'bg-green-500' :
                      step.status === 'error' ? 'bg-red-500' :
                      step.status === 'warning' ? 'bg-yellow-500' :
                      'bg-gray-400'
                    ]">
                      <CheckIcon v-if="step.status === 'success'" class="h-5 w-5 text-white" />
                      <XMarkIcon v-else-if="step.status === 'error'" class="h-5 w-5 text-white" />
                      <ExclamationTriangleIcon v-else-if="step.status === 'warning'" class="h-5 w-5 text-white" />
                      <ClockIcon v-else class="h-5 w-5 text-white" />
                    </span>
                  </div>
                  <div class="flex min-w-0 flex-1 justify-between space-x-4 pt-1.5">
                    <div>
                      <p class="text-sm text-gray-900">
                        {{ step.step_name }}
                        <span v-if="step.duration_ms" class="text-gray-500">({{ step.duration_ms }}ms)</span>
                      </p>
                      <p v-if="step.description" class="mt-0.5 text-sm text-gray-500">
                        {{ step.description }}
                      </p>
                      <p v-if="step.error_message" class="mt-0.5 text-sm text-red-600">
                        Error: {{ step.error_message }}
                      </p>
                    </div>
                    <div class="whitespace-nowrap text-right text-sm text-gray-500">
                      {{ formatDate(step.timestamp) }}
                    </div>
                  </div>
                </div>
              </div>
            </li>
          </ul>
        </div>
      </div>

      <!-- Email Content (if available) -->
      <div v-if="log.email_content || log.parsed_content">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Email Content</h3>
        <div class="bg-gray-50 border border-gray-200 rounded-lg">
          <div class="border-b border-gray-200">
            <nav class="-mb-px flex space-x-8 px-4 pt-4">
              <button
                v-if="log.email_content"
                @click="contentTab = 'raw'"
                :class="[
                  'py-2 px-1 border-b-2 font-medium text-sm',
                  contentTab === 'raw' 
                    ? 'border-indigo-500 text-indigo-600' 
                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                ]"
              >
                Raw Content
              </button>
              <button
                v-if="log.parsed_content"
                @click="contentTab = 'parsed'"
                :class="[
                  'py-2 px-1 border-b-2 font-medium text-sm',
                  contentTab === 'parsed' 
                    ? 'border-indigo-500 text-indigo-600' 
                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                ]"
              >
                Parsed Content
              </button>
            </nav>
          </div>
          <div class="p-4">
            <div v-if="contentTab === 'raw' && log.email_content" class="space-y-4">
              <pre class="bg-white border rounded p-3 text-sm text-gray-800 overflow-x-auto">{{ log.email_content }}</pre>
            </div>
            <div v-else-if="contentTab === 'parsed' && log.parsed_content" class="space-y-4">
              <div v-for="(section, key) in log.parsed_content" :key="key" class="border-b border-gray-200 last:border-b-0 pb-3 last:pb-0">
                <h4 class="text-sm font-medium text-gray-700 capitalize mb-2">{{ key.replace('_', ' ') }}</h4>
                <pre v-if="typeof section === 'string'" class="bg-white border rounded p-3 text-sm text-gray-800 overflow-x-auto">{{ section }}</pre>
                <div v-else-if="Array.isArray(section)" class="space-y-1">
                  <div v-for="(item, idx) in section" :key="idx" class="text-sm text-gray-800">
                    {{ typeof item === 'object' ? JSON.stringify(item, null, 2) : item }}
                  </div>
                </div>
                <pre v-else class="bg-white border rounded p-3 text-sm text-gray-800 overflow-x-auto">{{ JSON.stringify(section, null, 2) }}</pre>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Attachments -->
      <div v-if="log.attachments?.length > 0">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Attachments ({{ log.attachments.length }})</h3>
        <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
          <ul class="divide-y divide-gray-200">
            <li v-for="attachment in log.attachments" :key="attachment.id" class="px-4 py-4">
              <div class="flex items-center justify-between">
                <div class="flex items-center">
                  <PaperClipIcon class="h-5 w-5 text-gray-400 mr-3" />
                  <div>
                    <p class="text-sm font-medium text-gray-900">{{ attachment.filename }}</p>
                    <p class="text-sm text-gray-500">
                      {{ formatFileSize(attachment.size) }} • {{ attachment.mime_type }}
                    </p>
                  </div>
                </div>
                <div class="flex items-center space-x-2">
                  <span :class="[
                    'inline-flex px-2 py-1 text-xs font-medium rounded-full',
                    attachment.virus_scanned === 'clean' ? 'bg-green-100 text-green-800' :
                    attachment.virus_scanned === 'infected' ? 'bg-red-100 text-red-800' :
                    'bg-yellow-100 text-yellow-800'
                  ]">
                    {{ attachment.virus_scanned || 'pending' }}
                  </span>
                  <button
                    v-if="attachment.stored_path"
                    @click="downloadAttachment(attachment)"
                    class="text-indigo-600 hover:text-indigo-900 text-sm font-medium"
                  >
                    Download
                  </button>
                </div>
              </div>
            </li>
          </ul>
        </div>
      </div>

      <!-- Error Details -->
      <div v-if="log.error_message || log.error_details">
        <h3 class="text-lg font-medium text-red-600 mb-4">Error Information</h3>
        <div class="bg-red-50 border border-red-200 rounded-lg p-4">
          <div v-if="log.error_message" class="mb-3">
            <h4 class="text-sm font-medium text-red-800 mb-1">Error Message</h4>
            <p class="text-sm text-red-700">{{ log.error_message }}</p>
          </div>
          <div v-if="log.error_details">
            <h4 class="text-sm font-medium text-red-800 mb-1">Error Details</h4>
            <pre class="text-xs text-red-700 bg-red-100 rounded p-2 overflow-x-auto">{{ log.error_details }}</pre>
          </div>
        </div>
      </div>

      <!-- System Metadata -->
      <div>
        <h3 class="text-lg font-medium text-gray-900 mb-4">System Information</h3>
        <div class="bg-white border border-gray-200 rounded-lg">
          <dl class="divide-y divide-gray-200">
            <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4">
              <dt class="text-sm font-medium text-gray-500">Email ID</dt>
              <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0 font-mono">
                {{ log.email_id }}
              </dd>
            </div>
            <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4">
              <dt class="text-sm font-medium text-gray-500">Processed At</dt>
              <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">
                {{ formatDate(log.processed_at) }}
              </dd>
            </div>
            <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4">
              <dt class="text-sm font-medium text-gray-500">Created At</dt>
              <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">
                {{ formatDate(log.created_at) }}
              </dd>
            </div>
            <div v-if="log.retry_count" class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4">
              <dt class="text-sm font-medium text-gray-500">Retry Count</dt>
              <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">
                {{ log.retry_count }}
              </dd>
            </div>
            <div v-if="log.queue_name" class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4">
              <dt class="text-sm font-medium text-gray-500">Queue</dt>
              <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">
                {{ log.queue_name }}
              </dd>
            </div>
          </dl>
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
      
      <button
        v-if="log?.status === 'failed'"
        @click="retryProcessing"
        class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-md hover:bg-indigo-700"
      >
        Retry Processing
      </button>
    </template>
  </StackedDialog>
</template>

<script setup>
import { ref, computed } from 'vue'
import {
  ArrowUpIcon,
  ArrowDownIcon,
  CheckIcon,
  XMarkIcon,
  ExclamationTriangleIcon,
  ClockIcon,
  CommandLineIcon,
  PaperClipIcon
} from '@heroicons/vue/24/outline'
import StackedDialog from '@/Components/Modals/StackedDialog.vue'

const props = defineProps({
  show: Boolean,
  log: Object
})

const emit = defineEmits(['close'])

// State
const contentTab = ref('raw')

// Methods
function formatDate(date) {
  if (!date) return 'N/A'
  return new Date(date).toLocaleString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit'
  })
}

function formatFileSize(bytes) {
  if (!bytes) return '0 B'
  const k = 1024
  const sizes = ['B', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
}

async function downloadAttachment(attachment) {
  try {
    const response = await fetch(`/api/email-admin/attachments/${attachment.id}/download`)
    if (!response.ok) throw new Error('Download failed')
    
    const blob = await response.blob()
    const url = window.URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = attachment.filename
    document.body.appendChild(a)
    a.click()
    document.body.removeChild(a)
    window.URL.revokeObjectURL(url)
  } catch (error) {
    console.error('Error downloading attachment:', error)
  }
}

async function retryProcessing() {
  if (!props.log) return
  
  try {
    const response = await fetch(`/api/email-admin/processing-logs/${props.log.id}/retry`, {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
      }
    })
    
    if (!response.ok) throw new Error('Retry failed')
    
    // Close modal and refresh parent
    emit('close')
    
  } catch (error) {
    console.error('Error retrying processing:', error)
  }
}

// Set default content tab based on available data
if (props.log?.parsed_content && !props.log?.email_content) {
  contentTab.value = 'parsed'
}
</script>