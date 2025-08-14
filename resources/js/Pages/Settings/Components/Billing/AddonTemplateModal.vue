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
            <DialogPanel class="relative transform overflow-hidden rounded-lg bg-white px-4 pb-4 pt-5 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-2xl sm:p-6">
              <form @submit.prevent="saveTemplate">
                <div>
                  <DialogTitle as="h3" class="text-base font-semibold leading-6 text-gray-900">
                    {{ isEditing ? 'Edit Addon Template' : 'Create Addon Template' }}
                  </DialogTitle>
                  
                  <div class="mt-4 space-y-6">
                    <!-- Basic Information -->
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                      <!-- Name -->
                      <div class="sm:col-span-2">
                        <label for="name" class="block text-sm font-medium leading-6 text-gray-900">
                          Template Name <span class="text-red-500">*</span>
                        </label>
                        <div class="mt-2">
                          <input
                            id="name"
                            v-model="form.name"
                            type="text"
                            required
                            placeholder="e.g., SSL Certificate, Monthly Support"
                            class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
                          />
                        </div>
                      </div>

                      <!-- Description -->
                      <div class="sm:col-span-2">
                        <label for="description" class="block text-sm font-medium leading-6 text-gray-900">
                          Description
                        </label>
                        <div class="mt-2">
                          <textarea
                            id="description"
                            v-model="form.description"
                            rows="2"
                            placeholder="Brief description of this addon template..."
                            class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
                          />
                        </div>
                      </div>

                      <!-- Category -->
                      <div>
                        <label for="category" class="block text-sm font-medium leading-6 text-gray-900">
                          Category <span class="text-red-500">*</span>
                        </label>
                        <div class="mt-2">
                          <select
                            id="category"
                            v-model="form.category"
                            required
                            class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
                          >
                            <option value="">Select category...</option>
                            <option value="product">Product</option>
                            <option value="service">Service</option>
                            <option value="license">License</option>
                            <option value="hardware">Hardware</option>
                            <option value="software">Software</option>
                            <option value="expense">Expense</option>
                            <option value="other">Other</option>
                          </select>
                        </div>
                      </div>

                      <!-- SKU -->
                      <div>
                        <label for="sku" class="block text-sm font-medium leading-6 text-gray-900">
                          SKU
                        </label>
                        <div class="mt-2">
                          <input
                            id="sku"
                            v-model="form.sku"
                            type="text"
                            placeholder="e.g., SSL-CERT-001"
                            class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
                          />
                        </div>
                      </div>
                    </div>

                    <!-- Pricing Information -->
                    <div class="border-t border-gray-200 pt-6">
                      <h4 class="text-md font-medium text-gray-900 mb-4">Default Pricing</h4>
                      <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <!-- Unit Price -->
                        <div>
                          <label for="default_unit_price" class="block text-sm font-medium leading-6 text-gray-900">
                            Unit Price <span class="text-red-500">*</span>
                          </label>
                          <div class="mt-2">
                            <div class="relative">
                              <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                <span class="text-gray-500 sm:text-sm">$</span>
                              </div>
                              <input
                                id="default_unit_price"
                                v-model="form.default_unit_price"
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

                        <!-- Default Quantity -->
                        <div>
                          <label for="default_quantity" class="block text-sm font-medium leading-6 text-gray-900">
                            Default Quantity <span class="text-red-500">*</span>
                          </label>
                          <div class="mt-2">
                            <input
                              id="default_quantity"
                              v-model="form.default_quantity"
                              type="number"
                              step="0.01"
                              min="0.01"
                              required
                              placeholder="1"
                              class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
                            />
                          </div>
                        </div>

                        <!-- Tax Rate -->
                        <div>
                          <label for="default_tax_rate" class="block text-sm font-medium leading-6 text-gray-900">
                            Tax Rate (%)
                          </label>
                          <div class="mt-2">
                            <input
                              id="default_tax_rate"
                              v-model="form.default_tax_rate"
                              type="number"
                              step="0.01"
                              min="0"
                              max="100"
                              placeholder="0"
                              class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
                            />
                          </div>
                        </div>
                      </div>
                    </div>

                    <!-- Configuration -->
                    <div class="border-t border-gray-200 pt-6">
                      <h4 class="text-md font-medium text-gray-900 mb-4">Configuration</h4>
                      <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <!-- Billing Category -->
                        <div>
                          <label for="billing_category" class="block text-sm font-medium leading-6 text-gray-900">
                            Billing Category
                          </label>
                          <div class="mt-2">
                            <select
                              id="billing_category"
                              v-model="form.billing_category"
                              class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
                            >
                              <option value="addon">Addon</option>
                              <option value="expense">Expense</option>
                              <option value="product">Product</option>
                              <option value="service">Service</option>
                            </select>
                          </div>
                        </div>

                        <!-- Checkboxes -->
                        <div class="space-y-4">
                          <label class="flex items-center">
                            <input
                              v-model="form.is_billable"
                              type="checkbox"
                              class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-600"
                            />
                            <span class="ml-2 text-sm text-gray-700">Billable by default</span>
                          </label>
                          <label class="flex items-center">
                            <input
                              v-model="form.is_taxable"
                              type="checkbox"
                              class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-600"
                            />
                            <span class="ml-2 text-sm text-gray-700">Taxable by default</span>
                          </label>
                          <label class="flex items-center">
                            <input
                              v-model="form.is_active"
                              type="checkbox"
                              class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-600"
                            />
                            <span class="ml-2 text-sm text-gray-700">Active template</span>
                          </label>
                        </div>
                      </div>
                    </div>

                    <!-- Preview -->
                    <div class="bg-gray-50 px-4 py-3 rounded-lg border-t border-gray-200">
                      <div class="flex justify-between text-sm">
                        <span>Preview Total:</span>
                        <span>{{ formatPrice(calculatedTotal) }}</span>
                      </div>
                      <div class="text-xs text-gray-500 mt-1">
                        {{ form.default_quantity }} × {{ formatPrice(form.default_unit_price) }}
                        <span v-if="form.default_tax_rate > 0"> + {{ form.default_tax_rate }}% tax</span>
                      </div>
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
                    <span v-else>{{ isEditing ? 'Update Template' : 'Create Template' }}</span>
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
import { useCreateAddonTemplateMutation, useUpdateAddonTemplateMutation } from '@/Composables/queries/useAddonTemplatesQuery'

const props = defineProps({
  show: Boolean,
  template: Object
})

const emit = defineEmits(['close', 'saved'])

// TanStack Query mutations
const createMutation = useCreateAddonTemplateMutation()
const updateMutation = useUpdateAddonTemplateMutation()

const isSaving = computed(() => createMutation.isPending.value || updateMutation.isPending.value)
const isEditing = computed(() => !!props.template?.id)

// Form data
const form = reactive({
  name: '',
  description: '',
  category: 'service',
  sku: '',
  default_unit_price: 0,
  default_quantity: 1,
  default_tax_rate: 0,
  billing_category: 'addon',
  is_billable: true,
  is_taxable: false,
  is_active: true
})

// Calculated total for preview
const calculatedTotal = computed(() => {
  const subtotal = (parseFloat(form.default_unit_price) || 0) * (parseFloat(form.default_quantity) || 0)
  const taxAmount = form.is_taxable ? subtotal * ((parseFloat(form.default_tax_rate) || 0) / 100) : 0
  return subtotal + taxAmount
})

// Reset form function - moved before watcher to fix initialization error
const resetForm = () => {
  Object.assign(form, {
    name: '',
    description: '',
    category: 'service',
    sku: '',
    default_unit_price: 0,
    default_quantity: 1,
    default_tax_rate: 0,
    billing_category: 'addon',
    is_billable: true,
    is_taxable: false,
    is_active: true
  })
}

// Watch for template changes and populate form
watch(() => props.template, (template) => {
  if (template) {
    Object.assign(form, {
      name: template.name || '',
      description: template.description || '',
      category: template.category || 'service',
      sku: template.sku || '',
      default_unit_price: template.default_unit_price || 0,
      default_quantity: template.default_quantity || 1,
      default_tax_rate: (template.default_tax_rate || 0) * 100, // Convert from decimal to percentage
      billing_category: template.billing_category || 'addon',
      is_billable: template.is_billable ?? true,
      is_taxable: template.is_taxable ?? false,
      is_active: template.is_active ?? true
    })
  } else {
    resetForm()
  }
}, { immediate: true })

// Format price helper
const formatPrice = (amount) => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD'
  }).format(amount || 0)
}

// Save template
const saveTemplate = async () => {
  try {
    const data = {
      name: form.name,
      description: form.description,
      category: form.category,
      sku: form.sku,
      default_unit_price: parseFloat(form.default_unit_price),
      default_quantity: parseFloat(form.default_quantity),
      default_tax_rate: (parseFloat(form.default_tax_rate) || 0) / 100, // Convert percentage to decimal
      billing_category: form.billing_category,
      is_billable: form.is_billable,
      is_taxable: form.is_taxable,
      is_active: form.is_active
    }

    if (isEditing.value) {
      await updateMutation.mutateAsync({ id: props.template.id, data })
    } else {
      await createMutation.mutateAsync(data)
    }
    
    emit('saved')
    resetForm()
  } catch (error) {
    console.error('Failed to save addon template:', error)
  }
}
</script>