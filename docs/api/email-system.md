# Email System API

REST API endpoints for managing Service Vault's application-wide email processing system.

## Table of Contents

- [Authentication](#authentication)
- [Email System Configuration](#email-system-configuration)
- [Domain Mappings](#domain-mappings)
- [Email Administration](#email-administration)
- [Error Handling](#error-handling)

## Authentication

All email system API endpoints require authentication. Use one of the following methods:

**Session-based (Web UI)**
```javascript
// Automatic with CSRF token
headers: {
  'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
}
```

**API Token**
```bash
curl -H "Authorization: Bearer your-api-token" \
     -H "Content-Type: application/json" \
     https://your-domain/api/email-system/config
```

**Required Permissions**
- Email configuration: `system.configure`
- Email monitoring: Admin role or appropriate permissions

## Email System Configuration

### Get Configuration
```http
GET /api/email-system/config
```

**Response**
```json
{
  "id": 1,
  "configuration_name": "Default Email System Configuration",
  "system_active": true,
  
  "incoming_enabled": true,
  "incoming_provider": "outlook",
  "incoming_host": "outlook.office365.com",
  "incoming_port": 993,
  "incoming_username": "support@company.com",
  "incoming_encryption": "ssl",
  "incoming_folder": "INBOX",
  
  "outgoing_enabled": true,
  "outgoing_provider": "smtp",
  "outgoing_host": "smtp.office365.com",
  "outgoing_port": 587,
  "outgoing_username": "support@company.com",
  "outgoing_encryption": "tls",
  
  "from_address": "support@company.com",
  "from_name": "Company Support",
  "reply_to_address": "support@company.com",
  
  "auto_create_tickets": true,
  "process_commands": true,
  "send_confirmations": true,
  "max_retries": 3,
  
  "timestamp_source": "service_vault",
  "timestamp_timezone": "preserve",
  
  "created_at": "2025-08-25T23:22:50.000000Z",
  "updated_at": "2025-08-25T23:55:04.000000Z"
}
```

### Update Configuration
```http
PUT /api/email-system/config
```

**Request Body**
```json
{
  "system_active": true,
  
  "incoming_enabled": true,
  "incoming_provider": "outlook",
  "incoming_host": "outlook.office365.com",
  "incoming_port": 993,
  "incoming_username": "support@company.com",
  "incoming_password": "app-password",
  "incoming_encryption": "ssl",
  "incoming_folder": "INBOX",
  
  "outgoing_enabled": true,
  "outgoing_provider": "smtp",
  "outgoing_host": "smtp.office365.com",
  "outgoing_port": 587,
  "outgoing_username": "support@company.com",
  "outgoing_password": "app-password",
  "outgoing_encryption": "tls",
  
  "from_address": "support@company.com",
  "from_name": "Company Support",
  "reply_to_address": "support@company.com",
  
  "auto_create_tickets": true,
  "process_commands": true,
  "send_confirmations": true,
  "max_retries": 3,
  
  "timestamp_source": "service_vault",
  "timestamp_timezone": "preserve"
}
```

**Validation Rules**
```
system_active: boolean
incoming_enabled: boolean
incoming_provider: nullable|string|in:imap,gmail,outlook,exchange
incoming_host: nullable|string|max:255
incoming_port: nullable|integer|min:1|max:65535
incoming_username: nullable|string|max:255
incoming_password: nullable|string|max:255
incoming_encryption: nullable|in:tls,ssl,starttls,none
incoming_folder: nullable|string|max:100

outgoing_enabled: boolean
outgoing_provider: nullable|string|in:smtp,gmail,outlook,ses,sendgrid,postmark,mailgun
outgoing_host: nullable|string|max:255
outgoing_port: nullable|integer|min:1|max:65535
outgoing_username: nullable|string|max:255
outgoing_password: nullable|string|max:255
outgoing_encryption: nullable|in:tls,ssl,starttls,none

from_address: nullable|email|max:255
from_name: nullable|string|max:255
reply_to_address: nullable|email|max:255

auto_create_tickets: boolean
process_commands: boolean
send_confirmations: boolean
max_retries: integer|min:0|max:10

timestamp_source: string|in:service_vault,original
timestamp_timezone: string|in:preserve,convert_local,convert_utc
```

**Response**
```json
{
  "message": "Email system configuration updated successfully",
  "config": {
    // Updated configuration object
  }
}
```

### Test Configuration
```http
POST /api/email-system/test
```

**Request Body**
```json
{
  "test_data": {
    // Optional: specific configuration to test
    // If omitted, tests current saved configuration
    "incoming_host": "outlook.office365.com",
    "incoming_port": 993,
    "incoming_username": "support@company.com",
    "incoming_password": "test-password"
  }
}
```

**Response**
```json
{
  "success": true,
  "tests": {
    "incoming_connection": {
      "passed": true,
      "message": "Successfully connected to incoming server"
    },
    "incoming_authentication": {
      "passed": true,
      "message": "Authentication successful"
    },
    "incoming_folder_access": {
      "passed": true,
      "message": "INBOX folder accessible"
    },
    "outgoing_connection": {
      "passed": true,
      "message": "Successfully connected to outgoing server"
    },
    "outgoing_authentication": {
      "passed": true,
      "message": "SMTP authentication successful"
    }
  },
  "overall_status": "passed",
  "tested_at": "2025-08-26T10:30:00.000000Z"
}
```

### Get System Status
```http
GET /api/email-system/status
```

**Response**
```json
{
  "system_active": true,
  "fully_configured": true,
  "incoming_enabled": true,
  "outgoing_enabled": true,
  "incoming_provider": "outlook",
  "outgoing_provider": "smtp",
  "domain_mappings_count": 5,
  "active_domain_mappings": 4,
  "last_updated": "2025-08-25T23:55:04.000000Z"
}
```

### Microsoft 365 Folder Management

#### Get M365 Folders
```http
POST /api/settings/email/m365-folders
```

**Request Body**
```json
{
  "m365_tenant_id": "12345678-1234-1234-1234-123456789012",
  "m365_client_id": "87654321-4321-4321-4321-210987654321", 
  "m365_client_secret": "your-client-secret",
  "m365_mailbox": "support@company.com"
}
```

**Response**
```json
{
  "success": true,
  "folders": [
    {
      "id": "AAMkAGU3ZGExOTBjLTU2NzMtNDg5MS05MTg4LWQ2NmFlMmI0NjhhMwAuAAAAAACcoLtXNdwG",
      "name": "Inbox",
      "original_name": "Inbox",
      "full_path": "Inbox",
      "parent_id": null,
      "total_count": 25,
      "unread_count": 3,
      "level": 0,
      "sort_key": "Inbox"
    },
    {
      "id": "AAMkAGU3ZGExOTBjLTU2NzMtNDg5MS05MTg4LWQ2NmFlMmI0NjhhMwAuAAAAAACcoLtXNdwH",
      "name": "Archive",
      "original_name": "Archive", 
      "full_path": "Archive",
      "parent_id": null,
      "total_count": 150,
      "unread_count": 0,
      "level": 0,
      "sort_key": "Archive"
    },
    {
      "id": "AAMkAGU3ZGExOTBjLTU2NzMtNDg5MS05MTg4LWQ2NmFlMmI0NjhhMwAuAAAAAACcoLtXNdwI",
      "name": "  Archive-2023",
      "original_name": "Archive-2023",
      "full_path": "Archive/Archive-2023", 
      "parent_id": "AAMkAGU3ZGExOTBjLTU2NzMtNDg5MS05MTg4LWQ2NmFlMmI0NjhhMwAuAAAAAACcoLtXNdwH",
      "total_count": 50,
      "unread_count": 0,
      "level": 1,
      "sort_key": "Archive/Archive-2023"
    }
  ]
}
```

**Features**
- **Hierarchical Structure**: Folders include `level` field for visual hierarchy (0=root, 1=child)
- **Message Counts**: Both `total_count` and `unread_count` for each folder
- **Parent-Child Relationships**: `parent_id` field links child folders to parents  
- **Search-Friendly**: `full_path` provides complete folder path for filtering
- **Visual Display**: `name` field includes indentation for immediate UI use

**Error Response**
```json
{
  "success": false,
  "message": "Failed to get M365 folders: Authentication failed",
  "errors": {
    "authentication": "Invalid tenant ID, client ID, or client secret"
  }
}
```

## Domain Mappings

### List Domain Mappings
```http
GET /api/domain-mappings
```

**Query Parameters**
```
page: integer (pagination)
per_page: integer (max 100)
is_active: boolean (filter active/inactive)
account_id: uuid (filter by account)
pattern_type: string (domain|email|wildcard)
```

**Response**
```json
{
  "data": [
    {
      "id": 1,
      "domain_pattern": "@acme.com",
      "pattern_type": "domain",
      "account_id": "123e4567-e89b-12d3-a456-426614174000",
      "account": {
        "id": "123e4567-e89b-12d3-a456-426614174000",
        "name": "Acme Corporation"
      },
      "default_assigned_user_id": "987fcdeb-51a2-43d1-9f4e-123456789abc",
      "default_assigned_user": {
        "id": "987fcdeb-51a2-43d1-9f4e-123456789abc",
        "name": "John Smith",
        "email": "john@acme.com"
      },
      "default_category": "General Support",
      "default_priority": "medium",
      "auto_create_tickets": true,
      "send_auto_reply": false,
      "auto_reply_template": null,
      "is_active": true,
      "priority": 100,
      "created_at": "2025-08-25T12:00:00.000000Z",
      "updated_at": "2025-08-25T12:00:00.000000Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 1,
    "per_page": 25,
    "total": 1
  }
}
```

### Create Domain Mapping
```http
POST /api/domain-mappings
```

**Request Body**
```json
{
  "domain_pattern": "@acme.com",
  "pattern_type": "domain",
  "account_id": "123e4567-e89b-12d3-a456-426614174000",
  "default_assigned_user_id": "987fcdeb-51a2-43d1-9f4e-123456789abc",
  "default_category": "General Support",
  "default_priority": "medium",
  "auto_create_tickets": true,
  "send_auto_reply": false,
  "auto_reply_template": "Thank you for contacting us...",
  "is_active": true,
  "priority": 100,
  "custom_rules": {
    "keywords": ["urgent", "asap"],
    "auto_escalate": true
  }
}
```

**Validation Rules**
```
domain_pattern: required|string|max:255|unique:email_domain_mappings,domain_pattern,NULL,id,pattern_type,{pattern_type}
pattern_type: required|in:domain,email,wildcard
account_id: required|uuid|exists:accounts,id
default_assigned_user_id: nullable|uuid|exists:users,id
default_category: nullable|string|max:255
default_priority: nullable|in:low,medium,high,urgent
auto_create_tickets: boolean
send_auto_reply: boolean
auto_reply_template: nullable|string
is_active: boolean
priority: integer|min:1|max:1000
custom_rules: nullable|json
```

### Update Domain Mapping
```http
PUT /api/domain-mappings/{id}
```

### Delete Domain Mapping
```http
DELETE /api/domain-mappings/{id}
```

### Preview Domain Mapping
```http
POST /api/domain-mappings/preview
```

**Request Body**
```json
{
  "email_address": "support@acme.com"
}
```

**Response**
```json
{
  "matched": true,
  "mapping": {
    "id": 1,
    "domain_pattern": "@acme.com",
    "pattern_type": "domain",
    "account": {
      "id": "123e4567-e89b-12d3-a456-426614174000",
      "name": "Acme Corporation"
    }
  },
  "assignment_preview": {
    "account_name": "Acme Corporation",
    "assigned_user": "John Smith",
    "category": "General Support",
    "priority": "medium",
    "auto_create_ticket": true
  }
}
```

## Email Administration

### Dashboard Data
```http
GET /api/email-admin/dashboard
```

**Query Parameters**
```
time_range: string (24h|7d|30d|90d) - default: 24h
service_type: string (incoming|outgoing|both) - optional filter
statuses: string (comma-separated status list) - optional filter
```

**Response**
```json
{
  "success": true,
  "data": {
    "system_health": {
      "system_active": true,
      "fully_configured": true,
      "incoming_enabled": true,
      "outgoing_enabled": true,
      "incoming_provider": "outlook",
      "outgoing_provider": "smtp",
      "domain_mappings_count": 5,
      "active_domain_mappings": 4,
      "last_updated": "2025-08-25T23:55:04.000000Z"
    },
    "overview": {
      "total_emails_processed": 1247,
      "successful_processing": 1225,
      "failed_processing": 22,
      "tickets_created": 856,
      "comments_added": 391,
      "commands_executed": 89,
      "system_configured": true,
      "domain_mappings_active": 4
    },
    "performance": {
      "avg_processing_time_ms": 1200,
      "max_processing_time_ms": 8300,
      "min_processing_time_ms": 245,
      "success_rate": 98.5,
      "hourly_volume": {
        "2025-08-26T08:00:00Z": 45,
        "2025-08-26T09:00:00Z": 67,
        "2025-08-26T10:00:00Z": 34
      }
    },
    "domain_mappings": {
      "total_mappings": 5,
      "active_mappings": 4,
      "by_type": {
        "domain": 3,
        "email": 1,
        "wildcard": 1
      },
      "recent_mappings": 2
    },
    "queue_health": {
      "pending_jobs": 3,
      "failed_jobs": 0,
      "processed_today": 156,
      "oldest_pending": "2025-08-26T10:28:00Z"
    },
    "recent_activity": [
      {
        "email_id": "em_123456789",
        "status": "processed",
        "from_address": "customer@acme.com",
        "subject": "Support Request - Login Issue",
        "created_at": "2025-08-26T10:30:00Z",
        "ticket_number": "TK-789",
        "account_name": "Acme Corporation"
      }
    ],
    "alerts": [
      {
        "type": "info",
        "message": "Email system is active and configured",
        "details": "All services operational"
      }
    ]
  },
  "time_range": "24h",
  "generated_at": "2025-08-26T10:35:00.000000Z"
}
```

### Processing Logs
```http
GET /api/email-admin/processing-logs
```

**Query Parameters**
```
page: integer
per_page: integer (max 100)
status: string (pending|processing|processed|failed|retry)
direction: string (incoming|outgoing)
account_id: uuid
has_commands: boolean
command_success: boolean
date_from: date (Y-m-d)
date_to: date (Y-m-d)
search: string (searches subject, from_address, email_id)
```

**Response**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "email_id": "em_123456789",
      "status": "processed",
      "direction": "incoming",
      "from_address": "customer@acme.com",
      "to_addresses": ["support@company.com"],
      "subject": "Support Request - Login Issue",
      "message_id": "<msg123@acme.com>",
      "received_at": "2025-08-26T10:30:00Z",
      "processing_duration_ms": 1200,
      "created_new_ticket": true,
      "ticket_comment_id": "comment_456",
      "commands_processed": 1,
      "commands_executed_count": 1,
      "commands_failed_count": 0,
      "command_processing_success": true,
      "error_message": null,
      "retry_count": 0,
      "next_retry_at": null,
      "account": {
        "id": "123e4567-e89b-12d3-a456-426614174000",
        "name": "Acme Corporation"
      },
      "ticket": {
        "id": "ticket_789",
        "ticket_number": "TK-789",
        "subject": "Support Request - Login Issue"
      },
      "created_at": "2025-08-26T10:30:00Z",
      "updated_at": "2025-08-26T10:30:15Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 5,
    "per_page": 25,
    "total": 125
  }
}
```

### Processing Log Detail
```http
GET /api/email-admin/processing-logs/{emailId}
```

**Response**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "email_id": "em_123456789",
    "status": "processed",
    "direction": "incoming",
    "from_address": "customer@acme.com",
    "to_addresses": ["support@company.com"],
    "subject": "Support Request - Login Issue",
    "message_id": "<msg123@acme.com>",
    "received_at": "2025-08-26T10:30:00Z",
    "processing_duration_ms": 1200,
    "raw_email_content": "[Raw email content...]",
    "parsed_content": {
      "body_text": "I'm having trouble logging in...",
      "body_html": "<p>I'm having trouble logging in...</p>",
      "attachments": []
    },
    "created_new_ticket": true,
    "ticket_comment_id": "comment_456",
    "commands_processed": 1,
    "commands_executed_count": 1,
    "commands_failed_count": 0,
    "command_processing_success": true,
    "command_results": {
      "priority": {
        "command": "priority:high",
        "executed": true,
        "result": "Priority updated to high"
      }
    },
    "error_message": null,
    "retry_count": 0,
    "next_retry_at": null,
    "account": {
      "id": "123e4567-e89b-12d3-a456-426614174000",
      "name": "Acme Corporation"
    },
    "ticket": {
      "id": "ticket_789",
      "ticket_number": "TK-789",
      "subject": "Support Request - Login Issue",
      "comments": [
        {
          "id": "comment_456",
          "content": "Email received from customer@acme.com",
          "created_at": "2025-08-26T10:30:00Z"
        }
      ]
    },
    "created_at": "2025-08-26T10:30:00Z",
    "updated_at": "2025-08-26T10:30:15Z"
  }
}
```

### Retry Processing
```http
POST /api/email-admin/retry-processing
```

**Request Body**
```json
{
  "email_ids": ["em_123456789", "em_987654321"],
  "force": false
}
```

**Response**
```json
{
  "success": true,
  "message": "Retry initiated for 2 emails",
  "retried": 2,
  "failed": 0
}
```

### Queue Status
```http
GET /api/email-admin/queue-status
```

**Response**
```json
{
  "success": true,
  "data": {
    "queues": {
      "email-incoming": {
        "pending": 2,
        "failed": 0,
        "status": "healthy"
      },
      "email-outgoing": {
        "pending": 1,
        "failed": 0,
        "status": "healthy"
      },
      "email-processing": {
        "pending": 0,
        "failed": 1,
        "status": "healthy"
      },
      "default": {
        "pending": 5,
        "failed": 0,
        "status": "healthy"
      }
    },
    "failed_jobs": {
      "total": 1,
      "by_queue": {
        "email-processing": 1
      },
      "oldest": {
        "id": 456,
        "queue": "email-processing",
        "failed_at": "2025-08-26T09:45:00Z"
      }
    },
    "workers": {
      "active_workers": 3,
      "status": "running"
    },
    "health_check": {
      "healthy": true,
      "issues": []
    }
  },
  "checked_at": "2025-08-26T10:35:00.000000Z"
}
```

### Clear Failed Jobs
```http
DELETE /api/email-admin/failed-jobs
```

**Query Parameters**
```
older_than_hours: integer (1-720, default 24)
queue: string (specific queue name, optional)
```

**Response**
```json
{
  "success": true,
  "message": "Cleared 5 failed jobs",
  "cleared_count": 5
}
```

## Error Handling

### Common HTTP Status Codes

**200 OK** - Request successful
**400 Bad Request** - Invalid request parameters
**401 Unauthorized** - Authentication required
**403 Forbidden** - Insufficient permissions
**404 Not Found** - Resource not found
**422 Unprocessable Entity** - Validation errors
**500 Internal Server Error** - Server error

### Error Response Format

```json
{
  "success": false,
  "error": "Validation failed",
  "message": "The given data was invalid",
  "errors": {
    "incoming_host": ["The incoming host field is required when incoming enabled is true."],
    "from_address": ["The from address field must be a valid email address."]
  }
}
```

### Validation Errors

Domain mapping validation errors:
```json
{
  "success": false,
  "error": "Validation failed", 
  "errors": {
    "domain_pattern": ["The domain pattern has already been taken for this pattern type."],
    "account_id": ["The selected account id is invalid."]
  }
}
```

### Email System Errors

Configuration test failure:
```json
{
  "success": false,
  "tests": {
    "incoming_connection": {
      "passed": false,
      "message": "Connection failed: Unable to connect to outlook.office365.com:993"
    },
    "incoming_authentication": {
      "passed": false,
      "message": "Authentication failed: Invalid credentials"
    }
  },
  "overall_status": "failed",
  "tested_at": "2025-08-26T10:30:00.000000Z"
}
```

---

*Last Updated: August 26, 2025 - Application-Wide Email System API*