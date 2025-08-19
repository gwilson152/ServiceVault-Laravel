# Feature-Specific Agent Permission System

**Advanced Agent Assignment Control with Granular Feature-Based Permissions**

Service Vault implements a sophisticated feature-specific agent permission system that provides granular control over which users can act as agents for different system features. This system extends the basic Agent/Customer architecture with fine-grained permission control for specialized workflows.

## Overview

The feature-specific agent permission system allows administrators to precisely control which users can act as agents for specific features, providing better security and workflow control than simple universal agent designation.

### Core Concept

Instead of a single "agent" designation, the system now supports **four feature-specific agent permissions**:

```
┌─────────────────────────────────────────────────────────┐
│             Feature-Specific Agent Permissions         │
├─────────────────────────────────────────────────────────┤
│                                                         │
│  timers.act_as_agent      tickets.act_as_agent         │
│  ┌─────────────────┐      ┌─────────────────┐          │
│  │ Timer Creation  │      │ Ticket          │          │
│  │ Timer Assignment│      │ Assignment      │          │
│  │ Timer Management│      │ Responsibility  │          │
│  └─────────────────┘      └─────────────────┘          │
│                                                         │
│  time.act_as_agent        billing.act_as_agent         │
│  ┌─────────────────┐      ┌─────────────────┐          │
│  │ Time Entry      │      │ Billing Rate    │          │
│  │ Creation        │      │ Management      │          │
│  │ Assignment      │      │ Responsibility  │          │
│  └─────────────────┘      └─────────────────┘          │
│                                                         │
│           Multi-Layer Agent Determination               │
│        Primary → Secondary → Tertiary Fallback         │
└─────────────────────────────────────────────────────────┘
```

## Feature-Specific Permissions

### 1. Timer Agent Permission (`timers.act_as_agent`)

**Purpose**: Controls who can be assigned as the responsible agent for timer creation and management.

**Usage Context**:
- StartTimerModal when creating new timers
- Timer assignment in multi-user environments
- Timer management interfaces

**API Integration**:
```bash
GET /api/users/agents?agent_type=timer
```

**Frontend Components**:
- `StartTimerModal.vue` - Uses `agent_type: 'timer'`
- `AgentSelector.vue` - With `agentType="timer"`

### 2. Ticket Agent Permission (`tickets.act_as_agent`)

**Purpose**: Controls who can be assigned as the responsible agent for ticket management and resolution.

**Usage Context**:
- Ticket assignment workflows
- Ticket management interfaces
- Service delivery responsibility

**API Integration**:
```bash
GET /api/users/assignable  # Uses tickets.act_as_agent internally
```

**Frontend Components**:
- `AssignmentModal.vue` - Automatic ticket agent filtering
- `TicketAssignment.vue` - Service agent selection

### 3. Time Entry Agent Permission (`time.act_as_agent`)

**Purpose**: Controls who can be assigned as the responsible agent for time entry creation and management.

**Usage Context**:
- Manual time entry creation
- Time entry assignment workflows
- Time tracking management

**API Integration**:
```bash
GET /api/users/agents?agent_type=time
```

**Frontend Components**:
- `UnifiedTimeEntryDialog.vue` - Uses `agent_type: 'time'`
- `AgentSelector.vue` - With `agentType="time"`

### 4. Billing Agent Permission (`billing.act_as_agent`)

**Purpose**: Controls who can be assigned as the responsible agent for billing operations and rate management.

**Usage Context**:
- Billing rate creation and management
- Invoice responsibility assignment
- Financial operations management

**API Integration**:
```bash
GET /api/users/billing-agents
```

**Future Components**:
- Billing rate assignment interfaces
- Invoice management workflows
- Financial responsibility assignment

## Multi-Layer Agent Determination

The system uses a sophisticated multi-layer approach to determine valid agents for each feature:

### Layer 1: Primary Agent Designation
```php
// Users explicitly designated as agents
WHERE user_type = 'agent'
```

### Layer 2: Feature-Specific Permission
```php
// Users with specific feature agent permission
WHERE roleTemplate.permissions CONTAINS 'feature.act_as_agent'
```

### Layer 3: Fallback Permissions
```php
// Internal users with relevant functional permissions
WHERE account.account_type = 'internal' 
  AND roleTemplate.permissions CONTAINS fallback_permissions
```

### Permission Mapping

| Feature | Required Permission | Fallback Permissions |
|---------|-------------------|---------------------|
| Timer | `timers.act_as_agent` | `timers.write`, `timers.manage` |
| Ticket | `tickets.act_as_agent` | `tickets.assign`, `tickets.manage` |
| Time | `time.act_as_agent` | `time.track`, `time.manage` |
| Billing | `billing.act_as_agent` | `billing.manage`, `billing.admin` |

## Implementation Architecture

### Backend Implementation

#### UserController Agent Methods

The `UserController` provides specialized endpoints for each agent type:

```php
// Generic agent endpoint with feature filtering
public function agents(Request $request): JsonResponse
{
    $agentType = $request->get('agent_type', 'timer');
    $requiredPermission = $this->getRequiredAgentPermission($agentType);
    // ... filtering logic
}

// Ticket-specific agent endpoint
public function assignableUsers(Request $request): JsonResponse
{
    // Uses tickets.act_as_agent permission
}

// Billing-specific agent endpoint  
public function billingAgents(Request $request): JsonResponse
{
    // Uses billing.act_as_agent permission
}
```

#### Permission Resolution Helper Methods

```php
private function getRequiredAgentPermission(string $agentType): string
{
    return match($agentType) {
        'timer' => 'timers.act_as_agent',
        'ticket' => 'tickets.act_as_agent', 
        'time' => 'time.act_as_agent',
        'billing' => 'billing.act_as_agent',
        default => 'timers.act_as_agent'
    };
}

private function getFallbackPermissions(string $agentType): array
{
    return match($agentType) {
        'timer' => ['timers.write', 'timers.manage'],
        'ticket' => ['tickets.assign', 'tickets.manage'],
        'time' => ['time.track', 'time.manage'], 
        'billing' => ['billing.manage', 'billing.admin'],
        default => ['timers.write', 'timers.manage']
    };
}
```

### Frontend Implementation

#### Enhanced AgentSelector Component

The `AgentSelector` component now supports feature-specific filtering:

```vue
<AgentSelector
  v-model="form.userId"
  label="Service Agent"
  :agent-type="'timer'"
  :agents="availableAgents"
  @agent-selected="handleAgentSelected"
/>
```

**Props**:
- `agentType`: Specifies which feature-specific permission to check
- `agents`: Pre-filtered list of valid agents
- `isLoading`: Loading state for agent data

**Agent Type Support**:
- `timer` - For timer creation and assignment
- `time` - For time entry creation and assignment  
- `ticket` - For ticket assignment (implicit)
- `billing` - For billing responsibility assignment

#### Component Integration

Each major component now uses appropriate agent types:

```vue
// StartTimerModal.vue
const loadAgentsForAccount = async (accountId) => {
  const params = {
    per_page: 100,
    agent_type: 'timer' // Specific to timer agents
  }
  const response = await axios.get('/api/users/agents', { params })
}

// UnifiedTimeEntryDialog.vue  
const loadAgentsForAccount = async (accountId) => {
  const params = {
    per_page: 100,
    agent_type: 'time' // Specific to time entry agents
  }
  const response = await axios.get('/api/users/agents', { params })
}
```

## Database Schema

### Role Template Integration

Feature-specific agent permissions are stored in the `permissions` array of role templates:

```sql
-- Role template with feature-specific agent permissions
INSERT INTO role_templates (permissions) VALUES (
  '[
    "timers.act_as_agent",
    "tickets.act_as_agent", 
    "time.act_as_agent",
    "billing.act_as_agent",
    "other.permissions"
  ]'::jsonb
);
```

### Migration System

The system includes a comprehensive migration to add feature-specific permissions to existing role templates:

```php
// Migration: 2025_08_15_211808_add_feature_specific_agent_permissions_to_role_templates.php
$newAgentPermissions = [
    'timers.act_as_agent',
    'tickets.act_as_agent',
    'time.act_as_agent', 
    'billing.act_as_agent'
];

$roleTemplatesWithAgentPrivileges = [
    'Super Admin', 'Admin', 'Agent'
];
```

## API Reference

### Agent Endpoints

#### Generic Agent Endpoint
```bash
GET /api/users/agents?agent_type={type}&account_id={id}&search={term}
```

**Parameters**:
- `agent_type`: `timer|ticket|time|billing` (default: `timer`)
- `account_id`: Filter by account (optional)
- `search`: Search by name/email (optional)
- `per_page`: Pagination limit (default: 100)

**Response**:
```json
{
  "data": [
    {
      "id": "uuid",
      "name": "Agent Name",
      "email": "agent@example.com",
      "user_type": "agent",
      "account": { "account_type": "internal" },
      "roleTemplate": {
        "permissions": ["timers.act_as_agent", "..."]
      }
    }
  ],
  "message": "Available agents retrieved successfully"
}
```

#### Ticket Assignment Endpoint
```bash
GET /api/users/assignable?account_id={id}&search={term}
```

Uses `tickets.act_as_agent` permission internally for filtering.

#### Billing Agents Endpoint
```bash
GET /api/users/billing-agents?account_id={id}&search={term}
```

Uses `billing.act_as_agent` permission for billing responsibility assignment.

### Permission Requirements

| Endpoint | Required Permission |
|----------|-------------------|
| `/api/users/agents` | `timers.admin`, `time.admin`, `admin.write` |
| `/api/users/assignable` | `tickets.assign`, `admin.write` |
| `/api/users/billing-agents` | `billing.admin`, `billing.manage`, `admin.write` |

## Role Template Configuration

### Built-in Role Templates

The system includes feature-specific agent permissions in standard role templates:

#### Super Admin Role
```json
{
  "name": "Super Admin",
  "permissions": [
    "timers.act_as_agent",
    "tickets.act_as_agent", 
    "time.act_as_agent",
    "billing.act_as_agent",
    "*"
  ]
}
```

#### Admin Role
```json
{
  "name": "Admin", 
  "permissions": [
    "timers.act_as_agent",
    "tickets.act_as_agent",
    "time.act_as_agent", 
    "billing.act_as_agent",
    "admin.manage"
  ]
}
```

#### Agent Role
```json
{
  "name": "Agent",
  "permissions": [
    "timers.act_as_agent",
    "tickets.act_as_agent",
    "time.act_as_agent",
    "billing.act_as_agent",
    "timers.write",
    "tickets.manage",
    "time.track"
  ]
}
```

### Custom Role Configuration

Administrators can create specialized roles with specific agent permissions:

#### Timer Specialist Role
```json
{
  "name": "Timer Specialist",
  "permissions": [
    "timers.act_as_agent",
    "timers.write",
    "timers.manage"
  ]
}
```

#### Billing Manager Role
```json
{
  "name": "Billing Manager", 
  "permissions": [
    "billing.act_as_agent",
    "billing.manage",
    "billing.admin"
  ]
}
```

## Security Considerations

### Multi-Layer Validation

The system implements multiple layers of security:

1. **Permission-based filtering** at the API level
2. **User type validation** for basic agent capability
3. **Account type checking** for internal user fallbacks
4. **Feature-specific permission validation** for granular control

### Access Control

```php
// Example security check in controllers
if (!$user->hasAnyPermission(['timers.admin', 'time.admin', 'admin.write'])) {
    return response()->json([
        'message' => 'Insufficient permissions to view available agents'
    ], 403);
}
```

### Query Optimization

Agent queries are optimized with proper ordering and caching:

```php
// Priority-based ordering for agent selection
$query->orderByRaw("
    CASE 
        WHEN user_type = 'agent' AND account.account_type = 'internal' THEN 1
        WHEN user_type = 'agent' THEN 2
        WHEN has_feature_specific_permission THEN 3
        ELSE 4
    END
")->orderBy('name');
```

## Benefits

### Administrative Control
- **Granular Permissions**: Assign specific agent capabilities per feature
- **Security Enhancement**: Limit agent access to appropriate features only  
- **Flexible Role Design**: Create specialized roles for different workflows
- **Audit Trail**: Track who can act as agents for different features

### Developer Experience
- **Consistent API**: Unified agent endpoint with feature-specific filtering
- **Component Reusability**: Single AgentSelector component for all use cases
- **Type Safety**: Clear agent type specification in components
- **Documentation**: Well-documented permission system

### User Experience  
- **Relevant Options**: Users only see appropriate agents for each context
- **Clear Labeling**: Context-specific language ("Service Agent", "Billing Agent")
- **Visual Indicators**: Agent type badges and priority ordering
- **Predictable Behavior**: Consistent agent selection across features

## Migration Guide

### Upgrading Existing Systems

1. **Run Migration**:
   ```bash
   php artisan migrate
   ```

2. **Update Role Templates**: The migration automatically adds feature-specific permissions to Super Admin, Admin, and Agent roles.

3. **Component Updates**: Existing components will automatically use the new permission system with backward compatibility.

4. **Custom Roles**: Review and update any custom role templates to include appropriate feature-specific agent permissions.

### Backward Compatibility

- **Existing APIs**: All existing endpoints continue to work
- **Default Behavior**: The system defaults to `timer` agent type for backward compatibility
- **Fallback Logic**: Users without specific permissions may still qualify via fallback permissions
- **Graceful Degradation**: Components handle missing permissions gracefully

## Future Enhancements

### Planned Features

1. **Project Agent Permission** (`projects.act_as_agent`)
2. **Report Agent Permission** (`reports.act_as_agent`)  
3. **Account Manager Permission** (`accounts.act_as_agent`)
4. **System Agent Permission** (`system.act_as_agent`)

### Enhanced UI Features

1. **Agent Assignment Dashboard**: Visual interface for managing agent assignments
2. **Permission Matrix**: Grid view of users vs feature permissions
3. **Bulk Permission Updates**: Efficiently update multiple users
4. **Agent Workload Balancing**: Smart assignment based on current workload

---

*For related documentation, see [Agent/Customer Architecture](agent-customer-architecture.md), [Three-Dimensional Permissions](../architecture/three-dimensional-permissions.md), and [API Reference](../api/index.md).*