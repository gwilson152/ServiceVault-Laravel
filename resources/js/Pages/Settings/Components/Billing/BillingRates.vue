<template>
  <div class="space-y-8">
    <!-- Billing Rates Header -->
    <div class="flex items-center justify-between">
      <div>
        <h3 class="text-lg font-medium text-gray-900">Billing Rates</h3>
        <p class="text-gray-600 mt-1">Manage global and account-specific hourly rates with inheritance hierarchy</p>
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

    <!-- Rate Hierarchy Info -->
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
      <div class="flex">
        <div class="flex-shrink-0">
          <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a.75.75 0 000 1.5h.253a.25.25 0 01.244.304l-.459 2.066A1.75 1.75 0 0010.747 15H11a.75.75 0 000-1.5h-.253a.25.25 0 01-.244-.304l.459-2.066A1.75 1.75 0 009.253 9H9z" clip-rule="evenodd" />
          </svg>
        </div>
        <div class="ml-3">
          <h4 class="text-sm font-medium text-blue-800">Rate Override Hierarchy</h4>
          <div class="mt-2 text-sm text-blue-700">
            <p class="mb-2">Billing rates follow a simple hierarchy (highest to lowest priority):</p>
            <ol class="list-decimal list-inside space-y-1 ml-2">
              <li><strong>Account-specific rates</strong> - Rates set for individual accounts (created here or on account detail pages)</li>
              <li><strong>Global rates</strong> - Default rates used when no account-specific overrides exist</li>
            </ol>
            <p class="mt-2 text-xs">Account rates automatically inherit to child accounts unless specifically overridden.</p>
          </div>
        </div>
      </div>
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
            <div class="flex items-center space-x-3">
              <h4 class="text-sm font-medium text-gray-900">{{ rate.name }}</h4>
              <span v-if="rate.is_default" :class="[
                'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                rate.account ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800'
              ]">
                {{ rate.account ? 'Account Default' : 'Global Default' }}
              </span>
              <span :class="[
                'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                rate.account ? 'bg-blue-50 text-blue-700 border border-blue-200' : 'bg-green-50 text-green-700 border border-green-200'
              ]">
                {{ rate.account ? 'Account Rate' : 'Global Rate' }}
              </span>
            </div>
            <p v-if="rate.description" class="text-xs text-gray-500 mt-1">{{ rate.description }}</p>
            <div class="text-xs text-gray-500 mt-1 space-y-0.5">
              <div v-if="rate.account" class="flex items-center">
                <svg class="w-3 h-3 mr-1 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v8a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2H4zm2 6a2 2 0 11-4 0 2 2 0 014 0zm8 0a2 2 0 11-4 0 2 2 0 014 0z" clip-rule="evenodd" />
                </svg>
                Account: {{ rate.account.name }}
              </div>
              <div v-else class="flex items-center">
                <svg class="w-3 h-3 mr-1 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z" clip-rule="evenodd" />
                </svg>
                Available globally to all users and accounts
              </div>
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
      mode="global"
      @close="closeModal"
      @saved="handleRateSaved"
    />
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useBillingRatesQuery, useDeleteBillingRateMutation } from '@/Composables/queries/useBillingQuery'
import BillingRateModal from '@/Components/Billing/BillingRateModal.vue'

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