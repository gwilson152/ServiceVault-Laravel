# User Management System

Complete user lifecycle management with invitation-based onboarding, three-dimensional permissions, and account hierarchy integration.

## Overview

Service Vault's user management system supports both service provider employees and customer organization users with sophisticated role-based access control, invitation workflows, and account-based user assignment.

## User Creation Workflows

### 1. Invitation-Based User Creation (Recommended)

**When to Use**: For all new users, especially customer users and new employees
**Benefits**: Secure password setup, email verification, professional onboarding experience

#### Process:

1. Admin creates user with "Send invitation email" enabled
2. Password fields are automatically hidden and not required
3. User receives invitation email with secure setup link
4. User sets their own password and verifies email
5. Account is automatically activated upon completion

```php
// API Request Example
POST /api/users
{
    "name": "John Doe",
    "email": "john@company.com",
    "timezone": "America/New_York",
    "locale": "en",
    "is_active": true,
    "is_visible": true,
    "account_id": "bf017dfc-f98d-47c2-b00a-676a1c3bb5b7",
    "role_template_id": "6194d5f9-7508-4f48-9090-829e6af6dff2",
    "send_invitation": true  // Key flag for invitation workflow
}
```

### 2. Direct User Creation

**When to Use**: Internal users where immediate access is needed
**Requirements**: Password must be provided by admin

#### Process:

1. Admin unchecks "Send invitation email"
2. Password fields become required
3. User is created with immediate login capability
4. Email is automatically verified

### 3. Inactive User Creation

**When to Use**: Placeholder users, future employees, or temporarily disabled accounts
**Benefits**: No password required, can be activated later

#### Process:

1. Admin sets "User is active" to unchecked
2. Password fields are automatically hidden (not required for inactive users)
3. User cannot log in until activated and password is set

## User Model Structure

### Core Fields

```php
// User Model Attributes
'name'              // Full display name (required)
'email'             // Email address (nullable for inactive users, unique when provided)
'password'          // Nullable - supports invitation workflow and inactive users
'timezone'          // User's timezone (defaults to system timezone)
'locale'            // Language preference (default: 'en')
'is_active'         // Can user log in and access system
'is_visible'        // Is user shown in lists and selections
'account_id'        // Single account assignment (not array)
'role_template_id'  // Single role assignment (not array)
'preferences'       // JSON user preferences
'last_active_at'    // Last activity timestamp
'last_login_at'     // Last login timestamp
```

### Email Address Requirements

**Service Vault supports optional email addresses for specific use cases:**

#### Email Required (Most Users)

-   **Active users**: Must have email for login and communication
-   **Invitation workflow**: Email required to send invitation links
-   **Standard operation**: Email serves as login identifier

#### Email Optional (Special Cases)

-   **Inactive users**: Placeholder users don't need email until activated
-   **Template users**: System role templates or examples
-   **Future employees**: Users created in advance of hiring
-   **Archived users**: Former employees who no longer need email communication

#### Database Constraints

```sql
-- PostgreSQL partial unique constraint
CREATE UNIQUE INDEX users_email_unique ON users (email) WHERE email IS NOT NULL;

-- Email is nullable but unique when provided
email VARCHAR(255) NULL
```

#### API Validation Logic

```php
// Email is required for active users unless using invitation workflow
$emailRequired = $request->boolean('is_active', true) && !$request->boolean('send_invitation', false);

$rules = [
    'email' => $emailRequired
        ? 'required|email|unique:users,email'
        : 'nullable|email|unique:users,email'
];
```

### Relationship Constraints

-   **One Account**: Each user belongs to exactly one account (not multiple)
-   **One Role**: Each user has exactly one role template (not multiple)
-   **Account Hierarchy**: Users inherit permissions within their account's hierarchy

## User Status Management

### Active vs Inactive Users

#### Active Users (`is_active: true`)

-   Can log in to the system
-   Require password (unless invitation is being sent)
-   Appear in assignment lists
-   Receive notifications
-   Count toward license limits

#### Inactive Users (`is_active: false`)

-   Cannot log in to system
-   No password required
-   Hidden from most user selections
-   Do not receive notifications
-   Do not count toward license limits

### Visible vs Hidden Users

#### Visible Users (`is_visible: true`)

-   Appear in user selection dropdowns
-   Shown in user management lists
-   Available for ticket assignment
-   Included in reports

#### Hidden Users (`is_visible: false`)

-   Hidden from selection lists (except admins)
-   Not available for new assignments
-   Useful for former employees or archived users
-   Still maintain historical data relationships

## User Form Interface

### Dynamic Field Behavior

The user creation/editing form adapts based on user state with intelligent field visibility:

#### New User - Invitation Mode (Default)

```
✅ Send invitation email    [checked by default]
✅ Email Address *          [visible - required for invitation]
❌ Password fields          [hidden - not required]
❌ Confirm Password         [hidden - not required]
```

#### New User - Direct Creation

```
❌ Send invitation email    [unchecked]
✅ Email Address *          [visible - required for login]
✅ Password *               [visible - required]
✅ Confirm Password *       [visible - required]
```

#### Inactive User (Placeholder)

```
❌ User is active          [unchecked]
❌ Email Address           [visible - optional for inactive users]
❌ Password fields         [hidden - not required regardless of invitation]
❌ Send invitation email   [hidden - cannot invite inactive users]
```

#### UserFormModal Integration

The `UserFormModal.vue` component includes enhanced context-aware behavior:

```javascript
// Smart field visibility based on user state
const showEmailField = computed(() => {
    // Always show email field, but requirements change based on state
    return true;
});

const emailRequired = computed(() => {
    // Email required for active users unless using invitation workflow
    return form.value.is_active && !form.value.send_invitation;
});

const showPasswordFields = computed(() => {
    // Show password fields only for direct creation of active users
    return form.value.is_active && !form.value.send_invitation;
});

const showInvitationToggle = computed(() => {
    // Only show invitation option for active users
    return form.value.is_active;
});
```

#### Account Context Preselection

```javascript
// UserFormModal supports preselected account context
const props = defineProps({
    preselectedAccountId: {
        type: [String, Number],
        default: null,
    },
});

// Auto-populate account when creating users from specific contexts
const resetForm = () => {
    form.value = {
        // ... other fields
        account_id: props.preselectedAccountId || null,
        // ... rest of form
    };
};
```

#### Editing Existing User

```
N/A Send invitation email  [not shown for existing users]
✅ Password                [visible - optional, leave empty to keep current]
✅ Confirm Password        [visible when password provided]
```

### Form Sections

1. **Basic Information**

    - Full Name (required)
    - Email Address (required, unique)
    - Invitation toggle (new users only)
    - Password fields (conditional)

2. **User Preferences**

    - Timezone (defaults to system timezone)
    - Language/Locale

3. **Account Assignment**

    - Primary Account (single selection dropdown)
    - Account hierarchy context

4. **Role Assignment**

    - Role Template (single selection dropdown)
    - Context-aware (Service Provider vs Account User)

5. **Account Status**
    - User is active (login capability)
    - User is visible (appears in lists)

## Default Values

### New User Defaults

```javascript
{
    name: '',
    email: '',
    timezone: Intl.DateTimeFormat().resolvedOptions().timeZone, // System timezone
    locale: 'en',
    is_active: true,
    is_visible: true,
    send_invitation: true  // Invitation-first approach
}
```

### Timezone Detection

The system automatically detects the browser's timezone using JavaScript:

```javascript
Intl.DateTimeFormat().resolvedOptions().timeZone;
// Examples: "America/New_York", "Europe/London", "Asia/Tokyo"
```

## API Validation Rules

### User Creation

```php
// Dynamic validation based on user state and workflow
$emailRequired = $request->boolean('is_active', true) && !$request->boolean('send_invitation', false);
$passwordRequired = !$request->boolean('send_invitation') && $request->boolean('is_active', true);

$rules = [
    'name' => 'required|string|max:255',
    'email' => $emailRequired
        ? 'required|email|unique:users,email'
        : 'nullable|email|unique:users,email',
    'password' => $passwordRequired
        ? 'required|string|min:8|confirmed'
        : 'nullable|string|min:8|confirmed',
    'timezone' => 'nullable|string|max:50',
    'locale' => 'nullable|string|max:10',
    'is_active' => 'boolean',
    'is_visible' => 'boolean',
    'account_id' => 'nullable|exists:accounts,id',
    'role_template_id' => 'nullable|exists:role_templates,id',
    'preferences' => 'nullable|array',
    'send_invitation' => 'boolean'
];
```

#### Validation Matrix

| User State               | Active | Send Invitation | Email Required | Password Required |
| ------------------------ | ------ | --------------- | -------------- | ----------------- |
| **Standard Active User** | ✅ Yes | ❌ No           | ✅ Required    | ✅ Required       |
| **Invitation User**      | ✅ Yes | ✅ Yes          | ✅ Required    | ❌ Optional       |
| **Inactive User**        | ❌ No  | N/A             | ❌ Optional    | ❌ Optional       |
| **Placeholder User**     | ❌ No  | ❌ No           | ❌ Optional    | ❌ Optional       |

### User Updates

```php
$rules = [
    'name' => 'required|string|max:255',
    'email' => ['required', 'email', Rule::unique('users')->ignore($user->id)],
    'password' => 'nullable|string|min:8|confirmed', // Always optional for updates
    'timezone' => 'nullable|string|max:50',
    'locale' => 'nullable|string|max:10',
    'is_active' => 'boolean',
    'is_visible' => 'boolean',
    'account_id' => 'nullable|exists:accounts,id',
    'role_template_id' => 'nullable|exists:role_templates,id',
    'preferences' => 'nullable|array'
];
```

## Database Schema

### Users Table

```sql
CREATE TABLE users (
    id UUID PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NULL,                  -- Nullable for inactive users
    email_verified_at TIMESTAMP NULL,
    password VARCHAR(255) NULL,               -- Nullable for invitation workflow
    account_id UUID NULL,                     -- Single account relationship
    role_template_id UUID NULL,               -- Single role relationship
    preferences JSON NULL,
    timezone VARCHAR(50) DEFAULT 'UTC',
    locale VARCHAR(10) DEFAULT 'en',
    last_active_at TIMESTAMP NULL,
    last_login_at TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT TRUE,
    is_visible BOOLEAN DEFAULT TRUE,          -- Visibility control
    remember_token VARCHAR(100) NULL,
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW(),

    FOREIGN KEY (account_id) REFERENCES accounts(id) ON DELETE SET NULL,
    FOREIGN KEY (role_template_id) REFERENCES role_templates(id) ON DELETE SET NULL
);

-- Partial unique constraint: email is unique only when not null
CREATE UNIQUE INDEX users_email_unique ON users (email) WHERE email IS NOT NULL;
```

#### Migration Details

The email nullable change was implemented with a sophisticated migration:

```php
// Migration: 2025_08_14_151943_make_email_nullable_on_users_table.php
public function up(): void
{
    // Step 1: Drop existing unique constraint
    Schema::table('users', function (Blueprint $table) {
        $table->dropUnique(['email']);
    });

    // Step 2: Make email column nullable
    Schema::table('users', function (Blueprint $table) {
        $table->string('email')->nullable()->change();
    });

    // Step 3: Create PostgreSQL partial unique constraint
    // This ensures email uniqueness only for non-null values
    DB::statement('CREATE UNIQUE INDEX users_email_unique ON users (email) WHERE email IS NOT NULL');
}
```

**Benefits of Partial Unique Constraint:**

-   Multiple users can have `NULL` email addresses
-   Email addresses are still unique when provided
-   Supports both active users (with email) and inactive placeholder users (without email)
-   Database-level integrity maintained

## Permission Integration

### Three-Dimensional Permissions

Users inherit permissions through their role template across three dimensions:

1. **Functional Permissions**: What they can DO

    - `users.create`, `users.edit`, `users.view`, `users.delete`
    - `admin.manage`, `accounts.manage`

2. **Widget Permissions**: What they can SEE on dashboard

    - `widgets.dashboard.user-management`
    - `widgets.dashboard.account-activity`

3. **Page Permissions**: What pages they can ACCESS
    - `pages.admin.users`
    - `pages.settings.roles`

### Role Context

Role templates are context-aware:

-   **Service Provider Context**: Internal company roles
-   **Account User Context**: Customer organization roles

## Security Considerations

### Password Security

-   Minimum 8 characters when required
-   Bcrypt hashing for stored passwords
-   Nullable database field supports invitation workflow
-   Password confirmation required for direct creation

### Email Security

-   Unique email addresses across the system
-   Email verification through invitation process
-   Secure invitation tokens with expiration

### Account Security

-   Users cannot access data outside their account hierarchy
-   Role-based permission inheritance
-   Audit trail for user modifications

## User Lifecycle States

```
┌─────────────┐    send_invitation=true    ┌─────────────┐
│ New User    │ ────────────────────────→ │ Invited     │
│ Created     │                           │ (no password)│
└─────────────┘                           └─────────────┘
       │                                         │
       │ send_invitation=false                   │ user completes
       │ (direct creation)                       │ invitation setup
       ↓                                         ↓
┌─────────────┐                           ┌─────────────┐
│ Active      │ ←─ reactivate ────────── │ Active      │
│ (immediate) │                          │ (verified)  │
└─────────────┘                          └─────────────┘
       │                                         │
       │ is_active = false                       │ is_active = false
       ↓                                         ↓
┌─────────────┐                           ┌─────────────┐
│ Inactive    │                          │ Inactive    │
│ (temp)      │                          │ (temp)      │
└─────────────┘                          └─────────────┘
       │                                         │
       │ is_visible = false                      │ is_visible = false
       ↓                                         ↓
┌─────────────┐                           ┌─────────────┐
│ Hidden      │                          │ Hidden      │
│ (archived)  │                          │ (archived)  │
└─────────────┘                          └─────────────┘
```

## Frontend Components

### UserFormModal.vue

Located: `resources/js/Components/UserFormModal.vue`

Key features:

-   Dynamic form fields based on user state
-   Automatic timezone detection
-   Conditional password requirements
-   Real-time validation
-   Account and role selection dropdowns

### Users/Show.vue

Located: `resources/js/Pages/Users/Show.vue`

Key features:

-   Comprehensive user details
-   Account and role information
-   Activity history
-   Permission overview
-   Action buttons for editing

## API Endpoints

### User Management

```
GET    /api/users                     # List users with filtering
POST   /api/users                     # Create user (with invitation support)
GET    /api/users/{user}              # Get user details
PUT    /api/users/{user}              # Update user
DELETE /api/users/{user}              # Deactivate user (soft delete)

GET    /api/users/assignable          # Get users available for assignment
GET    /api/users/{user}/tickets      # Get user's assigned tickets
GET    /api/users/{user}/time-entries # Get user's time entries
GET    /api/users/{user}/activity     # Get user activity analytics
GET    /api/users/{user}/accounts     # Get user's account information
```

## Best Practices

### User Creation

1. **Default to Invitation Workflow**: Always use invitation emails for new users
2. **Set Appropriate Timezone**: Use system detection or ask user for timezone
3. **Single Account Assignment**: Users should belong to exactly one account
4. **Single Role Assignment**: Users should have exactly one role template
5. **Meaningful Names**: Use full display names, not usernames

### User Management

1. **Deactivate vs Delete**: Use `is_active: false` instead of hard deletion
2. **Hide vs Remove**: Use `is_visible: false` for archived users
3. **Account Consistency**: Ensure users are assigned to accounts they should access
4. **Role Appropriateness**: Match role templates to user responsibilities

### Security

1. **Principle of Least Privilege**: Assign minimal necessary permissions
2. **Regular Review**: Periodically audit user permissions and account assignments
3. **Invitation Expiry**: Implement invitation token expiration
4. **Activity Monitoring**: Track user login and activity patterns

## Integration Points

### Account Hierarchy

Users inherit access to subsidiary accounts within their account's hierarchy. See [Account Hierarchy Documentation](account-hierarchy.md).

### Domain Mapping

Automatic user assignment based on email domain patterns. See [Domain Mapping Documentation](domain-mapping.md).

### Timer System

Users can manage timers and time entries based on their permissions. See [Timer System Documentation](timer-enhancements.md).

### Ticket System

Users can be assigned to tickets based on their account and role permissions. See [Service Tickets Documentation](tickets.md).

---

**User Management System** - Complete lifecycle management with invitation workflows and three-dimensional permission integration.

_Updated: August 14, 2025 - Added nullable email support with partial unique constraints, enhanced UserFormModal with context-aware preselection, and comprehensive validation matrix for all user creation scenarios_
