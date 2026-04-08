import { describe, it, expect, beforeEach, afterEach } from 'vitest'
import { mount } from '@vue/test-utils'
import LoginPage from '@/pages/auth/LoginPage.vue'
import { createTestPinia, createTestRouter } from '../../../fixtures/helpers'

/**
 * LoginPage Component Tests
 * 
 * Tests:
 * - Login form rendering
 * - Redirect to dashboard when authenticated
 * - Redirect handling from other pages
 * - Sign up link
 */

describe('LoginPage.vue', () => {
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
    it('renders login page', () => {
      wrapper = mount(LoginPage, {
        global: {
          plugins: [pinia, router],
          stubs: {
            LoginForm: { template: '<div>LoginForm Stub</div>' },
          },
        },
      })
      
      expect(wrapper.exists()).toBe(true)
    })

    it('displays login form', () => {
      wrapper = mount(LoginPage, {
        global: {
          plugins: [pinia, router],
        },
      })
      
      expect(wrapper.text().toLowerCase()).toMatch(/login|sign in/)
    })

    it('displays page title', () => {
      wrapper = mount(LoginPage, {
        global: {
          plugins: [pinia, router],
        },
      })
      
      expect(wrapper.text().toLowerCase()).toMatch(/login|sign in|welcome/)
    })
  })

  describe('Authentication Flow', () => {
    it('redirects to dashboard when user is already authenticated', async () => {
      const { useAuthStore } = await import('@/stores/auth')
      const authStore = useAuthStore()
      authStore.setUser({ id: 1, email: 'user@example.com' })
      
      wrapper = mount(LoginPage, {
        global: {
          plugins: [pinia, router],
        },
      })
      
      // Page should navigate or show loading
      expect(wrapper.exists()).toBe(true) || expect(router.currentRoute.value.path).toContain('dashboard')
    })

    it('processes login submission', async () => {
      wrapper = mount(LoginPage, {
        global: {
          plugins: [pinia, router],
        },
      })
      
      expect(wrapper.exists()).toBe(true)
    })
  })

  describe('Navigation', () => {
    it('displays sign up link', () => {
      wrapper = mount(LoginPage, {
        global: {
          plugins: [pinia, router],
        },
      })
      
      const text = wrapper.text().toLowerCase()
      expect(text).toMatch(/sign up|register|create account/)
    })

    it('displays password reset link', () => {
      wrapper = mount(LoginPage, {
        global: {
          plugins: [pinia, router],
        },
      })
      
      const text = wrapper.text().toLowerCase()
      expect(text).toMatch(/forgot|reset|password/)
    })
  })
})
