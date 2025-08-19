import { ref, computed, watch } from 'vue'

export function useColumnVisibility(tableId = 'default', user = null, defaultVisibility = {}) {
  const storageKey = `table_column_visibility_${tableId}_${user?.id || 'anonymous'}`
  
  // Load saved column visibility from localStorage
  const loadVisibility = () => {
    try {
      const saved = localStorage.getItem(storageKey)
      return saved ? JSON.parse(saved) : defaultVisibility
    } catch (e) {
      return defaultVisibility
    }
  }

  const columnVisibility = ref(loadVisibility())

  // Save column visibility to localStorage
  const saveVisibility = () => {
    try {
      localStorage.setItem(storageKey, JSON.stringify(columnVisibility.value))
    } catch (e) {
      console.warn('Failed to save column visibility:', e)
    }
  }

  // Watch for changes and save
  watch(columnVisibility, saveVisibility, { deep: true })

  const toggleColumn = (columnId) => {
    columnVisibility.value = {
      ...columnVisibility.value,
      [columnId]: !columnVisibility.value[columnId]
    }
  }

  const showColumn = (columnId) => {
    columnVisibility.value = {
      ...columnVisibility.value,
      [columnId]: true
    }
  }

  const hideColumn = (columnId) => {
    columnVisibility.value = {
      ...columnVisibility.value,
      [columnId]: false
    }
  }

  const resetVisibility = () => {
    columnVisibility.value = defaultVisibility
    localStorage.removeItem(storageKey)
  }

  const isColumnVisible = computed(() => (columnId) => {
    // If explicit setting exists, use it
    if (columnVisibility.value.hasOwnProperty(columnId)) {
      return columnVisibility.value[columnId] !== false
    }
    // Otherwise, check default visibility
    if (defaultVisibility.hasOwnProperty(columnId)) {
      return defaultVisibility[columnId] !== false
    }
    // Default to visible
    return true
  })

  return {
    columnVisibility,
    toggleColumn,
    showColumn,
    hideColumn,
    resetVisibility,
    isColumnVisible
  }
}