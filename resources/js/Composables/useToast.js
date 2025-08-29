import { useToastStore } from '@/Stores/toastStore'

/**
 * Toast composable for easy toast management across the app
 * 
 * Usage:
 * const { toast, success, error, warning, info, loading, promise } = useToast()
 * 
 * // Basic usage
 * success('Operation completed successfully!')
 * error('Something went wrong')
 * 
 * // Advanced usage with options
 * toast.add({
 *   type: 'info',
 *   title: 'New Message',
 *   message: 'You have a new notification',
 *   actionText: 'View',
 *   onAction: () => navigateToMessages(),
 *   position: 'bottom-right',
 *   duration: 10000
 * })
 * 
 * // Promise handling
 * await promise(
 *   fetch('/api/data'),
 *   {
 *     loading: 'Fetching data...',
 *     success: 'Data loaded successfully!',
 *     error: 'Failed to load data'
 *   }
 * )
 */
export function useToast() {
  const toastStore = useToastStore()

  // Core toast object with all methods
  const toast = {
    // Main methods
    add: toastStore.add,
    remove: toastStore.remove,
    clear: toastStore.clear,
    update: toastStore.update,
    
    // Convenience methods
    success: toastStore.success,
    error: toastStore.error,
    warning: toastStore.warning,
    info: toastStore.info,
    loading: toastStore.loading,
    promise: toastStore.promise,

    // State
    toasts: toastStore.toasts,
    activeToasts: toastStore.activeToasts,
    count: toastStore.toastCount
  }

  // Individual convenience methods for destructuring
  const success = (message, options = {}) => {
    return toastStore.success(message, options)
  }

  const error = (message, options = {}) => {
    return toastStore.error(message, options)
  }

  const warning = (message, options = {}) => {
    return toastStore.warning(message, options)
  }

  const info = (message, options = {}) => {
    return toastStore.info(message, options)
  }

  const loading = (message = 'Loading...', options = {}) => {
    return toastStore.loading(message, options)
  }

  const promise = async (promiseToTrack, options = {}) => {
    return toastStore.promise(promiseToTrack, options)
  }

  // Advanced convenience methods
  const confirmAction = (message, actionText = 'Confirm', options = {}) => {
    return new Promise((resolve) => {
      toast.add({
        type: 'warning',
        message,
        actionText,
        persistent: true,
        onAction: () => resolve(true),
        ...options
      })
    })
  }

  const apiError = (error, fallbackMessage = 'An unexpected error occurred') => {
    let message = fallbackMessage
    
    if (error?.response?.data?.message) {
      message = error.response.data.message
    } else if (error?.message) {
      message = error.message
    }
    
    return toast.error(message, {
      title: 'API Error',
      duration: 8000
    })
  }

  const validationErrors = (errors) => {
    const errorMessages = []
    
    if (typeof errors === 'object') {
      Object.keys(errors).forEach(field => {
        const fieldErrors = Array.isArray(errors[field]) ? errors[field] : [errors[field]]
        errorMessages.push(...fieldErrors)
      })
    }
    
    if (errorMessages.length > 0) {
      return toast.error(errorMessages.join('. '), {
        title: 'Validation Error',
        duration: 10000
      })
    }
  }

  const networkError = () => {
    return toast.error('Network error. Please check your connection and try again.', {
      title: 'Connection Error',
      duration: 8000
    })
  }

  const unauthorized = () => {
    return toast.error('Your session has expired. Please log in again.', {
      title: 'Session Expired',
      persistent: true
    })
  }

  // Batch operations
  const clearAll = () => {
    toast.clear()
  }

  const clearByType = (type) => {
    toastStore.toasts.forEach(t => {
      if (t.type === type) {
        toast.remove(t.id)
      }
    })
  }

  const clearByPosition = (position) => {
    toast.clear(position)
  }

  // Quick notifications for common operations
  const saved = (itemName = 'Item') => {
    return success(`${itemName} saved successfully`)
  }

  const deleted = (itemName = 'Item') => {
    return success(`${itemName} deleted successfully`)
  }

  const updated = (itemName = 'Item') => {
    return success(`${itemName} updated successfully`)
  }

  const copied = (itemName = 'Content') => {
    return success(`${itemName} copied to clipboard`)
  }

  return {
    // Core toast object
    toast,
    
    // Individual methods for destructuring
    success,
    error,
    warning,
    info,
    loading,
    promise,
    
    // Advanced methods
    confirmAction,
    apiError,
    validationErrors,
    networkError,
    unauthorized,
    
    // Batch operations
    clearAll,
    clearByType,
    clearByPosition,
    
    // Quick notifications
    saved,
    deleted,
    updated,
    copied
  }
}