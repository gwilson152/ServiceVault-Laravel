<template>
  <div class="space-y-6">
    <!-- Revenue Chart -->
    <div class="bg-white shadow rounded-lg">
      <div class="px-6 py-4 border-b border-gray-200">
        <h3 class="text-lg font-medium text-gray-900">Revenue Overview</h3>
      </div>
      <div class="p-6">
        <div class="text-center text-gray-500">
          <ChartBarIcon class="mx-auto h-12 w-12 text-gray-400" />
          <h3 class="mt-2 text-sm font-medium text-gray-900">Revenue Analytics</h3>
          <p class="mt-1 text-sm text-gray-500">Charts and analytics will be displayed here.</p>
        </div>
      </div>
    </div>

    <!-- Quick Stats -->
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
      <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <CurrencyDollarIcon class="h-6 w-6 text-green-600" />
            </div>
            <div class="ml-5 w-0 flex-1">
              <dl>
                <dt class="text-sm font-medium text-gray-500 truncate">Monthly Revenue</dt>
                <dd class="text-lg font-medium text-gray-900">
                  ${{ formatCurrency(stats.monthly_revenue || 0) }}
                </dd>
              </dl>
            </div>
          </div>
        </div>
      </div>

      <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <DocumentIcon class="h-6 w-6 text-blue-600" />
            </div>
            <div class="ml-5 w-0 flex-1">
              <dl>
                <dt class="text-sm font-medium text-gray-500 truncate">Avg Invoice Value</dt>
                <dd class="text-lg font-medium text-gray-900">
                  ${{ formatCurrency(stats.average_invoice_value || 0) }}
                </dd>
              </dl>
            </div>
          </div>
        </div>
      </div>

      <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <ClockIcon class="h-6 w-6 text-orange-600" />
            </div>
            <div class="ml-5 w-0 flex-1">
              <dl>
                <dt class="text-sm font-medium text-gray-500 truncate">Avg Collection Time</dt>
                <dd class="text-lg font-medium text-gray-900">
                  {{ Math.round(stats.average_collection_days || 0) }} days
                </dd>
              </dl>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { 
  ChartBarIcon, 
  CurrencyDollarIcon, 
  DocumentIcon, 
  ClockIcon 
} from '@heroicons/vue/24/outline'

defineProps({
  stats: {
    type: Object,
    default: () => ({})
  }
})

defineEmits(['refresh'])

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('en-US', {
    minimumFractionDigits: 0,
    maximumFractionDigits: 0
  }).format(amount || 0)
}
</script>