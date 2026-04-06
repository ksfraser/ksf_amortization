import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '../utils/api'

/**
 * Metrics Store
 * 
 * Manages:
 * - Performance metrics (latency, cache)
 * - Error tracking
 * - System health status
 * - Metrics data refreshing
 */

export const useMetricsStore = defineStore('metrics', () => {
  // State
  const dashboard = ref(null)
  const latency = ref(null)
  const cache = ref(null)
  const errors = ref(null)
  const health = ref(null)

  const isLoading = ref(false)
  const error = ref(null)
  const lastUpdated = ref(null)
  const autoRefreshInterval = ref(null)

  /**
   * Fetch dashboard metrics
   * @returns {Promise<object>} Dashboard data
   */
  async function fetchDashboard() {
    isLoading.value = true
    error.value = null

    try {
      const response = await api.get('/admin/metrics/dashboard')
      dashboard.value = response.data
      lastUpdated.value = new Date()
      return response.data
    } catch (err) {
      error.value = err.response?.data?.message || 'Failed to fetch dashboard'
      throw err
    } finally {
      isLoading.value = false
    }
  }

  /**
   * Fetch latency metrics
   * @param {object} options Query options (time_range, etc.)
   * @returns {Promise<object>} Latency data
   */
  async function fetchLatency(options = {}) {
    isLoading.value = true
    error.value = null

    try {
      const response = await api.get('/admin/metrics/latency', {
        params: options,
      })
      latency.value = response.data
      lastUpdated.value = new Date()
      return response.data
    } catch (err) {
      error.value = err.response?.data?.message || 'Failed to fetch latency'
      throw err
    } finally {
      isLoading.value = false
    }
  }

  /**
   * Fetch cache metrics
   * @param {object} options Query options
   * @returns {Promise<object>} Cache data
   */
  async function fetchCache(options = {}) {
    isLoading.value = true
    error.value = null

    try {
      const response = await api.get('/admin/metrics/cache', {
        params: options,
      })
      cache.value = response.data
      lastUpdated.value = new Date()
      return response.data
    } catch (err) {
      error.value = err.response?.data?.message || 'Failed to fetch cache metrics'
      throw err
    } finally {
      isLoading.value = false
    }
  }

  /**
   * Fetch error metrics
   * @param {object} options Query options
   * @returns {Promise<object>} Error data
   */
  async function fetchErrors(options = {}) {
    isLoading.value = true
    error.value = null

    try {
      const response = await api.get('/admin/metrics/errors', {
        params: options,
      })
      errors.value = response.data
      lastUpdated.value = new Date()
      return response.data
    } catch (err) {
      error.value =
        err.response?.data?.message || 'Failed to fetch error metrics'
      throw err
    } finally {
      isLoading.value = false
    }
  }

  /**
   * Fetch system health
   * @returns {Promise<object>} Health status
   */
  async function fetchHealth() {
    try {
      const response = await api.get('/admin/metrics/health')
      health.value = response.data
      lastUpdated.value = new Date()
      return response.data
    } catch (err) {
      error.value =
        err.response?.data?.message || 'Failed to fetch health status'
      throw err
    }
  }

  /**
   * Fetch all metrics at once
   * @returns {Promise<void>}
   */
  async function fetchAll() {
    try {
      await Promise.all([
        fetchDashboard(),
        fetchLatency(),
        fetchCache(),
        fetchErrors(),
        fetchHealth(),
      ])
    } catch (err) {
      console.error('Error fetching metrics:', err)
    }
  }

  /**
   * Export metrics as CSV/JSON
   * @param {string} format Export format (csv, json)
   * @param {object} options Query options
   * @returns {Promise<object>} Export data
   */
  async function exportMetrics(format = 'json', options = {}) {
    error.value = null

    try {
      const response = await api.get('/admin/metrics/export', {
        params: { format, ...options },
      })
      return response.data
    } catch (err) {
      error.value = err.response?.data?.message || 'Failed to export metrics'
      throw err
    }
  }

  /**
   * Start auto-refresh of metrics
   * @param {number} interval Refresh interval in milliseconds
   */
  function startAutoRefresh(interval = 30000) {
    if (autoRefreshInterval.value) {
      clearInterval(autoRefreshInterval.value)
    }

    autoRefreshInterval.value = setInterval(() => {
      fetchAll().catch((err) => {
        console.error('Auto-refresh failed:', err)
      })
    }, interval)
  }

  /**
   * Stop auto-refresh
   */
  function stopAutoRefresh() {
    if (autoRefreshInterval.value) {
      clearInterval(autoRefreshInterval.value)
      autoRefreshInterval.value = null
    }
  }

  return {
    // State
    dashboard,
    latency,
    cache,
    errors,
    health,
    isLoading,
    error,
    lastUpdated,

    // Actions
    fetchDashboard,
    fetchLatency,
    fetchCache,
    fetchErrors,
    fetchHealth,
    fetchAll,
    exportMetrics,
    startAutoRefresh,
    stopAutoRefresh,
  }
})
