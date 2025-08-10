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
                            <form @submit.prevent="submitApproval">
                                <div class="sm:flex sm:items-start">
                                    <div class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full sm:mx-0 sm:h-10 sm:w-10"
                                         :class="isRejection ? 'bg-red-100' : 'bg-green-100'"
                                    >
                                        <XMarkIcon v-if="isRejection" class="h-6 w-6 text-red-600" aria-hidden="true" />
                                        <CheckIcon v-else class="h-6 w-6 text-green-600" aria-hidden="true" />
                                    </div>
                                    <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left w-full">
                                        <DialogTitle as="h3" class="text-base font-semibold leading-6 text-gray-900">
                                            {{ isRejection ? 'Reject' : 'Approve' }} Addon
                                        </DialogTitle>
                                        <div class="mt-4">
                                            <!-- Addon Details -->
                                            <div class="bg-gray-50 rounded-lg p-4 mb-4">
                                                <div class="text-sm">
                                                    <div class="font-medium text-gray-900">{{ addon?.name }}</div>
                                                    <div class="text-gray-500 mt-1">
                                                        {{ addon?.quantity }}x {{ formatPrice(addon?.unit_price) }} = {{ formatPrice(addon?.total_amount) }}
                                                    </div>
                                                    <div v-if="addon?.description" class="text-gray-600 mt-1">
                                                        {{ addon.description }}
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Approval Notes -->
                                            <div>
                                                <label for="approval_notes" class="block text-sm font-medium leading-6 text-gray-900">
                                                    {{ isRejection ? 'Rejection Reason *' : 'Approval Notes' }}
                                                </label>
                                                <div class="mt-2">
                                                    <textarea
                                                        id="approval_notes"
                                                        v-model="form.approval_notes"
                                                        :required="isRejection"
                                                        rows="3"
                                                        class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
                                                        :placeholder="isRejection ? 'Please provide a reason for rejection...' : 'Optional notes about this approval...'"
                                                    />
                                                </div>
                                                <p class="mt-1 text-xs text-gray-500">
                                                    {{ isRejection ? 'Please explain why this addon is being rejected.' : 'Optional notes that will be visible to the team.' }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mt-5 sm:mt-6 sm:flex sm:flex-row-reverse">
                                    <button
                                        type="submit"
                                        :disabled="processing || (isRejection && !form.approval_notes.trim())"
                                        class="inline-flex w-full justify-center rounded-md px-3 py-2 text-sm font-semibold text-white shadow-sm focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 sm:ml-3 sm:w-auto disabled:opacity-50"
                                        :class="isRejection ? 'bg-red-600 hover:bg-red-500 focus-visible:outline-red-600' : 'bg-green-600 hover:bg-green-500 focus-visible:outline-green-600'"
                                    >
                                        <span v-if="processing">Processing...</span>
                                        <span v-else>{{ isRejection ? 'Reject' : 'Approve' }} Addon</span>
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
import { CheckIcon, XMarkIcon } from '@heroicons/vue/24/outline'
import { ref, reactive, watch } from 'vue'
import axios from 'axios'

const props = defineProps({
    show: Boolean,
    addon: Object,
    isRejection: {
        type: Boolean,
        default: false
    }
})

const emit = defineEmits(['close', 'approved'])

const processing = ref(false)
const form = reactive({
    approval_notes: ''
})

// Reset form when modal opens/closes
watch(() => props.show, (show) => {
    if (show) {
        form.approval_notes = ''
    }
})

// Format price helper
const formatPrice = (amount) => {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD'
    }).format(amount || 0)
}

// Submit approval/rejection
const submitApproval = async () => {
    if (!props.addon) return
    
    processing.value = true
    
    try {
        const endpoint = props.isRejection ? 'reject' : 'approve'
        const url = `/api/ticket-addons/${props.addon.id}/${endpoint}`
        
        const data = {
            approval_notes: form.approval_notes.trim() || null
        }
        
        const response = await axios.post(url, data)
        
        emit('approved', response.data.data)
    } catch (error) {
        console.error('Failed to process addon approval:', error)
        
        // Show error message
        let errorMessage = 'Failed to process approval. Please try again.'
        if (error.response?.data?.message) {
            errorMessage = error.response.data.message
        }
        alert(errorMessage)
    } finally {
        processing.value = false
    }
}
</script>