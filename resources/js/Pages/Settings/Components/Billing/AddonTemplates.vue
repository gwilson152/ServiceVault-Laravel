<template>
  <div class="space-y-8">
    <!-- Addon Templates Header -->
    <div class="flex items-center justify-between">
      <div>
        <h3 class="text-lg font-medium text-gray-900">Addon Templates</h3>
        <p class="text-gray-600 mt-1">Create reusable templates for common addons and services</p>
      </div>
      <button
        @click="showCreateModal = true"
        type="button"
        class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-indigo-700 bg-indigo-100 hover:bg-indigo-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
      >
        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
        </svg>
        Add New Template
      </button>
    </div>

    <!-- Addon Categories -->
    <div v-if="addonCategories" class="bg-blue-50 border border-blue-200 rounded-lg p-4">
      <div class="text-sm font-medium text-blue-900 mb-3">Available Categories:</div>
      <div class="flex flex-wrap gap-2">
        <span 
          v-for="(label, key) in addonCategories" 
          :key="key"
          class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800"
        >
          {{ label }}
        </span>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="isLoading" class="flex justify-center py-8">
      <svg class="animate-spin h-8 w-8 text-indigo-600" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
      </svg>
    </div>

    <!-- Addon Templates List -->
    <div v-else-if="addonTemplates && addonTemplates.length > 0" class="bg-white border border-gray-200 rounded-lg">
      <div class="space-y-0 divide-y divide-gray-200">
        <div 
          v-for="template in addonTemplates" 
          :key="template.id"
          class="flex items-center justify-between p-6 hover:bg-gray-50"
        >
          <div class="flex-1">
            <div class="flex items-center mb-1">
              <h4 class="text-sm font-medium text-gray-900">{{ template.name }}</h4>
              <span class="ml-2 inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                {{ getCategoryLabel(template.category) }}
              </span>
            </div>
            <p class="text-xs text-gray-500">{{ template.description }}</p>
            <div class="text-xs text-gray-500 mt-1 space-x-4">
              <span v-if="template.sku">SKU: {{ template.sku }}</span>
              <span>Qty: {{ template.default_quantity }}</span>
              <span v-if="template.is_billable" class="text-green-600">Billable</span>
              <span v-if="template.is_taxable" class="text-blue-600">Taxable</span>
            </div>
          </div>
          <div class="flex items-center space-x-4">
            <div class="text-right">
              <div class="text-lg font-semibold text-gray-900">${{ template.default_unit_price }}</div>
              <div class="text-xs text-gray-500">per unit</div>
            </div>
            <div>
              <span :class="[
                'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                template.is_active 
                  ? 'bg-green-100 text-green-800' 
                  : 'bg-gray-100 text-gray-800'
              ]">
                {{ template.is_active ? 'Active' : 'Inactive' }}
              </span>
            </div>
            <div class="flex items-center space-x-2">
              <button
                @click="editTemplate(template)"
                class="inline-flex items-center px-2 py-1 text-xs font-medium text-indigo-700 bg-indigo-100 rounded hover:bg-indigo-200"
              >
                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
                Edit
              </button>
              <button
                @click="deleteTemplate(template)"
                class="inline-flex items-center px-2 py-1 text-xs font-medium text-red-700 bg-red-100 rounded hover:bg-red-200"
              >
                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
                Delete
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Empty State -->
    <div v-else class="bg-white border border-gray-200 rounded-lg p-12 text-center">
      <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a1 1 0 011-1h6a1 1 0 011 1v2M7 7h10"/>
      </svg>
      <h3 class="mt-2 text-sm font-medium text-gray-900">No addon templates configured</h3>
      <p class="mt-1 text-sm text-gray-500">Get started by creating your first addon template.</p>
      <div class="mt-6">
        <button
          @click="showCreateModal = true"
          class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
        >
          <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
          </svg>
          Create Template
        </button>
      </div>
    </div>

    <!-- Create/Edit Modal -->
    <AddonTemplateModal
      :show="showCreateModal || showEditModal"
      :template="selectedTemplate"
      @close="closeModal"
      @saved="handleTemplateSaved"
    />
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useAddonTemplatesQuery, useDeleteAddonTemplateMutation } from '@/Composables/queries/useAddonTemplatesQuery'
import AddonTemplateModal from './AddonTemplateModal.vue'

// TanStack Query hooks
const { data: addonTemplates, isLoading } = useAddonTemplatesQuery()
const deleteTemplateMutation = useDeleteAddonTemplateMutation()

// Modal state
const showCreateModal = ref(false)
const showEditModal = ref(false)
const selectedTemplate = ref(null)

// Addon categories
const addonCategories = computed(() => {
  return {
    product: 'Product',
    service: 'Service', 
    license: 'License',
    hardware: 'Hardware',
    software: 'Software',
    expense: 'Expense',
    other: 'Other'
  }
})

// Get category label
const getCategoryLabel = (categoryKey) => {
  return addonCategories.value[categoryKey] || categoryKey
}

// Edit template
const editTemplate = (template) => {
  selectedTemplate.value = template
  showEditModal.value = true
}

// Delete template
const deleteTemplate = async (template) => {
  if (!confirm(`Are you sure you want to delete the addon template "${template.name}"?`)) return
  
  try {
    await deleteTemplateMutation.mutateAsync(template.id)
    console.log('Addon template deleted successfully')
  } catch (error) {
    console.error('Failed to delete addon template:', error)
    alert('Failed to delete addon template. Please try again.')
  }
}

// Close modal
const closeModal = () => {
  showCreateModal.value = false
  showEditModal.value = false
  selectedTemplate.value = null
}

// Handle template saved
const handleTemplateSaved = () => {
  closeModal()
}
</script>