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
                  
                  <div class="mt-4 space-y-4">
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
                          placeholder="e.g., Standard Rate, Senior Developer Rate"
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
                          rows="2"
                          placeholder="Brief description of this billing rate..."
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
                        </div>
                      </div>
                    </div>

                    <!-- Account/User Assignment -->
                    <div>
                      <label class="block text-sm font-medium leading-6 text-gray-900">
                        Assignment Type
                      </label>
                      <div class="mt-2 space-y-2">
                        <label class="flex items-center">
                          <input
                            v-model="form.assignment_type"
                            type="radio"
                            value="global"
                            class="h-4 w-4 text-indigo-600 focus:ring-indigo-600 border-gray-300"
                          />
                          <span class="ml-2 text-sm text-gray-700">Global Rate (default for all users)</span>
                        </label>
                        <label class="flex items-center">
                          <input
                            v-model="form.assignment_type"
                            type="radio"
                            value="account"
                            class="h-4 w-4 text-indigo-600 focus:ring-indigo-600 border-gray-300"
                          />
                          <span class="ml-2 text-sm text-gray-700">Account-specific Rate</span>
                        </label>
                        <label class="flex items-center">
                          <input
                            v-model="form.assignment_type"
                            type="radio"
                            value="user"
                            class="h-4 w-4 text-indigo-600 focus:ring-indigo-600 border-gray-300"
                          />
                          <span class="ml-2 text-sm text-gray-700">User-specific Rate</span>
                        </label>
                      </div>
                    </div>

                    <!-- Active Status -->
                    <div>
                      <label class="flex items-center">
                        <input
                          v-model="form.is_active"
                          type="checkbox"
                          class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-600"
                        />
                        <span class="ml-2 text-sm text-gray-700">Active</span>
                      </label>
                      <p class="mt-1 text-xs text-gray-500">Only active rates will be available for billing</p>
                    </div>
                  </div>
                </div>
                
                <div class="mt-5 sm:mt-6 sm:flex sm:flex-row-reverse">
                  <button
                    type="submit"
                    :disabled="isSaving"
                    class="inline-flex w-full justify-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 sm:ml-3 sm:w-auto disabled:opacity-50"
                  >
                    <span v-if="isSaving">Saving...</span>
                    <span v-else>{{ isEditing ? 'Update Rate' : 'Create Rate' }}</span>
                  </button>
                  <button
                    type="button"
                    class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:mt-0 sm:w-auto"
                    @click="$emit('close')"
                  >
                    Cancel
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
import { useCreateBillingRateMutation, useUpdateBillingRateMutation } from '@/Composables/queries/useBillingQuery'

const props = defineProps({
  show: Boolean,
  rate: Object
})

const emit = defineEmits(['close', 'saved'])

// TanStack Query mutations
const createMutation = useCreateBillingRateMutation()
const updateMutation = useUpdateBillingRateMutation()

const isSaving = computed(() => createMutation.isPending.value || updateMutation.isPending.value)
const isEditing = computed(() => !!props.rate?.id)

// Form data
const form = reactive({
  name: '',
  description: '',
  rate: 0,
  assignment_type: 'global',
  is_active: true
})

// Watch for rate changes and populate form
watch(() => props.rate, (rate) => {
  if (rate) {
    Object.assign(form, {
      name: rate.name || '',
      description: rate.description || '',
      rate: rate.rate || 0,
      assignment_type: rate.account_id ? 'account' : rate.user_id ? 'user' : 'global',
      is_active: rate.is_active ?? true
    })
  } else {
    resetForm()
  }
}, { immediate: true })

// Reset form
const resetForm = () => {
  Object.assign(form, {
    name: '',
    description: '',
    rate: 0,
    assignment_type: 'global',
    is_active: true
  })
}

// Save rate
const saveRate = async () => {
  try {
    const data = {
      name: form.name,
      description: form.description,
      rate: parseFloat(form.rate),
      is_active: form.is_active
    }

    if (isEditing.value) {
      await updateMutation.mutateAsync({ id: props.rate.id, data })
    } else {
      await createMutation.mutateAsync(data)
    }
    
    emit('saved')
    resetForm()
  } catch (error) {
    console.error('Failed to save billing rate:', error)
  }
}
</script>