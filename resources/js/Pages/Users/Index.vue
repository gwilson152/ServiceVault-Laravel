<script setup>
import { Head, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import UserFormModal from '@/Components/UserFormModal.vue'
import UsersTable from '@/Components/Tables/UsersTable.vue'
import { useUsersTable } from '@/Composables/useUsersTable'
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
const showCreateModal = ref(false)
const selectedUser = ref(null)
const showDeleteConfirm = ref(false)
const userToDelete = ref(null)

// Table density preference (like tickets page)
const tableDensity = ref(localStorage.getItem('users-table-density') || 'compact')

// Watch for density changes and save to localStorage
watch(tableDensity, (newDensity) => {
  localStorage.setItem('users-table-density', newDensity)
}, { immediate: false })

// Initialize TanStack Table
const {
  table,
  globalFilter,
  setStatusFilter,
  setRoleFilter,
  setAccountFilter,
  clearAllFilters,
  totalUsers,
  currentPage,
  totalPages,
} = useUsersTable(users, false)

const loadUsers = async () => {
    try {
        loading.value = true
        
        // Load all users for client-side filtering/sorting
        const response = await axios.get('/api/users', {
            params: {
                per_page: 1000 // Load more users for client-side operations
            }
        })
        users.value = response.data.data
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

// Removed manual sorting - now handled by TanStack Table

const openCreateModal = () => {
    selectedUser.value = null
    showCreateModal.value = true
}

const openEditModal = (user) => {
    selectedUser.value = user
    showCreateModal.value = true
}

const handleUserSaved = () => {
    loadUsers()
    showCreateModal.value = false
}

const confirmDelete = (user) => {
    userToDelete.value = user
    showDeleteConfirm.value = true
}

const deleteUser = async () => {
    try {
        await axios.delete(`/api/users/${userToDelete.value.id}`)
        loadUsers()
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
        loadUsers()
    } catch (error) {
        console.error('Failed to toggle user status:', error)
    }
}

// Sync search query with TanStack global filter
watch(searchQuery, (newValue) => {
  table.setGlobalFilter(newValue)
})

// Sync filters with TanStack table
watch(selectedStatus, (newValue) => {
  setStatusFilter(newValue)
})

watch(selectedRoleTemplate, (newValue) => {
  setRoleFilter(newValue)
})

watch(selectedAccount, (newValue) => {
  setAccountFilter(newValue)
})

// Computed for active filters
const hasActiveFilters = computed(() => {
  return !!(searchQuery.value || selectedStatus.value !== 'all' || selectedRoleTemplate.value || selectedAccount.value)
})

const clearFilters = () => {
  searchQuery.value = ''
  selectedStatus.value = 'all'
  selectedRoleTemplate.value = null
  selectedAccount.value = null
  clearAllFilters()
}

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
                <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                    <div class="p-4 border-b border-gray-200">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-semibold text-gray-900">
                                Users
                                <span class="text-sm font-normal text-gray-500 ml-2">
                                    ({{ table.getFilteredRowModel().rows.length }} of {{ users.length }})
                                </span>
                            </h3>
                            
                            <!-- Table Controls -->
                            <div class="flex items-center space-x-4">
                                <!-- Clear Filters -->
                                <button
                                    v-if="hasActiveFilters"
                                    @click="clearFilters"
                                    class="text-sm text-red-600 hover:text-red-700 font-medium bg-red-50 px-3 py-1 rounded-lg"
                                >
                                    Clear Filters
                                </button>
                                
                                <!-- Table Density Toggle -->
                                <div class="flex items-center space-x-2">
                                    <span class="text-sm text-gray-500">Density:</span>
                                    <div class="flex items-center space-x-1">
                                        <button
                                            @click="tableDensity = 'comfortable'"
                                            :class="[
                                                'px-2 py-1 rounded text-xs font-medium transition-colors',
                                                tableDensity === 'comfortable' 
                                                    ? 'bg-blue-100 text-blue-700' 
                                                    : 'text-gray-600 hover:text-gray-700 hover:bg-gray-100'
                                            ]"
                                            title="Comfortable spacing"
                                        >
                                            Comfortable
                                        </button>
                                        <button
                                            @click="tableDensity = 'compact'"
                                            :class="[
                                                'px-2 py-1 rounded text-xs font-medium transition-colors',
                                                tableDensity === 'compact' 
                                                    ? 'bg-blue-100 text-blue-700' 
                                                    : 'text-gray-600 hover:text-gray-700 hover:bg-gray-100'
                                            ]"
                                            title="Compact spacing for maximum data density"
                                        >
                                            Compact
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Loading State -->
                    <div v-if="loading" class="p-8 text-center">
                        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div>
                        <p class="mt-2 text-gray-500">Loading users...</p>
                    </div>
                    
                    <!-- Error State -->
                    <div v-else-if="error" class="p-8 text-center">
                        <div class="rounded-md bg-red-50 p-4">
                            <div class="text-sm text-red-700">{{ error }}</div>
                        </div>
                    </div>
                    
                    <!-- Empty State -->
                    <div v-else-if="table.getFilteredRowModel().rows.length === 0" class="p-8 text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">No users found</h3>
                        <p class="mt-1 text-sm text-gray-500">
                            {{ hasActiveFilters ? 'Try adjusting your filters' : 'Get started by creating your first user' }}
                        </p>
                        <div class="mt-6">
                            <button
                                v-if="!hasActiveFilters"
                                @click="openCreateModal"
                                class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg transition-colors"
                            >
                                Create New User
                            </button>
                        </div>
                    </div>
                    
                    <!-- Users Table View -->
                    <div v-else style="overflow: visible;">
                        <div class="overflow-x-auto relative" style="overflow-y: visible;">
                            <UsersTable
                                :table="table"
                                :density="tableDensity"
                                @toggle-status="toggleUserStatus"
                                @edit-user="openEditModal"
                                @delete-user="confirmDelete"
                            />
                        </div>
                    </div>
                    
                    <!-- Pagination Controls -->
                    <div v-if="table.getPageCount() > 1" class="border-t border-gray-200 bg-white px-4 py-3 sm:px-6">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <span class="text-sm text-gray-700">
                                    Showing {{ table.getState().pagination.pageIndex * table.getState().pagination.pageSize + 1 }} to 
                                    {{ Math.min((table.getState().pagination.pageIndex + 1) * table.getState().pagination.pageSize, table.getFilteredRowModel().rows.length) }} of 
                                    {{ table.getFilteredRowModel().rows.length }} users
                                </span>
                            </div>
                            <div class="flex items-center space-x-2">
                                <button
                                    @click="table.previousPage()"
                                    :disabled="!table.getCanPreviousPage()"
                                    class="px-3 py-1 text-sm border border-gray-300 rounded hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    Previous
                                </button>
                                <span class="text-sm text-gray-500">
                                    Page {{ table.getState().pagination.pageIndex + 1 }} of {{ table.getPageCount() }}
                                </span>
                                <button
                                    @click="table.nextPage()"
                                    :disabled="!table.getCanNextPage()"
                                    class="px-3 py-1 text-sm border border-gray-300 rounded hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    Next
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