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

    <!-- Search Input (only show when no item is selected or clearable is false) -->
    <div v-if="!selectedItem || !clearable" class="relative">
      <input
        ref="inputRef"
        :id="inputId"
        v-model="actualSearchTerm"
        type="text"
        :placeholder="defaultPlaceholder"
        :required="required"
        :disabled="initialLoading || disabled"
        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 disabled:bg-gray-100 disabled:cursor-not-allowed"
        :class="{ 'border-red-500': error }"
        @focus="handleFocus"
        @blur="handleBlur"
        @keydown="handleKeydown"
      />

      <!-- Dropdown Icon -->
      <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
        <div
          v-if="initialLoading || isSearching || isFetching"
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
    <div v-if="selectedItem && clearable" :class="selectedItemClasses">
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
          v-if="clearable"
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
      v-if="showDropdown"
      :class="[
        'absolute left-0 right-0 bg-white border border-gray-300 rounded-lg shadow-lg z-50 max-h-64 overflow-y-auto',
        dropdownPosition
      ]"
    >
      <!-- Loading state -->
      <div v-if="initialLoading || isSearching || (isFetching && !displayItems.length)" class="p-4 text-center text-gray-500">
        <div class="inline-block animate-spin rounded-full h-5 w-5 border-b-2 border-blue-600 mr-2"></div>
        {{ isSearching || isFetching ? 'Searching...' : 'Loading...' }}
      </div>

      <!-- Error state -->
      <div v-else-if="queryError" class="p-4 text-center text-red-500">
        <p class="text-sm">Error loading {{ type }} data</p>
        <button
          @click="refetch"
          class="text-xs text-blue-500 hover:text-blue-700 mt-1"
        >
          Try again
        </button>
      </div>

      <!-- Recent/search results -->
      <div v-else-if="displayItems.length > 0">
        <!-- Recent items header -->
        <div v-if="showRecentHeader" class="px-3 py-2 bg-gray-50 border-b text-xs font-medium text-gray-500 uppercase">
          {{ actualSearchTerm ? 'Search Results' : 'Recent Items' }}
        </div>

        <!-- Items list -->
        <div
          v-for="(item, index) in displayItems"
          :key="getItemKey(item)"
          @click="selectItem(item)"
          @mouseenter="highlightedIndex = index"
          :class="[
            'px-3 py-3 cursor-pointer border-b border-gray-100 last:border-b-0',
            highlightedIndex === index ? 'bg-blue-50' : 'hover:bg-gray-50'
          ]"
        >
          <div class="flex items-center">
            <!-- Icon -->
            <div :class="[itemIconClasses, 'mr-3']">
              <component :is="itemIcon" class="w-4 h-4" />
            </div>
            
            <!-- Content -->
            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium text-gray-900 truncate">
                {{ getItemTitle(item) }}
              </p>
              <p v-if="getItemSubtitle(item)" class="text-xs text-gray-500 truncate">
                {{ getItemSubtitle(item) }}
              </p>
              
              <!-- Badges -->
              <div v-if="getItemBadges(item).length > 0" class="flex items-center space-x-1 mt-1">
                <span
                  v-for="badge in getItemBadges(item)"
                  :key="badge.text"
                  :class="badge.classes"
                  class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium"
                >
                  {{ badge.text }}
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- No results -->
      <div v-else class="p-4 text-center text-gray-500">
        <p class="text-sm">
          {{ searchTerm ? `No ${type}s found for "${searchTerm}"` : `No recent ${type}s` }}
        </p>
      </div>

      <!-- Create new option -->
      <div
        v-if="canCreate && createModalComponent"
        @click="openCreateModal"
        class="px-3 py-3 cursor-pointer border-t border-gray-200 bg-gray-50 hover:bg-gray-100"
      >
        <div class="flex items-center text-blue-600">
          <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
          </svg>
          <span class="text-sm font-medium">{{ createOptionText }}</span>
        </div>
        <p v-if="createOptionSubtext" class="text-xs text-gray-500 ml-6">
          {{ createOptionSubtext }}
        </p>
      </div>
    </div>

    <!-- Error Message -->
    <p v-if="error" class="mt-1 text-sm text-red-600">{{ error }}</p>

    <!-- Create Modal (Teleported to body to avoid clipping) -->
    <Teleport to="body">
      <component
        v-if="canCreate && showCreateModal && createModalComponent"
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
import { ref, computed, watch, nextTick, onMounted, onUnmounted } from 'vue'
import {
  UserIcon,
  BuildingOfficeIcon,
  TicketIcon,
  CurrencyDollarIcon,
  UserGroupIcon
} from '@heroicons/vue/24/outline'

// Import query composables
import {
  useSelectorTicketsQuery,
  useSelectorAccountsQuery,
  useSelectorUsersQuery,
  useSelectorBillingRatesQuery,
  useActiveSelectorItem,
  selectorUtils
} from '@/Composables/queries/useSelectorQueries'

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
    validator: value => ['ticket', 'account', 'user', 'agent', 'billing-rate', 'role-template'].includes(value)
  },
  
  // Data override for edge cases
  customItems: {
    type: Array,
    default: () => [], // Override with custom dataset for special cases
  },
  recentItemsLimit: {
    type: Number,
    default: 10, // Number of recent items to show initially
  },
  searchMinLength: {
    type: Number,
    default: 2, // Minimum characters before API search
  },
  searchDebounceMs: {
    type: Number,
    default: 300,
  },
  clearable: {
    type: Boolean,
    default: true, // Allow clearing selection
  },
  activeSelection: {
    type: [String, Number],
    default: null, // Force specific item to be selectable
  },
  filterSet: {
    type: Object,
    default: () => ({}), // Applied filters
  },
  autoPermissions: {
    type: Boolean,
    default: true, // Auto-handle permission logic internally
  },
  
  // UI props
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
  
  // Sorting options
  sortField: {
    type: String,
    default: null, // Custom sort field (e.g., 'name', 'created_at')
  },
  sortDirection: {
    type: String,
    default: 'desc', // Sort direction: 'asc' or 'desc'
    validator: value => ['asc', 'desc'].includes(value.toLowerCase())
  },
})

// Emits
const emit = defineEmits(['update:modelValue', 'item-selected', 'item-created'])

// Reactive state
const searchTerm = ref('') // Debounced search term for API
const actualSearchTerm = ref('') // Immediate search term for UI
const showDropdown = ref(false)
const highlightedIndex = ref(-1)
const showCreateModal = ref(false)
const dropupMode = ref(false)
const inputId = `unified-selector-${Math.random().toString(36).substr(2, 9)}`
const isSearching = ref(false)
const inputRef = ref(null)

// Debounce utility
let searchDebounceTimeout = null
const debounceSearch = (value) => {
  clearTimeout(searchDebounceTimeout)
  searchDebounceTimeout = setTimeout(() => {
    searchTerm.value = value
    isSearching.value = false
    // Maintain focus and dropdown state after search
    nextTick(() => {
      if (inputRef.value && document.activeElement !== inputRef.value) {
        inputRef.value.focus()
        showDropdown.value = true
      }
    })
  }, props.searchDebounceMs)
}

// Use custom items if provided (for special cases)
const shouldUseCustomData = computed(() => {
  return props.customItems.length > 0
})

// Query setup based on selector type and custom data preference
const filterSetRef = ref(props.filterSet)
const agentTypeRef = ref(props.agentType)
const sortFieldRef = ref(props.sortField)
const sortDirectionRef = ref(props.sortDirection)
const enableQueries = computed(() => !shouldUseCustomData.value && !props.disabled)

// Initialize appropriate query based on type
let selectorQuery = null
let activeItemQuery = null

if (!shouldUseCustomData.value) {
  switch (props.type) {
    case 'ticket':
      selectorQuery = useSelectorTicketsQuery({
        searchTerm,
        filterSet: filterSetRef,
        sortField: sortFieldRef,
        sortDirection: sortDirectionRef,
        recentLimit: props.recentItemsLimit,
        enabled: enableQueries
      })
      break
    case 'account':
      selectorQuery = useSelectorAccountsQuery({
        searchTerm,
        filterSet: filterSetRef,
        sortField: sortFieldRef,
        sortDirection: sortDirectionRef,
        recentLimit: props.recentItemsLimit,
        enabled: enableQueries
      })
      break
    case 'user':
      selectorQuery = useSelectorUsersQuery({
        searchTerm,
        filterSet: filterSetRef,
        agentType: agentTypeRef,
        sortField: sortFieldRef,
        sortDirection: sortDirectionRef,
        recentLimit: props.recentItemsLimit,
        enabled: enableQueries
      })
      break
    case 'agent':
      selectorQuery = useSelectorUsersQuery({
        searchTerm,
        filterSet: filterSetRef,
        agentType: agentTypeRef,
        sortField: sortFieldRef,
        sortDirection: sortDirectionRef,
        recentLimit: props.recentItemsLimit,
        enabled: enableQueries
      })
      break
    case 'billing-rate':
      selectorQuery = useSelectorBillingRatesQuery({
        searchTerm,
        filterSet: filterSetRef,
        sortField: sortFieldRef,
        sortDirection: sortDirectionRef,
        recentLimit: props.recentItemsLimit,
        enabled: enableQueries
      })
      break
  }

  // Active selection query for ensuring selected item is available
  if (props.activeSelection) {
    activeItemQuery = useActiveSelectorItem(
      props.type,
      ref(props.activeSelection),
      { enabled: computed(() => !!props.activeSelection) }
    )
  }
}

// Computed properties for query results
const queryData = computed(() => selectorQuery?.data?.value || [])
const queryLoading = computed(() => selectorQuery?.isLoading?.value || false)
const queryError = computed(() => selectorQuery?.error?.value)
const refetch = () => selectorQuery?.refetch?.()

// Active item data
const activeItemData = computed(() => activeItemQuery?.data?.value)

// Combined data source
const displayItems = computed(() => {
  if (shouldUseCustomData.value) {
    // Use custom data with simple filtering
    if (actualSearchTerm.value) {
      const term = actualSearchTerm.value.toLowerCase()
      return props.customItems.filter(item => {
        const title = getItemTitle(item).toLowerCase()
        const subtitle = getItemSubtitle(item)?.toLowerCase() || ''
        return title.includes(term) || subtitle.includes(term)
      })
    }
    return props.customItems
  }

  // Use query data
  let items = queryData.value || []
  
  // Ensure active selection is included if provided and not in results
  if (activeItemData.value && !items.some(item => getItemKey(item) === getItemKey(activeItemData.value))) {
    items = [activeItemData.value, ...items]
  }
  
  return items
})

// Loading state - separate initial loading from search fetching
const initialLoading = computed(() => {
  // Only show as loading if it's the initial load (no data yet)
  return queryLoading.value && !queryData.value?.length
})

const isFetching = computed(() => {
  return selectorQuery?.isFetching?.value || false
})

// Search state tracking with debouncing
watch(actualSearchTerm, (newValue, oldValue) => {
  if (newValue !== oldValue) {
    isSearching.value = true
    // Keep dropdown open during search
    if (!showDropdown.value) {
      showDropdown.value = true
    }
    debounceSearch(newValue)
  }
})

// Type configurations (preserved from original)
const getUserTypeBadgeClasses = (userType) => {
  const classes = {
    'service_provider': 'bg-purple-100 text-purple-800',
    'account_user': 'bg-blue-100 text-blue-800',
    'agent': 'bg-green-100 text-green-800',
    'employee': 'bg-gray-100 text-gray-800',
  }
  return classes[userType] || 'bg-gray-100 text-gray-800'
}

const typeConfigs = {
  ticket: {
    icon: TicketIcon,
    createModal: CreateTicketModalTabbed,
    createText: 'Create New Ticket',
    createSubtext: 'Start a new service request',
    titleField: 'title',
    subtitleField: 'account.name',
    keyField: 'id',
    selectedClasses: 'p-3 bg-blue-50 border border-blue-200 rounded-lg',
    iconClasses: 'w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center',
    titleClasses: 'text-sm font-medium text-blue-900',
    subtitleClasses: 'text-xs text-blue-700',
    getBadges: (item) => {
      const badges = []
      if (item.status) {
        badges.push({
          text: item.status,
          classes: 'bg-blue-100 text-blue-800'
        })
      }
      if (item.priority) {
        badges.push({
          text: item.priority,
          classes: 'bg-orange-100 text-orange-800'
        })
      }
      return badges
    }
  },
  account: {
    icon: BuildingOfficeIcon,
    createModal: AccountFormModal,
    createText: 'Create New Account',
    createSubtext: 'Add a new customer account',
    titleField: 'name',
    subtitleField: 'email',
    keyField: 'id',
    selectedClasses: 'p-3 bg-green-50 border border-green-200 rounded-lg',
    iconClasses: 'w-8 h-8 bg-green-100 rounded-full flex items-center justify-center',
    titleClasses: 'text-sm font-medium text-green-900',
    subtitleClasses: 'text-xs text-green-700',
    getBadges: (item) => {
      const badges = []
      if (item.status) {
        badges.push({
          text: item.status,
          classes: item.status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
        })
      }
      return badges
    }
  },
  user: {
    icon: UserIcon,
    createModal: UserFormModal,
    createText: 'Create New User',
    createSubtext: 'Add a new user to the account',
    titleField: 'name',
    subtitleField: 'email',
    keyField: 'id',
    selectedClasses: 'p-3 bg-gray-50 border border-gray-200 rounded-lg',
    iconClasses: 'w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center',
    titleClasses: 'text-sm font-medium text-gray-900',
    subtitleClasses: 'text-xs text-gray-700',
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
    createModal: null,
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
  },
  'role-template': {
    icon: UserGroupIcon,
    createModal: null,
    createText: 'Create New Role',
    createSubtext: 'Add a new role template',
    titleField: 'name',
    subtitleField: 'context',
    keyField: 'id',
    selectedClasses: 'p-3 bg-purple-50 border border-purple-200 rounded-lg',
    iconClasses: 'w-8 h-8 bg-purple-100 rounded-full flex items-center justify-center',
    titleClasses: 'text-sm font-medium text-purple-900',
    subtitleClasses: 'text-xs text-purple-700',
    getBadges: (item) => {
      const badges = []
      if (item.context) {
        badges.push({
          text: item.context,
          classes: 'bg-purple-100 text-purple-800'
        })
      }
      if (item.is_system) {
        badges.push({
          text: 'System Role',
          classes: 'bg-gray-100 text-gray-800'
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

const dropdownPosition = computed(() => {
  return dropupMode.value ? 'bottom-full mb-1' : 'top-full mt-1'
})

const showRecentHeader = computed(() => {
  return displayItems.value.length > 0 && !shouldUseCustomData.value && !searchTerm.value
})

// Selected item management
const selectedItem = ref(null)

// Initialize selected item from modelValue
watch(() => props.modelValue, (newValue) => {
  if (newValue && typeof newValue === 'object') {
    selectedItem.value = newValue
  } else if (newValue) {
    // Find item in current data
    const found = displayItems.value.find(item => getItemKey(item) == newValue)
    if (found) {
      selectedItem.value = found
    } else if (activeItemData.value && getItemKey(activeItemData.value) == newValue) {
      selectedItem.value = activeItemData.value
    }
  } else {
    selectedItem.value = null
  }
}, { immediate: true })

// Methods
const getItemKey = (item) => {
  return item[config.value.keyField]
}

const getItemTitle = (item) => {
  if (props.type === 'ticket') {
    return `#${item.ticket_number} - ${item[config.value.titleField]}`
  }
  if (props.type === 'billing-rate') {
    let title = item[config.value.titleField] || ''
    if (item.is_timer_specific) {
      title += ' (Original)'
    }
    return title
  }
  return item[config.value.titleField] || ''
}

const getItemSubtitle = (item) => {
  if (props.type === 'billing-rate') {
    const rate = `$${item.rate}/hr`
    if (item.description) {
      return `${item.description} • ${rate}`
    }
    return rate
  }
  
  const subtitleField = config.value.subtitleField
  if (subtitleField.includes('.')) {
    const parts = subtitleField.split('.')
    let value = item
    for (const part of parts) {
      value = value?.[part]
      if (!value) break
    }
    return value || ''
  }
  
  return item[subtitleField] || ''
}

const getItemBadges = (item) => {
  return config.value.getBadges ? config.value.getBadges(item) : []
}

const selectItem = (item) => {
  selectedItem.value = item
  const itemKey = getItemKey(item)
  emit('update:modelValue', itemKey)
  emit('item-selected', item)
  
  // Update recent items
  selectorUtils.updateRecentItems(props.type, item)
  
  showDropdown.value = false
  highlightedIndex.value = -1
  actualSearchTerm.value = ''
  searchTerm.value = ''
}

const clearSelection = () => {
  if (!props.clearable) return
  
  selectedItem.value = null
  emit('update:modelValue', null)
  emit('item-selected', null)
  
  actualSearchTerm.value = ''
  searchTerm.value = ''
  showDropdown.value = false
}

const handleFocus = () => {
  showDropdown.value = true
  highlightedIndex.value = -1
}

const handleBlur = (event) => {
  // Delay hiding dropdown to allow clicks on dropdown items and prevent closing during search
  setTimeout(() => {
    // Don't close dropdown if actively searching, fetching, or if input still has focus
    if (isSearching.value || isFetching.value || document.activeElement === inputRef.value) {
      return
    }
    
    if (!event.relatedTarget?.closest('.absolute')) {
      showDropdown.value = false
      highlightedIndex.value = -1
    }
  }, 200) // Slightly longer delay
}

const handleKeydown = (event) => {
  if (!showDropdown.value) {
    if (['ArrowDown', 'ArrowUp', 'Enter'].includes(event.key)) {
      showDropdown.value = true
      return
    }
  }

  switch (event.key) {
    case 'ArrowDown':
      event.preventDefault()
      highlightedIndex.value = Math.min(highlightedIndex.value + 1, displayItems.value.length - 1)
      break
    case 'ArrowUp':
      event.preventDefault()
      highlightedIndex.value = Math.max(highlightedIndex.value - 1, -1)
      break
    case 'Enter':
      event.preventDefault()
      if (highlightedIndex.value >= 0 && displayItems.value[highlightedIndex.value]) {
        selectItem(displayItems.value[highlightedIndex.value])
      }
      break
    case 'Escape':
      showDropdown.value = false
      highlightedIndex.value = -1
      break
  }
}

// No need for this watcher since we're using v-model directly

// Create modal handling
const openCreateModal = () => {
  showCreateModal.value = true
  showDropdown.value = false
}

const closeCreateModal = () => {
  showCreateModal.value = false
}

const handleItemCreated = (item) => {
  closeCreateModal()
  if (item) {
    selectItem(item)
  }
  emit('item-created', item)
}

// Update filter set reactively
watch(() => props.filterSet, (newFilters) => {
  filterSetRef.value = newFilters
}, { deep: true })

// Update agent type reactively
watch(() => props.agentType, (newType) => {
  agentTypeRef.value = newType
})

// Update sort parameters reactively
watch(() => props.sortField, (newField) => {
  sortFieldRef.value = newField
})

watch(() => props.sortDirection, (newDirection) => {
  sortDirectionRef.value = newDirection
})

// Cleanup
onUnmounted(() => {
  // Any cleanup if needed
})
</script>