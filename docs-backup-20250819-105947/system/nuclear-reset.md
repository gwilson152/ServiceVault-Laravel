# Nuclear System Reset

⚠️ **DANGER**: This feature completely destroys all data and resets the system to initial state. Use with extreme caution.

## Overview

The Nuclear Reset feature provides a secure way to completely reset Service Vault to its initial state, wiping all data and configurations. This is primarily intended for:

- **Development environments** when you need to start fresh
- **Emergency recovery** when the system is in an unrecoverable state
- **Demo environments** that need periodic reset
- **Testing scenarios** requiring clean state

## ⚠️ Security & Access Control

### Multi-Layer Security
1. **Super Administrator Only**: Only users with Super Admin role can access this feature
2. **Password Confirmation**: Current user's password must be verified
3. **Multi-Step Confirmation**: Three confirmation checkboxes plus password entry
4. **Comprehensive Logging**: All attempts logged with CRITICAL/WARNING levels for audit

### Access Requirements
- **Authentication**: Must be logged in with valid session
- **Authorization**: Must have Super Administrator role (`isSuperAdmin()` returns true)
- **Password Verification**: Current password must be correctly entered
- **UI Confirmation**: All three confirmation checkboxes must be checked

## 🔥 What Nuclear Reset Does

### Data Destruction
- **All Users**: Deletes all user accounts (including the current admin)
- **All Tickets**: Removes all service tickets and related data
- **All Time Entries**: Deletes all time tracking data
- **All Accounts**: Removes all customer/business accounts
- **All Settings**: Resets all system configurations
- **All Sessions**: Clears all active user sessions
- **All Files**: Removes uploaded files and attachments

### System Reset Actions
1. **Database Reset**: Executes `migrate:fresh --seed`
   - Drops all database tables
   - Recreates all tables from migrations
   - Seeds essential system data (statuses, categories, priorities, etc.)

2. **Cache Clearing**: Removes all cached data
   - Application cache (Laravel cache)
   - Configuration cache
   - Route cache
   - View cache
   - Permission cache
   - Redis cache flush

3. **Setup State Reset**: Removes `system.setup_complete` flag
   - Forces redirect to `/setup` page
   - Requires complete system reconfiguration
   - New admin account creation required

4. **Session Termination**: Logs out all users
   - Clears all active sessions
   - Invalidates all remember tokens
   - Forces fresh authentication

## 🖥️ User Interface Access

### Location
Navigate to **Settings** → **Nuclear Reset** tab

### UI Features
- **Warning Indicators**: Red color scheme with danger icons
- **Multi-Step Confirmation**:
  1. ✅ "I understand this will permanently delete all data"
  2. ✅ "I have backed up any data I need to preserve"
  3. ✅ "I intend to completely reset the system and start over"
- **Password Confirmation**: Current user password required
- **Loading States**: Progress indicators during execution
- **Error Handling**: Clear error messages for invalid attempts

### Modal Workflow
1. Click "Initiate Nuclear Reset" button
2. Read all warnings in the confirmation modal
3. Check all three confirmation checkboxes
4. Enter your current password
5. Click "🚨 EXECUTE NUCLEAR RESET 🚨"
6. System executes reset and redirects to `/setup`

## 🔧 API Endpoint

### Route
```
POST /api/settings/nuclear-reset
```

### Request
```json
{
    "password": "current_user_password"
}
```

### Response (Success)
```json
{
    "success": true,
    "message": "Nuclear reset completed successfully. System has been reset to initial state. You will be redirected to setup.",
    "redirect_to": "/setup"
}
```

### Response (Error)
```json
{
    "success": false,
    "message": "Invalid password. Nuclear reset cancelled."
}
```

### Error Codes
- **401**: Authentication required
- **403**: Access denied (not Super Admin)
- **422**: Validation failed (missing or invalid password)
- **500**: Execution failed

## 🔨 Command Line Interface

### Artisan Command
```bash
php artisan system:nuclear-reset --user-id=USER_ID
```

### Parameters
- `--user-id`: ID of the user performing the reset (for audit logging)

### Example Usage
```bash
# Execute nuclear reset with user ID 1
php artisan system:nuclear-reset --user-id=1

# View command help
php artisan system:nuclear-reset --help
```

### Command Output
```
🚨 NUCLEAR SYSTEM RESET INITIATED 🚨
This will completely wipe all data and reset the system to initial state.

Step 1/4: Clearing application cache...
✅ Cache cleared

Step 2/4: Resetting database (migrate:fresh --seed)...
✅ Database reset and seeded

Step 3/4: Clearing setup completion flag...
✅ Setup completion flag cleared

Step 4/4: Final cache clear...
✅ Final cache clear completed

🚨 NUCLEAR RESET COMPLETED SUCCESSFULLY 🚨
System has been reset to initial state.
The application will now redirect to /setup for reconfiguration.
```

## 📊 Audit Logging

### Log Levels
- **CRITICAL**: Nuclear reset initiation and completion
- **WARNING**: Invalid attempts and security violations
- **ERROR**: Execution failures and exceptions

### Log Data
All nuclear reset activities are logged with:
- **User Information**: ID, email, name
- **Security Context**: IP address, user agent
- **Timestamp**: Precise execution time
- **Result**: Success/failure status
- **Error Details**: Exception messages and stack traces

### Example Log Entries
```
[CRITICAL] Nuclear system reset initiated by super admin
{
    "user_id": 1,
    "user_email": "admin@company.com",
    "user_name": "System Administrator",
    "ip": "192.168.1.100",
    "user_agent": "Mozilla/5.0...",
    "timestamp": "2025-08-15T14:30:00Z"
}

[WARNING] Non-super-admin attempted nuclear reset
{
    "user_id": 5,
    "user_email": "manager@company.com",
    "ip": "192.168.1.105",
    "timestamp": "2025-08-15T14:25:00Z"
}
```

## 🚨 Safety Measures

### Pre-Reset Checklist
- [ ] **Backup Critical Data**: Export any data you need to preserve
- [ ] **Document Settings**: Note custom configurations
- [ ] **Verify Environment**: Ensure you're not in production
- [ ] **Plan Recovery**: Have reconfiguration plan ready
- [ ] **Notify Users**: Warn other users about impending reset

### Post-Reset Actions
1. **Complete Setup Wizard**: Navigate to `/setup`
2. **Create Admin Account**: Set up new administrator credentials
3. **Configure System**: Restore essential settings
4. **Restore Data**: Import any backed-up data if needed
5. **Test Functionality**: Verify system operation

## 🔒 Security Recommendations

### When to Use
- ✅ **Development/Testing**: Safe for non-production environments
- ✅ **Demo Systems**: Regular reset for clean demonstrations
- ✅ **Emergency Recovery**: When system is corrupted beyond repair
- ✅ **Training Environments**: Reset between training sessions

### When NOT to Use
- ❌ **Production Systems**: Never use in live production
- ❌ **Shared Environments**: Without explicit approval from all users
- ❌ **Data Recovery**: Not a solution for partial data issues
- ❌ **Regular Maintenance**: Use normal maintenance procedures instead

### Best Practices
1. **Environment Validation**: Always verify you're in the correct environment
2. **Backup Strategy**: Maintain regular backups independent of nuclear reset
3. **Access Control**: Limit Super Admin role to trusted personnel only
4. **Documentation**: Document reset procedures and recovery plans
5. **Testing**: Test nuclear reset in non-critical environments first

## 🔄 Recovery Process

### Immediate Post-Reset Steps
1. **Setup Wizard**: Complete `/setup` configuration
   - Company information
   - System settings
   - Admin account creation
   - Advanced configuration

2. **System Verification**: Confirm all components working
   - Database connectivity
   - Cache functionality
   - Email configuration
   - Timer system operation

3. **User Recreation**: Rebuild user accounts
   - Create necessary user accounts
   - Assign appropriate roles
   - Configure permissions
   - Send invitation emails

4. **Data Restoration**: Import backed-up data (if any)
   - Account hierarchies
   - Essential tickets
   - Historical data (if preserved)

### Long-term Recovery
- **Monitor System**: Watch for any post-reset issues
- **Update Documentation**: Record any configuration changes
- **User Training**: Brief users on any workflow changes
- **Backup Schedule**: Resume regular backup procedures

## 🐛 Troubleshooting

### Common Issues

#### Nuclear Reset Fails
- **Check Permissions**: Verify file/directory permissions
- **Database Connection**: Ensure PostgreSQL is accessible
- **Disk Space**: Confirm adequate disk space for operations
- **Redis Connection**: Verify Redis server availability

#### Setup Wizard Not Accessible
- **Cache Issues**: Clear all caches manually
- **Route Problems**: Check route cache and configuration
- **Database State**: Verify `system.setup_complete` is removed

#### Partial Reset State
- **Manual Cleanup**: Run individual commands if atomic reset failed
  ```bash
  php artisan migrate:fresh --seed
  php artisan cache:clear
  redis-cli FLUSHDB
  ```

### Debug Commands
```bash
# Check system state
php artisan tinker --execute="echo App\Models\Setting::where('key', 'system.setup_complete')->exists() ? 'Setup Complete' : 'Setup Required';"

# Verify database state
php artisan migrate:status

# Check cache state
php artisan config:show cache
```

## ⚠️ Final Warning

**Nuclear Reset is a destructive operation that cannot be undone.** 

- All data will be permanently lost
- All users will be logged out and deleted
- Complete system reconfiguration will be required
- This action should only be performed by authorized Super Administrators
- Always ensure you have backups of any critical data before proceeding

Use this feature responsibly and only when absolutely necessary.

---

**Nuclear Reset Documentation**  
*Last Updated: August 15, 2025*  
*Feature Implementation: Phase 15A+*