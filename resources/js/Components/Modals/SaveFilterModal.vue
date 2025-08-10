<template>
    <TransitionRoot as="template" :show="show">
        <Dialog as="div" class="relative z-50" @close="$emit('close')">
            <TransitionChild
                as="template"
                enter="ease-out duration-300"
                enter-from="opacity-0"
                enter-to="opacity-100"
                leave="ease-in duration-200"
                leave-from="opacity-100"
                leave-to="opacity-0"
            >
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" />
            </TransitionChild>

            <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
                <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                    <TransitionChild
                        as="template"
                        enter="ease-out duration-300"
                        enter-from="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                        enter-to="opacity-100 translate-y-0 sm:scale-100"
                        leave="ease-in duration-200"
                        leave-from="opacity-100 translate-y-0 sm:scale-100"
                        leave-to="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    >
                        <DialogPanel class="relative transform overflow-hidden rounded-lg bg-white px-4 pb-4 pt-5 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:p-6">
                            <div class="sm:flex sm:items-start">
                                <div class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-blue-100 sm:mx-0 sm:h-10 sm:w-10">
                                    <BookmarkIcon class="h-6 w-6 text-blue-600" aria-hidden="true" />
                                </div>
                                <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left w-full">
                                    <DialogTitle as="h3" class="text-base font-semibold leading-6 text-gray-900">
                                        Save Filter View
                                    </DialogTitle>
                                    <div class="mt-4">
                                        <form @submit.prevent="saveView">
                                            <!-- View Name -->
                                            <div class="mb-4">
                                                <label for="viewName" class="block text-sm font-medium leading-6 text-gray-900">
                                                    View Name <span class="text-red-500">*</span>
                                                </label>
                                                <div class="mt-2">
                                                    <input
                                                        id="viewName"
                                                        v-model="form.name"
                                                        type="text"
                                                        required
                                                        placeholder="e.g., My Open High Priority Tickets"
                                                        class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
                                                    />
                                                </div>
                                            </div>

                                            <!-- Description -->
                                            <div class="mb-4">
                                                <label for="viewDescription" class="block text-sm font-medium leading-6 text-gray-900">
                                                    Description
                                                </label>
                                                <div class="mt-2">
                                                    <textarea
                                                        id="viewDescription"
                                                        v-model="form.description"
                                                        rows="2"
                                                        placeholder="Optional description of this filter view..."
                                                        class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
                                                    />
                                                </div>
                                            </div>

                                            <!-- Current Filters Preview -->
                                            <div v-if="filterCount > 0" class="mb-4 p-3 bg-gray-50 rounded-lg">
                                                <div class="text-sm font-medium text-gray-700 mb-2">
                                                    Current Filters ({{ filterCount }})
                                                </div>
                                                <div class="flex flex-wrap gap-1">
                                                    <span
                                                        v-for="(value, key) in currentFilters"
                                                        :key="key"
                                                        class="inline-flex items-center px-2 py-1 rounded text-xs bg-blue-100 text-blue-800"
                                                    >
                                                        {{ formatFilterLabel(key, value) }}
                                                    </span>
                                                </div>
                                            </div>

                                            <!-- No Filters Warning -->
                                            <div v-else class="mb-4 p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                                                <div class="flex">
                                                    <ExclamationTriangleIcon class="h-5 w-5 text-yellow-400" aria-hidden="true" />
                                                    <div class="ml-3">
                                                        <h3 class="text-sm font-medium text-yellow-800">
                                                            No Filters Applied
                                                        </h3>
                                                        <div class="mt-1 text-sm text-yellow-700">
                                                            You need to apply some filters before saving a view.
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Options -->
                                            <div class="mb-4">
                                                <label class="flex items-center">
                                                    <input
                                                        v-model="form.isDefault"
                                                        type="checkbox"
                                                        class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-600"
                                                    />
                                                    <span class="ml-2 text-sm text-gray-700">
                                                        Set as default view
                                                    </span>
                                                </label>
                                            </div>

                                            <div class="mt-5 sm:mt-6 sm:flex sm:flex-row-reverse">
                                                <button
                                                    type="submit"
                                                    :disabled="processing || filterCount === 0 || !form.name.trim()"
                                                    class="inline-flex w-full justify-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 sm:ml-3 sm:w-auto disabled:opacity-50"
                                                >
                                                    <span v-if="processing">Saving...</span>
                                                    <span v-else>Save View</span>
                                                </button>
                                                <button
                                                    type="button"
                                                    class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:mt-0 sm:w-auto"
                                                    @click="$emit('close')"
                                                >
                                                    Cancel
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </DialogPanel>
                    </TransitionChild>
                </div>
            </div>
        </Dialog>
    </TransitionRoot>
</template>

<script setup>
import { Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot } from '@headlessui/vue'
import { BookmarkIcon, ExclamationTriangleIcon } from '@heroicons/vue/24/outline'
import { ref, reactive, computed, watch } from 'vue'

const props = defineProps({
    show: {
        type: Boolean,
        default: true
    },
    currentFilters: {
        type: Object,
        default: () => ({})
    }
})

const emit = defineEmits(['close', 'saved'])

const processing = ref(false)
const form = reactive({
    name: '',
    description: '',
    isDefault: false
})

const filterCount = computed(() => {
    return Object.keys(props.currentFilters).filter(key => {
        const value = props.currentFilters[key]
        return value && value !== '' && !(Array.isArray(value) && value.length === 0)
    }).length
})

const formatFilterLabel = (key, value) => {
    const labels = {
        status: 'Status',
        priority: 'Priority',
        assigned_to: 'Assigned',
        created_after: 'After',
        created_before: 'Before',
        search: 'Search',
        account_id: 'Account'
    }
    
    let displayValue = value
    if (key === 'assigned_to' && value === 'me') {
        displayValue = 'Me'
    } else if (key === 'assigned_to' && value === 'unassigned') {
        displayValue = 'Unassigned'
    } else if (Array.isArray(value)) {
        displayValue = value.join(', ')
    } else if (typeof value === 'boolean') {
        displayValue = value ? 'Yes' : 'No'
    }
    
    return `${labels[key] || key.replace('_', ' ')}: ${displayValue}`
}

const saveView = async () => {
    if (!form.name.trim() || filterCount.value === 0) {
        return
    }

    processing.value = true

    try {
        // In a real app, this would be an API call
        const savedView = {
            id: Date.now(), // Simple ID generation for demo
            name: form.name.trim(),
            description: form.description.trim(),
            filters: { ...props.currentFilters },
            isDefault: form.isDefault,
            created_at: new Date().toISOString(),
            filter_count: filterCount.value
        }

        emit('saved', savedView)
        
        // Reset form
        form.name = ''
        form.description = ''
        form.isDefault = false
        
    } catch (error) {
        console.error('Failed to save filter view:', error)
        alert('Failed to save filter view. Please try again.')
    } finally {
        processing.value = false
    }
}

// Reset form when modal opens
watch(() => props.show, (show) => {
    if (show) {
        form.name = ''
        form.description = ''
        form.isDefault = false
    }
})
</script>