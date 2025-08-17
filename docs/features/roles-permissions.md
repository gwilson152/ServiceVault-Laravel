# Roles & Permissions Management

Service Vault implements a comprehensive **Three-Dimensional Permission System** that provides granular control over user access across functional operations, dashboard widgets, and page navigation.

## System Overview

The permission system is built on three interconnected dimensions:

1. **Functional Permissions** - Traditional feature-based access control
2. **Widget Permissions** - Granular dashboard widget access control  
3. **Page Permissions** - Route and page-level access control

This three-dimensional approach ensures maximum flexibility while maintaining security and usability.

## Role Template Architecture

### Core Concepts

**Role Templates** serve as blueprints for user permissions and dashboard configurations:
- Define which functions users can access
- Control which widgets appear on dashboards
- Determine which pages users can navigate to
- Configure default dashboard layouts
- Support unlimited customization

**Context Awareness:**
- `service_provider` - For internal staff managing customer accounts
- `account_user` - For customer organization staff  
- `both` - Roles that work in either context

### Default Role Templates

**Service Provider Roles:**
- **Super Admin** - Complete system access (non-modifiable)
- **Admin** - Full service provider administration
- **Manager** - Service oversight and team management
- **Employee** - Service delivery and time tracking

**Customer Account Roles:**
- **Account Manager** - Primary account + subsidiary access
- **Account User** - Single account access only

All role templates except Super Admin are fully customizable.

## Three-Dimensional Permission Management

### 1. Functional Permissions

Traditional feature-based permissions controlling system operations:

```php
// Administration
'admin.manage'          // Full administrative access
'system.configure'      // System-wide configuration
'users.manage'          // User account management
'accounts.manage'       // Customer account management

// Service Delivery
'tickets.view.all'      // View all tickets across accounts
'tickets.view.account'  // View tickets for accessible accounts
'tickets.create'        // Create new service tickets
'tickets.assign'        // Assign tickets to team members

// Time Management  
'time.track'           // Track time on service tickets
'time.approve'         // Approve time entries
'timers.manage.team'   // Manage team member timers

// Billing & Financial
'billing.view.account' // View account billing information
'billing.manage'       // Manage billing and invoicing
```

### 2. Widget Permissions

Granular control over dashboard widget visibility:

```php
// Widget-specific permissions
'widgets.dashboard.system-health'      // System Health widget
'widgets.dashboard.system-stats'       // System Statistics widget  
'widgets.dashboard.user-management'    // User Management widget
'widgets.dashboard.ticket-overview'    // Service Tickets Overview
'widgets.dashboard.time-tracking'      // Active Timers widget
'widgets.dashboard.billing-overview'   // Billing Overview widget

// Global widget permissions
'widgets.configure'                    // Configure widget settings
'dashboard.customize'                  // Full dashboard customization
```

### 3. Page Permissions

Route and navigation-level access control:

```php
// Administrative pages
'pages.admin.dashboard'    // Admin dashboard access
'pages.admin.users'        // User management pages
'pages.admin.accounts'     // Account management pages
'pages.admin.settings'     // System settings pages

// Service delivery pages
'pages.tickets.index'      // Ticket listing page
'pages.tickets.create'     // Ticket creation page
'pages.tickets.manage'     // Ticket management interface

// Portal pages
'pages.portal.dashboard'   // Customer portal dashboard
'pages.portal.tickets'     // Customer ticket interface
'pages.portal.billing'     // Customer billing interface
```

## Management Interfaces

### Role Template Management

**Location:** `/roles` (Admin access required)

**Features:**
- ✅ **Complete CRUD Operations** - Create, read, update, delete role templates
- ✅ **Permission Matrix Editor** - Visual three-dimensional permission assignment with comprehensive descriptions
- ✅ **Enhanced User Experience** - No-delay tooltips with smart positioning and detailed permission explanations
- ✅ **Context Configuration** - Service provider, account user, or both
- ✅ **Template Cloning** - Duplicate existing templates for customization
- ✅ **Usage Statistics** - See how many users are assigned to each role

**Interface Components:**
- Role template listing with permission counts
- Three-dimensional permission editor with category grouping and comprehensive descriptions
- Visual permission matrix with checkboxes, bulk selection, and smart tooltips
- Context switcher for role applicability
- User assignment statistics and management
- No-delay tooltip system with auto-positioning to prevent clipping

### Dashboard Preview System

**Purpose:** Allow administrators to see exactly what dashboard experience users will have based on their role template.

**Key Features:**
- ✅ **Live Dashboard Preview** - Real-time preview of user dashboard experience
- ✅ **Context Switching** - Preview both Service Provider and Account User experiences
- ✅ **Mock Data Generation** - Realistic sample data for all widgets
- ✅ **Multiple View Modes** - Dashboard, Widgets, Navigation, and Layout views
- ✅ **Permission Validation** - Shows exactly which widgets and features are available

**Preview Modes:**
1. **Dashboard View** - Complete dashboard experience with mock widgets
2. **Widgets View** - List of available widgets with configuration details
3. **Navigation View** - Menu structure and accessible pages
4. **Layout View** - Visual grid layout with widget positioning

### Widget Assignment Interface

**Purpose:** Advanced drag & drop interface for assigning widgets to role templates and designing dashboard layouts.

**Key Features:**
- ✅ **Drag & Drop Widget Management** - Visual assignment of widgets to role templates
- ✅ **Visual Layout Designer** - 12-column grid system with drag, drop, and resize
- ✅ **Widget Configuration** - Per-widget settings (enabled by default, configurable, etc.)
- ✅ **Category Filtering** - Organize widgets by category for easier management
- ✅ **Real-Time Validation** - Immediate feedback on changes and conflicts
- ✅ **Change Tracking** - Unsaved changes indicator with confirmation dialogs

**Interface Modes:**
1. **Widget Selection Mode** - Assign/unassign widgets with drag & drop
2. **Layout Design Mode** - Visual layout designer with grid positioning

**Widget Configuration Options:**
- **Enabled by Default** - Automatically shown on new user dashboards
- **Configurable** - Users can modify widget settings
- **Display Order** - Sequence of widgets in listings
- **Widget Size** - Default width and height in grid system

## Permission Inheritance

### Account Hierarchy Support

The permission system supports hierarchical account relationships:

**Account Manager Role:**
- Access to primary account + all subsidiary accounts
- Inherited permissions cascade down account tree
- Can manage users within account hierarchy
- Billing and reporting access across all subsidiary accounts

**Account User Role:**
- Access limited to single assigned account
- No hierarchy navigation or cross-account access
- Account-scoped data and operations only

### Super Admin Inheritance

**Super Admin Role Characteristics:**
- **Non-Modifiable** - System-protected role that cannot be edited
- **Dynamic Permissions** - Automatically inherits all available permissions
- **System Access** - Complete access to all system functions, widgets, and pages
- **Override Capability** - Can access any data or perform any operation
- **Audit Trail** - All Super Admin actions are logged for security

## API Integration

### Role Template Management

```bash
# CRUD Operations
GET    /api/role-templates                      # List all role templates
POST   /api/role-templates                      # Create new role template  
GET    /api/role-templates/{roleTemplate}      # Get specific role template
PUT    /api/role-templates/{roleTemplate}      # Update role template
DELETE /api/role-templates/{roleTemplate}      # Delete role template
POST   /api/role-templates/{roleTemplate}/clone # Clone existing template

# Permission Management
GET    /api/role-templates/permissions/available # Get all available permissions
GET    /api/widget-permissions                  # Get widget permissions
POST   /api/widget-permissions/sync             # Sync widget permissions
```

### Dashboard Preview System

```bash
# Preview Endpoints
GET /api/role-templates/{roleTemplate}/preview/dashboard   # Full dashboard preview
GET /api/role-templates/{roleTemplate}/preview/widgets     # Widget preview  
GET /api/role-templates/{roleTemplate}/preview/navigation  # Navigation preview
GET /api/role-templates/{roleTemplate}/preview/layout      # Layout preview
```

### Widget Assignment Interface

```bash  
# Widget Management
GET /api/role-templates/{roleTemplate}/widgets  # Get current widget assignments
PUT /api/role-templates/{roleTemplate}/widgets  # Update assignments and layout
```

## Security Features

### Authorization Framework

**Policy-Based Access Control:**
- All management interfaces protected by Laravel policies
- Multi-level authorization checks (user → role → template → permission)
- Context-aware access validation
- Automatic permission inheritance and cascading

**Data Validation:**
- Comprehensive input validation for all permission operations
- Cross-dimensional permission consistency checks
- Role template modification restrictions for system roles
- Database transaction support for atomic updates

### Audit & Logging

**Permission Change Tracking:**
- Complete audit trail of all role template modifications
- User attribution for all permission changes
- Timestamp tracking for compliance and security
- Change diff logging for detailed audit reports

## Advanced Features

### Mock User Service

For dashboard preview functionality, the system includes sophisticated mock data generation:

**Mock User Generation:**
- Context-appropriate user profiles
- Realistic names and email addresses based on role type
- Account context simulation (Service Provider vs Account User)
- Permission set validation and testing

**Mock Data Types:**
- Service ticket data with various statuses and priorities
- Time tracking data with active timers and entries
- Account activity and communication logs
- Billing information and financial summaries
- User management data and statistics

### Widget Registry System

**Auto-Discovery:**
- Automatic scanning for widget components
- Dynamic registration of widget permissions
- Filesystem-based widget detection
- Hot-reload support in development

**Permission Mapping:**
- Automatic generation of widget-specific permissions
- Category-based permission grouping  
- Context-aware widget filtering
- Dependency resolution for widget requirements

## Usage Examples

### Creating a Custom Role Template

```php
// API Example: Create a custom "Senior Technician" role
POST /api/role-templates
{
    "name": "Senior Technician",
    "description": "Experienced technician with team oversight",
    "context": "service_provider",
    "permissions": [
        "tickets.view.all",
        "tickets.create", 
        "tickets.assign",
        "time.track",
        "time.approve"
    ],
    "widget_permissions": [
        "widgets.dashboard.ticket-overview",
        "widgets.dashboard.time-tracking", 
        "widgets.dashboard.my-tickets"
    ],
    "page_permissions": [
        "pages.tickets.index",
        "pages.tickets.create",
        "pages.time.entries"
    ]
}
```

### Assigning Widgets with Layout

```php
// API Example: Configure widgets and layout
PUT /api/role-templates/{roleTemplate}/widgets
{
    "widgets": [
        {
            "widget_id": "ticket-overview",
            "enabled": true,
            "enabled_by_default": true,
            "configurable": true,
            "widget_config": {}
        },
        {
            "widget_id": "time-tracking", 
            "enabled": true,
            "enabled_by_default": true,
            "configurable": false,
            "widget_config": {}
        }
    ],
    "layout": [
        {
            "i": "ticket-overview",
            "x": 0,
            "y": 0, 
            "w": 8,
            "h": 4,
            "widget_config": {}
        },
        {
            "i": "time-tracking",
            "x": 8,
            "y": 0,
            "w": 4, 
            "h": 4,
            "widget_config": {}
        }
    ]
}
```

## Best Practices

### Role Template Design

1. **Start with Defaults** - Begin with existing role templates and customize
2. **Context Clarity** - Clearly define service_provider vs account_user contexts
3. **Principle of Least Privilege** - Grant minimum permissions necessary
4. **Regular Review** - Audit role templates and permissions regularly
5. **Documentation** - Maintain clear descriptions for all custom roles

### Widget Management

1. **User Experience Focus** - Design widget layouts for user productivity
2. **Progressive Disclosure** - Show most important widgets by default
3. **Context Awareness** - Different widgets for different user contexts
4. **Performance Consideration** - Limit number of widgets per dashboard
5. **Customization Balance** - Allow customization while maintaining consistency

### Permission Architecture

1. **Three-Dimensional Thinking** - Consider functional, widget, and page permissions together
2. **Hierarchical Planning** - Design permissions with account hierarchy in mind
3. **Future Flexibility** - Build permission structure to accommodate future features
4. **Testing Coverage** - Test all permission combinations thoroughly
5. **User Training** - Provide clear documentation for administrators

## Troubleshooting

### Common Issues

**Permission Not Working:**
- Check all three permission dimensions (functional, widget, page)
- Verify role template is correctly assigned to user
- Confirm permission exists in available permissions list
- Clear permission caches if necessary

**Widget Not Appearing:**
- Verify widget permission is granted
- Check widget context matches user context
- Ensure widget is enabled in role template
- Confirm widget component exists and is registered

**Dashboard Layout Issues:**
- Validate layout JSON structure
- Check for overlapping widget positions
- Verify widget IDs match assigned widgets
- Test responsive layout on different screen sizes

### Debug Commands

```bash
# Clear permission caches
php artisan cache:clear
php artisan route:clear
php artisan config:clear

# Check user permissions
php artisan tinker
>>> $user = User::find(1);
>>> $user->getAllPermissions();

# Verify widget registry
php artisan tinker
>>> app(WidgetRegistryService::class)->getDiscoveryStats();
```

---

**Service Vault Roles & Permissions** - Complete three-dimensional permission management system with live dashboard preview and advanced widget assignment capabilities.