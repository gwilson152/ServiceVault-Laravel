<template>
  <div class="bg-white border border-gray-200 rounded-lg p-6">
    <div class="flex items-center justify-between mb-4">
      <h3 class="text-lg font-medium text-gray-900">Database Tables</h3>
      <button
        @click="refreshTables"
        :disabled="isLoading"
        class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50"
      >
        <ArrowPathIcon class="w-4 h-4 mr-2" :class="{ 'animate-spin': isLoading }" />
        Refresh
      </button>
    </div>

    <!-- Search Tables -->
    <div class="mb-4">
      <div class="relative">
        <MagnifyingGlassIcon class="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-400" />
        <input
          v-model="searchQuery"
          type="text"
          placeholder="Search tables..."
          class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
        />
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="isLoading" class="text-center py-8">
      <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
      <p class="mt-2 text-sm text-gray-600">Loading tables...</p>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="text-center py-8">
      <ExclamationTriangleIcon class="mx-auto h-12 w-12 text-red-400" />
      <h3 class="mt-2 text-sm font-medium text-gray-900">Error loading tables</h3>
      <p class="mt-1 text-sm text-gray-500">{{ error }}</p>
      <button
        @click="refreshTables"
        class="mt-4 inline-flex items-center px-3 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700"
      >
        Try Again
      </button>
    </div>

    <!-- Tables List -->
    <div v-else-if="filteredTables.length > 0" class="space-y-2">
      <div
        v-for="table in filteredTables"
        :key="table.name"
        :data-table-name="table.name"
        @click="selectTable(table)"
        @mouseenter="showTableTooltip(table, $event)"
        @mouseleave="hideTableTooltip(table)"
        class="group cursor-pointer border border-gray-200 rounded-lg p-4 hover:border-indigo-300 hover:bg-indigo-50 transition-all relative"
        :class="{
          'border-indigo-500 bg-indigo-50': selectedTable?.name === table.name
        }"
      >
        <div class="flex items-start justify-between">
          <div class="flex-1">
            <div class="flex items-center">
              <TableCellsIcon class="w-5 h-5 text-gray-400 mr-3" />
              <h4 class="text-sm font-medium text-gray-900">{{ table.name }}</h4>
              <span v-if="table.row_count" class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800">
                {{ formatRowCount(table.row_count) }} rows
              </span>
            </div>
            <p v-if="table.comment" class="mt-1 text-sm text-gray-500">{{ table.comment }}</p>
          </div>
          
          <div v-if="selectedTable?.name === table.name" class="flex-shrink-0 ml-3">
            <CheckCircleIcon class="w-5 h-5 text-indigo-600" />
          </div>
        </div>

        <!-- Column Summary -->
        <div v-if="table.columns && table.columns.length > 0" class="mt-3">
          <p class="text-xs font-medium text-gray-700 mb-2">Columns ({{ table.columns.length }}):</p>
          <div class="flex flex-wrap gap-1">
            <span
              v-for="column in table.columns.slice(0, 8)"
              :key="column.name"
              class="inline-flex items-center px-2 py-1 rounded text-xs"
              :class="{
                'bg-blue-100 text-blue-800': column.is_primary_key,
                'bg-green-100 text-green-800': column.is_foreign_key,
                'bg-gray-100 text-gray-700': !column.is_primary_key && !column.is_foreign_key
              }"
            >
              {{ column.name }}
              <span v-if="column.is_primary_key" class="ml-1 text-xs">🔑</span>
              <span v-if="column.is_foreign_key" class="ml-1 text-xs">🔗</span>
            </span>
            <span v-if="table.columns.length > 8" class="inline-flex items-center px-2 py-1 rounded text-xs bg-gray-100 text-gray-700">
              +{{ table.columns.length - 8 }} more
            </span>
          </div>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else class="text-center py-8">
      <TableCellsIcon class="mx-auto h-12 w-12 text-gray-400" />
      <h3 class="mt-2 text-sm font-medium text-gray-900">No tables found</h3>
      <p class="mt-1 text-sm text-gray-500">
        {{ searchQuery ? 'No tables match your search criteria.' : 'No tables available in this database.' }}
      </p>
    </div>

    <!-- Selected Table Details -->
    <div v-if="selectedTable" class="mt-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
      <div class="flex items-center justify-between mb-3">
        <h4 class="text-sm font-medium text-blue-900">Selected: {{ selectedTable.name }}</h4>
        <button
          @click="viewTableDetails"
          class="text-xs text-blue-600 hover:text-blue-800"
        >
          View Details
        </button>
      </div>
      <div class="grid grid-cols-2 gap-4 text-sm">
        <div>
          <span class="font-medium text-blue-700">Columns:</span>
          <span class="text-blue-900 ml-1">{{ selectedTable.columns?.length || 0 }}</span>
        </div>
        <div>
          <span class="font-medium text-blue-700">Rows:</span>
          <span class="text-blue-900 ml-1">{{ formatRowCount(selectedTable.row_count) }}</span>
        </div>
      </div>
    </div>

    <!-- Table Details Tooltip -->
    <div
      v-if="tooltipVisible && tooltipTable"
      ref="tooltip"
      class="fixed z-50 bg-white border border-gray-200 rounded-lg shadow-lg p-4 max-w-md"
      :style="tooltipStyle"
      @mouseenter="keepTooltipVisible"
      @mouseleave="hideTableTooltip"
    >
      <div class="space-y-3">
        <!-- Table Header -->
        <div class="border-b border-gray-200 pb-2">
          <div class="flex items-center">
            <TableCellsIcon class="w-5 h-5 text-gray-400 mr-2" />
            <h3 class="text-sm font-semibold text-gray-900">{{ tooltipTable.name }}</h3>
          </div>
          <p v-if="tooltipTable.comment" class="text-xs text-gray-600 mt-1">{{ tooltipTable.comment }}</p>
        </div>

        <!-- Table Stats -->
        <div class="grid grid-cols-2 gap-4 text-xs">
          <div>
            <span class="font-medium text-gray-700">Columns:</span>
            <span class="text-gray-900 ml-1">{{ tooltipTable.columns?.length || 0 }}</span>
          </div>
          <div>
            <span class="font-medium text-gray-700">Rows:</span>
            <span class="text-gray-900 ml-1">{{ formatRowCount(tooltipTable.row_count) }}</span>
          </div>
        </div>

        <!-- Column Details -->
        <div v-if="tooltipTable.columns && tooltipTable.columns.length > 0">
          <h4 class="text-xs font-medium text-gray-700 mb-2">Columns:</h4>
          <div class="max-h-40 overflow-y-auto space-y-1">
            <div
              v-for="column in tooltipTable.columns"
              :key="column.name"
              class="flex items-center justify-between text-xs py-1"
            >
              <div class="flex items-center flex-1">
                <span class="font-medium text-gray-900">{{ column.name }}</span>
                <span class="ml-2 text-gray-500">{{ column.type }}</span>
                <span v-if="column.is_primary_key" class="ml-1 text-blue-600" title="Primary Key">🔑</span>
                <span v-if="column.is_foreign_key" class="ml-1 text-green-600" title="Foreign Key">🔗</span>
                <span v-if="!column.is_nullable" class="ml-1 text-orange-600" title="Not Null">*</span>
              </div>
              <div v-if="column.default_value" class="text-gray-400 ml-2 truncate max-w-20" :title="column.default_value">
                {{ column.default_value }}
              </div>
            </div>
          </div>
        </div>

        <!-- Additional Info -->
        <div v-if="tooltipTable.schema || tooltipTable.size_pretty" class="border-t border-gray-200 pt-2 text-xs text-gray-600">
          <div v-if="tooltipTable.schema" class="mb-1">Schema: {{ tooltipTable.schema }}</div>
          <div v-if="tooltipTable.size_pretty">Size: {{ tooltipTable.size_pretty }}</div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, nextTick } from 'vue'
import {
  TableCellsIcon,
  MagnifyingGlassIcon,
  ArrowPathIcon,
  CheckCircleIcon,
  ExclamationTriangleIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
  profileId: {
    type: String,
    default: null
  },
  modelValue: {
    type: Object,
    default: null
  }
})

const emit = defineEmits(['update:modelValue', 'table-selected', 'schema-loaded'])

// State
const tables = ref([])
const selectedTable = ref(props.modelValue)
const searchQuery = ref('')
const isLoading = ref(false)
const error = ref(null)

// Tooltip state
const tooltipVisible = ref(false)
const tooltipTable = ref(null)
const tooltipStyle = ref({})
const tooltip = ref(null)
const tooltipHideTimer = ref(null)
const currentHoveredTable = ref(null)

// Computed
const filteredTables = computed(() => {
  if (!searchQuery.value) return tables.value
  
  const query = searchQuery.value.toLowerCase()
  return tables.value.filter(table => 
    table.name.toLowerCase().includes(query) ||
    table.comment?.toLowerCase().includes(query) ||
    table.columns?.some(col => col.name.toLowerCase().includes(query))
  )
})

// Methods
const selectTable = (table) => {
  selectedTable.value = table
  emit('update:modelValue', table)
  emit('table-selected', table)
}

const refreshTables = async () => {
  if (!props.profileId) {
    return
  }
  
  isLoading.value = true
  error.value = null
  
  try {
    const response = await fetch(`/api/import/profiles/${props.profileId}/schema`, {
      method: 'GET',
      credentials: 'same-origin',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        'X-Requested-With': 'XMLHttpRequest'
      }
    })
    
    if (response.ok) {
      const data = await response.json()
      
      // Handle different response formats
      if (data.schema?.tables) {
        tables.value = data.schema.tables
        emit('schema-loaded', { tables: data.schema.tables })
      } else if (data.tables) {
        tables.value = data.tables
        emit('schema-loaded', { tables: data.tables })
      } else {
        tables.value = []
        emit('schema-loaded', { tables: [] })
      }
      
      // Scroll to selected table after tables are loaded
      if (selectedTable.value) {
        nextTick(() => {
          scrollToSelectedTable()
        })
      }
    } else {
      const errorData = await response.json()
      error.value = errorData.message || 'Failed to load database schema'
    }
  } catch (err) {
    error.value = err.message || 'Network error occurred'
  } finally {
    isLoading.value = false
  }
}

const viewTableDetails = () => {
  if (selectedTable.value) {
    // Could emit an event or open a modal with detailed table information
    emit('view-table-details', selectedTable.value)
  }
}

const showTableTooltip = (table, event) => {
  // Cancel any pending hide operation
  if (tooltipHideTimer.value) {
    clearTimeout(tooltipHideTimer.value)
    tooltipHideTimer.value = null
  }
  
  // Track current hovered table
  currentHoveredTable.value = table.name
  tooltipTable.value = table
  tooltipVisible.value = true
  
  // Position tooltip at top right of the table item
  const rect = event.currentTarget.getBoundingClientRect()
  const tooltipWidth = 384 // max-w-md = 24rem = 384px
  const tooltipHeight = 320 // estimated max height
  const spacing = 8
  
  // Position at top right of the table item
  let left = rect.right + spacing
  let top = rect.top
  
  // Check if tooltip would go off right edge of screen
  if (left + tooltipWidth > window.innerWidth) {
    // Position to the left of the table item instead
    left = rect.left - tooltipWidth - spacing
  }
  
  // Check if tooltip would go off bottom edge of screen
  if (top + tooltipHeight > window.innerHeight) {
    // Align bottom of tooltip with bottom of visible area
    top = window.innerHeight - tooltipHeight - spacing
  }
  
  // Ensure tooltip doesn't go off top edge
  if (top < spacing) {
    top = spacing
  }
  
  // Ensure tooltip doesn't go off left edge (fallback)
  if (left < spacing) {
    left = spacing
  }
  
  tooltipStyle.value = {
    left: `${left}px`,
    top: `${top}px`
  }
}

const hideTableTooltip = (table = null) => {
  // If we're moving to another table, don't hide - just change immediately
  if (table && currentHoveredTable.value !== table.name) {
    return
  }
  
  // Clear current hovered table
  currentHoveredTable.value = null
  
  // Clear any existing timer
  if (tooltipHideTimer.value) {
    clearTimeout(tooltipHideTimer.value)
  }
  
  // Add delay to allow mouse to move to tooltip or another table
  tooltipHideTimer.value = setTimeout(() => {
    // Only hide if we're not hovering over another table
    if (!currentHoveredTable.value) {
      tooltipVisible.value = false
      tooltipTable.value = null
    }
    tooltipHideTimer.value = null
  }, 100)
}


const keepTooltipVisible = () => {
  // Cancel any pending hide when mouse enters tooltip
  if (tooltipHideTimer.value) {
    clearTimeout(tooltipHideTimer.value)
    tooltipHideTimer.value = null
  }
}

const formatRowCount = (count) => {
  if (!count || count === 0) return '0'
  if (count < 1000) return count.toString()
  if (count < 1000000) return `${(count / 1000).toFixed(1)}k`
  return `${(count / 1000000).toFixed(1)}M`
}

const scrollToSelectedTable = () => {
  if (!selectedTable.value?.name) return
  
  // Use nextTick to ensure DOM is updated
  nextTick(() => {
    const tableElement = document.querySelector(`[data-table-name="${selectedTable.value.name}"]`)
    if (tableElement) {
      tableElement.scrollIntoView({ 
        behavior: 'smooth', 
        block: 'center' 
      })
    }
  })
}

// Watchers
watch(() => props.modelValue, (newValue) => {
  selectedTable.value = newValue
  if (newValue) {
    scrollToSelectedTable()
  }
})

watch(() => props.profileId, (newProfileId) => {
  if (newProfileId) {
    refreshTables()
  }
})

// Lifecycle
onMounted(() => {
  if (props.profileId) {
    refreshTables()
  }
})
</script>