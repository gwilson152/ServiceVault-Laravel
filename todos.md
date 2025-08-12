# Service Vault - Todo List

## Recent Completions ✅

-   [x] **Implement Collapsible App Menu with Pin and User Preference Storage**
    
    Successfully implemented a collapsible sidebar navigation with the following features:
    - Smart width transitions between 256px (expanded) and 80px (collapsed)
    - Pin/collapse button with chevron icons and smooth animations
    - Persistent user preference storage using localStorage
    - Logo adaptation (full "Service Vault" text vs "SV" initials)
    - Responsive design that only applies to desktop (md+ breakpoints)
    - Enhanced visual effects with hover states and scale animations
    - Navigation item tooltips when collapsed
    - Additional header toggle button when sidebar is collapsed

-   [x] **Fix Navigation Icons and Visual Enhancement**
    
    Resolved the navigation icons issue and enhanced the visual design:
    - **Icon Mapping Fix**: Updated iconMap to match exact icon names from NavigationService
    - **Missing Icons Added**: Imported and mapped CalendarIcon, BuildingOfficeIcon, CurrencyDollarIcon
    - **Visual Improvements**: Added hover effects, smooth transitions, subtle shadows
    - **Animation Effects**: Navigation items now slide on hover, improved button interactions
    - **Backward Compatibility**: Maintained legacy icon mappings for existing references
    - **Proper Icon Display**: All navigation items now show correct, contextual icons

-   [x] **Complete TicketOverviewWidget Modal Integration**
    
    Updated TicketOverviewWidget to properly integrate with existing components:
    - Imports and uses existing CreateTicketModal component
    - Proper navigation to ticket detail pages (/tickets/{id})
    - Navigation to tickets index page (/tickets)
    - Ticket creation handling with refresh and navigation
    - Reduced code redundancy by reusing modal component

-   [x] **Comprehensive Billing Workflow Implementation**
    
    Successfully implemented a complete billing system with the following features:
    - **Fresh Database Architecture**: New tables for invoices, payments, billing settings, tax configurations
    - **Complete Invoice System**: Invoice generation from time entries and ticket addons with automatic numbering
    - **Payment Tracking**: Full payment lifecycle management with multiple payment methods
    - **Enhanced Time Entries Page**: Replaced placeholder with fully functional interface including bulk approvals
    - **API Integration**: Comprehensive billing API endpoints with proper authentication and authorization
    - **Settings Integration**: Invoice configuration components for company details and payment settings

## Permission System Refactoring Plan 🔧

**Priority: HIGH** - Performance and architecture cleanup required

### Overview
The codebase contains inefficient permission checking patterns that create unnecessary database queries. Currently using `$user->roleTemplates()->whereJsonContains('permissions', 'x')->exists()` instead of the efficient `$user->hasPermission('x')` methods already available in the User model.

### Current Issues
- **Performance**: Multiple identical database queries for permission checks
- **Inconsistency**: Mix of efficient and inefficient permission checking methods
- **Bloat**: Backward compatibility method that shouldn't exist
- **Architecture**: Single role template per user but code suggests multiple templates

### Affected Files Analysis
- `app/Http/Controllers/Portal/CustomerPortalController.php` (5 instances)
- `app/Http/Controllers/Api/TimeEntryController.php` (20+ instances)
- `app/Http/Controllers/Dashboard/ManagerDashboardController.php` (3 instances)
- `app/Http/Controllers/UserInvitationController.php` (9 instances)
- `app/Http/Resources/TimeEntryResource.php` (7 instances)

### Refactoring Tasks

-   [ ] **Phase 1: Analysis & Permission Mapping**
    - Catalog all permission strings used in `whereJsonContains()` calls
    - Map them to existing User model methods where possible
    - Identify missing permission methods that need to be added to User model

-   [ ] **Phase 2: Controller Refactoring** 
    - Replace `roleTemplates()->whereJsonContains('permissions', 'admin.manage')->exists()` with `hasPermission('admin.manage')`
    - Replace `roleTemplates()->whereJsonContains('permissions', 'teams.manage')->exists()` with `hasPermission('teams.manage')`
    - Use `hasAnyPermission(['perm1', 'perm2'])` for complex permission checks
    - **Files**: CustomerPortalController, TimeEntryController, ManagerDashboardController, UserInvitationController

-   [ ] **Phase 3: Resource Refactoring**
    - Update `app/Http/Resources/TimeEntryResource.php` to use efficient permission checks
    - Move permission logic out of resources where possible

-   [ ] **Phase 4: Clean Up & Testing**
    - Remove bloated `roleTemplates()` method from User model
    - Add any missing efficient permission methods to User model
    - Performance testing to confirm query reduction
    - Verify all permission checks still work correctly

### Expected Benefits
- **Reduced Database Queries**: From N queries per permission check to 0 (using loaded relationships)
- **Better Performance**: Faster page loads and API responses
- **Improved Maintainability**: Single source of truth for permission logic
- **Consistent Architecture**: All permission checks use the same efficient pattern

## Frontend Enhancements

-   [ ] **Remove Cards View and Enhance Table View on Tickets Page**

    Streamline the tickets page by removing the cards view option and focusing on a dense, business-oriented table layout that maximizes information density and improves scanning efficiency.

    **Key Changes:**

    -   Remove cards/grid view toggle and related components
    -   Enhance table view with compact row height and optimized spacing
    -   Improve data density while maintaining readability
    -   Add hover states and selection indicators for better interaction
    -   Optimize column widths and content alignment
    -   Implement business-focused styling (clean lines, professional appearance)
    -   Maintain responsive behavior for mobile devices
    -   Preserve all existing functionality (filtering, sorting, pagination)

    **Benefits:**

    -   More tickets visible per screen
    -   Faster data scanning and comparison
    -   Professional enterprise appearance
    -   Reduced UI complexity and maintenance overhead
    -   Better performance with single view mode

-   [ ] **Create Comprehensive Billing Management Interface**
    - Invoice generation wizard with time entries and ticket addon selection
    - Invoice management dashboard with status tracking
    - Payment recording and tracking interface
    - Billing reports and analytics page
    - Integration with existing timer and ticket systems

-   [ ] **Create Page-Specific Widget Panel in App Menu**

    Create a collapsible widget panel in the right side of the application header that displays contextual information and actions specific to the current page. The panel will integrate with Service Vault's existing three-dimensional permission system and widget architecture.

    **Key Features:**

    -   Slide-out panel from right side of header
    -   Page-specific widgets loaded dynamically by each page
    -   Permission-aware widget visibility
    -   Responsive design (desktop panel, tablet modal, mobile bottom sheet)
    -   Integration with existing widget system and permissions
    -   Persistent state across page navigation

    **Widget Categories by Page:**

    -   **Dashboard**: Account switcher, widget layout controls, system health summary
    -   **Tickets Management**: Filters, bulk actions, status statistics, recent activity
    -   **Time Tracking**: Active timers controls, time entry shortcuts, approval actions
    -   **User Management**: Invitation shortcuts, role assignment, account switching
    -   **Role Templates**: Permission previews, widget assignment shortcuts, user assignment stats
    -   **System Admin**: Health monitoring, configuration shortcuts, activity logs

-   [ ] **Enhance App Menu with Contextual Information**

    Improve the application header to display contextual information about the current page and entity being viewed. This creates a more intuitive navigation experience by showing users exactly where they are and what they're working with.

    **Key Features:**

    -   Context-aware breadcrumb navigation
    -   Entity-specific information display (ticket ID/title, user name, account details)
    -   Status indicators and badges
    -   Quick action buttons based on current context
    -   Smart back navigation
    -   Responsive design that adapts to screen size

    **Context Display Examples:**

    -   **Ticket Pages**: Show ticket ID, title, status badge, priority, assignee
    -   **User Management**: Display user name, email, role indicators, account associations
    -   **Account Context**: Show account name, type, subsidiary count, active users
    -   **Role Templates**: Display role name, context, user count, permissions summary

    **Benefits:**

    -   Users always know where they are in the application
    -   Essential information available at a glance
    -   Faster navigation with context-aware controls
    -   More professional and intuitive interface