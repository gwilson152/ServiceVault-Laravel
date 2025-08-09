# Setup Wizard Documentation

> **Location**: `/setup`  
> **Controller**: `App\Http\Controllers\SetupController`  
> **Frontend**: `resources/js/Pages/Setup/Index.vue`

## Overview

The Service Vault Setup Wizard provides a comprehensive initial configuration interface that guides administrators through the complete system setup process. It creates the initial account structure, configures system settings, establishes role templates, and sets up the first administrator user.

## Setup Process Flow

### 1. Pre-Setup Validation
```php
private function isSystemSetup(): bool
{
    return User::count() > 0 
        && Account::count() > 0 
        && RoleTemplate::where('is_system_role', true)->count() > 0
        && \App\Models\Setting::where('key', 'license.status')->exists();
}
```

The setup wizard only runs if:
- No users exist in the system
- No accounts exist in the system  
- No system role templates exist
- No license status has been set

If setup is already complete, users are redirected to `Setup/AlreadyComplete.vue`.

### 2. Form Sections

#### Company Information
- **Company Name** (required): Primary organization name
- **Company Email** (required): Primary contact email
- **Company Website** (optional): Organization website URL
- **Company Phone** (optional): Primary contact phone
- **Company Address** (optional): Full organization address

#### System Configuration
- **Timezone** (required): System-wide default timezone
- **Currency** (required): Default currency code (USD, EUR, GBP, CAD, AUD)
- **Language** (required): System default language (en, es, fr, de, ja)
- **Date Format** (required): System date format (Y-m-d, m/d/Y, d/m/Y, d-m-Y)
- **Time Format** (required): 12 or 24-hour format (H:i, g:i A)

#### User Limits
- **Maximum Users** (required): Default 250 users
- **Note**: Licensing system will be implemented in future releases

#### Administrator Account
- **Admin Name** (required): First administrator full name
- **Admin Email** (required): Administrator email (must be unique)
- **Admin Password** (required): Minimum 8 characters with confirmation

#### Advanced Settings
- **Max Account Depth** (required): Maximum hierarchy levels (1-20, default: 10)
- **Timer Sync Interval** (required): Seconds between sync updates (1-60, default: 5)
- **Permission Cache TTL** (required): Cache lifetime in seconds (60-3600, default: 300)
- **Enable Real-time Features** (checkbox): WebSocket features toggle
- **Enable Notifications** (checkbox): System notifications toggle

## Setup Execution Process

### 1. Company Account Creation
```php
$account = Account::create([
    'name' => $request->company_name,
    'slug' => Str::slug($request->company_name),
    'description' => 'Primary company account',
    'settings' => [
        'email' => $request->company_email,
        'website' => $request->company_website,
        'address' => $request->company_address,
        'phone' => $request->company_phone,
        'timezone' => $request->timezone,
        'currency' => $request->currency,
        'date_format' => $request->date_format,
        'time_format' => $request->time_format,
        'language' => $request->language,
    ],
    'is_active' => true,
]);
```

### 2. System Configuration Storage
Stores configuration in the `settings` table with `type = 'system'`:
- `system.timezone`
- `system.currency`
- `system.date_format`
- `system.time_format`
- `system.language`
- `system.enable_real_time`
- `system.enable_notifications`
- `system.max_account_depth`
- `system.timer_sync_interval`
- `system.permission_cache_ttl`

### 3. License Placeholder Storage
Creates minimal license entries for future implementation:
```php
$licenseSettings = [
    'license.max_users' => $request->max_users,
    'license.status' => 'unlicensed_development',
    'license.created_at' => now()->toISOString(),
];
```

### 4. Role Template Creation
Creates six default role templates:

#### System Roles
- **Super Administrator**: Full system access + role template management
  - Permissions: `system.manage`, `accounts.create`, `accounts.manage`, `users.manage`, `role_templates.manage`, `timers.manage`, `billing.manage`, `settings.manage`
- **System Administrator**: System administration without role template management
  - Permissions: `accounts.create`, `accounts.manage`, `users.manage`, `timers.manage`, `billing.manage`, `settings.manage`

#### Account Roles  
- **Account Manager**: Account-specific management
  - Permissions: `account.manage`, `users.assign`, `projects.manage`, `billing.view`
- **Team Lead**: Team management and approval workflows
  - Permissions: `team.manage`, `projects.manage`, `time_entries.approve`, `reports.view`
- **Employee** (default): Standard time tracking access
  - Permissions: `timers.create`, `timers.manage`, `time_entries.create`, `projects.view`
- **Customer**: Portal access with limited visibility
  - Permissions: `portal.access`, `tickets.view`, `invoices.view`

### 5. Administrator User Creation
```php
$adminUser = User::create([
    'name' => $request->admin_name,
    'email' => $request->admin_email,
    'password' => Hash::make($request->admin_password),
    'email_verified_at' => now(),
]);

// Create and assign super admin role
$adminRole = Role::create([
    'account_id' => $account->id,
    'role_template_id' => $superAdminTemplate->id,
]);

$adminUser->roles()->attach($adminRole->id);
$account->users()->attach($adminUser->id);
```

### 6. Post-Setup Actions
- Clears setup status cache
- Authenticates the new administrator user
- Redirects to dashboard with success message

## Technical Implementation Details

### Validation Rules
```php
$validator = Validator::make($request->all(), [
    // Company Information
    'company_name' => 'required|string|max:255',
    'company_email' => 'required|email|max:255',
    'company_website' => 'nullable|url|max:255',
    'company_address' => 'nullable|string|max:500',
    'company_phone' => 'nullable|string|max:50',
    
    // System Configuration
    'timezone' => 'required|string|max:100',
    'currency' => 'required|string|size:3',
    'date_format' => 'required|string|max:50',
    'time_format' => 'required|string|max:20',
    'language' => 'required|string|max:10',
    
    // Features & Limits
    'enable_real_time' => 'boolean',
    'enable_notifications' => 'boolean',
    'max_account_depth' => 'required|integer|min:1|max:20',
    'timer_sync_interval' => 'required|integer|min:1|max:60',
    'permission_cache_ttl' => 'required|integer|min:60|max:3600',
    
    // User Limits
    'max_users' => 'required|integer|min:1|max:10000',

    // Admin User Information
    'admin_name' => 'required|string|max:255',
    'admin_email' => 'required|email|max:255|unique:users,email',
    'admin_password' => 'required|string|min:8|confirmed',
]);
```

### Frontend Form Structure
```javascript
const form = useForm({
  // Company Information
  company_name: '',
  company_email: '',
  company_website: '',
  company_address: '',
  company_phone: '',
  
  // System Configuration
  timezone: 'UTC',
  currency: 'USD',
  date_format: 'Y-m-d',
  time_format: 'H:i',
  language: 'en',
  
  // Features & Limits
  enable_real_time: true,
  enable_notifications: true,
  max_account_depth: 10,
  timer_sync_interval: 5,
  permission_cache_ttl: 300,
  
  // User Limits (licensing will be implemented later)
  max_users: 250,
  
  // Admin User Information
  admin_name: '',
  admin_email: '',
  admin_password: '',
  admin_password_confirmation: '',
})
```

## Security Considerations

1. **One-Time Setup**: Setup can only be run when system is empty
2. **Password Security**: Admin password requires minimum 8 characters with confirmation
3. **Email Uniqueness**: Admin email must be unique in the users table
4. **Input Validation**: All inputs are validated with appropriate Laravel rules
5. **Auto-Authentication**: Administrator is automatically logged in after setup

## Future Enhancements

### Licensing Integration
- Replace placeholder license storage with full licensing system
- Add license key validation against license server
- Implement license-based feature restrictions
- Add license renewal and upgrade workflows

### Enhanced Validation
- Domain validation for company email
- Phone number format validation
- Enhanced password strength requirements
- Multi-language support for error messages

### Setup Customization
- Industry-specific role template sets
- Custom field configuration during setup
- Integration settings (SMTP, LDAP, SSO)
- Theme selection and customization

## Error Handling

The setup wizard includes comprehensive error handling:
- Form validation errors are displayed inline
- Database transaction rollback on setup failure
- Graceful handling of duplicate setup attempts
- Clear error messages for all validation failures

## Testing Setup Process

### Manual Testing Steps
1. Ensure database is empty (fresh migration)
2. Access `/setup` URL
3. Fill out all required fields
4. Submit form and verify success
5. Confirm redirection to dashboard
6. Verify admin user can access all features

### Automated Testing
```php
public function test_setup_creates_complete_system()
{
    $response = $this->post('/setup', [
        'company_name' => 'Test Company',
        'company_email' => 'admin@test.com',
        'timezone' => 'UTC',
        'currency' => 'USD',
        'date_format' => 'Y-m-d',
        'time_format' => 'H:i',
        'language' => 'en',
        'max_users' => 250,
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

## Related Documentation

- [ABAC Permission System](../architecture/abac-permission-system.md)
- [Account Management](../features/accounts.md)
- [Role Templates](../features/role-templates.md)
- [User Management](../features/user-management.md)