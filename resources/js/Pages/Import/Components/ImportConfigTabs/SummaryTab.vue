<template>
  <div class="space-y-8">
    <!-- Import Summary -->
    <div class="bg-indigo-50 border border-indigo-200 rounded-lg p-6">
      <div class="flex items-center mb-4">
        <InformationCircleIcon class="w-5 h-5 text-indigo-600 mr-2" />
        <h3 class="text-lg font-medium text-gray-900">Import Summary</h3>
      </div>
      <p class="text-sm text-gray-600 mb-6">
        Review your import configuration and estimated results.
      </p>

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="text-center">
          <div class="text-2xl font-bold text-indigo-600">{{ estimatedCounts.conversations }}</div>
          <div class="text-sm text-gray-600">Conversations</div>
        </div>
        <div class="text-center">
          <div class="text-2xl font-bold text-indigo-600">{{ estimatedCounts.customers }}</div>
          <div class="text-sm text-gray-600">Customers</div>
        </div>
        <div class="text-center">
          <div class="text-2xl font-bold text-indigo-600">{{ estimatedCounts.time_entries }}</div>
          <div class="text-sm text-gray-600">Time Entries</div>
        </div>
        <div class="text-center">
          <div class="text-2xl font-bold text-indigo-600">{{ estimatedCounts.accounts }}</div>
          <div class="text-sm text-gray-600">Accounts</div>
        </div>
      </div>
    </div>

    <!-- Configuration Review -->
    <div class="bg-white border border-gray-200 rounded-lg p-6">
      <h3 class="text-lg font-medium text-gray-900 mb-4">Configuration Review</h3>
      
      <div class="space-y-4">
        <div class="flex justify-between items-center py-2 border-b border-gray-100">
          <span class="text-sm font-medium text-gray-900">Account Strategy</span>
          <span class="text-sm text-gray-600">
            {{ config.account_strategy === 'mailbox_per_account' ? 'One account per mailbox' :
               config.account_strategy === 'single_account' ? 'Single account for all data' :
               config.account_strategy === 'domain_mapping' ? 'Domain-based user matching' :
               config.account_strategy === 'domain_mapping_strict' ? 'Domain-based strict matching' : 'Unknown strategy' }}
          </span>
        </div>
        
        <div class="flex justify-between items-center py-2 border-b border-gray-100">
          <span class="text-sm font-medium text-gray-900">User Strategy</span>
          <span class="text-sm text-gray-600">
            {{ config.user_strategy === 'map_emails' ? 'Map by email address' :
               config.user_strategy === 'create_new' ? 'Create new users' : 'Skip user import' }}
          </span>
        </div>

        <div class="flex justify-between items-center py-2 border-b border-gray-100">
          <span class="text-sm font-medium text-gray-900">Sync Strategy</span>
          <span class="text-sm text-gray-600">
            {{ config.sync_strategy === 'create_only' ? 'Create only' :
               config.sync_strategy === 'update_only' ? 'Update only' : 'Upsert (create + update)' }}
          </span>
        </div>

        <div class="flex justify-between items-center py-2 border-b border-gray-100">
          <span class="text-sm font-medium text-gray-900">Date Range</span>
          <span class="text-sm text-gray-600">
            {{ config.date_range.enabled ? 
               `${config.date_range.start_date || 'No start'} to ${config.date_range.end_date || 'No end'}` : 
               'All dates' }}
          </span>
        </div>

        <div class="flex justify-between items-center py-2">
          <span class="text-sm font-medium text-gray-900">Excluded Mailboxes</span>
          <span class="text-sm text-gray-600">
            {{ config.excluded_mailboxes?.length || 0 }} excluded
          </span>
        </div>
      </div>
    </div>

    <!-- Import Actions -->
    <div class="bg-gray-50 rounded-lg p-6">
      <h3 class="text-lg font-medium text-gray-900 mb-4">Ready to Import</h3>
      <p class="text-sm text-gray-600 mb-6">
        You can preview the import to see what data will be imported, or start the import process directly.
      </p>

      <div class="flex space-x-4">
        <button
          @click="$emit('preview', config)"
          class="flex-1 px-4 py-3 text-sm font-medium text-indigo-600 bg-white border border-indigo-300 rounded-md hover:bg-indigo-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
        >
          <DocumentMagnifyingGlassIcon class="w-4 h-4 mr-2 inline" />
          Preview Import
        </button>
        
        <button
          @click="$emit('execute', config)"
          class="flex-1 px-4 py-3 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
        >
          <PlayIcon class="w-4 h-4 mr-2 inline" />
          Start Import
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import {
  InformationCircleIcon,
  DocumentMagnifyingGlassIcon,
  PlayIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
  config: {
    type: Object,
    required: true
  },
  previewData: {
    type: Object,
    required: true
  },
  profile: {
    type: Object,
    required: true
  },
  statistics: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['preview', 'execute'])

const estimatedCounts = computed(() => {
  // With simplified approach - no limits, all data determined by date range and dependencies
  const conversationCount = props.statistics?.conversations || 0
  const customerCount = props.statistics?.customers || 0
  const timeEntriesCount = props.statistics?.time_entries || 0
  const mailboxCount = props.statistics?.mailboxes || 0
  
  // Estimate accounts based on strategy
  let accountCount = 1
  if (props.config.account_strategy === 'mailbox_per_account') {
    accountCount = mailboxCount - (props.config.excluded_mailboxes?.length || 0)
  } else if (props.config.account_strategy === 'single_account') {
    accountCount = 1
  } else if (props.config.account_strategy === 'domain_mapping' || props.config.account_strategy === 'domain_mapping_strict') {
    // For domain mapping, show that existing accounts will be used
    accountCount = 'Uses existing accounts'
  }

  return {
    conversations: conversationCount.toLocaleString(),
    customers: customerCount.toLocaleString(),
    time_entries: timeEntriesCount.toLocaleString(),
    accounts: typeof accountCount === 'string' ? accountCount : accountCount.toLocaleString()
  }
})
</script>