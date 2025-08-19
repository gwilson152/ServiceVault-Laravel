<template>
  <div class="relative inline-block text-left">
    <div>
      <button
        @click="isOpen = !isOpen"
        type="button"
        class="inline-flex items-center justify-center w-full rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
      >
        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h13M3 8h9m-9 4h9m5-4v12m0 0l-4-4m4 4l4-4" />
        </svg>
        Sort by: {{ getCurrentSortLabel() }}
        <svg class="ml-2 -mr-1 h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
        </svg>
      </button>
    </div>

    <!-- Dropdown Menu -->
    <teleport to="body">
      <div
        v-if="isOpen"
        ref="dropdown"
        :style="{ top: dropdownPosition.top + 'px', left: dropdownPosition.left + 'px' }"
        class="fixed z-50 mt-2 w-56 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 focus:outline-none"
        @click.stop
      >
        <div class="py-1" role="menu">
          <div class="px-4 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wider border-b border-gray-100">
            Sort Options
          </div>
          
          <button
            v-for="option in sortOptions"
            :key="`${option.key}-${option.direction}`"
            @click="setSorting(option.key, option.direction)"
            class="group flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900 w-full text-left"
            :class="{ 'bg-blue-50 text-blue-700': isCurrentSort(option.key, option.direction) }"
            role="menuitem"
          >
            <component :is="option.icon" class="w-4 h-4 mr-3 text-gray-400" />
            <div class="flex-1">
              <div class="font-medium">{{ option.label }}</div>
              <div class="text-xs text-gray-500">{{ option.description }}</div>
            </div>
            <svg
              v-if="isCurrentSort(option.key, option.direction)"
              class="w-4 h-4 text-blue-600"
              fill="currentColor"
              viewBox="0 0 20 20"
            >
              <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 13.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
            </svg>
          </button>
        </div>
      </div>
    </teleport>

    <!-- Click Outside Detector -->
    <div
      v-if="isOpen"
      @click="isOpen = false"
      class="fixed inset-0 z-40"
    ></div>
  </div>
</template>

<script setup>
import { ref, computed, nextTick, onMounted, onUnmounted } from 'vue'

const props = defineProps({
  table: {
    type: Object,
    required: true
  }
})

const isOpen = ref(false)
const dropdown = ref(null)
const dropdownPosition = ref({ top: 0, left: 0 })

// Sort options configuration
const sortOptions = [
  {
    key: 'created_at',
    direction: 'desc',
    label: 'Newest First',
    description: 'Recently created tickets',
    icon: 'svg'
  },
  {
    key: 'created_at',
    direction: 'asc',
    label: 'Oldest First',
    description: 'Oldest created tickets',
    icon: 'svg'
  },
  {
    key: 'updated_at',
    direction: 'desc',
    label: 'Recently Updated',
    description: 'Latest activity first',
    icon: 'svg'
  },
  {
    key: 'due_date',
    direction: 'asc',
    label: 'Due Date (Soonest)',
    description: 'Urgent tickets first',
    icon: 'svg'
  },
  {
    key: 'due_date',
    direction: 'desc',
    label: 'Due Date (Latest)',
    description: 'Furthest due dates first',
    icon: 'svg'
  },
  {
    key: 'ticket_number',
    direction: 'desc',
    label: 'Ticket Number (High)',
    description: 'Highest ticket numbers',
    icon: 'svg'
  },
  {
    key: 'ticket_number',
    direction: 'asc',
    label: 'Ticket Number (Low)',
    description: 'Lowest ticket numbers',
    icon: 'svg'
  }
]

const currentSort = computed(() => {
  const sorting = props.table.getState().sorting
  return sorting.length > 0 ? sorting[0] : { id: 'created_at', desc: true }
})

const getCurrentSortLabel = () => {
  const current = currentSort.value
  const option = sortOptions.find(opt => 
    opt.key === current.id && 
    ((current.desc && opt.direction === 'desc') || (!current.desc && opt.direction === 'asc'))
  )
  return option ? option.label : 'Custom'
}

const isCurrentSort = (key, direction) => {
  const current = currentSort.value
  return current.id === key && 
         ((current.desc && direction === 'desc') || (!current.desc && direction === 'asc'))
}

const setSorting = (key, direction) => {
  props.table.setSorting([{ id: key, desc: direction === 'desc' }])
  isOpen.value = false
}

const updateDropdownPosition = () => {
  nextTick(() => {
    if (isOpen.value && dropdown.value) {
      const button = dropdown.value.previousElementSibling
      if (button) {
        const rect = button.getBoundingClientRect()
        dropdownPosition.value = {
          top: rect.bottom + window.scrollY,
          left: rect.left + window.scrollX
        }
      }
    }
  })
}

// Watch for dropdown opening to update position
const openDropdown = () => {
  isOpen.value = true
  updateDropdownPosition()
}

// Handle window resize
const handleResize = () => {
  if (isOpen.value) {
    updateDropdownPosition()
  }
}

onMounted(() => {
  window.addEventListener('resize', handleResize)
})

onUnmounted(() => {
  window.removeEventListener('resize', handleResize)
})
</script>