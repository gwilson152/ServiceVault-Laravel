# FreeScout Import System - Complete Implementation Guide

## Overview

The FreeScout Import System provides a comprehensive solution for migrating data from FreeScout helpdesk instances to Service Vault. It includes real-time progress tracking, intelligent relationship mapping, and enterprise-grade reliability features.

## ✅ Composite Constraint Solution

### Duplicate Email Problem Solved
The original FreeScout import issue where the same person exists as both an agent (support staff) and customer has been **completely resolved** with the consolidated migration system.

**Problem**: 
```
SQLSTATE[23505]: Unique violation: 7 ERROR: duplicate key value violates unique constraint "users_email_unique"
DETAIL: Key (email)=(grant@drivenw.com) already exists.
```

**Solution**:
Service Vault now uses a **composite unique constraint** `(email, user_type)` that allows:
- ✅ Same email for different user types: `grant@drivenw.com` as both `agent` and `account_user`  
- ✅ Prevents true duplicates: Cannot have two `agent` records with same email
- ✅ Import compatibility: FreeScout users/customers with duplicate emails import seamlessly

**Database Schema**:
```sql
-- Composite unique constraint in users table
CREATE UNIQUE INDEX users_email_user_type_partial_unique 
ON users (email, user_type) WHERE email IS NOT NULL;

-- Example allowed records:
-- ('grant@drivenw.com', 'agent')     ✅ Allowed
-- ('grant@drivenw.com', 'account_user') ✅ Allowed  
-- ('grant@drivenw.com', 'agent')     ❌ Blocked (duplicate)
```

## System Architecture

### Backend Components

#### FreescoutImportController
**Location**: `app/Http/Controllers/Api/FreescoutImportController.php`

Complete API endpoints for import management:
- `POST /api/import/freescout/validate-config` - Configuration validation
- `POST /api/import/freescout/preview` - Import data preview
- `POST /api/import/freescout/execute` - Execute import
- `GET /api/import/freescout/job/{id}/status` - Job progress tracking
- `POST /api/import/freescout/analyze-relationships` - Relationship analysis

#### FreescoutImportService  
**Location**: `app/Services/FreescoutImportService.php`

Five-step import processing pipeline:
1. **Account Creation** - Maps FreeScout mailboxes or domains to Service Vault accounts
2. **User Processing** - Imports agents and customers with proper role assignment
3. **Conversation Import** - Converts FreeScout conversations to Service Vault tickets
4. **Thread Processing** - Maps conversation threads to ticket comments
5. **Time Entry Import** - Imports time tracking with billing rate integration

#### WebSocket Events
**Location**: `app/Events/FreescoutImportProgressUpdated.php`

Real-time broadcasting:
- Channel: `import.freescout.job.{jobId}`
- Events: `freescout.import.progress`, `import.job.status.changed`

### Frontend Components

#### Main FreeScout API Interface
**Location**: `resources/js/Pages/Import/FreescoutApi.vue`

Tab-based interface with enhanced user experience:
- **API Profiles Tab**: Profile management with sidebar statistics and recent activity
- **Import Logs Tab**: Comprehensive log viewer with detailed job information, progress tracking, and error reporting
- Real-time data loading with automatic refresh
- Responsive design with proper mobile support

#### FreescoutImportProgressDialog
**Location**: `resources/js/Components/Import/FreescoutImportProgressDialog.vue`

Real-time progress tracking with:
- Step-by-step progress visualization
- Live statistics and record counts
- WebSocket integration for instant updates
- Configuration context display
- Error handling and recovery options

#### ImportJobManager
**Location**: `resources/js/Components/Import/ImportJobManager.vue`

Complete job management interface:
- Job history with filtering and pagination
- Status tracking and progress visualization
- Management actions (retry, cancel, download)
- Performance metrics and duration tracking

#### ImportJobDetailsModal
**Location**: `resources/js/Components/Import/ImportJobDetailsModal.vue`

Comprehensive job details viewer:
- Complete import statistics
- Configuration summary
- Error log display
- Export and retry options

## Data Relationship Mapping

### Core Challenge
FreeScout uses a **mailbox-centric** architecture while Service Vault uses an **account-centric** architecture. The import system bridges this gap through intelligent relationship mapping.

### Account Mapping Strategies

#### 1. Map Mailboxes to Accounts
- Each FreeScout mailbox becomes a Service Vault account
- Simple 1:1 mapping for straightforward migrations
- Maintains organizational structure from FreeScout

#### 2. Domain Mapping Strategy
- Uses existing Service Vault domain mappings
- Groups customers by email domain
- Leverages pre-configured account relationships

### User Classification

#### FreeScout Agents → Service Vault Agents
- `user_type: 'agent'`
- Assigned 'Agent' role template
- Account access based on strategy (all accounts vs. primary account)

#### FreeScout Customers → Service Vault Account Users
- `user_type: 'account_user'`
- Assigned 'Account User' role template
- Account assignment based on domain or conversation mapping

### Data Transformation Pipeline

#### Conversations → Tickets
```
FreeScout Conversation:
- ID: conversation_123
- Subject: "Payment issue"
- Mailbox: "Support"
- Customer: john@acme.com

Service Vault Ticket:
- external_id: freescout_conversation_123
- title: "Payment issue"
- account_id: [mapped from mailbox/domain]
- customer_id: [imported user ID]
```

#### Conversation Threads → Ticket Comments
```
FreeScout Thread:
- Type: "message" (public) / "note" (internal)
- Author: Agent or Customer
- Content: HTML or plain text

Service Vault Comment:
- is_internal: based on thread type
- user_id: mapped author
- content: processed with optional prefixes
```

#### Time Entries → Time Entries
```
FreeScout Time Entry:
- Time: "2.5" (hours)
- User: Agent
- Conversation: conversation_123

Service Vault Time Entry:
- duration: 150 (minutes)
- user_id: [mapped agent]
- ticket_id: [mapped conversation]
- account_id: [inherited from ticket]
- billing_rate_id: [strategy-based]
```

## Configuration Options

### Account Strategy Configuration
```javascript
{
  account_strategy: 'map_mailboxes', // or 'domain_mapping'
  agent_access: 'all_accounts', // or 'primary_account'
  unmapped_users: 'auto_create' // or 'skip', 'default_account'
}
```

### Time Entry Configuration
```javascript
{
  time_entry_defaults: {
    billable: true,
    approved: false
  },
  billing_rate_strategy: 'auto_select', // or 'no_rate', 'fixed_rate'
  fixed_billing_rate_id: null
}
```

### Comment Processing Configuration
```javascript
{
  comment_processing: {
    preserve_html: true,
    extract_attachments: false,
    add_context_prefix: true
  }
}
```

## Real-Time Progress Tracking

### WebSocket Integration

The system uses Laravel Echo for real-time updates:

```javascript
// Connect to import progress channel
window.Echo.private(`import.freescout.job.${jobId}`)
  .listen('freescout.import.progress', (data) => {
    // Handle step-by-step progress updates
  })
  .listen('import.job.status.changed', (data) => {
    // Handle job status changes
  })
```

### Fallback Strategy
- **With WebSocket**: Polls every 10 seconds as backup
- **Without WebSocket**: Polls every 2 seconds
- **Graceful degradation** to polling-only mode

### Progress Events
Each import step broadcasts detailed progress:
```javascript
{
  job_id: 123,
  current_step: 'accounts',
  step_details: {
    accounts_created: 5,
    strategy: 'map_mailboxes'
  },
  progress: 20,
  records_processed: 150
}
```

## Error Handling & Recovery

### Graceful Error Handling
- **Continue Processing**: Individual record failures don't stop the import
- **Detailed Logging**: Comprehensive error tracking with context
- **Transaction Safety**: Database rollback on critical failures
- **Recovery Options**: Retry failed jobs with same configuration

### Error Categories
1. **Connection Errors**: FreeScout API connectivity issues
2. **Data Validation Errors**: Invalid or missing required data
3. **Relationship Errors**: Missing dependencies (users, accounts)
4. **System Errors**: Database or application-level failures

### Retry Logic
- **Failed Jobs**: Can be retried with same configuration
- **Duplicate Detection**: External ID tracking prevents duplicates
- **Partial Recovery**: Resume from last successful step

## Performance Optimizations

### Efficient Processing
- **Chunked Processing**: Handles large datasets in manageable batches
- **Connection Pooling**: Optimized FreeScout API requests
- **Memory Management**: Prevents memory leaks during long imports
- **Progressive Enhancement**: Loads data as needed

### Monitoring & Metrics
- **Progress Tracking**: Real-time processing statistics
- **Performance Metrics**: Duration, throughput, success rates
- **Resource Usage**: Memory and CPU monitoring
- **Audit Logging**: Complete trail of all operations

## Security Considerations

### Authentication & Authorization
- **Laravel Sanctum**: API authentication and session management
- **Permission Validation**: Service Vault permission system integration
- **WebSocket Security**: Channel authentication and user validation
- **CSRF Protection**: All API endpoints protected

### Data Protection
- **External ID Tracking**: Prevents accidental duplicates
- **Relationship Integrity**: Maintains foreign key constraints
- **Audit Trail**: Complete logging of all data changes
- **Reversibility**: Track import sources for potential rollback

## Usage Examples

### Basic Import Execution
```javascript
// Start import with configuration
const response = await fetch('/api/import/freescout/execute', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'X-CSRF-TOKEN': csrfToken
  },
  body: JSON.stringify({
    profile_id: profileId,
    config: {
      account_strategy: 'map_mailboxes',
      agent_access: 'all_accounts',
      billing_rate_strategy: 'auto_select',
      time_entry_defaults: {
        billable: true,
        approved: false
      }
    }
  })
})
```

### Job Status Monitoring
```javascript
// Poll for job status
const statusResponse = await fetch(`/api/import/freescout/job/${jobId}/status`)
const jobData = await statusResponse.json()

console.log(`Progress: ${jobData.job.progress}%`)
console.log(`Status: ${jobData.job.status}`)
console.log(`Records: ${jobData.job.records_processed}`)
```

## Troubleshooting

### Common Issues

#### API Connection Failures
- Verify FreeScout API URL and key
- Check network connectivity and firewall rules
- Validate API permissions and rate limits

#### Missing Role Templates
- Ensure 'Agent' and 'Account User' role templates exist
- Check role template permissions and configuration
- Validate role assignment logic

#### Relationship Mapping Errors
- Verify domain mappings are active and configured
- Check account creation permissions
- Validate user email formats and domains

### Debugging Tools

#### Validation Endpoint
```javascript
// Pre-validate configuration
const validation = await fetch('/api/import/freescout/validate-config', {
  method: 'POST',
  body: JSON.stringify({ profile_id, config })
})
```

#### Preview Mode
```javascript
// Preview import without execution
const preview = await fetch('/api/import/freescout/preview', {
  method: 'POST',
  body: JSON.stringify({ profile_id, config, sample_size: 10 })
})
```

## Best Practices

### Pre-Import Preparation
1. **Backup Data**: Create complete database backup
2. **Validate Configuration**: Run validation endpoint
3. **Test with Preview**: Review sample data transformation
4. **Start Small**: Begin with limited record sets
5. **Monitor Resources**: Ensure adequate server capacity

### During Import
1. **Monitor Progress**: Use real-time progress tracking
2. **Watch for Errors**: Review error logs promptly
3. **Resource Monitoring**: Check server performance
4. **Network Stability**: Ensure stable FreeScout API access

### Post-Import Verification
1. **Data Integrity**: Verify record counts and relationships
2. **User Testing**: Confirm imported data accessibility
3. **Performance Check**: Test system responsiveness
4. **Documentation**: Record import configuration and results

## Maintenance

### Regular Tasks
- **Monitor Job History**: Review import success rates
- **Clean Old Jobs**: Archive completed import jobs
- **Update Documentation**: Keep configuration guides current
- **Performance Tuning**: Optimize based on usage patterns

### System Updates
- **API Compatibility**: Test with FreeScout API changes
- **Service Vault Integration**: Verify compatibility with platform updates
- **Security Patches**: Apply updates for dependencies
- **Feature Enhancements**: Add new import capabilities as needed

---

**Implementation Status**: ✅ Complete and Production Ready  
**Last Updated**: August 26, 2025  
**System Requirements**: Laravel 12, Service Vault Platform, FreeScout API Access