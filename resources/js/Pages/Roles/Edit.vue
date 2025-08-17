<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import QuickTooltip from '@/Components/UI/QuickTooltip.vue'
import { ref, onMounted, computed, watch } from 'vue'
import axios from 'axios'

// Define persistent layout
defineOptions({
  layout: AppLayout
})

const props = defineProps({
    roleTemplateId: String
})

const roleTemplate = ref(null)
const availablePermissions = ref({
    functional_permissions: {},
    widget_permissions: [],
    page_permissions: {}
})
const loading = ref(true)
const error = ref(null)
const saving = ref(false)

// Track selected permissions for UI
const selectedFunctional = ref(new Set())
const selectedWidget = ref(new Set())
const selectedPage = ref(new Set())

const form = useForm({
    name: '',
    description: '',
    context: 'service_provider',
    permissions: [],
    widget_permissions: [],
    page_permissions: [],
    is_default: false
})

const loadRoleTemplate = async () => {
    try {
        loading.value = true
        
        // Load role template and available permissions in parallel
        const [roleResponse, permissionsResponse] = await Promise.all([
            axios.get(`/api/role-templates/${props.roleTemplateId}`),
            axios.get('/api/role-templates/permissions/available')
        ])
        
        roleTemplate.value = roleResponse.data.data
        availablePermissions.value = permissionsResponse.data.data
        
        // Populate form with existing data
        form.name = roleTemplate.value.name
        form.description = roleTemplate.value.description
        form.context = roleTemplate.value.context
        form.is_default = roleTemplate.value.is_default
        
        // Initialize selected permissions
        selectedFunctional.value = new Set(roleTemplate.value.permissions || [])
        selectedWidget.value = new Set(roleTemplate.value.widget_permissions || [])
        selectedPage.value = new Set(roleTemplate.value.page_permissions || [])
        
    } catch (err) {
        error.value = 'Failed to load role template'
        console.error('Role template loading error:', err)
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
    selectedFunctional.value = new Set(selectedFunctional.value)
}

const toggleWidgetPermission = (permissionKey) => {
    if (selectedWidget.value.has(permissionKey)) {
        selectedWidget.value.delete(permissionKey)
    } else {
        selectedWidget.value.add(permissionKey)
    }
    selectedWidget.value = new Set(selectedWidget.value)
}

const togglePagePermission = (permissionKey) => {
    if (selectedPage.value.has(permissionKey)) {
        selectedPage.value.delete(permissionKey)
    } else {
        selectedPage.value.add(permissionKey)
    }
    selectedPage.value = new Set(selectedPage.value)
}

const toggleCategoryFunctional = (category, permissions) => {
    const allSelected = permissions.every(p => selectedFunctional.value.has(p.key))
    
    if (allSelected) {
        permissions.forEach(p => selectedFunctional.value.delete(p.key))
    } else {
        permissions.forEach(p => selectedFunctional.value.add(p.key))
    }
    selectedFunctional.value = new Set(selectedFunctional.value)
}

const toggleCategoryPage = (category, permissions) => {
    const allSelected = permissions.every(p => selectedPage.value.has(p.key))
    
    if (allSelected) {
        permissions.forEach(p => selectedPage.value.delete(p.key))
    } else {
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
        saving.value = true
        
        const response = await axios.put(`/api/role-templates/${props.roleTemplateId}`, {
            name: form.name,
            description: form.description,
            context: form.context,
            permissions: Array.from(selectedFunctional.value),
            widget_permissions: Array.from(selectedWidget.value),
            page_permissions: Array.from(selectedPage.value),
            is_default: form.is_default
        })
        
        // Redirect to the updated role template
        window.location.href = route('roles.show', props.roleTemplateId)
    } catch (err) {
        if (err.response?.data?.errors) {
            form.errors = err.response.data.errors
        } else {
            console.error('Update error:', err)
            error.value = 'Failed to update role template'
        }
    } finally {
        saving.value = false
    }
}

const confirmDelete = async () => {
    if (!confirm('Are you sure you want to delete this role template? This action cannot be undone.')) {
        return
    }
    
    try {
        await axios.delete(`/api/role-templates/${props.roleTemplateId}`)
        window.location.href = route('roles.index')
    } catch (err) {
        console.error('Delete error:', err)
        alert('Failed to delete role template. It may be in use by existing users.')
    }
}

onMounted(() => {
    loadRoleTemplate()
})
</script>

<template>
    <Head :title="`Edit Role Template - ${roleTemplate?.name || 'Loading...'}`" />

    <!-- Page Header -->
    <div class="bg-white shadow-sm border-b border-gray-200 mb-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                        Edit Role Template
                    </h2>
                    <p class="text-sm text-gray-600 mt-1">
                        Modify role template permissions and settings
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Page Header -->
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center space-x-4">
                        <Link :href="route('roles.index')" class="text-gray-500 hover:text-gray-700">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                            </svg>
                        </Link>
                        <div>
                            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                                Edit Role Template: {{ roleTemplate?.name || 'Loading...' }}
                            </h2>
                            <p class="text-sm text-gray-600">
                                Modify permissions and settings for this role template
                            </p>
                        </div>
                    </div>
                    <div v-if="roleTemplate && !roleTemplate.is_modifiable" class="text-sm text-red-600 bg-red-50 px-3 py-1 rounded">
                        This is a system role and cannot be modified
                    </div>
                </div>
                
                <!-- Permission Notation Reference (only show when not loading and role is modifiable) -->
                <div v-if="!loading && roleTemplate?.is_modifiable" class="mb-6 bg-gray-50 border border-gray-200 rounded-lg p-4">
                    <h4 class="text-sm font-medium text-gray-900 mb-2">Permission Notation Reference</h4>
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 text-xs">
                        <div>
                            <h5 class="font-medium text-gray-800 mb-1">Functional Format</h5>
                            <div class="space-y-1 text-gray-600">
                                <div><code class="bg-gray-100 px-1 rounded text-gray-800">category.action</code> - Basic permission</div>
                                <div><code class="bg-gray-100 px-1 rounded text-gray-800">category.action.scope</code> - Scoped permission</div>
                            </div>
                        </div>
                        <div>
                            <h5 class="font-medium text-gray-800 mb-1">Widget Format</h5>
                            <div class="space-y-1 text-gray-600">
                                <div><code class="bg-gray-100 px-1 rounded text-gray-800">widgets.dashboard.{widget}</code> - Widget access</div>
                                <div><code class="bg-gray-100 px-1 rounded text-gray-800">widgets.configure</code> - Global widget control</div>
                            </div>
                        </div>
                        <div>
                            <h5 class="font-medium text-gray-800 mb-1">Page Format</h5>
                            <div class="space-y-1 text-gray-600">
                                <div><code class="bg-gray-100 px-1 rounded text-gray-800">pages.{section}.{page}</code> - Page access</div>
                                <div><code class="bg-gray-100 px-1 rounded text-gray-800">pages.admin.*</code> - Section access</div>
                            </div>
                        </div>
                    </div>
                    <div class="mt-3 pt-3 border-t border-gray-200 text-xs text-gray-600">
                        <strong>Scope levels:</strong> <code class="bg-gray-100 px-1 rounded text-gray-800">all</code> (system-wide), 
                        <code class="bg-gray-100 px-1 rounded text-gray-800">account</code> (accessible accounts), 
                        <code class="bg-gray-100 px-1 rounded text-gray-800">own</code> (user's own data), 
                        <code class="bg-gray-100 px-1 rounded text-gray-800">assigned</code> (assigned items)
                    </div>
                </div>
                
                <!-- Loading State -->
                <div v-if="loading" class="bg-white shadow sm:rounded-lg p-6">
                    <div class="flex items-center justify-center">
                        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
                        <span class="ml-2 text-gray-600">Loading role template...</span>
                    </div>
                </div>

                <!-- Error State -->
                <div v-else-if="error" class="bg-white shadow sm:rounded-lg p-6">
                    <div class="rounded-md bg-red-50 p-4">
                        <div class="text-sm text-red-700">{{ error }}</div>
                    </div>
                </div>

                <!-- Non-Modifiable Warning -->
                <div v-else-if="roleTemplate && !roleTemplate.is_modifiable" class="bg-white shadow sm:rounded-lg p-6">
                    <div class="rounded-md bg-yellow-50 p-4">
                        <div class="flex">
                            <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                            </svg>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-yellow-800">System Role Template</h3>
                                <p class="mt-2 text-sm text-yellow-700">
                                    This is a system role template and cannot be modified. System roles are essential for platform operation.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Form (only show if modifiable) -->
                <form v-else-if="roleTemplate && roleTemplate.is_modifiable" @submit.prevent="submit" class="space-y-6">
                    <!-- Basic Information -->
                    <div class="bg-white shadow sm:rounded-lg">
                        <div class="px-4 py-5 sm:p-6">
                            <div class="flex justify-between items-center mb-4">
                                <h3 class="text-lg leading-6 font-medium text-gray-900">Basic Information</h3>
                                <button
                                    type="button"
                                    @click="confirmDelete"
                                    class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-red-700 bg-red-100 hover:bg-red-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
                                >
                                    Delete Role Template
                                </button>
                            </div>
                            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                                <div>
                                    <label for="name" class="block text-sm font-medium text-gray-700">Role Name</label>
                                    <input 
                                        type="text" 
                                        id="name" 
                                        v-model="form.name"
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
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

                    <!-- Three-Dimensional Permissions with Summary -->
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                        <!-- Permissions Grid (2 columns) -->
                        <div class="lg:col-span-2 space-y-8">
                        <!-- Functional Permissions -->
                        <div class="bg-white shadow sm:rounded-lg">
                            <div class="px-4 py-5 sm:p-6">
                                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                                    Functional Permissions
                                    <span class="ml-2 text-sm text-gray-500">({{ selectedFunctional.size }} selected)</span>
                                </h3>
                                <div class="max-h-[500px] overflow-y-auto">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div 
                                            v-for="(permissions, category) in availablePermissions.functional_permissions" 
                                            :key="category" 
                                            class="border-2 rounded-lg p-4 bg-gray-50"
                                        >
                                        <div class="flex items-center justify-between mb-3">
                                            <h4 class="text-base font-semibold text-gray-900 flex items-center">
                                                <div class="w-3 h-3 rounded-full bg-indigo-500 mr-2"></div>
                                                {{ getCategoryDisplayName(category) }}
                                            </h4>
                                            <button
                                                type="button"
                                                @click="toggleCategoryFunctional(category, permissions)"
                                                class="text-sm font-medium text-indigo-600 hover:text-indigo-500 px-3 py-1 border border-indigo-300 rounded-md hover:bg-indigo-50"
                                            >
                                                {{ permissions.every(p => selectedFunctional.has(p.key)) ? 'Unselect All' : 'Select All' }}
                                            </button>
                                        </div>
                                        <div class="space-y-3 bg-white rounded-md p-3 border border-gray-200">
                                            <div v-for="permission in permissions" :key="permission.key" class="flex items-start bg-gray-50 rounded-lg p-3 hover:bg-gray-100 transition-colors">
                                                <input 
                                                    :id="permission.key"
                                                    type="checkbox" 
                                                    :checked="selectedFunctional.has(permission.key)"
                                                    @change="toggleFunctionalPermission(permission.key)"
                                                    class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded mt-1"
                                                />
                                                <div class="ml-3 flex-1">
                                                    <div class="flex items-center space-x-2">
                                                        <label :for="permission.key" class="block text-sm font-medium text-gray-900 cursor-pointer">
                                                            {{ permission.name }}
                                                        </label>
                                                        <QuickTooltip 
                                                            v-if="permission.description" 
                                                            :content="permission.description"
                                                            position="auto"
                                                            max-width="max-w-sm"
                                                        >
                                                            <svg class="w-4 h-4 text-gray-400 hover:text-gray-600 cursor-help" fill="currentColor" viewBox="0 0 20 20">
                                                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 00-.867.5 1 1 0 11-1.731-1A3 3 0 0113 8a3.001 3.001 0 01-2 2.83V11a1 1 0 11-2 0v-1a1 1 0 011-1 1 1 0 100-2zm0 8a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/>
                                                            </svg>
                                                        </QuickTooltip>
                                                    </div>
                                                    <div class="text-xs text-gray-500 font-mono mt-1 bg-white rounded px-2 py-1 border border-gray-200">
                                                        {{ permission.key }}
                                                    </div>
                                                </div>
                                            </div>
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
                                <div class="max-h-[400px] overflow-y-auto">
                                    <div class="grid grid-cols-1 gap-4">
                                        <div 
                                            v-for="(widgets, category) in groupedWidgetPermissions" 
                                            :key="category" 
                                            class="border-2 rounded-lg p-4 bg-purple-50"
                                        >
                                        <h4 class="text-base font-semibold text-gray-900 flex items-center mb-3">
                                            <div class="w-3 h-3 rounded-full bg-purple-500 mr-2"></div>
                                            {{ getCategoryDisplayName(category) }}
                                        </h4>
                                        <div class="space-y-3 bg-white rounded-md p-3 border border-gray-200">
                                            <div v-for="widget in widgets" :key="widget.key" class="flex items-start bg-gray-50 rounded-lg p-3 hover:bg-gray-100 transition-colors">
                                                <input 
                                                    :id="widget.key"
                                                    type="checkbox" 
                                                    :checked="selectedWidget.has(widget.key)"
                                                    @change="toggleWidgetPermission(widget.key)"
                                                    class="h-4 w-4 text-purple-600 focus:ring-purple-500 border-gray-300 rounded mt-1"
                                                />
                                                <div class="ml-3 flex-1">
                                                    <div class="flex items-center space-x-2">
                                                        <label :for="widget.key" class="block text-sm font-medium text-gray-900 cursor-pointer">
                                                            {{ widget.name }}
                                                        </label>
                                                        <QuickTooltip 
                                                            v-if="widget.description" 
                                                            :content="widget.description"
                                                            position="auto"
                                                            max-width="max-w-sm"
                                                        >
                                                            <svg class="w-4 h-4 text-gray-400 hover:text-gray-600 cursor-help" fill="currentColor" viewBox="0 0 20 20">
                                                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 00-.867.5 1 1 0 11-1.731-1A3 3 0 0113 8a3.001 3.001 0 01-2 2.83V11a1 1 0 11-2 0v-1a1 1 0 011-1 1 1 0 100-2zm0 8a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/>
                                                            </svg>
                                                        </QuickTooltip>
                                                    </div>
                                                    <div class="text-xs text-gray-500 font-mono mt-1 bg-white rounded px-2 py-1 border border-gray-200">
                                                        {{ widget.key }}
                                                    </div>
                                                </div>
                                            </div>
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
                                <div class="max-h-[400px] overflow-y-auto">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div 
                                            v-for="(permissions, category) in availablePermissions.page_permissions" 
                                            :key="category" 
                                            class="border-2 rounded-lg p-4 bg-green-50"
                                        >
                                        <div class="flex items-center justify-between mb-3">
                                            <h4 class="text-base font-semibold text-gray-900 flex items-center">
                                                <div class="w-3 h-3 rounded-full bg-green-500 mr-2"></div>
                                                {{ getCategoryDisplayName(category) }}
                                            </h4>
                                            <button
                                                type="button"
                                                @click="toggleCategoryPage(category, permissions)"
                                                class="text-sm font-medium text-green-600 hover:text-green-500 px-3 py-1 border border-green-300 rounded-md hover:bg-green-50"
                                            >
                                                {{ permissions.every(p => selectedPage.has(p.key)) ? 'Unselect All' : 'Select All' }}
                                            </button>
                                        </div>
                                        <div class="space-y-3 bg-white rounded-md p-3 border border-gray-200">
                                            <div v-for="permission in permissions" :key="permission.key" class="flex items-start bg-gray-50 rounded-lg p-3 hover:bg-gray-100 transition-colors">
                                                <input 
                                                    :id="permission.key"
                                                    type="checkbox" 
                                                    :checked="selectedPage.has(permission.key)"
                                                    @change="togglePagePermission(permission.key)"
                                                    class="h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300 rounded mt-1"
                                                />
                                                <div class="ml-3 flex-1">
                                                    <div class="flex items-center space-x-2">
                                                        <label :for="permission.key" class="block text-sm font-medium text-gray-900 cursor-pointer">
                                                            {{ permission.name }}
                                                        </label>
                                                        <QuickTooltip 
                                                            v-if="permission.description" 
                                                            :content="permission.description"
                                                            position="auto"
                                                            max-width="max-w-sm"
                                                        >
                                                            <svg class="w-4 h-4 text-gray-400 hover:text-gray-600 cursor-help" fill="currentColor" viewBox="0 0 20 20">
                                                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 00-.867.5 1 1 0 11-1.731-1A3 3 0 0113 8a3.001 3.001 0 01-2 2.83V11a1 1 0 11-2 0v-1a1 1 0 011-1 1 1 0 100-2zm0 8a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/>
                                                            </svg>
                                                        </QuickTooltip>
                                                    </div>
                                                    <div class="text-xs text-gray-500 font-mono mt-1 bg-white rounded px-2 py-1 border border-gray-200">
                                                        {{ permission.key }}
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        </div>
                        
                        <!-- Selected Permissions Summary -->
                        <div class="lg:col-span-1 space-y-6">
                            <!-- Summary Overview -->
                            <div class="bg-blue-50 border border-blue-200 shadow sm:rounded-lg sticky top-4">
                                <div class="px-4 py-5 sm:p-6">
                                    <h3 class="text-lg leading-6 font-medium text-blue-900 mb-4">Selected Permissions</h3>
                                    
                                    <!-- Summary Stats -->
                                    <div class="grid grid-cols-1 gap-3 mb-4">
                                        <div class="bg-white rounded-lg p-3 border border-blue-200">
                                            <div class="flex items-center justify-between">
                                                <span class="text-sm font-medium text-gray-700">Functional</span>
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                    {{ selectedFunctional.size }}
                                                </span>
                                            </div>
                                        </div>
                                        <div class="bg-white rounded-lg p-3 border border-blue-200">
                                            <div class="flex items-center justify-between">
                                                <span class="text-sm font-medium text-gray-700">Widget</span>
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                    {{ selectedWidget.size }}
                                                </span>
                                            </div>
                                        </div>
                                        <div class="bg-white rounded-lg p-3 border border-blue-200">
                                            <div class="flex items-center justify-between">
                                                <span class="text-sm font-medium text-gray-700">Page</span>
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                    {{ selectedPage.size }}
                                                </span>
                                            </div>
                                        </div>
                                        <div class="bg-white rounded-lg p-3 border border-blue-200">
                                            <div class="flex items-center justify-between">
                                                <span class="text-sm font-medium text-gray-700">Total</span>
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                    {{ selectedFunctional.size + selectedWidget.size + selectedPage.size }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Selected Permissions List -->
                                    <div class="space-y-4">
                                        <!-- Functional Permissions Summary -->
                                        <div v-if="selectedFunctional.size > 0">
                                            <h4 class="text-sm font-medium text-blue-900 mb-2">Functional Permissions</h4>
                                            <div class="max-h-32 overflow-y-auto space-y-1">
                                                <div v-for="permission in Array.from(selectedFunctional)" :key="permission" 
                                                     class="text-xs bg-white rounded px-2 py-1 border border-blue-200">
                                                    <div class="font-mono text-blue-800">{{ permission }}</div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Widget Permissions Summary -->
                                        <div v-if="selectedWidget.size > 0">
                                            <h4 class="text-sm font-medium text-blue-900 mb-2">Widget Permissions</h4>
                                            <div class="max-h-32 overflow-y-auto space-y-1">
                                                <div v-for="permission in Array.from(selectedWidget)" :key="permission" 
                                                     class="text-xs bg-white rounded px-2 py-1 border border-blue-200">
                                                    <div class="font-mono text-blue-800">{{ permission }}</div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Page Permissions Summary -->
                                        <div v-if="selectedPage.size > 0">
                                            <h4 class="text-sm font-medium text-blue-900 mb-2">Page Permissions</h4>
                                            <div class="max-h-32 overflow-y-auto space-y-1">
                                                <div v-for="permission in Array.from(selectedPage)" :key="permission" 
                                                     class="text-xs bg-white rounded px-2 py-1 border border-blue-200">
                                                    <div class="font-mono text-blue-800">{{ permission }}</div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Empty State -->
                                        <div v-if="selectedFunctional.size === 0 && selectedWidget.size === 0 && selectedPage.size === 0" 
                                             class="text-center py-4">
                                            <svg class="mx-auto h-8 w-8 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                            <p class="mt-2 text-sm text-blue-600">No permissions selected</p>
                                            <p class="text-xs text-blue-500">Start selecting permissions to see them here</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="flex justify-end space-x-3 pt-6">
                        <Link 
                            :href="route('roles.show', props.roleTemplateId)"
                            class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                        >
                            Cancel
                        </Link>
                        <button
                            type="submit"
                            :disabled="saving"
                            class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50"
                        >
                            <div v-if="saving" class="animate-spin -ml-1 mr-2 h-4 w-4 border-2 border-white border-t-transparent rounded-full"></div>
                            {{ saving ? 'Saving...' : 'Save Changes' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
</template>