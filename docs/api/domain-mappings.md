# Domain Mappings API

Automatic user-to-account assignment based on email domain patterns.

## Features
- **Domain Patterns**: Wildcard matching (*.company.com)
- **Auto Assignment**: Users automatically assigned to accounts on registration
- **Priority System**: Multiple mappings with priority ordering
- **Preview System**: Test domain assignments before creating users

## Authentication
- **Token Abilities**: `accounts:manage`, `admin:read|write`

## Endpoints

### Domain Mapping Management
```http
GET /api/domain-mappings                               # List all mappings
POST /api/domain-mappings                              # Create mapping
GET /api/domain-mappings/{id}                          # Mapping details
PUT /api/domain-mappings/{id}                          # Update mapping
DELETE /api/domain-mappings/{id}                       # Delete mapping
```

### Domain Assignment Tools
```http
POST /api/domain-mappings/preview                      # Preview assignment
GET /api/domain-mappings/validate/requirements         # System validation
```

## Request/Response Format

### Create Domain Mapping
```http
POST /api/domain-mappings
{
  "domain_pattern": "*.company.com",
  "account_id": 123,
  "priority": 1,
  "is_active": true
}
```

### Preview Assignment
```http
POST /api/domain-mappings/preview
{
  "email": "user@subdomain.company.com"
}
```

**Response:**
```json
{
  "matches": [
    {
      "id": 1,
      "domain_pattern": "*.company.com",
      "account": {
        "id": 123,
        "name": "Company Inc"
      },
      "priority": 1,
      "confidence": "high"
    }
  ],
  "assigned_account": {
    "id": 123,
    "name": "Company Inc"
  }
}
```

### Domain Mapping Response
```json
{
  "id": 1,
  "domain_pattern": "*.company.com",
  "account_id": 123,
  "priority": 1,
  "is_active": true,
  "created_at": "2024-12-01T09:00:00Z",
  "account": {
    "id": 123,
    "name": "Company Inc"
  }
}
```