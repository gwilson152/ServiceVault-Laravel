import { ref, computed, watch } from 'vue'
import { usePage } from '@inertiajs/vue3'
import axios from 'axios'

// Global state for timer settings (shared across all components)
const timerSettings = ref({
  // Timer Behavior
  default_auto_stop: false,
  allow_concurrent_timers: true,
  auto_commit_on_stop: false,
  require_description: true,
  default_billable: true,
  
  // Synchronization
  sync_interval_seconds: 5,
  
  // Validation
  min_timer_duration_minutes: 0,
  max_timer_duration_hours: 8,
  auto_stop_long_timers: false,
  
  // Display
  time_display_format: 'hms', // 'hms' | 'hm' | 'decimal'
  show_timer_overlay: true,
  play_timer_sounds: false,
})

const settingsLoaded = ref(false)
const settingsLoading = ref(false)
const settingsError = ref(null)

// Cache for user permissions
const userPermissions = ref(new Set())

export function useTimerSettings() {
  const page = usePage()
  
  // Computed permissions
  const user = computed(() => page.props.auth?.user)
  
  const canManageSettings = computed(() => {
    return userPermissions.value.has('admin.manage') || 
           userPermissions.value.has('settings.manage')
  })
  
  const canManageAllTimers = computed(() => {
    return userPermissions.value.has('timers.manage.all') || 
           userPermissions.value.has('admin.manage')
  })
  
  const canViewAllTimers = computed(() => {
    return userPermissions.value.has('timers.view.all') || 
           userPermissions.value.has('admin.read') ||
           canManageAllTimers.value
  })

  // Timer validation computed properties
  const validation = computed(() => ({
    description: {
      required: timerSettings.value.require_description,
      minLength: timerSettings.value.require_description ? 1 : 0
    },
    duration: {
      min: timerSettings.value.min_timer_duration_minutes * 60, // Convert to seconds
      max: timerSettings.value.max_timer_duration_hours * 3600,  // Convert to seconds
    },
    billable: {
      default: timerSettings.value.default_billable
    },
    autoStop: {
      enabled: timerSettings.value.default_auto_stop,
      longTimers: timerSettings.value.auto_stop_long_timers
    },
    autoCommit: {
      enabled: timerSettings.value.auto_commit_on_stop
    }
  }))

  // Formatting utilities based on settings
  const formatting = computed(() => ({
    duration: {
      format: timerSettings.value.time_display_format,
      showSeconds: timerSettings.value.time_display_format === 'hms'
    },
    overlay: {
      show: timerSettings.value.show_timer_overlay,
      sounds: timerSettings.value.play_timer_sounds
    },
    sync: {
      interval: timerSettings.value.sync_interval_seconds * 1000 // Convert to milliseconds
    }
  }))

  // Load timer settings from API
  const loadSettings = async (force = false) => {
    if (settingsLoaded.value && !force) return timerSettings.value
    
    settingsLoading.value = true
    settingsError.value = null
    
    try {
      const response = await axios.get('/api/settings/timer')
      
      if (response.data && response.data.data) {
        // Merge with defaults to ensure all properties exist
        Object.assign(timerSettings.value, response.data.data)
      }
      
      settingsLoaded.value = true
      return timerSettings.value
      
    } catch (error) {
      // Handle 403 errors gracefully - user doesn't have permission
      if (error.response?.status === 403) {
        console.info('Timer settings not accessible for this user, using defaults')
        settingsLoaded.value = true
        return timerSettings.value
      }
      
      console.error('Failed to load timer settings:', error)
      settingsError.value = error.response?.data?.message || 'Failed to load timer settings'
      
      // Return defaults on error
      return timerSettings.value
      
    } finally {
      settingsLoading.value = false
    }
  }

  // Save timer settings to API
  const saveSettings = async (newSettings) => {
    if (!canManageSettings.value) {
      throw new Error('You do not have permission to manage settings')
    }
    
    settingsLoading.value = true
    settingsError.value = null
    
    try {
      const response = await axios.put('/api/settings/timer', newSettings)
      
      if (response.data && response.data.data) {
        Object.assign(timerSettings.value, response.data.data)
      }
      
      return timerSettings.value
      
    } catch (error) {
      console.error('Failed to save timer settings:', error)
      settingsError.value = error.response?.data?.message || 'Failed to save timer settings'
      throw error
      
    } finally {
      settingsLoading.value = false
    }
  }

  // Update a single setting
  const updateSetting = async (key, value) => {
    if (!canManageSettings.value) {
      throw new Error('You do not have permission to manage settings')
    }
    
    const updates = { [key]: value }
    return await saveSettings(updates)
  }

  // Load user permissions from current user data
  const loadUserPermissions = () => {
    if (user.value?.permissions) {
      userPermissions.value = new Set(user.value.permissions)
    }
  }

  // Duration formatting utilities
  const formatDuration = (seconds, options = {}) => {
    if (!seconds) return '0m'
    
    const format = options.format || timerSettings.value.time_display_format
    const hours = Math.floor(seconds / 3600)
    const minutes = Math.floor((seconds % 3600) / 60)
    const secs = seconds % 60
    
    switch (format) {
      case 'hms':
        if (hours > 0) {
          return `${hours}:${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`
        } else {
          return `${minutes}:${secs.toString().padStart(2, '0')}`
        }
      case 'hm':
        if (hours > 0) {
          return `${hours}h ${minutes}m`
        } else {
          return `${minutes}m`
        }
      case 'decimal':
        return `${(seconds / 3600).toFixed(2)}h`
      default:
        return `${hours}h ${minutes}m`
    }
  }

  // Parse duration from string input
  const parseDuration = (input) => {
    if (typeof input === 'number') return input
    if (!input || typeof input !== 'string') return 0
    
    input = input.toLowerCase().trim()
    
    // Match patterns like "1h 30m", "1:30:00", "1.5h", "90m", etc.
    const patterns = [
      // "1h 30m 45s" or "1h 30m" or "1h"
      /^(?:(\d+)h)?\s*(?:(\d+)m)?\s*(?:(\d+)s)?$/,
      // "1:30:45" or "1:30" or ":30"
      /^(?:(\d+):)?(\d+):(\d+)$/,
      // "1.5h" decimal hours
      /^(\d+(?:\.\d+)?)h$/,
      // "90m" minutes only
      /^(\d+)m$/,
      // "45s" seconds only
      /^(\d+)s$/
    ]
    
    for (const pattern of patterns) {
      const match = input.match(pattern)
      if (match) {
        if (pattern === patterns[1]) { // HH:MM:SS format
          const hours = parseInt(match[1] || 0)
          const minutes = parseInt(match[2] || 0)
          const seconds = parseInt(match[3] || 0)
          return hours * 3600 + minutes * 60 + seconds
        } else if (pattern === patterns[2]) { // Decimal hours
          return Math.round(parseFloat(match[1]) * 3600)
        } else if (pattern === patterns[3]) { // Minutes only
          return parseInt(match[1]) * 60
        } else if (pattern === patterns[4]) { // Seconds only
          return parseInt(match[1])
        } else { // "1h 30m 45s" format
          const hours = parseInt(match[1] || 0)
          const minutes = parseInt(match[2] || 0)
          const seconds = parseInt(match[3] || 0)
          return hours * 3600 + minutes * 60 + seconds
        }
      }
    }
    
    return 0
  }

  // Validate duration against settings
  const validateDuration = (seconds) => {
    const errors = []
    
    if (seconds < validation.value.duration.min) {
      const minDuration = formatDuration(validation.value.duration.min)
      errors.push(`Duration must be at least ${minDuration}`)
    }
    
    if (seconds > validation.value.duration.max) {
      const maxDuration = formatDuration(validation.value.duration.max)
      errors.push(`Duration cannot exceed ${maxDuration}`)
    }
    
    return errors
  }

  // Calculate approval requirement based on settings and context
  const requiresApproval = (context = {}) => {
    const { duration = 0, amount = 0, userId = null } = context
    
    // This would be enhanced with actual business rules from settings
    const maxHoursWithoutApproval = 8 * 3600 // 8 hours in seconds
    const maxAmountWithoutApproval = 1000 // $1000
    
    if (duration > maxHoursWithoutApproval) return true
    if (amount > maxAmountWithoutApproval) return true
    
    // Other approval rules could be added here based on settings
    return false
  }

  // Initialize on first use
  if (!settingsLoaded.value && !settingsLoading.value) {
    loadUserPermissions()
    loadSettings()
  }

  // Watch for user changes to reload permissions
  watch(user, loadUserPermissions, { immediate: true })

  return {
    // Settings state
    settings: timerSettings,
    settingsLoaded,
    settingsLoading,
    settingsError,
    
    // Computed properties
    validation,
    formatting,
    
    // Permissions
    canManageSettings,
    canManageAllTimers,
    canViewAllTimers,
    
    // Methods
    loadSettings,
    saveSettings,
    updateSetting,
    formatDuration,
    parseDuration,
    validateDuration,
    requiresApproval,
    
    // Utilities
    loadUserPermissions
  }
}

// Export a singleton instance for components that need immediate access
export const globalTimerSettings = {
  settings: timerSettings,
  loaded: settingsLoaded,
  loading: settingsLoading,
  error: settingsError
}