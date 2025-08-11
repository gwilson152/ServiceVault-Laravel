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
                      <div class="w-8 h-8 bg-indigo-100 rounded-lg flex items-center justify-center">
                        <EyeIcon class="w-5 h-5 text-indigo-600" />
                      </div>
                    </div>
                    <div>
                      <DialogTitle as="h3" class="text-lg leading-6 font-medium text-gray-900">
                        Dashboard Preview
                      </DialogTitle>
                      <p v-if="roleTemplate" class="text-sm text-gray-500">
                        {{ roleTemplate.name }} {{ contextLabel }} Experience
                      </p>
                    </div>
                  </div>
                  <div class="flex items-center space-x-3">
                    <!-- Context Switcher -->
                    <div v-if="roleTemplate?.context === 'both'" class="flex items-center space-x-2">
                      <label class="text-sm font-medium text-gray-700">Context:</label>
                      <select
                        v-model="selectedContext"
                        @change="loadPreview"
                        class="border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                      >
                        <option value="service_provider">Service Provider</option>
                        <option value="account_user">Account User</option>
                      </select>
                    </div>
                    
                    <!-- View Mode Tabs -->
                    <div class="flex rounded-lg border border-gray-200 bg-gray-50">
                      <button
                        v-for="mode in viewModes"
                        :key="mode.id"
                        @click="activeTab = mode.id"
                        :class="[
                          activeTab === mode.id
                            ? 'bg-white text-indigo-600 border-indigo-200'
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
                      class="rounded-md bg-white text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
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
                  <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
                  <span class="ml-3 text-gray-600">Loading dashboard preview...</span>
                </div>
              </div>

              <!-- Error State -->
              <div v-else-if="error" class="bg-gray-50 px-4 py-8 sm:px-6">
                <div class="rounded-md bg-red-50 p-4">
                  <div class="flex">
                    <ExclamationTriangleIcon class="h-5 w-5 text-red-400" aria-hidden="true" />
                    <div class="ml-3">
                      <h3 class="text-sm font-medium text-red-800">Failed to load preview</h3>
                      <p class="mt-2 text-sm text-red-700">{{ error }}</p>
                      <div class="mt-3">
                        <button
                          @click="loadPreview"
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
              <div v-else-if="previewData" class="bg-gray-50 px-4 py-6 sm:px-6 max-h-[80vh] overflow-y-auto">
                
                <!-- Dashboard Tab -->
                <div v-show="activeTab === 'dashboard'" class="space-y-6">
                  <!-- Preview Meta Info -->
                  <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <div class="flex items-start">
                      <InformationCircleIcon class="h-5 w-5 text-blue-400 mt-0.5" />
                      <div class="ml-3">
                        <h4 class="text-sm font-medium text-blue-900">Preview Mode Active</h4>
                        <div class="mt-1 text-sm text-blue-700">
                          <p>Viewing dashboard as: <span class="font-semibold">{{ previewData.mock_user?.name }}</span></p>
                          <p>Role: {{ roleTemplate?.name }} ({{ contextLabel }})</p>
                          <p>Available widgets: {{ previewData.stats?.total_widgets || 0 }}</p>
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- Dashboard Preview -->
                  <div class="bg-white rounded-lg shadow border">
                    <!-- Mock Dashboard Header -->
                    <div class="bg-gray-800 text-white px-6 py-4 rounded-t-lg">
                      <div class="flex items-center justify-between">
                        <div>
                          <h2 class="text-lg font-semibold">{{ previewData.dashboard?.title || 'Dashboard' }}</h2>
                          <p v-if="previewData.mock_user" class="text-sm text-gray-300">
                            Welcome back, {{ previewData.mock_user.name }}
                          </p>
                        </div>
                        <div class="text-xs text-gray-400">
                          PREVIEW MODE
                        </div>
                      </div>
                    </div>

                    <!-- Widget Grid -->
                    <div class="p-6">
                      <div v-if="previewData.dashboard?.available_widgets?.length === 0" class="text-center py-12">
                        <div class="w-12 h-12 bg-gray-200 rounded-lg mx-auto mb-4 flex items-center justify-center">
                          <CubeIcon class="w-6 h-6 text-gray-400" />
                        </div>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">No widgets available</h3>
                        <p class="text-gray-500">This role template has no widgets configured.</p>
                      </div>
                      
                      <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                        <div
                          v-for="widget in previewData.dashboard?.available_widgets || []"
                          :key="widget.id"
                          :class="getWidgetGridClasses(widget)"
                          class="bg-gray-50 border-2 border-dashed border-gray-300 rounded-lg p-4 transition-all duration-200 hover:border-indigo-300 hover:bg-indigo-50"
                        >
                          <div class="flex items-center justify-between mb-3">
                            <h4 class="text-sm font-medium text-gray-900">{{ widget.name }}</h4>
                            <div class="text-xs text-gray-500 px-2 py-1 bg-white rounded-full border">
                              {{ widget.category }}
                            </div>
                          </div>
                          <div class="text-xs text-gray-600 mb-3">{{ widget.description }}</div>
                          
                          <!-- Mock Widget Content -->
                          <div class="bg-white rounded border p-3 min-h-[80px] flex items-center justify-center">
                            <div class="text-center">
                              <component :is="getWidgetIcon(widget.category)" class="w-8 h-8 text-gray-400 mx-auto mb-2" />
                              <div class="text-xs text-gray-500">{{ widget.component }}</div>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Widgets Tab -->
                <div v-show="activeTab === 'widgets'" class="space-y-4">
                  <div class="bg-white rounded-lg shadow border overflow-hidden">
                    <div class="px-4 py-3 border-b border-gray-200">
                      <h3 class="text-lg font-medium text-gray-900">Available Widgets</h3>
                      <p class="text-sm text-gray-500">Widgets accessible to this role template</p>
                    </div>
                    
                    <div class="divide-y divide-gray-200">
                      <div
                        v-for="widget in previewData.dashboard?.available_widgets || []"
                        :key="widget.id"
                        class="px-4 py-4 hover:bg-gray-50"
                      >
                        <div class="flex items-center justify-between">
                          <div class="flex items-center space-x-3">
                            <component :is="getWidgetIcon(widget.category)" class="w-6 h-6 text-gray-400" />
                            <div>
                              <h4 class="text-sm font-medium text-gray-900">{{ widget.name }}</h4>
                              <p class="text-xs text-gray-500">{{ widget.description }}</p>
                            </div>
                          </div>
                          <div class="flex items-center space-x-2">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                              {{ widget.category }}
                            </span>
                            <span
                              :class="[
                                widget.enabled_by_default
                                  ? 'bg-green-100 text-green-800'
                                  : 'bg-yellow-100 text-yellow-800'
                              ]"
                              class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                            >
                              {{ widget.enabled_by_default ? 'Default' : 'Optional' }}
                            </span>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Navigation Tab -->
                <div v-show="activeTab === 'navigation'" class="space-y-4">
                  <div class="bg-white rounded-lg shadow border overflow-hidden">
                    <div class="px-4 py-3 border-b border-gray-200">
                      <h3 class="text-lg font-medium text-gray-900">Navigation Menu</h3>
                      <p class="text-sm text-gray-500">Menu items accessible to this role template</p>
                    </div>
                    
                    <div class="p-4">
                      <nav class="space-y-1">
                        <div
                          v-for="item in previewData.dashboard?.navigation || []"
                          :key="item.name || item.label"
                          class="group flex items-center px-2 py-2 text-sm font-medium rounded-md text-gray-600 hover:bg-gray-50 hover:text-gray-900"
                        >
                          <component
                            v-if="item.icon"
                            :is="getNavigationIcon(item.icon)"
                            class="mr-3 h-5 w-5 text-gray-400 group-hover:text-gray-500"
                          />
                          <span>{{ item.name || item.label }}</span>
                          <span v-if="item.badge" class="ml-auto inline-block py-0.5 px-3 text-xs rounded-full bg-gray-100 group-hover:bg-gray-200">
                            {{ item.badge }}
                          </span>
                        </div>
                      </nav>
                    </div>
                  </div>
                </div>

                <!-- Layout Tab -->
                <div v-show="activeTab === 'layout'" class="space-y-4">
                  <div class="bg-white rounded-lg shadow border overflow-hidden">
                    <div class="px-4 py-3 border-b border-gray-200">
                      <h3 class="text-lg font-medium text-gray-900">Dashboard Layout</h3>
                      <p class="text-sm text-gray-500">Default widget placement and sizing</p>
                    </div>
                    
                    <div class="p-6">
                      <!-- Layout Grid Visualization -->
                      <div class="relative bg-gray-50 border-2 border-dashed border-gray-300 rounded-lg p-4 min-h-[400px]">
                        <div class="absolute inset-4 grid grid-cols-12 gap-2 opacity-20">
                          <div v-for="i in 12" :key="i" class="bg-gray-400 rounded"></div>
                        </div>
                        
                        <div
                          v-for="layoutItem in previewData.dashboard?.layout || []"
                          :key="layoutItem.i"
                          :style="getLayoutItemStyle(layoutItem)"
                          class="absolute bg-white border-2 border-indigo-300 rounded-lg p-3 shadow-sm"
                        >
                          <div class="text-xs font-medium text-gray-900 mb-1">
                            {{ getWidgetName(layoutItem.i) }}
                          </div>
                          <div class="text-xs text-gray-500">
                            {{ layoutItem.w }}×{{ layoutItem.h }} @ ({{ layoutItem.x }}, {{ layoutItem.y }})
                          </div>
                        </div>
                      </div>
                      
                      <!-- Layout Stats -->
                      <div class="mt-4 grid grid-cols-3 gap-4 text-center">
                        <div class="bg-gray-50 rounded-lg p-3">
                          <div class="text-lg font-semibold text-gray-900">{{ previewData.dashboard?.layout?.length || 0 }}</div>
                          <div class="text-xs text-gray-500">Widgets Placed</div>
                        </div>
                        <div class="bg-gray-50 rounded-lg p-3">
                          <div class="text-lg font-semibold text-gray-900">12</div>
                          <div class="text-xs text-gray-500">Grid Columns</div>
                        </div>
                        <div class="bg-gray-50 rounded-lg p-3">
                          <div class="text-lg font-semibold text-gray-900">{{ Math.max(...(previewData.dashboard?.layout || []).map(item => item.y + item.h)) || 0 }}</div>
                          <div class="text-xs text-gray-500">Grid Rows</div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Footer -->
              <div class="bg-gray-50 px-4 py-3 border-t border-gray-200 sm:px-6">
                <div class="flex items-center justify-between">
                  <div v-if="previewData?.stats" class="flex items-center space-x-4 text-sm text-gray-500">
                    <span>{{ previewData.stats.total_widgets }} widgets</span>
                    <span>•</span>
                    <span>{{ previewData.stats.navigation_items }} menu items</span>
                    <span>•</span>
                    <span>{{ previewData.stats.functional_permissions }} permissions</span>
                  </div>
                  <div class="flex space-x-3">
                    <button
                      type="button"
                      class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                      @click="close"
                    >
                      Close Preview
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
import { ref, computed, watch, onMounted } from 'vue'
import axios from 'axios'
import { Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot } from '@headlessui/vue'
import {
  XMarkIcon,
  EyeIcon,
  CubeIcon,
  InformationCircleIcon,
  ExclamationTriangleIcon,
  Squares2X2Icon,
  ListBulletIcon,
  Bars3Icon,
  RectangleStackIcon,
  // Widget category icons
  CogIcon,
  ChartBarIcon,
  TicketIcon,
  ClockIcon,
  CurrencyDollarIcon,
  ChatBubbleLeftRightIcon,
  RocketLaunchIcon,
  UserGroupIcon,
  // Navigation icons
  HomeIcon,
  DocumentTextIcon,
  UsersIcon,
  BuildingOfficeIcon,
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

const emit = defineEmits(['close'])

// State
const loading = ref(false)
const error = ref(null)
const previewData = ref(null)
const activeTab = ref('dashboard')
const selectedContext = ref('service_provider')

// Computed
const contextLabel = computed(() => {
  return selectedContext.value === 'service_provider' ? '(Service Provider)' : '(Account User)'
})

const viewModes = [
  { id: 'dashboard', label: 'Dashboard', icon: Squares2X2Icon },
  { id: 'widgets', label: 'Widgets', icon: CubeIcon },
  { id: 'navigation', label: 'Navigation', icon: Bars3Icon },
  { id: 'layout', label: 'Layout', icon: RectangleStackIcon },
]

// Methods
const close = () => {
  emit('close')
}

const loadPreview = async () => {
  if (!props.roleTemplate) return
  
  loading.value = true
  error.value = null
  
  try {
    const params = {}
    if (selectedContext.value) {
      params.context = selectedContext.value
    }
    
    const response = await axios.get(`/api/role-templates/${props.roleTemplate.id}/preview/dashboard`, { params })
    previewData.value = response.data.data
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to load preview'
    console.error('Preview loading error:', err)
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

const getNavigationIcon = (iconName) => {
  const iconMap = {
    'home': HomeIcon,
    'document': DocumentTextIcon,
    'users': UsersIcon,
    'building': BuildingOfficeIcon,
    'ticket': TicketIcon,
    'clock': ClockIcon,
    'chart': ChartBarIcon,
  }
  return iconMap[iconName] || DocumentTextIcon
}

const getWidgetGridClasses = (widget) => {
  const size = widget.default_size || { w: 4, h: 3 }
  
  // Map widget width to grid columns (12-column system)
  if (size.w >= 8) {
    return 'md:col-span-2 lg:col-span-3 xl:col-span-4'
  } else if (size.w >= 6) {
    return 'md:col-span-2 lg:col-span-2'
  } else {
    return 'col-span-1'
  }
}

const getLayoutItemStyle = (layoutItem) => {
  const gridWidth = 100 / 12 // 12 column grid
  const rowHeight = 40 // pixels
  
  return {
    left: `${layoutItem.x * gridWidth}%`,
    top: `${layoutItem.y * rowHeight}px`,
    width: `${layoutItem.w * gridWidth}%`,
    height: `${layoutItem.h * rowHeight}px`,
  }
}

const getWidgetName = (widgetId) => {
  const widget = previewData.value?.dashboard?.available_widgets?.find(w => w.id === widgetId)
  return widget?.name || widgetId
}

// Watchers
watch(() => props.open, (newValue) => {
  if (newValue && props.roleTemplate) {
    selectedContext.value = props.roleTemplate.context === 'both' ? 'service_provider' : props.roleTemplate.context
    loadPreview()
  }
})

watch(() => props.roleTemplate, () => {
  if (props.open && props.roleTemplate) {
    selectedContext.value = props.roleTemplate.context === 'both' ? 'service_provider' : props.roleTemplate.context
    loadPreview()
  }
})

// Lifecycle
onMounted(() => {
  if (props.open && props.roleTemplate) {
    loadPreview()
  }
})
</script>