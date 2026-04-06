import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

/**
 * Clients Store (Simplified for Testing)
 * 
 * Manages:
 * - OAuth2 client list in memory
 * - Client CRUD operations
 * - Client filtering and searching
 * - Client selection
 */

export const useClientsStore = defineStore('clients', () => {
  // State
  const list = ref([])
  const current = ref(null)
  const filter = ref('')

  // Computed
  const filtered = computed(() => {
    if (!filter.value) {
      return list.value
    }
    const searchTerm = filter.value.toLowerCase()
    return list.value.filter((client) => {
      return (
        client.name?.toLowerCase().includes(searchTerm) ||
        client.clientId?.toLowerCase().includes(searchTerm) ||
        client.client_id?.toLowerCase().includes(searchTerm)
      )
    })
  })

  /**
   * Add client to list
   * @param {object} client Client data
   */
  function addClient(client) {
    list.value.push(client)
  }

  /**
   * Set entire list of clients
   * @param {array} clients Clients array
   */
  function setList(clients) {
    list.value = clients
  }

  /**
   * Update existing client by ID
   * @param {number|string} clientId Client ID
   * @param {object} updates Client updates
   */
  function updateClient(clientId, updates) {
    const index = list.value.findIndex((c) => c.id === clientId)
    if (index !== -1) {
      list.value[index] = { ...list.value[index], ...updates }
      // Also update current if it's the selected client
      if (current.value?.id === clientId) {
        current.value = { ...current.value, ...updates }
      }
    }
  }

  /**
   * Remove client from list
   * @param {number|string} clientId Client ID
   */
  function removeClient(clientId) {
    list.value = list.value.filter((c) => c.id !== clientId)
    if (current.value?.id === clientId) {
      current.value = null
    }
  }

  /**
   * Clear entire list
   */
  function clearList() {
    list.value = []
    current.value = null
  }

  /**
   * Select client as current
   * @param {object} client Client object
   */
  function selectClient(client) {
    current.value = client
  }

  /**
   * Select client by ID
   * @param {number|string} clientId Client ID
   */
  function selectClientById(clientId) {
    const client = list.value.find((c) => c.id === clientId)
    if (client) {
      current.value = client
    }
  }

  /**
   * Clear current selection
   */
  function clearCurrent() {
    current.value = null
  }

  /**
   * Set filter and update filtered list
   * @param {string} search Search term
   */
  function setFilter(search) {
    filter.value = search
  }

  /**
   * Alias for setFilter (search functionality)
   * @param {string} search Search term
   */
  function search(search) {
    setFilter(search)
  }

  /**
   * Clear filter
   */
  function clearFilter() {
    filter.value = ''
  }

  /**
   * Sort list by field
   * @param {string} field Field to sort by
   * @param {string} order Sort order (asc/desc)
   */
  function sort(field, order = 'asc') {
    list.value.sort((a, b) => {
      const aVal = a[field]
      const bVal = b[field]
      
      if (aVal < bVal) return order === 'asc' ? -1 : 1
      if (aVal > bVal) return order === 'asc' ? 1 : -1
      return 0
    })
  }

  return {
    // State
    list,
    current,
    filter,

    // Computed
    filtered,

    // Actions
    addClient,
    setList,
    updateClient,
    removeClient,
    clearList,
    selectClient,
    selectClientById,
    clearCurrent,
    setFilter,
    search,
    clearFilter,
    sort,
  }
})
