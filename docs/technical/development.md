# Development Guide

Development workflows, coding standards, and best practices for Service Vault.

## Development Environment

### Prerequisites
- **PHP 8.3+** with required extensions
- **Composer 2.x** for dependency management
- **Node.js 18+** and **npm/pnpm** for frontend builds
- **PostgreSQL 15+** as primary database
- **Redis 7+** for caching and session storage

### Environment Setup
```bash
# Clone repository
git clone <repository-url>
cd servicevault-laravel

# Install PHP dependencies
composer install

# Install frontend dependencies  
npm install

# Environment configuration
cp .env.example .env
php artisan key:generate

# Database setup
php artisan migrate:fresh --seed

# Development servers (manual start)
php artisan serve              # Laravel (http://localhost:8000)
php artisan reverb:start       # WebSocket (http://localhost:8080)  
npm run dev                    # Vite HMR (automatic)
```

## Development Workflow

### Git Workflow
- **Main Branch**: `master` (production-ready)
- **Feature Branches**: `feature/description` or `fix/issue-description`
- **Commit Messages**: Descriptive commits with context

### Code Generation Standards

**Laravel CLI-First Approach**:
```bash
# Model generation with relationships
php artisan make:model Account -mfs
# Creates: Model + Migration + Factory + Seeder

# API Controller generation  
php artisan make:controller Api/AccountController --api --model=Account

# Authorization policies
php artisan make:policy AccountPolicy --model=Account

# Form request validation
php artisan make:request StoreAccountRequest
```

### Database Management

**Migration Best Practices**:
```php
// Always check table existence
if (Schema::hasTable('table_name')) {
    return;
}

// Use transactions for complex changes
DB::transaction(function () {
    Schema::create('table_name', function (Blueprint $table) {
        // Table definition
    });
});
```

**Seeding Strategy**:
- Development: Full dataset with realistic relationships
- Testing: Minimal dataset for test scenarios
- Production: Essential system data only

## Coding Standards

### Laravel Conventions

**Model Standards**:
```php
class Account extends Model
{
    use HasFactory, SoftDeletes;
    
    protected $fillable = ['name', 'description', 'parent_id'];
    
    protected $casts = [
        'settings' => 'array',
        'is_active' => 'boolean',
    ];
    
    // Relationships
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
```

**Controller Patterns**:
```php
class Api\AccountController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Account::class);
        
        return AccountResource::collection(
            Account::query()
                ->when($request->search, fn($q) => $q->where('name', 'like', "%{$request->search}%"))
                ->paginate($request->per_page ?? 20)
        );
    }
}
```

**Authorization Pattern**:
```php
// Policy-based authorization
$this->authorize('create', Ticket::class);

// Permission-based checking
if ($user->hasPermission('tickets.manage')) {
    // Allow action
}

// Token ability checking  
if ($user->currentAccessToken()?->can('tickets:write')) {
    // API action allowed
}
```

### Vue.js Standards

**Component Structure**:
```vue
<template>
  <!-- Template with semantic HTML -->
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useQuery } from '@tanstack/vue-query'

// Props and emits
const props = defineProps({
  modelValue: String,
  items: Array
})

const emit = defineEmits(['update:modelValue', 'item-selected'])

// Reactive state
const isOpen = ref(false)
const selectedItem = ref(null)

// Computed properties
const filteredItems = computed(() => {
  // Logic here
})

// Lifecycle
onMounted(() => {
  // Initialize component
})
</script>
```

**State Management**:
```javascript
// TanStack Query for server state
const { data: tickets, isLoading } = useQuery({
  queryKey: ['tickets', filters],
  queryFn: () => fetchTickets(filters)
})

// Local reactive state
const form = reactive({
  title: '',
  description: '',
  account_id: null
})
```

## Testing Standards

### Backend Testing
```php
// Feature test example
class TicketManagementTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_user_can_create_ticket()
    {
        $user = User::factory()->create();
        $account = Account::factory()->create();
        
        $response = $this->actingAs($user)
            ->postJson('/api/tickets', [
                'title' => 'Test Ticket',
                'account_id' => $account->id
            ]);
            
        $response->assertCreated();
        $this->assertDatabaseHas('tickets', ['title' => 'Test Ticket']);
    }
}
```

### Frontend Testing
- Component unit tests with Vue Test Utils
- E2E tests with Playwright/Cypress
- API integration tests

## Architecture Guidelines

### Component Architecture

#### UnifiedSelector Component

Single, consistent selector component for all entity types throughout the application:

**Basic Usage**:
```vue
<template>
  <UnifiedSelector
    v-model="selectedId"
    type="account"
    :items="availableAccounts"
    label="Account"
    placeholder="Select account..."
    @item-selected="handleSelection"
  />
</template>

<script setup>
const selectedId = ref(null)
const availableAccounts = ref([])

const handleSelection = (account) => {
  console.log('Selected account:', account)
}
</script>
```

**Supported Types and Features**:
```vue
<!-- Ticket Selection with Creation -->
<UnifiedSelector
  v-model="ticketId"
  type="ticket"
  :items="tickets"
  :can-create="true"
  @item-created="handleTicketCreated"
/>

<!-- Hierarchical Account Selection -->
<UnifiedSelector
  v-model="accountId"
  type="account"
  :items="accounts"
  :hierarchical="true"
  :can-create="hasAccountCreatePermission"
/>

<!-- Agent Selection with Feature-Specific Filtering -->
<UnifiedSelector
  v-model="agentId"
  type="agent"
  agent-type="timer"
  :items="timerAgents"
  label="Timer Agent"
/>

<!-- Billing Rate Selection with Hierarchy Display -->
<UnifiedSelector
  v-model="rateId"
  type="billing-rate"
  :items="billingRates"
  :show-hierarchy="true"
  placeholder="Select billing rate..."
/>

<!-- Nested Modal Usage -->
<UnifiedSelector
  v-model="userId"
  type="user"
  :items="users"
  :nested="true"
  :can-create="true"
/>
```

**Component Props**:
- `type`: Entity type (`ticket`, `account`, `user`, `agent`, `billing-rate`)
- `items`: Array of available items
- `hierarchical`: Show hierarchical structure (accounts, billing rates)
- `can-create`: Enable "Create New" option
- `nested`: Proper z-index for nested modals
- `agent-type`: Feature-specific agent filtering (`timer`, `ticket`, `time`, `billing`)

#### StackedDialog Component

Native `<dialog>`-based modal system with proper stacking and accessibility:

**Basic Usage**:
```vue
<template>
  <StackedDialog
    :show="isDialogOpen"
    title="Edit Timer"
    max-width="2xl"
    @close="closeDialog"
  >
    <form @submit.prevent="handleSubmit">
      <!-- Form content -->
      <div class="flex justify-end space-x-3 mt-6">
        <button type="button" @click="closeDialog" class="btn-secondary">
          Cancel
        </button>
        <button type="submit" class="btn-primary">
          Save Changes
        </button>
      </div>
    </form>
  </StackedDialog>
</template>

<script setup>
const isDialogOpen = ref(false)

const openDialog = () => {
  isDialogOpen.value = true
}

const closeDialog = () => {
  isDialogOpen.value = false
}

const handleSubmit = () => {
  // Handle form submission
  closeDialog()
}
</script>
```

**Advanced Dialog Features**:
```vue
<!-- Large Modal with Custom Close Handling -->
<StackedDialog
  :show="isOpen"
  title="Create Ticket"
  max-width="4xl"
  :prevent-close="hasUnsavedChanges"
  @close="handleClose"
  @before-close="confirmClose"
>
  <!-- Large form content -->
</StackedDialog>

<!-- Nested Modal (Higher Z-Index) -->
<StackedDialog
  :show="isNestedOpen"
  title="Select Account"
  max-width="md"
  nested
  @close="closeNested"
>
  <!-- Nested content -->
</StackedDialog>

<!-- Modal with Custom Header -->
<StackedDialog
  :show="isOpen"
  max-width="lg"
  @close="closeDialog"
>
  <template #header>
    <div class="flex items-center justify-between">
      <h3 class="text-lg font-semibold">Custom Header</h3>
      <div class="flex space-x-2">
        <button class="btn-icon">Settings</button>
      </div>
    </div>
  </template>
  
  <!-- Modal content -->
</StackedDialog>
```

**Dialog Props**:
- `show`: Boolean to control visibility
- `title`: Modal title (optional if using header slot)
- `max-width`: Tailwind max-width class (`sm`, `md`, `lg`, `xl`, `2xl`, `4xl`, etc.)
- `nested`: Higher z-index for stacked modals
- `prevent-close`: Disable close on backdrop click

**Migration from Old Modal System**:
```vue
<!-- ❌ Old Modal (deprecated) -->
<Modal :show="isOpen" @close="closeModal">
  <template #title>Modal Title</template>
  <!-- Content -->
</Modal>

<!-- ✅ New StackedDialog -->
<StackedDialog
  :show="isOpen"
  title="Modal Title"
  @close="closeModal"
>
  <!-- Content -->
</StackedDialog>
```

### Permission System Implementation

**Three-Dimensional Checking**:
```php
// Check across all permission dimensions
$user->hasPermission('tickets.view');        // Functional
$user->hasPermission('widgets.dashboard.tickets'); // Widget
$user->hasPermission('pages.tickets.manage'); // Page
```

**Feature-Specific Agents**:
```php
// Multi-layer agent determination
private function getAvailableAgents(string $agentType): Collection
{
    return User::query()
        ->where('user_type', 'agent')  // Primary agents
        ->orWhere(fn($q) => $q->whereHas('roleTemplate', fn($rt) => 
            $rt->whereJsonContains('permissions', $this->getAgentPermission($agentType))
        ))
        ->get();
}
```

## Performance Guidelines

### Database Optimization

**Query Optimization**:
```php
// Eager loading to prevent N+1
$tickets = Ticket::with(['account', 'assignedTo', 'timers.user'])->get();

// Selective column loading
$users = User::select(['id', 'name', 'email'])->get();

// Pagination for large datasets
$tickets = Ticket::paginate(20);
```

**Index Strategy**:
- Index frequently queried columns
- Composite indexes for multi-column queries
- JSON path indexes for JSONB columns

### Frontend Performance

**Component Optimization**:
```vue
<script setup>
import { computed, shallowRef } from 'vue'

// Use shallowRef for large objects
const items = shallowRef([])

// Memoize expensive computations
const expensiveComputation = computed(() => {
  return heavyProcessing(items.value)
})
</script>
```

**API Optimization**:
- Use TanStack Query for caching
- Implement proper loading states
- Batch API requests when possible

## Security Guidelines

### Input Validation
```php
// Form request validation
class StoreTicketRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'title' => 'required|string|max:500',
            'account_id' => 'required|exists:accounts,id',
            'ticket_id' => 'nullable|exists:tickets,id'
        ];
    }
}
```

### Authorization Patterns
- Always check permissions at controller level
- Use policies for complex authorization logic
- Validate token abilities for API endpoints

### Data Protection
- Never log sensitive data (passwords, tokens)
- Sanitize output to prevent XSS
- Use HTTPS in production environments

## Debugging & Development Tools

### Laravel Tools
```bash
# Interactive shell for testing
php artisan tinker

# Queue monitoring
php artisan horizon

# Route debugging
php artisan route:list

# Database inspection
php artisan db:show
```

### Frontend Tools
- Vue DevTools browser extension
- TanStack Query DevTools
- Network tab for API debugging

### Performance Monitoring
- Laravel Telescope (development)
- Laravel Debugbar
- Database query logging

## Deployment Considerations

### Environment Configuration
- Separate .env files per environment
- Environment-specific service configuration
- Secure credential management

### Build Process
```bash
# Production build
npm run build
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Monitoring & Logging
- Application performance monitoring
- Error tracking and alerting
- Database performance monitoring

For deployment specifics, see [Setup Guide](../guides/setup.md).

## Troubleshooting

### Common Issues
- **Timer overlay missing**: Check for non-Inertia navigation
- **Permission errors**: Verify role template assignments
- **Database connection**: Check PostgreSQL and Redis status
- **WebSocket issues**: Ensure Laravel Reverb is running

### Development Server Issues
- **Port conflicts**: Change default ports in configuration
- **CORS errors**: Verify Sanctum configuration
- **Asset compilation**: Clear Vite cache and rebuild