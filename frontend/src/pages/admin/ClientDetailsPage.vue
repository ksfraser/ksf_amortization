<template>
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-8 flex items-center justify-between">
      <div>
        <RouterLink to="/admin/clients" class="text-primary-600 text-sm font-medium hover:underline">
          ← Back to Clients
        </RouterLink>
        <h1 class="text-3xl font-bold text-gray-900 mt-2">Client Details</h1>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Main Card -->
      <div class="lg:col-span-2 card">
        <div class="card-header">
          <h3 class="font-semibold text-gray-900">{{ clientName }}</h3>
        </div>
        <div class="card-body space-y-6">
          <!-- Client ID -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Client ID</label>
            <code class="block w-full bg-gray-100 px-4 py-2 rounded font-mono text-sm text-gray-900">
              {{ clientId }}
            </code>
          </div>

          <!-- Client Secret -->
          <div>
            <div class="flex items-center justify-between mb-1">
              <label class="block text-sm font-medium text-gray-700">Client Secret</label>
              <Button size="sm" variant="secondary" @click="rotateSecret">
                🔄 Rotate
              </Button>
            </div>
            <code class="block w-full bg-gray-100 px-4 py-2 rounded font-mono text-sm text-gray-900">
              {{ showSecret ? clientSecret : '••••••••••••••••' }}
            </code>
          </div>

          <!-- Redirect URIs -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Redirect URIs</label>
            <div class="space-y-2">
              <div v-for="uri in redirectUris" :key="uri" class="bg-gray-50 px-4 py-2 rounded">
                {{ uri }}
              </div>
            </div>
          </div>

          <!-- Scopes -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Scopes</label>
            <div class="flex flex-wrap gap-2">
              <span v-for="scope in scopes" :key="scope" class="badge badge-primary">
                {{ scope }}
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- Sidebar -->
      <div class="space-y-4">
        <!-- Info Card -->
        <div class="card">
          <div class="card-header">
            <h4 class="font-semibold text-gray-900">Information</h4>
          </div>
          <div class="card-body space-y-3">
            <div>
              <p class="text-xs text-gray-600 font-medium">Created</p>
              <p class="text-sm text-gray-900">{{ createdAt }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-600 font-medium">Last Used</p>
              <p class="text-sm text-gray-900">{{ lastUsed }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-600 font-medium">Status</p>
              <span class="badge badge-success">Active</span>
            </div>
          </div>
        </div>

        <!-- Actions -->
        <div class="card">
          <div class="card-body space-y-2">
            <Button variant="secondary" class="w-full" @click="editClient">
              ✏️ Edit
            </Button>
            <Button variant="danger" class="w-full" @click="deleteClient">
              🗑️ Delete
            </Button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useNotificationStore } from '../../stores/ui'
import { useModalStore } from '../../stores/ui'
import Button from '../../components/common/Button.vue'

/**
 * Client Details Page
 * 
 * View and manage individual OAuth client
 */

const router = useRouter()
const route = useRoute()
const notificationStore = useNotificationStore()
const modalStore = useModalStore()

const clientId = ref(route.params.id)
const clientName = ref('Sample Application')
const clientSecret = ref('sk_secret_abcdefghijklmnopqrstuvwxyz123456789')
const showSecret = ref(false)
const redirectUris = ref([
  'https://example.com/callback',
  'https://example.com/auth',
])
const scopes = ref(['read', 'write', 'profile', 'email'])
const createdAt = ref('March 15, 2026')
const lastUsed = ref('4 hours ago')

const rotateSecret = () => {
  modalStore.showWarning(
    'Rotate Secret',
    'Rotating the secret will invalidate the current one. All applications using this secret will need to update.',
    () => {
      clientSecret.value = 'sk_secret_' + Math.random().toString(36).substring(2, 40)
      notificationStore.success('Secret rotated successfully')
    }
  )
}

const editClient = () => {
  notificationStore.info('Edit functionality coming soon')
}

const deleteClient = () => {
  modalStore.showDestructive(
    'Delete Client',
    'Are you sure? This action cannot be undone.',
    () => {
      notificationStore.success('Client deleted successfully')
      router.push('/admin/clients')
    }
  )
}
</script>
