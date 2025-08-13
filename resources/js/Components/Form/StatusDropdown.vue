<template>
  <div class="relative inline-block" style="isolation: isolate;">
    <Listbox v-model="selectedValue" @update:model-value="handleChange">
      <div class="relative" style="z-index: 9999;">
        <ListboxButton
          ref="buttonRef"
          @click="calculateDropdownPosition"
          class="relative cursor-pointer rounded-full text-left focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-1"
          :class="[
            'inline-flex items-center px-2.5 py-0.5 text-xs font-medium shadow-sm transition-colors',
            getStatusClasses(currentStatus)
          ]"
        >
          <span 
            class="w-2 h-2 rounded-full mr-2 flex-shrink-0"
            :style="{ backgroundColor: getStatusColor(currentStatus) }"
          ></span>
          <span class="truncate">{{ getStatusLabel(currentStatus) }}</span>
          <ChevronUpDownIcon 
            class="ml-2 h-3 w-3 text-current opacity-60" 
            aria-hidden="true" 
          />
        </ListboxButton>

        <transition
          leave-active-class="transition ease-in duration-100"
          leave-from-class="opacity-100"
          leave-to-class="opacity-0"
        >
          <ListboxOptions
            class="fixed z-[9999] max-h-56 w-56 overflow-auto rounded-md bg-white py-1 text-sm shadow-xl ring-1 ring-black ring-opacity-5 focus:outline-none"
            :style="dropdownPosition"
          >
            <ListboxOption
              v-for="option in availableStatuses"
              :key="option.value"
              v-slot="{ active, selected }"
              :value="option.value"
              as="template"
              :disabled="option.disabled"
            >
              <li
                :class="[
                  active ? 'bg-blue-100 text-blue-900' : 'text-gray-900',
                  selected ? 'font-semibold' : 'font-normal',
                  option.disabled ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer',
                  'relative select-none py-2 pl-3 pr-9'
                ]"
              >
                <div class="flex items-center">
                  <span 
                    class="w-2 h-2 rounded-full mr-3 flex-shrink-0"
                    :style="{ backgroundColor: option.color }"
                  ></span>
                  <span class="truncate">{{ option.label }}</span>
                  <span v-if="option.disabled" class="ml-2 text-xs text-gray-400">
                    (Not allowed)
                  </span>
                </div>

                <span
                  v-if="selected"
                  class="absolute inset-y-0 right-0 flex items-center pr-4 text-blue-600"
                >
                  <CheckIcon class="h-4 w-4" aria-hidden="true" />
                </span>
              </li>
            </ListboxOption>
          </ListboxOptions>
        </transition>
      </div>
    </Listbox>

    <!-- Loading indicator -->
    <div
      v-if="loading"
      class="absolute inset-0 z-40 flex items-center justify-center bg-white bg-opacity-75 rounded-full"
    >
      <svg class="animate-spin h-3 w-3 text-blue-600" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
      </svg>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, nextTick, onMounted, onUnmounted } from 'vue'
import {
  Listbox,
  ListboxButton,
  ListboxOptions,
  ListboxOption,
} from '@headlessui/vue'
import { ChevronUpDownIcon, CheckIcon } from '@heroicons/vue/20/solid'

const props = defineProps({
  modelValue: {
    type: String,
    required: true
  },
  statuses: {
    type: Array,
    default: () => []
  },
  workflowTransitions: {
    type: Object,
    default: () => ({})
  },
  loading: {
    type: Boolean,
    default: false
  },
  disabled: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['update:modelValue', 'change'])

const selectedValue = ref(props.modelValue)
const currentStatus = computed(() => props.modelValue)
const buttonRef = ref(null)
const dropdownPosition = ref({})

// Watch for external changes to modelValue
watch(() => props.modelValue, (newValue) => {
  selectedValue.value = newValue
})

// Get available status transitions based on current status and workflow rules
const availableStatuses = computed(() => {
  const current = props.modelValue
  const transitions = props.workflowTransitions[current] || []
  
  // Always include current status (no change option)
  const options = []
  
  // Add current status as first option
  const currentStatusObj = props.statuses.find(s => s.key === current)
  if (currentStatusObj) {
    options.push({
      value: current,
      label: currentStatusObj.name,
      color: currentStatusObj.color,
      disabled: false
    })
  }
  
  // Add allowed transitions
  transitions.forEach(statusKey => {
    const status = props.statuses.find(s => s.key === statusKey)
    if (status && status.is_active) {
      options.push({
        value: status.key,
        label: status.name,
        color: status.color,
        disabled: false
      })
    }
  })
  
  // Add other statuses as disabled (for reference)
  if (import.meta.env.DEV) {
    props.statuses.forEach(status => {
      if (status.key !== current && !transitions.includes(status.key)) {
        options.push({
          value: status.key,
          label: status.name,
          color: status.color,
          disabled: true
        })
      }
    })
  }
  
  return options
})

const handleChange = (newValue) => {
  if (newValue !== props.modelValue && !props.disabled) {
    emit('update:modelValue', newValue)
    emit('change', {
      from: props.modelValue,
      to: newValue,
      timestamp: new Date().toISOString()
    })
  }
}

// Helper methods for styling
const getStatusClasses = (status) => {
  const statusObj = props.statuses.find(s => s.key === status)
  if (!statusObj) return 'bg-gray-100 text-gray-800'
  
  // Create classes from colors
  return `text-gray-800 border border-gray-300 hover:bg-gray-50`
}

const getStatusColor = (status) => {
  const statusObj = props.statuses.find(s => s.key === status)
  return statusObj?.color || '#6B7280'
}

const getStatusLabel = (status) => {
  const statusObj = props.statuses.find(s => s.key === status)
  return statusObj?.name || status
}

// Calculate dropdown position to avoid animations
const calculateDropdownPosition = async () => {
  await nextTick()
  if (buttonRef.value && buttonRef.value.$el) {
    const rect = buttonRef.value.$el.getBoundingClientRect()
    dropdownPosition.value = {
      top: `${rect.bottom + window.scrollY + 4}px`,
      left: `${rect.left + window.scrollX}px`,
      minWidth: `${Math.max(224, rect.width)}px`, // 224px = w-56
    }
  }
}

// Update position on scroll/resize
const updatePosition = () => {
  if (buttonRef.value && buttonRef.value.$el) {
    calculateDropdownPosition()
  }
}

onMounted(() => {
  window.addEventListener('scroll', updatePosition, true)
  window.addEventListener('resize', updatePosition)
})

onUnmounted(() => {
  window.removeEventListener('scroll', updatePosition, true)
  window.removeEventListener('resize', updatePosition)
})
</script>