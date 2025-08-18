<template>
  <div class="space-y-4">
    <!-- Account Selection -->
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-2">
        Account <span class="text-red-500">*</span>
      </label>
      
      <div class="relative">
        <input
          v-model="accountSearchTerm"
          type="text"
          :placeholder="selectedAccount ? selectedAccount.name : 'Search and select an account...'"
          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
          @focus="showAccountDropdown = true"
          @blur="handleAccountBlur"
        >
        
        <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
          <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
          </svg>
        </div>
      </div>
      
      <!-- Account Dropdown -->
      <div
        v-if="showAccountDropdown"
        class="absolute z-50 w-full mt-1 bg-white border border-gray-300 rounded-lg shadow-lg max-h-60 overflow-y-auto"
      >
        <div v-if="isLoadingAccounts" class="p-4 text-center text-gray-500">
          <div class="inline-block animate-spin rounded-full h-4 w-4 border-b-2 border-blue-600"></div>
          <span class="ml-2">Loading accounts...</span>
        </div>
        
        <div v-else-if="filteredAccounts.length === 0" class="p-4 text-center text-gray-500">
          No accounts found
        </div>
        
        <div v-else>
          <div
            v-for="account in filteredAccounts"
            :key="account.id"
            @mousedown.prevent="selectAccount(account)"
            class="px-4 py-3 hover:bg-gray-100 cursor-pointer border-b border-gray-100 last:border-b-0"
            :class="{ 'bg-blue-50': selectedAccount?.id === account.id }"
          >
            <div class="flex items-center">
              <div :style="{ marginLeft: `${(account.depth || 0) * 16}px` }" class="flex items-center flex-1">
                <div v-if="account.depth > 0" class="flex items-center mr-2">
                  <div class="w-3 border-t border-gray-300"></div>
                  <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                  </svg>
                </div>
                
                <div class="flex-1">
                  <p class="text-sm font-medium text-gray-900">{{ account.name }}</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      
      <p v-if="accountError" class="mt-1 text-sm text-red-600">{{ accountError }}</p>
    </div>

    <!-- User Selection -->
    <div v-if="showUserSelector">
      <label class="block text-sm font-medium text-gray-700 mb-2">
        Assign Agent <span class="text-gray-500 text-xs">(optional - will auto-select account)</span>
      </label>
      
      <div class="relative">
        <select
          v-model="selectedUserId"
          :disabled="isLoadingAllUsers"
          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 disabled:bg-gray-100"
          @change="handleUserChange"
        >
          <option value="">{{ isLoadingAllUsers ? 'Loading users...' : 'Select an agent...' }}</option>
          <optgroup 
            v-for="accountGroup in usersByAccount" 
            :key="accountGroup.account.id"
            :label="accountGroup.account.name"
          >
            <option
              v-for="user in accountGroup.users"
              :key="user.id"
              :value="user.id"
            >
              {{ user.name }} ({{ user.email }})
            </option>
          </optgroup>
        </select>
        
        <div v-if="isLoadingAllUsers" class="absolute inset-y-0 right-0 flex items-center pr-3">
          <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-blue-600"></div>
        </div>
      </div>
      
      <p v-if="userError" class="mt-1 text-sm text-red-600">{{ userError }}</p>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import axios from 'axios'

// Props
const props = defineProps({
  accountId: {
    type: String,
    default: null
  },
  userId: {
    type: String, 
    default: null
  },
  accountError: {
    type: String,
    default: null
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
const emit = defineEmits(['update:accountId', 'update:userId'])

// State
const accountSearchTerm = ref('')
const showAccountDropdown = ref(false)
const isLoadingAccounts = ref(false)
const isLoadingAllUsers = ref(false)
const accounts = ref([])
const allUsers = ref([])
const selectedAccount = ref(null)
const selectedUserId = ref(props.userId)

// Computed
const filteredAccounts = computed(() => {
  if (!accountSearchTerm.value) return accounts.value
  
  const term = accountSearchTerm.value.toLowerCase()
  return accounts.value.filter(account => 
    account.name.toLowerCase().includes(term) ||
  )
})

const usersByAccount = computed(() => {
  // Group users by their account
  const groups = {}
  
  for (const user of allUsers.value) {
    if (user.account) {
      const accountId = user.account.id
      if (!groups[accountId]) {
        groups[accountId] = {
          account: user.account,
          users: []
        }
      }
      groups[accountId].users.push(user)
    }
  }
  
  // Convert to array and sort by account name
  return Object.values(groups).sort((a, b) => a.account.name.localeCompare(b.account.name))
})

// Methods
const loadAccounts = async () => {
  isLoadingAccounts.value = true
  try {
    const response = await axios.get('/api/accounts/selector/hierarchical')
    accounts.value = flattenHierarchy(response.data.data)
  } catch (error) {
    console.error('Failed to load accounts:', error)
  } finally {
    isLoadingAccounts.value = false
  }
}

const flattenHierarchy = (hierarchical, depth = 0) => {
  const flattened = []
  
  for (const account of hierarchical) {
    flattened.push({
      ...account,
      depth
    })
    
    if (account.children && account.children.length > 0) {
      flattened.push(...flattenHierarchy(account.children, depth + 1))
    }
  }
  
  return flattened
}

const selectAccount = (account) => {
  selectedAccount.value = account
  accountSearchTerm.value = ''
  showAccountDropdown.value = false
  
  // Emit account selection
  emit('update:accountId', account.id)
  
  // If we're showing user selector and user was selected, keep the user if they belong to this account
  if (props.showUserSelector && selectedUserId.value) {
    const currentUser = allUsers.value.find(u => u.id === selectedUserId.value)
    if (!currentUser || currentUser.account.id !== account.id) {
      // Clear user selection if they don't belong to the selected account
      selectedUserId.value = ''
      emit('update:userId', '')
    }
  }
}

const loadAllUsers = async () => {
  if (!props.showUserSelector) return
  
  isLoadingAllUsers.value = true
  try {
    const response = await axios.get('/api/users', {
      params: {
        per_page: 200, // Get more users since we're loading all
        with_account: true // Make sure we get account relationship
      }
    })
    
    allUsers.value = response.data.data || []
  } catch (error) {
    console.error('Failed to load users:', error)
    allUsers.value = []
  } finally {
    isLoadingAllUsers.value = false
  }
}

const handleUserChange = () => {
  const selectedUser = allUsers.value.find(u => u.id === selectedUserId.value)
  
  if (selectedUser && selectedUser.account) {
    // Auto-select the user's account
    const userAccount = accounts.value.find(acc => acc.id === selectedUser.account.id)
    if (userAccount) {
      selectedAccount.value = userAccount
      emit('update:accountId', selectedUser.account.id)
    }
  }
  
  emit('update:userId', selectedUserId.value)
}

const handleAccountBlur = () => {
  setTimeout(() => {
    showAccountDropdown.value = false
  }, 150)
}

// Lifecycle
onMounted(async () => {
  await Promise.all([
    loadAccounts(),
    loadAllUsers()
  ])
  
  // Initialize selections after data is loaded
  initializeFromProps()
})

const initializeFromProps = () => {
  // Initialize account selection
  if (props.accountId && accounts.value.length > 0) {
    const account = accounts.value.find(acc => acc.id === props.accountId)
    if (account) {
      selectedAccount.value = account
    }
  }
  
  // Initialize user selection
  if (props.userId && allUsers.value.length > 0) {
    const user = allUsers.value.find(u => u.id === props.userId)
    if (user && user.account) {
      // Auto-select user's account if not already selected
      if (!selectedAccount.value || selectedAccount.value.id !== user.account.id) {
        const userAccount = accounts.value.find(acc => acc.id === user.account.id)
        if (userAccount) {
          selectedAccount.value = userAccount
          emit('update:accountId', user.account.id)
        }
      }
    }
  }
}

// Watchers to handle prop changes
watch(() => props.accountId, (newAccountId) => {
  if (newAccountId && accounts.value.length > 0) {
    const account = accounts.value.find(acc => acc.id === newAccountId)
    if (account && account.id !== selectedAccount.value?.id) {
      selectedAccount.value = account
    }
  } else if (!newAccountId) {
    selectedAccount.value = null
  }
})

watch(() => props.userId, (newUserId) => {
  selectedUserId.value = newUserId
  if (newUserId && allUsers.value.length > 0) {
    const user = allUsers.value.find(u => u.id === newUserId)
    if (user && user.account) {
      // Auto-select user's account
      const userAccount = accounts.value.find(acc => acc.id === user.account.id)
      if (userAccount && (!selectedAccount.value || selectedAccount.value.id !== user.account.id)) {
        selectedAccount.value = userAccount
        emit('update:accountId', user.account.id)
      }
    }
  }
})
</script>