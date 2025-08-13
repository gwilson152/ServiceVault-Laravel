# Service Tickets

Comprehensive service ticket management system with enhanced detail pages, workflow engine, timer integration, and real-time collaboration features.

## Overview

### Service Ticket System Features
- **Enhanced Detail Page**: Comprehensive central hub for all ticket interactions with tabbed interface
- **Real-Time Messaging**: Internal and external communication with live updates
- **Workflow Engine**: Comprehensive ticket lifecycle management
- **Timer Integration**: Inline timer controls per ticket with real-time tracking
- **Account-Aware**: Multi-organization service delivery with account context
- **Permission-Based**: Granular access control for service providers and customers
- **Billing Integration**: Complete billing overview showing time entry invoice status
- **Addon Management**: Additional items and services per ticket with approval workflows
- **Activity Timeline**: Full audit trail with comprehensive event tracking

### Ticket Workflow States
- **draft**: Initial ticket creation state
- **open**: Ticket submitted and awaiting assignment
- **assigned**: Ticket assigned to team member
- **in_progress**: Active work in progress
- **waiting_customer**: Waiting for customer response or approval
- **waiting_vendor**: Waiting for external vendor or third party
- **resolved**: Ticket completed, awaiting customer confirmation
- **closed**: Ticket confirmed complete and closed
- **cancelled**: Ticket cancelled before completion

## Ticket Management

### Creating Service Tickets

#### Basic Ticket Creation
```php
$ticket = ServiceTicket::create([
    'title' => 'User Authentication Issue',
    'description' => 'Users unable to login with correct credentials',
    'status' => 'open',
    'priority' => 'high',
    'account_id' => $account->id,
    'created_by' => $user->id,
    'category' => 'technical_support',
    'estimated_hours' => 4,
]);
```

#### Ticket Categories
- `technical_support` - Technical issues and troubleshooting
- `feature_request` - New feature development requests
- `bug_report` - Software bug reports and fixes
- `maintenance` - System maintenance and updates
- `consultation` - Advisory and consultation services
- `training` - User training and documentation
- `custom` - Custom project work

#### Priority Levels
- `low` - Non-urgent, can wait for regular scheduling
- `normal` - Standard priority for regular queue
- `high` - Important issues requiring prompt attention
- `urgent` - Critical issues requiring immediate attention
- `critical` - System-down or security issues requiring emergency response

### Ticket Assignment

#### Manual Assignment
```php
$ticket->assignTo($user, $assignedBy);
$ticket->update([
    'assigned_to' => $user->id,
    'assigned_at' => now(),
    'assigned_by' => $assignedBy->id,
    'status' => 'assigned'
]);
```

#### Auto-Assignment Rules
Service tickets can be auto-assigned based on:
- **Account Mapping**: Specific team members assigned to customer accounts
- **Category Expertise**: Team members with specific skill categories
- **Workload Balancing**: Automatic distribution based on current workload
- **Escalation Rules**: Automatic escalation after time thresholds

### Ticket Workflow Transitions

#### Status Transitions
```php
// Transition ticket to in_progress
$ticket->transitionTo('in_progress', $user, [
    'notes' => 'Starting work on authentication issue',
    'estimated_completion' => now()->addHours(2),
]);

// Transition to waiting_customer
$ticket->transitionTo('waiting_customer', $user, [
    'notes' => 'Need customer to provide additional access credentials',
    'customer_action_required' => 'Provide admin login credentials',
]);

// Resolve ticket
$ticket->transitionTo('resolved', $user, [
    'notes' => 'Authentication issue resolved. New login system deployed.',
    'resolution_summary' => 'Updated authentication service and reset user passwords',
]);
```

#### Valid Transitions
```php
private const VALID_TRANSITIONS = [
    'draft' => ['open', 'cancelled'],
    'open' => ['assigned', 'in_progress', 'cancelled'],
    'assigned' => ['in_progress', 'waiting_customer', 'cancelled'],
    'in_progress' => ['waiting_customer', 'waiting_vendor', 'resolved', 'cancelled'],
    'waiting_customer' => ['in_progress', 'resolved', 'cancelled'],
    'waiting_vendor' => ['in_progress', 'resolved', 'cancelled'],
    'resolved' => ['closed', 'in_progress'], // Can reopen if customer finds issues
    'closed' => [], // Final state
    'cancelled' => [], // Final state
];
```

## Timer Integration

### Inline Timer Controls
Each service ticket includes inline timer controls for seamless time tracking:

#### TicketTimerControls Component
```vue
<template>
  <div class="ticket-timer-controls">
    <!-- Start Timer Button -->
    <button
      v-if="!userTimer"
      @click="startTimer"
      class="btn-start-timer"
    >
      <PlayIcon class="w-4 h-4" />
      Start Timer
    </button>

    <!-- Active Timer Display -->
    <div v-else-if="userTimer.status === 'running'" class="active-timer">
      <div class="timer-display">
        <span class="timer-duration">{{ formatDuration(userTimer.current_duration) }}</span>
        <span class="timer-value">${{ formatCurrency(userTimer.current_value) }}</span>
      </div>
      
      <div class="timer-controls">
        <button @click="pauseTimer" class="btn-pause">
          <PauseIcon class="w-3 h-3" />
        </button>
        <button @click="stopTimer" class="btn-stop">
          <StopIcon class="w-3 h-3" />
        </button>
      </div>
    </div>

    <!-- Paused Timer Display -->
    <div v-else-if="userTimer.status === 'paused'" class="paused-timer">
      <div class="timer-display">
        <span class="timer-duration paused">{{ formatDuration(userTimer.total_duration) }}</span>
        <PauseIcon class="w-4 h-4 text-yellow-500" />
      </div>
      
      <div class="timer-controls">
        <button @click="resumeTimer" class="btn-resume">
          <PlayIcon class="w-3 h-3" />
        </button>
        <button @click="stopTimer" class="btn-stop">
          <StopIcon class="w-3 h-3" />
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useTimer } from '@/Composables/useTimer';

const props = defineProps({
  ticket: {
    type: Object,
    required: true
  }
});

const { startTimerForTicket, pauseTimer, resumeTimer, stopTimer } = useTimer();

// Find user's timer for this ticket
const userTimer = computed(() => {
  return props.ticket.timers?.find(timer => 
    timer.user_id === $page.props.auth.user.id &&
    ['running', 'paused'].includes(timer.status)
  );
});

const startTimer = async () => {
  await startTimerForTicket(props.ticket.id, {
    description: `Working on: ${props.ticket.title}`,
    account_id: props.ticket.account_id,
    billing_rate_id: props.ticket.default_billing_rate_id,
  });
};
</script>
```

### Multi-User Timer Visibility
Service tickets display all active timers from team members:

```php
// Get all active timers for this ticket
public function getActiveTimers(): Collection
{
    return $this->timers()
        ->with(['user', 'billingRate'])
        ->whereIn('status', ['running', 'paused'])
        ->get();
}

// Calculate total time tracked on ticket
public function getTotalTimeTracked(): int
{
    return $this->timeEntries()->sum('duration') + 
           $this->timers()->where('status', 'running')->sum('total_duration');
}

// Calculate current ticket value
public function getCurrentValue(): float
{
    $timeEntryValue = $this->timeEntries()
        ->join('billing_rates', 'time_entries.billing_rate_id', '=', 'billing_rates.id')
        ->sum(DB::raw('(time_entries.duration / 3600) * billing_rates.rate'));

    $activeTimerValue = $this->timers()
        ->join('billing_rates', 'timers.billing_rate_id', '=', 'billing_rates.id')
        ->where('timers.status', 'running')
        ->sum(DB::raw('(timers.total_duration / 3600) * billing_rates.rate'));

    return $timeEntryValue + $activeTimerValue;
}
```

## Account Context & Permissions

### Permission-Based Access

#### Service Provider Permissions
- `tickets.create` - Create new service tickets
- `tickets.view.all` - View all tickets across accounts
- `tickets.view.account` - View tickets for assigned accounts
- `tickets.assign` - Assign tickets to team members
- `tickets.manage` - Full ticket management capabilities
- `tickets.delete` - Delete tickets (admin only)

#### Customer Account Permissions
- `tickets.view.own` - View tickets for their account
- `tickets.create.account` - Create tickets for their account
- `tickets.comment` - Add comments and updates to tickets
- `tickets.approve` - Approve ticket resolutions

### Account Context Switching
Service providers can switch account context to focus on specific customers:

```php
public function getTicketsForAccount(Account $account, User $user): Builder
{
    $query = ServiceTicket::where('account_id', $account->id);
    
    // Apply permission-based filtering
    if (!$user->hasPermission('tickets.view.all')) {
        if ($user->hasPermission('tickets.view.assigned')) {
            $query->where('assigned_to', $user->id);
        } else {
            // Customer users can only see their account's tickets
            $query->whereHas('account.users', function($q) use ($user) {
                $q->where('users.id', $user->id);
            });
        }
    }
    
    return $query;
}
```

## Ticket Addon System (Phase 12B)

### Additional Items and Services
Service tickets can include additional items and services beyond time tracking:

#### Addon Types
- **Physical Items**: Hardware, equipment, supplies
- **Software Licenses**: Third-party software, subscriptions
- **Third-Party Services**: External contractor work, specialized services
- **Travel Expenses**: Mileage, lodging, meals for on-site work
- **Custom Items**: Project-specific additions

#### Addon Management
```php
// Add addon to ticket
$ticket->addAddon([
    'type' => 'software_license',
    'name' => 'Microsoft Office 365 Business License',
    'description' => 'Annual subscription for new user',
    'quantity' => 1,
    'unit_price' => 150.00,
    'total_price' => 150.00,
    'vendor' => 'Microsoft',
    'notes' => 'License will be assigned to john.doe@company.com'
]);

// Calculate ticket total with addons
public function getTotalWithAddons(): float
{
    $timeValue = $this->getCurrentValue();
    $addonValue = $this->addons()->sum('total_price');
    
    return $timeValue + $addonValue;
}
```

#### Addon UI Component
```vue
<template>
  <div class="ticket-addons">
    <!-- Add Addon Button -->
    <button @click="showAddAddonDialog = true" class="btn-add-addon">
      <PlusIcon class="w-4 h-4" />
      Add Item/Service
    </button>

    <!-- Addon List -->
    <div v-if="ticket.addons?.length" class="addon-list">
      <div v-for="addon in ticket.addons" :key="addon.id" class="addon-item">
        <div class="addon-details">
          <h4 class="addon-name">{{ addon.name }}</h4>
          <p class="addon-description">{{ addon.description }}</p>
          <div class="addon-pricing">
            <span>Qty: {{ addon.quantity }}</span>
            <span>Unit: ${{ addon.unit_price }}</span>
            <span class="font-semibold">Total: ${{ addon.total_price }}</span>
          </div>
        </div>
        
        <div class="addon-actions">
          <button @click="editAddon(addon)" class="btn-edit">Edit</button>
          <button @click="removeAddon(addon)" class="btn-remove">Remove</button>
        </div>
      </div>
    </div>

    <!-- Ticket Total -->
    <div class="ticket-total">
      <div class="total-line">
        <span>Time Tracking:</span>
        <span>${{ formatCurrency(ticket.time_value) }}</span>
      </div>
      <div v-if="ticket.addon_value > 0" class="total-line">
        <span>Items & Services:</span>
        <span>${{ formatCurrency(ticket.addon_value) }}</span>
      </div>
      <div class="total-line font-bold">
        <span>Total:</span>
        <span>${{ formatCurrency(ticket.total_value) }}</span>
      </div>
    </div>
  </div>
</template>
```

## Communication & Updates

### Ticket Comments and Updates
Service tickets support threaded communication between service providers and customers:

#### Comment Types
- `public` - Visible to both service provider and customer
- `internal` - Only visible to service provider team
- `system` - Automated system updates (status changes, assignments)
- `time_entry` - Automatic comments when time entries are created

#### Comment Management
```php
$ticket->addComment([
    'user_id' => $user->id,
    'type' => 'public',
    'content' => 'I have identified the root cause of the authentication issue...',
    'visibility' => 'customer',
    'attachments' => ['screenshot.png', 'error_log.txt']
]);

// Notify relevant parties
$ticket->notifyStakeholders('comment_added', [
    'comment' => $comment,
    'notify_customer' => true,
    'notify_assigned_user' => true,
    'notify_account_managers' => true
]);
```

### Real-Time Updates
Service tickets support real-time updates via Laravel Echo broadcasting:

```javascript
// Listen for ticket updates
Echo.private(`ticket.${ticketId}`)
    .listen('TicketUpdated', (event) => {
        updateTicketDisplay(event.ticket);
    })
    .listen('CommentAdded', (event) => {
        addCommentToTicket(event.comment);
    })
    .listen('StatusChanged', (event) => {
        updateTicketStatus(event.old_status, event.new_status);
    })
    .listen('TimerStarted', (event) => {
        addActiveTimer(event.timer);
    });
```

## Reporting & Analytics

### Ticket Metrics
Service tickets provide comprehensive reporting capabilities:

#### Performance Metrics
```php
// Calculate ticket resolution times
public function getAverageResolutionTime(Account $account = null): float
{
    $query = ServiceTicket::where('status', 'closed');
    
    if ($account) {
        $query->where('account_id', $account->id);
    }
    
    return $query->selectRaw('AVG(TIMESTAMPDIFF(HOUR, created_at, updated_at)) as avg_hours')
                 ->value('avg_hours') ?: 0;
}

// Calculate customer satisfaction metrics
public function getCustomerSatisfactionStats(Account $account): array
{
    return [
        'total_tickets' => $account->serviceTickets()->count(),
        'resolved_tickets' => $account->serviceTickets()->where('status', 'closed')->count(),
        'average_resolution_time' => $this->getAverageResolutionTime($account),
        'tickets_reopened' => $account->serviceTickets()
            ->whereHas('statusHistory', function($q) {
                $q->where('new_status', 'in_progress')
                  ->where('old_status', 'resolved');
            })->count(),
    ];
}
```

#### Revenue Analytics
```php
// Calculate ticket revenue
public function getTicketRevenue(ServiceTicket $ticket): array
{
    $timeRevenue = $ticket->timeEntries()
        ->join('billing_rates', 'time_entries.billing_rate_id', '=', 'billing_rates.id')
        ->sum(DB::raw('(time_entries.duration / 3600) * billing_rates.rate'));

    $addonRevenue = $ticket->addons()->sum('total_price');

    return [
        'time_revenue' => $timeRevenue,
        'addon_revenue' => $addonRevenue,
        'total_revenue' => $timeRevenue + $addonRevenue,
        'billable_hours' => $ticket->timeEntries()->sum('duration') / 3600,
        'total_hours_tracked' => ($ticket->timeEntries()->sum('duration') + 
                                $ticket->timers()->sum('total_duration')) / 3600,
    ];
}
```

## Advanced Features

### Ticket Templates
Create reusable templates for common service requests:

```php
$template = TicketTemplate::create([
    'name' => 'New User Setup',
    'category' => 'user_management',
    'description' => 'Complete new user account setup and training',
    'default_priority' => 'normal',
    'estimated_hours' => 2,
    'checklist' => [
        'Create user account in Active Directory',
        'Setup email account',
        'Install and configure software',
        'Provide user training session',
        'Document access credentials'
    ],
    'default_addons' => [
        ['name' => 'Software License', 'type' => 'software_license', 'unit_price' => 50.00],
        ['name' => 'Training Materials', 'type' => 'physical_item', 'unit_price' => 25.00]
    ]
]);
```

### SLA Management
Service Level Agreement tracking and enforcement:

```php
public function getSLAStatus(): array
{
    $sla = $this->account->serviceLevelAgreement;
    
    if (!$sla) {
        return ['status' => 'no_sla'];
    }
    
    $responseTime = $sla->getResponseTimeForPriority($this->priority);
    $resolutionTime = $sla->getResolutionTimeForPriority($this->priority);
    
    $timeSinceCreated = $this->created_at->diffInHours(now());
    $timeSinceResolved = $this->status === 'closed' ? 
        $this->updated_at->diffInHours($this->created_at) : null;
    
    return [
        'response_sla_met' => $this->first_response_at && 
            $this->created_at->diffInHours($this->first_response_at) <= $responseTime,
        'resolution_sla_met' => $timeSinceResolved && $timeSinceResolved <= $resolutionTime,
        'response_time_remaining' => max(0, $responseTime - $timeSinceCreated),
        'resolution_time_remaining' => $this->status !== 'closed' ? 
            max(0, $resolutionTime - $timeSinceCreated) : null,
    ];
}
```

### Escalation Rules
Automatic ticket escalation based on time and priority:

```php
// Escalation rule example
public function checkEscalationRules(): void
{
    $escalationRules = $this->account->escalationRules()
        ->where('priority', $this->priority)
        ->where('status', $this->status)
        ->get();
    
    foreach ($escalationRules as $rule) {
        if ($this->shouldEscalate($rule)) {
            $this->escalate($rule);
        }
    }
}

private function shouldEscalate(EscalationRule $rule): bool
{
    $timeSinceLastUpdate = $this->updated_at->diffInHours(now());
    return $timeSinceLastUpdate >= $rule->escalation_time_hours;
}

private function escalate(EscalationRule $rule): void
{
    // Notify escalation contacts
    foreach ($rule->escalationContacts as $contact) {
        $contact->notify(new TicketEscalatedNotification($this, $rule));
    }
    
    // Update ticket priority if specified
    if ($rule->new_priority) {
        $this->update(['priority' => $rule->new_priority]);
    }
    
    // Reassign if specified
    if ($rule->reassign_to) {
        $this->assignTo($rule->reassign_to, null, 'auto_escalation');
    }
}
```

## Security & Compliance

### Audit Trail
Complete audit trail for all ticket activities:

```php
// Log all ticket changes
protected static function booted()
{
    static::updating(function (ServiceTicket $ticket) {
        $changes = $ticket->getDirty();
        
        foreach ($changes as $field => $newValue) {
            $ticket->auditLog()->create([
                'user_id' => auth()->id(),
                'action' => 'updated',
                'field' => $field,
                'old_value' => $ticket->getOriginal($field),
                'new_value' => $newValue,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        }
    });
}
```

### Data Privacy
Compliance with data protection regulations:

```php
// Data retention and privacy controls
public function anonymizeTicket(): void
{
    $this->update([
        'title' => 'Anonymized Ticket',
        'description' => 'Ticket data anonymized per data retention policy',
        'customer_data_removed' => true,
        'anonymized_at' => now(),
    ]);
    
    // Remove or anonymize related data
    $this->comments()->update(['content' => 'Comment removed per privacy policy']);
    $this->attachments()->delete();
}

public function exportTicketData(): array
{
    // GDPR data export functionality
    return [
        'ticket' => $this->toArray(),
        'comments' => $this->comments()->get()->toArray(),
        'time_entries' => $this->timeEntries()->get()->toArray(),
        'addons' => $this->addons()->get()->toArray(),
        'audit_log' => $this->auditLog()->get()->toArray(),
    ];
}
```

## Integration Points

### Time Entry Integration
Seamless conversion from timers to billable time entries:

```php
public function commitTimerToTicket(Timer $timer): TimeEntry
{
    $timeEntry = TimeEntry::create([
        'user_id' => $timer->user_id,
        'service_ticket_id' => $this->id,
        'account_id' => $this->account_id,
        'description' => $timer->description,
        'duration' => $timer->total_duration,
        'started_at' => $timer->started_at,
        'ended_at' => $timer->stopped_at ?: now(),
        'billing_rate_id' => $timer->billing_rate_id,
        'billable' => true,
        'status' => 'pending_approval',
    ]);

    // Create automatic comment on ticket
    $this->addComment([
        'user_id' => $timer->user_id,
        'type' => 'system',
        'content' => "Time entry created: {$this->formatDuration($timer->total_duration)} - {$timer->description}",
        'time_entry_id' => $timeEntry->id,
    ]);

    return $timeEntry;
}
```

### Billing System Integration
Direct integration with invoicing and billing:

```php
public function generateInvoiceLineItems(): array
{
    $lineItems = [];
    
    // Time-based line items
    $timeEntries = $this->timeEntries()
        ->where('billable', true)
        ->where('status', 'approved')
        ->with('billingRate')
        ->get()
        ->groupBy('billing_rate_id');
        
    foreach ($timeEntries as $billingRateId => $entries) {
        $totalHours = $entries->sum('duration') / 3600;
        $rate = $entries->first()->billingRate;
        
        $lineItems[] = [
            'description' => "Service Time - {$rate->name} (Ticket #{$this->id}: {$this->title})",
            'quantity' => $totalHours,
            'unit_price' => $rate->rate,
            'total' => $totalHours * $rate->rate,
            'type' => 'time_tracking'
        ];
    }
    
    // Addon line items
    foreach ($this->addons as $addon) {
        $lineItems[] = [
            'description' => "{$addon->name} - {$addon->description}",
            'quantity' => $addon->quantity,
            'unit_price' => $addon->unit_price,
            'total' => $addon->total_price,
            'type' => 'addon_item'
        ];
    }
    
    return $lineItems;
}
```

## Enhanced Ticket Detail Page

### Comprehensive Ticket Management Hub

The ticket detail page (`/tickets/{id}`) serves as the central hub for all ticket interactions, featuring:

#### Tabbed Interface Design
- **Messages**: Real-time communication system with internal and external comments
- **Time Tracking**: Comprehensive time management with active timers and time entry history
- **Add-ons**: Item and service management with approval workflows
- **Activity**: Complete audit trail with filtering and detailed event tracking
- **Billing**: Invoice integration showing which time entries are billed

#### Key Features
```vue
<!-- Modern responsive ticket detail layout -->
<div class="grid grid-cols-1 xl:grid-cols-4 gap-6">
  <!-- Main Content Area (3 columns) -->
  <div class="xl:col-span-3 space-y-6">
    <!-- Tabbed Content with Messages, Time, Addons, Activity, Billing -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
      <!-- Dynamic tab switching with real-time data loading -->
    </div>
  </div>
  
  <!-- Sidebar (1 column) -->
  <div class="space-y-6">
    <!-- Ticket details, status controls, time summary -->
  </div>
</div>
```

#### Real-Time Messaging System
- **Internal Comments**: Team-only communication for coordination
- **External Comments**: Customer-visible updates and communications
- **System Messages**: Automated activity logging
- **File Attachments**: Document and image sharing
- **Live Updates**: Real-time message delivery via Laravel Echo

#### Time Tracking Integration
- **Active Timer Display**: Real-time duration and cost tracking
- **Timer Controls**: Start, pause, resume, and commit functionality
- **Time Entry Management**: Create, edit, and approve time entries
- **Billing Rate Assignment**: Flexible rate management per entry
- **Duration Calculation**: Streamlined work time tracking (break duration logic removed)

#### Time Entry Modal Enhancements
The time entry system has been streamlined for optimal user experience:

**Simplified Form Structure:**
```javascript
const form = ref({
  user_id: window.auth?.user?.id || '',
  date: new Date().toISOString().split('T')[0],
  start_time: '',
  hours: 0,
  minutes: 0,
  description: '',
  billable: true
  // Break duration logic completely removed for UX simplification
})
```

**Key Improvements:**
- Removed unnecessary break duration fields to eliminate confusion
- Streamlined duration calculation focusing only on work time
- Cleaner API payload without break-related data
- Enhanced user experience with simplified time tracking

#### Addon Management System
- **Template-Based Creation**: Pre-defined addon types and categories
- **Approval Workflow**: Multi-stage approval process with notes
- **Cost Tracking**: Detailed pricing and billing integration
- **Status Management**: Pending, approved, rejected, completed states

#### Activity Timeline
- **Complete Audit Trail**: Every action and change logged
- **Filtering Options**: Filter by activity type, user, or date range
- **Visual Timeline**: Chronological display with activity icons
- **Detailed Context**: Rich information about each activity

#### Billing Integration
The billing system provides comprehensive financial tracking:

- **Time Entry Billing**: Shows which time entries are associated with invoices
- **Invoice Links**: Direct access to related billing documents  
- **Cost Summaries**: Real-time calculation of billable amounts
- **Rate Management**: Billing rate assignment and tracking
- **Revenue Analytics**: Performance and profitability metrics

**Important**: Time entries are billed individually, not tickets as a whole. The billing overview tracks invoice associations through time entries.

### API Enhancements

New endpoints added to support the enhanced ticket detail page:

```php
// Ticket detail page endpoints
GET    /api/tickets/{ticket}/time-summary     // Time tracking summary
GET    /api/tickets/{ticket}/activity         // Activity timeline  
GET    /api/tickets/{ticket}/activity/stats   // Activity statistics
GET    /api/tickets/{ticket}/billing-summary  // Billing overview
GET    /api/tickets/{ticket}/billing-rate     // Current billing rate
POST   /api/tickets/{ticket}/billing-rate     // Set billing rate
GET    /api/tickets/{ticket}/invoices         // Related invoices
POST   /api/tickets/{ticket}/status          // Update ticket status
POST   /api/tickets/{ticket}/assignment      // Update assignment

// Addon management endpoints
GET    /api/ticket-addons                     // List addons
POST   /api/ticket-addons                     // Create addon
PUT    /api/ticket-addons/{addon}             // Update addon
DELETE /api/ticket-addons/{addon}             // Delete addon
POST   /api/ticket-addons/{addon}/approve     // Approve addon
POST   /api/ticket-addons/{addon}/reject      // Reject addon
POST   /api/ticket-addons/{addon}/complete    // Complete addon
```

### Component Architecture

The enhanced ticket detail page utilizes modular Vue.js components:

- **`TimeTrackingManager.vue`**: Comprehensive time management interface
- **`TicketAddonManager.vue`**: Addon creation and approval workflows  
- **`ActivityTimeline.vue`**: Interactive activity history display
- **`BillingOverview.vue`**: Financial tracking and invoice integration
- **`AddTimeEntryModal.vue`**: Streamlined time entry creation
- **`EditTimeEntryModal.vue`**: Time entry modification interface

Service Vault's service ticket system provides comprehensive workflow management with an enhanced detail page interface, seamless timer integration, real-time collaboration, and extensive customization options for enterprise service delivery.