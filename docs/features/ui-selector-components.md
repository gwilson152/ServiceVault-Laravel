# UI Selector Components

Professional, reusable selector components providing consistent user experience across Service Vault interfaces.

## Overview

Service Vault features four polished selector components that provide standardized user interactions for complex data selection. These components follow unified design patterns and deliver professional UX with smart features like viewport-aware positioning, conditional display logic, auto-reopen behavior, and comprehensive keyboard navigation.

## Component Architecture

### UserSelector

**Purpose**: Select system users with built-in create new user functionality  
**File**: `/resources/js/Components/UI/UserSelector.vue`  
**Data Source**: User data via TanStack Query with account and role filtering

#### Features
- **Intelligent User Search**: Filters users by name, email, role, and account information
- **Built-in User Creation**: Integrated "Create New User" option that opens UserFormModal
- **Auto-Reopen Behavior**: Automatically reopens dropdown when selections are cleared for seamless UX
- **Smart Viewport Positioning**: Dropup/dropdown mode based on available screen space
- **Context-Aware Account Preselection**: Pre-fills account context in user creation modal
- **Rich User Display**: Shows user name, email, role, and account information
- **Conditional Display Logic**: Hides search input when user is selected, shows selected user card
- **Loading and Empty States**: Professional loading indicators and "no users found" messaging
- **Account Integration**: Filters and displays users with account and role context

#### User Creation Integration
```javascript
// Automatically opens UserFormModal with preselected account context
const openCreateModal = () => {
  showCreateUserModal.value = true
  // UserFormModal receives preselectedAccountId from parent context
}

const handleUserCreated = (user) => {
  // New user automatically selected after creation
  selectUser(user)
  closeCreateModal()
}
```

#### Props
- `modelValue`: Selected user ID or user object (String/Object)
- `users`: Array of available users for selection
- `isLoading`: Loading state indicator (Boolean)
- `label`: Input label text (default: "User")
- `placeholder`: Search placeholder text
- `required`: Show required indicator (Boolean)
- `error`: Error message to display
- `reopenOnClear`: Auto-reopen dropdown when cleared (default: true)
- `showCreateOption`: Enable "Create New User" functionality (default: false)
- `noUsersMessage`: Custom message when no users available
- `accounts`: Array of accounts for user creation modal
- `roleTemplates`: Array of role templates for user creation modal
- `preselectedAccountId`: Account ID to preselect in user creation modal

#### Usage Example
```vue
<UserSelector
  v-model="selectedUserId"
  :users="availableUsers"
  :is-loading="usersLoading"
  label="Assign to User"
  placeholder="Select a user..."
  required
  :error="errors.user_id"
  :show-create-option="true"
  :accounts="accounts"
  :role-templates="roleTemplates"
  :preselected-account-id="currentAccountId"
  @user-selected="handleUserSelection"
/>
```

#### Smart Selection Display
When a user is selected, the component shows a professional user card instead of the search input:
```vue
<!-- Selected User Card -->
<div class="p-2 bg-blue-50 border border-blue-200 rounded-lg">
  <div class="flex items-center justify-between">
    <div class="flex items-center">
      <div class="w-8 h-8 bg-gray-300 rounded-full flex items-center justify-center mr-3">
        <!-- User Icon -->
      </div>
      <div>
        <p class="text-sm font-medium text-blue-900">{{ selectedUser.name }}</p>
        <p class="text-xs text-blue-700">{{ selectedUser.email }}</p>
        <p class="text-xs text-blue-600">{{ selectedUser.role_template?.name }}</p>
      </div>
    </div>
    <button @click="clearSelection" class="text-blue-600 hover:text-blue-800">
      <!-- Clear Icon -->
    </button>
  </div>
</div>
```

### HierarchicalAccountSelector

**Purpose**: Select business accounts with hierarchical relationship display  
**File**: `/resources/js/Components/UI/HierarchicalAccountSelector.vue`  
**Data Source**: `/api/accounts/selector/hierarchical`

#### Features
- **Hierarchical Display**: Shows account relationships with proper indentation and visual hierarchy
- **Rich Data Structure**: Displays account number, name, and hierarchy level with `display_name` formatting
- **Smart Search**: Filters accounts by name, account number, and display hierarchy text
- **Default Option**: "No account (general timer)" for non-client work scenarios
- **Account Tree Navigation**: Flattened display of nested account relationships for easy selection

#### API Integration
```javascript
const { data: accounts, isLoading } = useAccountsQuery({
  endpoint: 'hierarchical' // Uses /api/accounts/selector/hierarchical
})

// Account data structure includes:
// {
//   id: 'uuid-123',
//   account_number: 'ACC-001',
//   name: 'Parent Company',
//   display_name: 'ACC-001 - Parent Company',
//   level: 0,
//   children: [...]
// }
```

#### Props
- `modelValue`: Selected account ID (String/Object)
- `label`: Label text (default: "Account")
- `placeholder`: Input placeholder text
- `required`: Show required indicator (Boolean)
- `error`: Error message to display
- `reopenOnClear`: Auto-reopen dropdown when cleared (default: true)

#### Usage Example
```vue
<HierarchicalAccountSelector
  v-model="selectedAccountId"
  label="Customer Account"
  placeholder="No account (general timer)"
  required
  :error="errors.account_id"
  :reopen-on-clear="true"
  @account-selected="handleAccountSelection"
/>
```

### TicketSelector

**Purpose**: Select service tickets with status filtering and rich display  
**File**: `/resources/js/Components/UI/TicketSelector.vue`  
**Data Source**: Filtered ticket data via TanStack Query

#### Features
- **Account-Filtered Results**: Only shows tickets for selected account context
- **Status-Based Filtering**: Automatically excludes `closed` and `cancelled` tickets
- **Rich Display Information**: Shows ticket number, title, status badges, and priority
- **Status Color Coding**: Visual status indicators with consistent color scheme:
  - Green: Open, New tickets
  - Blue: In Progress, Assigned tickets  
  - Yellow: On Hold, Waiting tickets
  - Red: Urgent, Critical priorities
- **Real-Time Data**: Integrates with TanStack Query for efficient caching and updates

#### Status Badge Logic
```javascript
const getStatusClasses = (statusName) => {
  const status = statusName.toLowerCase()
  if (status.includes('open') || status.includes('new')) {
    return 'bg-green-100 text-green-800'
  } else if (status.includes('progress') || status.includes('assigned')) {
    return 'bg-blue-100 text-blue-800'
  } else if (status.includes('hold') || status.includes('waiting')) {
    return 'bg-yellow-100 text-yellow-800'
  }
  return 'bg-gray-100 text-gray-800'
}
```

#### Usage Example
```vue
<TicketSelector
  v-model="selectedTicketId"
  :tickets="availableTickets"
  :is-loading="ticketsLoading"
  placeholder="No specific ticket"
  @ticket-selected="handleTicketSelection"
/>
```

### BillingRateSelector

**Purpose**: Select billing rates with rate information and default handling  
**File**: `/resources/js/Components/UI/BillingRateSelector.vue`  
**Data Source**: `/api/billing/rates`

#### Features
- **Rate Information Display**: Shows rate name, hourly amount ($X.XX/hr), and default indicator
- **Default Rate Preselection**: Automatically selects default billing rate when component loads
- **Auto-Reopen on Clear**: Automatically reopens dropdown when selection is cleared for seamless UX
- **Smart Viewport Positioning**: Dropup/dropdown mode based on screen position (critical for timer overlay)
- **Search Functionality**: Filter rates by name, hourly amount, or description text
- **Default Badge**: Visual indicator for organization default rates with blue styling
- **Currency Formatting**: Proper financial formatting for hourly rates
- **Rate Descriptions**: Optional additional context for billing rate selection
- **Enhanced Keyboard Navigation**: Arrow keys, Enter, and Escape support
- **Professional Selection Display**: Shows selected rate card instead of search input when selected

#### Data Structure
```javascript
// Billing rate data structure:
// {
//   id: 'uuid-789',
//   name: 'Senior Developer',
//   rate: '150.00',
//   description: 'Senior development and architecture work',
//   is_default: true,
//   is_active: true
// }
```

#### Props
- `modelValue`: Selected billing rate ID (String/Number)
- `rates`: Array of available billing rates
- `isLoading`: Loading state indicator (Boolean)
- `placeholder`: Input placeholder text (default: "Select billing rate...")
- `reopenOnClear`: Auto-reopen dropdown when cleared (default: true)

#### Timer Integration
BillingRateSelector is specifically optimized for timer creation workflows:
```javascript
// Auto-select default rate when rates load
watch(() => billingRates.value, (newRates) => {
  if (newRates && !quickStartForm.billingRateId) {
    const defaultRate = newRates.find(rate => rate.is_default) || newRates[0]
    if (defaultRate) {
      quickStartForm.billingRateId = defaultRate.id
    }
  }
}, { immediate: true })
```

#### Usage Example
```vue
<BillingRateSelector
  v-model="selectedRateId"
  :rates="billingRates"
  :is-loading="ratesLoading"
  placeholder="No billing rate"
  :reopen-on-clear="true"
  @rate-selected="handleRateSelection"
/>
```

#### Smart Clear and Reopen
When users click the clear button on a selected rate, the component automatically:
1. **Clears the selection** and emits the change
2. **Reopens the dropdown** to show available options
3. **Focuses the search input** for immediate typing
4. **Checks viewport position** to ensure proper dropdown placement

```javascript
const clearSelection = () => {
  selectedRate.value = null
  searchTerm.value = ''
  emit('update:modelValue', null)
  emit('rate-selected', null)
  
  // Optionally reopen dropdown and focus input
  if (props.reopenOnClear) {
    isOpen.value = true
    setTimeout(() => {
      const input = document.getElementById(inputId)
      if (input) {
        input.focus()
        checkDropdownPosition()
      }
    }, 10)
  }
}
```

## Unified Design Patterns

### Auto-Reopen on Clear Behavior

All selector components feature intelligent "reopen on clear" functionality that provides seamless user experience when changing selections:

#### Smart Clear Behavior
When a user clears a selection (clicks the X button), the component automatically:
1. **Reopens the dropdown** to show available options
2. **Focuses the search input** for immediate typing
3. **Checks viewport position** to ensure proper dropdown placement
4. **Provides smooth UX** without requiring additional clicks

```javascript
const clearSelection = () => {
  selectedItem.value = null
  searchTerm.value = ''
  emit('update:modelValue', null)
  emit('item-selected', null)
  
  // Optionally reopen dropdown and focus input
  if (props.reopenOnClear) {
    showDropdown.value = true
    setTimeout(() => {
      const input = document.getElementById(inputId)
      if (input) {
        input.focus()
        checkDropdownPosition()
      }
    }, 10)
  }
}
```

#### Configurable Behavior
The `reopenOnClear` prop (default: `true`) allows disabling this behavior when needed:

```vue
<!-- Default behavior: auto-reopen on clear -->
<HierarchicalAccountSelector v-model="accountId" />

<!-- Disable auto-reopen -->
<HierarchicalAccountSelector v-model="accountId" :reopen-on-clear="false" />
```

### Conditional UI Display

All selector components implement a professional UX pattern where the search input is hidden when a selection is made, showing the selected item with a clear option instead:

```vue
<!-- Search Input (only visible when no selection) -->
<div v-if="!selectedItem" class="relative">
  <input
    v-model="searchTerm"
    type="text"
    :placeholder="placeholder"
    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md..."
    @focus="isOpen = true"
  />
</div>

<!-- Selected Item Display (only visible when selection made) -->
<div 
  v-if="selectedItem"
  class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md... cursor-pointer"
  @click="clearSelection"
>
  <div class="flex items-center justify-between">
    <div>{{ selectedItem.displayText }}</div>
    <svg class="w-4 h-4 text-gray-400"><!-- Clear icon --></svg>
  </div>
</div>
```

### Smart Viewport Positioning

All components implement intelligent dropdown positioning to prevent viewport overflow, especially important for timer overlay positioned at bottom of screen:

```javascript
const checkDropdownPosition = () => {
  const input = document.getElementById(inputId)
  if (!input) return
  
  const inputRect = input.getBoundingClientRect()
  const viewportHeight = window.innerHeight
  const spaceBelow = viewportHeight - inputRect.bottom
  const spaceAbove = inputRect.top
  
  // Switch to dropup mode if insufficient space below
  dropupMode.value = spaceBelow < 250 && spaceAbove > spaceBelow
}
```

### Enhanced Keyboard Navigation

Comprehensive keyboard support across all selector components:

```javascript
// Keyboard event handlers
@keydown.escape="closeDropdown"
@keydown.arrow-down.prevent="navigateDown"
@keydown.arrow-up.prevent="navigateUp"  
@keydown.enter.prevent="selectHighlighted"

// Navigation methods
const navigateDown = () => {
  if (highlightedIndex.value < filteredItems.value.length - 1) {
    highlightedIndex.value++
  }
}

const navigateUp = () => {
  if (highlightedIndex.value > 0) {
    highlightedIndex.value--
  }
}

const selectHighlighted = () => {
  if (filteredItems.value[highlightedIndex.value]) {
    selectItem(filteredItems.value[highlightedIndex.value])
  }
}
```

### Accessibility Features

All components include comprehensive accessibility support:

#### ARIA Support
```vue
<input
  :id="inputId"
  :aria-expanded="isOpen"
  :aria-haspopup="true"
  :aria-owns="dropdownId"
  role="combobox"
  aria-autocomplete="list"
/>

<div
  :id="dropdownId"
  role="listbox"
  :aria-label="accessibilityLabel"
>
  <div
    v-for="(item, index) in filteredItems"
    :key="item.id"
    role="option"
    :aria-selected="highlightedIndex === index"
  >
```

#### Focus Management
- Clear visual focus indicators for keyboard users
- Proper tab order and focus restoration
- Screen reader friendly announcements
- High contrast mode support

#### Unique Component IDs
```javascript
// Generate unique IDs to prevent conflicts
const selectorId = `selector-${Math.random().toString(36).substr(2, 9)}`
const inputId = `input-${Math.random().toString(36).substr(2, 9)}`
const dropdownId = `dropdown-${Math.random().toString(36).substr(2, 9)}`
```

## Component Integration Patterns

### Timer Overlay Integration

The timer quick start form demonstrates optimal integration of all four selector components:

```vue
<template>
  <div class="space-y-3">
    <!-- Timer Description -->
    <input
      v-model="quickStartForm.description"
      type="text"
      placeholder="Timer description..."
      class="w-full px-3 py-2 border border-gray-300..."
    />
    
    <!-- Account Selection -->
    <HierarchicalAccountSelector
      v-model="quickStartForm.accountId"
      placeholder="No account (general timer)"
      @account-selected="handleAccountSelected"
    />
    
    <!-- Ticket Selection (conditional on account) -->
    <TicketSelector
      v-if="quickStartForm.accountId"
      v-model="quickStartForm.ticketId"
      :tickets="availableTickets"
      :is-loading="ticketsLoading"
      placeholder="No specific ticket"
      @ticket-selected="handleTicketSelected"
    />
    
    <!-- Billing Rate Selection (with auto-default selection) -->
    <BillingRateSelector
      v-model="quickStartForm.billingRateId"
      :rates="billingRates"
      :is-loading="billingRatesLoading"
      placeholder="No billing rate"
      :reopen-on-clear="true"
      @rate-selected="handleRateSelected"
    />
    
    <!-- Optional User Assignment (for ticket assignment scenarios) -->
    <UserSelector
      v-if="showUserAssignment"
      v-model="quickStartForm.assignedUserId"
      :users="availableUsers"
      :is-loading="usersLoading"
      label="Assign to"
      placeholder="Select user..."
      :show-create-option="false"
      @user-selected="handleUserSelected"
    />
  </div>
</template>
```

### Form State Management

Reactive form state with proper defaults and reset logic:

```javascript
const quickStartForm = reactive({
  description: '',
  accountId: '',        // Bound to HierarchicalAccountSelector
  ticketId: '',         // Bound to TicketSelector (conditional)
  billingRateId: '',    // Bound to BillingRateSelector (auto-default)
  assignedUserId: ''    // Bound to UserSelector (optional)
})

// Clear ticket selection when account changes
watch(() => quickStartForm.accountId, () => {
  quickStartForm.ticketId = ''
})

// Set default billing rate when rates load
watch(() => billingRates.value, (newRates) => {
  if (newRates && !quickStartForm.billingRateId) {
    const defaultRate = newRates.find(rate => rate.is_default) || newRates[0]
    if (defaultRate) {
      quickStartForm.billingRateId = defaultRate.id
    }
  }
}, { immediate: true })
```

### Data Filtering and Relationships

Smart data filtering based on component relationships:

```javascript
// Tickets filtered by selected account
const ticketsFilter = computed(() => ({
  account_id: quickStartForm.accountId || null
}))

const { data: tickets, isLoading: ticketsLoading } = useTicketsQuery(ticketsFilter)

// Filter out closed/cancelled tickets
const availableTickets = computed(() => {
  if (!tickets.value) return []
  return tickets.value.filter(ticket => 
    ticket.status !== 'closed' && 
    ticket.status !== 'cancelled'
  )
})
```

## Performance Optimizations

### Efficient Data Loading
- **TanStack Query Integration**: Optimized caching and background updates
- **Conditional API Calls**: Only fetch data when needed (e.g., tickets only when account selected)
- **Debounced Search**: Minimize API calls during user typing
- **Smart Caching**: Reuse loaded data across component instances

### Memory Management
- **Event Cleanup**: Proper event listener removal on component unmount
- **Reactive Cleanup**: Automatic cleanup of Vue reactivity watchers
- **DOM Reference Management**: Clean component ID generation and cleanup

### Search Performance
```javascript
// Optimized search filtering
const filteredItems = computed(() => {
  if (!searchTerm.value.trim()) return items.value
  
  const search = searchTerm.value.toLowerCase()
  return items.value.filter(item => 
    item.name.toLowerCase().includes(search) ||
    item.displayText.toLowerCase().includes(search) ||
    (item.description && item.description.toLowerCase().includes(search))
  )
})
```

## Error Handling

### Graceful Degradation
- **Loading States**: Visual indicators during data fetching
- **Empty States**: User-friendly messages when no data available
- **Network Errors**: Retry mechanisms and fallback behavior
- **Validation Errors**: Clear error messaging for invalid selections

### User Feedback
```javascript
// Loading state handling
<div v-if="isLoading" class="px-3 py-2 text-sm text-gray-500">
  Loading {{ itemType }}...
</div>

// Empty state handling  
<div v-else-if="filteredItems.length === 0" class="px-3 py-2 text-sm text-gray-500">
  No {{ itemType }} found
</div>

// Error state handling
<div v-else-if="error" class="px-3 py-2 text-sm text-red-600">
  Error loading {{ itemType }}. Please try again.
</div>
```

## Testing Considerations

### Component Testing
- **Unit Tests**: Individual component behavior and state management
- **Integration Tests**: Component interaction within forms and workflows
- **Accessibility Tests**: Keyboard navigation and screen reader compatibility
- **Performance Tests**: Large dataset handling and search responsiveness

### User Experience Testing
- **Usability Tests**: Real user interaction with selector components
- **Viewport Tests**: Dropdown positioning across different screen sizes
- **Keyboard Tests**: Full keyboard navigation workflows
- **Mobile Tests**: Touch interaction and responsive behavior

## Future Enhancements

### Planned Improvements
- **Multi-Select Support**: Allow selection of multiple items where appropriate
- **Advanced Filtering**: Additional filter criteria and sorting options
- **Custom Templates**: Configurable display templates for different use cases
- **Virtualization**: Support for extremely large datasets with virtual scrolling
- **Offline Support**: Local caching and offline-first functionality

### API Enhancements
- **Pagination Support**: Handle large datasets with efficient pagination
- **Server-Side Search**: Delegate search filtering to backend for performance
- **Bulk Operations**: Support for bulk selection and operations
- **Real-Time Updates**: Live data updates via WebSocket integration

---

**UI Selector Components** provide the foundation for consistent, professional user interactions across Service Vault's complex data selection scenarios.

_Last Updated: August 14, 2025 - Added UserSelector with create new user functionality, enhanced auto-reopen behavior, and timer integration optimizations_