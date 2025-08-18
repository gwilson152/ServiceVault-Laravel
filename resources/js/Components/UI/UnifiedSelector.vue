<template>
  <div class="relative">
    <label
      v-if="label"
      :for="inputId"
      class="block text-sm font-medium text-gray-700 mb-2"
    >
      {{ label }}
      <span v-if="required" class="text-red-500">*</span>
    </label>

    <!-- Search Input (only show when no item is selected) -->
    <div v-if="!selectedItem" class="relative">
      <input
        :id="inputId"
        v-model="searchTerm"
        type="text"
        :placeholder="placeholder"
        :required="required"
        :disabled="loading || disabled"
        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 disabled:bg-gray-100 disabled:cursor-not-allowed"
        :class="{ 'border-red-500': error }"
        @focus="handleFocus"
        @blur="handleBlur"
        @keydown="handleKeydown"
      />

      <!-- Dropdown Icon -->
      <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
        <div
          v-if="loading"
          class="inline-block animate-spin rounded-full h-4 w-4 border-b-2 border-blue-600"
        ></div>
        <svg
          v-else
          class="w-4 h-4 text-gray-400"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M19 9l-7 7-7-7"
          />
        </svg>
      </div>
    </div>

    <!-- Selected Item Display -->
    <div v-if="selectedItem" :class="selectedItemClasses">
      <div class="flex items-center justify-between">
        <div class="flex items-center flex-1 min-w-0">
          <!-- Icon -->
          <div :class="itemIconClasses" class="flex-shrink-0 mr-3">
            <component :is="itemIcon" class="w-5 h-5" />
          </div>
          
          <!-- Content -->
          <div class="flex-1 min-w-0">
            <p :class="itemTitleClasses">{{ getItemTitle(selectedItem) }}</p>
            <p v-if="getItemSubtitle(selectedItem)" :class="itemSubtitleClasses">
              {{ getItemSubtitle(selectedItem) }}
            </p>
            
            <!-- Badges -->
            <div v-if="getItemBadges(selectedItem).length > 0" class="flex items-center space-x-2 mt-1">
              <span
                v-for="badge in getItemBadges(selectedItem)"
                :key="badge.text"
                :class="badge.classes"
                class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium"
              >
                {{ badge.text }}
              </span>
            </div>
          </div>
        </div>
        
        <!-- Clear Button -->
        <button
          type="button"
          @click="clearSelection"
          class="text-gray-400 hover:text-gray-600 flex-shrink-0 ml-2"
          :title="`Clear ${type} selection`"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M6 18L18 6M6 6l12 12"
            />
          </svg>
        </button>
      </div>
    </div>

    <!-- Dropdown -->
    <div
      v-if="showDropdown && !selectedItem"
      ref="dropdown"
      class="absolute w-full bg-white border border-gray-300 rounded-lg shadow-lg max-h-60 overflow-y-auto"
      style="z-index: 9999;"
      :class="dropdownPosition"
    >
      <div v-if="loading" class="p-4 text-center text-gray-500">
        <div class="inline-block animate-spin rounded-full h-4 w-4 border-b-2 border-blue-600"></div>
        <span class="ml-2">Loading {{ type }}s...</span>
      </div>

      <div v-else>
        <!-- Create New Option -->
        <div
          v-if="canCreate"
          @mousedown.prevent="openCreateModal"
          class="px-4 py-3 hover:bg-green-50 cursor-pointer border-b border-gray-100 bg-green-25"
        >
          <div class="flex items-center">
            <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center mr-3">
              <svg
                class="w-4 h-4 text-green-600"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M12 6v6m0 0v6m0-6h6m-6 0H6"
                />
              </svg>
            </div>
            <div class="flex-1">
              <p class="text-sm font-medium text-green-700">{{ createOptionText }}</p>
              <p class="text-xs text-green-600">{{ createOptionSubtext }}</p>
            </div>
          </div>
        </div>

        <!-- No items message -->
        <div
          v-if="filteredItems.length === 0 && !canCreate"
          class="p-4 text-center text-gray-500"
        >
          {{ searchTerm ? `No ${type}s found` : `No ${type}s available` }}
        </div>

        <!-- No existing items message (when create option is available and no items) -->
        <div
          v-if="filteredItems.length === 0 && canCreate"
          class="px-4 py-2 text-xs text-gray-500 text-center"
        >
          {{ searchTerm ? `No existing ${type}s match your search` : `No existing ${type}s` }}
        </div>

        <!-- Items List -->
        <div v-if="filteredItems.length > 0">
          <div
            v-for="item in filteredItems"
            :key="getItemKey(item)"
            @mousedown.prevent="selectItem(item)"
            class="px-4 py-3 hover:bg-gray-100 cursor-pointer border-b border-gray-100 last:border-b-0"
          >
            <div class="flex items-center justify-between">
              <div class="flex items-center flex-1 min-w-0">
                <!-- Icon -->
                <div :class="itemIconClasses" class="flex-shrink-0 mr-3">
                  <component :is="itemIcon" class="w-4 h-4" />
                </div>
                
                <!-- Content -->
                <div class="flex-1 min-w-0">
                  <p class="text-sm font-medium text-gray-900">{{ getItemTitle(item) }}</p>
                  <p v-if="getItemSubtitle(item)" class="text-xs text-gray-500 truncate">
                    {{ getItemSubtitle(item) }}
                  </p>
                  
                  <!-- Badges -->
                  <div v-if="getItemBadges(item).length > 0" class="flex items-center space-x-2 mt-1">
                    <span
                      v-for="badge in getItemBadges(item)"
                      :key="badge.text"
                      :class="badge.classes"
                      class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium"
                    >
                      {{ badge.text }}
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Error Message -->
    <p v-if="error" class="mt-1 text-sm text-red-600">{{ error }}</p>

    <!-- Create Modal (Teleported to body to avoid clipping) -->
    <Teleport to="body">
      <component
        v-if="canCreate && showCreateModal"
        :is="createModalComponent"
        :show="showCreateModal"
        :open="showCreateModal"
        v-bind="createModalProps"
        :nested="true"
        @close="closeCreateModal"
        @created="handleItemCreated"
        @saved="handleItemCreated"
      />
    </Teleport>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import {
  UserIcon,
  BuildingOfficeIcon,
  TicketIcon,
  CurrencyDollarIcon,
  UserGroupIcon
} from '@heroicons/vue/24/outline'

// Import modal components
import CreateTicketModalTabbed from '@/Components/Modals/CreateTicketModalTabbed.vue'
import AccountFormModal from '@/Components/AccountFormModal.vue'
import UserFormModal from '@/Components/UserFormModal.vue'

// Props
const props = defineProps({
  modelValue: {
    type: [String, Number, Object],
    default: null,
  },
  type: {
    type: String,
    required: true,
    validator: value => ['ticket', 'account', 'user', 'agent', 'billing-rate'].includes(value)
  },
  items: {
    type: Array,
    default: () => [],
  },
  loading: {
    type: Boolean,
    default: false,
  },
  disabled: {
    type: Boolean,
    default: false,
  },
  label: {
    type: String,
    default: null,
  },
  placeholder: {
    type: String,
    default: null,
  },
  required: {
    type: Boolean,
    default: false,
  },
  error: {
    type: String,
    default: null,
  },
  canCreate: {
    type: Boolean,
    default: false,
  },
  createModalProps: {
    type: Object,
    default: () => ({}),
  },
  nested: {
    type: Boolean,
    default: false,
  },
  // Type-specific props
  agentType: {
    type: String,
    default: null, // For agent selectors: 'timer', 'ticket', 'time', 'billing'
  },
  hierarchical: {
    type: Boolean,
    default: false, // For account selectors
  },
  showRateHierarchy: {
    type: Boolean,
    default: false, // For billing rate selectors
  },
})

// Emits
const emit = defineEmits([
  'update:modelValue',
  'item-selected',
  'item-created',
  // Legacy event names for backward compatibility during migration
  'ticket-selected',
  'ticket-created',
  'account-selected', 
  'account-created',
  'user-selected',
  'user-created',
  'agent-selected',
  'rate-selected',
])

// State
const inputId = `unified-selector-${Math.random().toString(36).substr(2, 9)}`
const searchTerm = ref('')
const showDropdown = ref(false)
const selectedItem = ref(null)
const dropdown = ref(null)
const dropupMode = ref(false)
const showCreateModal = ref(false)

// Type configurations
const typeConfigs = {
  ticket: {
    icon: TicketIcon,
    createModal: CreateTicketModalTabbed,
    createText: 'Create New Ticket',
    createSubtext: 'Add a new ticket to the system',
    titleField: 'title',
    subtitleField: 'ticket_number',
    keyField: 'id',
    selectedClasses: 'p-2 bg-blue-50 border border-blue-200 rounded-lg',
    iconClasses: 'w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center',
    titleClasses: 'text-sm font-medium text-blue-900',
    subtitleClasses: 'text-xs text-blue-700',
    getBadges: (item) => {
      const badges = []
      if (item.status) {
        badges.push({
          text: item.status,
          classes: getStatusClasses(item.status)
        })
      }
      if (item.priority) {
        badges.push({
          text: `${item.priority} Priority`,
          classes: 'bg-gray-100 text-gray-800'
        })
      }
      return badges
    }
  },
  account: {
    icon: BuildingOfficeIcon,
    createModal: AccountFormModal,
    createText: 'Create New Account',
    createSubtext: 'Add a new account to the system',
    titleField: 'name',
    subtitleField: 'display_name',
    keyField: 'id',
    selectedClasses: 'p-3 bg-green-50 border border-green-200 rounded-lg',
    iconClasses: 'w-8 h-8 bg-green-100 rounded-full flex items-center justify-center',
    titleClasses: 'text-sm font-medium text-green-900',
    subtitleClasses: 'text-xs text-green-700',
    getBadges: (item) => {
      const badges = []
      if (item.account_type) {
        badges.push({
          text: item.account_type,
          classes: 'bg-green-100 text-green-800'
        })
      }
      return badges
    }
  },
  user: {
    icon: UserIcon,
    createModal: UserFormModal,
    createText: 'Create New User',
    createSubtext: 'Add a new user to the system',
    titleField: 'name',
    subtitleField: 'email',
    keyField: 'id',
    selectedClasses: 'p-3 bg-purple-50 border border-purple-200 rounded-lg',
    iconClasses: 'w-8 h-8 bg-purple-100 rounded-full flex items-center justify-center',
    titleClasses: 'text-sm font-medium text-purple-900',
    subtitleClasses: 'text-xs text-purple-700',
    getBadges: (item) => {
      const badges = []
      if (item.user_type) {
        badges.push({
          text: item.user_type,
          classes: getUserTypeBadgeClasses(item.user_type)
        })
      }
      if (item.role_template?.name) {
        badges.push({
          text: item.role_template.name,
          classes: 'bg-purple-100 text-purple-800'
        })
      }
      return badges
    }
  },
  agent: {
    icon: UserGroupIcon,
    createModal: UserFormModal,
    createText: 'Create New Agent',
    createSubtext: 'Add a new service agent',
    titleField: 'name',
    subtitleField: 'email',
    keyField: 'id',
    selectedClasses: 'p-3 bg-indigo-50 border border-indigo-200 rounded-lg',
    iconClasses: 'w-8 h-8 bg-indigo-100 rounded-full flex items-center justify-center',
    titleClasses: 'text-sm font-medium text-indigo-900',
    subtitleClasses: 'text-xs text-indigo-700',
    getBadges: (item) => {
      const badges = []
      if (item.user_type) {
        badges.push({
          text: item.user_type,
          classes: getUserTypeBadgeClasses(item.user_type)
        })
      }
      // Add agent-specific permissions badges
      if (props.agentType && item.permissions) {
        const agentPermission = `${props.agentType}.act_as_agent`
        if (item.permissions.includes(agentPermission)) {
          badges.push({
            text: `${props.agentType} agent`,
            classes: 'bg-indigo-100 text-indigo-800'
          })
        }
      }
      return badges
    }
  },
  'billing-rate': {
    icon: CurrencyDollarIcon,
    createModal: null, // Billing rates typically don't have creation modals
    createText: 'Create New Rate',
    createSubtext: 'Add a new billing rate',
    titleField: 'name',
    subtitleField: 'rate',
    keyField: 'id',
    selectedClasses: 'p-3 bg-yellow-50 border border-yellow-200 rounded-lg',
    iconClasses: 'w-8 h-8 bg-yellow-100 rounded-full flex items-center justify-center',
    titleClasses: 'text-sm font-medium text-yellow-900',
    subtitleClasses: 'text-xs text-yellow-700',
    getBadges: (item) => {
      const badges = []
      if (item.is_default) {
        badges.push({
          text: 'Default',
          classes: 'bg-yellow-100 text-yellow-800'
        })
      }
      if (item.account_id) {
        badges.push({
          text: 'Account Rate',
          classes: 'bg-blue-100 text-blue-800'
        })
      } else {
        badges.push({
          text: 'Global Rate',
          classes: 'bg-green-100 text-green-800'
        })
      }
      return badges
    }
  }
}

// Computed properties
const config = computed(() => typeConfigs[props.type])

const defaultPlaceholder = computed(() => {
  return props.placeholder || `Search and select a ${props.type}...`
})

const createOptionText = computed(() => config.value.createText)
const createOptionSubtext = computed(() => config.value.createSubtext)
const createModalComponent = computed(() => config.value.createModal)

const itemIcon = computed(() => config.value.icon)
const selectedItemClasses = computed(() => config.value.selectedClasses)
const itemIconClasses = computed(() => config.value.iconClasses)
const itemTitleClasses = computed(() => config.value.titleClasses)
const itemSubtitleClasses = computed(() => config.value.subtitleClasses)

const filteredItems = computed(() => {
  if (!searchTerm.value) return props.items

  const term = searchTerm.value.toLowerCase()
  return props.items.filter(item => {
    const title = getItemTitle(item).toLowerCase()
    const subtitle = getItemSubtitle(item)?.toLowerCase() || ''
    return title.includes(term) || subtitle.includes(term)
  })
})

const dropdownPosition = computed(() => {
  return dropupMode.value ? 'bottom-full mb-1' : 'top-full mt-1'
})

// Methods
const getItemKey = (item) => {
  return item[config.value.keyField]
}

const getItemTitle = (item) => {
  if (props.type === 'ticket') {
    return `#${item.ticket_number} - ${item[config.value.titleField]}`
  }
  if (props.type === 'billing-rate') {
    return item[config.value.titleField] || ''
  }
  return item[config.value.titleField] || ''
}

const getItemSubtitle = (item) => {
  if (props.type === 'billing-rate') {
    return item.description || `$${item.rate}/hr`
  }
  return item[config.value.subtitleField] || null
}

const getItemBadges = (item) => {
  return config.value.getBadges ? config.value.getBadges(item) : []
}

const selectItem = (item) => {
  selectedItem.value = item
  searchTerm.value = ''
  showDropdown.value = false

  const itemKey = getItemKey(item)
  emit('update:modelValue', itemKey)
  emit('item-selected', item)
  
  // Emit legacy events for backward compatibility during migration
  emit(`${props.type}-selected`, item)
}

const clearSelection = () => {
  selectedItem.value = null
  searchTerm.value = ''
  emit('update:modelValue', null)
  emit('item-selected', null)
  emit(`${props.type}-selected`, null)

  // Optionally reopen dropdown and focus input
  showDropdown.value = true
  setTimeout(() => {
    const input = document.getElementById(inputId)
    if (input) {
      input.focus()
      checkDropdownPosition()
    }
  }, 10)
}

const openCreateModal = async () => {
  showDropdown.value = false

  // Ensure CSRF token is ready before opening modal
  try {
    await window.axios.get('/sanctum/csrf-cookie')
  } catch (error) {
    console.error('Failed to initialize CSRF token:', error)
  }

  showCreateModal.value = true
}

const closeCreateModal = () => {
  showCreateModal.value = false
}

const handleItemCreated = (newItem) => {
  // Close modal
  showCreateModal.value = false

  // Select the newly created item
  selectItem(newItem)

  // Emit creation event
  emit('item-created', newItem)
  emit(`${props.type}-created`, newItem)
}

const handleFocus = () => {
  if (props.disabled || props.loading) return
  showDropdown.value = true
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

// Helper functions for badge styling
const getStatusClasses = (status) => {
  const statusLower = status.toLowerCase()
  if (statusLower.includes('open') || statusLower.includes('new')) {
    return 'bg-green-100 text-green-800'
  } else if (statusLower.includes('progress') || statusLower.includes('assigned')) {
    return 'bg-blue-100 text-blue-800'
  } else if (statusLower.includes('pending') || statusLower.includes('waiting')) {
    return 'bg-yellow-100 text-yellow-800'
  } else if (statusLower.includes('closed') || statusLower.includes('resolved')) {
    return 'bg-gray-100 text-gray-800'
  } else if (statusLower.includes('cancelled')) {
    return 'bg-red-100 text-red-800'
  }
  return 'bg-gray-100 text-gray-800'
}

const getUserTypeBadgeClasses = (userType) => {
  switch (userType) {
    case 'agent':
      return 'bg-blue-100 text-blue-800'
    case 'account_user':
      return 'bg-green-100 text-green-800'
    case 'admin':
      return 'bg-red-100 text-red-800'
    default:
      return 'bg-gray-100 text-gray-800'
  }
}

// Initialize selected item from modelValue
const initializeSelectedItem = () => {
  if (props.modelValue && props.items.length > 0) {
    const item = props.items.find(i => getItemKey(i) == props.modelValue)
    if (item) {
      selectedItem.value = item
    } else {
      selectedItem.value = null
    }
  } else if (!props.modelValue) {
    selectedItem.value = null
  }
}

// Watchers
watch(() => props.modelValue, () => {
  initializeSelectedItem()
})

watch(() => props.items, () => {
  initializeSelectedItem()
})


</script>