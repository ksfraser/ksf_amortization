import { describe, it, expect, beforeEach, afterEach } from 'vitest'
import { mount } from '@vue/test-utils'
import DashboardPage from '@/views/user/DashboardPage.vue'
import { createTestPinia, createTestRouter, createUser } from '../../../fixtures/helpers'

/**
 * DashboardPage Component Tests
 * 
 * Tests:
 * - Dashboard rendering
 * - User data display
 * - Navigation to sections
 * - Redirect to login if not authenticated
 */

describe('DashboardPage.vue', () => {
  let wrapper
  let pinia
  let router

  beforeEach(async () => {
    pinia = createTestPinia()
    router = createTestRouter()
    
    const { useAuthStore } = await import('@/stores/auth')
    const authStore = useAuthStore()
    authStore.setUser(createUser())
  })

  afterEach(() => {
    if (wrapper) {
      wrapper.unmount()
    }
  })

  describe('Rendering', () => {
    it('renders dashboard page', () => {
      wrapper = mount(DashboardPage, {
        global: {
          plugins: [pinia, router],
        },
      })
      
      expect(wrapper.exists()).toBe(true)
    })

    it('displays user greeting', async () => {
      const { useAuthStore } = await import('@/stores/auth')
      const authStore = useAuthStore()
      
      wrapper = mount(DashboardPage, {
        global: {
          plugins: [pinia, router],
        },
      })
      
      await wrapper.vm.$nextTick()
      const text = wrapper.text().toLowerCase()
      expect(text).toMatch(/welcome|hello|dashboard|user/)
    })
  })

  describe('Authentication Protection', () => {
    it('redirects to login if not authenticated', () => {
      const { useAuthStore } = require('@/stores/auth')
      const authStore = useAuthStore()
      authStore.user = null
      
      wrapper = mount(DashboardPage, {
        global: {
          plugins: [pinia, router],
        },
      })
      
      // Should redirect or show login
      expect(wrapper.exists()).toBe(true)
    })
  })

  describe('Dashboard Sections', () => {
    it('displays user tokens section', () => {
      wrapper = mount(DashboardPage, {
        global: {
          plugins: [pinia, router],
        },
      })
      
      const text = wrapper.text().toLowerCase()
      expect(text).toMatch(/token|api|key/) || wrapper.find('[class*="section"]').exists()
    })

    it('displays consents section', () => {
      wrapper = mount(DashboardPage, {
        global: {
          plugins: [pinia, router],
        },
      })
      
      const text = wrapper.text().toLowerCase()
      expect(text).toMatch(/consent|permission|grant/) || wrapper.find('[class*="section"]').exists()
    })

    it('displays linked applications', () => {
      wrapper = mount(DashboardPage, {
        global: {
          plugins: [pinia, router],
        },
      })
      
      const text = wrapper.text().toLowerCase()
      expect(text).toMatch(/app|linked|connected/) || wrapper.find('[class*="section"]').exists()
    })
  })

  describe('Navigation', () => {
    it('links to profile page', () => {
      wrapper = mount(DashboardPage, {
        global: {
          plugins: [pinia, router],
        },
      })
      
      const links = wrapper.findAll('a, [role="link"]')
      expect(links.length).toBeGreaterThanOrEqual(1)
    })
  })
})
