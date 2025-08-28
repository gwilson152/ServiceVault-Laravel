# CLAUDE.md

This file provides development conventions and guidelines for working with the Service Vault codebase.

## Project Overview

Service Vault is a comprehensive B2B service ticket and time management platform built with Laravel 12. It is primarily a **ticketing/service request platform** with sophisticated time tracking capabilities, featuring three-dimensional permission system and enterprise-level customization.

**🎯 Platform Status:** Production-Ready

## Development Conventions

### Code Standards
- **NEVER ADD COMMENTS** unless explicitly asked
- Follow existing patterns in the codebase before creating new ones
- Always check for existing libraries/utilities before adding dependencies
- Use consistent naming conventions across similar components
- Follow Laravel conventions for models, controllers, and routes

### Database Conventions
- **Fresh Migration System**: Use consolidated migrations for new deployments
- **UUID Primary Keys**: All user-facing entities use UUID primary keys
- **Composite Constraints**: Email + user_type constraint allows duplicate emails across user types
- **External IDs**: All importable models include `external_id` for import system integration
- **PostgreSQL Features**: Leverage triggers, partial indexes, and check constraints

### API Conventions
- RESTful endpoints with consistent parameter naming
- Use `agent_id` instead of `assigned_user_id` for clarity
- Three-dimensional authentication (functional + widget + page permissions)
- Standardized response format with data/meta structure
- Permission-aware filtering on all endpoints

### Frontend Conventions
- **StackedDialog Architecture**: Use native `<dialog>` elements for all modals
- **UnifiedSelector Components**: Consistent selector components for all entity types
- **StandardPageLayout**: Use for all list/index pages with configurable slots
- **MultiSelect Filters**: Use with user-specific persistence
- **TanStack Query**: Use for all API calls with proper caching and error handling

### Reusable UI Components

**Core Layout Components**:
- **`StandardPageLayout`**: Main layout wrapper with configurable slots for all list/index pages
- **`FilterSection`**: Standardized filter container with collapsible mobile behavior
- **`PageHeader`**: Consistent page headers with title and action areas
- **`PageSidebar`**: Sidebar wrapper with automatic spacing

**Modal & Dialog Components**:
- **`StackedDialog`**: Native `<dialog>`-based modal system with proper z-index stacking
- **`Modal`** *(deprecated)*: Legacy modal component - migrate to StackedDialog

**Selector & Input Components**:
- **`UnifiedSelector`**: Self-managing selector for all entity types (tickets, accounts, users, agents, billing-rates, role-templates)
- **`MultiSelect`**: Multi-select dropdown with user persistence for filters
- **`SearchableSelect`**: Single-select with search functionality

**Timer Components**:
- **`TimerBroadcastOverlay`**: Persistent timer interface with real-time sync across pages
- **`TimerCard`**: Individual timer display component
- **`TimerControls`**: Timer action buttons (start, stop, pause, commit)

### Component Usage Examples

```vue
<!-- Standard Page Layout -->
<StandardPageLayout :title="pageTitle" :show-sidebar="true" :show-filters="true">
  <template #header-actions><!-- Action buttons --></template>
  <template #filters><FilterSection><!-- Filters --></FilterSection></template>
  <template #main-content><!-- Primary content --></template>
  <template #sidebar><!-- Stats and widgets --></template>
</StandardPageLayout>

<!-- Unified Selectors (Self-Managing) -->
<UnifiedSelector v-model="selectedAccountId" type="account" />
<UnifiedSelector v-model="selectedAgentId" type="agent" agent-type="ticket" />
<UnifiedSelector v-model="selectedRateId" type="billing-rate" />

<!-- Stacked Dialog -->
<StackedDialog :show="isOpen" title="Dialog Title" @close="closeDialog">
  <!-- Dialog content -->
</StackedDialog>

<!-- Multi-Select Filters -->
<MultiSelect v-model="selectedStatuses" :options="ticketStatuses" value-key="key" label-key="name" />
```

### Security Conventions
- Never commit secrets or keys to the repository
- Always follow security best practices
- Use Laravel Sanctum for API authentication
- Implement proper permission checks on all operations
- Sanitize all user inputs

### Testing Conventions
- **Check README/codebase** for testing approach before assuming frameworks
- **Run lint/typecheck** commands after code changes (npm run lint, npm run typecheck, etc.)
- Ask user for commands if not found, suggest adding to CLAUDE.md

### Git Conventions
- **NEVER commit** unless explicitly asked
- Only commit when user explicitly requests it
- Include consolidated migration achievements in commit messages

## Documentation Structure

All detailed documentation is centralized in `/docs/`. **Always update documentation when making code changes.**

### Documentation Policy
1. **Code Changes** → Update relevant documentation files
2. **New Features** → Add to `/docs/guides/`
3. **API Changes** → Update `/docs/api/` specifications  
4. **Architecture Changes** → Update `/docs/technical/`
5. **Development Process Changes** → Update `/docs/technical/development.md`

### Quick Documentation Reference
- **[Documentation Index](docs/README.md)** - Master index with quick start
- **[Setup Guide](docs/guides/setup.md)** - Installation and configuration
- **[API Reference](docs/api/resources.md)** - Complete API documentation
- **[System Architecture](docs/technical/architecture.md)** - Technical implementation
- **[Database Schema](docs/technical/database.md)** - PostgreSQL schema reference
- **[UI Components](docs/technical/ui-components.md)** - Component library reference

## Recent Achievements

**✅ Consolidated Database Migration System:**
- Consolidated 87+ fragmented migrations into 8 logical, comprehensive migration files
- Fixed FreeScout import duplicate email issues with `(email, user_type)` composite constraint
- Clean deployments with no migration history baggage
- PostgreSQL optimized with triggers, partial indexes, and check constraints
- All models and seeders updated to match consolidated schema

**✅ Production-Ready Features:**
- Three-dimensional permission system with role templates
- Multi-timer system with Redis state management and real-time sync
- Complete billing system with two-tier rate hierarchy
- Universal import system with visual query builder
- Real-time broadcasting with Laravel Reverb WebSockets
- Enterprise authentication with Laravel Sanctum

## Important Instructions

1. **Always prefer editing existing files** over creating new ones
2. **Never proactively create documentation** unless explicitly requested
3. **Check existing patterns** before implementing new solutions
4. **Update documentation** in the same commit/PR as code changes
5. **Use TodoWrite tool** for complex multi-step tasks
6. **Run tests and lint** commands after significant changes

---

*This CLAUDE.md focuses on development conventions. For detailed implementation guides, API specifications, and system architecture, refer to the comprehensive documentation in `/docs/`.*