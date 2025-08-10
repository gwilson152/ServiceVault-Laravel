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
                            <form @submit.prevent="submitAddon">
                                <div class="sm:flex sm:items-start">
                                    <div class="w-full">
                                        <DialogTitle as="h3" class="text-base font-semibold leading-6 text-gray-900">
                                            Add Addon to Ticket
                                        </DialogTitle>
                                        
                                        <div class="mt-4 space-y-6">
                                            <!-- Template Selection -->
                                            <div>
                                                <label for="template" class="block text-sm font-medium leading-6 text-gray-900">
                                                    Use Template (Optional)
                                                </label>
                                                <div class="mt-2">
                                                    <select
                                                        id="template"
                                                        v-model="form.selectedTemplate"
                                                        @change="applyTemplate"
                                                        class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
                                                    >
                                                        <option value="">Custom addon...</option>
                                                        <optgroup 
                                                            v-for="(templates, category) in groupedTemplates" 
                                                            :key="category" 
                                                            :label="categoryLabels[category]"
                                                        >
                                                            <option 
                                                                v-for="template in templates" 
                                                                :key="template.id" 
                                                                :value="template.id"
                                                            >
                                                                {{ template.name }} - {{ formatPrice(template.default_unit_price) }}
                                                            </option>
                                                        </optgroup>
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="grid grid-cols-1 gap-x-6 gap-y-4 sm:grid-cols-2">
                                                <!-- Name -->
                                                <div class="sm:col-span-2">
                                                    <label for="name" class="block text-sm font-medium leading-6 text-gray-900">
                                                        Name <span class="text-red-500">*</span>
                                                    </label>
                                                    <div class="mt-2">
                                                        <input
                                                            id="name"
                                                            v-model="form.name"
                                                            type="text"
                                                            required
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
                                                            rows="3"
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
                                                            class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
                                                        />
                                                    </div>
                                                </div>

                                                <!-- Unit Price -->
                                                <div>
                                                    <label for="unit_price" class="block text-sm font-medium leading-6 text-gray-900">
                                                        Unit Price <span class="text-red-500">*</span>
                                                    </label>
                                                    <div class="mt-2">
                                                        <div class="relative">
                                                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                                                <span class="text-gray-500 sm:text-sm">$</span>
                                                            </div>
                                                            <input
                                                                id="unit_price"
                                                                v-model="form.unit_price"
                                                                type="number"
                                                                step="0.01"
                                                                min="0"
                                                                required
                                                                class="block w-full rounded-md border-0 py-1.5 pl-8 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
                                                            />
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Quantity -->
                                                <div>
                                                    <label for="quantity" class="block text-sm font-medium leading-6 text-gray-900">
                                                        Quantity <span class="text-red-500">*</span>
                                                    </label>
                                                    <div class="mt-2">
                                                        <input
                                                            id="quantity"
                                                            v-model="form.quantity"
                                                            type="number"
                                                            step="0.01"
                                                            min="0.01"
                                                            required
                                                            class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
                                                        />
                                                    </div>
                                                </div>

                                                <!-- Discount Amount -->
                                                <div>
                                                    <label for="discount_amount" class="block text-sm font-medium leading-6 text-gray-900">
                                                        Discount Amount
                                                    </label>
                                                    <div class="mt-2">
                                                        <div class="relative">
                                                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                                                <span class="text-gray-500 sm:text-sm">$</span>
                                                            </div>
                                                            <input
                                                                id="discount_amount"
                                                                v-model="form.discount_amount"
                                                                type="number"
                                                                step="0.01"
                                                                min="0"
                                                                class="block w-full rounded-md border-0 py-1.5 pl-8 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
                                                            />
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Tax Rate -->
                                                <div>
                                                    <label for="tax_rate" class="block text-sm font-medium leading-6 text-gray-900">
                                                        Tax Rate (%)
                                                    </label>
                                                    <div class="mt-2">
                                                        <input
                                                            id="tax_rate"
                                                            v-model="form.tax_rate_percent"
                                                            type="number"
                                                            step="0.01"
                                                            min="0"
                                                            max="100"
                                                            class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
                                                        />
                                                    </div>
                                                </div>

                                                <!-- Billing Category -->
                                                <div>
                                                    <label for="billing_category" class="block text-sm font-medium leading-6 text-gray-900">
                                                        Billing Category <span class="text-red-500">*</span>
                                                    </label>
                                                    <div class="mt-2">
                                                        <select
                                                            id="billing_category"
                                                            v-model="form.billing_category"
                                                            required
                                                            class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
                                                        >
                                                            <option value="addon">Addon</option>
                                                            <option value="expense">Expense</option>
                                                            <option value="product">Product</option>
                                                            <option value="service">Service</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Checkboxes -->
                                            <div class="flex items-center space-x-6">
                                                <label class="flex items-center">
                                                    <input
                                                        v-model="form.is_billable"
                                                        type="checkbox"
                                                        class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-600"
                                                    />
                                                    <span class="ml-2 text-sm text-gray-700">Billable</span>
                                                </label>
                                                <label class="flex items-center">
                                                    <input
                                                        v-model="form.is_taxable"
                                                        type="checkbox"
                                                        class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-600"
                                                    />
                                                    <span class="ml-2 text-sm text-gray-700">Taxable</span>
                                                </label>
                                            </div>

                                            <!-- Calculated Total -->
                                            <div class="bg-gray-50 px-4 py-3 rounded-lg">
                                                <div class="flex justify-between text-sm">
                                                    <span>Subtotal:</span>
                                                    <span>{{ formatPrice(calculatedSubtotal) }}</span>
                                                </div>
                                                <div v-if="calculatedDiscount > 0" class="flex justify-between text-sm text-orange-600">
                                                    <span>Discount:</span>
                                                    <span>-{{ formatPrice(calculatedDiscount) }}</span>
                                                </div>
                                                <div v-if="calculatedTax > 0" class="flex justify-between text-sm text-gray-600">
                                                    <span>Tax:</span>
                                                    <span>{{ formatPrice(calculatedTax) }}</span>
                                                </div>
                                                <div class="flex justify-between text-base font-medium border-t pt-2 mt-2">
                                                    <span>Total:</span>
                                                    <span>{{ formatPrice(calculatedTotal) }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mt-5 sm:mt-6 sm:flex sm:flex-row-reverse">
                                    <button
                                        type="submit"
                                        :disabled="processing"
                                        class="inline-flex w-full justify-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 sm:ml-3 sm:w-auto disabled:opacity-50"
                                    >
                                        <span v-if="processing">Adding...</span>
                                        <span v-else>Add Addon</span>
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
import { ref, reactive, computed, watch, onMounted } from 'vue'
import axios from 'axios'

const props = defineProps({
    show: Boolean,
    ticket: Object
})

const emit = defineEmits(['close', 'added'])

// Form data
const form = reactive({
    selectedTemplate: '',
    name: '',
    description: '',
    category: '',
    sku: '',
    unit_price: 0,
    quantity: 1,
    discount_amount: 0,
    tax_rate_percent: 0,
    is_billable: true,
    is_taxable: false,
    billing_category: 'addon'
})

const processing = ref(false)
const templates = ref([])

const categoryLabels = {
    product: 'Products',
    service: 'Services', 
    license: 'Licenses',
    hardware: 'Hardware',
    software: 'Software',
    expense: 'Expenses',
    other: 'Other'
}

// Group templates by category
const groupedTemplates = computed(() => {
    return templates.value.reduce((groups, template) => {
        const category = template.category
        if (!groups[category]) {
            groups[category] = []
        }
        groups[category].push(template)
        return groups
    }, {})
})

// Calculated values
const calculatedSubtotal = computed(() => {
    return (parseFloat(form.unit_price) || 0) * (parseFloat(form.quantity) || 0)
})

const calculatedDiscount = computed(() => {
    return Math.min(parseFloat(form.discount_amount) || 0, calculatedSubtotal.value)
})

const calculatedTax = computed(() => {
    if (!form.is_taxable) return 0
    const afterDiscount = calculatedSubtotal.value - calculatedDiscount.value
    const taxRate = (parseFloat(form.tax_rate_percent) || 0) / 100
    return afterDiscount * taxRate
})

const calculatedTotal = computed(() => {
    return calculatedSubtotal.value - calculatedDiscount.value + calculatedTax.value
})

// Load templates
const loadTemplates = async () => {
    try {
        const response = await axios.get('/api/addon-templates')
        templates.value = response.data.data || []
    } catch (error) {
        console.error('Failed to load addon templates:', error)
    }
}

// Apply template
const applyTemplate = () => {
    if (!form.selectedTemplate) return
    
    const template = templates.value.find(t => t.id === parseInt(form.selectedTemplate))
    if (!template) return
    
    form.name = template.name
    form.description = template.description || ''
    form.category = template.category
    form.sku = template.sku || ''
    form.unit_price = parseFloat(template.default_unit_price)
    form.quantity = parseFloat(template.default_quantity)
    form.is_billable = template.is_billable
    form.is_taxable = template.is_taxable
    form.billing_category = template.billing_category
    form.tax_rate_percent = (parseFloat(template.default_tax_rate) * 100).toFixed(2)
}

// Reset form
const resetForm = () => {
    form.selectedTemplate = ''
    form.name = ''
    form.description = ''
    form.category = ''
    form.sku = ''
    form.unit_price = 0
    form.quantity = 1
    form.discount_amount = 0
    form.tax_rate_percent = 0
    form.is_billable = true
    form.is_taxable = false
    form.billing_category = 'addon'
}

// Submit addon
const submitAddon = async () => {
    processing.value = true
    
    try {
        const data = {
            ticket_id: props.ticket.id,
            name: form.name,
            description: form.description,
            category: form.category,
            sku: form.sku,
            unit_price: parseFloat(form.unit_price),
            quantity: parseFloat(form.quantity),
            discount_amount: parseFloat(form.discount_amount) || 0,
            tax_rate: (parseFloat(form.tax_rate_percent) || 0) / 100,
            is_billable: form.is_billable,
            is_taxable: form.is_taxable,
            billing_category: form.billing_category
        }
        
        const response = await axios.post('/api/ticket-addons', data)
        
        emit('added', response.data.data)
        emit('close')
        resetForm()
    } catch (error) {
        console.error('Failed to add addon:', error)
        // Handle error (could emit error event or show notification)
    } finally {
        processing.value = false
    }
}

// Format price helper
const formatPrice = (amount) => {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD'
    }).format(amount || 0)
}

// Watch for modal open/close
watch(() => props.show, (show) => {
    if (show) {
        resetForm()
        loadTemplates()
    }
})

onMounted(() => {
    loadTemplates()
})
</script>