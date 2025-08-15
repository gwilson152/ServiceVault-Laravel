<template>
  <div class="space-y-8">
    <!-- Advanced Settings Header -->
    <div>
      <h2 class="text-2xl font-semibold text-gray-900">Advanced Settings</h2>
      <p class="text-gray-600 mt-2">Developer and debugging options for advanced users.</p>
    </div>

    <!-- Debug & Development Tools -->
    <div class="bg-white border border-gray-200 rounded-lg p-6">
      <h3 class="text-lg font-medium text-gray-900 mb-6">Debug & Development</h3>
      
      <div class="space-y-6">
        <div class="flex items-center justify-between">
          <div>
            <label class="text-sm font-medium text-gray-700">Debug Timer Overlay</label>
            <p class="text-xs text-gray-500 mt-1">Show a draggable debug overlay with timer permissions and computed values</p>
          </div>
          <div class="flex items-center">
            <input 
              type="checkbox" 
              id="show_debug_overlay"
              v-model="form.show_debug_overlay"
              class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
            />
            <label for="show_debug_overlay" class="ml-2 text-sm text-gray-600">
              {{ form.show_debug_overlay ? 'Enabled' : 'Disabled' }}
            </label>
          </div>
        </div>

        <div class="flex items-center justify-between">
          <div>
            <label class="text-sm font-medium text-gray-700">Debug Permissions Overlay</label>
            <p class="text-xs text-gray-500 mt-1">Show a draggable debug overlay with user data, roles, and all permissions</p>
          </div>
          <div class="flex items-center">
            <input 
              type="checkbox" 
              id="show_permissions_debug_overlay"
              v-model="form.show_permissions_debug_overlay"
              class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
            />
            <label for="show_permissions_debug_overlay" class="ml-2 text-sm text-gray-600">
              {{ form.show_permissions_debug_overlay ? 'Enabled' : 'Disabled' }}
            </label>
          </div>
        </div>

        <div class="bg-amber-50 rounded-lg p-4 border border-amber-200">
          <div class="flex">
            <div class="flex-shrink-0">
              <svg class="h-5 w-5 text-amber-400" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
              </svg>
            </div>
            <div class="ml-3">
              <h3 class="text-sm font-medium text-amber-800">Developer Feature</h3>
              <div class="mt-2 text-sm text-amber-700">
                <p>The debug overlay is intended for development and troubleshooting. It shows internal timer state, permissions, and computed values that help diagnose issues with timer functionality.</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Success Message -->
    <div v-if="showSuccessMessage" class="bg-green-50 border border-green-200 rounded-md p-4">
      <div class="flex">
        <div class="flex-shrink-0">
          <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
          </svg>
        </div>
        <div class="ml-3">
          <p class="text-sm font-medium text-green-800">
            Advanced settings saved successfully!
          </p>
          <p class="text-xs text-green-600 mt-1">
            Debug overlays will reflect the new settings immediately.
          </p>
        </div>
      </div>
    </div>

    <!-- Actions -->
    <div class="flex justify-end">
      <button
        type="button"
        @click="saveSettings"
        :disabled="loading"
        :class="[
          'inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md shadow-sm',
          loading
            ? 'bg-gray-300 text-gray-500 cursor-not-allowed'
            : 'bg-indigo-600 text-white hover:bg-indigo-700 focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500'
        ]"
      >
        <span v-if="loading" class="mr-2">
          <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
        </span>
        {{ loading ? 'Saving...' : 'Save Advanced Settings' }}
      </button>
    </div>
  </div>
</template>

<script setup>
import { reactive, ref, watch } from 'vue'

const props = defineProps({
  settings: {
    type: Object,
    default: () => ({})
  },
  loading: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['update'])

// Success message state
const showSuccessMessage = ref(false)

// Form data with defaults
const form = reactive({
  show_debug_overlay: localStorage.getItem('debug_overlay_enabled') === 'true',
  show_permissions_debug_overlay: localStorage.getItem('permissions_debug_overlay_enabled') === 'true',
})

// Watch for settings prop changes
watch(() => props.settings, (newSettings) => {
  Object.assign(form, newSettings)
}, { immediate: true, deep: true })

// Save settings
const saveSettings = () => {
  emit('update', { ...form })
  
  // Show success message
  showSuccessMessage.value = true
  
  // Hide success message after 3 seconds
  setTimeout(() => {
    showSuccessMessage.value = false
  }, 3000)
}
</script>