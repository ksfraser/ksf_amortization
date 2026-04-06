import { describe, it, expect, beforeEach, afterEach } from 'vitest'
import { mount } from '@vue/test-utils'
import Alert from '@/components/common/Alert.vue'

/**
 * Alert Component Tests
 * 
 * Tests:
 * - Alert types (success, error, warning, info)
 * - Title and message display
 * - Close button functionality
 * - Auto-dismiss
 * - Icon rendering
 */

describe('Alert.vue', () => {
  let wrapper

  afterEach(() => {
    if (wrapper) {
      wrapper.unmount()
    }
  })

  describe('Rendering', () => {
    it('renders alert with default slot', () => {
      wrapper = mount(Alert, {
        props: { type: 'info', title: 'Info' },
        slots: { default: 'Alert message' },
      })
      expect(wrapper.find('[role="alert"]').exists()).toBe(true)
      expect(wrapper.text()).toContain('Alert message')
    })

    it('displays title when provided', () => {
      wrapper = mount(Alert, {
        props: { type: 'success', title: 'Success!' },
      })
      expect(wrapper.text()).toContain('Success!')
    })

    it('displays message prop', () => {
      wrapper = mount(Alert, {
        props: { type: 'error', message: 'Error occurred' },
      })
      expect(wrapper.text()).toContain('Error occurred')
    })
  })

  describe('Alert Types', () => {
    const types = [
      { type: 'success', color: 'bg-success-50', border: 'border-success' },
      { type: 'error', color: 'bg-error-50', border: 'border-error' },
      { type: 'warning', color: 'bg-warning-50', border: 'border-warning' },
      { type: 'info', color: 'bg-primary-50', border: 'border-primary' },
    ]

    types.forEach(({ type, color, border }) => {
      it(`applies correct styles for ${type} type`, () => {
        wrapper = mount(Alert, {
          props: { type, title: 'Test' },
        })
        expect(wrapper.find('[role="alert"]').classes()).toContain(color)
      })

      it(`renders correct icon for ${type} type`, () => {
        wrapper = mount(Alert, {
          props: { type, title: 'Test' },
        })
        expect(wrapper.find('svg').exists()).toBe(true)
      })
    })
  })

  describe('Close Button', () => {
    it('shows close button when closable is true', () => {
      wrapper = mount(Alert, {
        props: { type: 'info', title: 'Test', closable: true },
      })
      expect(wrapper.find('button').exists()).toBe(true)
    })

    it('hides close button when closable is false', () => {
      wrapper = mount(Alert, {
        props: { type: 'info', title: 'Test', closable: false },
      })
      expect(wrapper.find('button').exists()).toBe(false)
    })

    it('emits close event when close button clicked', async () => {
      wrapper = mount(Alert, {
        props: { type: 'info', title: 'Test', closable: true },
      })
      await wrapper.find('button').trigger('click')
      expect(wrapper.emitted('close')).toBeTruthy()
    })
  })

  describe('Auto-dismiss', () => {
    it('auto-closes after delay when autoClose is set', async () => {
      vi.useFakeTimers()
      wrapper = mount(Alert, {
        props: { type: 'success', title: 'Success', autoClose: 2000 },
      })
      
      expect(wrapper.find('[role="alert"]').exists()).toBe(true)
      vi.advanceTimersByTime(2000)
      await wrapper.vm.$nextTick()
      
      expect(wrapper.emitted('close')).toBeTruthy()
      vi.useRealTimers()
    })

    it('does not auto-close when autoClose is false', async () => {
      vi.useFakeTimers()
      wrapper = mount(Alert, {
        props: { type: 'info', title: 'Info', autoClose: false },
      })
      
      vi.advanceTimersByTime(5000)
      expect(wrapper.emitted('close')).toBeFalsy()
      vi.useRealTimers()
    })
  })

  describe('Accessibility', () => {
    it('has proper role="alert"', () => {
      wrapper = mount(Alert, {
        props: { type: 'warning', title: 'Warning' },
      })
      expect(wrapper.find('[role="alert"]').exists()).toBe(true)
    })

    it('close button has aria-label', () => {
      wrapper = mount(Alert, {
        props: { type: 'info', title: 'Test', closable: true },
      })
      const button = wrapper.find('button')
      expect(button.attributes('aria-label')).toMatch(/close|dismiss/i)
    })
  })

  describe('Props Defaults', () => {
    it('uses default props when not provided', () => {
      wrapper = mount(Alert, {
        props: { type: 'info' },
      })
      expect(wrapper.find('[role="alert"]').exists()).toBe(true)
    })
  })
})
