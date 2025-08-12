<template>
  <div class="widget-content">
    <div class="widget-header">
      <h3 class="widget-title">{{ props.widgetConfig?.name || 'Billing Overview' }}</h3>
      <div class="widget-actions">
        <button
          @click="refreshData"
          :disabled="isLoading"
          class="p-1 text-gray-400 hover:text-gray-600 transition-colors"
          title="Refresh"
        >
          <svg class="w-4 h-4" :class="{ 'animate-spin': isLoading }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
          </svg>
        </button>
      </div>
    </div>

    <div v-if="isLoading" class="widget-loading">
      <div class="flex items-center justify-center h-24">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
      </div>
    </div>

    <div v-else-if="error" class="widget-error">
      <div class="text-center py-4">
        <div class="text-red-600 text-sm">{{ error }}</div>
        <button @click="refreshData" class="mt-2 text-xs text-blue-600 hover:text-blue-800">
          Try Again
        </button>
      </div>
    </div>

    <div v-else class="widget-data">
      <div class="grid grid-cols-2 gap-4 p-4">
        <!-- Placeholder content for billing overview -->
        <div class="text-center p-4 bg-gray-50 rounded-lg">
          <div class="text-2xl font-bold text-gray-800">${{ totalRevenue.toLocaleString() }}</div>
          <div class="text-sm text-gray-600">Total Revenue</div>
        </div>
        
        <div class="text-center p-4 bg-gray-50 rounded-lg">
          <div class="text-2xl font-bold text-gray-800">{{ pendingInvoices }}</div>
          <div class="text-sm text-gray-600">Pending Invoices</div>
        </div>
        
        <div class="text-center p-4 bg-gray-50 rounded-lg">
          <div class="text-2xl font-bold text-gray-800">${{ monthlyRecurring.toLocaleString() }}</div>
          <div class="text-sm text-gray-600">Monthly Recurring</div>
        </div>
        
        <div class="text-center p-4 bg-gray-50 rounded-lg">
          <div class="text-2xl font-bold text-gray-800">{{ overdueCount }}</div>
          <div class="text-sm text-gray-600">Overdue Invoices</div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'

// Props
const props = defineProps({
  widgetData: {
    type: [Object, Array],
    default: null
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
const isLoading = ref(false)
const error = ref(null)

// Mock data for demonstration
const totalRevenue = ref(125750.00)
const pendingInvoices = ref(12)
const monthlyRecurring = ref(8500.00)
const overdueCount = ref(3)

// Methods
const refreshData = async () => {
  isLoading.value = true
  error.value = null
  
  try {
    const params = new URLSearchParams()
    if (props.accountContext?.id) {
      params.append('account_id', props.accountContext.id)
    }
    
    const response = await fetch(`/api/billing/overview?${params}`)
    if (!response.ok) {
      throw new Error('Failed to fetch billing overview')
    }
    
    const data = await response.json()
    const overview = data.data || {}
    
    totalRevenue.value = overview.total_revenue || 0
    pendingInvoices.value = overview.pending_invoices || 0
    monthlyRecurring.value = overview.monthly_recurring || 0
    overdueCount.value = overview.overdue_count || 0
    
  } catch (err) {
    error.value = err.message || 'Failed to load billing data'
    console.error('Error loading billing overview:', err)
    
    // Fallback to mock data for development
    totalRevenue.value = Math.floor(Math.random() * 200000) + 100000
    pendingInvoices.value = Math.floor(Math.random() * 20) + 5
    monthlyRecurring.value = Math.floor(Math.random() * 15000) + 5000
    overdueCount.value = Math.floor(Math.random() * 8) + 1
  } finally {
    isLoading.value = false
  }
}

// Lifecycle
onMounted(() => {
  // Only refresh if we don't have initial data
  if (!props.widgetData) {
    refreshData()
  }
})

// Widget configuration is defined in the WidgetRegistryService.php
// This component is for the 'billing-overview' widget
</script>

<style scoped>
.widget-content {
  @apply bg-white rounded-lg shadow-sm border border-gray-200 h-full flex flex-col;
}

.widget-header {
  @apply flex items-center justify-between p-4 border-b border-gray-200;
}

.widget-title {
  @apply text-lg font-semibold text-gray-900;
}

.widget-actions {
  @apply flex items-center gap-2;
}

.widget-loading,
.widget-error,
.widget-data {
  @apply flex-1;
}

.widget-data {
  @apply overflow-auto;
}
</style>