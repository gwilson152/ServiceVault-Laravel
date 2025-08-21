# Billing & Financial API

Complete API reference for Service Vault's billing rates, invoices, and financial management.

## Billing Rates API

### List Billing Rates
```http
GET /api/billing-rates?account_id={id}&include_rate_id={rate_id}
```

**Query Parameters**:
- `account_id`: Filter rates for specific account (uses BillingRateService hierarchy)
- `include_rate_id`: Force inclusion of specific rate (for timer commits)
- `is_default`: Filter by default status
- `active_only`: Only active rates

**Enhanced Behavior** (August 2025):
- **Hierarchical Filtering**: When `account_id` provided, returns properly prioritized rates
- **Timer-Specific Inclusion**: `include_rate_id` ensures specific rates are available even if normally filtered by hierarchy
- **Rate Disambiguation**: Timer-specific rates flagged with `is_timer_specific: true`

**Response**:
```json
{
  "data": [
    {
      "id": "rate-uuid",
      "name": "Senior Developer",
      "rate": "125.00",
      "description": "Senior development work",
      "is_default": true,
      "account_id": "account-uuid",
      "inheritance_source": "account",
      "is_timer_specific": false,
      "account": {
        "id": "account-uuid",
        "name": "Company Account"
      },
      "created_at": "2025-08-19T10:30:00Z"
    },
    {
      "id": "timer-rate-uuid",
      "name": "Critical Hourly",
      "rate": "130.00", 
      "description": "Emergency and critical support rate",
      "is_default": false,
      "account_id": null,
      "inheritance_source": "global",
      "is_timer_specific": true,
      "created_at": "2025-08-19T08:15:00Z"
    }
  ]
}
```

**Key Fields**:
- `inheritance_source`: "account", "global", or "fallback"
- `is_timer_specific`: Rate was specifically requested via `include_rate_id`
- `description`: Rate description (displayed alongside amount in UI)

### Create Billing Rate
```http
POST /api/billing-rates
Content-Type: application/json

{
  "name": "Premium Support Rate",
  "rate": "175.00",
  "account_id": "account-uuid",
  "is_default": false,
  "description": "Premium support and emergency response"
}
```

### Update Billing Rate
```http
PUT /api/billing-rates/{rate_id}
Content-Type: application/json

{
  "name": "Updated Rate Name",
  "rate": "200.00",
  "is_default": true
}
```

**Recent Change**: Currency specification removed - all rates are in system base currency.

## Invoice Management API

### List Invoices
```http
GET /api/billing/invoices?status=draft&account_id={id}&date_range=2025-08
```

**Query Parameters**:
- `status`: draft, sent, paid, overdue, cancelled
- `account_id`: Filter by account
- `date_range`: YYYY-MM format for month/year filtering
- `due_date_start`, `due_date_end`: Due date range

### Show Invoice with Line Items
```http
GET /api/billing/invoices/{invoice_id}
```

**Enhanced Response** (August 2025):
```json
{
  "data": {
    "id": "invoice-uuid",
    "invoice_number": "INV-1001",
    "account_id": "account-uuid",
    "invoice_date": "2025-08-19",
    "due_date": "2025-09-19",
    "status": "draft",
    "subtotal": "5000.00",
    "tax_rate": 8.25,
    "tax_application_mode": "non_service_items",
    "override_tax": false,
    "tax_amount": "412.50",
    "total": "5412.50",
    "inherited_tax_settings": {
      "tax_rate": 8.25,
      "tax_application_mode": "non_service_items",
      "tax_enabled": true
    },
    "line_items": [
      {
        "id": "line-item-uuid",
        "line_type": "time_entry",
        "sort_order": 0,
        "description": "Development Services",
        "quantity": "8.00",
        "unit_price": "125.00",
        "tax_amount": "0.00",
        "total_amount": "1000.00",
        "taxable": false,
        "billable": true
      },
      {
        "id": "line-item-uuid-2",
        "line_type": "addon",
        "sort_order": 1,
        "description": "Server Setup Fee",
        "quantity": "1.00",
        "unit_price": "500.00",
        "tax_amount": "41.25",
        "total_amount": "541.25",
        "taxable": true,
        "billable": true
      }
    ]
  }
}
```

**New Tax Features**:
- **tax_application_mode**: Controls which items are taxable ("all_items", "non_service_items", "custom")
- **override_tax**: Whether invoice overrides account/system tax settings
- **inherited_tax_settings**: Tax settings inherited from account/system
- **taxable**: Per-line-item tax override (null=inherit, true=taxable, false=not taxable)
- **sort_order**: For drag-and-drop reordering of line items

### Create Invoice
```http
POST /api/billing/invoices
Content-Type: application/json

{
  "account_id": "account-uuid",
  "invoice_date": "2025-08-19",
  "due_date": "2025-09-19",
  "tax_rate": 8.25,
  "tax_application_mode": "non_service_items",
  "override_tax": false,
  "line_items": [
    {
      "description": "Development Services - August",
      "quantity": 40,
      "unit_price": "125.00",
      "taxable": false,
      "time_entry_ids": ["uuid1", "uuid2", "uuid3"]
    },
    {
      "description": "Server Setup Fee", 
      "quantity": 1,
      "unit_price": "500.00",
      "taxable": true,
      "ticket_addon_ids": ["addon-uuid"]
    }
  ],
  "notes": "Thank you for your business"
}
```

### Invoice Line Item Management

#### Update Line Item Taxable Status
```http
PUT /api/billing/invoices/{invoice_id}/line-items/{line_item_id}
Content-Type: application/json

{
  "taxable": true
}
```

**Taxable Field Values**:
- `true`: Item is explicitly taxable
- `false`: Item is explicitly not taxable  
- `null`: Item inherits from invoice/account/system settings

#### Reorder Line Items
```http
POST /api/billing/invoices/{invoice_id}/line-items/reorder
Content-Type: application/json

{
  "line_items": [
    {"id": "item-1-uuid", "sort_order": 0},
    {"id": "item-2-uuid", "sort_order": 1},
    {"id": "item-3-uuid", "sort_order": 2}
  ]
}
```

#### Add Separator Line Item
```http
POST /api/billing/invoices/{invoice_id}/separators
Content-Type: application/json

{
  "title": "Additional Services",
  "position": 2
}
```

**Response**:
```json
{
  "data": {
    "id": "separator-uuid",
    "line_type": "separator",
    "sort_order": 2,
    "description": "Additional Services",
    "quantity": "0.00",
    "unit_price": "0.00",
    "total_amount": "0.00",
    "taxable": null,
    "billable": false
  }
}
```

### Generate Invoice from Time Entries
```http
POST /api/invoices/generate-from-entries
Content-Type: application/json

{
  "account_id": "account-uuid",
  "start_date": "2025-08-01",
  "end_date": "2025-08-31",
  "include_approved_only": true,
  "group_by": "billing_rate",
  "terms": 30
}
```

## Ticket Addons API

### List Ticket Addons
```http
GET /api/ticket-addons?status=approved&category=service&ticket_id={id}
```

**Query Parameters**:
- `status`: pending, approved, rejected
- `category`: product, service, expense, license, hardware, software, other  
- `ticket_id`: Filter by specific ticket
- `account_id`: Filter by account
- `page`, `per_page`: Pagination

### Ticket Addon Approval Workflow
```http
POST /api/ticket-addons/{addon_id}/approve
POST /api/ticket-addons/{addon_id}/reject
POST /api/ticket-addons/{addon_id}/unapprove

{
  "notes": "Approved for billing"
}
```

**Unapproval System** (August 2025):
- **Unapprove Endpoint**: `POST /api/ticket-addons/{addon_id}/unapprove` with optional notes
- **Invoice Protection**: Only approved addons not yet invoiced can be unapproved  
- **Status Reset**: Returns addon to "pending" status and clears approval metadata
- **Permission Required**: Same permissions as approval workflow
- **API Response Fields**: Includes `is_invoiced` and `can_unapprove` boolean fields

**Enhanced Response Format**:
```json
{
  "data": {
    "id": "addon-uuid",
    "name": "Server Setup Fee",
    "description": "Initial server configuration",
    "category": "service",
    "unit_price": "500.00",
    "quantity": "1.00", 
    "total_amount": "500.00",
    "status": "approved",
    "billable": true,
    "is_invoiced": false,
    "can_unapprove": true,
    "ticket": {
      "id": "ticket-uuid",
      "ticket_number": "TKT-1001",
      "title": "Server Setup"
    },
    "added_by": {
      "id": "user-uuid",
      "name": "John Doe"
    }
  }
}
```

## Unbilled Items & Approval Workflow

### Get Unbilled Items
```http
GET /api/billing/unbilled-items?account_id={id}&include_unapproved=true
```

**Query Parameters**:
- `account_id`: Filter items for specific account (optional - omit for all accounts)
- `include_unapproved`: Include pending approval items
- `type`: Filter by item type (time_entry, ticket_addon)

**Enhanced All Accounts Support** (August 2025):
- **Omit account_id**: Returns unbilled items across all accounts
- **Account-specific filtering**: Provide account_id for focused review
- **Permission-aware**: Results filtered by user's account access permissions

**Response**:
```json
{
  "data": {
    "approved": {
      "time_entries": [
        {
          "id": "entry-uuid",
          "description": "Development work",
          "duration": 480,
          "billing_rate": "125.00",
          "amount": "1000.00",
          "status": "approved",
          "account": {
            "id": "account-uuid",
            "name": "Company Account"
          }
        }
      ],
      "ticket_addons": [
        {
          "id": "addon-uuid", 
          "description": "Server setup",
          "quantity": 1,
          "unit_price": "500.00",
          "amount": "500.00",
          "status": "approved",
          "account": {
            "id": "account-uuid",
            "name": "Company Account"
          }
        }
      ]
    },
    "unapproved": {
      "time_entries": [],
      "ticket_addons": []
    },
    "totals": {
      "approved_amount": "1500.00",
      "pending_amount": "0.00",
      "total_amount": "1500.00"
    },
    "account_breakdown": [
      {
        "account_id": "account-uuid",
        "account_name": "Company Account",
        "approved_amount": "1500.00",
        "pending_amount": "0.00"
      }
    ]
  }
}
```

**New Fields**:
- `account`: Account information included with each item for all-accounts view
- `account_breakdown`: Summary by account when viewing all accounts

### Enhanced Approval Wizard API

**Access Points**:
- **Time & Addons Page**: `/time-and-addons` with "Launch Approval Wizard" button
- **Billing Page**: `/billing` with integrated approval workflow access
- **Account Selection**: Choose specific account or "All Accounts" for comprehensive review

**All Accounts Approval Workflow**:
```http
GET /api/billing/unbilled-items?include_unapproved=true
```

Response includes items from all accounts user has access to, with account context for each item.

## Payment Tracking API

### Record Payment
```http
POST /api/invoices/{invoice_id}/payments
Content-Type: application/json

{
  "amount": "5500.00",
  "payment_date": "2025-08-20",
  "payment_method": "bank_transfer",
  "reference": "TXN-123456",
  "notes": "Payment received via wire transfer"
}
```

### Payment Methods
```http
GET /api/payment-methods
```

**Available Methods**:
- `cash`
- `check`
- `bank_transfer`
- `credit_card`
- `paypal`
- `stripe`
- `other`

## Financial Reporting API

### Revenue Summary
```http
GET /api/reports/revenue?period=monthly&year=2025&account_id={id}
```

**Query Parameters**:
- `period`: daily, weekly, monthly, quarterly, yearly
- `year`: Target year (defaults to current)
- `account_id`: Filter by account
- `compare_previous`: Include previous period comparison

**Response**:
```json
{
  "current_period": {
    "total_revenue": "45000.00",
    "invoiced_amount": "50000.00", 
    "paid_amount": "40000.00",
    "outstanding_amount": "10000.00"
  },
  "previous_period": {
    "total_revenue": "38000.00",
    "growth_percentage": 18.4
  },
  "breakdown": [
    {
      "month": "2025-08",
      "revenue": "15000.00",
      "invoices": 12,
      "average_invoice": "1250.00"
    }
  ]
}
```

### Time Entry Billing Analysis
```http
GET /api/reports/time-billing?start_date=2025-08-01&end_date=2025-08-31
```

**Response**:
```json
{
  "summary": {
    "total_hours": 320,
    "billable_hours": 280,
    "billable_percentage": 87.5,
    "total_amount": "35000.00"
  },
  "by_billing_rate": [
    {
      "rate_id": "rate-uuid",
      "rate_name": "Senior Developer",
      "hourly_rate": "125.00",
      "hours_logged": 120,
      "total_amount": "15000.00"
    }
  ],
  "by_account": [
    {
      "account_id": "account-uuid",
      "account_name": "Company Account",
      "hours": 80,
      "amount": "10000.00"
    }
  ]
}
```

## Tax Settings API

### Get Tax Configuration
```http
GET /api/settings/tax
```

**Response**:
```json
{
  "data": {
    "enabled": true,
    "default_rate": 8.25,
    "default_application_mode": "non_service_items"
  }
}
```

**Tax Application Modes**:
- `all_items`: Tax applies to both time entries (services) and addons (products) - time entries are taxable by default
- `non_service_items`: Tax applies only to addons/products, not time entries/services - time entries are never taxed
- `custom`: Tax application determined by individual item settings - only explicitly marked items are taxable

### Update Tax Configuration
```http
PUT /api/settings/tax
Content-Type: application/json

{
  "enabled": true,
  "default_rate": 6.0,
  "default_application_mode": "non_service_items"
}
```

**Important**: Tax settings require `system.configure` permission and manual save via settings UI.

**Tax Inheritance Hierarchy**:
1. **Invoice Override**: If `override_tax` is true, use invoice-specific settings
2. **Account Settings**: Account-specific tax configuration (future feature)
3. **System Defaults**: Global tax settings configured in `/settings`

### Tax Calculation Logic

**Time Entry Taxability**:
```javascript
// Determine if time entry is taxable (simplified logic)
function isTimeEntryTaxable(timeEntry, taxSettings, invoiceMode) {
  // 1. Explicit setting takes precedence
  if (timeEntry.taxable !== null) {
    return timeEntry.taxable;
  }
  
  // 2. Apply tax application mode directly
  switch (invoiceMode || taxSettings.default_application_mode) {
    case 'all_items':
      return true; // Time entries are taxable by default in this mode
    case 'non_service_items':
      return false; // Time entries are never taxed in this mode
    case 'custom':
      return timeEntry.taxable === true; // Only explicitly marked as taxable
    default:
      return true; // Default to all items behavior
  }
}
```

## Account Financial Settings

### Get Account Billing Settings
```http
GET /api/accounts/{account_id}/billing-settings
```

**Response**:
```json
{
  "default_terms": 30,
  "default_billing_rate_id": "rate-uuid",
  "auto_invoice": false,
  "billing_contact": {
    "name": "Finance Department",
    "email": "billing@company.com"
  },
  "tax_settings": {
    "tax_rate": "8.25",
    "tax_number": "TX123456789"
  }
}
```

### Update Billing Settings
```http
PUT /api/accounts/{account_id}/billing-settings
Content-Type: application/json

{
  "default_terms": 45,
  "default_billing_rate_id": "new-rate-uuid",
  "auto_invoice": true,
  "billing_contact": {
    "name": "Updated Contact",
    "email": "newbilling@company.com"
  }
}
```

## Billing Rate System

Service Vault features a **simplified two-tier billing rate hierarchy** for clear and manageable pricing:

### Rate Priority (Highest to Lowest)

1. **Account-Specific Rates** - Custom rates for individual accounts
2. **Global Default Rates** - System-wide fallback rates

### Key Implementation Notes

- **Smart Auto-Selection**: Time entry dialogs automatically select billing rates using hierarchy: Account Default → Global Default → First Available
- **Unified Components**: Shared `BillingRateModal` component works across settings and account contexts
- **Visual Hierarchy**: Color-coded badges (Blue for account rates, Green for global rates)
- **Inheritance System**: Child accounts inherit parent account rates unless overridden
- **Unified Selector**: `UnifiedSelector` type="billing-rate" with group headers and rate hierarchy
- **API Integration**: `/api/billing-rates?account_id={id}` returns account + inherited + global rates

### Auto-Selection Logic (UnifiedTimeEntryDialog)

1. **Account Selection Trigger**: When user selects an account, `autoSelectBillingRate()` is called
2. **Rate Priority Search**: Searches for rates in this order:
   - Account-specific rates marked as `is_default: true`
   - Global rates (no account_id) marked as `is_default: true` 
   - First available rate if no defaults found
3. **Seamless UX**: Rate selection happens automatically without user intervention

## Permissions

### Required Permissions

**Billing Rate Management**:
- `billing.view` - View billing rates
- `billing.manage` - Create/edit billing rates
- `billing.act_as_agent` - Can be assigned billing responsibility

**Invoice Management**:
- `invoices.view` - View invoices
- `invoices.manage` - Create/edit invoices
- `payments.write` - Record payments

**Financial Reporting**:
- `admin.read` - View financial reports
- `admin.write` - Access all financial data

## Error Handling

### Billing Rate Errors
```json
{
  "message": "The given data was invalid",
  "errors": {
    "rate": ["Rate must be greater than 0"],
    "account_id": ["Account not found or access denied"]
  }
}
```

### Invoice Generation Errors
```json
{
  "message": "Cannot generate invoice",
  "error_code": "NO_BILLABLE_ENTRIES",
  "details": "No approved time entries found for the specified period"
}
```

### Payment Recording Errors
```json
{
  "message": "Payment amount exceeds invoice total",
  "invoice_total": "5000.00",
  "payment_amount": "5500.00",
  "error_code": "PAYMENT_EXCEEDS_INVOICE"
}
```

## Integration Examples

### JavaScript/Axios
```javascript
// Create billing rate
const response = await axios.post('/api/billing-rates', {
  name: 'Consultation Rate',
  rate: '150.00',
  account_id: accountId,
  is_default: false
})

// Generate monthly invoice
const invoice = await axios.post('/api/invoices/generate-from-entries', {
  account_id: accountId,
  start_date: '2025-08-01',
  end_date: '2025-08-31',
  include_approved_only: true
})
```

### cURL Examples
```bash
# List account billing rates
curl -X GET "https://servicevault.com/api/billing-rates?account_id=uuid" \
  -H "Authorization: Bearer ${TOKEN}"

# Record payment
curl -X POST "https://servicevault.com/api/invoices/uuid/payments" \
  -H "Authorization: Bearer ${TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{
    "amount": "5000.00",
    "payment_date": "2025-08-20",
    "payment_method": "bank_transfer"
  }'
```