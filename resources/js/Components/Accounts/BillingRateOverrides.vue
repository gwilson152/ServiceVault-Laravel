<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h3 class="text-lg font-medium text-gray-900">Billing Rate Overrides</h3>
        <p class="text-sm text-gray-600">Manage account-specific billing rates and default selections</p>
      </div>
      <button
        @click="showCreateModal = true"
        class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
      >
        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
        </svg>
        Add Rate Override
      </button>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex justify-center py-8">
      <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="text-center py-8">
      <p class="text-red-600">{{ error }}</p>
      <button @click="loadBillingRates()" class="mt-2 text-indigo-600 hover:text-indigo-500">
        Try again
      </button>
    </div>

    <!-- Billing Rates List -->
    <div v-else class="space-y-4">
      <!-- Account-Specific Rates -->
      <div v-if="accountRates.length > 0">
        <h4 class="text-sm font-semibold text-gray-900 mb-3 flex items-center">
          <svg class="w-4 h-4 mr-2 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-8.293l-3-3a1 1 0 00-1.414 0l-3 3a1 1 0 001.414 1.414L9 9.414V13a1 1 0 102 0V9.414l1.293 1.293a1 1 0 001.414-1.414z" clip-rule="evenodd" />
          </svg>
          Account-Specific Rates
        </h4>
        <div class="bg-white border border-gray-200 rounded-lg divide-y divide-gray-200">
          <div
            v-for="rate in accountRates"
            :key="rate.id"
            class="p-4 flex items-center justify-between hover:bg-gray-50"
          >
            <div class="flex-1">
              <div class="flex items-center space-x-3">
                <h5 class="text-sm font-medium text-gray-900">{{ rate.name }}</h5>
                <span v-if="rate.is_default" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                  Account Default
                </span>
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

      <!-- Inherited Rates -->
      <div v-if="inheritedRates.length > 0">
        <h4 class="text-sm font-semibold text-gray-900 mb-3 flex items-center">
          <svg class="w-4 h-4 mr-2 text-purple-600" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M12.316 3.051a1 1 0 01.633 1.265l-4 12a1 1 0 11-1.898-.632l4-12a1 1 0 011.265-.633zM5.707 6.293a1 1 0 010 1.414L3.414 10l2.293 2.293a1 1 0 11-1.414 1.414l-3-3a1 1 0 010-1.414l3-3a1 1 0 011.414 0zm8.586 0a1 1 0 011.414 0l3 3a1 1 0 010 1.414l-3 3a1 1 0 11-1.414-1.414L16.586 10l-2.293-2.293a1 1 0 010-1.414z" clip-rule="evenodd" />
          </svg>
          Inherited Rates
        </h4>
        <div class="bg-gray-50 border border-gray-200 rounded-lg divide-y divide-gray-200">
          <div
            v-for="rate in inheritedRates"
            :key="`inherited-${rate.id}`"
            class="p-4 flex items-center justify-between"
          >
            <div class="flex-1">
              <div class="flex items-center space-x-3">
                <h5 class="text-sm font-medium text-gray-700">{{ rate.name }}</h5>
                <span v-if="rate.is_default" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                  {{ rate.inheritance_source === 'global' ? 'Global Default' : 'Parent Default' }}
                </span>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700">
                  {{ rate.inheritance_source === 'global' ? 'Global' : 'Inherited' }}
                </span>
              </div>
              <div class="text-sm text-gray-600 mt-1 space-y-1">
                <div v-if="rate.description">{{ rate.description }}</div>
                <div v-if="rate.inherited_from_account" class="italic">
                  Inherited from {{ rate.inherited_from_account }}
                </div>
              </div>
            </div>
            <div class="flex items-center space-x-4">
              <div class="text-right">
                <div class="text-lg font-medium text-gray-700">${{ rate.rate }}</div>
                <div class="text-xs text-gray-500">per hour</div>
              </div>
              <button
                @click="overrideRate(rate)"
                class="text-indigo-600 hover:text-indigo-900 text-sm font-medium"
                title="Create account-specific override"
              >
                Override
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-if="accountRates.length === 0 && inheritedRates.length === 0" class="text-center py-12 bg-white border border-gray-200 rounded-lg">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>
        </svg>
        <h3 class="mt-2 text-sm font-medium text-gray-900">No billing rates configured</h3>
        <p class="mt-1 text-sm text-gray-500">Get started by creating your first account-specific billing rate.</p>
        <div class="mt-6">
          <button
            @click="showCreateModal = true"
            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700"
          >
            Add Rate Override
          </button>
        </div>
      </div>
    </div>

    <!-- Create/Edit Modal -->
    <BillingRateModal
      :show="showCreateModal"
      :rate="selectedRate"
      :account-id="accountId"
      @close="closeModal"
      @saved="handleRateSaved"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import BillingRateModal from '@/Components/Settings/BillingRateModal.vue'
import axios from 'axios'

// Props
const props = defineProps({
  accountId: {
    type: [String, Number],
    required: true
  }
})

// State
const loading = ref(false)
const error = ref(null)
const billingRates = ref([])
const showCreateModal = ref(false)
const selectedRate = ref(null)

// Computed
const accountRates = computed(() => {
  return billingRates.value.filter(rate => rate.inheritance_source === 'account')
})

const inheritedRates = computed(() => {
  return billingRates.value.filter(rate => ['parent', 'global'].includes(rate.inheritance_source))
})

// Methods
const loadBillingRates = async () => {
  loading.value = true
  error.value = null
  
  try {
    const response = await axios.get('/api/billing-rates', {
      params: { account_id: props.accountId }
    })
    billingRates.value = response.data.data || []
  } catch (err) {
    console.error('Failed to load billing rates:', err)
    error.value = 'Failed to load billing rates. Please try again.'
  } finally {
    loading.value = false
  }
}

const editRate = (rate) => {
  selectedRate.value = rate
  showCreateModal.value = true
}

const overrideRate = (rate) => {
  // Create a new account-specific rate based on the inherited rate
  selectedRate.value = {
    name: rate.name,
    rate: rate.rate,
    description: `Account override for ${rate.name}`,
    is_active: true,
    is_default: false
  }
  showCreateModal.value = true
}

const deleteRate = async (rate) => {
  if (!confirm(`Are you sure you want to delete the billing rate "${rate.name}"?`)) return
  
  try {
    await axios.delete(`/api/billing-rates/${rate.id}`)
    await loadBillingRates() // Reload the list
  } catch (err) {
    console.error('Failed to delete billing rate:', err)
    alert('Failed to delete billing rate. Please try again.')
  }
}

const closeModal = () => {
  showCreateModal.value = false
  selectedRate.value = null
}

const handleRateSaved = () => {
  closeModal()
  loadBillingRates() // Reload the list
}

// Lifecycle
onMounted(() => {
  loadBillingRates()
})
</script>