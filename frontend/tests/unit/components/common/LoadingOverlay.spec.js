import { describe, it, expect, beforeEach, afterEach } from 'vitest'
import { mount } from '@vue/test-utils'
import LoadingOverlay from '@/components/common/LoadingOverlay.vue'

/**
 * LoadingOverlay Component Tests
 * 
 * Tests:
 * - Visibility based on isLoading prop
 * - Message display
 * - Spinner/loader animation
 * - Accessibility
 * - Transitions
 */

describe('LoadingOverlay.vue', () => {
  let wrapper

  afterEach(() => {
    if (wrapper) {
      wrapper.unmount()
    }
  })

  describe('Rendering', () => {
    it('renders overlay when isLoading is true', () => {
      wrapper = mount(LoadingOverlay, {
        props: {
          isLoading: true,
        },
      })
      
      expect(wrapper.find('.overlay').exists()).toBe(true)
    })

    it('does not render overlay when isLoading is false', () => {
      wrapper = mount(LoadingOverlay, {
        props: {
          isLoading: false,
        },
      })
      
      expect(wrapper.find('.overlay').exists()).toBe(false)
    })

    it('displays spinner when loading', () => {
      wrapper = mount(LoadingOverlay, {
        props: {
          isLoading: true,
        },
      })
      
      const spinner = wrapper.find('[class*="spinner"], [class*="loader"], svg')
      expect(spinner.exists()).toBe(true)
    })
  })

  describe('Message Display', () => {
    it('displays message when provided', () => {
      wrapper = mount(LoadingOverlay, {
        props: {
          isLoading: true,
          message: 'Loading data...',
        },
      })
      
      expect(wrapper.text()).toContain('Loading data...')
    })

    it('displays default message when not provided', () => {
      wrapper = mount(LoadingOverlay, {
        props: {
          isLoading: true,
        },
      })
      
      const text = wrapper.text().toLowerCase()
      expect(text).toMatch(/loading|please wait/)
    })

    it('does not display message when isLoading is false', () => {
      wrapper = mount(LoadingOverlay, {
        props: {
          isLoading: false,
          message: 'Loading data...',
        },
      })
      
      expect(wrapper.text()).not.toContain('Loading data...')
    })
  })

  describe('Loading States', () => {
    it('shows overlay with message for loading state', () => {
      wrapper = mount(LoadingOverlay, {
        props: {
          isLoading: true,
          message: 'Fetching records...',
        },
      })
      
      const overlay = wrapper.find('.overlay')
      expect(overlay.exists()).toBe(true)
      expect(wrapper.text()).toContain('Fetching records...')
    })

    it('hides overlay when loading completes', async () => {
      wrapper = mount(LoadingOverlay, {
        props: {
          isLoading: true,
        },
      })
      
      expect(wrapper.find('.overlay').exists()).toBe(true)
      
      await wrapper.setProps({ isLoading: false })
      expect(wrapper.find('.overlay').exists()).toBe(false)
    })

    it('supports updating message while loading', async () => {
      wrapper = mount(LoadingOverlay, {
        props: {
          isLoading: true,
          message: 'Step 1...',
        },
      })
      
      expect(wrapper.text()).toContain('Step 1...')
      
      await wrapper.setProps({ message: 'Step 2...' })
      expect(wrapper.text()).toContain('Step 2...')
    })
  })

  describe('Transitions', () => {
    it('has smooth fade transition', () => {
      wrapper = mount(LoadingOverlay, {
        props: {
          isLoading: true,
        },
      })
      
      const overlay = wrapper.find('.overlay')
      const classes = overlay.classes()
      expect(classes).toContain('transition') || expect(classes.join(' ')).toMatch(/fade|opacity/)
    })

    it('applies opacity classes for visibility', () => {
      wrapper = mount(LoadingOverlay, {
        props: {
          isLoading: true,
        },
      })
      
      const overlay = wrapper.find('.overlay')
      const classes = overlay.classes().join(' ')
      expect(classes).toMatch(/opacity|visible|show/)
    })
  })

  describe('Overlay Styling', () => {
    it('applies fixed positioning', () => {
      wrapper = mount(LoadingOverlay, {
        props: {
          isLoading: true,
        },
      })
      
      const overlay = wrapper.find('.overlay')
      expect(overlay.classes()).toContain('fixed')
    })

    it('covers full screen', () => {
      wrapper = mount(LoadingOverlay, {
        props: {
          isLoading: true,
        },
      })
      
      const overlay = wrapper.find('.overlay')
      const classes = overlay.classes().join(' ')
      expect(classes).toMatch(/inset-0|top-0|left-0|right-0|bottom-0/)
    })

    it('has semi-transparent background', () => {
      wrapper = mount(LoadingOverlay, {
        props: {
          isLoading: true,
        },
      })
      
      const overlay = wrapper.find('.overlay')
      const classes = overlay.classes().join(' ')
      expect(classes).toMatch(/bg-black|bg-gray|opacity-[0-9]/)
    })
  })

  describe('Spinner Animation', () => {
    it('has animated spinner', () => {
      wrapper = mount(LoadingOverlay, {
        props: {
          isLoading: true,
        },
      })
      
      const spinner = wrapper.find('[class*="spinner"], [class*="animate"]')
      expect(spinner.exists() || wrapper.html().includes('animate')).toBe(true)
    })

    it('displays centered spinner', () => {
      wrapper = mount(LoadingOverlay, {
        props: {
          isLoading: true,
        },
      })
      
      const overlay = wrapper.find('.overlay')
      const classes = overlay.classes().join(' ')
      expect(classes).toMatch(/flex|center|justify/)
    })
  })

  describe('Accessibility', () => {
    it('has role for loading indicator', () => {
      wrapper = mount(LoadingOverlay, {
        props: {
          isLoading: true,
        },
      })
      
      const overlay = wrapper.find('[role="status"], [role="progressbar"], [aria-busy="true"]')
      expect(overlay.exists()).toBe(true)
    })

    it('sets aria-busy when loading', () => {
      wrapper = mount(LoadingOverlay, {
        props: {
          isLoading: true,
        },
      })
      
      expect(wrapper.find('[aria-busy="true"]').exists()).toBe(true)
    })

    it('updates aria-busy when loading state changes', async () => {
      wrapper = mount(LoadingOverlay, {
        props: {
          isLoading: true,
        },
      })
      
      expect(wrapper.find('[aria-busy="true"]').exists()).toBe(true)
      
      await wrapper.setProps({ isLoading: false })
      expect(wrapper.find('[aria-busy="false"]').exists()).toBe(true)
    })
  })

  describe('Content Visibility', () => {
    it('prevents interaction with content when loading', () => {
      wrapper = mount(LoadingOverlay, {
        props: {
          isLoading: true,
        },
      })
      
      const overlay = wrapper.find('.overlay')
      const classes = overlay.classes().join(' ')
      expect(classes).toMatch(/pointer-events/)
    })

    it('has high z-index to cover content', () => {
      wrapper = mount(LoadingOverlay, {
        props: {
          isLoading: true,
        },
      })
      
      const overlay = wrapper.find('.overlay')
      const classes = overlay.classes().join(' ')
      expect(classes).toMatch(/z-\d+|z-overlay|z-\[9999\]/)
    })
  })

  describe('Props Update', () => {
    it('reacts to isLoading prop changes', async () => {
      wrapper = mount(LoadingOverlay, {
        props: {
          isLoading: false,
        },
      })
      
      expect(wrapper.find('.overlay').exists()).toBe(false)
      
      await wrapper.setProps({ isLoading: true })
      expect(wrapper.find('.overlay').exists()).toBe(true)
    })

    it('handles rapid toggle', async () => {
      wrapper = mount(LoadingOverlay, {
        props: {
          isLoading: false,
        },
      })
      
      await wrapper.setProps({ isLoading: true })
      await wrapper.setProps({ isLoading: false })
      await wrapper.setProps({ isLoading: true })
      
      expect(wrapper.find('.overlay').exists()).toBe(true)
    })
  })
})
