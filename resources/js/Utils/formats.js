/**
 * Formatting utilities for dates, times, currency, and other display values
 * Respects system settings for consistent formatting across the application
 */

import { usePage } from '@inertiajs/vue3'

/**
 * Get system settings from page props
 */
const getSettings = () => {
  try {
    const page = usePage()
    return page.props.settings || {}
  } catch (error) {
    console.warn('Failed to get system settings:', error)
    return {}
  }
}

/**
 * PHP date format to JavaScript date format mapping
 */
const phpToJsDateFormats = {
  'Y-m-d': 'yyyy-MM-dd',           // 2024-12-31
  'm/d/Y': 'MM/dd/yyyy',           // 12/31/2024
  'd/m/Y': 'dd/MM/yyyy',           // 31/12/2024
  'd-m-Y': 'dd-MM-yyyy',           // 31-12-2024
  'Y/m/d': 'yyyy/MM/dd',           // 2024/12/31
  'M d, Y': 'MMM dd, yyyy',        // Dec 31, 2024
  'F d, Y': 'MMMM dd, yyyy',       // December 31, 2024
  'd M Y': 'dd MMM yyyy',          // 31 Dec 2024
  'd F Y': 'dd MMMM yyyy',         // 31 December 2024
  'j/n/Y': 'M/d/yyyy',             // 1/1/2024 (no leading zeros)
  'n/j/Y': 'd/M/yyyy',             // 1/1/2024 (no leading zeros)
}

/**
 * PHP time format to JavaScript time format mapping
 */
const phpToJsTimeFormats = {
  'H:i': 'HH:mm',                  // 24:00
  'H:i:s': 'HH:mm:ss',             // 24:00:00
  'g:i A': 'h:mm a',               // 12:00 AM
  'g:i a': 'h:mm a',               // 12:00 am
  'h:i A': 'hh:mm a',              // 12:00 AM
  'h:i a': 'hh:mm a',              // 12:00 am
}

/**
 * Convert PHP date format to JavaScript date format
 */
const convertPhpToJsFormat = (phpFormat, type = 'date') => {
  const formatMap = type === 'time' ? phpToJsTimeFormats : phpToJsDateFormats
  return formatMap[phpFormat] || phpFormat
}

/**
 * Format a date string according to system settings
 */
export const formatDate = (dateString, options = {}) => {
  if (!dateString) return ''
  
  try {
    const date = new Date(dateString)
    if (isNaN(date.getTime())) return dateString
    
    const settings = getSettings()
    const format = options.format || settings.date_format || 'Y-m-d'
    
    // Handle relative dates for recent dates if requested
    if (options.relative !== false) {
      const now = new Date()
      const diffDays = Math.floor((now - date) / (1000 * 60 * 60 * 24))
      
      if (diffDays === 0) return 'Today'
      if (diffDays === 1) return 'Yesterday'
      if (diffDays === -1) return 'Tomorrow'
      if (diffDays < 7 && diffDays > 0) return `${diffDays} days ago`
      if (diffDays > -7 && diffDays < 0) return `In ${Math.abs(diffDays)} days`
    }
    
    // Use browser's Intl.DateTimeFormat for consistent formatting
    const locale = settings.language || 'en-US'
    
    // Map common PHP formats to Intl options
    const formatOptions = getIntlDateFormatOptions(format)
    
    if (formatOptions) {
      return new Intl.DateTimeFormat(locale, formatOptions).format(date)
    }
    
    // Fallback to basic formatting
    return date.toLocaleDateString(locale)
    
  } catch (error) {
    console.warn('Date formatting error:', error)
    return dateString
  }
}

/**
 * Format a time string according to system settings
 */
export const formatTime = (timeString, options = {}) => {
  if (!timeString) return ''
  
  try {
    // Handle both full datetime strings and time-only strings
    const date = timeString.includes('T') || timeString.includes(' ')
      ? new Date(timeString)
      : new Date(`1970-01-01T${timeString}`)
      
    if (isNaN(date.getTime())) return timeString
    
    const settings = getSettings()
    const format = options.format || settings.time_format || 'H:i'
    const locale = settings.language || 'en-US'
    
    // Map common PHP time formats to Intl options
    const formatOptions = getIntlTimeFormatOptions(format)
    
    if (formatOptions) {
      return new Intl.DateTimeFormat(locale, formatOptions).format(date)
    }
    
    // Fallback formatting
    return date.toLocaleTimeString(locale, { 
      hour: '2-digit', 
      minute: '2-digit',
      hour12: format.includes('A') || format.includes('a')
    })
    
  } catch (error) {
    console.warn('Time formatting error:', error)
    return timeString
  }
}

/**
 * Format a full datetime string according to system settings
 */
export const formatDateTime = (dateTimeString, options = {}) => {
  if (!dateTimeString) return ''
  
  try {
    const date = new Date(dateTimeString)
    if (isNaN(date.getTime())) return dateTimeString
    
    const settings = getSettings()
    const dateFormat = options.dateFormat || settings.date_format || 'Y-m-d'
    const timeFormat = options.timeFormat || settings.time_format || 'H:i'
    const locale = settings.language || 'en-US'
    
    // Handle relative dates for recent dates
    if (options.relative !== false) {
      const now = new Date()
      const diffHours = Math.floor((now - date) / (1000 * 60 * 60))
      
      if (diffHours < 1) {
        const diffMinutes = Math.floor((now - date) / (1000 * 60))
        if (diffMinutes < 1) return 'Just now'
        if (diffMinutes === 1) return '1 minute ago'
        return `${diffMinutes} minutes ago`
      }
      
      if (diffHours < 24) {
        if (diffHours === 1) return '1 hour ago'
        return `${diffHours} hours ago`
      }
    }
    
    const dateFormatOptions = getIntlDateFormatOptions(dateFormat)
    const timeFormatOptions = getIntlTimeFormatOptions(timeFormat)
    
    if (dateFormatOptions && timeFormatOptions) {
      const combinedOptions = { ...dateFormatOptions, ...timeFormatOptions }
      return new Intl.DateTimeFormat(locale, combinedOptions).format(date)
    }
    
    // Fallback
    return `${formatDate(dateTimeString, { format: dateFormat, relative: false })} ${formatTime(dateTimeString, { format: timeFormat })}`
    
  } catch (error) {
    console.warn('DateTime formatting error:', error)
    return dateTimeString
  }
}

/**
 * Format currency according to system settings
 */
export const formatCurrency = (amount, options = {}) => {
  if (amount === null || amount === undefined) return ''
  
  try {
    const settings = getSettings()
    const currency = options.currency || settings.currency || 'USD'
    const locale = settings.language || 'en-US'
    
    const formatter = new Intl.NumberFormat(locale, {
      style: 'currency',
      currency: currency,
      minimumFractionDigits: options.minimumFractionDigits ?? 2,
      maximumFractionDigits: options.maximumFractionDigits ?? 2,
    })
    
    return formatter.format(parseFloat(amount))
    
  } catch (error) {
    console.warn('Currency formatting error:', error)
    return `$${parseFloat(amount).toFixed(2)}`
  }
}

/**
 * Format duration in seconds to human readable format
 */
export const formatDuration = (seconds, options = {}) => {
  if (!seconds || seconds === 0) return options.showZero ? '0m' : ''
  
  const totalSeconds = Math.abs(parseInt(seconds))
  const hours = Math.floor(totalSeconds / 3600)
  const minutes = Math.floor((totalSeconds % 3600) / 60)
  const remainingSeconds = totalSeconds % 60
  
  if (options.format === 'decimal') {
    return (totalSeconds / 3600).toFixed(2) + ' hrs'
  }
  
  if (options.format === 'detailed') {
    const parts = []
    if (hours > 0) parts.push(`${hours}h`)
    if (minutes > 0 || hours > 0) parts.push(`${minutes}m`)
    if (remainingSeconds > 0 && hours === 0) parts.push(`${remainingSeconds}s`)
    return parts.join(' ')
  }
  
  // Default format
  if (hours > 0) {
    return `${hours}h ${minutes}m`
  }
  return `${minutes}m`
}

/**
 * Format file size in bytes to human readable format
 */
export const formatFileSize = (bytes, options = {}) => {
  if (bytes === 0) return '0 Bytes'
  
  const k = 1024
  const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  const size = parseFloat((bytes / Math.pow(k, i)).toFixed(options.decimals || 2))
  
  return `${size} ${sizes[i]}`
}

/**
 * Get Intl.DateTimeFormat options from PHP date format
 */
const getIntlDateFormatOptions = (phpFormat) => {
  switch (phpFormat) {
    case 'Y-m-d':
    case 'Y/m/d':
      return { year: 'numeric', month: '2-digit', day: '2-digit' }
    case 'm/d/Y':
    case 'd/m/Y':
    case 'd-m-Y':
      return { year: 'numeric', month: '2-digit', day: '2-digit' }
    case 'M d, Y':
      return { year: 'numeric', month: 'short', day: 'numeric' }
    case 'F d, Y':
      return { year: 'numeric', month: 'long', day: 'numeric' }
    case 'd M Y':
      return { year: 'numeric', month: 'short', day: '2-digit' }
    case 'd F Y':
      return { year: 'numeric', month: 'long', day: '2-digit' }
    default:
      return null
  }
}

/**
 * Get Intl.DateTimeFormat options from PHP time format
 */
const getIntlTimeFormatOptions = (phpFormat) => {
  switch (phpFormat) {
    case 'H:i':
      return { hour: '2-digit', minute: '2-digit', hour12: false }
    case 'H:i:s':
      return { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false }
    case 'g:i A':
    case 'g:i a':
      return { hour: 'numeric', minute: '2-digit', hour12: true }
    case 'h:i A':
    case 'h:i a':
      return { hour: '2-digit', minute: '2-digit', hour12: true }
    default:
      return null
  }
}

/**
 * Format number according to locale
 */
export const formatNumber = (number, options = {}) => {
  if (number === null || number === undefined) return ''
  
  try {
    const settings = getSettings()
    const locale = settings.language || 'en-US'
    
    return new Intl.NumberFormat(locale, {
      minimumFractionDigits: options.minimumFractionDigits ?? 0,
      maximumFractionDigits: options.maximumFractionDigits ?? 2,
      ...options
    }).format(parseFloat(number))
    
  } catch (error) {
    console.warn('Number formatting error:', error)
    return number.toString()
  }
}

/**
 * Format percentage
 */
export const formatPercentage = (value, options = {}) => {
  if (value === null || value === undefined) return ''
  
  try {
    const settings = getSettings()
    const locale = settings.language || 'en-US'
    
    return new Intl.NumberFormat(locale, {
      style: 'percent',
      minimumFractionDigits: options.minimumFractionDigits ?? 0,
      maximumFractionDigits: options.maximumFractionDigits ?? 1,
    }).format(parseFloat(value) / 100)
    
  } catch (error) {
    console.warn('Percentage formatting error:', error)
    return `${value}%`
  }
}

export default {
  formatDate,
  formatTime,
  formatDateTime,
  formatCurrency,
  formatDuration,
  formatFileSize,
  formatNumber,
  formatPercentage,
}