# Email Configuration

Service Vault includes a comprehensive email configuration system supporting both outgoing (SMTP) and incoming (IMAP) email with multiple authentication methods including modern OAuth2 and legacy app passwords.

## Overview

The email configuration wizard provides:

- **Independent SMTP and IMAP Setup**: Configure outgoing and incoming email separately
- **Multiple Provider Support**: Microsoft 365, Gmail/Google Workspace, Outlook.com, and custom servers
- **Modern Authentication**: OAuth2 support for enhanced security
- **Legacy Compatibility**: App password support for older setups
- **Manual Port Configuration**: Custom port settings with common port suggestions
- **Real-Time Testing**: Built-in connection and functionality testing
- **Wizard Interface**: Step-by-step configuration with direct navigation

## Accessing Email Configuration

1. Navigate to **Settings** → **Email Settings** tab
2. Use the 3-step wizard or click directly on any step to jump to that configuration section

## Step 1: Outgoing Email (SMTP)

Configure how Service Vault sends emails (notifications, alerts, etc.)

### Provider Options

#### Microsoft 365 (OAuth) - **Recommended**
- **Authentication**: Modern OAuth2 with automatic token refresh
- **Setup Requirements**: 
  - Microsoft 365 admin must register Service Vault as an application
  - Requires Client ID and Tenant ID from admin
  - No password needed - OAuth handles authentication
- **Benefits**: Enhanced security, MFA support, automatic token management
- **Host Settings**: `smtp-mail.outlook.com:587` (TLS)

#### Microsoft 365 (App Password) - **Legacy**
- **Authentication**: 16-character app passwords
- **Setup Process**:
  1. Sign in to [Microsoft 365 Security](https://security.microsoft.com)
  2. Go to **Sign-in options** → **App passwords**
  3. Click **"Create app password"**
  4. Enter a name like "Service Vault Email"
  5. Copy the generated 16-character password
  6. Use full email address as username
  7. Use app password (not regular password)
- **Host Settings**: `smtp-mail.outlook.com:587` (TLS)
- **Note**: May not be available if disabled by admin

#### Gmail / Google Workspace
- **Authentication**: App passwords (requires 2FA enabled)
- **Setup Process**:
  1. Sign in to [Google Account](https://myaccount.google.com)
  2. Go to **Security** → **2-Step Verification** (enable if not already)
  3. Go to **Security** → **App passwords**
  4. Select app: **Mail**, device: **Other (custom name)**
  5. Enter "Service Vault" as custom name
  6. Copy generated 16-character password
- **Host Settings**: `smtp.gmail.com:587` (TLS)
- **Requirements**: 2-Factor Authentication must be enabled

#### Outlook.com
- **Authentication**: Regular password authentication
- **Setup**: Use your Outlook.com email and password
- **Host Settings**: `smtp-mail.outlook.com:587` (TLS)

#### Custom Server
- **Authentication**: Manual configuration
- **Setup**: Enter all SMTP settings manually
- **Use Cases**: Corporate mail servers, custom SMTP providers

### SMTP Configuration Fields

- **From Email Address**: Email address for outgoing notifications
- **From Name**: Display name for outgoing emails
- **SMTP Host**: Mail server hostname
- **Port**: SMTP port with dropdown helpers:
  - `587` - TLS (STARTTLS) - Most common, recommended
  - `465` - SSL/TLS - Legacy but still used
  - `25` - Plain - Not recommended (no encryption)
  - `2525` - Alternative port for some providers
- **Encryption**: TLS (STARTTLS), SSL/TLS, or None
- **Username**: SMTP authentication username
- **Password**: SMTP password or app password (not needed for OAuth)

## Step 2: Incoming Email (IMAP)

Optional configuration for automatic ticket creation from incoming emails.

### Enable/Disable Incoming Email
- Toggle to enable automatic email-to-ticket creation
- Can be skipped if only outgoing email is needed

### IMAP Provider Options

Same providers as SMTP with independent selection:
- **Microsoft 365 (OAuth)**: `outlook.office365.com:993` (SSL)
- **Microsoft 365 (App Password)**: `outlook.office365.com:993` (SSL)
- **Gmail/Google Workspace**: `imap.gmail.com:993` (SSL)
- **Outlook.com**: `imap-mail.outlook.com:993` (SSL)
- **Custom Server**: Manual configuration

### IMAP Configuration Fields

- **IMAP Host**: Mail server hostname for incoming email
- **Port**: IMAP port with dropdown helpers:
  - `993` - IMAP over SSL/TLS - Recommended
  - `143` - IMAP with STARTTLS or plain
  - `585` - Alternative IMAP SSL port
- **Encryption**: SSL/TLS, TLS (STARTTLS), or None
- **Username**: IMAP authentication username
- **Password**: IMAP password or app password (not needed for OAuth)
- **Email Folder**: Folder to monitor (usually "INBOX")
- **Check Interval**: How often to check for new emails (5, 10, 15, or 30 minutes)

### Smart Credential Sync
When using the same provider for both SMTP and IMAP (e.g., Microsoft 365 for both), the system offers to sync credentials automatically.

## Step 3: Test & Save

Validate configuration and save settings.

### SMTP Testing
- **Test Email Address**: Enter email to receive test message
- **Test Process**: Sends actual test email using configured SMTP settings
- **Results**: Shows success/failure with detailed error messages

### IMAP Testing
- **Connection Test**: Validates IMAP connection and authentication
- **Folder Access**: Confirms access to specified email folder
- **Message Count**: Reports total and unread message counts

### Configuration Summary
Review all settings before saving:
- **Outgoing Email**: Provider, from address, host settings
- **Incoming Email**: Provider, host settings, folder configuration

## Provider-Specific Setup Guides

### Microsoft 365 OAuth Setup (Admin Tasks)

For OAuth authentication, Microsoft 365 administrators must:

1. **Register Application**:
   - Sign in to [Microsoft 365 Admin Center](https://admin.microsoft.com)
   - Go to **Azure Active Directory** → **App registrations**
   - Click **New registration**
   - Name: "Service Vault Email Integration"

2. **Configure Permissions**:
   - Add **Microsoft Graph** permissions:
     - `Mail.Read` (for IMAP)
     - `Mail.Send` (for SMTP)
     - `offline_access` (for token refresh)
   - Grant admin consent for organization

3. **Get Application Details**:
   - Copy **Application (client) ID**
   - Copy **Directory (tenant) ID**
   - Provide these to Service Vault users

### Google Workspace OAuth (Future Enhancement)
Currently uses app passwords. OAuth2 support planned for future releases.

## Security Considerations

### OAuth vs App Passwords

**OAuth2 (Recommended)**:
- ✅ Enhanced security with token-based authentication
- ✅ Automatic token refresh
- ✅ Multi-factor authentication support
- ✅ Granular permission scopes
- ✅ Audit trails in provider admin console
- ❌ Requires admin setup in Microsoft 365

**App Passwords (Legacy)**:
- ✅ Simple setup for end users
- ✅ Works with existing systems
- ✅ No admin intervention required
- ❌ Less secure than OAuth
- ❌ No automatic expiration
- ❌ May be disabled by admin policies

### Best Practices

1. **Use OAuth when available** - More secure and feature-rich
2. **Enable 2FA** - Required for app passwords in most providers
3. **Regular Password Rotation** - For app password setups
4. **Monitor Failed Attempts** - Check email provider logs
5. **Test Regularly** - Verify configuration after provider updates
6. **Use Dedicated Service Account** - Don't use personal email accounts

## Troubleshooting

### Common SMTP Issues

**Authentication Failed**:
- Verify username/password or app password
- Check if 2FA is enabled (required for app passwords)
- Confirm OAuth tokens are valid

**Connection Refused**:
- Verify SMTP host and port settings
- Check firewall restrictions
- Confirm encryption settings (TLS/SSL)

**Mail Delivery Failures**:
- Check from address is valid
- Verify DNS records for domain
- Review provider sending limits

### Common IMAP Issues

**Login Failed**:
- Verify IMAP credentials
- Check if IMAP is enabled in email provider
- Confirm OAuth permissions include mail reading

**Folder Access Denied**:
- Verify folder name (case-sensitive)
- Check folder permissions in email client
- Use "INBOX" for primary mailbox

**Sync Issues**:
- Check network connectivity
- Verify check interval settings
- Review Redis connection for state management

### Debug Information

When contacting support, provide:
- Provider type (Microsoft 365, Gmail, etc.)
- Authentication method (OAuth, app password)
- Error messages from test results
- Network configuration details
- Any recent provider setting changes

## API Endpoints

Email configuration is managed through REST API:

- `GET /api/settings` - Get all settings including email configuration
- `PUT /api/settings/email` - Update email configuration
- `POST /api/settings/email/test-smtp` - Test SMTP configuration
- `POST /api/settings/email/test-imap` - Test IMAP configuration

### Example Configuration

```json
{
  "smtp_host": "smtp-mail.outlook.com",
  "smtp_port": "587",
  "smtp_encryption": "tls",
  "smtp_username": "support@company.com",
  "smtp_password": "16-char-app-password",
  "from_address": "support@company.com",
  "from_name": "Company Support",
  
  "enable_email_to_ticket": true,
  "imap_host": "outlook.office365.com", 
  "imap_port": "993",
  "imap_encryption": "ssl",
  "imap_username": "support@company.com",
  "imap_password": "16-char-app-password",
  "imap_folder": "INBOX",
  "email_check_interval": "5"
}
```

## Integration with Service Vault Features

### Outgoing Email Uses

- **User Invitations**: Welcome emails for new users
- **Ticket Notifications**: Status updates and assignments
- **Timer Alerts**: Automatic timer reminders
- **System Notifications**: Important system events
- **Password Resets**: Security-related communications

### Incoming Email Features

- **Automatic Ticket Creation**: Convert emails to service tickets
- **Ticket Updates**: Reply to tickets via email
- **Customer Communication**: Direct email integration
- **File Attachments**: Email attachments become ticket attachments

The email configuration system integrates seamlessly with Service Vault's permission system, ensuring that email functionality respects user roles and account hierarchies.