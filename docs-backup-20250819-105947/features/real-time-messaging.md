# Real-Time Ticket Messaging

Service Vault implements real-time ticket messaging using Laravel Reverb WebSocket server for instant communication and collaboration.

## Overview

The real-time messaging system provides instant updates for ticket comments across all connected users, enabling seamless collaboration without page refreshes.

### Key Features

- **Instant Message Delivery**: Comments appear immediately for all users viewing the ticket
- **Permission-Based Filtering**: Internal comments only visible to authorized users
- **Session-Based Authentication**: Secure WebSocket connections using Laravel sessions
- **Conversation-Style UI**: Modern chat interface with message bubbles and user avatars
- **Graceful Fallback**: System works with manual refresh when WebSocket unavailable

## Architecture

### Components

1. **Laravel Reverb Server**: WebSocket server for real-time connections (port 8080)
2. **Broadcasting Events**: `TicketCommentCreated` event broadcasts new comments
3. **Private Channels**: `ticket.{id}` channels for secure ticket-specific messaging  
4. **Frontend Composables**: `useTicketCommentBroadcasting` for WebSocket management
5. **UI Components**: `TicketCommentsSection` with real-time message updates

### Technology Stack

- **Backend**: Laravel Reverb (WebSocket server)
- **Frontend**: Laravel Echo + Vue.js composables
- **Authentication**: Session-based channel authorization
- **Broadcasting**: Synchronous event broadcasting with `ShouldBroadcastNow`

## Setup and Configuration

### Environment Variables

```bash
# Broadcasting Configuration
BROADCAST_CONNECTION=reverb

# Laravel Reverb Configuration  
REVERB_APP_ID=887419
REVERB_APP_KEY=vijdpr7didkhd29h1upq
REVERB_APP_SECRET=rmfcu2xz4j1uwaom3ofa
REVERB_HOST="localhost"
REVERB_PORT=8080
REVERB_SCHEME=http

# Frontend Configuration
VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"
```

### Server Startup

```bash
# Start Laravel web server
php artisan serve

# Start WebSocket server (separate process)
php artisan reverb:start

# Start frontend development server
npm run dev
```

## Implementation Details

### Broadcasting Event

**File**: `app/Events/TicketCommentCreated.php`

```php
class TicketCommentCreated implements ShouldBroadcastNow
{
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('ticket.' . $this->comment->ticket_id),
        ];
    }
    
    public function broadcastAs(): string
    {
        return 'comment.created';
    }
}
```

### Channel Authorization

**File**: `routes/channels.php`

```php
// Ticket-specific channels for real-time messaging
Broadcast::channel('ticket.{ticketId}', function (User $user, string $ticketId) {
    $ticket = \App\Models\Ticket::find($ticketId);
    return $ticket && $ticket->canBeViewedBy($user);
});
```

### Frontend WebSocket Configuration

**File**: `resources/js/echo.js`

```javascript
window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: currentHost,
    wsPort: wsPort,
    wssPort: wsPort,
    forceTLS: isSecure,
    encrypted: isSecure,
    enabledTransports: ['ws', 'wss'],
    auth: {
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
        },
    },
})
```

### Real-Time Composable

**File**: `resources/js/Composables/useTicketCommentBroadcasting.js`

```javascript
export function useTicketCommentBroadcasting(ticketId, onCommentReceived, userPermissions = {}) {
    const connect = () => {
        channel = window.Echo.private(`ticket.${ticketId}`)
        
        channel.listen('.comment.created', (event) => {
            const comment = event.comment
            
            // Permission-based filtering
            if (shouldShowComment(comment)) {
                onCommentReceived(comment)
            }
        })
    }
}
```

## Permission System

### Comment Visibility Rules

1. **Public Comments**: Visible to all users with ticket access
2. **Internal Comments**: Only visible to users with:
   - `admin.read` permission
   - `tickets.manage` permission  
   - `tickets.view.internal` permission

### Channel Access Control

Users can join ticket channels only if:
- They have permission to view the ticket (`canBeViewedBy()` method)
- They are authenticated via Laravel session
- The ticket exists in the database

## User Interface

### Message Display

- **Conversation Style**: Messages displayed as chat bubbles
- **User Identification**: Left-aligned for others, right-aligned for current user  
- **Avatars**: User initials displayed in colored circles
- **Timestamps**: Relative time display (e.g., "2 minutes ago")
- **Internal Badges**: Yellow badges for internal comments

### Message Composition

- **Rich Text Area**: Multi-line text input with formatting
- **Internal Toggle**: Checkbox for internal comments (permission-dependent)
- **Send Button**: Disabled until content is entered
- **Loading States**: Visual feedback during submission

### Empty States

- **No Messages**: Friendly empty state with call-to-action
- **Permission Denied**: Clear messaging for read-only access

## Debugging and Monitoring

### Console Logging

The system provides detailed console logs for debugging:

```javascript
// Connection status
"Laravel Reverb WebSocket initialized for localhost:8080"
"Real-time: Connected successfully"

// Message reception  
"Real-time: New comment received via WebSocket"
"Real-time: Calling onCommentReceived with comment:"
```

### Common Issues

1. **WebSocket Connection Fails**: 
   - Ensure Reverb server is running on correct port
   - Check firewall settings for port 8080

2. **Authentication Errors**:
   - Verify broadcasting routes are registered
   - Check CSRF token is present in meta tags

3. **Messages Not Appearing**:
   - Check user permissions for internal comments
   - Verify channel authorization logic

## Performance Considerations

### Synchronous Broadcasting

Events use `ShouldBroadcastNow` for immediate delivery without queue delays.

### Connection Management  

- WebSocket connections auto-reconnect on network issues
- Channels are properly cleaned up on component unmount
- Multiple ticket tabs reuse existing connections

### Scalability

- Each ticket has its own private channel
- Users only subscribe to channels for tickets they're viewing
- Channel authorization prevents unauthorized access

## Development Guidelines

### Adding New Real-Time Features

1. Create broadcasting event implementing `ShouldBroadcastNow`
2. Define channel authorization in `routes/channels.php`
3. Add frontend listener in appropriate composable
4. Update UI components to handle real-time data
5. Test with multiple users/browser tabs

### Testing Real-Time Features

1. Open ticket in multiple browser tabs/users
2. Add comments from different tabs
3. Verify instant appearance across all tabs
4. Test permission filtering for internal comments
5. Check console logs for debugging information

## Security

- **Private Channels**: All messaging uses private channels with authorization
- **Session Authentication**: WebSocket auth uses Laravel sessions, not tokens
- **Permission Filtering**: Server-side and client-side permission checks
- **CSRF Protection**: WebSocket requests include CSRF tokens

## Future Enhancements

- **Typing Indicators**: Show when users are typing
- **Message Threading**: Reply to specific comments
- **File Attachments**: Real-time file sharing notifications
- **Presence Channels**: Show who's currently viewing the ticket
- **Message Reactions**: Add emoji reactions to comments