/**
 * Vue composable for formatting utilities
 * Automatically injects system settings from usePage
 */

import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { 
  formatDate as _formatDate,
  formatTime as _formatTime, 
  formatDateTime as _formatDateTime,
  formatCurrency as _formatCurrency,
  formatDuration as _formatDuration,
  formatFileSize as _formatFileSize,
  formatNumber as _formatNumber,
  formatPercentage as _formatPercentage
} from '@/Utils/formats'

export function useFormats() {
  // Get settings from page props
  const page = usePage()
  const settings = computed(() => page.props?.settings || {})
  
  // Create wrapped functions that automatically inject settings
  const formatDate = (dateString, options = {}) => {
    return _formatDate(dateString, { ...options, settings: settings.value })
  }
  
  const formatTime = (timeString, options = {}) => {
    return _formatTime(timeString, { ...options, settings: settings.value })
  }
  
  const formatDateTime = (dateTimeString, options = {}) => {
    return _formatDateTime(dateTimeString, { ...options, settings: settings.value })
  }
  
  const formatCurrency = (amount, options = {}) => {
    return _formatCurrency(amount, { ...options, settings: settings.value })
  }
  
  const formatNumber = (number, options = {}) => {
    return _formatNumber(number, { ...options, settings: settings.value })
  }
  
  const formatPercentage = (value, options = {}) => {
    return _formatPercentage(value, { ...options, settings: settings.value })
  }
  
  // These don't need settings, so we can use them directly
  const formatDuration = _formatDuration
  const formatFileSize = _formatFileSize
  
  return {
    formatDate,
    formatTime,
    formatDateTime,
    formatCurrency,
    formatDuration,
    formatFileSize,
    formatNumber,
    formatPercentage,
    settings
  }
}