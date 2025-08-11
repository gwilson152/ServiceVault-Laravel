# User Invitations API

Email-based user invitation system with automatic account assignment.

## Features
- **Email Invitations**: Send invitations via email
- **Auto Assignment**: Domain mapping integration
- **Role Templates**: Pre-assign roles to invitations
- **Expiration**: Configurable invitation expiration

## Authentication
- **Token Abilities**: `admin:write`, `users:manage`

## Endpoints

### Invitation Management
```http
GET /api/user-invitations                              # List invitations
POST /api/user-invitations                             # Send invitation
GET /api/user-invitations/{id}                         # Invitation details
PUT /api/user-invitations/{id}                         # Update invitation
DELETE /api/user-invitations/{id}                      # Cancel invitation
```

## Request/Response Format

### Send Invitation
```http
POST /api/user-invitations
{
  "email": "user@company.com",
  "name": "John Doe",
  "role_template_id": 2,
  "account_id": 123,
  "message": "Welcome to our team!"
}
```

### Invitation Response
```json
{
  "id": 1,
  "email": "user@company.com",
  "name": "John Doe",
  "status": "pending",
  "expires_at": "2024-12-08T09:00:00Z",
  "sent_at": "2024-12-01T09:00:00Z",
  "accepted_at": null,
  "role_template_id": 2,
  "account_id": 123,
  "invited_by": {
    "id": 456,
    "name": "Admin User"
  }
}
```