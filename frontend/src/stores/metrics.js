import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

/**
 * Metrics Store (Simplified for Testing)
 * 
 * Manages:
 * - Performance metrics data
 * - Time period selection
 * - Metric comparisons
 * - Metric filtering
 * - Alert tracking
 */

export const useMetricsStore = defineStore('metrics', () => {
  // State
  const metrics = ref(null)
  const period = ref('24h')
  const comparisonPeriod = ref(null)
  const comparisonMetrics = ref(null)
  const startDate = ref(null)
  const endDate = ref(null)
  const statusCodeFilter = ref(null)
  const endpointFilter = ref(null)
  const alerts = ref([])

  // Computed
  const periodLabel = computed(() => {
    const labels = {
      '24h': 'Last 24 Hours',
      '7d': 'Last 7 Days',
      '30d': 'Last 30 Days',
    }
    return labels[period.value] || period.value
  })

  /**
   * Set metrics data
   * @param {object} metricsData Metrics object
   */
  function setMetrics(metricsData) {
    metrics.value = metricsData
  }

  /**
   * Update specific metric value
   * @param {string} key Metric key
   * @param {*} value New value
   */
  function updateMetric(key, value) {
    if (metrics.value) {
      metrics.value[key] = value
    }
  }

  /**
   * Update multiple metrics
   * @param {object} updates Metrics to update
   */
  function updateMetrics(updates) {
    if (metrics.value) {
      Object.assign(metrics.value, updates)
    }
  }

  /**
   * Clear current metrics
   */
  function clearMetrics() {
    metrics.value = null
  }

  /**
   * Set time period
   * @param {string} newPeriod Period value (24h, 7d, 30d)
   */
  function setPeriod(newPeriod) {
    period.value = newPeriod
  }

  /**
   * Set custom date range
   * @param {string} start Start date (ISO format)
   * @param {string} end End date (ISO format)
   */
  function setDateRange(start, end) {
    startDate.value = start
    endDate.value = end
  }

  /**
   * Set comparison period
   * @param {string} compPeriod Comparison period
   */
  function setComparisonPeriod(compPeriod) {
    comparisonPeriod.value = compPeriod
  }

  /**
   * Set comparison metrics data
   * @param {object} compMetrics Comparison metrics object
   */
  function setComparisonMetrics(compMetrics) {
    comparisonMetrics.value = compMetrics
  }

  /**
   * Get metric change (absolute difference)
   * @param {string} key Metric key
   * @returns {number} Change amount
   */
  function getMetricChange(key) {
    if (!metrics.value || !comparisonMetrics.value) {
      return null
    }
    return metrics.value[key] - comparisonMetrics.value[key]
  }

  /**
   * Get metric percentage change
   * @param {string} key Metric key
   * @returns {number} Percentage change
   */
  function getMetricPercentChange(key) {
    if (!metrics.value || !comparisonMetrics.value) {
      return null
    }
    const change = metrics.value[key] - comparisonMetrics.value[key]
    return (change / comparisonMetrics.value[key]) * 100
  }

  /**
   * Clear comparison data
   */
  function clearComparison() {
    comparisonPeriod.value = null
    comparisonMetrics.value = null
  }

  /**
   * Set status code filter
   * @param {string} code Status code to filter by
   */
  function setStatusCodeFilter(code) {
    statusCodeFilter.value = code
  }

  /**
   * Set endpoint filter
   * @param {string} endpoint Endpoint to filter by
   */
  function setEndpointFilter(endpoint) {
    endpointFilter.value = endpoint
  }

  /**
   * Clear all filters
   */
  function clearFilters() {
    statusCodeFilter.value = null
    endpointFilter.value = null
  }

  /**
   * Add alert
   * @param {object} alert Alert object (type, severity, message)
   */
  function addAlert(alert) {
    alerts.value.push({
      ...alert,
      id: Date.now(),
      timestamp: new Date(),
    })
  }

  /**
   * Remove alert
   * @param {number} alertId Alert ID
   */
  function removeAlert(alertId) {
    alerts.value = alerts.value.filter((a) => a.id !== alertId)
  }

  /**
   * Clear all alerts
   */
  function clearAlerts() {
    alerts.value = []
  }

  return {
    // State
    metrics,
    period,
    comparisonPeriod,
    comparisonMetrics,
    startDate,
    endDate,
    statusCodeFilter,
    endpointFilter,
    alerts,

    // Computed
    periodLabel,

    // Actions
    setMetrics,
    updateMetric,
    updateMetrics,
    clearMetrics,
    setPeriod,
    setDateRange,
    setComparisonPeriod,
    setComparisonMetrics,
    getMetricChange,
    getMetricPercentChange,
    clearComparison,
    setStatusCodeFilter,
    setEndpointFilter,
    clearFilters,
    addAlert,
    removeAlert,
    clearAlerts,
  }
})
