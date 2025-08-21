<template>
  <div class="bg-white border border-gray-200 rounded-lg p-6">
    <div class="flex items-center justify-between mb-4">
      <h3 class="text-lg font-medium text-gray-900">Data Filters</h3>
      <button
        @click="addFilter"
        class="inline-flex items-center px-3 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700"
      >
        <PlusIcon class="w-4 h-4 mr-2" />
        Add Filter
      </button>
    </div>

    <div class="mb-4">
      <p class="text-sm text-gray-600">
        Add conditions to filter which records are imported. All conditions are combined with AND logic.
      </p>
    </div>

    <!-- Filters List -->
    <div v-if="filters.length > 0" class="space-y-4">
      <div
        v-for="(filter, index) in filters"
        :key="index"
        class="border border-gray-200 rounded-lg p-4"
      >
        <div class="grid grid-cols-1 lg:grid-cols-5 gap-4 items-start">
          <!-- Field Selection -->
          <div class="lg:col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-1">Field</label>
            <select
              v-model="filter.field"
              @change="onFieldChange(filter)"
              class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm"
            >
              <option value="">Select field...</option>
              <optgroup
                v-for="table in availableFields"
                :key="table.name"
                :label="table.name"
              >
                <option
                  v-for="column in table.columns"
                  :key="`${table.name}.${column.name}`"
                  :value="`${table.name}.${column.name}`"
                  :title="column.data_type"
                >
                  {{ column.name }} ({{ column.data_type }})
                </option>
              </optgroup>
            </select>
          </div>

          <!-- Operator -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Operator</label>
            <select
              v-model="filter.operator"
              @change="onOperatorChange(filter)"
              class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm"
            >
              <option value="">Select operator...</option>
              <optgroup label="Comparison">
                <option value="=">=</option>
                <option value="!=">≠ (not equal)</option>
                <option value=">">&gt;</option>
                <option value=">=">&gt;=</option>
                <option value="<">&lt;</option>
                <option value="<=">&lt;=</option>
              </optgroup>
              <optgroup label="Text">
                <option value="LIKE">LIKE (contains)</option>
                <option value="ILIKE">ILIKE (case-insensitive)</option>
                <option value="NOT LIKE">NOT LIKE</option>
                <option value="REGEXP">REGEXP (pattern)</option>
              </optgroup>
              <optgroup label="Lists">
                <option value="IN">IN (list)</option>
                <option value="NOT IN">NOT IN</option>
              </optgroup>
              <optgroup label="Null Checks">
                <option value="IS NULL">IS NULL</option>
                <option value="IS NOT NULL">IS NOT NULL</option>
              </optgroup>
              <optgroup label="Date/Time">
                <option value="BETWEEN">BETWEEN</option>
                <option value="DATE_TRUNC">DATE_TRUNC</option>
              </optgroup>
            </select>
          </div>

          <!-- Value -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Value</label>
            
            <!-- Single Value Input -->
            <input
              v-if="!isNullOperator(filter.operator) && !isBetweenOperator(filter.operator)"
              v-model="filter.value"
              :type="getInputType(filter)"
              :placeholder="getValuePlaceholder(filter)"
              class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm"
            />
            
            <!-- Range Input for BETWEEN -->
            <div v-else-if="isBetweenOperator(filter.operator)" class="space-y-2">
              <input
                v-model="filter.value"
                :type="getInputType(filter)"
                placeholder="Start value"
                class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm"
              />
              <input
                v-model="filter.value2"
                :type="getInputType(filter)"
                placeholder="End value"
                class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm"
              />
            </div>

            <!-- Null operators don't need values -->
            <div v-else-if="isNullOperator(filter.operator)" class="text-sm text-gray-500 italic">
              No value required
            </div>
          </div>

          <!-- Actions -->
          <div class="flex justify-end space-x-2">
            <button
              @click="duplicateFilter(index)"
              class="inline-flex items-center p-2 border border-transparent rounded-md text-gray-600 hover:bg-gray-50"
              title="Duplicate filter"
            >
              <DocumentDuplicateIcon class="w-4 h-4" />
            </button>
            <button
              @click="removeFilter(index)"
              class="inline-flex items-center p-2 border border-transparent rounded-md text-red-600 hover:bg-red-50"
              title="Remove filter"
            >
              <TrashIcon class="w-4 h-4" />
            </button>
          </div>
        </div>

        <!-- Filter Preview -->
        <div v-if="isValidFilter(filter)" class="mt-4 p-3 bg-gray-50 rounded-md">
          <p class="text-xs font-medium text-gray-700 mb-1">SQL Preview:</p>
          <code class="text-xs text-gray-600">{{ formatFilterSQL(filter) }}</code>
        </div>

        <!-- Validation Error -->
        <div v-else-if="filter.field && filter.operator" class="mt-3 p-3 bg-red-50 border border-red-200 rounded-md">
          <p class="text-xs text-red-700">
            <ExclamationTriangleIcon class="w-4 h-4 inline mr-1" />
            {{ getFilterValidationError(filter) }}
          </p>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else class="text-center py-8">
      <FunnelIcon class="mx-auto h-12 w-12 text-gray-400" />
      <h3 class="mt-2 text-sm font-medium text-gray-900">No filters applied</h3>
      <p class="mt-1 text-sm text-gray-500">
        Add filters to limit which records are imported from the source database.
      </p>
    </div>

    <!-- Suggested Filters -->
    <div v-if="suggestedFilters.length > 0" class="mt-6">
      <h4 class="text-sm font-medium text-gray-900 mb-3">Suggested Filters</h4>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
        <div
          v-for="suggestion in suggestedFilters"
          :key="`${suggestion.field}-${suggestion.operator}`"
          class="flex items-center justify-between p-3 bg-blue-50 border border-blue-200 rounded-lg"
        >
          <div>
            <p class="text-sm font-medium text-blue-900">{{ suggestion.name }}</p>
            <p class="text-xs text-blue-700">{{ suggestion.description }}</p>
          </div>
          <button
            @click="applySuggestedFilter(suggestion)"
            class="inline-flex items-center px-2 py-1 border border-transparent text-xs font-medium rounded text-blue-700 bg-blue-100 hover:bg-blue-200"
          >
            Apply
          </button>
        </div>
      </div>
    </div>

    <!-- Complete WHERE Clause Preview -->
    <div v-if="validFilters.length > 0" class="mt-6">
      <h4 class="text-sm font-medium text-gray-900 mb-2">Complete WHERE Clause</h4>
      <div class="p-4 bg-gray-50 rounded-md">
        <pre class="text-xs text-gray-700 whitespace-pre-wrap">{{ completeWhereClause }}</pre>
      </div>
      <div class="mt-2 flex items-center text-xs text-gray-600">
        <InformationCircleIcon class="w-4 h-4 mr-1" />
        {{ validFilters.length }} filter(s) will be applied
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import {
  PlusIcon,
  TrashIcon,
  FunnelIcon,
  DocumentDuplicateIcon,
  ExclamationTriangleIcon,
  InformationCircleIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
  baseTable: {
    type: Object,
    default: null
  },
  joins: {
    type: Array,
    default: () => []
  },
  availableTables: {
    type: Array,
    default: () => []
  },
  modelValue: {
    type: Array,
    default: () => []
  },
  selectedTargetType: {
    type: String,
    default: ''
  }
})

const emit = defineEmits(['update:modelValue', 'filters-changed'])

// State
const filters = ref([...props.modelValue])

// Computed
const availableFields = computed(() => {
  const tables = []
  
  // Add base table
  if (props.baseTable?.columns) {
    tables.push({
      name: props.baseTable.name,
      columns: props.baseTable.columns
    })
  }
  
  // Add joined tables
  props.joins.forEach(join => {
    if (join.table) {
      const joinTable = props.availableTables.find(t => t.name === join.table)
      if (joinTable?.columns) {
        tables.push({
          name: join.table,
          columns: joinTable.columns
        })
      }
    }
  })
  
  return tables
})

const validFilters = computed(() => {
  return filters.value.filter(filter => isValidFilter(filter))
})

const completeWhereClause = computed(() => {
  if (validFilters.value.length === 0) return ''
  
  const clauses = validFilters.value.map(filter => formatFilterSQL(filter))
  return `WHERE ${clauses.join('\n  AND ')}`
})

const suggestedFilters = computed(() => {
  if (!props.selectedTargetType || !props.baseTable) return []
  
  const suggestions = []
  
  // Common suggested filters based on target type and available fields
  const commonSuggestions = {
    customer_users: [
      {
        name: 'Active customers only',
        description: 'Exclude inactive/deleted customers',
        field: 'status',
        operator: '!=',
        value: 'deleted'
      },
      {
        name: 'Recent customers',
        description: 'Customers created in the last year',
        field: 'created_at',
        operator: '>=',
        value: new Date(Date.now() - 365 * 24 * 60 * 60 * 1000).toISOString().split('T')[0]
      }
    ],
    tickets: [
      {
        name: 'Exclude spam',
        description: 'Filter out spam tickets',
        field: 'state',
        operator: '!=',
        value: 'spam'
      },
      {
        name: 'Recent tickets only',
        description: 'Tickets from the last 2 years',
        field: 'created_at',
        operator: '>=',
        value: new Date(Date.now() - 2 * 365 * 24 * 60 * 60 * 1000).toISOString().split('T')[0]
      }
    ],
    time_entries: [
      {
        name: 'Billable entries only',
        description: 'Only import billable time entries',
        field: 'billable',
        operator: '=',
        value: 'true'
      },
      {
        name: 'Recent entries',
        description: 'Time entries from the last 6 months',
        field: 'date',
        operator: '>=',
        value: new Date(Date.now() - 6 * 30 * 24 * 60 * 60 * 1000).toISOString().split('T')[0]
      }
    ]
  }
  
  const targetSuggestions = commonSuggestions[props.selectedTargetType] || []
  
  // Only include suggestions for fields that actually exist
  targetSuggestions.forEach(suggestion => {
    const fieldExists = availableFields.value.some(table => 
      table.columns.some(col => col.name === suggestion.field)
    )
    
    if (fieldExists) {
      // Add table prefix if needed
      const fieldWithTable = findFieldWithTable(suggestion.field)
      if (fieldWithTable) {
        suggestions.push({
          ...suggestion,
          field: fieldWithTable
        })
      }
    }
  })
  
  return suggestions
})

// Methods
const addFilter = () => {
  filters.value.push({
    field: '',
    operator: '',
    value: '',
    value2: '' // For BETWEEN operations
  })
  updateModelValue()
}

const removeFilter = (index) => {
  filters.value.splice(index, 1)
  updateModelValue()
}

const duplicateFilter = (index) => {
  const original = filters.value[index]
  filters.value.splice(index + 1, 0, { ...original })
  updateModelValue()
}

const updateModelValue = () => {
  emit('update:modelValue', filters.value)
  emit('filters-changed', filters.value)
}

const onFieldChange = (filter) => {
  // Reset operator and value when field changes
  filter.operator = ''
  filter.value = ''
  filter.value2 = ''
}

const onOperatorChange = (filter) => {
  // Reset values when operator changes
  filter.value = ''
  filter.value2 = ''
}

const isValidFilter = (filter) => {
  if (!filter.field || !filter.operator) return false
  
  // Null operators don't need values
  if (isNullOperator(filter.operator)) return true
  
  // BETWEEN needs both values
  if (isBetweenOperator(filter.operator)) {
    return filter.value && filter.value2
  }
  
  // Other operators need at least one value
  return filter.value !== ''
}

const isNullOperator = (operator) => {
  return ['IS NULL', 'IS NOT NULL'].includes(operator)
}

const isBetweenOperator = (operator) => {
  return operator === 'BETWEEN'
}

const getInputType = (filter) => {
  if (!filter.field) return 'text'
  
  const column = findColumnInfo(filter.field)
  if (!column) return 'text'
  
  switch (column.data_type.toLowerCase()) {
    case 'integer':
    case 'bigint':
    case 'smallint':
    case 'numeric':
    case 'decimal':
      return 'number'
    case 'date':
      return 'date'
    case 'timestamp':
    case 'datetime':
      return 'datetime-local'
    case 'boolean':
      return 'text' // We'll use select for boolean
    default:
      return 'text'
  }
}

const getValuePlaceholder = (filter) => {
  if (!filter.field || !filter.operator) return 'Enter value...'
  
  const column = findColumnInfo(filter.field)
  if (!column) return 'Enter value...'
  
  switch (filter.operator) {
    case 'LIKE':
    case 'ILIKE':
      return '%pattern%'
    case 'IN':
    case 'NOT IN':
      return 'value1,value2,value3'
    case 'REGEXP':
      return '^pattern$'
    default:
      switch (column.data_type.toLowerCase()) {
        case 'date':
          return 'YYYY-MM-DD'
        case 'timestamp':
          return 'YYYY-MM-DD HH:MM:SS'
        case 'boolean':
          return 'true or false'
        default:
          return 'Enter value...'
      }
  }
}

const getFilterValidationError = (filter) => {
  if (!filter.field) return 'Field is required'
  if (!filter.operator) return 'Operator is required'
  
  if (isNullOperator(filter.operator)) return null
  
  if (isBetweenOperator(filter.operator)) {
    if (!filter.value) return 'Start value is required'
    if (!filter.value2) return 'End value is required'
    return null
  }
  
  if (!filter.value) return 'Value is required'
  
  return null
}

const formatFilterSQL = (filter) => {
  if (!isValidFilter(filter)) return ''
  
  let sql = filter.field
  
  switch (filter.operator) {
    case 'BETWEEN':
      sql += ` BETWEEN '${filter.value}' AND '${filter.value2}'`
      break
    case 'IN':
    case 'NOT IN':
      const values = filter.value.split(',').map(v => `'${v.trim()}'`).join(', ')
      sql += ` ${filter.operator} (${values})`
      break
    case 'IS NULL':
    case 'IS NOT NULL':
      sql += ` ${filter.operator}`
      break
    case 'LIKE':
    case 'ILIKE':
    case 'NOT LIKE':
      sql += ` ${filter.operator} '${filter.value}'`
      break
    default:
      // Handle numeric vs string values
      const column = findColumnInfo(filter.field)
      const isNumeric = column && ['integer', 'bigint', 'numeric', 'decimal'].includes(column.data_type.toLowerCase())
      
      if (isNumeric && !isNaN(filter.value)) {
        sql += ` ${filter.operator} ${filter.value}`
      } else {
        sql += ` ${filter.operator} '${filter.value}'`
      }
  }
  
  return sql
}

const findColumnInfo = (fieldPath) => {
  const [tableName, columnName] = fieldPath.split('.')
  if (!tableName || !columnName) return null
  
  const table = availableFields.value.find(t => t.name === tableName)
  if (!table) return null
  
  return table.columns.find(c => c.name === columnName)
}

const findFieldWithTable = (fieldName) => {
  for (const table of availableFields.value) {
    const column = table.columns.find(c => c.name === fieldName)
    if (column) {
      return `${table.name}.${fieldName}`
    }
  }
  return null
}

const applySuggestedFilter = (suggestion) => {
  filters.value.push({
    field: suggestion.field,
    operator: suggestion.operator,
    value: suggestion.value,
    value2: ''
  })
  updateModelValue()
}

// Watchers
watch(() => props.modelValue, (newValue) => {
  filters.value = [...newValue]
}, { deep: true })

watch(filters, () => {
  updateModelValue()
}, { deep: true })
</script>