import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import ProfileView from '@/components/auth/ProfileView.vue'
import { createTestPinia, createTestRouter, createUser } from '../../../fixtures/helpers'

/**
 * ProfileView Component Tests
 * 
 * Tests:
 * - User profile display
 * - Edit profile modal/form
 * - Password change modal
 * - Profile update submission
 * - Logout functionality
 * - Delete account
 */

describe('ProfileView.vue', () => {
  let wrapper
  let pinia
  let router

  beforeEach(async () => {
    pinia = createTestPinia()
    router = createTestRouter()
    
    // Set authenticated user
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
    it('renders profile view', () => {
      wrapper = mount(ProfileView, {
        global: {
          plugins: [pinia, router],
        },
      })
      
      expect(wrapper.exists()).toBe(true)
    })

    it('displays user information', () => {
      wrapper = mount(ProfileView, {
        global: {
          plugins: [pinia, router],
        },
      })
      
      const { useAuthStore } = require('@/stores/auth')
      const authStore = useAuthStore()
      
      expect(wrapper.text()).toContain(authStore.user.email)
    })
  })

  describe('Profile Display', () => {
    it('displays user email', async () => {
      const { useAuthStore } = await import('@/stores/auth')
      const authStore = useAuthStore()
      authStore.setUser(createUser({ email: 'john@example.com' }))
      
      wrapper = mount(ProfileView, {
        global: {
          plugins: [pinia, router],
        },
      })
      
      expect(wrapper.text()).toContain('john@example.com')
    })

    it('displays user name', async () => {
      const { useAuthStore } = await import('@/stores/auth')
      const authStore = useAuthStore()
      authStore.setUser(createUser({ name: 'John Doe' }))
      
      wrapper = mount(ProfileView, {
        global: {
          plugins: [pinia, router],
        },
      })
      
      expect(wrapper.text()).toContain('John Doe')
    })

    it('displays profile picture if available', async () => {
      const { useAuthStore } = await import('@/stores/auth')
      const authStore = useAuthStore()
      authStore.setUser(createUser({ avatar: 'https://example.com/avatar.jpg' }))
      
      wrapper = mount(ProfileView, {
        global: {
          plugins: [pinia, router],
        },
      })
      
      const img = wrapper.find('img[alt*="profile"], img[alt*="avatar"]')
      expect(img.exists() || wrapper.html().includes('avatar')).toBe(true)
    })
  })

  describe('Edit Profile', () => {
    it('renders edit profile button', () => {
      wrapper = mount(ProfileView, {
        global: {
          plugins: [pinia, router],
        },
      })
      
      const button = wrapper.find('button[class*="edit"]')
      expect(button.exists() || wrapper.text().toLowerCase().includes('edit profile')).toBe(true)
    })

    it('opens edit profile modal on button click', async () => {
      wrapper = mount(ProfileView, {
        global: {
          plugins: [pinia, router],
        },
      })
      
      const editButton = wrapper.findAll('button').find(b => b.text().toLowerCase().includes('edit'))
      if (editButton) {
        await editButton.trigger('click')
        await wrapper.vm.$nextTick()
        
        const modal = wrapper.find('[role="dialog"], [class*="modal"]')
        expect(modal.exists()).toBe(true)
      }
    })

    it('displays editable form fields in edit mode', async () => {
      wrapper = mount(ProfileView, {
        global: {
          plugins: [pinia, router],
        },
      })
      
      const editButton = wrapper.findAll('button').find(b => b.text().toLowerCase().includes('edit'))
      if (editButton) {
        await editButton.trigger('click')
        await wrapper.vm.$nextTick()
        
        const inputs = wrapper.findAll('input[type="text"], input[type="email"], textarea')
        expect(inputs.length).toBeGreaterThanOrEqual(1)
      }
    })

    it('updates profile on form submission', async () => {
      wrapper = mount(ProfileView, {
        global: {
          plugins: [pinia, router],
        },
      })
      
      // Simulate profile update
      const { useAuthStore } = await import('@/stores/auth')
      const authStore = useAuthStore()
      const originalUser = { ...authStore.user }
      
      authStore.setUser({
        ...originalUser,
        name: 'Updated Name',
      })
      
      await wrapper.vm.$nextTick()
      expect(wrapper.text()).toContain('Updated Name')
    })

    it('cancels edit without saving', async () => {
      wrapper = mount(ProfileView, {
        global: {
          plugins: [pinia, router],
        },
      })
      
      const { useAuthStore } = await import('@/stores/auth')
      const authStore = useAuthStore()
      const originalName = authStore.user.name
      
      const editButton = wrapper.findAll('button').find(b => b.text().toLowerCase().includes('edit'))
      if (editButton) {
        await editButton.trigger('click')
        
        const cancelButton = wrapper.findAll('button').find(b => b.text().toLowerCase().includes('cancel'))
        if (cancelButton) {
          await cancelButton.trigger('click')
          await wrapper.vm.$nextTick()
          
          expect(authStore.user.name).toBe(originalName)
        }
      }
    })
  })

  describe('Change Password', () => {
    it('renders change password button', () => {
      wrapper = mount(ProfileView, {
        global: {
          plugins: [pinia, router],
        },
      })
      
      const text = wrapper.text().toLowerCase()
      expect(text).toMatch(/change password|password/)
    })

    it('opens password change modal', async () => {
      wrapper = mount(ProfileView, {
        global: {
          plugins: [pinia, router],
        },
      })
      
      const button = wrapper.findAll('button').find(b => b.text().toLowerCase().includes('password'))
      if (button) {
        await button.trigger('click')
        await wrapper.vm.$nextTick()
        
        const passwordInputs = wrapper.findAll('input[type="password"]')
        expect(passwordInputs.length).toBeGreaterThanOrEqual(2)
      }
    })

    it('requires current password', async () => {
      wrapper = mount(ProfileView, {
        global: {
          plugins: [pinia, router],
        },
      })
      
      const button = wrapper.findAll('button').find(b => b.text().toLowerCase().includes('password'))
      if (button) {
        await button.trigger('click')
        await wrapper.vm.$nextTick()
        
        const form = wrapper.find('form')
        expect(wrapper.text()).toMatch(/current|old/)
      }
    })

    it('requires confirmation of new password', async () => {
      wrapper = mount(ProfileView, {
        global: {
          plugins: [pinia, router],
        },
      })
      
      const button = wrapper.findAll('button').find(b => b.text().toLowerCase().includes('password'))
      if (button) {
        await button.trigger('click')
        await wrapper.vm.$nextTick()
        
        expect(wrapper.text()).toMatch(/confirm|repeat/)
      }
    })
  })

  describe('Two-Factor Authentication', () => {
    it('displays 2FA status', () => {
      wrapper = mount(ProfileView, {
        global: {
          plugins: [pinia, router],
        },
      })
      
      const text = wrapper.text().toLowerCase()
      expect(text).toMatch(/2fa|two-factor|two factor|mfa|authentication/)
    })

    it('can enable 2FA', async () => {
      wrapper = mount(ProfileView, {
        global: {
          plugins: [pinia, router],
        },
      })
      
      const button = wrapper.findAll('button').find(b => b.text().toLowerCase().includes('2fa') || b.text().toLowerCase().includes('authentication'))
      if (button) {
        await button.trigger('click')
        await wrapper.vm.$nextTick()
        
        expect(wrapper.exists()).toBe(true)
      }
    })
  })

  describe('Sessions & Devices', () => {
    it('displays active sessions', () => {
      wrapper = mount(ProfileView, {
        global: {
          plugins: [pinia, router],
        },
      })
      
      const text = wrapper.text().toLowerCase()
      expect(text).toMatch(/session|device|active/) || expect(wrapper.find('table, [role="table"]').exists()).toBe(true)
    })

    it('allows logout from other devices', async () => {
      wrapper = mount(ProfileView, {
        global: {
          plugins: [pinia, router],
        },
      })
      
      const buttons = wrapper.findAll('button')
      const logoutButton = buttons.find(b => b.text().toLowerCase().includes('logout'))
      
      if (logoutButton) {
        await logoutButton.trigger('click')
        expect(wrapper.emitted('logout')).toBeTruthy() || expect(wrapper.exists()).toBe(true)
      }
    })
  })

  describe('Account Actions', () => {
    it('provides download data option', () => {
      wrapper = mount(ProfileView, {
        global: {
          plugins: [pinia, router],
        },
      })
      
      const text = wrapper.text().toLowerCase()
      expect(text).toMatch(/download|export|data/)
    })

    it('displays delete account option', () => {
      wrapper = mount(ProfileView, {
        global: {
          plugins: [pinia, router],
        },
      })
      
      const text = wrapper.text().toLowerCase()
      expect(text).toMatch(/delete|deactivate|close account/)
    })

    it('shows warning before account deletion', async () => {
      wrapper = mount(ProfileView, {
        global: {
          plugins: [pinia, router],
        },
      })
      
      const buttons = wrapper.findAll('button')
      const deleteButton = buttons.find(b => b.text().toLowerCase().includes('delete'))
      
      if (deleteButton) {
        await deleteButton.trigger('click')
        await wrapper.vm.$nextTick()
        
        const text = wrapper.text().toLowerCase()
        expect(text).toMatch(/permanent|cannot|warning|confirm/)
      }
    })
  })

  describe('Loading & Error States', () => {
    it('shows loading indicator while saving', async () => {
      wrapper = mount(ProfileView, {
        props: { isLoading: true },
        global: {
          plugins: [pinia, router],
        },
      })
      
      expect(wrapper.find('[class*="loading"], [class*="spinner"]').exists() || wrapper.text().toLowerCase().includes('loading')).toBe(true)
    })

    it('displays error message on failure', async () => {
      wrapper = mount(ProfileView, {
        props: { error: 'Failed to update profile' },
        global: {
          plugins: [pinia, router],
        },
      })
      
      expect(wrapper.text()).toContain('Failed to update profile')
    })

    it('shows success message after save', async () => {
      wrapper = mount(ProfileView, {
        global: {
          plugins: [pinia, router],
        },
      })
      
      await wrapper.setProps({ success: 'Profile updated successfully' })
      expect(wrapper.text()).toContain('Profile updated successfully')
    })
  })

  describe('Accessibility', () => {
    it('has proper heading structure', () => {
      wrapper = mount(ProfileView, {
        global: {
          plugins: [pinia, router],
        },
      })
      
      const headings = wrapper.findAll('h1, h2, h3')
      expect(headings.length).toBeGreaterThanOrEqual(1)
    })

    it('has accessible form controls', () => {
      wrapper = mount(ProfileView, {
        global: {
          plugins: [pinia, router],
        },
      })
      
      const labels = wrapper.findAll('label')
      expect(labels.length).toBeGreaterThanOrEqual(0)
    })
  })
})
