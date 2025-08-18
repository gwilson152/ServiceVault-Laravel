# Ticket Addons

Additional services/products system for tickets with StackedDialog architecture and proper data model relationships.

## Overview

The Ticket Addon system allows users to add additional services, products, or expenses to existing tickets. This enables scope expansion and additional billing beyond the original ticket scope.

## Data Model Architecture

### Core Relationships

```
┌─────────────────┐       ┌─────────────────┐       ┌─────────────────┐
│     Tickets     │◄─────►│  Ticket Addons  │       │  Time Entries   │
│                 │       │                 │       │                 │
│ - id            │       │ - id (UUID)     │       │ - id (UUID)     │
│ - title         │       │ - ticket_id     │       │ - ticket_id     │
│ - description   │       │ - name          │       │ - user_id       │
│ - account_id    │       │ - category      │       │ - duration      │
│ - status        │       │ - unit_price    │       │ - billable      │
│ - priority      │       │ - quantity      │       │ - account_id    │
│                 │       │ - is_billable   │       │                 │
│                 │       │ - is_taxable    │       │                 │
└─────────────────┘       └─────────────────┘       └─────────────────┘
        │                          │                          │
        └──────────────────────────┼──────────────────────────┘
                                   │
                    ┌─────────────────┐
                    │    Accounts     │
                    │                 │
                    │ - id            │
                    │ - name          │
                    │ - hierarchy     │
                    └─────────────────┘
```

### Key Principles

1. **Addons belong to Tickets**: Each addon is associated with a specific ticket
2. **Time Entries belong to Tickets**: Time entries track work performed on tickets
3. **No direct relationship**: Addons and Time Entries are NOT directly related to each other
4. **Both use Accounts**: Both addons and time entries reference accounts for billing context

## Technical Implementation

### Model Configuration

**TicketAddon Model:**
```php
class TicketAddon extends Model
{
    use HasFactory, HasUuid; // ✅ Fixed: Properly implements UUID trait
    
    // Relationships
    public function ticket(): BelongsTo
    public function addedBy(): BelongsTo
    public function approvedBy(): BelongsTo
    public function template(): BelongsTo
    
    // NO timeEntries relationship (addons are not directly related to time entries)
}
```

### Database Schema

**Standardized Billable Columns:**
- All models use consistent `billable` column (not `is_billable`)
- `time_entries.billable` - Boolean for billing time tracking
- `ticket_addons.is_billable` - Boolean for addon billing (different naming for clarity)

### Recent Fixes (August 18, 2025)

1. **UUID Generation Issue**: 
   - Problem: TicketAddon model was missing HasUuid trait implementation
   - Solution: Added `HasUuid` trait to model class declaration
   - Result: Proper UUID generation for all new addon records

2. **Billable Column Standardization**:
   - Problem: Inconsistent usage between `billable` and `is_billable` across codebase
   - Solution: Standardized all Timer and TimeEntry models to use existing `billable` column
   - Result: Consistent billable functionality across all time-related models

3. **Incorrect Relationship Removal**:
   - Problem: TicketAddon model had incorrect `timeEntries()` relationship
   - Solution: Removed relationship and updated controller/UI accordingly
   - Result: Clean data model following proper relationship architecture

## UI Architecture

### StackedDialog Implementation

**Components:**
- `AddAddonModal.vue` - Create new addons with StackedDialog
- `EditAddonModal.vue` - Edit pending addons with restrictions
- `AddonApprovalModal.vue` - Approve/reject addon workflow
- `TicketAddonManager.vue` - Main addon management interface

### Category Alignment

Addon categories are aligned with billing settings:
- product
- service  
- license
- hardware
- software
- expense
- other

## API Endpoints

### Core Operations
```bash
# Get addons for specific ticket
GET /api/tickets/{id}/addons

# Create new addon
POST /api/ticket-addons
{
    "ticket_id": "uuid",
    "name": "Microsoft Office 365",
    "category": "license",
    "unit_price": 12.50,
    "quantity": 1,
    "is_billable": true,
    "is_taxable": true,
    "billing_category": "addon"
}

# Update addon (pending only)
PUT /api/ticket-addons/{id}

# Approve addon
POST /api/ticket-addons/{id}/approve

# Mark complete
POST /api/ticket-addons/{id}/complete

# Delete addon
DELETE /api/ticket-addons/{id}
```

### Permissions

- `tickets.view` - View ticket addons
- `tickets.edit` - Create/edit addons  
- `tickets.approve` - Approve/reject addons
- `admin.write` - Full addon management

## Business Logic

### Approval Workflow
1. **Created** - Auto-approved by default (configurable)
2. **Pending** - Awaiting manager approval (if enabled)
3. **Approved** - Can be marked complete
4. **Rejected** - Can be edited and resubmitted
5. **Completed** - Final state

### Billing Integration
- Approved addons contribute to ticket total cost
- Integrates with two-tier billing rate system
- Supports tax calculations and discounts
- Tracks total amounts for financial reporting

## Best Practices

### Development Guidelines

1. **UUID Usage**: Always use HasUuid trait for new models requiring UUID primary keys
2. **Relationship Clarity**: Keep addon and time entry relationships separate and ticket-focused
3. **Billable Consistency**: Use existing `billable` columns consistently across time-related models
4. **Modal Architecture**: Use StackedDialog for all addon-related modals
5. **Category Alignment**: Ensure addon categories match billing configuration

### Data Integrity

1. **Required Fields**: ticket_id, name, category, unit_price always required
2. **UUID Generation**: Automatic via HasUuid trait implementation
3. **Total Calculation**: Automatic calculation on save (quantity × unit_price - discount + tax)
4. **Status Validation**: Enforce proper status transitions in approval workflow

## Troubleshooting

### Common Issues

**UUID Generation Errors:**
- Ensure model uses `HasUuid` trait in class declaration
- Check that `id` field is properly defined as UUID in migration

**Billable Column Errors:**  
- Use `billable` for time-related models (Timer, TimeEntry)
- Use `is_billable` for addon models (TicketAddon)
- Ensure database schema matches model expectations

**Relationship Errors:**
- Addons belong to tickets, not time entries
- Time entries belong to tickets, not addons  
- Both can reference accounts independently

This documentation reflects the current state after the August 18, 2025 fixes and should be referenced for all future addon-related development.