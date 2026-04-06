import { describe, it, expect, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import Button from '@/components/common/Button.vue'

/**
 * Button Component Tests
 * 
 * Tests:
 * - Props validation (variant, size, disabled, loading)
 * - Event emissions (click)
 * - Visual states
 * - Loading spinner
 * - Accessibility
 */

describe('Button.vue', () => {
  let wrapper

  beforeEach(() => {
    wrapper = null
  })

  afterEach(() => {
    if (wrapper) {
      wrapper.unmount()
    }
  })

  describe('Rendering', () => {
    it('renders button element', () => {
      wrapper = mount(Button, {
        slots: {
          default: 'Click me',
        },
      })

      expect(wrapper.find('button').exists()).toBe(true)
      expect(wrapper.text()).toContain('Click me')
    })

    it('renders with default slot content', () => {
      wrapper = mount(Button, {
        slots: {
          default: 'Test Button',
        },
      })

      expect(wrapper.text()).toBe('Test Button')
    })
  })

  describe('Props: Variants', () => {
    it('applies primary variant class', () => {
      wrapper = mount(Button, {
        props: { variant: 'primary' },
        slots: { default: 'Primary' },
      })

      expect(wrapper.find('button').classes()).toContain('btn-primary')
    })

    it('applies secondary variant class', () => {
      wrapper = mount(Button, {
        props: { variant: 'secondary' },
        slots: { default: 'Secondary' },
      })

      expect(wrapper.find('button').classes()).toContain('btn-secondary')
    })

    it('applies success variant class', () => {
      wrapper = mount(Button, {
        props: { variant: 'success' },
        slots: { default: 'Success' },
      })

      expect(wrapper.find('button').classes()).toContain('btn-success')
    })

    it('applies error variant class', () => {
      wrapper = mount(Button, {
        props: { variant: 'error' },
        slots: { default: 'Error' },
      })

      expect(wrapper.find('button').classes()).toContain('btn-danger')
    })

    it('applies warning variant class', () => {
      wrapper = mount(Button, {
        props: { variant: 'warning' },
        slots: { default: 'Warning' },
      })

      expect(wrapper.find('button').classes()).toContain('btn-warning')
    })
  })

  describe('Props: Sizes', () => {
    it('applies small size class', () => {
      wrapper = mount(Button, {
        props: { size: 'sm' },
        slots: { default: 'Small' },
      })

      expect(wrapper.find('button').classes()).toContain('btn-sm')
    })

    it('applies medium (default) size', () => {
      wrapper = mount(Button, {
        slots: { default: 'Medium' },
      })

      expect(wrapper.find('button').classes()).toContain('btn-md')
    })

    it('applies large size class', () => {
      wrapper = mount(Button, {
        props: { size: 'lg' },
        slots: { default: 'Large' },
      })

      expect(wrapper.find('button').classes()).toContain('btn-lg')
    })
  })

  describe('Props: Disabled State', () => {
    it('sets disabled attribute when disabled prop is true', () => {
      wrapper = mount(Button, {
        props: { disabled: true },
        slots: { default: 'Disabled' },
      })

      expect(wrapper.find('button').attributes('disabled')).toBeDefined()
    })

    it('does not set disabled when prop is false', () => {
      wrapper = mount(Button, {
        props: { disabled: false },
        slots: { default: 'Enabled' },
      })

      expect(wrapper.find('button').attributes('disabled')).toBeUndefined()
    })

    it('prevents click when disabled', async () => {
      wrapper = mount(Button, {
        props: { disabled: true },
        slots: { default: 'Disabled' },
      })

      await wrapper.find('button').trigger('click')
      expect(wrapper.emitted('click')).toBeFalsy()
    })

    it('applies disabled styling', () => {
      wrapper = mount(Button, {
        props: { disabled: true },
        slots: { default: 'Disabled' },
      })

      expect(wrapper.find('button').classes()).toContain('opacity-50')
      expect(wrapper.find('button').classes()).toContain('cursor-not-allowed')
    })
  })

  describe('Props: Loading State', () => {
    it('renders loading spinner when loading is true', () => {
      wrapper = mount(Button, {
        props: { loading: true },
        slots: { default: 'Loading' },
      })

      expect(wrapper.find('svg').exists()).toBe(true)
    })

    it('does not render spinner when loading is false', () => {
      wrapper = mount(Button, {
        props: { loading: false },
        slots: { default: 'Not Loading' },
      })

      // Check for animate-spin class indicates loading
      const button = wrapper.find('button')
      expect(button.classes().some(c => c.includes('animate'))).toBe(false)
    })

    it('disables button when loading', () => {
      wrapper = mount(Button, {
        props: { loading: true },
        slots: { default: 'Loading' },
      })

      expect(wrapper.find('button').attributes('disabled')).toBeDefined()
    })

    it('disables button when both loading and disabled are true', () => {
      wrapper = mount(Button, {
        props: { loading: true, disabled: true },
        slots: { default: 'Busy' },
      })

      expect(wrapper.find('button').attributes('disabled')).toBeDefined()
    })
  })

  describe('Events', () => {
    it('emits click event when clicked', async () => {
      wrapper = mount(Button, {
        props: { disabled: false },
        slots: { default: 'Click' },
      })

      await wrapper.find('button').trigger('click')
      expect(wrapper.emitted('click')).toBeTruthy()
    })

    it('emits click event with correct count', async () => {
      wrapper = mount(Button, {
        slots: { default: 'Click' },
      })

      await wrapper.find('button').trigger('click')
      await wrapper.find('button').trigger('click')
      await wrapper.find('button').trigger('click')

      expect(wrapper.emitted('click')).toHaveLength(3)
    })

    it('does not emit click when disabled', async () => {
      wrapper = mount(Button, {
        props: { disabled: true },
        slots: { default: 'Click' },
      })

      await wrapper.find('button').trigger('click')
      expect(wrapper.emitted('click')).toBeFalsy()
    })

    it('does not emit click when loading', async () => {
      wrapper = mount(Button, {
        props: { loading: true },
        slots: { default: 'Loading' },
      })

      await wrapper.find('button').trigger('click')
      expect(wrapper.emitted('click')).toBeFalsy()
    })
  })

  describe('Accessibility', () => {
    it('has type=button attribute', () => {
      wrapper = mount(Button, {
        slots: { default: 'Button' },
      })

      expect(wrapper.find('button').attributes('type')).toBe('button')
    })

    it('supports aria-label prop', () => {
      wrapper = mount(Button, {
        props: { 'aria-label': 'Close dialog' },
        slots: { default: '×' },
      })

      expect(wrapper.find('button').attributes('aria-label')).toBe('Close dialog')
    })

    it('has keyboard navigation support', async () => {
      wrapper = mount(Button, {
        slots: { default: 'Keyboard' },
      })

      const button = wrapper.find('button')
      expect(button.element.tabIndex === -1 || button.element.tabIndex === 0).toBe(true)
    })

    it('indicates loading state with aria-busy', () => {
      wrapper = mount(Button, {
        props: { loading: true },
        slots: { default: 'Loading' },
      })

      expect(wrapper.find('button').attributes('aria-busy')).toBe('true')
    })

    it('indicates disabled state with aria-disabled', () => {
      wrapper = mount(Button, {
        props: { disabled: true },
        slots: { default: 'Disabled' },
      })

      expect(wrapper.find('button').attributes('aria-disabled')).toBe('true')
    })
  })

  describe('Edge Cases', () => {
    it('handles empty slot', () => {
      wrapper = mount(Button, {
        slots: { default: '' },
      })

      expect(wrapper.find('button').exists()).toBe(true)
    })

    it('handles multiple prop combinations', () => {
      wrapper = mount(Button, {
        props: {
          variant: 'success',
          size: 'lg',
          disabled: false,
          loading: false,
        },
        slots: { default: 'Complex' },
      })

      expect(wrapper.find('button').exists()).toBe(true)
      expect(wrapper.text()).toBe('Complex')
    })

    it('updates when props change', async () => {
      wrapper = mount(Button, {
        props: { disabled: false },
        slots: { default: 'Toggle' },
      })

      expect(wrapper.find('button').attributes('disabled')).toBeUndefined()

      await wrapper.setProps({ disabled: true })
      expect(wrapper.find('button').attributes('disabled')).toBeDefined()
    })
  })
})
