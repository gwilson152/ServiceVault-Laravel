<template>
  <div class="space-y-4">
    <!-- Account Selection -->
    <UnifiedSelector
      v-model="selectedAccountId"
      type="account"
      :items="availableAccounts"
      :label="accountLabel"
      :placeholder="accountPlaceholder"
      :required="accountRequired"
      :hierarchical="true"
      :error="accountError"
      @item-selected="handleAccountSelected"
    />
    
    <!-- User Selection (shows when account is selected) -->
    <div v-if="selectedAccountId && showUserSelector">
      <label v-if="userLabel" :for="userInputId" class="block text-sm font-medium text-gray-700 mb-2">
        {{ userLabel }}
        <span v-if="userRequired" class="text-red-500">*</span>
      </label>
      
      <div class="relative">
        <select
          :id="userInputId"
          v-model="selectedUserId"
          :required="userRequired"
          :disabled="isLoadingUsers"
          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 disabled:bg-gray-100 disabled:cursor-not-allowed"
          @change="handleUserSelected"
        >
          <option value="">{{ isLoadingUsers ? 'Loading users...' : userPlaceholder }}</option>
          <option
            v-for="user in availableUsers"
            :key="user.id"
            :value="user.id"
          >
            {{ user.name }} ({{ user.email }})
          </option>
        </select>
        
        <!-- Loading indicator -->
        <div v-if="isLoadingUsers" class="absolute inset-y-0 right-0 flex items-center pr-3">
          <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-blue-600"></div>
        </div>
      </div>
      
      <p v-if="userError" class="mt-1 text-sm text-red-600">{{ userError }}</p>
      
      <!-- User Info Display -->
      <div v-if="selectedUser && !isLoadingUsers" class="mt-2 p-2 bg-green-50 border border-green-200 rounded-lg">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-green-900">{{ selectedUser.name }}</p>
            <p class="text-xs text-green-700">{{ selectedUser.email }}</p>
            <p v-if="selectedUser.role_template" class="text-xs text-green-600">{{ selectedUser.role_template.name }}</p>
          </div>
          <button
            type="button"
            @click="clearUserSelection"
            class="text-green-600 hover:text-green-800"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import UnifiedSelector from './UnifiedSelector.vue'
import axios from 'axios'

// Props
const props = defineProps({
  // Account props
  accountValue: {
    type: String,
    default: null
  },
  accountLabel: {
    type: String,
    default: 'Account'
  },
  accountPlaceholder: {
    type: String,
    default: 'Search and select an account...'
  },
  accountRequired: {
    type: Boolean,
    default: false
  },
  accountError: {
    type: String,
    default: null
  },
  
  // User props
  userValue: {
    type: String,
    default: null
  },
  userLabel: {
    type: String,
    default: 'User'
  },
  userPlaceholder: {
    type: String,
    default: 'Select user...'
  },
  userRequired: {
    type: Boolean,
    default: false
  },
  userError: {
    type: String,
    default: null
  },
  showUserSelector: {
    type: Boolean,
    default: true
  }
})

// Emits
const emit = defineEmits(['update:accountValue', 'update:userValue', 'account-selected', 'user-selected'])

// State
const userInputId = `user-selector-${Math.random().toString(36).substr(2, 9)}`
const selectedAccountId = ref(props.accountValue)
const selectedUserId = ref(props.userValue)
const selectedAccount = ref(null)
const selectedUser = ref(null)
const availableAccounts = ref([])
const availableUsers = ref([])
const isLoadingUsers = ref(false)

// Methods
const handleAccountSelected = (account) => {
  selectedAccount.value = account
  selectedAccountId.value = account?.id || null
  
  // Clear user selection when account changes
  if (selectedUserId.value) {
    clearUserSelection()
  }
  
  // Load users for the selected account
  if (account) {
    loadUsersForAccount(account.id)
  } else {
    availableUsers.value = []
  }
  
  emit('update:accountValue', selectedAccountId.value)
  emit('account-selected', account)
}

const handleUserSelected = () => {
  const user = availableUsers.value.find(u => u.id === selectedUserId.value)
  selectedUser.value = user
  
  emit('update:userValue', selectedUserId.value)
  emit('user-selected', user)
}

const clearUserSelection = () => {
  selectedUserId.value = null
  selectedUser.value = null
  emit('update:userValue', null)
  emit('user-selected', null)
}

const loadAvailableAccounts = async () => {
  try {
    const response = await axios.get('/api/accounts', {
      params: {
        per_page: 100
      }
    })
    availableAccounts.value = response.data.data || []
  } catch (error) {
    console.error('Failed to load available accounts:', error)
    availableAccounts.value = []
  }
}

const loadUsersForAccount = async (accountId) => {
  if (!accountId) {
    availableUsers.value = []
    return
  }
  
  isLoadingUsers.value = true
  try {
    const response = await axios.get('/api/users', {
      params: {
        account_id: accountId,
        per_page: 100 // Get all users for the account
      }
    })
    
    availableUsers.value = response.data.data || []
    
    // If there's a preselected user value, find and set the user object
    if (props.userValue && availableUsers.value.length > 0) {
      const user = availableUsers.value.find(u => u.id === props.userValue)
      if (user) {
        selectedUser.value = user
        selectedUserId.value = props.userValue
      }
    }
  } catch (error) {
    console.error('Failed to load users for account:', error)
    availableUsers.value = []
  } finally {
    isLoadingUsers.value = false
  }
}

// Watchers
watch(() => props.accountValue, (newValue) => {
  if (newValue !== selectedAccountId.value) {
    selectedAccountId.value = newValue
    // If we have an account ID but no account object, we need to find it
    if (newValue && !selectedAccount.value) {
      // This will be handled by the UnifiedSelector
    }
  }
})

watch(() => props.userValue, (newValue) => {
  if (newValue !== selectedUserId.value) {
    selectedUserId.value = newValue
    if (newValue && availableUsers.value.length > 0) {
      const user = availableUsers.value.find(u => u.id === newValue)
      selectedUser.value = user
    }
  }
})

// Initialize
onMounted(async () => {
  await loadAvailableAccounts()
})
</script>