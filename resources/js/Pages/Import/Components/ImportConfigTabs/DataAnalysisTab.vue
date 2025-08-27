<template>
  <div class="space-y-8">
    <!-- Data Relationship Analysis Section -->
    <div class="bg-white border border-gray-200 rounded-lg p-6">
      <div class="flex items-center mb-4">
        <ShareIcon class="w-5 h-5 text-gray-400 mr-2" />
        <h3 class="text-lg font-medium text-gray-900">Data Relationship Analysis</h3>
      </div>
      <p class="text-sm text-gray-600 mb-6">
        Analysis of relationships between FreeScout data and how they'll be mapped to Service Vault.
      </p>

      <!-- Placeholder content for now -->
      <div class="bg-gray-50 rounded-lg p-8 text-center">
        <ChartBarIcon class="mx-auto h-12 w-12 text-gray-400" />
        <h3 class="mt-2 text-sm font-medium text-gray-900">Data Analysis</h3>
        <p class="mt-1 text-sm text-gray-500">
          Relationship analysis and data mapping configuration will be displayed here.
        </p>
      </div>
    </div>

    <!-- Time Entry Mapping Configuration -->
    <div class="bg-white border border-gray-200 rounded-lg p-6">
      <div class="flex items-center mb-4">
        <ClockIcon class="w-5 h-5 text-gray-400 mr-2" />
        <h3 class="text-lg font-medium text-gray-900">Time Entry Mapping</h3>
      </div>
      <p class="text-sm text-gray-600 mb-4">
        Configure how FreeScout time entries should be imported.
      </p>

      <div class="space-y-4">
        <label class="relative flex items-start">
          <div class="flex items-center h-5">
            <input
              v-model="localConfig.time_entry_mapping"
              value="preserve_original"
              type="radio"
              class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300"
            />
          </div>
          <div class="ml-3">
            <div class="text-sm font-medium text-gray-900">Preserve original time entries</div>
            <div class="text-sm text-gray-500">Import time entries as recorded in FreeScout with original timestamps.</div>
          </div>
        </label>

        <label class="relative flex items-start">
          <div class="flex items-center h-5">
            <input
              v-model="localConfig.time_entry_mapping"
              value="aggregate_by_day"
              type="radio"
              class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300"
            />
          </div>
          <div class="ml-3">
            <div class="text-sm font-medium text-gray-900">Aggregate by day</div>
            <div class="text-sm text-gray-500">Combine multiple time entries per day into single entries.</div>
          </div>
        </label>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import {
  ShareIcon,
  ChartBarIcon,
  ClockIcon
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