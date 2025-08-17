<template>
  <div class="space-y-6">
    <!-- Billing Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
      <div class="bg-white border border-gray-200 rounded-lg p-4">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-gray-600">Total Billable</p>
            <p class="text-2xl font-bold text-green-600">${{ summary.total_billable }}</p>
          </div>
          <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
            <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
            </svg>
          </div>
        </div>
      </div>
      
      <div class="bg-white border border-gray-200 rounded-lg p-4">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-gray-600">Invoiced</p>
            <p class="text-2xl font-bold text-blue-600">${{ summary.total_invoiced }}</p>
          </div>
          <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
            <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
          </div>
        </div>
      </div>
      
      <div class="bg-white border border-gray-200 rounded-lg p-4">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-gray-600">Outstanding</p>
            <p class="text-2xl font-bold text-orange-600">${{ summary.outstanding_amount }}</p>
          </div>
          <div class="w-8 h-8 bg-orange-100 rounded-full flex items-center justify-center">
            <svg class="w-4 h-4 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
          </div>
        </div>
      </div>
      
      <div class="bg-white border border-gray-200 rounded-lg p-4">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-gray-600">Billable Hours</p>
            <p class="text-2xl font-bold text-purple-600">{{ formatDuration(summary.billable_hours * 3600) }}</p>
          </div>
          <div class="w-8 h-8 bg-purple-100 rounded-full flex items-center justify-center">
            <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
        </div>
      </div>
    </div>

    <!-- Billing Rate Information -->
    <div class="bg-white border border-gray-200 rounded-lg p-6">
      <h3 class="text-lg font-semibold text-gray-900 mb-4">Billing Rate</h3>
      <div v-if="billingRate" class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-600">Rate Name</label>
          <p class="mt-1 text-sm text-gray-900">{{ billingRate.name }}</p>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-600">Hourly Rate</label>
          <p class="mt-1 text-sm font-medium text-gray-900">${{ billingRate.rate }}/hour</p>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-600">Currency</label>
          <p class="mt-1 text-sm text-gray-900">{{ billingRate.currency?.toUpperCase() || 'USD' }}</p>
        </div>
      </div>
      <div v-else class="py-4 text-gray-500">
        <p class="text-sm">Billing rates are determined automatically based on:</p>
        <ul class="mt-2 text-sm list-disc list-inside space-y-1 text-gray-600">
          <li>Agent's billing rate (if assigned to an agent)</li>
          <li>Account-specific rates for this customer</li>
          <li>Global default rates as fallback</li>
        </ul>
        <p class="mt-2 text-xs text-gray-500">Time entries inherit rates when created.</p>
      </div>
    </div>

    <!-- Time Entries with Billing Status -->
    <div class="bg-white border border-gray-200 rounded-lg">
      <div class="px-6 py-4 border-b border-gray-200">
        <div class="flex items-center justify-between">
          <h3 class="text-lg font-semibold text-gray-900">Billable Time Entries</h3>
          <div class="flex items-center space-x-2">
            <select 
              v-model="filters.billing_status" 
              @change="loadTimeEntries"
              class="border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            >
              <option value="">All Entries</option>
              <option value="pending">Pending</option>
              <option value="approved">Approved</option>
              <option value="invoiced">Invoiced</option>
              <option value="paid">Paid</option>
            </select>
          </div>
        </div>
      </div>
      
      <!-- Time Entries Table -->
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Date
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                User
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Duration
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Rate
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Amount
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Status
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Invoice
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-if="loadingTimeEntries" class="animate-pulse">
              <td colspan="7" class="px-6 py-4 text-center text-gray-500">
                Loading time entries...
              </td>
            </tr>
            <tr v-else-if="timeEntries.length === 0">
              <td colspan="7" class="px-6 py-4 text-center text-gray-500">
                No billable time entries found.
              </td>
            </tr>
            <tr v-else v-for="entry in timeEntries" :key="entry.id" class="hover:bg-gray-50">
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                {{ formatDate(entry.started_at) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center">
                  <div class="w-8 h-8 bg-gray-300 rounded-full flex items-center justify-center mr-3">
                    <span class="text-xs font-medium text-gray-700">
                      {{ entry.user?.name?.charAt(0)?.toUpperCase() || '?' }}
                    </span>
                  </div>
                  <div class="text-sm font-medium text-gray-900">{{ entry.user?.name || 'Unknown' }}</div>
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                {{ formatDuration(entry.duration) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                ${{ entry.hourly_rate || billingRate?.rate || '0.00' }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-green-600">
                ${{ entry.billable_amount || '0.00' }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span :class="getBillingStatusClasses(entry.billing_status)" class="px-2 py-1 rounded-full text-xs font-medium">
                  {{ formatBillingStatus(entry.billing_status) }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-blue-600">
                <Link 
                  v-if="entry.invoice_id" 
                  :href="route('invoices.show', entry.invoice_id)"
                  class="hover:text-blue-700"
                >
                  {{ entry.invoice?.invoice_number || `INV-${entry.invoice_id}` }}
                </Link>
                <span v-else class="text-gray-400">Not invoiced</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Related Invoices -->
    <div v-if="invoices.length > 0" class="bg-white border border-gray-200 rounded-lg">
      <div class="px-6 py-4 border-b border-gray-200">
        <h3 class="text-lg font-semibold text-gray-900">Related Invoices</h3>
      </div>
      
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Invoice #
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Date
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Amount
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Status
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Due Date
              </th>
              <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                Actions
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="invoice in invoices" :key="invoice.id" class="hover:bg-gray-50">
              <td class="px-6 py-4 whitespace-nowrap">
                <Link 
                  :href="route('invoices.show', invoice.id)"
                  class="text-sm font-medium text-blue-600 hover:text-blue-700"
                >
                  {{ invoice.invoice_number }}
                </Link>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                {{ formatDate(invoice.invoice_date) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                ${{ invoice.total_amount }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span :class="getInvoiceStatusClasses(invoice.status)" class="px-2 py-1 rounded-full text-xs font-medium">
                  {{ formatInvoiceStatus(invoice.status) }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                <span :class="getDueDateClasses(invoice.due_date, invoice.status)">
                  {{ formatDate(invoice.due_date) }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                <Link 
                  :href="route('invoices.show', invoice.id)"
                  class="text-blue-600 hover:text-blue-700 mr-3"
                >
                  View
                </Link>
                <button
                  v-if="invoice.status === 'draft'"
                  @click="sendInvoice(invoice)"
                  class="text-green-600 hover:text-green-700"
                >
                  Send
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Addon Billing -->
    <div v-if="billableAddons.length > 0" class="bg-white border border-gray-200 rounded-lg">
      <div class="px-6 py-4 border-b border-gray-200">
        <h3 class="text-lg font-semibold text-gray-900">Billable Add-ons</h3>
      </div>
      
      <div class="p-6 space-y-4">
        <div 
          v-for="addon in billableAddons" 
          :key="addon.id"
          class="flex items-center justify-between p-4 border border-gray-200 rounded-lg"
        >
          <div class="flex-1">
            <h4 class="text-sm font-medium text-gray-900">{{ addon.title }}</h4>
            <p class="text-xs text-gray-600">{{ formatAddonType(addon.type) }}</p>
            <div v-if="addon.description" class="text-xs text-gray-500 mt-1">
              {{ addon.description }}
            </div>
          </div>
          <div class="text-right">
            <div class="text-sm font-medium text-green-600">
              ${{ addon.actual_cost || addon.estimated_cost || '0.00' }}
            </div>
            <div class="text-xs text-gray-500">
              {{ addon.actual_hours || addon.estimated_hours || 0 }}h
            </div>
            <span :class="getAddonStatusClasses(addon.status)" class="inline-block mt-1 px-2 py-1 rounded-full text-xs font-medium">
              {{ formatAddonStatus(addon.status) }}
            </span>
          </div>
        </div>
      </div>
    </div>

    <!-- Billing Actions -->
    <div v-if="canManageBilling" class="bg-gray-50 rounded-lg p-6">
      <h3 class="text-lg font-semibold text-gray-900 mb-4">Billing Actions</h3>
      <div class="flex flex-wrap gap-3">
        <button
          @click="generateInvoice"
          :disabled="!hasUnbilledItems"
          class="bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors"
        >
          Generate Invoice
        </button>
        <button
          @click="exportBillingReport"
          class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors"
        >
          Export Report
        </button>
        <button
          @click="showRateModal = true"
          class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors"
        >
          Manage Rates
        </button>
      </div>
    </div>

    <!-- Modals -->
    <BillingRateModal 
      v-if="showRateModal"
      :ticket="ticket"
      :current-rate="billingRate"
      @saved="handleRateSaved"
      @cancelled="showRateModal = false"
    />
    
    
    <InvoiceGeneratorModal
      v-if="showInvoiceModal"
      :ticket="ticket"
      :unbilled-entries="unbilledEntries"
      :unbilled-addons="unbilledAddons"
      @generated="handleInvoiceGenerated"
      @cancelled="showInvoiceModal = false"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { Link } from '@inertiajs/vue3'
import axios from 'axios'

// Import child components
import BillingRateModal from './BillingRateModal.vue'
import InvoiceGeneratorModal from './InvoiceGeneratorModal.vue'

// Props
const props = defineProps({
  ticket: {
    type: Object,
    required: true
  }
})

// Reactive data
const summary = ref({
  total_billable: '0.00',
  total_invoiced: '0.00',
  outstanding_amount: '0.00',
  billable_hours: 0
})

const billingRate = ref(null)
const timeEntries = ref([])
const invoices = ref([])
const billableAddons = ref([])
const loadingTimeEntries = ref(false)

// Modal states
const showRateModal = ref(false)
const showInvoiceModal = ref(false)

// Filters
const filters = ref({
  billing_status: ''
})

// Computed properties
const canManageBilling = computed(() => {
  // TODO: Implement proper permission checking
  return true
})

const hasUnbilledItems = computed(() => {
  return timeEntries.value.some(entry => !entry.invoice_id) || 
         billableAddons.value.some(addon => addon.status === 'approved' && !addon.invoice_id)
})

const unbilledEntries = computed(() => {
  return timeEntries.value.filter(entry => !entry.invoice_id)
})

const unbilledAddons = computed(() => {
  return billableAddons.value.filter(addon => addon.status === 'approved' && !addon.invoice_id)
})

// Methods
const formatDate = (date) => {
  if (!date) return 'N/A'
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

const formatDuration = (seconds) => {
  if (!seconds) return '0m'
  
  const hours = Math.floor(seconds / 3600)
  const minutes = Math.floor((seconds % 3600) / 60)
  
  if (hours > 0) {
    return `${hours}h ${minutes}m`
  } else {
    return `${minutes}m`
  }
}

const formatBillingStatus = (status) => {
  const statusMap = {
    'pending': 'Pending',
    'approved': 'Approved',
    'invoiced': 'Invoiced',
    'paid': 'Paid'
  }
  return statusMap[status] || status
}

const formatInvoiceStatus = (status) => {
  return status?.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase()) || 'Unknown'
}

const formatAddonType = (type) => {
  const typeMap = {
    'additional_work': 'Additional Work',
    'emergency_support': 'Emergency Support',
    'consultation': 'Consultation',
    'training': 'Training',
    'custom': 'Custom'
  }
  return typeMap[type] || type
}

const formatAddonStatus = (status) => {
  return status?.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase()) || 'Unknown'
}

const getBillingStatusClasses = (status) => {
  const statusMap = {
    'pending': 'bg-yellow-100 text-yellow-800',
    'approved': 'bg-green-100 text-green-800',
    'invoiced': 'bg-blue-100 text-blue-800',
    'paid': 'bg-purple-100 text-purple-800'
  }
  return statusMap[status] || 'bg-gray-100 text-gray-800'
}

const getInvoiceStatusClasses = (status) => {
  const statusMap = {
    'draft': 'bg-gray-100 text-gray-800',
    'sent': 'bg-blue-100 text-blue-800',
    'viewed': 'bg-purple-100 text-purple-800',
    'paid': 'bg-green-100 text-green-800',
    'overdue': 'bg-red-100 text-red-800',
    'cancelled': 'bg-gray-100 text-gray-800'
  }
  return statusMap[status] || 'bg-gray-100 text-gray-800'
}

const getAddonStatusClasses = (status) => {
  const statusMap = {
    'pending': 'bg-yellow-100 text-yellow-800',
    'approved': 'bg-green-100 text-green-800',
    'completed': 'bg-blue-100 text-blue-800',
    'rejected': 'bg-red-100 text-red-800'
  }
  return statusMap[status] || 'bg-gray-100 text-gray-800'
}

const getDueDateClasses = (dueDate, status) => {
  if (!dueDate || status === 'paid') return 'text-gray-600'
  
  const due = new Date(dueDate)
  const now = new Date()
  const diffDays = Math.ceil((due - now) / (1000 * 60 * 60 * 24))
  
  if (diffDays < 0) {
    return 'text-red-600 font-medium' // Overdue
  } else if (diffDays <= 7) {
    return 'text-orange-600 font-medium' // Due soon
  } else {
    return 'text-gray-600' // Normal
  }
}

// Data loading methods
const loadBillingSummary = async () => {
  if (!props.ticket?.id) return
  
  try {
    const response = await axios.get(`/api/tickets/${props.ticket.id}/billing-summary`)
    summary.value = response.data.data || {
      total_billable: '0.00',
      total_invoiced: '0.00',
      outstanding_amount: '0.00',
      billable_hours: 0
    }
  } catch (error) {
    console.error('Failed to load billing summary:', error)
  }
}

const loadBillingRate = async () => {
  if (!props.ticket?.id) return
  
  try {
    const response = await axios.get(`/api/tickets/${props.ticket.id}/billing-rate`)
    billingRate.value = response.data.data || null
  } catch (error) {
    console.error('Failed to load billing rate:', error)
    billingRate.value = null
  }
}

const loadTimeEntries = async () => {
  if (!props.ticket?.id) return
  
  loadingTimeEntries.value = true
  
  try {
    const params = new URLSearchParams({
      billable: 'true',
      ...Object.fromEntries(Object.entries(filters.value).filter(([_, v]) => v !== ''))
    })
    
    const response = await axios.get(`/api/tickets/${props.ticket.id}/time-entries?${params}`)
    timeEntries.value = response.data.data || []
  } catch (error) {
    console.error('Failed to load time entries:', error)
    timeEntries.value = []
  } finally {
    loadingTimeEntries.value = false
  }
}

const loadInvoices = async () => {
  if (!props.ticket?.id) return
  
  try {
    const response = await axios.get(`/api/tickets/${props.ticket.id}/invoices`)
    invoices.value = response.data.data || []
  } catch (error) {
    console.error('Failed to load invoices:', error)
    invoices.value = []
  }
}

const loadBillableAddons = async () => {
  if (!props.ticket?.id) return
  
  try {
    const response = await axios.get(`/api/tickets/${props.ticket.id}/addons?billable=true`)
    billableAddons.value = response.data.data || []
  } catch (error) {
    console.error('Failed to load billable addons:', error)
    billableAddons.value = []
  }
}

// Action methods
const generateInvoice = () => {
  showInvoiceModal.value = true
}

const exportBillingReport = async () => {
  try {
    const response = await axios.get(`/api/tickets/${props.ticket.id}/billing-report`, {
      responseType: 'blob'
    })
    
    // Create download link
    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `ticket-${props.ticket.ticket_number}-billing-report.pdf`)
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)
  } catch (error) {
    console.error('Failed to export billing report:', error)
  }
}

const sendInvoice = async (invoice) => {
  try {
    await axios.post(`/api/invoices/${invoice.id}/send`)
    await loadInvoices()
  } catch (error) {
    console.error('Failed to send invoice:', error)
  }
}

// Modal handlers
const handleRateSaved = () => {
  showRateModal.value = false
  loadBillingRate()
  loadTimeEntries()
  loadBillingSummary()
}


const handleInvoiceGenerated = () => {
  showInvoiceModal.value = false
  loadInvoices()
  loadTimeEntries()
  loadBillableAddons()
  loadBillingSummary()
}

// Lifecycle
onMounted(() => {
  loadBillingSummary()
  loadBillingRate()
  loadTimeEntries()
  loadInvoices()
  loadBillableAddons()
})

// Watchers
watch(() => props.ticket?.id, (newId) => {
  if (newId) {
    loadBillingSummary()
    loadBillingRate()
    loadTimeEntries()
    loadInvoices()
    loadBillableAddons()
  }
})
</script>