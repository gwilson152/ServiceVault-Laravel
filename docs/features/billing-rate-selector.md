# Billing Rate Selector Component

The `BillingRateSelector` is a sophisticated Vue component that provides an intuitive interface for selecting billing rates within Service Vault's two-tier billing rate hierarchy system.

## Overview

The billing rate selector automatically organizes and displays rates according to Service Vault's simplified hierarchy:
1. **Account-Specific Rates** (highest priority)
2. **Parent Account Inherited Rates** (medium priority)  
3. **Global Default Rates** (fallback)

## Features

### 1. Visual Hierarchy
- **Group Headers**: Rates are organized into clearly labeled sections with emojis
  - 📋 Account-Specific Rates
  - 🔗 Inherited from Parent Account
  - 🌐 Global Default Rates
- **Color-Coded Badges**: Blue for account rates, green for global rates
- **Inheritance Indicators**: Shows which account rates are inherited from

### 2. Smart Sorting
Rates are automatically sorted by:
1. **Priority** (Account → Parent → Global)
2. **Default Status** (Default rates first within each group)
3. **Rate Amount** (Higher rates first for easier selection)
4. **Name** (Alphabetical as final sort)

### 3. Enhanced User Experience
- **Search Functionality**: Filter rates by name, amount, or description
- **Keyboard Navigation**: Arrow keys and Enter for accessibility
- **Responsive Dropdown**: Automatically adjusts position to stay in viewport
- **No Billing Option**: Always available for non-billable work
- **Clear Selection**: Easy rate clearing with reopen functionality

### 4. Educational Features
- **Hierarchy Info Panel**: Optional footer explaining rate priority (enabled via `showHierarchyInfo` prop)
- **Inheritance Notes**: Shows which parent account rates are inherited from
- **Rate Details**: Displays rate description and source information

## Component API

### Props

```vue
<BillingRateSelector
  v-model="selectedRateId"
  :rates="availableRates"
  :is-loading="loadingState"
  :show-hierarchy-info="true"
  :placeholder="'Select billing rate...'"
  :reopen-on-clear="true"
  @rate-selected="handleRateSelected"
/>
```

#### Prop Details

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `modelValue` | `String\|Number` | `null` | Selected rate ID or 'no-billing' |
| `rates` | `Array` | `[]` | Array of billing rate objects |
| `isLoading` | `Boolean` | `false` | Loading state indicator |
| `placeholder` | `String` | `'Select billing rate...'` | Input placeholder text |
| `showHierarchyInfo` | `Boolean` | `false` | Show educational hierarchy panel |
| `reopenOnClear` | `Boolean` | `true` | Reopen dropdown after clearing selection |

### Events

| Event | Payload | Description |
|-------|---------|-------------|
| `update:modelValue` | `String\|Number\|null` | Emitted when selection changes |
| `rate-selected` | `Object\|null` | Emitted with full rate object when selected |

### Rate Object Structure

```javascript
{
  id: 1,
  name: "Standard Hourly",
  description: "Standard rate for general technical work",
  rate: 90.00,
  is_active: true,
  is_default: true,
  inheritance_source: "account", // "account", "parent", or "global"
  inherited_from_account: "Parent Company Name", // Only for inherited rates
  account_id: 123, // null for global rates
  // ... other fields
}
```

## Usage Examples

### 1. Time Entry Forms

```vue
<template>
  <div>
    <label class="block text-sm font-medium text-gray-700 mb-1">
      Billing Rate
    </label>
    <BillingRateSelector
      v-model="form.billingRateId"
      :rates="availableBillingRates"
      :is-loading="billingRatesLoading"
      :show-hierarchy-info="true"
      placeholder="Select billing rate..."
      @rate-selected="handleRateSelected"
    />
  </div>
</template>

<script>
const handleRateSelected = (rate) => {
  if (rate) {
    // Update billing calculations
    calculateBillingAmount()
  }
}
</script>
```

### 2. Timer Configuration

```vue
<template>
  <BillingRateSelector
    v-model="timerConfig.billingRateId"
    :rates="accountBillingRates"
    :is-loading="ratesLoading"
    placeholder="Default rate will be used"
    @rate-selected="updateTimerRate"
  />
</template>
```

### 3. Settings Management

```vue
<template>
  <!-- In account detail page for creating account-specific overrides -->
  <BillingRateSelector
    v-model="newRateId"
    :rates="allRatesForAccount"
    :show-hierarchy-info="true"
    placeholder="Create new override..."
  />
</template>
```

## Visual Design

### Rate Display Structure

```
┌─────────────────────────────────────────────────────────────┐
│ Selected Rate: Standard Hourly - $90/hr [Default] [Account] │
│                                                    [×]      │
└─────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐
│ 🚫 No Billing - Non-billable work                          │
├─────────────────────────────────────────────────────────────┤
│ 📋 Account-Specific Rates                                   │
│   Senior Developer - $130/hr     [Default] [Account Rate]   │
│   Project Manager - $110/hr              [Account Rate]     │
├─────────────────────────────────────────────────────────────┤
│ 🔗 Inherited from Parent Account                            │
│   Standard Rate - $90/hr          [Default] [Inherited]     │
│   Emergency Rate - $150/hr                [Inherited]       │
├─────────────────────────────────────────────────────────────┤
│ 🌐 Global Default Rates                                     │
│   Development - $80/hr                   [Global Rate]      │
│   Travel Time - $40/hr                   [Global Rate]      │
├─────────────────────────────────────────────────────────────┤
│ ℹ️ Rate Priority:                                           │
│   Account-specific rates override inherited rates,          │
│   which override global rates.                              │
└─────────────────────────────────────────────────────────────┘
```

### Badge System

| Badge | Color | Meaning |
|-------|-------|---------|
| `Default` | Blue | This rate is set as default for its scope |
| `Account Rate` | Blue | Account-specific rate |
| `Inherited` | Blue | Inherited from parent account |
| `Global Rate` | Green | System-wide global rate |

## Integration Points

### 1. Time Entry Dialog
- **Location**: `UnifiedTimeEntryDialog.vue`
- **Context**: Shows hierarchy info for educational purposes
- **Behavior**: Automatically loads rates based on selected account

### 2. Timer Configuration
- **Location**: `TimerConfigurationForm.vue`
- **Context**: Rate selection for active timers
- **Behavior**: Persists rate selection in timer state

### 3. Account Detail Pages
- **Location**: Account billing tab
- **Context**: Creating account-specific rate overrides
- **Behavior**: Shows full rate hierarchy for the account

### 4. Settings Management
- **Location**: `/settings/billing` rates tab
- **Context**: Global rate management
- **Behavior**: Shows all rates across all accounts

## Accessibility

### Keyboard Support
- **Arrow Keys**: Navigate through rate options
- **Enter**: Select highlighted rate
- **Escape**: Close dropdown
- **Tab**: Standard tab navigation

### Screen Reader Support
- **ARIA Labels**: Proper labeling for rate options
- **Role Attributes**: Correct semantic markup
- **State Announcements**: Loading and selection states

### Visual Accessibility
- **High Contrast**: Color combinations meet WCAG standards
- **Focus Indicators**: Clear focus states for keyboard navigation
- **Text Sizing**: Responsive text that scales with user preferences

## Performance Considerations

### Optimization Features
- **Efficient Sorting**: Pre-sorted data reduces render time
- **Virtual Scrolling**: Handles large rate lists efficiently  
- **Search Debouncing**: Prevents excessive filtering operations
- **Smart Re-rendering**: Only re-renders when necessary

### Memory Management
- **Event Cleanup**: Proper event listener removal
- **Dropdown Positioning**: Efficient viewport calculations
- **Search State**: Proper cleanup of search terms

## Best Practices

### Implementation Guidelines

1. **Always Provide Rates Array**: Even if empty, provide an array to prevent errors
2. **Handle Loading States**: Use `isLoading` prop for better UX
3. **Account Context**: Load rates specific to the selected account when available
4. **Error Handling**: Gracefully handle rate loading failures
5. **Educational Context**: Use `showHierarchyInfo` in complex forms

### Common Patterns

```javascript
// Loading rates for specific account
const loadBillingRatesForAccount = async (accountId) => {
  billingRatesLoading.value = true
  try {
    const params = accountId ? { account_id: accountId } : {}
    const response = await axios.get('/api/billing-rates', { params })
    availableBillingRates.value = response.data.data || []
  } catch (error) {
    console.error('Failed to load billing rates:', error)
    availableBillingRates.value = []
  } finally {
    billingRatesLoading.value = false
  }
}

// Handle rate selection with billing calculation
const handleRateSelected = (rate) => {
  if (rate && form.value.billable) {
    calculateBillingAmount()
  }
}
```

## Troubleshooting

### Common Issues

1. **Rates Not Loading**: Check API endpoint and account_id parameter
2. **Hierarchy Not Displaying**: Verify rate objects have `inheritance_source` field
3. **Selection Not Working**: Ensure `v-model` is bound to correct property
4. **Dropdown Positioning**: Component auto-adjusts, but check container overflow

### Debug Information

```javascript
// Check rate data structure
console.log('Available rates:', availableBillingRates.value)
console.log('Selected rate ID:', selectedRateId.value)
console.log('Filtered rates:', component.filteredRates)
```

---

**BillingRateSelector Component** - Intelligent rate selection with clear hierarchy visualization for Service Vault's billing system.

*Last Updated: August 15, 2025 - Enhanced with two-tier hierarchy system and improved visual design*