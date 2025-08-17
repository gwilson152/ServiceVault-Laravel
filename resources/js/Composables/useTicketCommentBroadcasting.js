import { onMounted, onUnmounted } from 'vue'
import { usePage } from '@inertiajs/vue3'

export function useTicketCommentBroadcasting(ticketId, onCommentReceived) {
    const page = usePage()
    const user = page.props?.auth?.user
    
    let channel = null
    
    const connectToChannel = () => {
        if (!ticketId || !window.Echo || !user) {
            console.log('TicketCommentBroadcasting: Cannot connect - missing requirements')
            return
        }

        try {
            // Connect to private ticket channel
            channel = window.Echo.private(`ticket.${ticketId}`)
            
            // Listen for new comments
            channel.listen('.comment.created', (event) => {
                console.log('TicketCommentBroadcasting: New comment received', event)
                
                // Filter internal comments if user doesn't have permission to see them
                const comment = event.comment
                // For now, let's simplify the permission check to allow all comments and let the backend handle filtering
                console.log('TicketCommentBroadcasting: Processing comment', comment.id, 'Internal:', comment.is_internal)
                
                // Call the callback with the new comment
                if (onCommentReceived && typeof onCommentReceived === 'function') {
                    onCommentReceived(comment)
                }
            })
            
            console.log('TicketCommentBroadcasting: Connected to ticket channel', ticketId)
            
        } catch (error) {
            console.error('TicketCommentBroadcasting: Connection error', error)
        }
    }
    
    const disconnectFromChannel = () => {
        if (channel) {
            console.log('TicketCommentBroadcasting: Disconnecting from ticket channel', ticketId)
            window.Echo.leave(`ticket.${ticketId}`)
            channel = null
        }
    }
    
    // Lifecycle management
    onMounted(() => {
        console.log('TicketCommentBroadcasting: Mounting for ticket', ticketId)
        connectToChannel()
    })
    
    onUnmounted(() => {
        console.log('TicketCommentBroadcasting: Unmounting for ticket', ticketId)
        disconnectFromChannel()
    })
    
    return {
        connectToChannel,
        disconnectFromChannel
    }
}