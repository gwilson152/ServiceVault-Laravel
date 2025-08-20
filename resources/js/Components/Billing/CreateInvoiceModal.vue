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
                            <div class="flex items-center">
                              <input
                                id="override_tax"
                                v-model="form.override_tax"
                                type="checkbox"
                                class="h-4 w-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500"
                              />
                              <label for="override_tax" class="ml-2 block text-sm font-medium text-gray-700">
                                Override Tax Settings for This Invoice
                              </label>
                            </div>
                            <p v-if="!form.override_tax" class="text-xs text-gray-500">
                              When unchecked, this invoice will use tax settings inherited from account or system defaults.
                            </p>

                            <div v-if="!form.override_tax" class="bg-blue-50 border border-blue-200 rounded-md p-3">
                              <div class="flex">
                                <div class="flex-shrink-0">
                                  <InformationCircleIcon class="h-5 w-5 text-blue-400" />
                                </div>
                                <div class="ml-3">
                                  <h4 class="text-sm font-medium text-blue-900">Using Inherited Tax Settings</h4>
                                  <div class="mt-1 text-sm text-blue-700">
                                    <p>Tax Rate: <strong>{{ getEffectiveTaxRateForForm() }}%</strong></p>
                                    <p>Application Mode: <strong>{{ getEffectiveTaxApplicationModeDisplayForForm() }}</strong></p>
                                    <p class="text-xs mt-1 text-blue-600">
                                      These settings come from account or system defaults. Enable "Override Tax Settings" to customize.
                                    </p>
                                  </div>
                                </div>
                              </div>
                            </div>

                            <div v-if="form.override_tax">
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
                            </div>
                            
                            <div v-if="form.override_tax">
                              <label for="tax_application_mode" class="block text-sm font-medium text-gray-700">
                                Apply Tax To
                              </label>
                              <select
                                id="tax_application_mode"
                                v-model="form.tax_application_mode"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                              >
                                <option value="all_items">All Taxable Items</option>
                                <option value="non_service_items">Products Only (No Services)</option>
                                <option value="custom">Custom (Per Item)</option>
                              </select>
                              <p class="mt-1 text-xs text-gray-500">
                                <span v-if="form.tax_application_mode === 'all_items'">Tax applies to both services and products</span>
                                <span v-else-if="form.tax_application_mode === 'non_service_items'">Tax applies only to products/addons, not time entries</span>
                                <span v-else>Tax applies based on individual item settings</span>
                              </p>
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
import { XMarkIcon, DocumentIcon, InformationCircleIcon } from '@heroicons/vue/24/outline'
import UnifiedSelector from '@/Components/UI/UnifiedSelector.vue'
import UnbilledItemsSelector from '@/Components/Billing/UnbilledItemsSelector.vue'
import { useUnbilledItemsQuery, useCreateInvoiceMutation } from '@/Composables/queries/useBillingQuery.js'
import { useQuery } from '@tanstack/vue-query'

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
  tax_application_mode: 'all_items',
  override_tax: false,
  notes: ''
})

// TanStack Query hooks
const createInvoiceMutation = useCreateInvoiceMutation()

// Load tax settings
const { data: taxSettings } = useQuery({
  queryKey: ['taxSettings'],
  queryFn: async () => {
    const response = await window.axios.get('/api/settings/tax')
    return response.data.data
  },
  staleTime: 1000 * 60 * 5, // 5 minutes
})

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

// Helper functions
const isTimeEntryTaxable = (timeEntry, effectiveMode = null) => {
  // If the time entry has an explicit taxable setting, use that
  if (timeEntry.is_taxable !== null && timeEntry.is_taxable !== undefined) {
    return timeEntry.is_taxable
  }
  
  // Use effective mode to determine taxability
  const mode = effectiveMode || (form.override_tax 
    ? form.tax_application_mode 
    : (taxSettings.value?.default_application_mode || 'all_items'))
  
  switch (mode) {
    case 'all_items':
      // All taxable items mode: time entries are taxable by default
      return true
    case 'non_service_items':
      // Time entries are never taxed in this mode
      return false
    case 'custom':
      // Only explicitly marked as taxable
      return timeEntry.is_taxable === true
    default:
      // Default to all items mode behavior
      return true
  }
}

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
  // Use effective tax application mode (override or system default)
  const effectiveMode = form.override_tax 
    ? form.tax_application_mode 
    : (taxSettings.value?.default_application_mode || 'all_items')
  
  // Calculate subtotal based on effective tax application mode
  let timeEntryTaxable = 0
  let addonTaxable = 0
  
  if (effectiveMode === 'all_items') {
    // Apply to all taxable items - respect system setting for time entries
    timeEntryTaxable = selectedItems.value.time_entries
      .filter(item => isTimeEntryTaxable(item, effectiveMode))
      .reduce((sum, item) => sum + parseFloat(item.total_amount || 0), 0)
      
    addonTaxable = selectedItems.value.ticket_addons
      .filter(item => item.is_taxable === true) // Only explicitly taxable addons
      .reduce((sum, item) => sum + parseFloat(item.total_amount || 0), 0)
  } else if (effectiveMode === 'non_service_items') {
    // Apply only to non-service items (addons/products), not time entries
    timeEntryTaxable = 0 // Services are not taxed
    
    addonTaxable = selectedItems.value.ticket_addons
      .filter(item => item.is_taxable === true) // Only explicitly taxable addons
      .reduce((sum, item) => sum + parseFloat(item.total_amount || 0), 0)
  } else if (effectiveMode === 'custom') {
    // Use individual item taxable settings - respect system setting for time entries
    timeEntryTaxable = selectedItems.value.time_entries
      .filter(item => isTimeEntryTaxable(item, effectiveMode))
      .reduce((sum, item) => sum + parseFloat(item.total_amount || 0), 0)
      
    addonTaxable = selectedItems.value.ticket_addons
      .filter(item => item.is_taxable === true)
      .reduce((sum, item) => sum + parseFloat(item.total_amount || 0), 0)
  }
  
  return timeEntryTaxable + addonTaxable
})

const invoiceTax = computed(() => {
  // Use effective tax rate (override or system default)
  const effectiveRate = form.override_tax 
    ? parseFloat(form.tax_rate || 0)
    : parseFloat(taxSettings.value?.default_rate || 0)
  
  // Apply effective tax rate only to taxable items
  return taxableSubtotal.value * (effectiveRate / 100)
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

// Initialize form with tax settings when they load
watch(taxSettings, (newSettings) => {
  if (newSettings && !form.override_tax) {
    form.tax_rate = parseFloat(newSettings.default_rate || 0)
    form.tax_application_mode = newSettings.default_application_mode || 'all_items'
  }
}, { immediate: true })

// Watch for override_tax changes to reset tax settings
watch(() => form.override_tax, (isOverride) => {
  if (!isOverride && taxSettings.value) {
    // Reset to system defaults when override is disabled
    form.tax_rate = parseFloat(taxSettings.value.default_rate || 0)
    form.tax_application_mode = taxSettings.value.default_application_mode || 'all_items'
  }
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
  
  // Inherit account tax preferences (only when override is disabled)
  if (account && !form.override_tax) {
    form.tax_rate = account.default_tax_rate || 0
    form.tax_application_mode = account.default_tax_application_mode || 'all_items'
  }
}

// Helper methods for tax inheritance display
const getEffectiveTaxRateForForm = () => {
  if (form.override_tax) {
    return form.tax_rate || 0;
  }
  
  // Get from selected account or system default (via API)
  // For now, use form.tax_rate which gets populated from account selection
  return form.tax_rate || 0;
};

const getEffectiveTaxApplicationModeForForm = () => {
  if (form.override_tax) {
    return form.tax_application_mode || 'all_items';
  }
  
  // Get from selected account or system default
  return form.tax_application_mode || 'all_items';
};

const getEffectiveTaxApplicationModeDisplayForForm = () => {
  const mode = getEffectiveTaxApplicationModeForForm();
  switch (mode) {
    case 'all_items': return 'All Taxable Items';
    case 'non_service_items': return 'Products Only (No Services)';
    case 'custom': return 'Custom (Per Item)';
    default: return 'All Taxable Items';
  }
};


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
    tax_application_mode: form.tax_application_mode,
    override_tax: form.override_tax,
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
    tax_application_mode: 'all_items',
    override_tax: false,
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