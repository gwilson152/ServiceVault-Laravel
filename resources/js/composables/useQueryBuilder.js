import { ref, computed, nextTick, readonly } from 'vue'

export function useQueryBuilder(initialConfig = {}) {
  // Core state - these are the single sources of truth
  const baseTable = ref(initialConfig.base_table || '')
  const joins = ref([...( initialConfig.joins || [])])
  const fields = ref([...( initialConfig.fields || [])])
  const filters = ref([...( initialConfig.filters || [])])
  const targetType = ref(initialConfig.target_type || 'customer_users')
  
  // Prevent recursive updates
  const isUpdating = ref(false)
  
  // Computed full query configuration
  const queryConfig = computed(() => ({
    base_table: baseTable.value,
    joins: joins.value,
    fields: fields.value,
    filters: filters.value,
    target_type: targetType.value
  }))
  
  // Computed query validation
  const isValidQuery = computed(() => {
    return !!(
      baseTable.value && 
      fields.value.length > 0
    )
  })
  
  // Controlled mutations - these prevent cascading reactive loops
  const setBaseTable = async (table) => {
    if (isUpdating.value) return
    
    isUpdating.value = true
    
    try {
      // Only update if different
      if (baseTable.value !== table?.name) {
        baseTable.value = table?.name || ''
        
        // Clear dependent data when base table changes
        joins.value = []
        fields.value = []
        // Keep filters - they might still be relevant
      }
    } finally {
      await nextTick()
      isUpdating.value = false
    }
  }
  
  const setJoins = async (newJoins) => {
    if (isUpdating.value) return
    
    isUpdating.value = true
    
    try {
      joins.value = [...newJoins]
      
      // When joins change, clear fields that reference non-existent tables
      fields.value = fields.value.filter(field => {
        if (!field.source) return true
        
        const [tableName] = field.source.split('.')
        if (tableName === baseTable.value) return true
        
        return newJoins.some(join => join.table === tableName)
      })
    } finally {
      await nextTick()
      isUpdating.value = false
    }
  }
  
  const setFields = async (newFields) => {
    if (isUpdating.value) return
    
    isUpdating.value = true
    
    try {
      fields.value = [...newFields]
    } finally {
      await nextTick()
      isUpdating.value = false
    }
  }
  
  const setFilters = async (newFilters) => {
    if (isUpdating.value) return
    
    isUpdating.value = true
    
    try {
      filters.value = [...newFilters]
    } finally {
      await nextTick()
      isUpdating.value = false
    }
  }
  
  const setTargetType = async (newTargetType) => {
    // Target type changes are critical and should not be blocked by isUpdating
    // Just update the target type directly - it's independent of other operations
    if (targetType.value !== newTargetType) {
      targetType.value = newTargetType
      
      // Clear fields when target type changes - they need remapping
      // Only clear if we're not currently updating to avoid conflicts
      if (!isUpdating.value) {
        fields.value = []
      }
    }
  }
  
  // Utility methods
  const reset = () => {
    baseTable.value = ''
    joins.value = []
    fields.value = []
    filters.value = []
    targetType.value = 'customer_users'
  }

  const loadConfig = async (config) => {
    if (isUpdating.value) return
    
    isUpdating.value = true
    
    try {
      // Load configuration without side effects
      if (config.base_table) {
        baseTable.value = config.base_table
      }
      if (config.joins) {
        joins.value = [...config.joins]
      }
      if (config.fields) {
        fields.value = [...config.fields]
      }
      if (config.filters) {
        filters.value = [...config.filters]
      }
      if (config.target_type) {
        targetType.value = config.target_type
      }
    } finally {
      await nextTick()
      isUpdating.value = false
    }
  }
  
  const exportConfig = () => ({
    base_table: baseTable.value,
    joins: [...joins.value],
    fields: [...fields.value], 
    filters: [...filters.value],
    target_type: targetType.value
  })
  
  return {
    // Read-only computed state
    queryConfig,
    isValidQuery,
    
    // Individual reactive refs (for v-model bindings)
    baseTable: readonly(baseTable),
    joins: readonly(joins),
    fields: readonly(fields),
    filters: readonly(filters),
    targetType: readonly(targetType),
    
    // Controlled mutation methods
    setBaseTable,
    setJoins,
    setFields,
    setFilters,
    setTargetType,
    
    // Utilities
    reset,
    loadConfig,
    exportConfig
  }
}