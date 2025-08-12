<template>
  <!-- Page Header -->
  <div class="bg-white shadow-sm border-b border-gray-200 mb-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
      <div class="flex items-center justify-between">
        <div>
          <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Admin Dashboard
          </h2>
          <p class="text-sm text-gray-600 mt-1">
            System administration and management
          </p>
        </div>
      </div>
    </div>
  </div>

  <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6 text-gray-900">

            <!-- Quick Stats Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
              <div class="bg-blue-50 p-6 rounded-lg border border-blue-200">
                <div class="flex items-center">
                  <div class="flex-1">
                    <p class="text-sm font-medium text-blue-600">Total Users</p>
                    <p class="text-2xl font-bold text-blue-900">{{ stats.total_users || 0 }}</p>
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
                    <p class="text-2xl font-bold text-green-900">{{ stats.active_timers || 0 }}</p>
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
                    <p class="text-2xl font-bold text-indigo-900">{{ stats.total_accounts || 0 }}</p>
                  </div>
                  <div class="w-12 h-12 bg-indigo-100 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-4m-5 0H3m2 0h3M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                    </svg>
                  </div>
                </div>
              </div>

              <div class="bg-purple-50 p-6 rounded-lg border border-purple-200">
                <div class="flex items-center">
                  <div class="flex-1">
                    <p class="text-sm font-medium text-purple-600">System Health</p>
                    <p class="text-2xl font-bold text-purple-900">Good</p>
                  </div>
                  <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
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
                  <button class="w-full text-left px-3 py-2 text-sm text-blue-600 hover:bg-blue-50 rounded">
                    View All Users
                  </button>
                  <button class="w-full text-left px-3 py-2 text-sm text-blue-600 hover:bg-blue-50 rounded">
                    Manage Roles
                  </button>
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
                  <button class="w-full text-left px-3 py-2 text-sm text-green-600 hover:bg-green-50 rounded">
                    View Accounts
                  </button>
                  <button class="w-full text-left px-3 py-2 text-sm text-green-600 hover:bg-green-50 rounded">
                    Create Account
                  </button>
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
                  <button class="w-full text-left px-3 py-2 text-sm text-indigo-600 hover:bg-indigo-50 rounded">
                    General Settings
                  </button>
                  <button class="w-full text-left px-3 py-2 text-sm text-indigo-600 hover:bg-indigo-50 rounded">
                    Email Configuration
                  </button>
                  <button class="w-full text-left px-3 py-2 text-sm text-indigo-600 hover:bg-indigo-50 rounded">
                    Theme Management
                  </button>
                </div>
              </div>

              <!-- Billing & Invoicing -->
              <div class="bg-white p-6 rounded-lg border border-gray-200 shadow-sm">
                <h3 class="text-lg font-semibold text-gray-900 mb-3">Billing & Invoicing</h3>
                <p class="text-sm text-gray-600 mb-4">Manage billing rates and invoice generation</p>
                <div class="space-y-2">
                  <button class="w-full text-left px-3 py-2 text-sm text-yellow-600 hover:bg-yellow-50 rounded">
                    Billing Rates
                  </button>
                  <button class="w-full text-left px-3 py-2 text-sm text-yellow-600 hover:bg-yellow-50 rounded">
                    Invoice Templates
                  </button>
                  <button class="w-full text-left px-3 py-2 text-sm text-yellow-600 hover:bg-yellow-50 rounded">
                    Payment Settings
                  </button>
                </div>
              </div>

              <!-- Reports & Analytics -->
              <div class="bg-white p-6 rounded-lg border border-gray-200 shadow-sm">
                <h3 class="text-lg font-semibold text-gray-900 mb-3">Reports & Analytics</h3>
                <p class="text-sm text-gray-600 mb-4">View system reports and analytics</p>
                <div class="space-y-2">
                  <button class="w-full text-left px-3 py-2 text-sm text-purple-600 hover:bg-purple-50 rounded">
                    Time Reports
                  </button>
                  <button class="w-full text-left px-3 py-2 text-sm text-purple-600 hover:bg-purple-50 rounded">
                    User Activity
                  </button>
                  <button class="w-full text-left px-3 py-2 text-sm text-purple-600 hover:bg-purple-50 rounded">
                    System Usage
                  </button>
                </div>
              </div>

              <!-- System Maintenance -->
              <div class="bg-white p-6 rounded-lg border border-gray-200 shadow-sm">
                <h3 class="text-lg font-semibold text-gray-900 mb-3">System Maintenance</h3>
                <p class="text-sm text-gray-600 mb-4">Database maintenance and system health</p>
                <div class="space-y-2">
                  <button class="w-full text-left px-3 py-2 text-sm text-red-600 hover:bg-red-50 rounded">
                    Database Backup
                  </button>
                  <button class="w-full text-left px-3 py-2 text-sm text-red-600 hover:bg-red-50 rounded">
                    System Logs
                  </button>
                  <button class="w-full text-left px-3 py-2 text-sm text-red-600 hover:bg-red-50 rounded">
                    Health Check
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'

// Define persistent layout
defineOptions({
  layout: AppLayout
})

// Define props for any data passed from the controller
defineProps({
  stats: {
    type: Object,
    default: () => ({
      total_users: 0,
      active_timers: 0,
      total_accounts: 0,
    })
  }
})

// Reactive data
const stats = ref({
  total_users: 0,
  active_timers: 0,
  total_accounts: 0,
})

// Component mounted
onMounted(() => {
  // TODO: Load dashboard statistics via API when implemented
  console.log('Admin Dashboard loaded')
})
</script>