<template>
  <AppLayout :title="dashboardMeta?.title || 'Dashboard'">
    <template #header>
      <div class="flex items-center justify-between">
        <div>
          <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ dashboardMeta?.title || 'Dashboard' }}
          </h2>
          <p v-if="dashboardMeta?.userName" class="text-sm text-gray-600 mt-1">
            Welcome back, {{ dashboardMeta.userName }}
            <span v-if="dashboardMeta?.lastLogin" class="text-gray-500">
              • Last login: {{ dashboardMeta.lastLogin }}
            </span>
          </p>
        </div>
        
        <!-- Account Context Switcher -->
        <div v-if="accountContext?.canSwitchAccounts" class="flex items-center space-x-4">
          <div class="flex items-center space-x-2">
            <label for="account-select" class="text-sm font-medium text-gray-700">
              Account:
            </label>
            <select
              id="account-select"
              v-model="selectedAccountId"
              @change="switchAccount"
              class="border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
            >
              <option value="">All Accounts</option>
              <option 
                v-for="account in accountContext.availableAccounts" 
                :key="account.id" 
                :value="account.id"
              >
                {{ account.name }}
              </option>
            </select>
          </div>
        </div>
      </div>
    </template>

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div v-if="availableWidgets.length === 0" class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6 text-gray-900 text-center">
            <div class="mb-4">
              <svg class="w-12 h-12 text-gray-400 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
              </svg>
            </div>
            <h3 class="text-lg font-medium text-gray-900 mb-2">No widgets available</h3>
            <p class="text-gray-600">Contact your administrator to configure dashboard widgets.</p>
          </div>
        </div>

        <!-- Widget Grid Layout -->
        <div v-else class="widget-grid">
          <div
            v-for="widget in availableWidgets"
            :key="widget.id"
            :class="getWidgetClasses(widget)"
            class="widget-container bg-white overflow-hidden shadow-sm rounded-lg"
          >
            <!-- Widget Header -->
            <div v-if="widget.name" class="widget-header px-4 py-3 border-b border-gray-200 bg-gray-50">
              <div class="flex items-center justify-between">
                <h3 class="text-sm font-semibold text-gray-900">{{ widget.name }}</h3>
                <div class="flex items-center space-x-2">
                  <button
                    v-if="widget.configurable"
                    @click="configureWidget(widget)"
                    class="p-1 text-gray-400 hover:text-gray-600 rounded-md"
                    title="Configure widget"
                  >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4"></path>
                    </svg>
                  </button>
                  <div class="text-xs text-gray-500 px-2 py-1 bg-gray-100 rounded-full">
                    {{ widget.category }}
                  </div>
                </div>
              </div>
              <p v-if="widget.description" class="text-xs text-gray-600 mt-1">{{ widget.description }}</p>
            </div>

            <!-- Widget Content -->
            <div class="widget-content p-4">
              <WidgetLoader
                :widget="widget"
                :widget-data="widgetData[widget.id]"
                :account-context="accountContext"
                @refresh="refreshWidget(widget.id)"
                @configure="configureWidget(widget)"
              />
            </div>
          </div>
        </div>

        <!-- Widget Categories Tabs (for future enhancement) -->
        <div v-if="widgetCategories.length > 1" class="mt-8 border-b border-gray-200">
          <nav class="-mb-px flex space-x-8">
            <button
              v-for="category in widgetCategories"
              :key="category"
              @click="filterByCategory(category)"
              :class="[
                'py-2 px-1 border-b-2 font-medium text-sm',
                selectedCategory === category
                  ? 'border-indigo-500 text-indigo-600'
                  : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
              ]"
            >
              {{ formatCategoryName(category) }}
            </button>
          </nav>
        </div>
      </div>
    </div>

    <!-- Widget Configuration Modal -->
    <Modal :show="showConfigModal" @close="closeConfigModal">
      <div class="p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Configure {{ configuringWidget?.name }}</h3>
        <p class="text-sm text-gray-600 mb-4">Widget configuration coming soon...</p>
        <div class="flex justify-end space-x-3">
          <button
            @click="closeConfigModal"
            class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400"
          >
            Close
          </button>
        </div>
      </div>
    </Modal>
  </AppLayout>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Modal from '@/Components/Modal.vue'
import WidgetLoader from '@/Components/WidgetLoader.vue'

// Props from the controller
const props = defineProps({
  title: String,
  availableWidgets: {
    type: Array,
    default: () => []
  },
  dashboardLayout: {
    type: Array,
    default: () => []
  },
  widgetData: {
    type: Object,
    default: () => ({})
  },
  accountContext: {
    type: Object,
    default: () => ({})
  },
  dashboardMeta: {
    type: Object,
    default: () => ({})
  },
  widgetCategories: {
    type: Array,
    default: () => []
  }
})

// Reactive state
const selectedAccountId = ref(props.accountContext?.selectedAccount?.id || '')
const selectedCategory = ref('all')
const showConfigModal = ref(false)
const configuringWidget = ref(null)
const widgetErrors = reactive({})

// Computed properties
const filteredWidgets = computed(() => {
  if (selectedCategory.value === 'all') {
    return props.availableWidgets
  }
  return props.availableWidgets.filter(widget => widget.category === selectedCategory.value)
})

// Methods
const getWidgetClasses = (widget) => {
  // Default responsive grid classes
  const baseClasses = 'widget-item'
  
  // Add size-based classes if layout information is available
  const layoutItem = props.dashboardLayout.find(item => item.i === widget.id)
  if (layoutItem) {
    const widthClass = `w-${Math.min(12, Math.max(1, layoutItem.w))}/12`
    const heightClass = `min-h-${layoutItem.h * 20}`
    return `${baseClasses} ${widthClass} ${heightClass}`
  }
  
  // Default responsive sizing
  return `${baseClasses} w-full md:w-1/2 lg:w-1/3 xl:w-1/4`
}

const switchAccount = () => {
  router.get(route('dashboard'), {
    account: selectedAccountId.value
  }, {
    preserveScroll: true,
    replace: true
  })
}

const refreshWidget = (widgetId) => {
  // Refresh specific widget data
  router.get(route('dashboard'), {
    account: selectedAccountId.value,
    refresh_widget: widgetId
  }, {
    preserveScroll: true,
    only: ['widgetData'],
    onError: () => {
      widgetErrors[widgetId] = true
    }
  })
}

const configureWidget = (widget) => {
  configuringWidget.value = widget
  showConfigModal.value = true
}

const closeConfigModal = () => {
  showConfigModal.value = false
  configuringWidget.value = null
}

const filterByCategory = (category) => {
  selectedCategory.value = category
}

const formatCategoryName = (category) => {
  return category
    .split('_')
    .map(word => word.charAt(0).toUpperCase() + word.slice(1))
    .join(' ')
}

// Lifecycle
onMounted(() => {
  // Initialize any required state
  console.log('Dashboard loaded with', props.availableWidgets.length, 'available widgets')
})
</script>

<style scoped>
.widget-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
  gap: 1.5rem;
  align-items: start;
}

@media (min-width: 768px) {
  .widget-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (min-width: 1024px) {
  .widget-grid {
    grid-template-columns: repeat(3, 1fr);
  }
}

@media (min-width: 1280px) {
  .widget-grid {
    grid-template-columns: repeat(4, 1fr);
  }
}

.widget-container {
  transition: all 0.2s ease-in-out;
}

.widget-container:hover {
  transform: translateY(-2px);
  box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
}

.widget-header {
  background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
}

.widget-content {
  min-height: 120px;
}
</style>