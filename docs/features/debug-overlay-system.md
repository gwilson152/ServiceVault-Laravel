# Debug Overlay System

Service Vault includes a comprehensive debug overlay system designed exclusively for **Super Admin users** during development and troubleshooting. The system provides real-time visibility into timer state, permissions, and user data through draggable, minimizable overlays.

## Overview

The debug overlay system consists of two specialized overlays that help diagnose and understand system behavior:

1. **Timer Debug Overlay** - Shows timer permissions, computed values, and real-time timer state
2. **Permissions Debug Overlay** - Displays user information, role data, and categorized permissions

## Security & Access Control

### Super Admin Only Access
- **Strict Restriction**: Debug overlays are **only accessible to Super Admin users**
- **Frontend Protection**: Overlays check `user?.is_super_admin` before rendering
- **Settings Protection**: Advanced settings tab is hidden from non-Super Admin users
- **Route Protection**: Direct access to `/settings/advanced` redirects non-Super Admin users

### Permission Requirements
- Advanced settings require `system.configure` permission
- All debug overlay APIs require Super Admin status
- Database settings use `advanced.*` prefix for organization

## Timer Debug Overlay

### Features
- **Permission Diagnostics**: Shows computed permission values and their sources
- **Real-Time Timer State**: Live updates of active timers and their properties
- **Interactive Controls**: Quick access to timer management functions
- **Drag & Drop**: Repositionable overlay with position persistence

### Displayed Information
- Timer creation permissions (`canCreateTimers`)
- Timer control permissions (`canControlTimers`) 
- Timer commit permissions (`canCommitTimers`)
- Active timer count and duration
- Current user context and role information

### Technical Implementation
- Uses `useLocalStorageReactive` composable for settings synchronization
- Broadcasts position changes via custom events
- Integrates with timer broadcasting system for real-time updates

## Permissions Debug Overlay

### Features
- **Expandable Categories**: Click-to-expand permission groups with counts
- **User Overview**: Complete user profile and role template information  
- **Account Context**: Associated account details and relationships
- **Color-Coded Categories**: Visual differentiation of permission types

### Permission Categories

1. **Timers** (Cyan) - `timers.*` permissions
2. **Time** (Purple) - `time.*` permissions  
3. **Admin** (Red) - `admin.*`, `system.*`, `settings.*` permissions
4. **Billing** (Green) - `billing.*`, `invoices.*`, `payments.*` permissions
5. **Tickets** (Blue) - `tickets.*` permissions
6. **Other** (Gray) - All remaining permissions

### User Information Display
- User ID, name, email, type
- Super Admin status
- Account ID and account name
- Role template details
- Permission summary statistics

## Configuration & Settings

### Advanced Settings Interface

Located at **Settings > Advanced** (Super Admin only):

```
Debug & Development Tools
├── Debug Timer Overlay        [Toggle]
├── Debug Permissions Overlay  [Toggle] 
└── Developer Warning Message
```

### Database Storage
- Settings stored with `advanced.*` prefix in settings table
- API endpoints: `GET/PUT /api/settings/advanced`
- Dual storage: Database + localStorage for immediate reactivity
- Success feedback with 3-second auto-hide message

### Reactive Settings
- **Immediate Updates**: Settings changes apply without page refresh
- **Event Broadcasting**: Custom localStorage events notify all components
- **Fallback Support**: localStorage fallback if API requests fail
- **Cross-Tab Sync**: Settings sync across browser tabs/windows

## Technical Architecture

### Composable Integration
```javascript
// Reactive localStorage with event broadcasting
import { useLocalStorageReactive } from '@/Composables/useLocalStorageReactive.js'

const [showDebugOverlay, setShowDebugOverlay] = useLocalStorageReactive('debug_overlay_enabled', false)
```

### Event System
```javascript
// Custom event dispatch for immediate updates
window.dispatchEvent(new CustomEvent('localStorage-changed', {
    detail: { key: 'debug_overlay_enabled', value: newValue }
}))
```

### Position Persistence
- Overlay positions saved to localStorage
- Viewport boundary constraints
- Drag offset calculations for smooth movement

## Usage Guidelines

### When to Enable
- **Development**: Debugging timer functionality and permission issues
- **Troubleshooting**: Diagnosing user access or role assignment problems  
- **System Analysis**: Understanding permission inheritance and computed values
- **Integration Testing**: Verifying API responses and data flow

### Best Practices
- **Enable Selectively**: Only turn on specific overlays as needed
- **Monitor Performance**: Overlays add overhead for real-time updates
- **Document Issues**: Use overlay data when reporting bugs or system behavior
- **Disable in Production**: Never leave enabled on production systems

### Overlay Interaction
- **Dragging**: Click and drag overlay header to reposition
- **Minimizing**: Click minimize button to collapse content
- **Expanding**: Click category headers to show/hide permissions
- **Closing**: Click X button or disable in Settings > Advanced

## API Endpoints

### Advanced Settings API
```bash
# Get current settings
GET /api/settings/advanced

# Update settings  
PUT /api/settings/advanced
{
  "show_debug_overlay": true,
  "show_permissions_debug_overlay": false
}
```

### Response Format
```json
{
  "message": "Advanced settings updated successfully"
}
```

## Implementation Files

### Core Components
- `resources/js/Components/Timer/TimerBroadcastOverlay.vue`
- `resources/js/Components/Debug/PermissionsDebugOverlay.vue`
- `resources/js/Pages/Settings/Components/AdvancedSettings.vue`

### Composables & Utilities
- `resources/js/Composables/useLocalStorageReactive.js`
- `app/Http/Controllers/Api/SettingController.php` (getAdvancedSettings, updateAdvancedSettings)

### Database Schema
```sql
-- Settings table with advanced.* prefix
INSERT INTO settings (key, value, category) VALUES 
('advanced.show_debug_overlay', 'false', 'advanced'),
('advanced.show_permissions_debug_overlay', 'false', 'advanced');
```

## Security Considerations

### Access Control
- All debug functionality restricted to Super Admin users
- No exposure of sensitive data to non-privileged users
- Settings API protected with `system.configure` permission

### Data Exposure
- Debug overlays show internal system state
- Permission lists reveal system capabilities
- User data displays account relationships

### Production Safety
- Debug overlays automatically hidden for non-Super Admin users
- Settings interface only accessible to authorized users
- No debug data logged or transmitted outside the application

## Troubleshooting

### Common Issues

**Overlays not updating after settings change**
- Verify `useLocalStorageReactive` composable is properly imported
- Check browser console for custom event dispatch errors
- Ensure both database and localStorage are being updated

**Permissions not displaying correctly**  
- Verify user has Super Admin status (`is_super_admin: true`)
- Check permission categorization logic in computed properties
- Confirm UserResource is exposing permissions array properly

**Settings not saving**
- Verify API endpoint authorization (`system.configure` permission)
- Check network requests in browser dev tools
- Confirm database connection and settings table structure

**Overlays not showing at all**
- Confirm user is Super Admin (`user?.is_super_admin`)
- Verify localStorage has correct boolean values
- Check component mounting and conditional rendering logic

---

The debug overlay system provides powerful diagnostic capabilities while maintaining strict security controls, ensuring it's only available to authorized Super Admin users for legitimate development and troubleshooting purposes.