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
            <DialogPanel class="relative transform overflow-hidden rounded-lg bg-white px-4 pt-5 pb-4 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-2xl sm:p-6">
              <div>
                <div class="flex items-center justify-between mb-4">
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

                <form @submit.prevent="handleSubmit" class="space-y-6">
                  <!-- Account Selection -->
                  <div>
                    <label for="account_id" class="block text-sm font-medium text-gray-700">
                      Account
                    </label>
                    <select
                      id="account_id"
                      v-model="form.account_id"
                      required
                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                      <option value="">Select an account</option>
                      <option value="account-1">Sample Account 1</option>
                      <option value="account-2">Sample Account 2</option>
                    </select>
                  </div>

                  <!-- Invoice Details -->
                  <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <div>
                      <label for="invoice_date" class="block text-sm font-medium text-gray-700">
                        Invoice Date
                      </label>
                      <input
                        id="invoice_date"
                        v-model="form.invoice_date"
                        type="date"
                        required
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
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
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                      />
                    </div>
                  </div>

                  <!-- Description -->
                  <div>
                    <label for="description" class="block text-sm font-medium text-gray-700">
                      Description
                    </label>
                    <textarea
                      id="description"
                      v-model="form.description"
                      rows="3"
                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                      placeholder="Invoice description..."
                    />
                  </div>

                  <!-- Time Entries Option -->
                  <div class="flex items-center">
                    <input
                      id="include_time_entries"
                      v-model="form.include_time_entries"
                      type="checkbox"
                      class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                    />
                    <label for="include_time_entries" class="ml-2 block text-sm text-gray-900">
                      Include unbilled time entries
                    </label>
                  </div>

                  <!-- Submit Buttons -->
                  <div class="mt-5 sm:mt-6 sm:grid sm:grid-flow-row-dense sm:grid-cols-2 sm:gap-3">
                    <button
                      type="submit"
                      :disabled="creating"
                      class="inline-flex w-full justify-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-base font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-50 sm:col-start-2 sm:text-sm"
                    >
                      <span v-if="creating" class="mr-2">
                        <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-white"></div>
                      </span>
                      {{ creating ? 'Creating...' : 'Create Invoice' }}
                    </button>
                    <button
                      type="button"
                      @click="$emit('close')"
                      class="mt-3 inline-flex w-full justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-base font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 sm:col-start-1 sm:mt-0 sm:text-sm"
                    >
                      Cancel
                    </button>
                  </div>
                </form>
              </div>
            </DialogPanel>
          </TransitionChild>
        </div>
      </div>
    </Dialog>
  </TransitionRoot>
</template>

<script setup>
import { ref, reactive, watch } from 'vue'
import {
  Dialog,
  DialogPanel,
  DialogTitle,
  TransitionChild,
  TransitionRoot,
} from '@headlessui/vue'
import { XMarkIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  show: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['close', 'created'])

const creating = ref(false)

const form = reactive({
  account_id: '',
  invoice_date: new Date().toISOString().split('T')[0],
  due_date: '',
  description: '',
  include_time_entries: true
})

// Calculate due date (30 days from invoice date)
watch(() => form.invoice_date, (newDate) => {
  if (newDate) {
    const dueDate = new Date(newDate)
    dueDate.setDate(dueDate.getDate() + 30)
    form.due_date = dueDate.toISOString().split('T')[0]
  }
})

const handleSubmit = async () => {
  creating.value = true
  try {
    const response = await fetch('/api/billing/invoices', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
      },
      body: JSON.stringify(form)
    })

    if (response.ok) {
      const data = await response.json()
      emit('created', data.data)
      // Reset form
      Object.assign(form, {
        account_id: '',
        invoice_date: new Date().toISOString().split('T')[0],
        due_date: '',
        description: '',
        include_time_entries: true
      })
    } else {
      console.error('Error creating invoice')
    }
  } catch (error) {
    console.error('Error creating invoice:', error)
  } finally {
    creating.value = false
  }
}
</script>