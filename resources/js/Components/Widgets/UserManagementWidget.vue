<template>
  <div class="user-management-widget">
    <!-- Quick Stats -->
    <div class="stats-row grid grid-cols-3 gap-2 mb-4">
      <div class="stat-item text-center p-2 bg-blue-50 rounded-lg">
        <p class="text-lg font-bold text-blue-900">{{ widgetData?.total_users || 0 }}</p>
        <p class="text-xs text-blue-600">Total Users</p>
      </div>
      <div class="stat-item text-center p-2 bg-green-50 rounded-lg">
        <p class="text-lg font-bold text-green-900">{{ widgetData?.recent_users?.length || 0 }}</p>
        <p class="text-xs text-green-600">Recent</p>
      </div>
      <div class="stat-item text-center p-2 bg-yellow-50 rounded-lg">
        <p class="text-lg font-bold text-yellow-900">{{ widgetData?.pending_invitations || 0 }}</p>
        <p class="text-xs text-yellow-600">Pending</p>
      </div>
    </div>

    <!-- Recent Users -->
    <div class="recent-users mb-4">
      <h4 class="text-sm font-medium text-gray-700 mb-2">Recent Users</h4>
      <div v-if="recentUsers.length > 0" class="space-y-2">
        <div
          v-for="user in recentUsers.slice(0, 3)"
          :key="user.id"
          class="user-item flex items-center space-x-3 p-2 bg-gray-50 rounded-lg hover:bg-gray-100 cursor-pointer transition-colors"
          @click="viewUser(user.id)"
        >
          <div class="w-8 h-8 bg-gray-300 rounded-full flex items-center justify-center">
            <span class="text-xs font-medium text-gray-600">
              {{ getUserInitials(user.name) }}
            </span>
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-sm font-medium text-gray-900 truncate">{{ user.name }}</p>
            <p class="text-xs text-gray-500 truncate">{{ user.email }}</p>
          </div>
          <div class="text-xs text-gray-400">
            {{ formatDate(user.created_at) }}
          </div>
        </div>
      </div>
      
      <div v-else class="empty-state text-center py-3">
        <div class="mx-auto flex items-center justify-center h-8 w-8 rounded-full bg-gray-100 mb-1">
          <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z"></path>
          </svg>
        </div>
        <p class="text-xs text-gray-500">No recent users</p>
      </div>
    </div>

    <!-- Quick Actions -->
    <div class="quick-actions grid grid-cols-2 gap-2">
      <button 
        @click="inviteUser"
        class="action-btn flex items-center justify-center px-3 py-2 bg-indigo-100 text-indigo-700 rounded-lg hover:bg-indigo-200 transition-colors text-sm"
      >
        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
        </svg>
        Invite User
      </button>
      
      <button 
        @click="viewAllUsers"
        class="action-btn flex items-center justify-center px-3 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors text-sm"
      >
        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z"></path>
        </svg>
        View All
      </button>
    </div>

    <!-- Refresh Button -->
    <div class="mt-4 pt-3 border-t border-gray-100">
      <button 
        @click="refreshData"
        :disabled="isRefreshing"
        class="w-full text-xs text-gray-500 hover:text-gray-700 disabled:opacity-50"
      >
        {{ isRefreshing ? 'Refreshing...' : 'Refresh Data' }}
      </button>
    </div>
  </div>
</template>


<script setup>
import { computed, ref } from 'vue'
import { router } from '@inertiajs/vue3'

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

// Computed
const recentUsers = computed(() => {
  return props.widgetData?.recent_users || []
})

// Methods
const getUserInitials = (name) => {
  if (!name) return '??'
  const parts = name.split(' ')
  if (parts.length >= 2) {
    return (parts[0][0] + parts[1][0]).toUpperCase()
  }
  return name.substring(0, 2).toUpperCase()
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

const viewUser = (userId) => {
  console.log('Viewing user:', userId)
  // Navigate to user detail page when routes are available
  // router.visit(route('users.show', userId))
}

const inviteUser = () => {
  console.log('Opening invite user dialog')
  // Open invite user modal or navigate to invite page
  // router.visit(route('users.invite'))
}

const viewAllUsers = () => {
  console.log('Navigating to all users')
  // router.visit(route('users.index'))
}

const refreshData = () => {
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

.user-item {
  transition: all 0.2s ease-in-out;
}

.user-item:hover {
  transform: translateX(2px);
}

.action-btn {
  transition: all 0.2s ease-in-out;
}

.action-btn:hover {
  transform: translateY(-1px);
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}
</style>