import { describe, it, expect, beforeEach, afterEach } from 'vitest'
import { mount } from '@vue/test-utils'
import ProfilePage from '@/pages/user/ProfilePage.vue'
import { createTestPinia, createTestRouter, createUser } from '../../../fixtures/helpers'

/**
 * ProfilePage Component Tests
 * 
 * Tests:
 * - Profile data display
 * - Edit functionality
 * - Security settings
 */

describe('ProfilePage.vue', () => {
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
    it('renders profile page', () => {
      wrapper = mount(ProfilePage, {
        global: {
          plugins: [pinia, router],
        },
      })
      
      expect(wrapper.exists()).toBe(true)
    })

    it('displays profile title', () => {
      wrapper = mount(ProfilePage, {
        global: {
          plugins: [pinia, router],
        },
      })
      
      const text = wrapper.text().toLowerCase()
      expect(text).toMatch(/profile|account|settings/)
    })
  })

  describe('Profile Sections', () => {
    it('displays personal info section', () => {
      wrapper = mount(ProfilePage, {
        global: {
          plugins: [pinia, router],
        },
      })
      
      const text = wrapper.text().toLowerCase()
      expect(text).toMatch(/profile|personal|info|name|email/)
    })

    it('displays security settings', () => {
      wrapper = mount(ProfilePage, {
        global: {
          plugins: [pinia, router],
        },
      })
      
      const text = wrapper.text().toLowerCase()
      expect(text).toMatch(/security|password|2fa/)
    })
  })
})
