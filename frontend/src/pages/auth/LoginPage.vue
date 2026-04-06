<template>
  <div class="min-h-screen bg-gradient-to-br from-primary-50 to-primary-100 flex items-center justify-center px-4">
    <div class="w-full max-w-md">
      <!-- Card -->
      <div class="bg-white rounded-lg shadow-lg p-8">
        <!-- Header -->
        <div class="text-center mb-8">
          <div class="h-16 w-16 bg-primary-600 rounded-lg flex items-center justify-center mx-auto mb-4">
            <span class="text-white font-bold text-3xl">K</span>
          </div>
          <h1 class="text-2xl font-bold text-gray-900">KSF Amortization</h1>
          <p class="text-gray-600 mt-1">Sign in to your account</p>
        </div>

        <!-- Login Form -->
        <LoginForm @success="onLoginSuccess" @error="onLoginError" />
      </div>

      <!-- Footer -->
      <p class="text-center text-sm text-gray-600 mt-6">
        Protected by world-class security
      </p>
    </div>
  </div>
</template>

<script setup>
import { useRouter } from 'vue-router'
import LoginForm from '../../components/auth/LoginForm.vue'

/**
 * Login Page
 * 
 * Handles user authentication
 */

const router = useRouter()

const onLoginSuccess = () => {
  // Redirect handled by form component
}

const onLoginError = (error) => {
  console.error('Login error:', error)
}
</script>
