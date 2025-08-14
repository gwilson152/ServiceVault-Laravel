<template>
  <div class="relative" :id="selectorId">
    <!-- Selected Rate Display -->
    <div 
      v-if="selectedRate"
      class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-white flex items-center justify-between cursor-pointer hover:border-blue-500 transition-colors"
      @click="clearSelection"
    >
      <div class="flex items-center space-x-2">
        <div class="flex items-center space-x-1">
          <span class="font-medium">{{ selectedRate.name }}</span>
          <span class="text-gray-500 dark:text-gray-400">- ${{ selectedRate.rate }}/hr</span>
          <span v-if="selectedRate.is_default" class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-400">
            Default
          </span>
        </div>
      </div>
      <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
      </svg>
    </div>

    <!-- Search Input (only show when no selection) -->
    <div v-if="!selectedRate" class="relative">
      <input
        :id="inputId"
        v-model="searchTerm"
        type="text"
        :placeholder="placeholder"
        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
        @focus="isOpen = true"
        @input="isOpen = true"
        @keydown.escape="closeDropdown"
        @keydown.arrow-down.prevent="navigateDown"
        @keydown.arrow-up.prevent="navigateUp"
        @keydown.enter.prevent="selectHighlighted"
      />
      
      <!-- Loading/Clear Icon -->
      <div class="absolute inset-y-0 right-0 pr-3 flex items-center">
        <div v-if="isLoading" class="animate-spin rounded-full h-4 w-4 border-b-2 border-blue-500"></div>
        <svg v-else class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
        </svg>
      </div>
    </div>

    <!-- Dropdown List -->
    <div 
      v-if="isOpen && !selectedRate"
      :class="[
        'absolute z-50 w-full mt-1 bg-white dark:bg-gray-800 shadow-lg border border-gray-200 dark:border-gray-600 rounded-md max-h-60 overflow-auto',
        dropupMode ? 'bottom-full mb-1' : 'top-full mt-1'
      ]"
    >
      <div v-if="filteredRates.length === 0" class="px-3 py-2 text-sm text-gray-500 dark:text-gray-400">
        {{ isLoading ? 'Loading billing rates...' : 'No billing rates found' }}
      </div>
      
      <div
        v-for="(rate, index) in filteredRates"
        :key="rate.id"
        :class="[
          'px-3 py-2 cursor-pointer text-sm transition-colors',
          highlightedIndex === index 
            ? 'bg-blue-50 dark:bg-blue-900/20 text-blue-900 dark:text-blue-100' 
            : 'text-gray-900 dark:text-gray-100 hover:bg-gray-50 dark:hover:bg-gray-700'
        ]"
        @click="selectRate(rate)"
        @mouseenter="highlightedIndex = index"
      >
        <div class="flex items-center justify-between">
          <div class="flex items-center space-x-2">
            <span class="font-medium">{{ rate.name }}</span>
            <span class="text-gray-500 dark:text-gray-400">- ${{ rate.rate }}/hr</span>
          </div>
          <div class="flex items-center space-x-1">
            <span v-if="rate.is_default" class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-400">
              Default
            </span>
          </div>
        </div>
        <div v-if="rate.description" class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
          {{ rate.description }}
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted, nextTick } from 'vue'

const props = defineProps({
  modelValue: {
    type: [String, Number],
    default: null
  },
  rates: {
    type: Array,
    default: () => []
  },
  isLoading: {
    type: Boolean,
    default: false
  },
  placeholder: {
    type: String,
    default: 'Select billing rate...'
  }
})

const emit = defineEmits(['update:modelValue', 'rate-selected'])

// Generate unique IDs for accessibility
const selectorId = `billing-rate-selector-${Math.random().toString(36).substr(2, 9)}`
const inputId = `billing-rate-input-${Math.random().toString(36).substr(2, 9)}`

// Component state
const isOpen = ref(false)
const searchTerm = ref('')
const highlightedIndex = ref(0)
const dropupMode = ref(false)

// Find selected rate
const selectedRate = computed(() => {
  if (!props.modelValue || !props.rates) return null
  return props.rates.find(rate => rate.id == props.modelValue) || null
})

// Filter rates based on search
const filteredRates = computed(() => {
  if (!props.rates) return []
  
  if (!searchTerm.value.trim()) {
    return props.rates
  }
  
  const search = searchTerm.value.toLowerCase()
  return props.rates.filter(rate => 
    rate.name.toLowerCase().includes(search) ||
    rate.rate.toString().includes(search) ||
    (rate.description && rate.description.toLowerCase().includes(search))
  )
})

// Dropdown positioning
const checkDropdownPosition = () => {
  const input = document.getElementById(inputId)
  if (!input) return
  
  const inputRect = input.getBoundingClientRect()
  const viewportHeight = window.innerHeight
  const spaceBelow = viewportHeight - inputRect.bottom
  const spaceAbove = inputRect.top
  
  dropupMode.value = spaceBelow < 250 && spaceAbove > spaceBelow
}

// Keyboard navigation
const navigateDown = () => {
  if (highlightedIndex.value < filteredRates.value.length - 1) {
    highlightedIndex.value++
  }
}

const navigateUp = () => {
  if (highlightedIndex.value > 0) {
    highlightedIndex.value--
  }
}

const selectHighlighted = () => {
  if (filteredRates.value[highlightedIndex.value]) {
    selectRate(filteredRates.value[highlightedIndex.value])
  }
}

// Rate selection
const selectRate = (rate) => {
  emit('update:modelValue', rate.id)
  emit('rate-selected', rate)
  closeDropdown()
}

const clearSelection = () => {
  emit('update:modelValue', null)
  emit('rate-selected', null)
  searchTerm.value = ''
  isOpen.value = true
  nextTick(() => {
    const input = document.getElementById(inputId)
    if (input) input.focus()
  })
}

const closeDropdown = () => {
  isOpen.value = false
  searchTerm.value = ''
  highlightedIndex.value = 0
}

// Click outside to close
const handleClickOutside = (event) => {
  const selector = document.getElementById(selectorId)
  if (selector && !selector.contains(event.target)) {
    closeDropdown()
  }
}

// Reset highlighted index when filtered results change
watch(filteredRates, () => {
  highlightedIndex.value = 0
})

// Check dropdown position when opened
watch(isOpen, (newValue) => {
  if (newValue) {
    nextTick(checkDropdownPosition)
  }
})

onMounted(() => {
  document.addEventListener('click', handleClickOutside)
  window.addEventListener('resize', checkDropdownPosition)
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
  window.removeEventListener('resize', checkDropdownPosition)
})
</script>