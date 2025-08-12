<template>
  <div class="space-y-8">
    <!-- Ticket Configuration Header -->
    <div>
      <h2 class="text-2xl font-semibold text-gray-900">Ticket Configuration</h2>
      <p class="text-gray-600 mt-2">Manage ticket statuses, categories, and workflow settings.</p>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex justify-center py-8">
      <svg class="animate-spin h-8 w-8 text-indigo-600" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
      </svg>
    </div>

    <template v-else>
      <!-- Ticket Statuses -->
      <div class="bg-white border border-gray-200 rounded-lg p-6">
        <div class="flex items-center justify-between mb-6">
          <h3 class="text-lg font-medium text-gray-900">Ticket Statuses</h3>
          <div class="flex items-center space-x-2">
            <button
              @click="openStatusManager"
              class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-indigo-700 bg-indigo-100 hover:bg-indigo-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
            >
              <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
              </svg>
              Add Status
            </button>
            <button
              @click="openWorkflowManager"
              class="inline-flex items-center px-3 py-2 border border-gray-300 text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
            >
              <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l4-4 4 4m0 6l-4 4-4-4" />
              </svg>
              Workflow Rules
            </button>
          </div>
        </div>
        
        <div v-if="statuses && statuses.length > 0">
          <draggable 
            v-model="statusList"
            @change="handleStatusReorder"
            item-key="id"
            class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4"
            ghost-class="opacity-50"
            chosen-class="scale-105"
          >
            <template #item="{ element: status }">
              <div 
                class="group flex items-center p-3 border rounded-lg cursor-move hover:shadow-md transition-all"
                :class="status.is_default ? 'border-indigo-200 bg-indigo-50' : 'border-gray-200 bg-gray-50 hover:border-gray-300'"
                @dblclick="editStatus(status)"
              >
                <div class="flex items-center mr-2 text-gray-400">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16" />
                  </svg>
                </div>
                <div 
                  class="w-3 h-3 rounded-full mr-3"
                  :style="{ backgroundColor: status.color }"
                ></div>
                <div class="flex-1">
                  <div class="text-sm font-medium text-gray-900">{{ status.name }}</div>
                  <div class="text-xs text-gray-500 space-x-2">
                    <span>{{ status.is_closed ? 'Closed' : 'Open' }}</span>
                    <span v-if="status.is_default" class="text-indigo-600">(Default)</span>
                    <span v-if="status.order_index !== null" class="text-gray-400">Order: {{ status.order_index }}</span>
                  </div>
                </div>
                <div class="opacity-0 group-hover:opacity-100 transition-opacity">
                  <button @click.stop="editStatus(status)" class="p-1 text-gray-400 hover:text-blue-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                    </svg>
                  </button>
                </div>
              </div>
            </template>
          </draggable>
          <p class="text-xs text-gray-500 mt-2">
            💡 Drag to reorder, double-click to edit
          </p>
        </div>
        
        <div v-else class="text-sm text-gray-500 text-center py-4">
          No ticket statuses configured.
        </div>
      </div>

      <!-- Ticket Categories -->
      <div class="bg-white border border-gray-200 rounded-lg p-6">
        <div class="flex items-center justify-between mb-6">
          <h3 class="text-lg font-medium text-gray-900">Ticket Categories</h3>
          <button 
            @click="openCategoryManager"
            class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-indigo-700 bg-indigo-100 hover:bg-indigo-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
          >
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
            </svg>
            Manage Categories
          </button>
        </div>
        
        <div v-if="categories && categories.length > 0">
          <draggable 
            v-model="categoryList"
            @change="handleCategoryReorder"
            item-key="id"
            class="grid grid-cols-1 md:grid-cols-2 gap-4"
            ghost-class="opacity-50"
            chosen-class="scale-105"
          >
            <template #item="{ element: category }">
              <div 
                class="group p-4 border rounded-lg cursor-move hover:shadow-md transition-all"
                :class="category.is_default ? 'border-indigo-200 bg-indigo-50' : 'border-gray-200 bg-gray-50 hover:border-gray-300'"
                @dblclick="editCategory(category)"
              >
                <div class="flex items-center mb-2">
                  <div class="flex items-center mr-2 text-gray-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16" />
                    </svg>
                  </div>
                  <div 
                    class="w-3 h-3 rounded-full mr-3"
                    :style="{ backgroundColor: category.color }"
                  ></div>
                  <h4 class="text-sm font-medium text-gray-900 flex-1">{{ category.name }}</h4>
                  <span v-if="category.is_default" class="ml-2 text-xs text-indigo-600">(Default)</span>
                  <button @click.stop="editCategory(category)" class="opacity-0 group-hover:opacity-100 transition-opacity p-1 text-gray-400 hover:text-blue-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                    </svg>
                  </button>
                </div>
                
                <div class="text-xs text-gray-600 space-y-1">
                  <div v-if="category.sla_hours">SLA: {{ category.sla_hours }} hours</div>
                  <div v-if="category.default_estimated_hours">Est: {{ category.default_estimated_hours }}h</div>
                  <div v-if="category.requires_approval" class="text-yellow-600">Requires Approval</div>
                </div>
              </div>
            </template>
          </draggable>
          <p class="text-xs text-gray-500 mt-2">
            💡 Drag to reorder, double-click to edit
          </p>
        </div>
        
        <div v-else class="text-sm text-gray-500 text-center py-4">
          No ticket categories configured.
        </div>
      </div>

      <!-- Ticket Priorities -->
      <div class="bg-white border border-gray-200 rounded-lg p-6">
        <div class="flex items-center justify-between mb-6">
          <h3 class="text-lg font-medium text-gray-900">Ticket Priorities</h3>
          <button 
            @click="openPriorityManager"
            class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-indigo-700 bg-indigo-100 hover:bg-indigo-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
          >
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
            </svg>
            Manage Priorities
          </button>
        </div>
        
        <div v-if="priorities && priorities.length > 0">
          <draggable 
            v-model="priorityList"
            @change="handlePriorityReorder"
            item-key="id"
            class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4"
            ghost-class="opacity-50"
            chosen-class="scale-105"
          >
            <template #item="{ element: priority }">
              <div 
                class="group flex items-center p-3 border rounded-lg cursor-move hover:shadow-md transition-all"
                :class="priority.is_default ? 'border-indigo-200 bg-indigo-50' : 'border-gray-200 bg-gray-50 hover:border-gray-300'"
                @dblclick="editPriority(priority)"
              >
                <div class="flex items-center mr-2 text-gray-400">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16" />
                  </svg>
                </div>
                <div 
                  class="w-3 h-3 rounded-full mr-3"
                  :style="{ backgroundColor: priority.color }"
                ></div>
                <div class="flex-1">
                  <div class="flex items-center">
                    <div class="text-sm font-medium text-gray-900">{{ priority.name }}</div>
                    <span v-if="priority.is_default" class="ml-2 text-xs text-indigo-600">(Default)</span>
                  </div>
                  <div class="text-xs text-gray-500 space-x-2">
                    <span>Level {{ priority.severity_level }}</span>
                    <span v-if="priority.escalation_multiplier !== 1.00">
                      SLA: {{ priority.escalation_multiplier }}x
                    </span>
                  </div>
                </div>
                <div class="opacity-0 group-hover:opacity-100 transition-opacity">
                  <button @click.stop="editPriority(priority)" class="p-1 text-gray-400 hover:text-blue-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                    </svg>
                  </button>
                </div>
              </div>
            </template>
          </draggable>
          <p class="text-xs text-gray-500 mt-2">
            💡 Drag to reorder, double-click to edit
          </p>
        </div>
        
        <div v-else class="text-sm text-gray-500 text-center py-4">
          No ticket priorities configured.
        </div>
      </div>

      <!-- Workflow Configuration -->
      <div v-if="workflowTransitions" class="bg-white border border-gray-200 rounded-lg p-6">
        <div class="flex items-center justify-between mb-6">
          <h3 class="text-lg font-medium text-gray-900">Workflow Transitions</h3>
          <button
            @click="openWorkflowEditor"
            class="inline-flex items-center px-3 py-2 border border-gray-300 text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
          >
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
            </svg>
            Edit Workflow
          </button>
        </div>
        
        <!-- Workflow Visualization -->
        <div class="space-y-4">
          <div 
            v-for="(transitions, fromStatus) in workflowTransitions" 
            :key="fromStatus"
            class="group p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors cursor-pointer"
            @click="editTransitions(fromStatus, transitions)"
          >
            <div class="flex items-center justify-between mb-3">
              <div class="flex items-center">
                <div 
                  class="w-3 h-3 rounded-full mr-3"
                  :style="{ backgroundColor: getStatusColor(fromStatus) }"
                ></div>
                <div class="text-sm font-medium text-gray-900">
                  {{ formatStatusName(fromStatus) }}
                </div>
              </div>
              <div class="opacity-0 group-hover:opacity-100 transition-opacity">
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                </svg>
              </div>
            </div>
            
            <div v-if="transitions.length > 0" class="flex items-center space-x-2">
              <span class="text-xs text-gray-500">Can transition to:</span>
              <div class="flex flex-wrap gap-2">
                <span 
                  v-for="toStatus in transitions" 
                  :key="toStatus"
                  class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                  :style="{ 
                    backgroundColor: getStatusColor(toStatus) + '20',
                    color: getStatusColor(toStatus)
                  }"
                >
                  <div 
                    class="w-2 h-2 rounded-full mr-1.5"
                    :style="{ backgroundColor: getStatusColor(toStatus) }"
                  ></div>
                  {{ formatStatusName(toStatus) }}
                </span>
              </div>
            </div>
            <div v-else class="text-xs text-gray-500 italic">
              No transitions allowed (final state)
            </div>
          </div>
        </div>
      </div>

      <!-- Refresh Button -->
      <div class="flex justify-end">
        <button
          type="button"
          @click="$emit('refresh')"
          class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
        >
          <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
          </svg>
          Refresh Configuration
        </button>
      </div>
    </template>

    <!-- Modals -->
    <StatusManagerModal
      :show="showStatusManager"
      :editing-status="editingStatus"
      @close="showStatusManager = false"
      @saved="handleStatusSaved"
    />

    <CategoryManagerModal
      :show="showCategoryManager"
      :editing-category="editingCategory"
      @close="showCategoryManager = false"
      @saved="handleCategorySaved"
    />

    <PriorityManagerModal
      :show="showPriorityManager"
      :editing-priority="editingPriority"
      @close="showPriorityManager = false"
      @saved="handlePrioritySaved"
    />

    <WorkflowEditorModal
      :show="showWorkflowEditor"
      :statuses="statuses"
      :initial-transitions="workflowTransitions"
      @close="showWorkflowEditor = false"
      @saved="handleWorkflowSaved"
    />
  </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import draggable from 'vuedraggable'
import StatusManagerModal from '@/Components/Modals/StatusManagerModal.vue'
import CategoryManagerModal from '@/Components/Modals/CategoryManagerModal.vue'
import PriorityManagerModal from '@/Components/Modals/PriorityManagerModal.vue'
import WorkflowEditorModal from '@/Components/Modals/WorkflowEditorModal.vue'

const props = defineProps({
  config: {
    type: Object,
    default: () => ({})
  },
  loading: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['refresh'])

// State for modals/dialogs
const showStatusManager = ref(false)
const showCategoryManager = ref(false)
const showPriorityManager = ref(false)
const showWorkflowEditor = ref(false)
const editingStatus = ref(null)
const editingCategory = ref(null)
const editingPriority = ref(null)
const editingTransitions = ref(null)

const statuses = computed(() => props.config.statuses || [])
const categories = computed(() => props.config.categories || [])
const priorities = computed(() => props.config.priorities || [])
const workflowTransitions = computed(() => props.config.workflow_transitions || {})

// Reactive lists for drag-drop
const statusList = ref([])
const categoryList = ref([])
const priorityList = ref([])

// Watch for changes in props and update reactive lists
watch(() => props.config.statuses, (newStatuses) => {
  statusList.value = [...(newStatuses || [])]
}, { immediate: true, deep: true })

watch(() => props.config.categories, (newCategories) => {
  categoryList.value = [...(newCategories || [])]
}, { immediate: true, deep: true })

watch(() => props.config.priorities, (newPriorities) => {
  priorityList.value = [...(newPriorities || [])]
}, { immediate: true, deep: true })

// Helper methods
const formatStatusName = (status) => {
  const statusObj = statuses.value.find(s => s.key === status)
  return statusObj?.name || status.replace('_', ' ')
}

const getStatusColor = (status) => {
  const statusObj = statuses.value.find(s => s.key === status)
  return statusObj?.color || '#6B7280'
}

// Event handlers
const openStatusManager = () => {
  showStatusManager.value = true
  editingStatus.value = null
}

const openCategoryManager = () => {
  showCategoryManager.value = true
  editingCategory.value = null
}

const openPriorityManager = () => {
  showPriorityManager.value = true
  editingPriority.value = null
}

const openWorkflowEditor = () => {
  showWorkflowEditor.value = true
}

const editStatus = (status) => {
  editingStatus.value = status
  showStatusManager.value = true
}

const editCategory = (category) => {
  editingCategory.value = category
  showCategoryManager.value = true
}

const editPriority = (priority) => {
  editingPriority.value = priority
  showPriorityManager.value = true
}

const editTransitions = (fromStatus, transitions) => {
  editingTransitions.value = { fromStatus, transitions }
  showWorkflowEditor.value = true
}

// Drag-drop reorder handlers
const handleStatusReorder = async (event) => {
  if (event.moved) {
    const reorderedItems = statusList.value.map((item, index) => ({
      id: item.id,
      sort_order: index
    }))
    
    // Store original order for rollback
    const originalOrder = [...statuses.value]
    
    try {
      const response = await fetch('/api/ticket-statuses/reorder', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.head.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ statuses: reorderedItems })
      })
      
      if (!response.ok) {
        throw new Error('Server returned error')
      }
      
      // Update succeeded - no need for full page refresh
      // The optimistic update is already applied via draggable
    } catch (error) {
      console.error('Failed to reorder statuses:', error)
      // Revert on error by restoring original order
      statusList.value = [...originalOrder]
    }
  }
}

const handleCategoryReorder = async (event) => {
  if (event.moved) {
    const reorderedItems = categoryList.value.map((item, index) => ({
      id: item.id,
      sort_order: index
    }))
    
    // Store original order for rollback
    const originalOrder = [...categories.value]
    
    try {
      const response = await fetch('/api/ticket-categories/reorder', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.head.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ categories: reorderedItems })
      })
      
      if (!response.ok) {
        throw new Error('Server returned error')
      }
      
      // Update succeeded - no need for full page refresh
      // The optimistic update is already applied via draggable
    } catch (error) {
      console.error('Failed to reorder categories:', error)
      // Revert on error by restoring original order
      categoryList.value = [...originalOrder]
    }
  }
}

const handlePriorityReorder = async (event) => {
  if (event.moved) {
    const reorderedItems = priorityList.value.map((item, index) => ({
      id: item.id,
      sort_order: index
    }))
    
    // Store original order for rollback
    const originalOrder = [...priorities.value]
    
    try {
      const response = await fetch('/api/ticket-priorities/reorder', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.head.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ priorities: reorderedItems })
      })
      
      if (!response.ok) {
        throw new Error('Server returned error')
      }
      
      // Update succeeded - no need for full page refresh
      // The optimistic update is already applied via draggable
    } catch (error) {
      console.error('Failed to reorder priorities:', error)
      // Revert on error by restoring original order
      priorityList.value = [...originalOrder]
    }
  }
}

// Modal save handlers
const handleStatusSaved = (savedStatus) => {
  emit('refresh')
}

const handleCategorySaved = (savedCategory) => {
  emit('refresh')
}

const handlePrioritySaved = (savedPriority) => {
  emit('refresh')
}

const handleWorkflowSaved = (savedWorkflow) => {
  emit('refresh')
}

// API calls for status management
const createStatus = async (statusData) => {
  try {
    const response = await fetch('/api/ticket-statuses', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.head.querySelector('meta[name="csrf-token"]').content
      },
      body: JSON.stringify(statusData)
    })
    
    if (response.ok) {
      emit('refresh')
      showStatusManager.value = false
    }
  } catch (error) {
    console.error('Failed to create status:', error)
  }
}

const updateStatus = async (statusId, statusData) => {
  try {
    const response = await fetch(`/api/ticket-statuses/${statusId}`, {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.head.querySelector('meta[name="csrf-token"]').content
      },
      body: JSON.stringify(statusData)
    })
    
    if (response.ok) {
      emit('refresh')
      showStatusManager.value = false
    }
  } catch (error) {
    console.error('Failed to update status:', error)
  }
}

const updateWorkflowTransitions = async (transitionsData) => {
  try {
    const response = await fetch('/api/settings/workflow-transitions', {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.head.querySelector('meta[name="csrf-token"]').content
      },
      body: JSON.stringify(transitionsData)
    })
    
    if (response.ok) {
      emit('refresh')
      showWorkflowEditor.value = false
    }
  } catch (error) {
    console.error('Failed to update workflow transitions:', error)
  }
}
</script>