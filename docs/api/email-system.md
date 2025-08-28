# Email System API

REST API endpoints for managing Service Vault's unified email system including configuration, user management, and processing.

## Table of Contents

- [Authentication](#authentication)
- [Email System Configuration](#email-system-configuration)
- [User Management](#user-management)
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
  "email_provider": "outlook", // incoming email provider
  "incoming_host": "outlook.office365.com",
  "incoming_port": 993,
  "incoming_username": "support@company.com",
  "incoming_encryption": "ssl",
  "incoming_folder": "INBOX",
  
  "outgoing_enabled": true,
  "outgoing_provider": "smtp",
  "use_same_provider": false, // use same provider as incoming for M365
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
  
  "timestamp_source": "original",
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
  "email_provider": "outlook", // incoming email provider
  "incoming_host": "outlook.office365.com",
  "incoming_port": 993,
  "incoming_username": "support@company.com",
  "incoming_password": "app-password",
  "incoming_encryption": "ssl",
  "incoming_folder": "INBOX",
  
  "outgoing_enabled": true,
  "outgoing_provider": "smtp",
  "use_same_provider": false, // use same provider as incoming for M365
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
  
  "timestamp_source": "original",
  "timestamp_timezone": "preserve"
}
```

**Validation Rules**
```
system_active: boolean
incoming_enabled: boolean
email_provider: nullable|string|in:imap,gmail,outlook,m365
incoming_host: nullable|string|max:255
incoming_port: nullable|integer|min:1|max:65535
incoming_username: nullable|string|max:255
incoming_password: nullable|string|max:255
incoming_encryption: nullable|in:tls,ssl,starttls,none
incoming_folder: nullable|string|max:100

outgoing_enabled: boolean
outgoing_provider: nullable|string|in:smtp,gmail,outlook,m365,same_as_incoming,ses,sendgrid,postmark,mailgun
use_same_provider: boolean
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
  "email_provider": "outlook", // incoming email provider
  "outgoing_provider": "smtp",
  "use_same_provider": false, // use same provider as incoming for M365
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

## User Management

The unified email system includes API endpoints for managing email-triggered user creation and related statistics.

### Get User Management Statistics

```http
GET /api/email-system/user-stats
```

**Response**
```json
{
  "success": true,
  "data": {
    "user_overview": {
      "total_users": 1247,
      "active_users": 1156,
      "inactive_users": 91,
      "pending_approval": 15,
      "auto_created_users": 892,
      "auto_created_percentage": 71.5
    },
    "daily_metrics": {
      "users_created_today": 8,
      "users_approved_today": 12,
      "verification_emails_sent_today": 5,
      "approval_queue_size": 15
    },
    "processing_stats": {
      "email_to_user_success_rate": 96.3,
      "average_processing_time_ms": 800,
      "domain_mapping_success_rate": 98.1,
      "verification_success_rate": 89.2
    },
    "recent_activity": [
      {
        "id": 1,
        "action": "user_created",
        "user_email": "newuser@client.com",
        "account_name": "Client Corporation",
        "created_at": "2025-08-27T10:30:00Z"
      },
      {
        "id": 2,
        "action": "user_approved",
        "user_email": "pending@company.com", 
        "approved_by": "admin@internal.com",
        "created_at": "2025-08-27T09:45:00Z"
      }
    ]
  },
  "generated_at": "2025-08-27T10:35:00Z"
}
```

### Get User Management Settings

```http
GET /api/email-system/user-management-settings
```

**Response**
```json
{
  "success": true,
  "data": {
    "enable_email_processing": true,
    "auto_create_users": true,
    "require_email_verification": true,
    "require_admin_approval": false,
    "unmapped_domain_strategy": "create_generic_account",
    "default_account_id": "123e4567-e89b-12d3-a456-426614174000",
    "default_role_template_id": "987fcdeb-51a2-43d1-9f4e-123456789abc",
    "approval_workflow_enabled": false,
    "verification_email_template": "default",
    "welcome_email_enabled": true,
    "audit_user_creation": true
  }
}
```

### Update User Management Settings

```http
PUT /api/email-system/user-management-settings
```

**Request Body**
```json
{
  "enable_email_processing": true,
  "auto_create_users": true,
  "require_email_verification": true,
  "require_admin_approval": false,
  "unmapped_domain_strategy": "create_generic_account",
  "default_account_id": "123e4567-e89b-12d3-a456-426614174000",
  "default_role_template_id": "987fcdeb-51a2-43d1-9f4e-123456789abc",
  "approval_workflow_enabled": false,
  "verification_email_template": "default",
  "welcome_email_enabled": true,
  "audit_user_creation": true
}
```

**Validation Rules**
```
enable_email_processing: boolean
auto_create_users: boolean
require_email_verification: boolean
require_admin_approval: boolean
unmapped_domain_strategy: in:create_generic_account,require_manual_assignment,reject_email
default_account_id: nullable|uuid|exists:accounts,id
default_role_template_id: nullable|uuid|exists:role_templates,id
approval_workflow_enabled: boolean
verification_email_template: string|max:255
welcome_email_enabled: boolean
audit_user_creation: boolean
```

**Response**
```json
{
  "success": true,
  "message": "User management settings updated successfully",
  "data": {
    // Updated settings object
  }
}
```

### Bulk Approve Users

```http
POST /api/email-system/bulk-approve-users
```

**Request Body**
```json
{
  "user_ids": [
    "123e4567-e89b-12d3-a456-426614174000",
    "987fcdeb-51a2-43d1-9f4e-123456789abc"
  ],
  "send_welcome_email": true,
  "assign_default_role": true
}
```

**Response**
```json
{
  "success": true,
  "message": "Successfully approved 2 users",
  "data": {
    "approved_users": 2,
    "failed_approvals": 0,
    "welcome_emails_sent": 2,
    "roles_assigned": 2
  }
}
```

### Get Pending User Approvals

```http
GET /api/email-system/pending-approvals
```

**Query Parameters**
```
page: integer
per_page: integer (max 100)  
account_id: uuid (filter by account)
created_after: date (filter by creation date)
```

**Response**
```json
{
  "success": true,
  "data": [
    {
      "id": "123e4567-e89b-12d3-a456-426614174000",
      "name": "John Doe",
      "email": "john@newclient.com",
      "account": {
        "id": "987fcdeb-51a2-43d1-9f4e-123456789abc",
        "name": "New Client Corp"
      },
      "role_template": {
        "id": "456e7890-a1b2-34c5-d6e7-f8g9h0i1j2k3",
        "name": "Customer User"
      },
      "created_from_email": true,
      "email_verified": false,
      "pending_since": "2025-08-27T08:30:00Z",
      "created_at": "2025-08-27T08:30:00Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 2, 
    "per_page": 25,
    "total": 15
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

## Unified Email Processing API

### POST `/api/email-admin/process-emails`

A unified endpoint that consolidates all manual email processing operations with flexible operation modes.

**Authentication:** Required (Admin/Super Admin)

**Parameters:**

| Parameter | Type | Required | Description | Values |
|-----------|------|----------|-------------|---------|
| `operation` | string | Yes | Type of email operation to perform | `retrieve`, `reprocess`, `check_mappings`, `test_config` |
| `mode` | string | No | Processing mode (for retrieve/reprocess operations) | `test`, `process` |
| `limit` | integer | No | Maximum number of emails to process (1-50) | Default: 10 |
| `target` | string | No | Target emails for reprocessing | `failed`, `pending`, `all` |
| `provider_test` | string | No | Provider type for configuration testing | `incoming`, `outgoing`, `both` |

#### Operation Types

##### 1. Email Retrieval (`retrieve`)
Manually fetch new emails from configured email sources.

**Request:**
```json
{
  "operation": "retrieve",
  "mode": "test",
  "limit": 10
}
```

**Response:**
```json
{
  "success": true,
  "operation": "retrieve",
  "mode": "test",
  "emails_retrieved": 5,
  "emails_processed": 0,
  "test_mode": true,
  "details": {
    "new_emails": 5,
    "duplicates_skipped": 2,
    "processing_errors": 0
  },
  "message": "Email retrieval completed successfully"
}
```

##### 2. Email Reprocessing (`reprocess`)
Reprocess previously failed or queued emails.

**Request:**
```json
{
  "operation": "reprocess",
  "mode": "process",
  "target": "failed"
}
```

**Response:**
```json
{
  "success": true,
  "operation": "reprocess",
  "mode": "process",
  "emails_found": 12,
  "emails_processed": 10,
  "processing_errors": 2,
  "target": "failed",
  "details": {
    "tickets_created": 8,
    "comments_added": 2,
    "errors": [
      {
        "email_id": "uuid-123",
        "error": "Invalid sender domain"
      }
    ]
  },
  "message": "Email reprocessing completed"
}
```

##### 3. Domain Mapping Check (`check_mappings`)
Check unprocessed emails against current domain mappings and reprocess matches.

**Request:**
```json
{
  "operation": "check_mappings"
}
```

**Response:**
```json
{
  "success": true,
  "operation": "check_mappings",
  "emails_checked": 25,
  "processed_count": 8,
  "matched_mappings": [
    {
      "pattern": "support@example.com",
      "account_name": "Example Corp",
      "emails_processed": 3
    },
    {
      "pattern": "*.acme.com",
      "account_name": "Acme Industries",
      "emails_processed": 5
    }
  ],
  "message": "Domain mapping check completed"
}
```

##### 4. Configuration Testing (`test_config`)
Test email system configuration and connectivity.

**Request:**
```json
{
  "operation": "test_config",
  "provider_test": "both"
}
```

**Response:**
```json
{
  "success": true,
  "operation": "test_config",
  "provider_test": "both",
  "results": {
    "incoming": {
      "status": "success",
      "provider": "microsoft365",
      "connection_time": 1.23,
      "folders_accessible": 5,
      "test_message": "Successfully connected to Microsoft 365"
    },
    "outgoing": {
      "status": "success", 
      "provider": "smtp",
      "connection_time": 0.89,
      "test_email_sent": true,
      "test_message": "SMTP connection successful"
    }
  },
  "message": "Configuration testing completed"
}
```

#### Error Responses

**400 Bad Request:**
```json
{
  "success": false,
  "error": "Validation failed",
  "message": "The operation field is required.",
  "errors": {
    "operation": ["The operation field is required."]
  }
}
```

**422 Validation Error:**
```json
{
  "success": false,
  "error": "Invalid operation",
  "message": "The selected operation is invalid.",
  "valid_operations": ["retrieve", "reprocess", "check_mappings", "test_config"]
}
```

**500 Server Error:**
```json
{
  "success": false,
  "error": "Processing failed",
  "message": "Email processing encountered an unexpected error",
  "details": "Connection timeout to email server"
}
```

#### Legacy Endpoints (Deprecated)

The following endpoints are deprecated and will be removed in a future version. Use the unified `/api/email-admin/process-emails` endpoint instead:

- `POST /api/email-admin/manual-retrieval` → Use `operation: "retrieve"`
- `POST /api/email-admin/check-domain-mappings` → Use `operation: "check_mappings"`

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
      "email_provider": "outlook", // incoming email provider
      "outgoing_provider": "smtp",
  "use_same_provider": false, // use same provider as incoming for M365
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
      "users_auto_created": 47,
      "pending_user_approvals": 15,
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

*Last Updated: August 27, 2025 - Unified Email System API with User Management*