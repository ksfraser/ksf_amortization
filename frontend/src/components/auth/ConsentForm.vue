<template>
  <form @submit.prevent="handleSubmit" class="space-y-6">
    <!-- App Info -->
    <div class="bg-primary-50 border border-primary-200 rounded-lg p-4">
      <div class="flex gap-4">
        <div class="h-16 w-16 bg-primary-200 rounded-lg flex items-center justify-center flex-shrink-0">
          <span class="text-primary-700 font-bold text-2xl">{{ appName.charAt(0) }}</span>
        </div>
        <div class="flex-1">
          <h3 class="font-semibold text-gray-900">{{ appName }}</h3>
          <p class="text-sm text-gray-600">wants to access your account</p>
        </div>
      </div>
    </div>

    <!-- Scopes -->
    <div class="space-y-3">
      <h4 class="font-semibold text-gray-900">This app will have access to:</h4>

      <div v-for="scope in scopes" :key="scope" class="flex items-start gap-3 px-4 py-2 bg-gray-50 rounded-lg">
        <svg class="h-5 w-5 text-green-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
        </svg>
        <div>
          <p class="font-medium text-gray-900">{{ formatScopeName(scope) }}</p>
          <p class="text-sm text-gray-600">{{ getScopeDescription(scope) }}</p>
        </div>
      </div>
    </div>

    <!-- Warning -->
    <Alert
      type="warning"
      title="Authorization Required"
      message="You are about to grant this application access to your account. Only proceed if you trust this application."
    />

    <!-- Action Buttons -->
    <div class="flex gap-3 pt-4">
      <Button
        variant="secondary"
        class="flex-1"
        @click="handleDeny"
        :disabled="isLoading"
      >
        Deny
      </Button>
      <Button
        variant="primary"
        class="flex-1"
        @click="handleApprove"
        :loading="isLoading"
        :disabled="isLoading"
      >
        Approve
      </Button>
    </div>

    <!-- Error Message -->
    <Alert
      v-if="error"
      type="error"
      title="Error"
      :message="error"
      @close="error = ''"
    />
  </form>
</template>

<script setup>
import { ref } from 'vue'
import Button from '../common/Button.vue'
import Alert from '../common/Alert.vue'

/**
 * Consent Form Component
 * 
 * Displays OAuth consent screen where user approves/denies scope access
 * 
 * Props:
 * - appName: Application name requesting access
 * - scopes: Array of requested scopes
 * 
 * Emits:
 * - approve: When user approves consent
 * - deny: When user denies consent
 */

const props = defineProps({
  appName: {
    type: String,
    required: true,
  },
  scopes: {
    type: Array,
    default: () => [],
  },
})

const emit = defineEmits(['approve', 'deny'])

const isLoading = ref(false)
const error = ref('')

const scopeDescriptions = {
  read: 'Read access to your profile and data',
  write: 'Write access to modify your data',
  profile: 'Access to your profile information',
  email: 'Access to your email address',
  offline_access: 'Access when you are not online',
}

const formatScopeName = (scope) => {
  return scope
    .split('_')
    .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
    .join(' ')
}

const getScopeDescription = (scope) => {
  return scopeDescriptions[scope] || 'Access to your account'
}

const handleApprove = async () => {
  isLoading.value = true
  try {
    emit('approve')
  } catch (err) {
    error.value = err.message || 'Failed to approve consent'
  } finally {
    isLoading.value = false
  }
}

const handleDeny = async () => {
  isLoading.value = true
  try {
    emit('deny')
  } catch (err) {
    error.value = err.message || 'Failed to deny consent'
  } finally {
    isLoading.value = false
  }
}
</script>
