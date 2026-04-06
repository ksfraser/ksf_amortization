import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '../utils/api'

/**
 * OAuth Clients Store
 * 
 * Manages:
 * - OAuth2 client list
 * - Client CRUD operations
 * - Client filtering and pagination
 */

export const useClientsStore = defineStore('clients', () => {
  // State
  const clients = ref([])
  const isLoading = ref(false)
  const error = ref(null)
  const pagination = ref({
    limit: 20,
    offset: 0,
    total: 0,
  })
  const filters = ref({
    search: '',
    status: 'all', // all, active, inactive
  })

  // Computed
  const filteredClients = computed(() => {
    return clients.value.filter((client) => {
      if (filters.value.search) {
        const search = filters.value.search.toLowerCase()
        return (
          client.name.toLowerCase().includes(search) ||
          client.client_id.toLowerCase().includes(search)
        )
      }
      return true
    })
  })

  /**
   * Fetch all OAuth2 clients
   * @returns {Promise<array>} Clients list
   */
  async function fetchClients() {
    isLoading.value = true
    error.value = null

    try {
      const params = {
        limit: pagination.value.limit,
        offset: pagination.value.offset,
      }
      const response = await api.get('/admin/clients', { params })
      clients.value = response.data.clients
      pagination.value.total = response.data.total
      return response.data
    } catch (err) {
      error.value = err.response?.data?.message || 'Failed to fetch clients'
      throw err
    } finally {
      isLoading.value = false
    }
  }

  /**
   * Fetch single client details
   * @param {string} clientId Client ID
   * @returns {Promise<object>} Client data
   */
  async function fetchClient(clientId) {
    isLoading.value = true
    error.value = null

    try {
      const response = await api.get(`/admin/clients/${clientId}`)
      const index = clients.value.findIndex((c) => c.client_id === clientId)
      if (index !== -1) {
        clients.value[index] = response.data
      }
      return response.data
    } catch (err) {
      error.value = err.response?.data?.message || 'Failed to fetch client'
      throw err
    } finally {
      isLoading.value = false
    }
  }

  /**
   * Create new OAuth2 client
   * @param {object} clientData Client configuration
   * @returns {Promise<object>} Created client
   */
  async function createClient(clientData) {
    isLoading.value = true
    error.value = null

    try {
      const response = await api.post('/admin/clients', clientData)
      clients.value.unshift(response.data)
      return response.data
    } catch (err) {
      error.value = err.response?.data?.message || 'Failed to create client'
      throw err
    } finally {
      isLoading.value = false
    }
  }

  /**
   * Update OAuth2 client
   * @param {string} clientId Client ID
   * @param {object} updates Client updates
   * @returns {Promise<object>} Updated client
   */
  async function updateClient(clientId, updates) {
    isLoading.value = true
    error.value = null

    try {
      const response = await api.put(`/admin/clients/${clientId}`, updates)
      const index = clients.value.findIndex((c) => c.client_id === clientId)
      if (index !== -1) {
        clients.value[index] = response.data
      }
      return response.data
    } catch (err) {
      error.value = err.response?.data?.message || 'Failed to update client'
      throw err
    } finally {
      isLoading.value = false
    }
  }

  /**
   * Delete OAuth2 client
   * @param {string} clientId Client ID
   * @returns {Promise<void>}
   */
  async function deleteClient(clientId) {
    isLoading.value = true
    error.value = null

    try {
      await api.delete(`/admin/clients/${clientId}`)
      clients.value = clients.value.filter((c) => c.client_id !== clientId)
    } catch (err) {
      error.value = err.response?.data?.message || 'Failed to delete client'
      throw err
    } finally {
      isLoading.value = false
    }
  }

  /**
   * Rotate client secret
   * @param {string} clientId Client ID
   * @returns {Promise<object>} New secret
   */
  async function rotateSecret(clientId) {
    isLoading.value = true
    error.value = null

    try {
      const response = await api.post(
        `/admin/clients/${clientId}/rotate-secret`
      )
      const index = clients.value.findIndex((c) => c.client_id === clientId)
      if (index !== -1) {
        clients.value[index].client_secret = response.data.client_secret
      }
      return response.data
    } catch (err) {
      error.value = err.response?.data?.message || 'Failed to rotate secret'
      throw err
    } finally {
      isLoading.value = false
    }
  }

  /**
   * Set search filter
   * @param {string} search Search query
   */
  function setSearchFilter(search) {
    filters.value.search = search
  }

  /**
   * Set pagination
   * @param {number} limit Items per page
   * @param {number} offset Starting position
   */
  async function setPagination(limit, offset) {
    pagination.value.limit = limit
    pagination.value.offset = offset
    await fetchClients()
  }

  return {
    // State
    clients,
    isLoading,
    error,
    pagination,
    filters,

    // Computed
    filteredClients,

    // Actions
    fetchClients,
    fetchClient,
    createClient,
    updateClient,
    deleteClient,
    rotateSecret,
    setSearchFilter,
    setPagination,
  }
})
