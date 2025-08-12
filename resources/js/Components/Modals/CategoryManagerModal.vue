<template>
  <Modal :show="show" @close="close" max-width="lg">
    <div class="px-6 py-4">
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-semibold text-gray-900">
          {{ editingCategory ? 'Edit Category' : 'Add Category' }}
        </h3>
        <button @click="close" class="text-gray-400 hover:text-gray-600">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <form @submit.prevent="handleSubmit" class="space-y-4">
        <!-- Key Field -->
        <div>
          <label for="key" class="block text-sm font-medium text-gray-700 mb-1">
            Key <span class="text-red-500">*</span>
          </label>
          <input
            id="key"
            v-model="form.key"
            type="text"
            required
            :disabled="!!editingCategory"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 disabled:bg-gray-100"
            placeholder="e.g., support, bug, feature, maintenance"
          >
          <p class="text-xs text-gray-500 mt-1">
            Unique identifier for the category (cannot be changed after creation)
          </p>
        </div>

        <!-- Name Field -->
        <div>
          <label for="name" class="block text-sm font-medium text-gray-700 mb-1">
            Name <span class="text-red-500">*</span>
          </label>
          <input
            id="name"
            v-model="form.name"
            type="text"
            required
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            placeholder="e.g., Support Request, Bug Report, Feature Request"
          >
        </div>

        <!-- Description Field -->
        <div>
          <label for="description" class="block text-sm font-medium text-gray-700 mb-1">
            Description
          </label>
          <textarea
            id="description"
            v-model="form.description"
            rows="3"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            placeholder="Description of what this category covers"
          ></textarea>
        </div>

        <!-- Color Field -->
        <div>
          <label for="color" class="block text-sm font-medium text-gray-700 mb-1">
            Color <span class="text-red-500">*</span>
          </label>
          <div class="flex items-center space-x-3">
            <input
              id="color"
              v-model="form.color"
              type="color"
              required
              class="w-16 h-10 border border-gray-300 rounded cursor-pointer"
            >
            <input
              v-model="form.color"
              type="text"
              pattern="^#[0-9A-Fa-f]{6}$"
              class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              placeholder="#000000"
            >
            <div
              class="w-10 h-10 rounded border border-gray-300"
              :style="{ backgroundColor: form.color }"
            ></div>
          </div>
        </div>

        <!-- SLA and Estimation Row -->
        <div class="grid grid-cols-2 gap-4">
          <!-- SLA Hours -->
          <div>
            <label for="sla_hours" class="block text-sm font-medium text-gray-700 mb-1">
              SLA Hours
            </label>
            <input
              id="sla_hours"
              v-model.number="form.sla_hours"
              type="number"
              min="1"
              step="1"
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              placeholder="24"
            >
            <p class="text-xs text-gray-500 mt-1">Target resolution time in hours</p>
          </div>

          <!-- Default Estimated Hours -->
          <div>
            <label for="default_estimated_hours" class="block text-sm font-medium text-gray-700 mb-1">
              Estimated Hours
            </label>
            <input
              id="default_estimated_hours"
              v-model.number="form.default_estimated_hours"
              type="number"
              min="0.5"
              step="0.5"
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              placeholder="2.0"
            >
            <p class="text-xs text-gray-500 mt-1">Default work estimate for this type</p>
          </div>
        </div>

        <!-- Sort Order -->
        <div>
          <label for="sort_order" class="block text-sm font-medium text-gray-700 mb-1">
            Sort Order
          </label>
          <input
            id="sort_order"
            v-model.number="form.sort_order"
            type="number"
            min="0"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            placeholder="Display order (optional)"
          >
        </div>

        <!-- Checkboxes -->
        <div class="space-y-3">
          <div class="flex items-center">
            <input
              id="is_active"
              v-model="form.is_active"
              type="checkbox"
              class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
            >
            <label for="is_active" class="ml-2 block text-sm text-gray-700">
              Active
            </label>
          </div>

          <div class="flex items-center">
            <input
              id="is_default"
              v-model="form.is_default"
              type="checkbox"
              class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
            >
            <label for="is_default" class="ml-2 block text-sm text-gray-700">
              Default Category (for new tickets)
            </label>
          </div>

          <div class="flex items-center">
            <input
              id="requires_approval"
              v-model="form.requires_approval"
              type="checkbox"
              class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
            >
            <label for="requires_approval" class="ml-2 block text-sm text-gray-700">
              Requires Approval (tickets need manager approval before work begins)
            </label>
          </div>

          <div class="flex items-center">
            <input
              id="auto_assign"
              v-model="form.auto_assign"
              type="checkbox"
              class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
            >
            <label for="auto_assign" class="ml-2 block text-sm text-gray-700">
              Auto-assign (automatically assign to available team members)
            </label>
          </div>
        </div>

        <!-- Form Actions -->
        <div class="flex items-center justify-end space-x-3 pt-6 border-t border-gray-200">
          <button
            type="button"
            @click="close"
            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
          >
            Cancel
          </button>
          <button
            type="submit"
            :disabled="processing"
            class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <span v-if="processing" class="flex items-center">
              <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              Saving...
            </span>
            <span v-else>
              {{ editingCategory ? 'Update Category' : 'Create Category' }}
            </span>
          </button>
        </div>
      </form>
    </div>
  </Modal>
</template>

<script setup>
import { ref, reactive, watch } from 'vue'
import Modal from '@/Components/Modal.vue'

const props = defineProps({
  show: {
    type: Boolean,
    default: false
  },
  editingCategory: {
    type: Object,
    default: null
  }
})

const emit = defineEmits(['close', 'saved'])

const processing = ref(false)

const form = reactive({
  key: '',
  name: '',
  description: '',
  color: '#10B981',
  sla_hours: null,
  default_estimated_hours: null,
  sort_order: null,
  is_active: true,
  is_default: false,
  requires_approval: false,
  auto_assign: false
})

// Reset form when modal opens/closes or editing category changes
watch([() => props.show, () => props.editingCategory], () => {
  if (props.show) {
    resetForm()
  }
}, { immediate: true })

const resetForm = () => {
  if (props.editingCategory) {
    // Populate form with existing category data
    Object.assign(form, {
      key: props.editingCategory.key || '',
      name: props.editingCategory.name || '',
      description: props.editingCategory.description || '',
      color: props.editingCategory.color || '#10B981',
      sla_hours: props.editingCategory.sla_hours,
      default_estimated_hours: props.editingCategory.default_estimated_hours,
      sort_order: props.editingCategory.sort_order,
      is_active: props.editingCategory.is_active ?? true,
      is_default: props.editingCategory.is_default ?? false,
      requires_approval: props.editingCategory.requires_approval ?? false,
      auto_assign: props.editingCategory.auto_assign ?? false
    })
  } else {
    // Reset to defaults for new category
    Object.assign(form, {
      key: '',
      name: '',
      description: '',
      color: '#10B981',
      sla_hours: null,
      default_estimated_hours: null,
      sort_order: null,
      is_active: true,
      is_default: false,
      requires_approval: false,
      auto_assign: false
    })
  }
}

const close = () => {
  emit('close')
}

const handleSubmit = async () => {
  processing.value = true
  
  try {
    const url = props.editingCategory 
      ? `/api/ticket-categories/${props.editingCategory.id}`
      : '/api/ticket-categories'
    
    const method = props.editingCategory ? 'PUT' : 'POST'
    
    const response = await fetch(url, {
      method,
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.head.querySelector('meta[name="csrf-token"]').content
      },
      body: JSON.stringify(form)
    })
    
    const data = await response.json()
    
    if (response.ok) {
      emit('saved', data.data)
      close()
    } else {
      console.error('Failed to save category:', data.message || 'Unknown error')
    }
  } catch (error) {
    console.error('Error saving category:', error)
  } finally {
    processing.value = false
  }
}
</script>