<template>
  <StackedDialog 
    :show="show" 
    @close="$emit('close')"
    title="Import Preview"
    max-width="6xl"
  >
    <div v-if="isLoading" class="text-center py-12">
      <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
      <p class="mt-4 text-sm text-gray-600">Loading import preview...</p>
    </div>

    <div v-else-if="error" class="bg-red-50 border border-red-200 rounded-md p-4">
      <div class="flex">
        <div class="flex-shrink-0">
          <XCircleIcon class="h-5 w-5 text-red-400" />
        </div>
        <div class="ml-3">
          <h3 class="text-sm font-medium text-red-800">Preview Failed</h3>
          <div class="mt-2 text-sm text-red-700">
            <p>{{ error.message || 'Failed to load import preview' }}</p>
          </div>
        </div>
      </div>
    </div>

    <div v-else-if="previewData" class="space-y-6">
      <!-- Summary -->
      <div class="bg-indigo-50 border border-indigo-200 rounded-lg p-4">
        <div class="flex">
          <div class="flex-shrink-0">
            <InformationCircleIcon class="h-5 w-5 text-indigo-400" />
          </div>
          <div class="ml-3">
            <h3 class="text-sm font-medium text-indigo-800">Import Summary</h3>
            <div class="mt-2 text-sm text-indigo-700">
              <p>This preview shows a sample of data that will be imported from <strong>{{ profile?.name }}</strong>.</p>
              <div v-if="totalRecords" class="mt-2 grid grid-cols-4 gap-4 text-xs">
                <div class="bg-white bg-opacity-50 rounded p-2">
                  <div class="font-medium">Users</div>
                  <div class="text-indigo-600">{{ totalRecords.users || 0 }} records</div>
                </div>
                <div class="bg-white bg-opacity-50 rounded p-2">
                  <div class="font-medium">Customers</div>
                  <div class="text-indigo-600">{{ totalRecords.customers || 0 }} records</div>
                </div>
                <div class="bg-white bg-opacity-50 rounded p-2">
                  <div class="font-medium">Conversations</div>
                  <div class="text-indigo-600">{{ totalRecords.conversations || 0 }} records</div>
                </div>
                <div class="bg-white bg-opacity-50 rounded p-2">
                  <div class="font-medium">Threads</div>
                  <div class="text-indigo-600">{{ totalRecords.threads || 0 }} records</div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Active Filters Indicator -->
      <div v-if="profile?.tempFilters" class="bg-amber-50 border border-amber-200 rounded-lg p-4">
        <div class="flex">
          <div class="flex-shrink-0">
            <CheckCircleIcon class="h-5 w-5 text-amber-400" />
          </div>
          <div class="ml-3">
            <h3 class="text-sm font-medium text-amber-800">Active Import Filters</h3>
            <div class="mt-2 text-sm text-amber-700">
              <div class="space-y-1">
                <div v-if="profile.tempFilters.selected_tables?.length > 0">
                  <span class="font-medium">Data Types:</span> {{ profile.tempFilters.selected_tables.join(', ') }}
                </div>
                <div v-if="profile.tempFilters.import_filters?.date_from || profile.tempFilters.import_filters?.date_to">
                  <span class="font-medium">Date Range:</span> 
                  {{ profile.tempFilters.import_filters.date_from || 'No start' }} to {{ profile.tempFilters.import_filters.date_to || 'No end' }}
                </div>
                <div v-if="profile.tempFilters.import_filters?.ticket_status">
                  <span class="font-medium">Ticket Status:</span> {{ getStatusLabel(profile.tempFilters.import_filters.ticket_status) }}
                </div>
                <div v-if="profile.tempFilters.import_filters?.limit">
                  <span class="font-medium">Record Limit:</span> {{ profile.tempFilters.import_filters.limit }} per type
                </div>
                <div v-if="profile.tempFilters.import_filters?.active_users_only">
                  <span class="font-medium">Users:</span> Active only
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Import Selection -->
      <div class="bg-gray-50 rounded-lg p-4 space-y-3">
        <h3 class="text-sm font-medium text-gray-900">Select Data to Import</h3>
        <p class="text-xs text-gray-600">Choose which data types should be included in the import.</p>
        
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
          <label 
            v-for="(section, key) in previewData" 
            :key="key"
            class="relative flex items-start p-3 bg-white rounded-md border cursor-pointer hover:border-indigo-300 transition-colors"
            :class="selectedTables.includes(key) ? 'border-indigo-500 bg-indigo-50' : 'border-gray-200'"
          >
            <div class="flex items-center h-5">
              <input
                :id="`table-${key}`"
                v-model="selectedTables"
                :value="key"
                type="checkbox"
                class="h-4 w-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500"
              />
            </div>
            <div class="ml-3 flex-1">
              <div class="text-sm font-medium text-gray-900">
                {{ getShortTitle(section.title) }}
              </div>
              <div class="text-xs text-gray-500">
                {{ section.total_count }} records
              </div>
            </div>
          </label>
        </div>
        
        <div class="flex items-center justify-between pt-2 border-t border-gray-200">
          <div class="flex items-center space-x-3">
            <button 
              @click="selectAll"
              class="text-xs text-indigo-600 hover:text-indigo-800 font-medium"
            >
              Select All
            </button>
            <button 
              @click="selectNone"
              class="text-xs text-gray-600 hover:text-gray-800 font-medium"
            >
              Select None
            </button>
          </div>
          <div class="text-xs text-gray-500">
            {{ selectedTables.length }} of {{ Object.keys(previewData).length }} data types selected
          </div>
        </div>
      </div>

      <!-- Preview Tabs -->
      <div class="border-b border-gray-200">
        <nav class="-mb-px flex flex-wrap gap-x-4 gap-y-2">
          <button
            v-for="(section, key) in previewData"
            :key="key"
            @click="activeTab = key"
            :class="[
              activeTab === key
                ? 'border-indigo-500 text-indigo-600'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
              'py-2 px-3 border-b-2 font-medium text-sm rounded-t-md transition-colors duration-150',
              !selectedTables.includes(key) ? 'opacity-50' : ''
            ]"
          >
            <span class="flex items-center">
              <CheckCircleIcon 
                v-if="selectedTables.includes(key)" 
                class="w-4 h-4 mr-1 text-green-500" 
              />
              <XCircleIcon 
                v-else 
                class="w-4 h-4 mr-1 text-gray-400" 
              />
              <span class="truncate max-w-xs">{{ getShortTitle(section.title) }}</span>
            </span>
            <span :class="activeTab === key ? 'text-indigo-500' : 'text-gray-400'" class="ml-1 text-xs font-normal">
              ({{ section.total_count }})
            </span>
          </button>
        </nav>
      </div>

      <!-- Preview Content -->
      <div v-if="currentSection" class="space-y-4">
        <div class="bg-gray-50 rounded-lg p-4">
          <h4 class="font-medium text-gray-900">{{ currentSection.title }}</h4>
          <p class="text-sm text-gray-600 mt-1">{{ currentSection.description }}</p>
          <div class="text-xs text-gray-500 mt-2">
            Showing {{ currentSection.sample_data?.length || 0 }} of {{ currentSection.total_count }} total records
          </div>
        </div>

        <!-- Sample Data Table with Individual Record Selection -->
        <div v-if="currentSection.sample_data?.length > 0" class="space-y-4">
          <!-- Bulk Selection Controls -->
          <div class="flex items-center justify-between bg-gray-50 rounded-lg p-3">
            <div class="flex items-center space-x-4">
              <label class="flex items-center">
                <input
                  type="checkbox"
                  :checked="isAllRecordsSelected(activeTab)"
                  :indeterminate="isSomeRecordsSelected(activeTab)"
                  @change="toggleAllRecords(activeTab, $event.target.checked)"
                  class="h-4 w-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500"
                />
                <span class="ml-2 text-sm font-medium text-gray-700">
                  Select All Records
                </span>
              </label>
              <div class="text-xs text-gray-500">
                {{ getSelectedRecordCount(activeTab) }} of {{ currentSection.sample_data.length }} sample records selected
              </div>
            </div>
            
            <div class="flex items-center space-x-2 text-xs">
              <button 
                @click="selectAllRecords(activeTab)"
                class="text-indigo-600 hover:text-indigo-800 font-medium"
              >
                Select All
              </button>
              <span class="text-gray-300">|</span>
              <button 
                @click="deselectAllRecords(activeTab)"
                class="text-gray-600 hover:text-gray-800 font-medium"
              >
                Deselect All
              </button>
            </div>
          </div>
          
          <!-- Records Table -->
          <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
            <table class="min-w-full divide-y divide-gray-300">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-4 py-3 text-left">
                    <span class="sr-only">Select</span>
                  </th>
                  <th
                    v-for="column in getTableColumns(currentSection.sample_data[0])"
                    :key="column"
                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                  >
                    {{ formatColumnName(column) }}
                  </th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr 
                  v-for="(row, index) in currentSection.sample_data" 
                  :key="getRecordKey(row)"
                  :class="isRecordSelected(activeTab, row) ? 'bg-indigo-50' : 'bg-white hover:bg-gray-50'"
                >
                  <td class="px-4 py-4 whitespace-nowrap">
                    <input
                      type="checkbox"
                      :checked="isRecordSelected(activeTab, row)"
                      @change="toggleRecord(activeTab, row, $event.target.checked)"
                      class="h-4 w-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500"
                    />
                  </td>
                  <td
                    v-for="column in getTableColumns(row)"
                    :key="column"
                    class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"
                  >
                    <div class="max-w-xs truncate" :title="formatCellValue(row[column])">
                      {{ formatCellValue(row[column]) }}
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          
          <!-- Selection Summary -->
          <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
            <div class="flex items-center">
              <InformationCircleIcon class="h-5 w-5 text-blue-400 mr-2" />
              <div class="text-sm text-blue-800">
                <strong>{{ getSelectedRecordCount(activeTab) }}</strong> sample records selected. 
                This represents the selection pattern for all <strong>{{ currentSection.total_count }}</strong> records in this data type.
              </div>
            </div>
          </div>
        </div>

        <div v-else class="text-center py-8 text-gray-500">
          <CircleStackIcon class="mx-auto h-12 w-12 text-gray-300" />
          <p class="mt-2">No sample data available for this section</p>
        </div>
      </div>

      <!-- Actions -->
      <div class="border-t border-gray-200 pt-6">
        <div class="flex items-center justify-between">
          <div class="text-sm text-gray-500">
            <p>Review the sample data above before proceeding with the import.</p>
            <p class="mt-1">This import will create Service Vault records from the source database.</p>
          </div>
          
          <div class="flex items-center space-x-3">
            <button
              type="button"
              @click="$emit('close')"
              class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
            >
              Cancel
            </button>
            <button
              type="button"
              @click="executeImport"
              :disabled="selectedTables.length === 0"
              :class="[
                'inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500',
                selectedTables.length > 0
                  ? 'text-white bg-indigo-600 hover:bg-indigo-700'
                  : 'text-gray-400 bg-gray-300 cursor-not-allowed'
              ]"
            >
              <PlayIcon class="w-4 h-4 mr-2" />
              Execute Import
              <span v-if="selectedTables.length > 0" class="ml-1">
                ({{ selectedTables.length }} {{ selectedTables.length === 1 ? 'type' : 'types' }}{{ hasActiveFilters ? ' with filters' : '' }})
              </span>
            </button>
          </div>
        </div>
      </div>
    </div>
  </StackedDialog>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import StackedDialog from '@/Components/StackedDialog.vue'
import {
  XCircleIcon,
  InformationCircleIcon,
  CircleStackIcon,
  PlayIcon,
  CheckCircleIcon,
} from '@heroicons/vue/24/outline'
import { useImportQueries } from '@/Composables/queries/useImportQueries.js'

const props = defineProps({
  show: Boolean,
  profile: {
    type: Object,
    default: null
  }
})

const emit = defineEmits(['close', 'execute', 'executed'])

// Composables
const { previewImport, isPreviewingImport, executeImport: executeImportMutation } = useImportQueries()

// State
const previewData = ref(null)
const error = ref(null)
const activeTab = ref('users')
const selectedTables = ref([])
const selectedRecords = ref({}) // { tableName: Set<recordId> }
const isLoading = computed(() => isPreviewingImport.value)

// Computed
const currentSection = computed(() => {
  return previewData.value && activeTab.value ? previewData.value[activeTab.value] : null
})

const totalRecords = computed(() => {
  if (!previewData.value) return null
  
  const totals = {}
  Object.keys(previewData.value).forEach(key => {
    totals[key] = previewData.value[key].total_count
  })
  return totals
})

const hasActiveFilters = computed(() => {
  return Boolean(props.profile?.tempFilters)
})

// Watch for profile changes
watch(() => [props.show, props.profile], ([show, profile]) => {
  if (show && profile) {
    loadPreview()
  } else {
    previewData.value = null
    error.value = null
    activeTab.value = 'users'
    selectedTables.value = []
    selectedRecords.value = {}
  }
}, { immediate: true })

// Methods
const loadPreview = async () => {
  if (!props.profile) return
  
  try {
    error.value = null
    // Use tempFilters if available from filter builder, otherwise use default preview
    const filters = props.profile.tempFilters || null
    const result = await previewImport(props.profile.id, filters)
    previewData.value = result
    
    // Set active tab to first available section and select all tables by default
    const sections = Object.keys(result)
    if (sections.length > 0) {
      activeTab.value = sections[0]
      selectedTables.value = [...sections] // Select all tables by default
      
      // Initialize record selections - select all records by default
      const newSelectedRecords = {}
      sections.forEach(sectionKey => {
        const section = result[sectionKey]
        if (section.sample_data && section.sample_data.length > 0) {
          newSelectedRecords[sectionKey] = new Set(
            section.sample_data.map(record => getRecordId(record))
          )
        } else {
          newSelectedRecords[sectionKey] = new Set()
        }
      })
      selectedRecords.value = newSelectedRecords
    }
  } catch (err) {
    error.value = err
    previewData.value = null
  }
}

const getTableColumns = (row) => {
  return Object.keys(row)
}

const formatColumnName = (column) => {
  return column.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())
}

const formatCellValue = (value) => {
  if (value === null || value === undefined) {
    return '-'
  }
  if (typeof value === 'object') {
    return JSON.stringify(value)
  }
  return String(value)
}

const getShortTitle = (title) => {
  // Simplify tab titles for better space usage
  const titleMap = {
    'FreeScout Staff → Service Vault Users': 'Staff',
    'FreeScout Customers → Service Vault Accounts + Users': 'Customers',
    'FreeScout Conversations → Service Vault Tickets': 'Tickets',
    'FreeScout Threads → Service Vault Comments': 'Comments'
  }
  
  return titleMap[title] || title.split(' ')[0] || title
}

const getStatusLabel = (status) => {
  const statusMap = {
    '1': 'Active (Open)',
    '2': 'Pending',
    '3': 'Closed'
  }
  return statusMap[status] || `Status ${status}`
}

const selectAll = () => {
  if (previewData.value) {
    selectedTables.value = [...Object.keys(previewData.value)]
  }
}

const selectNone = () => {
  selectedTables.value = []
}

// Record selection methods
const getRecordId = (record) => {
  return record.id || record.uuid || JSON.stringify(record)
}

const getRecordKey = (record) => {
  return getRecordId(record)
}

const isRecordSelected = (tableKey, record) => {
  const recordId = getRecordId(record)
  return selectedRecords.value[tableKey]?.has(recordId) || false
}

const toggleRecord = (tableKey, record, selected) => {
  const recordId = getRecordId(record)
  if (!selectedRecords.value[tableKey]) {
    selectedRecords.value[tableKey] = new Set()
  }
  
  if (selected) {
    selectedRecords.value[tableKey].add(recordId)
  } else {
    selectedRecords.value[tableKey].delete(recordId)
  }
}

const getSelectedRecordCount = (tableKey) => {
  return selectedRecords.value[tableKey]?.size || 0
}

const isAllRecordsSelected = (tableKey) => {
  const section = previewData.value?.[tableKey]
  if (!section?.sample_data?.length) return false
  
  const totalRecords = section.sample_data.length
  const selectedCount = getSelectedRecordCount(tableKey)
  return selectedCount === totalRecords
}

const isSomeRecordsSelected = (tableKey) => {
  const section = previewData.value?.[tableKey]
  if (!section?.sample_data?.length) return false
  
  const totalRecords = section.sample_data.length
  const selectedCount = getSelectedRecordCount(tableKey)
  return selectedCount > 0 && selectedCount < totalRecords
}

const toggleAllRecords = (tableKey, selected) => {
  if (selected) {
    selectAllRecords(tableKey)
  } else {
    deselectAllRecords(tableKey)
  }
}

const selectAllRecords = (tableKey) => {
  const section = previewData.value?.[tableKey]
  if (section?.sample_data?.length) {
    selectedRecords.value[tableKey] = new Set(
      section.sample_data.map(record => getRecordId(record))
    )
  }
}

const deselectAllRecords = (tableKey) => {
  selectedRecords.value[tableKey] = new Set()
}

const executeImport = async () => {
  if (selectedTables.value.length === 0) {
    alert('Please select at least one data type to import.')
    return
  }
  
  try {
    // Use tempFilters from filter builder if available, otherwise use current filters
    const filters = props.profile?.tempFilters || {
      selected_tables: selectedTables.value,
      import_filters: importFilters.value
    }
    
    const options = {
      selected_tables: filters.selected_tables || selectedTables.value,
      import_filters: filters.import_filters || importFilters.value,
      overwrite_existing: false,
      batch_size: 100
    }
    
    await executeImportMutation(props.profile.id, options)
    
    // Emit success event for parent component to handle
    emit('executed', {
      message: 'Import job started successfully',
      selectedTables: filters.selected_tables || selectedTables.value,
      filters: filters.import_filters || importFilters.value
    })
    
    // Close the modal
    emit('close')
  } catch (error) {
    console.error('Import execution failed:', error)
    alert('Failed to start import job. Please check the console for details.')
  }
}
</script>