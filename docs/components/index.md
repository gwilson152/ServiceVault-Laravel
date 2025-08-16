# Component Documentation

This section contains documentation for Service Vault's Vue.js components and UI systems.

## UI Components

### Dialog System
- **[Enhanced Dialog System](dialog-system.md)** - Auto-stacking dialogs with tabbed interfaces
  - StackedDialog - Base component with auto-stacking functionality
  - TabbedDialog - Specialized wrapper for multi-section forms
  - Migration guide from legacy Modal component

### Form Components
- **AccountFormModal** - Legacy account form (replaced by TabbedDialog implementation)
- **BillingRateModal** - Billing rate creation and editing
- **AddonTemplateModal** - Ticket addon template management

### Selector Components
- **HierarchicalAccountSelector** - Account hierarchy selector with creation
- **UserSelector** - User selection with role-based filtering
- **BillingRateSelector** - Billing rate selection with hierarchy display
- **TicketSelector** - Ticket selection with search and filtering

### Layout Components
- **TimerBroadcastOverlay** - Persistent timer overlay with Agent/Customer filtering
- **Modal** - Legacy modal component (superseded by StackedDialog)
- **AppLayout** - Main application layout with navigation

## Widget System

### Dashboard Widgets
- **TicketTimerStats** - Real-time timer statistics for tickets
- **TicketFilters** - Dynamic ticket filtering interface
- **RecentTimeEntries** - Recent time entry display with approval workflows

### Timer Widgets
- **StartTimerModal** - Timer creation interface with account/ticket assignment
- **UnifiedTimeEntryDialog** - Consolidated time entry creation and editing

## Component Architecture

### Design Principles
1. **Composition over Inheritance** - Components build on each other
2. **Prop Consistency** - Similar props across related components
3. **Event Standardization** - Consistent event naming and payload structure
4. **Slot Flexibility** - Comprehensive slot system for customization

### Common Patterns

#### Modal/Dialog Pattern
```vue
<!-- Base pattern for all dialogs -->
<StackedDialog :show="showDialog" @close="showDialog = false">
  <template #header>Custom Header</template>
  <div>Content</div>
  <template #footer>Action Buttons</template>
</StackedDialog>
```

#### Selector Pattern
```vue
<!-- Base pattern for all selectors -->
<SelectorComponent
  v-model="selectedValue"
  :options="availableOptions"
  :loading="loading"
  @create="handleCreate"
/>
```

#### Form Pattern
```vue
<!-- Base pattern for form components -->
<FormComponent
  :model-value="formData"
  :errors="errors"
  :saving="saving"
  @update:model-value="formData = $event"
  @save="handleSave"
/>
```

## Best Practices

### Component Development
1. **Use TypeScript-style props** with proper validation
2. **Emit events for all user actions** - don't mutate props directly
3. **Provide comprehensive slot system** for customization
4. **Follow Tailwind CSS utility-first approach**
5. **Use Vue Composition API** for complex logic

### State Management
1. **Use TanStack Query** for server state
2. **Use reactive refs** for local component state
3. **Emit events** to parent for state changes
4. **Avoid Vuex/Pinia** unless absolutely necessary

### Error Handling
1. **Display errors contextually** near related fields
2. **Provide loading states** for async operations
3. **Handle network failures gracefully**
4. **Show user-friendly error messages**

### Performance
1. **Use v-show over v-if** for frequently toggled content
2. **Implement proper key attributes** for list rendering
3. **Lazy load heavy components** when possible
4. **Debounce user input** for search and filters

## Migration Guides

### From Legacy Modal to StackedDialog
See [Dialog System Documentation](dialog-system.md#migration-from-legacy-modal) for complete migration guide.

### From Axios to TanStack Query
Service Vault has migrated from direct axios usage to TanStack Query for better caching and state management. New components should use the query composables:

```vue
<script setup>
import { useAccountsQuery } from '@/Composables/queries/useAccountsQuery'

const { data: accounts, isLoading, error } = useAccountsQuery()
</script>
```

## Component Testing

### Testing Approach
1. **Unit tests** for individual component logic
2. **Integration tests** for component interactions
3. **Visual regression tests** for UI consistency
4. **Accessibility testing** for compliance

### Testing Utilities
- Vue Testing Library for component testing
- Mock Service Worker (MSW) for API mocking
- Playwright for end-to-end testing

## Contributing

### Adding New Components
1. Follow existing naming conventions
2. Create comprehensive documentation
3. Include usage examples
4. Add to appropriate index files
5. Update migration guides if replacing existing components

### Component Review Checklist
- [ ] Props are properly typed and validated
- [ ] Events are emitted for all user actions
- [ ] Component is responsive and accessible
- [ ] Error states are handled gracefully
- [ ] Loading states provide feedback
- [ ] Documentation is complete and accurate
- [ ] Examples demonstrate common usage patterns

---

*Last Updated: August 15, 2025 - Enhanced Dialog System: Auto-stacking dialogs with tabbed interfaces, smart dismissal behavior, and comprehensive component documentation*