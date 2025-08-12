<template>
  <div class="ticket-overview-widget">
    <!-- Stats Row -->
    <div class="stats-row grid grid-cols-2 lg:grid-cols-4 gap-2 mb-4">
      <div class="stat-item text-center p-2 bg-blue-50 rounded-lg">
        <p class="text-lg font-bold text-blue-900">{{ ticketStats.total || 0 }}</p>
        <p class="text-xs text-blue-600">Total</p>
      </div>
      <div class="stat-item text-center p-2 bg-yellow-50 rounded-lg">
        <p class="text-lg font-bold text-yellow-900">{{ ticketStats.open || 0 }}</p>
        <p class="text-xs text-yellow-600">Open</p>
      </div>
      <div class="stat-item text-center p-2 bg-indigo-50 rounded-lg">
        <p class="text-lg font-bold text-indigo-900">{{ ticketStats.in_progress || 0 }}</p>
        <p class="text-xs text-indigo-600">In Progress</p>
      </div>
      <div class="stat-item text-center p-2 bg-green-50 rounded-lg">
        <p class="text-lg font-bold text-green-900">{{ ticketStats.resolved || 0 }}</p>
        <p class="text-xs text-green-600">Resolved</p>
      </div>
    </div>

    <!-- Recent Tickets -->
    <div class="recent-tickets">
      <div v-if="recentTickets.length > 0" class="space-y-2">
        <div
          v-for="ticket in recentTickets"
          :key="ticket.id"
          class="ticket-item p-2 bg-gray-50 rounded-lg hover:bg-gray-100 cursor-pointer transition-colors"
          @click="viewTicket(ticket.id)"
        >
          <div class="flex items-center justify-between mb-1">
            <div class="flex items-center space-x-2">
              <span :class="getStatusColor(ticket.status)" class="w-2 h-2 rounded-full"></span>
              <span class="font-medium text-gray-900 text-sm">{{ ticket.title || `Ticket #${ticket.id}` }}</span>
            </div>
            <span class="text-xs text-gray-500">{{ formatDate(ticket.created_at) }}</span>
          </div>
          
          <div class="flex items-center justify-between text-xs text-gray-600">
            <span>{{ ticket.account?.name || 'Unknown Account' }}</span>
            <div class="flex items-center space-x-1">
              <span v-if="ticket.assigned_user" class="text-indigo-600">
                {{ ticket.assigned_user.name }}
              </span>
              <span :class="getStatusBadgeClass(ticket.status)" class="px-2 py-0.5 rounded-full text-xs">
                {{ formatStatus(ticket.status) }}
              </span>
            </div>
          </div>
        </div>
      </div>
      
      <div v-else class="empty-state text-center py-4">
        <div class="mx-auto flex items-center justify-center h-10 w-10 rounded-full bg-gray-100 mb-2">
          <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
          </svg>
        </div>
        <p class="text-sm text-gray-500">No recent tickets</p>
      </div>
    </div>

    <!-- Quick Actions -->
    <div class="quick-actions mt-4 pt-3 border-t border-gray-100">
      <div class="flex justify-between text-xs">
        <button 
          @click="showCreateModal = true"
          class="text-indigo-600 hover:text-indigo-800"
        >
          Create Ticket
        </button>
        <div class="flex space-x-2">
          <button 
            @click="viewAllTickets"
            class="text-gray-500 hover:text-gray-700"
          >
            View All
          </button>
          <button 
            @click="refreshTickets"
            :disabled="isRefreshing"
            class="text-gray-500 hover:text-gray-700 disabled:opacity-50"
          >
            {{ isRefreshing ? 'Refreshing...' : 'Refresh' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Create Ticket Modal -->
    <CreateTicketModal
      :show="showCreateModal"
      :available-accounts="availableAccounts"
      :can-assign-tickets="canAssignTickets"
      @close="showCreateModal = false"
      @created="onTicketCreated"
    />
  </div>
</template>


<script setup>
import { computed, ref } from 'vue'
import { router } from '@inertiajs/vue3'
import CreateTicketModal from '@/Components/Modals/CreateTicketModal.vue'

// Props
const props = defineProps({
  widgetData: {
    type: Object,
    default: () => ({})
  },
  widgetConfig: {
    type: Object,
    default: () => ({})
  },
  accountContext: {
    type: Object,
    default: () => ({})
  }
})

// Emits
const emit = defineEmits(['refresh', 'configure'])

// State
const isRefreshing = ref(false)
const showCreateModal = ref(false)

// Computed properties for modal
const availableAccounts = computed(() => {
  // For now, return empty array - the modal will handle account selection based on user permissions
  return []
})

const canAssignTickets = computed(() => {
  // This should be determined from user permissions in a real implementation
  return props.accountContext?.canAssignTickets || false
})

// Computed
const recentTickets = computed(() => {
  return props.widgetData?.recent_tickets || []
})

const ticketStats = computed(() => {
  return props.widgetData?.stats || {
    total: 0,
    open: 0,
    in_progress: 0,
    resolved: 0
  }
})

// Methods
const getStatusColor = (status) => {
  const colors = {
    'open': 'bg-red-500',
    'in_progress': 'bg-yellow-500',
    'resolved': 'bg-green-500',
    'closed': 'bg-gray-500'
  }
  return colors[status] || 'bg-gray-300'
}

const getStatusBadgeClass = (status) => {
  const classes = {
    'open': 'bg-red-100 text-red-700',
    'in_progress': 'bg-yellow-100 text-yellow-700',
    'resolved': 'bg-green-100 text-green-700',
    'closed': 'bg-gray-100 text-gray-700'
  }
  return classes[status] || 'bg-gray-100 text-gray-700'
}

const formatStatus = (status) => {
  return status.split('_').map(word => 
    word.charAt(0).toUpperCase() + word.slice(1)
  ).join(' ')
}

const formatDate = (dateString) => {
  if (!dateString) return ''
  
  const date = new Date(dateString)
  const now = new Date()
  const diffInHours = (now - date) / (1000 * 60 * 60)
  
  if (diffInHours < 24) {
    return `${Math.floor(diffInHours)}h ago`
  } else if (diffInHours < 24 * 7) {
    return `${Math.floor(diffInHours / 24)}d ago`
  } else {
    return date.toLocaleDateString()
  }
}

const viewTicket = (ticketId) => {
  // Navigate to ticket detail page using Inertia router
  router.visit(`/tickets/${ticketId}`)
}

const onTicketCreated = (newTicket) => {
  // Handle successful ticket creation
  showCreateModal.value = false
  
  // Refresh the widget data to show the new ticket
  emit('refresh')
  
  // Optionally navigate to the new ticket
  if (newTicket?.id) {
    router.visit(`/tickets/${newTicket.id}`)
  }
}

const viewAllTickets = () => {
  // Navigate to tickets index page
  router.visit('/tickets')
}

const refreshTickets = () => {
  isRefreshing.value = true
  emit('refresh')
  setTimeout(() => {
    isRefreshing.value = false
  }, 1000)
}
</script>

<style scoped>
.stat-item {
  transition: all 0.2s ease-in-out;
}

.stat-item:hover {
  transform: scale(1.05);
}

.ticket-item {
  transition: all 0.2s ease-in-out;
}

.ticket-item:hover {
  transform: translateX(2px);
}
</style>