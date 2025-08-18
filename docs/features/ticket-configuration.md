# Ticket Configuration System

Service Vault provides a comprehensive ticket configuration system that allows administrators to define and manage ticket workflows, statuses, categories, and priorities through an intuitive drag-and-drop interface.

## Overview

The ticket configuration system is built around three main components:

-   **Ticket Statuses** - Define the lifecycle states of tickets
-   **Ticket Categories** - Classify tickets by type with SLA configuration
-   **Ticket Priorities** - Assign urgency levels with escalation multipliers
-   **Workflow Transitions** - Define valid status transitions

## Key Features

### ✅ Drag-and-Drop Ordering

-   Visual reordering of all configuration items
-   **Optimistic UI updates** - Instant feedback without page reload
-   Background API persistence with error recovery
-   Consistent ordering across the application

### ✅ Modal-Based CRUD Operations

-   Create, edit, and delete configuration items
-   Color picker integration for visual identification
-   Form validation with real-time feedback
-   Context-aware field requirements

### ✅ Path Parameter Navigation

-   URL-based tab navigation (`/settings/tickets`)
-   Browser history support
-   Deep linking to specific configuration sections

### ✅ Workflow Management

-   Visual workflow transition editor
-   Status-to-status transition rules
-   Quick actions (select all, clear all, reset defaults)
-   Workflow validation and conflict detection

## Architecture

### Frontend Components

#### TicketConfiguration.vue

Main configuration component with drag-drop functionality:

-   Uses `vuedraggable` for reordering interface
-   Implements optimistic updates for smooth UX
-   Manages modal state and CRUD operations
-   Handles API communication with error recovery

#### Modal Components

-   **StatusManagerModal.vue** - Status CRUD with color picker
-   **CategoryManagerModal.vue** - Category management with SLA configuration
-   **PriorityManagerModal.vue** - Priority management with escalation settings
-   **WorkflowEditorModal.vue** - Visual workflow transition editor

### Backend API

#### Ticket Configuration Endpoints

```bash
GET    /api/settings/ticket-config     # Get all configuration data
GET    /api/ticket-statuses            # List statuses with filtering
POST   /api/ticket-statuses            # Create new status
PUT    /api/ticket-statuses/{id}       # Update existing status
DELETE /api/ticket-statuses/{id}       # Delete status (with validation)
POST   /api/ticket-statuses/reorder    # Reorder statuses
```

#### Category & Priority Endpoints

```bash
# Categories
GET    /api/ticket-categories          # List with SLA information
POST   /api/ticket-categories          # Create with approval settings
PUT    /api/ticket-categories/{id}     # Update category
DELETE /api/ticket-categories/{id}     # Delete (with usage validation)
POST   /api/ticket-categories/reorder  # Reorder categories

# Priorities
GET    /api/ticket-priorities          # List with escalation info
POST   /api/ticket-priorities          # Create with multipliers
PUT    /api/ticket-priorities/{id}     # Update priority
DELETE /api/ticket-priorities/{id}     # Delete (with usage validation)
POST   /api/ticket-priorities/reorder  # Reorder priorities
```

#### Workflow Configuration

```bash
PUT    /api/settings/workflow-transitions # Update transition rules
```

## Data Models

### TicketStatus

```php
class TicketStatus extends Model
{
    protected $fillable = [
        'key',                  // Unique identifier
        'name',                 // Display name
        'description',          // Optional description
        'color',                // Hex color code
        'bg_color',             // Background color
        'icon',                 // Icon identifier
        'is_active',            // Active status
        'is_default',           // Default status flag
        'is_closed',            // Closed state flag
        'billable',          // Billable time flag
        'sort_order',           // Display order
        'metadata'              // Additional data
    ];
}
```

### TicketCategory

```php
class TicketCategory extends Model
{
    protected $fillable = [
        'key',                          // Unique identifier
        'name',                         // Display name
        'description',                  // Optional description
        'color',                        // Hex color code
        'bg_color',                     // Background color
        'icon',                         // Icon identifier
        'is_active',                    // Active status
        'is_default',                   // Default category flag
        'requires_approval',            // Approval requirement
        'default_priority_multiplier',  // SLA multiplier
        'default_estimated_hours',      // Default time estimate
        'sla_hours',                    // SLA deadline in hours
        'sort_order',                   // Display order
        'metadata'                      // Additional data
    ];
}
```

### TicketPriority

```php
class TicketPriority extends Model
{
    protected $fillable = [
        'key',                   // Unique identifier
        'name',                  // Display name
        'description',           // Optional description
        'color',                 // Hex color code
        'bg_color',              // Background color
        'icon',                  // Icon/emoji identifier
        'severity_level',        // Numeric severity (1-10)
        'escalation_multiplier', // SLA escalation factor
        'is_active',             // Active status
        'is_default',            // Default priority flag
        'sort_order',            // Display order
        'metadata'               // Additional data
    ];
}
```

## Optimistic UI Updates

The system implements optimistic updates for drag-drop reordering to provide smooth user experience:

### How It Works

1. **Drag Operation** - User drags item to new position
2. **Immediate UI Update** - Interface updates instantly via `vuedraggable`
3. **Background API Call** - Reorder request sent to server
4. **Success** - No additional action needed (already visible)
5. **Error** - Revert to original order and show error message

### Error Recovery

```javascript
const handleStatusReorder = async (event) => {
    if (event.moved) {
        // Store original order for rollback
        const originalOrder = [...statuses.value];

        try {
            const response = await fetch("/api/ticket-statuses/reorder", {
                method: "POST",
                body: JSON.stringify({ statuses: reorderedItems }),
            });

            if (!response.ok) {
                throw new Error("Server returned error");
            }

            // Success - optimistic update already applied
        } catch (error) {
            console.error("Failed to reorder statuses:", error);
            // Revert on error by restoring original order
            statusList.value = [...originalOrder];
        }
    }
};
```

## Workflow Transition System

### Visual Editor

-   Checkbox-based transition configuration
-   Source status → Target status mapping
-   Quick actions for bulk operations
-   Validation of circular transitions

### Transition Rules Storage

Workflow transitions are stored as JSON in the settings table:

```json
{
    "open": ["in_progress", "pending_review", "closed"],
    "in_progress": ["pending_review", "open", "closed"],
    "pending_review": ["closed", "in_progress"],
    "closed": []
}
```

### Integration with Tickets

The tickets page automatically respects configuration ordering:

-   Status dropdown uses configured order
-   Priority dropdown uses configured order
-   Category dropdown uses configured order
-   Workflow transitions enforce valid status changes

## SLA Management

### Category-Based SLA

Each ticket category can define:

-   **SLA Hours** - Target resolution time
-   **Default Estimated Hours** - Initial time estimate
-   **Approval Requirements** - Workflow gates

### Priority Escalation

Priorities include escalation multipliers:

-   **Normal Priority** - 1.0x multiplier (baseline)
-   **High Priority** - 0.75x multiplier (25% faster SLA)
-   **Urgent Priority** - 0.5x multiplier (50% faster SLA)

### SLA Calculation

```php
// Effective SLA = Category SLA × Priority Multiplier
$effectiveSlaHours = $category->sla_hours * $priority->escalation_multiplier;
```

## Permission System

### Required Permissions

-   **View Configuration**: `admin.read`, `settings.manage`, `tickets.manage`
-   **Modify Configuration**: `admin.write`, `settings.manage`
-   **Delete Items**: `admin.write`, `settings.manage` + usage validation

### Validation Rules

-   Cannot delete default items
-   Cannot delete items in use by existing tickets
-   Unique key constraints across all configuration types
-   Color validation (hex format)
-   Ordering constraints (non-negative integers)

## Usage Integration

### Tickets Page

The main tickets page (`/tickets`) automatically uses configuration:

-   **Filters** populate from active statuses, categories, priorities
-   **Status Changes** respect workflow transition rules
-   **Display Order** matches configuration ordering
-   **Visual Styling** uses configured colors and icons

### Dynamic Loading

Configuration is loaded via the `/api/settings/ticket-config` endpoint:

```javascript
const loadTicketConfig = async () => {
    const response = await fetch("/api/settings/ticket-config");
    const data = await response.json();

    ticketStatuses.value = data.data.statuses || [];
    ticketPriorities.value = data.data.priorities || [];
    ticketCategories.value = data.data.categories || [];
    workflowTransitions.value = data.data.workflow_transitions || {};
};
```

## Future Enhancements

### Planned Features

-   **Bulk Import/Export** - CSV-based configuration management
-   **Template System** - Pre-configured workflow templates
-   **Conditional Transitions** - Role-based transition rules
-   **Automation Rules** - Auto-status changes based on conditions
-   **SLA Notifications** - Email alerts for approaching deadlines

### API Extensions

-   **Bulk Operations** - Multi-item CRUD operations
-   **Configuration Versioning** - Track configuration changes
-   **Validation Rules** - Custom validation for transitions
-   **Integration Hooks** - Webhook support for configuration changes

## Best Practices

### Configuration Management

1. **Start Simple** - Begin with basic status workflow
2. **Test Transitions** - Validate workflow before deployment
3. **Monitor Usage** - Review configuration effectiveness
4. **Document Changes** - Track configuration modifications

### Performance Considerations

1. **Caching** - Configuration is cached for performance
2. **Pagination** - Large configurations support pagination
3. **Lazy Loading** - Modal components load on demand
4. **Optimistic Updates** - Immediate feedback without server round-trips

### User Experience

1. **Visual Feedback** - Color coding and icons for clarity
2. **Drag Indicators** - Clear drag handles and hover states
3. **Error Recovery** - Graceful handling of failed operations
4. **Responsive Design** - Mobile-friendly configuration interface
