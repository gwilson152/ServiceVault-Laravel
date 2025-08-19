# Tickets API

Service ticket management with workflow, team assignment, and time tracking.

## Features
- **Complete Workflow**: Status transitions and approvals
- **Team Assignment**: Multi-user ticket collaboration
- **Time Tracking**: Integrated timer and time entry management
- **Addons System**: Billable additions with approval workflow
- **Comments System**: Threaded communication
- **SLA Management**: Category-based service levels

## Authentication
- **Token Abilities**: `tickets:read|write|account`, `admin:read|write`

## Core Endpoints

### Ticket Management
```http
GET /api/tickets?status=open&assigned_to=123             # List tickets
GET /api/tickets/my/assigned                             # My assigned tickets
GET /api/tickets/filter-counts                          # Status filter counts
GET /api/tickets/stats/dashboard                        # Dashboard statistics
POST /api/tickets                                       # Create ticket
GET /api/tickets/{id}                                   # Ticket details
PUT /api/tickets/{id}                                   # Update ticket
DELETE /api/tickets/{id}                                # Delete ticket
```

### Ticket Workflow
```http
POST /api/tickets/{id}/assign                          # Assign ticket
POST /api/tickets/{id}/transition                      # Change status
```

### Team Management  
```http
POST /api/tickets/{id}/team/add                        # Add team member
DELETE /api/tickets/{id}/team/{memberId}               # Remove team member
```

## Time Tracking Integration

### Ticket Timers
```http
GET /api/tickets/{id}/timers                           # All ticket timers
GET /api/tickets/{id}/timers/active                    # Active ticket timers
POST /api/tickets/{id}/timers                          # Start timer for ticket
```

### Time Entries
```http
GET /api/tickets/{id}/time-entries                     # Ticket time entries
```

## Comments System

### Ticket Comments
```http
GET /api/tickets/{id}/comments                         # List comments
POST /api/tickets/{id}/comments                        # Add comment
PUT /api/tickets/{id}/comments/{commentId}             # Update comment
DELETE /api/tickets/{id}/comments/{commentId}          # Delete comment
```

## Addons System

### Ticket Addons
```http
GET /api/ticket-addons?ticket_id=123                   # List ticket addons
POST /api/ticket-addons                                # Create addon
GET /api/ticket-addons/{id}                            # Addon details
PUT /api/ticket-addons/{id}                            # Update addon
DELETE /api/ticket-addons/{id}                         # Delete addon
POST /api/ticket-addons/{id}/approve                   # Approve addon
POST /api/ticket-addons/{id}/reject                    # Reject addon
```

## Categories & Statuses

### Ticket Categories
```http
GET /api/ticket-categories                             # List categories
GET /api/ticket-categories/options                     # Category options
GET /api/ticket-categories/sla-status                  # SLA status info
GET /api/ticket-categories/statistics                  # Category stats
POST /api/ticket-categories                            # Create category
PUT /api/ticket-categories/{id}                        # Update category
DELETE /api/ticket-categories/{id}                     # Delete category
```

### Ticket Statuses
```http
GET /api/ticket-statuses                               # List statuses
GET /api/ticket-statuses/options                       # Status options
POST /api/ticket-statuses                              # Create status
PUT /api/ticket-statuses/{id}                          # Update status
DELETE /api/ticket-statuses/{id}                       # Delete status
GET /api/ticket-statuses/{id}/transitions              # Valid transitions
```

## Addon Templates

### Predefined Addons
```http
GET /api/addon-templates                               # List templates
POST /api/addon-templates                              # Create template
GET /api/addon-templates/{id}                          # Template details
PUT /api/addon-templates/{id}                          # Update template
DELETE /api/addon-templates/{id}                       # Delete template
POST /api/addon-templates/{id}/create-addon            # Create addon from template
```

## Response Formats

### Ticket Response
```json
{
  "id": 1,
  "title": "System Issue",
  "description": "Detailed issue description",
  "status": "open",
  "priority": "high",
  "category_id": 1,
  "account_id": 123,
  "customer_id": 456,
  "agent_id": 789,
  "created_by_id": 456,
  "created_at": "2024-12-01T09:00:00Z",
  "updated_at": "2024-12-01T12:00:00Z",
  "account": { "id": 123, "name": "Company" },
  "category": { "id": 1, "name": "Technical", "sla_hours": 24 },
  "customer": { "id": 456, "name": "John Doe" },
  "agent": { "id": 789, "name": "Support Agent" },
  "team_members": [...],
  "addons": [...],
  "time_entries_sum": 14400,
  "total_cost": 750.00
}
```

### Comment Response
```json
{
  "id": 1,
  "ticket_id": 123,
  "user_id": 456,
  "content": "Comment content",
  "is_internal": false,
  "created_at": "2024-12-01T10:00:00Z",
  "user": { "id": 456, "name": "John Doe" }
}
```

### Addon Response
```json
{
  "id": 1,
  "ticket_id": 123,
  "template_id": 2,
  "title": "Additional Service",
  "description": "Service description", 
  "cost": 150.00,
  "quantity": 1,
  "total_cost": 150.00,
  "status": "pending",
  "approved_at": null,
  "approved_by": null
}
```