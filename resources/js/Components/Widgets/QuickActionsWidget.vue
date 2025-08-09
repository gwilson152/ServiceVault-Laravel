<template>
  <div class="quick-actions-widget">
    <div v-if="actions.length > 0" class="action-grid">
      <button
        v-for="action in actions"
        :key="action.name"
        @click="handleAction(action)"
        class="action-button group flex items-center justify-center p-3 border-2 border-dashed border-gray-300 rounded-lg hover:border-gray-400 hover:bg-gray-50 transition-all duration-200"
      >
        <div class="text-center">
          <div class="mx-auto flex items-center justify-center h-8 w-8 rounded-full bg-gray-100 group-hover:bg-gray-200 mb-2">
            <component :is="getActionIcon(action.icon)" class="h-4 w-4 text-gray-600" />
          </div>
          <p class="text-sm font-medium text-gray-900 group-hover:text-gray-700">
            {{ action.name }}
          </p>
        </div>
      </button>
      
      <!-- Add custom action button -->
      <button 
        @click="showCustomActions = true"
        class="action-button group flex items-center justify-center p-3 border-2 border-dashed border-indigo-300 rounded-lg hover:border-indigo-400 hover:bg-indigo-50 transition-all duration-200"
      >
        <div class="text-center">
          <div class="mx-auto flex items-center justify-center h-8 w-8 rounded-full bg-indigo-100 group-hover:bg-indigo-200 mb-2">
            <PlusIcon class="h-4 w-4 text-indigo-600" />
          </div>
          <p class="text-sm font-medium text-indigo-900">
            More
          </p>
        </div>
      </button>
    </div>

    <div v-else class="empty-state text-center py-4">
      <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-gray-100">
        <CommandIcon class="h-6 w-6 text-gray-400" />
      </div>
      <p class="mt-2 text-sm text-gray-500">No quick actions available</p>
    </div>

    <!-- Custom Actions Modal -->
    <div v-if="showCustomActions" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" @click="closeCustomActions">
      <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4" @click.stop>
        <h3 class="text-lg font-medium text-gray-900 mb-4">Quick Actions</h3>
        
        <div class="space-y-2">
          <button
            v-for="commonAction in commonActions"
            :key="commonAction.name"
            @click="handleAction(commonAction)"
            class="w-full flex items-center px-4 py-3 text-left hover:bg-gray-50 rounded-lg transition-colors"
          >
            <component :is="getActionIcon(commonAction.icon)" class="h-5 w-5 text-gray-400 mr-3" />
            <div>
              <p class="font-medium text-gray-900">{{ commonAction.name }}</p>
              <p class="text-sm text-gray-500">{{ commonAction.description }}</p>
            </div>
          </button>
        </div>
        
        <div class="mt-6 flex justify-end">
          <button
            @click="closeCustomActions"
            class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 transition-colors"
          >
            Close
          </button>
        </div>
      </div>
    </div>
  </div>
</template>


<script setup>
import { computed, ref } from 'vue'
import { router } from '@inertiajs/vue3'

// Simple icon components (you can replace with your preferred icon library)
const PlayIcon = () => h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
  h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M14.828 14.828a4 4 0 01-5.656 0M9 10h6m-6 4h6m-6-8h6m-6 12h6' })
])

const PlusIcon = () => h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
  h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M12 4v16m8-8H4' })
])

const UserPlusIcon = () => h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
  h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z' })
])

const DocumentPlusIcon = () => h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
  h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z' })
])

const CommandIcon = () => h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
  h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z' })
])

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
const showCustomActions = ref(false)

// Computed
const actions = computed(() => {
  return props.widgetData?.actions || []
})

const commonActions = computed(() => [
  {
    name: 'Start Timer',
    description: 'Begin tracking time for a task',
    action: 'start-timer',
    icon: 'play'
  },
  {
    name: 'Create Ticket',
    description: 'Submit a new service request',
    route: 'tickets.create',
    icon: 'plus'
  },
  {
    name: 'Invite User',
    description: 'Send invitation to new team member',
    route: 'users.invite',
    icon: 'user-plus'
  },
  {
    name: 'View Reports',
    description: 'Access analytics and reporting',
    route: 'reports.index',
    icon: 'document-plus'
  }
])

// Methods
const getActionIcon = (iconName) => {
  const icons = {
    'play': PlayIcon,
    'plus': PlusIcon,
    'user-plus': UserPlusIcon,
    'document-plus': DocumentPlusIcon,
    'command': CommandIcon
  }
  return icons[iconName] || CommandIcon
}

const handleAction = (action) => {
  if (action.route) {
    // Navigate to a specific route
    try {
      router.visit(route(action.route))
    } catch (e) {
      console.warn(`Route '${action.route}' not found`)
    }
  } else if (action.action) {
    // Handle custom actions
    handleCustomAction(action.action)
  } else if (action.url) {
    // Navigate to external URL
    window.open(action.url, '_blank')
  }
  
  closeCustomActions()
}

const handleCustomAction = (actionType) => {
  switch (actionType) {
    case 'start-timer':
      // Emit an event or call a timer service
      console.log('Starting timer...')
      // You could emit an event to parent components or call a timer API
      break
    case 'quick-note':
      // Open a quick note modal
      console.log('Opening quick note...')
      break
    default:
      console.log(`Unknown action: ${actionType}`)
  }
}

const closeCustomActions = () => {
  showCustomActions.value = false
}

// Helper function to create virtual nodes (for icon components)
const h = (tag, props, children) => {
  return {
    render() {
      return tag === 'svg' 
        ? createVNode(tag, props, children)
        : createVNode(tag, props, children)
    }
  }
}

// Import createVNode for virtual DOM creation
import { createVNode } from 'vue'
</script>

<style scoped>
.action-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
  gap: 0.5rem;
}

.action-button {
  min-height: 80px;
  transition: all 0.2s ease-in-out;
}

.action-button:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.empty-state {
  padding: 2rem 0;
}
</style>