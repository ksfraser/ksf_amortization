import { describe, it, expect, beforeEach } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useClientsStore } from '@/stores/clients'
import { createClient } from '../../fixtures/helpers'

/**
 * Clients Store Tests
 * 
 * Tests for client management state:
 * - Client list operations
 * - Filtering and searching
 * - CRUD operations
 * - Current client selection
 */

describe('Clients Store', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
  })

  describe('Initial State', () => {
    it('starts with empty list', () => {
      const store = useClientsStore()
      expect(store.list).toEqual([])
    })

    it('has no selected client initially', () => {
      const store = useClientsStore()
      expect(store.current).toBeNull()
    })

    it('has no filter initially', () => {
      const store = useClientsStore()
      expect(store.filter).toBe('')
    })
  })

  describe('Client Management', () => {
    it('adds client to list', () => {
      const store = useClientsStore()
      const client = createClient({ name: 'Test Client' })
      
      store.addClient(client)
      
      expect(store.list).toHaveLength(1)
      expect(store.list[0].name).toBe('Test Client')
    })

    it('sets list from array', () => {
      const store = useClientsStore()
      const clients = [
        createClient({ name: 'Client 1' }),
        createClient({ name: 'Client 2' }),
      ]
      
      store.setList(clients)
      
      expect(store.list).toHaveLength(2)
      expect(store.list[0].name).toBe('Client 1')
      expect(store.list[1].name).toBe('Client 2')
    })

    it('updates existing client', () => {
      const store = useClientsStore()
      const client = createClient({ id: 1, name: 'Original' })
      store.addClient(client)
      
      store.updateClient(1, { name: 'Updated' })
      
      expect(store.list[0].name).toBe('Updated')
      expect(store.list[0].id).toBe(1)
    })

    it('removes client from list', () => {
      const store = useClientsStore()
      store.addClient(createClient({ id: 1 }))
      store.addClient(createClient({ id: 2 }))
      
      expect(store.list).toHaveLength(2)
      
      store.removeClient(1)
      
      expect(store.list).toHaveLength(1)
      expect(store.list[0].id).toBe(2)
    })

    it('clears entire list', () => {
      const store = useClientsStore()
      store.addClient(createClient())
      store.addClient(createClient())
      
      expect(store.list).toHaveLength(2)
      
      store.clearList()
      
      expect(store.list).toHaveLength(0)
    })
  })

  describe('Current Client Selection', () => {
    it('selects client as current', () => {
      const store = useClientsStore()
      const client = createClient({ id: 1, name: 'Selected' })
      store.addClient(client)
      
      store.selectClient(client)
      
      expect(store.current).toEqual(client)
    })

    it('selects client by ID', () => {
      const store = useClientsStore()
      const client = createClient({ id: 42 })
      store.addClient(client)
      
      store.selectClientById(42)
      
      expect(store.current.id).toBe(42)
    })

    it('clears current selection', () => {
      const store = useClientsStore()
      store.selectClient(createClient())
      expect(store.current).not.toBeNull()
      
      store.clearCurrent()
      
      expect(store.current).toBeNull()
    })

    it('updates current client in list', () => {
      const store = useClientsStore()
      const client = createClient({ id: 1, name: 'Original' })
      store.addClient(client)
      store.selectClient(client)
      
      store.updateClient(1, { name: 'Updated' })
      
      expect(store.current.name).toBe('Updated')
    })
  })

  describe('Filtering', () => {
    it('filters clients by name', () => {
      const store = useClientsStore()
      store.setList([
        createClient({ name: 'Alpha Client' }),
        createClient({ name: 'Beta Client' }),
        createClient({ name: 'Alpha Company' }),
      ])
      
      store.setFilter('Alpha')
      
      expect(store.filtered).toHaveLength(2)
      expect(store.filtered.every(c => c.name.includes('Alpha'))).toBe(true)
    })

    it('returns all clients when filter is empty', () => {
      const store = useClientsStore()
      const clients = [
        createClient({ name: 'Client 1' }),
        createClient({ name: 'Client 2' }),
      ]
      store.setList(clients)
      store.setFilter('')
      
      expect(store.filtered).toHaveLength(2)
    })

    it('case-insensitive filtering', () => {
      const store = useClientsStore()
      store.setList([createClient({ name: 'TestClient' })])
      
      store.setFilter('test')
      
      expect(store.filtered).toHaveLength(1)
    })

    it('clears filter', () => {
      const store = useClientsStore()
      store.setList([
        createClient({ name: 'Alpha' }),
        createClient({ name: 'Beta' }),
      ])
      store.setFilter('Alpha')
      
      expect(store.filtered).toHaveLength(1)
      
      store.clearFilter()
      
      expect(store.filtered).toHaveLength(2)
    })
  })

  describe('Searching', () => {
    it('searches by client name', () => {
      const store = useClientsStore()
      store.setList([
        createClient({ name: 'Acme Inc', clientId: 'acme_1' }),
        createClient({ name: 'Beta Corp', clientId: 'beta_1' }),
      ])
      
      store.search('Acme')
      
      expect(store.filtered).toHaveLength(1)
      expect(store.filtered[0].name).toBe('Acme Inc')
    })

    it('searches by client ID', () => {
      const store = useClientsStore()
      store.setList([
        createClient({ name: 'Client 1', clientId: 'client_123' }),
        createClient({ name: 'Client 2', clientId: 'client_456' }),
      ])
      
      store.search('client_123')
      
      expect(store.filtered).toHaveLength(1)
      expect(store.filtered[0].clientId).toBe('client_123')
    })

    it('clears search results', () => {
      const store = useClientsStore()
      store.setList([
        createClient({ name: 'Client 1' }),
        createClient({ name: 'Client 2' }),
      ])
      store.search('Client 1')
      
      expect(store.filtered).toHaveLength(1)
      
      store.clearFilter()
      
      expect(store.filtered).toHaveLength(2)
    })
  })

  describe('Sorting', () => {
    it('sorts clients by name ascending', () => {
      const store = useClientsStore()
      store.setList([
        createClient({ name: 'Zebra' }),
        createClient({ name: 'Alpha' }),
        createClient({ name: 'Beta' }),
      ])
      
      store.sort('name', 'asc')
      
      expect(store.list[0].name).toBe('Alpha')
      expect(store.list[1].name).toBe('Beta')
      expect(store.list[2].name).toBe('Zebra')
    })

    it('sorts clients by name descending', () => {
      const store = useClientsStore()
      store.setList([
        createClient({ name: 'Alpha' }),
        createClient({ name: 'Zebra' }),
        createClient({ name: 'Beta' }),
      ])
      
      store.sort('name', 'desc')
      
      expect(store.list[0].name).toBe('Zebra')
      expect(store.list[1].name).toBe('Beta')
      expect(store.list[2].name).toBe('Alpha')
    })

    it('sorts by creation date', () => {
      const store = useClientsStore()
      store.setList([
        createClient({ name: 'C', createdAt: '2026-04-05' }),
        createClient({ name: 'A', createdAt: '2026-04-03' }),
        createClient({ name: 'B', createdAt: '2026-04-04' }),
      ])
      
      store.sort('createdAt', 'asc')
      
      expect(store.list[0].name).toBe('A')
      expect(store.list[1].name).toBe('B')
      expect(store.list[2].name).toBe('C')
    })
  })

  describe('Pagination', () => {
    it('sets page size', () => {
      const store = useClientsStore()
      store.setPageSize(10)
      
      expect(store.pageSize).toBe(10)
    })

    it('navigates to page', () => {
      const store = useClientsStore()
      store.goToPage(2)
      
      expect(store.currentPage).toBe(2)
    })

    it('calculates total pages', () => {
      const store = useClientsStore()
      store.setPageSize(10)
      store.setList(Array(25).fill(null).map((_, i) => createClient({ id: i })))
      
      expect(store.totalPages).toBe(3)
    })

    it('gets paginated results', () => {
      const store = useClientsStore()
      store.setPageSize(2)
      store.setList([
        createClient({ id: 1 }),
        createClient({ id: 2 }),
        createClient({ id: 3 }),
        createClient({ id: 4 }),
      ])
      
      store.goToPage(1)
      expect(store.paginated).toHaveLength(2)
      expect(store.paginated[0].id).toBe(1)
      
      store.goToPage(2)
      expect(store.paginated).toHaveLength(2)
      expect(store.paginated[0].id).toBe(3)
    })
  })

  describe('Client Status', () => {
    it('filters active clients', () => {
      const store = useClientsStore()
      store.setList([
        createClient({ status: 'active' }),
        createClient({ status: 'inactive' }),
        createClient({ status: 'active' }),
      ])
      
      expect(store.activeClients).toHaveLength(2)
    })

    it('filters inactive clients', () => {
      const store = useClientsStore()
      store.setList([
        createClient({ status: 'active' }),
        createClient({ status: 'inactive' }),
      ])
      
      expect(store.inactiveClients).toHaveLength(1)
    })

    it('updates client status', () => {
      const store = useClientsStore()
      store.addClient(createClient({ id: 1, status: 'active' }))
      
      store.updateClient(1, { status: 'inactive' })
      
      expect(store.list[0].status).toBe('inactive')
    })
  })

  describe('Statistics', () => {
    it('counts total clients', () => {
      const store = useClientsStore()
      store.setList([
        createClient(),
        createClient(),
        createClient(),
      ])
      
      expect(store.totalCount).toBe(3)
    })

    it('counts active clients', () => {
      const store = useClientsStore()
      store.setList([
        createClient({ status: 'active' }),
        createClient({ status: 'active' }),
        createClient({ status: 'inactive' }),
      ])
      
      expect(store.activeCount).toBe(2)
    })
  })

  describe('Error Handling', () => {
    it('stores error message', () => {
      const store = useClientsStore()
      const error = 'Failed to load clients'
      
      store.setError(error)
      
      expect(store.error).toBe(error)
    })

    it('clears error', () => {
      const store = useClientsStore()
      store.setError('Error message')
      
      store.clearError()
      
      expect(store.error).toBeNull() || expect(store.error).toBeUndefined()
    })
  })

  describe('Loading State', () => {
    it('sets loading state', () => {
      const store = useClientsStore()
      
      store.setLoading(true)
      expect(store.isLoading).toBe(true)
      
      store.setLoading(false)
      expect(store.isLoading).toBe(false)
    })
  })

  describe('Store Cleanup', () => {
    it('resets store to initial state', () => {
      const store = useClientsStore()
      store.setList([createClient(), createClient()])
      store.selectClient(createClient())
      store.setFilter('test')
      store.setError('Error')
      
      store.$reset()
      
      expect(store.list).toEqual([])
      expect(store.current).toBeNull()
      expect(store.filter).toBe('')
      expect(store.error).toBeNull() || expect(store.error).toBeUndefined()
    })
  })
})
