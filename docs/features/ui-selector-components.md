# UI Selector Components

Professional, reusable selector components providing consistent user experience across Service Vault interfaces.

## Overview

Service Vault features three polished selector components that provide standardized user interactions for complex data selection. These components follow unified design patterns and deliver professional UX with smart features like viewport-aware positioning, conditional display logic, and comprehensive keyboard navigation.

## Component Architecture

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

#### Usage Example
```vue
<HierarchicalAccountSelector
  v-model="selectedAccountId"
  placeholder="No account (general timer)"
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
- **Search Functionality**: Filter rates by name, hourly amount, or description text
- **Default Badge**: Visual indicator for organization default rates
- **Currency Formatting**: Proper financial formatting for hourly rates
- **Rate Descriptions**: Optional additional context for billing rate selection

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

#### Usage Example
```vue
<BillingRateSelector
  v-model="selectedRateId"
  :rates="billingRates"
  :is-loading="ratesLoading"
  placeholder="No billing rate"
  @rate-selected="handleRateSelection"
/>
```

## Unified Design Patterns

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

The timer quick start form demonstrates optimal integration of all three selector components:

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
    
    <!-- Billing Rate Selection -->
    <BillingRateSelector
      v-model="quickStartForm.billingRateId"
      :rates="billingRates"
      :is-loading="billingRatesLoading"
      placeholder="No billing rate"
      @rate-selected="handleRateSelected"
    />
  </div>
</template>
```

### Form State Management

Reactive form state with proper defaults and reset logic:

```javascript
const quickStartForm = reactive({
  description: '',
  accountId: '',     // Bound to HierarchicalAccountSelector
  ticketId: '',      // Bound to TicketSelector (conditional)
  billingRateId: ''  // Bound to BillingRateSelector (auto-default)
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

_Last Updated: August 14, 2025 - Enhanced Timer Creation with Professional Selector Components_