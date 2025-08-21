<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Page Header -->
    <div class="bg-white shadow-sm border-b border-gray-200">
      <div class="px-4 sm:px-6 py-4">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
          <!-- Header Content Slot -->
          <div class="min-w-0 flex-1">
            <slot name="header-content">
              <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                  {{ title }}
                </h2>
                <p v-if="subtitle" class="text-sm text-gray-600 mt-1 hidden sm:block">
                  {{ subtitle }}
                </p>
              </div>
            </slot>
          </div>
          
          <!-- Header Actions Slot -->
          <div class="flex items-center gap-2">
            <slot name="header-actions">
              <!-- Default empty - pages can provide their own actions -->
            </slot>
            
            <!-- Mobile sidebar toggle (only show if sidebar is enabled) -->
            <button
              v-if="showSidebar"
              @click="showMobileSidebar = !showMobileSidebar"
              class="lg:hidden p-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg transition-colors"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
              </svg>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Tabs Section (optional) -->
    <div v-if="$slots.tabs" class="bg-white border-b border-gray-200">
      <div class="px-4 sm:px-6">
        <slot name="tabs">
          <!-- Tab navigation goes here -->
        </slot>
      </div>
    </div>

    <div class="px-4 sm:px-6 py-4 lg:py-6">
      <!-- Mobile Filters Toggle (only show if filters are enabled) -->
      <div v-if="showFilters" class="lg:hidden mb-4">
        <button
          @click="showMobileFilters = !showMobileFilters"
          class="w-full flex items-center justify-between bg-white rounded-lg shadow-sm border border-gray-200 px-4 py-3"
        >
          <span class="font-medium text-gray-900">Filters & Search</span>
          <svg 
            class="w-5 h-5 text-gray-400 transition-transform"
            :class="{ 'rotate-180': showMobileFilters }"
            fill="none" 
            stroke="currentColor" 
            viewBox="0 0 24 24"
          >
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
          </svg>
        </button>
      </div>

      <!-- Filters Section (collapsible on mobile) -->
      <div 
        v-if="showFilters"
        v-show="showMobileFilters || !isMobile"
        class="bg-white rounded-lg shadow-sm border border-gray-200 mb-4 lg:mb-6"
      >
        <slot name="filters">
          <!-- Default empty - pages can provide their own filters -->
        </slot>
      </div>

      <!-- Main Layout Grid -->
      <div :class="gridClasses">
        <!-- Main Content Area -->
        <div :class="contentClasses">
          <slot name="main-content">
            <!-- Default empty - pages must provide main content -->
          </slot>
        </div>
        
        <!-- Sidebar (hidden on mobile unless toggled) -->
        <div 
          v-if="showSidebar"
          :class="sidebarClasses"
        >
          <slot name="sidebar">
            <!-- Default empty - pages can provide sidebar content -->
          </slot>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'

// Props
const props = defineProps({
  title: {
    type: String,
    default: 'Page Title'
  },
  subtitle: {
    type: String,
    default: ''
  },
  showSidebar: {
    type: Boolean,
    default: true
  },
  showFilters: {
    type: Boolean,
    default: true
  },
  sidebarWidth: {
    type: String,
    default: 'lg:col-span-4 xl:col-span-3'
  },
  contentWidth: {
    type: String,
    default: 'lg:col-span-8 xl:col-span-9'
  }
})

// Reactive state
const showMobileFilters = ref(false)
const showMobileSidebar = ref(false)
const isMobile = ref(false)

// Computed classes for responsive layout
const gridClasses = computed(() => {
  if (!props.showSidebar) {
    return ''
  }
  return 'lg:grid lg:grid-cols-12 lg:gap-6'
})

const contentClasses = computed(() => {
  if (!props.showSidebar) {
    return ''
  }
  return props.contentWidth
})

const sidebarClasses = computed(() => {
  const baseClasses = `${props.sidebarWidth} space-y-4 mt-4 lg:mt-0`
  const visibilityClasses = showMobileSidebar.value ? 'block' : 'hidden lg:block'
  return `${baseClasses} ${visibilityClasses}`
})

// Handle window resize for mobile detection
const handleResize = () => {
  isMobile.value = window.innerWidth < 1024
  if (!isMobile.value) {
    showMobileFilters.value = false
    showMobileSidebar.value = false
  }
}

// Lifecycle
onMounted(() => {
  handleResize()
  window.addEventListener('resize', handleResize)
})

onUnmounted(() => {
  window.removeEventListener('resize', handleResize)
})
</script>