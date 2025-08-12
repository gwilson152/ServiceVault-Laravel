<template>
  <div>
    <!-- Page Header -->
    <div class="bg-white shadow-sm border-b border-gray-200 mb-6">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
        <div class="flex items-center justify-between">
          <div class="flex items-center space-x-4">
          <!-- Back Button -->
          <Link 
            :href="route('tickets.index')" 
            class="text-gray-600 hover:text-gray-900"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
          </Link>
          
          <div class="flex-1">
            <!-- Editable Title -->
            <div v-if="editingTitle" class="flex items-center space-x-2">
              <input
                v-model="editedTitle"
                type="text"
                class="text-2xl font-bold text-gray-900 bg-white border border-gray-300 rounded px-2 py-1 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 flex-1"
                @keyup.enter="saveTitle"
                @keyup.escape="cancelEditingTitle"
              />
              <button
                @click="saveTitle"
                :disabled="savingTitle"
                class="bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400 text-white font-medium py-1 px-3 rounded text-sm transition-colors"
              >
                {{ savingTitle ? 'Saving...' : 'Save' }}
              </button>
              <button
                @click="cancelEditingTitle"
                class="bg-gray-300 hover:bg-gray-400 text-gray-700 font-medium py-1 px-3 rounded text-sm transition-colors"
              >
                Cancel
              </button>
            </div>
            
            <!-- Static Title with Edit Button -->
            <div v-else class="flex items-center space-x-2 group">
              <h1 class="text-2xl font-bold text-gray-900">
                {{ ticket?.title || 'Loading...' }}
              </h1>
              <button
                v-if="canEdit && ticket?.title"
                @click="startEditingTitle"
                class="text-blue-600 hover:text-blue-700 text-sm opacity-0 group-hover:opacity-100 transition-opacity"
              >
                Edit
              </button>
            </div>
            
            <div class="flex items-center space-x-2 mt-1">
              <span class="text-sm text-gray-500">{{ ticket?.ticket_number }}</span>
              <span v-if="ticket" :class="statusClasses" class="px-2 py-1 rounded-full text-xs font-medium">
                {{ formatStatus(ticket.status) }}
              </span>
              <span v-if="ticket" :class="priorityClasses" class="px-2 py-1 rounded-full text-xs font-medium">
                {{ formatPriority(ticket.priority) }}
              </span>
            </div>
          </div>
        </div>
        </div>
        
        <!-- Actions -->
        <div class="flex items-center space-x-3">
          <!-- Start Timer Button -->
          <button
            v-if="canStartTimer"
            @click="startTimer"
            :disabled="hasActiveTimer"
            :class="hasActiveTimer ? 'bg-gray-400 cursor-not-allowed' : 'bg-green-600 hover:bg-green-700'"
            class="text-white font-medium py-2 px-4 rounded-lg transition-colors flex items-center space-x-2"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h1m4 0h1m-6 4h1m4 0h1m-6-8h8a2 2 0 012 2v8a2 2 0 01-2 2H8a2 2 0 01-2-2V6a2 2 0 012-2z" />
            </svg>
            <span>{{ hasActiveTimer ? 'Timer Running' : 'Start Timer' }}</span>
          </button>
        </div>
      </div>
    </div>

    <div class="py-6">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div v-if="!ticket" class="text-center py-12">
          <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
          <p class="mt-2 text-gray-600">Loading ticket...</p>
        </div>
        
        <div v-else class="grid grid-cols-1 lg:grid-cols-3 gap-6">
          <!-- Main Content -->
          <div class="lg:col-span-2 space-y-6">
            <!-- Ticket Details Card -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
              <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                  <h2 class="text-lg font-semibold text-gray-900">Description</h2>
                  <button
                    v-if="canEdit && !editingDescription"
                    @click="startEditingDescription"
                    class="text-blue-600 hover:text-blue-700 text-sm"
                  >
                    Edit
                  </button>
                </div>
                
                <!-- Editable Description -->
                <div v-if="editingDescription">
                  <textarea
                    v-model="editedDescription"
                    rows="4"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                  ></textarea>
                  <div class="flex items-center space-x-2 mt-3">
                    <button
                      @click="saveDescription"
                      :disabled="savingDescription"
                      class="bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400 text-white font-medium py-1 px-3 rounded text-sm transition-colors"
                    >
                      {{ savingDescription ? 'Saving...' : 'Save' }}
                    </button>
                    <button
                      @click="cancelEditingDescription"
                      class="bg-gray-300 hover:bg-gray-400 text-gray-700 font-medium py-1 px-3 rounded text-sm transition-colors"
                    >
                      Cancel
                    </button>
                  </div>
                </div>
                
                <!-- Static Description -->
                <div v-else class="prose max-w-none text-gray-700">
                  {{ ticket.description || 'No description provided.' }}
                </div>
              </div>
            </div>
            
            <!-- Comments Thread -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
              <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">Comments & Updates</h2>
              </div>
              
              <!-- Comments List -->
              <div class="divide-y divide-gray-200">
                <div v-if="comments.length === 0" class="p-6 text-center text-gray-500">
                  No comments yet. Be the first to add an update!
                </div>
                
                <div 
                  v-for="comment in comments" 
                  :key="comment.id"
                  class="p-6"
                >
                  <div class="flex space-x-3">
                    <!-- Avatar -->
                    <div class="flex-shrink-0">
                      <div class="w-8 h-8 bg-gray-300 rounded-full flex items-center justify-center">
                        <span class="text-sm font-medium text-gray-700">
                          {{ comment.user?.name?.charAt(0)?.toUpperCase() || '?' }}
                        </span>
                      </div>
                    </div>
                    
                    <!-- Comment Content -->
                    <div class="flex-1 min-w-0">
                      <div class="flex items-center space-x-2">
                        <p class="text-sm font-medium text-gray-900">{{ comment.user?.name || 'Unknown User' }}</p>
                        <span v-if="comment.is_internal" class="px-2 py-1 bg-yellow-100 text-yellow-800 text-xs rounded-full">
                          Internal
                        </span>
                        <p class="text-sm text-gray-500">{{ formatDate(comment.created_at) }}</p>
                        <p v-if="comment.was_edited" class="text-xs text-gray-400">(edited)</p>
                      </div>
                      <div class="mt-2 text-sm text-gray-700">{{ comment.content }}</div>
                    </div>
                  </div>
                </div>
              </div>
              
              <!-- Add Comment Form -->
              <div v-if="ticket?.id" class="border-t border-gray-200 p-6">
                <form @submit.prevent="addComment">
                  <div class="mb-4">
                    <textarea
                      v-model="newComment"
                      placeholder="Add a comment or update..."
                      rows="3"
                      class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                      required
                    ></textarea>
                  </div>
                  <div class="flex items-center justify-between">
                    <label class="flex items-center">
                      <input 
                        v-model="commentIsInternal" 
                        type="checkbox" 
                        class="mr-2 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                      >
                      <span class="text-sm text-gray-600">Internal comment (not visible to customer)</span>
                    </label>
                    <button
                      type="submit"
                      :disabled="!newComment.trim() || isSubmitting || !ticket?.id"
                      class="bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400 text-white font-medium py-2 px-4 rounded-lg transition-colors"
                    >
                      {{ isSubmitting ? 'Adding...' : 'Add Comment' }}
                    </button>
                  </div>
                </form>
              </div>
              <div v-else class="border-t border-gray-200 p-6 text-center text-gray-500">
                Loading ticket data...
              </div>
            </div>
          </div>
          
          <!-- Sidebar -->
          <div class="space-y-6">
            <!-- Ticket Info Card -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
              <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Ticket Information</h3>
              </div>
              <div class="p-6 space-y-4">
                <div>
                  <label class="text-sm font-medium text-gray-600">Account</label>
                  <p class="text-sm text-gray-900">{{ ticket.account?.name || 'Unknown Account' }}</p>
                </div>
                <div>
                  <label class="text-sm font-medium text-gray-600">Assigned Agent</label>
                  <p class="text-sm text-gray-900">{{ ticket.agent?.name || 'Unassigned' }}</p>
                </div>
                <div>
                  <label class="text-sm font-medium text-gray-600">Customer</label>
                  <p class="text-sm text-gray-900">
                    {{ ticket.customer?.name || ticket.customer_name || 'No customer assigned' }}
                  </p>
                  <p v-if="ticket.customer_email" class="text-xs text-gray-500">{{ ticket.customer_email }}</p>
                </div>
                <div>
                  <label class="text-sm font-medium text-gray-600">Created</label>
                  <p class="text-sm text-gray-900">{{ formatDate(ticket.created_at) }}</p>
                </div>
                <div v-if="ticket.due_date">
                  <label class="text-sm font-medium text-gray-600">Due Date</label>
                  <p class="text-sm text-gray-900">{{ formatDate(ticket.due_date) }}</p>
                </div>
                <div v-if="ticket.estimated_hours">
                  <label class="text-sm font-medium text-gray-600">Estimated Hours</label>
                  <p class="text-sm text-gray-900">{{ ticket.estimated_hours }}h</p>
                </div>
              </div>
            </div>
            
            <!-- Time Tracking Card -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
              <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Time Tracking</h3>
              </div>
              <div class="p-6 space-y-4">
                <div>
                  <label class="text-sm font-medium text-gray-600">Total Time Logged</label>
                  <p class="text-lg font-semibold text-gray-900">{{ formatDuration(totalTimeLogged) }}</p>
                </div>
                <div v-if="activeTimers.length > 0">
                  <label class="text-sm font-medium text-gray-600">Active Timers</label>
                  <div class="mt-2 space-y-2">
                    <div 
                      v-for="timer in activeTimers" 
                      :key="timer.id"
                      class="flex items-center justify-between p-2 bg-green-50 border border-green-200 rounded-lg"
                    >
                      <div class="flex items-center space-x-2">
                        <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                        <span class="text-sm text-gray-700">{{ timer.user?.name || 'Unknown' }}</span>
                      </div>
                      <span class="text-sm font-medium text-green-700">{{ formatDuration(timer.duration) }}</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import axios from 'axios'

// Define persistent layout
defineOptions({
  layout: AppLayout
})

// Props
const props = defineProps({
  ticket: {
    type: Object,
    default: null
  }
})

// Reactive data
const ticket = ref(props.ticket)
const comments = ref([])
const newComment = ref('')
const commentIsInternal = ref(false)
const isSubmitting = ref(false)
const activeTimers = ref([])
const totalTimeLogged = ref(0)

// Editing states
const editingDescription = ref(false)
const editedDescription = ref('')
const savingDescription = ref(false)
const editingTitle = ref(false)
const editedTitle = ref('')
const savingTitle = ref(false)

// Computed properties
const statusClasses = computed(() => {
  if (!ticket.value) return ''
  
  const statusMap = {
    'open': 'bg-blue-100 text-blue-800',
    'in_progress': 'bg-yellow-100 text-yellow-800',
    'waiting_customer': 'bg-purple-100 text-purple-800',
    'resolved': 'bg-green-100 text-green-800',
    'closed': 'bg-gray-100 text-gray-800',
    'cancelled': 'bg-red-100 text-red-800'
  }
  
  return statusMap[ticket.value.status] || 'bg-gray-100 text-gray-800'
})

const priorityClasses = computed(() => {
  if (!ticket.value) return ''
  
  const priorityMap = {
    'low': 'bg-gray-100 text-gray-800',
    'normal': 'bg-blue-100 text-blue-800', 
    'high': 'bg-orange-100 text-orange-800',
    'urgent': 'bg-red-100 text-red-800'
  }
  
  return priorityMap[ticket.value.priority] || 'bg-gray-100 text-gray-800'
})


const canEdit = computed(() => {
  // TODO: Implement proper permission checking based on user role and ticket ownership
  return true
})

const canStartTimer = computed(() => {
  // TODO: Implement proper permission checking
  return true
})

const hasActiveTimer = computed(() => {
  // Check if current user has an active timer for this ticket
  return activeTimers.value.some(timer => timer.user_id === window.auth?.user?.id)
})

// Methods
const formatStatus = (status) => {
  return status?.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase()) || 'Unknown'
}

const formatPriority = (priority) => {
  return priority?.charAt(0).toUpperCase() + priority?.slice(1) || 'Unknown'
}

const formatDate = (date) => {
  if (!date) return 'N/A'
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short', 
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const formatDuration = (seconds) => {
  if (!seconds) return '0m'
  
  const hours = Math.floor(seconds / 3600)
  const minutes = Math.floor((seconds % 3600) / 60)
  
  if (hours > 0) {
    return `${hours}h ${minutes}m`
  } else {
    return `${minutes}m`
  }
}

const loadTicketDetails = async () => {
  if (!ticket.value?.id) return
  
  try {
    const response = await axios.get(`/api/tickets/${ticket.value.id}`)
    ticket.value = response.data.data
    
    // Load comments
    await loadComments()
    
    // Load time tracking data
    await loadTimeTrackingData()
    
  } catch (error) {
    console.error('Failed to load ticket details:', error)
  }
}

const loadComments = async () => {
  if (!ticket.value?.id) return
  
  try {
    const response = await axios.get(`/api/tickets/${ticket.value.id}/comments`)
    comments.value = response.data.data || []
  } catch (error) {
    console.error('Failed to load comments:', error)
    comments.value = []
  }
}

const loadTimeTrackingData = async () => {
  if (!ticket.value?.id) return
  
  try {
    // Load active timers
    const timersResponse = await axios.get(`/api/tickets/${ticket.value.id}/timers`)
    activeTimers.value = timersResponse.data.data || []
    
    // Load time entries for total calculation
    const timeEntriesResponse = await axios.get(`/api/tickets/${ticket.value.id}/time-entries`)
    const timeEntries = timeEntriesResponse.data.data || []
    totalTimeLogged.value = timeEntries.reduce((total, entry) => total + (entry.duration || 0), 0)
    
  } catch (error) {
    console.error('Failed to load time tracking data:', error)
    activeTimers.value = []
    totalTimeLogged.value = 0
  }
}

const addComment = async () => {
  if (!newComment.value.trim() || isSubmitting.value) return
  
  // Ensure we have a valid ticket ID
  if (!ticket.value?.id) {
    console.error('No ticket ID available for comment submission')
    return
  }
  
  isSubmitting.value = true
  
  try {
    const response = await axios.post(`/api/tickets/${ticket.value.id}/comments`, {
      content: newComment.value.trim(),
      is_internal: commentIsInternal.value
    })
    
    // Add new comment to the beginning of the list
    comments.value.unshift(response.data.data)
    
    // Reset form
    newComment.value = ''
    commentIsInternal.value = false
    
  } catch (error) {
    console.error('Failed to add comment:', error)
    // TODO: Show error notification
  } finally {
    isSubmitting.value = false
  }
}

const startTimer = async () => {
  if (!ticket.value?.id || hasActiveTimer.value) return
  
  try {
    const response = await axios.post('/api/timers', {
      ticket_id: ticket.value.id,
      description: `Working on ${ticket.value.title || 'ticket'}`
    })
    
    // Refresh timer data
    await loadTimeTrackingData()
    
    // TODO: Show success notification
    
  } catch (error) {
    console.error('Failed to start timer:', error)
    // TODO: Show error notification
  }
}

// Inline editing methods
const startEditingDescription = () => {
  editingDescription.value = true
  editedDescription.value = ticket.value.description || ''
}

const cancelEditingDescription = () => {
  editingDescription.value = false
  editedDescription.value = ''
}

const saveDescription = async () => {
  if (!ticket.value?.id) return
  
  savingDescription.value = true
  
  try {
    const response = await axios.put(`/api/tickets/${ticket.value.id}`, {
      description: editedDescription.value.trim()
    })
    
    // Update local ticket data
    ticket.value.description = editedDescription.value.trim()
    
    // Exit editing mode
    editingDescription.value = false
    editedDescription.value = ''
    
  } catch (error) {
    console.error('Failed to save description:', error)
    // TODO: Show error notification
  } finally {
    savingDescription.value = false
  }
}

// Title editing methods
const startEditingTitle = () => {
  editingTitle.value = true
  editedTitle.value = ticket.value.title || ''
}

const cancelEditingTitle = () => {
  editingTitle.value = false
  editedTitle.value = ''
}

const saveTitle = async () => {
  if (!ticket.value?.id || !editedTitle.value.trim()) return
  
  savingTitle.value = true
  
  try {
    const response = await axios.put(`/api/tickets/${ticket.value.id}`, {
      title: editedTitle.value.trim()
    })
    
    // Update local ticket data
    ticket.value.title = editedTitle.value.trim()
    
    // Exit editing mode
    editingTitle.value = false
    editedTitle.value = ''
    
  } catch (error) {
    console.error('Failed to save title:', error)
    // TODO: Show error notification
  } finally {
    savingTitle.value = false
  }
}

// Lifecycle
onMounted(() => {
  loadTicketDetails()
})
</script>