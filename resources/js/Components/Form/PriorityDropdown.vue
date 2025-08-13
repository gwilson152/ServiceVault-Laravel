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
            getPriorityClasses(currentPriority)
          ]"
        >
          <component
            :is="getPriorityIcon(currentPriority)"
            class="mr-2 h-3 w-3 flex-shrink-0"
            :class="getPriorityIconClasses(currentPriority)"
          />
          <span class="truncate">{{ getPriorityLabel(currentPriority) }}</span>
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
            class="fixed z-[9999] max-h-56 w-48 overflow-auto rounded-md bg-white py-1 text-sm shadow-xl ring-1 ring-black ring-opacity-5 focus:outline-none"
            :style="dropdownPosition"
          >
            <ListboxOption
              v-for="option in availablePriorities"
              :key="option.value"
              v-slot="{ active, selected }"
              :value="option.value"
              as="template"
            >
              <li
                :class="[
                  active ? 'bg-blue-100 text-blue-900' : 'text-gray-900',
                  selected ? 'font-semibold' : 'font-normal',
                  'relative cursor-pointer select-none py-2 pl-3 pr-9'
                ]"
              >
                <div class="flex items-center">
                  <component
                    :is="option.icon"
                    class="mr-3 h-3 w-3 flex-shrink-0"
                    :style="{ color: option.color }"
                  />
                  <span class="truncate">{{ option.label }}</span>
                  <span class="ml-2 text-xs text-gray-400">
                    L{{ option.severity_level }}
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
import { 
  ChevronUpDownIcon, 
  CheckIcon, 
  ChevronDownIcon,
  MinusIcon,
  ChevronUpIcon,
  ChevronDoubleUpIcon,
  ExclamationTriangleIcon
} from '@heroicons/vue/20/solid'

const props = defineProps({
  modelValue: {
    type: String,
    required: true
  },
  priorities: {
    type: Array,
    default: () => []
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
const currentPriority = computed(() => props.modelValue)
const buttonRef = ref(null)
const dropdownPosition = ref({})

// Watch for external changes to modelValue
watch(() => props.modelValue, (newValue) => {
  selectedValue.value = newValue
})

// Available priorities sorted by severity level
const availablePriorities = computed(() => {
  return props.priorities
    .filter(p => p.is_active)
    .sort((a, b) => a.sort_order - b.sort_order)
    .map(priority => ({
      value: priority.key,
      label: priority.name,
      color: priority.color,
      severity_level: priority.severity_level,
      icon: getIconComponent(priority.icon),
      escalation_multiplier: priority.escalation_multiplier
    }))
})

const handleChange = (newValue) => {
  if (newValue !== props.modelValue && !props.disabled) {
    const newPriority = props.priorities.find(p => p.key === newValue)
    const oldPriority = props.priorities.find(p => p.key === props.modelValue)
    
    emit('update:modelValue', newValue)
    emit('change', {
      from: props.modelValue,
      to: newValue,
      fromPriority: oldPriority,
      toPriority: newPriority,
      timestamp: new Date().toISOString()
    })
  }
}

// Icon mapping
const iconComponents = {
  ChevronDownIcon,
  MinusIcon,
  ChevronUpIcon,
  ChevronDoubleUpIcon,
  ExclamationTriangleIcon,
}

const getIconComponent = (iconName) => {
  return iconComponents[iconName] || MinusIcon
}

// Helper methods for styling
const getPriorityClasses = (priority) => {
  const priorityObj = props.priorities.find(p => p.key === priority)
  if (!priorityObj) return 'bg-gray-100 text-gray-800'
  
  // Base classes
  return 'text-gray-800 border border-gray-300 hover:bg-gray-50'
}

const getPriorityIcon = (priority) => {
  const priorityObj = props.priorities.find(p => p.key === priority)
  if (!priorityObj) return MinusIcon
  
  return getIconComponent(priorityObj.icon)
}

const getPriorityIconClasses = (priority) => {
  const priorityObj = props.priorities.find(p => p.key === priority)
  if (!priorityObj) return 'text-gray-600'
  
  // Color based on severity
  const severityColors = {
    1: 'text-gray-600',      // Low
    2: 'text-blue-600',      // Normal
    3: 'text-yellow-600',    // Medium
    4: 'text-orange-600',    // High
    5: 'text-red-600'        // Urgent
  }
  
  return severityColors[priorityObj.severity_level] || 'text-gray-600'
}

const getPriorityLabel = (priority) => {
  const priorityObj = props.priorities.find(p => p.key === priority)
  return priorityObj?.name || priority
}

// Calculate dropdown position to avoid animations
const calculateDropdownPosition = async () => {
  await nextTick()
  if (buttonRef.value && buttonRef.value.$el) {
    const rect = buttonRef.value.$el.getBoundingClientRect()
    dropdownPosition.value = {
      top: `${rect.bottom + window.scrollY + 4}px`,
      left: `${rect.left + window.scrollX}px`,
      minWidth: `${Math.max(192, rect.width)}px`, // 192px = w-48
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