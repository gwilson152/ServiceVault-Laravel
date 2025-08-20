<template>
  <StackedDialog 
    :show="show" 
    @close="$emit('close')"
    :title="`Field Mapping Configuration - ${profile?.name}`"
    max-width="6xl"
  >
    <div class="space-y-6">
      <!-- Field Mapper Header -->
      <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
        <div class="flex">
          <div class="flex-shrink-0">
            <InformationCircleIcon class="h-5 w-5 text-blue-400" />
          </div>
          <div class="ml-3">
            <h3 class="text-sm font-medium text-blue-800">Field Mapping Configuration</h3>
            <div class="mt-2 text-sm text-blue-700">
              <p>Configure how fields from the source database map to Service Vault fields. You can create direct mappings, combine fields, or apply transformations.</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Table Selection -->
      <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        <div class="lg:col-span-1">
          <h3 class="text-lg font-medium text-gray-900 mb-4">Data Types</h3>
          <div class="space-y-2">
            <button 
              v-for="tableConfig in availableTables" 
              :key="tableConfig.key"
              @click="selectedTable = tableConfig.key"
              class="w-full text-left px-4 py-3 rounded-lg border transition-colors"
              :class="selectedTable === tableConfig.key 
                ? 'border-indigo-500 bg-indigo-50 text-indigo-900' 
                : 'border-gray-200 hover:border-gray-300 hover:bg-gray-50'"
            >
              <div class="flex items-center justify-between">
                <div>
                  <div class="font-medium text-sm">{{ tableConfig.title }}</div>
                  <div class="text-xs text-gray-500">{{ tableConfig.description }}</div>
                </div>
                <div class="flex items-center">
                  <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium"
                        :class="getMappingStatusClass(tableConfig.key)">
                    {{ getMappingStatusText(tableConfig.key) }}
                  </span>
                </div>
              </div>
            </button>
          </div>
        </div>

        <!-- Field Mapping Configuration -->
        <div class="lg:col-span-3">
          <div v-if="selectedTable && currentTableConfig" class="space-y-6">
            <div class="flex items-center justify-between">
              <div>
                <h3 class="text-lg font-medium text-gray-900">{{ currentTableConfig.title }}</h3>
                <p class="text-sm text-gray-600">{{ currentTableConfig.description }}</p>
              </div>
              <div class="flex items-center space-x-3">
                <button
                  @click="loadDefaultMapping"
                  class="text-sm text-indigo-600 hover:text-indigo-800 font-medium"
                >
                  Load Defaults
                </button>
                <button
                  @click="clearMappings"
                  class="text-sm text-gray-600 hover:text-gray-800 font-medium"
                >
                  Clear All
                </button>
              </div>
            </div>

            <!-- Source Fields Preview -->
            <div class="bg-gray-50 rounded-lg p-4">
              <h4 class="text-sm font-medium text-gray-900 mb-3">Source Fields Available</h4>
              <div v-if="sourceFieldsLoading" class="text-center py-4">
                <div class="inline-block animate-spin rounded-full h-6 w-6 border-b-2 border-indigo-600"></div>
                <p class="mt-2 text-sm text-gray-600">Loading source fields...</p>
              </div>
              <div v-else-if="sourceFields?.length > 0" class="flex flex-wrap gap-2">
                <span 
                  v-for="field in sourceFields" 
                  :key="field.name"
                  class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-white border border-gray-300"
                  :title="`${field.name} (${field.type})`"
                >
                  {{ field.name }}
                  <span class="ml-1 text-gray-400">{{ field.type }}</span>
                </span>
              </div>
              <div v-else class="text-sm text-gray-500">
                No source fields available. Try testing the database connection first.
              </div>
            </div>

            <!-- Field Mappings -->
            <div class="space-y-4">
              <h4 class="text-sm font-medium text-gray-900">Field Mappings</h4>
              
              <div v-if="currentMappings.length === 0" class="text-center py-8 border border-gray-200 rounded-lg">
                <DocumentPlusIcon class="mx-auto h-12 w-12 text-gray-400" />
                <h3 class="mt-2 text-sm font-medium text-gray-900">No field mappings configured</h3>
                <p class="mt-1 text-sm text-gray-500">Add your first field mapping to get started.</p>
                <div class="mt-6">
                  <button
                    @click="addMapping"
                    class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700"
                  >
                    <PlusIcon class="mr-2 h-4 w-4" />
                    Add Field Mapping
                  </button>
                </div>
              </div>

              <div v-else class="space-y-3">
                <div 
                  v-for="(mapping, index) in currentMappings" 
                  :key="index"
                  class="bg-white border border-gray-200 rounded-lg p-4"
                >
                  <div class="grid grid-cols-12 gap-4 items-start">
                    <!-- Destination Field -->
                    <div class="col-span-3">
                      <label class="block text-xs font-medium text-gray-700 mb-1">Service Vault Field</label>
                      <select
                        v-model="mapping.destination_field"
                        class="block w-full text-sm border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
                      >
                        <option value="">Select destination field...</option>
                        <option 
                          v-for="field in getDestinationFields(selectedTable)" 
                          :key="field.name"
                          :value="field.name"
                        >
                          {{ field.label }} ({{ field.name }})
                        </option>
                      </select>
                    </div>

                    <!-- Mapping Type -->
                    <div class="col-span-2">
                      <label class="block text-xs font-medium text-gray-700 mb-1">Mapping Type</label>
                      <select
                        v-model="mapping.type"
                        @change="onMappingTypeChange(mapping, index)"
                        class="block w-full text-sm border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
                      >
                        <option value="direct_mapping">Direct</option>
                        <option value="combine_fields">Combine Fields</option>
                        <option value="static_value">Static Value</option>
                        <option value="integer_to_uuid">Integer → UUID</option>
                        <option value="transform_function">Transform</option>
                      </select>
                    </div>

                    <!-- Source Configuration -->
                    <div class="col-span-6">
                      <label class="block text-xs font-medium text-gray-700 mb-1">Source Configuration</label>
                      
                      <!-- Direct Mapping -->
                      <div v-if="mapping.type === 'direct_mapping'" class="space-y-2">
                        <select
                          v-model="mapping.source_field"
                          class="block w-full text-sm border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
                        >
                          <option value="">Select source field...</option>
                          <option 
                            v-for="field in sourceFields" 
                            :key="field.name"
                            :value="field.name"
                          >
                            {{ field.name }} ({{ field.type }})
                          </option>
                        </select>
                      </div>

                      <!-- Combine Fields -->
                      <div v-else-if="mapping.type === 'combine_fields'" class="space-y-2">
                        <div class="grid grid-cols-2 gap-2">
                          <select
                            v-model="mapping.fields"
                            multiple
                            class="block w-full text-sm border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
                            size="3"
                          >
                            <option 
                              v-for="field in sourceFields" 
                              :key="field.name"
                              :value="field.name"
                            >
                              {{ field.name }}
                            </option>
                          </select>
                          <div>
                            <input
                              v-model="mapping.separator"
                              placeholder="Separator (e.g. ' ')"
                              class="block w-full text-sm border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
                            />
                            <p class="text-xs text-gray-500 mt-1">Fields will be joined with this separator</p>
                          </div>
                        </div>
                      </div>

                      <!-- Static Value -->
                      <div v-else-if="mapping.type === 'static_value'" class="space-y-2">
                        <input
                          v-model="mapping.static_value"
                          placeholder="Enter static value..."
                          class="block w-full text-sm border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
                        />
                        <p class="text-xs text-gray-500">This value will be used for all records</p>
                      </div>

                      <!-- Integer to UUID -->
                      <div v-else-if="mapping.type === 'integer_to_uuid'" class="space-y-2">
                        <select
                          v-model="mapping.source_field"
                          class="block w-full text-sm border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
                        >
                          <option value="">Select integer field...</option>
                          <option 
                            v-for="field in sourceFields.filter(f => f.type.includes('integer'))" 
                            :key="field.name"
                            :value="field.name"
                          >
                            {{ field.name }} ({{ field.type }})
                          </option>
                        </select>
                        <input
                          v-model="mapping.prefix"
                          placeholder="UUID prefix (e.g. 'freescout_user_')"
                          class="block w-full text-sm border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
                        />
                      </div>

                      <!-- Transform Function -->
                      <div v-else-if="mapping.type === 'transform_function'" class="space-y-2">
                        <select
                          v-model="mapping.source_field"
                          class="block w-full text-sm border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
                        >
                          <option value="">Select source field...</option>
                          <option 
                            v-for="field in sourceFields" 
                            :key="field.name"
                            :value="field.name"
                          >
                            {{ field.name }} ({{ field.type }})
                          </option>
                        </select>
                        <select
                          v-model="mapping.transform_function"
                          class="block w-full text-sm border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
                        >
                          <option value="">Select transformation...</option>
                          <option value="lowercase">Lowercase</option>
                          <option value="uppercase">Uppercase</option>
                          <option value="trim">Trim Whitespace</option>
                          <option value="date_format">Format Date</option>
                          <option value="boolean_convert">Convert to Boolean</option>
                        </select>
                      </div>
                    </div>

                    <!-- Actions -->
                    <div class="col-span-1 flex justify-end">
                      <button
                        @click="removeMapping(index)"
                        class="text-red-600 hover:text-red-800"
                        title="Remove mapping"
                      >
                        <TrashIcon class="h-4 w-4" />
                      </button>
                    </div>
                  </div>
                  
                  <!-- Mapping Preview -->
                  <div v-if="mapping.destination_field && hasValidSource(mapping)" class="mt-3 pt-3 border-t border-gray-100">
                    <div class="flex items-center text-xs text-gray-600">
                      <span class="font-medium">Preview:</span>
                      <ArrowRightIcon class="mx-2 h-3 w-3" />
                      <code class="bg-gray-100 px-2 py-1 rounded">{{ getMappingPreview(mapping) }}</code>
                    </div>
                  </div>
                </div>

                <!-- Add Mapping Button -->
                <button
                  @click="addMapping"
                  class="w-full flex items-center justify-center px-4 py-3 border border-dashed border-gray-300 rounded-lg text-sm font-medium text-gray-600 hover:text-gray-900 hover:border-gray-400"
                >
                  <PlusIcon class="mr-2 h-4 w-4" />
                  Add Another Field Mapping
                </button>
              </div>
            </div>
          </div>

          <!-- No Table Selected -->
          <div v-else class="text-center py-12">
            <CogIcon class="mx-auto h-12 w-12 text-gray-400" />
            <h3 class="mt-2 text-sm font-medium text-gray-900">Select a data type</h3>
            <p class="mt-1 text-sm text-gray-500">Choose a data type from the left to configure its field mappings.</p>
          </div>
        </div>
      </div>

      <!-- Actions -->
      <div class="flex items-center justify-between pt-4 border-t border-gray-200">
        <div class="flex items-center space-x-3">
          <button
            @click="testMappings"
            :disabled="!hasValidMappings"
            class="text-sm font-medium"
            :class="hasValidMappings 
              ? 'text-indigo-600 hover:text-indigo-800' 
              : 'text-gray-400 cursor-not-allowed'"
          >
            Test Mappings
          </button>
          <button
            @click="loadSampleMappings"
            class="text-sm text-gray-600 hover:text-gray-800 font-medium"
          >
            Load Sample Config
          </button>
        </div>
        
        <div class="flex items-center space-x-3">
          <button
            type="button"
            @click="$emit('close')"
            class="px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
          >
            Cancel
          </button>
          <button
            type="button"
            @click="saveMappings"
            :disabled="!hasValidMappings"
            class="px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
            :class="hasValidMappings
              ? 'text-white bg-indigo-600 hover:bg-indigo-700'
              : 'text-gray-400 bg-gray-300 cursor-not-allowed'"
          >
            Save Field Mappings
          </button>
        </div>
      </div>
    </div>
  </StackedDialog>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import StackedDialog from '@/Components/StackedDialog.vue'
import {
  InformationCircleIcon,
  DocumentPlusIcon,
  PlusIcon,
  TrashIcon,
  ArrowRightIcon,
  CogIcon,
} from '@heroicons/vue/24/outline'
import { useImportQueries } from '@/Composables/queries/useImportQueries.js'

const props = defineProps({
  show: Boolean,
  profile: {
    type: Object,
    default: null
  }
})

const emit = defineEmits(['close', 'mappings-saved'])

// Available tables for field mapping
const availableTables = ref([
  {
    key: 'users',
    title: 'Staff Users',
    description: 'FreeScout staff → Service Vault users',
    source_table: 'users',
    destination_table: 'users'
  },
  {
    key: 'customers', 
    title: 'Customer Accounts',
    description: 'FreeScout customers → Service Vault accounts + users',
    source_table: 'customers',
    destination_table: 'accounts'
  },
  {
    key: 'conversations',
    title: 'Tickets',
    description: 'FreeScout conversations → Service Vault tickets',
    source_table: 'conversations',
    destination_table: 'tickets'
  },
  {
    key: 'threads',
    title: 'Comments', 
    description: 'FreeScout threads → Service Vault comments',
    source_table: 'threads',
    destination_table: 'ticket_comments'
  }
])

// State
const selectedTable = ref('users')
const fieldMappings = ref({})
const sourceFields = ref([])
const sourceFieldsLoading = ref(false)

// Composables
const { getSchema, getFieldMappings, saveFieldMappings } = useImportQueries()

// Computed
const currentTableConfig = computed(() => {
  return availableTables.value.find(t => t.key === selectedTable.value)
})

const currentMappings = computed({
  get() {
    return fieldMappings.value[selectedTable.value] || []
  },
  set(value) {
    fieldMappings.value[selectedTable.value] = value
  }
})

const hasValidMappings = computed(() => {
  return Object.values(fieldMappings.value).some(mappings => 
    mappings.length > 0 && mappings.some(m => m.destination_field && hasValidSource(m))
  )
})

// Watch for table changes
watch(selectedTable, async (newTable) => {
  if (newTable && props.profile) {
    await loadSourceFields()
  }
})

// Watch for modal open/close
watch(() => props.show, (show) => {
  if (show && props.profile) {
    loadExistingMappings()
    loadSourceFields()
  } else {
    resetState()
  }
})

// Methods
const resetState = () => {
  selectedTable.value = 'users'
  fieldMappings.value = {}
  sourceFields.value = []
}

const loadSourceFields = async () => {
  if (!props.profile || !currentTableConfig.value) return
  
  try {
    sourceFieldsLoading.value = true
    const schema = await getSchema(props.profile.id)
    
    const table = schema.schema.tables.find(t => 
      t.name === currentTableConfig.value.source_table
    )
    
    if (table) {
      // Transform database schema format to component format
      sourceFields.value = (table.columns || []).map(column => ({
        name: column.column_name || column.name,
        type: column.data_type || column.type,
        nullable: column.is_nullable === 'YES',
        default: column.column_default
      }))
    }
  } catch (error) {
    console.error('Failed to load source fields:', error)
    sourceFields.value = []
  } finally {
    sourceFieldsLoading.value = false
  }
}

const loadExistingMappings = async () => {
  if (!props.profile) return
  
  try {
    const mappings = await getFieldMappings(props.profile.id)
    
    // Transform API response to component format
    const transformedMappings = {
      users: [],
      customers: [],
      conversations: [],
      threads: []
    }
    
    // Convert from database format to component format
    if (mappings && Array.isArray(mappings)) {
      mappings.forEach(mapping => {
        const tableKey = getTableKeyFromMapping(mapping.source_table, mapping.destination_table)
        if (tableKey && transformedMappings[tableKey]) {
          const componentMappings = convertDbMappingToComponent(mapping.field_mappings)
          transformedMappings[tableKey] = componentMappings
        }
      })
    }
    
    fieldMappings.value = transformedMappings
  } catch (error) {
    console.error('Failed to load existing field mappings:', error)
    // Initialize with empty mappings on error
    fieldMappings.value = {
      users: [],
      customers: [],
      conversations: [],
      threads: []
    }
  }
}

const addMapping = () => {
  const newMapping = {
    destination_field: '',
    type: 'direct_mapping',
    source_field: '',
    // Fields for combine_fields type
    fields: [],
    separator: ' ',
    // Field for static_value type
    static_value: '',
    // Fields for integer_to_uuid type
    prefix: '',
    // Field for transform_function type
    transform_function: ''
  }
  
  currentMappings.value = [...currentMappings.value, newMapping]
}

const removeMapping = (index) => {
  const mappings = [...currentMappings.value]
  mappings.splice(index, 1)
  currentMappings.value = mappings
}

const onMappingTypeChange = (mapping, index) => {
  // Reset type-specific fields when mapping type changes
  mapping.source_field = ''
  mapping.fields = []
  mapping.static_value = ''
  mapping.prefix = ''
  mapping.transform_function = ''
}

const hasValidSource = (mapping) => {
  switch (mapping.type) {
    case 'direct_mapping':
    case 'integer_to_uuid':
    case 'transform_function':
      return Boolean(mapping.source_field)
    case 'combine_fields':
      return mapping.fields && mapping.fields.length > 0
    case 'static_value':
      return Boolean(mapping.static_value)
    default:
      return false
  }
}

const getMappingPreview = (mapping) => {
  switch (mapping.type) {
    case 'direct_mapping':
      return `${mapping.source_field} → ${mapping.destination_field}`
    case 'combine_fields':
      return `${mapping.fields.join(` ${mapping.separator || ' '} `)} → ${mapping.destination_field}`
    case 'static_value':
      return `"${mapping.static_value}" → ${mapping.destination_field}`
    case 'integer_to_uuid':
      return `UUID(${mapping.prefix}${mapping.source_field}) → ${mapping.destination_field}`
    case 'transform_function':
      return `${mapping.transform_function}(${mapping.source_field}) → ${mapping.destination_field}`
    default:
      return 'Invalid mapping'
  }
}

const getMappingStatusClass = (tableKey) => {
  const mappings = fieldMappings.value[tableKey] || []
  const validMappings = mappings.filter(m => m.destination_field && hasValidSource(m))
  
  if (validMappings.length === 0) {
    return 'bg-gray-100 text-gray-800'
  } else if (validMappings.length < 3) {
    return 'bg-yellow-100 text-yellow-800'
  } else {
    return 'bg-green-100 text-green-800'
  }
}

const getMappingStatusText = (tableKey) => {
  const mappings = fieldMappings.value[tableKey] || []
  const validMappings = mappings.filter(m => m.destination_field && hasValidSource(m))
  
  if (validMappings.length === 0) {
    return 'Not Configured'
  } else {
    return `${validMappings.length} Mapped`
  }
}

const getDestinationFields = (tableKey) => {
  // Define destination fields for each table type
  const fieldDefinitions = {
    users: [
      { name: 'name', label: 'Name' },
      { name: 'email', label: 'Email' },
      { name: 'user_type', label: 'User Type' },
      { name: 'is_active', label: 'Active Status' },
      { name: 'password', label: 'Password' }
    ],
    customers: [
      { name: 'name', label: 'Account Name' },
      { name: 'description', label: 'Description' },
      { name: 'is_active', label: 'Active Status' }
    ],
    conversations: [
      { name: 'title', label: 'Ticket Title' },
      { name: 'description', label: 'Description' },
      { name: 'status', label: 'Status' },
      { name: 'priority', label: 'Priority' },
      { name: 'category', label: 'Category' },
      { name: 'ticket_number', label: 'Ticket Number' },
      { name: 'due_date', label: 'Due Date' },
      { name: 'tags', label: 'Tags' }
    ],
    threads: [
      { name: 'comment', label: 'Comment Text' },
      { name: 'is_internal', label: 'Internal Comment' }
    ]
  }
  
  return fieldDefinitions[tableKey] || []
}

const loadDefaultMapping = () => {
  // Load sensible default mappings for the current table
  const defaults = {
    users: [
      {
        destination_field: 'name',
        type: 'combine_fields',
        fields: ['first_name', 'last_name'],
        separator: ' '
      },
      {
        destination_field: 'email',
        type: 'direct_mapping',
        source_field: 'email'
      }
    ],
    customers: [
      {
        destination_field: 'name',
        type: 'combine_fields',
        fields: ['first_name', 'last_name'],
        separator: ' '
      }
    ],
    conversations: [
      {
        destination_field: 'title',
        type: 'direct_mapping',
        source_field: 'subject'
      },
      {
        destination_field: 'ticket_number',
        type: 'direct_mapping',
        source_field: 'number'
      }
    ],
    threads: [
      {
        destination_field: 'comment',
        type: 'direct_mapping',
        source_field: 'body'
      }
    ]
  }
  
  currentMappings.value = defaults[selectedTable.value] || []
}

const clearMappings = () => {
  currentMappings.value = []
}

const loadSampleMappings = () => {
  // Load comprehensive sample mappings for demonstration
  fieldMappings.value = {
    users: [
      {
        destination_field: 'name',
        type: 'combine_fields',
        fields: ['first_name', 'last_name'],
        separator: ' '
      },
      {
        destination_field: 'email',
        type: 'direct_mapping',
        source_field: 'email'
      },
      {
        destination_field: 'user_type',
        type: 'static_value',
        static_value: 'agent'
      }
    ],
    customers: [
      {
        destination_field: 'name',
        type: 'direct_mapping',
        source_field: 'company'
      }
    ],
    conversations: [
      {
        destination_field: 'title',
        type: 'direct_mapping',
        source_field: 'subject'
      },
      {
        destination_field: 'ticket_number',
        type: 'integer_to_uuid',
        source_field: 'number',
        prefix: 'FS'
      }
    ],
    threads: [
      {
        destination_field: 'comment',
        type: 'direct_mapping',
        source_field: 'body'
      }
    ]
  }
}

const testMappings = async () => {
  // TODO: Implement mapping test functionality
  alert('Mapping test functionality will be implemented to validate field mappings against sample data.')
}

const saveMappings = async () => {
  try {
    // Convert component format to database format
    const dbMappings = convertComponentMappingsToDb(fieldMappings.value)
    
    await saveFieldMappings(props.profile.id, dbMappings)
    
    emit('mappings-saved', {
      profile: props.profile,
      mappings: fieldMappings.value
    })
    
    emit('close')
  } catch (error) {
    console.error('Failed to save field mappings:', error)
    alert('Failed to save field mappings. Please try again.')
  }
}

// Helper functions for data transformation
const getTableKeyFromMapping = (sourceTable, destinationTable) => {
  const mapping = {
    'users': 'users',
    'customers': 'customers',
    'conversations': 'conversations',
    'threads': 'threads'
  }
  return mapping[sourceTable] || null
}

const convertDbMappingToComponent = (dbFieldMappings) => {
  const componentMappings = []
  
  if (!dbFieldMappings || typeof dbFieldMappings !== 'object') {
    return componentMappings
  }
  
  Object.entries(dbFieldMappings).forEach(([destField, mapping]) => {
    const componentMapping = {
      destination_field: destField,
      type: mapping.type || 'direct_mapping',
      source_field: mapping.source_field || '',
      fields: mapping.fields || [],
      separator: mapping.separator || ' ',
      static_value: mapping.static_value || '',
      prefix: mapping.prefix || '',
      transform_function: mapping.transform_function || ''
    }
    componentMappings.push(componentMapping)
  })
  
  return componentMappings
}

const convertComponentMappingsToDb = (componentMappings) => {
  const dbMappings = []
  
  Object.entries(componentMappings).forEach(([tableKey, mappings]) => {
    if (!mappings || mappings.length === 0) return
    
    const tableConfig = availableTables.value.find(t => t.key === tableKey)
    if (!tableConfig) return
    
    const fieldMappings = {}
    
    mappings.forEach(mapping => {
      if (!mapping.destination_field || !hasValidSource(mapping)) return
      
      const dbMapping = {
        type: mapping.type,
        source_field: mapping.source_field,
        fields: mapping.fields,
        separator: mapping.separator,
        static_value: mapping.static_value,
        prefix: mapping.prefix,
        transform_function: mapping.transform_function
      }
      
      // Clean up unused fields based on type
      switch (mapping.type) {
        case 'direct_mapping':
        case 'integer_to_uuid':
        case 'transform_function':
          delete dbMapping.fields
          delete dbMapping.separator
          delete dbMapping.static_value
          if (mapping.type !== 'integer_to_uuid') delete dbMapping.prefix
          if (mapping.type !== 'transform_function') delete dbMapping.transform_function
          break
        case 'combine_fields':
          delete dbMapping.source_field
          delete dbMapping.static_value
          delete dbMapping.prefix
          delete dbMapping.transform_function
          break
        case 'static_value':
          delete dbMapping.source_field
          delete dbMapping.fields
          delete dbMapping.separator
          delete dbMapping.prefix
          delete dbMapping.transform_function
          break
      }
      
      fieldMappings[mapping.destination_field] = dbMapping
    })
    
    if (Object.keys(fieldMappings).length > 0) {
      dbMappings.push({
        source_table: tableConfig.source_table,
        destination_table: tableConfig.destination_table,
        field_mappings: fieldMappings,
        import_order: getImportOrder(tableKey)
      })
    }
  })
  
  return dbMappings
}

const getImportOrder = (tableKey) => {
  const orderMap = {
    users: 1,
    customers: 2,
    conversations: 3,
    threads: 4
  }
  return orderMap[tableKey] || 0
}
</script>