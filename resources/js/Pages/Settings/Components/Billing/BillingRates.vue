<template>
  <div class="space-y-8">
    <!-- Billing Rates Header -->
    <div class="flex items-center justify-between">
      <div>
        <h3 class="text-lg font-medium text-gray-900">Billing Rates</h3>
        <p class="text-gray-600 mt-1">Manage hourly rates for different users and accounts</p>
      </div>
      <button
        @click="showCreateModal = true"
        type="button"
        class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-indigo-700 bg-indigo-100 hover:bg-indigo-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
      >
        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
        </svg>
        Add New Rate
      </button>
    </div>

    <!-- Loading State -->
    <div v-if="isLoading" class="flex justify-center py-8">
      <svg class="animate-spin h-8 w-8 text-indigo-600" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
      </svg>
    </div>

    <!-- Billing Rates List -->
    <div v-else-if="billingRates && billingRates.length > 0" class="bg-white border border-gray-200 rounded-lg">
      <div class="space-y-0 divide-y divide-gray-200">
        <div 
          v-for="rate in billingRates" 
          :key="rate.id"
          class="flex items-center justify-between p-6 hover:bg-gray-50"
        >
          <div class="flex-1">
            <h4 class="text-sm font-medium text-gray-900">{{ rate.name }}</h4>
            <p class="text-xs text-gray-500 mt-1">{{ rate.description }}</p>
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
            <div>
              <span :class="[
                'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                rate.is_active 
                  ? 'bg-green-100 text-green-800' 
                  : 'bg-gray-100 text-gray-800'
              ]">
                {{ rate.is_active ? 'Active' : 'Inactive' }}
              </span>
            </div>
            <div class="flex items-center space-x-2">
              <button
                @click="editRate(rate)"
                class="inline-flex items-center px-2 py-1 text-xs font-medium text-indigo-700 bg-indigo-100 rounded hover:bg-indigo-200"
              >
                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
                Edit
              </button>
              <button
                @click="deleteRate(rate)"
                class="inline-flex items-center px-2 py-1 text-xs font-medium text-red-700 bg-red-100 rounded hover:bg-red-200"
              >
                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
                Delete
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Empty State -->
    <div v-else class="bg-white border border-gray-200 rounded-lg p-12 text-center">
      <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>
      </svg>
      <h3 class="mt-2 text-sm font-medium text-gray-900">No billing rates configured</h3>
      <p class="mt-1 text-sm text-gray-500">Get started by creating your first billing rate.</p>
      <div class="mt-6">
        <button
          @click="showCreateModal = true"
          class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
        >
          <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
          </svg>
          Create Billing Rate
        </button>
      </div>
    </div>

    <!-- Create/Edit Modal -->
    <BillingRateModal
      :show="showCreateModal || showEditModal"
      :rate="selectedRate"
      @close="closeModal"
      @saved="handleRateSaved"
    />
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useBillingRatesQuery, useDeleteBillingRateMutation } from '@/Composables/queries/useBillingQuery'
import BillingRateModal from './BillingRateModal.vue'

// TanStack Query hooks
const { data: billingRates, isLoading } = useBillingRatesQuery()
const deleteRateMutation = useDeleteBillingRateMutation()

// Modal state
const showCreateModal = ref(false)
const showEditModal = ref(false)
const selectedRate = ref(null)

// Edit rate
const editRate = (rate) => {
  selectedRate.value = rate
  showEditModal.value = true
}

// Delete rate
const deleteRate = async (rate) => {
  if (!confirm(`Are you sure you want to delete the billing rate "${rate.name}"?`)) return
  
  try {
    await deleteRateMutation.mutateAsync(rate.id)
    console.log('Billing rate deleted successfully')
  } catch (error) {
    console.error('Failed to delete billing rate:', error)
    alert('Failed to delete billing rate. Please try again.')
  }
}

// Close modal
const closeModal = () => {
  showCreateModal.value = false
  showEditModal.value = false
  selectedRate.value = null
}

// Handle rate saved
const handleRateSaved = () => {
  closeModal()
}
</script>