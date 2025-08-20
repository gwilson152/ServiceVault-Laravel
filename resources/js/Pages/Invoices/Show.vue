<template>
  <div class="py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <!-- Loading State -->
      <div v-if="loading" class="flex justify-center items-center py-12">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600"></div>
      </div>

      <!-- Error State -->
      <div v-else-if="error" class="bg-red-50 border border-red-200 rounded-md p-4">
        <div class="text-red-800">Error loading invoice: {{ error.message }}</div>
      </div>

      <!-- Invoice Detail -->
      <div v-else-if="invoice" class="space-y-8">
        <!-- Header -->
        <div class="bg-white shadow rounded-lg">
          <div class="px-6 py-4 border-b border-gray-200">
            <div class="flex items-center justify-between">
              <div>
                <div class="flex items-center space-x-3">
                  <h1 class="text-2xl font-bold text-gray-900">
                    Invoice #{{ invoice.invoice_number }}
                  </h1>
                  <span :class="statusBadgeClass(invoice.status)">
                    {{ invoice.status.charAt(0).toUpperCase() + invoice.status.slice(1) }}
                  </span>
                </div>
                <p class="mt-1 text-sm text-gray-600">
                  {{ invoice.account?.name }}
                </p>
              </div>
              
              <div class="flex items-center space-x-3">
                <!-- Edit Toggle (Draft Only) -->
                <button
                  v-if="invoice.status === 'draft' && canEdit"
                  @click="toggleEditMode"
                  :class="[
                    editMode
                      ? 'bg-red-100 text-red-700 hover:bg-red-200'
                      : 'bg-indigo-100 text-indigo-700 hover:bg-indigo-200',
                    'inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500'
                  ]"
                >
                  <PencilIcon v-if="!editMode" class="h-4 w-4 mr-1" />
                  <XMarkIcon v-else class="h-4 w-4 mr-1" />
                  {{ editMode ? 'Cancel' : 'Edit' }}
                </button>

                <!-- Save Button (Edit Mode Only) -->
                <button
                  v-if="editMode"
                  @click="saveChanges"
                  :disabled="!hasChanges || isSaving"
                  class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  <span v-if="isSaving" class="mr-2">
                    <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-white"></div>
                  </span>
                  {{ isSaving ? 'Saving...' : 'Save Changes' }}
                </button>

                <!-- Action Menu -->
                <div class="relative" v-if="!editMode">
                  <button
                    @click="showActionsMenu = !showActionsMenu"
                    class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                  >
                    Actions
                    <ChevronDownIcon class="ml-1 h-4 w-4" />
                  </button>
                  
                  <div
                    v-if="showActionsMenu"
                    class="origin-top-right absolute right-0 mt-2 w-48 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 z-10"
                  >
                    <div class="py-1">
                      <a
                        :href="`/api/billing/invoices/${invoice.id}/pdf`"
                        target="_blank"
                        class="group flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                      >
                        <DocumentArrowDownIcon class="mr-3 h-4 w-4" />
                        Download PDF
                      </a>
                      
                      <button
                        v-if="invoice.status === 'draft'"
                        @click="sendInvoice"
                        :disabled="isProcessing"
                        class="group flex items-center w-full px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 disabled:opacity-50"
                      >
                        <PaperAirplaneIcon class="mr-3 h-4 w-4" />
                        Send Invoice
                      </button>
                      
                      <button
                        v-if="invoice.status === 'sent'"
                        @click="markAsPaid"
                        :disabled="isProcessing"
                        class="group flex items-center w-full px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 disabled:opacity-50"
                      >
                        <CheckCircleIcon class="mr-3 h-4 w-4" />
                        Mark as Paid
                      </button>
                      
                      <button
                        v-if="invoice.status === 'draft'"
                        @click="deleteInvoice"
                        :disabled="isProcessing"
                        class="group flex items-center w-full px-4 py-2 text-sm text-red-700 hover:bg-red-50 disabled:opacity-50"
                      >
                        <TrashIcon class="mr-3 h-4 w-4" />
                        Delete Invoice
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Invoice Details Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
          <!-- Left Column: Invoice Info -->
          <div class="lg:col-span-2 space-y-6">
            <!-- Invoice Information -->
            <div class="bg-white shadow rounded-lg p-6">
              <h3 class="text-lg font-medium text-gray-900 mb-4">Invoice Information</h3>
              
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700">Invoice Date</label>
                  <input
                    v-if="editMode"
                    v-model="editForm.invoice_date"
                    type="date"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                  />
                  <p v-else class="mt-1 text-sm text-gray-900">
                    {{ formatDate(invoice.invoice_date) }}
                  </p>
                </div>
                
                <div>
                  <label class="block text-sm font-medium text-gray-700">Due Date</label>
                  <input
                    v-if="editMode"
                    v-model="editForm.due_date"
                    type="date"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                  />
                  <p v-else class="mt-1 text-sm text-gray-900">
                    {{ formatDate(invoice.due_date) }}
                  </p>
                </div>
                
                <div>
                  <label class="block text-sm font-medium text-gray-700">Tax Rate (%)</label>
                  <input
                    v-if="editMode"
                    v-model="editForm.tax_rate"
                    type="number"
                    step="0.01"
                    min="0"
                    max="100"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                  />
                  <p v-else class="mt-1 text-sm text-gray-900">
                    {{ invoice.tax_rate || 0 }}%
                  </p>
                </div>
              </div>

              <div class="mt-4">
                <label class="block text-sm font-medium text-gray-700">Notes</label>
                <textarea
                  v-if="editMode"
                  v-model="editForm.notes"
                  rows="3"
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                  placeholder="Optional invoice notes..."
                />
                <p v-else class="mt-1 text-sm text-gray-900">
                  {{ invoice.notes || 'No notes' }}
                </p>
              </div>
            </div>

            <!-- Line Items -->
            <div class="bg-white shadow rounded-lg">
              <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex items-center justify-between">
                  <h3 class="text-lg font-medium text-gray-900">Line Items</h3>
                  <button
                    v-if="editMode"
                    @click="showAvailableItemsModal = true"
                    class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-indigo-700 bg-indigo-100 hover:bg-indigo-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                  >
                    <PlusIcon class="h-4 w-4 mr-1" />
                    Add Item
                  </button>
                </div>
              </div>

              <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                  <thead class="bg-gray-50">
                    <tr>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Description
                      </th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Qty
                      </th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Rate
                      </th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Amount
                      </th>
                      <th v-if="editMode" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Actions
                      </th>
                    </tr>
                  </thead>
                  <tbody class="bg-white divide-y divide-gray-200">
                    <tr v-for="item in invoice.line_items" :key="item.id">
                      <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center">
                          <div>
                            <div class="text-sm font-medium text-gray-900">
                              {{ item.description }}
                            </div>
                            <div class="text-xs text-gray-500">
                              <span v-if="item.line_type === 'time_entry'" class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
                                Time Entry
                              </span>
                              <span v-else-if="item.line_type === 'addon'" class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-800">
                                Add-on
                              </span>
                              <span v-else class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800">
                                Custom
                              </span>
                            </div>
                          </div>
                        </div>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ formatQuantity(item) }}
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        ${{ formatCurrency(item.unit_price) }}
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        ${{ formatCurrency(item.total_amount) }}
                      </td>
                      <td v-if="editMode" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        <button
                          @click="removeLineItem(item.id)"
                          :disabled="isProcessing"
                          class="text-red-600 hover:text-red-900 disabled:opacity-50"
                        >
                          <TrashIcon class="h-4 w-4" />
                        </button>
                      </td>
                    </tr>
                  </tbody>
                </table>

                <div v-if="invoice.line_items.length === 0" class="text-center py-8">
                  <DocumentIcon class="mx-auto h-12 w-12 text-gray-400" />
                  <h3 class="mt-2 text-sm font-medium text-gray-900">No line items</h3>
                  <p class="mt-1 text-sm text-gray-500">
                    {{ editMode ? 'Add items to this invoice.' : 'This invoice has no line items.' }}
                  </p>
                </div>
              </div>
            </div>
          </div>

          <!-- Right Column: Summary & Account Info -->
          <div class="space-y-6">
            <!-- Account Information -->
            <div class="bg-white shadow rounded-lg p-6">
              <h3 class="text-lg font-medium text-gray-900 mb-4">Account Information</h3>
              <div class="space-y-3">
                <div>
                  <p class="text-sm font-medium text-gray-900">{{ invoice.account?.name }}</p>
                </div>
              </div>
            </div>

            <!-- Invoice Summary -->
            <div class="bg-white shadow rounded-lg p-6">
              <h3 class="text-lg font-medium text-gray-900 mb-4">Summary</h3>
              <div class="space-y-2">
                <div class="flex justify-between text-sm">
                  <span class="text-gray-600">Subtotal:</span>
                  <span class="font-medium">${{ formatCurrency(invoice.subtotal || 0) }}</span>
                </div>
                <div v-if="invoice.tax_amount > 0" class="flex justify-between text-sm">
                  <span class="text-gray-600">Tax ({{ invoice.tax_rate }}%):</span>
                  <span class="font-medium">${{ formatCurrency(invoice.tax_amount || 0) }}</span>
                </div>
                <div class="border-t border-gray-200 pt-2">
                  <div class="flex justify-between text-lg font-semibold">
                    <span>Total:</span>
                    <span>${{ formatCurrency(invoice.total || 0) }}</span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Payment History -->
            <div v-if="invoice.payments && invoice.payments.length > 0" class="bg-white shadow rounded-lg p-6">
              <h3 class="text-lg font-medium text-gray-900 mb-4">Payment History</h3>
              <div class="space-y-3">
                <div v-for="payment in invoice.payments" :key="payment.id" class="flex justify-between items-center">
                  <div>
                    <p class="text-sm font-medium text-gray-900">${{ formatCurrency(payment.amount) }}</p>
                    <p class="text-xs text-gray-500">{{ formatDate(payment.payment_date) }}</p>
                  </div>
                  <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                    {{ payment.payment_method }}
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Available Items Modal -->
    <AvailableItemsModal
      v-if="invoice?.id"
      :show="showAvailableItemsModal"
      :invoice-id="invoice.id"
      @close="showAvailableItemsModal = false"
      @item-added="handleItemAdded"
    />
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import AvailableItemsModal from '@/Components/Invoices/AvailableItemsModal.vue'
import {
  PencilIcon,
  XMarkIcon,
  PlusIcon,
  TrashIcon,
  DocumentIcon,
  ChevronDownIcon,
  DocumentArrowDownIcon,
  PaperAirplaneIcon,
  CheckCircleIcon
} from '@heroicons/vue/24/outline'
import { useInvoiceQuery } from '@/Composables/queries/useInvoiceQuery.js'

const props = defineProps({
  invoiceId: {
    type: String,
    required: true
  }
})

// Set layout
defineOptions({
  layout: AppLayout
})

const page = usePage()
const user = computed(() => page.props.auth?.user)

// State
const editMode = ref(false)
const showActionsMenu = ref(false)
const showAvailableItemsModal = ref(false)

const editForm = reactive({
  invoice_date: '',
  due_date: '',
  tax_rate: 0,
  notes: ''
})

// Query composable
const { 
  invoice, 
  loading, 
  error, 
  updateInvoice,
  sendInvoice: sendInvoiceMutation,
  markAsPaid: markAsPaidMutation,
  deleteInvoice: deleteInvoiceMutation,
  removeLineItem: removeLineItemMutation,
  isUpdating,
  isSending,
  isMarkingPaid,
  isDeleting,
  isRemovingLineItem
} = useInvoiceQuery(props.invoiceId)

// Computed
const canEdit = computed(() => {
  if (!invoice.value || !user.value) return false
  return user.value.permissions?.includes('billing.admin') || 
         user.value.permissions?.includes('admin.write')
})

const hasChanges = computed(() => {
  if (!invoice.value) return false
  return (
    editForm.invoice_date !== invoice.value.invoice_date ||
    editForm.due_date !== invoice.value.due_date ||
    editForm.tax_rate !== (invoice.value.tax_rate || 0) ||
    editForm.notes !== (invoice.value.notes || '')
  )
})

const isSaving = computed(() => isUpdating.value)
const isProcessing = computed(() => isSending.value || isMarkingPaid.value || isDeleting.value || isRemovingLineItem.value)

// Methods
const statusBadgeClass = (status) => {
  const classes = 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium'
  switch (status) {
    case 'draft':
      return `${classes} bg-gray-100 text-gray-800`
    case 'sent':
      return `${classes} bg-blue-100 text-blue-800`
    case 'paid':
      return `${classes} bg-green-100 text-green-800`
    case 'overdue':
      return `${classes} bg-red-100 text-red-800`
    case 'cancelled':
      return `${classes} bg-red-100 text-red-800`
    default:
      return `${classes} bg-gray-100 text-gray-800`
  }
}

const toggleEditMode = () => {
  if (editMode.value) {
    // Cancel edit - reset form
    resetEditForm()
  } else {
    // Enter edit mode - populate form
    populateEditForm()
  }
  editMode.value = !editMode.value
}

const populateEditForm = () => {
  if (!invoice.value) return
  editForm.invoice_date = invoice.value.invoice_date
  editForm.due_date = invoice.value.due_date
  editForm.tax_rate = invoice.value.tax_rate || 0
  editForm.notes = invoice.value.notes || ''
}

const resetEditForm = () => {
  editForm.invoice_date = ''
  editForm.due_date = ''
  editForm.tax_rate = 0
  editForm.notes = ''
}

const saveChanges = async () => {
  if (!hasChanges.value) return
  
  try {
    await updateInvoice(editForm)
    editMode.value = false
  } catch (error) {
    console.error('Failed to update invoice:', error)
  }
}

const sendInvoice = async () => {
  try {
    await sendInvoiceMutation()
    showActionsMenu.value = false
  } catch (error) {
    console.error('Failed to send invoice:', error)
  }
}

const markAsPaid = async () => {
  try {
    await markAsPaidMutation()
    showActionsMenu.value = false
  } catch (error) {
    console.error('Failed to mark invoice as paid:', error)
  }
}

const deleteInvoice = async () => {
  if (!confirm('Are you sure you want to delete this invoice? This action cannot be undone.')) return
  
  try {
    await deleteInvoiceMutation()
    showActionsMenu.value = false
    // Redirect to invoices list after successful deletion
    router.visit('/billing')
  } catch (error) {
    console.error('Failed to delete invoice:', error)
  }
}

const removeLineItem = async (lineItemId) => {
  if (!confirm('Are you sure you want to remove this line item?')) return
  
  try {
    await removeLineItemMutation(lineItemId)
  } catch (error) {
    console.error('Failed to remove line item:', error)
  }
}

const handleItemAdded = () => {
  showAvailableItemsModal.value = false
  // The query will automatically refetch due to cache invalidation
}

const formatDate = (dateString) => {
  if (!dateString) return 'N/A'
  return new Date(dateString).toLocaleDateString()
}

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('en-US', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  }).format(amount || 0)
}

const formatQuantity = (item) => {
  if (item.line_type === 'time_entry') {
    const hours = Math.floor(item.quantity)
    const minutes = Math.round((item.quantity - hours) * 60)
    if (hours === 0) return `${minutes}m`
    if (minutes === 0) return `${hours}h`
    return `${hours}h ${minutes}m`
  }
  return item.quantity
}

// Initialize edit form when invoice loads
watch(invoice, (newInvoice) => {
  if (newInvoice && editMode.value) {
    populateEditForm()
  }
})
</script>