<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h3 class="text-lg font-medium text-gray-900">Billing Rates</h3>
        <p class="text-sm text-gray-600">Configure hourly billing rates for users, accounts, or global defaults.</p>
      </div>
      <button
        @click="showCreateModal = true"
        class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
      >
        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
        </svg>
        Add Billing Rate
      </button>
    </div>

    <!-- Loading State -->
    <div v-if="billingRatesQuery.isLoading.value" class="flex justify-center py-8">
      <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
    </div>

    <!-- Error State -->
    <div v-else-if="billingRatesQuery.isError.value" class="text-center py-8">
      <p class="text-red-600">Failed to load billing rates</p>
      <button @click="billingRatesQuery.refetch()" class="mt-2 text-indigo-600 hover:text-indigo-500">
        Try again
      </button>
    </div>

    <!-- Billing Rates List -->
    <div v-else-if="billingRates.length > 0" class="bg-white border border-gray-200 rounded-lg">
      <div class="px-6 py-4 border-b border-gray-200">
        <h4 class="text-sm font-medium text-gray-900">Active Billing Rates</h4>
      </div>
      <div class="divide-y divide-gray-200">
        <div
          v-for="rate in billingRates"
          :key="rate.id"
          class="px-6 py-4 flex items-center justify-between hover:bg-gray-50"
        >
          <div class="flex-1">
            <div class="flex items-center space-x-3">
              <h5 class="text-sm font-medium text-gray-900">{{ rate.name }}</h5>
              <span :class="[
                'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                rate.is_active 
                  ? 'bg-green-100 text-green-800' 
                  : 'bg-gray-100 text-gray-800'
              ]">
                {{ rate.is_active ? 'Active' : 'Inactive' }}
              </span>
            </div>
            <p v-if="rate.description" class="text-sm text-gray-600 mt-1">{{ rate.description }}</p>
            <div class="text-xs text-gray-500 mt-1">
              <span v-if="rate.account">Account: {{ rate.account.name }}</span>
              <span v-else-if="rate.user">User: {{ rate.user.name }}</span>
              <span v-else>Global Rate</span>
            </div>
          </div>
          <div class="flex items-center space-x-4">
            <div class="text-right">
              <div class="text-lg font-semibold text-gray-900">${{ rate.rate }}</div>
              <div class="text-xs text-gray-500">per hour</div>
            </div>
            <div class="flex items-center space-x-2">
              <button
                @click="editRate(rate)"
                class="text-indigo-600 hover:text-indigo-900 text-sm font-medium"
              >
                Edit
              </button>
              <button
                @click="deleteRate(rate)"
                class="text-red-600 hover:text-red-900 text-sm font-medium"
              >
                Delete
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else class="text-center py-12 bg-white border border-gray-200 rounded-lg">
      <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>
      </svg>
      <h3 class="mt-2 text-sm font-medium text-gray-900">No billing rates</h3>
      <p class="mt-1 text-sm text-gray-500">Get started by creating your first billing rate.</p>
      <div class="mt-6">
        <button
          @click="showCreateModal = true"
          class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700"
        >
          Add Billing Rate
        </button>
      </div>
    </div>

    <!-- Create/Edit Modal -->
    <BillingRateModal
      :show="showCreateModal"
      :rate="selectedRate"
      @close="closeModal"
    />
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useBillingRatesQuery, useDeleteBillingRateMutation } from '@/Composables/queries/useBillingQuery'
import BillingRateModal from './BillingRateModal.vue'

// TanStack Query hooks
const billingRatesQuery = useBillingRatesQuery()
const deleteRateMutation = useDeleteBillingRateMutation()

// Local state
const showCreateModal = ref(false)
const selectedRate = ref(null)

// Computed
const billingRates = computed(() => billingRatesQuery.data.value || [])

// Methods
const editRate = (rate) => {
  selectedRate.value = rate
  showCreateModal.value = true
}

const deleteRate = async (rate) => {
  if (!confirm(`Are you sure you want to delete the billing rate "${rate.name}"?`)) return
  
  try {
    await deleteRateMutation.mutateAsync(rate.id)
  } catch (error) {
    console.error('Failed to delete billing rate:', error)
    alert('Failed to delete billing rate. Please try again.')
  }
}

const closeModal = () => {
  showCreateModal.value = false
  selectedRate.value = null
}
</script>