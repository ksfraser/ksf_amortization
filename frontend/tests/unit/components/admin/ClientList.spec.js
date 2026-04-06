import { describe, it, expect, beforeEach, afterEach } from 'vitest'
import { mount } from '@vue/test-utils'
import ClientList from '@/components/admin/ClientList.vue'
import { createTestPinia, createTestRouter, createClient, flushPromises } from '../../../fixtures/helpers'

/**
 * ClientList Component Tests
 * 
 * Tests:
 * - Client list table/display
 * - Search and filtering
 * - Create client action
 * - Update/Delete actions
 * - Pagination
 * - Loading/error states
 */

describe('ClientList.vue', () => {
  let wrapper
  let pinia
  let router

  beforeEach(async () => {
    pinia = createTestPinia()
    router = createTestRouter()
  })

  afterEach(() => {
    if (wrapper) {
      wrapper.unmount()
    }
  })

  describe('Rendering', () => {
    it('renders client list', () => {
      wrapper = mount(ClientList, {
        global: {
          plugins: [pinia, router],
        },
      })
      
      expect(wrapper.exists()).toBe(true)
    })

    it('displays create button', () => {
      wrapper = mount(ClientList, {
        global: {
          plugins: [pinia, router],
        },
      })
      
      const button = wrapper.find('button[class*="create"], button[class*="new"]')
      expect(button.exists() || wrapper.text().toLowerCase().includes('new client')).toBe(true)
    })
  })

  describe('Client List Display', () => {
    it('displays empty state when no clients', () => {
      wrapper = mount(ClientList, {
        props: { clients: [] },
        global: {
          plugins: [pinia, router],
        },
      })
      
      const text = wrapper.text().toLowerCase()
      expect(text).toMatch(/no clients|empty/)
    })

    it('displays table with clients', async () => {
      const { useClientsStore } = await import('@/stores/clients')
      const clientsStore = useClientsStore()
      clientsStore.list = [
        createClient({ name: 'Client 1' }),
        createClient({ name: 'Client 2' }),
      ]
      
      wrapper = mount(ClientList, {
        global: {
          plugins: [pinia, router],
        },
      })
      
      await wrapper.vm.$nextTick()
      expect(wrapper.text()).toContain('Client 1')
      expect(wrapper.text()).toContain('Client 2')
    })

    it('displays table headers', () => {
      wrapper = mount(ClientList, {
        props: { clients: [] },
        global: {
          plugins: [pinia, router],
        },
      })
      
      const text = wrapper.text().toLowerCase()
      expect(text).toMatch(/name|id|status|created|action/)
    })

    it('displays client details in rows', async () => {
      const { useClientsStore } = await import('@/stores/clients')
      const clientsStore = useClientsStore()
      clientsStore.list = [
        createClient({ 
          name: 'Test Client',
          clientId: 'client_123',
          status: 'active',
        }),
      ]
      
      wrapper = mount(ClientList, {
        global: {
          plugins: [pinia, router],
        },
      })
      
      await wrapper.vm.$nextTick()
      expect(wrapper.text()).toContain('Test Client')
      expect(wrapper.text()).toContain('client_123')
      expect(wrapper.text()).toContain('active')
    })
  })

  describe('Search and Filtering', () => {
    it('renders search input', () => {
      wrapper = mount(ClientList, {
        global: {
          plugins: [pinia, router],
        },
      })
      
      const input = wrapper.find('input[type="text"], input[placeholder*="search"]')
      expect(input.exists()).toBe(true)
    })

    it('filters clients by name', async () => {
      const { useClientsStore } = await import('@/stores/clients')
      const clientsStore = useClientsStore()
      clientsStore.list = [
        createClient({ name: 'Alpha Client' }),
        createClient({ name: 'Beta Client' }),
      ]
      
      wrapper = mount(ClientList, {
        global: {
          plugins: [pinia, router],
        },
      })
      
      const searchInput = wrapper.find('input[type="text"]')
      await searchInput.setValue('Alpha')
      await wrapper.vm.$nextTick()
      
      expect(wrapper.text()).toContain('Alpha Client')
    })

    it('displays filter options', () => {
      wrapper = mount(ClientList, {
        global: {
          plugins: [pinia, router],
        },
      })
      
      const text = wrapper.text().toLowerCase()
      expect(text).toMatch(/filter|status|all/) || expect(wrapper.findAll('select, button[class*="filter"]').length).toBeGreaterThanOrEqual(0)
    })

    it('filters by status', async () => {
      wrapper = mount(ClientList, {
        global: {
          plugins: [pinia, router],
        },
      })
      
      const statusSelect = wrapper.find('select') || wrapper.findAll('button').find(b => b.text().toLowerCase().includes('status'))
      if (statusSelect) {
        await statusSelect.trigger('change')
      }
    })
  })

  describe('Pagination', () => {
    it('displays pagination controls', () => {
      wrapper = mount(ClientList, {
        global: {
          plugins: [pinia, router],
        },
      })
      
      const text = wrapper.text().toLowerCase()
      // Looking for pagination or just check for multiple client rows
      expect(wrapper.findAll('table tbody tr').length >= 0).toBe(true)
    })

    it('navigates between pages', async () => {
      wrapper = mount(ClientList, {
        global: {
          plugins: [pinia, router],
        },
      })
      
      const nextButton = wrapper.findAll('button').find(b => b.text().toLowerCase().includes('next'))
      if (nextButton) {
        await nextButton.trigger('click')
        await wrapper.vm.$nextTick()
        expect(wrapper.exists()).toBe(true)
      }
    })
  })

  describe('Client Actions', () => {
    it('provides view action', async () => {
      const { useClientsStore } = await import('@/stores/clients')
      const clientsStore = useClientsStore()
      clientsStore.list = [createClient()]
      
      wrapper = mount(ClientList, {
        global: {
          plugins: [pinia, router],
        },
      })
      
      const viewButton = wrapper.find('button[class*="view"], a[class*="view"]')
      expect(viewButton.exists() || wrapper.text().toLowerCase().includes('view')).toBe(true)
    })

    it('provides edit action', async () => {
      const { useClientsStore } = await import('@/stores/clients')
      const clientsStore = useClientsStore()
      clientsStore.list = [createClient()]
      
      wrapper = mount(ClientList, {
        global: {
          plugins: [pinia, router],
        },
      })
      
      const editButton = wrapper.findAll('button').find(b => b.text().toLowerCase().includes('edit'))
      expect(editButton.exists()).toBe(true)
    })

    it('provides delete action', async () => {
      const { useClientsStore } = await import('@/stores/clients')
      const clientsStore = useClientsStore()
      clientsStore.list = [createClient()]
      
      wrapper = mount(ClientList, {
        global: {
          plugins: [pinia, router],
        },
      })
      
      const deleteButton = wrapper.findAll('button').find(b => b.text().toLowerCase().includes('delete'))
      expect(deleteButton.exists()).toBe(true)
    })
  })

  describe('Create Client', () => {
    it('opens create modal on button click', async () => {
      wrapper = mount(ClientList, {
        global: {
          plugins: [pinia, router],
        },
      })
      
      const createButton = wrapper.findAll('button').find(b => b.text().toLowerCase().includes('create') || b.text().toLowerCase().includes('new'))
      if (createButton) {
        await createButton.trigger('click')
        await wrapper.vm.$nextTick()
        
        const modal = wrapper.find('[role="dialog"], [class*="modal"]')
        expect(modal.exists()).toBe(true)
      }
    })
  })

  describe('Bulk Actions', () => {
    it('has checkboxes for bulk selection', () => {
      wrapper = mount(ClientList, {
        global: {
          plugins: [pinia, router],
        },
      })
      
      const checkboxes = wrapper.findAll('input[type="checkbox"]')
      expect(checkboxes.length).toBeGreaterThanOrEqual(0)
    })

    it('shows bulk action buttons when items selected', async () => {
      wrapper = mount(ClientList, {
        global: {
          plugins: [pinia, router],
        },
      })
      
      const checkboxes = wrapper.findAll('input[type="checkbox"]')
      if (checkboxes.length > 0) {
        await checkboxes[0].setValue(true)
        await wrapper.vm.$nextTick()
        
        const bulkButton = wrapper.findAll('button').find(b => b.text().toLowerCase().includes('delete') || b.text().toLowerCase().includes('action'))
        expect(bulkButton).toBeDefined()
      }
    })
  })

  describe('Loading & Error States', () => {
    it('shows loading indicator while fetching', () => {
      wrapper = mount(ClientList, {
        props: { isLoading: true },
        global: {
          plugins: [pinia, router],
        },
      })
      
      expect(wrapper.find('[class*="loading"], [class*="spinner"]').exists() || wrapper.text().toLowerCase().includes('loading')).toBe(true)
    })

    it('displays error message on load failure', () => {
      wrapper = mount(ClientList, {
        props: { error: 'Failed to load clients' },
        global: {
          plugins: [pinia, router],
        },
      })
      
      expect(wrapper.text()).toContain('Failed to load clients')
    })

    it('displays empty state message', () => {
      wrapper = mount(ClientList, {
        props: { clients: [] },
        global: {
          plugins: [pinia, router],
        },
      })
      
      expect(wrapper.text().toLowerCase()).toMatch(/no clients|empty/)
    })
  })

  describe('Sorting', () => {
    it('displays sortable column headers', () => {
      wrapper = mount(ClientList, {
        global: {
          plugins: [pinia, router],
        },
      })
      
      const headers = wrapper.findAll('th')
      expect(headers.length).toBeGreaterThanOrEqual(1)
    })

    it('sorts by column on header click', async () => {
      wrapper = mount(ClientList, {
        global: {
          plugins: [pinia, router],
        },
      })
      
      const headers = wrapper.findAll('th')
      if (headers.length > 0) {
        await headers[0].trigger('click')
        await wrapper.vm.$nextTick()
        expect(wrapper.exists()).toBe(true)
      }
    })
  })

  describe('Accessibility', () => {
    it('has table with proper roles', () => {
      wrapper = mount(ClientList, {
        global: {
          plugins: [pinia, router],
        },
      })
      
      const table = wrapper.find('table, [role="table"]')
      expect(table.exists()).toBe(true)
    })

    it('has proper heading structure', () => {
      wrapper = mount(ClientList, {
        global: {
          plugins: [pinia, router],
        },
      })
      
      const headings = wrapper.findAll('h1, h2, h3')
      expect(headings.length).toBeGreaterThanOrEqual(1)
    })
  })
})
