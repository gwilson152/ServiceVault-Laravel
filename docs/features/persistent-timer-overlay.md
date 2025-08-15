# Persistent Timer Overlay System

## Overview

The Service Vault timer overlay uses **Inertia.js persistent layouts** to maintain timer state and WebSocket connections across all page navigation. This ensures a seamless user experience without timer interruptions during page transitions. The overlay features a **dual-mode interface** with dockable corner positioning and a fully moveable panel with user preference persistence for maximum positioning flexibility.

## Architecture

### Persistent Layout Implementation

All pages in Service Vault use the persistent layout pattern with `defineOptions`:

```vue
<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'

// Define persistent layout
defineOptions({
  layout: AppLayout
})
</script>

<template>
  <div>
    <!-- Page content here -->
  </div>
</template>
```

### Key Benefits

- ✅ **Timer overlay never unmounts** during navigation
- ✅ **WebSocket connections remain stable** 
- ✅ **No "connecting" messages** between pages
- ✅ **Timer state persists** across all page transitions
- ✅ **Improved performance** - layout doesn't re-render
- ✅ **Dual-mode positioning** with docked corner and moveable panel modes
- ✅ **User preference persistence** for position and dock status
- ✅ **Drag-to-move functionality** for flexible positioning
- ✅ **Compact panel design** optimized for screen space

## Technical Implementation

### AppLayout Structure

The `AppLayout.vue` component contains:

1. **Persistent sidebar navigation**
2. **Top header with user menu**
3. **TimerBroadcastOverlay component** (stays mounted)
4. **Flash message system**
5. **Page content slot**

### Timer Service Singleton

The `TimerBroadcastingService.js` manages:

- **Global WebSocket connection persistence**
- **Component reference counting**
- **Cross-device timer synchronization**
- **Real-time broadcasting integration**

### Page Structure

Pages are structured without layout wrappers:

```vue
<template>
  <div>
    <!-- Page Header -->
    <div class="bg-white shadow-sm border-b border-gray-200 mb-6">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
        <h2 class="font-semibold text-xl text-gray-800">
          Page Title
        </h2>
      </div>
    </div>

    <!-- Page Content -->
    <div class="py-6">
      <!-- Content here -->
    </div>
  </div>
</template>
```

## Dual-Mode Interface System

### User Preference Persistence

The timer overlay features a dual-mode interface with database-backed user preferences:

```javascript
// User preference structure for dock status
{
  key: 'timer_overlay_docked',
  value: boolean // true = docked corner mode, false = moveable panel mode
}

// User preference structure for panel position
{
  key: 'timer_overlay_position',
  value: {
    x: number, // X coordinate in pixels
    y: number  // Y coordinate in pixels
  }
}
```

### Interface Modes

**Docked Mode (Default):**
- Location: `fixed bottom-4 right-4 z-50`
- Behavior: Traditional corner overlay with horizontal timer cards
- Layout: Forms on left, timer badges on right
- Best for: Users who prefer the familiar corner interface

**Undocked Mode (Moveable Panel):**
- Location: User-defined position via drag and drop
- Behavior: Compact moveable panel with unified timer list
- Layout: Vertical list format with compact timer rows
- Features: 
  - Drag-to-move functionality
  - Compact design (min-w-72 max-w-80)
  - Persistent position storage
  - Integrated forms within panel
- Best for: Users who want flexible positioning and compact timer management

### Mode Toggle Interface

Users can toggle between docked and undocked modes via the dock/undock button:

**In Docked Mode:**
```vue
<!-- Undock Button (in docked mode) -->
<button
  @click="toggleDockPosition"
  :title="'Undock to moveable panel'"
>
  <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
  </svg>
  <span>Undock</span>
</button>
```

**In Undocked Mode:**
```vue
<!-- Dock Button (in undocked panel header) -->
<button
  @click="toggleDockPosition"
  title="Dock to corner"
>
  <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
  </svg>
</button>
```

### Drag-to-Move Functionality

The undocked panel features full drag-and-drop positioning:

**Drag Behavior:**
- Drag by clicking anywhere on the panel header or non-interactive areas
- Automatic boundary constraints to keep panel within viewport
- Real-time position updates with smooth movement
- Position automatically saved to user preferences

**Interactive Elements Protection:**
- Buttons, inputs, selects, textareas, and contenteditable elements don't trigger drag
- Forms remain fully functional within the moveable panel
- Click-through behavior preserved for all controls

**Implementation:**
```javascript
// Enhanced drag prevention for form elements
const startPanelDrag = (e) => {
  if (isDocked.value || 
      e.target.closest('button') || 
      e.target.closest('input') || 
      e.target.closest('select') || 
      e.target.closest('textarea') || 
      e.target.closest('[contenteditable]')) {
    return // Don't drag when clicking interactive elements
  }
  // ... drag logic
}
```

## TanStack Query Integration

Timer data is managed using TanStack Query for:

- **Optimistic updates** during timer actions
- **Automatic cache invalidation** on WebSocket events
- **Background synchronization** with server state
- **Efficient query caching** across navigation

## WebSocket Event Handling

Timer events are handled globally:

```javascript
// TimerBroadcastingService.js
this.timerChannel
  .listen('TimerStarted', (e) => {
    this.timerQueries?.addOrUpdateTimer(e.timer)
  })
  .listen('TimerStopped', (e) => {
    this.timerQueries?.removeTimer(e.timer.id)
  })
```

## Migration from Component Wrappers

The conversion from component wrappers to persistent layouts involved:

1. **Removing `<AppLayout>` wrapper tags** from all pages
2. **Adding `defineOptions({ layout: AppLayout })`** to script setup
3. **Converting header slots** to inline page headers
4. **Fixing template syntax** and div tag matching
5. **Testing build compilation** for all 15+ pages

## Performance Impact

### Before (Component Wrappers)
- ❌ Layout re-rendered on every navigation
- ❌ Timer overlay remounted/unmounted
- ❌ WebSocket reconnections
- ❌ "Connecting" messages visible

### After (Persistent Layouts)
- ✅ Layout persists across navigation
- ✅ Timer overlay stays mounted
- ✅ WebSocket connections stable
- ✅ Seamless user experience

## Browser Support

Persistent layouts work in all modern browsers:

- ✅ Chrome/Edge 90+
- ✅ Firefox 88+  
- ✅ Safari 14+
- ✅ Mobile browsers

## Development Notes

### Adding New Pages

When creating new pages, use the persistent layout pattern:

```vue
<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'

defineOptions({
  layout: AppLayout
})

// Page logic here
</script>

<template>
  <div>
    <!-- Page header -->
    <div class="bg-white shadow-sm border-b border-gray-200 mb-6">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
        <h2 class="font-semibold text-xl text-gray-800">
          New Page Title
        </h2>
      </div>
    </div>
    
    <!-- Page content -->
    <div class="py-6">
      <!-- Content here -->
    </div>
  </div>
</template>
```

### Important Considerations

1. **No named slots** - Persistent layouts don't support slots like `#header`
2. **Page headers inline** - Each page manages its own header content
3. **Direct page props** - No layout props, everything comes from page data
4. **Template structure** - Must have single root `<div>` wrapper
5. **Navigation patterns** - CRITICAL: Always use Inertia.js navigation to maintain persistence

## Critical Navigation Requirements

### ✅ CORRECT Navigation Patterns

**Use Inertia.js `<Link>` components for declarative navigation:**
```vue
<Link :href="route('tickets.show', ticket.id)" class="text-blue-600 hover:text-blue-700">
  View Ticket
</Link>
```

**Use Inertia.js `router.visit()` for programmatic navigation:**
```javascript
import { router } from '@inertiajs/vue3'

const navigateToTicket = (ticketId) => {
  router.visit(`/tickets/${ticketId}`)
}
```

### ❌ INCORRECT Navigation Patterns (Will Break Persistence)

**Never use `window.location.href` - causes full page reloads:**
```javascript
// ❌ WRONG - This bypasses Inertia and breaks timer overlay persistence
const navigateToTicket = (ticketId) => {
  window.location.href = `/tickets/${ticketId}`
}
```

**Never use regular HTML `<a>` tags - causes full page reloads:**
```vue
<!-- ❌ WRONG - This bypasses Inertia and breaks timer overlay persistence -->
<a :href="`/tickets/${ticket.id}`">View Ticket</a>
```

### Navigation Troubleshooting

If the timer overlay reloads or the layout re-renders during navigation, check for:

1. **`window.location.href` usage** - Replace with `router.visit()`
2. **HTML `<a>` tags** - Replace with Inertia `<Link>` components
3. **External links without `target="_blank"`** - Add target attribute for external URLs
4. **Form submissions without Inertia** - Use Inertia form helpers

### Real-World Fix Example

**Problem found in MyTicketsWidget.vue:**
```javascript
// ❌ This was causing full page reloads
const navigateToTicket = (ticketId) => {
  window.location.href = `/tickets/${ticketId}`
}
```

**Solution applied:**
```javascript
// ✅ This maintains SPA navigation and persistent overlay
import { router } from '@inertiajs/vue3'

const navigateToTicket = (ticketId) => {
  router.visit(`/tickets/${ticketId}`)
}
```

**Result:** Timer overlay now persists perfectly across all navigation.

## Related Documentation

- [Timer System Architecture](../architecture/timer-system.md)
- [Multi-Timer Management](time-tracking.md)
- [Real-Time Broadcasting](../architecture/real-time-broadcasting.md)
- [TanStack Query Integration](../development/tanstack-query.md)

## User Preference API

The dual-mode interface integrates with the user preferences system:

### API Endpoints

```bash
# Get user's dock status preference
GET /api/user-preferences/timer_overlay_docked

# Get user's panel position preference
GET /api/user-preferences/timer_overlay_position

# Set dock status preference  
POST /api/user-preferences
{
  "key": "timer_overlay_docked",
  "value": true|false
}

# Set panel position preference
POST /api/user-preferences
{
  "key": "timer_overlay_position",
  "value": {
    "x": 100,
    "y": 100
  }
}

# Update existing preferences
PUT /api/user-preferences/timer_overlay_docked
{
  "value": true|false
}

PUT /api/user-preferences/timer_overlay_position
{
  "value": {
    "x": 250,
    "y": 150
  }
}
```

### Database Structure

```sql
-- User preferences table
CREATE TABLE user_preferences (
    id BIGINT PRIMARY KEY,
    user_id UUID REFERENCES users(id) ON DELETE CASCADE,
    key VARCHAR(100) NOT NULL,
    value JSON NOT NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    UNIQUE(user_id, key)
);
```

### Implementation Example

```javascript
// Reactive state for both preferences
const isDocked = ref(true) // Default to docked mode
const panelPosition = reactive({ x: 100, y: 100 }) // Default panel position

// Load user preferences on component mount
const loadUserPreferences = async () => {
  if (!user.value?.id) return
  
  try {
    // Load dock status preference
    const dockResponse = await window.axios.get('/api/user-preferences/timer_overlay_docked')
    isDocked.value = dockResponse.data?.data?.value !== false // Default to docked (true)
    
    // Load panel position preference
    try {
      const positionResponse = await window.axios.get('/api/user-preferences/timer_overlay_position')
      if (positionResponse.data?.data?.value) {
        Object.assign(panelPosition, positionResponse.data.data.value)
      }
    } catch (posError) {
      // Keep default position if preference doesn't exist
    }
  } catch (error) {
    // Default to docked if preference doesn't exist
    isDocked.value = true
  }
}

// Save dock status when user toggles mode
const toggleDockPosition = async () => {
  const newPosition = !isDocked.value
  isDocked.value = newPosition
  
  await window.axios.post('/api/user-preferences', {
    key: 'timer_overlay_docked',
    value: newPosition
  })
}

// Save panel position when drag ends
const savePanelPosition = async () => {
  if (!user.value?.id) return
  
  try {
    await window.axios.post('/api/user-preferences', {
      key: 'timer_overlay_position',
      value: {
        x: panelPosition.x,
        y: panelPosition.y
      }
    })
  } catch (error) {
    console.error('Failed to save panel position preference:', error)
  }
}
```

---

*Last Updated: August 15, 2025 - Enhanced with Dual-Mode Interface: Dockable Corner and Moveable Panel with Drag-to-Move Functionality*