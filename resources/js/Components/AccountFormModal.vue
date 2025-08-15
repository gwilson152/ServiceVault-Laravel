<script setup>
import { ref, watch, computed, nextTick } from 'vue'
import { useCreateAccountMutation, useUpdateAccountMutation, useAccountSelectorQuery } from '@/Composables/queries/useAccountsQuery'

const props = defineProps({
    open: {
        type: Boolean,
        default: false
    },
    account: {
        type: Object,
        default: null
    }
})

const emit = defineEmits(['close', 'saved'])

// Dialog ref for native dialog management
const dialogRef = ref(null)

const form = ref({
    name: '',
    company_name: '',
    account_type: 'customer',
    description: '',
    parent_id: null,
    contact_person: '',
    email: '',
    phone: '',
    website: '',
    address: '',
    city: '',
    state: '',
    postal_code: '',
    country: '',
    billing_address: '',
    billing_city: '',
    billing_state: '',
    billing_postal_code: '',
    billing_country: '',
    tax_id: '',
    notes: '',
    is_active: true
})

const errors = ref({})

// TanStack Query hooks
const { data: availableParents, isLoading: loadingParents } = useAccountSelectorQuery()
const createMutation = useCreateAccountMutation()
const updateMutation = useUpdateAccountMutation()

const isEditing = computed(() => !!props.account?.id)
const modalTitle = computed(() => isEditing.value ? 'Edit Account' : 'Create Account')
const saving = computed(() => createMutation.isPending.value || updateMutation.isPending.value)

// Watch for changes to populate form and manage dialog
watch(() => props.open, async (isOpen) => {
    if (isOpen) {
        if (props.account) {
            // Editing existing account
            form.value = {
                name: props.account.name || '',
                company_name: props.account.company_name || '',
                account_type: props.account.account_type || 'customer',
                description: props.account.description || '',
                parent_id: props.account.parent_id || null,
                contact_person: props.account.contact_person || '',
                email: props.account.email || '',
                phone: props.account.phone || '',
                website: props.account.website || '',
                address: props.account.address || '',
                city: props.account.city || '',
                state: props.account.state || '',
                postal_code: props.account.postal_code || '',
                country: props.account.country || '',
                billing_address: props.account.billing_address || '',
                billing_city: props.account.billing_city || '',
                billing_state: props.account.billing_state || '',
                billing_postal_code: props.account.billing_postal_code || '',
                billing_country: props.account.billing_country || '',
                tax_id: props.account.tax_id || '',
                notes: props.account.notes || '',
                is_active: props.account.is_active ?? true
            }
        } else {
            // Creating new account
            resetForm()
        }
        errors.value = {}
        
        // Open dialog with a small delay to ensure it appears on top
        await nextTick()
        setTimeout(() => {
            if (dialogRef.value) {
                dialogRef.value.showModal()
            }
        }, 50)
    } else {
        // Close dialog
        if (dialogRef.value) {
            dialogRef.value.close()
        }
    }
})

const resetForm = () => {
    form.value = {
        name: '',
        company_name: '',
        account_type: 'customer',
        description: '',
        parent_id: null,
        contact_person: '',
        email: '',
        phone: '',
        website: '',
        address: '',
        city: '',
        state: '',
        postal_code: '',
        country: '',
        billing_address: '',
        billing_city: '',
        billing_state: '',
        billing_postal_code: '',
        billing_country: '',
        tax_id: '',
        notes: '',
        is_active: true
    }
}

const saveAccount = async () => {
    try {
        errors.value = {}
        
        // Ensure CSRF token is fresh before making the request
        await window.axios.get('/sanctum/csrf-cookie')
        
        if (isEditing.value) {
            const result = await updateMutation.mutateAsync({
                id: props.account.id,
                data: form.value
            })
            emit('saved', result.data.data)
        } else {
            const result = await createMutation.mutateAsync(form.value)
            emit('saved', result.data.data)
        }
        
        emit('close')
    } catch (error) {
        console.error('Account save error:', error)
        if (error.response?.data?.errors) {
            errors.value = error.response.data.errors
        } else if (error.response?.status === 419) {
            errors.value = { general: ['CSRF token mismatch. Please refresh the page and try again.'] }
        } else {
            errors.value = { general: ['An error occurred while saving the account.'] }
        }
    }
}

const copyAddressToBilling = () => {
    form.value.billing_address = form.value.address
    form.value.billing_city = form.value.city
    form.value.billing_state = form.value.state
    form.value.billing_postal_code = form.value.postal_code
    form.value.billing_country = form.value.country
}

const closeModal = () => {
    emit('close')
}

const flattenAccountTree = (accounts, depth = 0) => {
    let flattened = []
    for (const account of accounts) {
        // Don't show current account as a parent option when editing
        if (!isEditing.value || account.id !== props.account?.id) {
            flattened.push({
                ...account,
                display_name: '  '.repeat(depth) + account.name
            })
        }
        if (account.children && account.children.length > 0) {
            flattened.push(...flattenAccountTree(account.children, depth + 1))
        }
    }
    return flattened
}

const flatParents = computed(() => flattenAccountTree(availableParents.value?.data || []))
</script>

<template>
    <!-- Native dialog for proper stacking context -->
    <dialog
        ref="dialogRef"
        class="backdrop:bg-gray-500 backdrop:bg-opacity-75 bg-transparent max-w-2xl w-full max-h-[90vh] rounded-lg"
        @close="closeModal"
    >
        <div class="bg-white rounded-lg shadow-xl w-full max-h-[90vh] overflow-y-auto">
            <!-- Modal header -->
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-medium text-gray-900">{{ modalTitle }}</h3>
                    <button
                        type="button"
                        @click="closeModal"
                        class="text-gray-400 hover:text-gray-600 focus:outline-none focus:text-gray-600"
                    >
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Modal body -->
            <form @submit.prevent="saveAccount" class="px-6 py-4">
                <!-- General error -->
                <div v-if="errors.general" class="mb-4 bg-red-50 border border-red-200 rounded-md p-3">
                    <div class="flex">
                        <svg class="h-5 w-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <div class="ml-3 text-sm text-red-700">
                            {{ errors.general[0] }}
                        </div>
                    </div>
                </div>

                <div class="space-y-6">
                    <!-- Basic Information Section -->
                    <div class="border-b border-gray-200 pb-4">
                        <h4 class="text-lg font-medium text-gray-900 mb-4">Basic Information</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Account Name -->
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700">Account Name *</label>
                                <input
                                    id="name"
                                    v-model="form.name"
                                    type="text"
                                    required
                                    placeholder="Account display name"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                    :class="{ 'border-red-300 focus:border-red-500 focus:ring-red-500': errors.name }"
                                />
                                <p v-if="errors.name" class="mt-1 text-sm text-red-600">{{ errors.name[0] }}</p>
                            </div>

                            <!-- Company Name -->
                            <div>
                                <label for="company_name" class="block text-sm font-medium text-gray-700">Company Name</label>
                                <input
                                    id="company_name"
                                    v-model="form.company_name"
                                    type="text"
                                    placeholder="Legal business name"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                    :class="{ 'border-red-300 focus:border-red-500 focus:ring-red-500': errors.company_name }"
                                />
                                <p v-if="errors.company_name" class="mt-1 text-sm text-red-600">{{ errors.company_name[0] }}</p>
                            </div>

                            <!-- Account Type -->
                            <div>
                                <label for="account_type" class="block text-sm font-medium text-gray-700">Account Type *</label>
                                <select
                                    id="account_type"
                                    v-model="form.account_type"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                    :class="{ 'border-red-300 focus:border-red-500 focus:ring-red-500': errors.account_type }"
                                >
                                    <option value="customer">Customer</option>
                                    <option value="prospect">Prospect</option>
                                    <option value="partner">Partner</option>
                                    <option value="internal">Internal</option>
                                </select>
                                <p v-if="errors.account_type" class="mt-1 text-sm text-red-600">{{ errors.account_type[0] }}</p>
                            </div>

                            <!-- Parent Account -->
                            <div>
                                <label for="parent_id" class="block text-sm font-medium text-gray-700">Parent Account</label>
                                <select
                                    id="parent_id"
                                    v-model="form.parent_id"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                    :class="{ 'border-red-300 focus:border-red-500 focus:ring-red-500': errors.parent_id }"
                                >
                                    <option :value="null">No parent (Root level)</option>
                                    <option v-for="parent in flatParents" :key="parent.id" :value="parent.id">
                                        {{ parent.display_name }}
                                    </option>
                                </select>
                                <p v-if="errors.parent_id" class="mt-1 text-sm text-red-600">{{ errors.parent_id[0] }}</p>
                            </div>
                        </div>

                        <!-- Description -->
                        <div class="mt-4">
                            <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
                            <textarea
                                id="description"
                                v-model="form.description"
                                rows="2"
                                placeholder="Brief description of this account..."
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                :class="{ 'border-red-300 focus:border-red-500 focus:ring-red-500': errors.description }"
                            />
                            <p v-if="errors.description" class="mt-1 text-sm text-red-600">{{ errors.description[0] }}</p>
                        </div>
                    </div>

                    <!-- Contact Information Section -->
                    <div class="border-b border-gray-200 pb-4">
                        <h4 class="text-lg font-medium text-gray-900 mb-4">Contact Information</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Contact Person -->
                            <div>
                                <label for="contact_person" class="block text-sm font-medium text-gray-700">Primary Contact</label>
                                <input
                                    id="contact_person"
                                    v-model="form.contact_person"
                                    type="text"
                                    placeholder="Contact person name"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                    :class="{ 'border-red-300 focus:border-red-500 focus:ring-red-500': errors.contact_person }"
                                />
                                <p v-if="errors.contact_person" class="mt-1 text-sm text-red-600">{{ errors.contact_person[0] }}</p>
                            </div>

                            <!-- Email -->
                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700">Email Address</label>
                                <input
                                    id="email"
                                    v-model="form.email"
                                    type="email"
                                    placeholder="contact@company.com"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                    :class="{ 'border-red-300 focus:border-red-500 focus:ring-red-500': errors.email }"
                                />
                                <p v-if="errors.email" class="mt-1 text-sm text-red-600">{{ errors.email[0] }}</p>
                            </div>

                            <!-- Phone -->
                            <div>
                                <label for="phone" class="block text-sm font-medium text-gray-700">Phone Number</label>
                                <input
                                    id="phone"
                                    v-model="form.phone"
                                    type="tel"
                                    placeholder="+1 (555) 123-4567"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                    :class="{ 'border-red-300 focus:border-red-500 focus:ring-red-500': errors.phone }"
                                />
                                <p v-if="errors.phone" class="mt-1 text-sm text-red-600">{{ errors.phone[0] }}</p>
                            </div>

                            <!-- Website -->
                            <div>
                                <label for="website" class="block text-sm font-medium text-gray-700">Website</label>
                                <input
                                    id="website"
                                    v-model="form.website"
                                    type="url"
                                    placeholder="https://company.com"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                    :class="{ 'border-red-300 focus:border-red-500 focus:ring-red-500': errors.website }"
                                />
                                <p v-if="errors.website" class="mt-1 text-sm text-red-600">{{ errors.website[0] }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Address Information Section -->
                    <div class="border-b border-gray-200 pb-4">
                        <h4 class="text-lg font-medium text-gray-900 mb-4">Address Information</h4>
                        <div class="space-y-4">
                            <!-- Street Address -->
                            <div>
                                <label for="address" class="block text-sm font-medium text-gray-700">Street Address</label>
                                <textarea
                                    id="address"
                                    v-model="form.address"
                                    rows="2"
                                    placeholder="123 Main Street&#10;Suite 100"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                    :class="{ 'border-red-300 focus:border-red-500 focus:ring-red-500': errors.address }"
                                />
                                <p v-if="errors.address" class="mt-1 text-sm text-red-600">{{ errors.address[0] }}</p>
                            </div>

                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                <!-- City -->
                                <div>
                                    <label for="city" class="block text-sm font-medium text-gray-700">City</label>
                                    <input
                                        id="city"
                                        v-model="form.city"
                                        type="text"
                                        placeholder="New York"
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                        :class="{ 'border-red-300 focus:border-red-500 focus:ring-red-500': errors.city }"
                                    />
                                    <p v-if="errors.city" class="mt-1 text-sm text-red-600">{{ errors.city[0] }}</p>
                                </div>

                                <!-- State -->
                                <div>
                                    <label for="state" class="block text-sm font-medium text-gray-700">State/Province</label>
                                    <input
                                        id="state"
                                        v-model="form.state"
                                        type="text"
                                        placeholder="NY"
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                        :class="{ 'border-red-300 focus:border-red-500 focus:ring-red-500': errors.state }"
                                    />
                                    <p v-if="errors.state" class="mt-1 text-sm text-red-600">{{ errors.state[0] }}</p>
                                </div>

                                <!-- Postal Code -->
                                <div>
                                    <label for="postal_code" class="block text-sm font-medium text-gray-700">Postal Code</label>
                                    <input
                                        id="postal_code"
                                        v-model="form.postal_code"
                                        type="text"
                                        placeholder="10001"
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                        :class="{ 'border-red-300 focus:border-red-500 focus:ring-red-500': errors.postal_code }"
                                    />
                                    <p v-if="errors.postal_code" class="mt-1 text-sm text-red-600">{{ errors.postal_code[0] }}</p>
                                </div>

                                <!-- Country -->
                                <div>
                                    <label for="country" class="block text-sm font-medium text-gray-700">Country</label>
                                    <input
                                        id="country"
                                        v-model="form.country"
                                        type="text"
                                        placeholder="United States"
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                        :class="{ 'border-red-300 focus:border-red-500 focus:ring-red-500': errors.country }"
                                    />
                                    <p v-if="errors.country" class="mt-1 text-sm text-red-600">{{ errors.country[0] }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Billing Information Section -->
                    <div class="border-b border-gray-200 pb-4">
                        <div class="flex items-center justify-between mb-4">
                            <h4 class="text-lg font-medium text-gray-900">Billing Information</h4>
                            <button
                                type="button"
                                @click="copyAddressToBilling"
                                class="text-sm text-indigo-600 hover:text-indigo-500"
                            >
                                Copy from address above
                            </button>
                        </div>
                        <div class="space-y-4">
                            <!-- Billing Street Address -->
                            <div>
                                <label for="billing_address" class="block text-sm font-medium text-gray-700">Billing Address</label>
                                <textarea
                                    id="billing_address"
                                    v-model="form.billing_address"
                                    rows="2"
                                    placeholder="123 Billing Street&#10;Suite 200"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                    :class="{ 'border-red-300 focus:border-red-500 focus:ring-red-500': errors.billing_address }"
                                />
                                <p v-if="errors.billing_address" class="mt-1 text-sm text-red-600">{{ errors.billing_address[0] }}</p>
                            </div>

                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                <!-- Billing City -->
                                <div>
                                    <label for="billing_city" class="block text-sm font-medium text-gray-700">City</label>
                                    <input
                                        id="billing_city"
                                        v-model="form.billing_city"
                                        type="text"
                                        placeholder="New York"
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                        :class="{ 'border-red-300 focus:border-red-500 focus:ring-red-500': errors.billing_city }"
                                    />
                                    <p v-if="errors.billing_city" class="mt-1 text-sm text-red-600">{{ errors.billing_city[0] }}</p>
                                </div>

                                <!-- Billing State -->
                                <div>
                                    <label for="billing_state" class="block text-sm font-medium text-gray-700">State/Province</label>
                                    <input
                                        id="billing_state"
                                        v-model="form.billing_state"
                                        type="text"
                                        placeholder="NY"
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                        :class="{ 'border-red-300 focus:border-red-500 focus:ring-red-500': errors.billing_state }"
                                    />
                                    <p v-if="errors.billing_state" class="mt-1 text-sm text-red-600">{{ errors.billing_state[0] }}</p>
                                </div>

                                <!-- Billing Postal Code -->
                                <div>
                                    <label for="billing_postal_code" class="block text-sm font-medium text-gray-700">Postal Code</label>
                                    <input
                                        id="billing_postal_code"
                                        v-model="form.billing_postal_code"
                                        type="text"
                                        placeholder="10001"
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                        :class="{ 'border-red-300 focus:border-red-500 focus:ring-red-500': errors.billing_postal_code }"
                                    />
                                    <p v-if="errors.billing_postal_code" class="mt-1 text-sm text-red-600">{{ errors.billing_postal_code[0] }}</p>
                                </div>

                                <!-- Billing Country -->
                                <div>
                                    <label for="billing_country" class="block text-sm font-medium text-gray-700">Country</label>
                                    <input
                                        id="billing_country"
                                        v-model="form.billing_country"
                                        type="text"
                                        placeholder="United States"
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                        :class="{ 'border-red-300 focus:border-red-500 focus:ring-red-500': errors.billing_country }"
                                    />
                                    <p v-if="errors.billing_country" class="mt-1 text-sm text-red-600">{{ errors.billing_country[0] }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Business Details Section -->
                    <div>
                        <h4 class="text-lg font-medium text-gray-900 mb-4">Business Details</h4>
                        <div class="space-y-4">
                            <!-- Tax ID -->
                            <div>
                                <label for="tax_id" class="block text-sm font-medium text-gray-700">Tax ID / VAT Number</label>
                                <input
                                    id="tax_id"
                                    v-model="form.tax_id"
                                    type="text"
                                    placeholder="12-3456789 or VAT123456789"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                    :class="{ 'border-red-300 focus:border-red-500 focus:ring-red-500': errors.tax_id }"
                                />
                                <p v-if="errors.tax_id" class="mt-1 text-sm text-red-600">{{ errors.tax_id[0] }}</p>
                            </div>

                            <!-- Notes -->
                            <div>
                                <label for="notes" class="block text-sm font-medium text-gray-700">Internal Notes</label>
                                <textarea
                                    id="notes"
                                    v-model="form.notes"
                                    rows="3"
                                    placeholder="Internal notes about this account..."
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                    :class="{ 'border-red-300 focus:border-red-500 focus:ring-red-500': errors.notes }"
                                />
                                <p v-if="errors.notes" class="mt-1 text-sm text-red-600">{{ errors.notes[0] }}</p>
                            </div>

                            <!-- Active Status -->
                            <div>
                                <div class="flex items-center">
                                    <input
                                        id="is_active"
                                        v-model="form.is_active"
                                        type="checkbox"
                                        class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                                    />
                                    <label for="is_active" class="ml-2 block text-sm text-gray-900">
                                        Account is active
                                    </label>
                                </div>
                                <p class="mt-1 text-xs text-gray-500">Inactive accounts cannot be used for new tickets or time entries.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modal footer -->
                <div class="mt-6 flex items-center justify-end space-x-2">
                    <button
                        type="button"
                        @click="closeModal"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                    >
                        Cancel
                    </button>
                    <button
                        type="submit"
                        :disabled="saving"
                        class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-md shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <svg v-if="saving" class="inline w-4 h-4 mr-2 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        {{ saving ? 'Saving...' : (isEditing ? 'Update Account' : 'Create Account') }}
                    </button>
                </div>
            </form>
        </div>
    </dialog>
</template>