# Setup Wizard Documentation

Initial system configuration wizard for Service Vault.

> **Location**: `/setup`  
> **Controller**: `App\Http\Controllers\SetupController`  
> **Frontend**: `resources/js/Pages/Setup/Index.vue`

## Overview

The Setup Wizard guides administrators through complete initial system configuration, creating the foundational account structure, system settings, role templates, and first administrator user.

## Setup Requirements

Setup wizard only runs when system is empty:
- No users exist
- No accounts exist  
- No system role templates exist
- No license status configured

If setup is complete, redirects to `Setup/AlreadyComplete.vue`.

## Configuration Sections

### 1. Company Information
```bash
• Company Name (required)         # Primary organization name
• Company Email (required)        # Primary contact email  
• Company Website (optional)      # Organization website
• Company Phone (optional)        # Contact phone number
• Company Address (optional)      # Full organization address
```

### 2. System Configuration
```bash
• Timezone (required)             # System default timezone
• Currency (required)             # Default currency (USD, EUR, GBP, CAD, AUD)
• Language (required)             # System language (en, es, fr, de, ja)
• Date Format (required)          # Date format (Y-m-d, m/d/Y, d/m/Y, d-m-Y)
• Time Format (required)          # Time format (H:i, g:i A)
```

### 3. Administrator Account
```bash
• Admin Name (required)           # Administrator full name
• Admin Email (required)          # Administrator email (unique)
• Admin Password (required)       # Password (8+ characters with confirmation)
```

### 4. Advanced Settings
```bash
• Max Account Depth (1-20)        # Hierarchy levels (default: 10)
• Timer Sync Interval (1-60s)     # Sync frequency (default: 5)
• Permission Cache TTL (60-3600s) # Cache lifetime (default: 300)
• Enable Real-time Features       # WebSocket features toggle
• Enable Notifications            # System notifications toggle
• Maximum Users (1-10000)         # User limit (default: 250)
```

## Setup Process

### 1. Company Account Creation
```php
$account = Account::create([
    'name' => $request->company_name,
    'company_name' => $request->company_name,
    'account_type' => 'internal',
    'email' => $request->company_email,
    'website' => $request->company_website,
    'phone' => $request->company_phone,
    'address' => $request->company_address,
    'settings' => [
        'timezone' => $request->timezone,
        'currency' => $request->currency,
        'language' => $request->language
    ],
    'is_active' => true,
]);
```

### 2. System Settings Storage
Creates system configuration in `settings` table:
```php
$systemSettings = [
    'system.timezone' => $request->timezone,
    'system.currency' => $request->currency,
    'system.date_format' => $request->date_format,
    'system.time_format' => $request->time_format,
    'system.language' => $request->language,
    'system.enable_real_time' => $request->enable_real_time,
    'system.enable_notifications' => $request->enable_notifications,
    'system.max_account_depth' => $request->max_account_depth,
    'system.timer_sync_interval' => $request->timer_sync_interval,
    'system.permission_cache_ttl' => $request->permission_cache_ttl,
    'license.max_users' => $request->max_users,
    'license.status' => 'unlicensed_development',
];
```

### 3. Default Role Templates
Creates six system role templates:

**System Roles:**
- **Super Administrator**: Complete system access + role management
- **System Administrator**: System admin without role template management

**Account Roles:**
- **Account Manager**: Account-specific management capabilities
- **Team Lead**: Team oversight and approval workflows  
- **Employee** (default): Standard time tracking access
- **Customer**: Portal access with limited visibility

### 4. Administrator User Creation
```php
$adminUser = User::create([
    'name' => $request->admin_name,
    'email' => $request->admin_email,
    'password' => Hash::make($request->admin_password),
    'email_verified_at' => now(),
    'account_id' => $account->id,
    'role_template_id' => $superAdminTemplate->id,
]);
```

## Validation Rules

### Form Validation
```php
$rules = [
    // Company Information
    'company_name' => 'required|string|max:255',
    'company_email' => 'required|email|max:255',
    'company_website' => 'nullable|url|max:255',
    'company_phone' => 'nullable|string|max:50',
    'company_address' => 'nullable|string|max:500',
    
    // System Configuration
    'timezone' => 'required|string|max:100',
    'currency' => 'required|string|size:3',
    'language' => 'required|string|max:10',
    'date_format' => 'required|string|max:50',
    'time_format' => 'required|string|max:20',
    
    // Administrator
    'admin_name' => 'required|string|max:255',
    'admin_email' => 'required|email|max:255|unique:users,email',
    'admin_password' => 'required|string|min:8|confirmed',
    
    // Advanced Settings
    'max_account_depth' => 'required|integer|min:1|max:20',
    'timer_sync_interval' => 'required|integer|min:1|max:60',
    'permission_cache_ttl' => 'required|integer|min:60|max:3600',
    'max_users' => 'required|integer|min:1|max:10000',
    'enable_real_time' => 'boolean',
    'enable_notifications' => 'boolean',
];
```

## Frontend Implementation

### Vue.js Form Structure
```javascript
const form = useForm({
  // Company Information
  company_name: '',
  company_email: '',
  company_website: '',
  company_phone: '',
  company_address: '',
  
  // System Configuration
  timezone: 'UTC',
  currency: 'USD',
  language: 'en',
  date_format: 'Y-m-d',
  time_format: 'H:i',
  
  // Advanced Settings
  enable_real_time: true,
  enable_notifications: true,
  max_account_depth: 10,
  timer_sync_interval: 5,
  permission_cache_ttl: 300,
  max_users: 250,
  
  // Administrator
  admin_name: '',
  admin_email: '',
  admin_password: '',
  admin_password_confirmation: '',
});
```

## Security Features

1. **One-Time Setup**: Only runs on empty system
2. **Password Security**: 8+ characters with confirmation
3. **Email Uniqueness**: Admin email validation
4. **Input Validation**: Laravel form request validation
5. **Auto-Login**: Administrator authenticated after setup

## Post-Setup Actions

1. **Cache Clearing**: Clears setup status cache
2. **User Authentication**: Logs in new administrator
3. **Dashboard Redirect**: Redirects to main dashboard
4. **Success Notification**: Displays setup completion message

## Testing

### Manual Testing
1. Fresh database migration
2. Access `/setup` URL
3. Complete all form sections
4. Verify successful completion
5. Confirm admin dashboard access

### Automated Testing
```php
public function test_setup_wizard_creates_complete_system()
{
    $response = $this->post('/setup', [
        'company_name' => 'Test Company',
        'company_email' => 'admin@test.com',
        'timezone' => 'UTC',
        'currency' => 'USD',
        'admin_name' => 'Admin User',
        'admin_email' => 'admin@test.com',
        'admin_password' => 'password123',
        'admin_password_confirmation' => 'password123',
        // ... other required fields
    ]);
    
    $response->assertRedirect('/dashboard');
    $this->assertDatabaseHas('users', ['email' => 'admin@test.com']);
    $this->assertDatabaseHas('accounts', ['name' => 'Test Company']);
    $this->assertDatabaseCount('role_templates', 6);
}
```

## Error Handling

- **Form Validation**: Inline error display for invalid inputs
- **Database Errors**: Transaction rollback on setup failure
- **Duplicate Setup**: Graceful handling of completed setup attempts
- **Network Issues**: Retry mechanisms for setup submission

## Future Enhancements

1. **Industry Templates**: Predefined role sets for different industries
2. **Integration Setup**: SMTP, LDAP, SSO configuration during setup
3. **Theme Selection**: Custom branding and theme selection
4. **Import Options**: Data import from existing systems
5. **Multi-Language**: Localized setup wizard interface

The Setup Wizard provides a comprehensive foundation for Service Vault deployment with all essential system components configured and ready for production use.