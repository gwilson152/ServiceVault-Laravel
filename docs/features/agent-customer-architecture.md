# Agent/Customer Architecture

**Service Vault's Enhanced User Type System**

Service Vault implements a sophisticated Agent/Customer architecture that clearly separates service providers from their customers, ensuring proper role-based access control and billing accuracy.

## Overview

Service Vault is designed as a B2B service management platform where **Service Provider Companies** deliver services to **Customer Organizations**. The platform supports two distinct user types with different capabilities and interfaces.

## User Types

### Agents (Service Providers)
- **Definition**: Internal users who provide services and log time entries
- **Database Value**: `user_type = 'agent'`
- **Primary Role**: Service delivery, time tracking, ticket management

**Capabilities:**
- ✅ Create and manage timers for time tracking
- ✅ Create, edit, and approve time entries
- ✅ Access timer controls in tickets interface
- ✅ Convert timers to billable time entries
- ✅ Manage service tickets and customer communications
- ✅ Access administrative and management features (based on permissions)

### Account Users (Customers)
- **Definition**: External customer users who submit tickets but do not log time
- **Database Value**: `user_type = 'account_user'` (default)
- **Primary Role**: Service consumption, ticket submission, service oversight

**Capabilities:**
- ✅ Submit and track service tickets
- ✅ View time entries logged for their account (transparency)
- ✅ Communicate with service providers through tickets
- ✅ Access customer portal features
- ❌ Cannot create or manage timers
- ❌ Cannot create or edit time entries
- ❌ Cannot access agent-specific management features

## Technical Implementation

### Database Schema

```sql
-- Users table with user_type enum
CREATE TABLE users (
    id UUID PRIMARY KEY,
    name VARCHAR NOT NULL,
    email VARCHAR UNIQUE NOT NULL,
    user_type user_type_enum DEFAULT 'account_user',
    account_id UUID REFERENCES accounts(id),
    -- other fields...
);

-- User type enum
CREATE TYPE user_type_enum AS ENUM ('agent', 'account_user');
```

### Model Methods

The `User` model includes convenient methods for type checking:

```php
// User type constants
public const USER_TYPE_AGENT = 'agent';
public const USER_TYPE_ACCOUNT_USER = 'account_user';

// Type checking methods
public function isAgent(): bool
public function isAccountUser(): bool
public function canCreateTimeEntries(): bool
```

### Permission Integration

The architecture integrates with Service Vault's three-dimensional permission system:

```php
// Agent validation in controllers
if (!$user->canCreateTimeEntries()) {
    return response()->json([
        'message' => 'Only Agents can create timers for time tracking purposes.',
        'error' => 'User type validation failed'
    ], 403);
}
```

## Time Entry Data Model

### Enhanced Relationships

Time entries now follow a strict Agent/Customer model:

```php
time_entries:
- user_id (required) → AGENT who performed the work (never customer)
- account_id (required) → Customer account being billed/serviced  
- ticket_id (optional) → Specific service ticket if work was ticket-based

tickets:
- agent_id (required) → Agent assigned to work the ticket
- customer_id (optional) → Account user who submitted the ticket
- account_id (required) → Customer account that owns the ticket
```

### Business Logic Rules

1. **Agent-Only Time Creation**: Only Agents can create/edit time entries
2. **Account Context Required**: All time entries must have customer account for billing
3. **Multi-Agent Support**: Multiple agents can log time on same ticket
4. **Ticket Consistency**: When ticket_id provided, account_id must match ticket.account_id
5. **Permission Validation**: Agent must have permission to log time for specified account

### Database Constraints

PostgreSQL triggers ensure data integrity:

```sql
CREATE TRIGGER time_entry_ticket_account_consistency_trigger
BEFORE INSERT OR UPDATE ON time_entries
FOR EACH ROW
EXECUTE FUNCTION check_time_entry_ticket_account_consistency();
```

## User Interface Integration

### Agent Interface
- **Timer Controls**: Full access to start, stop, pause, and manage timers
- **Time Entry Management**: Create manual time entries, approve/reject entries
- **Ticket Management**: Full ticket lifecycle management with timer integration
- **Billing Context**: Automatic account resolution for proper billing

### Customer Interface
- **Read-Only Time Visibility**: Can view time entries logged for their account
- **Ticket Interaction**: Submit tickets, communicate with agents, track progress
- **Service Transparency**: See work performed by agents for accountability
- **Clean UI**: No timer controls or management features that would cause confusion

### Permission-Based Rendering

Frontend components check user type for appropriate UI rendering:

```vue
<!-- Timer Controls (Agents Only) -->
<TicketTimerControls v-if="user?.user_type === 'agent'" />

<!-- Manual Time Entry Button (Agents Only) -->
<button v-if="user?.user_type === 'agent'">Add Manual Time Entry</button>
```

## API Integration

### Authentication & Authorization

All timer and time entry endpoints validate Agent permissions:

```php
// Timer creation
public function store(Request $request): JsonResponse
{
    if (!$request->user()->canCreateTimeEntries()) {
        return response()->json(['error' => 'Agent access required'], 403);
    }
    // ... timer creation logic
}
```

### Token Scopes

Laravel Sanctum tokens include user-type aware abilities:

```php
// Agent-specific abilities
'timers:read', 'timers:write', 'timers:sync'
'time-entries:create', 'time-entries:edit', 'time-entries:approve'

// Customer-specific abilities  
'tickets:create', 'tickets:read', 'tickets:comment'
'time-entries:view' // Read-only for transparency
```

## Enhanced Timer Assignment System

The Agent/Customer architecture works seamlessly with the enhanced timer assignment system:

### Assignment Options

1. **General Timer**: No assignment (for general work tracking)
2. **Ticket Timer**: Assigned to specific ticket (billing via ticket's account)
3. **Account Timer**: Assigned directly to customer account (overhead/administrative)

### Commitment Requirements

- Timers can be created without assignment (Agents only)
- Timer-to-time-entry conversion requires ticket OR account assignment
- Account context is always resolved for billing purposes

### Billing Integration

```php
// Automatic account resolution
public function getBillingAccountId(): ?string
{
    if ($this->ticket_id && $this->ticket) {
        return $this->ticket->account_id; // From ticket
    }
    return $this->account_id; // Direct assignment
}
```

## Benefits & Outcomes

### Business Alignment
- ✅ Perfect alignment with B2B service delivery model
- ✅ Clear separation of service provider vs customer roles
- ✅ Accurate billing with proper account context
- ✅ Service transparency without operational complexity

### Technical Benefits
- ✅ Database-level data integrity with triggers
- ✅ Role-based UI rendering for clean interfaces
- ✅ Multi-layer permission validation
- ✅ Flexible timer assignment with billing accuracy

### User Experience
- ✅ **Agents**: Full service delivery capabilities with streamlined workflows
- ✅ **Customers**: Clean, focused interface for service consumption
- ✅ **Transparency**: Customers can see work performed without complexity
- ✅ **Security**: Proper role separation prevents unauthorized access

## Migration & Compatibility

### Existing Data
- All existing users default to `user_type = 'account_user'`
- Agents must be explicitly designated during user management
- Existing timers and time entries continue to function normally

### Backward Compatibility
- All existing API endpoints continue to work
- Enhanced validation adds security without breaking functionality
- UI components gracefully handle both user types

## Next Steps

1. **User Setup**: Configure user types through application setup process
2. **Role Assignment**: Designate service provider employees as Agents
3. **Customer Onboarding**: Customer organization users remain as Account Users
4. **Permission Configuration**: Assign appropriate role templates based on user types

---

*For technical implementation details, see [Timer System Architecture](../architecture/timer-system.md) and [Database Schema](../architecture/database-schema.md).*