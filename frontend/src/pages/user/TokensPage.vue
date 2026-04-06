<template>
  <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="mb-8">
      <h1 class="text-3xl font-bold text-gray-900">My Tokens</h1>
      <p class="text-gray-600 mt-1">Manage your access tokens and API keys</p>
    </div>

    <!-- Token List -->
    <div class="space-y-4">
      <div v-for="token in tokens" :key="token.id" class="card">
        <div class="card-body">
          <div class="flex items-start justify-between">
            <div class="flex-1">
              <div class="flex items-center gap-2">
                <h3 class="font-semibold text-gray-900">{{ token.name }}</h3>
                <span :class="['badge', token.active ? 'badge-success' : 'badge-error']">
                  {{ token.active ? 'Active' : 'Revoked' }}
                </span>
              </div>
              <p class="text-sm text-gray-600 mt-1">
                <code class="bg-gray-100 px-2 py-1 rounded">{{ maskToken(token.value) }}</code>
              </p>
              <div class="flex gap-6 mt-3 text-sm text-gray-600">
                <span>Created: {{ formatDate(token.created_at) }}</span>
                <span v-if="token.expires_at">Expires: {{ formatDate(token.expires_at) }}</span>
                <span>Last used: {{ formatRelativeTime(token.last_used) }}</span>
              </div>
            </div>
            <div class="flex gap-2">
              <Button
                size="sm"
                variant="secondary"
                @click="copyToken(token.value)"
              >
                Copy
              </Button>
              <Button
                v-if="token.active"
                size="sm"
                variant="danger"
                @click="revokeToken(token.id)"
              >
                Revoke
              </Button>
            </div>
          </div>
        </div>
      </div>

      <div v-if="tokens.length === 0" class="card">
        <div class="card-body text-center py-12">
          <p class="text-gray-500">No tokens yet</p>
          <Button variant="primary" class="mt-4" @click="createNewToken">
            + Create Token
          </Button>
        </div>
      </div>
    </div>

    <!-- Create Token Button -->
    <div v-if="tokens.length > 0" class="mt-8">
      <Button variant="primary" @click="createNewToken">
        + Create New Token
      </Button>
    </div>

    <!-- Token Created Modal -->
    <div v-if="showNewTokenModal" class="fixed inset-0 z-50 flex items-center justify-center">
      <div class="absolute inset-0 bg-black bg-opacity-50" @click="showNewTokenModal = false" />
      <div class="relative bg-white rounded-lg shadow-lg max-w-md w-full mx-4">
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 rounded-t-lg">
          <h3 class="text-lg font-semibold text-gray-900">New Token Created</h3>
        </div>

        <div class="px-6 py-4 space-y-4">
          <div class="bg-warning-50 border border-warning-200 rounded-lg p-4">
            <p class="text-sm text-warning-800 font-medium">⚠️ Save your token</p>
            <p class="text-sm text-warning-700 mt-2">
              This is the only time you'll see this token. Store it somewhere safe.
            </p>
          </div>

          <div class="bg-gray-100 p-4 rounded-lg font-mono text-sm break-all select-all">
            {{ newToken }}
          </div>

          <div class="flex gap-3">
            <Button
              variant="secondary"
              class="flex-1"
              @click="copyNewToken"
            >
              Copy
            </Button>
            <Button
              variant="primary"
              class="flex-1"
              @click="showNewTokenModal = false"
            >
              Done
            </Button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useNotificationStore } from '../../stores/ui'
import { formatDate, formatRelativeTime, copyToClipboard } from '../../utils/helpers'
import Button from '../../components/common/Button.vue'

/**
 * Tokens Page
 * 
 * Manage user access tokens
 */

const notificationStore = useNotificationStore()

const tokens = ref([
  {
    id: 1,
    name: 'Development API Key',
    value: 'test_dev_apikey_1234567890abcdefghijklmnopqrstuvwxyz',
    active: true,
    created_at: new Date(Date.now() - 30 * 24 * 3600000),
    expires_at: null,
    last_used: new Date(Date.now() - 2 * 3600000),
  },
  {
    id: 2,
    name: 'Mobile App Token',
    value: 'test_mobile_app_token_abc123xyz789',
    active: true,
    created_at: new Date(Date.now() - 90 * 24 * 3600000),
    expires_at: new Date(Date.now() + 90 * 24 * 3600000),
    last_used: new Date(Date.now() - 1 * 3600000),
  },
])

const showNewTokenModal = ref(false)
const newToken = ref('')

const maskToken = (token) => {
  return token.substring(0, 10) + '...' + token.substring(token.length - 4)
}

const copyToken = async (token) => {
  const copied = await copyToClipboard(token)
  if (copied) {
    notificationStore.success('Token copied to clipboard')
  }
}

const copyNewToken = async () => {
  const copied = await copyToClipboard(newToken.value)
  if (copied) {
    notificationStore.success('Token copied to clipboard')
  }
}

const createNewToken = () => {
  newToken.value = 'token_' + Math.random().toString(36).substring(2, 40)
  tokens.value.unshift({
    id: tokens.value.length + 1,
    name: `Token ${tokens.value.length + 1}`,
    value: newToken.value,
    active: true,
    created_at: new Date(),
    expires_at: null,
    last_used: new Date(),
  })
  showNewTokenModal.value = true
}

const revokeToken = (tokenId) => {
  const token = tokens.value.find((t) => t.id === tokenId)
  if (token) {
    token.active = false
    notificationStore.success('Token revoked successfully')
  }
}
</script>
