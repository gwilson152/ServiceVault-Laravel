<template>
  <div class="overflow-x-auto">
    <table 
      class="w-full table-auto divide-y divide-gray-200" 
      :class="{
        'table-comfortable': density === 'comfortable',
        'table-compact': density === 'compact'
      }"
    >
      <thead class="bg-gray-50 border-b border-gray-300">
        <tr
          v-for="headerGroup in table.getHeaderGroups()"
          :key="headerGroup.id"
        >
          <th
            v-for="header in headerGroup.headers"
            :key="header.id"
            :colspan="header.colSpan"
            :style="header.column.columnDef.size ? { width: header.column.columnDef.size + 'px' } : {}"
            :class="[
              density === 'compact' ? 'px-3 py-2' : 'px-6 py-3',
              'text-left text-xs font-semibold text-gray-600 uppercase tracking-wider border-b border-gray-200',
              {
                'cursor-pointer hover:bg-gray-100': header.column.getCanSort(),
                'text-center': ['actions', 'updated_at', 'created_at', 'due_date'].includes(header.id),
                'w-20': header.id === 'actions',
                'w-28': ['updated_at', 'created_at', 'due_date'].includes(header.id),
                'w-48': header.id === 'account',
                'w-32': header.id === 'category',
                'w-36': header.id === 'assigned_agent'
              }
            ]"
            @click="header.column.getToggleSortingHandler()?.($event)"
          >
            <div class="flex items-center" :class="{ 
              'justify-center': ['actions', 'updated_at', 'created_at', 'due_date'].includes(header.id),
              'justify-start': !['actions', 'updated_at', 'created_at', 'due_date'].includes(header.id)
            }">
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
      <tbody class="bg-white divide-y divide-gray-100">
        <tr
          v-for="row in table.getRowModel().rows"
          :key="row.id"
          class="hover:bg-blue-50 transition-all duration-150 cursor-pointer border-l-2 border-transparent hover:border-blue-300 hover:shadow-sm"
          @contextmenu="openContextMenu($event, row.original)"
        >
          <td
            v-for="cell in row.getVisibleCells()"
            :key="cell.id"
            :style="cell.column.columnDef.size ? { width: cell.column.columnDef.size + 'px' } : {}"
            :class="[
              density === 'compact' ? 'px-3 py-2' : 'px-6 py-4',
              'border-b border-gray-100',
              {
                'text-center': ['actions', 'updated_at', 'created_at', 'due_date'].includes(cell.column.id),
                'text-sm text-gray-900': !['ticket_number', 'status', 'priority', 'actions', 'account', 'assigned_to', 'total_time_logged', 'updated_at', 'created_at', 'due_date'].includes(cell.column.id),
                'text-sm text-gray-500': ['updated_at', 'created_at', 'due_date'].includes(cell.column.id),
                'whitespace-nowrap': !['ticket_number'].includes(cell.column.id), // Only allow ticket details column to wrap
                'w-20': cell.column.id === 'actions',
                'w-28': ['updated_at', 'created_at', 'due_date'].includes(cell.column.id),
                'w-48': cell.column.id === 'account',
                'w-32': cell.column.id === 'category',
                'w-36': cell.column.id === 'assigned_agent'
              }
            ]"
          >
            <!-- Combined Ticket Details Column -->
            <div v-if="cell.column.id === 'ticket_number'">
              <!-- Ticket Number & Title -->
              <div class="mb-2">
                <div class="text-sm font-semibold text-blue-600 hover:text-blue-800 whitespace-nowrap">
                  <Link :href="`/tickets/${cell.row.original.id}`" class="hover:underline focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-1 rounded">
                    {{ cell.row.original.ticket_number }}
                  </Link>
                </div>
                <div 
                  :class="[
                    'text-sm text-gray-700 max-w-sm font-medium',
                    density === 'compact' ? 'mt-0.5 line-clamp-1' : 'mt-1 line-clamp-2'
                  ]"
                  :title="cell.row.original.title"
                >
                  {{ cell.row.original.title }}
                </div>
              </div>
              
              <!-- Customer & Agent Info -->
              <div class="mb-2 text-xs text-gray-600 space-y-0.5">
                <!-- Customer Info -->
                <div v-if="cell.row.original.customer" class="flex items-center">
                  <svg class="w-3 h-3 mr-1 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                  </svg>
                  <span class="font-medium text-gray-700">{{ cell.row.original.customer.name }}</span>
                  <span class="ml-1 text-gray-500">{{ cell.row.original.account?.name ? `(${cell.row.original.account.name})` : '' }}</span>
                </div>
                
                <!-- Agent Info -->
                <div class="flex items-center">
                  <svg class="w-3 h-3 mr-1 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.982 18.725A7.488 7.488 0 0012 15.75a7.488 7.488 0 00-5.982 2.975m11.963 0a9 9 0 10-11.963 0m11.963 0A8.966 8.966 0 0112 21a8.966 8.966 0 01-5.982-2.275M15 9.75a3 3 0 11-6 0 3 3 0 016 0z" />
                  </svg>
                  <span class="text-gray-600">
                    Agent: <span class="font-medium text-gray-700">{{ cell.row.original.assigned_to?.name || 'Unassigned' }}</span>
                  </span>
                </div>
              </div>
              
              <!-- Status & Priority Row -->
              <div 
                :class="[
                  'flex items-center gap-2',
                  density === 'compact' ? 'flex-wrap' : 'flex-row'
                ]"
              >
                <!-- Status Badge (Read-only) -->
                <span 
                  :class="getStatusClasses(cell.row.original.status)" 
                  class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium shadow-sm"
                >
                  <span 
                    :class="getStatusDotClass(cell.row.original.status)" 
                    class="w-2 h-2 rounded-full mr-1.5"
                  ></span>
                  {{ formatStatus(cell.row.original.status) }}
                </span>
                
                <!-- Priority Badge (Read-only) -->
                <span
                  :class="getPriorityClasses(cell.row.original.priority)" 
                  class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium shadow-sm"
                >
                  <span 
                    :class="getPriorityIconClass(cell.row.original.priority)" 
                    class="mr-1.5"
                  >
                    {{ getPriorityIcon(cell.row.original.priority) }}
                  </span>
                  {{ formatPriority(cell.row.original.priority) }}
                </span>
              </div>
            </div>
            
            <!-- Combined Account/Customer Column -->
            <div v-else-if="cell.column.id === 'account'" class="text-left">
              <!-- Account Name -->
              <div class="text-sm font-medium text-gray-900 mb-1">
                {{ cell.row.original.account?.name || 'No Account' }}
              </div>
              
              <!-- Assigned User -->
              <div class="text-xs text-gray-600">
                <span class="text-gray-500">Assigned:</span>
                <span class="ml-1 font-medium">{{ cell.row.original.assigned_to?.name || 'Unassigned' }}</span>
              </div>
            </div>
            
            <!-- Actions Column -->
            <div v-else-if="cell.column.id === 'actions'" class="text-center">
              <!-- Action Buttons Row -->
              <div 
                :class="[
                  'flex items-center justify-center',
                  density === 'compact' ? 'space-x-1 mb-1' : 'space-x-2 mb-2'
                ]"
              >
                <!-- Add Time Entry Button -->
                <button
                  @click="$emit('open-manual-time-entry', cell.row.original)"
                  class="group inline-flex items-center px-2 py-1 text-xs font-medium rounded-md border transition-all duration-200 text-gray-600 border-gray-300 bg-white hover:bg-blue-50 hover:border-blue-300 hover:text-blue-700 active:bg-blue-100 sm:px-3"
                  title="Add Time Entry"
                >
                  <svg class="w-3 h-3 sm:w-4 sm:h-4 sm:mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                  <span class="hidden sm:inline">Add Time</span>
                </button>
                
                <!-- Add Ticket Addon Button -->
                <button
                  @click="$emit('open-ticket-addon', cell.row.original)"
                  class="group inline-flex items-center px-2 py-1 text-xs font-medium rounded-md border transition-all duration-200 text-gray-600 border-gray-300 bg-white hover:bg-green-50 hover:border-green-300 hover:text-green-700 active:bg-green-100 sm:px-3"
                  title="Add Ticket Addon"
                >
                  <svg class="w-3 h-3 sm:w-4 sm:h-4 sm:mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                  </svg>
                  <span class="hidden sm:inline">Add Addon</span>
                </button>
              </div>
              
              <!-- Time Tracked Row -->
              <div class="text-center">
                <div class="font-mono text-sm font-medium text-gray-900">
                  {{ formatDuration(cell.row.original.total_time_logged) }}
                </div>
                <div v-if="density === 'comfortable'" class="text-xs text-gray-500 mt-0.5">
                  {{ formatHours(cell.row.original.total_time_logged) }}
                </div>
              </div>
            </div>
            
            <!-- Created Date Column -->
            <span v-else-if="cell.column.id === 'created_at'">
              {{ formatDate(cell.getValue(), { relative: true }) }}
            </span>
            
            <!-- Due Date Column -->
            <span v-else-if="cell.column.id === 'due_date'">
              <span v-if="cell.getValue()" :class="getDueDateClasses(cell.getValue())">
                {{ formatDate(cell.getValue(), { relative: false }) }}
              </span>
              <span v-else class="text-gray-400 text-xs">No due date</span>
            </span>
            
            <!-- Category Column -->
            <span v-else-if="cell.column.id === 'category'">
              <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                {{ cell.getValue() }}
              </span>
            </span>
            
            <!-- Assigned Agent Column -->
            <span v-else-if="cell.column.id === 'assigned_agent'">
              <span v-if="cell.getValue() && cell.getValue() !== 'Unassigned'" class="text-sm text-gray-900">
                {{ cell.getValue() }}
              </span>
              <span v-else class="text-sm text-gray-400">Unassigned</span>
            </span>
            
            <!-- Updated Column -->
            <span v-else-if="cell.column.id === 'updated_at'">
              {{ formatDate(cell.getValue(), { relative: true }) }}
            </span>
            
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
    
    <!-- Business Pagination Controls -->
    <div 
      :class="[
        density === 'compact' ? 'px-3 py-3' : 'px-6 py-4',
        'flex items-center justify-between border-t border-gray-200 bg-gray-50'
      ]"
    >
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

    <!-- Context Menu -->
    <ContextMenu
      :show="showContextMenu"
      :x="contextMenuX"
      :y="contextMenuY"
      :target="contextMenuTarget"
      @close="closeContextMenu"
    >
      <template #default="{ item, close }">
        <div class="py-1">
          <a
            :href="route('tickets.show', item.id)"
            class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900"
            @click="close"
          >
            <div class="flex items-center">
              <svg class="w-4 h-4 mr-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
              </svg>
              View Ticket
            </div>
          </a>

          <button
            @click="$emit('open-manual-time-entry', item); close()"
            class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900"
          >
            <div class="flex items-center">
              <svg class="w-4 h-4 mr-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              Add Time Entry
            </div>
          </button>

          <button
            @click="$emit('open-ticket-addon', item); close()"
            class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900"
          >
            <div class="flex items-center">
              <svg class="w-4 h-4 mr-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
              </svg>
              Add Addon
            </div>
          </button>

          <hr class="my-1 border-gray-100">

          <a
            :href="route('tickets.show', [item.id, 'activity'])"
            class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900"
            @click="close"
          >
            <div class="flex items-center">
              <svg class="w-4 h-4 mr-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
              View Activity
            </div>
          </a>
        </div>
      </template>
    </ContextMenu>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { Link } from '@inertiajs/vue3'
import { FlexRender } from '@tanstack/vue-table'
import { useContextMenu } from '@/Composables/useContextMenu'
import { useFormats } from '@/Composables/useFormats'
import ContextMenu from '@/Components/UI/ContextMenu.vue'
// Removed inline editing and timer components for simplified table view

const props = defineProps({
  table: {
    type: Object,
    required: true
  },
  user: {
    type: Object,
    required: true
  },
  density: {
    type: String,
    default: 'compact',
    validator: (value) => ['comfortable', 'compact'].includes(value)
  }
})

const emit = defineEmits([
  'open-manual-time-entry',
  'open-ticket-addon'
])

// Context menu functionality
const {
  showContextMenu,
  contextMenuX,
  contextMenuY,
  contextMenuTarget,
  openContextMenu,
  closeContextMenu
} = useContextMenu()

// Formatting utilities with system settings
const { formatDate, formatDuration } = useFormats()

// Removed inline editing functionality for simplified table view

// Helper methods
const getStatusClasses = (status) => {
  const classes = {
    'open': 'bg-blue-50 text-blue-700 border border-blue-200',
    'in_progress': 'bg-yellow-50 text-yellow-700 border border-yellow-200',
    'pending_review': 'bg-purple-50 text-purple-700 border border-purple-200',
    'resolved': 'bg-green-50 text-green-700 border border-green-200',
    'closed': 'bg-gray-50 text-gray-700 border border-gray-200'
  }
  return classes[status] || classes.open
}

const getPriorityClasses = (priority) => {
  const classes = {
    'low': 'bg-gray-50 text-gray-700 border border-gray-200',
    'normal': 'bg-blue-50 text-blue-700 border border-blue-200',
    'high': 'bg-orange-50 text-orange-700 border border-orange-200',
    'urgent': 'bg-red-50 text-red-700 border border-red-200'
  }
  return classes[priority] || classes.normal
}

const getStatusDotClass = (status) => {
  const classes = {
    'open': 'bg-blue-500',
    'in_progress': 'bg-yellow-500',
    'pending_review': 'bg-purple-500',
    'resolved': 'bg-green-500',
    'closed': 'bg-gray-500'
  }
  return classes[status] || classes.open
}

const getPriorityIcon = (priority) => {
  const icons = {
    'low': '↓',
    'normal': '→',
    'high': '↑',
    'urgent': '⚠'
  }
  return icons[priority] || icons.normal
}

const getPriorityIconClass = (priority) => {
  const classes = {
    'low': 'text-gray-600',
    'normal': 'text-blue-600',
    'high': 'text-orange-600',
    'urgent': 'text-red-600'
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

// Use formatDuration from formats utility (imported above)

const formatHours = (seconds) => {
  if (!seconds) return '0.00 hrs'
  const hours = (seconds / 3600).toFixed(2)
  return `${hours} hrs`
}

// Use formatDate from formats utility (imported above)

// Removed inline editing functions for simplified table view
</script>

<style scoped>
.table-comfortable {
  @apply text-sm;
}

.table-compact {
  @apply text-xs;
}

.table-compact tbody tr:hover:not(.dropdown-open) {
  @apply shadow-sm;
  position: relative;
  z-index: 1;
}

.table-comfortable tbody tr:hover:not(.dropdown-open) {
  @apply shadow-md;
  position: relative;
  z-index: 1;
}

/* Fix z-index issues with dropdowns in table */
tbody tr td {
  position: static;
  overflow: visible;
}

/* Row with open dropdown gets highest priority */
tbody tr:has([data-headlessui-state="open"]) {
  position: relative;
  z-index: 10000 !important;
}

/* Normal hover state - but lower than open dropdowns */
tbody tr:hover:not(:has([data-headlessui-state="open"])) {
  z-index: 1;
}

/* Ensure the entire table respects dropdown z-index */
table {
  position: relative;
  z-index: auto;
}

/* Business-like table styling */
table {
  border-collapse: separate;
  border-spacing: 0;
}

tbody tr {
  transition: all 0.15s ease-in-out;
}

tbody tr:nth-child(even) {
  @apply bg-gray-50;
}

tbody tr:hover {
  transform: translateY(-1px);
}

/* Enhanced status and priority badges */
.inline-flex {
  display: inline-flex;
  align-items: center;
}

/* Text truncation utilities */
.line-clamp-1 {
  overflow: hidden;
  display: -webkit-box;
  -webkit-box-orient: vertical;
  -webkit-line-clamp: 1;
}

.line-clamp-2 {
  overflow: hidden;
  display: -webkit-box;
  -webkit-box-orient: vertical;
  -webkit-line-clamp: 2;
}
</style>