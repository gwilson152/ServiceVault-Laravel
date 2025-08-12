<template>
  <div class="widget-content">
    <div class="widget-header">
      <h3 class="widget-title">{{ props.widgetConfig?.name || 'Billing Rates' }}</h3>
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
      <!-- Rate Summary -->
      <div class="p-4 border-b border-gray-200">
        <div class="grid grid-cols-3 gap-2 text-center">
          <div class="bg-blue-50 rounded-lg p-2">
            <div class="text-lg font-semibold text-blue-700">{{ activeRates.length }}</div>
            <div class="text-xs text-blue-600">Active</div>
          </div>
          <div class="bg-gray-50 rounded-lg p-2">
            <div class="text-lg font-semibold text-gray-700">${{ averageRate }}</div>
            <div class="text-xs text-gray-600">Avg Rate</div>
          </div>
          <div class="bg-green-50 rounded-lg p-2">
            <div class="text-lg font-semibold text-green-700">${{ highestRate }}</div>
            <div class="text-xs text-green-600">Highest</div>
          </div>
        </div>
      </div>

      <!-- Billing Rates List -->
      <div class="p-4">
        <h4 class="text-sm font-medium text-gray-900 mb-3">Active Rates</h4>
        <div v-if="rates.length === 0" class="text-center py-4 text-gray-500">
          <svg class="mx-auto h-8 w-8 text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
          </svg>
          <p class="text-sm">No billing rates configured</p>
        </div>
        <div v-else class="space-y-2 max-h-48 overflow-y-auto">
          <div 
            v-for="rate in rates" 
            :key="rate.id"
            class="flex items-center justify-between p-2 bg-gray-50 rounded-lg hover:bg-gray-100 cursor-pointer"
            @click="editRate(rate.id)"
          >
            <div class="flex-1 min-w-0">
              <div class="text-sm font-medium text-gray-900 truncate">
                {{ rate.name }}
              </div>
              <div class="text-xs text-gray-500">
                <span v-if="rate.account">{{ rate.account.name }}</span>
                <span v-else-if="rate.user">{{ rate.user.name }}</span>
                <span v-else>Global Rate</span>
              </div>
            </div>
            <div class="text-right">
              <div class="text-sm font-medium text-gray-900">
                ${{ formatCurrency(rate.rate) }}/hr
              </div>
              <div class="text-xs">
                <span :class="rate.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'" 
                      class="px-2 py-1 rounded-full">
                  {{ rate.is_active ? 'Active' : 'Inactive' }}
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Rate Categories -->
      <div v-if="rateCounts.length > 0" class="p-4 border-t border-gray-200">
        <h4 class="text-sm font-medium text-gray-900 mb-3">Rate Distribution</h4>
        <div class="space-y-1">
          <div v-for="category in rateCounts" :key="category.range" 
               class="flex justify-between items-center text-xs">
            <span class="text-gray-600">{{ category.range }}</span>
            <div class="flex items-center">
              <div class="w-16 bg-gray-200 rounded-full h-2 mr-2">
                <div class="bg-indigo-600 h-2 rounded-full" 
                     :style="{ width: category.percentage + '%' }"></div>
              </div>
              <span class="font-medium text-gray-900 w-6 text-right">{{ category.count }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Quick Actions -->
      <div class="p-4 border-t border-gray-200 bg-gray-50">
        <div class="flex gap-2">
          <button
            @click="createRate"
            class="flex-1 text-xs bg-indigo-600 text-white px-3 py-2 rounded-md hover:bg-indigo-700 transition-colors"
          >
            New Rate
          </button>
          <button
            @click="viewSettings"
            class="flex-1 text-xs bg-gray-600 text-white px-3 py-2 rounded-md hover:bg-gray-700 transition-colors"
          >
            Settings
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
const rates = ref([])

// Computed
const activeRates = computed(() => {
  return rates.value.filter(rate => rate.is_active)
})

const averageRate = computed(() => {
  if (activeRates.value.length === 0) return 0
  const sum = activeRates.value.reduce((acc, rate) => acc + rate.rate, 0)
  return Math.round(sum / activeRates.value.length)
})

const highestRate = computed(() => {
  if (activeRates.value.length === 0) return 0
  return Math.max(...activeRates.value.map(rate => rate.rate))
})

const rateCounts = computed(() => {
  if (activeRates.value.length === 0) return []
  
  const ranges = [
    { min: 0, max: 50, range: '$0-50' },
    { min: 51, max: 100, range: '$51-100' },
    { min: 101, max: 150, range: '$101-150' },
    { min: 151, max: 999999, range: '$151+' }
  ]
  
  const counts = ranges.map(range => {
    const count = activeRates.value.filter(rate => 
      rate.rate >= range.min && rate.rate <= range.max
    ).length
    return {
      ...range,
      count,
      percentage: Math.round((count / activeRates.value.length) * 100)
    }
  }).filter(range => range.count > 0)
  
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
    params.append('active_only', 'true')
    params.append('with', 'account,user')
    
    const response = await fetch(`/api/billing/rates?${params}`)
    if (!response.ok) {
      throw new Error('Failed to fetch billing rates')
    }
    
    const data = await response.json()
    rates.value = data.data || []
    
  } catch (err) {
    error.value = err.message || 'Failed to load billing rates'
    console.error('Error loading billing rates:', err)
    
    // Fallback mock data for development
    rates.value = [
      {
        id: 1,
        name: 'Standard Development',
        rate: 85.00,
        is_active: true,
        account: null,
        user: null
      },
      {
        id: 2,
        name: 'Senior Consulting',
        rate: 125.00,
        is_active: true,
        account: null,
        user: { name: 'John Smith' }
      },
      {
        id: 3,
        name: 'Project Management',
        rate: 95.00,
        is_active: true,
        account: { name: 'Acme Corp' },
        user: null
      }
    ]
  } finally {
    isLoading.value = false
  }
}

const editRate = (rateId) => {
  router.visit(`/billing/rates/${rateId}/edit`)
}

const createRate = () => {
  router.visit('/billing/rates/create')
}

const viewSettings = () => {
  router.visit('/settings/billing')
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
    rates.value = props.widgetData
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