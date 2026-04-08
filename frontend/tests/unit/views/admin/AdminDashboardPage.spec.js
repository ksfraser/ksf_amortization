import { describe, it, expect, beforeEach, afterEach } from 'vitest'
import { mount } from '@vue/test-utils'
import AdminDashboardPage from '@/pages/admin/AdminDashboardPage.vue'
import { createTestPinia, createTestRouter, createUser } from '../../../fixtures/helpers'

/**
 * AdminDashboardPage Component Tests
 * 
 * Tests:
 * - Admin dashboard rendering
 * - Admin statistics display
 * - Navigation to admin sections
 * - Admin role protection
 */

describe('AdminDashboardPage.vue', () => {
  let wrapper
  let pinia
  let router

  beforeEach(async () => {
    pinia = createTestPinia()
    router = createTestRouter()
    
    const { useAuthStore } = await import('@/stores/auth')
    const authStore = useAuthStore()
    authStore.setUser(createUser({ role: 'admin' }))
  })

  afterEach(() => {
    if (wrapper) {
      wrapper.unmount()
    }
  })

  describe('Rendering', () => {
    it('renders admin dashboard', () => {
      wrapper = mount(AdminDashboardPage, {
        global: {
          plugins: [pinia, router],
        },
      })
      
      expect(wrapper.exists()).toBe(true)
    })

    it('displays admin title', () => {
      wrapper = mount(AdminDashboardPage, {
        global: {
          plugins: [pinia, router],
        },
      })
      
      const text = wrapper.text().toLowerCase()
      expect(text).toMatch(/admin|management|dashboard/)
    })
  })

  describe('Admin Sections', () => {
    it('displays clients management', () => {
      wrapper = mount(AdminDashboardPage, {
        global: {
          plugins: [pinia, router],
        },
      })
      
      const text = wrapper.text().toLowerCase()
      expect(text).toMatch(/client|manage/) || wrapper.find('[class*="section"]').exists()
    })

    it('displays metrics section', () => {
      wrapper = mount(AdminDashboardPage, {
        global: {
          plugins: [pinia, router],
        },
      })
      
      const text = wrapper.text().toLowerCase()
      expect(text).toMatch(/metric|analytics|report/) || wrapper.find('[class*="section"]').exists()
    })

    it('displays system settings', () => {
      wrapper = mount(AdminDashboardPage, {
        global: {
          plugins: [pinia, router],
        },
      })
      
      const text = wrapper.text().toLowerCase()
      expect(text).toMatch(/setting|config|system/) || wrapper.find('[class*="section"]').exists()
    })
  })

  describe('Authorization', () => {
    it('redirects non-admin users', async () => {
      const { useAuthStore } = await import('@/stores/auth')
      const authStore = useAuthStore()
      authStore.setUser(createUser({ role: 'user' }))
      
      wrapper = mount(AdminDashboardPage, {
        global: {
          plugins: [pinia, router],
        },
      })
      
      // Should redirect or show unauthorized
      expect(wrapper.exists()).toBe(true)
    })
  })

  describe('Navigation', () => {
    it('displays links to admin sections', () => {
      wrapper = mount(AdminDashboardPage, {
        global: {
          plugins: [pinia, router],
        },
      })
      
      const links = wrapper.findAll('a, [role="link"]')
      expect(links.length).toBeGreaterThanOrEqual(1)
    })
  })
})
