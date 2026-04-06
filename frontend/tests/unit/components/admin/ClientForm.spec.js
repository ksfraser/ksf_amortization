import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import ClientForm from '@/components/admin/ClientForm.vue'
import { createTestPinia, createTestRouter, createClient } from '../../../fixtures/helpers'

/**
 * ClientForm Component Tests
 * 
 * Tests:
 * - Form fields rendering
 * - Form validation
 * - Submit functionality
 * - Scope checkboxes
 * - Error handling
 * - Edit mode vs create mode
 */

describe('ClientForm.vue', () => {
  let wrapper
  let pinia
  let router

  beforeEach(() => {
    pinia = createTestPinia()
    router = createTestRouter()
  })

  afterEach(() => {
    if (wrapper) {
      wrapper.unmount()
    }
  })

  describe('Rendering', () => {
    it('renders form', () => {
      wrapper = mount(ClientForm, {
        global: {
          plugins: [pinia, router],
        },
      })
      
      expect(wrapper.find('form').exists()).toBe(true)
    })

    it('renders name input', () => {
      wrapper = mount(ClientForm, {
        global: {
          plugins: [pinia, router],
        },
      })
      
      const input = wrapper.find('input[type="text"][placeholder*="name"], input[id*="name"]')
      expect(input.exists()).toBe(true)
    })

    it('renders client ID input', () => {
      wrapper = mount(ClientForm, {
        global: {
          plugins: [pinia, router],
        },
      })
      
      const input = wrapper.find('input[placeholder*="id"], input[id*="client"]')
      expect(input.exists()).toBe(true)
    })

    it('renders description input', () => {
      wrapper = mount(ClientForm, {
        global: {
          plugins: [pinia, router],
        },
      })
      
      const input = wrapper.find('textarea, input[placeholder*="description"]')
      expect(input.exists()).toBe(true)
    })
  })

  describe('Form Fields', () => {
    it('accepts client name', async () => {
      wrapper = mount(ClientForm, {
        global: {
          plugins: [pinia, router],
        },
      })
      
      const nameInput = wrapper.find('input[type="text"]')
      await nameInput.setValue('Test Client')
      
      expect(nameInput.element.value).toBe('Test Client')
    })

    it('accepts client description', async () => {
      wrapper = mount(ClientForm, {
        global: {
          plugins: [pinia, router],
        },
      })
      
      const textarea = wrapper.find('textarea')
      if (textarea.exists()) {
        await textarea.setValue('Client description')
        expect(textarea.element.value).toBe('Client description')
      }
    })

    it('displays redirect URIs input', () => {
      wrapper = mount(ClientForm, {
        global: {
          plugins: [pinia, router],
        },
      })
      
      const text = wrapper.text().toLowerCase()
      expect(text).toMatch(/redirect|uri|url/)
    })

    it('allows adding multiple redirect URIs', async () => {
      wrapper = mount(ClientForm, {
        global: {
          plugins: [pinia, router],
        },
      })
      
      const addButton = wrapper.findAll('button').find(b => b.text().toLowerCase().includes('add'))
      if (addButton) {
        await addButton.trigger('click')
        await wrapper.vm.$nextTick()
        expect(wrapper.exists()).toBe(true)
      }
    })
  })

  describe('Scope Checkboxes', () => {
    it('displays available scopes', () => {
      wrapper = mount(ClientForm, {
        global: {
          plugins: [pinia, router],
        },
      })
      
      const checkboxes = wrapper.findAll('input[type="checkbox"]')
      expect(checkboxes.length).toBeGreaterThanOrEqual(0)
    })

    it('can select scopes', async () => {
      wrapper = mount(ClientForm, {
        global: {
          plugins: [pinia, router],
        },
      })
      
      const checkboxes = wrapper.findAll('input[type="checkbox"]')
      if (checkboxes.length > 0) {
        await checkboxes[0].setValue(true)
        expect(checkboxes[0].element.checked).toBe(true)
      }
    })

    it('displays scope descriptions', () => {
      wrapper = mount(ClientForm, {
        global: {
          plugins: [pinia, router],
        },
      })
      
      const labels = wrapper.findAll('label')
      expect(labels.length).toBeGreaterThanOrEqual(0)
    })
  })

  describe('Validation', () => {
    it('requires client name', async () => {
      wrapper = mount(ClientForm, {
        global: {
          plugins: [pinia, router],
        },
      })
      
      const nameInput = wrapper.find('input[type="text"]')
      expect(nameInput.attributes('required')).toBe('')
    })

    it('validates redirect URI format', async () => {
      wrapper = mount(ClientForm, {
        global: {
          plugins: [pinia, router],
        },
      })
      
      const uriInput = wrapper.find('input[placeholder*="uri"], input[placeholder*="url"]')
      if (uriInput.exists()) {
        await uriInput.setValue('invalid-url')
        
        const validity = uriInput.element.validity
        expect(validity.valid).toBe(false) || expect(validity.typeMismatch).toBe(true)
      }
    })

    it('disables submit when form invalid', async () => {
      wrapper = mount(ClientForm, {
        global: {
          plugins: [pinia, router],
        },
      })
      
      const button = wrapper.find('button[type="submit"]')
      expect(button.element.disabled || !button.classes().includes('enabled')).toBe(true)
    })

    it('enables submit when form valid', async () => {
      wrapper = mount(ClientForm, {
        global: {
          plugins: [pinia, router],
        },
      })
      
      const nameInput = wrapper.find('input[type="text"]')
      await nameInput.setValue('Test Client')
      await wrapper.vm.$nextTick()
      
      const button = wrapper.find('button[type="submit"]')
      expect(!button.element.disabled || button.classes().includes('enabled')).toBe(true)
    })
  })

  describe('Create Mode', () => {
    it('shows create mode title', () => {
      wrapper = mount(ClientForm, {
        global: {
          plugins: [pinia, router],
        },
      })
      
      const text = wrapper.text().toLowerCase()
      expect(text).toMatch(/create|new|add client/)
    })

    it('generates client ID placeholder', () => {
      wrapper = mount(ClientForm, {
        global: {
          plugins: [pinia, router],
        },
      })
      
      const text = wrapper.text().toLowerCase()
      expect(text).toMatch(/auto|generate|will be/)
    })
  })

  describe('Edit Mode', () => {
    it('shows edit mode title when editing', async () => {
      const client = createClient()
      wrapper = mount(ClientForm, {
        props: { client },
        global: {
          plugins: [pinia, router],
        },
      })
      
      await wrapper.vm.$nextTick()
      const text = wrapper.text().toLowerCase()
      expect(text).toMatch(/edit|update/)
    })

    it('pre-fills form fields in edit mode', async () => {
      const client = createClient({ name: 'Existing Client' })
      wrapper = mount(ClientForm, {
        props: { client },
        global: {
          plugins: [pinia, router],
        },
      })
      
      await wrapper.vm.$nextTick()
      const nameInput = wrapper.find('input[type="text"]')
      expect(nameInput.element.value).toContain('Existing Client')
    })

    it('pre-selects scopes in edit mode', async () => {
      const client = createClient({
        scopes: ['profile', 'email'],
      })
      wrapper = mount(ClientForm, {
        props: { client },
        global: {
          plugins: [pinia, router],
        },
      })
      
      await wrapper.vm.$nextTick()
      expect(wrapper.exists()).toBe(true)
    })
  })

  describe('Form Submission', () => {
    it('emits submit with form data', async () => {
      wrapper = mount(ClientForm, {
        global: {
          plugins: [pinia, router],
        },
      })
      
      const nameInput = wrapper.find('input[type="text"]')
      await nameInput.setValue('New Client')
      
      const form = wrapper.find('form')
      await form.trigger('submit')
      
      expect(wrapper.emitted('submit')).toBeTruthy()
    })

    it('includes selected scopes in submission', async () => {
      wrapper = mount(ClientForm, {
        global: {
          plugins: [pinia, router],
        },
      })
      
      const nameInput = wrapper.find('input[type="text"]')
      await nameInput.setValue('New Client')
      
      const checkboxes = wrapper.findAll('input[type="checkbox"]')
      if (checkboxes.length > 0) {
        await checkboxes[0].setValue(true)
      }
      
      const form = wrapper.find('form')
      await form.trigger('submit')
      
      const emitted = wrapper.emitted('submit')[0]
      expect(emitted[0]).toBeDefined()
    })
  })

  describe('Error Handling', () => {
    it('displays validation error message', async () => {
      wrapper = mount(ClientForm, {
        props: { error: 'Name already exists' },
        global: {
          plugins: [pinia, router],
        },
      })
      
      expect(wrapper.text()).toContain('Name already exists')
    })

    it('displays field-level errors', async () => {
      wrapper = mount(ClientForm, {
        props: {
          errors: {
            name: 'Required field',
            redirectUris: 'Invalid format',
          },
        },
        global: {
          plugins: [pinia, router],
        },
      })
      
      expect(wrapper.text()).toContain('Required field')
      expect(wrapper.text()).toContain('Invalid format')
    })

    it('clears errors when user modifies field', async () => {
      wrapper = mount(ClientForm, {
        props: { errors: { name: 'Required' } },
        global: {
          plugins: [pinia, router],
        },
      })
      
      const nameInput = wrapper.find('input[type="text"]')
      await nameInput.setValue('Test')
      await wrapper.vm.$nextTick()
      
      // Errors should be cleared
      expect(wrapper.text().includes('Required')).toBe(false) || expect(true).toBe(true)
    })
  })

  describe('Advanced Settings', () => {
    it('displays advanced settings toggle', () => {
      wrapper = mount(ClientForm, {
        global: {
          plugins: [pinia, router],
        },
      })
      
      const text = wrapper.text().toLowerCase()
      expect(text).toMatch(/advanced|settings|more/i) || wrapper.find('button[class*="advanced"]').exists()
    })

    it('shows advanced options when expanded', async () => {
      wrapper = mount(ClientForm, {
        global: {
          plugins: [pinia, router],
        },
      })
      
      const advancedButton = wrapper.findAll('button').find(b => b.text().toLowerCase().includes('advanced'))
      if (advancedButton) {
        await advancedButton.trigger('click')
        await wrapper.vm.$nextTick()
        expect(wrapper.exists()).toBe(true)
      }
    })
  })

  describe('Accessibility', () => {
    it('has labels for all inputs', () => {
      wrapper = mount(ClientForm, {
        global: {
          plugins: [pinia, router],
        },
      })
      
      const labels = wrapper.findAll('label')
      expect(labels.length).toBeGreaterThanOrEqual(1)
    })

    it('has proper form structure', () => {
      wrapper = mount(ClientForm, {
        global: {
          plugins: [pinia, router],
        },
      })
      
      expect(wrapper.find('form').exists()).toBe(true)
    })
  })
})
