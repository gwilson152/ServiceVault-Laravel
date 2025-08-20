<template>
  <div class="space-y-6">
    <!-- Data Type Selection -->
    <div>
      <h3 class="text-lg font-medium text-gray-900 mb-4">Select Data Types to Import</h3>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <label v-for="table in availableTables" :key="table.key" class="relative flex items-start">
          <div class="flex items-center h-5">
            <input
              :id="`table-${table.key}`"
              v-model="selectedTables"
              :value="table.key"
              type="checkbox"
              class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 rounded"
            />
          </div>
          <div class="ml-3 text-sm">
            <label :for="`table-${table.key}`" class="font-medium text-gray-700">
              {{ table.title }}
            </label>
            <p class="text-gray-500">{{ table.description }}</p>
          </div>
        </label>
      </div>
    </div>

    <!-- Date Range Filter -->
    <div>
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-medium text-gray-900">Date Range Filter</h3>
        <label class="inline-flex items-center">
          <input
            v-model="enableDateFilter"
            type="checkbox"
            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
          />
          <span class="ml-2 text-sm text-gray-600">Enable date filtering</span>
        </label>
      </div>
      
      <div v-if="enableDateFilter" class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            From Date
          </label>
          <input
            v-model="dateFrom"
            type="date"
            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
            placeholder="Start date"
          />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            To Date
          </label>
          <input
            v-model="dateTo"
            type="date"
            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
            placeholder="End date"
          />
        </div>
      </div>
    </div>

    <!-- Ticket Status Filter -->
    <div>
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-medium text-gray-900">Ticket Status Filter</h3>
        <label class="inline-flex items-center">
          <input
            v-model="enableStatusFilter"
            type="checkbox"
            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
          />
          <span class="ml-2 text-sm text-gray-600">Enable status filtering</span>
        </label>
      </div>
      
      <div v-if="enableStatusFilter" class="space-y-3">
        <p class="text-sm text-gray-600">Select ticket statuses to import (FreeScout conversations only):</p>
        <div class="space-y-2">
          <label v-for="status in ticketStatuses" :key="status.value" class="inline-flex items-center mr-6">
            <input
              v-model="selectedStatuses"
              :value="status.value"
              type="checkbox"
              class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
            />
            <span class="ml-2 text-sm text-gray-700">{{ status.label }}</span>
          </label>
        </div>
      </div>
    </div>

    <!-- Record Limit -->
    <div>
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-medium text-gray-900">Record Limit</h3>
        <label class="inline-flex items-center">
          <input
            v-model="enableRecordLimit"
            type="checkbox"
            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
          />
          <span class="ml-2 text-sm text-gray-600">Limit records for testing</span>
        </label>
      </div>
      
      <div v-if="enableRecordLimit">
        <label class="block text-sm font-medium text-gray-700 mb-2">
          Maximum Records per Data Type
        </label>
        <input
          v-model.number="recordLimit"
          type="number"
          min="1"
          max="10000"
          class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
          placeholder="e.g., 1000"
        />
        <p class="mt-1 text-xs text-gray-500">
          Useful for testing with smaller datasets before full import
        </p>
      </div>
    </div>

    <!-- Additional Options -->
    <div>
      <h3 class="text-lg font-medium text-gray-900 mb-4">Additional Options</h3>
      <div class="space-y-3">
        <label class="inline-flex items-center">
          <input
            v-model="activeUsersOnly"
            type="checkbox"
            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
          />
          <span class="ml-2 text-sm text-gray-700">Import active users only</span>
        </label>
      </div>
    </div>

    <!-- Quick Presets -->
    <div>
      <h3 class="text-lg font-medium text-gray-900 mb-4">Quick Presets</h3>
      <div class="flex space-x-3">
        <button
          @click="loadTestingPreset"
          class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
        >
          🧪 Testing Preset
        </button>
        <button
          @click="loadFullImportPreset"
          class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
        >
          📦 Full Import
        </button>
        <button
          @click="clearAllFilters"
          class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
        >
          🧹 Clear All
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'

// Props
const props = defineProps({
  profile: {
    type: Object,
    required: true
  },
  modelValue: {
    type: Object,
    default: () => ({})
  }
})

// Emits
const emit = defineEmits(['update:modelValue', 'filters-changed'])

// Reactive state
const selectedTables = ref(['users', 'customers', 'conversations', 'threads'])
const enableDateFilter = ref(false)
const dateFrom = ref('')
const dateTo = ref('')
const enableStatusFilter = ref(false)
const selectedStatuses = ref([])
const enableRecordLimit = ref(false)
const recordLimit = ref(1000)
const activeUsersOnly = ref(true)

// Available data types
const availableTables = ref([
  {
    key: 'users',
    title: 'Staff Users',
    description: 'FreeScout staff members → Service Vault users'
  },
  {
    key: 'customers',
    title: 'Customer Accounts',
    description: 'FreeScout customers → Service Vault accounts + users'
  },
  {
    key: 'conversations',
    title: 'Tickets',
    description: 'FreeScout conversations → Service Vault tickets'
  },
  {
    key: 'threads',
    title: 'Comments',
    description: 'FreeScout threads → Service Vault ticket comments'
  }
])

// Ticket statuses
const ticketStatuses = ref([
  { value: '1', label: 'Active (Open)' },
  { value: '2', label: 'Pending' },
  { value: '3', label: 'Closed' }
])

// Computed filter object
const currentFilters = computed(() => {
  const filters = {
    selected_tables: selectedTables.value,
    import_filters: {}
  }

  if (enableDateFilter.value) {
    if (dateFrom.value) filters.import_filters.date_from = dateFrom.value
    if (dateTo.value) filters.import_filters.date_to = dateTo.value
  }

  if (enableStatusFilter.value && selectedStatuses.value.length > 0) {
    filters.import_filters.ticket_status = selectedStatuses.value.join(',')
  }

  if (enableRecordLimit.value && recordLimit.value) {
    filters.import_filters.limit = recordLimit.value
  }

  if (activeUsersOnly.value) {
    filters.import_filters.active_users_only = true
  }

  return filters
})

// Watch for changes and emit
watch(currentFilters, (newFilters) => {
  emit('update:modelValue', newFilters)
  emit('filters-changed', newFilters)
}, { deep: true })

// Initialize from model value
watch(() => props.modelValue, (newValue) => {
  if (newValue) {
    selectedTables.value = newValue.selected_tables || []
    if (newValue.import_filters) {
      const filters = newValue.import_filters
      
      enableDateFilter.value = !!(filters.date_from || filters.date_to)
      dateFrom.value = filters.date_from || ''
      dateTo.value = filters.date_to || ''
      
      enableStatusFilter.value = !!filters.ticket_status
      selectedStatuses.value = filters.ticket_status ? filters.ticket_status.split(',') : []
      
      enableRecordLimit.value = !!filters.limit
      recordLimit.value = filters.limit || 1000
      
      activeUsersOnly.value = filters.active_users_only !== false
    }
  }
}, { immediate: true })

// Preset methods
const loadTestingPreset = () => {
  selectedTables.value = ['users', 'conversations']
  enableDateFilter.value = false
  enableStatusFilter.value = false
  enableRecordLimit.value = true
  recordLimit.value = 100
  activeUsersOnly.value = true
}

const loadFullImportPreset = () => {
  selectedTables.value = ['users', 'customers', 'conversations', 'threads']
  enableDateFilter.value = false
  enableStatusFilter.value = false
  enableRecordLimit.value = false
  activeUsersOnly.value = true
}

const clearAllFilters = () => {
  selectedTables.value = []
  enableDateFilter.value = false
  dateFrom.value = ''
  dateTo.value = ''
  enableStatusFilter.value = false
  selectedStatuses.value = []
  enableRecordLimit.value = false
  recordLimit.value = 1000
  activeUsersOnly.value = false
}
</script>