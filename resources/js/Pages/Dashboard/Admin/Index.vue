<template>
  <!-- Page Header -->
  <div class="bg-white shadow-sm border-b border-gray-200 mb-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
      <div class="flex items-center justify-between">
        <div>
          <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            System Administration
          </h2>
          <p class="text-sm text-gray-600 mt-1">
            Complete system overview and management
          </p>
        </div>
      </div>
    </div>
  </div>

  <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6 text-gray-900">

            <!-- System Health Status -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
              <div class="bg-white p-6 rounded-lg border border-gray-200 shadow-sm">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">System Health</h3>
                <div class="space-y-3">
                  <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600">Database</span>
                    <span :class="getHealthColor(systemHealth?.database)" class="px-2 py-1 text-xs font-medium rounded-full">
                      {{ systemHealth?.database || 'unknown' }}
                    </span>
                  </div>
                  <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600">Redis</span>
                    <span :class="getHealthColor(systemHealth?.redis)" class="px-2 py-1 text-xs font-medium rounded-full">
                      {{ systemHealth?.redis || 'unknown' }}
                    </span>
                  </div>
                  <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600">Storage</span>
                    <span :class="getHealthColor(systemHealth?.storage_disk)" class="px-2 py-1 text-xs font-medium rounded-full">
                      {{ systemHealth?.storage_disk || 'unknown' }}
                    </span>
                  </div>
                </div>
              </div>

              <div class="bg-white p-6 rounded-lg border border-gray-200 shadow-sm">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Queue Status</h3>
                <div class="space-y-3">
                  <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600">Queue Jobs</span>
                    <span class="text-lg font-bold text-blue-600">{{ systemHealth?.queue_jobs || 0 }}</span>
                  </div>
                  <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600">Failed Jobs</span>
                    <span class="text-lg font-bold text-red-600">{{ systemHealth?.failed_jobs || 0 }}</span>
                  </div>
                </div>
              </div>

              <div class="bg-white p-6 rounded-lg border border-gray-200 shadow-sm">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Activity Summary</h3>
                <div class="space-y-3">
                  <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600">New Users This Month</span>
                    <span class="text-lg font-bold text-green-600">{{ stats?.users_this_month || 0 }}</span>
                  </div>
                  <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600">Time Tracked Today</span>
                    <span class="text-lg font-bold text-purple-600">{{ formatDuration(stats?.time_tracked_today || 0) }}</span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Quick Stats Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
              <div class="bg-blue-50 p-6 rounded-lg border border-blue-200">
                <div class="flex items-center">
                  <div class="flex-1">
                    <p class="text-sm font-medium text-blue-600">Total Users</p>
                    <p class="text-2xl font-bold text-blue-900">{{ stats?.total_users || 0 }}</p>
                  </div>
                  <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z"></path>
                    </svg>
                  </div>
                </div>
              </div>

              <div class="bg-green-50 p-6 rounded-lg border border-green-200">
                <div class="flex items-center">
                  <div class="flex-1">
                    <p class="text-sm font-medium text-green-600">Active Timers</p>
                    <p class="text-2xl font-bold text-green-900">{{ stats?.active_timers || 0 }}</p>
                  </div>
                  <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                  </div>
                </div>
              </div>

              <div class="bg-indigo-50 p-6 rounded-lg border border-indigo-200">
                <div class="flex items-center">
                  <div class="flex-1">
                    <p class="text-sm font-medium text-indigo-600">Total Accounts</p>
                    <p class="text-2xl font-bold text-indigo-900">{{ stats?.total_accounts || 0 }}</p>
                  </div>
                  <div class="w-12 h-12 bg-indigo-100 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-4m-5 0H3m2 0h3M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                    </svg>
                  </div>
                </div>
              </div>

              <div class="bg-yellow-50 p-6 rounded-lg border border-yellow-200">
                <div class="flex items-center">
                  <div class="flex-1">
                    <p class="text-sm font-medium text-yellow-600">Domain Mappings</p>
                    <p class="text-2xl font-bold text-yellow-900">{{ stats?.domain_mappings || 0 }}</p>
                  </div>
                  <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9v-9m0 0V3"></path>
                    </svg>
                  </div>
                </div>
              </div>
            </div>

            <!-- Admin Actions Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
              <!-- User Management -->
              <div class="bg-white p-6 rounded-lg border border-gray-200 shadow-sm">
                <h3 class="text-lg font-semibold text-gray-900 mb-3">User Management</h3>
                <p class="text-sm text-gray-600 mb-4">Manage user accounts, roles, and permissions</p>
                <div class="space-y-2">
                  <Link :href="route('dashboard.admin.users')" class="w-full text-left px-3 py-2 text-sm text-blue-600 hover:bg-blue-50 rounded block">
                    View All Users
                  </Link>
                  <Link :href="route('dashboard.admin.role-templates')" class="w-full text-left px-3 py-2 text-sm text-blue-600 hover:bg-blue-50 rounded block">
                    Manage Role Templates
                  </Link>
                  <button class="w-full text-left px-3 py-2 text-sm text-blue-600 hover:bg-blue-50 rounded">
                    Send Invitations
                  </button>
                </div>
              </div>

              <!-- Account Management -->
              <div class="bg-white p-6 rounded-lg border border-gray-200 shadow-sm">
                <h3 class="text-lg font-semibold text-gray-900 mb-3">Account Management</h3>
                <p class="text-sm text-gray-600 mb-4">Configure accounts and organizational structure</p>
                <div class="space-y-2">
                  <Link :href="route('dashboard.admin.accounts')" class="w-full text-left px-3 py-2 text-sm text-green-600 hover:bg-green-50 rounded block">
                    View Accounts
                  </Link>
                  <Link :href="route('dashboard.admin.domain-mappings')" class="w-full text-left px-3 py-2 text-sm text-green-600 hover:bg-green-50 rounded block">
                    Domain Mappings
                  </Link>
                  <button class="w-full text-left px-3 py-2 text-sm text-green-600 hover:bg-green-50 rounded">
                    Account Hierarchy
                  </button>
                </div>
              </div>

              <!-- System Settings -->
              <div class="bg-white p-6 rounded-lg border border-gray-200 shadow-sm">
                <h3 class="text-lg font-semibold text-gray-900 mb-3">System Settings</h3>
                <p class="text-sm text-gray-600 mb-4">Configure system-wide settings and preferences</p>
                <div class="space-y-2">
                  <Link :href="route('dashboard.admin.settings')" class="w-full text-left px-3 py-2 text-sm text-indigo-600 hover:bg-indigo-50 rounded block">
                    System Settings
                  </Link>
                  <button class="w-full text-left px-3 py-2 text-sm text-indigo-600 hover:bg-indigo-50 rounded">
                    Email Configuration
                  </button>
                  <button class="w-full text-left px-3 py-2 text-sm text-indigo-600 hover:bg-indigo-50 rounded">
                    Theme Management
                  </button>
                </div>
              </div>
            </div>

            <!-- Recent Activity -->
            <div v-if="recentActivity && recentActivity.length > 0" class="mt-8">
              <h3 class="text-lg font-semibold text-gray-900 mb-4">Recent System Activity</h3>
              <div class="bg-white rounded-lg border border-gray-200 shadow-sm">
                <div class="divide-y divide-gray-200">
                  <div v-for="activity in recentActivity" :key="activity.id" class="p-4">
                    <div class="flex items-center space-x-3">
                      <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center">
                          <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                          </svg>
                        </div>
                      </div>
                      <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900">{{ activity.title || activity.description }}</p>
                        <p class="text-sm text-gray-600">{{ activity.description }}</p>
                        <p class="text-sm text-gray-500">{{ formatDate(activity.created_at) }}</p>
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
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

// Define persistent layout
defineOptions({
  layout: AppLayout
})

// Define props for data passed from the controller
const props = defineProps({
  title: {
    type: String,
    default: 'System Administration'
  },
  stats: {
    type: Object,
    default: () => ({
      total_users: 0,
      total_accounts: 0,
      active_timers: 0,
      total_time_entries: 0,
      users_this_month: 0,
      time_tracked_today: 0,
      pending_approvals: 0,
      domain_mappings: 0
    })
  },
  systemHealth: {
    type: Object,
    default: () => ({
      database: 'unknown',
      redis: 'unknown',
      storage_disk: 'unknown',
      queue_jobs: 0,
      failed_jobs: 0
    })
  },
  recentActivity: {
    type: Array,
    default: () => []
  }
})

// Helper method to get health status colors
const getHealthColor = (status) => {
  switch (status) {
    case 'healthy':
      return 'bg-green-100 text-green-800'
    case 'warning':
      return 'bg-yellow-100 text-yellow-800'
    case 'error':
      return 'bg-red-100 text-red-800'
    default:
      return 'bg-gray-100 text-gray-800'
  }
}

// Helper method to format duration
const formatDuration = (seconds) => {
  if (!seconds) return '0h 0m'
  const hours = Math.floor(seconds / 3600)
  const minutes = Math.floor((seconds % 3600) / 60)
  return `${hours}h ${minutes}m`
}

// Helper method to format date
const formatDate = (date) => {
  if (!date) return ''
  return new Date(date).toLocaleString()
}
</script>