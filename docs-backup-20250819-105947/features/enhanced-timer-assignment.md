# Enhanced Timer Assignment System

**Flexible Timer Assignment with Billing Context Validation**

Service Vault's Enhanced Timer Assignment System allows Agents to create timers with flexible assignment options while ensuring proper billing context for accurate time tracking and invoicing.

## Overview

The Enhanced Timer Assignment System provides three distinct assignment options that align with different types of work performed in a B2B service environment:

1. **General Timers**: No assignment for exploratory or pre-commitment work
2. **Ticket Timers**: Assigned to specific service tickets with automatic account context
3. **Account Timers**: Direct assignment to customer accounts for overhead/administrative work

## Assignment Types

### 1. General Timer
- **Purpose**: General work tracking without immediate billing context
- **Assignment**: No ticket or account assignment
- **Billing**: Cannot be converted to billable time entry without assignment
- **Use Cases**: 
  - Research and development work
  - Internal meetings or training
  - Exploratory work before ticket assignment

**Creation:**
```javascript
// Create general timer
const timer = await api.post('/api/timers', {
    description: 'Research customer requirements',
    // No ticket_id or account_id
});
```

### 2. Ticket Timer
- **Purpose**: Work performed for specific service tickets
- **Assignment**: Assigned to `ticket_id`
- **Billing**: Automatic account resolution via `ticket.account_id`
- **Use Cases**:
  - Ticket-specific service delivery
  - Customer support work
  - Maintenance and bug fixes

**Creation:**
```javascript
// Create ticket timer
const timer = await api.post('/api/timers', {
    description: 'Fix database connection issue',
    ticket_id: 'ticket-uuid-here'
    // account_id automatically resolved from ticket
});
```

### 3. Account Timer
- **Purpose**: Account-level work not tied to specific tickets
- **Assignment**: Direct assignment to `account_id`
- **Billing**: Direct billing to specified account
- **Use Cases**:
  - Account setup and onboarding
  - General consulting and advisory
  - Administrative overhead for specific accounts

**Creation:**
```javascript
// Create account timer
const timer = await api.post('/api/timers', {
    description: 'Monthly account review and planning',
    account_id: 'account-uuid-here'
    // No ticket_id - direct account billing
});
```

## User Interface

### Timer Creation Modal

The enhanced StartTimerModal provides intuitive assignment selection:

```vue
<template>
  <!-- Timer Assignment Type Selection -->
  <div class="grid grid-cols-3 gap-2">
    <button @click="timerType = 'general'">
      <div class="font-medium text-sm">General</div>
      <div class="text-xs text-gray-500">No assignment</div>
    </button>
    <button @click="timerType = 'ticket'">
      <div class="font-medium text-sm">Ticket</div>
      <div class="text-xs text-gray-500">Specific ticket work</div>
    </button>
    <button @click="timerType = 'account'">
      <div class="font-medium text-sm">Account</div>
      <div class="text-xs text-gray-500">Account-level work</div>
    </button>
  </div>
  
  <!-- Assignment Selection -->
  <select v-if="timerType === 'ticket'" v-model="form.ticket_id">
    <option v-for="ticket in availableTickets" :value="ticket.id">
      #{{ ticket.ticket_number }} - {{ ticket.title }}
    </option>
  </select>
  
  <select v-if="timerType === 'account'" v-model="form.account_id">
    <option v-for="account in availableAccounts" :value="account.id">
      {{ account.name }}
    </option>
  </select>
</template>
```

### Commitment Validation

Visual indicators show commitment requirements:

```vue
<p class="text-xs text-gray-500">
  <strong>Note:</strong> Assignment to a ticket or account is required to commit time entries for billing.
</p>
```

## Billing Context Resolution

### Automatic Account Resolution

The system automatically resolves billing accounts using sophisticated logic:

```php
// Timer model method
public function getBillingAccountId(): ?string
{
    // Priority 1: Ticket assignment (ticket's account)
    if ($this->ticket_id && $this->ticket) {
        return $this->ticket->account_id;
    }
    
    // Priority 2: Direct account assignment
    return $this->account_id;
}
```

### Commitment Validation

Timers can only be converted to billable time entries with proper assignment:

```php
// Timer model validation
public function canConvertToTimeEntry(): bool
{
    return $this->ticket_id || $this->account_id;
}

// Time entry conversion
public function convertToTimeEntry(array $additionalData = []): ?TimeEntry
{
    if (!$this->canConvertToTimeEntry()) {
        throw new \Exception('Timer must be assigned to either a ticket or account before converting to time entry');
    }
    
    $billingAccountId = $this->getBillingAccountId();
    
    return TimeEntry::create([
        'user_id' => $this->user_id,
        'account_id' => $billingAccountId, // Always required
        'ticket_id' => $this->ticket_id,   // Optional
        // ... other fields
    ]);
}
```

## Database Design

### Timer Table Schema

```sql
CREATE TABLE timers (
    id UUID PRIMARY KEY,
    user_id UUID NOT NULL REFERENCES users(id),
    account_id UUID REFERENCES accounts(id),      -- Direct account assignment
    ticket_id UUID REFERENCES tickets(id),        -- Ticket assignment
    billing_rate_id UUID REFERENCES billing_rates(id),
    time_entry_id UUID REFERENCES time_entries(id),
    description TEXT,
    status VARCHAR(20) DEFAULT 'running',
    started_at TIMESTAMP NOT NULL,
    stopped_at TIMESTAMP NULL,
    -- ... other fields
);
```

### Data Integrity Constraints

PostgreSQL triggers ensure data consistency:

```sql
-- Ensure time entry ticket/account consistency
CREATE TRIGGER time_entry_ticket_account_consistency_trigger
BEFORE INSERT OR UPDATE ON time_entries
FOR EACH ROW
EXECUTE FUNCTION check_time_entry_ticket_account_consistency();
```

## Agent/Customer Integration

### Permission Validation

Only Agents can create timers:

```php
// API endpoint validation
public function store(Request $request): JsonResponse
{
    // Validate Agent permissions
    if (!$request->user()->canCreateTimeEntries()) {
        return response()->json([
            'message' => 'Only Agents can create timers for time tracking purposes.',
            'error' => 'User type validation failed'
        ], 403);
    }
    
    // ... timer creation logic
}
```

### UI Permission Controls

Timer controls only visible to Agents:

```vue
<!-- Timer Controls (Agents Only) -->
<TicketTimerControls v-if="user?.user_type === 'agent'" />

<!-- Account Users see read-only time information -->
<div v-else>
  <p class="text-sm text-gray-600">Time logged by agents:</p>
  <TimeEntryReadOnlyList :entries="timeEntries" />
</div>
```

## API Endpoints

### Timer Creation

**POST** `/api/timers`

```json
{
  "description": "Working on customer requirements",
  "ticket_id": "optional-ticket-uuid",
  "account_id": "optional-account-uuid", 
  "billing_rate_id": "optional-rate-uuid"
}
```

### Ticket-Specific Timer

**POST** `/api/tickets/{ticketId}/timers/start`

```json
{
  "description": "Fix database connection issue",
  "billing_rate_id": "optional-rate-uuid"
}
```

### Timer Commitment

**POST** `/api/timers/{timerId}/commit`

```json
{
  "description": "Updated description",
  "round_to": 15,
  "notes": "Additional notes for time entry"
}
```

## Business Rules

### Assignment Rules

1. **General Timers**: Can run indefinitely without assignment
2. **Assignment Required**: Timer-to-time-entry conversion requires ticket OR account assignment
3. **Billing Context**: Account context always resolved for billing accuracy
4. **Agent Only**: Only users with `user_type = 'agent'` can create timers
5. **Data Integrity**: Database triggers ensure ticket/account consistency

### Workflow Integration

1. **Timer Creation**: Agent selects assignment type and context
2. **Time Tracking**: Timer runs with assignment context
3. **Commitment Decision**: Agent decides to commit or discard timer
4. **Billing Integration**: Time entry created with proper account context
5. **Invoice Generation**: Time entries grouped by account for billing

## Benefits

### Operational Flexibility
- ✅ **General Work**: Track exploration and research time before commitment
- ✅ **Ticket Work**: Direct assignment to service tickets with automatic billing
- ✅ **Account Work**: Administrative and overhead time tracking
- ✅ **Billing Accuracy**: Automatic account resolution prevents billing errors

### Business Alignment
- ✅ **Service Model**: Perfect alignment with B2B service delivery workflows
- ✅ **Billing Integrity**: Ensures all billable time has proper account context
- ✅ **Agent Efficiency**: Streamlined assignment process with clear options
- ✅ **Customer Transparency**: Customers see time logged for their accounts/tickets

### Technical Benefits
- ✅ **Data Integrity**: Database-level validation prevents inconsistent data
- ✅ **Performance**: Optimized queries with proper indexing
- ✅ **Scalability**: Efficient assignment resolution algorithms
- ✅ **Security**: Multi-layer permission validation

## Migration & Compatibility

### Existing Timers
- All existing timers continue to function normally
- Legacy timers without assignment can still be converted with manual account selection
- No data migration required

### API Compatibility
- All existing endpoints remain functional
- New assignment parameters are optional for backward compatibility
- Enhanced validation adds security without breaking changes

---

*For technical implementation details, see [Timer System Architecture](../architecture/timer-system.md) and [Agent/Customer Architecture](agent-customer-architecture.md).*