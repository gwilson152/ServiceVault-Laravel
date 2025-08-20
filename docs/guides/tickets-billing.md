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

## Tax Configuration System

### Tax Settings Overview

Service Vault includes a comprehensive tax management system that can be configured during setup or in settings:

**Setup Page Configuration** (Initial Installation):
- **Tax Enabled**: Enable/disable the tax system
- **Default Tax Rate**: System-wide tax percentage (e.g., 6%)
- **Tax Application Mode**: Controls which items are taxable
- **Time Entries Taxable by Default**: Whether time entries are taxable when application mode is "All Items"

**Tax Application Modes**:
1. **All Taxable Items**: Tax applies to both time entries (services) and addons (products)
2. **Products Only (No Services)**: Tax applies only to addons/products, not time entries
3. **Custom (Per Item)**: Tax application determined by individual line item settings

### Settings Page Tax Configuration

Navigate to **Settings > Billing > Tax Configuration** to modify tax settings:

**Available Settings**:
- **Enable Tax System**: Master toggle for tax calculations
- **Default Tax Rate**: Percentage applied to taxable items
- **Default Tax Application**: How taxes are applied system-wide
- **Time Entries Taxable by Default**: Controls default taxability for time entries

**Important**: Tax settings require clicking "Save Changes" - they are not auto-saved.

### Invoice Tax Behavior

**Tax Inheritance Hierarchy**:
1. **Invoice Override**: If enabled, invoice uses custom tax settings
2. **Account Settings**: Account-specific tax rates (future feature)
3. **System Defaults**: Global settings from configuration

**Per-Line-Item Tax Control**:
Each invoice line item has a taxable setting with three states:
- ✅ **Taxable** (green): Item is explicitly taxable
- ❌ **Not Taxable** (red): Item is explicitly not taxable  
- ➖ **Inherit** (gray): Item follows system/invoice settings

**Tax Calculation**:
- Tax is calculated individually per line item
- Final invoice shows subtotal, tax amount, and total
- Tax amount displays for each taxable line item in detail view

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

### Invoice Generation & Approval Workflow

**Enhanced Invoice Creation Process**:
Service Vault features a comprehensive invoice generation system with integrated approval workflow:

**Step 1: Account Selection & Item Discovery**
- Select target account for invoice generation
- System automatically discovers unbilled items:
  - Approved time entries (ready to invoice)
  - Pending time entries (require approval)
  - Approved ticket addons (ready to invoice)
  - Pending ticket addons (require approval)

**Step 2: Item Review & Selection**
- **Visual Status Indicators**:
  - ✅ Green checkmarks: Approved items (auto-selected)
  - ⏳ Orange pending: Unapproved items (noted as requiring approval)
  - 🚫 Red rejected: Previously rejected items (excluded)
- **Smart Selection**: Only approved items can be invoiced
- **Real-time Totals**: Dynamic calculation as items are selected/deselected

**Step 3: Approval Wizard Integration**
- **Launch from Billing Page**: Quick access to bulk approval workflow
- **Launch from Time Entries**: Review mode with account filtering
- **Step-through Process**: Review each pending item individually
- **Bulk Operations**: Approve/reject multiple items simultaneously
- **Progress Tracking**: Visual indicators of approval workflow completion

**Step 4: Invoice Configuration**
- **Manual Date Setting**: Custom invoice and due dates
- **Tax Rate Application**: Configurable tax calculations
- **Invoice Notes**: Additional terms or comments
- **Real-time Preview**: Live invoice summary with totals

**Invoice Management Features**:
- **Post-Generation Editing**: Add/remove items after creation
- **PDF Export**: Professional invoices for client delivery
- **Payment Tracking**: Mark invoices as paid/unpaid
- **Send Invoices**: Email delivery to clients
- **Invoice History**: Complete audit trail

**Approval Workflow Details**:
```
Pending Items → Review Process → Approval Decision → Invoice Inclusion
     ↓              ↓                 ↓                    ↓
Time Entries   Individual      Approve/Reject      Auto-Selected
  Addons       Bulk Review     with Notes         for Invoicing
```

**Permission Requirements**:
- **Invoice Creation**: `billing.manage` or `admin.write`
- **Item Approval**: `time.approve` (time entries) or `tickets.approve` (addons)
- **Approval Wizard Access**: Manager-level permissions or above

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

### Billing System Endpoints

```bash
# Billing Rate Management
GET    /api/billing-rates              # List billing rates
POST   /api/billing-rates              # Create billing rate
PUT    /api/billing-rates/{rate}       # Update billing rate
DELETE /api/billing-rates/{rate}       # Delete billing rate

# Account-Specific Rates
GET    /api/billing-rates?account_id={id}  # Get rates for account

# Invoice Management
GET    /api/billing/invoices           # List invoices with filtering
POST   /api/billing/invoices           # Create new invoice
GET    /api/billing/invoices/{invoice} # Show invoice details
PUT    /api/billing/invoices/{invoice} # Update invoice
DELETE /api/billing/invoices/{invoice} # Delete invoice
POST   /api/billing/invoices/{invoice}/send      # Email invoice to client
POST   /api/billing/invoices/{invoice}/mark-paid # Mark invoice as paid
GET    /api/billing/invoices/{invoice}/pdf       # Generate PDF

# Unbilled Items & Approval Workflow
GET    /api/billing/unbilled-items     # Get unbilled items by account
                                       # Params: account_id, include_unapproved

# Time Entry Approval
POST   /api/time-entries/{id}/approve  # Approve time entry
POST   /api/time-entries/{id}/reject   # Reject time entry
POST   /api/time-entries/bulk/approve  # Bulk approve time entries
POST   /api/time-entries/bulk/reject   # Bulk reject time entries

# Ticket Addon Approval  
POST   /api/ticket-addons/{id}/approve # Approve ticket addon
POST   /api/ticket-addons/{id}/reject  # Reject ticket addon
POST   /api/ticket-addons/bulk/approve # Bulk approve addons
POST   /api/ticket-addons/bulk/reject  # Bulk reject addons

# Billing Reports & Analytics
GET    /api/billing/reports/dashboard   # Billing dashboard statistics
GET    /api/billing/reports/revenue     # Revenue reports
GET    /api/billing/reports/outstanding # Outstanding invoices
GET    /api/billing/reports/payments    # Payment tracking
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