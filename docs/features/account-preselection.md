# Account Preselection

Auto-selection of accounts and agents in modal dialogs when launched from account detail pages.

## Overview

When creating tickets or users from an account detail page, the relevant fields are automatically preselected for better user experience.

## Features

### Create Ticket Modal
When launched from `/accounts/{id}` page:
- **Account**: Auto-selects the current account 
- **Agent**: Auto-selects current user (with RBAC validation)
- **Category**: Auto-selects default category

### Create User Modal  
When launched from account detail page:
- **Account**: Auto-selects the current account (read-only display)
- **Role**: Manual selection required

## Implementation

### Props
```vue
// CreateTicketModalTabbed.vue
const props = defineProps({
  accountId: { type: [String, Number], default: null },
  preselectedAgentId: { type: [String, Number], default: null }
})

// UserFormModal.vue  
const props = defineProps({
  preselectedAccountId: { type: [String, Number], default: null }
})
```

### Usage
```vue
<!-- From Accounts/Show.vue -->
<CreateTicketModalTabbed
  :account-id="account?.id"
  :preselected-agent-id="currentUser?.id"
  @close="showCreateTicketModal = false"
/>

<UserFormModal
  :preselected-account-id="account?.id"
  @close="showCreateUserModal = false"
/>
```

## Technical Details

- Uses `UnifiedSelector` component for consistent UI
- Key-based re-rendering ensures visual selection display
- RBAC validation prevents invalid agent assignments
- Form initialization happens after data loading completes

## Related Components

- `UnifiedSelector.vue` - Handles account/agent selection with visual feedback
- `CreateTicketModalTabbed.vue` - Ticket creation with preselection
- `UserFormModal.vue` - User creation with account preselection