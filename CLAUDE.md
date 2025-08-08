# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Service Vault is a comprehensive time management and invoicing system built with Laravel 12. It features hierarchical account management, advanced timer synchronization, ABAC permission system, comprehensive billing/invoicing capabilities, and enterprise-level theming for multi-tenant usage.

### Core Features ✅ IMPLEMENTED
- **Real-time Timer System**: App-wide floating overlay with live duration and dollar calculations
- **Modern Frontend Stack**: Vue.js 3.5 + Inertia.js + Headless UI + Tailwind CSS 4
- **ABAC Permission System**: Role templates with no hard-coded roles, hierarchical inheritance  
- **Cross-device Synchronization**: Redis-based timer state management with conflict resolution
- **Comprehensive API**: 25+ endpoints with authentication and authorization
- **Enterprise Architecture**: PostgreSQL + Redis with scalable component design

### Planned Features 🔄 IN DEVELOPMENT
- **Multi-Role Dashboard System**: Admin, Employee, Manager, and Customer Portal interfaces
- **Real-time Broadcasting**: WebSocket updates across devices and users
- **Time Entry Management**: Approval workflows and bulk operations
- **Hierarchical Account Management**: Unlimited depth organizational structures
- **Domain-based User Assignment**: Automatic account assignment via email domains
- **Enterprise Theming**: Multi-tenant branding with CSS custom properties
- **AccountSelector Component**: Hierarchical account selection for domain mapping
- **Comprehensive Billing**: Two-tier rate structure with account overrides

### Current Status: Phase 6/15 Complete (40% MVP Ready)
**✅ Working Features:**
- App-wide timer overlay with full control suite
- Real-time duration tracking and billing calculations  
- Cross-device timer synchronization via Redis
- Modern Vue.js frontend with responsive design
- Comprehensive backend API with ABAC authorization

**🎯 Next Priority:** Authentication system integration (Laravel Breeze) and real-time broadcasting (Laravel Echo)

## Documentation

All project documentation is centralized in `/docs/index.md`. Refer to the documentation index for:

- Development standards and workflows
- Architecture and database design
- Feature specifications
- API documentation
- Deployment guides

**Primary Reference**: [Documentation Index](docs/index.md)

### Documentation Maintenance Policy

**IMPORTANT**: Always update documentation when making code changes:

1. **Code Changes** → Update relevant documentation files
2. **New Features** → Add to `/docs/features/` 
3. **API Changes** → Update `/docs/api/` specifications
4. **Architecture Changes** → Update `/docs/architecture/`
5. **Development Process Changes** → Update `/docs/development/`

**Documentation Structure**:
```
docs/
├── index.md                    # Master index
├── development/                # Development guides
├── architecture/               # System architecture  
├── features/                   # Feature specifications
├── api/                        # API documentation
└── deployment/                 # Infrastructure guides
```

When creating new features or modifying existing ones, ensure documentation is updated in the same commit or pull request.

## Quick Start

```bash
# Development servers
php artisan serve         # Start Laravel server (http://localhost:8000)
npm run dev              # Start Vite dev server (with HMR)

# Database operations  
php artisan migrate:fresh --seed  # Reset database with test data
php artisan migrate      # Run pending migrations only
php artisan db:seed      # Seed test data only

# Frontend development
npm run build           # Production build
npm run dev             # Development with hot reload

# API testing endpoints (Timer System)
# GET    /api/timers                     # List user timers
# POST   /api/timers                     # Start new timer  
# GET    /api/timers/active/current      # Get current running timer
# POST   /api/timers/{timer}/stop        # Stop timer
# POST   /api/timers/{timer}/pause       # Pause running timer
# POST   /api/timers/{timer}/resume      # Resume paused timer
# POST   /api/timers/{timer}/commit      # Stop and convert to time entry
# DELETE /api/timers/{timer}?force=true  # Force delete timer

# Standard Laravel CLI
php artisan make:model ModelName -mfs          # Model + migration/factory/seeder
php artisan make:controller Api/ModelController --api --model=Model  # API controller
php artisan make:policy ModelPolicy --model=Model  # Authorization policy

# Testing & debugging
php artisan test         # Run test suite
php artisan tinker       # Interactive shell
```

## Architecture Guidelines

### Core Technology Stack
- **Backend**: Laravel 12 with PHP 8.2+
- **Database**: PostgreSQL (primary), MySQL (alternative)
- **Frontend**: Inertia.js + Vue.js 3.5+ with Headless UI + Tailwind CSS
- **UI Library**: Headless UI for accessibility + Tailwind CSS for comprehensive theming
- **Authentication**: Laravel Sanctum or Laravel Breeze
- **Queue System**: Redis or Database queues
- **Real-time**: Laravel Echo with Pusher or Socket.io
- **Theming**: CSS Custom Properties with centralized theme management

## Frontend Architecture

### Multi-Role Dashboard System

Service Vault implements a comprehensive role-based dashboard system:

#### Admin Dashboard (`/dashboard/admin`)
- **System Management**: User management, role template configuration
- **Account Hierarchy**: Create and manage organizational structures
- **Permission Administration**: ABAC role template management (super-admin)
- **Billing Configuration**: System-wide and account-specific rate management
- **Data Import/Export**: Comprehensive import system with field mapping
- **System Settings**: Global configuration and theme management

#### Employee Dashboard (`/dashboard/employee`)
- **Time Tracking**: Advanced timer with billing rate selection and real-time sync
- **Ticket Management**: Create, update, and resolve assigned tickets
- **Personal Analytics**: Time summaries, productivity metrics
- **Account Access**: View accounts based on permissions and assignments
- **Timer Controls**: Cross-device synchronization with pause/resume

#### Manager Dashboard (`/dashboard/manager`)
- **Team Oversight**: View and approve team time entries
- **Project Management**: Assign tickets and manage project workflows
- **Rate Management**: Configure project-specific billing rates
- **Team Analytics**: Productivity reports and project status dashboards
- **Approval Workflows**: Multi-stage time entry approval system

#### Customer Portal (`/portal`)
- **Account-Scoped Access**: Limited view of own account data only
- **Ticket Viewing**: Progress tracking with real-time status updates
- **Time Visibility**: View time spent and associated costs (if permitted)
- **Invoice Access**: Download invoices and payment history (if permitted)
- **Account Theming**: Branded experience with account-specific themes

### ABAC Permission System

#### Role Template Architecture
```php
// No hard-coded roles - all permissions via templates
class RoleTemplate extends Model
{
    protected $fillable = ['name', 'permissions', 'is_system_role', 'is_default'];
    
    protected $casts = [
        'permissions' => 'array',
        'is_system_role' => 'boolean',
        'is_default' => 'boolean',
    ];
}
```

#### Default Seeded Role Templates
- **Super Administrator**: Role template management, license management
- **System Administrator**: User management, system settings, account creation
- **Account Manager**: Account setup, user assignment, rate customization
- **Team Lead/Manager**: Team oversight, approval workflows, project planning
- **Employee**: Time tracking, ticket management, personal analytics
- **Customer/Client**: Portal access, ticket viewing, invoice access
- **Billing Administrator**: Invoice generation, payment tracking, rate management

#### Permission Inheritance
```php
// Hierarchical permission inheritance through accounts
public function hasPermissionForAccount(string $permission, Account $account): bool
{
    // Check direct permissions
    if ($this->hasDirectPermission($permission, $account)) {
        return true;
    }
    
    // Check inherited permissions from parent accounts
    return $account->ancestors()->get()->some(function ($parent) use ($permission) {
        return $this->hasDirectPermission($permission, $parent);
    });
}
```

### Component Architecture

#### AccountSelector Component
Critical component for domain mapping feature in Settings → Email → Domain Mapping:

```vue
<AccountSelector
  v-model="form.accountId"
  :show-hierarchy="true"
  :filter-accessible="true"
  placeholder="Select account for domain mapping"
  required
/>
```

**Features:**
- Hierarchical account display with unlimited depth
- Permission-filtered account lists
- Search and filtering capabilities
- Visual hierarchy indicators
- Accessibility compliance (WCAG)

#### Timer Widget System
```vue
<template>
  <div class="timer-system">
    <!-- Global floating timer -->
    <GlobalTimerWidget />
    
    <!-- Individual timer cards -->
    <TimerCard 
      v-for="timer in activeTimers" 
      :key="timer.id"
      :timer="timer"
      @update="handleTimerUpdate"
    />
  </div>
</template>
```

**Real-time Features:**
- Cross-device synchronization via Laravel Echo
- Billing rate pre-selection with persistence
- Running dollar amount calculations
- Optimistic UI updates with server confirmation

### Backend Integration Strategy

#### Voyager Integration
Voyager is used **exclusively for backend data management**:

- **Admin Panel**: `/admin` - Voyager BREAD interfaces for data management
- **Primary UI**: `/dashboard`, `/portal` - Custom Inertia.js + Vue.js interfaces
- **Data Management**: Voyager handles database operations, exports, imports
- **User Interfaces**: Custom Vue components for all user-facing features

#### API Architecture
```php
// API routes for frontend consumption
Route::middleware(['auth:sanctum'])->group(function () {
    Route::apiResource('accounts', AccountController::class);
    Route::get('accounts/selector', [AccountController::class, 'selector']);
    Route::apiResource('timers', TimerController::class);
    Route::post('timers/{timer}/stop', [TimerController::class, 'stop']);
});
```

### Database Design Patterns

#### Hierarchical Account Structure
Use nested set model or closure table pattern for unlimited depth account hierarchies:

```php
// Account model relationships
public function parent()
{
    return $this->belongsTo(Account::class, 'parent_id');
}

public function children()
{
    return $this->hasMany(Account::class, 'parent_id');
}

public function descendants()
{
    return $this->hasMany(Account::class, 'parent_id')
                ->with('descendants');
}
```

#### Permission System (ABAC)
Implement Attribute-Based Access Control using Laravel Policies and Gates:

```php
// Use Laravel's built-in authorization
Gate::define('manage-account', function (User $user, Account $account) {
    return $user->hasPermissionForAccount('manage', $account);
});

// In controllers
$this->authorize('manage-account', $account);
```

#### Polymorphic Custom Fields
Use Laravel's polymorphic relationships for flexible custom fields:

```php
public function customFields()
{
    return $this->morphMany(CustomField::class, 'customizable');
}
```

### Real-time Timer System
Implement using Laravel Echo and broadcasting:

```php
// Timer events
event(new TimerUpdated($timer));

// In TimerUpdated event
public function broadcastOn()
{
    return new PrivateChannel('user.' . $this->timer->user_id);
}
```

### Standard Development Method: Laravel CLI-First Approach

Service Vault follows Laravel 12 best practices using CLI-first generation for consistency and maintainability.

#### Model + Migration Generation (STANDARD METHOD)
Always generate models with migrations, factories, and seeders together:
```bash
# Generate complete model structure
php artisan make:model Account -mfs
php artisan make:model Timer -mfs  
php artisan make:model TimeEntry -mfs
php artisan make:model Project -mfs
php artisan make:model Task -mfs

# For pivot tables
php artisan make:migration create_role_user_table --create=role_user

# For existing table modifications
php artisan make:migration add_service_vault_fields_to_users_table --table=users
```

#### API Controllers with Model Binding
Generate API controllers with full CRUD operations:
```bash
php artisan make:controller Api/AccountController --api --model=Account
php artisan make:controller Api/TimerController --api --model=Timer
```

#### Authorization Policies
Generate policies for ABAC system:
```bash
php artisan make:policy AccountPolicy --model=Account
php artisan make:policy TimerPolicy --model=Timer
```

#### Form Requests for Validation
```bash
php artisan make:request StoreTimeEntryRequest
php artisan make:request UpdateTimeEntryRequest
```

#### API Resources for Response Consistency
```bash
php artisan make:resource TimeEntryResource
php artisan make:resource TimeEntryCollection
```

#### Service Classes
Create service classes for complex business logic:
```php
app/Services/
├── TimerService.php
├── BillingService.php
├── PermissionService.php
└── AccountService.php
```

#### Repository Pattern (Optional)
For complex queries, consider repositories:
```php
app/Repositories/
├── AccountRepository.php
└── TimeEntryRepository.php
```

### File Structure Conventions

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── Dashboard/          # Admin dashboard controllers
│   │   ├── Portal/             # Customer portal controllers
│   │   └── Api/                # API endpoints
│   ├── Requests/               # Form validation
│   ├── Resources/              # API resources
│   └── Middleware/             # Custom middleware
├── Models/
│   ├── User.php
│   ├── Account.php
│   ├── TimeEntry.php
│   ├── Timer.php
│   ├── Theme.php               # Theme management
│   └── Permissions/            # Permission-related models
├── Services/                   # Business logic services
│   ├── ThemeService.php        # Centralized theme management
│   └── ...
├── Repositories/               # Data access layer (if used)
├── Events/                     # Domain events
├── Listeners/                  # Event listeners
├── Jobs/                       # Queue jobs
└── Policies/                   # Authorization policies

database/
├── migrations/
├── seeders/
└── factories/

resources/
├── js/                         # Vue.js components (if using Inertia)
│   ├── Components/
│   │   ├── Selectors/          # Hierarchical selectors
│   │   ├── Timer/              # Timer components
│   │   ├── Theme/              # Theme management components
│   │   └── UI/                 # Base UI components (Headless UI + Tailwind)
│   ├── Pages/
│   │   ├── Dashboard/          # Admin pages
│   │   └── Portal/             # Customer portal pages
│   ├── Layouts/
│   ├── Composables/            # Vue composables for theme management
│   └── Themes/                 # Theme configuration and utilities
├── css/
│   ├── themes/                 # Theme-specific CSS files
│   │   ├── default.css
│   │   ├── dark.css
│   │   └── high-contrast.css
│   └── app.css                 # Main application styles
└── views/                      # Blade templates (if using Livewire)

tests/
├── Feature/                    # Integration tests
└── Unit/                       # Unit tests
```

## Critical Implementation Notes

### Centralized Theming System

Service Vault requires enterprise-level theming capabilities for multi-tenant usage and brand customization:

#### Theme Architecture
```php
// Theme Service for Laravel backend
class ThemeService
{
    public function getAccountTheme(Account $account): array
    {
        return array_merge(
            $this->getSystemDefaultTheme(),
            $account->theme_settings ?? []
        );
    }
    
    public function getUserTheme(User $user): array
    {
        return array_merge(
            $this->getAccountTheme($user->currentAccount),
            $user->theme_preferences ?? []
        );
    }
}
```

#### CSS Custom Properties Strategy
```css
:root {
  /* Primary Theme Colors */
  --color-primary: theme('colors.blue.600');
  --color-secondary: theme('colors.gray.600');
  --color-accent: theme('colors.indigo.600');
  
  /* Timer-specific Colors */
  --color-timer-running: theme('colors.green.500');
  --color-timer-paused: theme('colors.yellow.500');
  --color-timer-stopped: theme('colors.gray.500');
  
  /* Account Brand Colors (dynamic) */
  --color-brand-primary: var(--account-primary, var(--color-primary));
  --color-brand-secondary: var(--account-secondary, var(--color-secondary));
}

[data-theme="dark"] {
  --color-primary: theme('colors.blue.400');
  --color-background: theme('colors.gray.900');
  --color-surface: theme('colors.gray.800');
}
```

#### Vue.js Theme Composable
```javascript
// useTheme.js - Vue composable for theme management
import { ref, computed, watch } from 'vue'

export function useTheme() {
  const currentTheme = ref('default')
  const accountTheme = ref({})
  const userPreferences = ref({})
  
  const resolvedTheme = computed(() => {
    return {
      ...systemDefaults,
      ...accountTheme.value,
      ...userPreferences.value
    }
  })
  
  const applyTheme = (theme) => {
    Object.entries(theme).forEach(([key, value]) => {
      document.documentElement.style.setProperty(`--${key}`, value)
    })
  }
  
  return { currentTheme, resolvedTheme, applyTheme }
}
```

### Permission System
- Implement role templates as Laravel models with JSON permission storage
- Use Laravel Gates for permission checking with caching
- Create middleware for permission validation on routes
- Implement hierarchical permission inheritance through account relationships

### Timer Synchronization
- Use Laravel Echo for real-time updates
- Implement optimistic UI updates with server confirmation
- Store timer state in Redis for cross-device sync
- Use background jobs for timer calculations

### Billing Rate System
- Implement two-tier rate structure with account overrides
- Use rate snapshotting for historical accuracy
- Create dedicated service for rate resolution logic
- Handle rate inheritance through account hierarchy

### Account Hierarchy
- Use nested set or closure table for efficient hierarchy queries
- Implement scope methods for account-based data filtering
- Create efficient queries for permission inheritance
- Handle domain-based user assignment through CSV parsing

## Testing Strategy

### Feature Tests
- Test complete user workflows (timer start → stop → billing)
- Test permission system across account hierarchies
- Test real-time timer synchronization
- Test billing rate calculations and inheritance

### Unit Tests
- Test permission resolution logic
- Test rate calculation services
- Test account hierarchy traversal
- Test custom field validation

### Browser Tests (Laravel Dusk)
- Test complex UI interactions (hierarchical selectors)
- Test timer functionality across multiple tabs
- Test responsive design and mobile interactions

## Development Workflow

1. **Database First**: Design and migrate database schema
2. **Models & Relationships**: Set up Eloquent models with proper relationships
3. **Permissions & Policies**: Implement authorization layer
4. **Services & Business Logic**: Create service classes for complex operations
5. **Controllers & Routes**: Build API and web controllers
6. **Frontend Components**: Develop Vue.js components or Livewire components
7. **Real-time Features**: Implement broadcasting and WebSocket connections
8. **Testing**: Write comprehensive tests for all features

## Performance Considerations

### Database Optimization
- Use eager loading to prevent N+1 queries
- Index foreign keys and commonly queried fields
- Use database-level constraints for data integrity
- Consider read replicas for reporting queries

### Caching Strategy
- Cache user permissions (5-minute TTL as per original design)
- Cache account hierarchies
- Use Redis for session storage and timer state
- Implement query result caching for complex reports

### Queue Management
- Use queues for email notifications
- Background processing for invoice generation
- Async timer calculations and billing operations
- Rate limiting for API endpoints

## Integration Points

### External Systems
- SMTP configuration for email notifications
- License validation API integration
- Data import from CSV/JSON/database sources
- API endpoints for third-party integrations

### Real-time Features
- Laravel Echo for timer synchronization
- WebSocket connections for live updates
- Real-time theme switching without page reloads
- Push notifications for mobile (future)
- Server-sent events for non-WebSocket clients

### UI Library Integration (Headless UI + Tailwind CSS)

#### Why This Combination?
1. **Comprehensive Theming**: Tailwind's design token system perfect for Service Vault's multi-tenant needs
2. **Accessibility First**: Headless UI provides WCAG-compliant components out of the box
3. **Complex Component Support**: Ideal for hierarchical selectors and timer interfaces
4. **Vue 3.5+ Optimized**: Native Composition API support with excellent TypeScript integration
5. **Enterprise Theming**: CSS custom properties + Tailwind classes = unlimited customization

#### Theme-Aware Component Pattern
```vue
<template>
  <div class="timer-widget" :class="themeClasses">
    <HeadlessDisclosure v-slot="{ open }">
      <HeadlessDisclosureButton class="timer-control">
        <TimerDisplay :time="currentTime" :status="timerStatus" />
      </HeadlessDisclosureButton>
      <HeadlessDisclosurePanel class="timer-settings">
        <BillingRateSelector v-model="selectedRate" />
        <ThemeSelector v-if="canCustomizeTheme" />
      </HeadlessDisclosurePanel>
    </HeadlessDisclosure>
  </div>
</template>

<script setup>
import { useTheme } from '@/Composables/useTheme'
const { resolvedTheme, applyTheme } = useTheme()

const themeClasses = computed(() => ({
  'timer-widget--branded': resolvedTheme.value.useBrandColors,
  'timer-widget--compact': resolvedTheme.value.density === 'compact'
}))
</script>
```

## Domain Mapping Feature Implementation

### Settings Integration
The domain mapping feature is located in **Settings → Email → Domain Mapping** and must use the AccountSelector component:

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
  </div>
</template>
```

### Backend Implementation
```php
class DomainMapping extends Model
{
    protected $fillable = [
        'domain_pattern',
        'account_id', 
        'is_active',
        'priority'
    ];
    
    public function account()
    {
        return $this->belongsTo(Account::class);
    }
}
```

## Development Workflow & Best Practices

### Component Development Priorities
1. **AccountSelector Component**: Required for domain mapping feature
2. **Timer Widget System**: Core functionality with real-time sync
3. **Dashboard Layout Components**: Role-based interface structure
4. **Theme Management Interface**: Enterprise customization tools
5. **Permission Management UI**: Role template administration

### Testing Strategy
```php
// Feature test for domain mapping with AccountSelector
public function test_domain_mapping_with_account_selector()
{
    $admin = User::factory()->create();
    $account = Account::factory()->create(['name' => 'Test Company']);
    
    $response = $this->actingAs($admin)
        ->post('/api/domain-mappings', [
            'domain_pattern' => '*.testcompany.com',
            'account_id' => $account->id
        ]);
    
    $response->assertStatus(201);
    $this->assertDatabaseHas('domain_mappings', [
        'domain_pattern' => '*.testcompany.com',
        'account_id' => $account->id
    ]);
}
```

### Browser Testing
```javascript
// AccountSelector component interaction testing
test('account selector shows hierarchy', async ({ page }) => {
  await page.goto('/settings/email/domain-mapping');
  await page.click('[data-testid="account-selector"]');
  
  // Verify hierarchical display
  await expect(page.locator('.account-option[data-depth="0"]')).toBeVisible();
  await expect(page.locator('.account-option[data-depth="1"]')).toBeVisible();
});
```