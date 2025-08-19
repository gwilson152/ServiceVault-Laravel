# Enhanced Dialog System

Service Vault features an advanced dialog system with auto-stacking capabilities, tabbed interfaces, and proper z-index management for complex modal workflows.

## Overview

The dialog system consists of three main components:

1. **StackedDialog** - Base dialog with auto-stacking and proper dismissal behavior
2. **TabbedDialog** - Pre-configured tabbed interface that **wraps** StackedDialog
3. **Modal** - Legacy modal component (still supported)

### Component Hierarchy

```
TabbedDialog
├── StackedDialog (provides stacking, z-index, dismissal)
    ├── Native <dialog> element
    └── Backdrop management

Modal (legacy)
├── Native <dialog> element  
└── Basic modal functionality
```

**Key Design**: `TabbedDialog` is built **on top of** `StackedDialog`, inheriting all stacking behavior while adding tab-specific UI and functionality. This ensures design consistency and code reuse.

## Key Features

- **Auto-Stacking**: Dialogs automatically manage z-index and stack order
- **Smart Dismissal**: Only top-level dialogs respond to ESC/outside clicks
- **Tabbed Interface**: Built-in support for organized multi-section forms
- **Backdrop Management**: Proper opacity scaling for stacked dialogs
- **State Management**: Automatic dialog stack tracking and cleanup

## Components

### StackedDialog

The foundational component that provides auto-stacking capabilities.

#### Props

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `show` | Boolean | `false` | Controls dialog visibility |
| `maxWidth` | String | `'2xl'` | Maximum width class (`sm`, `md`, `lg`, `xl`, `2xl`, `3xl`, `4xl`, `5xl`, `6xl`, `7xl`) |
| `closeable` | Boolean | `true` | Whether dialog can be closed |
| `title` | String | `''` | Dialog title |
| `showHeader` | Boolean | `true` | Whether to show header section |
| `showFooter` | Boolean | `true` | Whether to show footer section |

#### Events

| Event | Description |
|-------|-------------|
| `@close` | Emitted when dialog should be closed |

#### Slots

| Slot | Description |
|------|-------------|
| `header` | Custom header content (when no title prop) |
| `default` | Main dialog content |
| `footer` | Footer content |

#### Usage

```vue
<template>
  <StackedDialog
    :show="showDialog"
    title="My Dialog"
    max-width="lg"
    @close="showDialog = false"
  >
    <div class="p-6">
      Dialog content here
    </div>
    
    <template #footer>
      <button @click="save">Save</button>
      <button @click="showDialog = false">Cancel</button>
    </template>
  </StackedDialog>
</template>
```

### TabbedDialog

A specialized dialog wrapper that provides tabbed interface functionality.

#### Props

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `show` | Boolean | `false` | Controls dialog visibility |
| `title` | String | `''` | Dialog title |
| `tabs` | Array | `[]` | Tab configuration array |
| `defaultTab` | String | `null` | Default active tab ID |
| `maxWidth` | String | `'2xl'` | Maximum width class |
| `closeable` | Boolean | `true` | Whether dialog can be closed |
| `saveLabel` | String | `'Save'` | Text for save button |
| `saving` | Boolean | `false` | Shows loading state on save button |
| `showFooter` | Boolean | `true` | Whether to show footer with buttons |

#### Tab Configuration

Tabs are configured using an array of objects:

```javascript
const tabs = [
  {
    id: 'basic',
    name: 'Basic Info',
    icon: 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'
  },
  {
    id: 'contact',
    name: 'Contact',
    icon: 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'
  }
]
```

#### Events

| Event | Description |
|-------|-------------|
| `@close` | Emitted when dialog should be closed |
| `@save` | Emitted when save button is clicked |
| `@tab-change` | Emitted when active tab changes |

#### Slots

| Slot | Description |
|------|-------------|
| `errors` | Error message display area |
| `default` | Tab content (receives `activeTab` prop) |
| `footer-start` | Additional footer content before buttons |
| `footer-end` | Additional footer content after buttons |

#### Usage

```vue
<template>
  <TabbedDialog
    :show="showDialog"
    title="Account Form"
    :tabs="tabs"
    :saving="saving"
    save-label="Create Account"
    @close="showDialog = false"
    @save="saveAccount"
    @tab-change="activeTab = $event"
  >
    <!-- Error messages -->
    <template #errors>
      <div v-if="errors.general" class="alert alert-error">
        {{ errors.general[0] }}
      </div>
    </template>

    <!-- Tab content -->
    <template #default="{ activeTab }">
      <div v-show="activeTab === 'basic'">
        <!-- Basic tab content -->
      </div>
      <div v-show="activeTab === 'contact'">
        <!-- Contact tab content -->
      </div>
    </template>
  </TabbedDialog>
</template>

<script setup>
const tabs = [
  { id: 'basic', name: 'Basic Info', icon: '...' },
  { id: 'contact', name: 'Contact', icon: '...' }
]
</script>
```

## Auto-Stacking Behavior

### Stack Management

The dialog system automatically manages a global stack of open dialogs:

- Each dialog gets a unique ID and is added to the stack when opened
- Z-index is calculated based on stack position (base 50 + level * 10)
- Stack is automatically cleaned up when dialogs close

### Dismissal Rules

- **ESC Key**: Only closes the topmost (most recently opened) dialog
- **Outside Click**: Only closes the topmost dialog when clicking backdrop
- **Close Button**: Always works regardless of stack position

### Body Overflow

- First dialog sets `document.body.style.overflow = 'hidden'`
- Last dialog restores normal overflow
- Multiple dialogs don't interfere with each other

## Migration from Legacy Modal

### Before (Modal component)

```vue
<Modal :show="showModal" @close="showModal = false">
  <div class="p-6">
    <h3>Dialog Title</h3>
    <div>Content</div>
    <div class="footer">
      <button @click="save">Save</button>
    </div>
  </div>
</Modal>
```

### After (StackedDialog)

```vue
<StackedDialog
  :show="showModal"
  title="Dialog Title"
  @close="showModal = false"
>
  <div class="p-6">
    <div>Content</div>
  </div>
  
  <template #footer>
    <button @click="save">Save</button>
  </template>
</StackedDialog>
```

## Best Practices

### 1. Tab Organization

Group related fields logically:
- **Basic Info**: Essential identifying information
- **Contact**: Communication details
- **Address**: Physical/mailing addresses
- **Billing**: Financial and billing information
- **Advanced**: Technical or less common settings

### 2. Form Validation

Display errors contextually:
```vue
<template #errors>
  <div v-if="errors.general" class="alert alert-error">
    {{ errors.general[0] }}
  </div>
</template>
```

### 3. Loading States

Always provide feedback during save operations:
```vue
<TabbedDialog
  :saving="mutation.isPending.value"
  save-label="Creating..."
  @save="handleSave"
/>
```

### 4. Proper Cleanup

Use watchers to reset form state:
```vue
watch(() => props.show, (isOpen) => {
  if (isOpen) {
    resetForm()
    activeTab.value = 'basic'
  }
})
```

## Common Patterns

### Account/Entity Forms

Most entity forms follow this pattern:
1. Basic information (names, types, relationships)
2. Contact details (person, email, phone)
3. Address information (physical location)
4. Billing details (separate billing address)
5. Business/Advanced settings

### Nested Dialogs

Dialogs can open other dialogs (e.g., creating related entities):

```vue
<!-- Parent dialog -->
<TabbedDialog :show="showAccountDialog">
  <!-- Account form content -->
  <button @click="showUserDialog = true">Add User</button>
</TabbedDialog>

<!-- Child dialog -->
<StackedDialog :show="showUserDialog">
  <!-- User form content -->
</StackedDialog>
```

The child dialog will automatically stack on top and handle dismissal properly.

## Styling

### Z-Index Levels

- Base dialogs: `z-50` (50)
- First stacked: `z-60` (60)  
- Second stacked: `z-70` (70)
- etc.

### Backdrop Opacity

- Base dialog: 75% opacity
- First stacked: 60% opacity
- Second stacked: 45% opacity
- Minimum: 30% opacity

### Maximum Width Options

All Tailwind max-width classes are supported:
- `sm`: 384px
- `md`: 448px  
- `lg`: 512px
- `xl`: 576px
- `2xl`: 672px (default)
- `3xl`: 768px
- `4xl`: 896px
- `5xl`: 1024px
- `6xl`: 1152px
- `7xl`: 1280px

## Examples

See the following implementation examples:
- `/Pages/Accounts/Index.vue` - Complete tabbed account form
- `/Components/Examples/AccountFormDialogExample.vue` - Standalone example
- `/Components/AccountFormModal.vue` - Legacy implementation for comparison

## Troubleshooting

### Dialog Stack Issues

If dialogs aren't stacking properly:
1. Ensure all dialogs use `StackedDialog` or `TabbedDialog`
2. Check that `show` props are managed correctly
3. Verify no direct z-index CSS overrides

### Form Reset Problems

If forms don't reset properly:
1. Add watcher for `show` prop changes
2. Reset form state when dialog opens
3. Clear error state appropriately

### Tab Navigation Issues

If tabs don't switch properly:
1. **Set `default-tab` prop** - Always provide a default tab ID
2. **Handle tab-change events** - Listen to `@tab-change` in parent component
3. **Use `v-show` not `v-if`** for tab content to avoid re-rendering issues
4. **Initialize activeTab properly** - Ensure parent component activeTab matches dialog state

#### Common Fix for Missing Tabs

If you don't see all tabs or tab content:

```vue
<!-- Make sure to set default-tab -->
<TabbedDialog
  :show="showDialog"
  :tabs="tabs"
  default-tab="basic"  <!-- ← Important! -->
  @tab-change="activeTab = $event"  <!-- ← Handle tab changes -->
>
  <template #default="{ activeTab }">
    <!-- Use v-show, not v-if -->
    <div v-show="activeTab === 'basic'">
      <!-- Tab content -->
    </div>
  </template>
</TabbedDialog>
```

### Performance Issues

If dialogs feel slow:
1. Use `v-show` instead of `v-if` for tab content
2. Avoid heavy computations in tab content
3. Consider lazy loading for complex form sections