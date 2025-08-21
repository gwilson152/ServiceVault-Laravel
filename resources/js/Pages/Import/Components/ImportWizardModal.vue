<template>
  <StackedDialog 
    :show="show" 
    @close="$emit('close')"
    title="Import Configuration Wizard"
    max-width="6xl"
  >
    <div class="space-y-6">
      <!-- Wizard Header with Steps -->
      <div class="border-b border-gray-200 pb-4">
        <div class="flex items-center justify-between">
          <h2 class="text-lg font-medium text-gray-900">
            {{ profile?.name }} - Import Wizard
          </h2>
          <div class="flex items-center space-x-4">
            <div class="flex items-center space-x-2">
              <div v-for="(step, index) in steps" :key="step.key" class="flex items-center">
                <div 
                  class="flex items-center justify-center w-8 h-8 rounded-full border-2 text-sm font-medium transition-colors"
                  :class="getStepClass(step, index)"
                >
                  <CheckIcon v-if="isStepCompleted(step)" class="w-5 h-5" />
                  <span v-else>{{ index + 1 }}</span>
                </div>
                <div v-if="index < steps.length - 1" class="w-8 h-0.5 bg-gray-200 mx-2"></div>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Step Labels -->
        <div class="flex justify-between mt-2 text-xs text-gray-500">
          <span v-for="step in steps" :key="step.key" class="text-center" :style="`width: ${100/steps.length}%`">
            {{ step.name }}
          </span>
        </div>
      </div>

      <!-- Step Content -->
      <div class="min-h-[500px]">
        <!-- Step 1: Filters -->
        <div v-if="currentStep === 'filters'" class="space-y-6">
          <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <div class="flex">
              <div class="flex-shrink-0">
                <AdjustmentsHorizontalIcon class="h-5 w-5 text-blue-400" />
              </div>
              <div class="ml-3">
                <h3 class="text-sm font-medium text-blue-800">Configure Import Filters</h3>
                <div class="mt-2 text-sm text-blue-700">
                  <p>Set up filters to control which data is imported from the source database.</p>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Filter Configuration Component -->
          <ImportFilterBuilderContent
            v-if="profile"
            :profile="profile"
            v-model="currentFilters"
            @filters-changed="handleFiltersChanged"
          />
        </div>

        <!-- Step 2: Field Mappings -->
        <div v-if="currentStep === 'mappings'" class="space-y-6">
          <div class="bg-green-50 border border-green-200 rounded-lg p-4">
            <div class="flex">
              <div class="flex-shrink-0">
                <ArrowsRightLeftIcon class="h-5 w-5 text-green-400" />
              </div>
              <div class="ml-3">
                <h3 class="text-sm font-medium text-green-800">Configure Field Mappings</h3>
                <div class="mt-2 text-sm text-green-700">
                  <p>Map source database fields to Service Vault fields with transformations.</p>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Field Mapping Component -->
          <ImportFieldMapperContent
            v-if="profile"
            :profile="profile"
            v-model="currentMappings"
            @mappings-changed="handleMappingsChanged"
          />
        </div>

        <!-- Step 3: Import Modes -->
        <div v-if="currentStep === 'modes'" class="space-y-6">
          <div class="bg-orange-50 border border-orange-200 rounded-lg p-4">
            <div class="flex">
              <div class="flex-shrink-0">
                <CogIcon class="h-5 w-5 text-orange-400" />
              </div>
              <div class="ml-3">
                <h3 class="text-sm font-medium text-orange-800">Configure Import Modes</h3>
                <div class="mt-2 text-sm text-orange-700">
                  <p>Set up how to handle duplicates and record creation/update behavior during import.</p>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Import Mode Configuration Component -->
          <ImportModeConfiguration
            v-if="profile"
            :config="currentModeConfig"
            :available-fields="availableFieldsFromMappings"
            @update:config="handleModeConfigChanged"
          />
        </div>

        <!-- Step 4: Preview -->
        <div v-if="currentStep === 'preview'" class="space-y-6">
          <div class="bg-purple-50 border border-purple-200 rounded-lg p-4">
            <div class="flex">
              <div class="flex-shrink-0">
                <EyeIcon class="h-5 w-5 text-purple-400" />
              </div>
              <div class="ml-3">
                <h3 class="text-sm font-medium text-purple-800">Preview Import Data</h3>
                <div class="mt-2 text-sm text-purple-700">
                  <p>Review the data that will be imported with your current filters and field mappings applied.</p>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Active Configuration Summary -->
          <div class="bg-gray-50 rounded-lg p-4">
            <h4 class="text-sm font-medium text-gray-900 mb-3">Active Configuration</h4>
            <div class="grid grid-cols-3 gap-4">
              <div>
                <h5 class="text-xs font-medium text-gray-700 mb-2">Import Filters</h5>
                <div v-if="hasActiveFilters" class="space-y-1">
                  <div v-if="currentFilters?.selected_tables?.length" class="text-xs text-gray-600">
                    📊 Tables: {{ currentFilters.selected_tables.join(', ') }}
                  </div>
                  <div v-if="currentFilters?.import_filters?.date_from" class="text-xs text-gray-600">
                    📅 From: {{ currentFilters.import_filters.date_from }}
                  </div>
                  <div v-if="currentFilters?.import_filters?.date_to" class="text-xs text-gray-600">
                    📅 To: {{ currentFilters.import_filters.date_to }}
                  </div>
                  <div v-if="currentFilters?.import_filters?.limit" class="text-xs text-gray-600">
                    🔢 Limit: {{ currentFilters.import_filters.limit }} records per type
                  </div>
                </div>
                <div v-else class="text-xs text-gray-500">No filters configured - importing all data</div>
              </div>
              
              <div>
                <h5 class="text-xs font-medium text-gray-700 mb-2">Field Mappings</h5>
                <div v-if="hasActiveMappings" class="space-y-1">
                  <div v-for="(mappings, table) in currentMappings" :key="table" class="text-xs text-gray-600">
                    🔄 {{ table }}: {{ Object.keys(mappings || {}).length }} mappings
                  </div>
                </div>
                <div v-else class="text-xs text-gray-500">Using default field mappings</div>
              </div>

              <div>
                <h5 class="text-xs font-medium text-gray-700 mb-2">Import Modes</h5>
                <div v-if="currentModeConfig?.import_mode" class="space-y-1">
                  <div class="text-xs text-gray-600">
                    🔧 Mode: {{ currentModeConfig.import_mode }}
                  </div>
                  <div v-if="currentModeConfig?.duplicate_detection?.enabled" class="text-xs text-gray-600">
                    🔍 Duplicate Detection: {{ currentModeConfig.duplicate_detection.strategy }}
                  </div>
                  <div v-if="currentModeConfig?.source_identifier_field" class="text-xs text-gray-600">
                    🏷️ ID Field: {{ currentModeConfig.source_identifier_field }}
                  </div>
                </div>
                <div v-else class="text-xs text-gray-500">Using default import mode (upsert)</div>
              </div>
            </div>
          </div>
          
          <!-- Preview Component -->
          <ImportPreviewContent
            v-if="profile"
            :profile="profile"
            :filters="currentFilters"
            :mappings="currentMappings"
            @preview-loaded="handlePreviewLoaded"
          />
        </div>
      </div>

      <!-- Navigation Footer -->
      <div class="flex items-center justify-between pt-4 border-t border-gray-200">
        <div class="flex space-x-3">
          <button
            v-if="currentStepIndex > 0"
            @click="goToPreviousStep"
            class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
          >
            <ArrowLeftIcon class="-ml-1 mr-2 h-4 w-4" />
            Back
          </button>
        </div>
        
        <div class="flex space-x-3">
          <button
            @click="$emit('close')"
            class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
          >
            Cancel
          </button>
          
          <button
            v-if="currentStepIndex < steps.length - 1"
            @click="goToNextStep"
            :disabled="!canProceedToNext"
            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:bg-gray-300 disabled:cursor-not-allowed"
          >
            Next
            <ArrowRightIcon class="ml-2 -mr-1 h-4 w-4" />
          </button>
          
          <button
            v-if="currentStep === 'preview'"
            @click="executeImport"
            :disabled="!previewLoaded"
            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 disabled:bg-gray-300 disabled:cursor-not-allowed"
          >
            <PlayIcon class="-ml-1 mr-2 h-4 w-4" />
            Execute Import
          </button>
        </div>
      </div>
    </div>
  </StackedDialog>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import StackedDialog from '@/Components/StackedDialog.vue'
import ImportFilterBuilderContent from './ImportFilterBuilderContent.vue'
import ImportFieldMapperContent from './ImportFieldMapperContent.vue'
import ImportPreviewContent from './ImportPreviewContent.vue'
import ImportModeConfiguration from '@/Components/Import/ImportModeConfiguration.vue'
import {
  CheckIcon,
  AdjustmentsHorizontalIcon,
  ArrowsRightLeftIcon,
  EyeIcon,
  ArrowLeftIcon,
  ArrowRightIcon,
  PlayIcon,
  CogIcon,
} from '@heroicons/vue/24/outline'

// Props
const props = defineProps({
  show: {
    type: Boolean,
    default: false
  },
  profile: {
    type: Object,
    default: null
  }
})

// Emits
const emit = defineEmits(['close', 'execute-import'])

// Reactive state
const currentStep = ref('filters')
const currentFilters = ref({})
const currentMappings = ref({})
const currentModeConfig = ref({})
const previewLoaded = ref(false)

// Steps configuration
const steps = ref([
  { key: 'filters', name: 'Import Filters', completed: false },
  { key: 'mappings', name: 'Field Mappings', completed: false },
  { key: 'modes', name: 'Import Modes', completed: false },
  { key: 'preview', name: 'Preview Data', completed: false }
])

// Computed
const currentStepIndex = computed(() => {
  return steps.value.findIndex(step => step.key === currentStep.value)
})

const canProceedToNext = computed(() => {
  switch (currentStep.value) {
    case 'filters':
      return true // Filters are always optional
    case 'mappings':
      return true // Mappings are optional (can use defaults)
    case 'modes':
      return true // Import modes are always valid (defaults exist)
    case 'preview':
      return previewLoaded.value
    default:
      return false
  }
})

const hasActiveFilters = computed(() => {
  return currentFilters.value?.selected_tables?.length > 0 ||
         currentFilters.value?.import_filters?.date_from ||
         currentFilters.value?.import_filters?.date_to ||
         currentFilters.value?.import_filters?.limit
})

const hasActiveMappings = computed(() => {
  return Object.keys(currentMappings.value || {}).some(table => 
    Object.keys(currentMappings.value[table] || {}).length > 0
  )
})

const availableFieldsFromMappings = computed(() => {
  // Extract available fields from field mappings for use in import mode configuration
  const fields = new Set()
  
  // Add common identifier fields
  fields.add('id')
  fields.add('email')
  fields.add('external_id')
  fields.add('created_at')
  fields.add('updated_at')
  
  // Extract fields from current mappings
  Object.values(currentMappings.value || {}).forEach(tableMappings => {
    Object.keys(tableMappings || {}).forEach(sourceField => {
      fields.add(sourceField)
    })
  })
  
  return Array.from(fields).sort()
})

// Watch for modal open/close
watch(() => props.show, (show) => {
  if (show) {
    // Reset wizard state when opening
    currentStep.value = 'filters'
    currentFilters.value = {}
    currentMappings.value = {}
    currentModeConfig.value = {}
    previewLoaded.value = false
    steps.value.forEach(step => step.completed = false)
  }
})

// Methods
const getStepClass = (step, index) => {
  if (isStepCompleted(step)) {
    return 'bg-green-600 border-green-600 text-white'
  } else if (step.key === currentStep.value) {
    return 'bg-indigo-600 border-indigo-600 text-white'
  } else if (index < currentStepIndex.value) {
    return 'bg-gray-300 border-gray-300 text-gray-600'
  } else {
    return 'bg-white border-gray-300 text-gray-400'
  }
}

const isStepCompleted = (step) => {
  return step.completed
}

const goToNextStep = () => {
  if (currentStepIndex.value < steps.value.length - 1) {
    // Mark current step as completed
    steps.value[currentStepIndex.value].completed = true
    
    // Move to next step
    currentStep.value = steps.value[currentStepIndex.value + 1].key
  }
}

const goToPreviousStep = () => {
  if (currentStepIndex.value > 0) {
    currentStep.value = steps.value[currentStepIndex.value - 1].key
  }
}

const handleFiltersChanged = (filters) => {
  currentFilters.value = filters
}

const handleMappingsChanged = (mappings) => {
  currentMappings.value = mappings
}

const handleModeConfigChanged = (modeConfig) => {
  currentModeConfig.value = modeConfig
}

const handlePreviewLoaded = (success) => {
  previewLoaded.value = success
}

const executeImport = () => {
  emit('execute-import', {
    profile: props.profile,
    filters: currentFilters.value,
    mappings: currentMappings.value,
    modeConfig: currentModeConfig.value
  })
}
</script>