<template>
  <div class="space-y-6">
    <!-- Loading State -->
    <div v-if="previewLoading" class="text-center py-12">
      <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
      <p class="mt-2 text-sm text-gray-600">Loading preview data...</p>
    </div>

    <!-- Error State -->
    <div v-else-if="previewError" class="bg-red-50 border border-red-200 rounded-lg p-4">
      <div class="flex">
        <div class="flex-shrink-0">
          <ExclamationTriangleIcon class="h-5 w-5 text-red-400" />
        </div>
        <div class="ml-3">
          <h3 class="text-sm font-medium text-red-800">Preview Failed</h3>
          <div class="mt-2 text-sm text-red-700">
            <p>{{ previewError }}</p>
          </div>
          <div class="mt-4">
            <button
              @click="loadPreview"
              class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-red-700 bg-red-100 hover:bg-red-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
            >
              <ArrowPathIcon class="-ml-1 mr-2 h-4 w-4" />
              Retry Preview
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Success State -->
    <div v-else-if="previewData">
      <!-- Summary Statistics -->
      <div class="bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 rounded-lg p-4">
        <h3 class="text-sm font-medium text-blue-900 mb-3">Import Summary</h3>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
          <div v-for="(data, key) in previewData" :key="key" class="text-center">
            <div class="text-2xl font-bold text-blue-600">
              {{ data.total_count || 0 }}
            </div>
            <div class="text-xs text-blue-700">
              {{ formatDataTypeName(key) }}
            </div>
          </div>
        </div>
      </div>

      <!-- Applied Filters Summary -->
      <div v-if="hasActiveFilters || hasActiveMappings" class="bg-amber-50 border border-amber-200 rounded-lg p-4">
        <h4 class="text-sm font-medium text-amber-800 mb-2">Applied Configuration</h4>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
          <div>
            <span class="font-medium text-amber-700">Filters:</span>
            <div v-if="hasActiveFilters" class="mt-1 space-y-1">
              <div v-if="filters?.selected_tables?.length" class="text-amber-700">
                📊 Tables: {{ filters.selected_tables.join(', ') }}
              </div>
              <div v-if="filters?.import_filters?.date_from || filters?.import_filters?.date_to" class="text-amber-700">
                📅 Date: {{ formatDateRange() }}
              </div>
              <div v-if="filters?.import_filters?.limit" class="text-amber-700">
                🔢 Limit: {{ filters.import_filters.limit }} per type
              </div>
              <div v-if="filters?.import_filters?.ticket_status" class="text-amber-700">
                🎫 Status: {{ formatTicketStatuses() }}
              </div>
            </div>
            <div v-else class="text-amber-600 mt-1">No filters applied</div>
          </div>
          
          <div>
            <span class="font-medium text-amber-700">Mappings:</span>
            <div v-if="hasActiveMappings" class="mt-1 space-y-1">
              <div v-for="(tableMappings, table) in mappings" :key="table" class="text-amber-700">
                🔄 {{ table }}: {{ Object.keys(tableMappings || {}).length }} mappings
              </div>
            </div>
            <div v-else class="text-amber-600 mt-1">Using default mappings</div>
          </div>
        </div>
      </div>

      <!-- Tabbed Data Preview -->
      <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
        <div class="border-b border-gray-200">
          <nav class="-mb-px flex space-x-8 px-6" aria-label="Tabs">
            <button
              v-for="(data, key) in previewData"
              :key="key"
              @click="activeTab = key"
              class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors"
              :class="activeTab === key
                ? 'border-indigo-500 text-indigo-600'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
            >
              {{ formatDataTypeName(key) }}
              <span class="ml-2 py-0.5 px-2 rounded-full text-xs font-medium"
                    :class="activeTab === key 
                      ? 'bg-indigo-100 text-indigo-600' 
                      : 'bg-gray-100 text-gray-900'">
                {{ data.total_count || 0 }}
              </span>
            </button>
          </nav>
        </div>

        <div class="p-6">
          <div v-if="activeTabData" class="space-y-4">
            <!-- Tab Description -->
            <div class="flex items-center justify-between">
              <p class="text-sm text-gray-600">{{ activeTabData.description }}</p>
              <div class="text-xs text-gray-500">
                Showing first {{ Math.min(5, activeTabData.sample_data?.length || 0) }} records
              </div>
            </div>

            <!-- Sample Data Table -->
            <div v-if="activeTabData.sample_data?.length > 0" class="overflow-x-auto">
              <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                  <tr>
                    <th
                      v-for="(value, key) in activeTabData.sample_data[0]"
                      :key="key"
                      class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                    >
                      {{ key }}
                    </th>
                  </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                  <tr v-for="(record, index) in activeTabData.sample_data.slice(0, 5)" :key="index">
                    <td
                      v-for="(value, key) in record"
                      :key="key"
                      class="px-4 py-2 text-sm text-gray-900 max-w-xs truncate"
                      :title="String(value)"
                    >
                      {{ formatCellValue(value) }}
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- No Data State -->
            <div v-else class="text-center py-8">
              <DocumentIcon class="mx-auto h-12 w-12 text-gray-400" />
              <h3 class="mt-2 text-sm font-medium text-gray-900">No sample data available</h3>
              <p class="mt-1 text-sm text-gray-500">
                This data type has no records matching your current filters.
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- Warning Messages -->
      <div v-if="previewWarnings.length > 0" class="space-y-3">
        <div v-for="warning in previewWarnings" :key="warning" class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
          <div class="flex">
            <div class="flex-shrink-0">
              <ExclamationTriangleIcon class="h-5 w-5 text-yellow-400" />
            </div>
            <div class="ml-3">
              <p class="text-sm text-yellow-700">{{ warning }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else class="text-center py-12">
      <EyeIcon class="mx-auto h-12 w-12 text-gray-400" />
      <h3 class="mt-2 text-sm font-medium text-gray-900">Ready to Preview</h3>
      <p class="mt-1 text-sm text-gray-500">
        Click "Load Preview" to see what data will be imported.
      </p>
      <div class="mt-6">
        <button
          @click="loadPreview"
          class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-indigo-700 bg-indigo-100 hover:bg-indigo-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
        >
          <EyeIcon class="-ml-1 mr-2 h-4 w-4" />
          Load Preview
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import {
  EyeIcon,
  ExclamationTriangleIcon,
  ArrowPathIcon,
  DocumentIcon,
} from '@heroicons/vue/24/outline'
import { useImportQueries } from '@/Composables/queries/useImportQueries.js'

// Props
const props = defineProps({
  profile: {
    type: Object,
    required: true
  },
  filters: {
    type: Object,
    default: () => ({})
  },
  mappings: {
    type: Object,
    default: () => ({})
  }
})

// Emits
const emit = defineEmits(['preview-loaded'])

// Composables
const { previewImport } = useImportQueries()

// Reactive state
const previewData = ref(null)
const previewLoading = ref(false)
const previewError = ref(null)
const previewWarnings = ref([])
const activeTab = ref('customer_users')

// Computed
const activeTabData = computed(() => {
  return previewData.value?.[activeTab.value] || null
})

const hasActiveFilters = computed(() => {
  return props.filters?.selected_tables?.length > 0 ||
         props.filters?.import_filters?.date_from ||
         props.filters?.import_filters?.date_to ||
         props.filters?.import_filters?.limit ||
         props.filters?.import_filters?.ticket_status
})

const hasActiveMappings = computed(() => {
  return Object.keys(props.mappings || {}).some(table => 
    Object.keys(props.mappings[table] || {}).length > 0
  )
})

// Watch for filter/mapping changes and reload preview
watch([() => props.filters, () => props.mappings], () => {
  if (previewData.value) {
    loadPreview()
  }
}, { deep: true })

// Auto-load preview on mount
onMounted(() => {
  loadPreview()
})

// Methods
const loadPreview = async () => {
  if (!props.profile) return
  
  try {
    previewLoading.value = true
    previewError.value = null
    previewWarnings.value = []
    
    const data = await previewImport(props.profile.id, props.filters)
    previewData.value = data
    
    // Set active tab to first available data type, preferring customer_users
    const availableTabs = Object.keys(data)
    if (availableTabs.length > 0) {
      activeTab.value = availableTabs.includes('customer_users') ? 'customer_users' : availableTabs[0]
    }
    
    // Generate warnings based on data
    generateWarnings(data)
    
    emit('preview-loaded', true)
  } catch (error) {
    console.error('Preview failed:', error)
    previewError.value = error.response?.data?.message || error.message || 'Failed to load preview'
    previewData.value = null
    emit('preview-loaded', false)
  } finally {
    previewLoading.value = false
  }
}

const generateWarnings = (data) => {
  const warnings = []
  
  // Check for empty datasets
  Object.entries(data).forEach(([key, value]) => {
    if (value.total_count === 0) {
      warnings.push(`No ${formatDataTypeName(key)} found matching your filters`)
    }
  })
  
  // Check for very large datasets
  Object.entries(data).forEach(([key, value]) => {
    if (value.total_count > 10000) {
      warnings.push(`Large dataset: ${formatDataTypeName(key)} has ${value.total_count} records. Consider adding filters to reduce import time.`)
    }
  })
  
  // Check if no field mappings are configured
  if (!hasActiveMappings.value) {
    warnings.push('No custom field mappings configured. Default mappings will be used.')
  }
  
  previewWarnings.value = warnings
}

const formatDataTypeName = (key) => {
  const names = {
    customer_users: 'Customer Users',
    conversations: 'Tickets',
    customer_accounts: 'Customer Accounts',
    staff_users: 'Staff Users',
    threads: 'Comments'
  }
  return names[key] || key
}

const formatCellValue = (value) => {
  if (value === null || value === undefined) {
    return '—'
  }
  
  if (typeof value === 'string' && value.length > 50) {
    return value.substring(0, 50) + '...'
  }
  
  if (typeof value === 'object') {
    return JSON.stringify(value).substring(0, 50) + '...'
  }
  
  return String(value)
}

const formatDateRange = () => {
  const from = props.filters?.import_filters?.date_from
  const to = props.filters?.import_filters?.date_to
  
  if (from && to) {
    return `${from} to ${to}`
  } else if (from) {
    return `from ${from}`
  } else if (to) {
    return `until ${to}`
  }
  return ''
}

const formatTicketStatuses = () => {
  const statuses = props.filters?.import_filters?.ticket_status?.split(',') || []
  const statusNames = {
    '1': 'Active',
    '2': 'Pending', 
    '3': 'Closed'
  }
  
  return statuses.map(s => statusNames[s] || s).join(', ')
}
</script>