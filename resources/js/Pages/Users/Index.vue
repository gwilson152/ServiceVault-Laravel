<script setup>
import { Head, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import UserFormModal from '@/Components/UserFormModal.vue'
import { ref, onMounted, computed, watch } from 'vue'
import axios from 'axios'

// Define persistent layout
defineOptions({
  layout: AppLayout
})

const users = ref([])
const accounts = ref([])
const roleTemplates = ref([])
const loading = ref(true)
const error = ref(null)
const searchQuery = ref('')
const selectedStatus = ref('all')
const selectedRoleTemplate = ref(null)
const selectedAccount = ref(null)
const sortField = ref('name')
const sortDirection = ref('asc')
const currentPage = ref(1)
const totalPages = ref(1)
const totalUsers = ref(0)
const showCreateModal = ref(false)
const selectedUser = ref(null)
const showDeleteConfirm = ref(false)
const userToDelete = ref(null)

const loadUsers = async (page = 1) => {
    try {
        loading.value = true
        
        const params = {
            page,
            search: searchQuery.value || undefined,
            status: selectedStatus.value !== 'all' ? selectedStatus.value : undefined,
            role_template_id: selectedRoleTemplate.value || undefined,
            account_id: selectedAccount.value || undefined,
            sort: sortField.value,
            direction: sortDirection.value
        }
        
        const response = await axios.get('/api/users', { params })
        users.value = response.data.data
        currentPage.value = response.data.meta.current_page
        totalPages.value = response.data.meta.last_page
        totalUsers.value = response.data.meta.total
    } catch (err) {
        error.value = 'Failed to load users'
        console.error('Users loading error:', err)
    } finally {
        loading.value = false
    }
}

const loadRoleTemplates = async () => {
    try {
        const response = await axios.get('/api/role-templates')
        roleTemplates.value = response.data.data
    } catch (err) {
        console.error('Role templates loading error:', err)
    }
}

const loadAccounts = async () => {
    try {
        const response = await axios.get('/api/accounts')
        accounts.value = response.data.data
    } catch (err) {
        console.error('Accounts loading error:', err)
    }
}

const handleSort = (field) => {
    if (sortField.value === field) {
        sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc'
    } else {
        sortField.value = field
        sortDirection.value = 'asc'
    }
    loadUsers(1)
}

const getSortIcon = (field) => {
    if (sortField.value !== field) return ''
    return sortDirection.value === 'asc' ? '↑' : '↓'
}

const getStatusBadge = (isActive) => {
    return isActive 
        ? 'bg-green-100 text-green-800'
        : 'bg-gray-100 text-gray-800'
}

const getStatusLabel = (isActive) => {
    return isActive ? 'Active' : 'Inactive'
}

const openCreateModal = () => {
    selectedUser.value = null
    showCreateModal.value = true
}

const openEditModal = (user) => {
    selectedUser.value = user
    showCreateModal.value = true
}

const handleUserSaved = () => {
    loadUsers(currentPage.value)
    showCreateModal.value = false
}

const confirmDelete = (user) => {
    userToDelete.value = user
    showDeleteConfirm.value = true
}

const deleteUser = async () => {
    try {
        await axios.delete(`/api/users/${userToDelete.value.id}`)
        loadUsers(currentPage.value)
        showDeleteConfirm.value = false
        userToDelete.value = null
    } catch (error) {
        console.error('Failed to delete user:', error)
    }
}

const toggleUserStatus = async (user) => {
    try {
        await axios.put(`/api/users/${user.id}`, {
            ...user,
            is_active: !user.is_active
        })
        loadUsers(currentPage.value)
    } catch (error) {
        console.error('Failed to toggle user status:', error)
    }
}

// Watch for filter changes
watch([searchQuery, selectedStatus, selectedRoleTemplate, selectedAccount], () => {
    loadUsers(1)
}, { debounce: 300 })

onMounted(() => {
    loadUsers()
    loadRoleTemplates()
    loadAccounts()
})
</script>

<template>
    <Head title="User Management" />

    <!-- Page Header -->
    <div class="bg-white shadow-sm border-b border-gray-200 mb-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                        User Management
                    </h2>
                    <p class="text-sm text-gray-600 mt-1">
                        Manage users, assign accounts, and configure role permissions
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

                <!-- Filters and Search -->
                <div class="mb-6 bg-white p-4 rounded-lg shadow">
                    <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                        <!-- Search -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                            <input
                                v-model="searchQuery"
                                type="text"
                                placeholder="Search users..."
                                class="block w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500"
                            />
                        </div>
                        
                        <!-- Status Filter -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                            <select 
                                v-model="selectedStatus"
                                class="block w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500"
                            >
                                <option value="all">All Status</option>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                        
                        <!-- Role Template Filter -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Role</label>
                            <select 
                                v-model="selectedRoleTemplate"
                                class="block w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500"
                            >
                                <option :value="null">All Roles</option>
                                <option v-for="role in roleTemplates" :key="role.id" :value="role.id">
                                    {{ role.name }}
                                </option>
                            </select>
                        </div>
                        
                        <!-- Account Filter -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Account</label>
                            <select 
                                v-model="selectedAccount"
                                class="block w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-1 focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500"
                            >
                                <option :value="null">All Accounts</option>
                                <option v-for="account in accounts" :key="account.id" :value="account.id">
                                    {{ account.display_name }}
                                </option>
                            </select>
                        </div>
                        
                        <!-- Actions -->
                        <div class="flex items-end">
                            <button
                                @click="openCreateModal"
                                class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                            >
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                </svg>
                                New User
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Users Table -->
                <div class="bg-white shadow overflow-hidden sm:rounded-md">
                    <div v-if="loading" class="px-4 py-8 sm:p-6">
                        <div class="flex items-center justify-center">
                            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
                            <span class="ml-2 text-gray-600">Loading users...</span>
                        </div>
                    </div>

                    <div v-else-if="error" class="px-4 py-5 sm:p-6">
                        <div class="rounded-md bg-red-50 p-4">
                            <div class="text-sm text-red-700">{{ error }}</div>
                        </div>
                    </div>

                    <div v-else-if="users.length > 0">
                        <!-- Table Header -->
                        <div class="bg-gray-50 px-6 py-3 border-b border-gray-200">
                            <div class="flex justify-between items-center">
                                <span class="text-sm font-medium text-gray-900">
                                    {{ totalUsers }} users total
                                </span>
                                <span class="text-sm text-gray-500">
                                    Page {{ currentPage }} of {{ totalPages }}
                                </span>
                            </div>
                        </div>
                        
                        <!-- Users Table -->
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th 
                                            scope="col" 
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100"
                                            @click="handleSort('name')"
                                        >
                                            User {{ getSortIcon('name') }}
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Accounts & Roles
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Activity
                                        </th>
                                        <th 
                                            scope="col" 
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100"
                                            @click="handleSort('is_active')"
                                        >
                                            Status {{ getSortIcon('is_active') }}
                                        </th>
                                        <th scope="col" class="relative px-6 py-3">
                                            <span class="sr-only">Actions</span>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="user in users" :key="user.id" class="hover:bg-gray-50">
                                        <!-- User Info -->
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div class="flex-shrink-0 h-10 w-10">
                                                    <div class="h-10 w-10 rounded-full bg-indigo-100 flex items-center justify-center">
                                                        <span class="text-indigo-600 font-medium text-sm">
                                                            {{ user.name.charAt(0).toUpperCase() }}
                                                        </span>
                                                    </div>
                                                </div>
                                                <div class="ml-4">
                                                    <div class="text-sm font-medium text-gray-900">{{ user.name }}</div>
                                                    <div class="text-sm text-gray-500">{{ user.email }}</div>
                                                    <div v-if="user.is_super_admin" class="text-xs text-purple-600 font-medium">Super Admin</div>
                                                </div>
                                            </div>
                                        </td>
                                        
                                        <!-- Accounts & Roles -->
                                        <td class="px-6 py-4">
                                            <div class="text-sm text-gray-900">
                                                <div v-if="user.accounts_summary">
                                                    <span class="font-medium">{{ user.accounts_summary.total_accounts }} accounts</span>
                                                    <div class="text-xs text-gray-500 mt-1">
                                                        <span v-for="(count, type) in user.accounts_summary.account_types" :key="type" class="mr-2">
                                                            {{ count }} {{ type }}
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="text-sm text-gray-500 mt-1">
                                                <div v-if="user.roles_summary">
                                                    {{ user.roles_summary.total_roles }} roles assigned
                                                </div>
                                            </div>
                                        </td>
                                        
                                        <!-- Activity -->
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <div v-if="user.statistics">
                                                <div>{{ user.statistics.total_assigned_tickets }} tickets</div>
                                                <div class="text-xs text-gray-500">{{ user.statistics.total_time_entries }} time entries</div>
                                                <div v-if="user.statistics.active_timers_count > 0" class="text-xs text-green-600">
                                                    {{ user.statistics.active_timers_count }} active timer(s)
                                                </div>
                                            </div>
                                            <div v-if="user.last_active_at" class="text-xs text-gray-400 mt-1">
                                                Last seen {{ new Date(user.last_active_at).toLocaleDateString() }}
                                            </div>
                                        </td>
                                        
                                        <!-- Status -->
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span 
                                                :class="getStatusBadge(user.is_active)" 
                                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                            >
                                                {{ getStatusLabel(user.is_active) }}
                                            </span>
                                            <div class="text-xs text-gray-500 mt-1">
                                                {{ new Date(user.created_at).toLocaleDateString() }}
                                            </div>
                                        </td>
                                        
                                        <!-- Actions -->
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <div class="flex items-center justify-end space-x-2">
                                                <Link 
                                                    :href="`/users/${user.id}`"
                                                    class="text-indigo-600 hover:text-indigo-900 text-sm font-medium"
                                                >
                                                    View
                                                </Link>
                                                <button 
                                                    @click="openEditModal(user)"
                                                    class="text-indigo-600 hover:text-indigo-900 text-sm font-medium"
                                                >
                                                    Edit
                                                </button>
                                                <button 
                                                    @click="toggleUserStatus(user)"
                                                    class="text-gray-600 hover:text-gray-900 text-sm font-medium"
                                                >
                                                    {{ user.is_active ? 'Deactivate' : 'Activate' }}
                                                </button>
                                                <button 
                                                    @click="confirmDelete(user)"
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
                        
                        <!-- Pagination -->
                        <div v-if="totalPages > 1" class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
                            <div class="flex-1 flex justify-between sm:hidden">
                                <button 
                                    @click="loadUsers(currentPage - 1)" 
                                    :disabled="currentPage <= 1"
                                    class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50"
                                >
                                    Previous
                                </button>
                                <button 
                                    @click="loadUsers(currentPage + 1)" 
                                    :disabled="currentPage >= totalPages"
                                    class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50"
                                >
                                    Next
                                </button>
                            </div>
                            <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                                <div>
                                    <p class="text-sm text-gray-700">
                                        Showing page <span class="font-medium">{{ currentPage }}</span> of <span class="font-medium">{{ totalPages }}</span>
                                    </p>
                                </div>
                                <div>
                                    <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                                        <button 
                                            @click="loadUsers(currentPage - 1)" 
                                            :disabled="currentPage <= 1"
                                            class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50"
                                        >
                                            <span class="sr-only">Previous</span>
                                            <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                                            </svg>
                                        </button>
                                        <button 
                                            @click="loadUsers(currentPage + 1)" 
                                            :disabled="currentPage >= totalPages"
                                            class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50"
                                        >
                                            <span class="sr-only">Next</span>
                                            <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                                            </svg>
                                        </button>
                                    </nav>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-else class="px-4 py-12 sm:p-6">
                        <div class="text-center">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 48 48">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5m-2 3v5m-4 0h9a2 2 0 002-2V9a2 2 0 00-2-2H8a2 2 0 00-2 2v17a2 2 0 002 2h8z"/>
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">No users found</h3>
                            <p class="mt-1 text-sm text-gray-500">
                                {{ searchQuery || selectedStatus !== 'all' || selectedRoleTemplate || selectedAccount 
                                    ? 'No users match your search criteria.' 
                                    : 'Get started by creating your first user.' }}
                            </p>
                            <div class="mt-6">
                                <button
                                    @click="openCreateModal"
                                    class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                                >
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                    </svg>
                                    Create User
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- User Form Modal -->
        <UserFormModal 
            :open="showCreateModal"
            :user="selectedUser"
            :accounts="accounts"
            :roleTemplates="roleTemplates"
            @close="showCreateModal = false"
            @saved="handleUserSaved"
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
                            <h3 class="text-lg font-medium text-gray-900">Deactivate User</h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500">
                                    Are you sure you want to deactivate "{{ userToDelete?.name }}"? This will disable their account access.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="px-6 py-4 bg-gray-50 flex items-center justify-end space-x-2">
                    <button
                        @click="showDeleteConfirm = false"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                    >
                        Cancel
                    </button>
                    <button
                        @click="deleteUser"
                        class="px-4 py-2 text-sm font-medium text-white bg-red-600 border border-transparent rounded-md shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
                    >
                        Deactivate User
                    </button>
                </div>
            </div>
        </div>
</template>