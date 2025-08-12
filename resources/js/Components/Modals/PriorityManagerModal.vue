<template>
  <Modal :show="show" @close="close" max-width="lg">
    <div class="px-6 py-4">
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-semibold text-gray-900">
          {{ editingPriority ? 'Edit Priority' : 'Add Priority' }}
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
            :disabled="!!editingPriority"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 disabled:bg-gray-100"
            placeholder="e.g., low, normal, high, urgent"
          >
          <p class="text-xs text-gray-500 mt-1">
            Unique identifier for the priority (cannot be changed after creation)
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
            placeholder="e.g., Low, Normal, High, Urgent"
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
            placeholder="Optional description of this priority level"
          ></textarea>
        </div>

        <!-- Color and Icon Row -->
        <div class="grid grid-cols-2 gap-4">
          <!-- Color Field -->
          <div>
            <label for="color" class="block text-sm font-medium text-gray-700 mb-1">
              Color <span class="text-red-500">*</span>
            </label>
            <div class="flex items-center space-x-2">
              <input
                id="color"
                v-model="form.color"
                type="color"
                required
                class="w-12 h-10 border border-gray-300 rounded cursor-pointer"
              >
              <input
                v-model="form.color"
                type="text"
                pattern="^#[0-9A-Fa-f]{6}$"
                class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                placeholder="#000000"
              >
            </div>
          </div>

          <!-- Icon Field -->
          <div>
            <label for="icon" class="block text-sm font-medium text-gray-700 mb-1">
              Icon
            </label>
            <select
              id="icon"
              v-model="form.icon"
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            >
              <option value="">No Icon</option>
              <option value="🔻">🔻 Low</option>
              <option value="⚪">⚪ Normal</option>
              <option value="🔶">🔶 High</option>
              <option value="🔺">🔺 Urgent</option>
              <option value="⚠️">⚠️ Warning</option>
              <option value="🚨">🚨 Critical</option>
            </select>
          </div>
        </div>

        <!-- Severity and Escalation Row -->
        <div class="grid grid-cols-2 gap-4">
          <!-- Severity Level -->
          <div>
            <label for="severity_level" class="block text-sm font-medium text-gray-700 mb-1">
              Severity Level <span class="text-red-500">*</span>
            </label>
            <input
              id="severity_level"
              v-model.number="form.severity_level"
              type="number"
              min="1"
              max="10"
              required
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              placeholder="1-10 (1=lowest, 10=highest)"
            >
            <p class="text-xs text-gray-500 mt-1">1 = Lowest priority, 10 = Highest priority</p>
          </div>

          <!-- Escalation Multiplier -->
          <div>
            <label for="escalation_multiplier" class="block text-sm font-medium text-gray-700 mb-1">
              SLA Multiplier <span class="text-red-500">*</span>
            </label>
            <input
              id="escalation_multiplier"
              v-model.number="form.escalation_multiplier"
              type="number"
              step="0.1"
              min="0.1"
              max="10"
              required
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              placeholder="1.0"
            >
            <p class="text-xs text-gray-500 mt-1">SLA time multiplier (0.5 = faster, 2.0 = slower)</p>
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
              Default Priority (for new tickets)
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
              {{ editingPriority ? 'Update Priority' : 'Create Priority' }}
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
  editingPriority: {
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
  color: '#6B7280',
  bg_color: null,
  icon: '',
  is_active: true,
  is_default: false,
  severity_level: 1,
  escalation_multiplier: 1.0,
  sort_order: null,
  metadata: {}
})

// Reset form when modal opens/closes or editing priority changes
watch([() => props.show, () => props.editingPriority], () => {
  if (props.show) {
    resetForm()
  }
}, { immediate: true })

const resetForm = () => {
  if (props.editingPriority) {
    // Populate form with existing priority data
    Object.assign(form, {
      key: props.editingPriority.key || '',
      name: props.editingPriority.name || '',
      description: props.editingPriority.description || '',
      color: props.editingPriority.color || '#6B7280',
      bg_color: props.editingPriority.bg_color,
      icon: props.editingPriority.icon || '',
      is_active: props.editingPriority.is_active ?? true,
      is_default: props.editingPriority.is_default ?? false,
      severity_level: props.editingPriority.severity_level || 1,
      escalation_multiplier: props.editingPriority.escalation_multiplier || 1.0,
      sort_order: props.editingPriority.sort_order,
      metadata: props.editingPriority.metadata || {}
    })
  } else {
    // Reset to defaults for new priority
    Object.assign(form, {
      key: '',
      name: '',
      description: '',
      color: '#6B7280',
      bg_color: null,
      icon: '',
      is_active: true,
      is_default: false,
      severity_level: 1,
      escalation_multiplier: 1.0,
      sort_order: null,
      metadata: {}
    })
  }
}

const close = () => {
  emit('close')
}

const handleSubmit = async () => {
  processing.value = true
  
  try {
    const url = props.editingPriority 
      ? `/api/ticket-priorities/${props.editingPriority.id}`
      : '/api/ticket-priorities'
    
    const method = props.editingPriority ? 'PUT' : 'POST'
    
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
      console.error('Failed to save priority:', data.message || 'Unknown error')
    }
  } catch (error) {
    console.error('Error saving priority:', error)
  } finally {
    processing.value = false
  }
}
</script>