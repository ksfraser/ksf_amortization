<template>
  <div class="card">
    <!-- Header -->
    <div class="card-header flex items-center justify-between">
      <h3 class="font-semibold text-gray-900">OAuth Clients</h3>
      <Button variant="primary" size="sm" @click="showCreateForm = true">
        + New Client
      </Button>
    </div>

    <!-- Search & Filter -->
    <div class="px-6 py-4 border-b border-gray-200 flex gap-4 items-center">
      <input
        type="text"
        placeholder="Search clients..."
        class="flex-1"
        @input="(e) => clientsStore.setSearchFilter(e.target.value)"
      />
      <select class="px-4 py-2 border border-gray-300 rounded-lg text-sm">
        <option>All Clients</option>
        <option>Active</option>
        <option>Inactive</option>
      </select>
    </div>

    <!-- Loading State -->
    <div v-if="clientsStore.isLoading" class="card-body text-center py-12">
      <div class="animate-spin">
        <svg class="w-8 h-8 text-primary-600 mx-auto" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
        </svg>
      </div>
    </div>

    <!-- Clients Table -->
    <div v-else-if="clientsStore.filteredClients.length > 0" class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b border-gray-200">
          <tr>
            <th class="px-6 py-3 text-left font-semibold text-gray-900">Client Name</th>
            <th class="px-6 py-3 text-left font-semibold text-gray-900">Client ID</th>
            <th class="px-6 py-3 text-left font-semibold text-gray-900">Created</th>
            <th class="px-6 py-3 text-center font-semibold text-gray-900">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="client in clientsStore.filteredClients" :key="client.client_id" class="border-b border-gray-200 hover:bg-gray-50">
            <td class="px-6 py-4 font-medium text-gray-900">{{ client.name }}</td>
            <td class="px-6 py-4 text-gray-600">
              <code class="bg-gray-100 px-2 py-1 rounded text-xs">{{ client.client_id }}</code>
            </td>
            <td class="px-6 py-4 text-gray-600">{{ formatDate(client.created_at) }}</td>
            <td class="px-6 py-4 text-center">
              <div class="flex gap-2 justify-center">
                <RouterLink
                  :to="`/admin/clients/${client.client_id}`"
                  class="text-primary-600 hover:text-primary-700 font-medium text-sm"
                >
                  View
                </RouterLink>
                <button
                  class="text-error-600 hover:text-error-700 font-medium text-sm"
                  @click="handleDelete(client.client_id)"
                >
                  Delete
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Empty State -->
    <div v-else class="card-body text-center py-12">
      <p class="text-gray-500">No clients found</p>
    </div>

    <!-- Create Client Modal -->
    <ClientForm
      v-if="showCreateForm"
      @save="handleCreate"
      @close="showCreateForm = false"
    />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useClientsStore } from '../../stores/clients'
import { useNotificationStore } from '../../stores/ui'
import { useModalStore } from '../../stores/ui'
import { formatDate } from '../../utils/helpers'
import Button from '../common/Button.vue'
import ClientForm from './ClientForm.vue'

/**
 * OAuth Clients List Component
 * 
 * Displays table of OAuth2 clients with CRUD operations
 */

const clientsStore = useClientsStore()
const notificationStore = useNotificationStore()
const modalStore = useModalStore()

const showCreateForm = ref(false)

onMounted(async () => {
  try {
    await clientsStore.fetchClients()
  } catch (error) {
    notificationStore.error('Failed to load clients')
  }
})

const handleCreate = async (clientData) => {
  try {
    await clientsStore.createClient(clientData)
    notificationStore.success('Client created successfully')
    showCreateForm.value = false
  } catch (error) {
    notificationStore.error('Failed to create client')
  }
}

const handleDelete = (clientId) => {
  modalStore.showDestructive(
    'Delete Client',
    'Are you sure? This action cannot be undone.',
    async () => {
      try {
        await clientsStore.deleteClient(clientId)
        notificationStore.success('Client deleted successfully')
      } catch (error) {
        notificationStore.error('Failed to delete client')
      }
    }
  )
}
</script>
