<template>
  <div class="space-y-8">
    <!-- Billing Configuration Header -->
    <div>
      <h2 class="text-2xl font-semibold text-gray-900">Billing & Invoicing</h2>
      <p class="text-gray-600 mt-2">Configure company information, billing rates, and addon templates.</p>
    </div>

    <!-- Sub-Tab Navigation -->
    <div class="border-b border-gray-200">
      <nav class="-mb-px flex space-x-8" aria-label="Billing Tabs">
        <button
          v-for="tab in billingTabs"
          :key="tab.id"
          @click="activeSubTab = tab.id"
          :class="[
            activeSubTab === tab.id
              ? 'border-indigo-500 text-indigo-600'
              : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
            'whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm flex items-center',
          ]"
        >
          <component
            :is="tab.icon"
            class="w-5 h-5 mr-2"
          />
          {{ tab.name }}
        </button>
      </nav>
    </div>

    <!-- Sub-Tab Content -->
    <div class="mt-8">
      <!-- Billing Rates Tab -->
      <div v-show="activeSubTab === 'rates'" class="space-y-8">
        <BillingRates />
      </div>

      <!-- Addon Templates Tab -->
      <div v-show="activeSubTab === 'templates'" class="space-y-8">
        <AddonTemplates />
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import {
  CurrencyDollarIcon,
  CubeIcon,
} from '@heroicons/vue/24/outline'
import BillingRates from './Billing/BillingRates.vue'
import AddonTemplates from './Billing/AddonTemplates.vue'

// Sub-tab configuration
const billingTabs = [
  { id: 'rates', name: 'Billing Rates', icon: CurrencyDollarIcon },
  { id: 'templates', name: 'Addon Templates', icon: CubeIcon },
]

// Active sub-tab state
const activeSubTab = ref('rates')

// Define props interface for compatibility
defineProps({
  config: {
    type: Object,
    default: () => ({})
  },
  loading: {
    type: Boolean,
    default: false
  }
})

// Define emits for compatibility
defineEmits(['refresh'])
</script>