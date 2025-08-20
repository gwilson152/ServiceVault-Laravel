# Import API Reference

Complete API documentation for Service Vault's PostgreSQL database import system.

## Overview

The Import API provides endpoints for managing PostgreSQL database connections, creating import profiles, and executing data migration jobs with real-time monitoring.

## Authentication

All import API endpoints require authentication and proper permissions:

```bash
# Session-based authentication (web app)
Cookie: laravel_session=...

# Token-based authentication (API clients)  
Authorization: Bearer {api_token}
```

## Required Permissions

```php
// System-level permissions
'system.import'              // Basic import access
'system.import.configure'    // Manage import profiles
'system.import.execute'      // Execute import jobs
'import.profiles.manage'     // Profile CRUD operations
'import.jobs.execute'        // Job execution
'import.jobs.monitor'        // Job monitoring
```

## Import Profiles

### List Import Profiles

```bash
GET /api/import/profiles
```

**Query Parameters:**
- `type` (string): Filter by profile type (`freescout-postgres`)
- `active` (boolean): Filter by active status
- `search` (string): Search by profile name
- `per_page` (integer): Results per page (default: 15)

**Response:**
```json
{
  "data": [
    {
      "id": "uuid",
      "name": "FreeScout Production",
      "type": "freescout-postgres",
      "host": "database.example.com",
      "port": 5432,
      "database": "freescout_prod",
      "username": "db_user",
      "ssl_mode": "prefer",
      "description": "Production FreeScout database",
      "is_active": true,
      "created_by": {
        "id": "uuid",
        "name": "Admin User"
      },
      "last_tested_at": "2025-08-20T10:30:00Z",
      "last_test_result": {
        "success": true,
        "message": "Connection successful",
        "database_info": {
          "version": "PostgreSQL 14.9",
          "database": "freescout_prod",
          "table_count": 45
        }
      },
      "created_at": "2025-08-20T09:00:00Z",
      "updated_at": "2025-08-20T10:30:00Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 3
  }
}
```

### Create Import Profile

```bash
POST /api/import/profiles
```

**Request Body:**
```json
{
  "name": "FreeScout Production",
  "type": "freescout-postgres",
  "host": "database.example.com", 
  "port": 5432,
  "database": "freescout_prod",
  "username": "db_user",
  "password": "secure_password",
  "ssl_mode": "prefer",
  "description": "Production FreeScout database import",
  "connection_options": {},
  "is_active": true
}
```

**Response:**
```json
{
  "message": "Import profile created successfully",
  "profile": {
    "id": "uuid",
    "name": "FreeScout Production",
    // ... profile data
  }
}
```

### Get Import Profile

```bash
GET /api/import/profiles/{profile_id}
```

**Response:**
```json
{
  "id": "uuid",
  "name": "FreeScout Production",
  // ... full profile data with relationships
  "import_jobs": [
    {
      "id": "uuid", 
      "status": "completed",
      "records_imported": 1250,
      "started_at": "2025-08-20T10:00:00Z"
    }
  ],
  "import_mappings": [
    {
      "id": "uuid",
      "source_table": "users",
      "destination_table": "users",
      "field_mappings": {...},
      "transformation_rules": {...}
    }
  ]
}
```

### Update Import Profile

```bash
PUT /api/import/profiles/{profile_id}
```

**Request Body:** Same as create, password optional

**Response:**
```json
{
  "message": "Import profile updated successfully",
  "profile": {
    // ... updated profile data
  }
}
```

### Delete Import Profile

```bash
DELETE /api/import/profiles/{profile_id}
```

**Response:**
```json
{
  "message": "Import profile deleted successfully"
}
```

**Error Response (Running Jobs):**
```json
{
  "message": "Cannot delete profile with running import jobs",
  "running_jobs": 2
}
```

## Connection Testing

### Test New Connection

```bash
POST /api/import/profiles/test-connection
```

**Request Body:**
```json
{
  "host": "database.example.com",
  "port": 5432,
  "database": "freescout_prod",
  "username": "db_user", 
  "password": "secure_password",
  "ssl_mode": "prefer"
}
```

**Response (Success):**
```json
{
  "connection_test": {
    "success": true,
    "message": "Connection successful",
    "database_info": {
      "version": "PostgreSQL 14.9",
      "database": "freescout_prod", 
      "table_count": 45,
      "size": "250 MB"
    }
  }
}
```

**Response (Failure):**
```json
{
  "connection_test": {
    "success": false,
    "message": "Connection failed: could not connect to server",
    "error": "Connection timed out"
  }
}
```

### Test Existing Profile Connection

```bash
POST /api/import/profiles/{profile_id}/test-connection
```

**Response:** Same format as test new connection

## Database Schema

### Get Database Schema

```bash
GET /api/import/profiles/{profile_id}/schema
```

**Response:**
```json
{
  "schema": {
    "tables": [
      {
        "name": "users",
        "row_count": 15,
        "columns": [
          {
            "name": "id",
            "type": "integer",
            "nullable": false,
            "primary_key": true
          },
          {
            "name": "first_name", 
            "type": "character varying(255)",
            "nullable": true
          }
        ]
      }
    ],
    "relationships": [
      {
        "table": "conversations",
        "column": "user_id",
        "referenced_table": "users",
        "referenced_column": "id"
      }
    ]
  },
  "server_info": {
    "version": "PostgreSQL 14.9",
    "encoding": "UTF8",
    "collation": "en_US.UTF-8"
  }
}
```

## Import Preview

### Preview Import Data

```bash
GET /api/import/profiles/{profile_id}/preview
```

**Response (FreeScout Profile):**
```json
{
  "users": {
    "title": "FreeScout Staff → Service Vault Users",
    "description": "Admin and user accounts will become Service Vault agent users",
    "sample_data": [
      {
        "id": 1,
        "first_name": "John",
        "last_name": "Doe", 
        "email": "john@company.com",
        "role": 1,
        "created_at": "2024-03-15T10:30:00Z"
      }
    ],
    "total_count": 15
  },
  "customers": {
    "title": "FreeScout Customers → Service Vault Accounts + Users",
    "description": "Each customer becomes an account with a user record",
    "sample_data": [
      {
        "id": 1,
        "first_name": "Jane",
        "last_name": "Smith",
        "email": "jane@client.com",
        "company": "Client Corp"
      }
    ],
    "total_count": 250
  },
  "conversations": {
    "title": "FreeScout Conversations → Service Vault Tickets", 
    "description": "Support conversations become tickets with status mapping and resolved relationships",
    "sample_data": [
      {
        "id": 1,
        "number": 1001,
        "subject": "Login Issue",
        "status": 1,
        "status_name": "Active (Open)",
        "customer_id": 1,
        "user_id": 1,
        "user_name": "John Doe",
        "created_at": "2024-03-20T14:15:00Z"
      }
    ],
    "total_count": 1200
  },
  "threads": {
    "title": "FreeScout Threads → Service Vault Comments",
    "description": "Conversation messages and notes become ticket comments", 
    "sample_data": [
      {
        "id": 1,
        "conversation_id": 1,
        "type": 1,
        "body": "I'm having trouble logging in...",
        "user_id": null,
        "customer_id": 1
      }
    ],
    "total_count": 3500
  }
}
```

## Field Mapping Management

### Get Import Profile Field Mappings

```bash
GET /api/import/profiles/{profile_id}/mappings
```

**Response:**
```json
[
  {
    "id": "uuid",
    "profile_id": "uuid",
    "source_table": "users",
    "destination_table": "users",
    "field_mappings": {
      "name": {
        "type": "combine_fields",
        "fields": ["first_name", "last_name"],
        "separator": " "
      },
      "email": {
        "type": "direct_mapping",
        "source_field": "email"
      },
      "user_type": {
        "type": "static_value",
        "static_value": "agent"
      },
      "id": {
        "type": "integer_to_uuid",
        "source_field": "id",
        "prefix": "freescout_user_"
      }
    },
    "import_order": 1,
    "is_active": true,
    "created_at": "2025-08-20T12:00:00Z",
    "updated_at": "2025-08-20T12:00:00Z"
  },
  {
    "id": "uuid",
    "profile_id": "uuid",
    "source_table": "conversations",
    "destination_table": "tickets",
    "field_mappings": {
      "title": {
        "type": "direct_mapping",
        "source_field": "subject"
      },
      "ticket_number": {
        "type": "integer_to_uuid",
        "source_field": "number",
        "prefix": "FS"
      },
      "status": {
        "type": "transform_function",
        "source_field": "status",
        "transform_function": "status_mapping"
      }
    },
    "import_order": 3,
    "is_active": true,
    "created_at": "2025-08-20T12:00:00Z",
    "updated_at": "2025-08-20T12:00:00Z"
  }
]
```

### Save Field Mappings

```bash
POST /api/import/profiles/{profile_id}/mappings
```

**Request Body:**
```json
{
  "mappings": [
    {
      "source_table": "users",
      "destination_table": "users",
      "field_mappings": {
        "name": {
          "type": "combine_fields",
          "fields": ["first_name", "last_name"],
          "separator": " "
        },
        "email": {
          "type": "direct_mapping",
          "source_field": "email"
        },
        "user_type": {
          "type": "static_value",
          "static_value": "agent"
        }
      },
      "import_order": 1
    },
    {
      "source_table": "conversations",
      "destination_table": "tickets", 
      "field_mappings": {
        "title": {
          "type": "direct_mapping",
          "source_field": "subject"
        },
        "description": {
          "type": "transform_function",
          "source_field": "preview",
          "transform_function": "trim"
        }
      },
      "import_order": 3
    }
  ]
}
```

**Response:**
```json
{
  "message": "Field mappings saved successfully",
  "mappings": [
    // ... array of created mapping objects
  ]
}
```

### Field Mapping Types

The field mapping system supports multiple transformation types:

#### 1. Direct Mapping
```json
{
  "type": "direct_mapping",
  "source_field": "email"
}
```
Maps one source field directly to destination field.

#### 2. Combine Fields  
```json
{
  "type": "combine_fields",
  "fields": ["first_name", "last_name"],
  "separator": " "
}
```
Combines multiple source fields with a separator.

#### 3. Static Value
```json
{
  "type": "static_value",
  "static_value": "agent"
}
```
Sets a fixed value for all records.

#### 4. Integer to UUID
```json
{
  "type": "integer_to_uuid",
  "source_field": "id",
  "prefix": "freescout_user_"
}
```
Converts integer IDs to deterministic UUIDs with prefix.

#### 5. Transform Functions
```json
{
  "type": "transform_function",
  "source_field": "email_address",
  "transform_function": "lowercase"
}
```
Applies data transformations. Available functions:
- `lowercase` - Convert to lowercase
- `uppercase` - Convert to uppercase  
- `trim` - Remove whitespace
- `date_format` - Format date strings
- `boolean_convert` - Convert to boolean

## Import Jobs

### List Import Jobs

```bash
GET /api/import/jobs
```

**Query Parameters:**
- `profile_id` (string): Filter by profile ID
- `status` (string|array): Filter by status (`pending`, `running`, `completed`, `failed`, `cancelled`)
- `date_from` (date): Filter jobs from date
- `date_to` (date): Filter jobs to date
- `search` (string): Search by profile name
- `per_page` (integer): Results per page (default: 15)

**Response:**
```json
{
  "data": [
    {
      "id": "uuid",
      "profile": {
        "id": "uuid",
        "name": "FreeScout Production",
        "type": "freescout-postgres"
      },
      "status": "completed",
      "progress_percentage": 100,
      "current_operation": null,
      "records_processed": 5000,
      "records_imported": 4850,
      "records_failed": 25,
      "records_skipped": 125,
      "import_options": {
        "batch_size": 100
      },
      "started_at": "2025-08-20T10:00:00Z",
      "completed_at": "2025-08-20T10:45:00Z",
      "duration": 2700,
      "errors": null,
      "metadata": {
        "source_counts": {
          "users": 15,
          "customers": 250, 
          "conversations": 1200,
          "threads": 3500
        }
      },
      "created_by": {
        "id": "uuid",
        "name": "Admin User"
      },
      "created_at": "2025-08-20T10:00:00Z"
    }
  ]
}
```

### Create Import Job

```bash
POST /api/import/jobs
```

**Request Body:**
```json
{
  "profile_id": "uuid",
  "options": {
    "overwrite_existing": false,
    "skip_validation": false,
    "batch_size": 100,
    "selected_tables": ["users", "customers", "conversations", "threads"],
    "import_filters": {
      "date_from": "2024-01-01",
      "date_to": "2024-12-31",
      "ticket_status": "1",
      "limit": 1000,
      "active_users_only": true
    }
  }
}
```

**Import Filter Options:**
- `date_from` (string): Import only records created after this date (YYYY-MM-DD)
- `date_to` (string): Import only records created before this date (YYYY-MM-DD)  
- `ticket_status` (string): For conversations - filter by status ("1"=active, "2"=pending, "3"=closed)
- `limit` (integer): Maximum records per data type (useful for testing)
- `active_users_only` (boolean): Import only active users (exclude disabled accounts)

**Response:**
```json
{
  "message": "Import job started successfully",
  "job": {
    "id": "uuid",
    "status": "running", 
    "progress_percentage": 0,
    "current_operation": "Initializing import...",
    // ... job details
  }
}
```

### Get Import Job

```bash
GET /api/import/jobs/{job_id}
```

**Response:** Full job details with profile and creator relationships

### Execute Import

```bash
POST /api/import/profiles/{profile_id}/import
```

**Response:**
```json
{
  "message": "Import job started successfully",
  "job": {
    "id": "uuid",
    "status": "running",
    // ... job details
  }
}
```

### Get Job Status

```bash
GET /api/import/jobs/{job_id}/status
```

**Response:**
```json
{
  "job_id": "uuid",
  "status": "running",
  "progress_percentage": 45,
  "current_operation": "Processing conversations → tickets",
  "records_processed": 2250,
  "records_imported": 2180,
  "records_skipped": 35,
  "records_failed": 12,
  "started_at": "2025-08-20T10:00:00Z",
  "duration": 1800,
  "estimated_completion": "2025-08-20T10:40:00Z"
}
```

### Cancel Import Job

```bash
POST /api/import/jobs/{job_id}/cancel
```

**Response:**
```json
{
  "message": "Import job cancelled successfully", 
  "job": {
    "id": "uuid",
    "status": "cancelled",
    // ... updated job details
  }
}
```

### Get Job Statistics

```bash
GET /api/import/jobs/stats
```

**Query Parameters:**
- `date_from` (date): Statistics from date
- `date_to` (date): Statistics to date

**Response:**
```json
{
  "stats": {
    "total_jobs": 25,
    "completed_jobs": 20,
    "failed_jobs": 3,
    "running_jobs": 1,
    "cancelled_jobs": 1,
    "total_records_imported": 125000,
    "total_records_failed": 450,
    "average_duration": 2340
  },
  "recent_jobs": [
    {
      "id": "uuid",
      "profile_name": "FreeScout Production",
      "status": "completed",
      "records_imported": 4850,
      "records_failed": 25,
      "started_at": "2025-08-20T10:00:00Z",
      "completed_at": "2025-08-20T10:45:00Z", 
      "duration": 2700
    }
  ]
}
```

## Error Handling

### Common HTTP Status Codes

- `200` - Success
- `201` - Created (new profile/job)
- `400` - Bad Request (validation errors, invalid status)
- `401` - Unauthorized (missing/invalid authentication)
- `403` - Forbidden (insufficient permissions)
- `404` - Not Found (profile/job not found)
- `422` - Unprocessable Entity (validation errors)
- `500` - Internal Server Error

### Error Response Format

```json
{
  "message": "Validation failed",
  "errors": {
    "host": ["The host field is required."],
    "port": ["The port must be between 1 and 65535."]
  }
}
```

### Connection Error Format

```json
{
  "message": "Connection test failed",
  "connection_error": {
    "success": false,
    "message": "Connection failed: SQLSTATE[08006]",
    "error": "could not connect to server: Connection refused"
  }
}
```

## Rate Limiting

Import API endpoints are rate limited:

- **Profile Management**: 60 requests per minute
- **Connection Testing**: 10 requests per minute (to prevent abuse)
- **Job Monitoring**: 120 requests per minute (for real-time updates)

## WebSocket Integration

Real-time job updates are available via Laravel Echo:

```javascript
// Subscribe to job updates
Echo.private(`import.job.${jobId}`)
  .listen('ImportJobUpdated', (event) => {
    console.log('Job progress:', event.progress_percentage);
  });
```

**Available Events:**
- `ImportJobStarted` - Job execution began
- `ImportJobUpdated` - Progress update
- `ImportJobCompleted` - Job finished successfully  
- `ImportJobFailed` - Job encountered errors
- `ImportJobCancelled` - Job was cancelled

---

*Import API Reference | Service Vault Documentation | Updated: August 20, 2025*