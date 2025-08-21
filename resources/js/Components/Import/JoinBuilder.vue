<template>
  <div class="bg-white border border-gray-200 rounded-lg p-6">
    <div class="flex items-center justify-between mb-4">
      <h3 class="text-lg font-medium text-gray-900">Table Relationships</h3>
      <button
        @click="addJoin"
        class="inline-flex items-center px-3 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700"
      >
        <PlusIcon class="w-4 h-4 mr-2" />
        Add Join
      </button>
    </div>

    <!-- Base Table Display -->
    <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
      <div class="flex items-center">
        <TableCellsIcon class="w-5 h-5 text-blue-600 mr-3" />
        <div>
          <h4 class="text-sm font-medium text-blue-900">Base Table</h4>
          <p class="text-sm text-blue-700">{{ baseTable?.name || 'No table selected' }}</p>
        </div>
      </div>
    </div>

    <!-- Joins List -->
    <div v-if="joins.length > 0" class="space-y-4">
      <div
        v-for="(join, index) in joins"
        :key="index"
        class="border border-gray-200 rounded-lg p-4"
      >
        <div class="flex items-start justify-between">
          <div class="flex-1 grid grid-cols-1 md:grid-cols-4 gap-4">
            <!-- Join Type -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Join Type</label>
              <select
                v-model="join.type"
                class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm"
              >
                <option value="INNER">INNER JOIN</option>
                <option value="LEFT">LEFT JOIN</option>
                <option value="RIGHT">RIGHT JOIN</option>
                <option value="FULL">FULL OUTER JOIN</option>
              </select>
            </div>

            <!-- Target Table -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Join Table</label>
              <select
                v-model="join.table"
                @change="onJoinTableChange(join, index)"
                class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm"
              >
                <option value="">Select table...</option>
                <option
                  v-for="table in availableTables"
                  :key="table.name"
                  :value="table.name"
                >
                  {{ table.name }}
                </option>
              </select>
            </div>

            <!-- Left Column -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Left Column</label>
              <select
                v-model="join.leftColumn"
                class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm"
              >
                <option value="">Select column...</option>
                <option
                  v-for="column in getAvailableLeftColumns(join, index)"
                  :key="column.name"
                  :value="column.name"
                  :title="column.data_type"
                >
                  {{ column.table_name }}.{{ column.name }}
                  <span v-if="column.is_foreign_key">🔗</span>
                  <span v-if="column.is_primary_key">🔑</span>
                </option>
              </select>
            </div>

            <!-- Right Column -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Right Column</label>
              <select
                v-model="join.rightColumn"
                class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm"
              >
                <option value="">Select column...</option>
                <option
                  v-for="column in getRightColumns(join)"
                  :key="column.name"
                  :value="column.name"
                  :title="column.data_type"
                >
                  {{ join.table }}.{{ column.name }}
                  <span v-if="column.is_foreign_key">🔗</span>
                  <span v-if="column.is_primary_key">🔑</span>
                </option>
              </select>
            </div>
          </div>

          <!-- Remove Join Button -->
          <button
            @click="removeJoin(index)"
            class="ml-4 inline-flex items-center p-2 border border-transparent rounded-md text-red-600 hover:bg-red-50"
          >
            <TrashIcon class="w-4 h-4" />
          </button>
        </div>

        <!-- Additional Condition -->
        <div v-if="join.table" class="mt-4">
          <label class="block text-sm font-medium text-gray-700 mb-1">Additional Condition (Optional)</label>
          <input
            v-model="join.condition"
            type="text"
            placeholder="e.g., emails.type = 'work'"
            class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm"
          />
          <p class="mt-1 text-xs text-gray-500">
            SQL WHERE condition to apply to this join
          </p>
        </div>

        <!-- Join Preview -->
        <div v-if="isValidJoin(join)" class="mt-4 p-3 bg-gray-50 rounded-md">
          <p class="text-xs font-medium text-gray-700 mb-1">SQL Preview:</p>
          <code class="text-xs text-gray-600">
            {{ formatJoinSQL(join) }}
          </code>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else class="text-center py-8">
      <LinkIcon class="mx-auto h-12 w-12 text-gray-400" />
      <h3 class="mt-2 text-sm font-medium text-gray-900">No joins configured</h3>
      <p class="mt-1 text-sm text-gray-500">
        Add table joins to combine data from multiple tables.
      </p>
    </div>

    <!-- Suggested Joins -->
    <div v-if="allSuggestedJoins.length > 0" class="mt-6">
      <div class="flex items-center justify-between mb-3">
        <h4 class="text-sm font-medium text-gray-900">Suggested Joins</h4>
        <span class="text-xs text-gray-500">
          {{ filteredSuggestedJoins.length }} of {{ allSuggestedJoins.length }} suggestions
        </span>
      </div>
      
      <!-- Filters and Controls -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-4 p-3 bg-gray-50 rounded-lg">
        <!-- Search Filter -->
        <div>
          <label class="block text-xs font-medium text-gray-700 mb-1">Search</label>
          <input
            v-model="suggestionsFilter"
            type="text"
            placeholder="Filter by table or column..."
            class="w-full text-xs border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
          />
        </div>
        
        <!-- Confidence Filter -->
        <div>
          <label class="block text-xs font-medium text-gray-700 mb-1">
            Min Confidence: {{ Math.round(minConfidence * 100) }}%
          </label>
          <input
            v-model.number="minConfidence"
            type="range"
            min="0"
            max="1"
            step="0.1"
            class="w-full"
          />
        </div>
        
        <!-- Limit -->
        <div>
          <label class="block text-xs font-medium text-gray-700 mb-1">Show</label>
          <select
            v-model.number="suggestionsLimit"
            class="w-full text-xs border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
          >
            <option :value="5">Top 5</option>
            <option :value="10">Top 10</option>
            <option :value="15">Top 15</option>
            <option :value="25">Top 25</option>
            <option :value="50">Top 50</option>
            <option :value="999">All</option>
          </select>
        </div>
      </div>
      
      <!-- Suggestions List -->
      <div v-if="filteredSuggestedJoins.length > 0" class="space-y-2 max-h-64 overflow-y-auto">
        <div
          v-for="suggestion in filteredSuggestedJoins"
          :key="`${suggestion.table}-${suggestion.leftColumn}-${suggestion.rightColumn}`"
          class="flex items-center justify-between p-3 bg-green-50 border border-green-200 rounded-lg"
        >
          <div>
            <p class="text-sm font-medium text-green-900">
              {{ baseTable?.name }} → {{ suggestion.table }}
            </p>
            <p class="text-xs text-green-700">
              {{ suggestion.leftColumn }} = {{ suggestion.rightColumn }}
              <span v-if="suggestion.confidence" class="ml-2 text-green-600">
                ({{ Math.round(suggestion.confidence * 100) }}% confidence)
              </span>
            </p>
          </div>
          <button
            @click="applySuggestedJoin(suggestion)"
            class="inline-flex items-center px-2 py-1 border border-transparent text-xs font-medium rounded text-green-700 bg-green-100 hover:bg-green-200"
          >
            Apply
          </button>
        </div>
      </div>
      
      <!-- No Results Message -->
      <div v-else class="text-center py-4 text-gray-500 text-sm">
        No suggestions match your current filters.
        <button
          @click="suggestionsFilter = ''; minConfidence = 0"
          class="ml-2 text-indigo-600 hover:text-indigo-800 underline"
        >
          Clear filters
        </button>
      </div>
    </div>

    <!-- Full Query Preview -->
    <div v-if="joins.length > 0" class="mt-6">
      <h4 class="text-sm font-medium text-gray-900 mb-2">Complete Query Preview</h4>
      <div class="p-4 bg-gray-50 rounded-md">
        <pre class="text-xs text-gray-700 whitespace-pre-wrap">{{ fullQueryPreview }}</pre>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import {
  PlusIcon,
  TrashIcon,
  TableCellsIcon,
  LinkIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
  baseTable: {
    type: Object,
    default: null
  },
  availableTables: {
    type: Array,
    default: () => []
  },
  modelValue: {
    type: Array,
    default: () => []
  },
  profileId: {
    type: String,
    required: false
  }
})

const emit = defineEmits(['update:modelValue', 'joins-changed'])

// State
const joins = ref([...props.modelValue])
const suggestedJoins = ref([])
const allSuggestedJoins = ref([]) // Store all suggestions before filtering
const tableColumns = ref({}) // Cache for table columns
const suggestionsLimit = ref(15)
const suggestionsFilter = ref('')
const minConfidence = ref(0.3)

// Computed
const fullQueryPreview = computed(() => {
  if (!props.baseTable || joins.value.length === 0) return ''
  
  let query = `SELECT *\nFROM ${props.baseTable.name}`
  
  joins.value.forEach(join => {
    if (isValidJoin(join)) {
      query += `\n${join.type} JOIN ${join.table} ON ${join.leftColumn} = ${join.table}.${join.rightColumn}`
      if (join.condition) {
        query += ` AND ${join.condition}`
      }
    }
  })
  
  return query
})

const filteredSuggestedJoins = computed(() => {
  let filtered = allSuggestedJoins.value
  
  // Apply confidence filter
  filtered = filtered.filter(suggestion => suggestion.confidence >= minConfidence.value)
  
  // Apply text filter
  if (suggestionsFilter.value.trim()) {
    const filter = suggestionsFilter.value.toLowerCase()
    filtered = filtered.filter(suggestion => 
      suggestion.table.toLowerCase().includes(filter) ||
      suggestion.leftColumn.toLowerCase().includes(filter) ||
      suggestion.rightColumn.toLowerCase().includes(filter)
    )
  }
  
  // Sort by confidence and apply limit
  return filtered
    .sort((a, b) => b.confidence - a.confidence)
    .slice(0, suggestionsLimit.value)
})

// Methods
const addJoin = () => {
  joins.value.push({
    type: 'LEFT',
    table: '',
    leftColumn: '',
    rightColumn: '',
    condition: ''
  })
  updateModelValue()
}

const removeJoin = (index) => {
  joins.value.splice(index, 1)
  updateModelValue()
}

const updateModelValue = () => {
  emit('update:modelValue', joins.value)
  emit('joins-changed', joins.value)
}

const onJoinTableChange = (join, index) => {
  // Reset columns when table changes
  join.rightColumn = ''
  
  // Load columns for the selected table
  if (join.table && !tableColumns.value[join.table]) {
    loadTableColumns(join.table)
  }
  
  updateModelValue()
}

const loadTableColumns = (tableName) => {
  // Use available tables instead of making a new API call
  const table = props.availableTables.find(t => t.name === tableName)
  if (table?.columns) {
    tableColumns.value[tableName] = table.columns
  }
}

const getAvailableLeftColumns = (join, joinIndex) => {
  // Get columns from base table and all previous joins
  let columns = []
  
  // Add base table columns
  if (props.baseTable?.columns) {
    columns.push(...props.baseTable.columns.map(col => ({
      ...col,
      table_name: props.baseTable.name
    })))
  }
  
  // Add columns from previous joins
  for (let i = 0; i < joinIndex; i++) {
    const prevJoin = joins.value[i]
    if (prevJoin.table && tableColumns.value[prevJoin.table]) {
      columns.push(...tableColumns.value[prevJoin.table].map(col => ({
        ...col,
        table_name: prevJoin.table
      })))
    }
  }
  
  return columns
}

const getRightColumns = (join) => {
  if (!join.table) return []
  return tableColumns.value[join.table] || []
}

const isValidJoin = (join) => {
  return join.table && join.leftColumn && join.rightColumn
}

const formatJoinSQL = (join) => {
  if (!isValidJoin(join)) return ''
  
  let sql = `${join.type} JOIN ${join.table} ON ${join.leftColumn} = ${join.table}.${join.rightColumn}`
  if (join.condition) {
    sql += ` AND ${join.condition}`
  }
  return sql
}

const generateSuggestedJoins = () => {
  if (!props.baseTable?.columns) return
  
  const suggestions = []
  const baseColumns = props.baseTable.columns
  
  props.availableTables.forEach(table => {
    if (table.name === props.baseTable.name) return
    if (!table.columns) return
    
    // Look for potential foreign key relationships
    baseColumns.forEach(baseCol => {
      table.columns.forEach(targetCol => {
        // Check for common naming patterns
        const confidence = calculateJoinConfidence(baseCol, targetCol, props.baseTable.name, table.name)
        
        if (confidence > 0.3) { // Only suggest if confidence > 30%
          suggestions.push({
            table: table.name,
            leftColumn: `${props.baseTable.name}.${baseCol.name}`,
            rightColumn: targetCol.name,
            confidence: confidence,
            type: 'LEFT'
          })
        }
      })
    })
  })
  
  // Store all suggestions and let the computed property handle filtering
  allSuggestedJoins.value = suggestions.filter(s => s.confidence > 0)
}

const calculateJoinConfidence = (leftCol, rightCol, leftTable, rightTable) => {
  // Safety checks
  if (!leftCol || !rightCol || !leftCol.name || !rightCol.name) {
    return 0
  }
  
  let confidence = 0
  
  // Check for exact name matches
  if (leftCol.name === rightCol.name) {
    confidence += 0.4
  }
  
  // Check for foreign key patterns
  if (leftCol.name === `${rightTable}_id` || rightCol.name === `${leftTable}_id`) {
    confidence += 0.6
  }
  
  // Check for common patterns
  if (leftCol.name.includes('_id') && rightCol.name === 'id') {
    confidence += 0.3
  }
  
  // Check for primary/foreign key indicators
  if (leftCol.is_foreign_key && rightCol.is_primary_key) {
    confidence += 0.5
  }
  
  // Check for data type compatibility
  if (leftCol.data_type && rightCol.data_type && leftCol.data_type === rightCol.data_type) {
    confidence += 0.1
  }
  
  return Math.min(confidence, 1.0) // Cap at 100%
}

const applySuggestedJoin = (suggestion) => {
  joins.value.push({
    type: suggestion.type,
    table: suggestion.table,
    leftColumn: suggestion.leftColumn,
    rightColumn: suggestion.rightColumn,
    condition: ''
  })
  
  // Load columns for the new table if needed
  if (!tableColumns.value[suggestion.table]) {
    loadTableColumns(suggestion.table)
  }
  
  updateModelValue()
  
  // Remove applied suggestion from all suggestions
  allSuggestedJoins.value = allSuggestedJoins.value.filter(s => 
    s.table !== suggestion.table || 
    s.leftColumn !== suggestion.leftColumn || 
    s.rightColumn !== suggestion.rightColumn
  )
}

// Watchers
watch(() => props.modelValue, (newValue) => {
  joins.value = [...newValue]
}, { deep: true })

watch(() => props.baseTable, (newBaseTable) => {
  if (newBaseTable) {
    generateSuggestedJoins()
  }
}, { immediate: true })

watch(() => props.availableTables, (newTables) => {
  if (newTables.length > 0 && props.baseTable) {
    generateSuggestedJoins()
  }
}, { deep: true, immediate: true })

// Load columns for tables that are already in joins
watch(joins, (newJoins) => {
  newJoins.forEach(join => {
    if (join.table && !tableColumns.value[join.table]) {
      loadTableColumns(join.table)
    }
  })
}, { deep: true, immediate: true })

// Initialize: preload base table columns and any existing join table columns
watch(() => [props.baseTable, props.availableTables], () => {
  // Preload base table columns if available
  if (props.baseTable?.name && props.baseTable?.columns) {
    tableColumns.value[props.baseTable.name] = props.baseTable.columns
  }
  
  // Preload columns for any existing joins
  joins.value.forEach(join => {
    if (join.table && !tableColumns.value[join.table]) {
      loadTableColumns(join.table)
    }
  })
  
  // Generate suggestions when data is available
  if (props.baseTable && props.availableTables.length > 0) {
    generateSuggestedJoins()
  }
}, { deep: true, immediate: true })
</script>