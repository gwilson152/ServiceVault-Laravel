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
    <div v-else-if="filteredTables.length > 0" class="space-y-2 max-h-96 overflow-y-auto">
      <div
        v-for="table in filteredTables"
        :key="table.name"
        @click="selectTable(table)"
        class="group cursor-pointer border border-gray-200 rounded-lg p-4 hover:border-indigo-300 hover:bg-indigo-50 transition-all"
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
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
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
    required: true
  },
  modelValue: {
    type: Object,
    default: null
  }
})

const emit = defineEmits(['update:modelValue', 'table-selected'])

// State
const tables = ref([])
const selectedTable = ref(props.modelValue)
const searchQuery = ref('')
const isLoading = ref(false)
const error = ref(null)

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
  if (!props.profileId) return
  
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
      tables.value = data.tables || []
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

const formatRowCount = (count) => {
  if (!count || count === 0) return '0'
  if (count < 1000) return count.toString()
  if (count < 1000000) return `${(count / 1000).toFixed(1)}k`
  return `${(count / 1000000).toFixed(1)}M`
}

// Watchers
watch(() => props.modelValue, (newValue) => {
  selectedTable.value = newValue
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