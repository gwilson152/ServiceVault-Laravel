<template>
  <div class="space-y-8">
    <!-- Import Limits Section -->
    <div class="bg-gray-50 rounded-lg p-6">
      <div class="flex items-center mb-4">
        <AdjustmentsHorizontalIcon class="w-5 h-5 text-gray-400 mr-2" />
        <h3 class="text-lg font-medium text-gray-900">Import Limits</h3>
      </div>
      <p class="text-sm text-gray-600 mb-6">
        Set limits for each data type to control the size of your import. Leave empty for no limit.
      </p>

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Conversations Limit -->
        <div>
          <label for="conversations-limit" class="block text-sm font-medium text-gray-700 mb-2">
            Conversations
          </label>
          <input
            id="conversations-limit"
            v-model.number="localConfig.limits.conversations"
            type="number"
            min="0"
            placeholder="No limit"
            class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
          />
          <p class="mt-1 text-xs text-gray-500">
            Available: {{ (previewData.conversations?.length || 0).toLocaleString() }}
          </p>
        </div>

        <!-- Time Entries Limit -->
        <div>
          <label for="time-entries-limit" class="block text-sm font-medium text-gray-700 mb-2">
            Time Entries
          </label>
          <input
            id="time-entries-limit"
            v-model.number="localConfig.limits.time_entries"
            type="number"
            min="0"
            placeholder="No limit"
            class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
          />
          <p class="mt-1 text-xs text-gray-500">
            Available: {{ (previewData.time_entries?.length || 0).toLocaleString() }}
          </p>
        </div>

        <!-- Customers Limit -->
        <div>
          <label for="customers-limit" class="block text-sm font-medium text-gray-700 mb-2">
            Customers
          </label>
          <input
            id="customers-limit"
            v-model.number="localConfig.limits.customers"
            type="number"
            min="0"
            placeholder="No limit"
            class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
          />
          <p class="mt-1 text-xs text-gray-500">
            Available: {{ (previewData.customers?.length || 0).toLocaleString() }}
          </p>
        </div>

        <!-- Mailboxes Limit -->
        <div>
          <label for="mailboxes-limit" class="block text-sm font-medium text-gray-700 mb-2">
            Mailboxes
          </label>
          <input
            id="mailboxes-limit"
            v-model.number="localConfig.limits.mailboxes"
            type="number"
            min="0"
            placeholder="No limit"
            class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
          />
          <p class="mt-1 text-xs text-gray-500">
            Available: {{ (previewData.mailboxes?.length || 0).toLocaleString() }}
          </p>
        </div>
      </div>
    </div>

    <!-- Account Mapping Strategy Section -->
    <div class="bg-white border border-gray-200 rounded-lg p-6">
      <div class="flex items-center mb-4">
        <BuildingOfficeIcon class="w-5 h-5 text-gray-400 mr-2" />
        <h3 class="text-lg font-medium text-gray-900">Account Mapping Strategy</h3>
      </div>
      <p class="text-sm text-gray-600 mb-4">
        Choose how FreeScout data should be organized into Service Vault accounts.
      </p>

      <div class="space-y-4">
        <label class="relative flex items-start">
          <div class="flex items-center h-5">
            <input
              v-model="localConfig.account_strategy"
              value="map_mailboxes"
              type="radio"
              class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300"
            />
          </div>
          <div class="ml-3">
            <div class="text-sm font-medium text-gray-900">Map mailboxes to accounts</div>
            <div class="text-sm text-gray-500">Create a separate account for each FreeScout mailbox. Recommended for organized data separation.</div>
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
            <div class="text-sm font-medium text-gray-900">Use domain mapping</div>
            <div class="text-sm text-gray-500">Group customers by email domain into accounts. Good for B2B customer organization.</div>
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
            <div class="text-sm font-medium text-gray-900">Single account</div>
            <div class="text-sm text-gray-500">Import all data into one account. Simple but may not scale well.</div>
          </div>
        </label>
      </div>
    </div>

    <!-- User Import Strategy Section -->
    <div class="bg-white border border-gray-200 rounded-lg p-6">
      <div class="flex items-center mb-4">
        <UserPlusIcon class="w-5 h-5 text-gray-400 mr-2" />
        <h3 class="text-lg font-medium text-gray-900">User Import Strategy</h3>
      </div>
      <p class="text-sm text-gray-600 mb-4">
        Configure how FreeScout users should be handled during import.
      </p>

      <div class="space-y-4">
        <label class="relative flex items-start">
          <div class="flex items-center h-5">
            <input
              v-model="localConfig.user_strategy"
              value="map_emails"
              type="radio"
              class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300"
            />
          </div>
          <div class="ml-3">
            <div class="text-sm font-medium text-gray-900">Map by email address</div>
            <div class="text-sm text-gray-500">Match FreeScout users to Service Vault users by email. Create new users if no match found.</div>
          </div>
        </label>

        <label class="relative flex items-start">
          <div class="flex items-center h-5">
            <input
              v-model="localConfig.user_strategy"
              value="create_new"
              type="radio"
              class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300"
            />
          </div>
          <div class="ml-3">
            <div class="text-sm font-medium text-gray-900">Create new users</div>
            <div class="text-sm text-gray-500">Always create new users from FreeScout data, even if emails match existing users.</div>
          </div>
        </label>

        <label class="relative flex items-start">
          <div class="flex items-center h-5">
            <input
              v-model="localConfig.user_strategy"
              value="skip_users"
              type="radio"
              class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300"
            />
          </div>
          <div class="ml-3">
            <div class="text-sm font-medium text-gray-900">Skip user import</div>
            <div class="text-sm text-gray-500">Don't import FreeScout users. Assign all imported data to current user.</div>
          </div>
        </label>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, watch } from 'vue'
import {
  AdjustmentsHorizontalIcon,
  BuildingOfficeIcon,
  UserPlusIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
  config: {
    type: Object,
    required: true
  },
  previewData: {
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