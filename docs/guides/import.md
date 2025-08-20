# Import System Guide

Complete guide to Service Vault's PostgreSQL database import system with specialized FreeScout support.

## Overview

The import system allows administrators to migrate data from external PostgreSQL databases into Service Vault. It features connection testing, data preview, and real-time import progress tracking.

## Key Features

- **PostgreSQL Database Connectivity** with SSL support and connection testing
- **FreeScout Import Profile** with predefined data mappings and UUID conversion
- **Filter-Based Import Control** - Apply date ranges, status filters, and record limits
- **Real-Time Import Progress** tracking and monitoring with WebSocket updates
- **Import Preview** with tabbed interface and relationship resolution
- **Permission-Based Access Control** with comprehensive three-dimensional authorization
- **UUID-Based Primary Keys** for consistent data architecture
- **Intelligent Data Transformation** with automatic integer-to-UUID conversion

## Getting Started

### Prerequisites

1. **Admin/Super Admin Role** with import permissions
2. **PostgreSQL Source Database** with accessible credentials
3. **Network Connectivity** between Service Vault and source database

### Required Permissions

```php
// System-level import permissions
'system.import'              // View import system
'system.import.configure'    // Create/edit import profiles
'system.import.execute'      // Execute import jobs

// Granular permissions
'import.profiles.manage'     // Manage import profiles
'import.jobs.execute'       // Execute import jobs
'import.jobs.monitor'       // Monitor import progress

// Page permissions
'pages.import.manage'       // Access import page
'pages.settings.import'     // Access import settings
```

## Import Profiles

### Creating an Import Profile

1. **Navigate to Import Management**
   - Go to `/import` in your Service Vault installation
   - Click "Create Import Profile"

2. **Basic Information**
   ```
   Profile Name: FreeScout Production
   Import Type: FreeScout PostgreSQL
   Description: Production FreeScout database import
   ```

3. **Database Connection**
   ```
   Host: your-database-host.com
   Port: 5432
   Database: freescout_production
   Username: database_user
   Password: ••••••••••
   SSL Mode: prefer (or require for secure connections)
   ```

4. **Test Connection**
   - Click "Test Connection" to validate database access
   - Verify connection success before saving

### FreeScout Import Profile

The FreeScout profile includes predefined mappings for:

- **Users** → Service Vault Users (staff members)
- **Customers** → Service Vault Accounts + Users
- **Conversations** → Service Vault Tickets
- **Threads** → Service Vault Comments

#### Data Mappings and UUID Conversion

Service Vault uses UUID primary keys while FreeScout uses integer IDs. The import system automatically converts all integer IDs to deterministic UUIDs using UUID v5 generation.

**FreeScout Users → Service Vault Users:**
```php
'id' → UUID (deterministic conversion: freescout_user_123 → uuid)
'first_name' + 'last_name' → 'name'
'email' → 'email'
'role' → 'user_type' (Admin/User → agent, others → employee)
'created_at' → 'created_at' (timestamp preserved)
'updated_at' → 'updated_at' (timestamp preserved)
```

**FreeScout Customers → Service Vault Accounts + Users:**
```php
// Account Creation
'id' → UUID (deterministic: freescout_customer_123 → uuid)
'company' || 'first_name + last_name' → Account 'name'
'account_type' → 'customer'

// User Creation  
'id' → UUID (linked to customer account)
'first_name' + 'last_name' → User 'name'
'email' → 'email'
'user_type' → 'account_user'
```

**FreeScout Conversations → Service Vault Tickets:**
```php
'id' → UUID (deterministic: freescout_conversation_123 → uuid)
'subject' → 'title'
'status' → 'status' (1→open, 2→pending, 3→closed)
'customer_id' → 'account_id' (UUID lookup from converted customer)
'user_id' → 'assigned_user_id' (UUID lookup from converted user)
'created_at' → 'created_at' (timestamp preserved)
```

**FreeScout Threads → Service Vault Comments:**
```php
'id' → UUID (deterministic: freescout_thread_123 → uuid)
'body' → 'comment'
'type' → 'is_internal' (1→false/customer, 2→false/message, 3→true/note)
'conversation_id' → 'ticket_id' (UUID lookup from converted conversation)
'person_id' → 'user_id' (UUID lookup from converted user)
'created_at' → 'created_at' (timestamp preserved)
```

## Import Execution

### Preview and Configure Import

1. **Select Import Profile**
2. **Click "Preview Data"**
3. **Review Sample Data** (Tabbed Interface)
   - **Staff**: FreeScout users → Service Vault users
   - **Customers**: Customer accounts and users
   - **Tickets**: Conversations with resolved status names and user relationships
   - **Comments**: Thread messages with proper categorization

4. **Configure Import Filters** (Optional)
   ```
   Data Types: Select which data types to import
   Date Range: From/to date filtering (e.g., last 6 months only)
   Status Filter: Import only specific ticket statuses
   Record Limit: Maximum records per data type (useful for testing)
   Active Users: Import only active users (exclude disabled accounts)
   ```

5. **Verify Configuration**
   - Check record counts per data type
   - Review applied filters in preview
   - Validate field mappings and relationships
   - Ensure data integrity

### Execute Import

1. **From Preview Modal**: Click "Execute Import"
2. **From Profile Card**: Click "Execute Import" action
3. **Monitor Progress**: Real-time updates every 10 seconds

### Import Job Monitoring

**Real-Time Statistics:**
- Records Imported (green)
- Records Failed (red)  
- Records Skipped (yellow)
- Records Processed (blue)

**Progress Tracking:**
- Overall percentage complete
- Current operation status
- Estimated completion time
- Duration tracking

## Import Job Management

### Job Statuses

- **Pending**: Job queued for execution
- **Running**: Import in progress
- **Completed**: Import finished successfully
- **Failed**: Import encountered fatal errors
- **Cancelled**: Job cancelled by user

### Job Actions

**Running Jobs:**
- Cancel import execution
- View real-time progress
- Monitor error logs

**Completed Jobs:**
- View detailed results
- Download import reports
- Review error logs (if any)

**Failed Jobs:**
- Retry import execution
- View error details
- Troubleshoot issues

## Troubleshooting

### Connection Issues

**SSL Connection Problems:**
```bash
# Try different SSL modes
SSL Mode: disable    # No SSL
SSL Mode: prefer     # SSL if available
SSL Mode: require    # Force SSL
```

**Network Connectivity:**
```bash
# Test PostgreSQL connection manually
psql -h hostname -p 5432 -U username -d database
```

**Firewall/Access:**
- Ensure PostgreSQL accepts connections from Service Vault server
- Check `pg_hba.conf` for access permissions
- Verify database user has SELECT permissions on required tables

### Import Failures

**Common Issues:**
1. **Missing Tables**: FreeScout schema differences
2. **Permission Errors**: Insufficient database access
3. **Data Constraints**: Duplicate keys or constraint violations
4. **Memory Limits**: Large datasets exceeding PHP limits

**Resolution Steps:**
1. **Check Error Logs**: View job error details
2. **Validate Schema**: Use "Get Schema" to inspect database
3. **Test Permissions**: Ensure database user has proper access
4. **Retry Import**: Use retry functionality for transient errors

### Performance Optimization

**Large Dataset Imports:**
```php
// Import options for large datasets
'batch_size' => 100          // Process in smaller batches
'memory_limit' => '512M'     // Increase PHP memory
'max_execution_time' => 3600 // Extended timeout
```

**Database Optimization:**
- Add indexes on frequently queried columns
- Optimize PostgreSQL connection pooling
- Consider importing during off-peak hours

## Security Considerations

### Database Credentials

- **Encrypted Storage**: Passwords encrypted using Laravel's Crypt facade
- **Limited Access**: Only users with import permissions can view profiles
- **Audit Trail**: All import activities logged with user tracking

### Data Validation

- **Schema Validation**: Automatic validation of FreeScout database structure
- **Data Sanitization**: Input validation and sanitization during import
- **Integrity Checks**: Relationship validation and constraint enforcement

### Permission Control

```php
// Three-dimensional permission checking
$user->hasPermission('system.import');           // Functional
$user->hasPermission('pages.import.manage');     // Page Access  
$user->hasPermission('import.profiles.manage');  // Granular
```

## API Integration

### Import Profile API

```bash
# List import profiles
GET /api/import/profiles

# Create import profile
POST /api/import/profiles

# Test connection
POST /api/import/profiles/test-connection

# Get database schema
GET /api/import/profiles/{id}/schema

# Preview import data
GET /api/import/profiles/{id}/preview
```

### Import Job API

```bash
# List import jobs
GET /api/import/jobs

# Execute import with filters
POST /api/import/jobs
{
  "profile_id": "uuid",
  "options": {
    "selected_tables": ["users", "customers", "conversations"],
    "import_filters": {
      "date_from": "2024-01-01",
      "date_to": "2024-12-31", 
      "ticket_status": "1",
      "limit": 1000,
      "active_users_only": true
    },
    "batch_size": 100,
    "overwrite_existing": false
  }
}

# Get job status
GET /api/import/jobs/{id}/status

# Cancel job  
POST /api/import/jobs/{id}/cancel

# Get job statistics
GET /api/import/jobs/stats
```

## Best Practices

### Pre-Import Planning

1. **Backup Service Vault Database**: Always backup before major imports
2. **Test with Sample Data**: Use staging environment for initial testing
3. **Validate Source Data**: Check for data quality issues beforehand
4. **Plan for Downtime**: Schedule imports during maintenance windows

### During Import

1. **Monitor Progress**: Watch for errors and performance issues
2. **Database Performance**: Monitor both source and destination databases
3. **User Communication**: Inform users of ongoing import activities
4. **Error Handling**: Be prepared to cancel and retry if issues arise

### Post-Import Verification

1. **Data Integrity**: Verify imported records match source data
2. **Relationship Validation**: Check that foreign keys are properly linked
3. **User Testing**: Have users validate imported data in their workflows
4. **Performance Check**: Monitor system performance after large imports

---

*Import System Guide | Service Vault Documentation | Updated: August 20, 2025*