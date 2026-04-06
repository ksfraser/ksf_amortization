<template>
  <div class="min-h-screen bg-gradient-to-br from-error-50 to-error-100 flex items-center justify-center px-4">
    <div class="text-center">
      <!-- 500 Number -->
      <div class="text-6xl font-bold text-error-600 mb-4">500</div>

      <!-- Message -->
      <h1 class="text-3xl font-bold text-gray-900 mb-2">Server Error</h1>
      <p class="text-gray-600 text-lg mb-8">
        Something went wrong on our end. Our team has been notified and is working on a fix.
      </p>

      <!-- Error Details -->
      <div class="bg-white rounded-lg shadow-lg p-6 max-w-md mx-auto mb-8 text-left">
        <p class="text-xs text-gray-500 font-mono break-all">
          Error ID: {{ errorId }}
        </p>
      </div>

      <!-- Illustration -->
      <div class="mb-8">
        <svg class="w-48 h-48 mx-auto text-error-300" fill="currentColor" viewBox="0 0 24 24">
          <path d="M1 21h22L12 2 1 21zm12-3h-2v-2h2v2zm0-4h-2v-4h2v4z" />
        </svg>
      </div>

      <!-- Buttons -->
      <div class="flex gap-4 justify-center">
        <button @click="goBack" class="btn btn-primary">
          Go Back
        </button>
        <RouterLink to="/dashboard" class="btn btn-secondary">
          Dashboard
        </RouterLink>
      </div>
    </div>
  </div>
</template>

<script setup>
import { useRouter } from 'vue-router'

/**
 * 500 Server Error Page
 */

const router = useRouter()

const errorId = `ERR_${Math.random().toString(36).substring(2, 11).toUpperCase()}`

const goBack = () => {
  router.back()
}
</script>
