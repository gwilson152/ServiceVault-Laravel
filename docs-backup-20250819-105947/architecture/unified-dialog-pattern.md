# Unified Dialog Pattern

**August 2025** - Design pattern for consistent create and edit experiences using single modal components.

## Overview

The Unified Dialog Pattern is a design approach where a single modal component handles both creating new records and editing existing ones. This ensures consistency across the application and reduces code duplication.

## Pattern Benefits

- **Consistency**: Same UI and UX for create and edit operations
- **Maintainability**: Single component to maintain instead of separate create/edit components
- **User Experience**: Familiar interface reduces cognitive load
- **Code Efficiency**: Eliminates duplication between create and edit forms

## Implementation Pattern

### Component Structure

```vue
<template>
  <TabbedDialog
    :show="show"
    :title="isEditMode ? 'Edit Item' : 'Create New Item'"
    :save-label="isEditMode ? 'Update Item' : 'Create Item'"
    @save="submitForm"
    @close="$emit('close')"
  >
    <!-- Form content with conditional behavior -->
  </TabbedDialog>
</template>

<script setup>
// Props for dual mode support
const props = defineProps({
  show: { type: Boolean, default: false },
  mode: {
    type: String,
    default: 'create',
    validator: (value) => ['create', 'edit'].includes(value)
  },
  item: { type: Object, default: () => null }
})

// Emits for both operations
const emit = defineEmits(['close', 'item-created', 'item-updated'])

// Mode detection
const isEditMode = computed(() => props.mode === 'edit')

// Form handling with mode-aware logic
const resetForm = () => {
  if (isEditMode.value && props.item) {
    // Prefill form with existing data
    populateFormFromItem(props.item)
  } else {
    // Initialize with defaults
    initializeDefaultForm()
  }
}

const submitForm = async () => {
  const payload = prepareFormData()
  
  if (isEditMode.value) {
    // Update existing item
    const response = await axios.put(`/api/items/${props.item.id}`, payload)
    emit('item-updated', response.data)
  } else {
    // Create new item
    const response = await axios.post('/api/items', payload)
    emit('item-created', response.data)
  }
  
  emit('close')
}
</script>
```

### Usage Pattern

```vue
<template>
  <!-- Create Mode -->
  <UnifiedItemDialog
    :show="showCreateModal"
    mode="create"
    :preselected-values="preselectedData"
    @close="showCreateModal = false"
    @item-created="handleItemCreated"
  />

  <!-- Edit Mode -->
  <UnifiedItemDialog
    :show="showEditModal"
    mode="edit"
    :item="selectedItem"
    @close="showEditModal = false"
    @item-updated="handleItemUpdated"
  />
</template>
```

## Service Vault Implementation

### CreateTicketModalTabbed Component

The primary example of this pattern in Service Vault is the `CreateTicketModalTabbed` component:

#### Key Features

- **Mode Props**: `mode` prop accepts 'create' or 'edit'
- **Data Props**: `ticket` prop for edit mode, other props for preselection
- **Smart Form Handling**: Automatically prefills form in edit mode
- **Dual API Integration**: POST for create, PUT for edit
- **Event Emission**: Separate events for create and update operations

#### Data Handling Logic

```javascript
// Smart form prefilling in resetForm()
if (isEditMode.value && props.ticket) {
  // Edit mode - prefill with ticket data
  form.title = props.ticket.title || "";
  form.description = props.ticket.description || "";
  form.account_id = props.ticket.account_id || null;
  
  // Handle agent assignment from multiple possible formats
  form.agent_id = props.ticket.agent_id || props.ticket.assigned_to?.id || null;
  
  // Handle category as object or string
  form.category = typeof props.ticket.category === 'object' 
    ? props.ticket.category.key || props.ticket.category.name || ""
    : props.ticket.category;
    
  // Format dates for HTML inputs
  if (props.ticket.due_date) {
    const date = new Date(props.ticket.due_date);
    form.due_date = date.toISOString().slice(0, 16);
  }
  
  // Convert arrays to display format
  form.tags = Array.isArray(props.ticket.tags) 
    ? props.ticket.tags.join(", ") 
    : (props.ticket.tags || "");
} else {
  // Create mode - use defaults and preselected values
  initializeCreateModeForm();
}
```

#### API Integration

```javascript
const submitForm = async () => {
  const payload = prepareFormData();
  
  if (isEditMode.value && props.ticket) {
    // Update existing ticket
    const response = await axios.put(`/api/tickets/${props.ticket.id}`, payload);
    emit("ticket-updated", response.data);
  } else {
    // Create new ticket
    const result = await createTicketMutation.mutateAsync(payload);
    emit("ticket-created", result);
  }
  
  emit("close");
};
```

### Integration Points

#### Ticket Detail Page

```vue
<!-- Actions dropdown includes edit option -->
<div class="actions-dropdown">
  <button v-if="canEdit" @click="showEditModal = true">
    Edit Ticket
  </button>
</div>

<!-- Unified modal for editing -->
<CreateTicketModalTabbed
  :show="showEditModal"
  mode="edit"
  :ticket="ticket"
  @close="showEditModal = false"
  @ticket-updated="handleTicketUpdated"
/>
```

#### Event Handling

```javascript
const handleTicketUpdated = (updatedTicket) => {
  // Refresh ticket data to show changes
  refetchTicket();
  refetchMessages();
};
```

## Data Transformation Challenges

### API Response to Form Data

Different API responses may require transformation for form compatibility:

#### Date Formatting
```javascript
// Convert ISO string to datetime-local format
if (apiData.due_date) {
  const date = new Date(apiData.due_date);
  form.due_date = date.toISOString().slice(0, 16); // YYYY-MM-DDTHH:mm
}
```

#### Relationship Handling
```javascript
// Handle different relationship formats
form.agent_id = apiData.agent_id || apiData.assigned_to?.id || null;
form.category = typeof apiData.category === 'object' 
  ? apiData.category.key 
  : apiData.category;
```

#### Array to String Conversion
```javascript
// Convert tag arrays to comma-separated strings
form.tags = Array.isArray(apiData.tags) 
  ? apiData.tags.join(", ") 
  : (apiData.tags || "");
```

### Form Data to API Payload

Reverse transformation when submitting:

```javascript
const prepareFormData = () => {
  const payload = { ...form };
  
  // Convert comma-separated tags to array
  if (payload.tags) {
    payload.tags = payload.tags.split(',').map(tag => tag.trim()).filter(tag => tag);
  }
  
  // Convert datetime-local to ISO string
  if (payload.due_date) {
    payload.due_date = new Date(payload.due_date).toISOString();
  }
  
  return payload;
};
```

## Best Practices

### Component Design

1. **Mode Detection**: Use computed properties for clean mode-based logic
2. **Props Validation**: Validate mode prop to ensure only valid values
3. **Event Naming**: Use specific event names (item-created vs item-updated)
4. **Data Handling**: Robust data transformation for different API formats

### Form Management

1. **Reset Logic**: Always reset form data when modal opens
2. **Validation**: Same validation rules for both modes
3. **Error Handling**: Consistent error display and handling
4. **Loading States**: Show appropriate loading indicators

### API Integration

1. **HTTP Methods**: Use appropriate methods (POST for create, PUT for edit)
2. **Error Responses**: Handle validation and server errors gracefully
3. **Success Handling**: Emit appropriate events with response data

## Future Extensions

### Planned Enhancements

- **Bulk Edit Mode**: Support editing multiple items simultaneously
- **Template Support**: Save form states as templates for quick creation
- **Version History**: Show change history for edited items
- **Draft Functionality**: Auto-save form state for resuming later

### Additional Components

Components that could benefit from unified dialog pattern:

- **User Management**: Create/edit user accounts
- **Account Management**: Create/edit customer accounts  
- **Time Entry Management**: Create/edit time entries
- **Billing Rate Management**: Create/edit billing rates

The Unified Dialog Pattern provides a scalable foundation for consistent CRUD operations across Service Vault while maintaining excellent user experience and code maintainability.