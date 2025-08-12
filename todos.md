# Service Vault - Todo List

## Frontend Enhancements

-   [ ] **Create Page-Specific Widget Panel in App Menu**

    **Implementation Overview:**
    Create a collapsible widget panel in the right side of the application header that displays contextual information and actions specific to the current page. The panel will integrate with Service Vault's existing three-dimensional permission system and widget architecture.

    **Technical Architecture:**

    ### 1. Panel Integration with AppLayout.vue

    ```vue
    <!-- Add to AppLayout.vue header section -->
    <div class="flex items-center space-x-4">
      <!-- Existing user menu -->
      <Menu as="div" class="relative ml-3">...</Menu>
    
      <!-- New Widget Panel Toggle -->
      <PageWidgetPanel />
    </div>
    ```

    ### 2. Core Components

    -   **`PageWidgetPanel.vue`** - Main panel component with slide-out animation
    -   **`PageContextProvider.vue`** - Context provider for page-specific data
    -   **`usePageWidget.js`** - Composable for registering page widgets
    -   **`PageWidgetLoader.vue`** - Dynamic widget loader (similar to existing WidgetLoader)

    ### 3. Permission Integration

    Leverage existing three-dimensional permission system:

    ```php
    // Page widget permissions (extend existing system)
    'widgets.page.tickets-management'     // Tickets page-specific widgets
    'widgets.page.timer-controls'         // Timer management widgets
    'widgets.page.account-overview'       // Account detail widgets
    'widgets.page.role-management'        // Role/permission widgets
    'widgets.page.system-status'          // System admin widgets
    ```

    ### 4. Page Widget Registration Pattern

    ```vue
    <!-- In individual pages like Tickets/Index.vue -->
    <script setup>
    import { usePageWidget } from "@/Composables/usePageWidget.js";

    const { registerWidget, unregisterWidget } = usePageWidget();

    onMounted(() => {
        // Register page-specific widgets
        registerWidget({
            id: "ticket-filters",
            component: "TicketFiltersWidget",
            title: "Quick Filters",
            permissions: ["widgets.page.tickets-management"],
            data: reactive({ filters: props.filters }),
        });

        registerWidget({
            id: "ticket-stats",
            component: "TicketStatsWidget",
            title: "Page Statistics",
            permissions: ["tickets.view.account"],
            data: computed(() => ({
                totalTickets: filteredTickets.value.length,
                openTickets: openTickets.value.length,
            })),
        });
    });

    onUnmounted(() => {
        unregisterWidget(["ticket-filters", "ticket-stats"]);
    });
    </script>
    ```

    ### 5. Widget Categories by Page

    -   **Dashboard**: Account switcher, widget layout controls, system health summary
    -   **Tickets Management**: Filters, bulk actions, status statistics, recent activity
    -   **Time Tracking**: Active timers controls, time entry shortcuts, approval actions
    -   **User Management**: Invitation shortcuts, role assignment, account switching
    -   **Role Templates**: Permission previews, widget assignment shortcuts, user assignment stats
    -   **System Admin**: Health monitoring, configuration shortcuts, activity logs

    ### 6. API Integration

    ```php
    // Extend existing widget API for page widgets
    GET /api/page-widgets/{page}              // Get available page widgets
    GET /api/page-widgets/{page}/data         // Get widget data for page
    POST /api/page-widgets/preferences        // Save user widget preferences
    ```

    ### 7. Responsive Design

    -   **Desktop**: Slide-out panel from right (300px width)
    -   **Tablet**: Modal overlay with widget grid
    -   **Mobile**: Bottom sheet with swipe gestures

    ### 8. State Management

    ```javascript
    // Global state for panel
    const pageWidgetState = reactive({
        isOpen: false,
        currentPage: null,
        registeredWidgets: new Map(),
        userPreferences: {},
        permissionCache: new Map(),
    });
    ```

    ### 9. Implementation Benefits

    -   **Contextual Information**: Page-relevant data always accessible
    -   **Reduced Cognitive Load**: Related actions grouped in consistent location
    -   **Permission-Aware**: Only shows widgets user can access
    -   **Persistent**: Panel state maintained across navigation
    -   **Extensible**: Easy to add new page widgets
    -   **Performant**: Lazy loading and permission caching

    ### 10. Development Phases

    1. **Phase 1**: Core panel component and provider
    2. **Phase 2**: Page widget registration system
    3. **Phase 3**: Permission integration and caching
    4. **Phase 4**: Default widgets for major pages
    5. **Phase 5**: User preferences and customization
    6. **Phase 6**: Mobile responsive design

    **Dependencies:**

    -   Existing Widget System (`WidgetRegistryService`, `WidgetLoader`)
    -   Three-Dimensional Permission System (`RoleTemplate`, permission checking)
    -   Navigation System (`useNavigationQuery`, route context)
    -   Persistent Layout System (`AppLayout.vue`, Inertia.js persistence)

-   [ ] **Enhance App Menu with Contextual Information**

    **Implementation Overview:**
    Improve the application header to display contextual information about the current page and entity being viewed. This creates a more intuitive navigation experience by showing users exactly where they are and what they're working with.

    **Technical Architecture:**

    ### 1. Context-Aware Header Enhancement

    ```vue
    <!-- Enhance AppLayout.vue header section -->
    <header class="bg-white shadow-sm border-b border-gray-200">
      <div class="flex justify-between items-center px-4 sm:px-6 lg:px-8 h-16">
        <div class="flex items-center space-x-4">
          <!-- Mobile menu button -->
          <button @click="sidebarOpen = true" class="md:hidden">...</button>
    
          <!-- Enhanced Breadcrumb/Context Display -->
          <PageContextBreadcrumb />
        </div>
    
        <!-- Right side with user menu and page widget panel -->
        <div class="flex items-center space-x-4">
          <PageWidgetPanel />
          <Menu as="div" class="relative ml-3">...</Menu>
        </div>
      </div>
    </header>
    ```

    ### 2. Core Components

    -   **`PageContextBreadcrumb.vue`** - Dynamic breadcrumb showing current context
    -   **`usePageContext.js`** - Composable for managing page context
    -   **`ContextualTitle.vue`** - Smart title component with entity information

    ### 3. Context Display Patterns

    **Ticket Pages:**

    ```vue
    <!-- Example: /tickets/12345 -->
    <div class="flex items-center space-x-2">
      <TicketIcon class="h-5 w-5 text-gray-400" />
      <span class="text-gray-500">Ticket</span>
      <ChevronRightIcon class="h-4 w-4 text-gray-300" />
      <span class="font-medium text-gray-900">#{{ ticket.id }} - {{ ticket.title }}</span>
      <StatusBadge :status="ticket.status" size="sm" />
    </div>
    ```

    **User Management:**

    ```vue
    <!-- Example: /users/john-doe -->
    <div class="flex items-center space-x-2">
      <UserIcon class="h-5 w-5 text-gray-400" />
      <span class="text-gray-500">User</span>
      <ChevronRightIcon class="h-4 w-4 text-gray-300" />
      <span class="font-medium text-gray-900">{{ user.name }}</span>
      <span class="text-sm text-gray-500">({{ user.email }})</span>
    </div>
    ```

    **Account Context:**

    ```vue
    <!-- Example: When viewing account-specific data -->
    <div class="flex items-center space-x-2">
      <BuildingOfficeIcon class="h-5 w-5 text-gray-400" />
      <span class="text-gray-500">Account</span>
      <ChevronRightIcon class="h-4 w-4 text-gray-300" />
      <span class="font-medium text-gray-900">{{ account.name }}</span>
      <span class="text-sm text-gray-500">{{ pageTitle }}</span>
    </div>
    ```

    ### 4. Page Context Registration

    ```javascript
    // usePageContext.js
    export function usePageContext() {
        const setPageContext = (context) => {
            pageContextState.current = {
                type: context.type, // 'ticket', 'user', 'account', 'role', etc.
                entity: context.entity, // The actual entity object
                title: context.title, // Page title
                subtitle: context.subtitle, // Optional subtitle
                breadcrumbs: context.breadcrumbs || [],
                actions: context.actions || [],
                metadata: context.metadata || {},
            };
        };

        return { setPageContext, clearPageContext };
    }
    ```

    ### 5. Implementation by Page Type

    **Ticket Detail Page:**

    ```vue
    <script setup>
    import { usePageContext } from "@/Composables/usePageContext.js";

    const { setPageContext } = usePageContext();

    onMounted(() => {
        setPageContext({
            type: "ticket",
            entity: props.ticket,
            title: `#${props.ticket.id} - ${props.ticket.title}`,
            subtitle: `${props.ticket.category?.name} • ${props.ticket.priority}`,
            breadcrumbs: [
                { label: "Tickets", route: "tickets.index" },
                {
                    label: `#${props.ticket.id}`,
                    route: "tickets.show",
                    params: { ticket: props.ticket.id },
                },
            ],
            metadata: {
                status: props.ticket.status,
                assignee: props.ticket.assignee,
                account: props.ticket.account,
                created_at: props.ticket.created_at,
            },
        });
    });
    </script>
    ```

    **Role Template Management:**

    ```vue
    <script setup>
    onMounted(() => {
        setPageContext({
            type: "role_template",
            entity: props.roleTemplate,
            title: props.roleTemplate.name,
            subtitle: `${props.roleTemplate.context} role • ${props.roleTemplate.users_count} users`,
            breadcrumbs: [
                { label: "Administration", route: "admin.dashboard" },
                { label: "Role Templates", route: "roles.index" },
                {
                    label: props.roleTemplate.name,
                    route: "roles.show",
                    params: { roleTemplate: props.roleTemplate.id },
                },
            ],
        });
    });
    </script>
    ```

    ### 6. Smart Navigation Features

    -   **Back Button**: Context-aware back navigation
    -   **Quick Actions**: Contextual action buttons based on entity type
    -   **Related Entities**: Quick links to related items (e.g., from ticket to account)
    -   **Search Context**: Scope search to current context when relevant

    ### 7. Responsive Behavior

    -   **Desktop**: Full breadcrumb with entity details
    -   **Tablet**: Truncated breadcrumb with tooltip
    -   **Mobile**: Icon + abbreviated title with expandable details

    ### 8. Integration Points

    ```javascript
    // Enhanced navigation with context
    const navigateWithContext = (route, context) => {
        router.visit(route, {
            onBefore: () => setLoadingContext(context),
            onSuccess: () => updatePageContext(context),
            preserveState: true,
        });
    };
    ```

    ### 9. Context-Specific Features by Entity Type

    **Ticket Context:**

    -   Status indicator with color coding
    -   Priority badge
    -   Assignee avatar
    -   Quick action buttons (Edit, Comment, Close)
    -   Time tracking integration

    **User Context:**

    -   Role indicators
    -   Account associations
    -   Status (active/inactive)
    -   Quick actions (Edit, Roles, Reset Password)

    **Account Context:**

    -   Account tier/type
    -   Subsidiary count
    -   Active users count
    -   Quick actions (Users, Settings, Billing)

    ### 10. Benefits

    -   **Improved Orientation**: Users always know where they are
    -   **Reduced Cognitive Load**: Essential information at a glance
    -   **Faster Navigation**: Context-aware back buttons and breadcrumbs
    -   **Better UX**: Entity-specific actions and information
    -   **Professional Appearance**: More polished and intuitive interface

    **Dependencies:**

    -   Vue 3 Composition API and reactivity
    -   Inertia.js router integration
    -   Existing icon library (Heroicons)
    -   Permission system for context-specific actions
