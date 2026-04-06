import { describe, it, expect, beforeEach } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useAuthStore } from '@/stores/auth'
import { createUser } from '../../fixtures/helpers'

/**
 * Auth Store Tests
 * 
 * Tests for authentication state management:
 * - User login/logout
 * - Token management
 * - Authentication state
 * - User data persistence
 */

describe('Auth Store', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
  })

  describe('Initial State', () => {
    it('has null user on initialization', () => {
      const store = useAuthStore()
      expect(store.user).toBeNull()
    })

    it('has no token on initialization', () => {
      const store = useAuthStore()
      expect(store.token).toBeUndefined() || expect(store.token).toBeNull()
    })

    it('is not authenticated initially', () => {
      const store = useAuthStore()
      expect(store.isAuthenticated).toBe(false)
    })
  })

  describe('Login', () => {
    it('sets user on successful login', () => {
      const store = useAuthStore()
      const user = createUser()
      
      store.setUser(user)
      
      expect(store.user).toEqual(user)
    })

    it('sets isAuthenticated to true after login', () => {
      const store = useAuthStore()
      const user = createUser()
      
      store.setUser(user)
      
      expect(store.isAuthenticated).toBe(true)
    })

    it('stores authentication token', () => {
      const store = useAuthStore()
      const token = 'test_token_abc123'
      
      store.setToken(token)
      
      expect(store.token).toBe(token)
    })

    it('preserves user data with multiple properties', () => {
      const store = useAuthStore()
      const user = createUser({
        id: 123,
        email: 'test@example.com',
        name: 'Test User',
        role: 'admin',
      })
      
      store.setUser(user)
      
      expect(store.user.id).toBe(123)
      expect(store.user.email).toBe('test@example.com')
      expect(store.user.name).toBe('Test User')
      expect(store.user.role).toBe('admin')
    })
  })

  describe('Logout', () => {
    it('clears user on logout', () => {
      const store = useAuthStore()
      store.setUser(createUser())
      expect(store.user).not.toBeNull()
      
      store.logout()
      
      expect(store.user).toBeNull()
    })

    it('clears token on logout', () => {
      const store = useAuthStore()
      store.setToken('token123')
      expect(store.token).toBe('token123')
      
      store.logout()
      
      expect(store.token).toBeUndefined() || expect(store.token).toBeNull()
    })

    it('sets isAuthenticated to false on logout', () => {
      const store = useAuthStore()
      store.setUser(createUser())
      store.setToken('token123')
      expect(store.isAuthenticated).toBe(true)
      
      store.logout()
      
      expect(store.isAuthenticated).toBe(false)
    })
  })

  describe('User Management', () => {
    it('updates user profile', () => {
      const store = useAuthStore()
      const initialUser = createUser({ name: 'Original' })
      store.setUser(initialUser)
      
      store.updateUser({ name: 'Updated' })
      
      expect(store.user.name).toBe('Updated')
    })

    it('preserves user id during update', () => {
      const store = useAuthStore()
      const user = createUser({ id: 42 })
      store.setUser(user)
      
      store.updateUser({ name: 'New Name' })
      
      expect(store.user.id).toBe(42)
    })

    it('handles partial user updates', () => {
      const store = useAuthStore()
      const user = createUser({
        name: 'John',
        email: 'john@example.com',
      })
      store.setUser(user)
      
      store.updateUser({ name: 'Jane' })
      
      expect(store.user.name).toBe('Jane')
      expect(store.user.email).toBe('john@example.com')
    })
  })

  describe('Token Management', () => {
    it('stores access token', () => {
      const store = useAuthStore()
      const token = 'access_token_xyz789'
      
      store.setToken(token)
      
      expect(store.token).toBe(token)
    })

    it('stores refresh token', () => {
      const store = useAuthStore()
      const refreshToken = 'refresh_token_xyz789'
      
      store.setRefreshToken(refreshToken)
      
      expect(store.refreshToken).toBe(refreshToken)
    })

    it('clears tokens on logout', () => {
      const store = useAuthStore()
      store.setToken('access_token')
      store.setRefreshToken('refresh_token')
      
      store.logout()
      
      expect(store.token).toBeUndefined() || expect(store.token).toBeNull()
      expect(store.refreshToken).toBeUndefined() || expect(store.refreshToken).toBeNull()
    })

    it('updates token without affecting user', () => {
      const store = useAuthStore()
      const user = createUser({ name: 'John' })
      store.setUser(user)
      store.setToken('old_token')
      
      store.setToken('new_token')
      
      expect(store.token).toBe('new_token')
      expect(store.user.name).toBe('John')
    })
  })

  describe('Authentication Status', () => {
    it('reports authenticated when user and token present', () => {
      const store = useAuthStore()
      store.setUser(createUser())
      store.setToken('token123')
      
      expect(store.isAuthenticated).toBe(true)
    })

    it('reports not authenticated when no user', () => {
      const store = useAuthStore()
      store.setToken('token123')
      
      expect(store.isAuthenticated).toBe(false)
    })

    it('reports not authenticated when no token', () => {
      const store = useAuthStore()
      store.setUser(createUser())
      
      expect(store.isAuthenticated).toBe(false)
    })
  })

  describe('User Roles', () => {
    it('identifies admin user', () => {
      const store = useAuthStore()
      store.setUser(createUser({ role: 'admin' }))
      
      expect(store.isAdmin).toBe(true)
    })

    it('identifies non-admin user', () => {
      const store = useAuthStore()
      store.setUser(createUser({ role: 'user' }))
      
      expect(store.isAdmin).toBe(false)
    })

    it('returns false for isAdmin when no user', () => {
      const store = useAuthStore()
      
      expect(store.isAdmin).toBe(false)
    })

    it('checks specific role', () => {
      const store = useAuthStore()
      store.setUser(createUser({ role: 'moderator' }))
      
      expect(store.hasRole('moderator')).toBe(true)
      expect(store.hasRole('admin')).toBe(false)
    })
  })

  describe('Permissions', () => {
    it('checks user permissions', () => {
      const store = useAuthStore()
      store.setUser(createUser({ permissions: ['create:client', 'read:metrics'] }))
      
      expect(store.hasPermission('create:client')).toBe(true)
      expect(store.hasPermission('delete:client')).toBe(false)
    })

    it('handles missing permissions array', () => {
      const store = useAuthStore()
      store.setUser(createUser({}))
      
      expect(store.hasPermission('any:permission')).toBe(false)
    })

    it('checks multiple permissions', () => {
      const store = useAuthStore()
      store.setUser(createUser({ permissions: ['read:user', 'write:user'] }))
      
      expect(store.hasAllPermissions(['read:user', 'write:user'])).toBe(true)
      expect(store.hasAllPermissions(['read:user', 'delete:user'])).toBe(false)
    })
  })

  describe('Error Handling', () => {
    it('stores authentication error', () => {
      const store = useAuthStore()
      const error = 'Invalid credentials'
      
      store.setError(error)
      
      expect(store.error).toBe(error)
    })

    it('clears error on successful login', () => {
      const store = useAuthStore()
      store.setError('Previous error')
      
      store.setUser(createUser())
      
      expect(store.error).toBeNull() || expect(store.error).toBeUndefined()
    })

    it('preserves error until explicitly cleared', () => {
      const store = useAuthStore()
      store.setError('Login failed')
      
      expect(store.error).toBe('Login failed')
      
      store.clearError()
      
      expect(store.error).toBeNull() || expect(store.error).toBeUndefined()
    })
  })

  describe('State Reactivity', () => {
    it('updates are reactive', () => {
      const store = useAuthStore()
      const user = createUser({ name: 'Initial' })
      store.setUser(user)
      
      expect(store.user.name).toBe('Initial')
      
      store.updateUser({ name: 'Changed' })
      
      expect(store.user.name).toBe('Changed')
    })

    it('computed properties update reactively', () => {
      const store = useAuthStore()
      
      expect(store.isAuthenticated).toBe(false)
      
      store.setUser(createUser())
      store.setToken('token')
      
      expect(store.isAuthenticated).toBe(true)
    })
  })

  describe('Store Cleanup', () => {
    it('can reset entire store', () => {
      const store = useAuthStore()
      store.setUser(createUser())
      store.setToken('token123')
      store.setError('Error message')
      
      store.$reset()
      
      expect(store.user).toBeNull()
      expect(store.token).toBeUndefined() || expect(store.token).toBeNull()
      expect(store.error).toBeNull() || expect(store.error).toBeUndefined()
    })
  })
})
