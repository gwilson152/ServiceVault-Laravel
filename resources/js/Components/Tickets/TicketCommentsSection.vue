<template>
    <div class="space-y-6">
        <!-- Message Composer - Now at the top -->
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm" v-if="canAddComments">
            <form @submit.prevent="sendMessage" class="p-6">
                <div class="space-y-4">
                    <!-- Text Input -->
                    <div>
                        <textarea
                            v-model="newMessage"
                            placeholder="Write your message..."
                            rows="4"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 resize-none text-sm placeholder-gray-500 transition-colors"
                            :disabled="isSubmitting"
                        ></textarea>
                    </div>
                    
                    <!-- Bottom Row -->
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-4">
                            <!-- Internal Comment Toggle -->
                            <label v-if="canAddInternalComments" class="flex items-center space-x-2 cursor-pointer">
                                <input
                                    v-model="isInternal"
                                    type="checkbox"
                                    class="rounded border-gray-300 text-yellow-600 focus:ring-yellow-500 transition-colors"
                                />
                                <span class="text-sm text-gray-600 select-none">Internal note</span>
                                <div class="group relative">
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <div class="absolute bottom-full mb-2 left-1/2 transform -translate-x-1/2 hidden group-hover:block">
                                        <div class="bg-gray-900 text-white text-xs rounded py-1 px-2 whitespace-nowrap">
                                            Only visible to team members
                                        </div>
                                    </div>
                                </div>
                            </label>
                            
                            <!-- File Upload (placeholder for future) -->
                            <button
                                type="button"
                                class="flex items-center space-x-2 text-gray-400 hover:text-gray-600 transition-colors cursor-not-allowed"
                                title="File attachments (coming soon)"
                                disabled
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                                </svg>
                                <span class="text-sm">Attach</span>
                            </button>
                        </div>
                        
                        <!-- Send Button -->
                        <button
                            type="submit"
                            :disabled="!newMessage.trim() || isSubmitting"
                            class="inline-flex items-center space-x-2 px-6 py-2.5 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200 shadow-sm hover:shadow-md"
                        >
                            <span v-if="isSubmitting" class="flex items-center space-x-2">
                                <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span>Sending...</span>
                            </span>
                            <span v-else class="flex items-center space-x-2">
                                <span>Send</span>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                                </svg>
                            </span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
        
        <!-- Permission notice -->
        <div v-else-if="!canAddComments" class="bg-gray-50 rounded-xl border border-gray-200 p-6 text-center">
            <div class="flex flex-col items-center space-y-2">
                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
                <p class="text-gray-600 font-medium">Read-only access</p>
                <p class="text-gray-500 text-sm">You don't have permission to add comments to this ticket.</p>
            </div>
        </div>

        <!-- Comments List -->
        <div class="space-y-4">
            <!-- Empty State -->
            <div v-if="!normalizedMessages || normalizedMessages.length === 0" class="text-center py-12">
                <div class="flex flex-col items-center space-y-4">
                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center">
                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-3.582 8-8 8a8.959 8.959 0 01-4.906-1.524A11.956 11.956 0 012 21c0-1.68.322-3.283.913-4.755A12.026 12.026 0 012 12C2 7.582 5.582 4 10 4s8 3.582 8 8z"/>
                        </svg>
                    </div>
                    <div class="space-y-1">
                        <h3 class="text-lg font-medium text-gray-900">No messages yet</h3>
                        <p class="text-gray-500 text-sm">
                            Start the conversation by sending a message above.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Message List -->
            <div
                v-for="message in normalizedMessages"
                :key="message.id"
                :class="[
                    'mb-6 flex',
                    message.user?.id === currentUser?.id ? 'justify-end' : 'justify-start'
                ]"
            >
                <!-- Message bubble with conversation styling -->
                <div :class="[
                    'max-w-md lg:max-w-lg xl:max-w-2xl',
                    message.user?.id === currentUser?.id ? 'order-1' : 'order-2'
                ]">
                    <!-- Message bubble -->
                    <div :class="[
                        'px-5 py-4 rounded-2xl shadow-sm',
                        message.is_internal 
                            ? (message.user?.id === currentUser?.id 
                                ? 'bg-yellow-500 text-white'
                                : 'bg-yellow-100 border border-yellow-300')
                            : (message.user?.id === currentUser?.id 
                                ? 'bg-blue-500 text-white' 
                                : 'bg-gray-100'),
                        message.user?.id === currentUser?.id
                            ? 'rounded-br-md'
                            : 'rounded-bl-md'
                    ]">
                        <!-- Message content -->
                        <div 
                            :class="[
                                'text-base leading-relaxed whitespace-pre-wrap',
                                message.user?.id === currentUser?.id ? 'text-white' : 'text-gray-900'
                            ]"
                            v-html="formatMessage(message.content)"
                        ></div>
                        
                        <!-- Attachment indicator -->
                        <div
                            v-if="message.attachments?.length"
                            :class="[
                                'mt-2 flex items-center space-x-2 text-xs',
                                message.user?.id === currentUser?.id ? 'text-blue-100' : 'text-gray-500'
                            ]"
                        >
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                            </svg>
                            <span>
                                {{ message.attachments.length }} 
                                file{{ message.attachments.length > 1 ? 's' : '' }}
                            </span>
                        </div>
                    </div>
                    
                    <!-- Message metadata -->
                    <div :class="[
                        'mt-1 flex items-center space-x-2 text-xs',
                        message.user?.id === currentUser?.id ? 'justify-end' : 'justify-start'
                    ]">
                        <span v-if="message.user?.id !== currentUser?.id" class="font-medium text-gray-600">
                            {{ message.user?.name || 'Unknown User' }}
                        </span>
                        <span v-if="message.is_internal" class="px-2 py-1 bg-yellow-200 text-yellow-800 rounded-full font-medium">
                            Internal
                        </span>
                        <span class="text-gray-500">
                            {{ formatDateTime(message.created_at) }}
                        </span>
                        <span v-if="message.was_edited" class="text-gray-400">
                            (edited)
                        </span>
                    </div>
                </div>

                <!-- Avatar -->
                <div :class="[
                    'flex-shrink-0 mx-4',
                    message.user?.id === currentUser?.id ? 'order-2' : 'order-1'
                ]">
                    <div :class="[
                        'w-10 h-10 rounded-full flex items-center justify-center text-sm font-medium text-white',
                        message.user?.id === currentUser?.id 
                            ? 'bg-blue-500' 
                            : 'bg-gray-500'
                    ]">
                        {{ (message.user?.name?.charAt(0)?.toUpperCase()) || '?' }}
                    </div>
                </div>
            </div>
        </div>

        
        <!-- Simple refresh hint -->
        <div v-if="normalizedMessages && normalizedMessages.length > 0" class="text-center py-2">
            <p class="text-xs text-gray-500">
                Refresh the page to see new messages from other users
            </p>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useTicketCommentBroadcasting } from '@/Composables/useTicketCommentBroadcasting'
import { usePage } from '@inertiajs/vue3'

// Props
const props = defineProps({
    ticketId: {
        type: String,
        required: true
    },
    messages: {
        type: [Array, Object],
        default: () => []
    },
    permissions: {
        type: Object,
        default: () => ({})
    },
    onMessageSent: {
        type: Function,
        default: () => {}
    },
    onNewMessage: {
        type: Function,
        default: () => {}
    }
})

// Page and user data
const page = usePage()
const currentUser = computed(() => page.props.auth?.user)

// Form state
const newMessage = ref('')
const isInternal = ref(false)
const isSubmitting = ref(false)

// Normalize messages to always be an array
const normalizedMessages = computed(() => {
    if (!props.messages) return []
    if (Array.isArray(props.messages)) return props.messages
    // If it's an object, convert it to an array of values
    return Object.values(props.messages)
})

// Permissions
const canAddComments = computed(() => props.permissions?.canAddComments || false)
const canAddInternalComments = computed(() => props.permissions?.canViewInternalComments || false)

// Real-time setup
const userPermissions = {
    canViewInternalComments: props.permissions?.canViewInternalComments || false,
    isAdmin: currentUser.value?.isSuperAdmin || false,
    canManageTickets: props.permissions?.canManageTickets || false
}

const { connectionMethod: connectionStatus } = useTicketCommentBroadcasting(
    props.ticketId, 
    props.onNewMessage,
    userPermissions
)

// Message formatting
const formatMessage = (content) => {
    if (!content) return ''
    return content.replace(/\n/g, '<br>')
}

const formatDateTime = (dateString) => {
    if (!dateString) return ''
    const date = new Date(dateString)
    const now = new Date()
    const diffInHours = (now - date) / (1000 * 60 * 60)
    
    if (diffInHours < 24) {
        return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
    } else if (diffInHours < 168) { // 7 days
        return date.toLocaleDateString([], { weekday: 'short', hour: '2-digit', minute: '2-digit' })
    } else {
        return date.toLocaleDateString([], { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' })
    }
}

// Send message
const sendMessage = async () => {
    if (!newMessage.value.trim() || isSubmitting.value) return
    
    isSubmitting.value = true
    
    try {
        await props.onMessageSent({
            content: newMessage.value.trim(),
            is_internal: isInternal.value
        })
        
        newMessage.value = ''
        isInternal.value = false
    } catch (error) {
        console.error('Failed to send message:', error)
    } finally {
        isSubmitting.value = false
    }
}
</script>