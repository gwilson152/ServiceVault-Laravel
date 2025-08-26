<template>
  <div class="space-y-8">
    <!-- Sync Strategy & Duplicate Detection -->
    <div class="bg-white border border-gray-200 rounded-lg p-6">
      <div class="flex items-center mb-4">
        <ArrowPathIcon class="w-5 h-5 text-gray-400 mr-2" />
        <h3 class="text-lg font-medium text-gray-900">Sync Strategy & Duplicate Detection</h3>
      </div>
      <p class="text-sm text-gray-600 mb-6">
        Configure how the import handles existing data and duplicates.
      </p>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Sync Strategy -->
        <div>
          <h4 class="text-sm font-medium text-gray-900 mb-3">Sync Strategy</h4>
          <div class="space-y-3">
            <label class="relative flex items-start">
              <div class="flex items-center h-5">
                <input
                  v-model="localConfig.sync_strategy"
                  value="create_only"
                  type="radio"
                  class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300"
                />
              </div>
              <div class="ml-3">
                <div class="text-sm font-medium text-gray-900">Create only</div>
                <div class="text-sm text-gray-500">Skip existing records, create new ones only</div>
              </div>
            </label>

            <label class="relative flex items-start">
              <div class="flex items-center h-5">
                <input
                  v-model="localConfig.sync_strategy"
                  value="update_only"
                  type="radio"
                  class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300"
                />
              </div>
              <div class="ml-3">
                <div class="text-sm font-medium text-gray-900">Update only</div>
                <div class="text-sm text-gray-500">Update existing records, skip new ones</div>
              </div>
            </label>

            <label class="relative flex items-start">
              <div class="flex items-center h-5">
                <input
                  v-model="localConfig.sync_strategy"
                  value="upsert"
                  type="radio"
                  class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300"
                />
              </div>
              <div class="ml-3">
                <div class="text-sm font-medium text-gray-900">Upsert (recommended)</div>
                <div class="text-sm text-gray-500">Create new and update existing records</div>
              </div>
            </label>
          </div>
        </div>

        <!-- Duplicate Detection -->
        <div>
          <h4 class="text-sm font-medium text-gray-900 mb-3">Duplicate Detection</h4>
          <div class="space-y-3">
            <label class="relative flex items-start">
              <div class="flex items-center h-5">
                <input
                  v-model="localConfig.duplicate_detection"
                  value="external_id"
                  type="radio"
                  class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300"
                />
              </div>
              <div class="ml-3">
                <div class="text-sm font-medium text-gray-900">External ID matching</div>
                <div class="text-sm text-gray-500">Use FreeScout IDs for exact matching</div>
              </div>
            </label>

            <label class="relative flex items-start">
              <div class="flex items-center h-5">
                <input
                  v-model="localConfig.duplicate_detection"
                  value="content_match"
                  type="radio"
                  class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300"
                />
              </div>
              <div class="ml-3">
                <div class="text-sm font-medium text-gray-900">Content matching</div>
                <div class="text-sm text-gray-500">Match by content, timestamps, etc.</div>
              </div>
            </label>
          </div>
        </div>
      </div>
    </div>

    <!-- Date Range Filter -->
    <div class="bg-white border border-gray-200 rounded-lg p-6">
      <div class="flex items-center mb-4">
        <CalendarIcon class="w-5 h-5 text-gray-400 mr-2" />
        <h3 class="text-lg font-medium text-gray-900">Date Range Filter</h3>
      </div>
      <p class="text-sm text-gray-600 mb-4">
        Optionally filter imported data by date range.
      </p>

      <div class="space-y-4">
        <label class="flex items-center">
          <input
            v-model="localConfig.date_range.enabled"
            type="checkbox"
            class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
          />
          <span class="ml-2 text-sm font-medium text-gray-900">Enable date range filtering</span>
        </label>

        <div v-if="localConfig.date_range.enabled" class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
          <div>
            <label for="start-date" class="block text-sm font-medium text-gray-700 mb-2">
              Start Date
            </label>
            <input
              id="start-date"
              v-model="localConfig.date_range.start_date"
              type="date"
              class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
            />
          </div>

          <div>
            <label for="end-date" class="block text-sm font-medium text-gray-700 mb-2">
              End Date
            </label>
            <input
              id="end-date"
              v-model="localConfig.date_range.end_date"
              type="date"
              class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
            />
          </div>
        </div>
      </div>
    </div>

    <!-- Mailbox Exclusions -->
    <div class="bg-white border border-gray-200 rounded-lg p-6">
      <div class="flex items-center mb-4">
        <InboxIcon class="w-5 h-5 text-gray-400 mr-2" />
        <h3 class="text-lg font-medium text-gray-900">Mailbox Exclusions</h3>
      </div>
      <p class="text-sm text-gray-600 mb-6">
        Select mailboxes to exclude from the import.
      </p>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div
          v-for="mailbox in previewData.mailboxes"
          :key="mailbox.id"
          :class="[
            'relative rounded-lg p-4 border-2 cursor-pointer transition-colors',
            localConfig.excluded_mailboxes.includes(mailbox.id)
              ? 'border-red-200 bg-red-50'
              : 'border-green-200 bg-green-50'
          ]"
        >
          <div class="flex items-center h-5">
            <input
              :id="`mailbox-${mailbox.id}`"
              v-model="localConfig.excluded_mailboxes"
              :value="mailbox.id"
              type="checkbox"
              class="h-4 w-4 text-red-600 focus:ring-red-500 border-gray-300 rounded"
            />
          </div>
          <div class="ml-3 flex-1">
            <label 
              :for="`mailbox-${mailbox.id}`" 
              :class="[
                'block text-sm font-medium cursor-pointer',
                localConfig.excluded_mailboxes.includes(mailbox.id) ? 'text-red-900 line-through' : 'text-green-900'
              ]"
            >
              {{ mailbox.name }}
            </label>
            <div class="flex items-center space-x-4 text-xs mt-1">
              <span>{{ mailbox.email }}</span>
              <span>•</span>
              <span>{{ (mailbox.conversation_count || 0).toLocaleString() }} conversations</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import {
  ArrowPathIcon,
  CalendarIcon,
  InboxIcon
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

const localConfig = computed({
  get() {
    return props.config
  },
  set(value) {
    emit('update-config', value)
  }
})
</script>