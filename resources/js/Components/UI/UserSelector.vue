<template>
  <div class="relative">
    <label v-if="label" :for="inputId" class="block text-sm font-medium text-gray-700 mb-2">
      {{ label }}
      <span v-if="required" class="text-red-500">*</span>
    </label>
    
    <!-- Search Input (only show when no user is selected) -->
    <div v-if="!selectedUser" class="relative">
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
    
    <!-- Selected User Display -->
    <div v-if="selectedUser" class="p-2 bg-blue-50 border border-blue-200 rounded-lg">
      <div class="flex items-center justify-between">
        <div class="flex items-center">
          <div class="w-8 h-8 bg-gray-300 rounded-full flex items-center justify-center mr-3">
            <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
            </svg>
          </div>
          <div>
            <p class="text-sm font-medium text-blue-900">{{ selectedUser.name }}</p>
            <p class="text-xs text-blue-700">{{ selectedUser.email }}</p>
            <p v-if="selectedUser.role_template?.name" class="text-xs text-blue-600">{{ selectedUser.role_template.name }}</p>
          </div>
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
      v-if="showDropdown && !selectedUser"
      ref="dropdown"
      class="absolute z-50 w-full bg-white border border-gray-300 rounded-lg shadow-lg max-h-60 overflow-y-auto"
      :class="dropdownPosition"
    >
      <div v-if="isLoading" class="p-4 text-center text-gray-500">
        <div class="inline-block animate-spin rounded-full h-4 w-4 border-b-2 border-blue-600"></div>
        <span class="ml-2">Loading users...</span>
      </div>
      
      <div v-else>
        <!-- Create New User Option (always show if enabled) -->
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
              <p class="text-sm font-medium text-green-700">Create New User</p>
              <p class="text-xs text-green-600">Add a new user to the system</p>
            </div>
          </div>
        </div>
        
        <!-- Existing Users -->
        <div v-if="filteredUsers.length > 0">
          <div
            v-for="user in filteredUsers"
            :key="user.id"
            @mousedown.prevent="selectUser(user)"
            class="px-4 py-3 hover:bg-gray-100 cursor-pointer border-b border-gray-100 last:border-b-0"
            :class="{ 'bg-blue-50': selectedUser?.id === user.id }"
          >
            <div class="flex items-center">
              <div class="w-8 h-8 bg-gray-300 rounded-full flex items-center justify-center mr-3">
                <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
              </div>
              <div class="flex-1">
                <p class="text-sm font-medium text-gray-900">{{ user.name }}</p>
                <p class="text-xs text-gray-600">{{ user.email }}</p>
                <p v-if="user.role_template?.name" class="text-xs text-gray-500">{{ user.role_template.name }}</p>
                <p v-if="user.account?.name" class="text-xs text-gray-400">{{ user.account.name }}</p>
              </div>
            </div>
          </div>
        </div>
        
        <!-- No Users Message (only show if no create option or after create option) -->
        <div v-if="filteredUsers.length === 0 && !showCreateOption" class="p-4 text-center text-gray-500">
          {{ searchTerm ? 'No users found' : (noUsersMessage || 'No users available') }}
        </div>
        <div v-else-if="filteredUsers.length === 0 && showCreateOption" class="px-4 py-2 text-xs text-gray-500 text-center border-t border-gray-100">
          {{ searchTerm ? 'No existing users match your search' : 'No existing users' }}
        </div>
      </div>
    </div>
    
    <!-- Error Message -->
    <p v-if="error" class="mt-1 text-sm text-red-600">{{ error }}</p>
    
    <!-- Create User Modal -->
    <UserFormModal
      v-if="showCreateOption"
      :open="showCreateUserModal"
      :accounts="accounts"
      :role-templates="roleTemplates"
      :preselected-account-id="preselectedAccountId"
      @close="closeCreateModal"
      @saved="handleUserCreated"
    />
  </div>
</template>

<script setup>
//
// PLANNED EXTENSIONS:
// - AccountUserSelector: Extends UserSelector with account-specific filtering for customer users
// - AgentUserSelector: Extends UserSelector with service provider filtering and assignment features
//

import { ref, computed, nextTick, watch } from 'vue'
import UserFormModal from '@/Components/UserFormModal.vue'

// Props
const props = defineProps({
  modelValue: {
    type: [String, Object],
    default: null
  },
  users: {
    type: Array,
    default: () => []
  },
  isLoading: {
    type: Boolean,
    default: false
  },
  label: {
    type: String,
    default: 'User'
  },
  placeholder: {
    type: String,
    default: 'Search and select a user...'
  },
  required: {
    type: Boolean,
    default: false
  },
  error: {
    type: String,
    default: null
  },
  noUsersMessage: {
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
  accounts: {
    type: Array,
    default: () => []
  },
  roleTemplates: {
    type: Array,
    default: () => []
  },
  preselectedAccountId: {
    type: [String, Number],
    default: null
  }
})

// Emits
const emit = defineEmits(['update:modelValue', 'user-selected', 'user-created'])

// State
const inputId = `user-selector-${Math.random().toString(36).substr(2, 9)}`
const searchTerm = ref('')
const showDropdown = ref(false)
const selectedUser = ref(null)
const dropdown = ref(null)
const dropupMode = ref(false)
const showCreateUserModal = ref(false)

// Computed
const filteredUsers = computed(() => {
  if (!searchTerm.value) return props.users
  
  const term = searchTerm.value.toLowerCase()
  return props.users.filter(user => 
    user.name.toLowerCase().includes(term) ||
    user.email.toLowerCase().includes(term) ||
    (user.role_template?.name && user.role_template.name.toLowerCase().includes(term)) ||
    (user.account?.name && user.account.name.toLowerCase().includes(term))
  )
})

const dropdownPosition = computed(() => {
  return dropupMode.value ? 'bottom-full mb-1' : 'top-full mt-1'
})

// Methods
const selectUser = (user) => {
  selectedUser.value = user
  searchTerm.value = ''
  showDropdown.value = false
  
  emit('update:modelValue', user.id)
  emit('user-selected', user)
}

const clearSelection = () => {
  selectedUser.value = null
  searchTerm.value = ''
  emit('update:modelValue', null)
  emit('user-selected', null)
  
  // Optionally reopen dropdown and focus input
  if (props.reopenOnClear) {
    showDropdown.value = true
    setTimeout(() => {
      const input = document.getElementById(inputId)
      if (input) {
        input.focus()
        checkDropdownPosition()
      }
    }, 10)
  }
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

const openCreateModal = () => {
  showDropdown.value = false
  showCreateUserModal.value = true
}

const closeCreateModal = () => {
  showCreateUserModal.value = false
}

const handleUserCreated = (newUser) => {
  // Close modal
  showCreateUserModal.value = false
  
  // Select the newly created user
  selectUser(newUser)
  
  // Emit event to parent to refresh users list
  emit('user-created', newUser)
}

// Initialize selected user from modelValue
const initializeSelectedUser = () => {
  if (props.modelValue && props.users.length > 0) {
    const user = props.users.find(u => u.id === props.modelValue)
    if (user) {
      selectedUser.value = user
    }
  }
}

// Watchers
watch(() => props.modelValue, initializeSelectedUser)
watch(() => props.users, initializeSelectedUser)
</script>