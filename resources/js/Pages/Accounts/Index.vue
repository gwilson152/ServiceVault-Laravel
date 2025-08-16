<script setup>
import { Head, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import TabbedDialog from '@/Components/TabbedDialog.vue'
import { ref, onMounted, computed, watch } from 'vue'
import { useCreateAccountMutation, useUpdateAccountMutation, useAccountSelectorQuery } from '@/Composables/queries/useAccountsQuery'
import axios from 'axios'

// Define persistent layout
defineOptions({
  layout: AppLayout
})

const accounts = ref([])
const loading = ref(true)
const error = ref(null)
const searchQuery = ref('')
const showFormModal = ref(false)
const selectedAccount = ref(null)
const showDeleteConfirm = ref(false)
const accountToDelete = ref(null)

// Form and dialog state
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

const isEditing = computed(() => !!selectedAccount.value?.id)
const modalTitle = computed(() => isEditing.value ? 'Edit Account' : 'Create Account')
const saving = computed(() => createMutation.isPending.value || updateMutation.isPending.value)

const loadAccounts = async () => {
    try {
        loading.value = true
        const response = await axios.get('/api/accounts')
        accounts.value = response.data.data
    } catch (err) {
        error.value = 'Failed to load accounts'
        console.error('Accounts loading error:', err)
    } finally {
        loading.value = false
    }
}

const filteredAccounts = computed(() => {
    if (!searchQuery.value) return accounts.value
    
    const query = searchQuery.value.toLowerCase()
    return accounts.value.filter(account => 
        account.name.toLowerCase().includes(query) ||
        account.company_name?.toLowerCase().includes(query) ||
        account.contact_person?.toLowerCase().includes(query) ||
        account.email?.toLowerCase().includes(query) ||
        account.phone?.toLowerCase().includes(query)
    )
})

const getAccountTypeLabel = (type) => {
    const types = {
        'customer': 'Customer',
        'prospect': 'Prospect', 
        'partner': 'Partner',
        'internal': 'Internal'
    }
    return types[type] || type
}

const getAccountTypeColor = (type) => {
    const colors = {
        'customer': 'bg-green-100 text-green-800',
        'prospect': 'bg-yellow-100 text-yellow-800',
        'partner': 'bg-blue-100 text-blue-800',
        'internal': 'bg-purple-100 text-purple-800'
    }
    return colors[type] || 'bg-gray-100 text-gray-800'
}

const getStatusColor = (status) => {
    const colors = {
        'active': 'bg-green-100 text-green-800',
        'inactive': 'bg-gray-100 text-gray-800',
        'suspended': 'bg-red-100 text-red-800',
        'pending': 'bg-yellow-100 text-yellow-800'
    }
    return colors[status] || 'bg-gray-100 text-gray-800'
}

// Watch for modal opening to populate form
watch(() => showFormModal.value, (isOpen) => {
    if (isOpen) {
        if (selectedAccount.value) {
            // Populate form with account data
            Object.keys(form.value).forEach(key => {
                form.value[key] = selectedAccount.value[key] ?? form.value[key]
            })
        } else {
            resetForm()
        }
        errors.value = {}
        activeTab.value = 'basic'
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

const openCreateModal = () => {
    selectedAccount.value = null
    showFormModal.value = true
}

const openEditModal = (account) => {
    selectedAccount.value = account
    showFormModal.value = true
}

const saveAccount = async () => {
    try {
        errors.value = {}
        
        if (isEditing.value) {
            const result = await updateMutation.mutateAsync({
                id: selectedAccount.value.id,
                data: form.value
            })
        } else {
            const result = await createMutation.mutateAsync(form.value)
        }
        
        showFormModal.value = false
        loadAccounts() // Reload accounts list
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
        if (!isEditing.value || account.id !== selectedAccount.value?.id) {
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

const handleAccountSaved = (savedAccount) => {
    // Reload accounts to reflect changes
    loadAccounts()
}

const confirmDelete = (account) => {
    accountToDelete.value = account
    showDeleteConfirm.value = true
}

const deleteAccount = async () => {
    try {
        await axios.delete(`/api/accounts/${accountToDelete.value.id}`)
        loadAccounts()
        showDeleteConfirm.value = false
        accountToDelete.value = null
    } catch (error) {
        console.error('Failed to delete account:', error)
        // Handle error (show toast notification, etc.)
    }
}

const viewAccountDetails = (account) => {
    router.visit(`/accounts/${account.id}`)
}

const manageAccountUsers = (account) => {
    router.visit(`/users?account=${account.id}`)
}

onMounted(() => {
    loadAccounts()
})
</script>

<template>
    <Head title="Accounts Management" />

    <!-- Page Header -->
    <div class="bg-white shadow-sm border-b border-gray-200 mb-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                        Accounts Management
                    </h2>
                    <p class="text-sm text-gray-600 mt-1">
                        Manage customer accounts, hierarchies, and access permissions
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

                <!-- Header Section with Search and Actions -->
                <div class="mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                    <div class="flex-1">
                        <div class="relative max-w-md">
                            <input
                                v-model="searchQuery"
                                type="text"
                                placeholder="Search by company, contact, email, or phone..."
                                class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                            />
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                            </div>
                        </div>
                    </div>
                    <div class="flex space-x-2">
                        <button
                            type="button"
                            class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                            @click="loadAccounts()"
                        >
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                            Refresh
                        </button>
                        <button
                            type="button"
                            @click="openCreateModal"
                            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                        >
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                            </svg>
                            New Account
                        </button>
                    </div>
                </div>

                <!-- Accounts List -->
                <div class="bg-white shadow overflow-hidden sm:rounded-md">
                    <div v-if="loading" class="px-4 py-8 sm:p-6">
                        <div class="flex items-center justify-center">
                            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
                            <span class="ml-2 text-gray-600">Loading accounts...</span>
                        </div>
                    </div>

                    <div v-else-if="error" class="px-4 py-5 sm:p-6">
                        <div class="rounded-md bg-red-50 p-4">
                            <div class="text-sm text-red-700">
                                {{ error }}
                            </div>
                        </div>
                    </div>

                    <div v-else-if="filteredAccounts.length > 0">
                        <!-- Accounts Table -->
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Company / Account
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Contact
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Type
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Hierarchy
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Status
                                        </th>
                                        <th scope="col" class="relative px-6 py-3">
                                            <span class="sr-only">Actions</span>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="account in filteredAccounts" :key="account.id" 
                                        class="hover:bg-blue-50 cursor-pointer transition-colors duration-150"
                                        @click="viewAccountDetails(account)"
                                        title="Click to view account details">
                                        <!-- Company / Account -->
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <!-- Hierarchy indicators -->
                                                <div v-if="account.hierarchy_level > 0" class="flex items-center mr-2 text-gray-400">
                                                    <div class="flex">
                                                        <span v-for="level in account.hierarchy_level" :key="level" class="mr-4">
                                                            <span v-if="level === account.hierarchy_level" class="text-gray-400">└─</span>
                                                            <span v-else class="text-gray-200">│</span>
                                                        </span>
                                                    </div>
                                                </div>
                                                
                                                <div class="flex-shrink-0 h-10 w-10">
                                                    <div class="h-10 w-10 rounded-full flex items-center justify-center"
                                                         :class="account.hierarchy_level === 0 ? 'bg-indigo-100' : 'bg-gray-100'">
                                                        <svg class="h-5 w-5" :class="account.hierarchy_level === 0 ? 'text-indigo-600' : 'text-gray-500'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path v-if="account.hierarchy_level === 0" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0H5m14 0v-5a2 2 0 00-2-2H9a2 2 0 00-2 2v5m14 0l-2-2m2 2l-2-2"/>
                                                            <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                                        </svg>
                                                    </div>
                                                </div>
                                                <div class="ml-4">
                                                    <div class="text-sm font-medium text-gray-900">
                                                        {{ account.display_name || account.name }}
                                                    </div>
                                                    <div v-if="account.company_name && account.company_name !== account.name" class="text-sm text-gray-500">
                                                        {{ account.name }}
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        
                                        <!-- Contact -->
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900">{{ account.contact_person || '—' }}</div>
                                            <div class="text-sm text-gray-500">
                                                {{ account.email || '—' }}
                                            </div>
                                            <div v-if="account.phone" class="text-sm text-gray-500">
                                                {{ account.phone }}
                                            </div>
                                        </td>
                                        
                                        <!-- Type -->
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                                  :class="getAccountTypeColor(account.account_type)">
                                                {{ getAccountTypeLabel(account.account_type) }}
                                            </span>
                                        </td>
                                        
                                        <!-- Hierarchy -->
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <div class="flex items-center space-x-2">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                                      :class="account.hierarchy_level === 0 ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800'">
                                                    {{ account.hierarchy_level === 0 ? 'Root Account' : `Subsidiary L${account.hierarchy_level}` }}
                                                </span>
                                            </div>
                                            <div class="text-xs text-gray-500 mt-1">
                                                <span v-if="account.children_count > 0" class="mr-2">{{ account.children_count }} subsidiaries</span>
                                                <span>{{ account.users_count || 0 }} users</span>
                                            </div>
                                        </td>
                                        
                                        <!-- Status -->
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span :class="getStatusColor(account.status)" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium">
                                                {{ account.status || 'Active' }}
                                            </span>
                                            <div class="text-xs text-gray-500 mt-1">
                                                {{ new Date(account.created_at).toLocaleDateString() }}
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <div class="flex items-center justify-end space-x-2">
                                                <button 
                                                    type="button" 
                                                    @click.stop="manageAccountUsers(account)"
                                                    class="text-gray-600 hover:text-gray-900 text-sm font-medium"
                                                >
                                                    Users
                                                </button>
                                                <button 
                                                    type="button" 
                                                    @click.stop="confirmDelete(account)"
                                                    class="text-red-600 hover:text-red-900 text-sm font-medium"
                                                >
                                                    Delete
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div v-else class="px-4 py-12 sm:p-6">
                        <div class="text-center">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 48 48">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0H5m14 0v-5a2 2 0 00-2-2H9a2 2 0 00-2 2v5m14 0l-2-2m2 2l-2-2"/>
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">No accounts found</h3>
                            <p class="mt-1 text-sm text-gray-500">
                                {{ searchQuery ? 'No accounts match your search criteria.' : 'Get started by creating your first account.' }}
                            </p>
                            <div class="mt-6">
                                <button
                                    type="button"
                                    @click="openCreateModal"
                                    class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                                >
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                    </svg>
                                    Create Account
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Account Form Dialog -->
        <TabbedDialog
            :show="showFormModal"
            :title="modalTitle"
            :tabs="tabs"
            default-tab="basic"
            :saving="saving"
            :save-label="isEditing ? 'Update Account' : 'Create Account'"
            @close="showFormModal = false"
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
                    <div>
                        <label for="billing_address" class="block text-sm font-medium text-gray-700">Billing Address</label>
                        <textarea
                            id="billing_address"
                            v-model="form.billing_address"
                            rows="2"
                            placeholder="123 Billing Street&#10;Suite 200"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                        />
                    </div>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div>
                            <label for="billing_city" class="block text-sm font-medium text-gray-700">City</label>
                            <input
                                id="billing_city"
                                v-model="form.billing_city"
                                type="text"
                                placeholder="New York"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                            />
                        </div>
                        <div>
                            <label for="billing_state" class="block text-sm font-medium text-gray-700">State/Province</label>
                            <input
                                id="billing_state"
                                v-model="form.billing_state"
                                type="text"
                                placeholder="NY"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                            />
                        </div>
                        <div>
                            <label for="billing_postal_code" class="block text-sm font-medium text-gray-700">Postal Code</label>
                            <input
                                id="billing_postal_code"
                                v-model="form.billing_postal_code"
                                type="text"
                                placeholder="10001"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                            />
                        </div>
                        <div>
                            <label for="billing_country" class="block text-sm font-medium text-gray-700">Country</label>
                            <input
                                id="billing_country"
                                v-model="form.billing_country"
                                type="text"
                                placeholder="United States"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                            />
                        </div>
                    </div>
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

        <!-- Delete Confirmation Modal -->
        <div v-if="showDeleteConfirm" class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center p-4 z-50">
            <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
                <div class="px-6 py-4">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-medium text-gray-900">Delete Account</h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500">
                                    Are you sure you want to delete "{{ accountToDelete?.name }}"? This action cannot be undone.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="px-6 py-4 bg-gray-50 flex items-center justify-end space-x-2">
                    <button
                        type="button"
                        @click="showDeleteConfirm = false"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        @click="deleteAccount"
                        class="px-4 py-2 text-sm font-medium text-white bg-red-600 border border-transparent rounded-md shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
                    >
                        Delete Account
                    </button>
                </div>
            </div>
        </div>
</template>