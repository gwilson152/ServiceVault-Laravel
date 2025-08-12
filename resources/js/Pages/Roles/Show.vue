<script setup>
import { Head, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import DashboardPreviewModal from '@/Components/DashboardPreviewModal.vue'
import WidgetAssignmentModal from '@/Components/WidgetAssignmentModal.vue'
import { ref, onMounted, computed } from 'vue'
import axios from 'axios'

// Define persistent layout
defineOptions({
  layout: AppLayout
})

const props = defineProps({
    roleTemplateId: String
})

const roleTemplate = ref(null)
const availableWidgets = ref([])
const loading = ref(true)
const widgetsLoading = ref(false)
const error = ref(null)
const showPreviewModal = ref(false)
const showWidgetModal = ref(false)

const loadRoleTemplate = async () => {
    try {
        loading.value = true
        
        if (props.roleTemplateId === 'create') {
            // Load create form data
            const response = await axios.get('/api/role-templates/create')
            roleTemplate.value = {
                id: null,
                name: '',
                description: '',
                context: 'service_provider',
                permissions: [],
                widget_permissions: [],
                page_permissions: [],
                users_count: 0,
                is_system_role: false,
                is_default: false,
                is_modifiable: true,
                ...response.data.data.default_values
            }
        } else {
            // Load existing role template
            const response = await axios.get(`/api/role-templates/${props.roleTemplateId}`)
            roleTemplate.value = response.data.data
        }
        
        await loadWidgetPreview()
    } catch (err) {
        error.value = 'Failed to load role template'
        console.error('Role template loading error:', err)
    } finally {
        loading.value = false
    }
}

const loadWidgetPreview = async () => {
    try {
        widgetsLoading.value = true
        const response = await axios.get(`/api/role-templates/${props.roleTemplateId}/preview/widgets`)
        availableWidgets.value = response.data.data.available_widgets
    } catch (err) {
        console.error('Widget preview loading error:', err)
    } finally {
        widgetsLoading.value = false
    }
}

const groupedPermissions = computed(() => {
    if (!roleTemplate.value) return {}
    
    const permissions = roleTemplate.value.permissions || []
    const grouped = {}
    
    permissions.forEach(permission => {
        const parts = permission.split('.')
        const category = parts[0] || 'general'
        
        if (!grouped[category]) {
            grouped[category] = []
        }
        grouped[category].push(permission)
    })
    
    return grouped
})

const groupedWidgets = computed(() => {
    if (!availableWidgets.value) return {}
    
    return availableWidgets.value.reduce((acc, widget) => {
        const category = widget.category || 'general'
        if (!acc[category]) {
            acc[category] = []
        }
        acc[category].push(widget)
        return acc
    }, {})
})

const formatPermissionName = (permission) => {
    return permission.split('.').map(part => 
        part.charAt(0).toUpperCase() + part.slice(1)
    ).join(' ')
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

const handleWidgetsSaved = () => {
    showWidgetModal.value = false
    // Reload widget preview to reflect changes
    loadWidgetPreview()
}

onMounted(() => {
    loadRoleTemplate()
})
</script>

<template>
    <Head :title="`Role Template - ${roleTemplate?.name || 'Loading...'}`" />

    <!-- Page Header -->
    <div class="bg-white shadow-sm border-b border-gray-200 mb-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                        Role Template Details
                    </h2>
                    <p class="text-sm text-gray-600 mt-1">
                        View and manage role template permissions
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
                                {{ roleTemplate?.name || 'Loading...' }}
                            </h2>
                            <p v-if="roleTemplate" class="text-sm text-gray-600">
                                {{ roleTemplate.description }}
                            </p>
                        </div>
                    </div>
                <div v-if="roleTemplate" class="flex space-x-2">
                    <button 
                        @click="showPreviewModal = true"
                        class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                    >
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                        Preview Dashboard
                    </button>
                    <button 
                        v-if="roleTemplate?.is_modifiable"
                        @click="showWidgetModal = true"
                        class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500"
                    >
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/>
                        </svg>
                        Manage Widgets
                    </button>
                    <Link 
                        v-if="roleTemplate?.is_modifiable"
                        :href="route('roles.edit', props.roleTemplateId)"
                        class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-indigo-700 bg-indigo-100 hover:bg-indigo-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                    >
                        Edit Role Template
                    </Link>
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

                <!-- Role Template Details -->
                <template v-else-if="roleTemplate">
                    <!-- Overview Card -->
                    <div class="bg-white shadow sm:rounded-lg">
                        <div class="px-4 py-5 sm:p-6">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Role Template Overview</h3>
                            <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-3">
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Context</dt>
                                    <dd class="mt-1 text-sm text-gray-900">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            {{ roleTemplate?.context?.replace('_', ' ').toUpperCase() || 'SERVICE PROVIDER' }}
                                        </span>
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Users Assigned</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ roleTemplate?.users_count || 0 }} users</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Status</dt>
                                    <dd class="mt-1 flex space-x-2">
                                        <span v-if="!roleTemplate.is_modifiable" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                            System Role
                                        </span>
                                        <span v-if="roleTemplate.is_default" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            Default Role
                                        </span>
                                        <span v-if="roleTemplate.is_modifiable && !roleTemplate.is_default" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            Custom Role
                                        </span>
                                    </dd>
                                </div>
                                <div class="sm:col-span-3">
                                    <dt class="text-sm font-medium text-gray-500">Permission Summary</dt>
                                    <dd class="mt-1 text-sm text-gray-900">
                                        <div class="flex space-x-4">
                                            <span>{{ (roleTemplate.permissions || []).length }} functional permissions</span>
                                            <span>•</span>
                                            <span>{{ (roleTemplate.widget_permissions || []).length }} widget permissions</span>
                                            <span>•</span>
                                            <span>{{ (roleTemplate.page_permissions || []).length }} page permissions</span>
                                        </div>
                                    </dd>
                                </div>
                            </dl>
                        </div>
                    </div>

                    <!-- Three-Dimensional Permissions -->
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <!-- Functional Permissions -->
                        <div class="bg-white shadow sm:rounded-lg">
                            <div class="px-4 py-5 sm:p-6">
                                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                                    Functional Permissions
                                    <span class="ml-2 text-sm text-gray-500">({{ (roleTemplate.permissions || []).length }})</span>
                                </h3>
                                <div class="space-y-4 max-h-96 overflow-y-auto">
                                    <div v-for="(permissions, category) in groupedPermissions" :key="category" class="border rounded-lg p-3">
                                        <h4 class="text-sm font-medium text-gray-900 mb-2">{{ getCategoryDisplayName(category) }}</h4>
                                        <div class="space-y-1">
                                            <div v-for="permission in permissions" :key="permission" class="text-xs text-gray-600 bg-gray-50 rounded px-2 py-1">
                                                {{ formatPermissionName(permission) }}
                                            </div>
                                        </div>
                                    </div>
                                    <div v-if="Object.keys(groupedPermissions).length === 0" class="text-sm text-gray-500 italic">
                                        No functional permissions assigned
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Widget Permissions -->
                        <div class="bg-white shadow sm:rounded-lg">
                            <div class="px-4 py-5 sm:p-6">
                                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                                    Widget Permissions  
                                    <span class="ml-2 text-sm text-gray-500">({{ availableWidgets.length }})</span>
                                </h3>
                                <div v-if="widgetsLoading" class="flex items-center justify-center py-8">
                                    <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-indigo-600"></div>
                                    <span class="ml-2 text-sm text-gray-600">Loading widgets...</span>
                                </div>
                                <div v-else class="space-y-4 max-h-96 overflow-y-auto">
                                    <div v-for="(widgets, category) in groupedWidgets" :key="category" class="border rounded-lg p-3">
                                        <h4 class="text-sm font-medium text-gray-900 mb-2">{{ getCategoryDisplayName(category) }}</h4>
                                        <div class="space-y-1">
                                            <div v-for="widget in widgets" :key="widget.id" class="text-xs text-gray-600 bg-gray-50 rounded px-2 py-1 flex items-center justify-between">
                                                <span>{{ widget.name }}</span>
                                                <span v-if="widget.enabled_by_default" class="text-green-600">✓</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div v-if="Object.keys(groupedWidgets).length === 0" class="text-sm text-gray-500 italic">
                                        No widgets available for this role
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Page Permissions -->
                        <div class="bg-white shadow sm:rounded-lg">
                            <div class="px-4 py-5 sm:p-6">
                                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                                    Page Permissions
                                    <span class="ml-2 text-sm text-gray-500">({{ (roleTemplate.page_permissions || []).length }})</span>
                                </h3>
                                <div class="space-y-2 max-h-96 overflow-y-auto">
                                    <div v-for="permission in roleTemplate.page_permissions" :key="permission" class="text-xs text-gray-600 bg-gray-50 rounded px-2 py-1">
                                        {{ formatPermissionName(permission) }}
                                    </div>
                                    <div v-if="(roleTemplate.page_permissions || []).length === 0" class="text-sm text-gray-500 italic">
                                        No page permissions assigned
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Assigned Roles -->
                    <div v-if="roleTemplate.roles && roleTemplate.roles.length > 0" class="bg-white shadow sm:rounded-lg">
                        <div class="px-4 py-5 sm:p-6">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Assigned Roles</h3>
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                                <div v-for="role in roleTemplate.roles" :key="role.id" class="border rounded-lg p-3">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="text-sm font-medium text-gray-900">{{ role.name }}</p>
                                            <p class="text-xs text-gray-500">{{ role.users_count }} users</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>
        
        <!-- Dashboard Preview Modal -->
        <DashboardPreviewModal 
            :open="showPreviewModal" 
            :role-template="roleTemplate" 
            @close="showPreviewModal = false"
        />

        <!-- Widget Assignment Modal -->
        <WidgetAssignmentModal 
            :open="showWidgetModal" 
            :role-template="roleTemplate" 
            @close="showWidgetModal = false"
            @saved="handleWidgetsSaved"
        />
</template>