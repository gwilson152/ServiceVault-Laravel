import { onMounted, onUnmounted, ref } from 'vue'

export function useTicketCommentBroadcasting(ticketId, onCommentReceived, userPermissions = {}) {
    let channel = null
    const connectionStatus = ref('disconnected')
    
    // Helper function to check if user can see internal comments
    const canViewInternalComments = () => {
        return userPermissions.canViewInternalComments || 
               userPermissions.isAdmin || 
               userPermissions.canManageTickets
    }
    
    // Helper function to filter comments based on user permissions
    const shouldShowComment = (comment) => {
        // Always show non-internal comments
        if (!comment.is_internal) {
            return true
        }
        // Only show internal comments if user has permission
        return canViewInternalComments()
    }
    
    const connect = () => {
        if (!ticketId || !window.Echo) {
            console.log('Real-time: WebSocket not available')
            connectionStatus.value = 'unavailable'
            return
        }

        try {
            connectionStatus.value = 'connecting'
            
            // Connect to private ticket channel
            channel = window.Echo.private(`ticket.${ticketId}`)
            
            // Listen for new comments
            channel.listen('.comment.created', (event) => {
                console.log('Real-time: New comment received via WebSocket', event)
                
                const comment = event.comment
                
                // Check permissions before showing
                if (!shouldShowComment(comment)) {
                    console.log('Real-time: Filtering out internal comment (no permission)', comment)
                    return
                }
                
                console.log('Real-time: Calling onCommentReceived with comment:', comment)
                if (onCommentReceived && typeof onCommentReceived === 'function') {
                    onCommentReceived(comment)
                } else {
                    console.warn('Real-time: onCommentReceived is not a function:', onCommentReceived)
                }
            })
            
            // Handle successful connection
            channel.subscribed(() => {
                console.log('Real-time: Connected successfully')
                connectionStatus.value = 'connected'
            })
            
            // Handle connection errors
            channel.error((error) => {
                console.log('Real-time: Connection failed')
                connectionStatus.value = 'failed'
            })
            
        } catch (error) {
            console.log('Real-time: Setup failed')
            connectionStatus.value = 'failed'
        }
    }
    
    const disconnect = () => {
        if (channel) {
            window.Echo?.leave(`ticket.${ticketId}`)
            channel = null
        }
        connectionStatus.value = 'disconnected'
    }
    
    // Lifecycle management
    onMounted(() => {
        connect()
    })
    
    onUnmounted(() => {
        disconnect()
    })
    
    return {
        connectionStatus,
        connect,
        disconnect
    }
}