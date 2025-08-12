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
            <DialogPanel class="relative transform overflow-hidden rounded-lg bg-white px-4 pt-5 pb-4 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-4xl sm:p-6">
              <div v-if="invoice">
                <div class="flex items-center justify-between mb-6">
                  <DialogTitle as="h3" class="text-lg font-medium leading-6 text-gray-900">
                    Invoice {{ invoice.invoice_number }}
                  </DialogTitle>
                  <button
                    @click="$emit('close')"
                    class="text-gray-400 hover:text-gray-500"
                  >
                    <XMarkIcon class="h-6 w-6" />
                  </button>
                </div>

                <!-- Invoice Header -->
                <div class="bg-gray-50 rounded-lg p-6 mb-6">
                  <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <div>
                      <h4 class="text-sm font-medium text-gray-900 mb-2">Invoice Details</h4>
                      <dl class="space-y-1">
                        <div class="flex justify-between">
                          <dt class="text-sm text-gray-500">Number:</dt>
                          <dd class="text-sm font-medium text-gray-900">{{ invoice.invoice_number }}</dd>
                        </div>
                        <div class="flex justify-between">
                          <dt class="text-sm text-gray-500">Date:</dt>
                          <dd class="text-sm text-gray-900">{{ formatDate(invoice.date) }}</dd>
                        </div>
                        <div class="flex justify-between">
                          <dt class="text-sm text-gray-500">Due Date:</dt>
                          <dd class="text-sm text-gray-900">{{ formatDate(invoice.due_date) }}</dd>
                        </div>
                        <div class="flex justify-between">
                          <dt class="text-sm text-gray-500">Status:</dt>
                          <dd>
                            <span
                              :class="getStatusBadgeClass(invoice.status)"
                              class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                            >
                              {{ formatStatus(invoice.status) }}
                            </span>
                          </dd>
                        </div>
                      </dl>
                    </div>
                    <div>
                      <h4 class="text-sm font-medium text-gray-900 mb-2">Account</h4>
                      <div class="text-sm text-gray-900">
                        {{ invoice.account?.name || 'N/A' }}
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Invoice Items -->
                <div class="mb-6">
                  <h4 class="text-sm font-medium text-gray-900 mb-4">Invoice Items</h4>
                  <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
                    <table class="min-w-full divide-y divide-gray-300">
                      <thead class="bg-gray-50">
                        <tr>
                          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Description
                          </th>
                          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Quantity
                          </th>
                          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Rate
                          </th>
                          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Amount
                          </th>
                        </tr>
                      </thead>
                      <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-if="!invoice.line_items || invoice.line_items.length === 0">
                          <td colspan="4" class="px-6 py-4 text-center text-gray-500">
                            No items found
                          </td>
                        </tr>
                        <tr v-else v-for="item in invoice.line_items" :key="item.id">
                          <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ item.description }}
                          </td>
                          <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ item.quantity }}
                          </td>
                          <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            ${{ formatCurrency(item.unit_price) }}
                          </td>
                          <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            ${{ formatCurrency(item.total_amount) }}
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </div>

                <!-- Invoice Total -->
                <div class="flex justify-end">
                  <div class="w-full max-w-sm">
                    <dl class="space-y-2">
                      <div class="flex justify-between">
                        <dt class="text-sm text-gray-500">Subtotal:</dt>
                        <dd class="text-sm text-gray-900">
                          ${{ formatCurrency(invoice.subtotal_amount) }}
                        </dd>
                      </div>
                      <div class="flex justify-between">
                        <dt class="text-sm text-gray-500">Tax:</dt>
                        <dd class="text-sm text-gray-900">
                          ${{ formatCurrency(invoice.tax_amount) }}
                        </dd>
                      </div>
                      <div class="flex justify-between border-t border-gray-200 pt-2">
                        <dt class="text-base font-medium text-gray-900">Total:</dt>
                        <dd class="text-base font-medium text-gray-900">
                          ${{ formatCurrency(invoice.total_amount) }}
                        </dd>
                      </div>
                      <div v-if="invoice.paid_amount > 0" class="flex justify-between">
                        <dt class="text-sm text-gray-500">Paid:</dt>
                        <dd class="text-sm text-green-600">
                          ${{ formatCurrency(invoice.paid_amount) }}
                        </dd>
                      </div>
                      <div v-if="invoice.paid_amount < invoice.total_amount" class="flex justify-between">
                        <dt class="text-sm font-medium text-gray-900">Outstanding:</dt>
                        <dd class="text-sm font-medium text-red-600">
                          ${{ formatCurrency(invoice.total_amount - invoice.paid_amount) }}
                        </dd>
                      </div>
                    </dl>
                  </div>
                </div>

                <!-- Actions -->
                <div class="mt-6 flex justify-end space-x-3">
                  <a
                    :href="`/api/billing/invoices/${invoice.id}/pdf`"
                    target="_blank"
                    class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                  >
                    Download PDF
                  </a>
                  <button
                    @click="$emit('close')"
                    class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                  >
                    Close
                  </button>
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
import {
  Dialog,
  DialogPanel,
  DialogTitle,
  TransitionChild,
  TransitionRoot,
} from '@headlessui/vue'
import { XMarkIcon } from '@heroicons/vue/24/outline'

defineProps({
  show: {
    type: Boolean,
    default: false
  },
  invoice: {
    type: Object,
    default: null
  }
})

defineEmits(['close'])

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('en-US', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  }).format(amount || 0)
}

const formatDate = (date) => {
  if (!date) return 'N/A'
  return new Date(date).toLocaleDateString()
}

const formatStatus = (status) => {
  return status.charAt(0).toUpperCase() + status.slice(1)
}

const getStatusBadgeClass = (status) => {
  const classes = {
    draft: 'bg-gray-100 text-gray-800',
    sent: 'bg-blue-100 text-blue-800',
    paid: 'bg-green-100 text-green-800',
    overdue: 'bg-red-100 text-red-800',
    cancelled: 'bg-gray-100 text-gray-800'
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
}
</script>