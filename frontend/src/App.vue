<template>
  <div id="app" class="min-h-screen flex flex-col bg-gray-50">
    <!-- Navigation -->
    <TopNavigation v-if="authStore.isAuthenticated" />

    <!-- Main Content -->
    <main class="flex-1">
      <!-- Router View for Pages -->
      <RouterView v-slot="{ Component }">
        <Transition name="fade" mode="out-in">
          <component :is="Component" :key="$route.fullPath" />
        </Transition>
      </RouterView>
    </main>

    <!-- Global Modals & Overlays -->
    <GlobalModal v-if="modalStore.isOpen" />
    <LoadingOverlay v-if="loadingStore.isLoading" />
  </div>
</template>

<script setup>
import { onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from './stores/auth'
import { useModalStore, useLoadingStore } from './stores/ui'
import TopNavigation from './components/common/TopNavigation.vue'
import GlobalModal from './components/common/GlobalModal.vue'
import LoadingOverlay from './components/common/LoadingOverlay.vue'

/**
 * Root Vue Component
 * 
 * Manages:
 * - Global layout and navigation
 * - Authentication state
 * - Modals and loading overlays
 * - Page routing with transitions
 */

const router = useRouter()
const authStore = useAuthStore()
const modalStore = useModalStore()
const loadingStore = useLoadingStore()

/**
 * Initialize app on mount
 * Check authentication status and redirect if needed
 */
onMounted(async () => {
  // Check if user is already authenticated from localStorage/session
  const token = localStorage.getItem('access_token')
  if (token) {
    authStore.setToken(token)
    try {
      await authStore.fetchCurrentUser()
    } catch (error) {
      console.error('Failed to load user:', error)
      authStore.logout()
      router.push('/login')
    }
  } else if (router.currentRoute.value.meta.requiresAuth) {
    // Redirect to login if trying to access protected route
    router.push('/login')
  }
})
</script>

<style scoped>
/*
 * Page Transition Animations
 */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
