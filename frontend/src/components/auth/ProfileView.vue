<template>
  <div class="space-y-6">
    <!-- Profile Header -->
    <div class="card">
      <div class="card-body flex items-center gap-6">
        <div class="h-24 w-24 bg-primary-100 rounded-full flex items-center justify-center flex-shrink-0">
          <span class="text-4xl font-bold text-primary-700">
            {{ authStore.userName?.charAt(0).toUpperCase() }}
          </span>
        </div>
        <div class="flex-1">
          <h2 class="text-2xl font-bold text-gray-900">{{ authStore.userName }}</h2>
          <p class="text-gray-600">{{ authStore.userEmail }}</p>
          <p class="text-sm text-gray-500 mt-2">
            Member since {{ formatDate(joinDate) }}
          </p>
        </div>
        <Button variant="primary" @click="showEditForm = true">
          Edit Profile
        </Button>
      </div>
    </div>

    <!-- Edit Form (Modal) -->
    <div v-if="showEditForm" class="fixed inset-0 z-50 flex items-center justify-center">
      <div class="absolute inset-0 bg-black bg-opacity-50" @click="showEditForm = false" />
      <div class="relative bg-white rounded-lg shadow-lg max-w-md w-full mx-4">
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 rounded-t-lg">
          <h3 class="text-lg font-semibold text-gray-900">Edit Profile</h3>
        </div>

        <form @submit.prevent="handleUpdate" class="px-6 py-4 space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              Full Name
            </label>
            <input
              v-model="editForm.name"
              type="text"
              class="w-full"
              :disabled="isUpdating"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              Email
            </label>
            <input
              v-model="editForm.email"
              type="email"
              class="w-full"
              :disabled="isUpdating"
            />
          </div>

          <div class="flex gap-3 pt-4">
            <Button
              variant="secondary"
              class="flex-1"
              @click="showEditForm = false"
            >
              Cancel
            </Button>
            <Button
              variant="primary"
              class="flex-1"
              type="submit"
              :loading="isUpdating"
            >
              Save Changes
            </Button>
          </div>
        </form>
      </div>
    </div>

    <!-- Security Section -->
    <div class="card">
      <div class="card-header">
        <h3 class="font-semibold text-gray-900">Security</h3>
      </div>
      <div class="card-body space-y-4">
        <!-- Password -->
        <div class="flex items-center justify-between py-3 border-b border-gray-200 last:border-0">
          <div>
            <p class="font-medium text-gray-900">Password</p>
            <p class="text-sm text-gray-600">Last changed 90 days ago</p>
          </div>
          <Button variant="secondary" size="sm" @click="showPasswordForm = true">
            Change
          </Button>
        </div>

        <!-- Two-Factor Authentication -->
        <div class="flex items-center justify-between py-3">
          <div>
            <p class="font-medium text-gray-900">Two-Factor Authentication</p>
            <p class="text-sm text-gray-600">
              <span class="badge badge-error">Disabled</span>
            </p>
          </div>
          <Button variant="secondary" size="sm">
            Enable
          </Button>
        </div>
      </div>
    </div>

    <!-- Change Password Form -->
    <div v-if="showPasswordForm" class="fixed inset-0 z-50 flex items-center justify-center">
      <div class="absolute inset-0 bg-black bg-opacity-50" @click="showPasswordForm = false" />
      <div class="relative bg-white rounded-lg shadow-lg max-w-md w-full mx-4">
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 rounded-t-lg">
          <h3 class="text-lg font-semibold text-gray-900">Change Password</h3>
        </div>

        <form @submit.prevent="handleChangePassword" class="px-6 py-4 space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              Current Password
            </label>
            <input
              v-model="passwordForm.current"
              type="password"
              class="w-full"
              :disabled="isChangingPassword"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              New Password
            </label>
            <input
              v-model="passwordForm.new"
              type="password"
              class="w-full"
              :disabled="isChangingPassword"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              Confirm Password
            </label>
            <input
              v-model="passwordForm.confirm"
              type="password"
              class="w-full"
              :disabled="isChangingPassword"
            />
          </div>

          <div class="flex gap-3 pt-4">
            <Button
              variant="secondary"
              class="flex-1"
              @click="showPasswordForm = false"
            >
              Cancel
            </Button>
            <Button
              variant="primary"
              class="flex-1"
              type="submit"
              :loading="isChangingPassword"
            >
              Change Password
            </Button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { useAuthStore } from '../../stores/auth'
import { useNotificationStore } from '../../stores/ui'
import { formatDate } from '../../utils/helpers'
import Button from '../common/Button.vue'

/**
 * User Profile View Component
 * 
 * Displays user profile information and allows editing
 */

const authStore = useAuthStore()
const notificationStore = useNotificationStore()

const showEditForm = ref(false)
const showPasswordForm = ref(false)
const isUpdating = ref(false)
const isChangingPassword = ref(false)
const joinDate = ref(new Date(Date.now() - 365 * 24 * 60 * 60 * 1000))

const editForm = reactive({
  name: authStore.userName,
  email: authStore.userEmail,
})

const passwordForm = reactive({
  current: '',
  new: '',
  confirm: '',
})

const handleUpdate = async () => {
  isUpdating.value = true
  try {
    await authStore.updateProfile({
      name: editForm.name,
      email: editForm.email,
    })
    notificationStore.success('Profile updated successfully')
    showEditForm.value = false
  } catch (error) {
    notificationStore.error('Failed to update profile')
  } finally {
    isUpdating.value = false
  }
}

const handleChangePassword = async () => {
  if (passwordForm.new !== passwordForm.confirm) {
    notificationStore.error('Passwords do not match')
    return
  }

  isChangingPassword.value = true
  try {
    await authStore.changePassword(passwordForm.current, passwordForm.new)
    notificationStore.success('Password changed successfully')
    passwordForm.current = ''
    passwordForm.new = ''
    passwordForm.confirm = ''
    showPasswordForm.value = false
  } catch (error) {
    notificationStore.error('Failed to change password')
  } finally {
    isChangingPassword.value = false
  }
}
</script>
