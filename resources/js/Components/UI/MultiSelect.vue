<template>
  <div class="relative" ref="containerRef">
    <button
      @click="toggleDropdown"
      type="button"
      class="relative w-full bg-white border border-gray-300 rounded-lg px-3 py-2 text-sm text-left cursor-pointer focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
      :class="{ 'border-blue-500 ring-2 ring-blue-500': isOpen }"
    >
      <span class="block truncate">
        {{ displayText }}
      </span>
      <span class="absolute inset-y-0 right-0 flex items-center pr-2 pointer-events-none">
        <svg 
          class="h-4 w-4 text-gray-400 transition-transform"
          :class="{ 'rotate-180': isOpen }"
          fill="none" 
          stroke="currentColor" 
          viewBox="0 0 24 24"
        >
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
        </svg>
      </span>
    </button>

    <!-- Dropdown -->
    <div
      v-if="isOpen"
      class="absolute z-50 mt-1 w-full bg-white shadow-lg max-h-60 rounded-lg border border-gray-200 overflow-auto focus:outline-none"
    >
      <!-- Select All / Clear All -->
      <div class="px-3 py-2 border-b border-gray-100 bg-gray-50">
        <div class="flex justify-between items-center">
          <button
            @click="selectAll"
            type="button"
            class="text-xs text-blue-600 hover:text-blue-700 font-medium"
          >
            Select All
          </button>
          <button
            @click="clearAll"
            type="button"
            class="text-xs text-gray-600 hover:text-gray-700 font-medium"
          >
            Clear All
          </button>
        </div>
      </div>

      <!-- Options -->
      <div class="py-1">
        <div
          v-for="option in options"
          :key="getOptionValue(option)"
          @click="toggleOption(option)"
          class="flex items-center px-3 py-2 hover:bg-gray-100 cursor-pointer"
        >
          <div class="flex items-center h-5">
            <input
              :checked="isSelected(option)"
              type="checkbox"
              class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded pointer-events-none"
            >
          </div>
          <div class="ml-3 flex-1">
            <span class="block text-sm text-gray-900">
              {{ getOptionLabel(option) }}
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'

// Props
const props = defineProps({
  modelValue: {
    type: Array,
    default: () => []
  },
  options: {
    type: Array,
    required: true
  },
  placeholder: {
    type: String,
    default: 'Select options...'
  },
  valueKey: {
    type: String,
    default: 'value'
  },
  labelKey: {
    type: String,
    default: 'label'
  },
  maxDisplayItems: {
    type: Number,
    default: 2
  }
})

// Emits
const emit = defineEmits(['update:modelValue'])

// State
const isOpen = ref(false)
const containerRef = ref(null)

// Computed
const selectedValues = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value)
})

const displayText = computed(() => {
  if (selectedValues.value.length === 0) {
    return props.placeholder
  }
  
  if (selectedValues.value.length === props.options.length) {
    return 'All selected'
  }
  
  if (selectedValues.value.length <= props.maxDisplayItems) {
    return selectedValues.value
      .map(value => {
        const option = props.options.find(opt => getOptionValue(opt) === value)
        return option ? getOptionLabel(option) : value
      })
      .join(', ')
  }
  
  return `${selectedValues.value.length} selected`
})

// Methods
const getOptionValue = (option) => {
  return typeof option === 'object' ? option[props.valueKey] : option
}

const getOptionLabel = (option) => {
  return typeof option === 'object' ? option[props.labelKey] : option
}

const isSelected = (option) => {
  const value = getOptionValue(option)
  return selectedValues.value.includes(value)
}

const toggleOption = (option) => {
  const value = getOptionValue(option)
  const currentValues = [...selectedValues.value]
  
  if (currentValues.includes(value)) {
    selectedValues.value = currentValues.filter(v => v !== value)
  } else {
    selectedValues.value = [...currentValues, value]
  }
}

const selectAll = () => {
  selectedValues.value = props.options.map(option => getOptionValue(option))
}

const clearAll = () => {
  selectedValues.value = []
}

const toggleDropdown = () => {
  isOpen.value = !isOpen.value
}

const closeDropdown = () => {
  isOpen.value = false
}

// Click outside to close
const handleClickOutside = (event) => {
  if (containerRef.value && !containerRef.value.contains(event.target)) {
    closeDropdown()
  }
}

// Lifecycle
onMounted(() => {
  document.addEventListener('click', handleClickOutside)
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
})
</script>