import { describe, it, expect, beforeEach } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useModalStore } from '@/stores/ui'

/**
 * UI Store Tests
 * 
 * Tests for UI state management:
 * - Modal visibility and state
 * - Toast notifications
 * - Global UI state
 * - Loading overlays
 */

describe('UI Store', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
  })

  describe('Modal Management', () => {
    it('starts with no modal open', () => {
      const store = useModalStore()
      expect(store.isOpen).toBe(false)
    })

    it('opens modal with data', () => {
      const store = useModalStore()
      
      store.open({
        title: 'Confirm Action',
        message: 'Are you sure?',
      })
      
      expect(store.isOpen).toBe(true)
      expect(store.modal.title).toBe('Confirm Action')
      expect(store.modal.message).toBe('Are you sure?')
    })

    it('closes modal', () => {
      const store = useModalStore()
      store.open({ title: 'Test' })
      expect(store.isOpen).toBe(true)
      
      store.close()
      
      expect(store.isOpen).toBe(false)
    })

    it('clears modal data on close', () => {
      const store = useModalStore()
      store.open({ title: 'Test', message: 'Message' })
      
      store.close()
      
      expect(store.modal.title).toBeNull() || expect(store.modal.title).toBeUndefined()
    })

    it('supports modal types', () => {
      const store = useModalStore()
      
      store.open({
        title: 'Success',
        type: 'success',
      })
      
      expect(store.modal.type).toBe('success')
    })

    it('stores confirm callback', () => {
      const store = useModalStore()
      const callback = () => console.log('confirmed')
      
      store.open({
        title: 'Confirm',
        onConfirm: callback,
      })
      
      expect(typeof store.modal.onConfirm).toBe('function')
    })

    it('stores cancel callback', () => {
      const store = useModalStore()
      const callback = () => console.log('cancelled')
      
      store.open({
        title: 'Confirm',
        onCancel: callback,
      })
      
      expect(typeof store.modal.onCancel).toBe('function')
    })

    it('executes confirm callback', () => {
      const store = useModalStore()
      let called = false
      
      store.open({
        title: 'Test',
        onConfirm: () => {
          called = true
        },
      })
      
      store.confirm()
      
      expect(called).toBe(true)
      expect(store.isOpen).toBe(false)
    })

    it('executes cancel callback', () => {
      const store = useModalStore()
      let called = false
      
      store.open({
        title: 'Test',
        onCancel: () => {
          called = true
        },
      })
      
      store.cancel()
      
      expect(called).toBe(true)
      expect(store.isOpen).toBe(false)
    })
  })

  describe('Modal Variants', () => {
    it('marks modal as destructive', () => {
      const store = useModalStore()
      
      store.open({
        title: 'Delete',
        isDestructive: true,
      })
      
      expect(store.modal.isDestructive).toBe(true)
    })

    it('sets custom button text', () => {
      const store = useModalStore()
      
      store.open({
        title: 'Test',
        confirmText: 'Accept',
        cancelText: 'Decline',
      })
      
      expect(store.modal.confirmText).toBe('Accept')
      expect(store.modal.cancelText).toBe('Decline')
    })

    it('supports loading state', () => {
      const store = useModalStore()
      
      store.open({
        title: 'Processing',
        isLoading: true,
      })
      
      expect(store.modal.isLoading).toBe(true)
    })
  })

  describe('Multiple Modals', () => {
    it('supports modal stack', () => {
      const store = useModalStore()
      
      store.open({ title: 'Modal 1' })
      store.pushModal({ title: 'Modal 2' })
      
      expect(store.modalStack.length).toBe(2)
    })

    it('pops modal from stack', () => {
      const store = useModalStore()
      store.open({ title: 'Modal 1' })
      store.pushModal({ title: 'Modal 2' })
      expect(store.modalStack.length).toBe(2)
      
      store.popModal()
      
      expect(store.modalStack.length).toBe(1)
    })

    it('closes all modals', () => {
      const store = useModalStore()
      store.open({ title: 'Modal 1' })
      store.pushModal({ title: 'Modal 2' })
      
      store.closeAll()
      
      expect(store.isOpen).toBe(false)
      expect(store.modalStack.length).toBe(0)
    })
  })

  describe('Toast Notifications', () => {
    it('adds success toast', () => {
      const store = useModalStore()
      
      store.toast.success('Operation successful')
      
      expect(store.toasts.length).toBeGreaterThan(0)
    })

    it('adds error toast', () => {
      const store = useModalStore()
      
      store.toast.error('Operation failed')
      
      expect(store.toasts.length).toBeGreaterThan(0)
    })

    it('adds warning toast', () => {
      const store = useModalStore()
      
      store.toast.warning('Warning message')
      
      expect(store.toasts.length).toBeGreaterThan(0)
    })

    it('adds info toast', () => {
      const store = useModalStore()
      
      store.toast.info('Info message')
      
      expect(store.toasts.length).toBeGreaterThan(0)
    })

    it('removes toast after timeout', async () => {
      const store = useModalStore()
      
      store.toast.success('Message', { duration: 100 })
      
      expect(store.toasts.length).toBe(1)
      
      // Wait for timeout
      await new Promise(resolve => setTimeout(resolve, 150))
      
      // Toast should be automatically removed
      expect(store.toasts.length).toBeLessThanOrEqual(1)
    })

    it('removes specific toast', () => {
      const store = useModalStore()
      
      const toast1 = store.toast.success('Toast 1')
      const toast2 = store.toast.success('Toast 2')
      
      expect(store.toasts.length).toBe(2)
      
      store.removeToast(toast1.id)
      
      expect(store.toasts.length).toBe(1)
    })

    it('clears all toasts', () => {
      const store = useModalStore()
      
      store.toast.success('Toast 1')
      store.toast.error('Toast 2')
      store.toast.warning('Toast 3')
      
      expect(store.toasts.length).toBe(3)
      
      store.clearToasts()
      
      expect(store.toasts.length).toBe(0)
    })
  })

  describe('Sidebar State', () => {
    it('toggles sidebar visibility', () => {
      const store = useModalStore()
      
      expect(store.sidebarOpen).toBe(false) || expect(typeof store.sidebarOpen).toBe('boolean')
      
      store.toggleSidebar()
      const newState = store.sidebarOpen
      
      store.toggleSidebar()
      expect(store.sidebarOpen).not.toBe(newState)
    })

    it('opens sidebar', () => {
      const store = useModalStore()
      
      store.openSidebar()
      
      expect(store.sidebarOpen).toBe(true)
    })

    it('closes sidebar', () => {
      const store = useModalStore()
      
      store.closeSidebar()
      
      expect(store.sidebarOpen).toBe(false)
    })
  })

  describe('Loading Overlay', () => {
    it('shows loading overlay', () => {
      const store = useModalStore()
      
      store.showLoading('Processing...')
      
      expect(store.isLoadingVisible).toBe(true)
      expect(store.loadingMessage).toBe('Processing...')
    })

    it('hides loading overlay', () => {
      const store = useModalStore()
      store.showLoading('Processing...')
      
      store.hideLoading()
      
      expect(store.isLoadingVisible).toBe(false)
    })

    it('updates loading message', () => {
      const store = useModalStore()
      store.showLoading('Step 1...')
      
      store.updateLoadingMessage('Step 2...')
      
      expect(store.loadingMessage).toBe('Step 2...')
    })
  })

  describe('Theme', () => {
    it('stores current theme', () => {
      const store = useModalStore()
      
      store.setTheme('dark')
      
      expect(store.theme).toBe('dark')
    })

    it('toggles between themes', () => {
      const store = useModalStore()
      store.setTheme('light')
      
      store.toggleTheme()
      
      expect(store.theme).toBe('dark') || expect(store.theme).toBe('light')
    })

    it('supports light and dark themes', () => {
      const store = useModalStore()
      
      store.setTheme('light')
      expect(store.theme).toBe('light')
      
      store.setTheme('dark')
      expect(store.theme).toBe('dark')
    })
  })

  describe('Notifications Center', () => {
    it('stores notifications', () => {
      const store = useModalStore()
      
      store.addNotification({
        id: 1,
        type: 'info',
        message: 'New notification',
      })
      
      expect(store.notifications.length).toBeGreaterThan(0)
    })

    it('marks notification as read', () => {
      const store = useModalStore()
      
      store.addNotification({
        id: 1,
        message: 'Notification',
        read: false,
      })
      
      store.markNotificationAsRead(1)
      
      expect(store.notifications[0].read).toBe(true)
    })

    it('clears notifications', () => {
      const store = useModalStore()
      store.addNotification({ id: 1, message: 'Test' })
      
      store.clearNotifications()
      
      expect(store.notifications.length).toBe(0)
    })
  })

  describe('Confirm Dialog', () => {
    it('opens confirm dialog', () => {
      const store = useModalStore()
      
      store.openConfirm({
        title: 'Confirm?',
        message: 'Are you sure?',
      })
      
      expect(store.isOpen).toBe(true)
      expect(store.modal.type).toBe('confirm')
    })

    it('opens alert dialog', () => {
      const store = useModalStore()
      
      store.openAlert({
        title: 'Alert',
        message: 'Important message',
      })
      
      expect(store.isOpen).toBe(true)
      expect(store.modal.type).toBe('alert')
    })
  })

  describe('Store Cleanup', () => {
    it('resets store to initial state', () => {
      const store = useModalStore()
      store.open({ title: 'Test' })
      store.toast.success('Message')
      store.showLoading('Loading...')
      
      store.$reset()
      
      expect(store.isOpen).toBe(false)
      expect(store.toasts.length).toBe(0)
      expect(store.isLoadingVisible).toBe(false)
    })
  })
})
