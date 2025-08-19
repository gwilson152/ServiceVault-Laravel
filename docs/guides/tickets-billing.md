# Tickets & Billing Guide

Complete guide to Service Vault's ticketing system with integrated billing and time tracking.

## Ticket Management

### Creating Tickets

**From Main Interface**:
1. Click "Create Ticket" button
2. Fill in basic information:
   - **Title**: Brief description of the issue/request
   - **Description**: Detailed information
   - **Account**: Client account (auto-selected in context)
   - **Customer**: Specific user within the account
   - **Agent**: Assigned service agent
   - **Priority**: Low, Normal, Medium, High, Urgent
   - **Category**: Support, Development, Maintenance, etc.
   - **Due Date**: Optional deadline
   - **Tags**: Comma-separated keywords

**Recent Enhancement**: Ticket creation dialog now supports both create and edit modes with smart data prefilling.

### Ticket Workflow

```
Open → In Progress → Waiting Customer → On Hold → Resolved → Closed
```

**Available Actions**:
- **Assign/Reassign**: Change agent assignment
- **Set Priority**: Adjust urgency level
- **Change Status**: Move through workflow
- **Add Comments**: Real-time messaging with notifications
- **Start Timer**: Begin time tracking on ticket
- **Add Billing**: Add billable items (addons)

### Real-Time Messaging

Each ticket has a real-time comment system:
- **Live Updates**: Messages appear instantly via WebSocket
- **Permission-Based**: Users only see messages they're authorized to view
- **Notifications**: Email and in-app notifications for relevant updates
- **File Attachments**: Support for documents, images, etc.

### Ticket Addons

Add billable items beyond time tracking:
- **Fixed-Price Items**: One-time charges (setup fees, licenses, etc.)
- **Quantity-Based Items**: Items with variable quantities
- **Automatic Billing**: Integrates with invoice generation

## Billing System

### Billing Rate Management

Service Vault uses a **simplified two-tier billing rate system**:

1. **Account-Specific Rates**: Custom rates for individual accounts
2. **Global Default Rates**: System-wide fallback rates

**Rate Priority**: Account-specific rates override global default rates.

**Recent Change**: Currency specification removed - all rates are in the system's base currency.

### Managing Billing Rates

**Settings → Billing Rates**:
- **Create Global Rates**: Available to all accounts
- **Account-Specific Rates**: Override global rates for specific clients
- **Default Rates**: Mark rates as default for automatic selection
- **Rate Templates**: Save commonly used rate configurations

**Visual Indicators**:
- 🔵 Blue badges: Account-specific rates
- 🟢 Green badges: Global default rates

### Time Tracking & Billing

**Automatic Billing Rate Application**:
1. Start timer on ticket → Rate auto-selected based on:
   - Account-specific rate (if configured)
   - Global default rate (fallback)
   - Manual rate selection (override)

2. Stop timer → Converts to time entry with calculated cost
3. Time entry → Included in invoice generation

**Billing Calculation**:
```
Duration × Hourly Rate = Billable Amount
```

**Approval Workflow**:
- Time entries require manager approval for billing
- Approved entries included in invoicing
- Rejected entries excluded from billing

### Invoice Generation

**Automated Invoice Creation**:
- Approved time entries from date range
- Ticket addons and fixed-price items
- Account-specific billing settings (terms, etc.)

**Manual Invoice Management**:
- Create custom invoices
- Add line items manually
- Apply discounts or adjustments
- Export as PDF for client delivery

## Permissions & Access

### Ticket Permissions

**Core Permissions**:
- **`tickets.view`**: View own tickets
- **`tickets.view.account`**: View account tickets
- **`tickets.view.all`**: View all tickets (admin)
- **`tickets.create`**: Create new tickets
- **`tickets.assign`**: Assign tickets to agents
- **`tickets.manage`**: Full ticket management

**Agent Assignment**:
Users can be assigned to tickets if they have:
- **Primary**: `tickets.act_as_agent` permission
- **Secondary**: `user_type = 'agent'`
- **Admin**: Administrative permissions

### Billing Permissions

**Billing Access Control**:
- **`billing.view`**: View billing rates and invoices
- **`billing.manage`**: Create/edit billing rates
- **`billing.act_as_agent`**: Can be assigned billing responsibility
- **`admin.write`**: Full billing system access

## API Integration

### Ticket Endpoints

```bash
# Ticket Management
GET    /api/tickets                    # List tickets with filtering
POST   /api/tickets                    # Create new ticket
GET    /api/tickets/{ticket}           # Show ticket details
PUT    /api/tickets/{ticket}           # Update ticket
DELETE /api/tickets/{ticket}           # Delete ticket

# Ticket Workflow
POST   /api/tickets/{ticket}/assign    # Assign to agent
POST   /api/tickets/{ticket}/status    # Update status
POST   /api/tickets/{ticket}/priority  # Change priority

# Real-Time Comments
GET    /api/tickets/{ticket}/comments  # List comments
POST   /api/tickets/{ticket}/comments  # Add comment
```

### Billing Rate Endpoints

```bash
# Billing Rate Management
GET    /api/billing-rates              # List billing rates
POST   /api/billing-rates              # Create billing rate
PUT    /api/billing-rates/{rate}       # Update billing rate
DELETE /api/billing-rates/{rate}       # Delete billing rate

# Account-Specific Rates
GET    /api/billing-rates?account_id={id}  # Get rates for account
```

## Configuration

### Ticket Categories

**Settings → Ticket Configuration → Categories**:
- Create custom categories (Support, Development, etc.)
- Set default category for auto-selection
- Configure category-specific settings
- Drag-and-drop reordering

### Ticket Priorities

**Settings → Ticket Configuration → Priorities**:
- Configure priority levels
- Set escalation rules
- Define SLA timeframes
- Color coding for visual identification

### Ticket Statuses

**Settings → Ticket Configuration → Statuses**:
- Customize workflow statuses
- Configure transitions between statuses
- Set permissions for status changes
- Define resolution/closure criteria

For technical implementation details, see [System Architecture](../technical/architecture.md#ticketing-system).