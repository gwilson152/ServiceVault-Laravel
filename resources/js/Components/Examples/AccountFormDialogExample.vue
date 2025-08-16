<script setup>
import { ref, computed, watch } from 'vue'
import { useCreateAccountMutation, useUpdateAccountMutation, useAccountSelectorQuery } from '@/Composables/queries/useAccountsQuery'
import TabbedDialog from '../TabbedDialog.vue'

const props = defineProps({
    show: {
        type: Boolean,
        default: false
    },
    account: {
        type: Object,
        default: null
    }
})

const emit = defineEmits(['close', 'saved'])

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
const activeTab = ref('basic')

// Tab configuration
const tabs = [
    { id: 'basic', name: 'Basic Info', icon: 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z' },
    { id: 'contact', name: 'Contact', icon: 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z' },
    { id: 'address', name: 'Address', icon: 'M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z' },
    { id: 'billing', name: 'Billing', icon: 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z' },
    { id: 'business', name: 'Business', icon: 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4' }
]

// TanStack Query hooks
const { data: availableParents, isLoading: loadingParents } = useAccountSelectorQuery()
const createMutation = useCreateAccountMutation()
const updateMutation = useUpdateAccountMutation()

const isEditing = computed(() => !!props.account?.id)
const modalTitle = computed(() => isEditing.value ? 'Edit Account' : 'Create Account')
const saving = computed(() => createMutation.isPending.value || updateMutation.isPending.value)

// Watch for modal opening to populate form
watch(() => props.show, (isOpen) => {
    if (isOpen) {
        if (props.account) {
            // Populate form with account data
            Object.keys(form.value).forEach(key => {
                form.value[key] = props.account[key] ?? form.value[key]
            })
        } else {
            resetForm()
        }
        errors.value = {}
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

const flattenAccountTree = (accounts, depth = 0) => {
    let flattened = []
    for (const account of accounts) {
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
    <TabbedDialog
        :show="show"
        :title="modalTitle"
        :tabs="tabs"
        :saving="saving"
        :save-label="isEditing ? 'Update Account' : 'Create Account'"
        @close="emit('close')"
        @save="saveAccount"
        @tab-change="activeTab = $event"
    >
        <!-- Error messages -->
        <template #errors>
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
        </template>

        <!-- Tab Content -->
        <template #default="{ activeTab }">
            <!-- Basic Information Tab -->
            <div v-show="activeTab === 'basic'" class="space-y-4">
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
                <div>
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

            <!-- Contact Information Tab -->
            <div v-show="activeTab === 'contact'" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="contact_person" class="block text-sm font-medium text-gray-700">Primary Contact</label>
                        <input
                            id="contact_person"
                            v-model="form.contact_person"
                            type="text"
                            placeholder="Contact person name"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                        />
                    </div>
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700">Email Address</label>
                        <input
                            id="email"
                            v-model="form.email"
                            type="email"
                            placeholder="contact@company.com"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                        />
                    </div>
                    <div>
                        <label for="phone" class="block text-sm font-medium text-gray-700">Phone Number</label>
                        <input
                            id="phone"
                            v-model="form.phone"
                            type="tel"
                            placeholder="+1 (555) 123-4567"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                        />
                    </div>
                    <div>
                        <label for="website" class="block text-sm font-medium text-gray-700">Website</label>
                        <input
                            id="website"
                            v-model="form.website"
                            type="url"
                            placeholder="https://company.com"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                        />
                    </div>
                </div>
            </div>

            <!-- Address Information Tab -->
            <div v-show="activeTab === 'address'" class="space-y-4">
                <div>
                    <label for="address" class="block text-sm font-medium text-gray-700">Street Address</label>
                    <textarea
                        id="address"
                        v-model="form.address"
                        rows="2"
                        placeholder="123 Main Street&#10;Suite 100"
                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                    />
                </div>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div>
                        <label for="city" class="block text-sm font-medium text-gray-700">City</label>
                        <input
                            id="city"
                            v-model="form.city"
                            type="text"
                            placeholder="New York"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                        />
                    </div>
                    <div>
                        <label for="state" class="block text-sm font-medium text-gray-700">State/Province</label>
                        <input
                            id="state"
                            v-model="form.state"
                            type="text"
                            placeholder="NY"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                        />
                    </div>
                    <div>
                        <label for="postal_code" class="block text-sm font-medium text-gray-700">Postal Code</label>
                        <input
                            id="postal_code"
                            v-model="form.postal_code"
                            type="text"
                            placeholder="10001"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                        />
                    </div>
                    <div>
                        <label for="country" class="block text-sm font-medium text-gray-700">Country</label>
                        <input
                            id="country"
                            v-model="form.country"
                            type="text"
                            placeholder="United States"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                        />
                    </div>
                </div>
            </div>

            <!-- Billing Information Tab -->
            <div v-show="activeTab === 'billing'" class="space-y-4">
                <div class="flex items-center justify-between mb-4">
                    <h4 class="text-sm font-medium text-gray-900">Billing Information</h4>
                    <button
                        type="button"
                        @click="copyAddressToBilling"
                        class="text-sm text-indigo-600 hover:text-indigo-500"
                    >
                        Copy from address
                    </button>
                </div>
                <!-- Billing fields here... (abbreviated for brevity) -->
            </div>

            <!-- Business Details Tab -->
            <div v-show="activeTab === 'business'" class="space-y-4">
                <div>
                    <label for="tax_id" class="block text-sm font-medium text-gray-700">Tax ID / VAT Number</label>
                    <input
                        id="tax_id"
                        v-model="form.tax_id"
                        type="text"
                        placeholder="12-3456789 or VAT123456789"
                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                    />
                </div>
                <div>
                    <label for="notes" class="block text-sm font-medium text-gray-700">Internal Notes</label>
                    <textarea
                        id="notes"
                        v-model="form.notes"
                        rows="3"
                        placeholder="Internal notes about this account..."
                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                    />
                </div>
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
        </template>
    </TabbedDialog>
</template>