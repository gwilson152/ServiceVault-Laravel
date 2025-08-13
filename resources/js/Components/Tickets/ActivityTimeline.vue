<template>
  <div class="space-y-6">
    <!-- Filter Controls -->
    <div class="flex items-center justify-between">
      <h3 class="text-lg font-semibold text-gray-900">Activity Timeline</h3>
      <div class="flex items-center space-x-3">
        <select 
          v-model="filters.type" 
          @change="loadActivity"
          class="border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
        >
          <option value="">All Activities</option>
          <option value="comment">Comments</option>
          <option value="status_change">Status Changes</option>
          <option value="assignment">Assignments</option>
          <option value="time_entry">Time Entries</option>
          <option value="addon">Add-ons</option>
          <option value="attachment">Attachments</option>
        </select>
        <select 
          v-model="filters.user_id" 
          @change="loadActivity"
          class="border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
        >
          <option value="">All Users</option>
          <option v-for="user in availableUsers" :key="user.id" :value="user.id">
            {{ user.name }}
          </option>
        </select>
      </div>
    </div>

    <!-- Timeline -->
    <div v-if="loading" class="text-center py-8">
      <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
      <p class="mt-2 text-gray-600">Loading activity...</p>
    </div>

    <div v-else-if="activities.length === 0" class="text-center py-12 text-gray-500">
      <svg class="w-12 h-12 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
      </svg>
      <p>No activity found.</p>
      <p class="text-sm mt-1">Activity will appear here as actions are taken on this ticket.</p>
    </div>

    <div v-else class="relative">
      <!-- Timeline Line -->
      <div class="absolute left-8 top-0 bottom-0 w-0.5 bg-gray-200"></div>
      
      <!-- Activity Items -->
      <div class="space-y-8">
        <div 
          v-for="(activity, index) in activities" 
          :key="activity.id"
          class="relative flex items-start space-x-4"
        >
          <!-- Timeline Dot -->
          <div class="relative flex items-center justify-center">
            <div :class="getActivityDotClasses(activity.type)" class="w-4 h-4 rounded-full border-2 border-white shadow-sm z-10">
              <svg v-if="getActivityIcon(activity.type)" class="w-2.5 h-2.5 text-white" fill="currentColor" viewBox="0 0 20 20">
                <path :d="getActivityIcon(activity.type)" />
              </svg>
            </div>
          </div>
          
          <!-- Activity Content -->
          <div class="flex-1 min-w-0 pb-8">
            <!-- Activity Card -->
            <div class="bg-white border border-gray-200 rounded-lg p-4 shadow-sm">
              <!-- Activity Header -->
              <div class="flex items-center justify-between mb-2">
                <div class="flex items-center space-x-2">
                  <div class="w-6 h-6 bg-gray-300 rounded-full flex items-center justify-center">
                    <span class="text-xs font-medium text-gray-700">
                      {{ activity.user?.name?.charAt(0)?.toUpperCase() || '?' }}
                    </span>
                  </div>
                  <span class="text-sm font-medium text-gray-900">{{ activity.user?.name || 'System' }}</span>
                  <span :class="getActivityTypeClasses(activity.type)" class="px-2 py-1 rounded-full text-xs font-medium">
                    {{ formatActivityType(activity.type) }}
                  </span>
                </div>
                <span class="text-xs text-gray-500" :title="formatDateTime(activity.created_at)">
                  {{ formatRelativeTime(activity.created_at) }}
                </span>
              </div>
              
              <!-- Activity Description -->
              <div class="text-sm text-gray-700 mb-3">
                {{ formatActivityDescription(activity) }}
              </div>
              
              <!-- Activity Details -->
              <div v-if="activity.details" class="space-y-2">
                <!-- Status Change Details -->
                <div v-if="activity.type === 'status_change'" class="flex items-center space-x-2 text-sm">
                  <span class="text-gray-600">From:</span>
                  <span :class="getStatusClasses(activity.details.old_status)" class="px-2 py-1 rounded-full text-xs font-medium">
                    {{ formatStatus(activity.details.old_status) }}
                  </span>
                  <span class="text-gray-600">→</span>
                  <span :class="getStatusClasses(activity.details.new_status)" class="px-2 py-1 rounded-full text-xs font-medium">
                    {{ formatStatus(activity.details.new_status) }}
                  </span>
                </div>
                
                <!-- Assignment Details -->
                <div v-if="activity.type === 'assignment'" class="flex items-center space-x-2 text-sm">
                  <span class="text-gray-600">
                    {{ activity.details.old_assignee ? 'Reassigned from' : 'Assigned to' }}:
                  </span>
                  <span v-if="activity.details.old_assignee" class="text-gray-600">
                    {{ activity.details.old_assignee }} →
                  </span>
                  <span class="font-medium text-gray-900">
                    {{ activity.details.new_assignee || 'Unassigned' }}
                  </span>
                </div>
                
                <!-- Time Entry Details -->
                <div v-if="activity.type === 'time_entry'" class="space-y-1">
                  <div class="flex items-center space-x-4 text-sm">
                    <span class="text-gray-600">Duration:</span>
                    <span class="font-medium text-gray-900">{{ formatDuration(activity.details.duration) }}</span>
                    <span v-if="activity.details.billable" class="text-green-600 text-xs">
                      Billable: ${{ activity.details.billable_amount }}
                    </span>
                  </div>
                  <div v-if="activity.details.description" class="text-sm text-gray-600">
                    {{ activity.details.description }}
                  </div>
                </div>
                
                <!-- Add-on Details -->
                <div v-if="activity.type === 'addon'" class="space-y-1">
                  <div class="flex items-center space-x-4 text-sm">
                    <span class="text-gray-600">Type:</span>
                    <span class="font-medium text-gray-900">{{ formatAddonType(activity.details.addon_type) }}</span>
                    <span v-if="activity.details.estimated_cost" class="text-green-600 text-xs">
                      Est: ${{ activity.details.estimated_cost }}
                    </span>
                  </div>
                  <div v-if="activity.details.description" class="text-sm text-gray-600">
                    {{ activity.details.description }}
                  </div>
                </div>
                
                <!-- Comment Details -->
                <div v-if="activity.type === 'comment' && activity.details.content" class="text-sm text-gray-700 bg-gray-50 rounded-md p-3">
                  <div v-html="formatMessage(activity.details.content)"></div>
                  <div v-if="activity.details.is_internal" class="mt-2">
                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                      Internal Comment
                    </span>
                  </div>
                </div>
                
                <!-- Attachment Details -->
                <div v-if="activity.type === 'attachment' && activity.details.attachments" class="space-y-2">
                  <div 
                    v-for="attachment in activity.details.attachments" 
                    :key="attachment.id"
                    class="flex items-center space-x-2 text-sm"
                  >
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                    </svg>
                    <a :href="attachment.url" target="_blank" class="text-blue-600 hover:text-blue-700">
                      {{ attachment.filename }}
                    </a>
                    <span class="text-xs text-gray-500">({{ formatFileSize(attachment.size) }})</span>
                  </div>
                </div>
                
                <!-- Additional Notes -->
                <div v-if="activity.details.notes" class="text-sm text-gray-600 italic mt-2">
                  "{{ activity.details.notes }}"
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Load More Button -->
    <div v-if="hasMorePages" class="text-center">
      <button
        @click="loadMoreActivity"
        :disabled="loadingMore"
        class="bg-white border border-gray-300 rounded-md px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
      >
        {{ loadingMore ? 'Loading...' : 'Load More Activity' }}
      </button>
    </div>

    <!-- Activity Stats -->
    <div class="bg-gray-50 rounded-lg p-6">
      <h4 class="text-lg font-semibold text-gray-900 mb-4">Activity Statistics</h4>
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="text-center">
          <div class="text-2xl font-bold text-blue-600">{{ stats.total_activities }}</div>
          <div class="text-sm text-gray-600">Total Events</div>
        </div>
        <div class="text-center">
          <div class="text-2xl font-bold text-green-600">{{ stats.comments_count }}</div>
          <div class="text-sm text-gray-600">Comments</div>
        </div>
        <div class="text-center">
          <div class="text-2xl font-bold text-purple-600">{{ stats.status_changes }}</div>
          <div class="text-sm text-gray-600">Status Changes</div>
        </div>
        <div class="text-center">
          <div class="text-2xl font-bold text-orange-600">{{ stats.participants_count }}</div>
          <div class="text-sm text-gray-600">Participants</div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import axios from 'axios'

// Props
const props = defineProps({
  ticket: {
    type: Object,
    required: true
  }
})

// Reactive data
const activities = ref([])
const availableUsers = ref([])
const loading = ref(false)
const loadingMore = ref(false)
const currentPage = ref(1)
const hasMorePages = ref(false)

// Filters
const filters = ref({
  type: '',
  user_id: ''
})

// Statistics
const stats = ref({
  total_activities: 0,
  comments_count: 0,
  status_changes: 0,
  participants_count: 0
})

// Methods
const formatActivityType = (type) => {
  const typeMap = {
    'comment': 'Comment',
    'status_change': 'Status Change',
    'assignment': 'Assignment',
    'time_entry': 'Time Entry',
    'addon': 'Add-on',
    'attachment': 'Attachment',
    'creation': 'Created',
    'update': 'Updated'
  }
  return typeMap[type] || type.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase())
}

const formatActivityDescription = (activity) => {
  const descriptions = {
    'comment': 'added a comment',
    'status_change': `changed status from ${formatStatus(activity.details?.old_status)} to ${formatStatus(activity.details?.new_status)}`,
    'assignment': activity.details?.old_assignee 
      ? `reassigned ticket from ${activity.details.old_assignee} to ${activity.details.new_assignee || 'Unassigned'}`
      : `assigned ticket to ${activity.details?.new_assignee || 'Unassigned'}`,
    'time_entry': `logged ${formatDuration(activity.details?.duration)} of work time`,
    'addon': `${activity.details?.action || 'created'} an add-on: ${activity.details?.title}`,
    'attachment': `uploaded ${activity.details?.attachments?.length || 1} file(s)`,
    'creation': 'created this ticket',
    'update': 'updated ticket details'
  }
  
  return descriptions[activity.type] || `performed ${activity.type} action`
}

const formatDateTime = (date) => {
  if (!date) return 'N/A'
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const formatRelativeTime = (date) => {
  if (!date) return 'N/A'
  
  const now = new Date()
  const eventDate = new Date(date)
  const diffMs = now - eventDate
  const diffMins = Math.floor(diffMs / 60000)
  const diffHours = Math.floor(diffMins / 60)
  const diffDays = Math.floor(diffHours / 24)
  
  if (diffMins < 1) return 'Just now'
  if (diffMins < 60) return `${diffMins}m ago`
  if (diffHours < 24) return `${diffHours}h ago`
  if (diffDays < 7) return `${diffDays}d ago`
  
  return formatDateTime(date)
}

const formatStatus = (status) => {
  return status?.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase()) || 'Unknown'
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

const formatAddonType = (type) => {
  const typeMap = {
    'additional_work': 'Additional Work',
    'emergency_support': 'Emergency Support',
    'consultation': 'Consultation',
    'training': 'Training',
    'custom': 'Custom'
  }
  return typeMap[type] || type
}

const formatMessage = (content) => {
  if (!content) return ''
  return content
    .replace(/\n/g, '<br>')
    .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
    .replace(/\*(.*?)\*/g, '<em>$1</em>')
}

const formatFileSize = (bytes) => {
  if (!bytes) return '0 B'
  const k = 1024
  const sizes = ['B', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i]
}

const getActivityDotClasses = (type) => {
  const colorMap = {
    'comment': 'bg-blue-500',
    'status_change': 'bg-purple-500',
    'assignment': 'bg-green-500',
    'time_entry': 'bg-orange-500',
    'addon': 'bg-pink-500',
    'attachment': 'bg-gray-500',
    'creation': 'bg-indigo-500',
    'update': 'bg-yellow-500'
  }
  
  return colorMap[type] || 'bg-gray-400'
}

const getActivityTypeClasses = (type) => {
  const colorMap = {
    'comment': 'bg-blue-100 text-blue-800',
    'status_change': 'bg-purple-100 text-purple-800',
    'assignment': 'bg-green-100 text-green-800',
    'time_entry': 'bg-orange-100 text-orange-800',
    'addon': 'bg-pink-100 text-pink-800',
    'attachment': 'bg-gray-100 text-gray-800',
    'creation': 'bg-indigo-100 text-indigo-800',
    'update': 'bg-yellow-100 text-yellow-800'
  }
  
  return colorMap[type] || 'bg-gray-100 text-gray-800'
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

const getActivityIcon = (type) => {
  const iconMap = {
    'comment': 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-3.582 8-8 8a8.959 8.959 0 01-4.906-1.524A11.956 11.956 0 012 21c0-1.68.322-3.283.913-4.755A12.026 12.026 0 012 12C2 7.582 5.582 4 10 4s8 3.582 8 8z',
    'status_change': 'M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15',
    'assignment': 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z',
    'time_entry': 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
    'addon': 'M12 6v6m0 0v6m0-6h6m-6 0H6',
    'attachment': 'M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13'
  }
  
  return iconMap[type] || null
}

// Data loading methods
const loadActivity = async (resetPage = true) => {
  if (!props.ticket?.id) return
  
  if (resetPage) {
    currentPage.value = 1
    activities.value = []
  }
  
  loading.value = resetPage
  
  try {
    const params = new URLSearchParams({
      page: currentPage.value,
      ...Object.fromEntries(Object.entries(filters.value).filter(([_, v]) => v !== ''))
    })
    
    const response = await axios.get(`/api/tickets/${props.ticket.id}/activity?${params}`)
    const data = response.data
    
    if (resetPage) {
      activities.value = data.data || []
    } else {
      activities.value.push(...(data.data || []))
    }
    
    hasMorePages.value = data.current_page < data.last_page
    
    // Load statistics
    await loadStats()
    
  } catch (error) {
    console.error('Failed to load activity:', error)
    if (resetPage) activities.value = []
  } finally {
    loading.value = false
    loadingMore.value = false
  }
}

const loadMoreActivity = async () => {
  if (loadingMore.value || !hasMorePages.value) return
  
  loadingMore.value = true
  currentPage.value++
  await loadActivity(false)
}

const loadAvailableUsers = async () => {
  try {
    const response = await axios.get('/api/users/assignable')
    availableUsers.value = response.data.data || []
  } catch (error) {
    console.error('Failed to load available users:', error)
    availableUsers.value = []
  }
}

const loadStats = async () => {
  if (!props.ticket?.id) return
  
  try {
    const response = await axios.get(`/api/tickets/${props.ticket.id}/activity-stats`)
    stats.value = response.data.data || {
      total_activities: 0,
      comments_count: 0,
      status_changes: 0,
      participants_count: 0
    }
  } catch (error) {
    console.error('Failed to load activity stats:', error)
  }
}

// Lifecycle
onMounted(() => {
  loadActivity()
  loadAvailableUsers()
})

// Watchers
watch(() => props.ticket?.id, (newId) => {
  if (newId) {
    loadActivity()
  }
})
</script>