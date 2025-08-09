<template>
  <div class="account-management-widget">
    <!-- Quick Stats -->
    <div class="stats-row grid grid-cols-2 gap-2 mb-4">
      <div class="stat-item text-center p-2 bg-green-50 rounded-lg">
        <p class="text-lg font-bold text-green-900">{{ widgetData?.total_accounts || 0 }}</p>
        <p class="text-xs text-green-600">Total Accounts</p>
      </div>
      <div class="stat-item text-center p-2 bg-blue-50 rounded-lg">
        <p class="text-lg font-bold text-blue-900">{{ widgetData?.recent_accounts?.length || 0 }}</p>
        <p class="text-xs text-blue-600">Recent</p>
      </div>
    </div>

    <!-- Recent Accounts -->
    <div class="recent-accounts mb-4">
      <h4 class="text-sm font-medium text-gray-700 mb-2">Recent Accounts</h4>
      <div v-if="recentAccounts.length > 0" class="space-y-2">
        <div
          v-for="account in recentAccounts.slice(0, 3)"
          :key="account.id"
          class="account-item flex items-center justify-between p-2 bg-gray-50 rounded-lg hover:bg-gray-100 cursor-pointer transition-colors"
          @click="viewAccount(account.id)"
        >
          <div class="flex items-center space-x-3">
            <div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center">
              <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-4m-5 0H3m2 0h3M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
              </svg>
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium text-gray-900 truncate">{{ account.name }}</p>
              <p class="text-xs text-gray-500">{{ account.users_count || 0 }} users</p>
            </div>
          </div>
          <div class="text-xs text-gray-400">
            {{ formatDate(account.created_at) }}
          </div>
        </div>
      </div>
      
      <div v-else class="empty-state text-center py-3">
        <div class="mx-auto flex items-center justify-center h-8 w-8 rounded-full bg-gray-100 mb-1">
          <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-4m-5 0H3m2 0h3M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
          </svg>
        </div>
        <p class="text-xs text-gray-500">No recent accounts</p>
      </div>
    </div>

    <!-- Quick Actions -->
    <div class="quick-actions space-y-2">
      <button 
        @click="createAccount"
        class="action-btn w-full flex items-center justify-center px-3 py-2 bg-green-100 text-green-700 rounded-lg hover:bg-green-200 transition-colors text-sm"
      >
        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
        </svg>
        Create Account
      </button>
      
      <div class="grid grid-cols-2 gap-2">
        <button 
          @click="viewAllAccounts"
          class="action-btn flex items-center justify-center px-2 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors text-xs"
        >
          <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path>
          </svg>
          View All
        </button>
        
        <button 
          @click="manageDomains"
          class="action-btn flex items-center justify-center px-2 py-2 bg-indigo-100 text-indigo-700 rounded-lg hover:bg-indigo-200 transition-colors text-xs"
        >
          <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9v-9m0 0V3"></path>
          </svg>
          Domains
        </button>
      </div>
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
const recentAccounts = computed(() => {
  return props.widgetData?.recent_accounts || []
})

// Methods
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

const viewAccount = (accountId) => {
  console.log('Viewing account:', accountId)
  // Navigate to account detail page when routes are available
  // router.visit(route('accounts.show', accountId))
}

const createAccount = () => {
  console.log('Creating new account')
  // Navigate to account creation page
  // router.visit(route('accounts.create'))
}

const viewAllAccounts = () => {
  console.log('Navigating to all accounts')
  // router.visit(route('accounts.index'))
}

const manageDomains = () => {
  console.log('Managing domain mappings')
  // router.visit(route('domain-mappings.index'))
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

.account-item {
  transition: all 0.2s ease-in-out;
}

.account-item:hover {
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