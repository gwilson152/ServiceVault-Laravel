# Billing & Financial Management System

Service Vault features a comprehensive enterprise-grade billing and financial management system that integrates seamlessly with time tracking, service tickets, and account management.

## Overview

The billing system provides complete financial workflow management for B2B service delivery, including invoice generation, payment processing, billing rate management, and financial reporting.

### Key Capabilities

- **Invoice Management**: Create, send, track, and manage invoices with automated workflows
- **Payment Processing**: Track payments, process refunds, and manage payment methods
- **Billing Rate Management**: Configure tiered billing rates for different services and accounts
- **Financial Reporting**: Comprehensive analytics and reporting for revenue tracking
- **Integration**: Seamless integration with timers, tickets, and service delivery

## Core Components

### 1. Invoice Management

#### Invoice Lifecycle
```
Draft → Pending → Sent → Paid/Overdue → Closed
```

#### Features
- **Automated Invoice Generation**: Generate invoices from time entries and ticket addons
- **Custom Invoice Templates**: Configurable invoice layouts and branding
- **Multi-format Export**: PDF generation and email delivery
- **Payment Terms**: Configurable payment terms and due dates
- **Invoice Numbering**: Customizable invoice number sequences

#### Invoice States
- **Draft**: Invoice being prepared, not yet finalized
- **Pending**: Invoice ready but not yet sent to customer
- **Sent**: Invoice delivered to customer, awaiting payment
- **Paid**: Payment received and processed
- **Overdue**: Invoice past due date without payment
- **Void**: Invoice cancelled or voided

### 2. Payment Processing

#### Payment Methods
- Bank Transfer
- Credit Card
- Check
- Online Payment
- Wire Transfer
- ACH

#### Payment Tracking
- **Real-time Status Updates**: Live payment status tracking
- **Payment Matching**: Automatic matching of payments to invoices
- **Partial Payments**: Support for partial payment processing
- **Refund Management**: Process refunds and credit adjustments
- **Payment History**: Complete audit trail of all transactions

### 3. Billing Rate Management

#### Rate Types
- **Global Rates**: Default rates for all services
- **Account-Specific Rates**: Custom rates for specific customers
- **User-Specific Rates**: Individual consultant or employee rates
- **Service-Type Rates**: Different rates for different types of work

#### Rate Configuration
```php
// Example rate structure
$billingRate = [
    'name' => 'Senior Development',
    'rate' => 125.00,
    'account_id' => $account->id,  // Optional: account-specific
    'user_id' => $user->id,        // Optional: user-specific
    'is_active' => true,
    'effective_date' => '2024-01-01'
];
```

#### Addon Templates
- **Predefined Services**: Template library for common billing items
- **Custom Addons**: Create custom billing items for specific needs
- **Approval Workflows**: Multi-stage approval for addon billing
- **Category Management**: Organize addons by service categories

## User Interface

### 1. Billing Dashboard (`/billing`)

#### Main Sections
- **Overview Tab**: KPI dashboard with revenue metrics
- **Invoices Tab**: Invoice management with TanStack Tables
- **Payments Tab**: Payment tracking and processing
- **Reports Tab**: Financial analytics and reporting

#### Key Features
- **TanStack Vue Tables**: Advanced sorting, filtering, and pagination
- **Real-time Updates**: Live status updates and notifications
- **Bulk Operations**: Process multiple invoices/payments simultaneously
- **Export Capabilities**: PDF, CSV, and Excel export options

### 2. Settings Integration (`/settings/billing`)

#### Configuration Sections
- **Company Information**: Business details for invoice headers
- **Invoice Settings**: Number prefixes, payment terms, tax configuration
- **Billing Rates**: Rate management and configuration
- **Addon Templates**: Service addon library management

### 3. Reports & Analytics (`/reports`)

#### Available Reports
- **Revenue Trends**: Monthly/quarterly revenue analysis
- **Account Performance**: Customer billing and payment analysis
- **Service Delivery Performance**: Service type profitability
- **User Productivity**: Individual consultant billing metrics

#### KPI Metrics
- Total Revenue
- Outstanding Amounts
- Invoice Counts
- Collection Rates
- Utilization Rates

## Dashboard Widgets

### 1. Billing Overview Widget
- **Total Revenue**: Current period revenue summary
- **Pending Invoices**: Count of unpaid invoices
- **Monthly Recurring**: Subscription and recurring revenue
- **Overdue Count**: Invoices past due date

### 2. Invoice Status Widget
- **Status Summary**: Visual breakdown of invoice statuses
- **Recent Invoices**: Latest invoice activity
- **Quick Actions**: Create new invoice, view all invoices

### 3. Payment Tracking Widget
- **Monthly Received**: Payment activity for current period
- **Pending Amounts**: Outstanding payment amounts
- **Payment Methods**: Distribution of payment types
- **Recent Payments**: Latest payment activity

### 4. Billing Rates Widget
- **Rate Summary**: Active billing rates overview
- **Rate Distribution**: Visualization of rate ranges
- **Quick Management**: Add rates, edit existing rates

## Permission System

### Three-Dimensional Billing Permissions

#### 1. Functional Permissions
```
billing.full_access          # Complete billing system access
billing.admin                # Administrative billing functions
billing.manage               # General billing management
billing.view.all             # View all billing data
billing.view.account         # View account-scoped billing data
billing.view.own             # View own billing data only

# Invoice Management
invoices.create              # Create new invoices
invoices.edit                # Edit existing invoices
invoices.delete              # Delete invoices
invoices.send                # Send invoices to customers
invoices.void                # Void invoices

# Payment Management
payments.create              # Record payments
payments.edit                # Edit payment records
payments.refund              # Process refunds
payments.track               # Track payment status

# Rate Management
billing.rates.create         # Create billing rates
billing.rates.edit           # Edit billing rates
billing.rates.manage         # Manage rate configurations
```

#### 2. Widget Permissions
```
widgets.billing.overview     # Billing Overview widget
widgets.billing.invoices     # Invoice Status widget
widgets.billing.payments     # Payment Tracking widget
widgets.billing.rates        # Billing Rates widget
```

#### 3. Page Permissions
```
pages.billing.overview       # Main billing page access
pages.billing.invoices       # Invoice management page
pages.billing.payments       # Payment management page
pages.billing.rates          # Rate management page
pages.billing.reports        # Billing reports page
```

### Role Templates

#### Billing Administrator
- Complete billing system access
- All invoice, payment, and rate management capabilities
- Full reporting and analytics access
- System configuration permissions

#### Billing Manager
- Account-scoped billing management
- Invoice creation and editing
- Payment processing
- Limited rate management

#### Billing Clerk
- Basic billing operations
- Invoice creation
- Payment recording
- Read-only access to reports

## API Integration

### RESTful Endpoints

#### Invoices
```
GET    /api/billing/invoices           # List invoices
POST   /api/billing/invoices           # Create invoice
GET    /api/billing/invoices/{id}      # Get invoice details
PUT    /api/billing/invoices/{id}      # Update invoice
DELETE /api/billing/invoices/{id}      # Delete invoice
POST   /api/billing/invoices/{id}/send # Send invoice
POST   /api/billing/invoices/{id}/void # Void invoice
```

#### Payments
```
GET    /api/billing/payments           # List payments
POST   /api/billing/payments           # Record payment
GET    /api/billing/payments/{id}      # Get payment details
PUT    /api/billing/payments/{id}      # Update payment
POST   /api/billing/payments/{id}/refund # Process refund
```

#### Billing Rates
```
GET    /api/billing/rates              # List billing rates
POST   /api/billing/rates              # Create rate
PUT    /api/billing/rates/{id}         # Update rate
DELETE /api/billing/rates/{id}         # Delete rate
```

### API Token Abilities
```
billing:read                 # View billing data
billing:write                # Manage billing settings
billing:admin                # Full billing administration
invoices:read                # View invoices
invoices:write               # Create/modify invoices
invoices:send                # Send invoices
payments:read                # View payments
payments:write               # Process payments
rates:read                   # View billing rates
rates:write                  # Manage billing rates
```

## Integration with Core Systems

### 1. Timer Integration
- **Automatic Rate Assignment**: Timers automatically use appropriate billing rates
- **Time-to-Invoice**: Convert time entries to invoice line items
- **Rate Calculation**: Real-time billing amount calculation during timer operation

### 2. Service Ticket Integration
- **Addon Billing**: Attach billable addons to service tickets
- **Approval Workflows**: Multi-stage approval for ticket-based billing
- **Service Categorization**: Different billing rates for different service types

### 3. Account Hierarchy
- **Inherited Billing**: Billing configurations inherit through account hierarchies
- **Consolidated Invoicing**: Option to consolidate billing for account groups
- **Account-Specific Rates**: Custom billing rates for specific customer accounts

## Customer Portal

### Self-Service Billing Access

#### Customer Features
- **Invoice Viewing**: Access to own invoices and payment history
- **Payment Status**: Real-time payment status tracking
- **Download Capabilities**: PDF download of invoices and receipts
- **Payment Submission**: Online payment processing (when configured)

#### Navigation Structure
```
My Billing
├── Overview (billing summary)
├── My Invoices (invoice history)
└── Payment History (payment records)
```

## Database Schema

### Key Tables
- **invoices**: Invoice records with line items and totals
- **payments**: Payment tracking and processing records
- **billing_rates**: Rate management and configuration
- **addon_templates**: Predefined billing addon library
- **invoice_line_items**: Individual invoice line items
- **payment_allocations**: Payment-to-invoice matching

### Relationships
```php
Invoice hasMany InvoiceLineItems
Invoice hasMany Payments
Account hasMany BillingRates
User hasMany BillingRates
Timer belongsTo BillingRate
```

## Configuration

### Environment Variables
```
# Billing Configuration
BILLING_CURRENCY=USD
BILLING_TAX_RATE=0.0
BILLING_PAYMENT_TERMS=30
INVOICE_PREFIX=INV-
```

### Settings Management
- **Company Information**: Business details for invoices
- **Invoice Configuration**: Number formats and templates
- **Tax Settings**: Tax rates and calculations
- **Payment Terms**: Default payment terms and due dates

## Security & Compliance

### Data Protection
- **Permission-based Access**: Three-dimensional permission filtering
- **Account Isolation**: Strict account-based data separation
- **Audit Logging**: Complete audit trail for all financial operations
- **Encryption**: Sensitive financial data encryption

### Financial Controls
- **Approval Workflows**: Multi-stage approval for high-value transactions
- **User Authentication**: Strong authentication requirements for financial operations
- **Access Logging**: Detailed logging of all billing system access

## Best Practices

### Invoice Management
1. **Regular Reconciliation**: Match payments to invoices promptly
2. **Clear Terms**: Set clear payment terms and due dates
3. **Consistent Numbering**: Use systematic invoice numbering
4. **Prompt Delivery**: Send invoices immediately upon completion

### Rate Management
1. **Regular Review**: Review and update billing rates periodically
2. **Account-Specific Rates**: Use custom rates for key customers
3. **Service Differentiation**: Different rates for different service types
4. **Effective Dating**: Plan rate changes with appropriate effective dates

### Financial Reporting
1. **Regular Analysis**: Review financial reports monthly
2. **Trend Monitoring**: Track revenue trends and patterns
3. **Collection Management**: Monitor overdue invoices actively
4. **Performance Metrics**: Track key financial performance indicators

---

**Service Vault Billing System** - Complete enterprise-grade financial management for B2B service delivery platforms.

*Last Updated: August 12, 2025 - Phase 13B Complete*