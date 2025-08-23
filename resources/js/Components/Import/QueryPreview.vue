<template>
  <div class="bg-white rounded-lg shadow-sm border border-gray-200">
    <!-- Header -->
    <div class="px-6 py-4 border-b border-gray-200">
      <div class="flex items-center justify-between">
        <div>
          <h3 class="text-lg font-medium text-gray-900">Query Preview</h3>
          <p class="mt-1 text-sm text-gray-500">
            Preview your query results and validate the configuration
          </p>
        </div>
        <div class="flex items-center space-x-3">
          <button
            @click="refreshPreview"
            :disabled="isLoading"
            class="inline-flex items-center px-3 py-1.5 border border-gray-300 text-xs font-medium rounded text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50"
          >
            <ArrowPathIcon class="w-4 h-4 mr-1.5" :class="{ 'animate-spin': isLoading }" />
            Refresh
          </button>
        </div>
      </div>
    </div>

    <!-- Query Status and Metrics -->
    <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <!-- Query Status -->
        <div class="flex items-center">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <CheckCircleIcon v-if="queryStatus === 'valid'" class="w-5 h-5 text-green-500" />
              <XCircleIcon v-else-if="queryStatus === 'invalid'" class="w-5 h-5 text-red-500" />
              <ClockIcon v-else class="w-5 h-5 text-yellow-500" />
            </div>
            <div class="ml-2">
              <p class="text-sm font-medium text-gray-900">Status</p>
              <p class="text-xs text-gray-600">{{ getStatusText() }}</p>
            </div>
          </div>
        </div>

        <!-- Estimated Records -->
        <div class="flex items-center">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <DocumentTextIcon class="w-5 h-5 text-blue-500" />
            </div>
            <div class="ml-2">
              <p class="text-sm font-medium text-gray-900">Est. Records</p>
              <p class="text-xs text-gray-600">{{ formatNumber(estimatedRecords) }}</p>
            </div>
          </div>
        </div>

        <!-- Query Complexity -->
        <div class="flex items-center">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <CogIcon class="w-5 h-5 text-purple-500" />
            </div>
            <div class="ml-2">
              <p class="text-sm font-medium text-gray-900">Complexity</p>
              <p class="text-xs text-gray-600">{{ getComplexityText() }}</p>
            </div>
          </div>
        </div>

        <!-- Performance -->
        <div class="flex items-center">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <BoltIcon class="w-5 h-5 text-amber-500" />
            </div>
            <div class="ml-2">
              <p class="text-sm font-medium text-gray-900">Performance</p>
              <p class="text-xs text-gray-600">{{ getPerformanceText() }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Query SQL Display -->
    <div v-if="generatedSQL" class="px-6 py-4 border-b border-gray-200">
      <div class="flex items-center justify-between mb-2">
        <h4 class="text-sm font-medium text-gray-900">Generated SQL</h4>
        <button
          @click="copySQL"
          class="inline-flex items-center px-2 py-1 text-xs font-medium text-gray-600 bg-gray-100 rounded hover:bg-gray-200"
        >
          <ClipboardDocumentIcon class="w-3 h-3 mr-1" />
          Copy
        </button>
      </div>
      <div class="bg-gray-900 rounded-md p-4 overflow-x-auto">
        <pre class="text-sm text-gray-100 whitespace-pre-wrap"><code>{{ formatSQL(generatedSQL) }}</code></pre>
      </div>
    </div>

    <!-- Validation Errors and Warnings -->
    <div v-if="validationErrors.length > 0 || validationWarnings.length > 0" class="px-6 py-4 border-b border-gray-200">
      <!-- Errors -->
      <div v-if="validationErrors.length > 0" class="mb-4">
        <h4 class="text-sm font-medium text-red-800 mb-2">
          <ExclamationTriangleIcon class="inline w-4 h-4 mr-1" />
          Errors ({{ validationErrors.length }})
        </h4>
        <ul class="space-y-1">
          <li v-for="error in validationErrors" :key="error" class="text-sm text-red-700 flex items-start">
            <XCircleIcon class="w-4 h-4 mr-2 mt-0.5 flex-shrink-0" />
            {{ error }}
          </li>
        </ul>
      </div>

      <!-- Warnings -->
      <div v-if="validationWarnings.length > 0">
        <h4 class="text-sm font-medium text-amber-800 mb-2">
          <ExclamationTriangleIcon class="inline w-4 h-4 mr-1" />
          Warnings ({{ validationWarnings.length }})
        </h4>
        <ul class="space-y-1">
          <li v-for="warning in validationWarnings" :key="warning" class="text-sm text-amber-700 flex items-start">
            <ExclamationTriangleIcon class="w-4 h-4 mr-2 mt-0.5 flex-shrink-0" />
            {{ warning }}
          </li>
        </ul>
      </div>
    </div>

    <!-- Sample Data Preview -->
    <div v-if="sampleData && sampleData.length > 0" class="px-6 py-4">
      <h4 class="text-sm font-medium text-gray-900 mb-3">Sample Data ({{ sampleData.length }} rows)</h4>
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th v-for="column in sampleColumns" :key="column" 
                  class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ column }}
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="(row, index) in sampleData" :key="index" class="hover:bg-gray-50">
              <td v-for="column in sampleColumns" :key="column"
                  class="px-3 py-2 whitespace-nowrap text-sm text-gray-900 max-w-xs truncate">
                <span :title="getDisplayValue(row[column])">{{ getDisplayValue(row[column]) }}</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- JOIN Impact Analysis -->
    <div v-if="joinAnalysis && joinAnalysis.length > 0" class="px-6 py-4 border-t border-gray-200 bg-blue-50">
      <h4 class="text-sm font-medium text-gray-900 mb-3">
        <LinkIcon class="inline w-4 h-4 mr-1" />
        JOIN Impact Analysis
      </h4>
      <div class="space-y-3">
        <div v-for="analysis in joinAnalysis" :key="analysis.join_table" 
             class="flex items-start justify-between p-3 bg-white rounded border">
          <div class="flex-1">
            <div class="flex items-center">
              <span class="text-sm font-medium text-gray-900">{{ analysis.join_table }}</span>
              <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium"
                    :class="getJoinImpactClass(analysis.impact)">
                {{ analysis.impact.toUpperCase() }}
              </span>
            </div>
            <p class="text-xs text-gray-600 mt-1">{{ analysis.description }}</p>
          </div>
          <div class="text-right">
            <p class="text-sm font-medium text-gray-900">{{ formatNumber(analysis.matched_records) }}</p>
            <p class="text-xs text-gray-500">matched records</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-if="!isLoading && !generatedSQL && !sampleData?.length" 
         class="px-6 py-12 text-center">
      <DocumentMagnifyingGlassIcon class="mx-auto h-12 w-12 text-gray-400" />
      <h3 class="mt-2 text-sm font-medium text-gray-900">No Query Preview</h3>
      <p class="mt-1 text-sm text-gray-500">
        Configure your query using the builder components to see a preview here.
      </p>
    </div>

    <!-- Loading State -->
    <div v-if="isLoading" class="px-6 py-12 text-center">
      <div class="inline-flex items-center">
        <ArrowPathIcon class="animate-spin -ml-1 mr-3 h-5 w-5 text-gray-600" />
        <span class="text-sm text-gray-600">Generating query preview...</span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import {
  ArrowPathIcon,
  CheckCircleIcon,
  XCircleIcon,
  ClockIcon,
  DocumentTextIcon,
  CogIcon,
  BoltIcon,
  ClipboardDocumentIcon,
  ExclamationTriangleIcon,
  LinkIcon,
  DocumentMagnifyingGlassIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
  profileId: {
    type: String,
    default: null
  },
  queryConfig: {
    type: Object,
    default: () => ({})
  },
  autoRefresh: {
    type: Boolean,
    default: true
  },
  refreshTrigger: {
    type: Number,
    default: 0
  }
})

const emit = defineEmits(['query-validated', 'preview-updated'])

// Will expose refreshPreview method

// State
const isLoading = ref(false)
const generatedSQL = ref('')
const sampleData = ref([])
const sampleColumns = ref([])
const estimatedRecords = ref(0)
const validationErrors = ref([])
const validationWarnings = ref([])
const joinAnalysis = ref([])
const queryStatus = ref('pending') // pending, valid, invalid

// Computed
const getStatusText = () => {
  switch (queryStatus.value) {
    case 'valid': return 'Valid Query'
    case 'invalid': return 'Invalid Query' 
    case 'pending': return 'Not Validated'
    default: return 'Unknown'
  }
}

const getComplexityText = () => {
  const joins = props.queryConfig.joins?.length || 0
  const filters = props.queryConfig.filters?.length || 0
  const fields = props.queryConfig.fields?.length || 0
  
  const totalComplexity = joins * 2 + filters + Math.floor(fields / 5)
  
  if (totalComplexity <= 3) return 'Simple'
  if (totalComplexity <= 8) return 'Moderate'
  return 'Complex'
}

const getPerformanceText = () => {
  if (estimatedRecords.value > 100000) return 'Slow'
  if (estimatedRecords.value > 10000) return 'Moderate'
  return 'Fast'
}

// Methods
const refreshPreview = async () => {
  if (!props.profileId || !props.queryConfig.base_table) {
    return
  }
  
  // Check if we have valid fields (core requirement)
  const hasValidFields = props.queryConfig.fields && 
    props.queryConfig.fields.length > 0 && 
    props.queryConfig.fields.every(field => 
      field.source && field.source.trim() !== '' &&
      field.target && field.target.trim() !== ''
    )
  
  // If no valid fields, we can't generate a meaningful query
  if (!hasValidFields) {
    // Clear previous results without showing errors
    queryStatus.value = 'pending'
    generatedSQL.value = ''
    estimatedRecords.value = 0
    sampleData.value = []
    validationErrors.value = []
    validationWarnings.value = []
    return
  }

  isLoading.value = true
  validationErrors.value = []
  validationWarnings.value = []

  try {
    // Clean up query config - remove empty filters
    const cleanQueryConfig = {
      ...props.queryConfig,
      filters: (props.queryConfig.filters || []).filter(filter => {
        if (!filter.field || !filter.field.trim() || !filter.operator || !filter.operator.trim()) {
          return false
        }
        
        // Null operators don't need values
        if (['IS NULL', 'IS NOT NULL'].includes(filter.operator)) {
          return true
        }
        
        // BETWEEN operators need both values
        if (filter.operator === 'BETWEEN') {
          return filter.value && filter.value.trim() !== '' && 
                 filter.value2 && filter.value2.trim() !== ''
        }
        
        // Other operators need at least one value
        return filter.value && filter.value.trim() !== ''
      })
    }
    
    
    // Validate and preview the query
    console.log('VALIDATE API - Sending to /builder/validate:', cleanQueryConfig)
    
    const response = await fetch(`/api/import/profiles/${props.profileId}/builder/validate`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
      },
      body: JSON.stringify(cleanQueryConfig)
    })

    const result = await response.json()

    if (response.ok) {
      console.log('VALIDATE API - Response:', {
        generated_sql: result.generated_sql,
        estimated_records: result.estimated_records
      })
      
      generatedSQL.value = result.generated_sql
      estimatedRecords.value = result.estimated_records || 0
      validationErrors.value = result.errors || []
      validationWarnings.value = result.warnings || []
      
      queryStatus.value = validationErrors.value.length === 0 ? 'valid' : 'invalid'

      // Get sample data if query is valid
      if (queryStatus.value === 'valid') {
        await fetchSampleData(cleanQueryConfig)
        await fetchJoinAnalysis()
      }
    } else {
      validationErrors.value = [result.message || 'Query validation failed']
      queryStatus.value = 'invalid'
    }

    emit('query-validated', {
      isValid: queryStatus.value === 'valid',
      errors: validationErrors.value,
      warnings: validationWarnings.value
    })

  } catch (error) {
    console.error('Query preview failed:', error)
    validationErrors.value = ['Failed to validate query: ' + error.message]
    queryStatus.value = 'invalid'
  } finally {
    isLoading.value = false
    emit('preview-updated')
  }
}

const fetchSampleData = async (queryConfig = props.queryConfig) => {
  try {
    // Always use complex query endpoint when we have field mappings
    // This ensures consistent SQL generation with the validation endpoint
    const hasFieldMappings = queryConfig.fields && queryConfig.fields.length > 0
    
    let response, endpoint, requestBody
    
    if (hasFieldMappings) {
      // Use complex query preview endpoint - same as validation
      endpoint = `/api/import/profiles/${props.profileId}/preview-query`
      requestBody = {
        ...queryConfig,
        limit: 10
      }
    } else {
      // Only use simple table preview when no field mappings exist
      endpoint = `/api/import/profiles/${props.profileId}/preview-table`  
      requestBody = {
        table_name: queryConfig.base_table,
        limit: 10
      }
    }
    
    console.log('SAMPLE DATA API - Sending to', endpoint + ':', requestBody)
    
    response = await fetch(endpoint, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
      },
      body: JSON.stringify(requestBody)
    })

    const result = await response.json()
    
    if (response.ok) {
      console.log('SAMPLE DATA API - Full response:', result)
      
      // Handle both simple table preview and complex query preview responses
      if (result.sample_data) {
        // Complex query preview response
        sampleData.value = result.sample_data
        estimatedRecords.value = result.estimated_records || 0
        
        // DO NOT overwrite the SQL from validation - it's already correct
        console.log('SAMPLE DATA API - NOT overwriting SQL. Current:', generatedSQL.value, 'API returned:', result.generated_query)
        console.log('SAMPLE DATA API - Sample data records:', sampleData.value.length, 'records:', sampleData.value.slice(0, 3))
        // generatedSQL.value = result.generated_query || ''
        
        if (result.columns) {
          sampleColumns.value = result.columns
        } else if (sampleData.value.length > 0) {
          sampleColumns.value = Object.keys(sampleData.value[0])
        }
      } else if (result.data) {
        // Simple table preview response
        sampleData.value = result.data
        estimatedRecords.value = result.row_count || 0
        if (sampleData.value.length > 0) {
          sampleColumns.value = Object.keys(sampleData.value[0])
        }
      }
    }
  } catch (error) {
    console.error('Failed to fetch sample data:', error)
  }
}

const fetchJoinAnalysis = async () => {
  if (!props.queryConfig.joins?.length) return

  try {
    const response = await fetch(`/api/import/profiles/${props.profileId}/builder/analyze-joins`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
      },
      body: JSON.stringify(props.queryConfig)
    })

    const result = await response.json()
    
    if (response.ok) {
      joinAnalysis.value = result.join_analysis || []
    }
  } catch (error) {
    console.error('Failed to fetch join analysis:', error)
  }
}

const copySQL = async () => {
  try {
    await navigator.clipboard.writeText(generatedSQL.value)
  } catch (error) {
    console.error('Failed to copy SQL:', error)
  }
}

const formatSQL = (sql) => {
  // Basic SQL formatting
  return sql
    .replace(/\bSELECT\b/g, 'SELECT')
    .replace(/\bFROM\b/g, '\nFROM')
    .replace(/\bLEFT JOIN\b/g, '\nLEFT JOIN')
    .replace(/\bINNER JOIN\b/g, '\nINNER JOIN') 
    .replace(/\bWHERE\b/g, '\nWHERE')
    .replace(/\bORDER BY\b/g, '\nORDER BY')
    .replace(/\bGROUP BY\b/g, '\nGROUP BY')
}

const formatNumber = (num) => {
  if (num >= 1000000) return (num / 1000000).toFixed(1) + 'M'
  if (num >= 1000) return (num / 1000).toFixed(1) + 'K'
  return num.toString()
}

const getDisplayValue = (value) => {
  if (value === null) return 'NULL'
  if (value === undefined) return ''
  if (typeof value === 'string' && value.length > 50) {
    return value.substring(0, 47) + '...'
  }
  return value.toString()
}

const getJoinImpactClass = (impact) => {
  switch (impact) {
    case 'high': return 'bg-red-100 text-red-800'
    case 'medium': return 'bg-yellow-100 text-yellow-800'
    case 'low': return 'bg-green-100 text-green-800'
    default: return 'bg-gray-100 text-gray-800'
  }
}

// DISABLE ALL AUTOMATIC WATCHING - something is causing circular updates
// Removed automatic refresh to prevent recursive loops
// Component will only update when user clicks "Refresh" button

// Expose refreshPreview method for parent component
defineExpose({
  refreshPreview
})
</script>