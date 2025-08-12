<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { ref, onMounted, computed, watch } from 'vue'
import axios from 'axios'

// Define persistent layout
defineOptions({
  layout: AppLayout
})

const form = useForm({
    name: '',
    description: '',
    context: 'service_provider',
    permissions: [],
    widget_permissions: [],
    page_permissions: [],
    is_default: false
})

const availablePermissions = ref({
    functional_permissions: {},
    widget_permissions: [],
    page_permissions: {}
})
const loading = ref(true)
const error = ref(null)

// Track selected permissions for UI
const selectedFunctional = ref(new Set())
const selectedWidget = ref(new Set())
const selectedPage = ref(new Set())

const loadAvailablePermissions = async () => {
    try {
        loading.value = true
        const response = await axios.get('/api/role-templates/permissions/available')
        availablePermissions.value = response.data.data
    } catch (err) {
        error.value = 'Failed to load available permissions'
        console.error('Permissions loading error:', err)
    } finally {
        loading.value = false
    }
}

// Update form data when selections change
watch([selectedFunctional, selectedWidget, selectedPage], () => {
    form.permissions = Array.from(selectedFunctional.value)
    form.widget_permissions = Array.from(selectedWidget.value)
    form.page_permissions = Array.from(selectedPage.value)
}, { deep: true })

const toggleFunctionalPermission = (permissionKey) => {
    if (selectedFunctional.value.has(permissionKey)) {
        selectedFunctional.value.delete(permissionKey)
    } else {
        selectedFunctional.value.add(permissionKey)
    }
    selectedFunctional.value = new Set(selectedFunctional.value) // Trigger reactivity
}

const toggleWidgetPermission = (permissionKey) => {
    if (selectedWidget.value.has(permissionKey)) {
        selectedWidget.value.delete(permissionKey)
    } else {
        selectedWidget.value.add(permissionKey)
    }
    selectedWidget.value = new Set(selectedWidget.value) // Trigger reactivity
}

const togglePagePermission = (permissionKey) => {
    if (selectedPage.value.has(permissionKey)) {
        selectedPage.value.delete(permissionKey)
    } else {
        selectedPage.value.add(permissionKey)
    }
    selectedPage.value = new Set(selectedPage.value) // Trigger reactivity
}

const toggleCategoryFunctional = (category, permissions) => {
    const allSelected = permissions.every(p => selectedFunctional.value.has(p.key))
    
    if (allSelected) {
        // Unselect all in category
        permissions.forEach(p => selectedFunctional.value.delete(p.key))
    } else {
        // Select all in category
        permissions.forEach(p => selectedFunctional.value.add(p.key))
    }
    selectedFunctional.value = new Set(selectedFunctional.value)
}

const toggleCategoryPage = (category, permissions) => {
    const allSelected = permissions.every(p => selectedPage.value.has(p.key))
    
    if (allSelected) {
        // Unselect all in category
        permissions.forEach(p => selectedPage.value.delete(p.key))
    } else {
        // Select all in category
        permissions.forEach(p => selectedPage.value.add(p.key))
    }
    selectedPage.value = new Set(selectedPage.value)
}

const getCategoryDisplayName = (category) => {
    const names = {
        'admin': 'Administration',
        'system': 'System',
        'accounts': 'Account Management',
        'users': 'User Management', 
        'roles': 'Role Management',
        'tickets': 'Service Tickets',
        'time': 'Time Management',
        'timers': 'Timer System',
        'billing': 'Billing',
        'portal': 'Customer Portal',
        'settings': 'Settings',
        'administration': 'Administration',
        'service_delivery': 'Service Delivery',
        'time_management': 'Time Management',
        'financial': 'Financial',
        'communication': 'Communication',
        'productivity': 'Productivity'
    }
    return names[category] || category.charAt(0).toUpperCase() + category.slice(1)
}

const filteredWidgetPermissions = computed(() => {
    const contextFilter = form.context
    if (contextFilter === 'both') return availablePermissions.value.widget_permissions
    
    return availablePermissions.value.widget_permissions.filter(widget => 
        widget.context === 'both' || widget.context === contextFilter
    )
})

const groupedWidgetPermissions = computed(() => {
    return filteredWidgetPermissions.value.reduce((acc, widget) => {
        const category = widget.category || 'general'
        if (!acc[category]) {
            acc[category] = []
        }
        acc[category].push(widget)
        return acc
    }, {})
})

const submit = async () => {
    try {
        const response = await axios.post('/api/role-templates', {
            name: form.name,
            description: form.description,
            context: form.context,
            permissions: Array.from(selectedFunctional.value),
            widget_permissions: Array.from(selectedWidget.value),
            page_permissions: Array.from(selectedPage.value),
            is_default: form.is_default
        })
        
        // Redirect to the created role template
        window.location.href = route('roles.show', response.data.data.id)
    } catch (err) {
        if (err.response?.data?.errors) {
            form.errors = err.response.data.errors
        } else {
            console.error('Creation error:', err)
        }
    }
}

onMounted(() => {
    loadAvailablePermissions()
})
</script>

<template>
    <Head title="Create Role Template" />

    <!-- Page Header -->
    <div class="bg-white shadow-sm border-b border-gray-200 mb-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                        Create Role Template
                    </h2>
                    <p class="text-sm text-gray-600 mt-1">
                        Create a new role template with custom permissions
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Page Header -->
                <div class="flex items-center space-x-4 mb-6">
                    <Link :href="route('roles.index')" class="text-gray-500 hover:text-gray-700">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                    </Link>
                    <div>
                        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                            Create Role Template
                        </h2>
                        <p class="text-sm text-gray-600">
                            Define a new role template with functional, widget, and page permissions
                        </p>
                    </div>
                </div>
                <!-- Loading State -->
                <div v-if="loading" class="bg-white shadow sm:rounded-lg p-6">
                    <div class="flex items-center justify-center">
                        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
                        <span class="ml-2 text-gray-600">Loading permissions...</span>
                    </div>
                </div>

                <!-- Error State -->
                <div v-else-if="error" class="bg-white shadow sm:rounded-lg p-6">
                    <div class="rounded-md bg-red-50 p-4">
                        <div class="text-sm text-red-700">{{ error }}</div>
                    </div>
                </div>

                <!-- Form -->
                <form v-else @submit.prevent="submit" class="space-y-6">
                    <!-- Basic Information -->
                    <div class="bg-white shadow sm:rounded-lg">
                        <div class="px-4 py-5 sm:p-6">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Basic Information</h3>
                            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                                <div>
                                    <label for="name" class="block text-sm font-medium text-gray-700">Role Name</label>
                                    <input 
                                        type="text" 
                                        id="name" 
                                        v-model="form.name"
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                        placeholder="e.g., Senior Developer"
                                    />
                                    <p v-if="form.errors.name" class="mt-2 text-sm text-red-600">{{ form.errors.name }}</p>
                                </div>
                                <div>
                                    <label for="context" class="block text-sm font-medium text-gray-700">Context</label>
                                    <select 
                                        id="context" 
                                        v-model="form.context"
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                    >
                                        <option value="service_provider">Service Provider</option>
                                        <option value="account_user">Account User</option>
                                        <option value="both">Both</option>
                                    </select>
                                    <p v-if="form.errors.context" class="mt-2 text-sm text-red-600">{{ form.errors.context }}</p>
                                </div>
                                <div class="sm:col-span-2">
                                    <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
                                    <textarea 
                                        id="description" 
                                        v-model="form.description"
                                        rows="3"
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                        placeholder="Describe the role and its responsibilities..."
                                    ></textarea>
                                    <p v-if="form.errors.description" class="mt-2 text-sm text-red-600">{{ form.errors.description }}</p>
                                </div>
                                <div class="sm:col-span-2">
                                    <div class="flex items-center">
                                        <input 
                                            id="is_default" 
                                            type="checkbox" 
                                            v-model="form.is_default"
                                            class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                                        />
                                        <label for="is_default" class="ml-2 block text-sm text-gray-900">
                                            Default role (assigned to new users automatically)
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Three-Dimensional Permissions -->
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <!-- Functional Permissions -->
                        <div class="bg-white shadow sm:rounded-lg">
                            <div class="px-4 py-5 sm:p-6">
                                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                                    Functional Permissions
                                    <span class="ml-2 text-sm text-gray-500">({{ selectedFunctional.size }} selected)</span>
                                </h3>
                                <div class="space-y-4 max-h-96 overflow-y-auto">
                                    <div 
                                        v-for="(permissions, category) in availablePermissions.functional_permissions" 
                                        :key="category" 
                                        class="border rounded-lg p-3"
                                    >
                                        <div class="flex items-center justify-between mb-2">
                                            <h4 class="text-sm font-medium text-gray-900">{{ getCategoryDisplayName(category) }}</h4>
                                            <button
                                                type="button"
                                                @click="toggleCategoryFunctional(category, permissions)"
                                                class="text-xs text-indigo-600 hover:text-indigo-500"
                                            >
                                                {{ permissions.every(p => selectedFunctional.has(p.key)) ? 'Unselect All' : 'Select All' }}
                                            </button>
                                        </div>
                                        <div class="space-y-2">
                                            <div v-for="permission in permissions" :key="permission.key" class="flex items-center">
                                                <input 
                                                    :id="permission.key"
                                                    type="checkbox" 
                                                    :checked="selectedFunctional.has(permission.key)"
                                                    @change="toggleFunctionalPermission(permission.key)"
                                                    class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                                                />
                                                <label :for="permission.key" class="ml-2 block text-xs text-gray-700">
                                                    {{ permission.name }}
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Widget Permissions -->
                        <div class="bg-white shadow sm:rounded-lg">
                            <div class="px-4 py-5 sm:p-6">
                                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                                    Widget Permissions
                                    <span class="ml-2 text-sm text-gray-500">({{ selectedWidget.size }} selected)</span>
                                </h3>
                                <div class="space-y-4 max-h-96 overflow-y-auto">
                                    <div 
                                        v-for="(widgets, category) in groupedWidgetPermissions" 
                                        :key="category" 
                                        class="border rounded-lg p-3"
                                    >
                                        <h4 class="text-sm font-medium text-gray-900 mb-2">{{ getCategoryDisplayName(category) }}</h4>
                                        <div class="space-y-2">
                                            <div v-for="widget in widgets" :key="widget.key" class="flex items-center">
                                                <input 
                                                    :id="widget.key"
                                                    type="checkbox" 
                                                    :checked="selectedWidget.has(widget.key)"
                                                    @change="toggleWidgetPermission(widget.key)"
                                                    class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                                                />
                                                <label :for="widget.key" class="ml-2 block text-xs text-gray-700">
                                                    {{ widget.name }}
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Page Permissions -->
                        <div class="bg-white shadow sm:rounded-lg">
                            <div class="px-4 py-5 sm:p-6">
                                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                                    Page Permissions
                                    <span class="ml-2 text-sm text-gray-500">({{ selectedPage.size }} selected)</span>
                                </h3>
                                <div class="space-y-4 max-h-96 overflow-y-auto">
                                    <div 
                                        v-for="(permissions, category) in availablePermissions.page_permissions" 
                                        :key="category" 
                                        class="border rounded-lg p-3"
                                    >
                                        <div class="flex items-center justify-between mb-2">
                                            <h4 class="text-sm font-medium text-gray-900">{{ getCategoryDisplayName(category) }}</h4>
                                            <button
                                                type="button"
                                                @click="toggleCategoryPage(category, permissions)"
                                                class="text-xs text-indigo-600 hover:text-indigo-500"
                                            >
                                                {{ permissions.every(p => selectedPage.has(p.key)) ? 'Unselect All' : 'Select All' }}
                                            </button>
                                        </div>
                                        <div class="space-y-2">
                                            <div v-for="permission in permissions" :key="permission.key" class="flex items-center">
                                                <input 
                                                    :id="permission.key"
                                                    type="checkbox" 
                                                    :checked="selectedPage.has(permission.key)"
                                                    @change="togglePagePermission(permission.key)"
                                                    class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                                                />
                                                <label :for="permission.key" class="ml-2 block text-xs text-gray-700">
                                                    {{ permission.name }}
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="flex justify-end space-x-3 pt-6">
                        <Link 
                            :href="route('roles.index')"
                            class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                        >
                            Cancel
                        </Link>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50"
                        >
                            <div v-if="form.processing" class="animate-spin -ml-1 mr-2 h-4 w-4 border-2 border-white border-t-transparent rounded-full"></div>
                            {{ form.processing ? 'Creating...' : 'Create Role Template' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
</template>