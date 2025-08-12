# Component Specifications

Vue.js component specifications and development guidelines for Service Vault.

## Widget System Components

### Dashboard Widget Base
All dashboard widgets extend from base widget component:

```vue
<template>
  <div class="widget-container" :class="widgetClasses">
    <div class="widget-header" v-if="showHeader">
      <h3 class="widget-title">{{ title }}</h3>
      <div class="widget-actions">
        <slot name="actions" />
      </div>
    </div>
    <div class="widget-content">
      <slot />
    </div>
  </div>
</template>

<script setup lang="ts">
interface WidgetProps {
  title?: string;
  showHeader?: boolean;
  loading?: boolean;
  error?: string | null;
  size?: 'sm' | 'md' | 'lg';
}
</script>
```

### Widget Development Pattern
```typescript
// Widget registration
export default {
  id: 'widget-name',
  component: 'WidgetComponent',
  title: 'Widget Title',
  description: 'Widget description',
  permissions: ['widgets.dashboard.widget-name'],
  defaultSize: { w: 6, h: 4 },
  minSize: { w: 3, h: 2 },
  maxSize: { w: 12, h: 8 }
}
```

## Form Components

### Account Selector
Hierarchical account selection with permission filtering:

```vue
<AccountSelector 
  v-model="selectedAccountId"
  :show-hierarchy="true"
  :filter-accessible="true"
  placeholder="Select account..."
  @account-selected="handleAccountSelected"
/>
```

**Props:**
- `modelValue: string | null` - Selected account ID
- `showHierarchy: boolean` - Show hierarchical structure
- `filterAccessible: boolean` - Filter by user permissions
- `placeholder: string` - Input placeholder text

### Role Template Selector
Role template selection with context filtering:

```vue
<RoleTemplateSelector
  v-model="selectedRoleId"
  :context="'service_provider'"
  :exclude-system-roles="false"
  @role-selected="handleRoleSelected"
/>
```

### Status Dropdown
Workflow-aware ticket status selection component with transition validation:

```vue
<StatusDropdown
  v-model="ticket.status"
  :statuses="ticketStatuses"
  :workflow-transitions="workflowTransitions"
  :loading="statusUpdating"
  @change="handleStatusChange"
/>
```

**Props:**
- `modelValue: string` - Current status key (required)
- `statuses: Array` - Available status definitions with colors and metadata
- `workflowTransitions: Object` - Allowed transitions from current status
- `loading: boolean` - Show loading indicator during updates
- `disabled: boolean` - Disable interaction

**Features:**
- **Workflow Validation**: Only shows statuses that are valid transitions from current state
- **Visual Design**: Status colors and icons for easy identification
- **Smart Positioning**: Fixed positioning to avoid table scroll conflicts
- **Loading States**: Built-in loading overlay during status updates
- **Development Mode**: Shows disabled options in dev mode for debugging

**Event Data:**
```typescript
interface StatusChangeEvent {
  from: string;    // Previous status key
  to: string;      // New status key  
  timestamp: string; // ISO timestamp of change
}
```

**Usage in Tables:**
The component is designed for inline editing within data tables, particularly `TicketsTable.vue`. It handles viewport positioning and z-index layering to work correctly within scrollable table containers.

**Workflow Integration:**
Status transitions are controlled by the `workflowTransitions` prop, which maps current status to allowed next statuses:
```javascript
workflowTransitions: {
  'open': ['in_progress', 'closed'],
  'in_progress': ['pending_review', 'resolved'],
  'pending_review': ['in_progress', 'resolved'],
  'resolved': ['closed', 'in_progress'],
  'closed': [] // Final state - no transitions
}
```

## Timer Components

### Timer Control Widget
Individual timer controls with real-time updates:

```vue
<TimerControl
  :timer="timer"
  :show-commit="true"
  :admin-mode="hasAdminAccess"
  @timer-updated="handleTimerUpdate"
  @timer-committed="handleTimerCommit"
/>
```

**Features:**
- Play/pause/stop controls
- Real-time duration display
- Admin override capabilities
- Commit to time entry

### Multi-Timer FAB
Floating action button for multi-timer management:

```vue
<MultiTimerFAB
  :active-timers="activeTimers"
  :admin-mode="isAdmin"
  @timer-started="handleTimerStart"
  @show-all-timers="showTimerOverlay"
/>
```

## Permission-Aware Components

### Conditional Rendering
Components automatically hide/show based on permissions:

```vue
<template>
  <div v-if="hasPermission('feature.access')">
    <AdminControls v-if="hasPermission('admin.write')" />
    <UserControls v-else />
  </div>
</template>

<script setup>
import { usePermissions } from '@/composables/usePermissions';

const { hasPermission } = usePermissions();
</script>
```

### Widget Permission Integration
```vue
<script setup>
import { useWidgetPermissions } from '@/composables/useWidgetPermissions';

const { canViewWidget, getVisibleWidgets } = useWidgetPermissions();

const visibleWidgets = computed(() => 
  getVisibleWidgets(availableWidgets.value)
);
</script>
```

## Modal Components

### Standard Modal Pattern
```vue
<DialogModal :show="showModal" @close="closeModal">
  <template #title>Modal Title</template>
  <template #content>
    <form @submit.prevent="handleSubmit">
      <!-- Form content -->
    </form>
  </template>
  <template #footer>
    <SecondaryButton @click="closeModal">Cancel</SecondaryButton>
    <PrimaryButton @click="handleSubmit" :loading="submitting">
      Save
    </PrimaryButton>
  </template>
</DialogModal>
```

### Confirmation Modal
```vue
<ConfirmationModal
  :show="showConfirm"
  title="Delete Item"
  message="This action cannot be undone."
  confirm-text="Delete"
  confirm-style="danger"
  @confirmed="handleDelete"
  @cancelled="showConfirm = false"
/>
```

## Real-Time Components

### Broadcasting Integration
Components that need real-time updates:

```vue
<script setup>
import { useTimerBroadcasting } from '@/composables/useTimerBroadcasting';

const { activeTimers, connectToTimerChannel } = useTimerBroadcasting();

onMounted(() => {
  connectToTimerChannel();
});
</script>
```

### Live Data Updates
```vue
<script setup>
import { useRealTimeData } from '@/composables/useRealTimeData';

const { data, isConnected, lastUpdate } = useRealTimeData('timers', {
  refreshInterval: 30000,
  enableBroadcasting: true
});
</script>
```

## Component Development Guidelines

### 1. TypeScript Integration
All components should use TypeScript with proper interfaces:

```typescript
interface ComponentProps {
  required: string;
  optional?: boolean;
  callback?: (data: any) => void;
}

interface ComponentEmits {
  'update:modelValue': [value: string];
  'changed': [data: ComponentData];
}
```

### 2. Composable Integration
Leverage Vue 3 composables for reusable logic:

```vue
<script setup>
import { usePermissions } from '@/composables/usePermissions';
import { useForm } from '@/composables/useForm';
import { useNotifications } from '@/composables/useNotifications';

const { hasPermission } = usePermissions();
const { form, submit, processing } = useForm();
const { showSuccess, showError } = useNotifications();
</script>
```

### 3. Styling Standards
Use Tailwind CSS with consistent patterns:

```vue
<template>
  <div class="component-container">
    <!-- Card pattern -->
    <div class="bg-white shadow rounded-lg p-6">
      <!-- Header pattern -->
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-medium text-gray-900">{{ title }}</h3>
        <slot name="actions" />
      </div>
      
      <!-- Content pattern -->
      <div class="space-y-4">
        <slot />
      </div>
    </div>
  </div>
</template>
```

### 4. Accessibility Standards
Ensure all components are accessible:

```vue
<template>
  <button
    :aria-label="buttonLabel"
    :aria-expanded="isExpanded"
    :aria-controls="controlsId"
    class="focus:outline-none focus:ring-2 focus:ring-indigo-500"
  >
    {{ text }}
  </button>
</template>
```

### 5. Error Handling
Implement consistent error handling:

```vue
<script setup>
const { showError } = useNotifications();

const handleAction = async () => {
  try {
    await performAction();
  } catch (error) {
    showError(error.message || 'An error occurred');
  }
};
</script>
```

## Component Testing

### Unit Testing Pattern
```javascript
import { mount } from '@vue/test-utils';
import Component from '@/Components/Component.vue';

describe('Component', () => {
  it('renders correctly with props', () => {
    const wrapper = mount(Component, {
      props: { title: 'Test Title' }
    });
    
    expect(wrapper.text()).toContain('Test Title');
  });
  
  it('emits events correctly', async () => {
    const wrapper = mount(Component);
    await wrapper.find('button').trigger('click');
    
    expect(wrapper.emitted()).toHaveProperty('clicked');
  });
});
```