<template>
  <TransitionRoot as="template" :show="show">
    <Dialog as="div" class="relative z-10" @close="$emit('close')">
      <TransitionChild
        as="template"
        enter="ease-out duration-300"
        enter-from="opacity-0"
        enter-to="opacity-100"
        leave="ease-in duration-200"
        leave-from="opacity-100"
        leave-to="opacity-0"
      >
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" />
      </TransitionChild>

      <div class="fixed inset-0 z-10 overflow-y-auto">
        <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
          <TransitionChild
            as="template"
            enter="ease-out duration-300"
            enter-from="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            enter-to="opacity-100 translate-y-0 sm:scale-100"
            leave="ease-in duration-200"
            leave-from="opacity-100 translate-y-0 sm:scale-100"
            leave-to="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
          >
            <DialogPanel class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-6xl">
              <div class="bg-white px-4 pt-5 pb-4 sm:p-6">
                <!-- Header -->
                <div class="flex items-center justify-between mb-6">
                  <DialogTitle as="h3" class="text-lg font-medium leading-6 text-gray-900">
                    Create Invoice
                  </DialogTitle>
                  <button
                    @click="$emit('close')"
                    class="text-gray-400 hover:text-gray-500"
                  >
                    <XMarkIcon class="h-6 w-6" />
                  </button>
                </div>

                <!-- Multi-step Form -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                  <!-- Left Column: Invoice Details -->
                  <div class="space-y-6">
                    <!-- Basic Invoice Information -->
                    <div>
                      <h4 class="text-sm font-medium text-gray-900 mb-4">Basic Information</h4>
                      
                      <!-- Account Selection -->
                      <div class="mb-4">
                        <UnifiedSelector
                          v-model="form.account_id"
                          type="account"
                          label="Account"
                          placeholder="Select an account..."
                          :clearable="true"
                          required
                          @item-selected="onAccountSelected"
                        />
                      </div>

                      <!-- Invoice Dates -->
                      <div class="grid grid-cols-2 gap-4 mb-4">
                        <div>
                          <label for="invoice_date" class="block text-sm font-medium text-gray-700">
                            Invoice Date
                          </label>
                          <input
                            id="invoice_date"
                            v-model="form.invoice_date"
                            type="date"
                            required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                          />
                        </div>
                        <div>
                          <label for="due_date" class="block text-sm font-medium text-gray-700">
                            Due Date
                          </label>
                          <input
                            id="due_date"
                            v-model="form.due_date"
                            type="date"
                            required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                          />
                        </div>
                      </div>
                    </div>

                    <!-- Invoice Options Tabs -->
                    <div class="border border-gray-200 rounded-lg">
                      <div class="border-b border-gray-200">
                        <nav class="-mb-px flex" aria-label="Invoice options">
                          <button
                            @click="activeOptionsTab = 'taxes'"
                            :class="[
                              activeOptionsTab === 'taxes'
                                ? 'border-indigo-500 text-indigo-600'
                                : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700',
                              'w-1/2 border-b-2 py-2 px-1 text-center text-sm font-medium'
                            ]"
                          >
                            Tax & Pricing
                          </button>
                          <button
                            @click="activeOptionsTab = 'notes'"
                            :class="[
                              activeOptionsTab === 'notes'
                                ? 'border-indigo-500 text-indigo-600'
                                : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700',
                              'w-1/2 border-b-2 py-2 px-1 text-center text-sm font-medium'
                            ]"
                          >
                            Notes & Settings
                          </button>
                        </nav>
                      </div>

                      <div class="p-4">
                        <!-- Tax & Pricing Tab -->
                        <div v-if="activeOptionsTab === 'taxes'">
                          <div class="space-y-4">
                            <div>
                              <label for="tax_rate" class="block text-sm font-medium text-gray-700">
                                Tax Rate (%)
                              </label>
                              <input
                                id="tax_rate"
                                v-model="form.tax_rate"
                                type="number"
                                step="0.01"
                                min="0"
                                max="100"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                placeholder="0.00"
                              />
                              <p class="mt-1 text-xs text-gray-500">Applied only to taxable items</p>
                            </div>
                          </div>
                        </div>

                        <!-- Notes & Settings Tab -->
                        <div v-if="activeOptionsTab === 'notes'">
                          <div class="space-y-4">
                            <div>
                              <label for="notes" class="block text-sm font-medium text-gray-700">
                                Invoice Notes
                              </label>
                              <textarea
                                id="notes"
                                v-model="form.notes"
                                rows="4"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                placeholder="Optional notes that will appear on the invoice..."
                              />
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>

                    <!-- Invoice Summary -->
                    <div v-if="selectedItems.time_entries.length > 0 || selectedItems.ticket_addons.length > 0" class="bg-gray-50 p-4 rounded-lg">
                      <h4 class="text-sm font-medium text-gray-900 mb-3">Invoice Summary</h4>
                      <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                          <span class="text-gray-600">Time Entries:</span>
                          <span class="font-medium">{{ selectedItems.time_entries.length }} items</span>
                        </div>
                        <div class="flex justify-between">
                          <span class="text-gray-600">Add-ons:</span>
                          <span class="font-medium">{{ selectedItems.ticket_addons.length }} items</span>
                        </div>
                        <hr class="my-2">
                        <div class="flex justify-between">
                          <span class="text-gray-600">Subtotal:</span>
                          <span class="font-medium">${{ formatCurrency(invoiceSubtotal) }}</span>
                        </div>
                        <div v-if="form.tax_rate > 0" class="space-y-1">
                          <div class="flex justify-between text-xs">
                            <span class="text-gray-500">Taxable Amount:</span>
                            <span class="text-gray-600">${{ formatCurrency(taxableSubtotal) }}</span>
                          </div>
                          <div class="flex justify-between">
                            <span class="text-gray-600">Tax ({{ form.tax_rate }}%):</span>
                            <span class="font-medium">${{ formatCurrency(invoiceTax) }}</span>
                          </div>
                        </div>
                        <div class="flex justify-between text-lg font-semibold text-gray-900 pt-2 border-t">
                          <span>Total:</span>
                          <span>${{ formatCurrency(invoiceTotal) }}</span>
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- Right Column: Unbilled Items -->
                  <div>
                    <h4 class="text-sm font-medium text-gray-900 mb-4">Select Items to Invoice</h4>
                    
                    <div v-if="!form.account_id" class="text-center py-8 text-gray-500">
                      <DocumentIcon class="mx-auto h-12 w-12 text-gray-400" />
                      <p class="mt-2 text-sm">Select an account to view unbilled items</p>
                    </div>
                    
                    <UnbilledItemsSelector
                      v-else
                      :account-id="form.account_id"
                      :account-name="selectedAccountName"
                      :data="unbilledData"
                      :loading="loadingUnbilled"
                      @update:selectedItems="selectedItems = $event"
                      @launchApprovalWizard="handleLaunchApprovalWizard"
                    />
                  </div>
                </div>

                <!-- Footer Actions -->
                <div class="mt-8 flex flex-col sm:flex-row justify-between items-center gap-4 pt-6 border-t border-gray-200">
                  <div class="flex items-center text-sm text-gray-600">
                    <span v-if="hasSelectedItems">
                      {{ totalSelectedItems }} item(s) selected • Total: ${{ formatCurrency(invoiceTotal) }}
                    </span>
                    <span v-else class="text-gray-400">
                      No items selected
                    </span>
                  </div>
                  
                  <div class="flex gap-3">
                    <button
                      type="button"
                      @click="$emit('close')"
                      class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                    >
                      Cancel
                    </button>
                    <button
                      @click="handleCreateInvoice"
                      :disabled="!canCreateInvoice || createInvoiceMutation.isPending.value"
                      class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                      <span v-if="createInvoiceMutation.isPending.value" class="mr-2">
                        <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-white"></div>
                      </span>
                      {{ createButtonText }}
                    </button>
                  </div>
                </div>
              </div>
            </DialogPanel>
          </TransitionChild>
        </div>
      </div>
    </Dialog>
  </TransitionRoot>
</template>

<script setup>
import { ref, reactive, watch, computed } from 'vue'
import {
  Dialog,
  DialogPanel,
  DialogTitle,
  TransitionChild,
  TransitionRoot,
} from '@headlessui/vue'
import { XMarkIcon, DocumentIcon } from '@heroicons/vue/24/outline'
import UnifiedSelector from '@/Components/UI/UnifiedSelector.vue'
import UnbilledItemsSelector from '@/Components/Billing/UnbilledItemsSelector.vue'
import { useUnbilledItemsQuery, useCreateInvoiceMutation } from '@/Composables/queries/useBillingQuery.js'

const props = defineProps({
  show: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['close', 'created', 'launchApprovalWizard'])

// Reactive state
const selectedAccountName = ref('')
const activeOptionsTab = ref('taxes')

const form = reactive({
  account_id: '',
  invoice_date: new Date().toISOString().split('T')[0],
  due_date: '',
  tax_rate: 0,
  notes: ''
})

// TanStack Query hooks
const createInvoiceMutation = useCreateInvoiceMutation()

// Create a stable reference for the account ID to prevent unnecessary re-renders
const accountIdForQuery = computed(() => form.account_id)
const queryEnabled = computed(() => !!form.account_id)

const { data: unbilledData, isLoading: loadingUnbilled, refetch: refetchUnbilled } = useUnbilledItemsQuery(
  accountIdForQuery, 
  { 
    enabled: queryEnabled,
    keepPreviousData: true,
    staleTime: 1000 * 30, // 30 seconds
    // Prevent refetching on window focus to avoid clearing state
    refetchOnWindowFocus: false
  }
)

const selectedItems = ref({
  time_entries: [],
  ticket_addons: []
})

// Computed properties
const totalSelectedItems = computed(() => {
  return selectedItems.value.time_entries.length + selectedItems.value.ticket_addons.length
})

const hasSelectedItems = computed(() => {
  return totalSelectedItems.value > 0
})

const invoiceSubtotal = computed(() => {
  const timeEntryTotal = selectedItems.value.time_entries.reduce((sum, item) => sum + parseFloat(item.total_amount || 0), 0)
  const addonTotal = selectedItems.value.ticket_addons.reduce((sum, item) => sum + parseFloat(item.total_amount || 0), 0)
  return timeEntryTotal + addonTotal
})

const taxableSubtotal = computed(() => {
  // Calculate subtotal for only taxable items
  const timeEntryTaxable = selectedItems.value.time_entries
    .filter(item => item.is_taxable !== false) // Time entries are taxable by default
    .reduce((sum, item) => sum + parseFloat(item.total_amount || 0), 0)
    
  const addonTaxable = selectedItems.value.ticket_addons
    .filter(item => item.is_taxable === true) // Only explicitly taxable addons
    .reduce((sum, item) => sum + parseFloat(item.total_amount || 0), 0)
    
  return timeEntryTaxable + addonTaxable
})

const invoiceTax = computed(() => {
  // Apply invoice-level tax rate only to taxable items
  return taxableSubtotal.value * (parseFloat(form.tax_rate) / 100)
})

const invoiceTotal = computed(() => {
  return invoiceSubtotal.value + invoiceTax.value
})

const canCreateInvoice = computed(() => {
  return form.account_id && 
         form.invoice_date
})

const createButtonText = computed(() => {
  // Debug: Log the selected items
  console.log('Selected items:', selectedItems.value)
  console.log('Time entries count:', selectedItems.value.time_entries?.length || 0)
  console.log('Ticket addons count:', selectedItems.value.ticket_addons?.length || 0)
  console.log('hasSelectedItems:', hasSelectedItems.value)
  
  if (createInvoiceMutation.isPending.value) {
    return hasSelectedItems.value ? 'Creating Invoice...' : 'Creating Blank Invoice...'
  }
  return hasSelectedItems.value ? 'Create Invoice' : 'Create Blank Invoice'
})

// Watch for account changes to clear selected items
watch(() => form.account_id, (newAccountId, oldAccountId) => {
  // Only clear selected items if account actually changed (not just cleared)
  if (oldAccountId && newAccountId !== oldAccountId) {
    selectedItems.value = { time_entries: [], ticket_addons: [] }
  }
})

// Calculate due date (30 days from invoice date)
watch(() => form.invoice_date, (newDate) => {
  if (newDate) {
    const dueDate = new Date(newDate)
    dueDate.setDate(dueDate.getDate() + 30)
    form.due_date = dueDate.toISOString().split('T')[0]
  }
})

// Methods
const onAccountSelected = (account) => {
  selectedAccountName.value = account?.name || ''
}


const handleLaunchApprovalWizard = () => {
  // Emit to parent component to handle approval wizard launch
  emit('launchApprovalWizard', {
    accountId: form.account_id,
    accountName: selectedAccountName.value
  })
}

const handleCreateInvoice = async () => {
  if (!canCreateInvoice.value) return
  
  // Prepare line items
  const timeEntryIds = selectedItems.value.time_entries.map(item => item.id)
  const addonIds = selectedItems.value.ticket_addons.map(item => item.id)
  
  const payload = {
    account_id: form.account_id,
    invoice_date: form.invoice_date,
    due_date: form.due_date,
    tax_rate: form.tax_rate || 0,
    notes: form.notes || null,
    time_entry_ids: timeEntryIds,
    ticket_addon_ids: addonIds
  }

  try {
    const result = await createInvoiceMutation.mutateAsync(payload)
    emit('created', result)
    resetForm()
  } catch (error) {
    console.error('Error creating invoice:', error)
    alert('Error creating invoice: ' + (error.message || 'Unknown error'))
  }
}

const resetForm = () => {
  Object.assign(form, {
    account_id: '',
    invoice_date: new Date().toISOString().split('T')[0],
    due_date: '',
    tax_rate: 0,
    notes: ''
  })
  selectedItems.value = { time_entries: [], ticket_addons: [] }
  selectedAccountName.value = ''
  activeOptionsTab.value = 'taxes'
}

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('en-US', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  }).format(amount || 0)
}

// Reset form when modal is closed
watch(() => props.show, (isShowing) => {
  if (!isShowing) {
    resetForm()
  }
})
</script>