/**
 * Common Utility Functions
 * 
 * Provides:
 * - Date/time formatting
 * - Error extraction
 * - String utilities
 * - Validation helpers
 */

/**
 * Format date to readable string
 * @param {Date|string} date Date to format
 * @param {string} format Format pattern (default: 'YYYY-MM-DD HH:mm:ss')
 * @returns {string} Formatted date
 */
export function formatDate(date, format = 'YYYY-MM-DD HH:mm:ss') {
  const d = new Date(date)
  if (isNaN(d)) return ''

  const pad = (n) => String(n).padStart(2, '0')
  const replacements = {
    'YYYY': d.getFullYear(),
    'MM': pad(d.getMonth() + 1),
    'DD': pad(d.getDate()),
    'HH': pad(d.getHours()),
    'mm': pad(d.getMinutes()),
    'ss': pad(d.getSeconds()),
  }

  let result = format
  for (const [key, value] of Object.entries(replacements)) {
    result = result.replace(key, value)
  }
  return result
}

/**
 * Format date as relative time (e.g., "2 hours ago")
 * @param {Date|string} date Date to format
 * @returns {string} Relative time string
 */
export function formatRelativeTime(date) {
  const d = new Date(date)
  const now = new Date()
  const seconds = Math.floor((now - d) / 1000)

  if (seconds < 60) return 'just now'
  if (seconds < 3600) return `${Math.floor(seconds / 60)} minutes ago`
  if (seconds < 86400) return `${Math.floor(seconds / 3600)} hours ago`
  if (seconds < 604800) return `${Math.floor(seconds / 86400)} days ago`

  return formatDate(d, 'YYYY-MM-DD')
}

/**
 * Extract error message from API response
 * @param {Error|object} error Error object
 * @returns {string} Error message
 */
export function getErrorMessage(error) {
  if (typeof error === 'string') return error

  if (error.response?.data?.message) {
    return error.response.data.message
  }

  if (error.response?.data?.error?.message) {
    return error.response.data.error.message
  }

  if (error.message) {
    return error.message
  }

  return 'An error occurred. Please try again.'
}

/**
 * Extract validation errors from API response
 * @param {Error|object} error Error object
 * @returns {object} Validation errors by field
 */
export function getValidationErrors(error) {
  if (error.response?.data?.errors) {
    return error.response.data.errors
  }
  return {}
}

/**
 * Validate email format
 * @param {string} email Email to validate
 * @returns {boolean} Is valid email
 */
export function isValidEmail(email) {
  const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
  return regex.test(email)
}

/**
 * Validate URL format
 * @param {string} url URL to validate
 * @returns {boolean} Is valid URL
 */
export function isValidUrl(url) {
  try {
    new URL(url)
    return true
  } catch {
    return false
  }
}

/**
 * Format bytes to human-readable size
 * @param {number} bytes Size in bytes
 * @param {number} decimals Decimal places
 * @returns {string} Formatted size
 */
export function formatBytes(bytes, decimals = 2) {
  if (bytes === 0) return '0 Bytes'

  const k = 1024
  const dm = decimals < 0 ? 0 : decimals
  const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))

  return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i]
}

/**
 * Truncate string to maximum length
 * @param {string} str String to truncate
 * @param {number} maxLength Maximum length
 * @param {string} suffix Suffix to append (default: '...')
 * @returns {string} Truncated string
 */
export function truncate(str, maxLength, suffix = '...') {
  if (str.length <= maxLength) return str
  return str.slice(0, maxLength - suffix.length) + suffix
}

/**
 * Debounce function
 * @param {Function} func Function to debounce
 * @param {number} wait Wait time in milliseconds
 * @returns {Function} Debounced function
 */
export function debounce(func, wait) {
  let timeout
  return function executedFunction(...args) {
    const later = () => {
      clearTimeout(timeout)
      func(...args)
    }
    clearTimeout(timeout)
    timeout = setTimeout(later, wait)
  }
}

/**
 * Throttle function
 * @param {Function} func Function to throttle
 * @param {number} limit Limit time in milliseconds
 * @returns {Function} Throttled function
 */
export function throttle(func, limit) {
  let inThrottle
  return function (...args) {
    if (!inThrottle) {
      func.apply(this, args)
      inThrottle = true
      setTimeout(() => (inThrottle = false), limit)
    }
  }
}

/**
 * Copy text to clipboard
 * @param {string} text Text to copy
 * @returns {Promise<void>}
 */
export async function copyToClipboard(text) {
  try {
    await navigator.clipboard.writeText(text)
    return true
  } catch (err) {
    console.error('Failed to copy:', err)
    return false
  }
}

/**
 * Capitalize first letter of string
 * @param {string} str String to capitalize
 * @returns {string} Capitalized string
 */
export function capitalize(str) {
  if (!str) return ''
  return str.charAt(0).toUpperCase() + str.slice(1)
}

/**
 * Convert camelCase to Title Case
 * @param {string} str String to convert
 * @returns {string} Title case string
 */
export function camelCaseToTitleCase(str) {
  return str
    .replace(/([A-Z])/g, ' $1')
    .replace(/^./, (char) => char.toUpperCase())
    .trim()
}

/**
 * Generate random ID
 * @param {number} length ID length
 * @returns {string} Random ID
 */
export function generateId(length = 10) {
  const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789'
  let id = ''
  for (let i = 0; i < length; i++) {
    id += chars.charAt(Math.floor(Math.random() * chars.length))
  }
  return id
}

/**
 * Check if object is empty
 * @param {object} obj Object to check
 * @returns {boolean} Is empty
 */
export function isEmpty(obj) {
  return Object.keys(obj).length === 0
}

/**
 * Deep clone object
 * @param {object} obj Object to clone
 * @returns {object} Cloned object
 */
export function deepClone(obj) {
  return JSON.parse(JSON.stringify(obj))
}
