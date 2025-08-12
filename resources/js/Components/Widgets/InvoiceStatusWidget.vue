<template>
  <div class="widget-content">
    <div class="widget-header">
      <h3 class="widget-title">{{ props.widgetConfig?.name || 'Invoice Status' }}</h3>
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
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
      </div>
    </div>

    <div v-else-if="error" class="widget-error">
      <div class="text-center py-4">
        <div class="text-red-600 text-sm">{{ error }}</div>
        <button @click="refreshData" class="mt-2 text-xs text-indigo-600 hover:text-indigo-800">
          Try Again
        </button>
      </div>
    </div>

    <div v-else class="widget-data">
      <!-- Status Summary -->
      <div class="p-4 border-b border-gray-200">
        <div class="grid grid-cols-4 gap-2 text-center">
          <div class="bg-green-50 rounded-lg p-2">
            <div class="text-lg font-semibold text-green-700">{{ statusCounts.paid }}</div>
            <div class="text-xs text-green-600">Paid</div>
          </div>
          <div class="bg-yellow-50 rounded-lg p-2">
            <div class="text-lg font-semibold text-yellow-700">{{ statusCounts.pending }}</div>
            <div class="text-xs text-yellow-600">Pending</div>
          </div>
          <div class="bg-blue-50 rounded-lg p-2">
            <div class="text-lg font-semibold text-blue-700">{{ statusCounts.sent }}</div>
            <div class="text-xs text-blue-600">Sent</div>
          </div>
          <div class="bg-red-50 rounded-lg p-2">
            <div class="text-lg font-semibold text-red-700">{{ statusCounts.overdue }}</div>
            <div class="text-xs text-red-600">Overdue</div>
          </div>
        </div>
      </div>

      <!-- Recent Invoices -->
      <div class="p-4">
        <h4 class="text-sm font-medium text-gray-900 mb-3">Recent Invoices</h4>
        <div v-if="invoices.length === 0" class="text-center py-4 text-gray-500">
          <svg class="mx-auto h-8 w-8 text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
          </svg>
          <p class="text-sm">No invoices found</p>
        </div>
        <div v-else class="space-y-2">
          <div 
            v-for="invoice in invoices" 
            :key="invoice.id"
            class="flex items-center justify-between p-2 bg-gray-50 rounded-lg hover:bg-gray-100 cursor-pointer"
            @click="viewInvoice(invoice.id)"
          >
            <div class="flex-1 min-w-0">
              <div class="text-sm font-medium text-gray-900 truncate">
                {{ invoice.invoice_number }}
              </div>
              <div class="text-xs text-gray-500">
                {{ formatDate(invoice.issue_date) }}
              </div>
            </div>
            <div class="text-right">
              <div class="text-sm font-medium text-gray-900">
                ${{ formatCurrency(invoice.total_amount) }}
              </div>
              <div class="text-xs">
                <span :class="getStatusClass(invoice.status)" class="px-2 py-1 rounded-full">
                  {{ invoice.status }}
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Quick Actions -->
      <div class="p-4 border-t border-gray-200 bg-gray-50">
        <div class="flex gap-2">
          <button
            @click="createInvoice"
            class="flex-1 text-xs bg-indigo-600 text-white px-3 py-2 rounded-md hover:bg-indigo-700 transition-colors"
          >
            New Invoice
          </button>
          <button
            @click="viewAllInvoices"
            class="flex-1 text-xs bg-gray-600 text-white px-3 py-2 rounded-md hover:bg-gray-700 transition-colors"
          >
            View All
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { router } from '@inertiajs/vue3'

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
const invoices = ref([])

// Computed
const statusCounts = computed(() => {
  const counts = { paid: 0, pending: 0, sent: 0, overdue: 0 }
  invoices.value.forEach(invoice => {
    if (counts.hasOwnProperty(invoice.status)) {
      counts[invoice.status]++
    }
  })
  return counts
})

// Methods
const refreshData = async () => {
  isLoading.value = true
  error.value = null
  
  try {
    const params = new URLSearchParams()
    if (props.accountContext?.id) {
      params.append('account_id', props.accountContext.id)
    }
    params.append('limit', '5')
    params.append('sort', 'issue_date:desc')
    
    const response = await fetch(`/api/billing/invoices?${params}`)
    if (!response.ok) {
      throw new Error('Failed to fetch invoices')
    }
    
    const data = await response.json()
    invoices.value = data.data || []
    
  } catch (err) {
    error.value = err.message || 'Failed to load invoice data'
    console.error('Error loading invoice data:', err)
  } finally {
    isLoading.value = false
  }
}

const viewInvoice = (invoiceId) => {
  router.visit(`/billing/invoices/${invoiceId}`)
}

const createInvoice = () => {
  router.visit('/billing/invoices/create')
}

const viewAllInvoices = () => {
  router.visit('/billing')
}

const getStatusClass = (status) => {
  const classes = {
    paid: 'bg-green-100 text-green-800',
    pending: 'bg-yellow-100 text-yellow-800',
    sent: 'bg-blue-100 text-blue-800',
    overdue: 'bg-red-100 text-red-800',
    draft: 'bg-gray-100 text-gray-800'
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
}

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric'
  })
}

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('en-US', {
    minimumFractionDigits: 0,
    maximumFractionDigits: 0
  }).format(amount || 0)
}

// Lifecycle
onMounted(() => {
  if (!props.widgetData) {
    refreshData()
  } else {
    invoices.value = props.widgetData
  }
})
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