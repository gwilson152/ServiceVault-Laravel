<template>
  <StackedDialog
    :show="show"
    @close="$emit('close')"
    title="Apply Import Template"
    max-width="4xl"
  >
    <div class="space-y-6">
      <!-- Header Info -->
      <div class="bg-blue-50 border border-blue-200 rounded-md p-4">
        <div class="flex">
          <div class="flex-shrink-0">
            <InformationCircleIcon class="h-5 w-5 text-blue-400" />
          </div>
          <div class="ml-3">
            <h3 class="text-sm font-medium text-blue-800">Profile: {{ profile?.name }}</h3>
            <div class="mt-2 text-sm text-blue-700">
              <p>Select a template to automatically configure your import queries and field mappings. Templates provide pre-built configurations for common platforms and use cases.</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="isLoading" class="flex items-center justify-center py-12">
        <div class="inline-flex items-center">
          <ArrowPathIcon class="animate-spin -ml-1 mr-3 h-5 w-5 text-gray-600" />
          <span class="text-sm text-gray-600">Loading templates...</span>
        </div>
      </div>

      <!-- Template Grid -->
      <div v-else-if="templates.length > 0" class="space-y-4">
        <h3 class="text-lg font-medium text-gray-900">Available Templates</h3>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div
            v-for="template in templates"
            :key="template.id"
            @click="selectTemplate(template)"
            class="relative cursor-pointer rounded-lg border p-6 hover:bg-gray-50 transition-colors"
            :class="selectedTemplate?.id === template.id 
              ? 'border-indigo-500 bg-indigo-50 ring-2 ring-indigo-500' 
              : 'border-gray-300 hover:border-gray-400'"
          >
            <!-- Selection Indicator -->
            <div v-if="selectedTemplate?.id === template.id" 
                 class="absolute -top-2 -right-2 h-6 w-6 rounded-full bg-indigo-600 flex items-center justify-center">
              <CheckIcon class="h-4 w-4 text-white" />
            </div>

            <!-- Template Icon -->
            <div class="flex items-start">
              <div class="flex-shrink-0">
                <div class="w-12 h-12 rounded-lg flex items-center justify-center"
                     :class="getTemplateIconClass(template.platform)">
                  <component :is="getTemplateIcon(template.platform)" class="w-6 h-6" />
                </div>
              </div>
              
              <div class="ml-4 flex-1">
                <!-- Template Name & Platform -->
                <div class="flex items-center justify-between">
                  <h4 class="text-lg font-medium text-gray-900">{{ template.name }}</h4>
                  <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                        :class="getPlatformBadgeClass(template.platform)">
                    {{ formatPlatform(template.platform) }}
                  </span>
                </div>
                
                <!-- Description -->
                <p class="mt-2 text-sm text-gray-600">{{ template.description }}</p>
                
                <!-- Template Features -->
                <div class="mt-3 space-y-2">
                  <div v-if="template.configuration?.queries" class="text-xs text-gray-500">
                    <strong>Includes:</strong>
                    <span class="ml-1">
                      {{ Object.keys(template.configuration.queries).map(formatQueryType).join(', ') }}
                    </span>
                  </div>
                  
                  <div class="flex items-center text-xs text-gray-500">
                    <CircleStackIcon class="w-3 h-3 mr-1" />
                    {{ template.database_type.toUpperCase() }}
                    
                    <span class="mx-2">•</span>
                    
                    <ClockIcon class="w-3 h-3 mr-1" />
                    {{ formatDate(template.created_at) }}
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Template Preview -->
        <div v-if="selectedTemplate" class="mt-6 border-t border-gray-200 pt-6">
          <h4 class="text-md font-medium text-gray-900 mb-4">Template Preview</h4>
          
          <div class="bg-gray-50 rounded-lg p-4">
            <div v-if="selectedTemplate.configuration?.queries" class="space-y-4">
              <div v-for="(query, queryType) in selectedTemplate.configuration.queries" 
                   :key="queryType"
                   class="bg-white rounded border p-4">
                <div class="flex items-center justify-between mb-2">
                  <h5 class="text-sm font-medium text-gray-900">{{ query.name || formatQueryType(queryType) }}</h5>
                  <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
                    {{ query.target_type || queryType }}
                  </span>
                </div>
                
                <p class="text-xs text-gray-600 mb-3">{{ query.description }}</p>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs">
                  <div>
                    <span class="font-medium text-gray-700">Base Table:</span>
                    <span class="ml-1 text-gray-600">{{ query.base_table }}</span>
                  </div>
                  
                  <div v-if="query.joins?.length">
                    <span class="font-medium text-gray-700">JOINs:</span>
                    <span class="ml-1 text-gray-600">{{ query.joins.length }} table{{ query.joins.length === 1 ? '' : 's' }}</span>
                  </div>
                  
                  <div v-if="query.fields?.length">
                    <span class="font-medium text-gray-700">Fields:</span>
                    <span class="ml-1 text-gray-600">{{ query.fields.length }} field{{ query.fields.length === 1 ? '' : 's' }}</span>
                  </div>
                </div>
              </div>
            </div>
            
            <div v-else class="text-center py-4 text-gray-500 text-sm">
              No configuration preview available
            </div>
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-else class="text-center py-12">
        <DocumentMagnifyingGlassIcon class="mx-auto h-12 w-12 text-gray-400" />
        <h3 class="mt-2 text-sm font-medium text-gray-900">No Templates Available</h3>
        <p class="mt-1 text-sm text-gray-500">
          No import templates are currently available for your database type.
        </p>
      </div>

      <!-- Error State -->
      <div v-if="error" class="rounded-md bg-red-50 p-4">
        <div class="flex">
          <div class="flex-shrink-0">
            <XCircleIcon class="h-5 w-5 text-red-400" />
          </div>
          <div class="ml-3">
            <h3 class="text-sm font-medium text-red-800">Failed to Load Templates</h3>
            <div class="mt-2 text-sm text-red-700">
              <p>{{ error }}</p>
            </div>
            <div class="mt-3">
              <button
                @click="loadTemplates"
                class="bg-red-100 px-2 py-1 rounded text-xs font-medium text-red-800 hover:bg-red-200"
              >
                Try Again
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Footer Actions -->
    <template #footer>
      <div class="flex items-center justify-end space-x-3">
        <button
          @click="$emit('close')"
          class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
        >
          Cancel
        </button>
        
        <button
          @click="applyTemplate"
          :disabled="!selectedTemplate || isApplying"
          class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          <span v-if="isApplying" class="inline-block animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2"></span>
          <CheckIcon v-else class="w-4 h-4 mr-2" />
          {{ isApplying ? 'Applying...' : 'Apply Template' }}
        </button>
      </div>
    </template>
  </StackedDialog>
</template>

<script setup>
import { ref, onMounted, computed, watch } from 'vue'
import axios from 'axios'
import StackedDialog from '@/Components/StackedDialog.vue'
import {
  InformationCircleIcon,
  ArrowPathIcon,
  CheckIcon,
  CircleStackIcon,
  ClockIcon,
  DocumentMagnifyingGlassIcon,
  XCircleIcon,
  ServerIcon,
  CogIcon,
  DocumentTextIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
  show: Boolean,
  profile: {
    type: Object,
    default: null
  }
})

const emit = defineEmits(['close', 'template-applied'])

// State
const templates = ref([])
const selectedTemplate = ref(null)
const isLoading = ref(false)
const isApplying = ref(false)
const error = ref(null)

// Methods
const loadTemplates = async () => {
  if (!props.profile) {
    return
  }
  
  isLoading.value = true
  error.value = null
  
  try {
    const response = await axios.get('/api/import/templates', {
      params: {
        database_type: props.profile.database_type,
        active: true
      }
    })
    
    templates.value = response.data.data || response.data || []
    
    // Auto-select the first template if only one available
    if (templates.value.length === 1) {
      selectedTemplate.value = templates.value[0]
    }
    
  } catch (err) {
    console.error('Failed to load templates:', err)
    error.value = err.response?.data?.message || err.message || 'Failed to load templates'
  } finally {
    isLoading.value = false
  }
}

const selectTemplate = (template) => {
  selectedTemplate.value = template
}

const applyTemplate = async () => {
  if (!selectedTemplate.value || !props.profile) return
  
  isApplying.value = true
  
  try {
    const response = await axios.put(`/api/import/profiles/${props.profile.id}/template`, {
      template_id: selectedTemplate.value.id
    })
    
    emit('template-applied', {
      profile: response.data.profile,
      template: selectedTemplate.value
    })
    
    emit('close')
    
  } catch (err) {
    console.error('Failed to apply template:', err)
    const errorMessage = err.response?.data?.message || err.message || 'Failed to apply template'
    alert('Error: ' + errorMessage)
  } finally {
    isApplying.value = false
  }
}

// Helper methods
const getTemplateIcon = (platform) => {
  switch (platform) {
    case 'freescout': return ServerIcon
    case 'custom': return CogIcon
    default: return DocumentTextIcon
  }
}

const getTemplateIconClass = (platform) => {
  switch (platform) {
    case 'freescout': return 'bg-blue-100 text-blue-600'
    case 'custom': return 'bg-gray-100 text-gray-600'
    default: return 'bg-purple-100 text-purple-600'
  }
}

const getPlatformBadgeClass = (platform) => {
  switch (platform) {
    case 'freescout': return 'bg-blue-100 text-blue-800'
    case 'custom': return 'bg-gray-100 text-gray-800'
    default: return 'bg-purple-100 text-purple-800'
  }
}

const formatPlatform = (platform) => {
  switch (platform) {
    case 'freescout': return 'FreeScout'
    case 'custom': return 'Custom'
    default: return platform.charAt(0).toUpperCase() + platform.slice(1)
  }
}

const formatQueryType = (queryType) => {
  return queryType.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())
}

const formatDate = (dateString) => {
  if (!dateString) return 'Unknown'
  return new Date(dateString).toLocaleDateString()
}

// Initialize
onMounted(() => {
  if (props.show && props.profile) {
    loadTemplates()
  }
})

// Watch for modal show changes
watch(() => props.show, (show) => {
  if (show && props.profile) {
    loadTemplates()
  } else {
    // Reset state when modal closes
    selectedTemplate.value = null
    error.value = null
  }
})

// Watch for profile changes
watch(() => props.profile, (profile) => {
  if (profile && props.show) {
    loadTemplates()
  }
})
</script>