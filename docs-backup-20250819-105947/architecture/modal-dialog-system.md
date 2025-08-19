# Modal Dialog System Architecture

Service Vault implements a sophisticated modal dialog system with support for dropdown components, stacking management, and unified user experience across all modal interfaces.

## Core Components

### StackedDialog.vue
**Primary modal component** with comprehensive features:
- **Modal Stacking**: Automatic z-index management for nested dialogs
- **Dropdown Support**: `allowDropdowns` prop for overflow-visible behavior
- **Responsive Design**: Adaptive sizing with configurable max-width
- **Event Management**: Escape key handling, backdrop clicks, and proper cleanup
- **Content Flexibility**: Configurable header, footer, and padding

```vue
<StackedDialog
  :show="isOpen"
  :title="Dialog Title"
  max-width="2xl"
  :allow-dropdowns="true"
  @close="handleClose"
>
  <!-- Dialog content with dropdown selectors -->
</StackedDialog>
```

### TabbedDialog.vue
**Tabbed interface wrapper** built on StackedDialog:
- **Tab Navigation**: Horizontal tab bar with active state management
- **Content Areas**: Tab-specific content sections
- **Dropdown Support**: Inherits `allowDropdowns` from StackedDialog
- **Save/Cancel Actions**: Built-in form submission handling

```vue
<TabbedDialog
  :show="isOpen"
  :title="Dialog Title"
  :tabs="tabDefinitions"
  :allow-dropdowns="true"
  @save="handleSave"
  @tab-change="handleTabChange"
>
  <!-- Tabbed content -->
</TabbedDialog>
```

## Dropdown Overflow Solution

### Problem
Modal dialogs with `overflow: hidden` clip dropdown components, causing poor UX where selectors are cut off or invisible.

### Solution Architecture
**Conditional overflow control** via `allowDropdowns` prop:

```javascript
// StackedDialog.vue implementation
const overflowClass = computed(() => {
  return props.allowDropdowns ? 'overflow-visible' : 'overflow-hidden'
})
```

**Applied to both:**
- **Dialog container**: Main modal wrapper
- **Content area**: Inner content section

### Implementation Pattern

**1. Dialog Components:**
```vue
<!-- Enable dropdown support -->
<StackedDialog :allow-dropdowns="true">
  <UnifiedSelector type="account" :items="accounts" />
</StackedDialog>
```

**2. Selector Components:**
```vue
<!-- UnifiedSelector with proper z-index -->
<div class="absolute w-full bg-white border rounded-lg shadow-lg"
     style="z-index: 9999;">
  <!-- Dropdown content -->
</div>
```

## Updated Modal Components

All modal components now support dropdown overflow:

### Form Modals
- **StartTimerModalTabbed**: Timer creation with billing rate selectors
- **CreateTicketModalTabbed**: Ticket creation with account/assignee selectors  
- **AccountFormModal**: Account management with parent account selectors
- **UserFormModal**: User management with account/role selectors
- **UnifiedTimeEntryDialog**: Time entry management with comprehensive selectors

### Configuration Pattern
```vue
<template>
  <StackedDialog
    :show="isOpen"
    :title="modalTitle"
    :allow-dropdowns="true"
    @close="closeModal"
  >
    <!-- UnifiedSelector components work without clipping -->
    <UnifiedSelector type="billing-rate" :items="rates" />
  </StackedDialog>
</template>
```

## Z-Index Management

**Hierarchical z-index system:**
- **Base Dialog**: 50 + (stack_level × 10)
- **Dropdown Components**: 9999 (above all dialogs)
- **Backdrop**: Dialog z-index - 1

```javascript
// StackedDialog z-index calculation
const zIndex = computed(() => {
  const baseZIndex = 50
  const level = stackLevel.value >= 0 ? stackLevel.value : 0
  return baseZIndex + level * 10
})
```

## Best Practices

### When to Use allowDropdowns
**Enable for modals containing:**
- UnifiedSelector components
- Any dropdown/select interfaces
- Hierarchical selection components
- Multi-level selectors

### Performance Considerations
**Minimal impact:**
- Only affects overflow CSS properties
- No JavaScript overhead
- Backward compatible (defaults to false)

### Development Guidelines
```vue
<!-- ✅ Good: Enable dropdowns for selector modals -->
<StackedDialog :allow-dropdowns="true">
  <UnifiedSelector type="account" />
</StackedDialog>

<!-- ✅ Good: Disable for simple content modals -->
<StackedDialog :allow-dropdowns="false">
  <p>Simple confirmation message</p>
</StackedDialog>

<!-- ❌ Avoid: Forgetting dropdown support -->
<StackedDialog>
  <UnifiedSelector type="user" /> <!-- Will be clipped -->
</StackedDialog>
```

## Technical Benefits

### Clean Architecture
- **Single prop solution**: Simple boolean flag
- **Composable design**: Works with existing modal system
- **Zero breaking changes**: Fully backward compatible

### Enhanced UX
- **No clipped dropdowns**: Perfect visibility across all modals
- **Consistent behavior**: Uniform dropdown experience
- **Professional appearance**: Polished interface interactions

### Maintainability
- **Declarative approach**: Intent clear from template
- **Centralized logic**: Managed in base dialog components
- **Easy debugging**: Clear prop-based behavior

## Migration Guide

### Existing Modals
Update existing StackedDialog/TabbedDialog usage:

```vue
<!-- Before -->
<StackedDialog :show="isOpen" @close="close">
  <UnifiedSelector type="account" /> <!-- Clipped -->
</StackedDialog>

<!-- After -->
<StackedDialog :show="isOpen" :allow-dropdowns="true" @close="close">
  <UnifiedSelector type="account" /> <!-- Perfect -->
</StackedDialog>
```

### New Modal Development
**Always consider dropdown needs:**
1. Identify selector components in modal
2. Add `:allow-dropdowns="true"` if present
3. Test dropdown visibility and positioning

This modal dialog system provides a robust foundation for Service Vault's comprehensive user interface while ensuring optimal dropdown component behavior across all modal contexts.