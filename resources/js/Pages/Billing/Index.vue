<template>
  <AppLayout title="Billing Management">
    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        
        <!-- Page Header -->
        <div class="mb-8">
          <div class="md:flex md:items-center md:justify-between">
            <div class="min-w-0 flex-1">
              <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:truncate sm:text-3xl sm:tracking-tight">
                Billing Management
              </h2>
              <p class="mt-1 text-sm text-gray-500">
                Manage invoices, payments, and billing configuration
              </p>
            </div>
            <div class="mt-4 flex md:ml-4 md:mt-0">
              <button
                @click="showCreateInvoiceModal = true"
                type="button"
                class="ml-3 inline-flex items-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
              >
                <PlusIcon class="-ml-0.5 mr-1.5 h-5 w-5" aria-hidden="true" />
                Create Invoice
              </button>
            </div>
          </div>
        </div>

        <!-- Stats Overview -->
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4 mb-8">
          <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
              <div class="flex items-center">
                <div class="flex-shrink-0">
                  <CurrencyDollarIcon class="h-6 w-6 text-green-600" aria-hidden="true" />
                </div>
                <div class="ml-5 w-0 flex-1">
                  <dl>
                    <dt class="text-sm font-medium text-gray-500 truncate">Total Revenue</dt>
                    <dd class="flex items-baseline">
                      <div class="text-2xl font-semibold text-gray-900">
                        ${{ formatCurrency(billingStats.total_revenue) }}
                      </div>
                      <div class="ml-2 flex items-baseline text-sm font-semibold text-green-600">
                        <ArrowUpIcon class="self-center flex-shrink-0 h-3 w-3" aria-hidden="true" />
                        <span class="sr-only"> Increased by </span>
                        12%
                      </div>
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
                  <ClockIcon class="h-6 w-6 text-orange-600" aria-hidden="true" />
                </div>
                <div class="ml-5 w-0 flex-1">
                  <dl>
                    <dt class="text-sm font-medium text-gray-500 truncate">Outstanding</dt>
                    <dd class="flex items-baseline">
                      <div class="text-2xl font-semibold text-gray-900">
                        ${{ formatCurrency(billingStats.outstanding_amount) }}
                      </div>
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
                  <DocumentIcon class="h-6 w-6 text-blue-600" aria-hidden="true" />
                </div>
                <div class="ml-5 w-0 flex-1">
                  <dl>
                    <dt class="text-sm font-medium text-gray-500 truncate">Invoices This Month</dt>
                    <dd class="flex items-baseline">
                      <div class="text-2xl font-semibold text-gray-900">
                        {{ billingStats.invoices_this_month }}
                      </div>
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
                  <CheckCircleIcon class="h-6 w-6 text-green-600" aria-hidden="true" />
                </div>
                <div class="ml-5 w-0 flex-1">
                  <dl>
                    <dt class="text-sm font-medium text-gray-500 truncate">Collection Rate</dt>
                    <dd class="flex items-baseline">
                      <div class="text-2xl font-semibold text-gray-900">
                        {{ Math.round(billingStats.collection_rate) }}%
                      </div>
                    </dd>
                  </dl>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Tab Navigation -->
        <div class="border-b border-gray-200 mb-6">
          <nav class="-mb-px flex space-x-8" aria-label="Tabs">
            <button
              v-for="tab in tabs"
              :key="tab.key"
              @click="activeTab = tab.key"
              :class="[
                activeTab === tab.key
                  ? 'border-indigo-500 text-indigo-600'
                  : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700',
                'whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium'
              ]"
            >
              <component :is="tab.icon" class="h-5 w-5 mr-2 inline" />
              {{ tab.label }}
            </button>
          </nav>
        </div>

        <!-- Tab Content -->
        <div class="mt-6">
          <!-- Invoices Tab -->
          <div v-if="activeTab === 'invoices'">
            <InvoicesTable 
              :invoices="invoices" 
              :loading="loading"
              @refresh="loadInvoices"
              @view-invoice="viewInvoice"
              @send-invoice="sendInvoice"
              @mark-paid="markInvoicePaid"
            />
          </div>

          <!-- Payments Tab -->
          <div v-if="activeTab === 'payments'">
            <PaymentsTable 
              :payments="payments" 
              :loading="loading"
              @refresh="loadPayments"
            />
          </div>

          <!-- Reports Tab -->
          <div v-if="activeTab === 'reports'">
            <BillingReports 
              :stats="billingStats"
              @refresh="loadBillingStats"
            />
          </div>

          <!-- Settings Tab -->
          <div v-if="activeTab === 'settings'">
            <BillingSettings 
              :settings="billingSettings"
              @update="updateBillingSettings"
            />
          </div>
        </div>
      </div>
    </div>

    <!-- Create Invoice Modal -->
    <CreateInvoiceModal
      :show="showCreateInvoiceModal"
      @close="showCreateInvoiceModal = false"
      @created="handleInvoiceCreated"
    />

    <!-- View Invoice Modal -->
    <ViewInvoiceModal
      :show="showViewInvoiceModal"
      :invoice="selectedInvoice"
      @close="showViewInvoiceModal = false"
    />
  </AppLayout>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import InvoicesTable from '@/Components/Billing/InvoicesTable.vue'
import PaymentsTable from '@/Components/Billing/PaymentsTable.vue'
import BillingReports from '@/Components/Billing/BillingReports.vue'
import BillingSettings from '@/Components/Billing/BillingSettings.vue'
import CreateInvoiceModal from '@/Components/Billing/CreateInvoiceModal.vue'
import ViewInvoiceModal from '@/Components/Billing/ViewInvoiceModal.vue'
import {
  PlusIcon,
  CurrencyDollarIcon,
  ClockIcon,
  DocumentIcon,
  CheckCircleIcon,
  ArrowUpIcon,
  DocumentTextIcon,
  CreditCardIcon,
  ChartBarIcon,
  CogIcon
} from '@heroicons/vue/24/outline'

// Reactive data
const activeTab = ref('invoices')
const loading = ref(false)
const invoices = ref([])
const payments = ref([])
const billingStats = ref({})
const billingSettings = ref({})
const showCreateInvoiceModal = ref(false)
const showViewInvoiceModal = ref(false)
const selectedInvoice = ref(null)

// Tab configuration
const tabs = [
  { key: 'invoices', label: 'Invoices', icon: DocumentTextIcon },
  { key: 'payments', label: 'Payments', icon: CreditCardIcon },
  { key: 'reports', label: 'Reports', icon: ChartBarIcon },
  { key: 'settings', label: 'Settings', icon: CogIcon }
]

// Methods
const loadInvoices = async () => {
  loading.value = true
  try {
    const response = await fetch('/api/billing/invoices')
    const data = await response.json()
    invoices.value = data.data
  } catch (error) {
    console.error('Error loading invoices:', error)
  } finally {
    loading.value = false
  }
}

const loadPayments = async () => {
  loading.value = true
  try {
    const response = await fetch('/api/billing/payments')
    const data = await response.json()
    payments.value = data.data
  } catch (error) {
    console.error('Error loading payments:', error)
  } finally {
    loading.value = false
  }
}

const loadBillingStats = async () => {
  try {
    const response = await fetch('/api/billing/reports/dashboard')
    const data = await response.json()
    billingStats.value = data.data
  } catch (error) {
    console.error('Error loading billing stats:', error)
  }
}

const loadBillingSettings = async () => {
  try {
    const response = await fetch('/api/billing/billing-settings')
    const data = await response.json()
    billingSettings.value = data.data?.[0] || {}
  } catch (error) {
    console.error('Error loading billing settings:', error)
  }
}

const viewInvoice = (invoice) => {
  selectedInvoice.value = invoice
  showViewInvoiceModal.value = true
}

const sendInvoice = async (invoice) => {
  try {
    await fetch(`/api/billing/invoices/${invoice.id}/send`, { method: 'POST' })
    loadInvoices()
  } catch (error) {
    console.error('Error sending invoice:', error)
  }
}

const markInvoicePaid = async (invoice) => {
  try {
    await fetch(`/api/billing/invoices/${invoice.id}/mark-paid`, { method: 'POST' })
    loadInvoices()
    loadBillingStats()
  } catch (error) {
    console.error('Error marking invoice as paid:', error)
  }
}

const handleInvoiceCreated = (invoice) => {
  showCreateInvoiceModal.value = false
  loadInvoices()
  loadBillingStats()
}

const updateBillingSettings = async (settings) => {
  try {
    const response = await fetch('/api/billing/billing-settings', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
      },
      body: JSON.stringify(settings)
    })
    
    if (response.ok) {
      loadBillingSettings()
    }
  } catch (error) {
    console.error('Error updating billing settings:', error)
  }
}

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('en-US', {
    minimumFractionDigits: 0,
    maximumFractionDigits: 0
  }).format(amount || 0)
}

// Initialize data
onMounted(() => {
  loadInvoices()
  loadBillingStats()
  loadBillingSettings()
})
</script>