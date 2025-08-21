# Universal Import System Guide

Complete guide to Service Vault's **universal database import system** supporting any PostgreSQL database with platform-specific templates and visual query builder.

## Overview

The universal import system enables administrators to migrate data from any PostgreSQL database into Service Vault using:
- **Template-Based Configuration** with pre-built platform support (FreeScout, etc.)
- **Visual Query Builder** for custom database structures
- **Advanced JOIN Support** for complex multi-table relationships
- **Real-Time Progress Tracking** with WebSocket updates

## Key Features

- **✅ Universal Database Support** - Any PostgreSQL database with intelligent schema introspection
- **🎯 Platform Templates** - Pre-configured import patterns for FreeScout, custom platforms
- **🔧 Visual Query Builder** - Drag-and-drop interface with table hover tooltips and detailed column information
- **📊 Real-Time Monitoring** - WebSocket-based live progress tracking with floating job monitor
- **🔍 Advanced Duplicate Detection** - Configurable matching strategies with confidence scoring
- **⚙️ Import Mode Configuration** - Create-only, update-only, or upsert modes with smart handling
- **📈 Comprehensive Analytics** - Import statistics, trend analysis, and performance metrics
- **🔄 Record Management** - Complete audit trails with UUID-based tracking and rollback capabilities
- **🔐 Permission-Based Access** - Three-dimensional authorization with granular controls
- **💾 Template System** - Reusable configurations with JSON-based storage
- **⏱️ Time Entry Support** - Comprehensive time tracking data migration
- **🛡️ Production-Ready** - Enterprise-level error handling and stability

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

### Step 2: Apply Template or Build Custom Query

After creating the connection, choose your configuration approach:

#### Option A: Apply Platform Template

**Pre-Built Templates:**
- **FreeScout Platform** - Optimized for FreeScout help desk migration
- **Custom Database** - Starting point for manual configuration

**Apply Template:**
1. Click **"Apply Template"** on profile card
2. Select template (FreeScout or Custom)
3. Review pre-configured queries and field mappings
4. Customize as needed

#### Option B: Build Custom Query

**Visual Query Builder Components:**
- **Table Selector** - Choose base table and browse schema
- **JOIN Builder** - Configure table relationships with suggested joins
- **Field Mapper** - Map source fields to Service Vault fields with transformations
- **Filter Builder** - Add WHERE conditions for data selection

**Build Custom:**
1. Click **"Build Custom Query"** on profile card
2. Use visual components to construct import query
3. Preview data and validate configuration
4. Save custom configuration

### Step 3: Configure Import Modes

**Import Mode Selection:**
- **Create Only** - Only create new records, skip existing ones
- **Update Only** - Only update existing records, skip new ones
- **Create or Update (Upsert)** - Smart handling of both new and existing records

**Duplicate Detection Configuration:**
1. **Detection Strategy** - Exact match, case-insensitive, or fuzzy matching
2. **Matching Fields** - Primary and secondary field selection for duplicate detection
3. **Source Identifier** - Field to use for tracking imported records
4. **Confidence Threshold** - Minimum similarity score for fuzzy matching

### Step 4: Preview and Execute

1. **Preview Import Data** - Review transformed data with impact assessment
2. **Validate Configuration** - Check field mappings, modes, and relationships
3. **Execute Import** - Run import job with real-time progress tracking

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
- Empty query configuration
- All Service Vault target types available
- Full visual query builder access
- Extensible for any PostgreSQL schema

## Visual Query Builder

### Table Selector

**Features:**
- **Schema Introspection** - Browse all tables and columns
- **Table Metadata** - Row counts, column types, primary/foreign keys
- **Interactive Hover Tooltips** - Detailed table and column information on hover
- **Search & Filter** - Find tables by name or column content
- **Visual Selection** - Click to select base table for query

**Enhanced Hover Information:**
- Complete column list with data types
- Primary key and foreign key indicators
- Nullable status and default values
- Table size and row count statistics
- Schema and additional metadata

**Usage:**
1. Select profile → "Build Custom Query"
2. Browse available tables in source database
3. Hover over tables to see detailed column information
4. Click table to select as base for import query
5. Review table structure and sample data

### JOIN Builder

**Features:**
- **Automatic Suggestions** - Detects potential relationships
- **JOIN Types** - INNER, LEFT, RIGHT, FULL OUTER JOIN support
- **Visual Configuration** - Drag-and-drop relationship building
- **Condition Support** - Additional WHERE conditions per JOIN

**JOIN Configuration:**
```sql
-- Example: Customer emails JOIN
LEFT JOIN emails ON emails.customer_id = customers.id 
  AND emails.type = 'work'

-- Example: Ticket assignment JOIN  
LEFT JOIN users ON users.id = conversations.user_id
```

**Suggested Joins:**
- Based on naming patterns (`customer_id`, `user_id`, etc.)
- Foreign key detection from schema
- Confidence scoring for join suggestions

### Field Mapper

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
- **Customer Users** - Account users and customers
- **Tickets** - Support tickets and service requests
- **Time Entries** - Time tracking with duration conversion
- **Agents** - Staff users and agents
- **Accounts** - Customer accounts and organizations

### Filter Builder

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
- **Recent Records** - Last 6 months, 1 year, etc.
- **Active Only** - Exclude deleted/inactive records
- **Billable Only** - For time entries
- **Status Filters** - Specific ticket statuses

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
- **Minimizable Interface** - Compact view when not actively monitoring
- **Live Progress Updates** - Real-time progress bars and statistics
- **Multi-Job Tracking** - Monitor multiple concurrent imports
- **Job Control** - Cancel running jobs directly from monitor
- **Connection Status** - WebSocket connection health indicator

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
- **Pending** - Queued for execution
- **Running** - Active import with real-time updates
- **Completed** - Successfully finished with statistics
- **Failed** - Error occurred with detailed logging
- **Cancelled** - User-initiated cancellation

### Import Analytics & Reporting

**Dashboard Analytics:**
- **Job Performance Metrics** - Success rates, duration trends, throughput analysis
- **Profile Statistics** - Per-profile import history and performance
- **Duplicate Analysis** - Matching effectiveness and confidence distributions
- **Trend Analysis** - Historical import patterns and growth metrics

**Record Management:**
- **Comprehensive Audit Trails** - UUID-based tracking with full lineage
- **Import Record Operations** - Bulk approve, retry, or delete operations
- **Rollback Capabilities** - Undo failed imports with data integrity preservation
- **Impact Assessment** - Preview import effects before execution

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
- Initial data seeding
- Append-only workflows
- When data integrity requires no updates

#### Update Only Mode
```
🔄 Updates existing records only
⏭️ Skips new records that don't exist
✅ Preserves all existing relationships
📊 Tracks update success rates
```

**Best For:**
- Data synchronization
- Bulk updates to existing records
- When new record creation is controlled elsewhere

#### Create or Update (Upsert) Mode
```
✅ Creates new records when not found
🔄 Updates existing records when found
🎯 Intelligent duplicate detection
📊 Comprehensive statistics for both operations
```

**Best For:**
- Full data synchronization
- Production data imports
- Most flexible approach for ongoing operations

### Duplicate Detection System

**Detection Strategies:**

#### Exact Match
```sql
-- Perfect field-by-field matching
WHERE source_field = target_field
```
- Fastest performance
- 100% accuracy for identical data
- Best for UUID or unique identifier fields

#### Case-Insensitive Match
```sql  
-- Case-insensitive comparison
WHERE LOWER(source_field) = LOWER(target_field)
```
- Handles case variations (John vs JOHN vs john)
- Good for email addresses and names
- Moderate performance impact

#### Fuzzy Match (Levenshtein Distance)
```sql
-- Similarity-based matching with confidence scoring
WHERE LEVENSHTEIN(source_field, target_field) <= threshold
```
- Handles typos and variations
- Configurable similarity threshold (0-100%)
- Higher performance cost but maximum flexibility

**Matching Field Configuration:**
- **Primary Fields** - High-priority matching (email, external_id)
- **Secondary Fields** - Additional context (name, phone)
- **Confidence Scoring** - Weighted combination of field matches
- **Threshold Control** - Minimum confidence for duplicate detection

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
  .listen('.import.progress.updated', (event) => {
    // Live progress updates
    updateProgressBar(event.progress_percentage);
    updateStats(event.records_imported, event.records_updated);
  })
  .listen('.import.job.status.changed', (event) => {
    // Status change notifications
    showNotification(event.event_type, event.status);
  });
```

**Multi-Channel Broadcasting:**
- **Job-Specific Channels** - `import.job.{job_id}` for individual job updates
- **Profile Channels** - `import.profile.{profile_id}` for profile-related events  
- **User Channels** - `user.{user_id}` for personal notifications
- **Global Channels** - `import.global` for admin oversight (permission-based)

**Real-Time Features:**
- **Live Progress Bars** - Smooth animations with current operation display
- **Instant Status Updates** - Job state changes broadcast immediately
- **Error Notifications** - Real-time error reporting with detailed messages
- **Completion Alerts** - Success notifications with import statistics

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
- **Source Data Preservation** - Complete original record storage
- **Transformation Tracking** - All field mapping and transformation rules
- **Relationship Mapping** - Links between source and target records
- **Error Logging** - Detailed failure reasons with stack traces
- **Performance Metrics** - Execution time and resource usage per record

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
- **Automatic Retry** - Transient connection issues
- **Batch Size Adjustment** - Memory optimization
- **Data Validation** - Pre-import constraint checking
- **Detailed Logging** - Error tracking and diagnosis

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
- Source is FreeScout help desk platform
- Standard customer → account mapping needed
- Time tracking data requires conversion
- Email relationship resolution needed

**Choose Custom Template If:**
- Non-standard database schema
- Complex multi-table relationships required
- Custom field transformations needed
- Unique business logic must be applied

### Performance Optimization

**Large Dataset Strategies:**
```json
{
  "batch_size": 50,          // Smaller batches for memory
  "timeout": 7200,           // Extended timeout
  "concurrent_jobs": 1       // Single job to reduce load
}
```

**Database Optimization:**
- Add indexes on JOIN columns
- Optimize source database queries
- Schedule imports during off-peak hours
- Monitor both source and destination performance

### Security Considerations

**Connection Security:**
- Use SSL connections (`ssl_mode: 'require'`)
- Rotate database credentials regularly
- Limit database user permissions to SELECT only
- Monitor connection access logs

**Data Protection:**
- Validate data transformations in staging
- Implement data retention policies
- Ensure GDPR/privacy compliance
- Audit import activities

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
- Verify table relationships in source schema
- Check column name case sensitivity
- Validate JOIN conditions with sample data
- Use table aliases for complex queries

**Field Mapping Errors:**
- Ensure source fields exist in database
- Validate data type compatibility
- Test transformations with sample data
- Check for NULL value handling

### Import Failures

**Memory Issues:**
```json
{
  "batch_size": 25,     // Reduce batch size
  "timeout": 1800       // Shorter timeout intervals
}
```

**Data Constraint Violations:**
- Review foreign key relationships
- Validate unique constraints
- Check required field mappings
- Test with small dataset first

**Performance Problems:**
- Add database indexes on filtered columns
- Optimize complex JOIN operations
- Consider importing during off-hours
- Monitor system resource usage

---

*Universal Import System Guide | Service Vault Documentation | Updated: August 21, 2025*