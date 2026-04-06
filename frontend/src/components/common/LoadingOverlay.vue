<template>
  <transition name="fade">
    <div v-if="isLoading" class="overlay fixed inset-0 z-40 flex items-center justify-center bg-black bg-opacity-25 backdrop-blur-sm transition">
      <div class="bg-white rounded-lg shadow-lg px-8 py-8">
        <div class="flex flex-col items-center gap-4">
          <!-- Spinner -->
          <div class="animate-spin">
            <svg
              class="w-12 h-12 text-primary-600"
              xmlns="http://www.w3.org/2000/svg"
              fill="none"
              viewBox="0 0 24 24"
            >
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
              <path
                class="opacity-75"
                fill="currentColor"
                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
              />
            </svg>
          </div>

          <!-- Message -->
          <p class="text-gray-700 font-medium">{{ message || 'Please wait...' }}</p>
        </div>
      </div>
    </div>
  </transition>
</template>

<script setup>
/**
 * Loading Overlay Component
 * 
 * Displays full-screen loading overlay with message
 * Accepts props for isLoading and message
 */

const props = defineProps({
  isLoading: {
    type: Boolean,
    required: true,
  },
  message: {
    type: String,
    default: 'Please wait...',
  },
})
</script>
