<template>
  <div class="space-y-6">
    <!-- Controls -->
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-2xl font-bold text-gray-900">Metrics Dashboard</h2>
        <p class="text-gray-600">System performance and health overview</p>
      </div>
      <div class="flex gap-2">
        <Button
          :variant="autoRefresh ? 'success' : 'secondary'"
          size="sm"
          @click="toggleAutoRefresh"
        >
          {{ autoRefresh ? '✓ Auto-refreshing' : 'Manual' }}
        </Button>
        <Button variant="primary" size="sm" @click="refresh">
          🔄 Refresh
        </Button>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="metricsStore.isLoading" class="text-center py-12">
      <div class="animate-spin">
        <svg class="w-8 h-8 text-primary-600 mx-auto" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
        </svg>
      </div>
    </div>

    <!-- Error State -->
    <Alert
      v-else-if="metricsStore.error"
      type="error"
      title="Error Loading Metrics"
      :message="metricsStore.error"
    />

    <!-- Dashboard Grid -->
    <div v-else class="space-y-6">
      <!-- Summary Cards -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Total Requests -->
        <div class="card">
          <div class="card-body">
            <p class="text-sm text-gray-600 font-medium">Total Requests</p>
            <p class="text-3xl font-bold text-gray-900 mt-2">
              {{ dashboard?.total_requests || 0 | formatNumber }}
            </p>
            <p class="text-xs text-gray-500 mt-2">Last 24 hours</p>
          </div>
        </div>

        <!-- Success Rate -->
        <div class="card">
          <div class="card-body">
            <p class="text-sm text-gray-600 font-medium">Success Rate</p>
            <p class="text-3xl font-bold text-success-600 mt-2">
              {{ dashboard?.success_rate || 0 }}%
            </p>
            <div class="w-full bg-gray-200 rounded-full h-2 mt-3">
              <div
                class="bg-success-600 h-2 rounded-full"
                :style="{ width: (dashboard?.success_rate || 0) + '%' }"
              />
            </div>
          </div>
        </div>

        <!-- Avg Latency -->
        <div class="card">
          <div class="card-body">
            <p class="text-sm text-gray-600 font-medium">Avg Latency</p>
            <p class="text-3xl font-bold text-primary-600 mt-2">
              {{ dashboard?.avg_latency || 0 }}ms
            </p>
            <p class="text-xs text-gray-500 mt-2">95th percentile: {{ dashboard?.p95_latency || 0 }}ms</p>
          </div>
        </div>

        <!-- Cache Hit Rate -->
        <div class="card">
          <div class="card-body">
            <p class="text-sm text-gray-600 font-medium">Cache Hit Rate</p>
            <p class="text-3xl font-bold text-primary-600 mt-2">
              {{ dashboard?.cache_hit_rate || 0 }}%
            </p>
            <p class="text-xs text-gray-500 mt-2">{{ dashboard?.cache_hits || 0 }} hits</p>
          </div>
        </div>
      </div>

      <!-- Detailed Sections -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Latency Breakdown -->
        <div class="card">
          <div class="card-header">
            <h3 class="font-semibold text-gray-900">Latency Percentiles</h3>
          </div>
          <div class="card-body space-y-3">
            <div v-for="(value, label) in latencyMetrics" :key="label" class="flex items-center justify-between">
              <span class="text-sm text-gray-600">{{ label }}</span>
              <span class="font-semibold text-gray-900">{{ value }}ms</span>
            </div>
          </div>
        </div>

        <!-- Cache Breakdown -->
        <div class="card">
          <div class="card-header">
            <h3 class="font-semibold text-gray-900">Cache Performance</h3>
          </div>
          <div class="card-body space-y-3">
            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-600">Total Hits</span>
              <span class="font-semibold text-gray-900">{{ cacheMetrics?.hits || 0 }}</span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-600">Total Misses</span>
              <span class="font-semibold text-gray-900">{{ cacheMetrics?.misses || 0 }}</span>
            </div>
            <div class="flex items-center justify-between pt-3 border-t border-gray-200">
              <span class="text-sm text-gray-600">Hit Rate</span>
              <span class="font-semibold text-success-600">{{ cacheMetrics?.hit_rate || 0 }}%</span>
            </div>
          </div>
        </div>

        <!-- Error Distribution -->
        <div class="card">
          <div class="card-header">
            <h3 class="font-semibold text-gray-900">Errors (Last 24h)</h3>
          </div>
          <div class="card-body space-y-2">
            <div v-if="errorMetrics?.errors?.length > 0">
              <div v-for="error in errorMetrics.errors" :key="error.code" class="flex items-center justify-between py-2 border-b border-gray-100 last:border-0">
                <div>
                  <p class="text-sm font-medium text-gray-900">{{ error.code }}</p>
                  <p class="text-xs text-gray-500">{{ error.message }}</p>
                </div>
                <span class="badge badge-error">{{ error.count }}</span>
              </div>
            </div>
            <div v-else class="text-center py-4 text-gray-500">
              No errors recorded
            </div>
          </div>
        </div>

        <!-- System Health -->
        <div class="card">
          <div class="card-header">
            <h3 class="font-semibold text-gray-900">System Health</h3>
          </div>
          <div class="card-body space-y-3">
            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-600">Overall Status</span>
              <span :class="['badge', healthStatus === 'healthy' ? 'badge-success' : healthStatus === 'degraded' ? 'badge-warning' : 'badge-error']">
                {{ healthStatus }}
              </span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-600">Uptime</span>
              <span class="font-semibold text-gray-900">99.9%</span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-600">Response Time</span>
              <span class="font-semibold text-gray-900">{{ dashboard?.avg_latency || 0 }}ms</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Last Updated -->
      <p class="text-xs text-gray-500 text-right">
        Last updated: {{ formatDate(metricsStore.lastUpdated) }}
      </p>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useMetricsStore } from '../../stores/metrics'
import { formatDate } from '../../utils/helpers'
import Button from '../common/Button.vue'
import Alert from '../common/Alert.vue'

/**
 * Metrics Dashboard Component
 * 
 * Displays performance metrics, system health, and analytics
 * Supports auto-refresh with configurable interval
 */

const metricsStore = useMetricsStore()

const autoRefresh = ref(false)
let autoRefreshTimer = null

const dashboard = computed(() => metricsStore.dashboard)
const latencyMetrics = computed(() => ({
  'P50': metricsStore.latency?.p50 || 0,
  'P95': metricsStore.latency?.p95 || 0,
  'P99': metricsStore.latency?.p99 || 0,
  'P99.9': metricsStore.latency?.p99_9 || 0,
}))
const cacheMetrics = computed(() => metricsStore.cache || {})
const errorMetrics = computed(() => metricsStore.errors || {})
const healthStatus = computed(() => metricsStore.health?.status || 'unknown')

const formatNumber = (num) => {
  return (num || 0).toLocaleString()
}

const refresh = async () => {
  await metricsStore.fetchAll()
}

const toggleAutoRefresh = () => {
  if (autoRefresh.value) {
    metricsStore.stopAutoRefresh()
    autoRefresh.value = false
  } else {
    metricsStore.startAutoRefresh(30000)
    autoRefresh.value = true
  }
}

onMounted(async () => {
  await refresh()
})

onUnmounted(() => {
  if (autoRefresh.value) {
    metricsStore.stopAutoRefresh()
  }
})
</script>
