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
                            <form @submit.prevent="rejectEntry">
                                <div class="sm:flex sm:items-start">
                                    <div class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                                        <XMarkIcon class="h-6 w-6 text-red-600" aria-hidden="true" />
                                    </div>
                                    <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left w-full">
                                        <DialogTitle as="h3" class="text-base font-semibold leading-6 text-gray-900">
                                            Reject Time Entry
                                        </DialogTitle>
                                        
                                        <div class="mt-4">
                                            <!-- Time Entry Details -->
                                            <div class="bg-gray-50 rounded-lg p-4 mb-4">
                                                <div class="text-sm">
                                                    <div class="font-medium text-gray-900">
                                                        {{ timeEntry?.ticket?.title || 'No Ticket' }}
                                                    </div>
                                                    <div class="text-gray-500 mt-1">
                                                        {{ formatDuration(timeEntry?.duration) }} 
                                                        <span v-if="timeEntry?.date">
                                                            on {{ formatDate(timeEntry.date) }}
                                                        </span>
                                                    </div>
                                                    <div v-if="timeEntry?.description" class="text-gray-600 mt-1">
                                                        {{ timeEntry.description }}
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Rejection Reason -->
                                            <div>
                                                <label for="rejection_reason" class="block text-sm font-medium leading-6 text-gray-900">
                                                    Rejection Reason <span class="text-red-500">*</span>
                                                </label>
                                                <div class="mt-2">
                                                    <textarea
                                                        id="rejection_reason"
                                                        v-model="form.rejection_reason"
                                                        required
                                                        rows="3"
                                                        class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
                                                        placeholder="Please explain why this time entry is being rejected..."
                                                    />
                                                </div>
                                                <p class="mt-1 text-xs text-gray-500">
                                                    This reason will be visible to the user who submitted the time entry.
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mt-5 sm:mt-6 sm:flex sm:flex-row-reverse">
                                    <button
                                        type="submit"
                                        :disabled="processing || !form.rejection_reason.trim()"
                                        class="inline-flex w-full justify-center rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-600 sm:ml-3 sm:w-auto disabled:opacity-50"
                                    >
                                        <span v-if="processing">Rejecting...</span>
                                        <span v-else>Reject Entry</span>
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
                        </DialogPanel>
                    </TransitionChild>
                </div>
            </div>
        </Dialog>
    </TransitionRoot>
</template>

<script setup>
import { Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot } from '@headlessui/vue'
import { XMarkIcon } from '@heroicons/vue/24/outline'
import { ref, reactive, watch } from 'vue'
import axios from 'axios'

const props = defineProps({
    show: {
        type: Boolean,
        default: true
    },
    timeEntry: {
        type: Object,
        required: true
    }
})

const emit = defineEmits(['close', 'rejected'])

const processing = ref(false)
const form = reactive({
    rejection_reason: ''
})

const formatDuration = (seconds) => {
    if (!seconds) return '0:00'
    
    const hours = Math.floor(seconds / 3600)
    const minutes = Math.floor((seconds % 3600) / 60)
    
    if (hours > 0) {
        return `${hours}:${minutes.toString().padStart(2, '0')}h`
    }
    return `${minutes}:${(seconds % 60).toString().padStart(2, '0')}m`
}

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    })
}

const rejectEntry = async () => {
    if (!form.rejection_reason.trim() || !props.timeEntry) return
    
    processing.value = true
    
    try {
        const response = await axios.post(`/api/time-entries/${props.timeEntry.id}/reject`, {
            rejection_reason: form.rejection_reason.trim()
        })
        
        emit('rejected', response.data.data)
    } catch (error) {
        console.error('Failed to reject time entry:', error)
        
        let errorMessage = 'Failed to reject time entry. Please try again.'
        if (error.response?.data?.message) {
            errorMessage = error.response.data.message
        }
        alert(errorMessage)
    } finally {
        processing.value = false
    }
}

// Reset form when modal opens
watch(() => props.show, (show) => {
    if (show) {
        form.rejection_reason = ''
    }
})
</script>