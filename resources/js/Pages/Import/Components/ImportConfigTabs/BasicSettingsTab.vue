<template>
  <div class="space-y-8">
    <!-- Date Range Filter Section -->
    <div class="bg-gray-50 rounded-lg p-6">
      <div class="flex items-center mb-4">
        <CalendarDaysIcon class="w-5 h-5 text-gray-400 mr-2" />
        <h3 class="text-lg font-medium text-gray-900">Import Date Range</h3>
      </div>
      <p class="text-sm text-gray-600 mb-6">
        Filter conversations by creation date. All related users, comments, and time entries will be automatically imported as needed.
      </p>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Start Date -->
        <div>
          <label for="start-date" class="block text-sm font-medium text-gray-700 mb-2">
            From Date (optional)
          </label>
          <input
            id="start-date"
            v-model="localConfig.date_range.start_date"
            type="date"
            class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
          />
          <p class="mt-1 text-xs text-gray-500">
            Import conversations created on or after this date
          </p>
        </div>

        <!-- End Date -->
        <div>
          <label for="end-date" class="block text-sm font-medium text-gray-700 mb-2">
            To Date (optional)
          </label>
          <input
            id="end-date"
            v-model="localConfig.date_range.end_date"
            type="date"
            class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
          />
          <p class="mt-1 text-xs text-gray-500">
            Import conversations created on or before this date
          </p>
        </div>
      </div>

      <div class="mt-4 bg-blue-50 border border-blue-200 rounded-lg p-4">
        <div class="flex">
          <div class="flex-shrink-0">
            <InformationCircleIcon class="h-5 w-5 text-blue-400" />
          </div>
          <div class="ml-3">
            <p class="text-sm text-blue-800">
              <strong>Automatic Dependency Resolution:</strong> When importing conversations in this date range, 
              the system will automatically import any required users, customers, and mailboxes that don't already exist.
            </p>
          </div>
        </div>
      </div>
    </div>

    <!-- Simple Configuration Section -->
    <div class="bg-white border border-gray-200 rounded-lg p-6">
      <div class="flex items-center mb-4">
        <CogIcon class="w-5 h-5 text-gray-400 mr-2" />
        <h3 class="text-lg font-medium text-gray-900">Import Configuration</h3>
      </div>
      <p class="text-sm text-gray-600 mb-6">
        Configure how FreeScout data should be imported into Service Vault.
      </p>

      <div class="space-y-6">
        <!-- Account Organization -->
        <div>
          <h4 class="text-sm font-medium text-gray-900 mb-3">Account Organization</h4>
          <div class="space-y-3">
            <label class="relative flex items-start">
              <div class="flex items-center h-5">
                <input
                  v-model="localConfig.account_strategy"
                  value="mailbox_per_account"
                  type="radio"
                  class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300"
                />
              </div>
              <div class="ml-3">
                <div class="text-sm font-medium text-gray-900">One account per mailbox</div>
                <div class="text-sm text-gray-500">Each FreeScout mailbox becomes a separate Service Vault account.</div>
              </div>
            </label>

            <label class="relative flex items-start">
              <div class="flex items-center h-5">
                <input
                  v-model="localConfig.account_strategy"
                  value="single_account"
                  type="radio"
                  class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300"
                />
              </div>
              <div class="ml-3">
                <div class="text-sm font-medium text-gray-900">Single account for all data</div>
                <div class="text-sm text-gray-500">Import all FreeScout data into one Service Vault account.</div>
              </div>
            </label>

            <label class="relative flex items-start">
              <div class="flex items-center h-5">
                <input
                  v-model="localConfig.account_strategy"
                  value="domain_mapping"
                  type="radio"
                  class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300"
                />
              </div>
              <div class="ml-3">
                <div class="text-sm font-medium text-gray-900">Domain-based with fallback</div>
                <div class="text-sm text-gray-500">Use domain mappings where they exist, create accounts based on customer information for unmapped domains.</div>
              </div>
            </label>

            <label class="relative flex items-start">
              <div class="flex items-center h-5">
                <input
                  v-model="localConfig.account_strategy"
                  value="domain_mapping_strict"
                  type="radio"
                  class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300"
                />
              </div>
              <div class="ml-3">
                <div class="text-sm font-medium text-gray-900">Domain-based strict matching (recommended)</div>
                <div class="text-sm text-gray-500">Skip users and conversations with unmapped domains entirely. Only import records with matching domain mappings.</div>
              </div>
            </label>
          </div>
        </div>

        <!-- Agent Import -->
        <div>
          <h4 class="text-sm font-medium text-gray-900 mb-3">Agent Import</h4>
          <div class="space-y-3">
            <label class="relative flex items-start">
              <div class="flex items-center h-5">
                <input
                  v-model="localConfig.agent_import_strategy"
                  value="create_new"
                  type="radio"
                  class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300"
                />
              </div>
              <div class="ml-3">
                <div class="text-sm font-medium text-gray-900">Import FreeScout users as new agents</div>
                <div class="text-sm text-gray-500">Creates new Service Vault agents from FreeScout users with Agent role.</div>
              </div>
            </label>

            <label class="relative flex items-start">
              <div class="flex items-center h-5">
                <input
                  v-model="localConfig.agent_import_strategy"
                  value="match_existing"
                  type="radio"
                  class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300"
                />
              </div>
              <div class="ml-3">
                <div class="text-sm font-medium text-gray-900">Match to existing agents by email (recommended)</div>
                <div class="text-sm text-gray-500">Links FreeScout users to existing Service Vault agents based on email matching. No new agents created.</div>
              </div>
            </label>

            <label class="relative flex items-start">
              <div class="flex items-center h-5">
                <input
                  v-model="localConfig.agent_import_strategy"
                  value="skip"
                  type="radio"
                  class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300"
                />
              </div>
              <div class="ml-3">
                <div class="text-sm font-medium text-gray-900">Skip agent import</div>
                <div class="text-sm text-gray-500">Don't import or match FreeScout users. All imported data will be unassigned.</div>
              </div>
            </label>
          </div>
        </div>

        <!-- Error Handling -->
        <div>
          <h4 class="text-sm font-medium text-gray-900 mb-3">Error Handling</h4>
          <div class="space-y-3">
            <label class="relative flex items-start">
              <div class="flex items-center h-5">
                <input
                  v-model="localConfig.continue_on_error"
                  :value="true"
                  type="radio"
                  class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300"
                />
              </div>
              <div class="ml-3">
                <div class="text-sm font-medium text-gray-900">Continue on validation errors (recommended)</div>
                <div class="text-sm text-gray-500">Skip invalid records and continue importing. All errors will be logged.</div>
              </div>
            </label>

            <label class="relative flex items-start">
              <div class="flex items-center h-5">
                <input
                  v-model="localConfig.continue_on_error"
                  :value="false"
                  type="radio"
                  class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300"
                />
              </div>
              <div class="ml-3">
                <div class="text-sm font-medium text-gray-900">Stop on first error</div>
                <div class="text-sm text-gray-500">Stop the import process when the first validation error occurs.</div>
              </div>
            </label>
          </div>
        </div>
      </div>
    </div>

    <!-- Import Information -->
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
      <div class="flex items-center mb-3">
        <InformationCircleIcon class="w-5 h-5 text-blue-600 mr-2" />
        <h3 class="text-lg font-medium text-blue-900">Import Process</h3>
      </div>
      <div class="text-sm text-blue-800 space-y-2">
        <p><strong>Primary Import:</strong> Conversations (tickets) within the specified date range</p>
        <p><strong>Automatic Dependencies:</strong> Required users, customers, mailboxes imported automatically as needed</p>
        <p><strong>Complete Data:</strong> All threads (comments) and time entries for imported conversations are included</p>
        <p><strong>Relationships:</strong> Time entries always linked to their conversation tickets, comments to tickets and users</p>
        <p><strong>Validation:</strong> Records missing required fields will be skipped and logged with specific reasons</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, watch } from 'vue'
import {
  CalendarDaysIcon,
  CogIcon,
  InformationCircleIcon
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
  statistics: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['update-config'])

// Create a local copy of config that emits changes
const localConfig = computed({
  get() {
    return props.config
  },
  set(value) {
    emit('update-config', value)
  }
})

// Watch for deep changes in config
watch(
  () => props.config,
  (newConfig) => {
    emit('update-config', newConfig)
  },
  { deep: true }
)
</script>