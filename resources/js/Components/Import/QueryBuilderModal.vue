<template>
  <StackedDialog
    :show="show"
    @close="$emit('close')"
    title="Visual Query Builder"
    :fullscreen="true"
    :fullscreen-padding="'2rem'"
  >
    <div class="flex flex-col h-full">
      <!-- Header with Profile Info -->
      <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
        <div class="flex items-center justify-between">
          <div>
            <h3 class="text-lg font-semibold text-gray-900">{{ profile?.name }}</h3>
            <p class="text-sm text-gray-600">
              {{ profile?.host }}:{{ profile?.port }}/{{ profile?.database }}
            </p>
          </div>
          <div class="flex items-center space-x-3">
            <div class="flex items-center">
              <div class="w-2 h-2 rounded-full mr-2"
                   :class="connectionStatus === 'connected' ? 'bg-green-500' : 'bg-red-500'">
              </div>
              <span class="text-sm text-gray-600">
                {{ connectionStatus === 'connected' ? 'Connected' : 'Disconnected' }}
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- Progress Steps -->
      <div class="px-6 py-3 border-b border-gray-200 bg-white">
        <nav class="flex space-x-4" aria-label="Progress">
          <button
            v-for="(step, index) in steps"
            :key="step.id"
            @click="currentStep = step.id"
            class="flex items-center px-3 py-1.5 text-sm font-medium rounded-md transition-colors"
            :class="getStepClass(step.id, index)"
          >
            <component :is="step.icon" class="w-4 h-4 mr-2" />
            {{ step.name }}
            <CheckCircleIcon v-if="step.completed" class="w-4 h-4 ml-2 text-green-500" />
          </button>
        </nav>
      </div>

      <!-- Main Content Area -->
      <div class="flex-1 flex overflow-hidden">
        <!-- Left Panel - Query Builder Steps -->
        <div class="w-1/2 border-r border-gray-200 flex flex-col">
          <div class="flex-1 overflow-y-auto p-6">
            <!-- Step 1: Table Selection -->
            <div v-show="currentStep === 'tables'" class="space-y-6">
              <div>
                <h3 class="text-lg font-medium text-gray-900 mb-2">Select Base Table</h3>
                <p class="text-sm text-gray-600 mb-4">
                  Choose the primary table for your import query. This will be the main source of data.
                </p>
              </div>
              
              <TableSelector
                :profile-id="profile?.id"
                :selected-table="queryConfig.base_table"
                @table-selected="handleTableSelected"
                @schema-loaded="handleSchemaLoaded"
              />
            </div>

            <!-- Step 2: JOIN Configuration -->
            <div v-show="currentStep === 'joins'" class="space-y-6">
              <div>
                <h3 class="text-lg font-medium text-gray-900 mb-2">Configure Table Relationships</h3>
                <p class="text-sm text-gray-600 mb-4">
                  Add JOINs to combine data from multiple tables. We'll suggest common relationships.
                </p>
              </div>

              <JoinBuilder
                :profile-id="profile?.id"
                :base-table="baseTableObject"
                :joins="queryConfig.joins || []"
                :available-tables="availableTables"
                @joins-changed="handleJoinsUpdated"
              />
            </div>

            <!-- Step 3: Field Mapping -->
            <div v-show="currentStep === 'fields'" class="space-y-6">
              <div>
                <h3 class="text-lg font-medium text-gray-900 mb-2">Map Fields to Service Vault</h3>
                <p class="text-sm text-gray-600 mb-4">
                  Select which fields to import and map them to Service Vault fields. Configure transformations as needed.
                </p>
                
                <!-- Progress/Validation Feedback -->
                <div class="mb-4">
                  <div v-if="queryConfig.fields?.length > 0" class="flex items-center p-3 bg-green-50 border border-green-200 rounded-md">
                    <CheckCircleIcon class="w-5 h-5 text-green-500 mr-2" />
                    <span class="text-sm text-green-700">
                      {{ queryConfig.fields.length }} field{{ queryConfig.fields.length === 1 ? '' : 's' }} mapped. Ready to proceed!
                    </span>
                  </div>
                  <div v-else class="flex items-center p-3 bg-amber-50 border border-amber-200 rounded-md">
                    <ExclamationTriangleIcon class="w-5 h-5 text-amber-500 mr-2" />
                    <div class="text-sm text-amber-700">
                      <p class="font-medium">Field mapping required</p>
                      <p>You must map at least one field to continue. Use the field mapper below to select and map source fields to Service Vault target fields.</p>
                    </div>
                  </div>
                </div>
              </div>

              <FieldMapper
                :profile-id="profile?.id"
                :base-table="baseTableObject"
                :joins="queryConfig.joins || []"
                :fields="queryConfig.fields || []"
                :target-type="queryConfig.target_type || 'customer_users'"
                @fields-changed="handleFieldsUpdated"
                @target-type-changed="handleTargetTypeChanged"
              />
            </div>

            <!-- Step 4: Filters -->
            <div v-show="currentStep === 'filters'" class="space-y-6">
              <div>
                <h3 class="text-lg font-medium text-gray-900 mb-2">Add Data Filters</h3>
                <p class="text-sm text-gray-600 mb-4">
                  Filter which records to import with WHERE conditions. Use suggested filters or create custom ones.
                </p>
              </div>

              <FilterBuilder
                :profile-id="profile?.id"
                :base-table="baseTableObject"
                :joins="queryConfig.joins || []"
                :fields="queryConfig.fields || []"
                :available-tables="availableTables"
                v-model="queryConfig.filters"
                :selected-target-type="queryConfig.target_type || 'customer_users'"
                @filters-changed="handleFiltersUpdated"
              />
            </div>
          </div>
        </div>

        <!-- Right Panel - Query Preview -->
        <div class="w-1/2 flex flex-col">
          <div class="flex-1 overflow-y-auto p-6">
            <QueryPreview
              :profile-id="profile?.id"
              :query-config="queryConfig"
              :auto-refresh="true"
              @query-validated="handleQueryValidated"
              @preview-updated="handlePreviewUpdated"
            />
          </div>
        </div>
      </div>

      <!-- Footer Actions -->
      <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
        <div class="flex items-center justify-between">
          <!-- Step Navigation -->
          <div class="flex items-center space-x-3">
            <button
              @click="previousStep"
              :disabled="currentStepIndex === 0"
              class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <ChevronLeftIcon class="w-4 h-4 mr-2" />
              Previous
            </button>
            
            <button
              @click="nextStep"
              :disabled="!canProceedToNextStep || currentStepIndex === steps.length - 1"
              class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              Next
              <ChevronRightIcon class="w-4 h-4 ml-2" />
            </button>
          </div>

          <!-- Query Actions -->
          <div class="flex items-center space-x-3">
            <button
              @click="saveAsTemplate"
              :disabled="!isQueryValid || isSaving"
              class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50"
            >
              <BookmarkIcon class="w-4 h-4 mr-2" />
              Save as Template
            </button>
            
            <button
              @click="saveQuery"
              :disabled="!isQueryValid || isSaving"
              class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 disabled:opacity-50"
            >
              <span v-if="isSaving" class="inline-block animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2"></span>
              <CheckIcon v-else class="w-4 h-4 mr-2" />
              {{ isSaving ? 'Saving...' : 'Save Query' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </StackedDialog>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import StackedDialog from '@/Components/StackedDialog.vue'
import TableSelector from './TableSelector.vue'
import JoinBuilder from './JoinBuilder.vue'
import FieldMapper from './FieldMapper.vue'
import FilterBuilder from './FilterBuilder.vue'
import QueryPreview from './QueryPreview.vue'
import {
  TableCellsIcon,
  LinkIcon,
  MapIcon,
  FunnelIcon,
  CheckCircleIcon,
  ExclamationTriangleIcon,
  ChevronLeftIcon,
  ChevronRightIcon,
  BookmarkIcon,
  CheckIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
  show: Boolean,
  profile: {
    type: Object,
    default: null
  },
  initialConfig: {
    type: Object,
    default: () => ({})
  }
})

const emit = defineEmits(['close', 'query-saved'])

// State
const currentStep = ref('tables')
const connectionStatus = ref('connected')
const availableTables = ref([])
const isQueryValid = ref(false)
const isSaving = ref(false)

const queryConfig = ref({
  base_table: '',
  joins: [],
  fields: [],
  filters: [],
  target_type: 'customer_users',
  ...props.initialConfig
})

// Steps configuration
const steps = ref([
  { 
    id: 'tables', 
    name: 'Tables', 
    icon: TableCellsIcon, 
    completed: false,
    required: true 
  },
  { 
    id: 'joins', 
    name: 'Relationships', 
    icon: LinkIcon, 
    completed: false,
    required: false 
  },
  { 
    id: 'fields', 
    name: 'Field Mapping', 
    icon: MapIcon, 
    completed: false,
    required: true 
  },
  { 
    id: 'filters', 
    name: 'Filters', 
    icon: FunnelIcon, 
    completed: false,
    required: false 
  }
])

// Computed
const currentStepIndex = computed(() => {
  return steps.value.findIndex(step => step.id === currentStep.value)
})

const canProceedToNextStep = computed(() => {
  const step = steps.value.find(s => s.id === currentStep.value)
  if (!step?.required) return true

  switch (currentStep.value) {
    case 'tables':
      return !!queryConfig.value.base_table
    case 'fields':
      return queryConfig.value.fields?.length > 0
    default:
      return true
  }
})

const baseTableObject = computed(() => {
  if (!queryConfig.value.base_table || !availableTables.value.length) {
    return null
  }
  return availableTables.value.find(table => table.name === queryConfig.value.base_table) || null
})

// Methods
const getStepClass = (stepId, index) => {
  const step = steps.value.find(s => s.id === stepId)
  const isCurrent = stepId === currentStep.value
  const isCompleted = step?.completed
  const isAccessible = index <= currentStepIndex.value || isCompleted

  if (isCurrent) {
    return 'bg-indigo-100 text-indigo-700 border border-indigo-300'
  } else if (isCompleted) {
    return 'bg-green-50 text-green-700 border border-green-200 hover:bg-green-100'
  } else if (isAccessible) {
    return 'text-gray-600 hover:text-gray-900 hover:bg-gray-50'
  } else {
    return 'text-gray-400 cursor-not-allowed'
  }
}

const previousStep = () => {
  const currentIndex = currentStepIndex.value
  if (currentIndex > 0) {
    currentStep.value = steps.value[currentIndex - 1].id
  }
}

const nextStep = () => {
  if (!canProceedToNextStep.value) return

  const currentIndex = currentStepIndex.value
  if (currentIndex < steps.value.length - 1) {
    // Mark current step as completed
    const step = steps.value.find(s => s.id === currentStep.value)
    if (step) step.completed = true

    currentStep.value = steps.value[currentIndex + 1].id
  }
}

// Event Handlers
const handleTableSelected = (table) => {
  queryConfig.value.base_table = table.name
  queryConfig.value.joins = [] // Reset joins when table changes
  queryConfig.value.fields = [] // Reset fields when table changes
  
  // Mark tables step as completed
  const tablesStep = steps.value.find(s => s.id === 'tables')
  if (tablesStep) tablesStep.completed = true
}

const handleSchemaLoaded = (schema) => {
  availableTables.value = schema.tables || []
}

const handleJoinsUpdated = (joins) => {
  queryConfig.value.joins = joins
  
  // Mark joins step as completed if there are joins
  const joinsStep = steps.value.find(s => s.id === 'joins')
  if (joinsStep) joinsStep.completed = joins.length > 0
}

const handleFieldsUpdated = (fields) => {
  queryConfig.value.fields = fields
  
  // Mark fields step as completed
  const fieldsStep = steps.value.find(s => s.id === 'fields')
  if (fieldsStep) fieldsStep.completed = fields.length > 0
}

const handleTargetTypeChanged = (targetType) => {
  queryConfig.value.target_type = targetType
}

const handleFiltersUpdated = (filters) => {
  queryConfig.value.filters = filters
  
  // Mark filters step as completed if there are filters
  const filtersStep = steps.value.find(s => s.id === 'filters')
  if (filtersStep) filtersStep.completed = filters.length > 0
}

const handleQueryValidated = (validation) => {
  isQueryValid.value = validation.isValid
}

const handlePreviewUpdated = () => {
  // Update UI if needed
}

const saveQuery = async () => {
  if (!props.profile) return
  
  isSaving.value = true
  
  try {
    const response = await fetch(`/api/import/profiles/${props.profile.id}/queries`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
      },
      body: JSON.stringify(queryConfig.value)
    })

    if (response.ok) {
      const result = await response.json()
      emit('query-saved', result)
      emit('close')
    } else {
      const error = await response.json()
      alert('Failed to save query: ' + (error.message || 'Unknown error'))
    }
  } catch (error) {
    console.error('Save query failed:', error)
    alert('Failed to save query: ' + error.message)
  } finally {
    isSaving.value = false
  }
}

const saveAsTemplate = async () => {
  const templateName = prompt('Enter template name:')
  if (!templateName) return

  try {
    const templateData = {
      name: templateName,
      platform: 'custom',
      description: `Custom template created from query builder`,
      database_type: 'postgresql',
      configuration: {
        queries: {
          [queryConfig.value.target_type]: {
            name: `${queryConfig.value.target_type} import`,
            base_table: queryConfig.value.base_table,
            joins: queryConfig.value.joins,
            fields: queryConfig.value.fields,
            filters: queryConfig.value.filters,
            target_type: queryConfig.value.target_type
          }
        }
      },
      is_system: false,
      is_active: true
    }

    const response = await fetch('/api/import/templates', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
      },
      body: JSON.stringify(templateData)
    })

    if (response.ok) {
      alert('Template saved successfully!')
    } else {
      const error = await response.json()
      alert('Failed to save template: ' + (error.message || 'Unknown error'))
    }
  } catch (error) {
    console.error('Save template failed:', error)
    alert('Failed to save template: ' + error.message)
  }
}

// Initialize
onMounted(() => {
  // Set initial step completion based on existing config
  if (queryConfig.value.base_table) {
    const tablesStep = steps.value.find(s => s.id === 'tables')
    if (tablesStep) tablesStep.completed = true
  }
  
  if (queryConfig.value.fields?.length > 0) {
    const fieldsStep = steps.value.find(s => s.id === 'fields')
    if (fieldsStep) fieldsStep.completed = true
  }
  
  if (queryConfig.value.joins?.length > 0) {
    const joinsStep = steps.value.find(s => s.id === 'joins')
    if (joinsStep) joinsStep.completed = true
  }
  
  if (queryConfig.value.filters?.length > 0) {
    const filtersStep = steps.value.find(s => s.id === 'filters')
    if (filtersStep) filtersStep.completed = true
  }
})
</script>