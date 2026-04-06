<template>
  <div class="min-h-screen bg-gradient-to-br from-primary-50 to-primary-100 flex items-center justify-center px-4">
    <div class="text-center">
      <!-- 404 Number -->
      <div class="text-6xl font-bold text-primary-600 mb-4">404</div>

      <!-- Message -->
      <h1 class="text-3xl font-bold text-gray-900 mb-2">Page Not Found</h1>
      <p class="text-gray-600 text-lg mb-8">
        Sorry, the page you're looking for doesn't exist or has been moved.
      </p>

      <!-- Illustration -->
      <div class="mb-8">
        <svg class="w-48 h-48 mx-auto text-gray-300" fill="currentColor" viewBox="0 0 24 24">
          <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z" />
        </svg>
      </div>

      <!-- Buttons -->
      <div class="flex gap-4 justify-center">
        <RouterLink to="/dashboard" class="btn btn-primary">
          Back to Dashboard
        </RouterLink>
        <RouterLink to="/" class="btn btn-secondary">
          Home
        </RouterLink>
      </div>
    </div>
  </div>
</template>

<script setup>
/**
 * 404 Not Found Error Page
 */
</script>
