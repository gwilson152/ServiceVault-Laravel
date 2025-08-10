<template>
  <AppLayout :title="pageTitle">
    <template #header>
      <div class="flex items-center justify-between">
        <div>
          <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ pageTitle }}
          </h2>
          <p class="text-sm text-gray-600 mt-1">
            Manage tickets, track time, and monitor progress
          </p>
        </div>
        
        <!-- Actions -->
        <div class="flex items-center space-x-3">
          <button
            @click="showCreateModal = true"
            class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg transition-colors"
          >
            <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
            </svg>
            New Ticket
          </button>
          
          <button
            @click="refreshTickets"
            :disabled="isLoading"
            class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-medium py-2 px-4 rounded-lg transition-colors"
          >
            <svg class="w-4 h-4" :class="{ 'animate-spin': isLoading }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
          </button>
        </div>
      </div>
    </template>

    <div class="py-6">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <!-- Page Layout: Main Content + Sidebar -->
        <div class="flex gap-6">
          <!-- Main Content Area -->
          <div class="flex-1">
            <!-- Filters and Search -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6">
              <div class="p-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Filters & Search</h3>
              </div>
              <div class="p-4 space-y-4">
                <!-- Search Bar -->
                <div class="relative">
                  <svg class="absolute left-3 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                  </svg>
                  <input
                    v-model="searchQuery"
                    type="text"
                    placeholder="Search tickets by number, title, or description..."
                    class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                  >
                </div>
                
                <!-- Filter Pills -->
                <div class="flex flex-wrap gap-2">
                  <!-- Status Filter -->
                  <div class="relative">
                    <select
                      v-model="statusFilter"
                      class="appearance-none bg-white border border-gray-300 rounded-lg px-3 py-2 pr-8 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    >
                      <option value="">All Statuses</option>
                      <option value="open">Open</option>
                      <option value="in_progress">In Progress</option>
                      <option value="pending_review">Pending Review</option>
                      <option value="resolved">Resolved</option>
                      <option value="closed">Closed</option>
                    </select>
                  </div>
                  
                  <!-- Priority Filter -->
                  <div class="relative">
                    <select
                      v-model="priorityFilter"
                      class="appearance-none bg-white border border-gray-300 rounded-lg px-3 py-2 pr-8 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    >
                      <option value="">All Priorities</option>
                      <option value="low">Low</option>
                      <option value="normal">Normal</option>
                      <option value="high">High</option>
                      <option value="urgent">Urgent</option>
                    </select>
                  </div>
                  
                  <!-- Assignment Filter -->
                  <div class="relative">
                    <select
                      v-model="assignmentFilter"
                      class="appearance-none bg-white border border-gray-300 rounded-lg px-3 py-2 pr-8 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    >
                      <option value="">All Assignments</option>
                      <option value="mine">My Tickets</option>
                      <option value="unassigned">Unassigned</option>
                    </select>
                  </div>
                  
                  <!-- Account Filter (if service provider) -->
                  <div v-if="canViewAllAccounts" class="relative">
                    <select
                      v-model="accountFilter"
                      class="appearance-none bg-white border border-gray-300 rounded-lg px-3 py-2 pr-8 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    >
                      <option value="">All Accounts</option>
                      <option 
                        v-for="account in availableAccounts" 
                        :key="account.id" 
                        :value="account.id"
                      >
                        {{ account.name }}
                      </option>
                    </select>
                  </div>
                  
                  <!-- Clear Filters -->
                  <button
                    v-if="hasActiveFilters"
                    @click="clearFilters"
                    class="text-sm text-red-600 hover:text-red-700 font-medium"
                  >
                    Clear Filters
                  </button>
                </div>
              </div>
            </div>

            <!-- Tickets Table -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
              <div class="p-4 border-b border-gray-200">
                <div class="flex items-center justify-between">
                  <h3 class="text-lg font-semibold text-gray-900">
                    Tickets
                    <span class="text-sm font-normal text-gray-500 ml-2">
                      ({{ filteredTickets.length }} of {{ tickets.length }})
                    </span>
                  </h3>
                  
                  <!-- View Toggle -->
                  <div class="flex items-center space-x-1">
                    <button
                      @click="viewMode = 'table'"
                      :class="[
                        'px-3 py-1 rounded text-sm font-medium transition-colors',
                        viewMode === 'table' 
                          ? 'bg-blue-100 text-blue-700' 
                          : 'text-gray-600 hover:text-gray-700'
                      ]"
                    >
                      Table
                    </button>
                    <button
                      @click="viewMode = 'cards'"
                      :class="[
                        'px-3 py-1 rounded text-sm font-medium transition-colors',
                        viewMode === 'cards' 
                          ? 'bg-blue-100 text-blue-700' 
                          : 'text-gray-600 hover:text-gray-700'
                      ]"
                    >
                      Cards
                    </button>
                  </div>
                </div>
              </div>
              
              <!-- Loading State -->
              <div v-if="isLoading" class="p-8 text-center">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div>
                <p class="mt-2 text-gray-500">Loading tickets...</p>
              </div>
              
              <!-- Empty State -->
              <div v-else-if="filteredTickets.length === 0" class="p-8 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">No tickets found</h3>
                <p class="mt-1 text-sm text-gray-500">
                  {{ hasActiveFilters ? 'Try adjusting your filters' : 'Get started by creating a new ticket' }}
                </p>
                <div class="mt-6">
                  <button
                    v-if="!hasActiveFilters"
                    @click="showCreateModal = true"
                    class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg transition-colors"
                  >
                    Create New Ticket
                  </button>
                </div>
              </div>
              
              <!-- Table View -->
              <div v-else-if="viewMode === 'table'" class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                  <thead class="bg-gray-50">
                    <tr>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Ticket
                      </th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Status
                      </th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Priority
                      </th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Assigned To
                      </th>
                      <th v-if="canViewAllAccounts" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Account
                      </th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Time Tracked
                      </th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Timer
                      </th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Updated
                      </th>
                      <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Actions
                      </th>
                    </tr>
                  </thead>
                  <tbody class="bg-white divide-y divide-gray-200">
                    <tr
                      v-for="ticket in filteredTickets"
                      :key="ticket.id"
                      class="hover:bg-gray-50 transition-colors"
                    >
                      <!-- Ticket Info -->
                      <td class="px-6 py-4 whitespace-nowrap">
                        <div>
                          <div class="text-sm font-medium text-blue-600 hover:text-blue-800">
                            <a :href="`/tickets/${ticket.id}`" class="hover:underline">
                              {{ ticket.ticket_number }}
                            </a>
                          </div>
                          <div class="text-sm text-gray-900 mt-1 max-w-xs truncate">
                            {{ ticket.title }}
                          </div>
                        </div>
                      </td>
                      
                      <!-- Status -->
                      <td class="px-6 py-4 whitespace-nowrap">
                        <span :class="getStatusClasses(ticket.status)" class="px-2 py-1 text-xs font-medium rounded-full">
                          {{ formatStatus(ticket.status) }}
                        </span>
                      </td>
                      
                      <!-- Priority -->
                      <td class="px-6 py-4 whitespace-nowrap">
                        <span :class="getPriorityClasses(ticket.priority)" class="px-2 py-1 text-xs font-medium rounded-full">
                          {{ formatPriority(ticket.priority) }}
                        </span>
                      </td>
                      
                      <!-- Assigned To -->
                      <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ ticket.assigned_to?.name || 'Unassigned' }}
                      </td>
                      
                      <!-- Account (if service provider) -->
                      <td v-if="canViewAllAccounts" class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ ticket.account?.name }}
                      </td>
                      
                      <!-- Time Tracked -->
                      <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ formatDuration(ticket.total_time_logged) }}
                      </td>
                      
                      <!-- Timer Controls -->
                      <td class="px-6 py-4 whitespace-nowrap">
                        <TicketTimerControls
                          :ticket="ticket"
                          @timer-started="handleTimerEvent"
                          @timer-stopped="handleTimerEvent"
                          @timer-paused="handleTimerEvent"
                        />
                      </td>
                      
                      <!-- Updated -->
                      <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {{ formatDate(ticket.updated_at) }}
                      </td>
                      
                      <!-- Actions -->
                      <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <div class="flex items-center justify-end space-x-2">
                          <button
                            @click="viewTicket(ticket)"
                            class="text-blue-600 hover:text-blue-700 font-medium"
                          >
                            View
                          </button>
                          <button
                            v-if="canEditTicket(ticket)"
                            @click="editTicket(ticket)"
                            class="text-gray-600 hover:text-gray-700 font-medium"
                          >
                            Edit
                          </button>
                        </div>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              
              <!-- Cards View -->
              <div v-else-if="viewMode === 'cards'" class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                  <div
                    v-for="ticket in filteredTickets"
                    :key="ticket.id"
                    class="bg-white border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow"
                  >
                    <!-- Card Header -->
                    <div class="flex items-start justify-between mb-3">
                      <div>
                        <h4 class="text-sm font-medium text-blue-600 hover:text-blue-800">
                          <a :href="`/tickets/${ticket.id}`" class="hover:underline">
                            {{ ticket.ticket_number }}
                          </a>
                        </h4>
                        <p class="text-sm text-gray-900 mt-1 line-clamp-2">
                          {{ ticket.title }}
                        </p>
                      </div>
                      <span :class="getPriorityClasses(ticket.priority)" class="px-2 py-1 text-xs font-medium rounded-full">
                        {{ formatPriority(ticket.priority) }}
                      </span>
                    </div>
                    
                    <!-- Card Content -->
                    <div class="space-y-2 mb-4">
                      <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-600">Status:</span>
                        <span :class="getStatusClasses(ticket.status)" class="px-2 py-1 text-xs font-medium rounded-full">
                          {{ formatStatus(ticket.status) }}
                        </span>
                      </div>
                      
                      <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-600">Assigned:</span>
                        <span class="text-gray-900">{{ ticket.assigned_to?.name || 'Unassigned' }}</span>
                      </div>
                      
                      <div v-if="canViewAllAccounts" class="flex items-center justify-between text-sm">
                        <span class="text-gray-600">Account:</span>
                        <span class="text-gray-900">{{ ticket.account?.name }}</span>
                      </div>
                      
                      <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-600">Time:</span>
                        <span class="text-gray-900">{{ formatDuration(ticket.total_time_logged) }}</span>
                      </div>
                    </div>
                    
                    <!-- Card Footer -->
                    <div class="flex items-center justify-between">
                      <TicketTimerControls
                        :ticket="ticket"
                        @timer-started="handleTimerEvent"
                        @timer-stopped="handleTimerEvent"
                        @timer-paused="handleTimerEvent"
                        compact
                      />
                      
                      <div class="flex items-center space-x-2">
                        <button
                          @click="viewTicket(ticket)"
                          class="text-xs text-blue-600 hover:text-blue-700 font-medium"
                        >
                          View
                        </button>
                        <button
                          v-if="canEditTicket(ticket)"
                          @click="editTicket(ticket)"
                          class="text-xs text-gray-600 hover:text-gray-700 font-medium"
                        >
                          Edit
                        </button>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Sidebar with Widgets -->
          <div class="w-80 space-y-6">
            <!-- Quick Stats Widget -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
              <div class="p-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Quick Stats</h3>
              </div>
              <div class="p-4 space-y-3">
                <div class="flex items-center justify-between">
                  <span class="text-sm text-gray-600">Total Tickets</span>
                  <span class="text-lg font-semibold text-gray-900">{{ tickets.length }}</span>
                </div>
                <div class="flex items-center justify-between">
                  <span class="text-sm text-gray-600">Open</span>
                  <span class="text-lg font-semibold text-blue-600">{{ getStatusCount('open') }}</span>
                </div>
                <div class="flex items-center justify-between">
                  <span class="text-sm text-gray-600">In Progress</span>
                  <span class="text-lg font-semibold text-yellow-600">{{ getStatusCount('in_progress') }}</span>
                </div>
                <div class="flex items-center justify-between">
                  <span class="text-sm text-gray-600">My Tickets</span>
                  <span class="text-lg font-semibold text-green-600">{{ getMyTicketsCount() }}</span>
                </div>
              </div>
            </div>
            
            <!-- Active Timers Widget -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
              <div class="p-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Active Timers</h3>
              </div>
              <div class="p-4">
                <div v-if="activeTimers.length === 0" class="text-center text-gray-500 text-sm">
                  No active timers
                </div>
                <div v-else class="space-y-2">
                  <div
                    v-for="timer in activeTimers"
                    :key="timer.id"
                    class="flex items-center justify-between text-sm p-2 bg-gray-50 rounded"
                  >
                    <span class="truncate">{{ timer.ticket_number || 'No Ticket' }}</span>
                    <span class="font-mono text-green-600">{{ formatDuration(timer.duration) }}</span>
                  </div>
                </div>
              </div>
            </div>
            
            <!-- Recent Activity Widget -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
              <div class="p-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Recent Activity</h3>
              </div>
              <div class="p-4">
                <div class="space-y-3 text-sm">
                  <div
                    v-for="activity in recentActivity"
                    :key="activity.id"
                    class="flex items-start space-x-2"
                  >
                    <div :class="getActivityIcon(activity.type)" class="w-2 h-2 rounded-full mt-2 flex-shrink-0"></div>
                    <div>
                      <p class="text-gray-900">{{ activity.description }}</p>
                      <p class="text-gray-500 text-xs">{{ formatDate(activity.created_at) }}</p>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Create Ticket Modal -->
    <CreateTicketModal
      :show="showCreateModal"
      @close="showCreateModal = false"
      @created="onTicketCreated"
    />
  </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import TicketTimerControls from '@/Components/Timer/TicketTimerControls.vue'
import CreateTicketModal from '@/Components/Modals/CreateTicketModal.vue'

// Props
const props = defineProps({
  initialTickets: {
    type: Array,
    default: () => []
  },
  initialActiveTimers: {
    type: Array,
    default: () => []
  },
  availableAccounts: {
    type: Array,
    default: () => []
  },
  canViewAllAccounts: {
    type: Boolean,
    default: false
  },
  permissions: {
    type: Object,
    default: () => ({})
  }
})

// State
const tickets = ref(props.initialTickets || [])
const activeTimers = ref(props.initialActiveTimers || [])
const isLoading = ref(false)
const showCreateModal = ref(false)
const viewMode = ref('table')

// Filters
const searchQuery = ref('')
const statusFilter = ref('')
const priorityFilter = ref('')
const assignmentFilter = ref('')
const accountFilter = ref('')

// Page data
const page = usePage()
const user = computed(() => page.props.auth?.user)

// Computed
const pageTitle = computed(() => {
  return props.canViewAllAccounts ? 'Service Tickets' : 'My Tickets'
})

const hasActiveFilters = computed(() => {
  return !!(searchQuery.value || statusFilter.value || priorityFilter.value || assignmentFilter.value || accountFilter.value)
})

const filteredTickets = computed(() => {
  let filtered = tickets.value

  // Search filter
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    filtered = filtered.filter(ticket => 
      ticket.ticket_number.toLowerCase().includes(query) ||
      ticket.title.toLowerCase().includes(query) ||
      (ticket.description && ticket.description.toLowerCase().includes(query))
    )
  }

  // Status filter
  if (statusFilter.value) {
    filtered = filtered.filter(ticket => ticket.status === statusFilter.value)
  }

  // Priority filter
  if (priorityFilter.value) {
    filtered = filtered.filter(ticket => ticket.priority === priorityFilter.value)
  }

  // Assignment filter
  if (assignmentFilter.value) {
    if (assignmentFilter.value === 'mine') {
      filtered = filtered.filter(ticket => ticket.assigned_to_id === user.value?.id)
    } else if (assignmentFilter.value === 'unassigned') {
      filtered = filtered.filter(ticket => !ticket.assigned_to_id)
    }
  }

  // Account filter
  if (accountFilter.value) {
    filtered = filtered.filter(ticket => ticket.account_id === parseInt(accountFilter.value))
  }

  return filtered
})

const recentActivity = computed(() => {
  // Generate recent activity from ticket updates
  return tickets.value
    .filter(ticket => ticket.updated_at)
    .sort((a, b) => new Date(b.updated_at) - new Date(a.updated_at))
    .slice(0, 5)
    .map(ticket => ({
      id: ticket.id,
      type: 'ticket_update',
      description: `${ticket.ticket_number} was updated`,
      created_at: ticket.updated_at
    }))
})

// Methods
const refreshTickets = async () => {
  isLoading.value = true
  try {
    const response = await fetch('/api/tickets', {
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.head.querySelector('meta[name="csrf-token"]').content
      }
    })

    if (response.ok) {
      const data = await response.json()
      tickets.value = data.data || []
    }
  } catch (error) {
    console.error('Failed to refresh tickets:', error)
  } finally {
    isLoading.value = false
  }
}

const refreshActiveTimers = async () => {
  try {
    const response = await fetch('/api/timers/active/current', {
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.head.querySelector('meta[name="csrf-token"]').content
      }
    })

    if (response.ok) {
      const data = await response.json()
      activeTimers.value = data.data || []
    }
  } catch (error) {
    console.error('Failed to refresh active timers:', error)
  }
}

const clearFilters = () => {
  searchQuery.value = ''
  statusFilter.value = ''
  priorityFilter.value = ''
  assignmentFilter.value = ''
  accountFilter.value = ''
}

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

const getActivityIcon = (type) => {
  const classes = {
    'ticket_update': 'bg-blue-500',
    'timer_start': 'bg-green-500',
    'timer_stop': 'bg-red-500',
    'status_change': 'bg-purple-500'
  }
  return classes[type] || classes.ticket_update
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

const getStatusCount = (status) => {
  return tickets.value.filter(ticket => ticket.status === status).length
}

const getMyTicketsCount = () => {
  return tickets.value.filter(ticket => ticket.assigned_to_id === user.value?.id).length
}

const canEditTicket = (ticket) => {
  // Can edit if assigned to user or if user has admin permissions
  return ticket.assigned_to_id === user.value?.id || props.permissions.canEditAllTickets
}

const viewTicket = (ticket) => {
  // Navigate to ticket detail view
  window.location.href = `/tickets/${ticket.id}`
}

const editTicket = (ticket) => {
  // Navigate to ticket edit view
  window.location.href = `/tickets/${ticket.id}/edit`
}

const handleTimerEvent = (event) => {
  // Refresh active timers when timer events occur
  refreshActiveTimers()
  
  // Optionally refresh tickets if timer affects displayed data
  if (event.type === 'timer_stopped' && event.converted_to_time_entry) {
    refreshTickets()
  }
}

const onTicketCreated = (newTicket) => {
  tickets.value.unshift(newTicket)
  showCreateModal.value = false
}

// Lifecycle
onMounted(() => {
  refreshActiveTimers()
  
  // Set up periodic refresh for active timers
  const timerInterval = setInterval(refreshActiveTimers, 30000) // Every 30 seconds
  
  // Cleanup
  return () => {
    clearInterval(timerInterval)
  }
})

// Watchers
watch([searchQuery, statusFilter, priorityFilter, assignmentFilter, accountFilter], () => {
  // Optional: debounce and make API calls for server-side filtering
  // For now, we're doing client-side filtering
}, { debounce: 300 })
</script>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>