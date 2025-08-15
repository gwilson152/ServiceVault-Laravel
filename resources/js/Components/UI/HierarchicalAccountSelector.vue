<template>
  <div class="relative">
    <label v-if="label" :for="inputId" class="block text-sm font-medium text-gray-700 mb-2">
      {{ label }}
      <span v-if="required" class="text-red-500">*</span>
    </label>
    
    <!-- Search Input (only show when no account is selected) -->
    <div v-if="!selectedAccount" class="relative">
      <input
        :id="inputId"
        v-model="searchTerm"
        type="text"
        :placeholder="placeholder"
        :required="required"
        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
        @focus="handleFocus"
        @blur="handleBlur"
        @keydown="handleKeydown"
      >
      
      <!-- Dropdown Icon -->
      <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
        </svg>
      </div>
    </div>
    
    <!-- Selected Account Display -->
    <div v-if="selectedAccount" class="p-2 bg-blue-50 border border-blue-200 rounded-lg">
      <div class="flex items-center justify-between">
        <div>
          <p class="text-sm font-medium text-blue-900">{{ selectedAccount.name }}</p>
          <p v-if="selectedAccount.company_name" class="text-xs text-blue-700">{{ selectedAccount.company_name }}</p>
        </div>
        <button
          type="button"
          @click="clearSelection"
          class="text-blue-600 hover:text-blue-800"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>
    </div>
    
    <!-- Dropdown -->
    <div
      v-if="showDropdown && !selectedAccount"
      ref="dropdown"
      class="absolute z-50 w-full bg-white border border-gray-300 rounded-lg shadow-lg max-h-60 overflow-y-auto"
      :class="dropdownPosition"
    >
      <div v-if="isLoading" class="p-4 text-center text-gray-500">
        <div class="inline-block animate-spin rounded-full h-4 w-4 border-b-2 border-blue-600"></div>
        <span class="ml-2">Loading accounts...</span>
      </div>
      
      <div v-else-if="filteredAccounts.length === 0 && !showCreateOption" class="p-4 text-center text-gray-500">
        {{ searchTerm ? 'No accounts found' : 'No accounts available' }}
      </div>
      <div v-else-if="filteredAccounts.length === 0 && showCreateOption" class="px-4 py-2 text-xs text-gray-500 text-center border-t border-gray-100">
        {{ searchTerm ? 'No existing accounts match your search' : 'No existing accounts' }}
      </div>
      
      <div v-else>
        <!-- Create New Account Option (always show if enabled) -->
        <div
          v-if="showCreateOption"
          @mousedown.prevent="openCreateModal"
          class="px-4 py-3 hover:bg-green-50 cursor-pointer border-b border-gray-100 bg-green-25"
        >
          <div class="flex items-center">
            <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center mr-3">
              <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
              </svg>
            </div>
            <div class="flex-1">
              <p class="text-sm font-medium text-green-700">Create New Account</p>
              <p class="text-xs text-green-600">Add a new account to the system</p>
            </div>
          </div>
        </div>
        
        <!-- Existing Accounts -->
        <div v-if="filteredAccounts.length > 0">
          <div
            v-for="account in filteredAccounts"
            :key="account.id"
            @mousedown.prevent="selectAccount(account)"
            class="px-4 py-3 hover:bg-gray-100 cursor-pointer border-b border-gray-100 last:border-b-0"
            :class="{ 'bg-blue-50': selectedAccount?.id === account.id }"
          >
          <div class="flex items-center">
            <!-- Indentation for hierarchy -->
            <div :style="{ marginLeft: `${account.level * 16}px` }" class="flex items-center flex-1">
              <!-- Hierarchy indicator -->
              <div v-if="account.level > 0" class="flex items-center mr-2">
                <div class="w-3 border-t border-gray-300"></div>
                <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
              </div>
              
              <div class="flex-1">
                <p class="text-sm font-medium text-gray-900">{{ account.name }}</p>
                <p v-if="account.company_name" class="text-xs text-gray-600">{{ account.company_name }}</p>
                <p v-if="account.account_type" class="text-xs text-gray-500 capitalize">{{ account.account_type }}</p>
              </div>
            </div>
          </div>
        </div>
        </div>
      </div>
    </div>
    
    <!-- Error Message -->
    <p v-if="error" class="mt-1 text-sm text-red-600">{{ error }}</p>
    
    <!-- Create Account Modal (Teleported to body to avoid clipping) -->
    <Teleport to="body">
      <AccountFormModal
        v-if="showCreateOption"
        :open="showCreateAccountModal"
        :account="null"
        @close="closeCreateModal"
        @saved="handleAccountCreated"
      />
    </Teleport>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch, Teleport } from 'vue'
import axios from 'axios'
import AccountFormModal from '@/Components/AccountFormModal.vue'

// Props
const props = defineProps({
  modelValue: {
    type: [String, Object],
    default: null
  },
  label: {
    type: String,
    default: 'Account'
  },
  placeholder: {
    type: String,
    default: 'Search and select an account...'
  },
  required: {
    type: Boolean,
    default: false
  },
  error: {
    type: String,
    default: null
  },
  reopenOnClear: {
    type: Boolean,
    default: true
  },
  showCreateOption: {
    type: Boolean,
    default: true
  },
  preselectedParentId: {
    type: [String, Number],
    default: null
  }
})

// Emits
const emit = defineEmits(['update:modelValue', 'account-selected', 'account-created'])

// State
const inputId = `account-selector-${Math.random().toString(36).substr(2, 9)}`
const searchTerm = ref('')
const showDropdown = ref(false)
const isLoading = ref(false)
const accounts = ref([])
const selectedAccount = ref(null)
const dropdown = ref(null)
const dropupMode = ref(false)
const showCreateAccountModal = ref(false)

// Computed
const filteredAccounts = computed(() => {
  if (!searchTerm.value) return accounts.value
  
  const term = searchTerm.value.toLowerCase()
  return accounts.value.filter(account => 
    account.name.toLowerCase().includes(term) ||
    (account.company_name && account.company_name.toLowerCase().includes(term)) ||
    account.account_type.toLowerCase().includes(term)
  )
})

const dropdownPosition = computed(() => {
  return dropupMode.value ? 'bottom-full mb-1' : 'top-full mt-1'
})

// Methods
const loadAccounts = async () => {
  isLoading.value = true
  try {
    const response = await axios.get('/api/accounts/selector/hierarchical')
    accounts.value = flattenHierarchy(response.data.data)
  } catch (error) {
    console.error('Failed to load accounts:', error)
  } finally {
    isLoading.value = false
  }
}

const flattenHierarchy = (hierarchical, level = 0) => {
  const flattened = []
  
  for (const account of hierarchical) {
    flattened.push({
      ...account,
      level
    })
    
    if (account.children && account.children.length > 0) {
      flattened.push(...flattenHierarchy(account.children, level + 1))
    }
  }
  
  return flattened
}

const selectAccount = (account) => {
  selectedAccount.value = account
  searchTerm.value = ''
  showDropdown.value = false
  
  emit('update:modelValue', account.id)
  emit('account-selected', account)
}

const clearSelection = () => {
  selectedAccount.value = null
  searchTerm.value = ''
  emit('update:modelValue', null)
  emit('account-selected', null)
  
  // Optionally reopen dropdown and focus input
  if (props.reopenOnClear) {
    showDropdown.value = true
    // Focus the input on next tick
    setTimeout(() => {
      const input = document.getElementById(inputId)
      if (input) {
        input.focus()
        checkDropdownPosition()
      }
    }, 10)
  }
}

const openCreateModal = async () => {
  showDropdown.value = false
  
  // Ensure CSRF token is ready before opening modal
  try {
    await window.axios.get('/sanctum/csrf-cookie')
  } catch (error) {
    console.error('Failed to initialize CSRF token:', error)
  }
  
  showCreateAccountModal.value = true
}

const closeCreateModal = () => {
  showCreateAccountModal.value = false
}

const handleAccountCreated = (newAccount) => {
  // Close modal
  showCreateAccountModal.value = false
  
  // Reload accounts to include the new one
  loadAccounts().then(() => {
    // Select the newly created account
    selectAccount(newAccount)
    
    // Emit event to parent to refresh account lists if needed
    emit('account-created', newAccount)
  })
}

const handleFocus = () => {
  showDropdown.value = true
  // Check position on next tick when dropdown is rendered
  setTimeout(checkDropdownPosition, 10)
}

const handleBlur = () => {
  // Delay hiding dropdown to allow for selection
  setTimeout(() => {
    showDropdown.value = false
  }, 150)
}

const handleKeydown = (event) => {
  if (event.key === 'Escape') {
    showDropdown.value = false
  }
}

const checkDropdownPosition = () => {
  const input = document.getElementById(inputId)
  if (!input) return
  
  const inputRect = input.getBoundingClientRect()
  const viewportHeight = window.innerHeight
  const spaceBelow = viewportHeight - inputRect.bottom
  const spaceAbove = inputRect.top
  
  // If there's not enough space below (for 250px dropdown) and more space above, use dropup
  dropupMode.value = spaceBelow < 250 && spaceAbove > spaceBelow
}

// Initialize selected account from modelValue
const initializeSelectedAccount = () => {
  if (props.modelValue && accounts.value.length > 0) {
    const account = accounts.value.find(acc => acc.id === props.modelValue)
    if (account) {
      selectedAccount.value = account
    }
  }
}

// Lifecycle
onMounted(() => {
  loadAccounts()
})

// Watchers
watch(() => props.modelValue, initializeSelectedAccount)
watch(accounts, initializeSelectedAccount)
</script>