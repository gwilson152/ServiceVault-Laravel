<template>
  <div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-200" style="min-width: 800px">
      <thead class="bg-gray-50">
        <tr
          v-for="headerGroup in table.getHeaderGroups()"
          :key="headerGroup.id"
        >
          <th
            v-for="header in headerGroup.headers"
            :key="header.id"
            :colspan="header.colSpan"
            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
            :class="{
              'cursor-pointer hover:bg-gray-100': header.column.getCanSort(),
              'text-right': header.id === 'actions'
            }"
            @click="header.column.getToggleSortingHandler()?.($event)"
          >
            <div class="flex items-center" :class="{ 'justify-end': header.id === 'actions' }">
              <FlexRender
                :render="header.column.columnDef.header"
                :props="header.getContext()"
              />
              <span v-if="header.column.getCanSort()" class="ml-2">
                <svg v-if="header.column.getIsSorted() === 'asc'" class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                  <path d="M7 10l5-5 5 5H7z"/>
                </svg>
                <svg v-else-if="header.column.getIsSorted() === 'desc'" class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                  <path d="M7 10l5 5 5-5H7z"/>
                </svg>
                <svg v-else class="w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                  <path d="M10 3a1 1 0 01.707.293l3 3a1 1 0 01-1.414 1.414L10 5.414 7.707 7.707a1 1 0 01-1.414-1.414l3-3A1 1 0 0110 3zm-3.707 9.293a1 1 0 011.414 0L10 14.586l2.293-2.293a1 1 0 011.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z"/>
                </svg>
              </span>
            </div>
          </th>
        </tr>
      </thead>
      <tbody class="bg-white divide-y divide-gray-200">
        <tr
          v-for="row in table.getRowModel().rows"
          :key="row.id"
          class="hover:bg-gray-50 transition-colors"
        >
          <td
            v-for="cell in row.getVisibleCells()"
            :key="cell.id"
            class="px-6 py-4 whitespace-nowrap"
            :class="{
              'text-right': cell.column.id === 'actions',
              'text-sm text-gray-900': !['ticket_number', 'status', 'priority', 'timer', 'actions'].includes(cell.column.id),
              'text-sm text-gray-500': cell.column.id === 'updated_at'
            }"
          >
            <!-- Ticket Column -->
            <div v-if="cell.column.id === 'ticket_number'">
              <div class="text-sm font-medium text-blue-600 hover:text-blue-800">
                <a :href="`/tickets/${cell.row.original.id}`" class="hover:underline">
                  {{ cell.row.original.ticket_number }}
                </a>
              </div>
              <div class="text-sm text-gray-900 mt-1 max-w-xs truncate">
                {{ cell.row.original.title }}
              </div>
            </div>
            
            <!-- Status Column -->
            <span v-else-if="cell.column.id === 'status'" :class="getStatusClasses(cell.getValue())" class="px-2 py-1 text-xs font-medium rounded-full">
              {{ formatStatus(cell.getValue()) }}
            </span>
            
            <!-- Priority Column -->
            <span v-else-if="cell.column.id === 'priority'" :class="getPriorityClasses(cell.getValue())" class="px-2 py-1 text-xs font-medium rounded-full">
              {{ formatPriority(cell.getValue()) }}
            </span>
            
            <!-- Time Tracked Column -->
            <span v-else-if="cell.column.id === 'total_time_logged'">
              {{ formatDuration(cell.getValue()) }}
            </span>
            
            <!-- Timer Column -->
            <div v-else-if="cell.column.id === 'timer'" class="flex items-center space-x-1">
              <TicketTimerControls
                :ticket="cell.row.original"
                :currentUser="user"
                :compact="true"
                :initialTimerData="timersByTicket[cell.row.original.id] || []"
                :availableBillingRates="[]"
                :assignableUsers="[]"
                @timer-started="$emit('timer-started', $event)"
                @timer-stopped="$emit('timer-stopped', $event)"
                @timer-paused="$emit('timer-paused', $event)"
                @time-entry-created="$emit('time-entry-created', $event)"
              />
            </div>
            
            <!-- Updated Column -->
            <span v-else-if="cell.column.id === 'updated_at'">
              {{ formatDate(cell.getValue()) }}
            </span>
            
            <!-- Actions Column -->
            <div v-else-if="cell.column.id === 'actions'" class="flex items-center justify-end space-x-1">
              <!-- Manual Time Entry Button -->
              <button
                @click="$emit('open-manual-time-entry', cell.row.original)"
                class="p-1.5 text-gray-500 hover:text-blue-600 hover:bg-blue-50 rounded transition-colors"
                title="Add Manual Time Entry"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
              </button>
              
              <!-- Add Ticket Addon Button -->
              <button
                @click="$emit('open-ticket-addon', cell.row.original)"
                class="p-1.5 text-gray-500 hover:text-green-600 hover:bg-green-50 rounded transition-colors"
                title="Add Ticket Addon"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                </svg>
              </button>
            </div>
            
            <!-- Default Cell Renderer -->
            <FlexRender
              v-else
              :render="cell.column.columnDef.cell"
              :props="cell.getContext()"
            />
          </td>
        </tr>
      </tbody>
    </table>
    
    <!-- Pagination Controls -->
    <div class="px-6 py-4 flex items-center justify-between border-t border-gray-200">
      <div class="flex-1 flex justify-between sm:hidden">
        <button
          @click="table.previousPage()"
          :disabled="!table.getCanPreviousPage()"
          class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          Previous
        </button>
        <button
          @click="table.nextPage()"
          :disabled="!table.getCanNextPage()"
          class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          Next
        </button>
      </div>
      <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
        <div>
          <p class="text-sm text-gray-700">
            Showing
            <span class="font-medium">{{ table.getRowModel().rows.length ? (table.getState().pagination.pageIndex * table.getState().pagination.pageSize) + 1 : 0 }}</span>
            to
            <span class="font-medium">{{ Math.min((table.getState().pagination.pageIndex + 1) * table.getState().pagination.pageSize, table.getFilteredRowModel().rows.length) }}</span>
            of
            <span class="font-medium">{{ table.getFilteredRowModel().rows.length }}</span>
            results
          </p>
        </div>
        <div class="flex items-center space-x-2">
          <select
            :value="table.getState().pagination.pageSize"
            @change="table.setPageSize(Number($event.target.value))"
            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 text-sm"
          >
            <option v-for="pageSize in [10, 20, 30, 40, 50]" :key="pageSize" :value="pageSize">
              Show {{ pageSize }}
            </option>
          </select>
          <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
            <button
              @click="table.setPageIndex(0)"
              :disabled="!table.getCanPreviousPage()"
              class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <span class="sr-only">First</span>
              <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                <path fill-rule="evenodd" d="M9.707 5.293a1 1 0 010 1.414L6.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
              </svg>
            </button>
            <button
              @click="table.previousPage()"
              :disabled="!table.getCanPreviousPage()"
              class="relative inline-flex items-center px-2 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <span class="sr-only">Previous</span>
              <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
              </svg>
            </button>
            <span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">
              Page {{ table.getState().pagination.pageIndex + 1 }} of {{ table.getPageCount() }}
            </span>
            <button
              @click="table.nextPage()"
              :disabled="!table.getCanNextPage()"
              class="relative inline-flex items-center px-2 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <span class="sr-only">Next</span>
              <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
              </svg>
            </button>
            <button
              @click="table.setPageIndex(table.getPageCount() - 1)"
              :disabled="!table.getCanNextPage()"
              class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <span class="sr-only">Last</span>
              <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                <path fill-rule="evenodd" d="M10.293 14.707a1 1 0 010-1.414L13.586 10l-3.293-3.293a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
              </svg>
            </button>
          </nav>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { FlexRender } from '@tanstack/vue-table'
import TicketTimerControls from '@/Components/Timer/TicketTimerControls.vue'

const props = defineProps({
  table: {
    type: Object,
    required: true
  },
  user: {
    type: Object,
    required: true
  },
  timersByTicket: {
    type: Object,
    default: () => ({})
  }
})

defineEmits([
  'timer-started',
  'timer-stopped',
  'timer-paused',
  'time-entry-created',
  'open-manual-time-entry',
  'open-ticket-addon'
])

// Helper methods
const getStatusClasses = (status) => {
  const classes = {
    'open': 'bg-blue-100 text-blue-800',
    'in_progress': 'bg-yellow-100 text-yellow-800',
    'pending_review': 'bg-purple-100 text-purple-800',
    'resolved': 'bg-green-100 text-green-800',
    'closed': 'bg-gray-100 text-gray-800'
  }
  return classes[status] || classes.open
}

const getPriorityClasses = (priority) => {
  const classes = {
    'low': 'bg-gray-100 text-gray-800',
    'normal': 'bg-blue-100 text-blue-800',
    'high': 'bg-orange-100 text-orange-800',
    'urgent': 'bg-red-100 text-red-800'
  }
  return classes[priority] || classes.normal
}

const formatStatus = (status) => {
  return status.split('_').map(word => 
    word.charAt(0).toUpperCase() + word.slice(1)
  ).join(' ')
}

const formatPriority = (priority) => {
  return priority.charAt(0).toUpperCase() + priority.slice(1)
}

const formatDuration = (seconds) => {
  if (!seconds) return '0h 0m'
  
  const hours = Math.floor(seconds / 3600)
  const minutes = Math.floor((seconds % 3600) / 60)
  
  if (hours > 0) {
    return `${hours}h ${minutes}m`
  }
  return `${minutes}m`
}

const formatDate = (dateString) => {
  if (!dateString) return ''
  
  const date = new Date(dateString)
  const now = new Date()
  const diffDays = Math.floor((now - date) / (1000 * 60 * 60 * 24))
  
  if (diffDays === 0) return 'Today'
  if (diffDays === 1) return 'Yesterday'
  if (diffDays < 7) return `${diffDays} days ago`
  
  return date.toLocaleDateString()
}
</script>