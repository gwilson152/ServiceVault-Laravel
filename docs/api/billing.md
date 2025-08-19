# Billing & Financial API

Complete API reference for Service Vault's billing rates, invoices, and financial management.

## Billing Rates API

### List Billing Rates
```http
GET /api/billing-rates?account_id={id}&include_inherited=true
```

**Query Parameters**:
- `account_id`: Filter rates for specific account
- `include_inherited`: Include parent account and global rates
- `is_default`: Filter by default status
- `active_only`: Only active rates

**Response**:
```json
{
  "data": [
    {
      "id": "rate-uuid",
      "name": "Senior Developer",
      "rate": "125.00",
      "is_default": true,
      "account_id": "account-uuid",
      "account": {
        "id": "account-uuid",
        "name": "Company Account"
      },
      "created_at": "2025-08-19T10:30:00Z"
    }
  ],
  "grouped_rates": {
    "account_specific": [
      {
        "id": "rate-uuid",
        "name": "Custom Rate",
        "rate": "150.00",
        "badge_color": "blue"
      }
    ],
    "global_default": [
      {
        "id": "rate-uuid",
        "name": "Standard Rate", 
        "rate": "100.00",
        "badge_color": "green"
      }
    ]
  }
}
```

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
GET /api/invoices?status=draft&account_id={id}&date_range=2025-08
```

**Query Parameters**:
- `status`: draft, sent, paid, overdue, cancelled
- `account_id`: Filter by account
- `date_range`: YYYY-MM format for month/year filtering
- `due_date_start`, `due_date_end`: Due date range

### Create Invoice
```http
POST /api/invoices
Content-Type: application/json

{
  "account_id": "account-uuid",
  "invoice_date": "2025-08-19",
  "due_date": "2025-09-19",
  "terms": 30,
  "line_items": [
    {
      "description": "Development Services - August",
      "quantity": 40,
      "rate": "125.00",
      "amount": "5000.00",
      "time_entry_ids": ["uuid1", "uuid2", "uuid3"]
    },
    {
      "description": "Server Setup Fee",
      "quantity": 1,
      "rate": "500.00",
      "amount": "500.00",
      "ticket_addon_ids": ["addon-uuid"]
    }
  ],
  "notes": "Thank you for your business"
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