<template>
  <div class="space-y-6">
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

          <!-- Field Mappings List -->
          <div class="space-y-4">
            <div class="flex items-center justify-between">
              <h4 class="text-sm font-medium text-gray-900">Field Mappings</h4>
              <button
                @click="addMapping"
                class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-indigo-700 bg-indigo-100 hover:bg-indigo-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
              >
                Add Field Mapping
              </button>
            </div>

            <div v-if="currentMappings.length === 0" class="text-center py-8 bg-gray-50 rounded-lg">
              <p class="text-sm text-gray-500">No field mappings configured.</p>
              <p class="text-xs text-gray-400 mt-1">Add mappings to customize how source fields map to Service Vault fields.</p>
            </div>

            <div v-else class="space-y-4">
              <div
                v-for="(mapping, index) in currentMappings"
                :key="index"
                class="bg-white border border-gray-200 rounded-lg p-4"
              >
                <div class="grid grid-cols-12 gap-4 items-end">
                  <!-- Destination Field -->
                  <div class="col-span-3">
                    <label class="block text-xs font-medium text-gray-700 mb-1">
                      Service Vault Field
                    </label>
                    <select
                      v-model="mapping.destination_field"
                      class="block w-full text-sm border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                    >
                      <option value="">Select field...</option>
                      <option
                        v-for="field in getDestinationFields(selectedTable)"
                        :key="field.name"
                        :value="field.name"
                      >
                        {{ field.label }}
                      </option>
                    </select>
                  </div>

                  <!-- Mapping Type -->
                  <div class="col-span-2">
                    <label class="block text-xs font-medium text-gray-700 mb-1">
                      Mapping Type
                    </label>
                    <select
                      v-model="mapping.type"
                      @change="onMappingTypeChange(mapping, index)"
                      class="block w-full text-sm border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                    >
                      <option value="direct_mapping">Direct</option>
                      <option value="combine_fields">Combine</option>
                      <option value="static_value">Static</option>
                      <option value="integer_to_uuid">UUID</option>
                      <option value="transform_function">Transform</option>
                    </select>
                  </div>

                  <!-- Dynamic Configuration Based on Type -->
                  <div class="col-span-6">
                    <!-- Direct Mapping -->
                    <div v-if="mapping.type === 'direct_mapping'">
                      <label class="block text-xs font-medium text-gray-700 mb-1">
                        Source Field
                      </label>
                      <select
                        v-model="mapping.source_field"
                        class="block w-full text-sm border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
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
                      <label class="block text-xs font-medium text-gray-700">
                        Source Fields & Separator
                      </label>
                      <div class="flex space-x-2">
                        <select
                          v-model="mapping.fields"
                          multiple
                          class="flex-1 text-sm border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
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
                        <input
                          v-model="mapping.separator"
                          placeholder="Sep"
                          class="w-12 text-sm border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                        />
                      </div>
                    </div>

                    <!-- Static Value -->
                    <div v-else-if="mapping.type === 'static_value'">
                      <label class="block text-xs font-medium text-gray-700 mb-1">
                        Static Value
                      </label>
                      <input
                        v-model="mapping.static_value"
                        type="text"
                        placeholder="Enter static value..."
                        class="block w-full text-sm border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                      />
                    </div>

                    <!-- Integer to UUID -->
                    <div v-else-if="mapping.type === 'integer_to_uuid'" class="space-y-2">
                      <label class="block text-xs font-medium text-gray-700">
                        Source Field & Prefix
                      </label>
                      <div class="flex space-x-2">
                        <select
                          v-model="mapping.source_field"
                          class="flex-1 text-sm border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                        >
                          <option value="">Select source field...</option>
                          <option
                            v-for="field in sourceFields"
                            :key="field.name"
                            :value="field.name"
                          >
                            {{ field.name }}
                          </option>
                        </select>
                        <input
                          v-model="mapping.prefix"
                          placeholder="Prefix"
                          class="w-20 text-sm border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                        />
                      </div>
                    </div>

                    <!-- Transform Function -->
                    <div v-else-if="mapping.type === 'transform_function'" class="space-y-2">
                      <label class="block text-xs font-medium text-gray-700">
                        Source Field & Transform
                      </label>
                      <div class="flex space-x-2">
                        <select
                          v-model="mapping.source_field"
                          class="flex-1 text-sm border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                        >
                          <option value="">Select source field...</option>
                          <option
                            v-for="field in sourceFields"
                            :key="field.name"
                            :value="field.name"
                          >
                            {{ field.name }}
                          </option>
                        </select>
                        <select
                          v-model="mapping.transform_function"
                          class="w-28 text-sm border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                        >
                          <option value="">Transform...</option>
                          <option value="lowercase">lowercase</option>
                          <option value="uppercase">uppercase</option>
                          <option value="trim">trim</option>
                          <option value="date_format">date_format</option>
                          <option value="boolean_convert">boolean</option>
                        </select>
                      </div>
                    </div>
                  </div>

                  <!-- Remove Button -->
                  <div class="col-span-1">
                    <button
                      @click="removeMapping(index)"
                      class="w-full h-8 flex items-center justify-center text-red-600 hover:text-red-800 hover:bg-red-50 rounded-md transition-colors"
                      title="Remove mapping"
                    >
                      <TrashIcon class="w-4 h-4" />
                    </button>
                  </div>
                </div>

                <!-- Mapping Preview -->
                <div v-if="mapping.destination_field && hasValidSource(mapping)" class="mt-3 pt-3 border-t border-gray-100">
                  <div class="text-xs text-gray-600">
                    <span class="font-medium">Preview:</span>
                    {{ getMappingPreview(mapping) }}
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { TrashIcon } from '@heroicons/vue/24/outline'
import { useImportQueries } from '@/Composables/queries/useImportQueries.js'

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
const emit = defineEmits(['update:modelValue', 'mappings-changed'])

// Composables
const { getSchema, getFieldMappings } = useImportQueries()

// Reactive state
const selectedTable = ref('customer_users')
const fieldMappings = ref({
  customer_users: [],
  conversations: [],
  customer_accounts: [],
  staff_users: [],
  threads: []
})
const sourceFields = ref([])
const sourceFieldsLoading = ref(false)

// Table configurations
const availableTables = ref([
  {
    key: 'customer_users',
    title: 'Customer Users',
    description: 'FreeScout customers → Service Vault customer users',
    source_table: 'customers',
    destination_table: 'users'
  },
  {
    key: 'conversations',
    title: 'Tickets',
    description: 'FreeScout conversations → Service Vault tickets',
    source_table: 'conversations',
    destination_table: 'tickets'
  },
  {
    key: 'customer_accounts', 
    title: 'Customer Accounts',
    description: 'FreeScout customers → Service Vault accounts',
    source_table: 'customers',
    destination_table: 'accounts'
  },
  {
    key: 'staff_users',
    title: 'Staff Users',
    description: 'FreeScout staff → Service Vault agent users',
    source_table: 'users',
    destination_table: 'users'
  },
  {
    key: 'threads',
    title: 'Comments', 
    description: 'FreeScout threads → Service Vault comments',
    source_table: 'threads',
    destination_table: 'ticket_comments'
  }
])

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

// Watch for changes and emit
watch(fieldMappings, (newMappings) => {
  emit('update:modelValue', newMappings)
  emit('mappings-changed', newMappings)
}, { deep: true })

// Watch for table changes
watch(selectedTable, async (newTable) => {
  if (newTable && props.profile) {
    await loadSourceFields()
  }
})

// Initialize
onMounted(() => {
  if (props.profile) {
    loadSourceFields()
    loadExistingMappings()
  }
})

// Initialize from model value
watch(() => props.modelValue, (newValue) => {
  if (newValue && Object.keys(newValue).length > 0) {
    fieldMappings.value = { ...newValue }
  }
}, { immediate: true })

// Methods
const loadSourceFields = async () => {
  if (!props.profile || !currentTableConfig.value) return
  
  try {
    sourceFieldsLoading.value = true
    const schema = await getSchema(props.profile.id)
    
    const table = schema.schema.tables.find(t => 
      t.name === currentTableConfig.value.source_table
    )
    
    if (table) {
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
    // Process existing mappings if any
    console.log('Existing mappings:', mappings)
  } catch (error) {
    console.error('Failed to load existing mappings:', error)
  }
}

const addMapping = () => {
  const newMapping = {
    destination_field: '',
    type: 'direct_mapping',
    source_field: '',
    fields: [],
    separator: ' ',
    static_value: '',
    prefix: '',
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
  const fieldDefinitions = {
    customer_users: [
      { name: 'name', label: 'Customer Name' },
      { name: 'email', label: 'Email Address' },
      { name: 'user_type', label: 'User Type (account_user)' },
      { name: 'account_id', label: 'Account Assignment' },
      { name: 'is_active', label: 'Active Status' },
      { name: 'phone', label: 'Phone Number' },
      { name: 'company', label: 'Company Name' }
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
    customer_accounts: [
      { name: 'name', label: 'Account Name' },
      { name: 'description', label: 'Description' },
      { name: 'is_active', label: 'Active Status' },
      { name: 'settings', label: 'Account Settings' }
    ],
    staff_users: [
      { name: 'name', label: 'Staff Name' },
      { name: 'email', label: 'Email' },
      { name: 'user_type', label: 'User Type (agent)' },
      { name: 'is_active', label: 'Active Status' },
      { name: 'password', label: 'Password' }
    ],
    threads: [
      { name: 'comment', label: 'Comment Text' },
      { name: 'is_internal', label: 'Internal Note' }
    ]
  }
  
  return fieldDefinitions[tableKey] || []
}

const loadDefaultMapping = () => {
  const defaultMappings = {
    customer_users: [
      {
        destination_field: 'name',
        type: 'combine_fields',
        source_field: '',
        fields: ['first_name', 'last_name'],
        separator: ' ',
        static_value: '',
        prefix: '',
        transform_function: ''
      },
      {
        destination_field: 'email',
        type: 'direct_mapping',
        source_field: 'email',
        fields: [],
        separator: ' ',
        static_value: '',
        prefix: '',
        transform_function: ''
      },
      {
        destination_field: 'user_type',
        type: 'static_value',
        source_field: '',
        fields: [],
        separator: ' ',
        static_value: 'account_user',
        prefix: '',
        transform_function: ''
      }
    ],
    conversations: [
      {
        destination_field: 'title',
        type: 'direct_mapping',
        source_field: 'subject',
        fields: [],
        separator: ' ',
        static_value: '',
        prefix: '',
        transform_function: ''
      }
    ],
    customer_accounts: [
      {
        destination_field: 'name',
        type: 'direct_mapping',
        source_field: 'company',
        fields: [],
        separator: ' ',
        static_value: '',
        prefix: '',
        transform_function: ''
      }
    ],
    staff_users: [
      {
        destination_field: 'name',
        type: 'combine_fields',
        source_field: '',
        fields: ['first_name', 'last_name'],
        separator: ' ',
        static_value: '',
        prefix: '',
        transform_function: ''
      },
      {
        destination_field: 'email',
        type: 'direct_mapping',
        source_field: 'email',
        fields: [],
        separator: ' ',
        static_value: '',
        prefix: '',
        transform_function: ''
      },
      {
        destination_field: 'user_type',
        type: 'static_value',
        source_field: '',
        fields: [],
        separator: ' ',
        static_value: 'agent',
        prefix: '',
        transform_function: ''
      }
    ],
    threads: [
      {
        destination_field: 'comment',
        type: 'direct_mapping',
        source_field: 'body',
        fields: [],
        separator: ' ',
        static_value: '',
        prefix: '',
        transform_function: ''
      }
    ]
  }
  
  currentMappings.value = defaultMappings[selectedTable.value] || []
}

const clearMappings = () => {
  currentMappings.value = []
}
</script>