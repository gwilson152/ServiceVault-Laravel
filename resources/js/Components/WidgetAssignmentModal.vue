<template>
  <TransitionRoot :show="open" as="template">
    <Dialog as="div" class="relative z-50" @close="close">
      <TransitionChild
        as="template"
        enter="ease-out duration-300"
        enter-from="opacity-0"
        enter-to="opacity-100"
        leave="ease-in duration-200"
        leave-from="opacity-100"
        leave-to="opacity-0"
      >
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" />
      </TransitionChild>

      <div class="fixed inset-0 z-10 overflow-y-auto">
        <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
          <TransitionChild
            as="template"
            enter="ease-out duration-300"
            enter-from="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            enter-to="opacity-100 translate-y-0 sm:scale-100"
            leave="ease-in duration-200"
            leave-from="opacity-100 translate-y-0 sm:scale-100"
            leave-to="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
          >
            <DialogPanel class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all sm:my-8 w-full max-w-7xl">
              <!-- Header -->
              <div class="bg-white px-4 py-5 border-b border-gray-200 sm:px-6">
                <div class="flex items-center justify-between">
                  <div class="flex items-center space-x-3">
                    <div class="flex-shrink-0">
                      <div class="w-8 h-8 bg-purple-100 rounded-lg flex items-center justify-center">
                        <Squares2X2Icon class="w-5 h-5 text-purple-600" />
                      </div>
                    </div>
                    <div>
                      <DialogTitle as="h3" class="text-lg leading-6 font-medium text-gray-900">
                        Widget Assignment
                      </DialogTitle>
                      <p v-if="roleTemplate" class="text-sm text-gray-500">
                        Configure widgets and layout for {{ roleTemplate.name }}
                      </p>
                    </div>
                  </div>
                  <div class="flex items-center space-x-3">
                    <!-- View Mode Toggle -->
                    <div class="flex rounded-lg border border-gray-200 bg-gray-50">
                      <button
                        v-for="mode in viewModes"
                        :key="mode.id"
                        @click="activeMode = mode.id"
                        :class="[
                          activeMode === mode.id
                            ? 'bg-white text-purple-600 border-purple-200'
                            : 'text-gray-500 hover:text-gray-700',
                          'px-3 py-1.5 text-sm font-medium border-r border-gray-200 first:rounded-l-lg last:rounded-r-lg last:border-r-0'
                        ]"
                      >
                        <component :is="mode.icon" class="w-4 h-4 inline mr-1" />
                        {{ mode.label }}
                      </button>
                    </div>
                    
                    <button
                      type="button"
                      class="rounded-md bg-white text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2"
                      @click="close"
                    >
                      <span class="sr-only">Close</span>
                      <XMarkIcon class="h-6 w-6" aria-hidden="true" />
                    </button>
                  </div>
                </div>
              </div>

              <!-- Loading State -->
              <div v-if="loading" class="bg-gray-50 px-4 py-8 sm:px-6">
                <div class="flex items-center justify-center">
                  <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-purple-600"></div>
                  <span class="ml-3 text-gray-600">Loading widget configuration...</span>
                </div>
              </div>

              <!-- Error State -->
              <div v-else-if="error" class="bg-gray-50 px-4 py-8 sm:px-6">
                <div class="rounded-md bg-red-50 p-4">
                  <div class="flex">
                    <ExclamationTriangleIcon class="h-5 w-5 text-red-400" aria-hidden="true" />
                    <div class="ml-3">
                      <h3 class="text-sm font-medium text-red-800">Failed to load widget configuration</h3>
                      <p class="mt-2 text-sm text-red-700">{{ error }}</p>
                      <div class="mt-3">
                        <button
                          @click="loadWidgets"
                          class="text-sm bg-red-100 text-red-800 rounded-md px-2 py-1 hover:bg-red-200"
                        >
                          Try Again
                        </button>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Content -->
              <div v-else-if="allWidgets.length > 0" class="bg-gray-50 max-h-[80vh] overflow-hidden">
                
                <!-- Widget Selection Mode -->
                <div v-show="activeMode === 'select'" class="flex h-full">
                  <!-- Available Widgets (Left Panel) -->
                  <div class="w-1/3 bg-white border-r border-gray-200 flex flex-col">
                    <div class="px-4 py-3 border-b border-gray-200 bg-gray-50">
                      <div class="flex items-center justify-between">
                        <h3 class="text-sm font-semibold text-gray-900">Available Widgets</h3>
                        <span class="text-xs text-gray-500">{{ availableWidgets.length }} widgets</span>
                      </div>
                      <!-- Category Filter -->
                      <div class="mt-2">
                        <select 
                          v-model="selectedCategory" 
                          class="text-xs border-gray-300 rounded-md w-full focus:border-purple-500 focus:ring-purple-500"
                        >
                          <option value="">All Categories</option>
                          <option v-for="category in categories" :key="category" :value="category">
                            {{ formatCategoryName(category) }}
                          </option>
                        </select>
                      </div>
                    </div>
                    <div class="flex-1 overflow-y-auto p-4 space-y-2">
                      <div
                        v-for="widget in filteredAvailableWidgets"
                        :key="`available-${widget.id}`"
                        :draggable="true"
                        @dragstart="startDrag(widget, 'available')"
                        class="group cursor-move bg-white rounded-lg border border-gray-200 p-3 hover:border-purple-300 hover:shadow-sm transition-all duration-150"
                      >
                        <div class="flex items-center justify-between">
                          <div class="flex items-center space-x-2">
                            <component :is="getWidgetIcon(widget.category)" class="w-4 h-4 text-gray-400" />
                            <div>
                              <h4 class="text-sm font-medium text-gray-900">{{ widget.name }}</h4>
                              <p class="text-xs text-gray-500">{{ widget.category }}</p>
                            </div>
                          </div>
                          <PlusIcon class="w-4 h-4 text-gray-400 group-hover:text-purple-500" />
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- Assigned Widgets (Right Panel) -->
                  <div class="w-2/3 flex flex-col">
                    <div class="px-4 py-3 border-b border-gray-200 bg-gray-50">
                      <div class="flex items-center justify-between">
                        <h3 class="text-sm font-semibold text-gray-900">Assigned Widgets</h3>
                        <span class="text-xs text-gray-500">{{ assignedWidgets.length }} assigned</span>
                      </div>
                    </div>
                    
                    <!-- Drop Zone -->
                    <div
                      ref="assignedDropZone"
                      @drop="handleDrop($event, 'assigned')"
                      @dragover.prevent
                      @dragenter.prevent
                      :class="[
                        'flex-1 overflow-y-auto p-4',
                        isDragging ? 'bg-purple-50 border-2 border-dashed border-purple-300' : ''
                      ]"
                    >
                      <div v-if="assignedWidgets.length === 0" class="text-center py-12">
                        <CubeIcon class="w-12 h-12 text-gray-300 mx-auto mb-4" />
                        <h3 class="text-lg font-medium text-gray-900 mb-2">No widgets assigned</h3>
                        <p class="text-gray-500 mb-4">Drag widgets from the left panel to assign them to this role template.</p>
                      </div>
                      
                      <div v-else class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                        <div
                          v-for="(widget, index) in assignedWidgets"
                          :key="`assigned-${widget.id}`"
                          :draggable="true"
                          @dragstart="startDrag(widget, 'assigned', index)"
                          class="group cursor-move bg-white rounded-lg border border-gray-200 p-4 hover:border-purple-300 hover:shadow-sm transition-all duration-150"
                        >
                          <div class="flex items-center justify-between mb-3">
                            <div class="flex items-center space-x-2">
                              <component :is="getWidgetIcon(widget.category)" class="w-5 h-5 text-gray-400" />
                              <div>
                                <h4 class="text-sm font-medium text-gray-900">{{ widget.name }}</h4>
                                <p class="text-xs text-gray-500">{{ widget.category }}</p>
                              </div>
                            </div>
                            <button
                              @click="removeWidget(index)"
                              class="opacity-0 group-hover:opacity-100 text-red-400 hover:text-red-600 transition-opacity"
                            >
                              <XMarkIcon class="w-4 h-4" />
                            </button>
                          </div>
                          
                          <!-- Widget Configuration -->
                          <div class="space-y-2 text-xs">
                            <div class="flex items-center justify-between">
                              <label class="text-gray-600">Enabled by default:</label>
                              <input
                                type="checkbox"
                                v-model="widget.enabled_by_default"
                                @change="updateWidget(index, 'enabled_by_default', widget.enabled_by_default)"
                                class="rounded border-gray-300 text-purple-600 focus:border-purple-500 focus:ring-purple-500"
                              />
                            </div>
                            <div class="flex items-center justify-between">
                              <label class="text-gray-600">Configurable:</label>
                              <input
                                type="checkbox"
                                v-model="widget.configurable"
                                @change="updateWidget(index, 'configurable', widget.configurable)"
                                class="rounded border-gray-300 text-purple-600 focus:border-purple-500 focus:ring-purple-500"
                              />
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Layout Design Mode -->
                <div v-show="activeMode === 'layout'" class="p-6">
                  <div class="bg-white rounded-lg border border-gray-200 min-h-[500px] relative">
                    <div class="px-4 py-3 border-b border-gray-200 bg-gray-50 rounded-t-lg">
                      <div class="flex items-center justify-between">
                        <h3 class="text-sm font-semibold text-gray-900">Dashboard Layout Designer</h3>
                        <div class="flex items-center space-x-2">
                          <span class="text-xs text-gray-500">12-column grid</span>
                          <button
                            @click="resetLayout"
                            class="text-xs text-purple-600 hover:text-purple-800 underline"
                          >
                            Reset Layout
                          </button>
                        </div>
                      </div>
                    </div>
                    
                    <!-- Grid Layout Area -->
                    <div class="p-4 relative bg-gray-50 min-h-[400px] grid-layout-container">
                      <!-- Grid Background -->
                      <div class="absolute inset-4 grid grid-cols-12 gap-2 opacity-10 pointer-events-none">
                        <div v-for="i in 12" :key="`grid-${i}`" class="bg-gray-400 rounded min-h-[40px]"></div>
                      </div>
                      
                      <!-- Layout Items -->
                      <div
                        v-for="(layoutItem, index) in dashboardLayout"
                        :key="`layout-${layoutItem.i}`"
                        :style="getLayoutItemStyle(layoutItem)"
                        @mousedown="startResize(index, $event)"
                        class="absolute bg-white border-2 border-purple-300 rounded-lg p-3 shadow-sm cursor-move layout-item"
                        :class="{ 'border-purple-500 shadow-md': draggedLayoutItem === index }"
                      >
                        <!-- Widget Header -->
                        <div class="flex items-center justify-between mb-2">
                          <div class="flex items-center space-x-2">
                            <component :is="getWidgetIcon(getWidgetById(layoutItem.i)?.category)" class="w-4 h-4 text-gray-400" />
                            <span class="text-xs font-medium text-gray-900">{{ getWidgetById(layoutItem.i)?.name }}</span>
                          </div>
                          <button
                            @click="removeFromLayout(index)"
                            class="text-red-400 hover:text-red-600 opacity-0 hover:opacity-100 transition-opacity"
                          >
                            <XMarkIcon class="w-3 h-3" />
                          </button>
                        </div>
                        
                        <!-- Layout Info -->
                        <div class="text-xs text-gray-500 space-y-1">
                          <div>Size: {{ layoutItem.w }}×{{ layoutItem.h }}</div>
                          <div>Position: ({{ layoutItem.x }}, {{ layoutItem.y }})</div>
                        </div>
                        
                        <!-- Resize Handle -->
                        <div class="absolute bottom-1 right-1 w-3 h-3 bg-purple-500 rounded cursor-se-resize resize-handle"></div>
                      </div>
                      
                      <!-- Add to Layout Hint -->
                      <div v-if="assignedWidgets.length > 0 && dashboardLayout.length === 0" class="absolute inset-0 flex items-center justify-center">
                        <div class="text-center">
                          <CubeIcon class="w-12 h-12 text-gray-300 mx-auto mb-4" />
                          <h3 class="text-lg font-medium text-gray-900 mb-2">Design Your Layout</h3>
                          <p class="text-gray-500">Switch to Widget Selection to assign widgets, then come back here to arrange them.</p>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Footer -->
              <div class="bg-gray-50 px-4 py-3 border-t border-gray-200 sm:px-6">
                <div class="flex items-center justify-between">
                  <div class="flex items-center space-x-4 text-sm text-gray-500">
                    <span>{{ assignedWidgets.length }} widgets assigned</span>
                    <span>•</span>
                    <span>{{ dashboardLayout.length }} widgets in layout</span>
                    <span v-if="hasChanges" class="text-orange-600 font-medium">• Unsaved changes</span>
                  </div>
                  <div class="flex space-x-3">
                    <button
                      type="button"
                      class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500"
                      @click="close"
                    >
                      Cancel
                    </button>
                    <button
                      type="button"
                      :disabled="saving || !hasChanges"
                      @click="saveChanges"
                      class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-purple-600 hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                      <div v-if="saving" class="animate-spin -ml-1 mr-2 h-4 w-4 border-2 border-white border-t-transparent rounded-full"></div>
                      {{ saving ? 'Saving...' : 'Save Changes' }}
                    </button>
                  </div>
                </div>
              </div>
            </DialogPanel>
          </TransitionChild>
        </div>
      </div>
    </Dialog>
  </TransitionRoot>
</template>

<script setup>
import { ref, computed, watch, onMounted, nextTick } from 'vue'
import axios from 'axios'
import { Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot } from '@headlessui/vue'
import {
  XMarkIcon,
  PlusIcon,
  CubeIcon,
  Squares2X2Icon,
  PencilSquareIcon,
  ExclamationTriangleIcon,
  // Widget category icons
  CogIcon,
  ChartBarIcon,
  TicketIcon,
  ClockIcon,
  CurrencyDollarIcon,
  ChatBubbleLeftRightIcon,
  RocketLaunchIcon,
  UserGroupIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
  open: {
    type: Boolean,
    default: false
  },
  roleTemplate: {
    type: Object,
    default: null
  }
})

const emit = defineEmits(['close', 'saved'])

// State
const loading = ref(false)
const saving = ref(false)
const error = ref(null)
const activeMode = ref('select')
const selectedCategory = ref('')
const allWidgets = ref([])
const assignedWidgets = ref([])
const dashboardLayout = ref([])
const isDragging = ref(false)
const draggedItem = ref(null)
const draggedLayoutItem = ref(null)
const originalData = ref(null)

// Computed
const viewModes = [
  { id: 'select', label: 'Widgets', icon: CubeIcon },
  { id: 'layout', label: 'Layout', icon: Squares2X2Icon },
]

const categories = computed(() => {
  return [...new Set(allWidgets.value.map(w => w.category))].sort()
})

const availableWidgets = computed(() => {
  return allWidgets.value.filter(widget => 
    !assignedWidgets.value.some(assigned => assigned.id === widget.id)
  )
})

const filteredAvailableWidgets = computed(() => {
  if (!selectedCategory.value) return availableWidgets.value
  return availableWidgets.value.filter(w => w.category === selectedCategory.value)
})

const hasChanges = computed(() => {
  if (!originalData.value) return false
  
  const currentState = {
    assignedWidgets: assignedWidgets.value.map(w => ({
      id: w.id,
      enabled_by_default: w.enabled_by_default,
      configurable: w.configurable
    })),
    dashboardLayout: dashboardLayout.value
  }
  
  return JSON.stringify(currentState) !== JSON.stringify(originalData.value)
})

// Methods
const close = () => {
  emit('close')
}

const loadWidgets = async () => {
  if (!props.roleTemplate) return
  
  loading.value = true
  error.value = null
  
  try {
    // Load all available widgets
    const [widgetsResponse, assignedResponse, layoutResponse] = await Promise.all([
      axios.get('/api/widget-permissions'),
      axios.get(`/api/role-templates/${props.roleTemplate.id}/preview/widgets`),
      axios.get(`/api/role-templates/${props.roleTemplate.id}/preview/layout`)
    ])
    
    allWidgets.value = widgetsResponse.data.data
    assignedWidgets.value = assignedResponse.data.data.widgets || []
    dashboardLayout.value = layoutResponse.data.data.layout || []
    
    // Store original state for change detection
    originalData.value = {
      assignedWidgets: assignedWidgets.value.map(w => ({
        id: w.id,
        enabled_by_default: w.enabled_by_default,
        configurable: w.configurable
      })),
      dashboardLayout: dashboardLayout.value
    }
    
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to load widget configuration'
    console.error('Widget loading error:', err)
  } finally {
    loading.value = false
  }
}

const getWidgetIcon = (category) => {
  const iconMap = {
    'administration': CogIcon,
    'service_delivery': TicketIcon,
    'time_management': ClockIcon,
    'financial': CurrencyDollarIcon,
    'communication': ChatBubbleLeftRightIcon,
    'productivity': RocketLaunchIcon,
  }
  return iconMap[category] || CubeIcon
}

const formatCategoryName = (category) => {
  return category.split('_').map(word => 
    word.charAt(0).toUpperCase() + word.slice(1)
  ).join(' ')
}

// Drag and Drop
const startDrag = (widget, source, index = null) => {
  isDragging.value = true
  draggedItem.value = { widget, source, index }
}

const handleDrop = (event, target) => {
  event.preventDefault()
  isDragging.value = false
  
  if (!draggedItem.value) return
  
  const { widget, source, index } = draggedItem.value
  
  if (source === 'available' && target === 'assigned') {
    // Add widget to assigned
    assignedWidgets.value.push({
      ...widget,
      enabled_by_default: widget.enabled_by_default || false,
      configurable: widget.configurable || true
    })
    
    // Auto-add to layout with default positioning
    addToLayout(widget)
    
  } else if (source === 'assigned' && target === 'available') {
    // Remove widget from assigned
    assignedWidgets.value.splice(index, 1)
    
    // Remove from layout
    removeFromLayoutById(widget.id)
  }
  
  draggedItem.value = null
}

const removeWidget = (index) => {
  const widget = assignedWidgets.value[index]
  assignedWidgets.value.splice(index, 1)
  removeFromLayoutById(widget.id)
}

const updateWidget = (index, property, value) => {
  assignedWidgets.value[index][property] = value
}

// Layout Management
const addToLayout = (widget) => {
  const existing = dashboardLayout.value.find(item => item.i === widget.id)
  if (existing) return
  
  const defaultSize = widget.default_size || { w: 4, h: 3 }
  const position = findNextPosition(defaultSize.w, defaultSize.h)
  
  dashboardLayout.value.push({
    i: widget.id,
    x: position.x,
    y: position.y,
    w: defaultSize.w,
    h: defaultSize.h,
    widget_config: {}
  })
}

const removeFromLayout = (index) => {
  dashboardLayout.value.splice(index, 1)
}

const removeFromLayoutById = (widgetId) => {
  const index = dashboardLayout.value.findIndex(item => item.i === widgetId)
  if (index !== -1) {
    dashboardLayout.value.splice(index, 1)
  }
}

const findNextPosition = (width, height) => {
  const maxCols = 12
  const grid = []
  
  // Build occupation grid
  for (let y = 0; y < 20; y++) {
    grid[y] = new Array(maxCols).fill(false)
  }
  
  dashboardLayout.value.forEach(item => {
    for (let y = item.y; y < item.y + item.h; y++) {
      for (let x = item.x; x < item.x + item.w; x++) {
        if (grid[y] && x < maxCols) {
          grid[y][x] = true
        }
      }
    }
  })
  
  // Find first available position
  for (let y = 0; y < grid.length; y++) {
    for (let x = 0; x <= maxCols - width; x++) {
      let canPlace = true
      
      for (let dy = 0; dy < height && canPlace; dy++) {
        for (let dx = 0; dx < width && canPlace; dx++) {
          if (!grid[y + dy] || grid[y + dy][x + dx]) {
            canPlace = false
          }
        }
      }
      
      if (canPlace) {
        return { x, y }
      }
    }
  }
  
  return { x: 0, y: 0 }
}

const getLayoutItemStyle = (layoutItem) => {
  const gridWidth = 100 / 12
  const rowHeight = 60
  
  return {
    left: `${layoutItem.x * gridWidth}%`,
    top: `${layoutItem.y * rowHeight}px`,
    width: `${layoutItem.w * gridWidth}%`,
    height: `${layoutItem.h * rowHeight}px`,
  }
}

const getWidgetById = (widgetId) => {
  return assignedWidgets.value.find(w => w.id === widgetId) || 
         allWidgets.value.find(w => w.id === widgetId)
}

const resetLayout = () => {
  dashboardLayout.value = []
  assignedWidgets.value.forEach(widget => {
    if (widget.enabled_by_default) {
      addToLayout(widget)
    }
  })
}

// Save Changes
const saveChanges = async () => {
  if (!props.roleTemplate || !hasChanges.value) return
  
  saving.value = true
  
  try {
    const payload = {
      widgets: assignedWidgets.value.map(w => ({
        widget_id: w.id,
        enabled: true,
        enabled_by_default: w.enabled_by_default,
        configurable: w.configurable,
        widget_config: {}
      })),
      layout: dashboardLayout.value
    }
    
    await axios.put(`/api/role-templates/${props.roleTemplate.id}/widgets`, payload)
    
    // Update original data to reflect saved state
    originalData.value = {
      assignedWidgets: assignedWidgets.value.map(w => ({
        id: w.id,
        enabled_by_default: w.enabled_by_default,
        configurable: w.configurable
      })),
      dashboardLayout: dashboardLayout.value
    }
    
    emit('saved')
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to save widget configuration'
    console.error('Save error:', err)
  } finally {
    saving.value = false
  }
}

// Watchers
watch(() => props.open, (newValue) => {
  if (newValue && props.roleTemplate) {
    loadWidgets()
  }
})

watch(() => props.roleTemplate, () => {
  if (props.open && props.roleTemplate) {
    loadWidgets()
  }
})

// Lifecycle
onMounted(() => {
  if (props.open && props.roleTemplate) {
    loadWidgets()
  }
})
</script>

<style scoped>
.layout-item {
  min-height: 80px;
  transition: all 0.2s ease;
}

.layout-item:hover {
  z-index: 10;
}

.resize-handle {
  opacity: 0;
  transition: opacity 0.2s ease;
}

.layout-item:hover .resize-handle {
  opacity: 1;
}

.grid-layout-container {
  position: relative;
  min-height: 400px;
}

/* Drag and drop visual feedback */
.cursor-move:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}
</style>