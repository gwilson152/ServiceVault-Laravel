# Time Entries API

Time tracking data with approval workflows and billing integration.

## Features
- **Timer Integration**: Created from committed timers
- **Approval Workflow**: Pending, approved, rejected states
- **Billing Integration**: Billable time with rates and costs
- **Bulk Operations**: Mass approve/reject capabilities
- **Account Scoping**: Time entries tied to accounts

## Authentication
- **Token Abilities**: `time-entries:read|write`, `admin:read|write`

## Core Endpoints

### Time Entry Management
```http
GET /api/time-entries?account_id=123&status=pending     # List time entries
POST /api/time-entries                                  # Create time entry
GET /api/time-entries/{id}                              # Time entry details
PUT /api/time-entries/{id}                              # Update time entry
DELETE /api/time-entries/{id}                           # Delete time entry
```

### Approval Workflow
```http
POST /api/time-entries/{id}/approve                     # Approve time entry
POST /api/time-entries/{id}/reject                      # Reject time entry
POST /api/time-entries/bulk/approve                     # Bulk approve
POST /api/time-entries/bulk/reject                      # Bulk reject
```

### Statistics & Reports
```http
GET /api/time-entries/stats/approvals                   # Approval statistics
GET /api/time-entries/stats/recent                      # Recent time entries
```

## User Time Entries

### User-Specific Endpoints
```http
GET /api/users/{id}/time-entries                        # User's time entries
```

## Response Format
```json
{
  "id": 1,
  "user_id": 123,
  "account_id": 456,
  "ticket_id": 789,
  "description": "Work description",
  "duration": 7200,
  "billable": true,
  "rate": 75.00,
  "total_cost": 150.00,
  "status": "pending",
  "date": "2024-12-01",
  "approved_at": null,
  "approved_by": null,
  "created_at": "2024-12-01T09:00:00Z",
  "user": { "id": 123, "name": "John Doe" },
  "account": { "id": 456, "name": "Company" },
  "ticket": { "id": 789, "title": "Task Title" }
}
```

## Bulk Operation Request
```json
{
  "time_entry_ids": [1, 2, 3],
  "reason": "Approved for billing"
}
```