<template>
    <div class="bg-white shadow-sm border border-red-200 rounded-lg">
        <div class="px-6 py-4 border-b border-red-200 bg-red-50">
            <div class="flex items-center">
                <ExclamationTriangleIcon class="w-6 h-6 text-red-600 mr-3" />
                <div>
                    <h3 class="text-lg font-semibold text-red-900">Nuclear System Reset</h3>
                    <p class="text-sm text-red-700 mt-1">
                        Completely wipe all data and reset the system to initial state
                    </p>
                </div>
            </div>
        </div>

        <div class="p-6">
            <div class="mb-6">
                <h4 class="text-md font-medium text-gray-900 mb-3">⚠️ Warning: This action is irreversible!</h4>
                <div class="bg-red-50 border border-red-200 rounded-md p-4">
                    <div class="text-sm text-red-800">
                        <p class="font-medium mb-2">This nuclear reset will:</p>
                        <ul class="list-disc ml-5 space-y-1">
                            <li>Delete ALL user accounts, tickets, time entries, and data</li>
                            <li>Reset all system settings to defaults</li>
                            <li>Clear all caches and sessions</li>
                            <li>Redirect to setup page for reconfiguration</li>
                            <li>Require you to create a new admin account</li>
                        </ul>
                        <p class="mt-3 font-medium">
                            Only Super Administrators can perform this action.
                        </p>
                    </div>
                </div>
            </div>

            <div class="flex justify-end">
                <button
                    @click="showConfirmationModal = true"
                    :disabled="loading"
                    class="inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed text-white text-sm font-medium rounded-md transition duration-150 ease-in-out"
                >
                    <ExclamationTriangleIcon class="w-4 h-4 mr-2" />
                    {{ loading ? 'Processing...' : 'Initiate Nuclear Reset' }}
                </button>
            </div>
        </div>

        <!-- Confirmation Modal -->
        <Modal 
            :show="showConfirmationModal" 
            @close="closeModal"
            max-width="lg"
        >
            <div class="p-6">
                <div class="flex items-center text-red-600 mb-4">
                    <ExclamationTriangleIcon class="w-6 h-6 mr-2" />
                    <h2 class="text-lg font-medium">Confirm Nuclear System Reset</h2>
                </div>
                <div class="space-y-4">
                    <div class="bg-red-50 border border-red-200 rounded-md p-4">
                        <p class="text-red-800 font-medium">
                            🚨 You are about to perform a NUCLEAR SYSTEM RESET 🚨
                        </p>
                        <p class="text-red-700 text-sm mt-2">
                            This will completely destroy all data and cannot be undone.
                        </p>
                    </div>

                    <div class="space-y-3">
                        <div class="flex items-start">
                            <input
                                id="confirm-understand"
                                v-model="confirmations.understand"
                                type="checkbox"
                                class="mt-1 h-4 w-4 text-red-600 focus:ring-red-500 border-gray-300 rounded"
                            />
                            <label for="confirm-understand" class="ml-2 text-sm text-gray-700">
                                I understand this will permanently delete all data
                            </label>
                        </div>

                        <div class="flex items-start">
                            <input
                                id="confirm-backup"
                                v-model="confirmations.backup"
                                type="checkbox"
                                class="mt-1 h-4 w-4 text-red-600 focus:ring-red-500 border-gray-300 rounded"
                            />
                            <label for="confirm-backup" class="ml-2 text-sm text-gray-700">
                                I have backed up any data I need to preserve
                            </label>
                        </div>

                        <div class="flex items-start">
                            <input
                                id="confirm-intention"
                                v-model="confirmations.intention"
                                type="checkbox"
                                class="mt-1 h-4 w-4 text-red-600 focus:ring-red-500 border-gray-300 rounded"
                            />
                            <label for="confirm-intention" class="ml-2 text-sm text-gray-700">
                                I intend to completely reset the system and start over
                            </label>
                        </div>
                    </div>

                    <div class="border-t pt-4">
                        <label for="password-confirmation" class="block text-sm font-medium text-gray-700 mb-2">
                            Confirm your password to proceed:
                        </label>
                        <input
                            id="password-confirmation"
                            v-model="password"
                            type="password"
                            placeholder="Enter your password"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-red-500 focus:border-red-500"
                            @keyup.enter="performNuclearReset"
                        />
                        <p v-if="passwordError" class="mt-1 text-sm text-red-600">
                            {{ passwordError }}
                        </p>
                    </div>
                </div>

                <div class="flex justify-between mt-6 pt-4 border-t">
                    <button
                        @click="closeModal"
                        :disabled="loading"
                        class="px-4 py-2 bg-gray-300 hover:bg-gray-400 disabled:opacity-50 text-gray-700 text-sm font-medium rounded-md transition duration-150 ease-in-out"
                    >
                        Cancel
                    </button>
                    
                    <button
                        @click="performNuclearReset"
                        :disabled="!canProceed || loading"
                        class="px-4 py-2 bg-red-600 hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed text-white text-sm font-medium rounded-md transition duration-150 ease-in-out"
                    >
                        <span v-if="loading">
                            <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white inline" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Executing Nuclear Reset...
                        </span>
                        <span v-else>
                            🚨 EXECUTE NUCLEAR RESET 🚨
                        </span>
                    </button>
                </div>
            </div>
        </Modal>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import Modal from '@/Components/Modal.vue'
import { ExclamationTriangleIcon } from '@heroicons/vue/24/outline'

// Reactive data
const showConfirmationModal = ref(false)
const loading = ref(false)
const password = ref('')
const passwordError = ref('')

const confirmations = ref({
    understand: false,
    backup: false,
    intention: false
})

// Computed properties
const canProceed = computed(() => {
    return confirmations.value.understand && 
           confirmations.value.backup && 
           confirmations.value.intention && 
           password.value.length > 0
})

// Methods
const closeModal = () => {
    if (loading.value) return
    
    showConfirmationModal.value = false
    password.value = ''
    passwordError.value = ''
    confirmations.value = {
        understand: false,
        backup: false,
        intention: false
    }
}

const performNuclearReset = async () => {
    if (!canProceed.value || loading.value) return

    loading.value = true
    passwordError.value = ''

    try {
        const response = await window.axios.post('/api/settings/nuclear-reset', {
            password: password.value
        })

        if (response.data.success) {
            // Show success message briefly before redirect
            alert('Nuclear reset completed successfully! Redirecting to setup...')
            
            // Clear any existing session/auth data
            localStorage.clear()
            sessionStorage.clear()
            
            // Redirect to setup page
            window.location.href = '/setup'
        } else {
            throw new Error(response.data.message || 'Nuclear reset failed')
        }
    } catch (error) {
        console.error('Nuclear reset error:', error)
        
        if (error.response?.status === 422 && error.response?.data?.message?.includes('password')) {
            passwordError.value = 'Invalid password. Please try again.'
        } else if (error.response?.status === 403) {
            passwordError.value = 'Access denied. Only Super Administrators can perform nuclear reset.'
        } else {
            passwordError.value = error.response?.data?.message || 'Nuclear reset failed. Please try again.'
        }
    } finally {
        loading.value = false
    }
}
</script>