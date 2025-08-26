<template>
  <StackedDialog v-if="show" @close="$emit('close')" size="large">
    <template #title>
      Preview FreeScout Import
    </template>

    <template #content>
      <div class="space-y-6">
        <!-- Import Configuration Summary -->
        <div class="bg-indigo-50 border border-indigo-200 rounded-lg p-4">
          <div class="flex items-center mb-3">
            <InformationCircleIcon class="w-5 h-5 text-indigo-600 mr-2" />
            <h3 class="text-sm font-medium text-indigo-900">Import Configuration</h3>
          </div>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
            <div>
              <span class="font-medium text-indigo-700">Source:</span>
              <div class="text-indigo-600">{{ profile.name }}</div>
              <div class="text-xs text-indigo-500">{{ profile.instance_url }}</div>
            </div>
            <div>
              <span class="font-medium text-indigo-700">Import Limits:</span>
              <div class="text-indigo-600">
                {{ config.limits.conversations || 'All' }} conversations,
                {{ config.limits.time_entries || 'All' }} time entries,
                {{ config.limits.customers || 'All' }} customers,
                {{ config.limits.mailboxes || 'All' }} mailboxes
              </div>
            </div>
            <div>
              <span class="font-medium text-indigo-700">Account Strategy:</span>
              <div class="text-indigo-600">
                {{ config.account_strategy === 'map_mailboxes' ? 'Map mailboxes to accounts' : 'Use domain mapping' }}
              </div>
            </div>
            <div>
              <span class="font-medium text-indigo-700">Sync Mode:</span>
              <div class="text-indigo-600">
                {{ config.sync_strategy === 'create_only' ? 'Create new records only' : 
                   config.sync_strategy === 'update_only' ? 'Update existing records only' : 
                   'Create new and update existing (upsert)' }}
              </div>
            </div>
            <div>
              <span class="font-medium text-indigo-700">Duplicate Detection:</span>
              <div class="text-indigo-600">
                {{ config.duplicate_detection === 'external_id' ? 'External ID matching' : 
                   config.duplicate_detection === 'content_match' ? 'Content matching' : 
                   'Fuzzy matching' }}
              </div>
            </div>
          </div>
        </div>

        <!-- Data Preview Tabs -->
        <div class="border-b border-gray-200">
          <nav class="-mb-px flex space-x-8">
            <button
              v-for="tab in previewTabs"
              :key="tab.id"
              @click="activeTab = tab.id"
              :class="[
                'py-2 px-1 border-b-2 font-medium text-sm whitespace-nowrap',
                activeTab === tab.id
                  ? 'border-indigo-500 text-indigo-600'
                  : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
              ]"
            >
              {{ tab.name }}
              <span 
                :class="[
                  'ml-2 py-0.5 px-2 rounded-full text-xs font-medium',
                  activeTab === tab.id
                    ? 'bg-indigo-100 text-indigo-600'
                    : 'bg-gray-100 text-gray-600'
                ]"
              >
                {{ tab.count }}
              </span>
            </button>
          </nav>
        </div>

        <!-- Tab Content -->
        <div class="min-h-96">
          <!-- Conversations Tab -->
          <div v-if="activeTab === 'conversations'" class="space-y-4">
            <div class="bg-gray-50 rounded-lg p-4">
              <div class="flex items-center justify-between mb-3">
                <h4 class="font-medium text-gray-900">Sample Conversations</h4>
                <span class="text-sm text-gray-500">Showing first {{ Math.min(mockConversations.length, 10) }} of {{ mockConversations.length }}</span>
              </div>
              <div class="space-y-3">
                <div
                  v-for="conversation in mockConversations.slice(0, 10)"
                  :key="conversation.id"
                  class="bg-white rounded-lg border p-4 hover:shadow-sm transition-shadow"
                >
                  <div class="flex items-start justify-between">
                    <div class="flex-1">
                      <div class="flex items-center space-x-2 mb-2">
                        <span class="font-medium text-gray-900">{{ conversation.subject }}</span>
                        <span 
                          :class="[
                            'px-2 py-1 rounded-full text-xs font-medium',
                            conversation.status === 'active' ? 'bg-green-100 text-green-800' :
                            conversation.status === 'closed' ? 'bg-gray-100 text-gray-800' :
                            'bg-yellow-100 text-yellow-800'
                          ]"
                        >
                          {{ conversation.status }}
                        </span>
                      </div>
                      <div class="text-sm text-gray-600 space-y-1">
                        <div><strong>Customer:</strong> {{ conversation.customer_name || conversation.customer_email }}</div>
                        <div v-if="conversation.customer_name"><strong>Email:</strong> {{ conversation.customer_email }}</div>
                        <div><strong>Mailbox:</strong> {{ conversation.mailbox }}</div>
                        <div v-if="conversation.assigned_to"><strong>Assigned to:</strong> {{ conversation.assigned_to }}</div>
                        <div><strong>Created:</strong> {{ new Date(conversation.created_at).toLocaleDateString() }}</div>
                        <div><strong>Threads:</strong> {{ conversation.threads_count || conversation.threads }} messages</div>
                        <div v-if="conversation.time_entries_count > 0" class="text-blue-600"><strong>Time Entries:</strong> {{ conversation.time_entries_count }}</div>
                      </div>
                    </div>
                    <div class="text-right text-xs text-gray-400">
                      ID: {{ conversation.id }}
                    </div>
                  </div>
                  <!-- Preview of first message -->
                  <div v-if="conversation.preview_message || conversation.preview" class="mt-3 p-3 bg-gray-50 rounded text-sm">
                    <div class="font-medium text-gray-700 mb-1">First message:</div>
                    <div class="text-gray-600 line-clamp-2">{{ conversation.preview_message || conversation.preview }}</div>
                  </div>
                  
                  <!-- Tags -->
                  <div v-if="conversation.tags && conversation.tags.length > 0" class="mt-2 flex flex-wrap gap-1">
                    <span v-for="tag in conversation.tags" :key="tag" class="px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded-full">
                      {{ tag }}
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Time Entries Tab -->
          <div v-if="activeTab === 'time_entries'" class="space-y-4">
            <div class="bg-gray-50 rounded-lg p-4">
              <div class="flex items-center justify-between mb-3">
                <h4 class="font-medium text-gray-900">Sample Time Entries</h4>
                <span class="text-sm text-gray-500">Showing first {{ Math.min(mockTimeEntries.length, 10) }} of {{ mockTimeEntries.length }}</span>
              </div>
              <div class="space-y-3">
                <div
                  v-for="timeEntry in mockTimeEntries.slice(0, 10)"
                  :key="timeEntry.id"
                  class="bg-white rounded-lg border p-4 hover:shadow-sm transition-shadow"
                >
                  <div class="flex items-start justify-between">
                    <div class="flex-1">
                      <div class="flex items-center space-x-2 mb-2">
                        <span class="font-medium text-gray-900">{{ timeEntry.description }}</span>
                        <span 
                          :class="[
                            'px-2 py-1 rounded-full text-xs font-medium',
                            timeEntry.billable ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'
                          ]"
                        >
                          {{ timeEntry.billable ? 'Billable' : 'Non-billable' }}
                        </span>
                      </div>
                      <div class="text-sm text-gray-600 space-y-1">
                        <div><strong>Duration:</strong> {{ Math.floor(timeEntry.duration_minutes / 60) }}h {{ timeEntry.duration_minutes % 60 }}m</div>
                        <div><strong>Agent:</strong> {{ timeEntry.user_name }}</div>
                        <div v-if="timeEntry.user_email"><strong>Agent Email:</strong> {{ timeEntry.user_email }}</div>
                        <div><strong>Conversation:</strong> #{{ timeEntry.conversation_id }}</div>
                        <div v-if="timeEntry.conversation_subject" class="text-xs text-gray-500"><strong>Subject:</strong> {{ timeEntry.conversation_subject }}</div>
                        <div><strong>Customer:</strong> {{ timeEntry.customer_name || timeEntry.customer_email }}</div>
                        <div><strong>Mailbox:</strong> {{ timeEntry.mailbox }}</div>
                        <div><strong>Date:</strong> {{ new Date(timeEntry.created_at).toLocaleDateString() }}</div>
                      </div>
                    </div>
                    <div class="text-right text-xs text-gray-400">
                      ID: {{ timeEntry.id }}
                    </div>
                  </div>
                  <!-- Import preview -->
                  <div class="mt-3 p-2 bg-blue-50 rounded text-xs">
                    <div class="font-medium text-blue-700">Will be imported as:</div>
                    <div class="text-blue-600">Service Vault Time Entry ({{ Math.floor(timeEntry.duration_minutes / 60) }}:{{ String(timeEntry.duration_minutes % 60).padStart(2, '0') }})</div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Customers Tab -->
          <div v-if="activeTab === 'customers'" class="space-y-4">
            <div class="bg-gray-50 rounded-lg p-4">
              <div class="flex items-center justify-between mb-3">
                <h4 class="font-medium text-gray-900">Sample Customers</h4>
                <span class="text-sm text-gray-500">Showing first {{ Math.min(mockCustomers.length, 10) }} of {{ mockCustomers.length }}</span>
              </div>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div
                  v-for="customer in mockCustomers.slice(0, 10)"
                  :key="customer.id"
                  class="bg-white rounded-lg border p-4"
                >
                  <div class="flex items-start justify-between mb-2">
                    <div class="flex-1">
                      <div class="font-medium text-gray-900">
                        {{ customer.first_name }} {{ customer.last_name }}
                      </div>
                      <div class="text-sm text-gray-600">{{ customer.email }}</div>
                      <div v-if="customer.company" class="text-sm text-gray-500">{{ customer.company }}</div>
                    </div>
                    <div class="text-xs text-gray-400">
                      ID: {{ customer.id }}
                    </div>
                  </div>
                  <div class="text-sm text-gray-500">
                    <div><strong>Created:</strong> {{ new Date(customer.created_at).toLocaleDateString() }}</div>
                    <div><strong>Conversations:</strong> {{ customer.conversation_count }}</div>
                  </div>
                  <!-- Account mapping preview -->
                  <div class="mt-2 p-2 bg-blue-50 rounded text-xs">
                    <div class="font-medium text-blue-700">Will be assigned to:</div>
                    <div class="text-blue-600">{{ getAccountForCustomer(customer) }}</div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Mailboxes Tab -->
          <div v-if="activeTab === 'mailboxes'" class="space-y-4">
            <div class="bg-gray-50 rounded-lg p-4">
              <div class="flex items-center justify-between mb-3">
                <h4 class="font-medium text-gray-900">Mailboxes</h4>
                <span class="text-sm text-gray-500">{{ mockMailboxes.length }} mailboxes</span>
              </div>
              <div class="space-y-3">
                <div
                  v-for="mailbox in mockMailboxes"
                  :key="mailbox.id"
                  class="bg-white rounded-lg border p-4"
                >
                  <div class="flex items-start justify-between">
                    <div class="flex-1">
                      <div class="font-medium text-gray-900 mb-1">{{ mailbox.name }}</div>
                      <div class="text-sm text-gray-600 space-y-1">
                        <div><strong>Email:</strong> {{ mailbox.email }}</div>
                        <div><strong>Conversations:</strong> {{ mailbox.conversation_count }}</div>
                        <div><strong>Active Users:</strong> {{ mailbox.user_count }}</div>
                      </div>
                    </div>
                    <div class="text-xs text-gray-400">
                      ID: {{ mailbox.id }}
                    </div>
                  </div>
                  <!-- Account mapping preview -->
                  <div v-if="config.account_strategy === 'map_mailboxes'" class="mt-3 p-2 bg-green-50 rounded text-xs">
                    <div class="font-medium text-green-700">Will create account:</div>
                    <div class="text-green-600">{{ mailbox.name }}</div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Import Summary Tab -->
          <div v-if="activeTab === 'summary'" class="space-y-4">
            <div class="bg-gray-50 rounded-lg p-6">
              <h4 class="font-medium text-gray-900 mb-4">Import Summary</h4>
              
              <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
                <div class="bg-white rounded-lg p-4 text-center border">
                  <div class="text-3xl font-bold text-indigo-600">{{ estimatedCounts.conversations }}</div>
                  <div class="text-sm text-gray-600">Conversations</div>
                  <div class="text-xs text-gray-400 mt-1">{{ estimatedCounts.threads }} total messages</div>
                </div>
                <div class="bg-white rounded-lg p-4 text-center border">
                  <div class="text-3xl font-bold text-indigo-600">{{ estimatedCounts.time_entries }}</div>
                  <div class="text-sm text-gray-600">Time Entries</div>
                  <div class="text-xs text-gray-400 mt-1">{{ mockTimeEntries.reduce((sum, entry) => sum + entry.duration_minutes, 0) }} minutes total</div>
                </div>
                <div class="bg-white rounded-lg p-4 text-center border">
                  <div class="text-3xl font-bold text-indigo-600">{{ estimatedCounts.customers }}</div>
                  <div class="text-sm text-gray-600">Customers</div>
                  <div class="text-xs text-gray-400 mt-1">{{ estimatedCounts.new_customers }} new</div>
                </div>
                <div class="bg-white rounded-lg p-4 text-center border">
                  <div class="text-3xl font-bold text-indigo-600">{{ estimatedCounts.accounts }}</div>
                  <div class="text-sm text-gray-600">Accounts</div>
                  <div class="text-xs text-gray-400 mt-1">{{ estimatedCounts.new_accounts }} new</div>
                </div>
              </div>

              <div class="border-t pt-4">
                <h5 class="font-medium text-gray-900 mb-3">Import Actions</h5>
                <div class="space-y-2 text-sm">
                  <div class="flex items-center">
                    <CheckIcon class="w-4 h-4 text-green-500 mr-2" />
                    <span>
                      {{ props.config.sync_strategy === 'create_only' ? 'Create' : 
                         props.config.sync_strategy === 'update_only' ? 'Update' : 'Import' }} 
                      {{ estimatedCounts.conversations }} conversations as Service Vault tickets
                    </span>
                  </div>
                  <div class="flex items-center">
                    <CheckIcon class="w-4 h-4 text-green-500 mr-2" />
                    <span>
                      {{ props.config.sync_strategy === 'create_only' ? 'Create' : 
                         props.config.sync_strategy === 'update_only' ? 'Update' : 'Import' }} 
                      {{ estimatedCounts.time_entries }} time entries with {{ Math.floor(mockTimeEntries.reduce((sum, entry) => sum + entry.duration_minutes, 0) / 60) }} hours total
                    </span>
                  </div>
                  <div class="flex items-center">
                    <CheckIcon class="w-4 h-4 text-green-500 mr-2" />
                    <span>
                      {{ props.config.sync_strategy === 'create_only' ? 'Create' : 
                         props.config.sync_strategy === 'update_only' ? 'Update' : 'Create/Update' }} 
                      {{ estimatedCounts.new_customers }} customer records
                    </span>
                  </div>
                  <div class="flex items-center">
                    <CheckIcon class="w-4 h-4 text-green-500 mr-2" />
                    <span>
                      {{ config.account_strategy === 'map_mailboxes' ? 
                          `Create ${estimatedCounts.new_accounts} accounts from mailboxes` :
                          `Use existing domain mapping for account assignment` }}
                    </span>
                  </div>
                  <div v-if="config.enable_date_filter" class="flex items-center">
                    <CheckIcon class="w-4 h-4 text-green-500 mr-2" />
                    <span>Filter by date range: {{ config.date_range.start }} to {{ config.date_range.end }}</span>
                  </div>
                </div>
              </div>

              <!-- Duplicate Detection Summary -->
              <div class="mt-4 p-3 bg-gray-50 rounded-lg border border-gray-200">
                <div class="flex items-center mb-2">
                  <InformationCircleIcon class="w-4 h-4 text-gray-600 mr-2" />
                  <span class="font-medium text-gray-800">Duplicate Detection Summary</span>
                </div>
                <div class="text-sm text-gray-600 space-y-1">
                  <div>Method: {{ props.config.duplicate_detection === 'external_id' ? 'External ID tracking (FreeScout IDs)' : 
                                 props.config.duplicate_detection === 'content_match' ? 'Content matching (email, subject, etc.)' : 
                                 'Fuzzy matching with similarity scoring' }}</div>
                  <div v-if="props.config.duplicate_detection === 'external_id'">
                    External IDs will be stored in Service Vault for future sync runs
                  </div>
                  <div class="mt-2 text-xs">
                    <span class="font-medium">Estimated duplicates:</span> 
                    {{ Math.floor(parseInt(estimatedCounts.conversations.replace(/,/g, '')) * 0.05) }} conversations,
                    {{ Math.floor(parseInt(estimatedCounts.customers.replace(/,/g, '')) * 0.15) }} customers
                  </div>
                </div>
              </div>

              <!-- Warnings/Issues -->
              <div v-if="importWarnings.length > 0" class="mt-4 p-3 bg-yellow-50 rounded-lg border border-yellow-200">
                <div class="flex items-center mb-2">
                  <ExclamationTriangleIcon class="w-4 h-4 text-yellow-600 mr-2" />
                  <span class="font-medium text-yellow-800">Potential Issues</span>
                </div>
                <ul class="text-sm text-yellow-700 space-y-1">
                  <li v-for="warning in importWarnings" :key="warning" class="flex items-start">
                    <span class="mr-2">•</span>
                    <span>{{ warning }}</span>
                  </li>
                </ul>
              </div>
            </div>
          </div>
        </div>
      </div>
    </template>

    <template #actions>
      <div class="flex justify-between">
        <div class="text-sm text-gray-500">
          Estimated import time: {{ estimatedDuration }} minutes
        </div>
        <div class="flex space-x-3">
          <button
            @click="$emit('close')"
            class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
          >
            Cancel
          </button>
          <button
            @click="$emit('execute', { profile, config })"
            class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
          >
            Execute Import
          </button>
        </div>
      </div>
    </template>
  </StackedDialog>
</template>

<script setup>
import { ref, computed } from 'vue'
import StackedDialog from '@/Components/StackedDialog.vue'
import {
  InformationCircleIcon,
  CheckIcon,
  ExclamationTriangleIcon
} from '@heroicons/vue/24/outline'

// Props
const props = defineProps({
  profile: {
    type: Object,
    required: true
  },
  config: {
    type: Object,
    required: true
  },
  importData: {
    type: Object,
    default: null
  }
})

// Emits
const emit = defineEmits(['close', 'execute'])

// State
const show = ref(true)
const activeTab = ref('conversations')

// Use enhanced import data if available, otherwise fallback to basic mock data
const mockConversations = computed(() => {
  return props.importData?.conversations || [
    {
      id: 12547,
      subject: 'Unable to login to account',
      customer_email: 'john.doe@acme.com',
      mailbox: 'General Support',
      status: 'active',
      created_at: '2025-08-25T14:30:00Z',
      threads: 5,
      preview_message: 'Hi, I\'m having trouble logging into my account. I keep getting an error message that says my password is incorrect, but I\'m sure it\'s right. Can you help me reset it?'
    },
  {
    id: 12546,
    subject: 'Billing inquiry - Invoice #INV-2025-0847',
    customer_email: 'sarah.wilson@widgets.co',
    mailbox: 'Billing Support',
    status: 'pending',
    created_at: '2025-08-25T13:15:00Z',
    threads: 3,
    preview_message: 'Hello, I received invoice INV-2025-0847 but I notice there are some charges I don\'t recognize. Could someone please review this with me?'
  },
  {
    id: 12545,
    subject: 'Feature request: Dark mode support',
    customer_email: 'mike.tech@startup.app',
    mailbox: 'Product Feedback',
    status: 'closed',
    created_at: '2025-08-24T16:20:00Z',
    threads: 8,
    preview_message: 'Would love to see dark mode support in the application. Many of us work late hours and it would really help reduce eye strain.'
  },
  {
    id: 12544,
    subject: 'Integration setup assistance needed',
    customer_email: 'lisa.admin@example.org',
    mailbox: 'Technical Support',
    status: 'active',
    created_at: '2025-08-24T11:45:00Z',
    threads: 12,
    preview_message: 'We\'re trying to set up the API integration with our CRM system but running into some authentication issues. The documentation mentions OAuth but we\'re not sure how to configure it properly.'
  }
  ]
})

const mockCustomers = computed(() => {
  return props.importData?.customers || [
  {
    id: 5734,
    email: 'john.doe@acme.com',
    first_name: 'John',
    last_name: 'Doe',
    company: 'Acme Corporation',
    created_at: '2025-07-15T09:30:00Z',
    conversation_count: 8
  },
  {
    id: 5733,
    email: 'sarah.wilson@widgets.co',
    first_name: 'Sarah',
    last_name: 'Wilson',
    company: 'Widget Solutions Inc.',
    created_at: '2025-08-01T14:22:00Z',
    conversation_count: 3
  },
  {
    id: 5732,
    email: 'mike.tech@startup.app',
    first_name: 'Mike',
    last_name: 'Rodriguez',
    company: 'Startup Accelerator',
    created_at: '2025-06-20T10:15:00Z',
    conversation_count: 15
  },
  {
    id: 5731,
    email: 'lisa.admin@example.org',
    first_name: 'Lisa',
    last_name: 'Chen',
    company: 'Example Foundation',
    created_at: '2025-08-10T16:30:00Z',
    conversation_count: 6
  }
  ]
})

const mockTimeEntries = computed(() => {
  return props.importData?.time_entries || [
  {
    id: 8451,
    conversation_id: 12547,
    user_id: 15,
    user_name: 'John Support',
    description: 'Assisted with account login issue',
    duration_minutes: 25,
    created_at: '2025-08-25T14:30:00Z',
    mailbox: 'General Support',
    customer_email: 'john.doe@acme.com',
    billable: true
  },
  {
    id: 8452,
    conversation_id: 12546,
    user_id: 22,
    user_name: 'Sarah Billing',
    description: 'Reviewed invoice discrepancy',
    duration_minutes: 15,
    created_at: '2025-08-25T15:45:00Z',
    mailbox: 'Billing Support',
    customer_email: 'sarah.wilson@widgets.co',
    billable: true
  },
  {
    id: 8453,
    conversation_id: 12544,
    user_id: 18,
    user_name: 'Mike Tech',
    description: 'API integration troubleshooting',
    duration_minutes: 45,
    created_at: '2025-08-25T16:20:00Z',
    mailbox: 'Technical Support',
    customer_email: 'lisa.admin@example.org',
    billable: true
  },
  {
    id: 8454,
    conversation_id: 12543,
    user_id: 18,
    user_name: 'Mike Tech',
    description: 'Emergency server downtime response',
    duration_minutes: 120,
    created_at: '2025-08-23T23:30:00Z',
    mailbox: 'Technical Support',
    customer_email: 'ops@enterprise.com',
    billable: true
  },
  {
    id: 8455,
    conversation_id: 12547,
    user_id: 15,
    user_name: 'John Support',
    description: 'Follow-up on password reset solution',
    duration_minutes: 10,
    created_at: '2025-08-25T16:45:00Z',
    mailbox: 'General Support',
    customer_email: 'john.doe@acme.com',
    billable: true
  }
  ]
})

const mockMailboxes = computed(() => {
  return props.importData?.mailboxes || [
  {
    id: 101,
    name: 'General Support',
    email: 'support@company.com',
    conversation_count: 1247,
    user_count: 8
  },
  {
    id: 102,
    name: 'Billing Support',
    email: 'billing@company.com',
    conversation_count: 342,
    user_count: 3
  },
  {
    id: 103,
    name: 'Technical Support',
    email: 'tech@company.com',
    conversation_count: 856,
    user_count: 12
  },
  {
    id: 104,
    name: 'Product Feedback',
    email: 'feedback@company.com',
    conversation_count: 189,
    user_count: 2
  }
  ]
})

// Computed properties
const previewTabs = computed(() => [
  { id: 'conversations', name: 'Conversations', count: mockConversations.value.length.toLocaleString() },
  { id: 'time_entries', name: 'Time Entries', count: mockTimeEntries.value.length.toLocaleString() },
  { id: 'customers', name: 'Customers', count: mockCustomers.value.length.toLocaleString() },
  { id: 'mailboxes', name: 'Mailboxes', count: mockMailboxes.value.length.toLocaleString() },
  { id: 'summary', name: 'Summary', count: '' }
])

const estimatedCounts = computed(() => {
  const conversations = Math.min(
    props.config.limits.conversations || mockConversations.value.length,
    mockConversations.value.length
  )
  const timeEntries = Math.min(
    props.config.limits.time_entries || mockTimeEntries.value.length,
    mockTimeEntries.value.length
  )
  const customers = Math.min(
    props.config.limits.customers || mockCustomers.value.length,
    mockCustomers.value.length
  )
  
  let accounts = 0
  if (props.config.account_strategy === 'map_mailboxes') {
    accounts = Math.min(
      props.config.limits.mailboxes || mockMailboxes.value.length,
      mockMailboxes.value.length
    )
  } else {
    accounts = 5 // Existing domain mappings
  }
  
  return {
    conversations: conversations.toLocaleString(),
    time_entries: timeEntries.toLocaleString(),
    customers: customers.toLocaleString(),
    accounts: accounts.toLocaleString(),
    threads: (conversations * 4).toLocaleString(), // Estimated 4 messages per conversation
    new_customers: Math.floor(customers * 0.7).toLocaleString(), // 70% estimated as new
    new_accounts: accounts.toLocaleString()
  }
})

const estimatedDuration = computed(() => {
  const totalItems = mockConversations.value.length + mockCustomers.value.length + mockMailboxes.value.length
  return Math.ceil(totalItems / 50) // Estimate 50 items per minute
})

const importWarnings = computed(() => {
  const warnings = []
  
  if (props.config.account_strategy === 'domain_mapping' && props.config.unmapped_users === 'skip') {
    warnings.push('Some customers may be skipped if their email domains don\'t match existing mappings')
  }
  
  if (!props.config.limits.conversations && mockConversations.value.length > 1000) {
    warnings.push('Large number of conversations may impact import performance')
  }
  
  if (props.profile.status !== 'connected') {
    warnings.push('API connection test has not been completed successfully')
  }
  
  return warnings
})

// Methods
const getAccountForCustomer = (customer) => {
  if (props.config.account_strategy === 'map_mailboxes') {
    return 'Based on conversation mailbox'
  }
  
  const domain = customer.email.split('@')[1]
  const domainMappings = {
    'acme.com': 'Acme Corporation',
    'widgets.co': 'Widget Solutions Inc.',
    'startup.app': 'Startup Accelerator',
    'example.org': 'Example Foundation'
  }
  
  return domainMappings[domain] || 'Will be auto-created or skipped'
}
</script>