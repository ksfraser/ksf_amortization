import axios from 'axios'
import { useAuthStore } from '../stores/auth'
import { useNotificationStore } from '../stores/ui'

/**
 * Axios HTTP Client
 * 
 * Provides:
 * - Centralized API configuration
 * - Request/response interceptors
 * - Token refresh handling
 * - Error handling
 * - Request timeout
 */

const api = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api/v1',
  timeout: import.meta.env.VITE_API_TIMEOUT || 30000,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
})

/**
 * Request Interceptor
 * Adds authentication token to all requests
 */
api.interceptors.request.use(
  (config) => {
    const authStore = useAuthStore()
    if (authStore.token) {
      config.headers.Authorization = `Bearer ${authStore.token}`
    }
    return config
  },
  (error) => {
    return Promise.reject(error)
  }
)

/**
 * Response Interceptor
 * Handles token refresh and error responses
 */
api.interceptors.response.use(
  (response) => {
    return response
  },
  async (error) => {
    const originalRequest = error.config
    const authStore = useAuthStore()
    const notificationStore = useNotificationStore()

    // Handle 401 Unauthorized
    if (error.response?.status === 401 && !originalRequest._retry) {
      originalRequest._retry = true

      try {
        // Try to refresh token
        await authStore.refreshAccessToken()

        // Retry original request with new token
        originalRequest.headers.Authorization = `Bearer ${authStore.token}`
        return api(originalRequest)
      } catch (refreshError) {
        // Refresh failed, logout user
        authStore.logout()
        window.location.href = '/login'
        return Promise.reject(refreshError)
      }
    }

    // Handle 403 Forbidden
    if (error.response?.status === 403) {
      notificationStore.error('Access denied', 'Permission Error')
    }

    // Handle 422 Unprocessable Entity (Validation errors)
    if (error.response?.status === 422) {
      const errors = error.response.data?.errors || {}
      const errorMessages = Object.entries(errors)
        .map(([field, messages]) => `${field}: ${messages.join(', ')}`)
        .join('\n')
      notificationStore.error(errorMessages || 'Validation failed')
    }

    // Handle 500 Server Error
    if (error.response?.status === 500) {
      notificationStore.error('Server error. Please try again later.', 'Error')
    }

    // Handle network error
    if (!error.response) {
      notificationStore.error(
        'Network error. Please check your connection.',
        'Connection Error'
      )
    }

    return Promise.reject(error)
  }
)

export default api
