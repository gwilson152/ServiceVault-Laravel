# CSRF Security Enhancements

Service Vault implements comprehensive CSRF (Cross-Site Request Forgery) protection with enhanced token management and error handling for secure API operations.

## Overview

Recent enhancements to the CSRF protection system address token expiration issues and provide robust error handling for API operations, particularly for account and user creation workflows through selector components.

## Enhanced CSRF Token Management

### Proactive Token Refresh

Components that perform sensitive operations now proactively refresh CSRF tokens before API calls:

```javascript
// AccountFormModal.vue - Account creation
const saveAccount = async () => {
    try {
        // Ensure CSRF token is fresh before making the request
        await window.axios.get('/sanctum/csrf-cookie')
        
        if (isEditing.value) {
            const result = await updateMutation.mutateAsync({
                id: props.account.id,
                data: form.value
            })
        } else {
            const result = await createMutation.mutateAsync(form.value)
        }
        
        emit('close')
    } catch (error) {
        // Enhanced error handling for CSRF issues
        if (error.response?.status === 419) {
            errors.value = { general: ['CSRF token mismatch. Please refresh the page and try again.'] }
        }
    }
}
```

### Modal Integration CSRF Protection

Selector components with creation functionality initialize CSRF tokens before opening modals:

```javascript
// HierarchicalAccountSelector.vue
const openCreateModal = async () => {
    showDropdown.value = false
    
    // Ensure CSRF token is ready before opening modal
    try {
        await window.axios.get('/sanctum/csrf-cookie')
    } catch (error) {
        console.error('Failed to initialize CSRF token:', error)
    }
    
    showCreateAccountModal.value = true
}
```

## API Configuration Enhancements

### Improved Request Interceptor

The axios instance includes comprehensive CSRF token handling:

```javascript
// /resources/js/Services/api.js
api.interceptors.request.use((config) => {
  // Get CSRF token from meta tag (for traditional Laravel forms)
  const token = document.head.querySelector('meta[name="csrf-token"]')
  if (token) {
    config.headers['X-CSRF-TOKEN'] = token.content
  }
  
  // Get XSRF token from cookies (for Sanctum)
  const xsrfToken = document.cookie
    .split('; ')
    .find(row => row.startsWith('XSRF-TOKEN='))
  if (xsrfToken) {
    config.headers['X-XSRF-TOKEN'] = decodeURIComponent(xsrfToken.split('=')[1])
  }
  
  return config
}, (error) => {
  return Promise.reject(error)
})
```

### Enhanced Response Interceptor

Specific handling for CSRF-related errors:

```javascript
api.interceptors.response.use(
  response => response,
  error => {
    if (error.response && error.response.status === 401) {
      window.location.href = '/login'
    } else if (error.response && error.response.status === 419) {
      console.error('CSRF token mismatch - token may have expired')
    }
    return Promise.reject(error)
  }
)
```

### Robust CSRF Initialization

Enhanced initialization function with better error handling:

```javascript
export const initializeCSRF = async () => {
  try {
    await axios.get('/sanctum/csrf-cookie', {
      withCredentials: true
    })
  } catch (error) {
    console.error('Failed to initialize CSRF cookie:', error)
    throw error
  }
}
```

## Error Handling Strategy

### User-Friendly Error Messages

Components provide clear guidance when CSRF errors occur:

```javascript
catch (error) {
    console.error('Account save error:', error)
    if (error.response?.data?.errors) {
        errors.value = error.response.data.errors
    } else if (error.response?.status === 419) {
        errors.value = { general: ['CSRF token mismatch. Please refresh the page and try again.'] }
    } else {
        errors.value = { general: ['An error occurred while saving the account.'] }
    }
}
```

### Progressive Error Resolution

1. **First Attempt**: Automatic CSRF token refresh before sensitive operations
2. **Error Detection**: Specific 419 status code handling
3. **User Guidance**: Clear messaging with actionable steps
4. **Fallback**: Page refresh recommendation for token reset

## Components with Enhanced CSRF Protection

### HierarchicalAccountSelector
- Proactive CSRF initialization before opening creation modal
- Enhanced error handling in account creation workflow
- Automatic token refresh on creation attempts

### UserSelector (UserFormModal)
- Similar CSRF protection patterns for user creation
- Token validation before modal operations
- Comprehensive error handling and user feedback

### AccountFormModal
- Direct CSRF token refresh before save operations
- Enhanced error messaging for 419 status codes
- Robust error recovery mechanisms

## Security Benefits

### Enhanced Protection
- **Token Freshness**: Ensures CSRF tokens are current before sensitive operations
- **Proactive Refresh**: Prevents 419 errors through proactive token management
- **Error Recovery**: Graceful handling of token expiration scenarios

### User Experience
- **Seamless Operations**: Reduced CSRF-related failures during normal usage
- **Clear Feedback**: User-friendly error messages with actionable guidance
- **Automatic Recovery**: Minimal user intervention required for token issues

### Developer Experience
- **Consistent Patterns**: Standardized CSRF handling across components
- **Debug Information**: Enhanced logging for CSRF-related issues
- **Reliable Operations**: Reduced debugging time for token-related problems

## Implementation Patterns

### For New Components

When creating components with sensitive operations:

1. **Initialize CSRF before modals**:
```javascript
const openModal = async () => {
    try {
        await window.axios.get('/sanctum/csrf-cookie')
    } catch (error) {
        console.error('CSRF initialization failed:', error)
    }
    showModal.value = true
}
```

2. **Refresh tokens before API calls**:
```javascript
const performSensitiveOperation = async () => {
    try {
        await window.axios.get('/sanctum/csrf-cookie')
        // Proceed with operation
    } catch (error) {
        // Handle CSRF errors appropriately
    }
}
```

3. **Provide clear error feedback**:
```javascript
if (error.response?.status === 419) {
    errors.value = { 
        general: ['CSRF token mismatch. Please refresh the page and try again.'] 
    }
}
```

## Laravel Backend Configuration

Ensure Laravel Sanctum is properly configured for CSRF protection:

```php
// config/sanctum.php
'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', sprintf(
    '%s%s',
    'localhost,localhost:3000,127.0.0.1,127.0.0.1:8000,::1',
    env('APP_URL') ? ','.parse_url(env('APP_URL'), PHP_URL_HOST) : ''
))),
```

## Testing CSRF Protection

### Manual Testing
1. **Token Expiration**: Test with expired CSRF tokens
2. **Modal Workflows**: Verify account/user creation from selectors
3. **Error Recovery**: Confirm user-friendly error messages
4. **Page Refresh**: Test recovery after page refresh

### Automated Testing
```javascript
// Test CSRF token handling
describe('CSRF Protection', () => {
  it('should refresh token before sensitive operations', async () => {
    // Mock expired token scenario
    // Verify automatic refresh
    // Confirm successful operation
  })
  
  it('should provide user-friendly 419 error messages', async () => {
    // Mock 419 response
    // Verify error message content
    // Confirm user guidance
  })
})
```

## Monitoring and Maintenance

### CSRF Error Monitoring
- Track 419 error rates in application logs
- Monitor CSRF token refresh frequency
- Alert on unusual CSRF failure patterns

### Performance Considerations
- CSRF refresh adds minimal latency to sensitive operations
- Token caching reduces redundant requests
- Proactive refresh improves success rates

---

**CSRF Security Enhancements** provide robust protection against token expiration issues while maintaining excellent user experience across Service Vault's interactive components.

_Last Updated: August 14, 2025 - Initial implementation of enhanced CSRF protection with proactive token management_