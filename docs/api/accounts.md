# Accounts API

Business account management with hierarchical structure support.

## Overview

The Accounts API provides comprehensive business account management with support for hierarchical account structures, allowing for complex organizational relationships and subsidiary management.

## Endpoints

### List Accounts (Hierarchical)

```http
GET /api/accounts
```

Returns all accounts in hierarchical order, with parent accounts listed before their children.

**Response:**
```json
{
  "data": [
    {
      "id": "uuid",
      "name": "Parent Company Inc",
      "display_name": "Parent Company Inc",
      "company_name": "Parent Company Inc",
      "account_type": "customer",
      "account_type_display": "Customer",
      "hierarchy_level": 0,
      "has_children": true,
      "children_count": 2,
      "users_count": 5,
      "is_active": true,
      "status": "active",
      "contact_person": "John Doe",
      "email": "john@parent.com",
      "phone": "+1-555-0123",
      "created_at": "2025-01-01T00:00:00Z",
      "updated_at": "2025-01-01T00:00:00Z"
    },
    {
      "id": "uuid",
      "name": "Subsidiary A",
      "display_name": "Subsidiary A",
      "company_name": "Subsidiary A LLC",
      "account_type": "customer",
      "account_type_display": "Customer", 
      "hierarchy_level": 1,
      "has_children": false,
      "children_count": 0,
      "users_count": 3,
      "parent_id": "parent-uuid",
      "is_active": true,
      "status": "active",
      "created_at": "2025-01-01T00:00:00Z",
      "updated_at": "2025-01-01T00:00:00Z"
    }
  ],
  "meta": {
    "total": 10,
    "hierarchical": true
  }
}
```

**Key Features:**
- Accounts are returned in hierarchical order (parent → child → grandchild)
- `hierarchy_level` indicates the depth in the hierarchy (0 = root account)
- `children_count` shows how many direct subsidiaries the account has
- `users_count` shows the number of users assigned to the account

### Get Hierarchical Selector Data

```http
GET /api/accounts/selector/hierarchical
```

Returns accounts structured for hierarchical selector components (dropdowns, trees).

**Response:**
```json
{
  "data": [
    {
      "id": "uuid",
      "name": "Parent Company Inc",
      "parent_id": null,
      "depth": 0,
      "has_children": true,
      "children": [
        {
          "id": "uuid",
          "name": "Subsidiary A",
          "parent_id": "parent-uuid",
          "depth": 1,
          "has_children": false,
          "children": []
        }
      ]
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
  "company_name": "New Account LLC",
  "account_type": "customer",
  "parent_id": "parent-uuid",
  "description": "Account description",
  "contact_person": "Jane Smith",
  "email": "jane@newaccount.com",
  "phone": "+1-555-0456",
  "address": "123 Business St",
  "city": "Business City",
  "state": "BC",
  "postal_code": "12345",
  "country": "USA",
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
    "hierarchy_level": 1,
    // ... other account fields
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
- Cannot delete accounts with active children
- Cannot delete accounts with assigned users
- Returns 422 if validation fails

## Hierarchy Features

### Hierarchy Levels
- **Level 0**: Root accounts (no parent)
- **Level 1+**: Subsidiary accounts (have parent)
- Unlimited nesting depth supported

### Visual Indicators
The frontend displays hierarchical relationships using:
- Tree-style connectors (└─, │)
- Different icons for root vs subsidiary accounts
- Color coding (blue for root, gray for subsidiaries)
- Indentation based on hierarchy level

### Account Types
- `customer`: Active customer account
- `prospect`: Potential customer
- `partner`: Business partner
- `internal`: Internal company account

### Business Rules
1. Root accounts can have unlimited subsidiaries
2. Subsidiaries can have their own subsidiaries
3. Users are assigned to specific accounts in the hierarchy
4. Permissions can be inherited through hierarchy (when implemented)

## Related APIs
- [Users API](users.md) - User-to-account assignments
- [Roles & Permissions API](roles-permissions.md) - Account-based permissions
- [Domain Mappings API](domain-mappings.md) - Automatic account assignment