# Accounts API

Simplified business account management for customer and partner organizations.

## Overview

The Accounts API provides streamlined business account management with a simplified, flat structure. Each account represents a business entity or organization that can have users, tickets, and billing configurations.

## Endpoints

### List Accounts

```http
GET /api/accounts
```

Returns all accounts in the system.

**Response:**
```json
{
  "data": [
    {
      "id": "uuid",
      "name": "Acme Corporation",
      "display_name": "Acme Corporation",
      "account_type": "customer",
      "account_type_display": "Customer",
      "users_count": 5,
      "is_active": true,
      "status": "active",
      "contact_person": "John Doe",
      "email": "john@acme.com",
      "phone": "+1-555-0123",
      "website": "https://acme.com",
      "address": "123 Business St",
      "city": "Business City",
      "state": "BC",
      "postal_code": "12345",
      "country": "USA",
      "created_at": "2025-01-01T00:00:00Z",
      "updated_at": "2025-01-01T00:00:00Z"
    }
  ],
  "meta": {
    "total": 10,
    "per_page": 50,
    "current_page": 1
  }
}
```

**Key Features:**
- Simple flat account structure
- `users_count` shows the number of users assigned to the account
- `display_name` provides a consistent account identifier

### Get Account Selector Data

```http
GET /api/accounts/selector
```

Returns accounts formatted for selector components (dropdowns, search).

**Response:**
```json
{
  "data": [
    {
      "id": "uuid",
      "name": "Acme Corporation",
      "display_name": "Acme Corporation",
      "account_type": "customer",
      "is_active": true
    }
  ],
  "meta": {
    "count": 10,
    "for_selector": true
  }
}
```

### Create Account

```http
POST /api/accounts
```

**Request Body:**
```json
{
  "name": "New Account",
  "account_type": "customer",
  "description": "Account description",
  "contact_person": "Jane Smith",
  "email": "jane@newaccount.com",
  "phone": "+1-555-0456",
  "website": "https://newaccount.com",
  "address": "123 Business St",
  "city": "Business City",
  "state": "BC",
  "postal_code": "12345",
  "country": "USA",
  "billing_address": "123 Business St",
  "billing_city": "Business City",
  "billing_state": "BC",
  "billing_postal_code": "12345",
  "billing_country": "USA",
  "tax_id": "12-3456789",
  "notes": "Important client notes",
  "is_active": true
}
```

**Response:**
```json
{
  "message": "Account created successfully",
  "data": {
    "id": "new-uuid",
    "name": "New Account",
    "display_name": "New Account",
    "account_type": "customer",
    "is_active": true,
    "users_count": 0,
    "created_at": "2025-01-01T00:00:00Z",
    "updated_at": "2025-01-01T00:00:00Z"
  }
}
```

### Get Account Details

```http
GET /api/accounts/{id}
```

Returns detailed account information including hierarchy relationships.

### Update Account

```http
PUT /api/accounts/{id}
```

**Request Body:** Same structure as create, all fields optional.

### Delete Account

```http
DELETE /api/accounts/{id}
```

**Validation:**
- Cannot delete accounts with assigned users
- Cannot delete accounts with active tickets
- Returns 422 if validation fails

## Account Features

### Account Structure
- Simple flat structure with no parent-child relationships
- Each account is independent and self-contained
- Direct user-to-account assignments

### Visual Indicators
The frontend displays accounts using:
- Consistent account type badges
- Status indicators (active/inactive)
- User count displays
- Clear account identification

### Account Types
- `customer`: Active customer account
- `prospect`: Potential customer
- `partner`: Business partner
- `internal`: Internal company account

### Business Rules
1. Each account operates independently
2. Users are assigned to specific accounts
3. Account names must be unique within the system
4. Account types determine available features and permissions
5. Billing rates can be account-specific or inherit global defaults

## Field Definitions

### Required Fields
- `name`: Account display name (unique)
- `account_type`: One of `customer`, `prospect`, `partner`, `internal`

### Optional Fields
- `description`: Account description or notes
- `contact_person`: Primary contact name
- `email`: Primary contact email
- `phone`: Primary contact phone
- `website`: Account website URL
- `address`, `city`, `state`, `postal_code`, `country`: Primary address
- `billing_address`, `billing_city`, `billing_state`, `billing_postal_code`, `billing_country`: Billing address
- `tax_id`: Tax identification number
- `notes`: Internal notes about the account
- `is_active`: Account status (default: true)

## Related APIs
- [Users API](users.md) - User-to-account assignments
- [Tickets API](tickets.md) - Account-based ticket management
- [Billing Rates API](billing-rates.md) - Account-specific billing configuration
- [Domain Mappings API](domain-mappings.md) - Automatic account assignment