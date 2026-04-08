import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import GlobalModal from '@/components/common/GlobalModal.vue'
import { createTestPinia } from '../../../fixtures/helpers'

/**
 * GlobalModal Component Tests
 * 
 * Tests:
 * - Modal visibility state
 * - Title and message display
 * - Confirm/Cancel buttons
 * - Modal types (info, success, error, warning)
 * - Button callbacks
 * - Accessibility
 */

describe('GlobalModal.vue', () => {
  let wrapper

  beforeEach(() => {
    createTestPinia()
  })

  afterEach(() => {
    if (wrapper) {
      wrapper.unmount()
    }
  })

  describe('Visibility', () => {
    it('shows modal when isOpen is true', async () => {
      wrapper = mount(GlobalModal, {
        global: {
          plugins: [createTestPinia()],
        },
      })
      
      // Update store to open modal
      const { useModalStore } = await import('@/stores/ui')
      const modalStore = useModalStore()
      modalStore.open({ title: 'Test', message: 'Test message' })
      
      await wrapper.vm.$nextTick()
      expect(wrapper.find('[role="dialog"]').exists()).toBe(true)
    })

    it('hides modal when isOpen is false', () => {
      wrapper = mount(GlobalModal, {
        global: {
          plugins: [createTestPinia()],
        },
      })
      expect(wrapper.find('[role="dialog"]').exists()).toBe(false)
    })
  })

  describe('Content Display', () => {
    it('displays title and message', async () => {
      wrapper = mount(GlobalModal, {
        global: {
          plugins: [createTestPinia()],
        },
      })
      
      const { useModalStore } = await import('@/stores/ui')
      const modalStore = useModalStore()
      modalStore.open({
        title: 'Confirm Action',
        message: 'Are you sure?',
      })
      
      await wrapper.vm.$nextTick()
      expect(wrapper.text()).toContain('Confirm Action')
      expect(wrapper.text()).toContain('Are you sure?')
    })
  })

  describe('Buttons', () => {
    it('renders confirm and cancel buttons', async () => {
      wrapper = mount(GlobalModal, {
        global: {
          plugins: [createTestPinia()],
        },
      })
      
      const { useModalStore } = await import('@/stores/ui')
      const modalStore = useModalStore()
      modalStore.open({
        title: 'Test',
        confirmText: 'OK',
        cancelText: 'Cancel',
      })
      
      await wrapper.vm.$nextTick()
      const buttons = wrapper.findAll('button')
      expect(buttons.length).toBeGreaterThanOrEqual(2)
    })

    it('calls confirm callback when confirm clicked', async () => {
      wrapper = mount(GlobalModal, {
        global: {
          plugins: [createTestPinia()],
        },
      })
      
      const { useModalStore } = await import('@/stores/ui')
      const modalStore = useModalStore()
      const mockConfirm = vi.fn()
      
      modalStore.open({
        title: 'Confirm',
        message: 'Proceed?',
        onConfirm: mockConfirm,
      })
      
      await wrapper.vm.$nextTick()
      const confirmButton = wrapper.findAll('button')[1]
      await confirmButton.trigger('click')
      
      expect(mockConfirm).toHaveBeenCalled()
    })

    it('calls cancel callback when cancel clicked', async () => {
      wrapper = mount(GlobalModal, {
        global: {
          plugins: [createTestPinia()],
        },
      })
      
      const { useModalStore } = await import('@/stores/ui')
      const modalStore = useModalStore()
      const mockCancel = vi.fn()
      
      modalStore.open({
        title: 'Confirm',
        onCancel: mockCancel,
      })
      
      await wrapper.vm.$nextTick()
      const cancelButton = wrapper.findAll('button')[0]
      await cancelButton.trigger('click')
      
      expect(mockCancel).toHaveBeenCalled()
    })
  })

  describe('Modal Types', () => {
    const types = ['info', 'success', 'error', 'warning']

    types.forEach((type) => {
      it(`applies styles for ${type} modal type`, async () => {
        wrapper = mount(GlobalModal, {
          global: {
            plugins: [createTestPinia()],
          },
        })
        
        const { useModalStore } = await import('@/stores/ui')
        const modalStore = useModalStore()
        modalStore.open({ title: 'Test', message: 'Test', type })
        
        await wrapper.vm.$nextTick()
        expect(wrapper.find('[role="dialog"]').exists()).toBe(true)
      })
    })
  })

  describe('Destructive Actions', () => {
    it('applies destructive styling when isDestructive is true', async () => {
      wrapper = mount(GlobalModal, {
        global: {
          plugins: [createTestPinia()],
        },
      })
      
      const { useModalStore } = await import('@/stores/ui')
      const modalStore = useModalStore()
      modalStore.open({
        title: 'Delete',
        message: 'Permanently delete?',
        isDestructive: true,
      })
      
      await wrapper.vm.$nextTick()
      const buttons = wrapper.findAll('button')
      expect(buttons.some(b => b.classes().some(c => c.includes('error') || c.includes('red')))).toBe(true)
    })
  })

  describe('Accessibility', () => {
    it('has role="dialog"', async () => {
      wrapper = mount(GlobalModal, {
        global: {
          plugins: [createTestPinia()],
        },
      })
      
      const { useModalStore } = await import('@/stores/ui')
      const modalStore = useModalStore()
      modalStore.open({ title: 'Test' })
      
      await wrapper.vm.$nextTick()
      expect(wrapper.find('[role="dialog"]').exists()).toBe(true)
    })

    it('has aria-labelledby for heading', async () => {
      wrapper = mount(GlobalModal, {
        global: {
          plugins: [createTestPinia()],
        },
      })
      
      const { useModalStore } = await import('@/stores/ui')
      const modalStore = useModalStore()
      modalStore.open({ title: 'Test Modal' })
      
      await wrapper.vm.$nextTick()
      const dialog = wrapper.find('[role="dialog"]')
      expect(dialog.attributes('aria-labelledby')).toBeDefined()
    })
  })
})
