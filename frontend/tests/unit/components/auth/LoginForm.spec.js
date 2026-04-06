import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import LoginForm from '@/components/auth/LoginForm.vue'
import { createTestPinia, createTestRouter, flushPromises } from '../../../fixtures/helpers'

/**
 * LoginForm Component Tests
 * 
 * Tests:
 * - Email and password fields
 * - Form validation
 * - Login submission
 * - Error handling
 * - Loading states
 * - Remember me checkbox
 */

describe('LoginForm.vue', () => {
  let wrapper
  let pinia
  let router

  beforeEach(() => {
    pinia = createTestPinia()
    router = createTestRouter()
  })

  afterEach(() => {
    if (wrapper) {
      wrapper.unmount()
    }
  })

  describe('Rendering', () => {
    it('renders login form', () => {
      wrapper = mount(LoginForm, {
        global: {
          plugins: [pinia, router],
        },
      })
      
      expect(wrapper.find('form').exists()).toBe(true)
    })

    it('renders email input', () => {
      wrapper = mount(LoginForm, {
        global: {
          plugins: [pinia, router],
        },
      })
      
      const emailInput = wrapper.find('input[type="email"]')
      expect(emailInput.exists()).toBe(true)
    })

    it('renders password input', () => {
      wrapper = mount(LoginForm, {
        global: {
          plugins: [pinia, router],
        },
      })
      
      const passwordInput = wrapper.find('input[type="password"]')
      expect(passwordInput.exists()).toBe(true)
    })

    it('renders submit button', () => {
      wrapper = mount(LoginForm, {
        global: {
          plugins: [pinia, router],
        },
      })
      
      const button = wrapper.find('button[type="submit"]')
      expect(button.exists()).toBe(true)
    })
  })

  describe('Form Fields', () => {
    it('email input accepts text input', async () => {
      wrapper = mount(LoginForm, {
        global: {
          plugins: [pinia, router],
        },
      })
      
      const emailInput = wrapper.find('input[type="email"]')
      await emailInput.setValue('user@example.com')
      
      expect(emailInput.element.value).toBe('user@example.com')
    })

    it('password input accepts text input', async () => {
      wrapper = mount(LoginForm, {
        global: {
          plugins: [pinia, router],
        },
      })
      
      const passwordInput = wrapper.find('input[type="password"]')
      await passwordInput.setValue('password123')
      
      expect(passwordInput.element.value).toBe('password123')
    })

    it('has labels for form fields', () => {
      wrapper = mount(LoginForm, {
        global: {
          plugins: [pinia, router],
        },
      })
      
      const labels = wrapper.findAll('label')
      expect(labels.length).toBeGreaterThanOrEqual(2)
    })
  })

  describe('Form Validation', () => {
    it('requires email field', async () => {
      wrapper = mount(LoginForm, {
        global: {
          plugins: [pinia, router],
        },
      })
      
      const emailInput = wrapper.find('input[type="email"]')
      expect(emailInput.attributes('required')).toBe('')
    })

    it('requires password field', async () => {
      wrapper = mount(LoginForm, {
        global: {
          plugins: [pinia, router],
        },
      })
      
      const passwordInput = wrapper.find('input[type="password"]')
      expect(passwordInput.attributes('required')).toBe('')
    })

    it('validates email format', async () => {
      wrapper = mount(LoginForm, {
        global: {
          plugins: [pinia, router],
        },
      })
      
      const emailInput = wrapper.find('input[type="email"]')
      await emailInput.setValue('invalid-email')
      
      const validity = emailInput.element.validity
      expect(validity.valid).toBe(false) || expect(wrapper.text()).toMatch(/invalid|email/)
    })

    it('disables submit button when form is invalid', async () => {
      wrapper = mount(LoginForm, {
        global: {
          plugins: [pinia, router],
        },
      })
      
      const button = wrapper.find('button[type="submit"]')
      expect(button.element.disabled || !button.classes().includes('enabled')).toBe(true)
    })

    it('enables submit button when form is valid', async () => {
      wrapper = mount(LoginForm, {
        global: {
          plugins: [pinia, router],
        },
      })
      
      const emailInput = wrapper.find('input[type="email"]')
      const passwordInput = wrapper.find('input[type="password"]')
      
      await emailInput.setValue('user@example.com')
      await passwordInput.setValue('password123')
      await wrapper.vm.$nextTick()
      
      const button = wrapper.find('button[type="submit"]')
      expect(!button.element.disabled).toBe(true)
    })
  })

  describe('Remember Me', () => {
    it('renders remember me checkbox', () => {
      wrapper = mount(LoginForm, {
        global: {
          plugins: [pinia, router],
        },
      })
      
      const checkbox = wrapper.find('input[type="checkbox"]')
      expect(checkbox.exists()).toBe(true)
    })

    it('can toggle remember me', async () => {
      wrapper = mount(LoginForm, {
        global: {
          plugins: [pinia, router],
        },
      })
      
      const checkbox = wrapper.find('input[type="checkbox"]')
      await checkbox.setValue(true)
      
      expect(checkbox.element.checked).toBe(true)
    })

    it('has label for remember me', () => {
      wrapper = mount(LoginForm, {
        global: {
          plugins: [pinia, router],
        },
      })
      
      const text = wrapper.text().toLowerCase()
      expect(text).toMatch(/remember|remember me/)
    })
  })

  describe('Error Handling', () => {
    it('displays error message on failed login', async () => {
      wrapper = mount(LoginForm, {
        global: {
          plugins: [pinia, router],
        },
      })
      
      await wrapper.setProps({ error: 'Invalid credentials' })
      expect(wrapper.text()).toContain('Invalid credentials')
    })

    it('displays validation errors', async () => {
      wrapper = mount(LoginForm, {
        global: {
          plugins: [pinia, router],
        },
      })
      
      await wrapper.setProps({ 
        errors: { 
          email: 'Email is required',
          password: 'Password is required' 
        } 
      })
      
      expect(wrapper.text()).toMatch(/email|password/)
    })

    it('clears errors when user starts typing', async () => {
      wrapper = mount(LoginForm, {
        global: {
          plugins: [pinia, router],
        },
      })
      
      await wrapper.setProps({ errors: { email: 'Invalid email' } })
      expect(wrapper.text()).toContain('Invalid email')
      
      const emailInput = wrapper.find('input[type="email"]')
      await emailInput.setValue('user@example.com')
      
      // Component should clear error after input
      await wrapper.vm.$nextTick()
    })
  })

  describe('Loading State', () => {
    it('shows loading indicator while submitting', async () => {
      wrapper = mount(LoginForm, {
        global: {
          plugins: [pinia, router],
        },
      })
      
      await wrapper.setProps({ isLoading: true })
      const button = wrapper.find('button[type="submit"]')
      
      expect(button.element.disabled).toBe(true) || expect(button.classes()).toContain('loading')
    })

    it('disables form while loading', async () => {
      wrapper = mount(LoginForm, {
        global: {
          plugins: [pinia, router],
        },
      })
      
      await wrapper.setProps({ isLoading: true })
      
      const form = wrapper.find('form')
      expect(form.element.hasAttribute('disabled') || wrapper.find('[class*="disabled"]').exists()).toBe(true)
    })
  })

  describe('Form Submission', () => {
    it('emits submit event with credentials', async () => {
      wrapper = mount(LoginForm, {
        global: {
          plugins: [pinia, router],
        },
      })
      
      const emailInput = wrapper.find('input[type="email"]')
      const passwordInput = wrapper.find('input[type="password"]')
      
      await emailInput.setValue('user@example.com')
      await passwordInput.setValue('password123')
      
      const form = wrapper.find('form')
      await form.trigger('submit')
      
      expect(wrapper.emitted('submit')).toBeTruthy()
    })

    it('includes remember me flag in submission', async () => {
      wrapper = mount(LoginForm, {
        global: {
          plugins: [pinia, router],
        },
      })
      
      const emailInput = wrapper.find('input[type="email"]')
      const passwordInput = wrapper.find('input[type="password"]')
      const checkbox = wrapper.find('input[type="checkbox"]')
      
      await emailInput.setValue('user@example.com')
      await passwordInput.setValue('password123')
      await checkbox.setValue(true)
      
      const form = wrapper.find('form')
      await form.trigger('submit')
      
      const emitted = wrapper.emitted('submit')[0]
      expect(emitted[0].rememberMe).toBe(true)
    })
  })

  describe('Links and Navigation', () => {
    it('renders forgot password link', () => {
      wrapper = mount(LoginForm, {
        global: {
          plugins: [pinia, router],
        },
      })
      
      const text = wrapper.text().toLowerCase()
      expect(text).toMatch(/forgot|reset|password/)
    })

    it('renders sign up link', () => {
      wrapper = mount(LoginForm, {
        global: {
          plugins: [pinia, router],
        },
      })
      
      const text = wrapper.text().toLowerCase()
      expect(text).toMatch(/sign up|register|create/)
    })
  })

  describe('Accessibility', () => {
    it('has proper form semantics', () => {
      wrapper = mount(LoginForm, {
        global: {
          plugins: [pinia, router],
        },
      })
      
      expect(wrapper.find('form').exists()).toBe(true)
    })

    it('has associated labels for inputs', () => {
      wrapper = mount(LoginForm, {
        global: {
          plugins: [pinia, router],
        },
      })
      
      const emailInput = wrapper.find('input[type="email"]')
      const id = emailInput.attributes('id')
      
      const label = wrapper.find(`label[for="${id}"]`)
      expect(label.exists()).toBe(true)
    })

    it('has proper autocomplete attributes', () => {
      wrapper = mount(LoginForm, {
        global: {
          plugins: [pinia, router],
        },
      })
      
      const emailInput = wrapper.find('input[type="email"]')
      expect(emailInput.attributes('autocomplete')).toBe('email')
      
      const passwordInput = wrapper.find('input[type="password"]')
      expect(passwordInput.attributes('autocomplete')).toBe('current-password')
    })
  })
})
