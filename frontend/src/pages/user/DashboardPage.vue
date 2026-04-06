<template>
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div>
      <h1 class="text-3xl font-bold text-gray-900">Dashboard</h1>
      <p class="text-gray-600 mt-1">Welcome back, {{ authStore.userName }}!</p>
    </div>

    <!-- Quick Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-8">
      <!-- Active Tokens -->
      <div class="card">
        <div class="card-body">
          <p class="text-sm text-gray-600 font-medium">Active Tokens</p>
          <p class="text-3xl font-bold text-gray-900 mt-2">{{ activeTokens }}</p>
          <RouterLink to="/tokens" class="text-primary-600 text-sm font-medium mt-2 inline-block hover:underline">
            Manage tokens →
          </RouterLink>
        </div>
      </div>

      <!-- Authorized Apps -->
      <div class="card">
        <div class="card-body">
          <p class="text-sm text-gray-600 font-medium">Authorized Apps</p>
          <p class="text-3xl font-bold text-gray-900 mt-2">{{ authorizedApps }}</p>
          <RouterLink to="/consents" class="text-primary-600 text-sm font-medium mt-2 inline-block hover:underline">
            Review consents →
          </RouterLink>
        </div>
      </div>

      <!-- Account Status -->
      <div class="card">
        <div class="card-body">
          <p class="text-sm text-gray-600 font-medium">Account Status</p>
          <div class="flex items-center gap-2 mt-2">
            <span class="h-3 w-3 bg-success-600 rounded-full" />
            <span class="text-xl font-bold text-gray-900">Active</span>
          </div>
          <RouterLink to="/profile" class="text-primary-600 text-sm font-medium mt-2 inline-block hover:underline">
            View profile →
          </RouterLink>
        </div>
      </div>
    </div>

    <!-- Recent Activity -->
    <div class="mt-8 card">
      <div class="card-header">
        <h3 class="font-semibold text-gray-900">Recent Activity</h3>
      </div>
      <div class="card-body">
        <div class="space-y-4">
          <div v-for="activity in recentActivities" :key="activity.id" class="flex items-center justify-between py-3 border-b border-gray-200 last:border-0">
            <div>
              <p class="font-medium text-gray-900">{{ activity.action }}</p>
              <p class="text-sm text-gray-600">{{ activity.details }}</p>
            </div>
            <span class="text-xs text-gray-500">{{ formatRelativeTime(activity.timestamp) }}</span>
          </div>

          <div v-if="recentActivities.length === 0" class="text-center py-8 text-gray-500">
            No recent activity
          </div>
        </div>
      </div>
    </div>

    <!-- Quick Actions -->
    <div class="mt-8 grid grid-cols-1 md:grid-cols-2 gap-6">
      <Button variant="primary" class="w-full py-3" @click="navigateTo('/profile')">
        📝 Update Profile
      </Button>
      <Button variant="secondary" class="w-full py-3" @click="navigateTo('/tokens')">
        🔐 Manage Tokens
      </Button>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../../stores/auth'
import { formatRelativeTime } from '../../utils/helpers'
import Button from '../../components/common/Button.vue'

/**
 * User Dashboard Page
 * 
 * Displays user overview, stats, and quick actions
 */

const router = useRouter()
const authStore = useAuthStore()

const activeTokens = ref(2)
const authorizedApps = ref(3)
const recentActivities = ref([
  {
    id: 1,
    action: 'Token Created',
    details: 'New access token generated',
    timestamp: new Date(Date.now() - 2 * 3600000),
  },
  {
    id: 2,
    action: 'Profile Updated',
    details: 'Email address changed',
    timestamp: new Date(Date.now() - 24 * 3600000),
  },
  {
    id: 3,
    action: 'Consent Granted',
    details: 'Authorized new application',
    timestamp: new Date(Date.now() - 7 * 24 * 3600000),
  },
])

const navigateTo = (path) => {
  router.push(path)
}

onMounted(async () => {
  // Fetch dashboard data
})
</script>
