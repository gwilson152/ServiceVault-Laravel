<template>
  <div class="relative inline-block">
    <!-- Trigger Button -->
    <button
      @click="showDropdown = !showDropdown"
      class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
    >
      <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2h2a2 2 0 002-2z" />
      </svg>
      Columns
      <svg class="w-4 h-4 ml-2" :class="{ 'rotate-180': showDropdown }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
      </svg>
    </button>

    <!-- Dropdown -->
    <div
      v-if="showDropdown"
      class="absolute right-0 z-50 mt-2 w-56 origin-top-right bg-white border border-gray-200 rounded-md shadow-lg"
    >
      <div class="py-1">
        <div class="px-4 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wider border-b border-gray-100">
          Show/Hide Columns
        </div>
        
        <div class="max-h-64 overflow-y-auto">
          <label
            v-for="column in availableColumns"
            :key="column.id"
            class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 cursor-pointer"
          >
            <input
              type="checkbox"
              :checked="isColumnVisible(column.id)"
              @change="$emit('toggle-column', column.id)"
              class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
            />
            <span class="ml-3 flex-1">{{ column.label }}</span>
          </label>
        </div>
        
        <div class="border-t border-gray-100">
          <button
            @click="$emit('reset-columns')"
            class="w-full px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 text-left"
          >
            Reset to Default
          </button>
        </div>
      </div>
    </div>

    <!-- Click outside to close -->
    <div
      v-if="showDropdown"
      class="fixed inset-0 z-40"
      @click="showDropdown = false"
    ></div>
  </div>
</template>

<script setup>
import { ref } from 'vue'

defineProps({
  availableColumns: {
    type: Array,
    required: true
  },
  isColumnVisible: {
    type: Function,
    required: true
  }
})

defineEmits(['toggle-column', 'reset-columns'])

const showDropdown = ref(false)
</script>