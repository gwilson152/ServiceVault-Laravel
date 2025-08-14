<template>
  <div class="space-y-6">
    <!-- Billing Configuration Header -->
    <div>
      <h2 class="text-2xl font-semibold text-gray-900">Billing & Invoicing</h2>
      <p class="text-gray-600 mt-2">Configure billing rates, addon templates, invoice settings, and tax configuration.</p>
    </div>

    <!-- Sub-tabs Navigation -->
    <div class="border-b border-gray-200">
      <nav class="-mb-px flex space-x-8" aria-label="Billing tabs">
        <button
          v-for="tab in billingTabs"
          :key="tab.id"
          @click="activeSubTab = tab.id"
          :class="[
            activeSubTab === tab.id
              ? 'border-indigo-500 text-indigo-600'
              : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
            'whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm flex items-center'
          ]"
        >
          <component :is="tab.icon" class="w-5 h-5 mr-2" />
          {{ tab.name }}
        </button>
      </nav>
    </div>

    <!-- Tab Content -->
    <div class="space-y-6">
      <!-- Billing Rates Tab -->
      <div v-show="activeSubTab === 'rates'">
        <BillingRatesTab />
      </div>

      <!-- Addon Templates Tab -->
      <div v-show="activeSubTab === 'templates'">
        <AddonTemplatesTab />
      </div>

      <!-- Invoice Settings Tab -->
      <div v-show="activeSubTab === 'invoice'">
        <InvoiceSettingsTab />
      </div>
    </div>
  </div>
</template>


<script setup>
import { ref } from 'vue'
import { CurrencyDollarIcon, ClipboardDocumentListIcon, DocumentTextIcon } from '@heroicons/vue/24/outline'
import BillingRatesTab from './BillingTabs/BillingRatesTab.vue'
import AddonTemplatesTab from './BillingTabs/AddonTemplatesTab.vue'
import InvoiceSettingsTab from './BillingTabs/InvoiceSettingsTab.vue'

// Sub-tab configuration
const billingTabs = [
  { id: 'rates', name: 'Billing Rates', icon: CurrencyDollarIcon },
  { id: 'templates', name: 'Addon Templates', icon: ClipboardDocumentListIcon },
  { id: 'invoice', name: 'Invoice Settings', icon: DocumentTextIcon },
]

// Active sub-tab state
const activeSubTab = ref('rates')
</script>