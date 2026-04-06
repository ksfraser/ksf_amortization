<template>
  <div class="min-h-screen bg-gray-50 flex items-center justify-center px-4 py-12">
    <div class="w-full max-w-md">
      <div class="bg-white rounded-lg shadow-lg p-8">
        <!-- Header -->
        <div class="text-center mb-8">
          <h1 class="text-2xl font-bold text-gray-900">Authorization Required</h1>
          <p class="text-gray-600 mt-1">An application is requesting access to your account</p>
        </div>

        <!-- Consent Form -->
        <ConsentForm
          app-name="Third Party Application"
          :scopes="['read', 'profile', 'email']"
          @approve="handleApprove"
          @deny="handleDeny"
        />
      </div>
    </div>
  </div>
</template>

<script setup>
import { useRouter } from 'vue-router'
import { useNotificationStore } from '../../stores/ui'
import ConsentForm from '../../components/auth/ConsentForm.vue'

/**
 * Consent Page
 * 
 * Displays OAuth2 consent screen
 */

const router = useRouter()
const notificationStore = useNotificationStore()

const handleApprove = async () => {
  try {
    notificationStore.success('Consent approved')
    // In production, this would redirect to callback URL with auth code
    router.push('/dashboard')
  } catch (error) {
    notificationStore.error('Failed to approve consent')
  }
}

const handleDeny = async () => {
  try {
    notificationStore.warning('Consent denied')
    // In production, this would redirect with error
    router.push('/')
  } catch (error) {
    notificationStore.error('Failed to deny consent')
  }
}
</script>
