# Email System Guide

Complete guide to Service Vault's unified email system combining email configuration, user management, and automated processing.

## Table of Contents

- [Overview](#overview)
- [System Architecture](#system-architecture)
- [Configuration](#configuration)
- [User Management](#user-management)
- [Domain Mapping](#domain-mapping)
- [Monitoring](#monitoring)
- [Troubleshooting](#troubleshooting)

## Overview

Service Vault's unified email system provides **complete email management** including configuration, automated user creation, ticket processing, and domain routing. All email functionality is consolidated into a single interface for streamlined administration.

### Key Features

- **🔧 Unified Configuration**: Single interface for all email system management
- **👥 Automated User Management**: Email-triggered user creation with domain mapping and approval workflows
- **📧 Multiple Provider Support**: SMTP, IMAP, Gmail, Outlook, Exchange integration
- **🎯 Domain-Based Routing**: Route emails to specific business accounts based on email patterns
- **🎫 Automatic Ticket Creation**: Convert emails into service tickets automatically
- **📊 Real-Time Monitoring**: Monitor email processing, user creation, and system health
- **⚙️ Command Processing**: Execute commands embedded in email content
- **🔄 Queue Management**: Reliable email processing with retry mechanisms

## System Architecture

### Components

```
┌─────────────────────────────────────────────────────────────┐
│                 Unified Email System                        │
├─────────────────────────────────────────────────────────────┤
│  Configuration (/settings/email)                           │
│  ├── Email Service Configuration                           │
│  ├── User Management & Auto-Creation                       │
│  ├── Domain Mappings & Routing Rules                       │
│  └── Processing & Workflow Settings                        │
├─────────────────────────────────────────────────────────────┤
│  Monitoring (/admin/email)                                 │
│  ├── Performance Metrics                                   │
│  ├── User Creation Statistics                              │
│  ├── Processing & Queue Health                             │
│  └── System Alerts & Monitoring                            │
└─────────────────────────────────────────────────────────────┘
```

### Page Separation

**Configuration Interface** (`/settings/email`)
- **Purpose**: Complete email system setup and configuration
- **Audience**: System administrators
- **Features**: Email service configuration, user management settings, domain mapping management, processing workflows

**Monitoring Dashboard** (`/admin/email`)
- **Purpose**: Monitor email system operations and user creation
- **Audience**: Email administrators
- **Features**: Performance metrics, user creation statistics, processing logs, queue monitoring, system health

## Configuration

The unified email system configuration includes **email service setup** and **user management features** organized into logical tabs:

### Step 1: Access Email Configuration

Navigate to **Settings → Email** or directly visit `/settings/email`

### Step 2: Basic Configuration Tab

1. **System Activation**
   - Toggle the master email system on/off
   - Must be enabled for email processing to work

### Step 3: Incoming Email Tab

1. **Enable Incoming Service**
   ```
   ☑ Incoming Email Enabled
   ```

2. **Select Incoming Provider**
   - **Generic IMAP**: Traditional IMAP servers
   - **Gmail**: Google Gmail integration
   - **Outlook/Office 365**: Microsoft email services
   - **Microsoft 365 (Graph API)**: Advanced M365 integration with folder browser

3. **Configure Connection Settings**
   ```
   Host: outlook.office365.com
   Port: 993
   Username: support@yourcompany.com
   Password: [application password]
   Encryption: SSL
   Folder: INBOX
   ```

#### Microsoft 365 Folder Selection

For Microsoft 365/Outlook configurations, the system provides an **intelligent folder hierarchy browser**:

1. **Load Folders**: Click "Load Folders" to retrieve your mailbox structure
2. **Browse Hierarchy**: Navigate through folders with visual tree structure:
   ```
   📧 Inbox
   📁 Archive
     ├─ Archive-2023
     ├─ Archive-2024
   📁 Projects
     ├─ Client A
     ├─ Client B
   📁 Support
     ├─ Tickets
     ├─ General
   ```
3. **Search Folders**: Use the search box to quickly find specific folders
4. **Message Counts**: View unread/total message counts for each folder
5. **Smart Selection**: Choose the folder to monitor for incoming service tickets

**Features**:
- ✅ **Hierarchical Display**: Visual tree structure with proper indentation
- ✅ **Real-Time Search**: Filter folders as you type
- ✅ **Message Counts**: Shows (unread/total) for each folder
- ✅ **Smart Defaults**: Inbox pre-selected as default option

### Step 4: Outgoing Email Tab

1. **Enable Outgoing Service**
   ```
   ☑ Outgoing Email Enabled
   ```

2. **Select Outgoing Provider**
   - **Generic SMTP**: Traditional SMTP servers
   - **Gmail**: Google Gmail integration
   - **Outlook/Office 365**: Microsoft email services
   - **Microsoft 365 (Graph API)**: Advanced M365 integration
   - **📧 Use same as incoming (Microsoft 365)**: When M365 is used for incoming
   - **Amazon SES**: AWS Simple Email Service
   - **SendGrid**: SendGrid email delivery platform
   - **Postmark**: Postmark transactional email service
   - **Mailgun**: Mailgun email service

3. **Microsoft 365 Efficiency**
   When using Microsoft 365 for incoming email, you can select **"Use same as incoming"** to automatically reuse the same M365 configuration for outgoing emails. This shows a helpful info panel with your current M365 settings.

4. **Configure Outgoing Settings** (when not using "same as incoming")
   ```
   Host: smtp.office365.com
   Port: 587
   Username: support@yourcompany.com
   Password: [application password]
   Encryption: TLS
   ```

5. **Set From Address Information**
   ```
   From Address: support@yourcompany.com
   From Name: Your Company Support
   Reply-To: support@yourcompany.com (optional)
   ```

### Step 5: Processing Configuration Tab

The Processing tab contains all email processing logic and settings:

#### Email Processing Settings

```
☑ Auto-Create Tickets: Convert emails to tickets automatically
☑ Process Commands: Execute embedded email commands
☑ Send Confirmations: Send auto-reply confirmations
Max Retries: 3
```

#### Timestamp Processing

```
Timestamp Processing:
• Ticket Creation Time: Original Email Timestamp (Default)
• Timezone Handling: Preserve Original Timezone
```

**Timestamp Processing Options**:
- **Original Email Timestamp (Default)**: Uses the timestamp from the email headers. Maintains the actual time the email was sent, which is usually what users expect to see.
- **Service Vault Processing Time**: Uses the timestamp when Service Vault processed the email. Ensures accurate chronological order in processing sequence but may not reflect actual send time.

**Timezone Handling**:
- **Preserve Original**: Keep the timezone information from the email
- **Convert to Server Timezone**: Convert timestamps to the server's timezone
- **Convert to UTC**: Standardize all timestamps to UTC

#### Post-Processing Actions

Configure what happens to emails after they're processed into tickets:

```
Action: Mark as Read (Default)
• Mark as Read: Leave email in folder but mark as read
• Move to Folder: Move email to a different folder after processing
• Delete: Permanently delete the email (not recommended)
```

### Step 6: User Management Configuration

The **User Management** section within the email system handles automated user creation from incoming emails:

#### Automatic User Creation Settings

```
☑ Enable Email Processing: Enable automatic user creation from emails
☑ Auto-Create Users: Create user accounts for unknown email senders
☑ Require Email Verification: New users must verify their email addresses
☑ Require Admin Approval: New users need admin approval before activation
```

#### Domain Strategy Configuration

```
Unmapped Domain Strategy:
• Create Generic Account: Create a generic business account for unmapped domains
• Require Manual Assignment: Hold emails for manual account assignment
• Reject Email: Automatically reject emails from unmapped domains
```

#### Default Assignment Settings

```
Default Account: [Select Default Business Account]
Default Role Template: [Select Default Role for New Users]
```

### Step 7: Activate System

```
☑ Email System Active
```

**Important**: Both incoming and outgoing services must be configured before activation.

### Step 8: Test Configuration

Click **"Test Configuration"** to verify:
- ✅ Incoming connection successful
- ✅ Outgoing connection successful  
- ✅ Authentication verified
- ✅ Folder access confirmed

## User Management

The unified email system includes automated user management features that create user accounts based on incoming email activity. This consolidates all user creation functionality into the email system interface.

### Overview

When emails are processed, the system can automatically:

- **Create User Accounts**: Generate user accounts for unknown email senders
- **Domain-Based Account Assignment**: Route users to specific business accounts based on email domain
- **Role Assignment**: Automatically assign role templates to new users
- **Approval Workflows**: Require admin approval for new user activation
- **Email Verification**: Send verification emails to confirm user email addresses

### User Creation Workflow

```
Incoming Email → Domain Check → User Exists?
                                     ↓ (No)
                               Create User Account
                                     ↓
                            Apply Domain Mapping
                                     ↓
                            Assign Role Template
                                     ↓
                         Email Verification (Optional)
                                     ↓
                         Admin Approval (Optional)
                                     ↓
                              User Activated
```

### Configuration

Access user management settings within **Settings → Email → User Management** tab.

#### Enable User Creation

```
☑ Enable Email Processing: Master switch for email-based user creation
☑ Auto-Create Users: Automatically create accounts for unknown senders
```

#### Verification & Approval

```
☑ Require Email Verification: New users must verify email addresses
☑ Require Admin Approval: Admin approval required before user activation
```

**Verification Process**:
- System sends verification email to new user
- User clicks verification link to confirm email address
- Account remains inactive until verified

**Approval Process**:
- New users appear in admin approval queue
- Admins can approve/reject user accounts
- Users receive notification upon approval

#### Domain Strategy

Configure how the system handles emails from unmapped domains:

**Create Generic Account**
```
- Creates a default business account for unmapped domains
- All unmapped users assigned to this generic account
- Useful for open systems accepting any email domain
```

**Require Manual Assignment**
```
- Emails from unmapped domains held for manual review
- Admin manually assigns appropriate business account
- Ensures proper account organization
```

**Reject Email**
```
- Automatically rejects emails from unmapped domains
- Sends rejection notification to sender
- Maintains strict domain control
```

#### Default Assignments

```
Default Account: [Business Account Selector]
Default Role Template: [Role Template Selector]
```

These defaults apply when:
- Domain mapping doesn't specify an account
- Role template not specified in domain mapping
- Generic account strategy is used

### User Statistics

The Email System displays comprehensive user management statistics:

#### User Overview
```
Total Users: 1,247
Active Users: 1,156 (92.7%)
Pending Approval: 15
Auto-Created: 892 (71.5%)
```

#### Daily Metrics
```
Users Created Today: 8
Approved Today: 12
Verification Emails Sent: 5
```

#### Processing Statistics
```
Email → User Success Rate: 96.3%
Average Processing Time: 0.8s
Domain Mapping Success: 98.1%
```

### User Management Best Practices

#### Domain Mapping Strategy

**1. Comprehensive Domain Mapping**
```
- Map all expected client domains
- Set appropriate default accounts
- Configure role templates per domain type
```

**2. Role Template Assignment**
```
- Create specific role templates for email users
- Limit permissions for auto-created accounts
- Separate permissions from manually created users
```

**3. Approval Workflows**
```
- Enable approval for sensitive environments
- Use verification for all auto-created accounts
- Monitor approval queue regularly
```

#### Security Considerations

**Email Verification**
- Always enable for auto-created accounts
- Prevents unauthorized account creation
- Confirms email ownership

**Admin Approval**
- Required for high-security environments
- Prevents unauthorized access
- Allows review of new user requests

**Role Limitations**
- Auto-created users should have limited initial permissions
- Require manual permission elevation
- Separate auto-created from manually created users

## Domain Mapping

Domain mappings route incoming emails to specific business accounts based on email address patterns.

### Access Domain Mappings

1. Go to **Settings → Email**
2. Click **"Manage domain mappings"**
3. Or visit `/settings/email/domain-mappings`

### Pattern Types

**Domain Pattern** (`@company.com`)
```
Pattern: @acme.com
Matches: any-email@acme.com, support@acme.com, sales@acme.com
```

**Exact Email** (`support@company.com`)
```
Pattern: support@acme.com
Matches: support@acme.com only
```

**Wildcard Pattern** (`*@company.com`)
```
Pattern: *@acme.com
Matches: any-email@acme.com with explicit wildcard
```

### Creating Domain Mappings

1. **Click "Add Domain Mapping"**
2. **Configure Pattern**
   ```
   Email Pattern: @acme.com
   Pattern Type: Domain
   ```

3. **Select Business Account**
   ```
   Business Account: Acme Corporation
   ```

4. **Set Defaults** (Optional)
   ```
   Default Agent: John Smith
   Default Category: General Support
   Default Priority: Medium
   ```

5. **Processing Options**
   ```
   ☑ Auto-Create Tickets
   ☑ Send Auto-Reply
   Priority: 100 (higher = higher matching priority)
   ```

### Mapping Priority

When multiple patterns could match an email, the system uses **priority order**:

1. **Exact email matches** (highest priority)
2. **Custom priority settings** (100 = higher than 50)
3. **Pattern specificity** (more specific patterns first)

## Monitoring

### Access Email Monitoring

Navigate to **Admin → Email** and choose from available monitoring interfaces:

- **Dashboard** (`/admin/email/dashboard`) - System overview and metrics
- **Processing Monitor** (`/admin/email/monitoring`) - Real-time email processing activity

### Dashboard Metrics

**System Status**
- ✅ Active/⚠️ Configured/❌ Inactive
- Incoming/Outgoing service status
- Domain mappings configured
- User creation system status

**Key Performance Indicators**
- **Total Emails Processed**: 1,247 (+5.2% vs yesterday)
- **Success Rate**: 98.5%
- **Users Auto-Created**: 47 (this week)
- **Commands Executed**: 89
- **Average Processing Time**: 1.2s

### Real-Time Processing Monitor

The **Processing Monitor** (`/admin/email/monitoring`) provides comprehensive real-time monitoring:

**Live Activity Feed**
- Real-time email processing events with WebSocket updates
- Email status indicators (sent, delivered, failed, bounced)
- Processing time and retry information
- Detailed error messages for failed emails

**Advanced Filtering**
```
Time Range: Last Hour | 6 Hours | 24 Hours | 7 Days | 30 Days
Status: All | Pending | Processing | Sent | Delivered | Failed | Bounced
Email Account: All Accounts | Specific account selection
Direction: All | Inbound | Outbound
```

**Performance Metrics Cards**
- **Total Processed**: Live count with success rate percentage
- **Successful**: Count with percentage change from previous period
- **Failed**: Error count with failure rate trends
- **Average Processing Time**: Real-time processing performance

**Queue Status Sidebar**
- **Pending Jobs**: Currently queued emails
- **Processing Jobs**: Actively being processed
- **Failed Jobs**: Requires manual intervention
- **System Performance**: CPU, memory, and queue health metrics

**Email Details Modal**
- Complete email processing timeline
- Raw email content viewer
- Attachment management
- Retry functionality for failed emails
- System metadata and debugging information

### System Health Monitoring

**Queue Health**
```
Pending Jobs: 3
Failed Jobs: 0
Processed Today: 156
Oldest Pending: 2 minutes ago
```

**Processing Statistics**
```
24h Success Rate: 98.5%
Average Response Time: 1.2s
Peak Processing Time: 8.3s
Commands Success Rate: 94%
```

**System Alerts**
- High failure rate warnings
- Configuration issues
- Queue backup alerts
- Domain mapping status

### Processing Logs

Access detailed processing logs at `/admin/email/processing-logs`:

- **Email Details**: From, Subject, Received Time
- **Processing Status**: Pending/Processing/Processed/Failed
- **Account Assignment**: Which business account received the email
- **Ticket Creation**: Associated ticket number
- **Command Results**: Executed commands and results
- **Error Information**: Detailed error messages for failed processing

## Troubleshooting

### Common Issues

**1. Email System Inactive**
```
Problem: System shows "Inactive" status
Solution: 
  - Verify incoming service configured and enabled
  - Verify outgoing service configured and enabled
  - Check "Email System Active" checkbox
  - Test configuration
```

**2. Authentication Failures**
```
Problem: "Authentication failed" in test results
Solution:
  - Verify username/password credentials
  - Check if 2FA requires app-specific passwords
  - Ensure correct host and port settings
  - Verify encryption method (TLS/SSL)
```

**3. No Tickets Created**
```
Problem: Emails processed but no tickets created
Solution:
  - Check domain mappings configuration
  - Verify "Auto-Create Tickets" enabled
  - Check email address matches domain patterns
  - Review processing logs for details
```

**4. High Processing Times**
```
Problem: Emails taking too long to process
Solution:
  - Check queue worker status
  - Monitor system resource usage
  - Review failed jobs queue
  - Consider increasing worker processes
```

**5. Missing Domain Mappings**
```
Problem: Emails not routing to correct accounts
Solution:
  - Verify domain patterns are correct
  - Check pattern type (domain/email/wildcard)
  - Review mapping priority order
  - Test with specific email addresses
```

**6. Microsoft 365 Folder Issues**
```
Problem: Folder hierarchy not displaying correctly
Solution:
  - Verify M365 credentials and permissions
  - Check that client app has Mail.Read permissions
  - Ensure mailbox has proper folder structure
  - Review folder loading logs in Laravel log

Problem: Cannot load M365 folders
Solution:
  - Verify Tenant ID, Client ID, and Client Secret
  - Check application permissions in Azure AD
  - Ensure client app is configured for client credentials flow
  - Review authentication logs for specific error messages

Problem: Folder selection not saving
Solution:
  - Ensure folder ID is being returned correctly
  - Check folder name contains valid characters
  - Verify folder hierarchy detection is working
  - Review browser console for JavaScript errors
```

### Queue Management

**View Queue Status**
```bash
php artisan queue:work --queue=email-incoming,email-outgoing
php artisan queue:failed
```

**Clear Failed Jobs**
```bash
php artisan queue:flush
php artisan queue:retry all
```

**Monitor Queue Health**
- Access queue monitoring at `/admin/email`
- Check "Queue Status" section
- Review pending and failed job counts

### Log Analysis

**Email Processing Logs**
```bash
tail -f storage/logs/laravel.log | grep "email"
```

**Common Log Entries**
```
[INFO] Email processed successfully: ticket_123
[ERROR] Authentication failed for support@company.com
[WARNING] No domain mapping found for user@example.com
[INFO] Command executed: priority:high on ticket_456
```

### Configuration Validation

**Test Email Services**
1. Go to Settings → Email
2. Click "Test Configuration"
3. Review test results:
   - ✅ Incoming connection
   - ✅ Outgoing connection
   - ✅ Authentication
   - ✅ Folder access

**Verify Domain Mappings**
1. Send test email to configured pattern
2. Check processing logs
3. Verify ticket creation
4. Confirm account assignment

## API Integration

The email system provides REST API endpoints for integration:

### Configuration Management
```http
GET    /api/email-system/config      # Get current configuration
PUT    /api/email-system/config      # Update configuration
POST   /api/email-system/test        # Test configuration
```

### Monitoring & Statistics
```http
GET    /api/email-admin/dashboard    # System dashboard data
GET    /api/email-admin/queue-status # Queue monitoring
GET    /api/email-admin/system-health # Health metrics
```

### Domain Mapping Management
```http
GET    /api/domain-mappings          # List domain mappings
POST   /api/domain-mappings          # Create mapping
PUT    /api/domain-mappings/{id}     # Update mapping
DELETE /api/domain-mappings/{id}     # Delete mapping
```

## Security Considerations

### Email Credentials
- Use application-specific passwords for Gmail/Outlook
- Store credentials securely in environment variables
- Regularly rotate email service passwords
- Enable 2FA on email accounts where possible

### Access Control
- Email configuration requires `system.configure` permission
- Email monitoring requires appropriate admin roles
- API endpoints protected by authentication middleware

### Data Privacy
- Email content processed according to configured rules
- Automatic ticket creation respects account isolation
- Processing logs contain email metadata only (not content)

---

*Last Updated: August 27, 2025 - Unified Email System with Integrated User Management*