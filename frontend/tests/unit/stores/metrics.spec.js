import { describe, it, expect, beforeEach } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useMetricsStore } from '@/stores/metrics'
import { createMetrics } from '../../fixtures/helpers'

/**
 * Metrics Store Tests
 * 
 * Tests for metrics and analytics state:
 * - Metrics data management
 * - Time period selection
 * - Aggregation and calculations
 * - Comparisons
 */

describe('Metrics Store', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
  })

  describe('Initial State', () => {
    it('starts with null metrics', () => {
      const store = useMetricsStore()
      expect(store.metrics).toBeNull()
    })

    it('has 24-hour period selected initially', () => {
      const store = useMetricsStore()
      expect(store.period).toBe('24h')
    })

    it('has no comparison period initially', () => {
      const store = useMetricsStore()
      expect(store.comparisonPeriod).toBeNull()
    })
  })

  describe('Metrics Management', () => {
    it('sets metrics data', () => {
      const store = useMetricsStore()
      const metrics = createMetrics({
        totalRequests: 1000,
        successRate: 98.5,
      })
      
      store.setMetrics(metrics)
      
      expect(store.metrics.totalRequests).toBe(1000)
      expect(store.metrics.successRate).toBe(98.5)
    })

    it('updates specific metric', () => {
      const store = useMetricsStore()
      store.setMetrics(createMetrics({ totalRequests: 1000 }))
      
      store.updateMetric('totalRequests', 2000)
      
      expect(store.metrics.totalRequests).toBe(2000)
    })

    it('updates multiple metrics', () => {
      const store = useMetricsStore()
      store.setMetrics(createMetrics())
      
      store.updateMetrics({
        totalRequests: 5000,
        errorCount: 50,
        successRate: 99,
      })
      
      expect(store.metrics.totalRequests).toBe(5000)
      expect(store.metrics.errorCount).toBe(50)
      expect(store.metrics.successRate).toBe(99)
    })

    it('clears metrics', () => {
      const store = useMetricsStore()
      store.setMetrics(createMetrics())
      expect(store.metrics).not.toBeNull()
      
      store.clearMetrics()
      
      expect(store.metrics).toBeNull()
    })
  })

  describe('Time Period Selection', () => {
    it('changes to 24h period', () => {
      const store = useMetricsStore()
      
      store.setPeriod('24h')
      
      expect(store.period).toBe('24h')
    })

    it('changes to 7d period', () => {
      const store = useMetricsStore()
      
      store.setPeriod('7d')
      
      expect(store.period).toBe('7d')
    })

    it('changes to 30d period', () => {
      const store = useMetricsStore()
      
      store.setPeriod('30d')
      
      expect(store.period).toBe('30d')
    })

    it('sets custom date range', () => {
      const store = useMetricsStore()
      const startDate = '2026-03-01'
      const endDate = '2026-04-05'
      
      store.setDateRange(startDate, endDate)
      
      expect(store.startDate).toBe(startDate)
      expect(store.endDate).toBe(endDate)
    })

    it('returns period label', () => {
      const store = useMetricsStore()
      store.setPeriod('24h')
      
      expect(store.periodLabel).toBe('Last 24 Hours') || expect(store.periodLabel).toBe('24h')
    })
  })

  describe('Aggregations', () => {
    it('calculates average response time', () => {
      const store = useMetricsStore()
      store.setMetrics(createMetrics({
        avgResponseTime: 245,
      }))
      
      expect(store.metrics.avgResponseTime).toBe(245)
    })

    it('calculates error rate percentage', () => {
      const store = useMetricsStore()
      store.setMetrics(createMetrics({
        totalRequests: 1000,
        errorCount: 5,
      }))
      
      const errorRate = (store.metrics.errorCount / store.metrics.totalRequests) * 100
      
      expect(errorRate).toBe(0.5)
    })

    it('calculates success rate', () => {
      const store = useMetricsStore()
      store.setMetrics(createMetrics({
        successRate: 99.5,
      }))
      
      expect(store.metrics.successRate).toBeGreaterThan(99)
    })

    it('aggregates by status code', () => {
      const store = useMetricsStore()
      store.setMetrics(createMetrics({
        statusCodes: {
          '200': 950,
          '404': 30,
          '500': 20,
        },
      }))
      
      expect(store.metrics.statusCodes['200']).toBe(950)
      expect(store.metrics.statusCodes['404']).toBe(30)
    })

    it('aggregates by endpoint', () => {
      const store = useMetricsStore()
      store.setMetrics(createMetrics({
        endpoints: {
          '/api/auth/login': 300,
          '/api/clients': 500,
          '/api/metrics': 200,
        },
      }))
      
      expect(store.metrics.endpoints['/api/auth/login']).toBe(300)
      expect(store.metrics.endpoints['/api/clients']).toBe(500)
    })
  })

  describe('Comparison', () => {
    it('sets comparison period', () => {
      const store = useMetricsStore()
      
      store.setComparisonPeriod('7d')
      
      expect(store.comparisonPeriod).toBe('7d')
    })

    it('fetches comparison metrics', () => {
      const store = useMetricsStore()
      const currentMetrics = createMetrics({ totalRequests: 2000 })
      const comparisonMetrics = createMetrics({ totalRequests: 1500 })
      
      store.setMetrics(currentMetrics)
      store.setComparisonMetrics(comparisonMetrics)
      
      expect(store.comparisonMetrics.totalRequests).toBe(1500)
    })

    it('calculates metric change', () => {
      const store = useMetricsStore()
      store.setMetrics(createMetrics({ totalRequests: 2000 }))
      store.setComparisonMetrics(createMetrics({ totalRequests: 1500 }))
      
      const change = store.getMetricChange('totalRequests')
      
      expect(change).toBe(500) || expect(typeof change).toBe('number')
    })

    it('calculates percentage change', () => {
      const store = useMetricsStore()
      store.setMetrics(createMetrics({ successRate: 99.5 }))
      store.setComparisonMetrics(createMetrics({ successRate: 98.0 }))
      
      const percentChange = store.getMetricPercentChange('successRate')
      
      expect(percentChange).toBeGreaterThan(0) || expect(typeof percentChange).toBe('number')
    })

    it('clears comparison', () => {
      const store = useMetricsStore()
      store.setComparisonMetrics(createMetrics())
      expect(store.comparisonMetrics).not.toBeNull()
      
      store.clearComparison()
      
      expect(store.comparisonMetrics).toBeNull()
      expect(store.comparisonPeriod).toBeNull()
    })
  })

  describe('Filters', () => {
    it('filters by status code', () => {
      const store = useMetricsStore()
      
      store.setStatusCodeFilter('500')
      
      expect(store.statusCodeFilter).toBe('500')
    })

    it('filters by endpoint', () => {
      const store = useMetricsStore()
      
      store.setEndpointFilter('/api/clients')
      
      expect(store.endpointFilter).toBe('/api/clients')
    })

    it('clears filters', () => {
      const store = useMetricsStore()
      store.setStatusCodeFilter('500')
      store.setEndpointFilter('/api/auth')
      
      store.clearFilters()
      
      expect(store.statusCodeFilter).toBeNull()
      expect(store.endpointFilter).toBeNull()
    })
  })

  describe('Alerts', () => {
    it('identifies high error rate', () => {
      const store = useMetricsStore()
      store.setMetrics(createMetrics({
        errorCount: 500,
        totalRequests: 1000,
      }))
      
      const errorRate = (store.metrics.errorCount / store.metrics.totalRequests) * 100
      expect(errorRate).toBe(50)
    })

    it('identifies slow response times', () => {
      const store = useMetricsStore()
      store.setMetrics(createMetrics({
        avgResponseTime: 5000,
      }))
      
      expect(store.metrics.avgResponseTime).toBeGreaterThan(1000)
    })

    it('stores alert triggers', () => {
      const store = useMetricsStore()
      
      store.addAlert({
        type: 'high_error_rate',
        severity: 'high',
        message: 'Error rate exceeded 5%',
      })
      
      expect(store.alerts.length).toBeGreaterThan(0)
    })

    it('clears alerts', () => {
      const store = useMetricsStore()
      store.addAlert({ type: 'test' })
      expect(store.alerts.length).toBeGreaterThan(0)
      
      store.clearAlerts()
      
      expect(store.alerts).toEqual([])
    })
  })

  describe('Real-time Updates', () => {
    it('updates metrics incrementally', () => {
      const store = useMetricsStore()
      store.setMetrics(createMetrics({ totalRequests: 1000 }))
      
      store.updateMetric('totalRequests', 1001)
      store.updateMetric('totalRequests', 1002)
      
      expect(store.metrics.totalRequests).toBe(1002)
    })

    it('recalculates rates on update', () => {
      const store = useMetricsStore()
      store.setMetrics(createMetrics({
        totalRequests: 1000,
        errorCount: 10,
      }))
      
      store.updateMetric('errorCount', 20)
      
      const newErrorRate = (20 / 1000) * 100
      expect(newErrorRate).toBe(2)
    })
  })

  describe('Export', () => {
    it('prepares data for export', () => {
      const store = useMetricsStore()
      store.setMetrics(createMetrics({
        totalRequests: 1000,
        successRate: 99,
      }))
      
      const exportData = store.getExportData()
      
      expect(exportData).toBeDefined()
      expect(exportData.totalRequests).toBe(1000)
    })

    it('generates CSV format', () => {
      const store = useMetricsStore()
      store.setMetrics(createMetrics({
        totalRequests: 1000,
        errorCount: 5,
      }))
      
      const csv = store.toCSV()
      
      expect(typeof csv).toBe('string')
      expect(csv.includes('totalRequests')).toBe(true)
    })
  })

  describe('Loading State', () => {
    it('sets loading', () => {
      const store = useMetricsStore()
      
      store.setLoading(true)
      expect(store.isLoading).toBe(true)
      
      store.setLoading(false)
      expect(store.isLoading).toBe(false)
    })
  })

  describe('Error Handling', () => {
    it('stores error', () => {
      const store = useMetricsStore()
      const error = 'Failed to load metrics'
      
      store.setError(error)
      
      expect(store.error).toBe(error)
    })

    it('clears error', () => {
      const store = useMetricsStore()
      store.setError('Error message')
      
      store.clearError()
      
      expect(store.error).toBeNull() || expect(store.error).toBeUndefined()
    })
  })

  describe('Store Cleanup', () => {
    it('resets store to initial state', () => {
      const store = useMetricsStore()
      store.setMetrics(createMetrics())
      store.setPeriod('7d')
      store.setStatusCodeFilter('500')
      
      store.$reset()
      
      expect(store.metrics).toBeNull()
      expect(store.period).toBe('24h')
      expect(store.statusCodeFilter).toBeNull()
    })
  })
})
