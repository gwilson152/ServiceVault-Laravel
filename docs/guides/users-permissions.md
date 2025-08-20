# Users & Permissions Guide

Complete guide to Service Vault's user management and three-dimensional permission system.

## User Management

### User Types

Service Vault supports multiple user types for different roles:

1. **Super Admin**: Complete system access and configuration
2. **Agent**: Service delivery team members  
3. **Manager**: Account management and oversight
4. **Account User**: Customer/client users
5. **Employee**: Internal staff with limited access

### Creating Users

**Settings → User Management**:
1. **Add New User**: Click "Create User" button
2. **Basic Information**:
   - Name (required)
   - Email (optional - supports users without email)
   - User Type (determines base permissions)
   - Account Assignment (for Account Users)
3. **Role Assignment**: Select role template or custom permissions
4. **Initial Status**: Active/Inactive, Visible/Hidden

**Recent Enhancement**: Users can be created without email addresses for placeholder/inactive users with partial unique constraints.

### User Invitation System

**Email-Based Invitations**:
1. Create user with email address
2. System sends invitation email
3. User clicks link to set password and activate account
4. Automatic account assignment based on email domain (if configured)

**Manual Account Creation**:
- Users can be created without email
- Password set manually by admin
- Useful for temporary or service accounts

## Three-Dimensional Permission System

Service Vault uses a unique **Three-Dimensional Permission System**: **Functional** (what users can DO) + **Widget** (what they SEE on dashboard) + **Page** (what pages they can ACCESS).

### 1. Functional Permissions (What users can DO)

Core functional permissions:

**Ticket Management**:
- `tickets.view` - View own tickets
- `tickets.view.account` - View account tickets  
- `tickets.view.all` - View all tickets (admin)
- `tickets.create` - Create new tickets
- `tickets.assign` - Assign tickets to agents
- `tickets.manage` - Full ticket management

**Time Tracking**:
- `timers.view` - View own timers
- `timers.create` - Start/stop timers
- `timers.manage` - Manage all timers
- `time.view` - View time entries
- `time.approve` - Approve time entries

**Billing & Financial**:
- `billing.view` - View billing rates
- `billing.manage` - Create/edit billing rates
- `invoices.view` - View invoices
- `invoices.manage` - Create/manage invoices

**Administrative**:
- `admin.read` - Read-only admin access
- `admin.write` - Full admin access
- `system.configure` - System configuration
- `users.manage` - User management

### 2. Widget Permissions (What users can SEE on dashboard)

Dashboard widget visibility:

- `widgets.dashboard.tickets` - Tickets widget
- `widgets.dashboard.timers` - Active timers widget
- `widgets.dashboard.time_entries` - Time entries widget
- `widgets.dashboard.billing` - Billing overview widget
- `widgets.dashboard.accounts` - Account summary widget

### 3. Page Permissions (What pages users can ACCESS)

Page-level access control:

- `pages.tickets.list` - Access tickets list page
- `pages.tickets.manage` - Access ticket management
- `pages.users.manage` - Access user management
- `pages.billing.rates` - Access billing rate management
- `pages.reports.financial` - Access financial reports

### Key Development Pattern

```php
// Check permission across all dimensions
$user->hasPermission('tickets.view.account');      // Functional
$user->hasPermission('widgets.dashboard.tickets'); // Widget  
$user->hasPermission('pages.tickets.manage');      // Page Access
```

**Permission Storage**: Role templates store three separate arrays (`permissions`, `widget_permissions`, `page_permissions`) with unified checking via `hasPermission()` method.

**Centralized Permission Service**: Use `App\Services\PermissionService` for all agent filtering and permission logic to ensure consistency across the application.

## Feature-Specific Agent Permission System

Service Vault implements a comprehensive feature-specific agent permission system that provides granular control over which users can act as agents for different system features.

### Core Agent Permissions

- `timers.act_as_agent` - Timer creation and assignment
- `tickets.act_as_agent` - Ticket assignment and management  
- `time.act_as_agent` - Time entry creation and assignment
- `billing.act_as_agent` - Billing responsibility assignment

### Multi-Layer Agent Determination

1. **Primary**: Users with `user_type = 'agent'`
2. **Secondary**: Users with feature-specific `*.act_as_agent` permissions
3. **Tertiary**: Internal account users with relevant fallback permissions

### API Integration

```bash
# Feature-specific agent endpoints
GET /api/users/agents?agent_type=timer          # Timer agents
GET /api/users/agents?agent_type=time           # Time entry agents  
GET /api/users/assignable                       # Ticket agents (uses tickets.act_as_agent)
GET /api/users/billing-agents                   # Billing agents
```

### Component Integration

```vue
<!-- StartTimerModal uses timer agent type -->
<UnifiedSelector type="agent" agent-type="timer" />

<!-- UnifiedTimeEntryDialog uses time agent type -->
<UnifiedSelector type="agent" agent-type="time" />
```

### Permission Helper Methods

```php
// In UserController
private function getRequiredAgentPermission(string $agentType): string
{
    return match($agentType) {
        'timer' => 'timers.act_as_agent',
        'ticket' => 'tickets.act_as_agent',
        'time' => 'time.act_as_agent', 
        'billing' => 'billing.act_as_agent',
        default => 'timers.act_as_agent'
    };
}
```

## Role Templates

### Predefined Role Templates

**Super Admin**:
- Complete system access
- All functional, widget, and page permissions
- System configuration and user management
- Multi-account access

**Agent**:
- Ticket assignment and management
- Timer creation and tracking
- Time entry management
- Account-scoped access

**Manager**:
- Team oversight and management
- Approval workflows
- Account management
- Reporting access

**Account User**:
- Limited account-scoped access
- Ticket creation and viewing
- Basic time tracking (if enabled)
- Self-service features

### Custom Role Templates

**Creating Custom Roles**:
1. **Settings → Role Management**
2. **Create New Role Template**
3. **Permission Selection**:
   - Choose functional permissions
   - Select widget visibility
   - Define page access
4. **Save & Apply**: Assign to users

**Role Template Benefits**:
- **Consistency**: Standardized permission sets
- **Scalability**: Easy to apply to multiple users
- **Maintenance**: Update template to update all assigned users
- **Compliance**: Audit trail for permission changes

## Account-Based Access Control

### Account Hierarchy

Service Vault supports business account relationships:

- **Parent Accounts**: Can have multiple child accounts
- **Child Accounts**: Inherit permissions and settings from parent
- **Account Isolation**: Complete data separation between unrelated accounts
- **Cross-Account Access**: Super Admins and designated users can access multiple accounts

### Domain-Based Assignment

**Automatic User-to-Account Mapping**:

Configure domain patterns to automatically assign users to accounts:

**Settings → Domain Mappings**:
1. **Domain Pattern**: `*.company.com`
2. **Target Account**: Company Account
3. **Priority**: Matching order for overlapping patterns
4. **Auto-Assignment**: Automatic vs. manual approval

**Use Cases**:
- Customer users automatically assigned to their company account
- Email domain-based access control
- Streamlined onboarding process

## Security & Best Practices

### Permission Strategy

**Principle of Least Privilege**:
- Grant minimum permissions required for user role
- Regular permission audits and reviews
- Remove unused permissions promptly
- Document permission requirements

**Account Isolation**:
- Users can only access data within their authorized accounts
- Cross-account access requires explicit permissions
- Admin oversight for multi-account users

### User Lifecycle Management

**User States**:
- **Active/Inactive**: Can/cannot log in
- **Visible/Hidden**: Appears/hidden in user lists
- **Email/No Email**: Full account vs. placeholder user

**User Deactivation Process**:
1. Set user to inactive (prevents login)
2. Remove from active role assignments
3. Optionally hide from user lists
4. Maintain audit trail of changes

## API Integration

### User Management Endpoints

```bash
# User CRUD
GET    /api/users                      # List users with filtering
POST   /api/users                      # Create new user  
GET    /api/users/{user}               # Show user details
PUT    /api/users/{user}               # Update user
DELETE /api/users/{user}               # Deactivate user

# Role & Permission Management
GET    /api/role-templates             # List role templates
POST   /api/role-templates             # Create role template
PUT    /api/users/{user}/role          # Assign role to user
GET    /api/users/{user}/permissions   # Get user permissions

# Agent Assignment  
GET    /api/users/agents               # Get available agents
GET    /api/users/agents?agent_type=timer  # Get timer-specific agents
```

### Permission Checking

**Frontend Permission Checking**:
```javascript
// Check functional permission
user.hasPermission('tickets.create')

// Check widget permission  
user.hasPermission('widgets.dashboard.tickets')

// Check page permission
user.hasPermission('pages.billing.rates')
```

**Backend Authorization**:
```php
// Policy-based authorization
$this->authorize('create', Ticket::class);

// Permission-based checking
if ($user->hasPermission('tickets.manage')) {
    // Allow action
}
```

For technical implementation details, see [Permission Architecture](../technical/architecture.md#permission-system).