import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '../utils/api'

/**
 * Authentication Store
 * 
 * Manages:
 * - User login/logout
 * - Token storage and refresh
 * - User profile information
 * - Authentication state
 */

export const useAuthStore = defineStore('auth', () => {
  // State
  const user = ref(null)
  const token = ref(localStorage.getItem('access_token') || null)
  const refreshToken = ref(localStorage.getItem('refresh_token') || null)
  const isLoading = ref(false)
  const error = ref(null)

  // Computed
  const isAuthenticated = computed(() => !!token.value)
  const userId = computed(() => user.value?.user_id)
  const userName = computed(() => user.value?.name)
  const userEmail = computed(() => user.value?.email)
  const userRole = computed(() => user.value?.role ?? 'user')

  /**
   * Login user with email/password
   * @param {string} email User email
   * @param {string} password User password
   * @returns {Promise<object>} Auth response
   */
  async function login(email, password) {
    isLoading.value = true
    error.value = null

    try {
      const response = await api.post('/auth/login', { email, password })
      const { access_token, refresh_token, user: userData } = response.data

      // Store tokens
      token.value = access_token
      refreshToken.value = refresh_token
      localStorage.setItem('access_token', access_token)
      localStorage.setItem('refresh_token', refresh_token)

      // Store user data
      user.value = userData
      localStorage.setItem('user', JSON.stringify(userData))

      return response.data
    } catch (err) {
      error.value = err.response?.data?.message || 'Login failed'
      throw err
    } finally {
      isLoading.value = false
    }
  }

  /**
   * Logout user and clear tokens
   */
  async function logout() {
    try {
      // Call logout endpoint if token exists
      if (token.value) {
        await api.post('/auth/logout')
      }
    } catch (err) {
      console.error('Logout error:', err)
    } finally {
      // Clear local state
      user.value = null
      token.value = null
      refreshToken.value = null
      localStorage.removeItem('access_token')
      localStorage.removeItem('refresh_token')
      localStorage.removeItem('user')
      error.value = null
    }
  }

  /**
   * Fetch current user profile
   * @returns {Promise<object>} User data
   */
  async function fetchCurrentUser() {
    if (!token.value) {
      return null
    }

    isLoading.value = true
    error.value = null

    try {
      const response = await api.get('/user/me')
      user.value = response.data
      localStorage.setItem('user', JSON.stringify(response.data))
      return response.data
    } catch (err) {
      error.value = err.response?.data?.message || 'Failed to fetch user'
      throw err
    } finally {
      isLoading.value = false
    }
  }

  /**
   * Refresh authentication token
   * @returns {Promise<string>} New access token
   */
  async function refreshAccessToken() {
    if (!refreshToken.value) {
      throw new Error('No refresh token available')
    }

    try {
      const response = await api.post('/auth/refresh', {
        refresh_token: refreshToken.value,
      })

      const { access_token, refresh_token: newRefreshToken } = response.data

      token.value = access_token
      refreshToken.value = newRefreshToken
      localStorage.setItem('access_token', access_token)
      localStorage.setItem('refresh_token', newRefreshToken)

      return access_token
    } catch (err) {
      // Refresh failed, logout user
      logout()
      throw err
    }
  }

  /**
   * Set token from external source
   * @param {string} accessToken Access token value
   */
  function setToken(accessToken) {
    token.value = accessToken
    localStorage.setItem('access_token', accessToken)
  }

  /**
   * Update user profile
   * @param {object} updates Profile updates
   * @returns {Promise<object>} Updated user data
   */
  async function updateProfile(updates) {
    isLoading.value = true
    error.value = null

    try {
      const response = await api.put('/user/me', updates)
      user.value = response.data
      localStorage.setItem('user', JSON.stringify(response.data))
      return response.data
    } catch (err) {
      error.value = err.response?.data?.message || 'Failed to update profile'
      throw err
    } finally {
      isLoading.value = false
    }
  }

  /**
   * Change user password
   * @param {string} currentPassword Current password
   * @param {string} newPassword New password
   * @returns {Promise<void>}
   */
  async function changePassword(currentPassword, newPassword) {
    error.value = null

    try {
      await api.post('/user/change-password', {
        current_password: currentPassword,
        new_password: newPassword,
      })
    } catch (err) {
      error.value = err.response?.data?.message || 'Failed to change password'
      throw err
    }
  }

  return {
    // State
    user,
    token,
    refreshToken,
    isLoading,
    error,

    // Computed
    isAuthenticated,
    userId,
    userName,
    userEmail,
    userRole,

    // Actions
    login,
    logout,
    fetchCurrentUser,
    refreshAccessToken,
    setToken,
    updateProfile,
    changePassword,
  }
})
