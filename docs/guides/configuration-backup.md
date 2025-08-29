# Configuration Backup & Restore Guide

Complete guide to Service Vault's configuration backup and restore system for managing settings across environments and ensuring system continuity.

## Overview

Service Vault's Configuration Backup & Restore system provides:

- **Selective Backup**: Export specific configuration categories
- **Preview Before Import**: See exactly what will change
- **Security-First**: Automatic credential masking and access control
- **Cross-Environment**: Seamless configuration migration
- **Audit Trail**: Complete logging of all operations

## Accessing Configuration Management

### Prerequisites
- **Super Administrator Account**: Only users with `is_super_admin` privilege can access
- **Active Session**: Must be logged in to the web interface
- **Password**: Current password required for all import operations

### Navigation
1. **Login** to Service Vault web interface
2. **Navigate to Settings** from the main menu
3. **Click Configuration Tab** (appears only for Super Administrators)

## Configuration Categories

### Available Categories

| Category | Description | Includes |
|----------|-------------|-----------|
| **System** | Core system settings | Timezone, currency, date formats, company info |
| **Email** | Email system configuration | SMTP/IMAP settings, processing rules, domain mappings |
| **Timer** | Timer system preferences | Timer limits, display preferences, auto-stop settings |
| **Advanced** | System debugging options | Debug overlays, logging levels, performance settings |
| **Tax** | Tax configuration | Tax rates, application modes, regional settings |
| **Tickets** | Workflow configuration | Statuses, categories, priorities, transitions |
| **Billing** | Financial configuration | Billing rates, addon templates, invoice settings |
| **Import Profiles** | Data import configuration | Import templates, profiles (credentials masked) |

### Category Dependencies

Some categories have logical dependencies:
- **Billing** may reference **Tax** settings
- **Tickets** workflow depends on **System** timezone settings
- **Email** processing uses **System** company information
- **Import Profiles** may reference **System** default settings

## Export Configuration

### Basic Export Process

1. **Select Categories**: Choose one or more categories to export
2. **Configure Options**: 
   - ✅ Include metadata (recommended)
   - Review security warnings for sensitive categories
3. **Export**: Click "Export Configuration"
4. **Download**: Browser downloads JSON file with timestamp

### Export Options

**Quick Selection Buttons**:
- **Select All**: All available categories
- **Select None**: Clear all selections  
- **System Only**: Just core system settings

**Export Metadata** (Recommended):
```json
{
  "exported_at": "2025-08-29T10:30:00Z",
  "exported_by": "Admin User",
  "exported_by_email": "admin@example.com", 
  "system_version": "1.0.0",
  "categories": ["system", "email"],
  "total_categories": 2
}
```

### Security Considerations

**Credential Masking**:
Import profiles automatically mask sensitive data:
- Passwords → `***MASKED***`
- API Keys → `***MASKED***` 
- Connection strings remain but without credentials

**File Security**:
- Store backup files in secure, encrypted storage
- Use descriptive filenames with environment and date
- Never commit backup files to public repositories
- Consider backup retention policies

## Import Configuration

### Import Workflow

1. **Upload File**: Drag & drop or browse for JSON backup file
2. **Validate**: System validates file structure and shows available categories
3. **Select Categories**: Choose which categories to import
4. **Preview Changes**: Review what will be modified, added, or remain unchanged
5. **Configure Options**: Set overwrite preferences
6. **Import**: Enter password and apply changes

### File Validation

The system validates:
- **JSON Structure**: Valid JSON format and schema
- **Category Availability**: Which categories are present in the file
- **Data Integrity**: Required fields and value formats
- **Version Compatibility**: Metadata version checks

**Validation Response**:
```json
{
  "success": true,
  "available_categories": [
    {
      "id": "system",
      "name": "System", 
      "count": 5
    }
  ],
  "metadata": {...},
  "total_categories": 1
}
```

### Import Preview

**Preview shows three types of changes**:

**Additions** (New Settings):
- Settings that don't exist in current system
- Will be created with imported values
- Shown in green in the interface

**Modifications** (Changed Settings):
- Settings that exist but have different values
- Shows current value vs. new value
- Shown in yellow in the interface

**Unchanged** (Same Settings):
- Settings that match current system
- No changes will be made
- Shown in gray in the interface

### Import Options

**Overwrite Existing**:
- ✅ **Enabled** (Default): Replace existing settings with imported values
- ❌ **Disabled**: Only add new settings, preserve existing ones

**Password Confirmation**:
- Required for all import operations
- Uses current user's password
- Failed authentication cancels import and logs attempt

## Use Cases

### Environment Migration

**Development → Staging**:
```bash
# 1. Development Environment
# Export: system, email, timer, advanced, tax, tickets
# Exclude: billing, import-profiles (environment-specific)

# 2. Staging Environment  
# Import selected categories
# Review preview carefully
# Apply with overwrite enabled
```

**Staging → Production**:
```bash
# 1. Production Backup
# Export ALL categories as rollback backup
# Store securely with date/time stamp

# 2. Staging Export
# Export production-ready configuration
# Exclude development/debug settings

# 3. Production Import
# Import with preview
# Test thoroughly in maintenance window
# Monitor for issues
```

### Configuration Templates

**Base System Template**:
- Export: system, tax, tickets (workflow basics)
- Use for new instance setup
- Standardize across multiple deployments

**Email Configuration Template**:
- Export: email, system (company info)
- Share email processing rules
- Standardize domain routing

### Disaster Recovery

**Regular Backup Schedule**:
```bash
Weekly Full Backup:
- All categories except import-profiles
- Include metadata
- Store with environment identifier

Monthly Archive:
- Complete system backup
- Include import-profiles
- Store for long-term retention
```

**Recovery Process**:
1. **Fresh Installation**: Complete system setup
2. **System Restore**: Import system category first
3. **Component Restore**: Import other categories in logical order
4. **Credential Restoration**: Manually reconfigure masked credentials
5. **Verification**: Test all systems thoroughly

## Troubleshooting

### Common Issues

**Permission Denied**:
```
Access denied. Only Super Administrators can export configuration.
```
**Solution**: Ensure user has `is_super_admin = true` in database

**Invalid Password**:
```
Invalid password. Import cancelled.
```
**Solution**: Use current account password, check for typing errors

**File Validation Failed**:
```
Invalid JSON file: Syntax error
```
**Solution**: Ensure file is valid JSON, not corrupted during transfer

**Missing Categories**:
```
No categories found in uploaded file
```
**Solution**: Verify file contains expected configuration data

### Import Profile Credentials

**After Import**:
1. **Navigate to Import Profiles**: Check all imported profiles
2. **Update Connection Settings**: Reconfigure masked credentials
3. **Test Connections**: Verify all profiles can connect successfully
4. **Update Documentation**: Note which credentials were updated

**Credential Types to Update**:
- Database passwords
- API keys (FreeScout, etc.)
- SMTP/IMAP passwords
- OAuth tokens

### Best Practices

**Pre-Import**:
- Always backup current configuration first
- Test imports in development environment
- Review preview changes carefully
- Plan for credential reconfiguration

**Post-Import**:
- Test all affected systems
- Verify critical functionality (email, timers, etc.)
- Update any cached configuration
- Monitor error logs for issues

**File Management**:
- Use descriptive naming: `prod-backup-2025-08-29-full.json`
- Store in secure, versioned storage
- Maintain retention policies
- Document what each backup contains

## Security Notes

### Access Control
- **Super Admin Only**: Strictest permission level required
- **Password Confirmation**: Every import operation requires password
- **Session Validation**: Must be actively logged in
- **Audit Logging**: All operations logged with user and IP

### Data Protection
- **Credential Masking**: Automatic for sensitive fields
- **Export Security**: Files contain no plaintext passwords
- **File Transmission**: Use secure channels for backup transfer
- **Storage Security**: Encrypt backup files at rest

### Compliance Considerations
- **Audit Trail**: Complete logging for compliance requirements
- **Access Logging**: Track who exported/imported what and when
- **Data Classification**: Consider backup files as sensitive data
- **Retention Policies**: Implement appropriate backup retention

---

*Configuration Backup & Restore Guide | Service Vault Documentation | Last Updated: August 29, 2025*