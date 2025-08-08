# Component Specifications

Vue.js component specifications for Service Vault UI components.

## Account Selector Component

### Purpose
A reusable hierarchical account selector component for forms requiring account selection.

### File Location
`resources/js/Components/Selectors/AccountSelector.vue`

### Props Interface
```typescript
interface AccountSelectorProps {
  modelValue: number | null;           // v-model binding
  showHierarchy?: boolean;             // Show hierarchical structure (default: true)
  filterAccessible?: boolean;          // Only show accessible accounts (default: true)
  placeholder?: string;                // Placeholder text
  required?: boolean;                  // Mark as required field
  disabled?: boolean;                  // Disable component
  maxDepth?: number;                   // Limit hierarchy depth
  excludeAccounts?: number[];          // Account IDs to exclude
  size?: 'sm' | 'md' | 'lg';          // Component size
}
```

### Events
```typescript
interface AccountSelectorEvents {
  'update:modelValue': (accountId: number | null) => void;
  'account-selected': (account: Account) => void;
  'search': (query: string) => void;
}
```

### Visual Structure
```vue
<template>
  <div class="account-selector">
    <!-- Combobox with search -->
    <Combobox v-model="selected" @update:model-value="handleSelection">
      <ComboboxInput 
        :placeholder="placeholder"
        @change="handleSearch"
        class="account-selector__input"
      />
      
      <!-- Hierarchical dropdown -->
      <ComboboxOptions class="account-selector__dropdown">
        <ComboboxOption
          v-for="account in filteredAccounts"
          :key="account.id"
          :value="account"
          :class="getAccountClasses(account)"
        >
          <div class="account-option">
            <!-- Hierarchy indentation -->
            <div :style="{ paddingLeft: `${account.depth * 20}px` }">
              <!-- Account icon -->
              <BuildingOfficeIcon class="account-icon" />
              
              <!-- Account name -->
              <span class="account-name">{{ account.name }}</span>
              
              <!-- User count badge -->
              <span v-if="account.user_count" class="user-count">
                {{ account.user_count }}
              </span>
            </div>
          </div>
        </ComboboxOption>
      </ComboboxOptions>
    </Combobox>
    
    <!-- Selected account display -->
    <div v-if="selected" class="selected-account">
      <span class="account-path">{{ getAccountPath(selected) }}</span>
    </div>
  </div>
</template>
```

### Usage Examples

#### Basic Usage
```vue
<template>
  <AccountSelector
    v-model="form.accountId"
    placeholder="Select target account"
    required
  />
</template>
```

#### Domain Mapping Usage
```vue
<template>
  <AccountSelector
    v-model="form.accountId"
    :show-hierarchy="true"
    :filter-accessible="true"
    placeholder="Select account for domain mapping"
    required
  />
</template>
```

#### Advanced Usage
```vue
<template>
  <AccountSelector
    v-model="selectedAccount"
    :max-depth="3"
    :exclude-accounts="[1, 2, 3]"
    size="lg"
    @account-selected="handleAccountSelection"
  />
</template>
```

### Styling Requirements

#### Base Styles
```css
.account-selector {
  @apply relative w-full;
}

.account-selector__input {
  @apply w-full rounded-md border-gray-300 shadow-sm;
  @apply focus:border-primary-500 focus:ring-primary-500;
}

.account-selector__dropdown {
  @apply absolute z-50 mt-1 max-h-60 w-full overflow-auto;
  @apply rounded-md bg-white py-1 shadow-lg ring-1 ring-black ring-opacity-5;
}
```

#### Hierarchical Styling
```css
.account-option {
  @apply px-4 py-2 cursor-pointer select-none;
}

.account-option:hover {
  @apply bg-gray-100;
}

.account-option[data-selected] {
  @apply bg-primary-100 text-primary-900;
}

/* Hierarchy depth styling */
.account-option[data-depth="0"] { @apply font-semibold; }
.account-option[data-depth="1"] { @apply font-medium; }
.account-option[data-depth="2"] { @apply font-normal; }
.account-option[data-depth="3"] { @apply font-light text-gray-600; }
```

### Backend Integration

#### API Endpoint
```http
GET /api/accounts/selector?accessible=true&search=query&max_depth=5
```

#### Response Format
```json
{
  "data": [
    {
      "id": 1,
      "name": "Acme Corporation",
      "slug": "acme-corp",
      "depth": 0,
      "parent_id": null,
      "user_count": 25,
      "can_manage": true,
      "path": "Acme Corporation"
    },
    {
      "id": 2, 
      "name": "Engineering Department",
      "slug": "engineering",
      "depth": 1,
      "parent_id": 1,
      "user_count": 12,
      "can_manage": true,
      "path": "Acme Corporation > Engineering Department"
    }
  ]
}
```

### Accessibility Requirements

#### ARIA Labels
```vue
<Combobox
  role="combobox"
  :aria-expanded="isOpen"
  aria-haspopup="listbox"
  :aria-label="ariaLabel"
>
```

#### Keyboard Navigation
- **Arrow Up/Down**: Navigate options
- **Enter/Space**: Select option
- **Escape**: Close dropdown
- **Tab**: Move to next form field

#### Screen Reader Support
- Announce selected account path
- Indicate hierarchy level
- Provide option count information

### Performance Considerations

#### Virtual Scrolling
For large account hierarchies (>100 accounts), implement virtual scrolling:
```vue
<RecycleScroller
  v-slot="{ item }"
  class="scroller"
  :items="accounts"
  :item-size="48"
  key-field="id"
>
  <AccountOption :account="item" />
</RecycleScroller>
```

#### Lazy Loading
Load child accounts on demand for deep hierarchies.

#### Caching
Cache account hierarchy data with appropriate invalidation.

## Related Components

### Dependencies
- **Headless UI**: `Combobox`, `ComboboxInput`, `ComboboxOptions`
- **Heroicons**: `BuildingOfficeIcon`, `ChevronDownIcon`
- **Vue 3**: Composition API, `ref`, `computed`

### Similar Components
- **UserSelector**: For selecting users within accounts
- **ProjectSelector**: For selecting projects with hierarchical display
- **RoleSelector**: For selecting roles with permission preview

## Testing Requirements

### Unit Tests
```javascript
describe('AccountSelector', () => {
  test('renders with placeholder text', () => {});
  test('filters accounts based on search', () => {});
  test('respects accessibility permissions', () => {});
  test('handles deep hierarchy correctly', () => {});
});
```

### Integration Tests
```javascript
describe('AccountSelector Integration', () => {
  test('loads accounts from API', () => {});
  test('updates form model correctly', () => {});
  test('handles API errors gracefully', () => {});
});
```

---
**Implementation Priority**: High  
**Dependencies**: Account API, Headless UI, Heroicons  
**Estimated Effort**: 2-3 days