import { defineStore } from 'pinia'
import { ref } from 'vue'

/**
 * Modal Store
 * 
 * Manages:
 * - Global modal visibility and state
 * - Modal content and actions
 * - Modal lifecycle (open/close)
 */

export const useModalStore = defineStore('modal', () => {
  // State
  const isOpen = ref(false)
  const type = ref(null) // 'info', 'success', 'error', 'warning', 'custom'
  const title = ref('')
  const message = ref('')
  const confirmText = ref('Confirm')
  const cancelText = ref('Cancel')
  const isDestructive = ref(false)

  // Callbacks
  const onConfirm = ref(null)
  const onCancel = ref(null)
  const onClose = ref(null)

  /**
   * Open modal with configuration
   * @param {object} config Modal configuration
   */
  function open(config = {}) {
    type.value = config.type ?? 'info'
    title.value = config.title ?? ''
    message.value = config.message ?? ''
    confirmText.value = config.confirmText ?? 'Confirm'
    cancelText.value = config.cancelText ?? 'Cancel'
    isDestructive.value = config.isDestructive ?? false
    onConfirm.value = config.onConfirm ?? null
    onCancel.value = config.onCancel ?? null
    onClose.value = config.onClose ?? null
    isOpen.value = true
  }

  /**
   * Close modal
   */
  function close() {
    isOpen.value = false
    if (onClose.value) {
      onClose.value()
    }
  }

  /**
   * Confirm modal action
   */
  function confirm() {
    if (onConfirm.value) {
      onConfirm.value()
    }
    close()
  }

  /**
   * Cancel modal action
   */
  function cancel() {
    if (onCancel.value) {
      onCancel.value()
    }
    close()
  }

  /**
   * Show confirmation modal
   * @param {string} title Modal title
   * @param {string} message Modal message
   * @param {Function} onConfirm Confirmation callback
   */
  function showConfirm(title, message, onConfirm) {
    open({
      type: 'info',
      title,
      message,
      confirmText: 'Confirm',
      cancelText: 'Cancel',
      onConfirm,
    })
  }

  /**
   * Show error modal
   * @param {string} title Modal title
   * @param {string} message Error message
   */
  function showError(title, message) {
    open({
      type: 'error',
      title,
      message,
      confirmText: 'OK',
    })
  }

  /**
   * Show success modal
   * @param {string} title Modal title
   * @param {string} message Success message
   */
  function showSuccess(title, message) {
    open({
      type: 'success',
      title,
      message,
      confirmText: 'OK',
    })
  }

  /**
   * Show warning modal
   * @param {string} title Modal title
   * @param {string} message Warning message
   * @param {Function} onConfirm Confirmation callback
   */
  function showWarning(title, message, onConfirm) {
    open({
      type: 'warning',
      title,
      message,
      confirmText: 'Continue',
      cancelText: 'Cancel',
      onConfirm,
    })
  }

  /**
   * Show destructive action confirmation
   * @param {string} title Modal title
   * @param {string} message Confirmation message
   * @param {Function} onConfirm Confirmation callback
   */
  function showDestructive(title, message, onConfirm) {
    open({
      type: 'error',
      title,
      message,
      confirmText: 'Delete',
      cancelText: 'Cancel',
      isDestructive: true,
      onConfirm,
    })
  }

  return {
    // State
    isOpen,
    type,
    title,
    message,
    confirmText,
    cancelText,
    isDestructive,

    // Actions
    open,
    close,
    confirm,
    cancel,
    showConfirm,
    showError,
    showSuccess,
    showWarning,
    showDestructive,
  }
})

/**
 * Loading Store
 * 
 * Manages:
 * - Global loading overlay visibility
 * - Loading message
 * - Loading state stack (for nested operations)
 */

export const useLoadingStore = defineStore('loading', () => {
  // State
  const isLoading = ref(false)
  const message = ref('Loading...')
  const loadingStack = ref([])

  /**
   * Start loading
   * @param {string} msg Loading message
   */
  function start(msg = 'Loading...') {
    loadingStack.value.push(msg)
    message.value = msg
    isLoading.value = true
  }

  /**
   * Stop loading
   */
  function stop() {
    loadingStack.value.pop()
    if (loadingStack.value.length > 0) {
      message.value = loadingStack.value[loadingStack.value.length - 1]
    } else {
      isLoading.value = false
      message.value = 'Loading...'
    }
  }

  /**
   * Reset loading state
   */
  function reset() {
    loadingStack.value = []
    isLoading.value = false
    message.value = 'Loading...'
  }

  return {
    // State
    isLoading,
    message,
    loadingStack,

    // Actions
    start,
    stop,
    reset,
  }
})

/**
 * Notification Store
 * 
 * Manages:
 * - Toast notifications
 * - Notification stack
 * - Auto-dismiss
 */

export const useNotificationStore = defineStore('notification', () => {
  // State
  const notifications = ref([])
  const autoDelay = ref(4000) // 4 seconds

  /**
   * Add notification
   * @param {object} config Notification configuration
   */
  function add(config = {}) {
    const id = Date.now().toString()
    const notification = {
      id,
      type: config.type ?? 'info', // 'info', 'success', 'error', 'warning'
      title: config.title ?? '',
      message: config.message ?? '',
      duration: config.duration ?? autoDelay.value,
    }

    notifications.value.push(notification)

    // Auto-dismiss after duration
    if (notification.duration > 0) {
      setTimeout(() => {
        remove(id)
      }, notification.duration)
    }

    return id
  }

  /**
   * Remove notification
   * @param {string} id Notification ID
   */
  function remove(id) {
    const index = notifications.value.findIndex((n) => n.id === id)
    if (index !== -1) {
      notifications.value.splice(index, 1)
    }
  }

  /**
   * Show success notification
   * @param {string} message Message text
   * @param {string} title Optional title
   */
  function success(message, title = 'Success') {
    return add({
      type: 'success',
      title,
      message,
    })
  }

  /**
   * Show error notification
   * @param {string} message Message text
   * @param {string} title Optional title
   */
  function error(message, title = 'Error') {
    return add({
      type: 'error',
      title,
      message,
      duration: 6000, // Show errors longer
    })
  }

  /**
   * Show warning notification
   * @param {string} message Message text
   * @param {string} title Optional title
   */
  function warning(message, title = 'Warning') {
    return add({
      type: 'warning',
      title,
      message,
    })
  }

  /**
   * Show info notification
   * @param {string} message Message text
   * @param {string} title Optional title
   */
  function info(message, title = 'Info') {
    return add({
      type: 'info',
      title,
      message,
    })
  }

  /**
   * Clear all notifications
   */
  function clearAll() {
    notifications.value = []
  }

  return {
    // State
    notifications,
    autoDelay,

    // Actions
    add,
    remove,
    success,
    error,
    warning,
    info,
    clearAll,
  }
})
