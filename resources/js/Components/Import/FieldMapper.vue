<template>
  <div class="bg-white border border-gray-200 rounded-lg p-6">
    <div class="flex items-center justify-between mb-4">
      <h3 class="text-lg font-medium text-gray-900">Field Mapping</h3>
      <div class="flex items-center space-x-2">
        <button
          @click="addCustomField"
          class="inline-flex items-center px-3 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
        >
          <PlusIcon class="w-4 h-4 mr-2" />
          Add Field
        </button>
        <button
          @click="autoMapFields"
          :disabled="!baseTable"
          class="inline-flex items-center px-3 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50"
        >
          <BoltIcon class="w-4 h-4 mr-2" />
          Auto Map
        </button>
      </div>
    </div>

    <!-- Target Type Selection -->
    <div class="mb-6">
      <label class="block text-sm font-medium text-gray-700 mb-2">Import Target</label>
      <select
        v-model="selectedTargetType"
        @change="onTargetTypeChange"
        class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
      >
        <option value="">Select target type...</option>
        <option value="customer_users">Customer Users</option>
        <option value="tickets">Support Tickets</option>
        <option value="time_entries">Time Entries</option>
        <option value="agents">Agent Users</option>
        <option value="accounts">Accounts</option>
        <option value="custom">Custom (Manual Mapping)</option>
      </select>
      <p class="mt-1 text-sm text-gray-500">
        What type of data are you importing into Service Vault?
      </p>
    </div>

    <!-- Fields List -->
    <div v-if="fields.length > 0" class="space-y-4">
      <div
        v-for="(field, index) in fields"
        :key="index"
        class="border border-gray-200 rounded-lg p-4"
      >
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 items-start">
          <!-- Source Field (4 columns) -->
          <div class="lg:col-span-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Source Field</label>
            <div v-if="field.isCustom" class="flex">
              <input
                v-model="field.source"
                type="text"
                placeholder="Custom SQL expression..."
                class="flex-1 border-gray-300 rounded-l-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm"
              />
              <div class="relative">
                <button
                  @click="showSourceFieldHelper(field, index)"
                  class="px-3 py-2 border-l-0 border-gray-300 bg-gray-50 rounded-r-md text-gray-600 hover:bg-gray-100"
                >
                  <QuestionMarkCircleIcon class="w-4 h-4" />
                </button>
              </div>
            </div>
            <select
              v-else
              v-model="field.source"
              class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm"
            >
              <option value="">Select source field...</option>
              <optgroup
                v-for="table in availableSourceFields"
                :key="table.name"
                :label="table.name"
              >
                <option
                  v-for="column in table.columns"
                  :key="`${table.name}.${column.name}`"
                  :value="`${table.name}.${column.name}`"
                >
                  {{ column.name }} ({{ column.data_type }})
                  <span v-if="column.is_primary_key">🔑</span>
                  <span v-if="column.is_foreign_key">🔗</span>
                </option>
              </optgroup>
            </select>
          </div>

          <!-- Arrow -->
          <div class="lg:col-span-1 flex justify-center items-center">
            <ArrowRightIcon class="w-5 h-5 text-gray-400" />
          </div>

          <!-- Target Field (3 columns) -->
          <div class="lg:col-span-3">
            <label class="block text-sm font-medium text-gray-700 mb-1">Target Field</label>
            <select
              v-model="field.target"
              class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm"
            >
              <option value="">Select target field...</option>
              <option
                v-for="targetField in availableTargetFields"
                :key="targetField.name"
                :value="targetField.name"
                :title="targetField.description"
              >
                {{ targetField.name }}
                <span v-if="targetField.required" class="text-red-500">*</span>
              </option>
            </select>
          </div>

          <!-- Transformation (3 columns) -->
          <div class="lg:col-span-3">
            <label class="block text-sm font-medium text-gray-700 mb-1">Transformation</label>
            <div class="flex">
              <select
                v-model="field.transformationType"
                @change="onTransformationTypeChange(field)"
                class="flex-1 border-gray-300 rounded-l-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm"
              >
                <option value="">No transformation</option>
                <option value="custom">Custom SQL</option>
                <option value="concat">Concatenate</option>
                <option value="case">Conditional (CASE)</option>
                <option value="date">Date Format</option>
                <option value="number">Number Format</option>
                <option value="text">Text Transform</option>
              </select>
              <button
                v-if="field.transformationType"
                @click="showTransformationHelper(field, index)"
                class="px-2 py-2 border-l-0 border-gray-300 bg-gray-50 rounded-r-md text-gray-600 hover:bg-gray-100"
              >
                <CogIcon class="w-4 h-4" />
              </button>
            </div>
          </div>

          <!-- Actions (1 column) -->
          <div class="lg:col-span-1 flex justify-end">
            <button
              @click="removeField(index)"
              class="inline-flex items-center p-2 border border-transparent rounded-md text-red-600 hover:bg-red-50"
            >
              <TrashIcon class="w-4 h-4" />
            </button>
          </div>
        </div>

        <!-- Custom Transformation Input -->
        <div v-if="field.transformationType && field.transformationType !== 'custom'" class="mt-3">
          <label class="block text-sm font-medium text-gray-700 mb-1">Transformation Parameters</label>
          <input
            v-model="field.transformation"
            type="text"
            :placeholder="getTransformationPlaceholder(field.transformationType)"
            class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm"
          />
        </div>

        <div v-else-if="field.transformationType === 'custom'" class="mt-3">
          <label class="block text-sm font-medium text-gray-700 mb-1">Custom SQL Expression</label>
          <textarea
            v-model="field.transformation"
            rows="2"
            placeholder="CASE WHEN condition THEN value ELSE other_value END"
            class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm"
          />
        </div>

        <!-- Field Preview -->
        <div v-if="field.source || field.transformation" class="mt-3 p-3 bg-gray-50 rounded-md">
          <p class="text-xs font-medium text-gray-700 mb-1">SQL Preview:</p>
          <code class="text-xs text-gray-600">
            {{ formatFieldSQL(field) }}
            {{ field.target ? ` as ${field.target}` : '' }}
          </code>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else class="text-center py-8">
      <DocumentTextIcon class="mx-auto h-12 w-12 text-gray-400" />
      <h3 class="mt-2 text-sm font-medium text-gray-900">No fields mapped</h3>
      <p class="mt-1 text-sm text-gray-500">
        Select a target type and add field mappings to define your import structure.
      </p>
    </div>

    <!-- Required Fields Warning -->
    <div v-if="missingRequiredFields.length > 0" class="mt-6 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
      <div class="flex">
        <ExclamationTriangleIcon class="w-5 h-5 text-yellow-400" />
        <div class="ml-3">
          <h3 class="text-sm font-medium text-yellow-800">Missing Required Fields</h3>
          <div class="mt-2 text-sm text-yellow-700">
            <p>The following required fields are not mapped:</p>
            <ul class="list-disc list-inside mt-1">
              <li v-for="field in missingRequiredFields" :key="field">{{ field }}</li>
            </ul>
          </div>
        </div>
      </div>
    </div>

    <!-- Complete Query Preview -->
    <div v-if="fields.length > 0" class="mt-6">
      <h4 class="text-sm font-medium text-gray-900 mb-2">Complete SELECT Statement</h4>
      <div class="p-4 bg-gray-50 rounded-md">
        <pre class="text-xs text-gray-700 whitespace-pre-wrap">{{ completeQueryPreview }}</pre>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import {
  PlusIcon,
  TrashIcon,
  BoltIcon,
  ArrowRightIcon,
  CogIcon,
  QuestionMarkCircleIcon,
  DocumentTextIcon,
  ExclamationTriangleIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
  profileId: {
    type: String,
    default: null
  },
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
  targetType: {
    type: String,
    default: 'customer_users'
  },
  modelValue: {
    type: Array,
    default: () => []
  }
})

const emit = defineEmits(['update:modelValue', 'fields-changed', 'target-type-changed'])

// State
const fields = ref([...props.modelValue])
const selectedTargetType = ref(props.targetType || '')

// Target field definitions for different import types
const targetTypeFields = {
  customer_users: [
    { name: 'external_id', required: true, description: 'Unique identifier from source system' },
    { name: 'name', required: true, description: 'Full name or combined first/last name' },
    { name: 'email', required: true, description: 'Primary email address' },
    { name: 'first_name', required: false, description: 'First name' },
    { name: 'last_name', required: false, description: 'Last name' },
    { name: 'company', required: false, description: 'Company name' },
    { name: 'phone', required: false, description: 'Phone number' },
    { name: 'timezone', required: false, description: 'Timezone preference' },
    { name: 'country', required: false, description: 'Country' },
    { name: 'state', required: false, description: 'State/Province' },
    { name: 'city', required: false, description: 'City' },
    { name: 'address', required: false, description: 'Street address' },
    { name: 'zip', required: false, description: 'Postal code' }
  ],
  tickets: [
    { name: 'external_id', required: true, description: 'Unique identifier from source system' },
    { name: 'title', required: true, description: 'Ticket title/subject' },
    { name: 'description', required: true, description: 'Ticket description/body' },
    { name: 'status', required: false, description: 'Ticket status (open, pending, closed)' },
    { name: 'priority', required: false, description: 'Priority level' },
    { name: 'category', required: false, description: 'Ticket category' },
    { name: 'customer_external_id', required: false, description: 'Reference to customer' },
    { name: 'assigned_user_external_id', required: false, description: 'Reference to assigned agent' },
    { name: 'ticket_number', required: false, description: 'Display ticket number' }
  ],
  time_entries: [
    { name: 'external_id', required: true, description: 'Unique identifier from source system' },
    { name: 'description', required: true, description: 'Time entry description' },
    { name: 'duration', required: true, description: 'Duration in minutes' },
    { name: 'started_at', required: true, description: 'Start date/time' },
    { name: 'user_external_id', required: false, description: 'Reference to user' },
    { name: 'ticket_external_id', required: false, description: 'Reference to ticket' },
    { name: 'billable', required: false, description: 'Whether billable (true/false)' },
    { name: 'rate_override', required: false, description: 'Custom billing rate' }
  ],
  agents: [
    { name: 'external_id', required: true, description: 'Unique identifier from source system' },
    { name: 'name', required: true, description: 'Full name' },
    { name: 'email', required: true, description: 'Email address' },
    { name: 'first_name', required: false, description: 'First name' },
    { name: 'last_name', required: false, description: 'Last name' },
    { name: 'role', required: false, description: 'User role' }
  ],
  accounts: [
    { name: 'external_id', required: true, description: 'Unique identifier from source system' },
    { name: 'name', required: true, description: 'Account name' },
    { name: 'description', required: false, description: 'Account description' },
    { name: 'domain', required: false, description: 'Primary domain' },
    { name: 'timezone', required: false, description: 'Account timezone' }
  ]
}

// Computed
const availableSourceFields = computed(() => {
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

const availableTargetFields = computed(() => {
  if (!selectedTargetType.value || !targetTypeFields[selectedTargetType.value]) {
    return []
  }
  return targetTypeFields[selectedTargetType.value]
})

const missingRequiredFields = computed(() => {
  if (!selectedTargetType.value) return []
  
  const requiredFields = availableTargetFields.value
    .filter(field => field.required)
    .map(field => field.name)
  
  const mappedFields = fields.value
    .filter(field => field.target)
    .map(field => field.target)
  
  return requiredFields.filter(field => !mappedFields.includes(field))
})

const completeQueryPreview = computed(() => {
  if (!props.baseTable || fields.value.length === 0) return ''
  
  const selectFields = fields.value
    .filter(field => field.source || field.transformation)
    .map(field => {
      const sql = formatFieldSQL(field)
      return field.target ? `${sql} as ${field.target}` : sql
    })
  
  if (selectFields.length === 0) return ''
  
  let query = `SELECT\n  ${selectFields.join(',\n  ')}\nFROM ${props.baseTable.name}`
  
  props.joins.forEach(join => {
    if (join.table && join.leftColumn && join.rightColumn) {
      query += `\n${join.type} JOIN ${join.table} ON ${join.leftColumn} = ${join.table}.${join.rightColumn}`
      if (join.condition) {
        query += ` AND ${join.condition}`
      }
    }
  })
  
  return query
})

// Methods
const addCustomField = () => {
  fields.value.push({
    source: '',
    target: '',
    transformation: '',
    transformationType: '',
    isCustom: false
  })
  updateModelValue()
}

const removeField = (index) => {
  fields.value.splice(index, 1)
  updateModelValue()
}

const updateModelValue = () => {
  emit('update:modelValue', fields.value)
  emit('fields-changed', fields.value)
}

const onTargetTypeChange = () => {
  // Clear existing fields when target type changes
  fields.value = []
  updateModelValue()
  emit('target-type-changed', selectedTargetType.value)
}

const onTransformationTypeChange = (field) => {
  // Reset transformation when type changes
  field.transformation = ''
}

const autoMapFields = () => {
  if (!selectedTargetType.value || !props.baseTable?.columns) return
  
  const autoMappedFields = []
  const targetFields = availableTargetFields.value
  
  targetFields.forEach(targetField => {
    // Try to find a matching source field
    let bestMatch = null
    let bestScore = 0
    
    availableSourceFields.value.forEach(table => {
      table.columns.forEach(column => {
        const score = calculateFieldMatchScore(column.name, targetField.name)
        if (score > bestScore) {
          bestScore = score
          bestMatch = `${table.name}.${column.name}`
        }
      })
    })
    
    if (bestMatch && bestScore > 0.3) { // Only auto-map if confidence > 30%
      autoMappedFields.push({
        source: bestMatch,
        target: targetField.name,
        transformation: '',
        transformationType: '',
        isCustom: false
      })
    }
  })
  
  fields.value = autoMappedFields
  updateModelValue()
}

const calculateFieldMatchScore = (sourceName, targetName) => {
  let score = 0
  
  // Exact match
  if (sourceName.toLowerCase() === targetName.toLowerCase()) {
    score += 1.0
  }
  
  // Contains match
  else if (sourceName.toLowerCase().includes(targetName.toLowerCase()) ||
           targetName.toLowerCase().includes(sourceName.toLowerCase())) {
    score += 0.6
  }
  
  // Common patterns
  const patterns = [
    { source: 'first_name', target: 'first_name', score: 0.9 },
    { source: 'fname', target: 'first_name', score: 0.8 },
    { source: 'email', target: 'email', score: 0.9 },
    { source: 'mail', target: 'email', score: 0.7 },
    { source: 'subject', target: 'title', score: 0.8 },
    { source: 'body', target: 'description', score: 0.8 },
    { source: 'content', target: 'description', score: 0.7 }
  ]
  
  patterns.forEach(pattern => {
    if (sourceName.toLowerCase().includes(pattern.source) && 
        targetName.toLowerCase().includes(pattern.target)) {
      score = Math.max(score, pattern.score)
    }
  })
  
  return score
}

const formatFieldSQL = (field) => {
  if (field.transformation && field.transformationType === 'custom') {
    return field.transformation
  } else if (field.transformation && field.transformationType) {
    return applyTransformation(field.source, field.transformationType, field.transformation)
  } else {
    return field.source
  }
}

const applyTransformation = (source, type, params) => {
  if (!source) return ''
  
  switch (type) {
    case 'concat':
      return `CONCAT(${source}, '${params}')`
    case 'case':
      return `CASE WHEN ${params} THEN ${source} ELSE NULL END`
    case 'date':
      return `TO_CHAR(${source}, '${params || 'YYYY-MM-DD'}')`
    case 'number':
      return `ROUND(${source}${params ? `::numeric, ${params}` : ''})`
    case 'text':
      return `${params.toUpperCase()}(${source})`
    default:
      return source
  }
}

const getTransformationPlaceholder = (type) => {
  switch (type) {
    case 'concat':
      return 'Text to append, e.g., " - Imported"'
    case 'case':
      return 'Condition, e.g., status = 1'
    case 'date':
      return 'Date format, e.g., YYYY-MM-DD HH24:MI:SS'
    case 'number':
      return 'Decimal places, e.g., 2'
    case 'text':
      return 'Function: UPPER, LOWER, TRIM'
    default:
      return ''
  }
}

const showSourceFieldHelper = (field, index) => {
  // This could open a modal with SQL expression examples
  console.log('Show source field helper for field', index)
}

const showTransformationHelper = (field, index) => {
  // This could open a modal with transformation examples
  console.log('Show transformation helper for field', index)
}

// Watchers
watch(() => props.modelValue, (newValue) => {
  fields.value = [...newValue]
}, { deep: true })

watch(() => props.targetType, (newValue) => {
  selectedTargetType.value = newValue || ''
}, { immediate: true })

watch(fields, () => {
  updateModelValue()
}, { deep: true })
</script>