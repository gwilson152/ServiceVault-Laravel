# UI Components

Service Vault's component library provides reusable, accessible components for consistent user interfaces.

## Layout Components

### StandardPageLayout

The primary layout wrapper for all pages in Service Vault, providing consistent structure and responsive behavior.

**Location**: `/resources/js/Layouts/StandardPageLayout.vue`

**Usage**:
```vue
<StandardPageLayout
  :title="pageTitle"
  subtitle="Optional page description"
  :show-sidebar="true"
  :show-filters="true"
  sidebar-width="lg:col-span-4"
  content-width="lg:col-span-8"
>
  <template #header-actions>
    <button class="btn-primary">New Item</button>
    <button class="btn-secondary">Refresh</button>
  </template>

  <template #filters>
    <FilterSection>
      <!-- Filter controls -->
    </FilterSection>
  </template>

  <template #main-content>
    <!-- Primary page content -->
  </template>

  <template #sidebar>
    <!-- Statistics, widgets, recent activity -->
  </template>
</StandardPageLayout>
```

**Props**:
- `title` (String): Page title displayed in header
- `subtitle` (String): Optional page description
- `show-sidebar` (Boolean, default: true): Show/hide sidebar
- `show-filters` (Boolean, default: true): Show/hide filter section
- `sidebar-width` (String, default: "lg:col-span-4 xl:col-span-3"): Tailwind classes for sidebar width
- `content-width` (String, default: "lg:col-span-8 xl:col-span-9"): Tailwind classes for content width

**Features**:
- **Mobile-First**: Automatic sidebar and filter collapsing on mobile
- **Full-Width Desktop**: No artificial width constraints on large screens
- **Accessibility**: Proper ARIA labels and keyboard navigation
- **Toggle Controls**: Mobile buttons for sidebar and filter visibility

### FilterSection

Standardized container for filter controls with responsive behavior.

**Location**: `/resources/js/Components/Layout/FilterSection.vue`

**Usage**:
```vue
<FilterSection title="Custom Filter Title">
  <div class="space-y-4">
    <!-- Filter inputs -->
  </div>
</FilterSection>
```

**Props**:
- `title` (String, default: "Filters & Search"): Section title

**Features**:
- **Responsive Header**: Hidden on mobile (handled by parent layout)
- **Consistent Padding**: Standardized spacing for all filter content
- **Clean Design**: White background with subtle border

### PageSidebar

Simple wrapper for sidebar content with consistent spacing.

**Location**: `/resources/js/Components/Layout/PageSidebar.vue`

**Usage**:
```vue
<PageSidebar>
  <StatsWidget />
  <RecentActivityWidget />
</PageSidebar>
```

**Features**:
- **Automatic Spacing**: `space-y-4` between child elements
- **No Layout Logic**: Pure spacing wrapper, responsive behavior handled by parent

## Form Components

### MultiSelect

Advanced multi-select dropdown with search, select all, and persistence features.

**Location**: `/resources/js/Components/UI/MultiSelect.vue`

**Usage**:
```vue
<MultiSelect
  v-model="selectedValues"
  :options="availableOptions"
  value-key="id"
  label-key="name"
  placeholder="Select items..."
  :max-display-items="2"
/>
```

**Props**:
- `modelValue` (Array): Selected values (v-model)
- `options` (Array, required): Available options
- `placeholder` (String, default: "Select options..."): Placeholder text
- `value-key` (String, default: "value"): Object property for option values
- `label-key` (String, default: "label"): Object property for option labels
- `max-display-items` (Number, default: 2): Max items to show before "X selected"

**Features**:
- **Visual Feedback**: Checkboxes and hover states
- **Bulk Actions**: "Select All" and "Clear All" buttons
- **Smart Display**: Shows individual items or count based on selection size
- **Accessibility**: Full keyboard navigation and screen reader support
- **Click Outside**: Automatic dropdown closing

**Example with Objects**:
```javascript
const options = [
  { id: 1, name: 'Open' },
  { id: 2, name: 'In Progress' },
  { id: 3, name: 'Closed' }
]

// selectedValues will contain: [1, 2] for selected IDs
```

**Example with Strings**:
```javascript
const options = ['high', 'medium', 'low']

// selectedValues will contain: ['high', 'medium'] for selected values
```

## Modal Components

### StackedDialog

Native HTML5 dialog-based modal system with proper stacking and accessibility.

**Location**: `/resources/js/Components/StackedDialog.vue`

**Usage**:
```vue
<!-- Standard Modal -->
<StackedDialog
  :show="isModalOpen"
  title="Modal Title"
  max-width="2xl"
  :allow-dropdowns="true"
  @close="closeModal"
>
  <template #header-subtitle>
    <p class="text-sm text-gray-600">Optional subtitle</p>
  </template>

  <!-- Modal content -->
  <div class="space-y-4">
    <!-- Content here -->
  </div>

  <template #footer>
    <div class="flex justify-end space-x-3">
      <button @click="closeModal" class="btn-secondary">Cancel</button>
      <button @click="save" class="btn-primary">Save</button>
    </div>
  </template>
</StackedDialog>

<!-- Fullscreen Modal (for complex interfaces) -->
<StackedDialog
  :show="isFullscreenOpen"
  title="Query Builder"
  :fullscreen="true"
  :fullscreen-padding="'2rem'"
  @close="closeFullscreen"
>
  <!-- Complex interface that needs full screen space -->
  <div class="h-full flex flex-col">
    <!-- Full height content -->
  </div>
</StackedDialog>
```

**Props**:
- `show` (Boolean, required): Control modal visibility
- `title` (String): Modal title
- `max-width` (String, default: "2xl"): Tailwind max-width class (ignored when fullscreen=true)
- `fullscreen` (Boolean, default: false): Enable fullscreen mode
- `fullscreen-padding` (String, default: "4rem"): Edge padding for fullscreen mode (64px from each edge)
- `closeable` (Boolean, default: true): Allow modal to be closed
- `show-header` (Boolean, default: true): Show/hide header section
- `show-footer` (Boolean, default: true): Show/hide footer section
- `pad-content` (Boolean, default: true): Add padding to content area
- `allow-dropdowns` (Boolean, default: false): Allow dropdowns to extend outside modal

**Features**:
- **Native Dialog**: Uses HTML5 `<dialog>` element for proper behavior
- **Auto Stacking**: Manages z-index for nested modals automatically
- **Fullscreen Mode**: Desktop fullscreen with configurable edge padding
- **Responsive Sizing**: Adapts to viewport size and content requirements
- **Escape Key**: Closes on Escape key press
- **Backdrop Click**: Optional close on backdrop click
- **Vertical Expansion**: Expands to fit content without artificial height limits

**Fullscreen Mode**:
- **Desktop Fullscreen**: Uses full viewport minus specified padding
- **Configurable Padding**: Default 64px from each edge, customizable
- **Height Management**: Full viewport height with proper flex layout
- **Use Cases**: Query builders, wizards, complex forms, data tables

## Selector Components

### UnifiedSelector

Self-managing entity selector with automatic data loading and search capabilities.

**Location**: `/resources/js/Components/UI/UnifiedSelector.vue`

**Usage**:
```vue
<UnifiedSelector
  v-model="selectedId"
  type="account"
  label="Select Account"
  placeholder="Choose an account..."
  :clearable="true"
  @item-selected="handleSelection"
/>
```

**Supported Types**:
- `ticket` - Ticket selection with creation support
- `account` - Account selection with creation support
- `user` - User selection (customer users)
- `agent` - Agent selection (staff users)
- `billing-rate` - Billing rate selection
- `role-template` - Role template selection

**Features**:
- **Self-Managing**: Automatic data loading, no manual items prop needed
- **Intelligent Search**: Debounced API search with minimum character requirements
- **Recent Items**: Remembers recently selected items per user
- **Permission-Aware**: Automatically filters based on user permissions
- **Creation Support**: Some types support creating new items inline

## Widget Components

Service Vault includes a comprehensive widget system for dashboard customization. Widgets are self-contained components that can be dynamically loaded and configured.

### Widget Loader

**Location**: `/resources/js/Components/WidgetLoader.vue`

Dynamically loads and renders widgets with error handling and loading states.

### Available Widgets

**Dashboard Widgets**:
- `SystemHealthWidget` - System status and health metrics
- `SystemStatsWidget` - Performance and usage statistics
- `QuickActionsWidget` - Customizable action buttons
- `UserManagementWidget` - User account overview
- `AccountManagementWidget` - Account statistics

**Ticket Widgets**:
- `TicketOverviewWidget` - Ticket statistics and status breakdown
- `MyTicketsWidget` - User's assigned tickets
- `TicketFiltersWidget` - Advanced ticket filtering
- `TicketTimerStatsWidget` - Timer statistics for tickets

**Time & Billing Widgets**:
- `TimeTrackingWidget` - Time tracking overview
- `TimeEntriesWidget` - Recent time entries
- `RecentTimeEntriesWidget` - Timeline of recent entries
- `AllTimersWidget` - All active timers across system
- `BillingOverviewWidget` - Billing statistics
- `BillingRatesWidget` - Rate management
- `InvoiceStatusWidget` - Invoice status overview
- `PaymentTrackingWidget` - Payment tracking

**Activity Widgets**:
- `AccountActivityWidget` - Account-specific activity feed

## Design Principles

### Consistency
- All components follow the same design patterns and spacing rules
- Standardized color palette and typography scales
- Consistent interaction patterns across all components

### Accessibility
- Full keyboard navigation support
- Screen reader compatibility with proper ARIA labels
- High contrast support and focus indicators
- Semantic HTML structure

### Responsiveness
- Mobile-first design approach
- Consistent breakpoint usage (`sm:`, `lg:`, `xl:`)
- Collapsible layouts for mobile devices
- Touch-friendly interactive elements

### Performance
- Lazy loading for complex components
- Optimized re-renders with proper Vue.js patterns
- Efficient state management with composables
- Minimal bundle impact with tree-shaking

## Component Guidelines

### Creating New Components

1. **Location**: Place in appropriate directory under `/resources/js/Components/`
2. **Naming**: Use PascalCase for component names
3. **Props**: Define clear prop interfaces with defaults and validation
4. **Emits**: Declare all emitted events explicitly
5. **Accessibility**: Include proper ARIA attributes and keyboard navigation
6. **Documentation**: Add JSDoc comments for complex props and methods

### Best Practices

- Use composition API with `<script setup>`
- Implement proper TypeScript interfaces for props
- Include loading and error states for async components
- Follow established design tokens and spacing patterns
- Test components with different screen sizes and input methods