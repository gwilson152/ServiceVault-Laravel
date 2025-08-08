# Domain Mapping Feature

Automatic user assignment to accounts based on email domain patterns.

## Overview

The domain mapping feature allows administrators to configure email domain patterns that automatically assign users to specific accounts during registration or user creation.

## Feature Location

**Settings → Email → Domain Mapping**

## UI Requirements

### Account Selector Component Integration

The "Add Domain Mapping" form must use the **Account Selector Component** for account selection:

```vue
<template>
  <div class="domain-mapping-form">
    <div class="form-group">
      <label for="domain-pattern">Domain Pattern</label>
      <input 
        id="domain-pattern"
        v-model="form.domainPattern"
        type="text"
        placeholder="*.company.com"
        class="form-input"
      />
    </div>
    
    <div class="form-group">
      <label for="target-account">Target Account</label>
      <AccountSelector
        v-model="form.accountId"
        :show-hierarchy="true"
        :filter-accessible="true"
        placeholder="Select account for domain mapping"
        required
      />
    </div>
    
    <div class="form-actions">
      <button type="submit" class="btn-primary">Add Mapping</button>
      <button type="button" class="btn-secondary" @click="cancel">Cancel</button>
    </div>
  </div>
</template>
```

## Component Requirements

### Account Selector Props
- **`show-hierarchy`**: `true` - Display hierarchical account structure
- **`filter-accessible`**: `true` - Only show accounts user can manage
- **`placeholder`**: Descriptive text for domain mapping context
- **`required`**: `true` - Domain mappings must specify target account

### Domain Pattern Input
- **Pattern validation**: Support wildcards (`*.domain.com`, `@company.com`)
- **Conflict detection**: Warn if pattern overlaps existing mappings
- **Real-time validation**: Show pattern validity as user types

## Functional Requirements

### Domain Pattern Matching
```php
// Example domain patterns
'*.company.com'     // Matches any subdomain of company.com
'@company.com'      // Matches exactly company.com
'company.com'       // Matches company.com and subdomains
'dev.*.company.com' // Matches dev.subdomain.company.com
```

### Account Selection Logic
1. **Hierarchical Display**: Show account tree structure
2. **Permission Filtering**: Only accounts user can assign users to
3. **Visual Indicators**: Show account depth and parent relationships
4. **Search/Filter**: Allow searching within account hierarchy

### Mapping Management
- **Add Mapping**: Create new domain → account mapping
- **Edit Mapping**: Modify existing domain patterns or target accounts
- **Delete Mapping**: Remove domain mappings with confirmation
- **Bulk Operations**: Import/export domain mappings via CSV

## User Experience

### Workflow
1. Navigate to **Settings → Email → Domain Mapping**
2. Click **"Add Domain Mapping"**
3. Enter domain pattern in text input
4. Use **Account Selector** to choose target account
5. Click **"Add Mapping"** to save

### Validation Messages
- **Invalid Pattern**: "Please enter a valid domain pattern (e.g., *.company.com)"
- **Duplicate Pattern**: "This domain pattern already exists for account X"
- **No Account Selected**: "Please select a target account for this domain mapping"

### Success Feedback
- **Mapping Added**: "Domain mapping for {pattern} → {account} created successfully"
- **Auto-assignment Preview**: "New users with emails matching {pattern} will be assigned to {account}"

## Backend Implementation

### Database Schema
```sql
CREATE TABLE domain_mappings (
    id BIGSERIAL PRIMARY KEY,
    domain_pattern VARCHAR(255) NOT NULL,
    account_id BIGINT REFERENCES accounts(id),
    created_by BIGINT REFERENCES users(id),
    is_active BOOLEAN DEFAULT TRUE,
    priority INTEGER DEFAULT 0,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    UNIQUE(domain_pattern, account_id)
);
```

### API Endpoints
```http
GET    /api/settings/domain-mappings      # List mappings
POST   /api/settings/domain-mappings      # Create mapping
PUT    /api/settings/domain-mappings/{id} # Update mapping
DELETE /api/settings/domain-mappings/{id} # Delete mapping
```

### Request Validation
```php
// StoreDomainMappingRequest
public function rules(): array
{
    return [
        'domain_pattern' => ['required', 'string', 'max:255'],
        'account_id' => ['required', 'exists:accounts,id'],
        'priority' => ['integer', 'min:0', 'max:100'],
    ];
}
```

## Integration Points

### User Registration
- Check email domain against mappings during registration
- Auto-assign to matching account if pattern found
- Log assignment for audit trail

### User Import
- Apply domain mappings during CSV/bulk user import
- Show mapping preview before import confirmation
- Override mappings with explicit account selection

### Account Hierarchy
- Respect account permissions when showing selector
- Inherit domain mappings from parent accounts (configurable)
- Show inherited mappings in different visual style

## Error Handling

### Common Errors
- **Account Not Accessible**: User cannot assign to selected account
- **Pattern Conflict**: Domain pattern overlaps with existing mapping
- **Invalid Pattern**: Domain pattern syntax is incorrect
- **Account Deleted**: Target account no longer exists

### Fallback Behavior
- **No Match**: User assigned to default account or manual assignment required
- **Multiple Matches**: Use highest priority mapping
- **Inactive Account**: Skip mapping and require manual assignment

## Testing Requirements

### Unit Tests
- Domain pattern matching logic
- Account permission filtering
- Validation rule testing

### Feature Tests
- Complete domain mapping workflow
- Account selector integration
- Permission boundary testing

### Browser Tests
- Account selector interaction
- Form validation display
- Hierarchical account navigation

## Related Documentation

- [Account Selector Component](../components/account-selector.md)
- [Account Management](account-management.md)
- [User Management](user-management.md)
- [Settings API](../api/settings.md)

---
**Status**: Specification  
**Priority**: High  
**Dependencies**: Account Selector Component, Account Management System