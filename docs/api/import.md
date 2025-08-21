# Universal Import API Reference

Complete API documentation for Service Vault's **universal database import system** supporting any PostgreSQL database with template-based configuration and visual query builder.

## Overview

The Import API provides endpoints for managing PostgreSQL database connections, applying platform templates, configuring custom queries, and executing data migration jobs with real-time monitoring.

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
// Core import permissions
'system.import'           // Access import system
'system.configure'        // Manage templates (Super Admin only)

// Page access
'pages.import.manage'     // Access /import page
```

## Import Profiles

### List Import Profiles

```bash
GET /api/import/profiles
```

**Query Parameters:**
- `database_type` (string): Filter by database type (`postgresql`)
- `status` (string): Filter by connection status
- `search` (string): Search by profile name
- `per_page` (integer): Results per page (default: 15)

**Response:**
```json
{
  "data": [
    {
      "id": "uuid",
      "name": "Production Database",
      "database_type": "postgresql",
      "host": "database.example.com",
      "port": 5432,
      "database": "production_db",
      "username": "db_user",
      "ssl_mode": "prefer",
      "description": "Production database import connection",
      "notes": "Main production database",
      "created_by": {
        "id": "uuid",
        "name": "Admin User"
      },
      "last_tested_at": "2025-08-20T10:30:00Z",
      "template_id": "uuid",
      "template": {
        "id": "uuid",
        "name": "FreeScout Platform",
        "platform": "freescout"
      },
      "last_test_result": {
        "success": true,
        "message": "Connection successful",
        "database_info": {
          "version": "PostgreSQL 14.9",
          "database": "production_db",
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
  "name": "Production Database",
  "database_type": "postgresql",
  "host": "database.example.com", 
  "port": 5432,
  "database": "production_db",
  "username": "db_user",
  "password": "secure_password",
  "ssl_mode": "prefer",
  "description": "Production database import connection",
  "notes": "Main production system"
}
```

**Response:**
```json
{
  "message": "Import profile created successfully",
  "profile": {
    "id": "uuid",
    "name": "Production Database",
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
  "name": "Production Database",
  "database_type": "postgresql",
  "template_id": "uuid",
  "template": {
    "id": "uuid",
    "name": "FreeScout Platform",
    "platform": "freescout"
  },
  "configuration": {
    "queries": {
      "customer_users": {
        "name": "Customer Users Import",
        "base_table": "customers",
        "joins": [...],
        "fields": [...],
        "filters": [...]
      }
    }
  },
  "import_jobs": [
    {
      "id": "uuid", 
      "status": "completed",
      "records_imported": 1250,
      "started_at": "2025-08-20T10:00:00Z"
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
  "database_type": "postgresql",
  "host": "database.example.com",
  "port": 5432,
  "database": "production_db",
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
      "database": "production_db", 
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

## Templates

### List Available Templates

```bash
GET /api/import/templates
```

**Response:**
```json
{
  "data": [
    {
      "id": "uuid",
      "name": "FreeScout Platform",
      "platform": "freescout",
      "database_type": "postgresql",
      "description": "Pre-configured import for FreeScout help desk platform",
      "supported_types": ["customer_users", "tickets", "time_entries", "agents"],
      "is_system": true,
      "created_at": "2025-08-20T09:00:00Z"
    },
    {
      "id": "uuid",
      "name": "Custom Database",
      "platform": "custom",
      "database_type": "postgresql",
      "description": "Flexible template for custom database structures",
      "supported_types": ["customer_users", "tickets", "time_entries", "agents", "accounts"],
      "is_system": true,
      "created_at": "2025-08-20T09:00:00Z"
    }
  ]
}
```

### Apply Template to Profile

```bash
PUT /api/import/profiles/{profile_id}/template
```

**Request Body:**
```json
{
  "template_id": "uuid"
}
```

**Response:**
```json
{
  "message": "Template applied successfully",
  "profile": {
    "id": "uuid",
    "template_id": "uuid",
    "configuration": {
      "queries": {
        "customer_users": {
          "name": "Customer Users Import",
          "base_table": "customers",
          "joins": [...],
          "fields": [...],
          "target_type": "customer_users"
        }
      }
    }
  }
}
```

## Query Builder

### Save Custom Query Configuration

```bash
POST /api/import/profiles/{profile_id}/queries
```

**Request Body:**
```json
{
  "base_table": "customers",
  "joins": [
    {
      "type": "LEFT",
      "table": "emails", 
      "on": "emails.customer_id = customers.id",
      "condition": "emails.type = 'work'"
    }
  ],
  "fields": [
    {
      "source": "customers.id",
      "target": "external_id",
      "transformation": null
    },
    {
      "source": "CONCAT(customers.first_name, ' ', customers.last_name)",
      "target": "name", 
      "transformation": "custom"
    }
  ],
  "filters": [
    {
      "field": "customers.created_at",
      "operator": ">=",
      "value": "2024-01-01"
    }
  ],
  "target_type": "customer_users"
}
```

## Import Preview

### Preview Import Data

```bash
GET /api/import/profiles/{profile_id}/preview
```

**Query Parameters:**
- `query_type` (string): Type of query to preview (`customer_users`, `tickets`, etc.)
- `limit` (integer): Number of records to preview (default: 10)

**Response:**
```json
{
  "customer_users": {
    "query": {
      "sql": "SELECT customers.id as external_id, CONCAT(customers.first_name, ' ', customers.last_name) as name...",
      "estimated_count": 250
    },
    "sample_data": [
      {
        "external_id": 1,
        "name": "Jane Smith",
        "email": "jane@client.com",
        "company": "Client Corp"
      }
    ],
    "field_mapping": {
      "external_id": "customers.id",
      "name": "CONCAT(customers.first_name, ' ', customers.last_name)",
      "email": "emails.email",
      "company": "customers.company"
    },
    "applied_filters": [
      "created_at >= '2024-01-01'",
      "emails.type = 'work'"
    ]
  }
}
```

## Visual Query Builder Components

### Get Table Schema for Builder

```bash
GET /api/import/profiles/{profile_id}/builder/tables
```

**Response:**
```json
{
  "tables": [
    {
      "name": "customers",
      "row_count": 250,
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
  "suggested_joins": [
    {
      "from_table": "customers",
      "to_table": "emails",
      "join_condition": "emails.customer_id = customers.id",
      "confidence": 0.95
    }
  ]
}
```

### Validate Query Configuration

```bash
POST /api/import/profiles/{profile_id}/builder/validate
```

**Request Body:**
```json
{
  "base_table": "customers",
  "joins": [...],
  "fields": [...],
  "filters": [...]
}
```

**Response:**
```json
{
  "valid": true,
  "generated_sql": "SELECT customers.id as external_id, CONCAT(customers.first_name, ' ', customers.last_name) as name...",
  "estimated_records": 250,
  "warnings": [],
  "errors": []
}
```

### Field Transformation Types

The visual field mapper supports multiple transformation types:

#### 1. Direct Field Mapping
```json
{
  "source": "customers.email",
  "target": "email",
  "transformation": "direct"
}
```

#### 2. Field Concatenation
```json
{
  "source": "CONCAT(customers.first_name, ' ', customers.last_name)",
  "target": "name",
  "transformation": "custom"
}
```

#### 3. Conditional Mapping (CASE)
```json
{
  "source": "CASE WHEN status = 1 THEN 'open' WHEN status = 2 THEN 'pending' ELSE 'closed' END",
  "target": "status",
  "transformation": "case_when"
}
```

#### 4. Data Transformations
```json
{
  "source": "ROUND(duration_seconds / 60)",
  "target": "duration",
  "transformation": "math"
}
```

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
        "name": "Production Database",
        "database_type": "postgresql"
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
          "customer_users": 250,
          "tickets": 1200,
          "time_entries": 850,
          "agents": 15
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

### Create Import Job with Advanced Configuration

```bash
POST /api/import/jobs
```

**Request Body:**
```json
{
  "profile_id": "uuid",
  "options": {
    "batch_size": 100,
    "timeout": 3600,
    "selected_types": ["customer_users", "tickets", "time_entries"],
    "import_filters": {
      "date_from": "2024-01-01",
      "date_to": "2024-12-31",
      "limit": 1000
    }
  },
  "mode_config": {
    "import_mode": "upsert",
    "duplicate_detection": {
      "enabled": true,
      "strategy": "fuzzy",
      "primary_fields": ["email", "external_id"],
      "secondary_fields": ["name", "phone"],
      "confidence_threshold": 85
    },
    "skip_duplicates": false,
    "update_duplicates": true,
    "source_identifier_field": "id",
    "matching_strategy": "weighted_fields"
  }
}
```

**Mode Configuration Options:**
- `import_mode`: `"create"`, `"update"`, or `"upsert"`
- `duplicate_detection`: Detection strategy configuration
- `skip_duplicates`: Skip records when duplicates found
- `update_duplicates`: Update existing records when duplicates found
- `source_identifier_field`: Field for tracking imported records
- `matching_strategy`: Algorithm for duplicate detection

**Import Filter Options:**
- `date_from` (string): Import only records created after this date (YYYY-MM-DD)
- `date_to` (string): Import only records created before this date (YYYY-MM-DD)  
- `limit` (integer): Maximum records per data type (useful for testing)
- Custom filters defined in query configuration

**Response:**
```json
{
  "message": "Import job started successfully",
  "job": {
    "id": "uuid",
    "status": "running", 
    "progress_percentage": 0,
    "current_operation": "Initializing import with duplicate detection...",
    "records_processed": 0,
    "records_imported": 0,
    "records_updated": 0,
    "records_skipped": 0,
    "records_failed": 0,
    "mode_config": {
      "import_mode": "upsert",
      "duplicate_detection": {...}
    },
    "estimated_total": 5000,
    "started_at": "2025-08-21T10:00:00Z",
    "profile": {
      "id": "uuid",
      "name": "Production Database"
    }
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
      "profile_name": "Production Database",
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
- **Query Builder**: 30 requests per minute
- **Template Operations**: 20 requests per minute
- **Job Monitoring**: 120 requests per minute (for real-time updates)

## Advanced WebSocket Integration

Real-time job monitoring via Laravel Reverb with multi-channel broadcasting:

```javascript
// Job-specific progress updates
Echo.private(`import.job.${jobId}`)
  .listen('.import.progress.updated', (event) => {
    console.log('Progress:', event.progress_percentage);
    console.log('Operation:', event.current_operation);
    console.log('Records imported:', event.records_imported);
    console.log('Records updated:', event.records_updated);
    console.log('Records skipped:', event.records_skipped);
    console.log('Records failed:', event.records_failed);
  })
  .listen('.import.job.status.changed', (event) => {
    console.log('Status changed:', event.status);
    console.log('Event type:', event.event_type);
    if (event.event_type === 'job_completed') {
      showSuccessNotification(event);
    }
  });

// User-specific notifications
Echo.private(`user.${userId}`)
  .listen('.import.progress.updated', handleUserImportUpdate)
  .listen('.import.job.status.changed', handleUserImportStatus);

// Profile-wide events
Echo.private(`import.profile.${profileId}`)
  .listen('.import.profile.updated', (event) => {
    console.log('Profile configuration changed');
  });
```

**Enhanced Event System:**
- `import.progress.updated` - Real-time progress with comprehensive statistics
- `import.job.status.changed` - Status transitions with event context
- `import.profile.updated` - Profile configuration changes
- `import.template.applied` - Template application events

**Multi-Channel Broadcasting:**
- **Job Channels** - `import.job.{job_id}` for specific job updates
- **Profile Channels** - `import.profile.{profile_id}` for profile events
- **User Channels** - `user.{user_id}` for personal notifications
- **Global Channels** - `import.global` for admin oversight

## Import Analytics & Reporting

### Get Analytics Dashboard

```bash
GET /api/import/analytics/dashboard
```

**Query Parameters:**
- `date_from` (date): Analytics from date
- `date_to` (date): Analytics to date
- `profile_id` (string): Filter by specific profile

**Response:**
```json
{
  "dashboard": {
    "overview": {
      "total_jobs": 156,
      "total_records_processed": 2500000,
      "total_records_imported": 2375000,
      "total_records_updated": 95000,
      "total_records_skipped": 25000,
      "total_records_failed": 5000,
      "average_success_rate": 95.2,
      "average_duration": 3420
    },
    "trends": {
      "daily_jobs": [...],
      "success_rates": [...],
      "performance_metrics": [...]
    },
    "top_profiles": [
      {
        "profile": {...},
        "job_count": 45,
        "success_rate": 98.5,
        "total_records": 850000
      }
    ]
  }
}
```

### Get Profile Statistics

```bash
GET /api/import/analytics/profiles/{profile_id}/stats
```

**Response:**
```json
{
  "profile_stats": {
    "total_jobs": 25,
    "success_rate": 96.8,
    "average_duration": 2840,
    "total_records_imported": 125000,
    "duplicate_detection_effectiveness": 89.2,
    "recent_performance": {
      "last_7_days": {...},
      "last_30_days": {...}
    },
    "record_breakdown": {
      "customer_users": 45000,
      "tickets": 60000,
      "time_entries": 20000
    }
  }
}
```

### Get Duplicate Analysis

```bash
GET /api/import/analytics/duplicate-analysis
```

**Query Parameters:**
- `strategy` (string): Filter by detection strategy
- `confidence_min` (integer): Minimum confidence threshold
- `date_from` (date): Analysis from date

**Response:**
```json
{
  "duplicate_analysis": {
    "detection_strategies": {
      "exact": {
        "total_matches": 15000,
        "average_confidence": 100,
        "false_positive_rate": 0.1
      },
      "fuzzy": {
        "total_matches": 8500,
        "average_confidence": 87.3,
        "false_positive_rate": 2.8
      }
    },
    "confidence_distribution": [
      {"range": "90-100%", "count": 12000},
      {"range": "80-89%", "count": 8500},
      {"range": "70-79%", "count": 3000}
    ],
    "effectiveness_metrics": {
      "true_positives": 95.2,
      "false_positives": 2.8,
      "false_negatives": 2.0
    }
  }
}
```

## Import Record Management

### List Import Records

```bash
GET /api/import/analytics/records
```

**Query Parameters:**
- `job_id` (string): Filter by import job
- `import_action` (string): Filter by action (`created`, `updated`, `skipped`, `failed`)
- `target_type` (string): Filter by target record type
- `has_duplicates` (boolean): Filter records with/without duplicates
- `confidence_min` (integer): Minimum duplicate confidence
- `per_page` (integer): Results per page

**Response:**
```json
{
  "data": [
    {
      "id": "uuid",
      "import_job_id": "uuid",
      "import_profile_id": "uuid",
      "source_table": "customers",
      "source_identifier": "12345",
      "source_hash": "sha256-hash",
      "target_type": "customer_users",
      "target_id": "uuid",
      "import_action": "created",
      "import_mode": "upsert",
      "matching_rules": {
        "strategy": "fuzzy",
        "primary_fields": ["email"],
        "confidence_threshold": 85
      },
      "matching_fields": {
        "email": {
          "confidence": 92.5,
          "matched_value": "john.doe@company.com"
        }
      },
      "duplicate_of": null,
      "error_message": null,
      "field_mappings": {...},
      "created_at": "2025-08-21T10:15:30Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 25,
    "total": 5000
  }
}
```

### Bulk Operations on Import Records

#### Bulk Approve Records

```bash
POST /api/import/records/bulk/approve
```

**Request Body:**
```json
{
  "record_ids": ["uuid1", "uuid2", "uuid3"],
  "criteria": {
    "job_id": "job-uuid",
    "import_action": "skipped",
    "confidence_min": 80
  },
  "options": {
    "create_missing_targets": true,
    "update_existing_targets": false
  }
}
```

#### Bulk Retry Failed Records

```bash
POST /api/import/records/bulk/retry
```

**Request Body:**
```json
{
  "record_ids": ["uuid1", "uuid2"],
  "retry_options": {
    "import_mode": "create",
    "skip_duplicates": true,
    "batch_size": 50
  }
}
```

#### Bulk Delete with Rollback

```bash
DELETE /api/import/records/bulk
```

**Request Body:**
```json
{
  "record_ids": ["uuid1", "uuid2"],
  "rollback_options": {
    "rollback_target_records": true,
    "preserve_relationships": false,
    "create_audit_log": true
  }
}
```

## Import Rollback Functionality

### Create Rollback Job

```bash
POST /api/import/jobs/{job_id}/rollback
```

**Request Body:**
```json
{
  "rollback_options": {
    "target_actions": ["created", "updated"],
    "preserve_relationships": true,
    "batch_size": 100,
    "dry_run": false
  },
  "confirmation": {
    "understood_consequences": true,
    "backup_created": true
  }
}
```

**Response:**
```json
{
  "message": "Rollback job created successfully",
  "rollback_job": {
    "id": "uuid",
    "original_job_id": "uuid",
    "status": "pending",
    "estimated_records": 2500,
    "rollback_options": {...},
    "created_at": "2025-08-21T11:00:00Z"
  }
}
```

### Get Rollback Status

```bash
GET /api/import/rollback/{rollback_job_id}/status
```

**Response:**
```json
{
  "rollback_job": {
    "id": "uuid",
    "status": "running",
    "progress_percentage": 35,
    "current_operation": "Rolling back customer_users records",
    "records_processed": 875,
    "records_rolled_back": 850,
    "records_failed": 25,
    "started_at": "2025-08-21T11:00:00Z",
    "estimated_completion": "2025-08-21T11:15:00Z"
  }
}
```

---

*Universal Import API Reference | Service Vault Documentation | Updated: August 21, 2025*