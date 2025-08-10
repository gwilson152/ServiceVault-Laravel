<template>
  <div class="widget-content">
    <div class="widget-header">
      <h3 class="widget-title">{{ props.widgetConfig?.name || 'Account Activity' }}</h3>
      <div class="widget-actions">
        <button
          @click="refreshData"
          :disabled="isLoading"
          class="p-1 text-gray-400 hover:text-gray-600 transition-colors"
          title="Refresh"
        >
          <svg class="w-4 h-4" :class="{ 'animate-spin': isLoading }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
          </svg>
        </button>
      </div>
    </div>

    <div v-if="isLoading" class="widget-loading">
      <div class="flex items-center justify-center h-32">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
      </div>
    </div>

    <div v-else-if="error" class="widget-error">
      <div class="text-center py-6">
        <div class="text-red-600 text-sm">{{ error }}</div>
        <button @click="refreshData" class="mt-2 text-xs text-blue-600 hover:text-blue-800">
          Try Again
        </button>
      </div>
    </div>

    <div v-else class="widget-data">
      <div class="p-4">
        <!-- Activity Timeline -->
        <div v-if="activities.length === 0" class="text-center py-6 text-gray-500">
          <svg class="mx-auto h-12 w-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
          </svg>
          <p class="mt-2 text-sm">No recent activity</p>
        </div>

        <div v-else class="space-y-3">
          <div 
            v-for="activity in activities" 
            :key="activity.id"
            class="flex items-start space-x-3 p-3 bg-gray-50 rounded-lg"
          >
            <div class="flex-shrink-0">
              <div 
                class="w-8 h-8 rounded-full flex items-center justify-center"
                :class="getActivityTypeClass(activity.type)"
              >
                <component :is="getActivityIcon(activity.type)" class="w-4 h-4 text-white" />
              </div>
            </div>
            
            <div class="min-w-0 flex-1">
              <div class="text-sm font-medium text-gray-900">
                {{ activity.title }}
              </div>
              <div class="text-sm text-gray-600 mt-1">
                {{ activity.description }}
              </div>
              <div class="flex items-center justify-between mt-2">
                <div class="text-xs text-gray-500">
                  {{ formatRelativeTime(activity.created_at) }}
                </div>
                <div class="text-xs text-gray-400">
                  {{ activity.user_name }}
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Account Context Info -->
      <div v-if="accountContext?.name" class="border-t border-gray-200 p-4 bg-gray-50">
        <div class="text-xs text-gray-500">
          Activity for: <span class="font-medium text-gray-700">{{ accountContext.name }}</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'

// Props
const props = defineProps({
  widgetData: {
    type: [Object, Array],
    default: null
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
const isLoading = ref(false)
const error = ref(null)
const activities = ref([])

// Mock data for demonstration
const generateMockActivities = () => {
  return [
    {
      id: 1,
      type: 'ticket',
      title: 'Ticket Created',
      description: 'New ticket #ST-2024-001 created for server maintenance',
      user_name: 'John Smith',
      created_at: new Date(Date.now() - 1000 * 60 * 30), // 30 minutes ago
    },
    {
      id: 2,
      type: 'time_entry',
      title: 'Time Entry Logged',
      description: '2.5 hours logged for network troubleshooting',
      user_name: 'Sarah Johnson',
      created_at: new Date(Date.now() - 1000 * 60 * 60 * 2), // 2 hours ago
    },
    {
      id: 3,
      type: 'invoice',
      title: 'Invoice Generated',
      description: 'Monthly service invoice INV-2024-012 generated',
      user_name: 'System',
      created_at: new Date(Date.now() - 1000 * 60 * 60 * 6), // 6 hours ago
    },
    {
      id: 4,
      type: 'user',
      title: 'User Access Updated',
      description: 'Access permissions updated for Alice Brown',
      user_name: 'Admin User',
      created_at: new Date(Date.now() - 1000 * 60 * 60 * 24), // 1 day ago
    },
    {
      id: 5,
      type: 'communication',
      title: 'Communication Sent',
      description: 'System maintenance notification sent to all users',
      user_name: 'System',
      created_at: new Date(Date.now() - 1000 * 60 * 60 * 48), // 2 days ago
    }
  ]
}

// Methods
const refreshData = async () => {
  isLoading.value = true
  error.value = null
  
  try {
    // TODO: Replace with actual API call when implemented
    await new Promise(resolve => setTimeout(resolve, 1000)) // Simulate API call
    
    // Generate mock data
    activities.value = generateMockActivities()
    
  } catch (err) {
    error.value = err.message || 'Failed to load account activity'
  } finally {
    isLoading.value = false
  }
}

const getActivityTypeClass = (type) => {
  const classes = {
    ticket: 'bg-blue-500',
    time_entry: 'bg-green-500',
    invoice: 'bg-yellow-500',
    user: 'bg-purple-500',
    communication: 'bg-indigo-500',
    default: 'bg-gray-500'
  }
  return classes[type] || classes.default
}

const getActivityIcon = (type) => {
  // Return SVG path strings for different activity types
  const icons = {
    ticket: 'TicketIcon',
    time_entry: 'ClockIcon',
    invoice: 'CurrencyIcon',
    user: 'UserIcon',
    communication: 'ChatIcon',
    default: 'DocumentIcon'
  }
  return icons[type] || icons.default
}

const formatRelativeTime = (date) => {
  const now = new Date()
  const diff = now - new Date(date)
  const seconds = Math.floor(diff / 1000)
  const minutes = Math.floor(seconds / 60)
  const hours = Math.floor(minutes / 60)
  const days = Math.floor(hours / 24)

  if (days > 0) return `${days} day${days > 1 ? 's' : ''} ago`
  if (hours > 0) return `${hours} hour${hours > 1 ? 's' : ''} ago`
  if (minutes > 0) return `${minutes} minute${minutes > 1 ? 's' : ''} ago`
  return 'Just now'
}

// Lifecycle
onMounted(() => {
  if (!props.widgetData) {
    refreshData()
  } else {
    activities.value = Array.isArray(props.widgetData) ? props.widgetData : [props.widgetData]
  }
})
</script>

<style scoped>
.widget-content {
  @apply bg-white rounded-lg shadow-sm border border-gray-200 h-full flex flex-col;
}

.widget-header {
  @apply flex items-center justify-between p-4 border-b border-gray-200;
}

.widget-title {
  @apply text-lg font-semibold text-gray-900;
}

.widget-actions {
  @apply flex items-center gap-2;
}

.widget-loading,
.widget-error,
.widget-data {
  @apply flex-1;
}

.widget-data {
  @apply overflow-auto;
}

/* Custom SVG Icons as pseudo-elements */
.TicketIcon::before {
  content: "";
  mask: url("data:image/svg+xml,%3csvg fill='currentColor' viewBox='0 0 20 20' xmlns='http://www.w3.org/2000/svg'%3e%3cpath fill-rule='evenodd' d='M5 2a1 1 0 011 1v1h1a1 1 0 010 2H6v1a1 1 0 01-2 0V6H3a1 1 0 010-2h1V3a1 1 0 011-1zm0 10a1 1 0 011 1v1h1a1 1 0 110 2H6v1a1 1 0 11-2 0v-1H3a1 1 0 110-2h1v-1a1 1 0 011-1zM12 2a1 1 0 01.967.744L14.146 7.2 17.5 9.134a1 1 0 010 1.732L14.146 12.8l-1.179 4.456a1 1 0 01-1.806.152L9 14.146l-2.161 3.262a1 1 0 01-1.806-.152L3.854 12.8.5 10.866a1 1 0 010-1.732L3.854 7.2l1.179-4.456A1 1 0 015.5 2h6.5z' clip-rule='evenodd'%3e%3c/path%3e%3c/svg%3e");
}
</style>