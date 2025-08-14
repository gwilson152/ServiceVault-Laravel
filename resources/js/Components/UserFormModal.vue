<script setup>
import { ref, watch, computed } from 'vue'
import { useCreateUserMutation, useUpdateUserMutation } from '@/Composables/queries/useUsersQuery'

const props = defineProps({
    open: {
        type: Boolean,
        default: false
    },
    user: {
        type: Object,
        default: null
    },
    accounts: {
        type: Array,
        default: () => []
    },
    roleTemplates: {
        type: Array,
        default: () => []
    },
    preselectedAccountId: {
        type: [String, Number],
        default: null
    }
})

const emit = defineEmits(['close', 'saved'])

const form = ref({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    timezone: '',
    locale: 'en',
    is_active: true,
    is_visible: true,
    account_id: null,
    role_template_id: null,
    preferences: {},
    send_invitation: true
})

const errors = ref({})
const showPassword = ref(false)

// TanStack Query mutations
const createUserMutation = useCreateUserMutation()
const updateUserMutation = useUpdateUserMutation()

const saving = computed(() => createUserMutation.isPending.value || updateUserMutation.isPending.value)

const isEditing = computed(() => !!props.user?.id)
const modalTitle = computed(() => isEditing.value ? 'Edit User' : 'Create User')

// Watch for changes to populate form
watch(() => props.open, async (isOpen) => {
    if (isOpen) {
        if (props.user) {
            // Editing existing user
            form.value = {
                name: props.user.name || '',
                email: props.user.email || '',
                password: '',
                password_confirmation: '',
                timezone: props.user.timezone || '',
                locale: props.user.locale || 'en',
                is_active: props.user.is_active ?? true,
                is_visible: props.user.is_visible ?? true,
                account_id: props.user.account?.id || null,
                role_template_id: props.user.role_template?.id || null,
                preferences: props.user.preferences || {}
            }
        } else {
            // Creating new user
            resetForm()
        }
        errors.value = {}
    }
})

const resetForm = () => {
    form.value = {
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
        timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
        locale: 'en',
        is_active: true,
        is_visible: true,
        account_id: props.preselectedAccountId || null,
        role_template_id: null,
        preferences: {},
        send_invitation: true
    }
}

const saveUser = async () => {
    try {
        errors.value = {}
        
        const formData = { ...form.value }
        
        // Handle password logic based on mode
        if (isEditing.value) {
            // For editing: only send password if provided
            if (!formData.password) {
                delete formData.password
                delete formData.password_confirmation
            }
            // Remove invitation flag for existing users
            delete formData.send_invitation
        } else {
            // For new users: remove password fields if sending invitation or user is inactive
            if (formData.send_invitation || !formData.is_active) {
                delete formData.password
                delete formData.password_confirmation
            }
        }
        
        let response
        if (isEditing.value) {
            response = await updateUserMutation.mutateAsync({ id: props.user.id, data: formData })
        } else {
            response = await createUserMutation.mutateAsync(formData)
        }
        
        emit('saved', response.data.data)
        emit('close')
    } catch (error) {
        if (error.response?.data?.errors) {
            errors.value = error.response.data.errors
        } else {
            errors.value = { general: ['An error occurred while saving the user.'] }
        }
    }
}

const closeModal = () => {
    emit('close')
}

const getAccountDisplayName = () => {
    if (!props.preselectedAccountId) return ''
    const account = props.accounts.find(acc => acc.id == props.preselectedAccountId)
    return account ? `${account.display_name || account.name} (${account.account_type || 'Account'})` : 'Selected Account'
}

const timezones = [
    'UTC',
    'America/New_York',
    'America/Chicago', 
    'America/Denver',
    'America/Los_Angeles',
    'Europe/London',
    'Europe/Berlin',
    'Asia/Tokyo',
    'Australia/Sydney'
]

const locales = [
    { value: 'en', label: 'English' },
    { value: 'es', label: 'Spanish' },
    { value: 'fr', label: 'French' },
    { value: 'de', label: 'German' },
    { value: 'it', label: 'Italian' },
    { value: 'pt', label: 'Portuguese' }
]
</script>

<template>
    <!-- Modal backdrop -->
    <div v-if="open" class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center p-4 z-50">
        <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
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
            <form @submit.prevent="saveUser" class="px-6 py-4">
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
                            <!-- Full Name -->
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700">Full Name *</label>
                                <input
                                    id="name"
                                    v-model="form.name"
                                    type="text"
                                    required
                                    placeholder="John Doe"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                    :class="{ 'border-red-300 focus:border-red-500 focus:ring-red-500': errors.name }"
                                />
                                <p v-if="errors.name" class="mt-1 text-sm text-red-600">{{ errors.name[0] }}</p>
                            </div>

                            <!-- Email -->
                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700">Email Address *</label>
                                <input
                                    id="email"
                                    v-model="form.email"
                                    type="email"
                                    required
                                    placeholder="john@company.com"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                    :class="{ 'border-red-300 focus:border-red-500 focus:ring-red-500': errors.email }"
                                />
                                <p v-if="errors.email" class="mt-1 text-sm text-red-600">{{ errors.email[0] }}</p>
                            </div>

                            <!-- Invitation Option (New Users Only) -->
                            <div v-if="!isEditing" class="md:col-span-2">
                                <div class="flex items-center">
                                    <input
                                        id="send_invitation"
                                        v-model="form.send_invitation"
                                        type="checkbox"
                                        class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                                    />
                                    <label for="send_invitation" class="ml-2 block text-sm text-gray-900">
                                        Send invitation email to user
                                    </label>
                                </div>
                                <p class="mt-1 text-xs text-gray-500">
                                    When enabled, the user will receive an email invitation to set their own password and activate their account.
                                </p>
                            </div>

                            <!-- Password (only if editing or not sending invitation) -->
                            <div v-if="isEditing || (!form.send_invitation && form.is_active)">
                                <label for="password" class="block text-sm font-medium text-gray-700">
                                    Password {{ isEditing ? '(leave empty to keep current)' : '*' }}
                                </label>
                                <div class="mt-1 relative">
                                    <input
                                        id="password"
                                        v-model="form.password"
                                        :type="showPassword ? 'text' : 'password'"
                                        :required="!isEditing && !form.send_invitation && form.is_active"
                                        placeholder="Enter password"
                                        class="block w-full pr-10 border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                        :class="{ 'border-red-300 focus:border-red-500 focus:ring-red-500': errors.password }"
                                    />
                                    <button
                                        type="button"
                                        @click="showPassword = !showPassword"
                                        class="absolute inset-y-0 right-0 pr-3 flex items-center"
                                    >
                                        <svg v-if="showPassword" class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                        <svg v-else class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21"/>
                                        </svg>
                                    </button>
                                </div>
                                <p v-if="errors.password" class="mt-1 text-sm text-red-600">{{ errors.password[0] }}</p>
                            </div>

                            <!-- Password Confirmation (only if editing or not sending invitation) -->
                            <div v-if="isEditing || (!form.send_invitation && form.is_active)">
                                <label for="password_confirmation" class="block text-sm font-medium text-gray-700">Confirm Password</label>
                                <input
                                    id="password_confirmation"
                                    v-model="form.password_confirmation"
                                    type="password"
                                    placeholder="Confirm password"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Preferences Section -->
                    <div class="border-b border-gray-200 pb-4">
                        <h4 class="text-lg font-medium text-gray-900 mb-4">User Preferences</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Timezone -->
                            <div>
                                <label for="timezone" class="block text-sm font-medium text-gray-700">Timezone</label>
                                <select
                                    id="timezone"
                                    v-model="form.timezone"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                >
                                    <option value="">Select timezone...</option>
                                    <option v-for="tz in timezones" :key="tz" :value="tz">{{ tz }}</option>
                                </select>
                            </div>

                            <!-- Locale -->
                            <div>
                                <label for="locale" class="block text-sm font-medium text-gray-700">Language</label>
                                <select
                                    id="locale"
                                    v-model="form.locale"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                >
                                    <option v-for="locale in locales" :key="locale.value" :value="locale.value">{{ locale.label }}</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Account Assignment Section -->
                    <div v-if="!preselectedAccountId" class="border-b border-gray-200 pb-4">
                        <h4 class="text-lg font-medium text-gray-900 mb-4">Account Assignment</h4>
                        <div>
                            <label for="account_id" class="block text-sm font-medium text-gray-700">Primary Account</label>
                            <select
                                id="account_id"
                                v-model="form.account_id"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                            >
                                <option :value="null">Select an account...</option>
                                <option v-for="account in accounts" :key="account.id" :value="account.id">
                                    {{ account.display_name }} ({{ account.account_type }})
                                </option>
                            </select>
                            <p class="mt-1 text-xs text-gray-500">Select the primary account this user belongs to.</p>
                        </div>
                    </div>
                    
                    <!-- Preselected Account Display -->
                    <div v-else class="border-b border-gray-200 pb-4">
                        <h4 class="text-lg font-medium text-gray-900 mb-4">Account Assignment</h4>
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
                            <div class="flex items-center">
                                <svg class="w-5 h-5 text-blue-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-4m-6 0H3m2 0h4m6 0v-3.87a3.37 3.37 0 00-.94-2.61c-.26-.26-.70-.26-.96 0L9.47 16.1a3.37 3.37 0 00-.94 2.61V21"/>
                                </svg>
                                <div>
                                    <p class="text-sm font-medium text-blue-900">Account: {{ getAccountDisplayName() }}</p>
                                    <p class="text-xs text-blue-700">User will be assigned to this account</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Role Assignment Section -->
                    <div class="border-b border-gray-200 pb-4">
                        <h4 class="text-lg font-medium text-gray-900 mb-4">Role Assignment</h4>
                        <div>
                            <label for="role_template_id" class="block text-sm font-medium text-gray-700">Role Template</label>
                            <select
                                id="role_template_id"
                                v-model="form.role_template_id"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                            >
                                <option :value="null">Select a role...</option>
                                <option v-for="role in roleTemplates" :key="role.id" :value="role.id">
                                    {{ role.name }} ({{ role.context }})
                                </option>
                            </select>
                            <p class="mt-1 text-xs text-gray-500">Select the role template for this user.</p>
                        </div>
                    </div>

                    <!-- Status Section -->
                    <div>
                        <h4 class="text-lg font-medium text-gray-900 mb-4">Account Status</h4>
                        <div class="space-y-4">
                            <div>
                                <div class="flex items-center">
                                    <input
                                        id="is_active"
                                        v-model="form.is_active"
                                        type="checkbox"
                                        class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                                    />
                                    <label for="is_active" class="ml-2 block text-sm text-gray-900">
                                        User is active
                                    </label>
                                </div>
                                <p class="mt-1 text-xs text-gray-500">Inactive users cannot log in or access the system.</p>
                            </div>
                            
                            <div>
                                <div class="flex items-center">
                                    <input
                                        id="is_visible"
                                        v-model="form.is_visible"
                                        type="checkbox"
                                        class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                                    />
                                    <label for="is_visible" class="ml-2 block text-sm text-gray-900">
                                        User is visible
                                    </label>
                                </div>
                                <p class="mt-1 text-xs text-gray-500">Hidden users are not shown in user lists and selections.</p>
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
                        {{ saving ? 'Saving...' : (isEditing ? 'Update User' : 'Create User') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>