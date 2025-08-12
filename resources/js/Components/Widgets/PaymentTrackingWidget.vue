<template>
  <div class="widget-content">
    <div class="widget-header">
      <h3 class="widget-title">{{ props.widgetConfig?.name || 'Payment Tracking' }}</h3>
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
      <!-- Payment Summary -->
      <div class="p-4 border-b border-gray-200">
        <div class="grid grid-cols-2 gap-4">
          <div class="text-center p-3 bg-green-50 rounded-lg">
            <div class="text-xl font-bold text-green-700">${{ formatCurrency(monthlyReceived) }}</div>
            <div class="text-xs text-green-600">This Month</div>
          </div>
          <div class="text-center p-3 bg-yellow-50 rounded-lg">
            <div class="text-xl font-bold text-yellow-700">${{ formatCurrency(pendingAmount) }}</div>
            <div class="text-xs text-yellow-600">Pending</div>
          </div>
        </div>
      </div>

      <!-- Recent Payments -->
      <div class="p-4">
        <h4 class="text-sm font-medium text-gray-900 mb-3">Recent Payments</h4>
        <div v-if="payments.length === 0" class="text-center py-4 text-gray-500">
          <svg class="mx-auto h-8 w-8 text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
          </svg>
          <p class="text-sm">No recent payments</p>
        </div>
        <div v-else class="space-y-2">
          <div 
            v-for="payment in payments" 
            :key="payment.id"
            class="flex items-center justify-between p-2 bg-gray-50 rounded-lg hover:bg-gray-100 cursor-pointer"
            @click="viewPayment(payment.id)"
          >
            <div class="flex-1 min-w-0">
              <div class="text-sm font-medium text-gray-900 truncate">
                {{ payment.invoice?.invoice_number || 'Direct Payment' }}
              </div>
              <div class="text-xs text-gray-500">
                {{ formatDate(payment.payment_date) }} • {{ payment.payment_method }}
              </div>
            </div>
            <div class="text-right">
              <div class="text-sm font-medium text-gray-900">
                ${{ formatCurrency(payment.amount) }}
              </div>
              <div class="text-xs">
                <span :class="getStatusClass(payment.status)" class="px-2 py-1 rounded-full">
                  {{ payment.status }}
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Payment Methods -->
      <div class="p-4 border-t border-gray-200">
        <h4 class="text-sm font-medium text-gray-900 mb-3">Payment Methods</h4>
        <div class="grid grid-cols-2 gap-2 text-xs">
          <div v-for="(count, method) in paymentMethods" :key="method" 
               class="flex justify-between items-center p-2 bg-gray-50 rounded">
            <span class="capitalize text-gray-700">{{ method }}</span>
            <span class="font-medium text-gray-900">{{ count }}</span>
          </div>
        </div>
      </div>

      <!-- Quick Actions -->
      <div class="p-4 border-t border-gray-200 bg-gray-50">
        <div class="flex gap-2">
          <button
            @click="recordPayment"
            class="flex-1 text-xs bg-green-600 text-white px-3 py-2 rounded-md hover:bg-green-700 transition-colors"
          >
            Record Payment
          </button>
          <button
            @click="viewAllPayments"
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
const payments = ref([])
const monthlyReceived = ref(0)
const pendingAmount = ref(0)

// Computed
const paymentMethods = computed(() => {
  const methods = {}
  payments.value.forEach(payment => {
    const method = payment.payment_method || 'other'
    methods[method] = (methods[method] || 0) + 1
  })
  return methods
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
    params.append('sort', 'payment_date:desc')
    
    // Fetch recent payments
    const paymentsResponse = await fetch(`/api/billing/payments?${params}`)
    if (!paymentsResponse.ok) {
      throw new Error('Failed to fetch payments')
    }
    
    const paymentsData = await paymentsResponse.json()
    payments.value = paymentsData.data || []
    
    // Fetch payment summary
    const summaryResponse = await fetch(`/api/billing/payments/summary?${params}`)
    if (summaryResponse.ok) {
      const summaryData = await summaryResponse.json()
      monthlyReceived.value = summaryData.data?.monthly_received || 0
      pendingAmount.value = summaryData.data?.pending_amount || 0
    }
    
  } catch (err) {
    error.value = err.message || 'Failed to load payment data'
    console.error('Error loading payment data:', err)
    
    // Fallback mock data for development
    payments.value = [
      {
        id: 1,
        amount: 2500.00,
        payment_date: '2024-08-10',
        payment_method: 'bank_transfer',
        status: 'completed',
        invoice: { invoice_number: 'INV-2024-001' }
      },
      {
        id: 2,
        amount: 1750.00,
        payment_date: '2024-08-08',
        payment_method: 'credit_card',
        status: 'completed',
        invoice: { invoice_number: 'INV-2024-002' }
      }
    ]
    monthlyReceived.value = 15750.00
    pendingAmount.value = 5200.00
  } finally {
    isLoading.value = false
  }
}

const viewPayment = (paymentId) => {
  router.visit(`/billing/payments/${paymentId}`)
}

const recordPayment = () => {
  router.visit('/billing/payments/create')
}

const viewAllPayments = () => {
  router.visit('/billing?tab=payments')
}

const getStatusClass = (status) => {
  const classes = {
    completed: 'bg-green-100 text-green-800',
    pending: 'bg-yellow-100 text-yellow-800',
    failed: 'bg-red-100 text-red-800',
    refunded: 'bg-gray-100 text-gray-800'
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
    payments.value = props.widgetData
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