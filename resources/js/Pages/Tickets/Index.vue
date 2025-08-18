<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Page Header -->
    <div class="bg-white shadow-sm border-b border-gray-200">
      <div class="px-4 sm:px-6 lg:px-8 py-4">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
          <div>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
              {{ pageTitle }}
            </h2>
            <p class="text-sm text-gray-600 mt-1 hidden sm:block">
              Manage tickets, track time, and monitor progress
            </p>
          </div>
          
          <!-- Actions -->
          <div class="flex items-center gap-2">
            <button
              @click="showCreateModal = true"
              class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-3 sm:px-4 rounded-lg transition-colors text-sm sm:text-base"
            >
              <svg class="w-4 h-4 inline mr-1 sm:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
              </svg>
              <span class="hidden sm:inline">New Ticket</span>
              <span class="sm:hidden">New</span>
            </button>
            
            <button
              @click="refreshTickets"
              :disabled="isLoading"
              class="p-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg transition-colors"
            >
              <svg class="w-4 h-4" :class="{ 'animate-spin': isLoading }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
              </svg>
            </button>
            
            <!-- Mobile sidebar toggle -->
            <button
              @click="showMobileSidebar = !showMobileSidebar"
              class="lg:hidden p-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg transition-colors"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
              </svg>
            </button>
          </div>
        </div>
      </div>
    </div>

    <div class="px-4 sm:px-6 lg:px-8 py-4 lg:py-6">
      <!-- Mobile Filters Toggle -->
      <div class="lg:hidden mb-4">
        <button
          @click="showMobileFilters = !showMobileFilters"
          class="w-full flex items-center justify-between bg-white rounded-lg shadow-sm border border-gray-200 px-4 py-3"
        >
          <span class="font-medium text-gray-900">Filters & Search</span>
          <svg 
            class="w-5 h-5 text-gray-400 transition-transform"
            :class="{ 'rotate-180': showMobileFilters }"
            fill="none" 
            stroke="currentColor" 
            viewBox="0 0 24 24"
          >
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
          </svg>
        </button>
      </div>

      <!-- Filters Section (Collapsible on mobile) -->
      <div 
        v-show="showMobileFilters || !isMobile"
        class="bg-white rounded-lg shadow-sm border border-gray-200 mb-4 lg:mb-6"
      >
        <div class="p-4 border-b border-gray-200 hidden lg:block">
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
              placeholder="Search tickets..."
              class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm sm:text-base"
            >
          </div>
          
          <!-- Filter Pills - Responsive Grid -->
          <div class="grid grid-cols-2 sm:flex sm:flex-wrap gap-2">
            <!-- Status Filter -->
            <select
              v-model="statusFilter"
              class="appearance-none bg-white border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            >
              <option value="">All Statuses</option>
              <option 
                v-for="status in ticketStatuses" 
                :key="status.key" 
                :value="status.key"
              >
                {{ status.name }}
              </option>
            </select>
            
            <!-- Priority Filter -->
            <select
              v-model="priorityFilter"
              class="appearance-none bg-white border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            >
              <option value="">All Priorities</option>
              <option 
                v-for="priority in ticketPriorities" 
                :key="priority.key" 
                :value="priority.key"
              >
                {{ priority.name }}
              </option>
            </select>
            
            <!-- Assignment Filter -->
            <select
              v-model="assignmentFilter"
              class="appearance-none bg-white border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            >
              <option value="">All Assignments</option>
              <option value="mine">My Tickets</option>
              <option value="unassigned">Unassigned</option>
            </select>
            
            <!-- Account Filter (if service provider) -->
            <select
              v-if="canViewAllAccounts"
              v-model="accountFilter"
              class="appearance-none bg-white border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
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
            
            <!-- Clear Filters -->
            <button
              v-if="hasActiveFilters"
              @click="clearFilters"
              class="col-span-2 sm:col-span-1 text-sm text-red-600 hover:text-red-700 font-medium bg-red-50 px-3 py-2 rounded-lg"
            >
              Clear Filters
            </button>
          </div>
        </div>
      </div>

      <!-- Main Layout - Responsive Grid -->
      <div class="lg:grid lg:grid-cols-12 lg:gap-6">
        <!-- Main Content Area -->
        <div class="lg:col-span-8 xl:col-span-9">
          <!-- Tickets Container -->
          <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="p-4 border-b border-gray-200">
              <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900">
                  Tickets
                  <span class="text-sm font-normal text-gray-500 ml-2">
                    ({{ filteredTickets.length }} of {{ tickets?.length || 0 }})
                  </span>
                </h3>
                
                <!-- Table Density Toggle -->
                <div class="flex items-center space-x-2">
                  <span class="text-sm text-gray-500">Density:</span>
                  <div class="flex items-center space-x-1">
                    <button
                      @click="tableDensity = 'comfortable'"
                      :class="[
                        'px-2 py-1 rounded text-xs font-medium transition-colors',
                        tableDensity === 'comfortable' 
                          ? 'bg-blue-100 text-blue-700' 
                          : 'text-gray-600 hover:text-gray-700 hover:bg-gray-100'
                      ]"
                      title="Comfortable spacing"
                    >
                      Comfortable
                    </button>
                    <button
                      @click="tableDensity = 'compact'"
                      :class="[
                        'px-2 py-1 rounded text-xs font-medium transition-colors',
                        tableDensity === 'compact' 
                          ? 'bg-blue-100 text-blue-700' 
                          : 'text-gray-600 hover:text-gray-700 hover:bg-gray-100'
                      ]"
                      title="Compact spacing for maximum data density"
                    >
                      Compact
                    </button>
                  </div>
                </div>
              </div>
            </div>
            
            <!-- Ticket List -->
            <TicketList
              :tickets="filteredTickets"
              :table="table"
              :user="user"
              :timers-by-ticket="timersByTicket"
              :ticket-statuses="ticketStatuses"
              :ticket-priorities="ticketPriorities"
              :workflow-transitions="workflowTransitions"
              :is-loading="isLoading"
              :error="error"
              error-message="Failed to load tickets. Please try again."
              :density="tableDensity"
              empty-title="No tickets found"
              :empty-message="hasActiveFilters ? 'Try adjusting your filters' : 'Get started by creating a new ticket'"
              :show-create-button="!hasActiveFilters"
              create-button-text="Create New Ticket"
              :show-retry-button="true"
              @retry="refetchTickets"
              @create-ticket="showCreateModal = true"
              @timer-started="handleTimerEvent"
              @timer-stopped="handleTimerEvent"
              @timer-paused="handleTimerEvent"
              @time-entry-created="handleTimeEntryCreated"
              @open-manual-time-entry="openManualTimeEntry"
              @open-ticket-addon="openTicketAddon"
              @status-updated="handleStatusUpdated"
              @priority-updated="handlePriorityUpdated"
            />
            
          </div>
        </div>
        
        <!-- Sidebar - Hidden on mobile, shown on desktop -->
        <div 
          :class="[
            'lg:col-span-4 xl:col-span-3 space-y-4 mt-4 lg:mt-0',
            showMobileSidebar ? 'block' : 'hidden lg:block'
          ]"
        >
          <!-- Quick Stats Widget -->
          <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="p-4 border-b border-gray-200">
              <h3 class="text-lg font-semibold text-gray-900">Quick Stats</h3>
            </div>
            <div class="p-4 space-y-3">
              <div class="flex items-center justify-between">
                <span class="text-sm text-gray-600">Total Tickets</span>
                <span class="text-lg font-semibold text-gray-900">{{ tickets?.length || 0 }}</span>
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
                  v-for="timer in activeTimers.slice(0, 5)"
                  :key="timer.id"
                  class="flex items-center justify-between text-sm p-2 bg-gray-50 rounded"
                >
                  <span class="truncate">{{ timer.ticket_number || 'No Ticket' }}</span>
                  <span class="font-mono text-green-600 ml-2">{{ formatDuration(timer.duration) }}</span>
                </div>
                <div v-if="activeTimers.length > 5" class="text-xs text-gray-500 text-center pt-1">
                  And {{ activeTimers.length - 5 }} more...
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
                  v-for="activity in recentActivity.slice(0, 5)"
                  :key="activity.id"
                  class="flex items-start space-x-2"
                >
                  <div :class="getActivityIcon(activity.type)" class="w-2 h-2 rounded-full mt-2 flex-shrink-0"></div>
                  <div class="min-w-0 flex-1">
                    <p class="text-gray-900 truncate">{{ activity.description }}</p>
                    <p class="text-gray-500 text-xs">{{ formatDate(activity.created_at) }}</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Create Ticket Modal -->
    <CreateTicketModalTabbed
      :show="showCreateModal"
      @close="showCreateModal = false"
      @ticket-created="onTicketCreated"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import TicketTimerControls from '@/Components/Timer/TicketTimerControls.vue'
import CreateTicketModalTabbed from '@/Components/Modals/CreateTicketModalTabbed.vue'
import TicketsTable from '@/Components/Tables/TicketsTable.vue'
import TicketList from '@/Components/Tickets/TicketList.vue'
import { useTicketsTable } from '@/Composables/useTicketsTable'
import { useTicketsQuery } from '@/Composables/queries/useTicketsQuery'

// Define persistent layout
defineOptions({
  layout: AppLayout
})

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
const activeTimers = ref(props.initialActiveTimers || [])
const timersByTicket = ref({}) // Store timers grouped by ticket_id
const showCreateModal = ref(false)
// Load table density preference from localStorage
const tableDensity = ref(localStorage.getItem('tickets-table-density') || 'compact')

// Watch for density changes and save to localStorage
watch(tableDensity, (newDensity) => {
  localStorage.setItem('tickets-table-density', newDensity)
}, { immediate: false })
const showMobileFilters = ref(false)
const showMobileSidebar = ref(false)
const isMobile = ref(window.innerWidth < 1024)

// Ticket configuration data for dropdowns
const ticketStatuses = ref([])
const ticketPriorities = ref([])
const workflowTransitions = ref({})

// Filters
const searchQuery = ref('')
const statusFilter = ref('')
const priorityFilter = ref('')
const assignmentFilter = ref('')
const accountFilter = ref('')

// TanStack Query for tickets
const ticketFilters = computed(() => ({
  search: searchQuery.value,
  status: statusFilter.value,
  priority: priorityFilter.value,
  assignment: assignmentFilter.value,
  account_id: accountFilter.value
}))

const { data: tickets, isLoading, error, refetch: refetchTickets } = useTicketsQuery(ticketFilters)

// Page data
const page = usePage()
const user = computed(() => page.props.auth?.user)

// Initialize TanStack Table
const ticketsForTable = computed(() => {
  const ticketsData = tickets.value?.data || tickets.value || []
  return Array.isArray(ticketsData) ? ticketsData : []
})

const {
  table,
  globalFilter,
  setAssignmentFilter,
  setAccountFilter,
  clearAllFilters,
} = useTicketsTable(ticketsForTable, user, props.canViewAllAccounts)

// Computed
const pageTitle = computed(() => {
  return props.canViewAllAccounts ? 'Service Tickets' : 'My Tickets'
})

const hasActiveFilters = computed(() => {
  return !!(searchQuery.value || statusFilter.value || priorityFilter.value || assignmentFilter.value || accountFilter.value)
})

// Sync search query with TanStack global filter
watch(searchQuery, (newValue) => {
  table.setGlobalFilter(newValue)
})

// Sync individual filters with TanStack column filters
// Status and priority filters removed - now handled through global search

watch(assignmentFilter, (newValue) => {
  setAssignmentFilter(newValue, user.value?.id)
})

watch(accountFilter, (newValue) => {
  setAccountFilter(newValue)
})

// Use TanStack Table's filtered rows
const filteredTickets = computed(() => {
  return table.getFilteredRowModel().rows.map(row => row.original)
})

const recentActivity = computed(() => {
  // Generate recent activity from ticket updates
  const ticketsData = tickets.value?.data || tickets.value || []
  return ticketsData
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
  // Use TanStack Query's refetch instead of manual fetch
  await refetchTickets()
}

// Debounce timer to prevent excessive API calls
let timerRefreshDebounce = null

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
    // Only log errors in development mode
    if (import.meta.env.DEV) {
      console.error('Failed to refresh active timers:', error)
    }
  }
}

const fetchTimersForTickets = async () => {
  const ticketsData = tickets.value?.data || tickets.value || []
  if (!ticketsData.length) return
  
  try {
    const ticketIds = ticketsData.map(ticket => ticket.id)
    
    const response = await fetch('/api/timers/bulk-active-for-tickets', {
      method: 'POST',
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.head.querySelector('meta[name="csrf-token"]').content
      },
      body: JSON.stringify({
        ticket_ids: ticketIds
      })
    })

    if (response.ok) {
      const data = await response.json()
      timersByTicket.value = data.data || {}
    } else if (import.meta.env.DEV) {
      console.error('Failed to fetch bulk timers - HTTP', response.status)
    }
  } catch (error) {
    // Only log errors in development mode
    if (import.meta.env.DEV) {
      console.error('Failed to fetch timers for tickets:', error)
    }
  }
}

// Load ticket configuration data for dropdowns
const loadTicketConfig = async () => {
  try {
    const response = await fetch('/api/settings/ticket-config', {
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.head.querySelector('meta[name="csrf-token"]').content
      }
    })

    if (response.ok) {
      const data = await response.json()
      ticketStatuses.value = data.data.statuses || []
      ticketPriorities.value = data.data.priorities || []
      workflowTransitions.value = data.data.workflow_transitions || {}
    }
  } catch (error) {
    if (import.meta.env.DEV) {
      console.error('Failed to load ticket configuration:', error)
    }
  }
}

const clearFilters = () => {
  searchQuery.value = ''
  statusFilter.value = ''
  priorityFilter.value = ''
  assignmentFilter.value = ''
  accountFilter.value = ''
  clearAllFilters()
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
  return tickets?.value?.filter(ticket => ticket.status === status).length || 0
}

const getMyTicketsCount = () => {
  return tickets?.value?.filter(ticket => ticket.assigned_to_id === user.value?.id).length || 0
}

// Unified timer refresh with debouncing
const refreshTimerData = async () => {
  await Promise.all([
    refreshActiveTimers(),
    fetchTimersForTickets()
  ])
}

const handleTimerEvent = (event) => {
  // Debounce timer refresh to prevent excessive API calls
  if (timerRefreshDebounce) {
    clearTimeout(timerRefreshDebounce)
  }
  
  timerRefreshDebounce = setTimeout(() => {
    refreshTimerData()
  }, 100) // 100ms debounce
  
  // Only refresh tickets if timer was converted to time entry
  if (event.type === 'timer_stopped' && event.converted_to_time_entry) {
    refetchTickets()
  }
}

const onTicketCreated = (newTicket) => {
  // TanStack Query mutation already handled the cache update
  // Just close the modal
  showCreateModal.value = false
}

const handleTimeEntryCreated = (timeEntry) => {
  // Refresh tickets to show updated time data
  refetchTickets()
  // Refresh active timers in case timer was committed
  refreshActiveTimers()
}

const handleStatusUpdated = (event) => {
  if (event.error) {
    // Handle error case - could show toast notification
    if (import.meta.env.DEV) {
      console.error('Status update failed:', event.error)
    }
  } else {
    // Refetch tickets data to get latest state
    refetchTickets()
  }
}

const handlePriorityUpdated = (event) => {
  if (event.error) {
    // Handle error case - could show toast notification
    if (import.meta.env.DEV) {
      console.error('Priority update failed:', event.error)
    }
  } else {
    // Refetch tickets data to get latest state
    refetchTickets()
  }
}

const openManualTimeEntry = (ticket) => {
  // TODO: Implement manual time entry dialog
  if (import.meta.env.DEV) {
    console.log('Manual time entry requested for ticket:', ticket.ticket_number)
  }
}

const openTicketAddon = (ticket) => {
  // TODO: Implement ticket addon dialog
  if (import.meta.env.DEV) {
    console.log('Ticket addon requested for ticket:', ticket.ticket_number)
  }
}

// Handle window resize
const handleResize = () => {
  isMobile.value = window.innerWidth < 1024
  if (!isMobile.value) {
    showMobileFilters.value = false
    showMobileSidebar.value = false
  }
}

// Lifecycle
onMounted(() => {
  // Initial load - use unified function
  refreshTimerData()
  
  // Load ticket configuration for dropdowns
  loadTicketConfig()
  
  // Add resize listener
  window.addEventListener('resize', handleResize)
  
  // Set up periodic refresh with unified function
  const timerInterval = setInterval(() => {
    refreshTimerData()
  }, 30000) // Every 30 seconds
  
  // Cleanup
  onUnmounted(() => {
    clearInterval(timerInterval)
    if (timerRefreshDebounce) {
      clearTimeout(timerRefreshDebounce)
    }
    window.removeEventListener('resize', handleResize)
  })
})
</script>

<style scoped>
/* Custom scrollbar for horizontal scroll */
.overflow-x-auto::-webkit-scrollbar {
  height: 8px;
}

.overflow-x-auto::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 4px;
}

.overflow-x-auto::-webkit-scrollbar-thumb {
  background: #888;
  border-radius: 4px;
}

.overflow-x-auto::-webkit-scrollbar-thumb:hover {
  background: #555;
}
</style>