# Persistent Timer Overlay System

## Overview

The Service Vault timer overlay uses **Inertia.js persistent layouts** to maintain timer state and WebSocket connections across all page navigation. This ensures a seamless user experience without timer interruptions during page transitions. The overlay features a **dockable interface** with user preference persistence for positioning flexibility.

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
- ✅ **Dockable positioning** with user preference persistence
- ✅ **Wider dialog forms** for improved usability (960px)

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

## Dockable Interface System

### User Preference Persistence

The timer overlay features a dockable interface with database-backed user preferences:

```javascript
// User preference structure
{
  key: 'timer_overlay_docked',
  value: boolean // true = docked to left, false = floating on right
}
```

### Position Management

**Default Position (Undocked):**
- Location: `fixed bottom-4 right-4 z-50`
- Behavior: Traditional floating overlay
- Best for: Primary timer users who want overlay out of the way

**Docked Position:**
- Location: `fixed bottom-4 left-4 z-50` 
- Behavior: Anchored to app menu side
- Best for: Heavy timer users who want quick access

### Dock Toggle Interface

Users can toggle between positions via the dock button:

```vue
<!-- Dock Toggle Button -->
<button
  @click="toggleDockPosition"
  class="bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 px-2 py-1 rounded-lg text-xs font-medium transition-colors flex items-center space-x-1"
  :title="isDocked ? 'Undock (move to bottom-right)' : 'Dock (move to bottom-left)'"
>
  <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
    <path v-if="isDocked" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16l-4-4m0 0l4-4m-4 4h18" />
    <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
  </svg>
  <span>{{ isDocked ? 'Undock' : 'Dock' }}</span>
</button>
```

### Enhanced Form Dimensions

Timer creation and edit forms now use wider dimensions for better usability:

- **Previous Width**: 320px (`w-80`)
- **Current Width**: 960px (`w-240`)
- **Improvement**: 200% wider for comfortable field interaction
- **Responsive**: Maintains horizontal layout with forms on left, timers on right

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

---

*Last Updated: August 14, 2025 - Added Critical Navigation Requirements and Troubleshooting Guide*