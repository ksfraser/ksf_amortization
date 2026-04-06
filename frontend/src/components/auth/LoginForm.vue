<template>
  <form @submit.prevent="handleSubmit" class="space-y-4">
    <!-- Email Field -->
    <div>
      <label for="email" class="block text-sm font-medium text-gray-700 mb-1">
        Email Address
      </label>
      <input
        id="email"
        v-model="form.email"
        type="email"
        placeholder="you@example.com"
        required
        class="w-full"
        :disabled="isLoading"
        @input="errors.email = ''"
      />
      <p v-if="errors.email" class="text-error-600 text-sm mt-1">
        {{ errors.email }}
      </p>
    </div>

    <!-- Password Field -->
    <div>
      <div class="flex justify-between items-center mb-1">
        <label for="password" class="block text-sm font-medium text-gray-700">
          Password
        </label>
        <a href="#" class="text-sm text-primary-600 hover:text-primary-700">
          Forgot password?
        </a>
      </div>
      <input
        id="password"
        v-model="form.password"
        type="password"
        placeholder="••••••••"
        required
        class="w-full"
        :disabled="isLoading"
        @input="errors.password = ''"
      />
      <p v-if="errors.password" class="text-error-600 text-sm mt-1">
        {{ errors.password }}
      </p>
    </div>

    <!-- Remember Me -->
    <div class="flex items-center">
      <input
        id="remember"
        v-model="form.rememberMe"
        type="checkbox"
        class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded"
      />
      <label for="remember" class="ml-2 block text-sm text-gray-700">
        Remember me
      </label>
    </div>

    <!-- Submit Button -->
    <Button
      variant="primary"
      type="submit"
      class="w-full"
      :loading="isLoading"
      :disabled="isLoading"
    >
      Sign In
    </Button>

    <!-- Error Message -->
    <Alert
      v-if="generalError"
      type="error"
      title="Login Failed"
      :message="generalError"
      closable
      @close="generalError = ''"
    />

    <!-- Sign Up Link -->
    <p class="text-center text-sm text-gray-600">
      Don't have an account?
      <a href="#" class="text-primary-600 hover:text-primary-700 font-medium">
        Sign up
      </a>
    </p>
  </form>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../../stores/auth'
import { useNotificationStore } from '../../stores/ui'
import { getValidationErrors } from '../../utils/helpers'
import Button from '../common/Button.vue'
import Alert from '../common/Alert.vue'

/**
 * Login Form Component
 * 
 * Handles user authentication with email/password
 * Emits:
 * - success: When login succeeds
 * - error: When login fails
 */

const emit = defineEmits(['success', 'error'])

const router = useRouter()
const authStore = useAuthStore()
const notificationStore = useNotificationStore()

const form = reactive({
  email: '',
  password: '',
  rememberMe: false,
})

const isLoading = ref(false)
const generalError = ref('')
const errors = reactive({
  email: '',
  password: '',
})

const handleSubmit = async () => {
  // Clear previous errors
  Object.assign(errors, { email: '', password: '' })
  generalError.value = ''
  isLoading.value = true

  try {
    // Validate form
    if (!form.email) {
      errors.email = 'Email is required'
      return
    }
    if (!form.password) {
      errors.password = 'Password is required'
      return
    }

    // Submit login
    await authStore.login(form.email, form.password)

    // Show success notification
    notificationStore.success(`Welcome, ${authStore.userName}!`)

    // Emit success event
    emit('success')

    // Redirect to dashboard
    router.push('/dashboard')
  } catch (error) {
    // Handle validation errors
    const validationErrors = getValidationErrors(error)
    if (Object.keys(validationErrors).length > 0) {
      Object.assign(errors, validationErrors)
    } else {
      generalError.value = authStore.error || 'Login failed. Please try again.'
    }

    emit('error', generalError.value)
  } finally {
    isLoading.value = false
  }
}
</script>
