<template>
  <StackedDialog
    :show="show"
    @close="$emit('close')"
    size="lg"
    title="Template Preview"
  >
    <div class="space-y-6">
      <!-- Preview Controls -->
      <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
        <div>
          <h3 class="text-sm font-medium text-gray-900">{{ template?.name || 'Untitled Template' }}</h3>
          <p class="text-sm text-gray-500">{{ getTypeLabel(template?.type) }}</p>
        </div>
        
        <div class="flex items-center space-x-3">
          <!-- Sample Data Selector -->
          <div>
            <label class="block text-xs text-gray-700 mb-1">Sample Data:</label>
            <select
              v-model="selectedSampleData"
              @change="generatePreview"
              class="text-sm border-gray-300 rounded-md focus:border-indigo-500 focus:ring-indigo-500"
            >
              <option v-for="sample in sampleDataOptions" :key="sample.key" :value="sample.key">
                {{ sample.label }}
              </option>
            </select>
          </div>

          <!-- Preview Format Toggle -->
          <div>
            <label class="block text-xs text-gray-700 mb-1">Format:</label>
            <div class="flex rounded-md border border-gray-300 overflow-hidden">
              <button
                @click="previewFormat = 'html'"
                :class="[
                  previewFormat === 'html'
                    ? 'bg-indigo-600 text-white'
                    : 'bg-white text-gray-700 hover:bg-gray-50',
                  'px-3 py-1 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-indigo-500'
                ]"
              >
                HTML
              </button>
              <button
                @click="previewFormat = 'text'"
                :class="[
                  previewFormat === 'text'
                    ? 'bg-indigo-600 text-white'
                    : 'bg-white text-gray-700 hover:bg-gray-50',
                  'px-3 py-1 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-indigo-500'
                ]"
              >
                Text
              </button>
            </div>
          </div>

          <!-- Refresh Preview -->
          <button
            @click="generatePreview"
            :disabled="generating"
            class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 disabled:opacity-50"
          >
            <ArrowPathIcon :class="['w-4 h-4', generating ? 'animate-spin' : '']" />
          </button>
        </div>
      </div>

      <!-- Email Preview -->
      <div class="border border-gray-200 rounded-lg overflow-hidden">
        <!-- Email Header -->
        <div class="bg-gray-100 px-4 py-3 border-b border-gray-200">
          <div class="flex items-center justify-between">
            <div>
              <div class="text-sm font-medium text-gray-900">
                Subject: <span class="font-normal">{{ previewData.subject || 'No subject' }}</span>
              </div>
              <div class="text-xs text-gray-500 mt-1">
                From: {{ previewData.from || 'system@servicevault.com' }}
                <span class="mx-2">•</span>
                To: {{ previewData.to || 'recipient@example.com' }}
              </div>
            </div>
            <div class="flex items-center space-x-2">
              <button
                @click="copyToClipboard('subject')"
                class="text-xs text-gray-500 hover:text-gray-700"
                title="Copy subject"
              >
                <ClipboardDocumentIcon class="w-4 h-4" />
              </button>
            </div>
          </div>
        </div>

        <!-- Email Body -->
        <div class="bg-white">
          <!-- HTML Preview -->
          <div v-if="previewFormat === 'html'" class="p-4">
            <div 
              v-if="previewData.html_body"
              v-html="previewData.html_body"
              class="prose max-w-none"
            ></div>
            <div v-else class="text-gray-500 italic">
              No HTML content available
            </div>
          </div>

          <!-- Text Preview -->
          <div v-else class="p-4 bg-gray-50">
            <pre 
              v-if="previewData.text_body"
              class="whitespace-pre-wrap text-sm text-gray-900 font-mono"
            >{{ previewData.text_body }}</pre>
            <div v-else class="text-gray-500 italic">
              No text content available
            </div>
          </div>
        </div>

        <!-- Copy Actions -->
        <div class="bg-gray-50 px-4 py-3 border-t border-gray-200 flex justify-end space-x-2">
          <button
            @click="copyToClipboard('html')"
            class="inline-flex items-center px-3 py-2 text-xs font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
          >
            <ClipboardDocumentIcon class="w-3 h-3 mr-1" />
            Copy HTML
          </button>
          <button
            @click="copyToClipboard('text')"
            class="inline-flex items-center px-3 py-2 text-xs font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
          >
            <ClipboardDocumentIcon class="w-3 h-3 mr-1" />
            Copy Text
          </button>
        </div>
      </div>

      <!-- Variable Values Used -->
      <div v-if="Object.keys(variablesUsed).length > 0" class="bg-blue-50 rounded-lg p-4">
        <h4 class="text-sm font-medium text-blue-900 mb-3">Variables Used in Preview</h4>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
          <div v-for="(value, key) in variablesUsed" :key="key" class="flex justify-between">
            <code class="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded">{{ key }}</code>
            <span class="text-xs text-blue-700 ml-2">{{ value || '(empty)' }}</span>
          </div>
        </div>
      </div>

      <!-- Error Display -->
      <div v-if="previewError" class="bg-red-50 border border-red-200 rounded-lg p-4">
        <div class="flex">
          <ExclamationTriangleIcon class="h-5 w-5 text-red-400" />
          <div class="ml-3">
            <h3 class="text-sm font-medium text-red-800">Preview Error</h3>
            <div class="mt-2 text-sm text-red-700">
              {{ previewError }}
            </div>
          </div>
        </div>
      </div>
    </div>

    <template #actions>
      <button
        @click="$emit('close')"
        class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500"
      >
        Close
      </button>
      
      <button
        @click="sendTestEmail"
        :disabled="!canSendTest || sending"
        class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 disabled:opacity-50"
      >
        <span v-if="sending" class="flex items-center">
          <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          Sending...
        </span>
        <span v-else>Send Test Email</span>
      </button>
    </template>
  </StackedDialog>

  <!-- Test Email Modal -->
  <TestEmailModal
    :show="showTestModal"
    @close="showTestModal = false"
    @send="handleSendTest"
  />
</template>

<script setup>
import { ref, reactive, computed, watch, onMounted } from 'vue'
import {
  ArrowPathIcon,
  ClipboardDocumentIcon,
  ExclamationTriangleIcon
} from '@heroicons/vue/24/outline'
import StackedDialog from '@/Components/StackedDialog.vue'
import TestEmailModal from './TestEmailModal.vue'

const props = defineProps({
  show: Boolean,
  template: Object
})

const emit = defineEmits(['close'])

// State
const generating = ref(false)
const sending = ref(false)
const showTestModal = ref(false)
const previewFormat = ref('html')
const selectedSampleData = ref('default')
const previewError = ref('')

const previewData = reactive({
  subject: '',
  html_body: '',
  text_body: '',
  from: '',
  to: '',
  variables_used: {}
})

const variablesUsed = ref({})

// Sample Data Options
const sampleDataOptions = [
  { key: 'default', label: 'Default Sample' },
  { key: 'ticket_urgent', label: 'Urgent Ticket' },
  { key: 'ticket_resolved', label: 'Resolved Ticket' },
  { key: 'invoice_overdue', label: 'Overdue Invoice' },
  { key: 'new_user', label: 'New User' }
]

// Computed
const canSendTest = computed(() => {
  return previewData.subject && (previewData.html_body || previewData.text_body)
})

// Template Type Labels
const templateTypeLabels = {
  'ticket_created': 'Ticket Created',
  'ticket_updated': 'Ticket Updated',
  'ticket_closed': 'Ticket Closed',
  'ticket_assigned': 'Ticket Assigned',
  'time_entry_added': 'Time Entry Added',
  'invoice_generated': 'Invoice Generated',
  'invoice_sent': 'Invoice Sent',
  'payment_received': 'Payment Received',
  'user_invitation': 'User Invitation',
  'password_reset': 'Password Reset',
  'email_verification': 'Email Verification',
  'custom': 'Custom Template'
}

// Sample Data Sets
const sampleDataSets = {
  default: {
    ticket: {
      id: 'f47ac10b-58cc-4372-a567-0e02b2c3d479',
      number: 'TKT-2025-0001',
      subject: 'Email server configuration issue',
      description: 'The email server is not sending outbound messages properly.',
      status: 'Open',
      priority: 'High',
      category: 'Technical Support',
      created_at: '2025-01-15 09:30:00',
      updated_at: '2025-01-15 10:15:00',
      due_at: '2025-01-20 17:00:00'
    },
    user: {
      id: 'a12b34c5-d678-90ef-1234-567890abcdef',
      name: 'John Doe',
      first_name: 'John',
      last_name: 'Doe',
      email: 'john.doe@company.com',
      role: 'Manager',
      title: 'IT Manager',
      phone: '+1 (555) 123-4567'
    },
    account: {
      id: 'acc-12345',
      name: 'Acme Corporation',
      contact_name: 'Jane Smith',
      contact_email: 'jane.smith@acme.com',
      phone: '+1 (555) 987-6543',
      address: '123 Business St, City, ST 12345'
    },
    agent: {
      name: 'Sarah Wilson',
      email: 'sarah.wilson@company.com',
      title: 'Senior Support Specialist',
      phone: '+1 (555) 456-7890'
    },
    system: {
      name: 'ServiceVault Support',
      url: 'https://support.company.com',
      support_email: 'support@company.com',
      support_phone: '+1 (555) 123-HELP'
    }
  },
  // Add other sample data sets as needed
  ticket_urgent: {
    // Urgent ticket specific sample data
  }
}

// Watch for template changes
watch(() => props.template, (newTemplate) => {
  if (newTemplate && props.show) {
    generatePreview()
  }
}, { immediate: true })

watch(() => props.show, (show) => {
  if (show && props.template) {
    generatePreview()
  }
})

// Methods
async function generatePreview() {
  if (!props.template) return

  try {
    generating.value = true
    previewError.value = ''

    const templateData = {
      subject: props.template.subject || '',
      body: props.template.body || '',
      plain_text_body: props.template.plain_text_body || '',
      type: props.template.type || 'custom',
      sample_data: selectedSampleData.value
    }

    const response = await fetch('/api/email-templates/preview', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
      },
      body: JSON.stringify(templateData)
    })

    if (!response.ok) {
      throw new Error('Failed to generate preview')
    }

    const result = await response.json()
    
    Object.assign(previewData, result)
    variablesUsed.value = result.variables_used || {}

  } catch (error) {
    console.error('Error generating preview:', error)
    previewError.value = error.message || 'Failed to generate preview'
  } finally {
    generating.value = false
  }
}

function getTypeLabel(type) {
  return templateTypeLabels[type] || type
}

function formatVariable(key) {
  return `{{${key}}}`
}

async function copyToClipboard(content) {
  try {
    let textToCopy = ''
    
    switch (content) {
      case 'subject':
        textToCopy = previewData.subject
        break
      case 'html':
        textToCopy = previewData.html_body
        break
      case 'text':
        textToCopy = previewData.text_body
        break
    }

    await navigator.clipboard.writeText(textToCopy)
    // Show success notification
  } catch (error) {
    console.error('Error copying to clipboard:', error)
    // Show error notification
  }
}

function sendTestEmail() {
  showTestModal.value = true
}

async function handleSendTest(testData) {
  try {
    sending.value = true
    
    const payload = {
      template_id: props.template.id,
      recipient_email: testData.email,
      sample_data: selectedSampleData.value,
      ...testData
    }

    const response = await fetch('/api/email-templates/send-test', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
      },
      body: JSON.stringify(payload)
    })

    if (!response.ok) {
      throw new Error('Failed to send test email')
    }

    showTestModal.value = false
    // Show success notification

  } catch (error) {
    console.error('Error sending test email:', error)
    // Show error notification
  } finally {
    sending.value = false
  }
}

// Initialize preview when component mounts
onMounted(() => {
  if (props.show && props.template) {
    generatePreview()
  }
})
</script>

<style scoped>
/* Preview styles */
.prose {
  max-width: none;
}

.prose h1 {
  font-size: 1.5rem;
  font-weight: 600;
  margin-bottom: 1rem;
}

.prose h2 {
  font-size: 1.25rem;
  font-weight: 600;
  margin-bottom: 0.75rem;
}

.prose p {
  margin-bottom: 1rem;
}

.prose ul, .prose ol {
  margin-bottom: 1rem;
  padding-left: 1.5rem;
}
</style>