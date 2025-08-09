
<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { 
  TicketIcon,
  ClockIcon,
  UserIcon,
  ChevronRightIcon,
  ExclamationTriangleIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
  widgetId: {
    type: String,
    required: true
  },
  accountContext: {
    type: Object,
    default: () => ({})
  }
})

// State
const tickets = ref([])
const loading = ref(true)
const error = ref(null)
const stats = ref({
  total: 0,
  in_progress: 0,
  pending: 0,
  overdue: 0
})

let refreshInterval = null

// Computed
const formattedTickets = computed(() => {
  return tickets.value.map(ticket => ({
    ...ticket,
    status_color: getStatusColor(ticket.status),
    priority_color: getPriorityColor(ticket.priority),
    is_overdue: ticket.due_date && new Date(ticket.due_date) < new Date(),
    days_since_updated: Math.floor((new Date() - new Date(ticket.updated_at)) / (1000 * 60 * 60 * 24))
  }))
})

const recentTickets = computed(() => {
  return formattedTickets.value.slice(0, 5)
})

// Methods
const getStatusColor = (status) => {
  const colors = {
    'new': 'bg-blue-100 text-blue-800',
    'assigned': 'bg-yellow-100 text-yellow-800',
    'in_progress': 'bg-indigo-100 text-indigo-800',
    'pending': 'bg-orange-100 text-orange-800',
    'testing': 'bg-purple-100 text-purple-800',
    'completed': 'bg-green-100 text-green-800',
    'closed': 'bg-gray-100 text-gray-800'
  }
  return colors[status] || 'bg-gray-100 text-gray-800'
}

const getPriorityColor = (priority) => {
  const colors = {
    'low': 'text-gray-500',
    'normal': 'text-blue-500',
    'high': 'text-orange-500',
    'urgent': 'text-red-500'
  }
  return colors[priority] || 'text-gray-500'
}

const fetchMyTickets = async () => {
  try {
    loading.value = true
    error.value = null
    
    const response = await fetch('/api/service-tickets/my/assigned', {
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.head.querySelector('meta[name="csrf-token"]').content
      }
    })

    if (!response.ok) {
      throw new Error(`HTTP ${response.status}: ${response.statusText}`)
    }

    const data = await response.json()
    tickets.value = data.data || []
    stats.value = data.stats || {
      total: tickets.value.length,
      in_progress: tickets.value.filter(t => t.status === 'in_progress').length,
      pending: tickets.value.filter(t => t.status === 'pending').length,
      overdue: tickets.value.filter(t => t.due_date && new Date(t.due_date) < new Date()).length
    }
    
  } catch (err) {
    console.error('Failed to fetch my tickets:', err)
    error.value = err.message
    tickets.value = []
  } finally {
    loading.value = false
  }
}

const navigateToTicket = (ticketId) => {
  window.location.href = `/tickets/${ticketId}`
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

// Lifecycle
onMounted(async () => {
  await fetchMyTickets()
  
  // Set up auto-refresh (default 30 seconds)
  const refreshIntervalMs = (props.widgetConfig?.refreshInterval || 30) * 1000
  refreshInterval = setInterval(fetchMyTickets, refreshIntervalMs)
})

onUnmounted(() => {
  if (refreshInterval) {
    clearInterval(refreshInterval)
  }
})
</script>

<template>
  <div class="h-full flex flex-col">
    <!-- Widget Header -->
    <div class="flex items-center justify-between mb-4">
      <div class="flex items-center space-x-2">
        <TicketIcon class="w-5 h-5 text-indigo-600" />
        <h3 class="text-lg font-semibold text-gray-900">My Tickets</h3>
      </div>
      
      <!-- Stats Summary -->
      <div class="flex space-x-4 text-sm text-gray-600">
        <div class="text-center">
          <div class="font-semibold text-indigo-600">{{ stats.in_progress }}</div>
          <div class="text-xs">In Progress</div>
        </div>
        <div class="text-center">
          <div class="font-semibold text-orange-600">{{ stats.pending }}</div>
          <div class="text-xs">Pending</div>
        </div>
        <div v-if="stats.overdue > 0" class="text-center">
          <div class="font-semibold text-red-600">{{ stats.overdue }}</div>
          <div class="text-xs">Overdue</div>
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex-1 flex items-center justify-center">
      <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="flex-1 flex items-center justify-center text-center">
      <div>
        <div class="text-red-600 mb-2">⚠️ Failed to load tickets</div>
        <div class="text-sm text-gray-500">{{ error }}</div>
        <button 
          @click="fetchMyTickets"
          class="mt-2 px-3 py-1 bg-indigo-600 text-white rounded text-sm hover:bg-indigo-700"
        >
          Retry
        </button>
      </div>
    </div>

    <!-- No Tickets State -->
    <div v-else-if="recentTickets.length === 0" class="flex-1 flex items-center justify-center text-center text-gray-500">
      <div>
        <TicketIcon class="w-12 h-12 mx-auto mb-3 text-gray-400" />
        <div class="text-lg font-medium">No Assigned Tickets</div>
        <div class="text-sm">You have no tickets currently assigned to you</div>
      </div>
    </div>

    <!-- Tickets List -->
    <div v-else class="flex-1 overflow-hidden">
      <div class="space-y-3 overflow-y-auto max-h-80">
        <div 
          v-for="ticket in recentTickets" 
          :key="ticket.id"
          @click="navigateToTicket(ticket.id)"
          class="bg-white border border-gray-200 rounded-lg p-3 hover:shadow-md transition-shadow cursor-pointer"
        >
          <!-- Ticket Header -->
          <div class="flex items-start justify-between mb-2">
            <div class="flex-1">
              <div class="flex items-center space-x-2 mb-1">
                <span class="font-medium text-sm text-gray-900">
                  #{{ ticket.ticket_number }}
                </span>
                
                <!-- Status Badge -->
                <span :class="`px-2 py-1 rounded-full text-xs font-medium ${ticket.status_color}`">
                  {{ ticket.status.replace('_', ' ').toUpperCase() }}
                </span>
                
                <!-- Priority Indicator -->
                <span v-if="ticket.priority !== 'normal'" :class="`text-xs font-medium ${ticket.priority_color}`">
                  {{ ticket.priority.toUpperCase() }}
                </span>
                
                <!-- Overdue Warning -->
                <ExclamationTriangleIcon 
                  v-if="ticket.is_overdue" 
                  class="w-4 h-4 text-red-500" 
                  title="Overdue"
                />
              </div>
              
              <h4 class="text-sm font-medium text-gray-900 line-clamp-2">
                {{ ticket.title }}
              </h4>
            </div>
            
            <ChevronRightIcon class="w-4 h-4 text-gray-400 flex-shrink-0 ml-2" />
          </div>

          <!-- Ticket Meta -->
          <div class="flex items-center justify-between text-xs text-gray-500">
            <div class="flex items-center space-x-3">
              <div class="flex items-center space-x-1">
                <UserIcon class="w-3 h-3" />
                <span>{{ ticket.account?.name || 'Unknown Account' }}</span>
              </div>
              
              <div class="flex items-center space-x-1">
                <ClockIcon class="w-3 h-3" />
                <span>Updated {{ formatDate(ticket.updated_at) }}</span>
              </div>
            </div>
            
            <div v-if="ticket.due_date" class="text-xs">
              <span :class="ticket.is_overdue ? 'text-red-600 font-medium' : 'text-gray-500'">
                Due {{ formatDate(ticket.due_date) }}
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- View All Link -->
      <div v-if="tickets.length > recentTickets.length" class="mt-3 text-center">
        <a 
          href="/tickets?filter=assigned_to_me" 
          class="text-sm text-indigo-600 hover:text-indigo-700 font-medium"
        >
          View all {{ tickets.length }} assigned tickets →
        </a>
      </div>
    </div>
  </div>
</template>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>