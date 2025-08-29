<template>
  <div class="space-y-8">
    <!-- Sync Strategy -->
    <div class="bg-white border border-gray-200 rounded-lg p-6">
      <div class="flex items-center mb-4">
        <ArrowPathIcon class="w-5 h-5 text-gray-400 mr-2" />
        <h3 class="text-lg font-medium text-gray-900">Sync Strategy & Duplicate Detection</h3>
      </div>
      <p class="text-sm text-gray-600 mb-6">
        Configure how the import handles existing data.
      </p>

      <div class="max-w-md">
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
  },
  statistics: {
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