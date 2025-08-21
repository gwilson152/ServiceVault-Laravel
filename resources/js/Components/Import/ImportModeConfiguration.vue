<template>
  <div class="space-y-6">
    <!-- Import Mode Selection -->
    <div class="bg-white rounded-lg border border-gray-200 p-6">
      <h3 class="text-lg font-medium text-gray-900 mb-4">Import Mode</h3>
      <p class="text-sm text-gray-600 mb-4">
        Choose how to handle records during import when duplicates are found.
      </p>
      
      <div class="space-y-3">
        <label class="flex items-start space-x-3 cursor-pointer">
          <input 
            type="radio" 
            :value="'create'" 
            v-model="localConfig.import_mode"
            class="mt-1 h-4 w-4 text-blue-600 border-gray-300 focus:ring-blue-500"
          />
          <div class="flex-1">
            <div class="text-sm font-medium text-gray-900">Create Only</div>
            <div class="text-sm text-gray-500">
              Always create new records. Skip duplicates if configured to do so.
            </div>
          </div>
        </label>
        
        <label class="flex items-start space-x-3 cursor-pointer">
          <input 
            type="radio" 
            :value="'update'" 
            v-model="localConfig.import_mode"
            class="mt-1 h-4 w-4 text-blue-600 border-gray-300 focus:ring-blue-500"
          />
          <div class="flex-1">
            <div class="text-sm font-medium text-gray-900">Update Only</div>
            <div class="text-sm text-gray-500">
              Only update existing records. Skip if no matching record is found.
            </div>
          </div>
        </label>
        
        <label class="flex items-start space-x-3 cursor-pointer">
          <input 
            type="radio" 
            :value="'upsert'" 
            v-model="localConfig.import_mode"
            class="mt-1 h-4 w-4 text-blue-600 border-gray-300 focus:ring-blue-500"
          />
          <div class="flex-1">
            <div class="text-sm font-medium text-gray-900">Create or Update (Upsert)</div>
            <div class="text-sm text-gray-500">
              Create new records or update existing ones. Recommended for most imports.
            </div>
          </div>
        </label>
      </div>
    </div>

    <!-- Duplicate Detection Configuration -->
    <div class="bg-white rounded-lg border border-gray-200 p-6">
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-medium text-gray-900">Duplicate Detection</h3>
        <label class="flex items-center">
          <input 
            type="checkbox" 
            v-model="duplicateDetection.enabled"
            class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
          />
          <span class="ml-2 text-sm text-gray-700">Enable duplicate detection</span>
        </label>
      </div>

      <div v-if="duplicateDetection.enabled" class="space-y-4">
        <!-- Source Identifier Field -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Source Identifier Field
          </label>
          <select 
            v-model="localConfig.source_identifier_field"
            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
          >
            <option value="">Select field...</option>
            <option 
              v-for="field in availableFields"
              :key="field"
              :value="field"
            >
              {{ field }}
            </option>
          </select>
          <p class="mt-1 text-xs text-gray-500">
            Primary field used to identify unique records (e.g., 'id', 'email', 'external_id')
          </p>
        </div>

        <!-- Matching Strategy -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Matching Strategy
          </label>
          <select 
            v-model="duplicateDetection.strategy"
            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
          >
            <option value="exact_match">Exact Match</option>
            <option value="case_insensitive">Case Insensitive</option>
            <option value="fuzzy_match">Fuzzy Matching</option>
          </select>
        </div>

        <!-- Primary Matching Fields -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Primary Matching Fields
          </label>
          <div class="space-y-2">
            <div 
              v-for="(field, index) in matchingStrategy.primary_fields"
              :key="index"
              class="flex items-center space-x-2"
            >
              <select 
                v-model="matchingStrategy.primary_fields[index]"
                class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
              >
                <option value="">Select field...</option>
                <option 
                  v-for="field in availableFields"
                  :key="field"
                  :value="field"
                >
                  {{ field }}
                </option>
              </select>
              <button
                @click="removePrimaryField(index)"
                type="button"
                class="text-red-600 hover:text-red-800"
              >
                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                </svg>
              </button>
            </div>
            <button
              @click="addPrimaryField"
              type="button"
              class="text-sm text-blue-600 hover:text-blue-800"
            >
              + Add Primary Field
            </button>
          </div>
          <p class="mt-1 text-xs text-gray-500">
            Fields that must match for a record to be considered a duplicate (highest priority)
          </p>
        </div>

        <!-- Secondary Matching Fields -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Secondary Matching Fields
          </label>
          <div class="space-y-2">
            <div 
              v-for="(field, index) in matchingStrategy.secondary_fields"
              :key="index"
              class="flex items-center space-x-2"
            >
              <select 
                v-model="matchingStrategy.secondary_fields[index]"
                class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
              >
                <option value="">Select field...</option>
                <option 
                  v-for="field in availableFields"
                  :key="field"
                  :value="field"
                >
                  {{ field }}
                </option>
              </select>
              <button
                @click="removeSecondaryField(index)"
                type="button"
                class="text-red-600 hover:text-red-800"
              >
                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                </svg>
              </button>
            </div>
            <button
              @click="addSecondaryField"
              type="button"
              class="text-sm text-blue-600 hover:text-blue-800"
            >
              + Add Secondary Field
            </button>
          </div>
          <p class="mt-1 text-xs text-gray-500">
            Optional fields for additional matching when primary fields don't provide strong matches
          </p>
        </div>

        <!-- Fuzzy Matching Settings -->
        <div v-if="duplicateDetection.strategy === 'fuzzy_match'" class="space-y-4">
          <div class="flex items-center">
            <input 
              type="checkbox" 
              v-model="matchingStrategy.fuzzy_matching"
              class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
            />
            <label class="ml-2 text-sm text-gray-700">Enable fuzzy matching</label>
          </div>
          
          <div v-if="matchingStrategy.fuzzy_matching">
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Similarity Threshold: {{ (matchingStrategy.similarity_threshold * 100).toFixed(0) }}%
            </label>
            <input 
              type="range" 
              v-model.number="matchingStrategy.similarity_threshold"
              min="0.5" 
              max="1" 
              step="0.05"
              class="w-full"
            />
            <p class="mt-1 text-xs text-gray-500">
              Minimum similarity required for fuzzy matches (70-95% recommended)
            </p>
          </div>
        </div>

        <!-- Case Sensitivity -->
        <div class="flex items-center">
          <input 
            type="checkbox" 
            v-model="duplicateDetection.case_sensitive"
            class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
          />
          <label class="ml-2 text-sm text-gray-700">Case sensitive matching</label>
        </div>
      </div>
    </div>

    <!-- Duplicate Handling Options -->
    <div v-if="duplicateDetection.enabled" class="bg-white rounded-lg border border-gray-200 p-6">
      <h3 class="text-lg font-medium text-gray-900 mb-4">Duplicate Handling</h3>
      
      <div class="space-y-4">
        <div class="flex items-center">
          <input 
            type="checkbox" 
            v-model="localConfig.skip_duplicates"
            class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
          />
          <label class="ml-2 text-sm text-gray-700">Skip duplicate records entirely</label>
        </div>
        
        <div v-if="!localConfig.skip_duplicates" class="flex items-center">
          <input 
            type="checkbox" 
            v-model="localConfig.update_duplicates"
            class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
          />
          <label class="ml-2 text-sm text-gray-700">Update existing records when duplicates are found</label>
        </div>
      </div>

      <div class="mt-4 p-4 bg-gray-50 rounded-md">
        <h4 class="text-sm font-medium text-gray-900 mb-2">Import Behavior Summary</h4>
        <div class="text-sm text-gray-600">
          <div v-if="localConfig.import_mode === 'create'">
            <strong>Create Mode:</strong> 
            <span v-if="localConfig.skip_duplicates">Will create new records and skip any duplicates found.</span>
            <span v-else>Will create new records even if duplicates exist.</span>
          </div>
          <div v-else-if="localConfig.import_mode === 'update'">
            <strong>Update Mode:</strong> Will only update existing records. New records will be skipped.
          </div>
          <div v-else>
            <strong>Upsert Mode:</strong>
            <span v-if="localConfig.skip_duplicates">Will create new records and skip updating duplicates.</span>
            <span v-else-if="localConfig.update_duplicates">Will create new records or update existing ones.</span>
            <span v-else>Will create new records but won't update existing ones.</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'

const props = defineProps({
  config: {
    type: Object,
    default: () => ({})
  },
  availableFields: {
    type: Array,
    default: () => []
  }
})

const emit = defineEmits(['update:config'])

// Local configuration state
const localConfig = ref({
  import_mode: 'upsert',
  source_identifier_field: '',
  skip_duplicates: false,
  update_duplicates: true,
  ...props.config
})

// Duplicate detection configuration
const duplicateDetection = ref({
  enabled: true,
  strategy: 'exact_match',
  case_sensitive: false,
  ...(props.config.duplicate_detection || {})
})

// Matching strategy configuration
const matchingStrategy = ref({
  primary_fields: [props.config.source_identifier_field || ''].filter(Boolean),
  secondary_fields: ['email'],
  fuzzy_matching: false,
  similarity_threshold: 0.8,
  ...(props.config.matching_strategy || {})
})

// Watch for changes and emit updates
watch([localConfig, duplicateDetection, matchingStrategy], () => {
  const updatedConfig = {
    ...localConfig.value,
    duplicate_detection: duplicateDetection.value,
    matching_strategy: matchingStrategy.value
  }
  emit('update:config', updatedConfig)
}, { deep: true })

// Methods for managing field arrays
const addPrimaryField = () => {
  matchingStrategy.value.primary_fields.push('')
}

const removePrimaryField = (index) => {
  matchingStrategy.value.primary_fields.splice(index, 1)
}

const addSecondaryField = () => {
  matchingStrategy.value.secondary_fields.push('')
}

const removeSecondaryField = (index) => {
  matchingStrategy.value.secondary_fields.splice(index, 1)
}

// Initialize with prop data
watch(() => props.config, (newConfig) => {
  if (newConfig) {
    localConfig.value = {
      import_mode: 'upsert',
      source_identifier_field: '',
      skip_duplicates: false,
      update_duplicates: true,
      ...newConfig
    }
    
    if (newConfig.duplicate_detection) {
      duplicateDetection.value = {
        enabled: true,
        strategy: 'exact_match',
        case_sensitive: false,
        ...newConfig.duplicate_detection
      }
    }
    
    if (newConfig.matching_strategy) {
      matchingStrategy.value = {
        primary_fields: [newConfig.source_identifier_field || ''].filter(Boolean),
        secondary_fields: ['email'],
        fuzzy_matching: false,
        similarity_threshold: 0.8,
        ...newConfig.matching_strategy
      }
    }
  }
}, { immediate: true, deep: true })
</script>