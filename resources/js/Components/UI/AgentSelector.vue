<template>
  <div class="relative">
    <label v-if="label" :for="inputId" class="block text-sm font-medium text-gray-700 mb-2">
      {{ label }}
      <span v-if="required" class="text-red-500">*</span>
    </label>
    
    <!-- Search Input (only show when no agent is selected) -->
    <div v-if="!selectedAgent" class="relative">
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
        <div v-if="isLoading" class="inline-block animate-spin rounded-full h-4 w-4 border-b-2 border-blue-600"></div>
        <svg v-else class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
        </svg>
      </div>
    </div>
    
    <!-- Selected Agent Display -->
    <div v-if="selectedAgent" class="p-3 bg-blue-50 border border-blue-200 rounded-lg">
      <div class="flex items-center justify-between">
        <div class="flex items-center">
          <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center mr-3">
            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
            </svg>
          </div>
          <div>
            <p class="text-sm font-medium text-blue-900">{{ selectedAgent.name }}</p>
            <p class="text-xs text-blue-700">{{ selectedAgent.email }}</p>
            <div class="flex items-center space-x-2 mt-1">
              <!-- Agent Type Badge -->
              <span :class="getAgentTypeBadgeClasses(selectedAgent)" class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium">
                {{ getAgentTypeLabel(selectedAgent) }}
              </span>
              <!-- Account Context -->
              <span v-if="selectedAgent.account?.name" class="text-xs text-blue-600">
                {{ selectedAgent.account.name }}
              </span>
            </div>
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
      v-if="showDropdown && !selectedAgent"
      ref="dropdown"
      class="absolute z-50 w-full bg-white border border-gray-300 rounded-lg shadow-lg max-h-60 overflow-y-auto"
      :class="dropdownPosition"
    >
      <div v-if="isLoading" class="p-4 text-center text-gray-500">
        <div class="inline-block animate-spin rounded-full h-4 w-4 border-b-2 border-blue-600"></div>
        <span class="ml-2">Loading agents...</span>
      </div>
      
      <div v-else>
        <!-- Available Agents -->
        <div v-if="filteredAgents.length > 0">
          <div
            v-for="agent in filteredAgents"
            :key="agent.id"
            @mousedown.prevent="selectAgent(agent)"
            class="px-4 py-3 hover:bg-gray-100 cursor-pointer border-b border-gray-100 last:border-b-0"
            :class="{ 'bg-blue-50': selectedAgent?.id === agent.id }"
          >
            <div class="flex items-center">
              <div class="w-8 h-8 bg-gray-300 rounded-full flex items-center justify-center mr-3">
                <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
              </div>
              <div class="flex-1">
                <p class="text-sm font-medium text-gray-900">{{ agent.name }}</p>
                <p class="text-xs text-gray-600">{{ agent.email }}</p>
                <div class="flex items-center space-x-2 mt-1">
                  <!-- Agent Type Badge -->
                  <span :class="getAgentTypeBadgeClasses(agent)" class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium">
                    {{ getAgentTypeLabel(agent) }}
                  </span>
                  <!-- Account Context -->
                  <span v-if="agent.account?.name" class="text-xs text-gray-500">
                    {{ agent.account.name }}
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>
        
        <!-- No Agents Message -->
        <div v-else class="p-4 text-center text-gray-500">
          {{ searchTerm ? 'No agents found matching your search' : 'No agents available for assignment' }}
          <div class="text-xs text-gray-400 mt-1">
            Only users with agent privileges can be assigned to timers
          </div>
        </div>
      </div>
    </div>
    
    <!-- Error Message -->
    <p v-if="error" class="mt-1 text-sm text-red-600">{{ error }}</p>
  </div>
</template>

<script setup>
import { ref, computed, nextTick, watch } from 'vue'

// Props
const props = defineProps({
  modelValue: {
    type: [String, Object],
    default: null
  },
  agents: {
    type: Array,
    default: () => []
  },
  isLoading: {
    type: Boolean,
    default: false
  },
  label: {
    type: String,
    default: 'Assign Service Agent'
  },
  placeholder: {
    type: String,
    default: 'Search and select an agent...'
  },
  required: {
    type: Boolean,
    default: false
  },
  error: {
    type: String,
    default: null
  },
  noAgentsMessage: {
    type: String,
    default: null
  },
  reopenOnClear: {
    type: Boolean,
    default: true
  },
  agentType: {
    type: String,
    default: 'timer', // 'timer', 'ticket', 'time', 'billing', or null for any
    validator: value => [null, 'timer', 'ticket', 'time', 'billing'].includes(value)
  }
})

// Emits
const emit = defineEmits(['update:modelValue', 'agent-selected'])

// State
const inputId = `agent-selector-${Math.random().toString(36).substr(2, 9)}`
const searchTerm = ref('')
const showDropdown = ref(false)
const selectedAgent = ref(null)
const dropdown = ref(null)
const dropupMode = ref(false)

// Computed
const filteredAgents = computed(() => {
  // Filter agents based on search term
  let agents = props.agents
  
  if (searchTerm.value) {
    const term = searchTerm.value.toLowerCase()
    agents = agents.filter(agent => 
      agent.name.toLowerCase().includes(term) ||
      agent.email.toLowerCase().includes(term) ||
      (agent.account?.name && agent.account.name.toLowerCase().includes(term))
    )
  }
  
  // Additional filtering to ensure only valid agents
  return agents.filter(agent => isValidAgent(agent))
})

const dropdownPosition = computed(() => {
  return dropupMode.value ? 'bottom-full mb-1' : 'top-full mt-1'
})

// Methods
const isValidAgent = (user) => {
  // Enhanced multi-layered agent determination with feature-specific permissions
  
  // Primary: Explicit agent designation
  if (user.user_type === 'agent') return true
  
  // Secondary: Feature-specific agent permissions
  const requiredPermission = getRequiredAgentPermission()
  if (requiredPermission && user.permissions?.includes(requiredPermission)) return true
  
  // Tertiary: Internal account users with relevant permissions (fallback)
  if (user.account?.account_type === 'internal') {
    const fallbackPermissions = getFallbackPermissions()
    if (fallbackPermissions.some(permission => user.permissions?.includes(permission))) {
      return true
    }
  }
  
  return false
}

const getRequiredAgentPermission = () => {
  switch (props.agentType) {
    case 'timer': return 'timers.act_as_agent'
    case 'ticket': return 'tickets.act_as_agent'
    case 'time': return 'time.act_as_agent'
    case 'billing': return 'billing.act_as_agent'
    default: return null // Any agent type
  }
}

const getFallbackPermissions = () => {
  switch (props.agentType) {
    case 'timer': return ['timers.write', 'timers.manage']
    case 'ticket': return ['tickets.assign', 'tickets.manage']
    case 'time': return ['time.track', 'time.manage']
    case 'billing': return ['billing.manage', 'billing.admin']
    default: return ['timers.write', 'tickets.assign', 'time.track', 'billing.manage']
  }
}

const getAgentTypeLabel = (agent) => {
  if (agent.is_super_admin) return 'Super Admin'
  if (agent.user_type === 'agent' && agent.account?.account_type === 'internal') return 'Internal Agent'
  if (agent.user_type === 'agent') return 'Agent'
  
  // Check for specific agent designations
  const agentDesignations = []
  if (agent.permissions?.includes('timers.act_as_agent')) agentDesignations.push('Timer')
  if (agent.permissions?.includes('tickets.act_as_agent')) agentDesignations.push('Ticket')
  if (agent.permissions?.includes('time.act_as_agent')) agentDesignations.push('Time')
  if (agent.permissions?.includes('billing.act_as_agent')) agentDesignations.push('Billing')
  
  if (agentDesignations.length > 0) {
    return `${agentDesignations.join('/')} Agent`
  }
  
  if (agent.account?.account_type === 'internal') return 'Internal User'
  return 'Service Provider'
}

const getAgentTypeBadgeClasses = (agent) => {
  if (agent.is_super_admin) {
    return 'bg-purple-100 text-purple-700'
  }
  if (agent.user_type === 'agent' && agent.account?.account_type === 'internal') {
    return 'bg-green-100 text-green-700'
  }
  if (agent.user_type === 'agent') {
    return 'bg-blue-100 text-blue-700'
  }
  
  // Check for specific agent designations
  const hasTimerAgent = agent.permissions?.includes('timers.act_as_agent')
  const hasTicketAgent = agent.permissions?.includes('tickets.act_as_agent')
  const hasTimeAgent = agent.permissions?.includes('time.act_as_agent')
  const hasBillingAgent = agent.permissions?.includes('billing.act_as_agent')
  
  if (hasTimerAgent || hasTicketAgent || hasTimeAgent || hasBillingAgent) {
    // Use different colors based on the primary agent type or combination
    if (hasTicketAgent && hasTimeAgent && hasTimerAgent && hasBillingAgent) {
      return 'bg-indigo-100 text-indigo-700' // Full service agent
    }
    if (hasTicketAgent) {
      return 'bg-orange-100 text-orange-700' // Ticket agent
    }
    if (hasTimeAgent) {
      return 'bg-yellow-100 text-yellow-700' // Time agent
    }
    if (hasBillingAgent) {
      return 'bg-emerald-100 text-emerald-700' // Billing agent
    }
    if (hasTimerAgent) {
      return 'bg-cyan-100 text-cyan-700' // Timer agent
    }
  }
  
  return 'bg-gray-100 text-gray-700'
}

const selectAgent = (agent) => {
  selectedAgent.value = agent
  searchTerm.value = ''
  showDropdown.value = false
  
  emit('update:modelValue', agent.id)
  emit('agent-selected', agent)
}

const clearSelection = () => {
  selectedAgent.value = null
  searchTerm.value = ''
  emit('update:modelValue', null)
  emit('agent-selected', null)
  
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

// Initialize selected agent from modelValue
const initializeSelectedAgent = () => {
  if (props.modelValue && props.agents.length > 0) {
    const agent = props.agents.find(a => a.id === props.modelValue)
    if (agent && isValidAgent(agent)) {
      selectedAgent.value = agent
    }
  } else if (!props.modelValue) {
    selectedAgent.value = null
  }
}

// Watchers
watch(() => props.modelValue, initializeSelectedAgent)
watch(() => props.agents, initializeSelectedAgent)
</script>