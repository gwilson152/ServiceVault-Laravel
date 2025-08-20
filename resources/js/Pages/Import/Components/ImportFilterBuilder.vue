<template>
  <StackedDialog 
    :show="show" 
    @close="$emit('close')"
    :title="`Configure Filters - ${profile?.name}`"
    max-width="4xl"
  >
    <div class="space-y-6">
      <!-- Filter Builder Header -->
      <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
        <div class="flex">
          <div class="flex-shrink-0">
            <InformationCircleIcon class="h-5 w-5 text-blue-400" />
          </div>
          <div class="ml-3">
            <h3 class="text-sm font-medium text-blue-800">Import Filter Configuration</h3>
            <div class="mt-2 text-sm text-blue-700">
              <p>Configure filters to control which data is imported. All filters are optional - leave blank to import all data.</p>
            </div>
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Data Type Selection -->
        <div class="space-y-4">
          <h3 class="text-lg font-medium text-gray-900">Data Types</h3>
          <p class="text-sm text-gray-600">Select which data types to include in the import.</p>
          
          <div class="space-y-3">
            <label 
              v-for="dataType in availableDataTypes" 
              :key="dataType.key"
              class="flex items-start p-3 border border-gray-200 rounded-lg hover:border-indigo-300 cursor-pointer"
              :class="selectedDataTypes.includes(dataType.key) ? 'border-indigo-500 bg-indigo-50' : ''"
            >
              <input
                v-model="selectedDataTypes"
                :value="dataType.key"
                type="checkbox"
                class="mt-1 h-4 w-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500"
              />
              <div class="ml-3">
                <div class="text-sm font-medium text-gray-900">{{ dataType.title }}</div>
                <div class="text-xs text-gray-500">{{ dataType.description }}</div>
              </div>
            </label>
          </div>
          
          <div class="flex items-center justify-between pt-2 border-t border-gray-200">
            <button 
              @click="selectAllDataTypes"
              class="text-xs text-indigo-600 hover:text-indigo-800 font-medium"
            >
              Select All
            </button>
            <button 
              @click="clearDataTypes"
              class="text-xs text-gray-600 hover:text-gray-800 font-medium"
            >
              Clear All
            </button>
          </div>
        </div>

        <!-- Filter Configuration -->
        <div class="space-y-4">
          <h3 class="text-lg font-medium text-gray-900">Import Filters</h3>
          <p class="text-sm text-gray-600">Apply filters to control which records are imported.</p>
          
          <div class="space-y-4">
            <!-- Date Range Filter -->
            <div class="border border-gray-200 rounded-lg p-4">
              <label class="flex items-center mb-3">
                <input
                  v-model="filtersEnabled.dateRange"
                  type="checkbox"
                  class="h-4 w-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500"
                />
                <span class="ml-2 text-sm font-medium text-gray-700">Date Range Filter</span>
              </label>
              
              <div v-if="filtersEnabled.dateRange" class="grid grid-cols-2 gap-3">
                <div>
                  <label class="block text-xs font-medium text-gray-700 mb-1">From Date</label>
                  <input
                    type="date"
                    v-model="filters.date_from"
                    class="block w-full text-sm border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
                  />
                </div>
                <div>
                  <label class="block text-xs font-medium text-gray-700 mb-1">To Date</label>
                  <input
                    type="date"
                    v-model="filters.date_to"
                    class="block w-full text-sm border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
                  />
                </div>
              </div>
              
              <p v-if="!filtersEnabled.dateRange" class="text-xs text-gray-500 mt-1">
                Import records from all dates
              </p>
            </div>

            <!-- Status Filter -->
            <div class="border border-gray-200 rounded-lg p-4">
              <label class="flex items-center mb-3">
                <input
                  v-model="filtersEnabled.ticketStatus"
                  type="checkbox"
                  class="h-4 w-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500"
                />
                <span class="ml-2 text-sm font-medium text-gray-700">Ticket Status Filter</span>
              </label>
              
              <div v-if="filtersEnabled.ticketStatus">
                <select
                  v-model="filters.ticket_status"
                  class="block w-full text-sm border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
                >
                  <option value="">All Statuses</option>
                  <option value="1">Active (Open)</option>
                  <option value="2">Pending</option>
                  <option value="3">Closed</option>
                </select>
              </div>
              
              <p v-if="!filtersEnabled.ticketStatus" class="text-xs text-gray-500 mt-1">
                Import tickets with all statuses
              </p>
            </div>

            <!-- Record Limit -->
            <div class="border border-gray-200 rounded-lg p-4">
              <label class="flex items-center mb-3">
                <input
                  v-model="filtersEnabled.recordLimit"
                  type="checkbox"
                  class="h-4 w-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500"
                />
                <span class="ml-2 text-sm font-medium text-gray-700">Record Limit</span>
              </label>
              
              <div v-if="filtersEnabled.recordLimit">
                <input
                  type="number"
                  v-model.number="filters.limit"
                  placeholder="Maximum records per type"
                  min="1"
                  class="block w-full text-sm border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
                />
                <p class="text-xs text-gray-500 mt-1">
                  Useful for testing with smaller datasets
                </p>
              </div>
              
              <p v-if="!filtersEnabled.recordLimit" class="text-xs text-gray-500 mt-1">
                Import all available records
              </p>
            </div>

            <!-- Active Users Filter -->
            <div class="border border-gray-200 rounded-lg p-4">
              <label class="flex items-center">
                <input
                  v-model="filters.active_users_only"
                  type="checkbox"
                  class="h-4 w-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500"
                />
                <span class="ml-2 text-sm font-medium text-gray-700">Active Users Only</span>
              </label>
              <p class="text-xs text-gray-500 mt-1">
                Skip disabled or inactive user accounts
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- Filter Summary -->
      <div v-if="hasActiveFilters" class="bg-gray-50 border border-gray-200 rounded-lg p-4">
        <h4 class="text-sm font-medium text-gray-900 mb-2">Active Filters Summary</h4>
        <div class="space-y-1 text-xs text-gray-600">
          <div v-if="selectedDataTypes.length > 0">
            <span class="font-medium">Data Types:</span> {{ selectedDataTypes.join(', ') }}
          </div>
          <div v-if="filters.date_from || filters.date_to">
            <span class="font-medium">Date Range:</span> 
            {{ filters.date_from || 'No start' }} to {{ filters.date_to || 'No end' }}
          </div>
          <div v-if="filters.ticket_status">
            <span class="font-medium">Ticket Status:</span> {{ getStatusLabel(filters.ticket_status) }}
          </div>
          <div v-if="filters.limit">
            <span class="font-medium">Record Limit:</span> {{ filters.limit }} per type
          </div>
          <div v-if="filters.active_users_only">
            <span class="font-medium">Users:</span> Active only
          </div>
        </div>
      </div>

      <!-- Actions -->
      <div class="flex items-center justify-between pt-4 border-t border-gray-200">
        <div class="flex items-center space-x-3">
          <button
            @click="clearAllFilters"
            class="text-sm text-gray-600 hover:text-gray-800"
          >
            Clear All Filters
          </button>
          <button
            @click="loadPresetFilters('testing')"
            class="text-sm text-indigo-600 hover:text-indigo-800"
          >
            Load Testing Preset
          </button>
        </div>
        
        <div class="flex items-center space-x-3">
          <button
            type="button"
            @click="$emit('close')"
            class="px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
          >
            Cancel
          </button>
          <button
            type="button"
            @click="applyFilters"
            class="px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
          >
            Apply Filters & Preview
          </button>
        </div>
      </div>
    </div>
  </StackedDialog>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import StackedDialog from '@/Components/StackedDialog.vue'
import { InformationCircleIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  show: Boolean,
  profile: {
    type: Object,
    default: null
  }
})

const emit = defineEmits(['close', 'apply-filters'])

// Available data types for FreeScout imports
const availableDataTypes = ref([
  {
    key: 'users',
    title: 'Staff Users',
    description: 'FreeScout staff → Service Vault users'
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
    description: 'FreeScout threads → Service Vault comments'
  }
])

// Filter state
const selectedDataTypes = ref(['users', 'customers', 'conversations', 'threads'])
const filtersEnabled = ref({
  dateRange: false,
  ticketStatus: false,
  recordLimit: false
})

const filters = ref({
  date_from: '',
  date_to: '',
  ticket_status: '',
  limit: null,
  active_users_only: false
})

// Computed
const hasActiveFilters = computed(() => {
  return selectedDataTypes.value.length < availableDataTypes.value.length ||
         filtersEnabled.value.dateRange ||
         filtersEnabled.value.ticketStatus || 
         filtersEnabled.value.recordLimit ||
         filters.value.active_users_only
})

// Watch for modal open/close to reset state
watch(() => props.show, (show) => {
  if (show) {
    resetFilters()
  }
})

// Methods
const selectAllDataTypes = () => {
  selectedDataTypes.value = availableDataTypes.value.map(dt => dt.key)
}

const clearDataTypes = () => {
  selectedDataTypes.value = []
}

const clearAllFilters = () => {
  resetFilters()
}

const resetFilters = () => {
  selectedDataTypes.value = ['users', 'customers', 'conversations', 'threads']
  filtersEnabled.value = {
    dateRange: false,
    ticketStatus: false,
    recordLimit: false
  }
  filters.value = {
    date_from: '',
    date_to: '',
    ticket_status: '',
    limit: null,
    active_users_only: false
  }
}

const loadPresetFilters = (preset) => {
  if (preset === 'testing') {
    // Load a testing-friendly preset
    selectedDataTypes.value = ['users', 'conversations']
    filtersEnabled.value.recordLimit = true
    filters.value.limit = 100
    filters.value.active_users_only = true
  }
}

const getStatusLabel = (status) => {
  const statusMap = {
    '1': 'Active (Open)',
    '2': 'Pending',
    '3': 'Closed'
  }
  return statusMap[status] || `Status ${status}`
}

const applyFilters = () => {
  // Build the final filter object
  const finalFilters = {
    selected_tables: selectedDataTypes.value,
    import_filters: {}
  }

  // Only include enabled filters with values
  if (filtersEnabled.value.dateRange && filters.value.date_from) {
    finalFilters.import_filters.date_from = filters.value.date_from
  }
  
  if (filtersEnabled.value.dateRange && filters.value.date_to) {
    finalFilters.import_filters.date_to = filters.value.date_to
  }
  
  if (filtersEnabled.value.ticketStatus && filters.value.ticket_status) {
    finalFilters.import_filters.ticket_status = filters.value.ticket_status
  }
  
  if (filtersEnabled.value.recordLimit && filters.value.limit) {
    finalFilters.import_filters.limit = filters.value.limit
  }
  
  if (filters.value.active_users_only) {
    finalFilters.import_filters.active_users_only = true
  }

  // Emit the filters to parent component
  emit('apply-filters', {
    profile: props.profile,
    filters: finalFilters
  })
}
</script>