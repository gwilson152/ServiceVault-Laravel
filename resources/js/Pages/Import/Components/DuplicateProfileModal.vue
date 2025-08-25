<template>
    <StackedDialog 
        :show="show" 
        @close="$emit('close')"
        title="Duplicate Import Profile"
        max-width="md"
    >
        <form @submit.prevent="duplicateProfile">
            <div class="space-y-6">
                <!-- Source Profile Info -->
                <div class="bg-gray-50 rounded-lg p-4">
                    <h3 class="text-sm font-medium text-gray-900">Source Profile</h3>
                    <div class="mt-2 text-sm text-gray-600">
                        <p><strong>Name:</strong> {{ profile?.name }}</p>
                        <p><strong>Database:</strong> {{ profile?.database_type }} - {{ profile?.database }}</p>
                        <p v-if="profile?.sync_enabled" class="flex items-center mt-1">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                                Sync Enabled
                            </span>
                            <span class="ml-2 text-xs">{{ profile?.sync_frequency }} at {{ profile?.sync_time }}</span>
                        </p>
                    </div>
                </div>

                <!-- Name Field -->
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">
                        New Profile Name
                    </label>
                    <input
                        id="name"
                        v-model="form.name"
                        type="text"
                        required
                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                        :class="{ 'border-red-300': errors.name }"
                        placeholder="Enter a unique name for the duplicate"
                    />
                    <p v-if="errors.name" class="mt-1 text-sm text-red-600">{{ errors.name[0] }}</p>
                    <p class="mt-1 text-xs text-gray-500">This name must be unique across all import profiles</p>
                </div>

                <!-- Description Field -->
                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-1">
                        Description (Optional)
                    </label>
                    <textarea
                        id="description"
                        v-model="form.description"
                        rows="3"
                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                        :class="{ 'border-red-300': errors.description }"
                        placeholder="Optional description for the duplicate profile"
                    ></textarea>
                    <p v-if="errors.description" class="mt-1 text-sm text-red-600">{{ errors.description[0] }}</p>
                </div>

                <!-- What Will Be Copied -->
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <InformationCircleIcon class="h-5 w-5 text-blue-400" />
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-blue-800">What will be copied</h3>
                            <div class="mt-2 text-sm text-blue-700">
                                <ul class="list-disc list-inside space-y-1">
                                    <li>Database connection settings (host, credentials, SSL mode)</li>
                                    <li>Import configuration (field mappings, query builder settings)</li>
                                    <li>Sync settings (frequency, time, options)</li>
                                    <li>Duplicate detection and update rules</li>
                                    <li>All import modes and processing options</li>
                                </ul>
                                <p class="mt-2 font-medium">The following will NOT be copied:</p>
                                <ul class="list-disc list-inside space-y-1 text-blue-600">
                                    <li>Import history and statistics</li>
                                    <li>Test results and connection status</li>
                                    <li>Last sync timestamps</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="mt-6 flex items-center justify-end space-x-3">
                <button
                    type="button"
                    @click="$emit('close')"
                    class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                    :disabled="isLoading"
                >
                    Cancel
                </button>
                <button
                    type="submit"
                    class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed"
                    :disabled="isLoading || !form.name.trim()"
                >
                    <div v-if="isLoading" class="animate-spin -ml-1 mr-2 h-4 w-4 border-2 border-white border-t-transparent rounded-full"></div>
                    <DocumentDuplicateIcon v-else class="w-4 h-4 mr-2" />
                    {{ isLoading ? 'Creating Duplicate...' : 'Create Duplicate' }}
                </button>
            </div>
        </form>
    </StackedDialog>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { DocumentDuplicateIcon, InformationCircleIcon } from '@heroicons/vue/24/outline'
import StackedDialog from '@/Components/StackedDialog.vue'

const props = defineProps({
    show: Boolean,
    profile: {
        type: Object,
        default: null
    }
})

const emit = defineEmits(['close', 'duplicated'])

// Form state
const form = ref({
    name: '',
    description: ''
})

const errors = ref({})
const isLoading = ref(false)

// Generate suggested name when profile changes
watch(() => props.profile, (profile) => {
    if (profile) {
        form.value.name = `Copy of ${profile.name}`
        form.value.description = `Duplicate of ${profile.name} created on ${new Date().toLocaleDateString()}`
    }
}, { immediate: true })

// Clear form when modal closes
watch(() => props.show, (show) => {
    if (!show) {
        errors.value = {}
        isLoading.value = false
    }
})

const duplicateProfile = async () => {
    if (!props.profile || isLoading.value) return

    isLoading.value = true
    errors.value = {}

    try {
        const response = await fetch(`/api/import/profiles/${props.profile.id}/duplicate`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            },
            body: JSON.stringify({
                name: form.value.name.trim(),
                description: form.value.description.trim() || null
            })
        })

        const data = await response.json()

        if (!response.ok) {
            if (response.status === 422 && data.errors) {
                errors.value = data.errors
            } else {
                throw new Error(data.message || 'Failed to duplicate profile')
            }
            return
        }

        // Success - emit the new profile data
        emit('duplicated', {
            message: data.message,
            profile: data.profile
        })
        
        emit('close')

    } catch (error) {
        console.error('Duplicate profile error:', error)
        errors.value = { 
            general: [error.message || 'An unexpected error occurred while duplicating the profile']
        }
    } finally {
        isLoading.value = false
    }
}
</script>