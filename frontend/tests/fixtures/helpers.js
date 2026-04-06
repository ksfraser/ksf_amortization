import { createPinia, setActivePinia } from 'pinia'
import { createRouter, createMemoryHistory } from 'vue-router'
import { mount } from '@vue/test-utils'

/**
 * Test Utilities & Factories
 * 
 * Provides helper functions for creating test fixtures and instances
 */

/**
 * Create a new Pinia instance for tests
 * Isolates store state between tests
 */
export function createTestPinia() {
  const pinia = createPinia()
  setActivePinia(pinia)
  return pinia
}

/**
 * Create a Vue Router instance for tests
 * Uses in-memory history to avoid DOM pollution
 */
export function createTestRouter(routes = []) {
  return createRouter({
    history: createMemoryHistory(),
    routes: [
      {
        path: '/',
        component: { template: '<div></div>' },
      },
      ...routes,
    ],
  })
}

/**
 * Mount a Vue component with test utilities
 * Includes Pinia, Router, and global mocks
 */
export function mountWithDefaults(component, options = {}) {
  const pinia = createTestPinia()
  const router = createTestRouter(options.routes || [])

  return mount(component, {
    global: {
      plugins: [pinia, router],
      stubs: {
        teleport: true,
        transition: false,
        ...options.stubs,
      },
      mocks: {
        $t: (key) => key, // Mock i18n if used
        ...options.mocks,
      },
    },
    ...options,
  })
}

/**
 * Test Data Factories
 */

export const factories = {
  /**
   * Create a mock user object
   */
  createUser(overrides = {}) {
    return {
      id: 'user-123',
      email: 'user@example.com',
      name: 'Test User',
      role: 'user',
      createdAt: new Date().toISOString(),
      ...overrides,
    }
  },

  /**
   * Create a mock OAuth client object
   */
  createClient(overrides = {}) {
    return {
      id: 'client-' + Math.random().toString(36).substr(2, 9),
      name: 'Test Client',
      description: 'A test OAuth2 client',
      clientId: 'test-client-' + Math.random().toString(36).substr(2, 9),
      clientSecret: 'secret-' + Math.random().toString(36).substr(2, 9),
      redirectUris: ['http://localhost:3000/callback'],
      scopes: ['read', 'write', 'profile'],
      createdAt: new Date().toISOString(),
      ...overrides,
    }
  },

  /**
   * Create a mock token object
   */
  createToken(overrides = {}) {
    return {
      id: 'token-' + Math.random().toString(36).substr(2, 9),
      name: 'Test Token',
      token: 'eyJhbGc...' + Math.random().toString(36).substr(2, 9),
      createdAt: new Date().toISOString(),
      expiresAt: new Date(Date.now() + 30 * 24 * 60 * 60 * 1000).toISOString(), // 30 days
      lastUsedAt: new Date().toISOString(),
      ...overrides,
    }
  },

  /**
   * Create a mock consent object
   */
  createConsent(overrides = {}) {
    return {
      id: 'consent-' + Math.random().toString(36).substr(2, 9),
      clientName: 'Test App',
      scopes: ['read', 'profile'],
      grantedAt: new Date().toISOString(),
      lastUsedAt: new Date().toISOString(),
      ...overrides,
    }
  },

  /**
   * Create mock metrics
   */
  createMetrics(overrides = {}) {
    return {
      dashboard: {
        totalRequests: 15420,
        successRate: 99.85,
        averageLatency: 142,
        cacheHitRate: 87.5,
        errorsLast24h: 23,
        uptime: 99.99,
      },
      latency: {
        p50: 89,
        p95: 245,
        p99: 512,
        p999: 1204,
      },
      cache: {
        hits: 3247,
        misses: 487,
        hitRate: 87.5,
      },
      ...overrides,
    }
  },
}

/**
 * Assertion Helpers
 */

export const assertions = {
  /**
   * Assert component was rendered
   */
  assertRendered(wrapper, selector) {
    expect(wrapper.find(selector).exists()).toBe(true)
  },

  /**
   * Assert component emitted event with payload
   */
  assertEmitted(wrapper, eventName, payload) {
    expect(wrapper.emitted(eventName)).toBeTruthy()
    if (payload !== undefined) {
      expect(wrapper.emitted(eventName)[0]).toEqual([payload])
    }
  },

  /**
   * Assert form field has error
   */
  assertFormError(wrapper, fieldName, errorMessage) {
    const errors = wrapper.vm.$v?.[fieldName]?.$errors || []
    const hasError = errors.some((e) => e.$message === errorMessage)
    expect(hasError).toBe(true)
  },

  /**
   * Assert text content matches
   */
  assertText(wrapper, selector, text) {
    expect(wrapper.find(selector).text()).toContain(text)
  },

  /**
   * Assert element has class
   */
  assertHasClass(wrapper, selector, className) {
    expect(wrapper.find(selector).classes()).toContain(className)
  },

  /**
   * Assert element is disabled
   */
  assertDisabled(wrapper, selector) {
    expect(wrapper.find(selector).attributes('disabled')).toBeDefined()
  },

  /**
   * Assert element is visible
   */
  assertVisible(wrapper, selector) {
    expect(wrapper.find(selector).isVisible()).toBe(true)
  },
}

/**
 * Wait Helpers
 */

export const waiters = {
  /**
   * Wait for component update
   */
  async flushPromises() {
    return new Promise((resolve) => setTimeout(resolve, 0))
  },

  /**
   * Wait for specific condition
   */
  async waitFor(callback, timeout = 1000) {
    const start = Date.now()
    while (Date.now() - start < timeout) {
      try {
        if (callback()) return
      } catch (e) {
        //
      }
      await this.flushPromises()
    }
    throw new Error('Timeout waiting for condition')
  },

  /**
   * Wait for element to exist
   */
  async waitForElement(wrapper, selector, timeout = 1000) {
    return this.waitFor(() => wrapper.find(selector).exists(), timeout)
  },
}

/**
 * Mock API Response Helpers
 */

export const mockResponses = {
  /**
   * Create a successful API response
   */
  success(data) {
    return Promise.resolve({
      status: 200,
      data,
    })
  },

  /**
   * Create an error API response
   */
  error(message, status = 400) {
    return Promise.reject({
      response: {
        status,
        data: { error: message },
      },
    })
  },

  /**
   * Create a validation error response
   */
  validationError(fields) {
    return Promise.reject({
      response: {
        status: 422,
        data: {
          error: 'Validation Failed',
          fields,
        },
      },
    })
  },
}

/**
 * Setup/Teardown Helpers
 */

export function setupBeforeEach() {
  // Reset all mocks
  vi.clearAllMocks()
  
  // Reset localStorage
  localStorage.clear()
  vi.mocked(localStorage.getItem).mockReturnValue(null)
  vi.mocked(localStorage.setItem).mockImplementation(() => {})
  
  // Reset sessionStorage
  sessionStorage.clear()
  vi.mocked(sessionStorage.getItem).mockReturnValue(null)
  vi.mocked(sessionStorage.setItem).mockImplementation(() => {})
}

export function setupAfterEach() {
  // Cleanup after each test
  vi.clearAllMocks()
}

// Convenience exports for factory functions
export const createUser = factories.createUser.bind(factories)
export const createClient = factories.createClient.bind(factories)
export const createToken = factories.createToken.bind(factories)
export const createConsent = factories.createConsent.bind(factories)
export const createMetrics = factories.createMetrics.bind(factories)
