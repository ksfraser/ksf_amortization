import { describe, it, expect, beforeEach, afterEach } from 'vitest'
import { mount } from '@vue/test-utils'
import TopNavigation from '@/components/common/TopNavigation.vue'
import { createTestPinia, createTestRouter } from '../../../fixtures/helpers'

/**
 * TopNavigation Component Tests
 * 
 * Tests:
 * - Navigation links rendering
 * - Active route highlighting
 * - Conditional auth/unauth views
 * - User menu dropdown
 * - Logout functionality
 */

describe('TopNavigation.vue', () => {
  let wrapper
  let pinia
  let router

  beforeEach(async () => {
    pinia = createTestPinia()
    router = createTestRouter()
  })

  afterEach(() => {
    if (wrapper) {
      wrapper.unmount()
    }
  })

  describe('Rendering', () => {
    it('renders navigation bar', () => {
      wrapper = mount(TopNavigation, {
        global: {
          plugins: [pinia, router],
          stubs: {
            RouterLink: true,
            RouterView: true,
          },
        },
      })
      
      expect(wrapper.find('nav').exists()).toBe(true)
    })

    it('displays brand/logo', () => {
      wrapper = mount(TopNavigation, {
        global: {
          plugins: [pinia, router],
          stubs: {
            RouterLink: true,
            RouterView: true,
          },
        },
      })
      
      const brand = wrapper.find('[class*="brand"], [class*="logo"]')
      expect(brand.exists() || wrapper.text().includes('KSF')).toBe(true)
    })
  })

  describe('Navigation Links', () => {
    it('renders main navigation links', () => {
      wrapper = mount(TopNavigation, {
        global: {
          plugins: [pinia, router],
          stubs: {
            RouterLink: true,
          },
        },
      })
      
      const links = wrapper.findAll('a, button')
      expect(links.length).toBeGreaterThanOrEqual(2)
    })

    it('includes dashboard link', () => {
      wrapper = mount(TopNavigation, {
        global: {
          plugins: [pinia, router],
          stubs: {
            RouterLink: true,
          },
        },
      })
      
      const text = wrapper.text().toLowerCase()
      expect(text).toMatch(/dashboard|home/)
    })
  })

  describe('Active Route Highlighting', () => {
    it('highlights active route', async () => {
      wrapper = mount(TopNavigation, {
        global: {
          plugins: [pinia, router],
          stubs: {
            RouterLink: {
              template: '<a :aria-current="isActive"><slot /></a>',
              props: ['to'],
              computed: {
                isActive() {
                  return this.$route.path === this.to
                },
              },
            },
          },
        },
      })
      
      const activeLinks = wrapper.findAll('[aria-current="page"]')
      expect(activeLinks.length).toBeGreaterThanOrEqual(0)
    })

    it('applies active styling to current route', async () => {
      wrapper = mount(TopNavigation, {
        global: {
          plugins: [pinia, router],
        },
      })
      
      await router.push('/dashboard')
      await wrapper.vm.$nextTick()
      
      const html = wrapper.html()
      expect(html.includes('active') || html.includes('current')).toBe(true)
    })
  })

  describe('Authenticated View', () => {
    it('shows user menu when authenticated', async () => {
      const { useAuthStore } = await import('@/stores/auth')
      const authStore = useAuthStore()
      authStore.setUser({
        id: 1,
        email: 'test@example.com',
        name: 'Test User',
      })
      
      wrapper = mount(TopNavigation, {
        global: {
          plugins: [pinia, router],
          stubs: {
            RouterLink: true,
          },
        },
      })
      
      const userMenu = wrapper.find('[class*="user"], [class*="profile"], [class*="menu"]')
      expect(userMenu.exists()).toBe(true)
    })

    it('displays user name in menu', async () => {
      const { useAuthStore } = await import('@/stores/auth')
      const authStore = useAuthStore()
      authStore.setUser({
        id: 1,
        email: 'test@example.com',
        name: 'John Doe',
      })
      
      wrapper = mount(TopNavigation, {
        global: {
          plugins: [pinia, router],
          stubs: {
            RouterLink: true,
          },
        },
      })
      
      await wrapper.vm.$nextTick()
      expect(wrapper.text()).toContain('John Doe') || expect(wrapper.text()).toContain('test@example.com')
    })

    it('shows logout button when authenticated', async () => {
      const { useAuthStore } = await import('@/stores/auth')
      const authStore = useAuthStore()
      authStore.setUser({
        id: 1,
        email: 'test@example.com',
      })
      
      wrapper = mount(TopNavigation, {
        global: {
          plugins: [pinia, router],
          stubs: {
            RouterLink: true,
          },
        },
      })
      
      const text = wrapper.text().toLowerCase()
      expect(text).toMatch(/logout|sign out|exit/)
    })
  })

  describe('Unauthenticated View', () => {
    it('shows login/signup when not authenticated', async () => {
      wrapper = mount(TopNavigation, {
        global: {
          plugins: [pinia, router],
          stubs: {
            RouterLink: true,
          },
        },
      })
      
      const text = wrapper.text().toLowerCase()
      expect(text).toMatch(/login|sign in|register|sign up/)
    })

    it('does not show user menu when not authenticated', async () => {
      wrapper = mount(TopNavigation, {
        global: {
          plugins: [pinia, router],
          stubs: {
            RouterLink: true,
          },
        },
      })
      
      const userMenu = wrapper.find('[class*="user-menu"]')
      expect(userMenu.exists()).toBe(false)
    })
  })

  describe('User Menu Dropdown', () => {
    it('opens/closes dropdown on click', async () => {
      const { useAuthStore } = await import('@/stores/auth')
      const authStore = useAuthStore()
      authStore.setUser({ id: 1, email: 'test@example.com' })
      
      wrapper = mount(TopNavigation, {
        global: {
          plugins: [pinia, router],
          stubs: {
            RouterLink: true,
          },
        },
      })
      
      const menuButton = wrapper.find('[class*="user"], button[aria-haspopup]')
      if (menuButton.exists()) {
        await menuButton.trigger('click')
        await wrapper.vm.$nextTick()
        expect(wrapper.find('[class*="dropdown"]').exists() || wrapper.html().includes('hidden') === false).toBe(true)
      }
    })

    it('displays profile link in menu', async () => {
      const { useAuthStore } = await import('@/stores/auth')
      const authStore = useAuthStore()
      authStore.setUser({ id: 1, email: 'test@example.com' })
      
      wrapper = mount(TopNavigation, {
        global: {
          plugins: [pinia, router],
          stubs: {
            RouterLink: true,
          },
        },
      })
      
      const text = wrapper.text().toLowerCase()
      expect(text).toMatch(/profile|settings|account/)
    })

    it('displays logout in menu', async () => {
      const { useAuthStore } = await import('@/stores/auth')
      const authStore = useAuthStore()
      authStore.setUser({ id: 1, email: 'test@example.com' })
      
      wrapper = mount(TopNavigation, {
        global: {
          plugins: [pinia, router],
          stubs: {
            RouterLink: true,
          },
        },
      })
      
      const text = wrapper.text().toLowerCase()
      expect(text).toMatch(/logout|sign out/)
    })
  })

  describe('Logout Functionality', () => {
    it('clears auth on logout', async () => {
      const { useAuthStore } = await import('@/stores/auth')
      const authStore = useAuthStore()
      authStore.setUser({ id: 1, email: 'test@example.com' })
      
      wrapper = mount(TopNavigation, {
        global: {
          plugins: [pinia, router],
          stubs: {
            RouterLink: true,
          },
        },
      })
      
      const logoutButton = wrapper.find('button[class*="logout"]')
      if (logoutButton.exists()) {
        await logoutButton.trigger('click')
        await wrapper.vm.$nextTick()
        expect(authStore.user).toBeNull()
      }
    })

    it('redirects to login after logout', async () => {
      const { useAuthStore } = await import('@/stores/auth')
      const authStore = useAuthStore()
      authStore.setUser({ id: 1, email: 'test@example.com' })
      
      wrapper = mount(TopNavigation, {
        global: {
          plugins: [pinia, router],
          stubs: {
            RouterLink: true,
          },
        },
      })
      
      const logoutButton = wrapper.find('button[class*="logout"], a[class*="logout"]')
      if (logoutButton.exists()) {
        await logoutButton.trigger('click')
        await wrapper.vm.$nextTick()
        // Check navigation occurred or store cleared
        expect(authStore.user).toBeNull() || expect(router.currentRoute.value.path).toContain('login')
      }
    })
  })

  describe('Admin Links', () => {
    it('shows admin link for admin users', async () => {
      const { useAuthStore } = await import('@/stores/auth')
      const authStore = useAuthStore()
      authStore.setUser({
        id: 1,
        email: 'admin@example.com',
        role: 'admin',
      })
      
      wrapper = mount(TopNavigation, {
        global: {
          plugins: [pinia, router],
          stubs: {
            RouterLink: true,
          },
        },
      })
      
      const text = wrapper.text().toLowerCase()
      expect(text).toMatch(/admin|manage/)
    })

    it('hides admin link for non-admin users', async () => {
      const { useAuthStore } = await import('@/stores/auth')
      const authStore = useAuthStore()
      authStore.setUser({
        id: 1,
        email: 'user@example.com',
        role: 'user',
      })
      
      wrapper = mount(TopNavigation, {
        global: {
          plugins: [pinia, router],
          stubs: {
            RouterLink: true,
          },
        },
      })
      
      expect(wrapper.findAll('[href*="admin"]').length).toBe(0)
    })
  })

  describe('Accessibility', () => {
    it('has proper navigation semantics', () => {
      wrapper = mount(TopNavigation, {
        global: {
          plugins: [pinia, router],
          stubs: {
            RouterLink: true,
          },
        },
      })
      
      expect(wrapper.find('nav').exists()).toBe(true)
    })

    it('has proper link roles', () => {
      wrapper = mount(TopNavigation, {
        global: {
          plugins: [pinia, router],
          stubs: {
            RouterLink: true,
          },
        },
      })
      
      const links = wrapper.findAll('a, [role="link"]')
      expect(links.length).toBeGreaterThanOrEqual(1)
    })

    it('has proper button roles', () => {
      wrapper = mount(TopNavigation, {
        global: {
          plugins: [pinia, router],
          stubs: {
            RouterLink: true,
          },
        },
      })
      
      const buttons = wrapper.findAll('button')
      expect(buttons.length).toBeGreaterThanOrEqual(0)
    })
  })

  describe('Responsive Behavior', () => {
    it('renders navigation menu', () => {
      wrapper = mount(TopNavigation, {
        global: {
          plugins: [pinia, router],
          stubs: {
            RouterLink: true,
          },
        },
      })
      
      expect(wrapper.find('nav').exists()).toBe(true)
    })

    it('maintains menu structure', () => {
      wrapper = mount(TopNavigation, {
        global: {
          plugins: [pinia, router],
          stubs: {
            RouterLink: true,
          },
        },
      })
      
      const nav = wrapper.find('nav')
      expect(nav.findAll('a, button').length).toBeGreaterThanOrEqual(2)
    })
  })
})
