<script setup>
import { computed, onMounted, onUnmounted, ref, watch, inject, provide, nextTick } from 'vue'

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    maxWidth: {
        type: String,
        default: '2xl',
    },
    fullscreen: {
        type: Boolean,
        default: false,
    },
    fullscreenPadding: {
        type: String,
        default: '4rem', // Default 64px from each edge for desktop fullscreen
    },
    closeable: {
        type: Boolean,
        default: true,
    },
    title: {
        type: String,
        default: '',
    },
    showHeader: {
        type: Boolean,
        default: true,
    },
    showFooter: {
        type: Boolean,
        default: true,
    },
    padContent: {
        type: Boolean,
        default: true,
    },
    allowDropdowns: {
        type: Boolean,
        default: false,
    },
})

const emit = defineEmits(['close'])

// Dialog stacking management
const dialogStack = inject('dialogStack', ref([]))
const dialogId = ref(Math.random().toString(36).substr(2, 9))
const dialog = ref()
const showSlot = ref(props.show)

// Provide the stack for child dialogs
provide('dialogStack', dialogStack)

// Computed properties
const stackLevel = computed(() => {
    return dialogStack.value.indexOf(dialogId.value)
})

const zIndex = computed(() => {
    const baseZIndex = 50
    const level = stackLevel.value >= 0 ? stackLevel.value : 0
    return baseZIndex + level * 10
})

const maxWidthClass = computed(() => {
    if (props.fullscreen) {
        return '' // No max-width restrictions for fullscreen
    }
    
    return {
        sm: 'sm:max-w-sm',
        md: 'sm:max-w-md',
        lg: 'sm:max-w-lg',
        xl: 'sm:max-w-xl',
        '2xl': 'sm:max-w-2xl',
        '3xl': 'sm:max-w-3xl',
        '4xl': 'sm:max-w-4xl',
        '5xl': 'sm:max-w-5xl',
        '6xl': 'sm:max-w-6xl',
        '7xl': 'sm:max-w-7xl',
    }[props.maxWidth]
})

const fullscreenStyles = computed(() => {
    if (!props.fullscreen) {
        return {}
    }
    
    return {
        width: `calc(100vw - ${props.fullscreenPadding} - ${props.fullscreenPadding})`,
        maxWidth: `calc(100vw - ${props.fullscreenPadding} - ${props.fullscreenPadding})`,
        height: `calc(100vh - ${props.fullscreenPadding} - ${props.fullscreenPadding})`,
        maxHeight: `calc(100vh - ${props.fullscreenPadding} - ${props.fullscreenPadding})`,
    }
})

const backdropOpacity = computed(() => {
    // Reduce backdrop opacity for stacked dialogs
    const level = stackLevel.value >= 0 ? stackLevel.value : 0
    return Math.max(0.75 - (level * 0.15), 0.3)
})

// Dialog management functions
const addToStack = () => {
    if (!dialogStack.value.includes(dialogId.value)) {
        dialogStack.value.push(dialogId.value)
    }
}

const removeFromStack = () => {
    const index = dialogStack.value.indexOf(dialogId.value)
    if (index > -1) {
        dialogStack.value.splice(index, 1)
    }
}

const close = () => {
    if (props.closeable) {
        emit('close')
    }
}

const closeOnEscape = (e) => {
    if (e.key === 'Escape') {
        e.preventDefault()
        // Only close if this is the topmost dialog
        const topDialog = dialogStack.value[dialogStack.value.length - 1]
        if (props.show && dialogId.value === topDialog) {
            close()
        }
    }
}

const closeOnBackdropClick = () => {
    // Only close if this is the topmost dialog
    const topDialog = dialogStack.value[dialogStack.value.length - 1]
    if (props.show && dialogId.value === topDialog) {
        close()
    }
}

const isTopDialog = computed(() => {
    const topDialog = dialogStack.value[dialogStack.value.length - 1]
    return dialogId.value === topDialog
})

// Watch for show prop changes
watch(
    () => props.show,
    async (newShow) => {
        if (newShow) {
            addToStack()
            
            // Set body overflow only for the first dialog
            if (dialogStack.value.length === 1) {
                document.body.style.overflow = 'hidden'
            }
            
            showSlot.value = true
            await nextTick()
            
            // Ensure dialog is closed before opening as modal
            if (dialog.value) {
                try {
                    if (!dialog.value.open) {
                        dialog.value.showModal()
                    } else {
                        // Dialog is already open, close it first then reopen as modal
                        dialog.value.close()
                        await nextTick()
                        dialog.value.showModal()
                    }
                } catch (error) {
                    console.warn('Dialog modal state error:', error)
                    // Fallback: ensure dialog is properly closed and try again
                    try {
                        dialog.value.close()
                        await nextTick()
                        dialog.value.showModal()
                    } catch (fallbackError) {
                        console.error('Failed to open dialog after fallback:', fallbackError)
                    }
                }
            }
        } else {
            removeFromStack()
            
            // Restore body overflow only when no dialogs are open
            if (dialogStack.value.length === 0) {
                document.body.style.overflow = ''
            }
            
            setTimeout(() => {
                if (dialog.value) {
                    try {
                        if (dialog.value.open) {
                            dialog.value.close()
                        }
                    } catch (error) {
                        console.warn('Error closing dialog:', error)
                    }
                }
                showSlot.value = false
            }, 200)
        }
    },
    { immediate: true }
)

onMounted(() => {
    document.addEventListener('keydown', closeOnEscape)
})

onUnmounted(() => {
    document.removeEventListener('keydown', closeOnEscape)
    removeFromStack()
    
    // Clean up body overflow if this was the last dialog
    if (dialogStack.value.length === 0) {
        document.body.style.overflow = ''
    }
})
</script>

<template>
    <dialog
        ref="dialog"
        :style="{ zIndex }"
        class="m-0 min-h-full min-w-full overflow-y-auto bg-transparent backdrop:bg-transparent"
    >
        <div
            :style="{ zIndex }"
            class="fixed inset-0 overflow-y-auto px-4 py-6 sm:px-0"
            scroll-region
        >
            <!-- Backdrop -->
            <Transition
                enter-active-class="ease-out duration-300"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="ease-in duration-200"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div
                    v-show="show"
                    class="fixed inset-0 transform transition-all"
                    @click="closeOnBackdropClick"
                >
                    <div
                        class="absolute inset-0 bg-gray-500"
                        :style="{ opacity: backdropOpacity }"
                    />
                </div>
            </Transition>

            <!-- Dialog content -->
            <Transition
                enter-active-class="ease-out duration-300"
                enter-from-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                enter-to-class="opacity-100 translate-y-0 sm:scale-100"
                leave-active-class="ease-in duration-200"
                leave-from-class="opacity-100 translate-y-0 sm:scale-100"
                leave-to-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            >
                <div
                    v-show="show"
                    class="mb-6 transform rounded-lg bg-white shadow-xl transition-all sm:mx-auto sm:w-full flex flex-col"
                    :class="[
                        allowDropdowns ? 'overflow-visible' : 'overflow-hidden',
                        maxWidthClass,
                        { 'mb-0': fullscreen }
                    ]"
                    :style="fullscreenStyles"
                    @click.stop
                >
                    <!-- Header -->
                    <div v-if="showHeader" class="flex items-center justify-between p-6 border-b border-gray-200">
                        <div class="flex items-center space-x-3">
                            <h3 v-if="title" class="text-lg font-medium text-gray-900">
                                {{ title }}
                            </h3>
                            <slot name="header" v-else />
                            
                            <!-- Stack level indicator for debugging -->
                            <span 
                                v-if="stackLevel > 0" 
                                class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800"
                            >
                                Level {{ stackLevel + 1 }}
                            </span>
                        </div>
                        
                        <button
                            v-if="closeable"
                            type="button"
                            @click="close"
                            class="text-gray-400 hover:text-gray-600 focus:outline-none focus:text-gray-600 transition-colors"
                        >
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <!-- Content -->
                    <div class="flex-1" :class="[
                        allowDropdowns ? 'overflow-visible' : 'overflow-auto',
                        { 'p-6': padContent }
                    ]">
                        <slot v-if="showSlot" />
                    </div>

                    <!-- Footer -->
                    <div v-if="showFooter && $slots.footer" class="border-t border-gray-200 p-6">
                        <slot name="footer" />
                    </div>
                </div>
            </Transition>
        </div>
    </dialog>
</template>