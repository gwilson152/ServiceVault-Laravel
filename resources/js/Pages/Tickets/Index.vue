<template>
  <StandardPageLayout
    :title="pageTitle"
    subtitle="Manage tickets, track time, and monitor progress"
    :show-sidebar="true"
    :show-filters="true"
  >
    <!-- Header Actions -->
    <template #header-actions>
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
    </template>

    <!-- Filters -->
    <template #filters>
      <FilterSection>
        <div class="space-y-4">
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
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
            <!-- Status Filter -->
            <div>
              <label class="block text-xs font-medium text-gray-700 mb-1">Status</label>
              <MultiSelect
                v-model="statusFilter"
                :options="ticketStatuses"
                value-key="key"
                label-key="name"
                placeholder="Select statuses..."
                :max-display-items="2"
              />
            </div>
            
            <!-- Priority Filter -->
            <div>
              <label class="block text-xs font-medium text-gray-700 mb-1">Priority</label>
              <MultiSelect
                v-model="priorityFilter"
                :options="ticketPriorities"
                value-key="key"
                label-key="name"
                placeholder="Select priorities..."
                :max-display-items="2"
              />
            </div>
            
            <!-- Assignment Filter -->
            <div>
              <label class="block text-xs font-medium text-gray-700 mb-1">Assignment</label>
              <select
                v-model="assignmentFilter"
                class="appearance-none bg-white border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 w-full"
              >
                <option value="">All Assignments</option>
                <option value="mine">My Tickets</option>
                <option value="unassigned">Unassigned</option>
              </select>
            </div>
            
            <!-- Account Filter (if service provider) -->
            <div v-if="canViewAllAccounts">
              <label class="block text-xs font-medium text-gray-700 mb-1">Account</label>
              <select
                v-model="accountFilter"
                class="appearance-none bg-white border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 w-full"
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
          </div>
          
          <!-- Clear Filters Button -->
          <div v-if="hasActiveFilters" class="mt-4 flex justify-end">
            <button
              @click="clearFilters"
              class="text-sm text-red-600 hover:text-red-700 font-medium bg-red-50 hover:bg-red-100 px-3 py-2 rounded-lg transition-colors"
            >
              Reset to Defaults
            </button>
          </div>
        </div>
      </FilterSection>
    </template>

    <!-- Main Content -->
    <template #main-content>
      <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        <div class="p-4 border-b border-gray-200">
          <div class="flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gray-900">
              Tickets
              <span class="text-sm font-normal text-gray-500 ml-2">
                ({{ filteredTickets.length }} of {{ tickets?.length || 0 }})
              </span>
            </h3>
            
            <!-- Controls -->
            <div class="flex items-center space-x-4">
              <!-- Sort Dropdown -->
              <SortDropdown :table="table" />
              
              <!-- Column Visibility -->
              <ColumnVisibilitySelector
                :available-columns="availableColumns.filter(col => !col.required)"
                :is-column-visible="isColumnVisible"
                @toggle-column="toggleColumn"
                @reset-columns="resetVisibility"
              />
              
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
        </div>
        
        <!-- Ticket List -->
        <TicketList
          :tickets="filteredTickets"
          :table="table"
          :user="user"
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
          @open-manual-time-entry="openManualTimeEntry"
          @open-ticket-addon="openTicketAddon"
        />
      </div>
    </template>

    <!-- Sidebar -->
    <template #sidebar>
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
    </template>
  </StandardPageLayout>
    
    <!-- Create Ticket Modal -->
    <CreateTicketModalTabbed
      :show="showCreateModal"
      @close="showCreateModal = false"
      @ticket-created="onTicketCreated"
    />
    
    <!-- Time Entry Modal -->
    <UnifiedTimeEntryDialog
      :show="showTimeEntryModal"
      mode="create"
      :context-ticket="selectedTicketForTimeEntry"
      @close="showTimeEntryModal = false"
      @saved="handleTimeEntrySaved"
    />
    
    <!-- Addon Modal -->
    <AddAddonModal
      v-if="selectedTicketForAddon"
      :show="showAddonModal"
      :ticket="selectedTicketForAddon"
      @close="showAddonModal = false"
      @addon-created="handleAddonSaved"
    />
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import StandardPageLayout from '@/Layouts/StandardPageLayout.vue'
import FilterSection from '@/Components/Layout/FilterSection.vue'
import MultiSelect from '@/Components/UI/MultiSelect.vue'
import CreateTicketModalTabbed from '@/Components/Modals/CreateTicketModalTabbed.vue'
import TicketList from '@/Components/Tickets/TicketList.vue'
import UnifiedTimeEntryDialog from '@/Components/TimeEntries/UnifiedTimeEntryDialog.vue'
import AddAddonModal from '@/Components/Tickets/AddAddonModal.vue'
import ColumnVisibilitySelector from '@/Components/UI/ColumnVisibilitySelector.vue'
import SortDropdown from '@/Components/UI/SortDropdown.vue'
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
  },
  // Accept additional props passed by Inertia
  errors: {
    type: Object,
    default: () => ({})
  },
  auth: {
    type: Object,
    default: () => ({})
  },
  settings: {
    type: Object,
    default: () => ({})
  },
  csrf_token: {
    type: String,
    default: ''
  },
  stats: {
    type: Object,
    default: () => ({})
  }
})

// State
const activeTimers = ref(props.initialActiveTimers || [])
const timersByTicket = ref({}) // Store timers grouped by ticket_id
const showCreateModal = ref(false)
const showTimeEntryModal = ref(false)
const showAddonModal = ref(false)
const selectedTicketForTimeEntry = ref(null)
const selectedTicketForAddon = ref(null)
// Load table density preference from localStorage
const tableDensity = ref(localStorage.getItem('tickets-table-density') || 'compact')

// Watch for density changes and save to localStorage
watch(tableDensity, (newDensity) => {
  localStorage.setItem('tickets-table-density', newDensity)
}, { immediate: false })

// Ticket configuration data for dropdowns
const ticketStatuses = ref([])
const ticketPriorities = ref([])
const workflowTransitions = ref({})

// Initialize filters from localStorage with user-specific keys
const getStorageKey = (key) => `tickets-filters-${user.value?.id || 'guest'}-${key}`

// Helper to get default statuses (all except closed types)
const getDefaultStatuses = () => {
  return ticketStatuses.value
    .filter(status => !status.is_closed)
    .map(status => status.key)
}

// Helper to get default priorities (all)
const getDefaultPriorities = () => {
  return ticketPriorities.value.map(priority => priority.key)
}

// Load saved filters or use defaults
const loadSavedFilter = (key, defaultValue) => {
  try {
    const saved = localStorage.getItem(getStorageKey(key))
    return saved ? JSON.parse(saved) : defaultValue
  } catch {
    return defaultValue
  }
}

// Filters - will be initialized after ticket config loads
const searchQuery = ref('')
const statusFilter = ref([])
const priorityFilter = ref([])
const assignmentFilter = ref('')
const accountFilter = ref('')

// TanStack Query for tickets
const ticketFilters = computed(() => {
  const filters = {
    search: searchQuery.value,
    agent_id: assignmentFilter.value,
    account_id: accountFilter.value
  }
  
  // Only include status filter if there are statuses selected
  if (statusFilter.value.length > 0) {
    filters.status = statusFilter.value
  }
  
  // Only include priority filter if there are priorities selected
  if (priorityFilter.value.length > 0) {
    filters.priority = priorityFilter.value
  }
  
  return filters
})

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
  // Column visibility
  availableColumns,
  toggleColumn,
  resetVisibility,
  isColumnVisible
} = useTicketsTable(ticketsForTable, user, props.canViewAllAccounts)

// Computed
const pageTitle = computed(() => {
  return props.canViewAllAccounts ? 'Service Tickets' : 'My Tickets'
})

const hasActiveFilters = computed(() => {
  return !!(
    searchQuery.value || 
    (statusFilter.value.length > 0 && statusFilter.value.length < ticketStatuses.value.length) ||
    (priorityFilter.value.length > 0 && priorityFilter.value.length < ticketPriorities.value.length) ||
    assignmentFilter.value || 
    accountFilter.value
  )
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

// Watch for changes in tickets data and extract timers
watch(tickets, (newTickets) => {
  if (newTickets) {
    extractTimersFromTickets()
  }
}, { deep: true })

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

const extractTimersFromTickets = () => {
  const ticketsData = tickets.value?.data || tickets.value || []
  if (!ticketsData.length) return
  
  // Extract user-specific timers from ticket data
  const timersByTicketId = {}
  
  ticketsData.forEach(ticket => {
    if (ticket.timers && ticket.timers.length > 0) {
      // Only include user's own timers that are active or paused
      const userTimers = ticket.timers.filter(timer => 
        timer.user_id === user.value?.id && 
        ['running', 'paused'].includes(timer.status)
      )
      
      if (userTimers.length > 0) {
        timersByTicketId[ticket.id] = userTimers
      }
    }
  })
  
  timersByTicket.value = timersByTicketId
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
      
      // Initialize filters after config is loaded
      initializeFilters()
    }
  } catch (error) {
    if (import.meta.env.DEV) {
      console.error('Failed to load ticket configuration:', error)
    }
  }
}

// Save filters to localStorage
const saveFiltersToStorage = () => {
  if (!user.value?.id) return
  
  localStorage.setItem(getStorageKey('search'), searchQuery.value)
  localStorage.setItem(getStorageKey('status'), JSON.stringify(statusFilter.value))
  localStorage.setItem(getStorageKey('priority'), JSON.stringify(priorityFilter.value))
  localStorage.setItem(getStorageKey('assignment'), assignmentFilter.value)
  localStorage.setItem(getStorageKey('account'), accountFilter.value)
}

// Watch filters and save to localStorage
watch([searchQuery, statusFilter, priorityFilter, assignmentFilter, accountFilter], () => {
  saveFiltersToStorage()
}, { deep: true })

const clearFilters = () => {
  searchQuery.value = ''
  statusFilter.value = getDefaultStatuses()
  priorityFilter.value = getDefaultPriorities()
  assignmentFilter.value = ''
  accountFilter.value = ''
  clearAllFilters()
  saveFiltersToStorage()
}

// Initialize filters after config loads
const initializeFilters = () => {
  searchQuery.value = loadSavedFilter('search', '')
  statusFilter.value = loadSavedFilter('status', getDefaultStatuses())
  priorityFilter.value = loadSavedFilter('priority', getDefaultPriorities())
  assignmentFilter.value = loadSavedFilter('assignment', '')
  accountFilter.value = loadSavedFilter('account', '')
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
  await refreshActiveTimers()
  extractTimersFromTickets()
}

// Removed timer event handling for simplified table

const onTicketCreated = (newTicket) => {
  // TanStack Query mutation already handled the cache update
  // Just close the modal
  showCreateModal.value = false
}

// Removed timer and inline editing handlers for simplified table

const openManualTimeEntry = (ticket) => {
  selectedTicketForTimeEntry.value = ticket
  showTimeEntryModal.value = true
}

const openTicketAddon = (ticket) => {
  selectedTicketForAddon.value = ticket
  showAddonModal.value = true
}

const handleTimeEntrySaved = () => {
  showTimeEntryModal.value = false
  selectedTicketForTimeEntry.value = null
  // Refresh tickets to show updated time data
  refetchTickets()
}

const handleAddonSaved = () => {
  showAddonModal.value = false
  selectedTicketForAddon.value = null
  // Refresh tickets to show updated addon data
  refetchTickets()
}

// Note: Mobile state management now handled by StandardPageLayout

// Lifecycle
onMounted(() => {
  // Initial load - use unified function
  refreshTimerData()
  
  // Load ticket configuration for dropdowns
  loadTicketConfig()
  
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