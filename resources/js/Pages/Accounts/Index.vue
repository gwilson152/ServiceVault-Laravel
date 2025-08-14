<script setup>
import { Head, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import AccountFormModal from '@/Components/AccountFormModal.vue'
import { ref, onMounted, computed } from 'vue'
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

const openCreateModal = () => {
    selectedAccount.value = null
    showFormModal.value = true
}

const openEditModal = (account) => {
    selectedAccount.value = account
    showFormModal.value = true
}

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

        <!-- Account Form Modal -->
        <AccountFormModal 
            :open="showFormModal"
            :account="selectedAccount"
            @close="showFormModal = false"
            @saved="handleAccountSaved"
        />

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