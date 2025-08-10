<template>
  <div class="widget-loader">
    <!-- Loading State -->
    <div v-if="isLoading" class="widget-loading">
      <div class="animate-pulse">
        <div class="h-4 bg-gray-300 rounded w-3/4 mb-2"></div>
        <div class="space-y-2">
          <div class="h-3 bg-gray-300 rounded"></div>
          <div class="h-3 bg-gray-300 rounded w-5/6"></div>
        </div>
      </div>
    </div>

    <!-- Widget Component -->
    <component
      v-else-if="widgetComponent && !hasError"
      :is="widgetComponent"
      v-bind="widgetProps"
      @refresh="$emit('refresh')"
      @configure="$emit('configure')"
    />

    <!-- Error State -->
    <div v-else-if="hasError" class="widget-error">
      <div class="text-center py-4">
        <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100">
          <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
          </svg>
        </div>
        <p class="mt-2 text-sm text-red-600">Failed to load widget</p>
        <p class="text-xs text-gray-500 mt-1">{{ errorMessage }}</p>
        <button 
          @click="retryLoad"
          class="mt-2 text-xs text-red-700 underline hover:no-underline"
        >
          Retry
        </button>
      </div>
    </div>

    <!-- Unknown Widget -->
    <div v-else class="widget-unknown">
      <div class="text-center py-4">
        <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-gray-100">
          <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
          </svg>
        </div>
        <p class="mt-2 text-sm text-gray-600">Unknown widget: {{ widget.component }}</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, markRaw } from 'vue'

// Props
const props = defineProps({
  widget: {
    type: Object,
    required: true
  },
  widgetData: {
    type: [Object, Array],
    default: null
  },
  accountContext: {
    type: Object,
    default: () => ({})
  }
})

// Emits
const emit = defineEmits(['refresh', 'configure'])

// State
const widgetComponent = ref(null)
const isLoading = ref(true)
const hasError = ref(false)
const errorMessage = ref('')

// Computed
const widgetProps = computed(() => ({
  widgetData: props.widgetData,
  widgetConfig: props.widget,
  accountContext: props.accountContext
}))

// Widget component mapping - all available components
const widgetComponents = {
  'SystemHealthWidget': () => import('@/Components/Widgets/SystemHealthWidget.vue'),
  'SystemStatsWidget': () => import('@/Components/Widgets/SystemStatsWidget.vue'),
  'QuickActionsWidget': () => import('@/Components/Widgets/QuickActionsWidget.vue'),
  'UserManagementWidget': () => import('@/Components/Widgets/UserManagementWidget.vue'),
  'AccountManagementWidget': () => import('@/Components/Widgets/AccountManagementWidget.vue'),
  'TicketOverviewWidget': () => import('@/Components/Widgets/TicketOverviewWidget.vue'),
  'TimeTrackingWidget': () => import('@/Components/Widgets/TimeTrackingWidget.vue'),
  'MyTicketsWidget': () => import('@/Components/Widgets/MyTicketsWidget.vue'),
  'TimeEntriesWidget': () => import('@/Components/Widgets/TimeEntriesWidget.vue'),
  'RecentTimeEntriesWidget': () => import('@/Components/Widgets/RecentTimeEntriesWidget.vue'),
  'AllTimersWidget': () => import('@/Components/Widgets/AllTimersWidget.vue'),
  'TicketFiltersWidget': () => import('@/Components/Widgets/TicketFiltersWidget.vue'),
  'TicketTimerStatsWidget': () => import('@/Components/Widgets/TicketTimerStatsWidget.vue'),
  'BillingOverviewWidget': () => import('@/Components/Widgets/BillingOverviewWidget.vue'),
  'AccountActivityWidget': () => import('@/Components/Widgets/AccountActivityWidget.vue'),
  // TODO: Create these widgets when needed:
  // 'TeamPerformanceWidget': () => import('@/Components/Widgets/TeamPerformanceWidget.vue'),
  // 'AccountUsersWidget': () => import('@/Components/Widgets/AccountUsersWidget.vue'),
}

// Methods
const loadWidget = async () => {
  isLoading.value = true
  hasError.value = false
  errorMessage.value = ''

  try {
    const componentName = props.widget.component
    
    if (!componentName) {
      throw new Error('No component specified for widget')
    }

    // Try to load the component
    const componentLoader = widgetComponents[componentName]
    
    if (!componentLoader) {
      // Log available components for debugging
      console.warn(`Widget component '${componentName}' not found. Available components:`, Object.keys(widgetComponents))
      throw new Error(`Widget component '${componentName}' not found`)
    }

    const component = await componentLoader()
    // Use markRaw to prevent the component from being made reactive
    widgetComponent.value = markRaw(component.default || component)
    
  } catch (error) {
    console.error(`Failed to load widget ${props.widget.component}:`, error)
    hasError.value = true
    errorMessage.value = error.message
  } finally {
    isLoading.value = false
  }
}

const retryLoad = () => {
  loadWidget()
}

// Watchers
watch(() => props.widget, () => {
  loadWidget()
}, { immediate: true })

// Lifecycle
onMounted(() => {
  loadWidget()
})
</script>

<style scoped>
.widget-loader {
  min-height: 60px;
}

.widget-loading,
.widget-error,
.widget-unknown {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 100px;
}

.widget-error {
  background-color: #fef2f2;
  border: 1px solid #fecaca;
  border-radius: 0.5rem;
}
</style>