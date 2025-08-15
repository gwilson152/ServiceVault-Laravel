import { ref, onMounted, onUnmounted } from 'vue'

/**
 * Reactive localStorage composable that updates when localStorage changes
 */
export function useLocalStorageReactive(key, defaultValue = null) {
  const value = ref(getStorageValue(key, defaultValue))
  
  function getStorageValue(key, defaultValue) {
    try {
      const item = localStorage.getItem(key)
      if (item === null) return defaultValue
      if (item === 'true') return true
      if (item === 'false') return false
      return item
    } catch (error) {
      console.warn(`Error reading localStorage key "${key}":`, error)
      return defaultValue
    }
  }
  
  function setValue(newValue) {
    try {
      value.value = newValue
      localStorage.setItem(key, String(newValue))
      // Dispatch custom event to notify other components
      window.dispatchEvent(new CustomEvent('localStorage-changed', {
        detail: { key, value: newValue }
      }))
    } catch (error) {
      console.warn(`Error setting localStorage key "${key}":`, error)
    }
  }
  
  function handleStorageChange(event) {
    if (event.detail?.key === key) {
      value.value = event.detail.value
    }
  }
  
  onMounted(() => {
    // Listen for custom localStorage change events
    window.addEventListener('localStorage-changed', handleStorageChange)
    
    // Also listen for storage events from other tabs/windows
    window.addEventListener('storage', (e) => {
      if (e.key === key) {
        value.value = getStorageValue(key, defaultValue)
      }
    })
  })
  
  onUnmounted(() => {
    window.removeEventListener('localStorage-changed', handleStorageChange)
    window.removeEventListener('storage', handleStorageChange)
  })
  
  return [value, setValue]
}