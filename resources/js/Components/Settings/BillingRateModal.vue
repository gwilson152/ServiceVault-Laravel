<template>
  <TransitionRoot as="template" :show="show">
    <Dialog as="div" class="relative z-50" @close="$emit('close')">
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

      <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
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
            <DialogPanel class="relative transform overflow-hidden rounded-lg bg-white px-4 pb-4 pt-5 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:p-6">
              <form @submit.prevent="saveRate">
                <div>
                  <DialogTitle as="h3" class="text-base font-semibold leading-6 text-gray-900">
                    {{ isEditing ? 'Edit Billing Rate' : 'Create Billing Rate' }}
                  </DialogTitle>
                  
                  <div class="mt-6 space-y-6">
                    <!-- Account Context (if provided) -->
                    <div v-if="accountId" class="bg-blue-50 border border-blue-200 rounded-md p-3">
                      <div class="flex">
                        <div class="flex-shrink-0">
                          <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a.75.75 0 000 1.5h.253a.25.25 0 01.244.304l-.459 2.066A1.75 1.75 0 0010.747 15H11a.75.75 0 000-1.5h-.253a.25.25 0 01-.244-.304l.459-2.066A1.75 1.75 0 009.253 9H9z" clip-rule="evenodd" />
                          </svg>
                        </div>
                        <div class="ml-3">
                          <p class="text-sm text-blue-800">
                            Creating account-specific billing rate override
                          </p>
                        </div>
                      </div>
                    </div>

                    <!-- Name -->
                    <div>
                      <label for="name" class="block text-sm font-medium leading-6 text-gray-900">
                        Rate Name <span class="text-red-500">*</span>
                      </label>
                      <div class="mt-2">
                        <input
                          id="name"
                          v-model="form.name"
                          type="text"
                          required
                          placeholder="e.g., Senior Developer, Project Manager"
                          class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
                        />
                      </div>
                    </div>

                    <!-- Description -->
                    <div>
                      <label for="description" class="block text-sm font-medium leading-6 text-gray-900">
                        Description
                      </label>
                      <div class="mt-2">
                        <textarea
                          id="description"
                          v-model="form.description"
                          rows="3"
                          placeholder="Optional description of this rate..."
                          class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
                        />
                      </div>
                    </div>

                    <!-- Rate -->
                    <div>
                      <label for="rate" class="block text-sm font-medium leading-6 text-gray-900">
                        Hourly Rate <span class="text-red-500">*</span>
                      </label>
                      <div class="mt-2">
                        <div class="relative">
                          <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                            <span class="text-gray-500 sm:text-sm">$</span>
                          </div>
                          <input
                            id="rate"
                            v-model="form.rate"
                            type="number"
                            step="0.01"
                            min="0"
                            required
                            placeholder="0.00"
                            class="block w-full rounded-md border-0 py-1.5 pl-8 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
                          />
                          <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
                            <span class="text-gray-500 sm:text-sm">per hour</span>
                          </div>
                        </div>
                      </div>
                    </div>

                    <!-- Default Checkbox -->
                    <div class="flex items-center">
                      <input
                        id="is_default"
                        v-model="form.is_default"
                        type="checkbox"
                        class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-600"
                      />
                      <label for="is_default" class="ml-2 block text-sm text-gray-900">
                        {{ accountId ? 'Set as default rate for this account' : 'Set as global default rate' }}
                      </label>
                    </div>

                    <!-- Active Checkbox -->
                    <div class="flex items-center">
                      <input
                        id="is_active"
                        v-model="form.is_active"
                        type="checkbox"
                        class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-600"
                      />
                      <label for="is_active" class="ml-2 block text-sm text-gray-900">
                        Rate is active and available for use
                      </label>
                    </div>
                  </div>
                </div>
                
                <div class="mt-6 flex items-center justify-end space-x-3">
                  <button
                    type="button"
                    class="rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50"
                    @click="$emit('close')"
                  >
                    Cancel
                  </button>
                  <button
                    type="submit"
                    :disabled="saving"
                    class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 disabled:opacity-50"
                  >
                    <span v-if="saving">Saving...</span>
                    <span v-else>{{ isEditing ? 'Update Rate' : 'Create Rate' }}</span>
                  </button>
                </div>
              </form>
            </DialogPanel>
          </TransitionChild>
        </div>
      </div>
    </Dialog>
  </TransitionRoot>
</template>

<script setup>
import { Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot } from '@headlessui/vue'
import { ref, reactive, computed, watch } from 'vue'
import axios from 'axios'

const props = defineProps({
  show: Boolean,
  rate: Object,
  accountId: [String, Number]
})

const emit = defineEmits(['close', 'saved'])

// Form data
const form = reactive({
  name: '',
  description: '',
  rate: 0,
  is_active: true,
  is_default: false
})

const saving = ref(false)

// Computed
const isEditing = computed(() => !!props.rate?.id)

// Watch for rate changes to populate form
watch(() => props.rate, (rate) => {
  if (rate) {
    form.name = rate.name || ''
    form.description = rate.description || ''
    form.rate = parseFloat(rate.rate) || 0
    form.is_active = rate.is_active ?? true
    form.is_default = rate.is_default ?? false
  }
})

// Watch for modal show/hide to reset form when creating new rate
watch(() => props.show, (show) => {
  if (show && !props.rate) {
    form.name = ''
    form.description = ''
    form.rate = 0
    form.is_active = true
    form.is_default = false
  }
})

// Save rate
const saveRate = async () => {
  try {
    saving.value = true
    const rateData = { 
      ...form,
      account_id: props.accountId || null 
    }
    
    if (isEditing.value) {
      await axios.put(`/api/billing-rates/${props.rate.id}`, rateData)
    } else {
      await axios.post('/api/billing-rates', rateData)
    }
    
    emit('saved')
  } catch (error) {
    console.error('Failed to save billing rate:', error)
    alert('Failed to save billing rate. Please try again.')
  } finally {
    saving.value = false
  }
}
</script>