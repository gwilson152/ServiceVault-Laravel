<template>
  <div class="system-stats-widget">
    <!-- Main Stats Grid -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
      <!-- Total Users -->
      <div class="stat-card bg-blue-50 border border-blue-200 rounded-lg p-3">
        <div class="flex items-center">
          <div class="flex-1">
            <p class="text-xs font-medium text-blue-600">Total Users</p>
            <p class="text-lg font-bold text-blue-900">{{ formatNumber(widgetData?.total_users) }}</p>
          </div>
          <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center">
            <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z"></path>
            </svg>
          </div>
        </div>
        <div class="mt-1">
          <span class="text-xs text-blue-700">+{{ formatNumber(widgetData?.users_this_month) }} this month</span>
        </div>
      </div>

      <!-- Total Accounts -->
      <div class="stat-card bg-green-50 border border-green-200 rounded-lg p-3">
        <div class="flex items-center">
          <div class="flex-1">
            <p class="text-xs font-medium text-green-600">Total Accounts</p>
            <p class="text-lg font-bold text-green-900">{{ formatNumber(widgetData?.total_accounts) }}</p>
          </div>
          <div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center">
            <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-4m-5 0H3m2 0h3M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
            </svg>
          </div>
        </div>
      </div>

      <!-- Active Timers -->
      <div class="stat-card bg-indigo-50 border border-indigo-200 rounded-lg p-3">
        <div class="flex items-center">
          <div class="flex-1">
            <p class="text-xs font-medium text-indigo-600">Active Timers</p>
            <p class="text-lg font-bold text-indigo-900">{{ formatNumber(widgetData?.active_timers) }}</p>
          </div>
          <div class="w-8 h-8 bg-indigo-100 rounded-lg flex items-center justify-center">
            <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
          </div>
        </div>
      </div>

      <!-- Time Entries -->
      <div class="stat-card bg-purple-50 border border-purple-200 rounded-lg p-3">
        <div class="flex items-center">
          <div class="flex-1">
            <p class="text-xs font-medium text-purple-600">Time Entries</p>
            <p class="text-lg font-bold text-purple-900">{{ formatNumber(widgetData?.total_time_entries) }}</p>
          </div>
          <div class="w-8 h-8 bg-purple-100 rounded-lg flex items-center justify-center">
            <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
            </svg>
          </div>
        </div>
      </div>
    </div>

    <!-- Activity Summary -->
    <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-3">
      <div class="activity-item">
        <div class="text-center p-3 bg-gray-50 rounded-lg">
          <p class="text-sm text-gray-600">Time Tracked Today</p>
          <p class="text-lg font-semibold text-gray-900">{{ formatDuration(widgetData?.time_tracked_today) }}</p>
        </div>
      </div>
      
      <div class="activity-item">
        <div class="text-center p-3 bg-yellow-50 rounded-lg">
          <p class="text-sm text-yellow-700">Pending Approvals</p>
          <p class="text-lg font-semibold text-yellow-900">{{ formatNumber(widgetData?.pending_approvals) }}</p>
        </div>
      </div>
      
      <div class="activity-item">
        <div class="text-center p-3 bg-blue-50 rounded-lg">
          <p class="text-sm text-blue-700">Domain Mappings</p>
          <p class="text-lg font-semibold text-blue-900">{{ formatNumber(widgetData?.domain_mappings) }}</p>
        </div>
      </div>
    </div>

    <!-- Quick Actions -->
    <div class="mt-4 pt-3 border-t border-gray-100">
      <div class="flex justify-between text-xs">
        <button 
          @click="refreshStats"
          :disabled="isRefreshing"
          class="text-indigo-600 hover:text-indigo-800 disabled:opacity-50"
        >
          {{ isRefreshing ? 'Refreshing...' : 'Refresh' }}
        </button>
        <span class="text-gray-500">
          Last updated: {{ formatLastUpdate() }}
        </span>
      </div>
    </div>
  </div>
</template>


<script setup>
import { ref } from 'vue'

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

// Methods
const formatNumber = (value) => {
  if (value === undefined || value === null) return '0'
  return new Intl.NumberFormat().format(value)
}

const formatDuration = (seconds) => {
  if (!seconds) return '0h 0m'
  const hours = Math.floor(seconds / 3600)
  const minutes = Math.floor((seconds % 3600) / 60)
  return `${hours}h ${minutes}m`
}

const formatLastUpdate = () => {
  return new Date().toLocaleTimeString([], { 
    hour: '2-digit', 
    minute: '2-digit' 
  })
}

const refreshStats = () => {
  isRefreshing.value = true
  emit('refresh')
  setTimeout(() => {
    isRefreshing.value = false
  }, 1000)
}
</script>

<style scoped>
.stat-card {
  transition: all 0.2s ease-in-out;
}

.stat-card:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.activity-item {
  transition: all 0.2s ease-in-out;
}

.activity-item:hover {
  transform: scale(1.02);
}
</style>