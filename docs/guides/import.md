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
- **🔧 Visual Query Builder** - Drag-and-drop interface for table JOINs, field mapping, and filters
- **📊 Real-Time Monitoring** - Live progress tracking and error reporting
- **🔐 Permission-Based Access** - Three-dimensional authorization with granular controls
- **💾 Template System** - Reusable configurations with JSON-based storage
- **⏱️ Time Entry Support** - Comprehensive time tracking data migration
- **🛡️ Production-Ready** - Comprehensive error handling and stability

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

### Step 3: Preview and Execute

1. **Preview Import Data** - Review transformed data before import
2. **Validate Configuration** - Check field mappings and relationships
3. **Execute Import** - Run import job with progress tracking

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
- **Search & Filter** - Find tables by name or column content
- **Visual Selection** - Click to select base table for query

**Usage:**
1. Select profile → "Build Custom Query"
2. Browse available tables in source database
3. Click table to select as base for import query
4. Review table structure and sample data

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

## Import Execution & Monitoring

### Import Process

1. **Query Generation** - Build final SQL from visual configuration
2. **Data Validation** - Verify field mappings and relationships
3. **Batch Processing** - Process data in configurable chunks
4. **Progress Tracking** - Real-time updates via WebSocket
5. **Error Handling** - Comprehensive error logging and recovery

### Real-Time Monitoring

**Progress Indicators:**
```
Import Progress: 75% (1,500 / 2,000 records)
Current Operation: Processing Time Entries
Duration: 00:05:23
Records Imported: 1,425 ✅
Records Failed: 25 ❌  
Records Skipped: 50 ⚠️
```

**Job Statuses:**
- **Pending** - Queued for execution
- **Running** - Active import in progress
- **Completed** - Successfully finished
- **Failed** - Error occurred during import
- **Cancelled** - User-initiated cancellation

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