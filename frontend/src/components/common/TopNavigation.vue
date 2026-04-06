<template>
  <nav class="bg-white border-b border-gray-200 shadow-sm">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex justify-between h-16">
        <!-- Logo -->
        <div class="flex items-center gap-2">
          <div class="h-8 w-8 bg-primary-600 rounded-lg flex items-center justify-center">
            <span class="text-white font-bold text-lg">K</span>
          </div>
          <RouterLink to="/" class="text-xl font-bold text-primary-600">
            KSF Amortization
          </RouterLink>
        </div>

        <!-- Center Links (logged in) -->
        <div v-if="authStore.isAuthenticated" class="flex items-center gap-8 flex-1 justify-center">
          <RouterLink
            to="/dashboard"
            :class="[
              'text-sm font-medium transition-colors',
              isActive('/dashboard') ? 'text-primary-600 border-b-2 border-primary-600' : 'text-gray-600 hover:text-primary-600',
            ]"
          >
            Dashboard
          </RouterLink>

          <RouterLink
            to="/profile"
            :class="[
              'text-sm font-medium transition-colors',
              isActive('/profile') ? 'text-primary-600 border-b-2 border-primary-600' : 'text-gray-600 hover:text-primary-600',
            ]"
          >
            Profile
          </RouterLink>

          <RouterLink
            v-if="authStore.userRole === 'admin'"
            to="/admin"
            :class="[
              'text-sm font-medium transition-colors',
              isActive('/admin') ? 'text-primary-600 border-b-2 border-primary-600' : 'text-gray-600 hover:text-primary-600',
            ]"
          >
            Admin
          </RouterLink>
        </div>

        <!-- Right Side -->
        <div class="flex items-center gap-4">
          <!-- User Menu -->
          <div v-if="authStore.isAuthenticated" class="relative group">
            <button class="flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-lg">
              <span class="h-8 w-8 bg-primary-100 rounded-full flex items-center justify-center text-primary-700 font-semibold">
                {{ authStore.userName?.charAt(0).toUpperCase() }}
              </span>
              <span class="hidden sm:inline">{{ authStore.userName }}</span>
            </button>

            <!-- Dropdown Menu -->
            <div class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 z-50">
              <div class="px-4 py-2 border-b border-gray-200">
                <p class="text-sm font-medium text-gray-900">{{ authStore.userName }}</p>
                <p class="text-xs text-gray-500">{{ authStore.userEmail }}</p>
              </div>
              <RouterLink
                to="/profile"
                class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
              >
                Profile
              </RouterLink>
              <RouterLink
                to="/tokens"
                class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
              >
                My Tokens
              </RouterLink>
              <RouterLink
                to="/consents"
                class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
              >
                My Consents
              </RouterLink>
              <button
                class="w-full text-left px-4 py-2 text-sm text-error-600 hover:bg-error-50 border-t border-gray-200"
                @click="logout"
              >
                Logout
              </button>
            </div>
          </div>

          <!-- Login Button (not logged in) -->
          <RouterLink
            v-else
            to="/login"
            class="btn btn-primary btn-sm"
          >
            Login
          </RouterLink>
        </div>
      </div>
    </div>
  </nav>
</template>

<script setup>
import { useRouter } from 'vue-router'
import { useAuthStore } from '../../stores/auth'

/**
 * Top Navigation Component
 * 
 * Displays application header with:
 * - Logo
 * - Navigation links
 * - User menu
 * - Logout button
 */

const router = useRouter()
const authStore = useAuthStore()

const isActive = (path) => {
  return router.currentRoute.value.path.startsWith(path)
}

const logout = async () => {
  await authStore.logout()
  router.push('/login')
}
</script>
