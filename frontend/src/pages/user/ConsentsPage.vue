<template>
  <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="mb-8">
      <h1 class="text-3xl font-bold text-gray-900">My Consents</h1>
      <p class="text-gray-600 mt-1">Review and manage applications with access to your account</p>
    </div>

    <!-- Consents List -->
    <div class="space-y-4">
      <div v-for="consent in consents" :key="consent.id" class="card">
        <div class="card-body">
          <div class="flex items-start justify-between">
            <div class="flex-1">
              <div class="flex items-center gap-3">
                <div class="h-12 w-12 bg-primary-100 rounded-lg flex items-center justify-center flex-shrink-0">
                  <span class="text-primary-700 font-bold">{{ consent.client_name.charAt(0) }}</span>
                </div>
                <div>
                  <h3 class="font-semibold text-gray-900">{{ consent.client_name }}</h3>
                  <p class="text-sm text-gray-600">Granted {{ formatRelativeTime(consent.granted_at) }}</p>
                </div>
              </div>

              <!-- Scopes -->
              <div class="mt-3 flex flex-wrap gap-2">
                <span v-for="scope in consent.scopes" :key="scope" class="badge badge-primary">
                  {{ scope }}
                </span>
              </div>

              <!-- Last Access -->
              <p class="text-xs text-gray-500 mt-3">
                Last used: {{ formatRelativeTime(consent.last_used) }}
              </p>
            </div>

            <!-- Revoke Button -->
            <Button
              size="sm"
              variant="danger"
              @click="revokeConsent(consent.id)"
            >
              Revoke
            </Button>
          </div>
        </div>
      </div>

      <div v-if="consents.length === 0" class="card">
        <div class="card-body text-center py-12">
          <p class="text-gray-500">No authorized applications yet</p>
        </div>
      </div>
    </div>

    <!-- Info Box -->
    <Alert
      type="info"
      title="About Consents"
      class="mt-8"
      message="Consents allow applications to access your account with the permissions you grant. You can revoke access at any time."
    />
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useNotificationStore } from '../../stores/ui'
import { useModalStore } from '../../stores/ui'
import { formatRelativeTime } from '../../utils/helpers'
import Button from '../../components/common/Button.vue'
import Alert from '../../components/common/Alert.vue'

/**
 * Consents Page
 * 
 * Manage user consents for third-party applications
 */

const notificationStore = useNotificationStore()
const modalStore = useModalStore()

const consents = ref([
  {
    id: 1,
    client_name: 'Analytics Dashboard',
    scopes: ['read', 'profile'],
    granted_at: new Date(Date.now() - 60 * 24 * 3600000),
    last_used: new Date(Date.now() - 1 * 24 * 3600000),
  },
  {
    id: 2,
    client_name: 'Mobile Application',
    scopes: ['read', 'write', 'profile', 'email'],
    granted_at: new Date(Date.now() - 30 * 24 * 3600000),
    last_used: new Date(Date.now() - 2 * 3600000),
  },
  {
    id: 3,
    client_name: 'Third Party Integration',
    scopes: ['read'],
    granted_at: new Date(Date.now() - 7 * 24 * 3600000),
    last_used: new Date(Date.now() - 15 * 24 * 3600000),
  },
])

const revokeConsent = (consentId) => {
  modalStore.showWarning(
    'Revoke Consent',
    'Are you sure? The application will no longer have access to your account.',
    () => {
      consents.value = consents.value.filter((c) => c.id !== consentId)
      notificationStore.success('Consent revoked successfully')
    }
  )
}
</script>
