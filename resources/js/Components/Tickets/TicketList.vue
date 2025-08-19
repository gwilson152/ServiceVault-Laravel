<template>
  <!-- Loading State -->
  <div v-if="isLoading" class="p-8 text-center">
    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div>
    <p class="mt-2 text-gray-500">Loading tickets...</p>
  </div>
  
  <!-- Error State -->
  <div v-else-if="error" class="p-8 text-center">
    <div class="rounded-md bg-red-50 p-4">
      <div class="text-sm text-red-700">
        {{ errorMessage }}
      </div>
      <button 
        v-if="showRetryButton"
        @click="$emit('retry')"
        class="mt-2 bg-red-600 hover:bg-red-700 text-white font-medium py-2 px-4 rounded-lg transition-colors text-sm"
      >
        Retry
      </button>
    </div>
  </div>
  
  <!-- Empty State -->
  <div v-else-if="tickets.length === 0" class="p-8 text-center">
    <svg class="mx-auto h-12 w-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
    </svg>
    <h3 class="mt-2 text-sm font-medium text-gray-900">{{ emptyTitle }}</h3>
    <p class="mt-1 text-sm text-gray-500">
      {{ emptyMessage }}
    </p>
    <div v-if="showCreateButton" class="mt-6">
      <button
        @click="$emit('create-ticket')"
        class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg transition-colors"
      >
        {{ createButtonText }}
      </button>
    </div>
  </div>
  
  <!-- Business Table View -->
  <div v-else class="w-full">
    <div class="overflow-x-auto">
      <!-- Full TanStack Table (for main tickets page) -->
      <TicketsTable
        v-if="table"
        :table="table"
        :user="user"
        :density="density"
        @open-manual-time-entry="$emit('open-manual-time-entry', $event)"
        @open-ticket-addon="$emit('open-ticket-addon', $event)"
      />
      
      <!-- Simple Table (for contexts without TanStack Table) -->
      <table v-else class="w-full table-auto divide-y divide-gray-200">
        <thead class="bg-gray-50 border-b border-gray-300">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider border-b border-gray-200">
              Ticket Details
            </th>
            <th v-if="canAccessActions" class="w-20 px-3 py-3 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider border-b border-gray-200">
              Actions
            </th>
            <th class="w-40 px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider border-b border-gray-200">
              Assigned To
            </th>
            <th class="w-24 px-3 py-3 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider border-b border-gray-200">
              Total Time
            </th>
            <th class="w-28 px-3 py-3 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider border-b border-gray-200">
              Updated
            </th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-100">
          <tr 
            v-for="ticket in tickets" 
            :key="ticket.id" 
            class="hover:bg-blue-50 transition-all duration-150 cursor-pointer border-l-2 border-transparent hover:border-blue-300 hover:shadow-sm"
          >
            <!-- Combined Ticket Details Column -->
            <td :class="[
              density === 'compact' ? 'px-3 py-2' : 'px-6 py-4', 
              'border-b border-gray-100'
            ]"> <!-- Removed whitespace-nowrap to allow content expansion -->
              <div :class="density === 'compact' ? 'mb-1' : 'mb-2'">
                <div class="text-sm font-semibold text-blue-600 hover:text-blue-800">
                  <Link 
                    :href="route('tickets.show', ticket.id)" 
                    class="hover:underline focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-1 rounded"
                  >
                    {{ ticket.ticket_number || `#${ticket.id}` }}
                  </Link>
                </div>
                <div 
                  :class="[
                    'text-sm text-gray-700 max-w-sm',
                    density === 'compact' ? 'mt-0.5 line-clamp-1' : 'mt-1 line-clamp-2'
                  ]"
                >
                  {{ ticket.title }}
                </div>
              </div>
              
              <!-- Status & Priority Row -->
              <div 
                :class="[
                  'flex items-center gap-2',
                  density === 'compact' ? 'flex-wrap' : 'flex-row'
                ]"
              >
                <!-- Status Badge -->
                <span 
                  class="px-2 py-1 text-xs font-semibold rounded-full"
                  :class="getStatusColor(ticket.status)"
                >
                  {{ ticket.status }}
                </span>
                
                <!-- Priority Badge -->
                <span 
                  class="px-2 py-1 text-xs font-semibold rounded-full"
                  :class="getPriorityColor(ticket.priority)"
                >
                  {{ ticket.priority }}
                </span>
              </div>
            </td>
            
            <!-- Actions Column (only for users with action permissions) -->
            <td v-if="canAccessActions" :class="[
              density === 'compact' ? 'px-2 py-2' : 'px-3 py-4', 
              'w-20 text-center border-b border-gray-100'
            ]">
              <div class="flex items-center justify-center">
                <Link 
                  :href="route('tickets.show', ticket.id)"
                  class="text-xs bg-gray-100 hover:bg-gray-200 text-gray-700 px-2 py-1 rounded transition-colors"
                >
                  View
                </Link>
              </div>
            </td>
            
            <!-- Assigned To Column -->
            <td :class="[
              density === 'compact' ? 'px-3 py-2' : 'px-6 py-4', 
              'w-40 whitespace-nowrap text-sm text-gray-900 border-b border-gray-100'
            ]">
              <div class="flex items-center">
                <div class="flex-shrink-0 w-6 h-6 rounded-full bg-gray-300 flex items-center justify-center mr-2">
                  <span class="text-xs font-medium text-gray-700">
                    {{ (ticket.assigned_agent?.name || 'U').charAt(0).toUpperCase() }}
                  </span>
                </div>
                <span class="truncate">
                  {{ ticket.assigned_agent?.name || 'Unassigned' }}
                </span>
              </div>
            </td>
            
            <!-- Total Time Column -->
            <td :class="[
              density === 'compact' ? 'px-2 py-2' : 'px-3 py-4', 
              'w-24 whitespace-nowrap text-sm text-gray-500 border-b border-gray-100 text-center'
            ]">
              {{ formatDuration(ticket.total_time_logged || 0) }}
            </td>
            
            <!-- Updated Column -->
            <td :class="[
              density === 'compact' ? 'px-2 py-2' : 'px-3 py-4', 
              'w-28 whitespace-nowrap text-sm text-gray-500 border-b border-gray-100 text-center'
            ]">
              {{ formatDate(ticket.updated_at) }}
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import TicketsTable from '@/Components/Tables/TicketsTable.vue'

const props = defineProps({
  // Data
  tickets: {
    type: Array,
    required: true
  },
  table: {
    type: Object,
    required: true
  },
  user: {
    type: Object,
    required: true
  },
  
  // State
  isLoading: {
    type: Boolean,
    default: false
  },
  error: {
    type: Boolean,
    default: false
  },
  errorMessage: {
    type: String,
    default: 'Failed to load tickets. Please try again.'
  },
  
  // Density
  density: {
    type: String,
    default: 'compact',
    validator: (value) => ['comfortable', 'compact'].includes(value)
  },
  
  // Empty state
  emptyTitle: {
    type: String,
    default: 'No tickets found'
  },
  emptyMessage: {
    type: String,
    required: true
  },
  showCreateButton: {
    type: Boolean,
    default: true
  },
  createButtonText: {
    type: String,
    default: 'Create New Ticket'
  },
  
  // Actions
  showRetryButton: {
    type: Boolean,
    default: true
  }
})

const emit = defineEmits([
  'retry',
  'create-ticket',
  'open-manual-time-entry',
  'open-ticket-addon'
])

// ABAC: Check if user can access actions column
const isAccountUser = computed(() => props.user?.user_type === 'account_user')
const canAccessActions = computed(() => !isAccountUser.value)

// Helper functions for simple table
const getStatusColor = (status) => {
  const colors = {
    'open': 'bg-blue-100 text-blue-800',
    'in_progress': 'bg-yellow-100 text-yellow-800',
    'pending': 'bg-orange-100 text-orange-800',
    'resolved': 'bg-green-100 text-green-800',
    'closed': 'bg-gray-100 text-gray-800',
    'cancelled': 'bg-red-100 text-red-800'
  }
  return colors[status?.toLowerCase()] || 'bg-gray-100 text-gray-800'
}

const getPriorityColor = (priority) => {
  const colors = {
    'low': 'bg-green-100 text-green-800',
    'normal': 'bg-blue-100 text-blue-800',
    'high': 'bg-orange-100 text-orange-800',
    'urgent': 'bg-red-100 text-red-800'
  }
  return colors[priority?.toLowerCase()] || 'bg-gray-100 text-gray-800'
}

const formatDuration = (seconds) => {
  if (!seconds || seconds === 0) return '0:00'
  
  const hours = Math.floor(seconds / 3600)
  const minutes = Math.floor((seconds % 3600) / 60)
  
  if (hours > 0) {
    return `${hours}:${minutes.toString().padStart(2, '0')}h`
  }
  return `${minutes}:${(seconds % 60).toString().padStart(2, '0')}`
}

const formatDate = (dateString) => {
  const date = new Date(dateString)
  const now = new Date()
  const diffTime = now - date
  const diffDays = Math.floor(diffTime / (1000 * 60 * 60 * 24))

  if (diffDays === 0) return 'Today'
  if (diffDays === 1) return 'Yesterday'
  if (diffDays < 7) return `${diffDays} days ago`
  
  return date.toLocaleDateString()
}
</script>

<style scoped>
.line-clamp-1 {
  display: -webkit-box;
  -webkit-line-clamp: 1;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

/* Table auto-sizing styles */
.table-auto {
  table-layout: auto;
}

/* Ensure the main ticket details column expands */
table.table-auto th:first-child,
table.table-auto td:first-child {
  width: auto;
}

/* Fixed width columns */
table.table-auto .w-20 {
  width: 5rem !important;
  min-width: 5rem;
  max-width: 5rem;
}

table.table-auto .w-24 {
  width: 6rem !important;
  min-width: 6rem;
  max-width: 6rem;
}

table.table-auto .w-28 {
  width: 7rem !important;
  min-width: 7rem;
  max-width: 7rem;
}

table.table-auto .w-40 {
  width: 10rem !important;
  min-width: 10rem;
  max-width: 10rem;
}
</style>