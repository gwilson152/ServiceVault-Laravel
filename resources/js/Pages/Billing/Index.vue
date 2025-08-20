<template>
  <div>
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
                        ${{ formatCurrency(billingStats?.total_revenue || 0) }}
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
                        ${{ formatCurrency(billingStats?.outstanding_amount || 0) }}
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
                        {{ billingStats?.invoices_this_month || 0 }}
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
                        {{ Math.round(billingStats?.collection_rate || 0) }}%
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
              :loading="invoicesLoading"
              @refresh="() => {}"
              @view-invoice="viewInvoice"
              @mark-paid="handleMarkInvoicePaid"
              @delete-invoice="handleDeleteInvoice"
            />
          </div>

          <!-- Payments Tab -->
          <div v-if="activeTab === 'payments'">
            <PaymentsTable 
              :payments="payments" 
              :loading="loading"
              @refresh="() => {}"
            />
          </div>

          <!-- Reports Tab -->
          <div v-if="activeTab === 'reports'">
            <BillingReports 
              :stats="billingStats"
              @refresh="() => {}"
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
      @launchApprovalWizard="handleLaunchApprovalWizard"
    />

    <!-- View Invoice Modal -->
    <ViewInvoiceModal
      :show="showViewInvoiceModal"
      :invoice="selectedInvoice"
      @close="showViewInvoiceModal = false"
    />
    
    <!-- Approval Wizard Modal -->
    <ApprovalWizardModal
      :show="showApprovalWizard"
      :account-id="approvalWizardData.accountId"
      :account-name="approvalWizardData.accountName"
      @close="closeApprovalWizard"
      @completed="onApprovalWizardCompleted"
      @createInvoice="onCreateInvoiceFromApproval"
    />
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import {
  useBillingDashboardStatsQuery,
  useBillingSettingsQuery,
  useInvoicesQuery,
  useInvoiceActionsMutations
} from '@/Composables/queries/useBillingQuery.js'

// Define persistent layout
defineOptions({
  layout: AppLayout
})

import InvoicesTable from '@/Components/Billing/InvoicesTable.vue'
import PaymentsTable from '@/Components/Billing/PaymentsTable.vue'
import BillingReports from '@/Components/Billing/BillingReports.vue'
import BillingSettings from '@/Components/Billing/BillingSettings.vue'
import CreateInvoiceModal from '@/Components/Billing/CreateInvoiceModal.vue'
import ViewInvoiceModal from '@/Components/Billing/ViewInvoiceModal.vue'
import ApprovalWizardModal from '@/Components/Billing/ApprovalWizardModal.vue'
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

// TanStack Query hooks
const { data: billingStats, isLoading: statsLoading, error: statsError } = useBillingDashboardStatsQuery()
const { data: billingSettings, isLoading: settingsLoading, error: settingsError } = useBillingSettingsQuery()
const { data: invoices, isLoading: invoicesLoading, error: invoicesError } = useInvoicesQuery()

// Invoice action mutations
const { deleteInvoice, markAsPaid } = useInvoiceActionsMutations()

// Reactive data
const activeTab = ref('invoices')
const payments = ref([])
const showCreateInvoiceModal = ref(false)
const showViewInvoiceModal = ref(false)
const selectedInvoice = ref(null)
const showApprovalWizard = ref(false)
const approvalWizardData = ref({ accountId: '', accountName: '' })

// Computed properties for loading states
const loading = computed(() => {
  return statsLoading.value || settingsLoading.value || invoicesLoading.value
})

// Tab configuration
const tabs = [
  { key: 'invoices', label: 'Invoices', icon: DocumentTextIcon },
  { key: 'payments', label: 'Payments', icon: CreditCardIcon },
  { key: 'reports', label: 'Reports', icon: ChartBarIcon },
  { key: 'settings', label: 'Settings', icon: CogIcon }
]

// Methods

const viewInvoice = (invoice) => {
  selectedInvoice.value = invoice
  showViewInvoiceModal.value = true
}

const handleMarkInvoicePaid = async (invoice) => {
  try {
    await markAsPaid.mutateAsync(invoice.id)
    // Success is handled automatically by the mutation's onSuccess callback
  } catch (error) {
    // Error is handled automatically by the mutation's onError callback
    console.error('Error marking invoice as paid:', error)
  }
}

const handleDeleteInvoice = async (invoice) => {
  if (!confirm(`Are you sure you want to delete invoice ${invoice.invoice_number}? This action cannot be undone.`)) return
  
  try {
    await deleteInvoice.mutateAsync(invoice.id)
    // Success is handled automatically by the mutation's onSuccess callback
  } catch (error) {
    // Error is handled automatically by the mutation's onError callback
    console.error('Error deleting invoice:', error)
  }
}

const handleInvoiceCreated = (invoice) => {
  showCreateInvoiceModal.value = false
  // TanStack Query will automatically refetch due to invalidation in the mutation
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
      // TanStack Query will automatically refetch
    }
  } catch (error) {
    console.error('Error updating billing settings:', error)
  }
}

// Approval wizard methods
const handleLaunchApprovalWizard = (data) => {
  approvalWizardData.value = {
    accountId: data.accountId,
    accountName: data.accountName
  }
  showCreateInvoiceModal.value = false
  showApprovalWizard.value = true
}

const closeApprovalWizard = () => {
  showApprovalWizard.value = false
  approvalWizardData.value = { accountId: '', accountName: '' }
}

const onApprovalWizardCompleted = () => {
  // Wizard completed but user chose not to create invoice immediately
  closeApprovalWizard()
}

const onCreateInvoiceFromApproval = (data) => {
  // User completed approvals and wants to create invoice
  closeApprovalWizard()
  
  // Reopen create invoice modal with pre-populated account
  setTimeout(() => {
    approvalWizardData.value = {
      accountId: data.accountId,
      accountName: data.accountName
    }
    showCreateInvoiceModal.value = true
  }, 100)
}

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('en-US', {
    minimumFractionDigits: 0,
    maximumFractionDigits: 0
  }).format(amount || 0)
}

// Initialize data - TanStack Query handles this automatically
</script>