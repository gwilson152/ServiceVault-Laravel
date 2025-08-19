# Setup Wizard Documentation

Initial system configuration wizard for Service Vault.

> **Location**: `/setup`  
> **Controller**: `App\Http\Controllers\SetupController`  
> **Frontend**: `resources/js/Pages/Setup/Index.vue`

## Overview

The Setup Wizard guides administrators through complete initial system configuration, creating the foundational account structure, system settings, role templates, and first administrator user.

## Setup Requirements

Setup wizard only runs when the `system.setup_complete` setting does not exist or is not set to a truthy value. The system uses a simplified setup detection mechanism for reliability.

**Setup Detection Logic:**
- Checks for `system.setup_complete` setting in the database
- Accepts multiple truthy formats: `true`, `'true'`, `1`, `'1'`
- Uses cached detection with 5-minute TTL for performance

**Route Protection:**
- **Before Setup**: All routes except `/setup`, `/api/*`, and `/_*` redirect to setup
- **After Setup**: Setup routes only accessible to Super Admin users
- **Middleware**: `CheckSetupStatus` (global) and `ProtectSetup` (setup routes)

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
    'system.timer_sync_interval' => $request->timer_sync_interval,
    'system.permission_cache_ttl' => $request->permission_cache_ttl,
    'license.max_users' => $request->max_users,
    'license.status' => 'unlicensed_development',
];
```

### 3. System Data Seeding
Setup automatically seeds essential system data:

**Role Templates:**
- **Super Administrator**: Complete system access + role management
- **System Administrator**: System admin without role template management
- **Account Manager**: Account-specific management capabilities
- **Team Lead**: Team oversight and approval workflows  
- **Employee** (default): Standard time tracking access
- **Customer**: Portal access with limited visibility

**Ticket Configuration:**
- **Statuses**: Open, In Progress, Waiting for Customer, On Hold, Resolved, Closed, Cancelled
- **Categories**: Technical Support, Maintenance, Development, Consulting, Emergency, Training, Documentation
- **Priorities**: Low, Normal, Medium, High, Urgent (with escalation multipliers)

**Billing Configuration:**
- **Rates**: Standard ($90), Critical ($130), Development ($65), Travel ($40)
- **Addon Templates**: Hardware, software licenses, and service offerings

### 4. Administrator User Creation
```php
$adminUser = User::create([
    'name' => $request->admin_name,
    'email' => $request->admin_email,
    'password' => Hash::make($request->admin_password),
    'email_verified_at' => now(),
    'account_id' => $account->id,
    'user_type' => 'agent', // Admin should be an agent for timer functionality
    'role_template_id' => $superAdminTemplate->id,
]);
```

### 5. Setup Completion
```php
// Mark setup as complete
\App\Models\Setting::create([
    'key' => 'system.setup_complete',
    'value' => true,
    'type' => 'system',
]);

// Clear setup status cache
Cache::forget('system_setup_status');
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

1. **Setup Detection**: Uses `system.setup_complete` setting for reliable detection
2. **Route Protection**: Middleware prevents access before/after setup based on role
3. **Password Security**: 8+ characters with confirmation
4. **Email Uniqueness**: Admin email validation
5. **Input Validation**: Laravel form request validation
6. **Seeder Safety**: All seeders use `updateOrCreate()` or `firstOrCreate()` to prevent duplicate key errors
7. **Stale Session Handling**: Detects and clears authentication sessions from before database reset

## Post-Setup Actions

1. **Setup Completion Flag**: Creates `system.setup_complete = true` setting
2. **Cache Clearing**: Clears setup status cache to force fresh detection
3. **Browser Cache Clearing**: Sends cache-busting headers to clear browser cache
4. **Login Redirect**: Redirects to login page instead of auto-authentication
5. **Success Notification**: Displays setup completion message

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
- **Duplicate Key Prevention**: Seeders use safe upsert methods to prevent constraint violations
- **Stale Authentication**: Middleware detects and clears invalid user sessions
- **JSON Cast Compatibility**: Setup detection handles different value storage formats
- **Network Issues**: Retry mechanisms for setup submission

## Middleware Architecture

### CheckSetupStatus Middleware
- **Purpose**: Redirects users to setup when system is incomplete
- **Applied**: Globally to all web routes  
- **Exclusions**: `/setup*`, `/api/*`, `/_*` routes
- **Cache**: Uses 5-minute cached detection for performance

### ProtectSetup Middleware  
- **Purpose**: Restricts setup access after completion to Super Admins only
- **Applied**: Only to setup routes (`/setup*`)
- **Logic**: 
  - If setup incomplete → Allow access
  - If setup complete + not authenticated → Redirect to login
  - If setup complete + not Super Admin → Redirect to dashboard
  - If setup complete + Super Admin → Allow access

### Middleware Flow
```
Request → CheckSetupStatus → ProtectSetup → SetupController
    ↓              ↓              ↓              ↓
Setup incomplete? Setup routes? Super Admin?  Process
    ↓              ↓              ↓              ↓
Redirect /setup   Allow access   Allow/Deny    Create/Update
```

## Future Enhancements

1. **Industry Templates**: Predefined role sets for different industries
2. **Integration Setup**: SMTP, LDAP, SSO configuration during setup
3. **Theme Selection**: Custom branding and theme selection
4. **Import Options**: Data import from existing systems
5. **Multi-Language**: Localized setup wizard interface

The Setup Wizard provides a comprehensive foundation for Service Vault deployment with all essential system components configured and ready for production use.