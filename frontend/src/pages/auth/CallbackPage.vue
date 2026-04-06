<template>
  <div class="flex items-center justify-center min-h-screen bg-gray-50">
    <div class="text-center">
      <div class="animate-spin">
        <svg class="w-16 h-16 text-primary-600 mx-auto" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
        </svg>
      </div>
      <h2 class="text-xl font-semibold text-gray-900 mt-6">Processing authorization...</h2>
      <p class="text-gray-600 mt-2">Please wait while we complete your authentication</p>
    </div>
  </div>
</template>

<script setup>
import { onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '../../stores/auth'
import { useNotificationStore } from '../../stores/ui'

/**
 * OAuth Callback Page
 * 
 * Handles the OAuth2 callback after user authorization
 */

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()
const notificationStore = useNotificationStore()

onMounted(async () => {
  try {
    const { code, state, error } = route.query

    // Check for errors from authorization server
    if (error) {
      notificationStore.error(`Authorization denied: ${error}`)
      router.push('/login')
      return
    }

    if (!code) {
      notificationStore.error('No authorization code received')
      router.push('/login')
      return
    }

    // In production, exchange code for token
    // For now, just redirect to dashboard
    notificationStore.success('Authorization successful')
    router.push('/dashboard')
  } catch (error) {
    notificationStore.error('Authorization failed')
    router.push('/login')
  }
})
</script>
