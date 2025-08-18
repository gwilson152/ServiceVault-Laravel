# Billing API Documentation

Complete RESTful API for Service Vault's billing and financial management system, including invoices, payments, billing rates, and financial reporting.

## Authentication

All billing API endpoints require authentication via Laravel Sanctum:

```bash
# Session-based authentication (web app)
Cookie: laravel_session=...

# Token-based authentication (API clients)
Authorization: Bearer {your-api-token}
```

### Required Token Abilities

```bash
billing:read          # View billing data
billing:write         # Manage billing settings
billing:admin         # Full billing administration
invoices:read         # View invoices
invoices:write        # Create/modify invoices
invoices:send         # Send invoices
invoices:void         # Void invoices
payments:read         # View payments
payments:write        # Process payments
payments:refund       # Process refunds
rates:read            # View billing rates
rates:write           # Manage billing rates
addons:read           # View addon templates
addons:write          # Manage addon templates
```

## Base URL

```
https://your-domain.com/api/billing
```

## Response Format

All responses follow a consistent JSON structure:

```json
{
  "data": { ... },
  "meta": { ... },
  "links": { ... }
}
```

### Error Responses

```json
{
    "message": "Error description",
    "errors": {
        "field": ["Validation error message"]
    }
}
```

## Invoices API

### List Invoices

```http
GET /api/billing/invoices
```

#### Query Parameters

| Parameter    | Type    | Description                                                              |
| ------------ | ------- | ------------------------------------------------------------------------ |
| `account_id` | UUID    | Filter by account ID                                                     |
| `status`     | string  | Filter by status (`draft`, `pending`, `sent`, `paid`, `overdue`, `void`) |
| `date_from`  | date    | Filter invoices from date (YYYY-MM-DD)                                   |
| `date_to`    | date    | Filter invoices to date (YYYY-MM-DD)                                     |
| `per_page`   | integer | Items per page (default: 15, max: 100)                                   |
| `page`       | integer | Page number                                                              |
| `sort`       | string  | Sort field (`issue_date`, `due_date`, `total_amount`)                    |
| `order`      | string  | Sort order (`asc`, `desc`)                                               |

#### Response

```json
{
    "data": [
        {
            "id": "uuid",
            "invoice_number": "INV-000001",
            "account": {
                "id": "uuid",
                "name": "Acme Corporation"
            },
            "issue_date": "2024-08-01",
            "due_date": "2024-08-31",
            "status": "sent",
            "subtotal": "1250.00",
            "tax_amount": "109.38",
            "total_amount": "1359.38",
            "paid_amount": "0.00",
            "outstanding_amount": "1359.38",
            "is_overdue": false,
            "line_items": [
                {
                    "id": "uuid",
                    "description": "Professional Services - Development",
                    "quantity": 10.5,
                    "unit_price": "125.00",
                    "line_total": "1312.50"
                }
            ],
            "payments": [],
            "created_by": {
                "id": "uuid",
                "name": "John Smith"
            },
            "created_at": "2024-08-01T09:00:00Z",
            "updated_at": "2024-08-01T09:00:00Z"
        }
    ],
    "meta": {
        "current_page": 1,
        "from": 1,
        "last_page": 3,
        "per_page": 15,
        "to": 15,
        "total": 42
    },
    "links": {
        "first": "/api/billing/invoices?page=1",
        "last": "/api/billing/invoices?page=3",
        "prev": null,
        "next": "/api/billing/invoices?page=2"
    }
}
```

### Create Invoice

```http
POST /api/billing/invoices
```

#### Request Body

```json
{
    "account_id": "uuid",
    "issue_date": "2024-08-01",
    "due_date": "2024-08-31",
    "notes": "Monthly service invoice",
    "line_items": [
        {
            "description": "Professional Services - Development",
            "quantity": 10.5,
            "unit_price": "125.00"
        },
        {
            "description": "Server Setup",
            "quantity": 1,
            "unit_price": "500.00"
        }
    ]
}
```

#### Response

```json
{
    "data": {
        "id": "uuid",
        "invoice_number": "INV-000042",
        "account_id": "uuid",
        "issue_date": "2024-08-01",
        "due_date": "2024-08-31",
        "status": "draft",
        "subtotal": "1812.50",
        "tax_amount": "158.59",
        "total_amount": "1971.09",
        "notes": "Monthly service invoice",
        "line_items": [
            {
                "id": "uuid",
                "description": "Professional Services - Development",
                "quantity": 10.5,
                "unit_price": "125.00",
                "line_total": "1312.50"
            },
            {
                "id": "uuid",
                "description": "Server Setup",
                "quantity": 1,
                "unit_price": "500.00",
                "line_total": "500.00"
            }
        ],
        "created_at": "2024-08-01T10:30:00Z"
    }
}
```

### Get Invoice

```http
GET /api/billing/invoices/{id}
```

### Update Invoice

```http
PUT /api/billing/invoices/{id}
```

### Delete Invoice

```http
DELETE /api/billing/invoices/{id}
```

### Send Invoice

```http
POST /api/billing/invoices/{id}/send
```

#### Request Body

```json
{
    "email_to": "billing@client.com",
    "email_cc": ["manager@client.com"],
    "email_subject": "Invoice INV-000001 from Service Vault",
    "email_message": "Please find attached your invoice for services rendered."
}
```

### Void Invoice

```http
POST /api/billing/invoices/{id}/void
```

#### Request Body

```json
{
    "reason": "Customer requested cancellation"
}
```

### Generate Invoice from Time Entries

```http
POST /api/billing/invoices/generate-from-time-entries
```

#### Request Body

```json
{
    "account_id": "uuid",
    "time_entry_ids": ["uuid1", "uuid2", "uuid3"],
    "issue_date": "2024-08-01",
    "due_date": "2024-08-31",
    "notes": "Services for July 2024"
}
```

## Payments API

### List Payments

```http
GET /api/billing/payments
```

#### Query Parameters

| Parameter        | Type   | Description                                                     |
| ---------------- | ------ | --------------------------------------------------------------- |
| `account_id`     | UUID   | Filter by account ID                                            |
| `invoice_id`     | UUID   | Filter by invoice ID                                            |
| `status`         | string | Filter by status (`pending`, `completed`, `failed`, `refunded`) |
| `payment_method` | string | Filter by payment method                                        |
| `date_from`      | date   | Filter payments from date                                       |
| `date_to`        | date   | Filter payments to date                                         |

#### Response

```json
{
    "data": [
        {
            "id": "uuid",
            "invoice": {
                "id": "uuid",
                "invoice_number": "INV-000001"
            },
            "account": {
                "id": "uuid",
                "name": "Acme Corporation"
            },
            "amount": "1359.38",
            "payment_date": "2024-08-15",
            "payment_method": "bank_transfer",
            "status": "completed",
            "reference_number": "TXN-12345",
            "notes": "Payment received via bank transfer",
            "processed_by": {
                "id": "uuid",
                "name": "Jane Doe"
            },
            "created_at": "2024-08-15T14:30:00Z"
        }
    ]
}
```

### Record Payment

```http
POST /api/billing/payments
```

#### Request Body

```json
{
    "invoice_id": "uuid",
    "amount": "1359.38",
    "payment_date": "2024-08-15",
    "payment_method": "bank_transfer",
    "reference_number": "TXN-12345",
    "notes": "Payment received via bank transfer"
}
```

### Get Payment

```http
GET /api/billing/payments/{id}
```

### Update Payment

```http
PUT /api/billing/payments/{id}
```

### Process Refund

```http
POST /api/billing/payments/{id}/refund
```

#### Request Body

```json
{
    "amount": "500.00",
    "reason": "Partial service credit",
    "refund_method": "bank_transfer"
}
```

### Payment Summary

```http
GET /api/billing/payments/summary
```

#### Query Parameters

| Parameter    | Type   | Description                                    |
| ------------ | ------ | ---------------------------------------------- |
| `account_id` | UUID   | Filter by account ID                           |
| `period`     | string | Time period (`monthly`, `quarterly`, `yearly`) |

#### Response

```json
{
    "data": {
        "monthly_received": "25750.00",
        "pending_amount": "5200.00",
        "total_payments": 142,
        "average_payment": "181.34",
        "payment_methods": {
            "bank_transfer": 89,
            "credit_card": 31,
            "check": 18,
            "online": 4
        }
    }
}
```

## Billing Rates API

### List Billing Rates

```http
GET /api/billing/rates
```

#### Query Parameters

| Parameter     | Type    | Description                               |
| ------------- | ------- | ----------------------------------------- |
| `account_id`  | UUID    | Filter by account ID                      |
| `user_id`     | UUID    | Filter by user ID                         |
| `active_only` | boolean | Show only active rates                    |
| `with`        | string  | Include relationships (`account`, `user`) |

#### Response

```json
{
    "data": [
        {
            "id": "uuid",
            "name": "Senior Development",
            "description": "Senior developer hourly rate",
            "rate": "125.00",
            "account": {
                "id": "uuid",
                "name": "Acme Corporation"
            },
            "user": {
                "id": "uuid",
                "name": "John Smith"
            },
            "is_active": true,
            "effective_date": "2024-01-01",
            "created_at": "2024-01-01T00:00:00Z"
        }
    ]
}
```

### Create Billing Rate

```http
POST /api/billing/rates
```

#### Request Body

```json
{
    "name": "Senior Development",
    "description": "Senior developer hourly rate",
    "rate": "125.00",
    "account_id": "uuid",
    "user_id": "uuid",
    "effective_date": "2024-01-01"
}
```

### Get Billing Rate

```http
GET /api/billing/rates/{id}
```

### Update Billing Rate

```http
PUT /api/billing/rates/{id}
```

### Delete Billing Rate

```http
DELETE /api/billing/rates/{id}
```

## Addon Templates API

### Get Addon Categories

```http
GET /api/settings/addon-categories
```

#### Response

```json
{
    "data": {
        "product": "Product",
        "service": "Service",
        "license": "License",
        "hardware": "Hardware",
        "software": "Software",
        "expense": "Expense",
        "other": "Other"
    }
}
```

### List Addon Templates

```http
GET /api/addon-templates
```

#### Query Parameters

| Parameter       | Type    | Description                  |
| --------------- | ------- | ---------------------------- |
| `category`      | string  | Filter by category           |
| `active_only`   | boolean | Show only active templates   |
| `billable_only` | boolean | Show only billable templates |

#### Response

```json
{
    "data": [
        {
            "id": "uuid",
            "name": "Server Setup",
            "description": "Initial server configuration and setup",
            "category": "service",
            "sku": "SRV-SETUP",
            "default_unit_price": "500.00",
            "default_quantity": 1,
            "billable": true,
            "is_taxable": true,
            "is_active": true,
            "created_at": "2024-01-01T00:00:00Z"
        }
    ]
}
```

### Create Addon Template

```http
POST /api/addon-templates
```

### Get Addon Template

```http
GET /api/addon-templates/{id}
```

### Update Addon Template

```http
PUT /api/addon-templates/{id}
```

### Delete Addon Template

```http
DELETE /api/addon-templates/{id}
```

#### Request Body

```json
{
    "name": "Server Setup",
    "description": "Initial server configuration and setup",
    "category": "service",
    "sku": "SRV-SETUP",
    "default_unit_price": "500.00",
    "default_quantity": 1,
    "billable": true,
    "is_taxable": true
}
```

## Billing Overview API

### Get Billing Overview

```http
GET /api/billing/overview
```

#### Query Parameters

| Parameter    | Type   | Description                                    |
| ------------ | ------ | ---------------------------------------------- |
| `account_id` | UUID   | Filter by account ID                           |
| `period`     | string | Time period (`monthly`, `quarterly`, `yearly`) |

#### Response

```json
{
    "data": {
        "total_revenue": "125750.00",
        "pending_invoices": 12,
        "monthly_recurring": "8500.00",
        "overdue_count": 3,
        "overdue_amount": "4250.00",
        "collection_rate": 0.925,
        "average_payment_time": 18.5,
        "top_accounts": [
            {
                "account": {
                    "id": "uuid",
                    "name": "Acme Corporation"
                },
                "revenue": "45000.00",
                "invoice_count": 24
            }
        ],
        "revenue_trend": [
            { "month": "2024-06", "revenue": "22500.00" },
            { "month": "2024-07", "revenue": "28750.00" },
            { "month": "2024-08", "revenue": "31200.00" }
        ]
    }
}
```

## Reports API

### Get Financial Reports

```http
GET /api/billing/reports
```

#### Query Parameters

| Parameter     | Type   | Description                                                 |
| ------------- | ------ | ----------------------------------------------------------- |
| `report_type` | string | Type of report (`revenue`, `payments`, `accounts`, `aging`) |
| `account_id`  | UUID   | Filter by account ID                                        |
| `date_from`   | date   | Report start date                                           |
| `date_to`     | date   | Report end date                                             |
| `format`      | string | Response format (`json`, `csv`, `pdf`)                      |

#### Response

```json
{
    "data": {
        "report_type": "revenue",
        "period": {
            "start": "2024-07-01",
            "end": "2024-07-31"
        },
        "summary": {
            "total_revenue": "31200.00",
            "invoice_count": 28,
            "average_invoice": "1114.29",
            "paid_invoices": 24,
            "pending_invoices": 4
        },
        "details": [
            {
                "account": {
                    "id": "uuid",
                    "name": "Acme Corporation"
                },
                "revenue": "12500.00",
                "invoices": 6,
                "payments": 5
            }
        ]
    }
}
```

### Export Reports

```http
GET /api/billing/reports/export
```

#### Query Parameters

Same as reports API, plus:

| Parameter | Type   | Description                           |
| --------- | ------ | ------------------------------------- |
| `format`  | string | Export format (`csv`, `excel`, `pdf`) |

Returns file download with appropriate content-type headers.

## Bulk Operations

### Bulk Invoice Operations

```http
POST /api/billing/invoices/bulk
```

#### Request Body

```json
{
    "action": "send",
    "invoice_ids": ["uuid1", "uuid2", "uuid3"],
    "parameters": {
        "email_template": "default",
        "send_immediately": true
    }
}
```

#### Supported Actions

-   `send` - Send multiple invoices
-   `void` - Void multiple invoices
-   `export` - Export multiple invoices
-   `update_status` - Update status of multiple invoices

### Bulk Payment Operations

```http
POST /api/billing/payments/bulk
```

#### Request Body

```json
{
    "action": "process",
    "payment_ids": ["uuid1", "uuid2", "uuid3"],
    "parameters": {
        "process_date": "2024-08-15"
    }
}
```

## Webhooks

### Invoice Events

```json
{
  "event": "invoice.created",
  "data": {
    "invoice": { ... }
  },
  "timestamp": "2024-08-01T10:30:00Z"
}
```

### Payment Events

```json
{
  "event": "payment.completed",
  "data": {
    "payment": { ... },
    "invoice": { ... }
  },
  "timestamp": "2024-08-15T14:30:00Z"
}
```

### Available Events

-   `invoice.created`
-   `invoice.sent`
-   `invoice.paid`
-   `invoice.overdue`
-   `invoice.voided`
-   `payment.created`
-   `payment.completed`
-   `payment.failed`
-   `payment.refunded`

## Error Codes

| Code | Description                                      |
| ---- | ------------------------------------------------ |
| 400  | Bad Request - Invalid parameters                 |
| 401  | Unauthorized - Invalid or missing authentication |
| 403  | Forbidden - Insufficient permissions             |
| 404  | Not Found - Resource doesn't exist               |
| 409  | Conflict - Resource already exists               |
| 422  | Unprocessable Entity - Validation errors         |
| 429  | Too Many Requests - Rate limit exceeded          |
| 500  | Internal Server Error                            |

## Rate Limiting

-   **Authentication endpoints**: 5 requests per minute
-   **Read operations**: 100 requests per minute
-   **Write operations**: 30 requests per minute
-   **Bulk operations**: 10 requests per minute

## Best Practices

### Authentication

-   Use API tokens with minimal required abilities
-   Rotate tokens regularly
-   Store tokens securely

### Data Handling

-   Always validate data before submission
-   Use proper date formats (YYYY-MM-DD)
-   Handle decimal precision correctly for monetary values
-   Implement proper error handling for network failures

### Performance

-   Use pagination for large datasets
-   Cache frequently accessed data
-   Use bulk operations for multiple items
-   Filter results at the API level rather than client-side

### Security

-   Validate all input data
-   Use HTTPS for all API calls
-   Implement proper account-level access controls
-   Log all financial operations for audit trails

---

**Service Vault Billing API** - Complete financial management API for B2B service platforms.

_Last Updated: August 14, 2025 - Phase 15A Refinements: Dynamic Addon Categories & Settings Integration_
