import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

export const useToastStore = defineStore('toast', () => {
  // State
  const toasts = ref([])
  let idCounter = 0

  // Getters
  const activeToasts = computed(() => 
    toasts.value.filter(toast => toast.visible !== false)
  )

  const toastCount = computed(() => toasts.value.length)

  // Actions
  const add = (options) => {
    const id = options.id || ++idCounter
    
    // Default configuration
    const toast = {
      id,
      type: 'info',
      title: null,
      message: '',
      duration: 5000,
      persistent: false,
      closeable: true,
      showIcon: true,
      position: 'top-right',
      actionText: null,
      onAction: null,
      visible: true,
      createdAt: Date.now(),
      ...options
    }

    // Remove duplicate toast with same id if it exists
    if (options.id) {
      const existingIndex = toasts.value.findIndex(t => t.id === id)
      if (existingIndex !== -1) {
        toasts.value.splice(existingIndex, 1)
      }
    }

    toasts.value.push(toast)

    // Auto-remove after duration (if not persistent)
    if (!toast.persistent && toast.duration > 0) {
      setTimeout(() => {
        remove(id)
      }, toast.duration)
    }

    return id
  }

  const remove = (id) => {
    const index = toasts.value.findIndex(toast => toast.id === id)
    if (index !== -1) {
      toasts.value.splice(index, 1)
    }
  }

  const clear = (position = null) => {
    if (position) {
      toasts.value = toasts.value.filter(toast => toast.position !== position)
    } else {
      toasts.value = []
    }
  }

  const update = (id, updates) => {
    const toast = toasts.value.find(t => t.id === id)
    if (toast) {
      Object.assign(toast, updates)
    }
  }

  // Convenience methods for different toast types
  const success = (message, options = {}) => {
    return add({
      type: 'success',
      message,
      ...options
    })
  }

  const error = (message, options = {}) => {
    return add({
      type: 'error',
      message,
      duration: options.duration || 8000, // Longer duration for errors
      ...options
    })
  }

  const warning = (message, options = {}) => {
    return add({
      type: 'warning',
      message,
      ...options
    })
  }

  const info = (message, options = {}) => {
    return add({
      type: 'info',
      message,
      ...options
    })
  }

  // Advanced methods
  const promise = async (promise, options = {}) => {
    const loadingId = add({
      type: 'info',
      message: options.loading || 'Loading...',
      persistent: true,
      closeable: false,
      showIcon: true,
      ...options.loadingOptions
    })

    try {
      const result = await promise
      remove(loadingId)
      
      if (options.success) {
        success(
          typeof options.success === 'function' 
            ? options.success(result) 
            : options.success,
          options.successOptions
        )
      }
      
      return result
    } catch (error) {
      remove(loadingId)
      
      if (options.error) {
        const errorMessage = typeof options.error === 'function' 
          ? options.error(error) 
          : options.error
        
        add({
          type: 'error',
          message: errorMessage,
          duration: 8000,
          ...options.errorOptions
        })
      }
      
      throw error
    }
  }

  const loading = (message = 'Loading...', options = {}) => {
    return add({
      type: 'info',
      message,
      persistent: true,
      closeable: false,
      showIcon: true,
      ...options
    })
  }

  return {
    // State
    toasts,
    
    // Getters
    activeToasts,
    toastCount,
    
    // Actions
    add,
    remove,
    clear,
    update,
    
    // Convenience methods
    success,
    error,
    warning,
    info,
    promise,
    loading
  }
})