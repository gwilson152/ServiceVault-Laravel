<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <div class="bg-white shadow-sm border-b border-gray-200">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between py-6">
          <div>
            <h1 class="text-2xl font-bold text-gray-900">Customer Portal</h1>
            <p class="text-sm text-gray-600 mt-1">Welcome back, {{ user?.name || 'Customer' }}</p>
          </div>
          
          <div class="flex items-center space-x-4">
            <div class="relative" ref="profileDropdown">
              <button
                @click="showProfileMenu = !showProfileMenu"
                class="flex items-center space-x-3 text-gray-700 hover:text-gray-900"
              >
                <div class="w-8 h-8 bg-gray-300 rounded-full flex items-center justify-center">
                  <span class="text-sm font-medium">{{ user?.name?.charAt(0)?.toUpperCase() || 'C' }}</span>
                </div>
                <span class="text-sm font-medium">{{ user?.name || 'Customer' }}</span>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
              </button>
              
              <div v-if="showProfileMenu" class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg border border-gray-200 z-10">
                <div class="py-1">
                  <Link :href="route('portal.settings')" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                    Account Settings
                  </Link>
                  <form method="POST" action="/logout">
                    <input type="hidden" name="_token" :value="$page.props.csrf_token">
                    <button type="submit" class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                      Sign Out
                    </button>
                  </form>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Loading State -->
      <div v-if="loading" class="text-center py-12">
        <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
        <p class="mt-2 text-gray-600">Loading dashboard...</p>
      </div>
      
      <!-- Dashboard Content -->
      <div v-else>
        <!-- Quick Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
          <!-- Closed Tickets -->
          <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center">
                  <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                </div>
              </div>
              <div class="ml-5 w-0 flex-1">
                <dl>
                  <dt class="text-sm font-medium text-gray-500 truncate">Closed Tickets</dt>
                  <dd class="text-lg font-medium text-gray-900">{{ stats.closed_tickets || 0 }}</dd>
                </dl>
              </div>
            </div>
          </div>

          <!-- Open Tickets -->
          <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <div class="w-8 h-8 bg-yellow-100 rounded-lg flex items-center justify-center">
                  <svg class="w-4 h-4 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
                  </svg>
                </div>
              </div>
              <div class="ml-5 w-0 flex-1">
                <dl>
                  <dt class="text-sm font-medium text-gray-500 truncate">Open Tickets</dt>
                  <dd class="text-lg font-medium text-gray-900">{{ stats.open_tickets || 0 }}</dd>
                </dl>
              </div>
            </div>
          </div>

          <!-- Hours This Month -->
          <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center">
                  <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                </div>
              </div>
              <div class="ml-5 w-0 flex-1">
                <dl>
                  <dt class="text-sm font-medium text-gray-500 truncate">Hours This Month</dt>
                  <dd class="text-lg font-medium text-gray-900">{{ formatHours(stats.hours_this_month) }}</dd>
                </dl>
              </div>
            </div>
          </div>

          <!-- Total Spent -->
          <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <div class="w-8 h-8 bg-purple-100 rounded-lg flex items-center justify-center">
                  <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
                  </svg>
                </div>
              </div>
              <div class="ml-5 w-0 flex-1">
                <dl>
                  <dt class="text-sm font-medium text-gray-500 truncate">Total Spent</dt>
                  <dd class="text-lg font-medium text-gray-900">${{ formatCurrency(stats.total_spent) }}</dd>
                </dl>
              </div>
            </div>
          </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <!-- Recent Tickets -->
          <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
              <h3 class="text-lg font-semibold text-gray-900">Recent Support Tickets</h3>
              <Link 
                :href="route('tickets.index')" 
                class="text-sm text-blue-600 hover:text-blue-700 font-medium"
              >
                View All →
              </Link>
            </div>
            <div class="p-6">
              <div v-if="recentTickets.length === 0" class="text-center py-8 text-gray-500">
                <svg class="w-12 h-12 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <p>No support tickets yet</p>
                <button 
                  @click="showCreateTicketModal = true"
                  class="mt-2 text-blue-600 hover:text-blue-700 font-medium"
                >
                  Create your first ticket
                </button>
              </div>
              
              <div v-else class="space-y-4">
                <div 
                  v-for="ticket in recentTickets" 
                  :key="ticket.id"
                  class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50 transition-colors"
                >
                  <div class="flex items-center justify-between mb-2">
                    <div class="flex items-center space-x-3">
                      <span :class="getStatusClasses(ticket.status)" class="px-2 py-1 rounded-full text-xs font-medium">
                        {{ formatStatus(ticket.status) }}
                      </span>
                      <span class="text-sm font-medium text-gray-500">{{ ticket.ticket_number }}</span>
                    </div>
                    <span class="text-xs text-gray-500">{{ formatDate(ticket.created_at) }}</span>
                  </div>
                  <h4 class="text-sm font-medium text-gray-900 mb-1">{{ ticket.title }}</h4>
                  <p class="text-sm text-gray-600 line-clamp-2">{{ ticket.description || 'No description provided' }}</p>
                </div>
              </div>
            </div>
          </div>

          <!-- Recent Activity -->
          <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
              <h3 class="text-lg font-semibold text-gray-900">Recent Activity</h3>
              <Link 
                v-if="permissions.canViewTimeTracking"
                :href="route('portal.time-tracking')" 
                class="text-sm text-blue-600 hover:text-blue-700 font-medium"
              >
                View Time Reports →
              </Link>
            </div>
            <div class="p-6">
              <div v-if="recentActivity.length === 0" class="text-center py-8 text-gray-500">
                <svg class="w-12 h-12 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
                <p>No recent activity</p>
              </div>
              
              <div v-else class="space-y-4">
                <div 
                  v-for="activity in recentActivity" 
                  :key="activity.id"
                  class="flex items-start space-x-3"
                >
                  <div class="flex-shrink-0">
                    <div class="w-2 h-2 bg-blue-500 rounded-full mt-2"></div>
                  </div>
                  <div class="flex-1 min-w-0">
                    <p class="text-sm text-gray-900">{{ activity.description }}</p>
                    <p class="text-xs text-gray-500 mt-1">{{ formatDateTime(activity.created_at) }}</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Quick Actions -->
        <div class="mt-8 bg-white rounded-lg shadow-sm border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h3>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <button
              @click="showCreateTicketModal = true"
              class="flex items-center space-x-3 p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors text-left"
            >
              <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                </svg>
              </div>
              <div>
                <p class="text-sm font-medium text-gray-900">Create Support Ticket</p>
                <p class="text-xs text-gray-500">Get help from our team</p>
              </div>
            </button>
            
            <Link
              v-if="permissions.canViewBilling"
              :href="route('portal.billing')"
              class="flex items-center space-x-3 p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors"
            >
              <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                </svg>
              </div>
              <div>
                <p class="text-sm font-medium text-gray-900">View Invoices</p>
                <p class="text-xs text-gray-500">Check billing and invoices</p>
              </div>
            </Link>
            
            <Link
              v-if="permissions.canViewTimeTracking"
              :href="route('portal.time-tracking')"
              class="flex items-center space-x-3 p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors"
            >
              <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
              </div>
              <div>
                <p class="text-sm font-medium text-gray-900">View Time Entries</p>
                <p class="text-xs text-gray-500">Track time and activity</p>
              </div>
            </Link>
          </div>
        </div>
      </div>
    </div>

    <!-- Create Ticket Modal -->
    <CreateTicketModalTabbed 
      :show="showCreateTicketModal"
      @ticket-created="handleTicketCreated"
      @close="showCreateTicketModal = false"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import CreateTicketModalTabbed from '@/Components/Modals/CreateTicketModalTabbed.vue'
import axios from 'axios'

// Define props
const props = defineProps({
  permissions: {
    type: Object,
    default: () => ({
      canViewTimeTracking: false,
      canViewBilling: false,
    })
  }
})

// Define persistent layout
defineOptions({
  layout: AppLayout
})

// Access user data from Inertia page props
const page = usePage()
const user = computed(() => page.props.auth?.user)

// State
const loading = ref(true)
const stats = ref({})
const recentTickets = ref([])
const recentActivity = ref([])
const showProfileMenu = ref(false)
const showCreateTicketModal = ref(false)

// Methods
const loadDashboardData = async () => {
  loading.value = true
  
  try {
    console.log('Loading portal dashboard data...')
    
    // Load dashboard statistics - test each individually first
    console.log('Loading stats...')
    const statsResponse = await axios.get('/api/portal/stats')
    console.log('Stats response:', statsResponse)
    
    console.log('Loading recent tickets...')
    const ticketsResponse = await axios.get('/api/portal/recent-tickets')
    console.log('Tickets response:', ticketsResponse)
    
    console.log('Loading recent activity...')
    const activityResponse = await axios.get('/api/portal/recent-activity')
    console.log('Activity response:', activityResponse)
    
    stats.value = statsResponse.data.data || {
      open_tickets: 0,
      closed_tickets: 0, 
      hours_this_month: 0,
      total_spent: 0
    }
    recentTickets.value = ticketsResponse.data.data || []
    recentActivity.value = activityResponse.data.data || []
    
  } catch (error) {
    console.error('Failed to load dashboard data:', error)
    // Set safe defaults on error
    stats.value = {
      open_tickets: 0,
      closed_tickets: 0,
      hours_this_month: 0,
      total_spent: 0
    }
    recentTickets.value = []
    recentActivity.value = []
  } finally {
    loading.value = false
  }
}

const formatHours = (hours) => {
  if (!hours) return '0h'
  return `${Math.round(hours)}h`
}

const formatCurrency = (amount) => {
  if (!amount) return '0.00'
  return new Intl.NumberFormat('en-US', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  }).format(amount)
}

const formatDate = (dateString) => {
  if (!dateString) return 'N/A'
  return new Date(dateString).toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric'
  })
}

const formatDateTime = (dateString) => {
  if (!dateString) return 'N/A'
  return new Date(dateString).toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const formatStatus = (status) => {
  return status?.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase()) || 'Unknown'
}

const getStatusClasses = (status) => {
  const statusMap = {
    'open': 'bg-blue-100 text-blue-800',
    'in_progress': 'bg-yellow-100 text-yellow-800',
    'waiting_customer': 'bg-purple-100 text-purple-800',
    'on_hold': 'bg-gray-100 text-gray-800',
    'resolved': 'bg-green-100 text-green-800',
    'closed': 'bg-gray-100 text-gray-800',
    'cancelled': 'bg-red-100 text-red-800'
  }
  
  return statusMap[status] || 'bg-gray-100 text-gray-800'
}

const handleTicketCreated = () => {
  showCreateTicketModal.value = false
  // Reload recent tickets
  loadDashboardData()
}

// Lifecycle
onMounted(() => {
  loadDashboardData()
})
</script>