<template>
  <StackedDialog 
    :show="show" 
    @close="$emit('close')"
    title="Import Preview"
    :fullscreen="true"
    :fullscreen-padding="'2rem'"
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

    <div v-else-if="previewData" class="h-full flex flex-col space-y-6">
      <!-- Query Summary -->
      <div class="bg-indigo-50 border border-indigo-200 rounded-lg p-4">
        <div class="flex">
          <div class="flex-shrink-0">
            <InformationCircleIcon class="h-5 w-5 text-indigo-400" />
          </div>
          <div class="ml-3 flex-1">
            <h3 class="text-sm font-medium text-indigo-800">
              {{ isQueryBasedImport ? 'Import Query Summary' : 'Import Preview Summary' }}
            </h3>
            <div class="mt-2 text-sm text-indigo-700">
              <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                  <div class="font-medium">Source Database</div>
                  <div class="text-indigo-600">{{ profile?.name }}</div>
                </div>
                <div>
                  <div class="font-medium">Import Type</div>
                  <div class="text-indigo-600">
                    {{ isQueryBasedImport ? 'Custom Query' : 'Template-Based' }}
                  </div>
                </div>
                <div>
                  <div class="font-medium">Estimated Records</div>
                  <div class="text-indigo-600">{{ formatNumber(previewData.estimated_records || 0) }}</div>
                </div>
              </div>
              
              <!-- Query Configuration Details (Query-based only) -->
              <div v-if="isQueryBasedImport" class="mt-3 pt-3 border-t border-indigo-200">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                  <div>
                    <div class="font-medium">Base Table:</div>
                    <div>{{ queryConfig?.base_table }}</div>
                  </div>
                  <div>
                    <div class="font-medium">Target Type:</div>
                    <div>{{ getTargetTypeLabel(queryConfig?.target_type) }}</div>
                  </div>
                  <div>
                    <div class="font-medium">Fields Mapped:</div>
                    <div>{{ queryConfig?.fields?.length || 0 }} fields</div>
                  </div>
                  <div v-if="queryConfig?.joins?.length > 0">
                    <div class="font-medium">Table Joins:</div>
                    <div>{{ queryConfig.joins.length }} join{{ queryConfig.joins.length === 1 ? '' : 's' }}</div>
                  </div>
                  <div v-if="queryConfig?.filters?.length > 0">
                    <div class="font-medium">Data Filters:</div>
                    <div>{{ queryConfig.filters.length }} filter{{ queryConfig.filters.length === 1 ? '' : 's' }}</div>
                  </div>
                </div>
              </div>
              
              <!-- Template Configuration Details (Template-based only) -->
              <div v-if="isTemplateBasedImport" class="mt-3 pt-3 border-t border-indigo-200">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                  <div>
                    <div class="font-medium">Configuration:</div>
                    <div>{{ profile?.template_id ? 'Template-based' : 'Custom configuration' }}</div>
                  </div>
                  <div v-if="profile?.template_id">
                    <div class="font-medium">Template ID:</div>
                    <div>{{ profile.template_id }}</div>
                  </div>
                  <div>
                    <div class="font-medium">Has Custom Queries:</div>
                    <div>{{ profile?.has_custom_queries ? 'Yes' : 'No' }}</div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Import Mode Configuration -->
      <div class="bg-gray-50 rounded-lg p-4">
        <h3 class="text-sm font-medium text-gray-900 mb-3">Import Configuration</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div>
            <label class="block text-xs font-medium text-gray-700 mb-2">Import Mode</label>
            <select 
              v-model="importMode"
              class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-indigo-500 focus:border-indigo-500"
            >
              <option value="create_only">Create Only (Skip existing)</option>
              <option value="update_only">Update Only (Skip new)</option>
              <option value="upsert">Upsert (Create or Update)</option>
            </select>
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-700 mb-2">Batch Size</label>
            <select 
              v-model="batchSize"
              class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-indigo-500 focus:border-indigo-500"
            >
              <option value="25">25 records</option>
              <option value="50">50 records</option>
              <option value="100">100 records</option>
              <option value="250">250 records</option>
            </select>
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-700 mb-2">Duplicate Detection</label>
            <select 
              v-model="duplicateStrategy"
              class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-indigo-500 focus:border-indigo-500"
            >
              <option value="exact">Exact Match</option>
              <option value="fuzzy">Fuzzy Match</option>
              <option value="case_insensitive">Case Insensitive</option>
            </select>
          </div>
        </div>
      </div>

      <!-- Sample Data Preview -->
      <div class="flex-1 flex flex-col min-h-0">
        <div class="flex items-center justify-between mb-4">
          <h4 class="text-sm font-medium text-gray-900">Sample Data Preview</h4>
          <div class="flex items-center space-x-3">
            <span class="text-xs text-gray-500">
              Showing {{ previewData.sample_data?.length || 0 }} of {{ previewData.estimated_records || 0 }} records
            </span>
            <button
              @click="refreshPreview"
              :disabled="isRefreshing"
              class="inline-flex items-center px-2 py-1 text-xs font-medium text-gray-600 bg-gray-100 rounded hover:bg-gray-200 disabled:opacity-50"
            >
              <ArrowPathIcon class="w-3 h-3 mr-1" :class="{ 'animate-spin': isRefreshing }" />
              Refresh
            </button>
          </div>
        </div>

        <!-- Data Table -->
        <div v-if="previewData.sample_data?.length > 0" class="flex-1 overflow-auto border border-gray-200 rounded-lg">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50 sticky top-0">
              <tr>
                <!-- Query-based: Show field mappings -->
                <th
                  v-if="isQueryBasedImport"
                  v-for="field in mappedFields"
                  :key="field.target"
                  class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                >
                  <div class="flex flex-col">
                    <span>{{ field.target_label }}</span>
                    <span class="text-gray-400 font-normal normal-case">← {{ field.source }}</span>
                  </div>
                </th>
                
                <!-- Template-based: Show raw data columns -->
                <th
                  v-if="isTemplateBasedImport"
                  v-for="column in getTableColumns(previewData.sample_data[0])"
                  :key="column"
                  class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                >
                  {{ formatColumnName(column) }}
                </th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr 
                v-for="(row, index) in previewData.sample_data" 
                :key="index"
                class="hover:bg-gray-50"
              >
                <!-- Query-based: Show mapped field values -->
                <td
                  v-if="isQueryBasedImport"
                  v-for="field in mappedFields"
                  :key="field.target"
                  class="px-3 py-2 whitespace-nowrap text-sm text-gray-900 max-w-xs"
                >
                  <div class="truncate" :title="getFieldValue(row, field)">
                    {{ getFieldValue(row, field) }}
                  </div>
                </td>
                
                <!-- Template-based: Show raw column values -->
                <td
                  v-if="isTemplateBasedImport"
                  v-for="column in getTableColumns(row)"
                  :key="column"
                  class="px-3 py-2 whitespace-nowrap text-sm text-gray-900 max-w-xs"
                >
                  <div class="truncate" :title="formatCellValue(row[column])">
                    {{ formatCellValue(row[column]) }}
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-else class="flex-1 flex items-center justify-center text-gray-500">
          <div class="text-center">
            <CircleStackIcon class="mx-auto h-12 w-12 text-gray-300" />
            <p class="mt-2">No sample data available</p>
            <p class="text-xs text-gray-400">Configure your query to see preview data</p>
          </div>
        </div>
      </div>

      <!-- Actions -->
      <div class="border-t border-gray-200 pt-4">
        <div class="flex items-center justify-between">
          <div class="text-sm text-gray-500">
            <p>Review the configuration and sample data before executing the import.</p>
            <p class="mt-1">This will create {{ getTargetTypeLabel(queryConfig?.target_type) }} records in Service Vault.</p>
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
              :disabled="!canExecuteImport"
              :class="[
                'inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500',
                canExecuteImport
                  ? 'text-white bg-indigo-600 hover:bg-indigo-700'
                  : 'text-gray-400 bg-gray-300 cursor-not-allowed'
              ]"
            >
              <PlayIcon class="w-4 h-4 mr-2" />
              Execute Import
              <span v-if="previewData.estimated_records" class="ml-1">
                ({{ formatNumber(previewData.estimated_records) }} records)
              </span>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Fallback for when no data, no loading, no error -->
    <div v-else class="flex items-center justify-center py-12">
      <div class="text-center">
        <CircleStackIcon class="mx-auto h-12 w-12 text-gray-300" />
        <h3 class="mt-4 text-sm font-medium text-gray-900">Preparing Import Preview</h3>
        <p class="mt-2 text-sm text-gray-500 max-w-sm">
          {{ isQueryBasedImport 
            ? 'Complete your query configuration to see import preview.' 
            : 'Loading import configuration...' 
          }}
        </p>
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
  ArrowPathIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
  show: Boolean,
  profile: {
    type: Object,
    default: null
  },
  queryConfig: {
    type: Object,
    default: () => ({})
  },
  // New prop to indicate if this is template-based or query-based import
  importType: {
    type: String,
    default: 'auto' // 'auto', 'template', 'query'
  }
})

const emit = defineEmits(['close', 'executed', 'execution-started'])

// State
const previewData = ref(null)
const error = ref(null)
const isLoading = ref(false)
const isRefreshing = ref(false)
const importMode = ref('create_only')
const batchSize = ref('100')
const duplicateStrategy = ref('exact')

// Computed
const detectedImportType = computed(() => {
  if (props.importType !== 'auto') {
    return props.importType
  }
  
  // Auto-detect based on profile configuration
  if (props.queryConfig?.base_table && props.queryConfig?.fields?.length > 0) {
    return 'query'
  }
  
  if (props.profile?.template_id || props.profile?.has_custom_queries) {
    return 'template'
  }
  
  return 'query' // Default fallback
})

const isQueryBasedImport = computed(() => detectedImportType.value === 'query')
const isTemplateBasedImport = computed(() => detectedImportType.value === 'template')

const mappedFields = computed(() => {
  if (!isQueryBasedImport.value) return []
  
  return props.queryConfig?.fields?.map(field => ({
    ...field,
    target_label: getTargetFieldLabel(field.target)
  })) || []
})

const canExecuteImport = computed(() => {
  if (isQueryBasedImport.value) {
    return previewData.value && 
           previewData.value.estimated_records > 0 && 
           mappedFields.value.length > 0
  } else {
    // Template-based import - just needs a profile and preview data
    return previewData.value && previewData.value.estimated_records > 0
  }
})

// Watch for changes
watch(() => [props.show, props.profile, props.queryConfig, props.importType], ([show, profile, queryConfig, importType]) => {
  if (show && profile) {
    if (isQueryBasedImport.value) {
      // For query-based imports, require queryConfig with base_table
      if (queryConfig?.base_table) {
        loadPreview()
      } else {
        // Show helpful message for incomplete query config
        previewData.value = null
        error.value = { 
          message: 'Query configuration is incomplete. Please complete your query setup in the previous steps.' 
        }
      }
    } else {
      // For template-based imports, just require profile
      loadPreview()
    }
  } else {
    previewData.value = null
    error.value = null
  }
}, { immediate: true, deep: true })

// Methods
const loadPreview = async () => {
  if (!props.profile) return
  
  // For query-based imports, require queryConfig with base_table
  if (isQueryBasedImport.value && !props.queryConfig?.base_table) return
  
  isLoading.value = true
  error.value = null
  
  try {
    let response
    
    if (isQueryBasedImport.value) {
      // Query-based import preview
      response = await fetch(`/api/import/profiles/${props.profile.id}/preview-query`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
        },
        body: JSON.stringify({
          ...props.queryConfig,
          limit: 20 // Preview limit
        })
      })
    } else {
      // Template-based import preview  
      response = await fetch(`/api/import/profiles/${props.profile.id}/simulate`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
        },
        body: JSON.stringify({
          configuration: props.profile.configuration || {},
          limit: 20 // Preview limit
        })
      })
    }

    if (response.ok) {
      const result = await response.json()
      
      if (isTemplateBasedImport.value && result.results) {
        // Template-based simulation returns results in a different format
        previewData.value = {
          sample_data: result.results.sample_data || [],
          estimated_records: result.results.estimated_records || 0
        }
      } else {
        previewData.value = result
      }
    } else {
      const errorData = await response.json()
      error.value = { message: errorData.message || 'Failed to load preview' }
    }
  } catch (err) {
    error.value = { message: err.message || 'Network error occurred' }
  } finally {
    isLoading.value = false
  }
}

const refreshPreview = async () => {
  isRefreshing.value = true
  await loadPreview()
  isRefreshing.value = false
}

const executeImport = async () => {
  if (!canExecuteImport.value) return
  
  try {
    let response
    let requestBody
    
    if (isQueryBasedImport.value) {
      // Query-based import execution
      response = await fetch(`/api/import/profiles/${props.profile.id}/execute-query`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
        },
        body: JSON.stringify({
          query_config: props.queryConfig,
          import_options: {
            mode: importMode.value,
            batch_size: parseInt(batchSize.value),
            duplicate_strategy: duplicateStrategy.value
          }
        })
      })
    } else {
      // Template-based import execution using the standard jobs API
      response = await fetch('/api/import/jobs', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
        },
        body: JSON.stringify({
          profile_id: props.profile.id,
          options: {
            batch_size: parseInt(batchSize.value)
          }
        })
      })
    }

    if (response.ok) {
      const result = await response.json()
      
      // Emit execution started event with job details for parent to show progress dialog
      emit('execution-started', {
        job_id: result.job_id || result.id,
        profile: props.profile,
        message: 'Import job started successfully',
        estimatedRecords: previewData.value?.estimated_records || 0,
        queryConfig: isQueryBasedImport.value ? props.queryConfig : null,
        importType: detectedImportType.value
      })
      
      // Also emit the legacy executed event for backward compatibility
      emit('executed', {
        job_id: result.job_id || result.id,
        message: 'Import job started successfully',
        estimated_records: previewData.value?.estimated_records || 0
      })
      
      // Close the preview modal
      emit('close')
    } else {
      const errorData = await response.json()
      console.error('Import execution failed:', errorData)
      
      // Emit execution started with error details for progress dialog to show
      emit('execution-started', {
        job_id: null,
        profile: props.profile,
        error: true,
        message: 'Failed to start import: ' + (errorData.message || 'Unknown error'),
        errorDetails: errorData.message || 'Unknown error occurred',
        estimatedRecords: 0
      })
    }
  } catch (error) {
    console.error('Import execution failed:', error)
    
    // Emit execution started with error details for progress dialog to show
    emit('execution-started', {
      job_id: null,
      profile: props.profile,
      error: true,
      message: 'Failed to start import job',
      errorDetails: error.message || 'Network error occurred',
      estimatedRecords: 0
    })
  }
}

const getTargetTypeLabel = (targetType) => {
  const labels = {
    'customer_users': 'Customer Users',
    'customers': 'Customers',
    'tickets': 'Tickets',
    'time_entries': 'Time Entries',
    'custom': 'Custom Records'
  }
  return labels[targetType] || targetType
}

const getTargetFieldLabel = (fieldName) => {
  // Convert snake_case to Title Case
  return fieldName
    .split('_')
    .map(word => word.charAt(0).toUpperCase() + word.slice(1))
    .join(' ')
}

const getFieldValue = (row, field) => {
  const value = row[field.source]
  if (value === null || value === undefined) {
    return '-'
  }
  if (typeof value === 'object') {
    return JSON.stringify(value)
  }
  return String(value)
}

const formatNumber = (num) => {
  if (!num || num === 0) return '0'
  if (num < 1000) return num.toString()
  if (num < 1000000) return `${(num / 1000).toFixed(1)}k`
  return `${(num / 1000000).toFixed(1)}M`
}

// Helper methods for template-based imports
const getTableColumns = (row) => {
  if (!row) return []
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
</script>