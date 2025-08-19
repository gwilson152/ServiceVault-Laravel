# Troubleshooting Guide

Common issues and solutions for Service Vault platform.

## Timer System Issues

### "No Agents Available" in Time Entry Dialog

**Problem**: Agent dropdown shows "No agents available" when trying to create time entries or commit timers.

**Common Causes & Solutions**:

1. **Permission Detection Issue**:
   ```javascript
   // Check if user has proper permissions in frontend
   console.log('User object:', user.value);
   console.log('Is Super Admin:', user.value?.is_super_admin);
   console.log('Permissions:', user.value?.permissions);
   ```

2. **Account Filtering Issue**:
   - Time entry agents should load without account filtering
   - Check if `loadAgentsForAccount()` is applying unnecessary filters
   - Verify agents exist: call `/api/users/agents?agent_type=time` directly

3. **Role Template Missing Permissions**:
   ```php
   // Check if role templates have required permissions
   php artisan tinker
   $role = RoleTemplate::where('name', 'Agent')->first();
   dd($role->permissions);
   ```
   
   Required permissions: `time.act_as_agent`, `time.assign`, `time.manage`, or `admin.write`

4. **Super Admin Permission Inheritance**:
   - Super Admin should automatically have all permissions
   - Check `User::hasPermission()` method and `RoleTemplate::isSuperAdmin()`
   - Verify `PermissionService::filterUsersByPermissions()` handles Super Admin correctly

**Quick Fix**: Ensure `canAssignToOthers` computed property includes Super Admin check:
```javascript
const canAssignToOthers = computed(() => {
    if (user.value?.is_super_admin || user.value?.roleTemplate?.name === "Super Admin") {
        return true;
    }
    // ... other permission checks
});
```

### Timer Overlay Missing or Not Persisting

**Problem**: Timer overlay disappears when navigating between pages.

**Cause**: Using HTML `<a>` tags instead of Inertia.js navigation.

**Solution**:
```vue
<!-- ✅ CORRECT -->
<Link :href="/tickets">View Tickets</Link>

<!-- ❌ WRONG -->
<a href="/tickets">View Tickets</a>
```

### Timer Data Not Saving

**Problem**: Timer creation or updates fail silently or with validation errors.

**Common Issues**:
1. **Missing Required Fields**: Check `StoreTimerRequest` and `UpdateTimerRequest` validation rules
2. **Account/Ticket Association**: Ensure `account_id` and `ticket_id` are properly populated
3. **User Assignment**: Verify `user_id` field is set correctly

**Debug Steps**:
```php
// Check validation errors in browser network tab
// Verify database columns match request fields
// Check model fillable arrays
```

### Cross-Device Timer Sync Issues

**Problem**: Timers not syncing between devices/browsers.

**Common Causes**:
1. **Redis Connection**: Verify Redis is running and accessible
2. **WebSocket Server**: Ensure Laravel Reverb is running (`php artisan reverb:start`)
3. **Browser Cache**: Clear localStorage and browser cache
4. **Network Issues**: Check WebSocket connection in browser console

## Permission System Issues

### User Cannot See Admin Features

**Problem**: Users with admin roles cannot access admin functions.

**Troubleshooting**:
1. **Check Role Assignment**:
   ```php
   $user = User::with('roleTemplate')->find($userId);
   echo "Role: " . $user->roleTemplate->name;
   ```

2. **Verify Permissions**:
   ```php
   $hasPermission = $user->hasPermission('admin.write');
   echo "Has admin.write: " . ($hasPermission ? 'Yes' : 'No');
   ```

3. **Check Frontend Auth Object**:
   ```javascript
   console.log('Auth user:', page.props.auth?.user);
   console.log('Role template:', page.props.auth?.user?.roleTemplate);
   ```

### Permission Changes Not Taking Effect

**Problem**: Permission updates don't immediately affect user access.

**Solutions**:
1. **Clear Caches**: `php artisan cache:clear`
2. **Restart WebSocket**: Restart Laravel Reverb server
3. **Browser Refresh**: Hard refresh browser to reload auth state
4. **Session Regeneration**: Re-login to get updated permissions

## Database Issues

### Migration Failures

**Problem**: Migrations fail with duplicate column or constraint errors.

**Common Solutions**:
1. **Check Existing Schema**: Migrations should include existence checks
2. **Reset Development Database**: `php artisan migrate:fresh --seed`
3. **Manual Migration**: Skip problematic migrations and apply manually

### N+1 Query Problems

**Problem**: Performance issues with excessive database queries.

**Monitoring**:
```php
// Enable query logging in development
DB::enableQueryLog();
// ... perform operations
dd(DB::getQueryLog());
```

**Solutions**:
- Use eager loading: `User::with(['roleTemplate', 'account'])`
- Implement proper API resource structure
- Use query optimization in large datasets

## Frontend Issues

### Inertia.js Navigation Problems

**Problem**: Page navigation breaks application state.

**Common Causes**:
1. **Mixed Navigation**: Using both Inertia and regular links
2. **CSRF Token Issues**: Expired or invalid CSRF tokens
3. **Server Errors**: Backend returning 500 errors

**Solutions**:
```javascript
// Consistent Inertia navigation
import { router } from '@inertiajs/vue3';
router.visit('/path');

// Handle CSRF refresh
window.refreshCSRFToken();
```

### Vue Component State Issues

**Problem**: Components not updating or showing stale data.

**Debug Steps**:
1. **Check Reactivity**: Ensure data is properly reactive
2. **Component Keys**: Use keys for list items that may change
3. **Prop Updates**: Verify parent components pass updated props

## API Issues

### Authentication Failures

**Problem**: API endpoints returning 401 Unauthorized.

**Troubleshooting**:
1. **Session vs Token**: Verify correct authentication method
2. **CSRF Protection**: Include CSRF token for session-based requests
3. **Token Scope**: Check API token has required abilities

### Permission Denied Errors

**Problem**: API returning 403 Forbidden for authorized users.

**Debug Process**:
1. **Check Endpoint Permissions**: Verify required permissions in controller
2. **Test Permission Service**: Use `PermissionService` methods directly
3. **Verify User Context**: Ensure correct user is being evaluated

---

For additional help, check the specific component documentation or contact the development team.