# Universal Import System Guide

Complete guide to Service Vault's **universal database import system** supporting any PostgreSQL database with platform-specific templates and visual query builder.

## Overview

The universal import system enables administrators to migrate data from any PostgreSQL database into Service Vault using:

-   **Template-Based Configuration** with pre-built platform support (FreeScout, etc.)
-   **Visual Query Builder** for custom database structures
-   **Advanced JOIN Support** for complex multi-table relationships
-   **Real-Time Progress Tracking** with WebSocket updates
-   **✅ Duplicate Email Support** - Composite unique constraint allows same email for different user types

## Key Features

-   **✅ Universal Database Support** - Any PostgreSQL database with intelligent schema introspection
-   **🎯 Platform Templates** - Pre-configured import patterns for FreeScout, custom platforms
-   **🔧 Visual Query Builder** - Fullscreen interface with table hover tooltips, real-time SQL preview, and consistent query generation
-   **📊 Real-Time Monitoring** - WebSocket-based live progress tracking with floating job monitor
-   **🔍 Advanced Duplicate Detection** - Configurable matching strategies with confidence scoring
-   **⚙️ Import Mode Configuration** - Create-only, update-only, or upsert modes with smart handling
-   **📈 Comprehensive Analytics** - Import statistics, trend analysis, and performance metrics
-   **🔄 Record Management** - Complete audit trails with UUID-based tracking and rollback capabilities
-   **🔐 Permission-Based Access** - Three-dimensional authorization with granular controls
-   **💾 Template System** - Reusable configurations with JSON-based storage
-   **⏱️ Time Entry Support** - Comprehensive time tracking data migration
-   **🛡️ Production-Ready** - Enterprise-level error handling and stability

## Getting Started

### Prerequisites

1. **System.Import Permission** - Required for accessing import functionality
2. **PostgreSQL Source Database** - Target database for data migration
3. **Network Connectivity** - Access between Service Vault and source database

### Required Permissions

```php
// Core import permissions
'system.import'           // Access import system
'system.configure'        // Manage templates (Super Admin only)

// Page access
'pages.import.manage'     // Access /import page
```

## Universal Import Workflow

### Step 1: Create Database Connection

1. **Navigate to Import Management** (`/import`)
2. **Click "Create Import Profile"**
3. **Configure Connection**:
    ```
    Profile Name: Production Database
    Database Type: PostgreSQL
    Host: your-database-host.com
    Port: 5432
    Database: your_database_name
    Username: database_user
    Password: ••••••••••
    SSL Mode: prefer
    Notes: Production import connection
    ```
4. **Test Connection** - Verify database accessibility
5. **Save Profile** - Connection stored for configuration

### Step 2: Apply Template or Query Builder

After creating the connection, choose your configuration approach:

#### Option A: Apply Platform Template

**Pre-Built Templates:**

-   **FreeScout Platform** - Optimized for FreeScout help desk migration
-   **Custom Database** - Starting point for manual configuration

**Apply Template:**

1. Click **"Apply Template"** on profile card
2. Select template (FreeScout or Custom)
3. Review pre-configured queries and field mappings
4. Customize as needed
5. **Visual Indicator**: Profile shows blue document icon for template-based configurations

#### Option B: Query Builder

**Visual Query Builder Components:**

-   **Table Selector** - Choose base table and browse schema with enhanced tooltips
-   **JOIN Builder** - Configure table relationships
-   **Field Mapper** - Map source fields to Service Vault fields with transformations
-   **Filter Builder** - Add WHERE conditions with real-time SQL generation
-   **Query Preview** - Live SQL preview with validation and sample data

**Build Custom:**

1. Click **"Query Builder"** on profile card
2. Use fullscreen visual components to construct import query
3. Preview data with real-time filtering and validation
4. **Save Query Configuration** - Saves to profile for persistence across sessions
5. **Visual Indicator**: Profile shows green cog icon for saved custom queries

#### Saved Configuration Persistence

**✅ Enhanced Configuration Management:**

-   **Automatic Persistence** - Custom queries saved to profile configuration
-   **Page Reload Resilience** - Configurations restore after browser refresh
-   **Visual Status Indicators** - Profile cards show configuration type at a glance:
    -   🟢 **Green Cog Icon** - Has saved custom query configuration
    -   🔵 **Blue Document Icon** - Uses platform template
-   **Session Recovery** - Query builder opens with previously saved settings

### Step 3: Configure Import Modes

**Import Mode Selection:**

-   **Create Only** - Only create new records, skip existing ones
-   **Update Only** - Only update existing records, skip new ones
-   **Create or Update (Upsert)** - Smart handling of both new and existing records

**Duplicate Detection Configuration:**

1. **Detection Strategy** - Exact match, case-insensitive, or fuzzy matching
2. **Matching Fields** - Primary and secondary field selection for duplicate detection
3. **Source Identifier** - Field to use for tracking imported records
4. **Confidence Threshold** - Minimum similarity score for fuzzy matching

### Step 4: Sync Scheduling (Optional)

**Automated Import Scheduling:**

Configure profiles for regular synchronization with flexible scheduling options:

1. **Enable Sync** - Toggle automated import execution
2. **Frequency Options**:
   - **Hourly** - Every hour for real-time sync
   - **Daily** - Once per day at specified time
   - **Weekly** - Weekly execution on chosen day
   - **Monthly** - Monthly execution on first day
   - **Custom** - Cron expression for advanced scheduling
3. **Sync Configuration**:
   ```json
   {
     "sync_enabled": true,
     "sync_frequency": "daily",
     "sync_time": "02:00",
     "sync_timezone": "UTC",
     "sync_options": {
       "update_existing": true,
       "skip_existing": false,
       "batch_size": 100,
       "timeout_minutes": 30
     }
   }
   ```
4. **Sync Management**:
   - **Next Sync Calculation** - Automatic scheduling based on frequency
   - **Sync History** - Track execution results and statistics
   - **Manual Trigger** - Override schedule for immediate execution
   - **Failure Notifications** - Alert on sync failures

### Step 5: Profile Duplication

**Clone Import Configurations:**

Create copies of existing import profiles with all settings preserved:

1. **Duplicate Profile** - Click duplicate option in profile dropdown menu
2. **Naming Dialog** - Specify new profile name and description
3. **Settings Preservation**:
   - Database connection settings
   - Query builder configurations
   - Field mappings and transformations
   - Sync scheduling options
   - Import mode preferences
4. **Reset Tracking Data** - Sync history and statistics start fresh
5. **Independent Operation** - Duplicated profiles operate independently

### Step 6: Preview and Execute

#### Unified Import Preview Experience

**✅ Enhanced Preview Architecture (Latest Update):**

Service Vault now provides a **unified import preview experience** that intelligently adapts to both template-based and query-based imports:

**Single Preview Dialog:**
- **Automatic Detection**: Detects import type (template vs. query) automatically
- **Adaptive Interface**: Shows relevant configuration details based on import type
- **Consistent User Experience**: Same preview flow for all import scenarios

**Access Methods:**
1. **"Preview Import Data"** (dropdown menu) → Unified preview for template-based imports
2. **"Preview Import"** (query builder) → Unified preview for query-based imports

**Smart Preview Content:**
- **Template-Based**: Shows configuration summary, raw data preview, and import options
- **Query-Based**: Shows query summary, field mappings, sample data, and SQL details

**Preview Features:**
1. **Import Configuration** - Mode selection (create/update/upsert) and batch size options
2. **Sample Data Preview** - Real-time data preview with refresh capability
3. **Impact Assessment** - Estimated records and configuration validation
4. **Import Execution** - Direct execution with progress dialog integration

#### Import Execution with Progress Dialog

**Separate Execution Experience:**
- **Dedicated Progress Dialog**: Independent dialog for import job execution
- **Real-Time Progress**: Live progress bars, status updates, and statistics
- **Professional Feedback**: Success/error handling with detailed messaging
- **Job Management**: Direct access to view import jobs after completion

**Execution Flow:**
1. **Preview Import Data** - Review configuration and sample data
2. **Click "Execute Import"** - Progress dialog opens immediately
3. **Monitor Progress** - Real-time job creation and execution feedback
4. **Completion Handling** - Success confirmation with option to view import jobs

## Platform Templates

### FreeScout Template

**Pre-configured for FreeScout help desk platform:**

**Customer Account Users Import:**

```sql
SELECT
  customers.id as external_id,
  CONCAT(customers.first_name, ' ', customers.last_name) as name,
  emails.email,
  customers.company,
  customers.phone,
  customers.created_at,
  customers.updated_at
FROM customers
LEFT JOIN emails ON emails.customer_id = customers.id
WHERE emails.type = 'work'
```

**Support Tickets Import:**

```sql
SELECT
  conversations.id as external_id,
  conversations.subject as title,
  conversations.body as description,
  CASE
    WHEN conversations.status = 1 THEN 'open'
    WHEN conversations.status = 2 THEN 'pending'
    WHEN conversations.status = 3 THEN 'closed'
    ELSE 'open'
  END as status,
  customers.id as customer_external_id,
  users.id as assigned_user_external_id,
  conversations.created_at,
  conversations.updated_at
FROM conversations
LEFT JOIN customers ON customers.id = conversations.customer_id
LEFT JOIN users ON users.id = conversations.user_id
```

**Time Tracking Entries:**

```sql
SELECT
  time_logs.id as external_id,
  time_logs.description,
  ROUND(time_logs.time_spent / 60) as duration,
  time_logs.date as started_at,
  time_logs.user_id as user_external_id,
  time_logs.conversation_id as ticket_external_id,
  COALESCE(time_logs.billable, true) as billable,
  time_logs.rate_amount as rate_override,
  time_logs.created_at,
  time_logs.updated_at
FROM time_logs
LEFT JOIN conversations ON conversations.id = time_logs.conversation_id
LEFT JOIN users ON users.id = time_logs.user_id
```

### Custom Template

**Flexible starting point for any database:**

-   Empty query configuration
-   All Service Vault target types available
-   Full visual query builder access
-   Extensible for any PostgreSQL schema

## Visual Query Builder (Production-Ready)

### Centralized State Management

**✅ Enhanced Architecture (Latest Update):**

-   **Reactive Loop Prevention** - Eliminated infinite update cycles through controlled mutations
-   **Centralized Query Store** - Single source of truth using Vue composables (`useQueryBuilder`)
-   **Async Safety Guards** - Prevents cascading reactive changes with `isUpdating` flags
-   **Session Persistence** - Saved configurations load automatically after page reload
-   **Real-Time Synchronization** - All components stay synchronized through controlled state

### Table Selector

**Features:**

-   **Schema Introspection** - Browse all tables and columns
-   **Table Metadata** - Row counts, column types, primary/foreign keys
-   **Interactive Hover Tooltips** - Detailed table and column information on hover
-   **Search & Filter** - Find tables by name or column content
-   **Visual Selection** - Click to select base table for query

**Enhanced Hover Information (Fixed Positioning):**

-   Complete column list with data types
-   Primary key and foreign key indicators
-   Nullable status and default values
-   Table size and row count statistics
-   Schema and additional metadata
-   Tooltips positioned at top-right of table items for optimal visibility

**Usage:**

1. Select profile → "Query Builder" (opens fullscreen dialog)
2. Browse available tables in source database
3. Hover over tables to see detailed column information (tooltips positioned at top-right)
4. Click table to select as base for import query
5. Review table structure and real-time filtered sample data

### JOIN Builder

**Features:**

-   **JOIN Types** - INNER, LEFT, RIGHT, FULL OUTER JOIN support
-   **Visual Configuration** - Manual relationship configuration with dropdowns
-   **Automatic Table Prefixing** - Smart SQL generation with proper table.column references
-   **Condition Support** - Additional WHERE conditions per JOIN
-   **Real-Time SQL Preview** - Live generation of JOIN syntax with validation
-   **State Synchronization** - Controlled mutations prevent reactive loops

**Enhanced JOIN Configuration:**

```sql
-- Properly prefixed JOIN generation
LEFT JOIN emails ON customers.id = emails.customer_id
  AND emails.type = 'work'

-- Complex multi-table JOINs
LEFT JOIN conversations ON customers.id = conversations.customer_id
LEFT JOIN users ON users.id = conversations.user_id
```

**Smart Prefix Handling:**

- **Left Column Selection** - Shows available columns from base table and previous JOINs
- **Right Column Selection** - Shows columns from selected join table
- **Automatic Prefixing** - Adds table prefixes when missing (customers.id, emails.customer_id)
- **Consistent SQL Generation** - Frontend preview and backend execution use identical logic
- **JOIN Validation** - Real-time validation of column references and syntax

### Field Mapper

**Enhanced Field Selection:**

-   **Multi-Table Source Fields** - Access columns from base table and all joined tables
-   **Organized Field Groups** - Fields grouped by table for easy navigation
-   **Smart Field Discovery** - Automatic detection of available source columns
-   **Real-Time Updates** - Field availability updates when JOINs are added/removed
-   **Table Context** - Clear indication of field source table (table.column format)

**Mapping Types:**

**1. Direct Field Mapping**

```
Source: customers.email → Target: email
```

**2. Field Concatenation**

```
Sources: [first_name, last_name] → Target: name
Result: "John Doe"
```

**3. Conditional Mapping (CASE)**

```sql
CASE
  WHEN status = 1 THEN 'open'
  WHEN status = 2 THEN 'pending'
  ELSE 'closed'
END → status
```

**4. Data Transformations**

```sql
ROUND(duration_seconds / 60) → duration  -- Convert seconds to minutes
UPPER(status) → status                   -- Text transformation
TO_CHAR(date, 'YYYY-MM-DD') → date      -- Date formatting
```

**Target Types:**

-   **Customer Users** - Account users and customers
-   **Tickets** - Support tickets and service requests
-   **Time Entries** - Time tracking with duration conversion
-   **Agents** - Staff users and agents
-   **Accounts** - Customer accounts and organizations

### Filter Builder (Enhanced with Real-Time Preview)

**Real-Time Features:**

-   Live SQL generation as filters are added/modified
-   Immediate validation of filter syntax and field references
-   Consistent query generation between validation and sample data
-   Debounced input handling for smooth user experience
-   **Fixed Recursive Updates** - Eliminated infinite API call loops through controlled state management

**Filter Types:**

**Comparison Filters:**

```sql
created_at >= '2024-01-01'          -- Date range
status != 'deleted'                 -- Exclude deleted
customer_id IS NOT NULL             -- Require relationships
```

**Text Filters:**

```sql
email ILIKE '%@company.com'         -- Domain filtering
subject NOT LIKE '%spam%'           -- Content exclusion
name ~ '^[A-Z]'                     -- Pattern matching
```

**List Filters:**

```sql
status IN ('open', 'pending')       -- Multiple values
type NOT IN ('spam', 'deleted')     -- Exclusion lists
```

**Date/Time Filters:**

```sql
created_at BETWEEN '2024-01-01' AND '2024-12-31'
DATE_TRUNC('month', created_at) = '2024-01-01'
```

**Suggested Filters:**

-   **Recent Records** - Last 6 months, 1 year, etc.
-   **Active Only** - Exclude deleted/inactive records
-   **Billable Only** - For time entries
-   **Status Filters** - Specific ticket statuses

## Advanced Import Execution & Real-Time Monitoring

### Enhanced Import Process

1. **Mode Configuration Application** - Apply import modes and duplicate detection rules
2. **Query Generation** - Build final SQL from visual configuration
3. **Data Validation** - Verify field mappings, modes, and relationships
4. **Duplicate Detection** - Apply configured matching strategies with confidence scoring
5. **Batch Processing** - Process data in configurable chunks with smart record handling
6. **Real-Time Broadcasting** - WebSocket updates via Laravel Reverb
7. **Comprehensive Logging** - Complete audit trails with rollback capabilities

### Real-Time Job Monitor

**Floating Job Monitor Features:**

-   **Minimizable Interface** - Compact view when not actively monitoring
-   **Live Progress Updates** - Real-time progress bars and statistics
-   **Multi-Job Tracking** - Monitor multiple concurrent imports
-   **Job Control** - Cancel running jobs directly from monitor
-   **Connection Status** - WebSocket connection health indicator

**Enhanced Progress Indicators:**

```
Import Progress: 75% (1,500 / 2,000 records)
Current Operation: Processing duplicate detection for Time Entries
Duration: 00:05:23
Records Created: 1,200 ✅
Records Updated: 225 🔄
Records Skipped: 50 ⚠️ (duplicates)
Records Failed: 25 ❌
Duplicate Confidence: 95% average
```

**Advanced Job Statuses:**

-   **Pending** - Queued for execution
-   **Running** - Active import with real-time updates
-   **Completed** - Successfully finished with statistics
-   **Failed** - Error occurred with detailed logging
-   **Cancelled** - User-initiated cancellation

### Import Analytics & Reporting

**Dashboard Analytics:**

-   **Job Performance Metrics** - Success rates, duration trends, throughput analysis
-   **Profile Statistics** - Per-profile import history and performance
-   **Duplicate Analysis** - Matching effectiveness and confidence distributions
-   **Trend Analysis** - Historical import patterns and growth metrics

**Record Management:**

-   **Comprehensive Audit Trails** - UUID-based tracking with full lineage
-   **Import Record Operations** - Bulk approve, retry, or delete operations
-   **Rollback Capabilities** - Undo failed imports with data integrity preservation
-   **Impact Assessment** - Preview import effects before execution

## Advanced Import Features

### Import Mode Configuration

**Three Import Modes Available:**

#### Create Only Mode

```
✅ Creates new records only
⏭️ Skips existing records (based on duplicate detection)
🚫 Never updates existing data
📊 Tracks skip statistics for reporting
```

**Best For:**

-   Initial data seeding
-   Append-only workflows
-   When data integrity requires no updates

#### Update Only Mode

```
🔄 Updates existing records only
⏭️ Skips new records that don't exist
✅ Preserves all existing relationships
📊 Tracks update success rates
```

**Best For:**

-   Data synchronization
-   Bulk updates to existing records
-   When new record creation is controlled elsewhere

#### Create or Update (Upsert) Mode

```
✅ Creates new records when not found
🔄 Updates existing records when found
🎯 Intelligent duplicate detection
📊 Comprehensive statistics for both operations
```

**Best For:**

-   Full data synchronization
-   Production data imports
-   Most flexible approach for ongoing operations

### Duplicate Detection System

**Detection Strategies:**

#### Exact Match

```sql
-- Perfect field-by-field matching
WHERE source_field = target_field
```

-   Fastest performance
-   100% accuracy for identical data
-   Best for UUID or unique identifier fields

#### Case-Insensitive Match

```sql
-- Case-insensitive comparison
WHERE LOWER(source_field) = LOWER(target_field)
```

-   Handles case variations (John vs JOHN vs john)
-   Good for email addresses and names
-   Moderate performance impact

#### Fuzzy Match (Levenshtein Distance)

```sql
-- Similarity-based matching with confidence scoring
WHERE LEVENSHTEIN(source_field, target_field) <= threshold
```

-   Handles typos and variations
-   Configurable similarity threshold (0-100%)
-   Higher performance cost but maximum flexibility

**Matching Field Configuration:**

-   **Primary Fields** - High-priority matching (email, external_id)
-   **Secondary Fields** - Additional context (name, phone)
-   **Confidence Scoring** - Weighted combination of field matches
-   **Threshold Control** - Minimum confidence for duplicate detection

**Smart Duplicate Handling:**

```
Record Processing Flow:
1. Calculate primary field matches (80% weight)
2. Calculate secondary field matches (20% weight)
3. Combine into confidence score (0-100%)
4. Apply threshold filter (default: 85%)
5. Execute import action based on mode configuration
6. Log decision with full audit trail
```

### Real-Time Monitoring System

**WebSocket Integration:**

```javascript
// Automatic connection via Laravel Reverb
Echo.private(`import.job.${jobId}`)
    .listen(".import.progress.updated", (event) => {
        // Live progress updates
        updateProgressBar(event.progress_percentage);
        updateStats(event.records_imported, event.records_updated);
    })
    .listen(".import.job.status.changed", (event) => {
        // Status change notifications
        showNotification(event.event_type, event.status);
    });
```

**Multi-Channel Broadcasting:**

-   **Job-Specific Channels** - `import.job.{job_id}` for individual job updates
-   **Profile Channels** - `import.profile.{profile_id}` for profile-related events
-   **User Channels** - `user.{user_id}` for personal notifications
-   **Global Channels** - `import.global` for admin oversight (permission-based)

**Real-Time Features:**

-   **Live Progress Bars** - Smooth animations with current operation display
-   **Instant Status Updates** - Job state changes broadcast immediately
-   **Error Notifications** - Real-time error reporting with detailed messages
-   **Completion Alerts** - Success notifications with import statistics

### Import Record Management

**UUID-Based Tracking:**

```json
{
  "import_record": {
    "id": "550e8400-e29b-41d4-a716-446655440000",
    "import_job_id": "job-uuid",
    "import_profile_id": "profile-uuid",
    "source_table": "customers",
    "source_identifier": "12345",
    "source_hash": "sha256-hash-of-source-data",
    "target_type": "customer_users",
    "target_id": "target-uuid",
    "import_action": "created|updated|skipped|failed",
    "import_mode": "upsert",
    "matching_rules": {...},
    "matching_fields": {...},
    "duplicate_of": "duplicate-record-uuid",
    "error_message": null,
    "field_mappings": {...},
    "created_at": "2025-08-21T10:00:00Z"
  }
}
```

**Comprehensive Audit Trail:**

-   **Source Data Preservation** - Complete original record storage
-   **Transformation Tracking** - All field mapping and transformation rules
-   **Relationship Mapping** - Links between source and target records
-   **Error Logging** - Detailed failure reasons with stack traces
-   **Performance Metrics** - Execution time and resource usage per record

**Bulk Operations:**

```bash
# Approve pending records
POST /api/import/records/bulk/approve
{
  "record_ids": ["uuid1", "uuid2", ...],
  "criteria": {
    "job_id": "job-uuid",
    "import_action": "skipped"
  }
}

# Retry failed records
POST /api/import/records/bulk/retry
{
  "record_ids": ["uuid1", "uuid2", ...],
  "retry_options": {
    "mode": "create",
    "skip_duplicates": true
  }
}

# Delete records with rollback
DELETE /api/import/records/bulk
{
  "record_ids": ["uuid1", "uuid2", ...],
  "rollback_target": true
}
```

### Error Handling

**Common Issues:**

1. **Connection Timeouts** - Large dataset processing
2. **Data Constraints** - Foreign key violations
3. **Schema Mismatches** - Source field type incompatibility
4. **Permission Errors** - Database access restrictions

**Resolution Strategies:**

-   **Automatic Retry** - Transient connection issues
-   **Batch Size Adjustment** - Memory optimization
-   **Data Validation** - Pre-import constraint checking
-   **Detailed Logging** - Error tracking and diagnosis

## Template Management

### Creating Custom Templates

**Super Admin Only:**

```php
// Template structure
{
  "name": "Custom Platform",
  "platform": "custom_platform",
  "database_type": "postgresql",
  "configuration": {
    "queries": {
      "users": {
        "name": "Platform Users",
        "base_table": "users",
        "fields": ["id", "name", "email"],
        "target_type": "customer_users"
      }
    },
    "priority_order": ["users", "tickets"],
    "suggested_filters": {
      "users": {
        "active_only": "status = 'active'"
      }
    }
  }
}
```

### Template API

```bash
# List available templates
GET /api/import/templates

# Get template details
GET /api/import/templates/{id}

# Apply template to profile
PUT /api/import/profiles/{id}/template
{
  "template_id": "uuid"
}
```

## API Reference

### Import Profiles

```bash
# Database connection management
POST /api/import/profiles
{
  "name": "Production DB",
  "database_type": "postgresql",
  "host": "db.example.com",
  "port": 5432,
  "database": "production",
  "username": "user",
  "password": "pass",
  "ssl_mode": "prefer",
  "notes": "Production import connection"
}

# Test database connection
POST /api/import/profiles/test-connection
{
  "database_type": "postgresql",
  "host": "db.example.com",
  "port": 5432,
  "database": "production",
  "username": "user",
  "password": "pass",
  "ssl_mode": "prefer"
}

# Get database schema
GET /api/import/profiles/{id}/schema

# Preview import data
GET /api/import/profiles/{id}/preview
```

### Query Builder

```bash
# Save custom query configuration
POST /api/import/profiles/{id}/queries
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

### Import Jobs

```bash
# Execute import with configuration
POST /api/import/jobs
{
  "profile_id": "uuid",
  "options": {
    "batch_size": 100,
    "timeout": 3600
  }
}

# Monitor job progress
GET /api/import/jobs/{id}/status

# Cancel running job
POST /api/import/jobs/{id}/cancel
```

## Best Practices

### Pre-Import Planning

1. **Schema Analysis** - Understand source database structure
2. **Test Connections** - Verify database accessibility
3. **Data Quality Review** - Check for inconsistencies
4. **Backup Strategy** - Backup Service Vault before import
5. **Performance Planning** - Consider batch sizes and timing

### Template Selection

**Choose FreeScout Template If:**

-   Source is FreeScout help desk platform
-   Standard customer → account mapping needed
-   Time tracking data requires conversion
-   Email relationship resolution needed

**Choose Custom Template If:**

-   Non-standard database schema
-   Complex multi-table relationships required
-   Custom field transformations needed
-   Unique business logic must be applied

### Performance Optimization

**Large Dataset Strategies:**

```json
{
    "batch_size": 50, // Smaller batches for memory
    "timeout": 7200, // Extended timeout
    "concurrent_jobs": 1 // Single job to reduce load
}
```

**Database Optimization:**

-   Add indexes on JOIN columns
-   Optimize source database queries
-   Schedule imports during off-peak hours
-   Monitor both source and destination performance

### Security Considerations

**Connection Security:**

-   Use SSL connections (`ssl_mode: 'require'`)
-   Rotate database credentials regularly
-   Limit database user permissions to SELECT only
-   Monitor connection access logs

**Data Protection:**

-   Validate data transformations in staging
-   Implement data retention policies
-   Ensure GDPR/privacy compliance
-   Audit import activities

## Troubleshooting

### Connection Issues

**SSL Problems:**

```bash
# Test different SSL modes
ssl_mode: 'disable'   # No SSL (development only)
ssl_mode: 'prefer'    # SSL if available
ssl_mode: 'require'   # Force SSL (recommended)
```

**Network Connectivity:**

```bash
# Manual connection test
psql -h hostname -p 5432 -U username -d database

# Check firewall rules
telnet hostname 5432
```

### Query Builder Issues

**JOIN Problems:**

-   Verify table relationships in source schema
-   Check column name case sensitivity
-   Validate JOIN conditions with sample data
-   Use table aliases for complex queries

**Field Mapping Errors:**

-   Ensure source fields exist in database
-   Validate data type compatibility
-   Test transformations with sample data
-   Check for NULL value handling

### Import Failures

**Memory Issues:**

```json
{
    "batch_size": 25, // Reduce batch size
    "timeout": 1800 // Shorter timeout intervals
}
```

**Data Constraint Violations:**

-   Review foreign key relationships
-   Validate unique constraints
-   Check required field mappings
-   Test with small dataset first

**Performance Problems:**

-   Add database indexes on filtered columns
-   Optimize complex JOIN operations
-   Consider importing during off-hours
-   Monitor system resource usage

---

_Universal Import System Guide | Service Vault Documentation | Updated: August 26, 2025_
