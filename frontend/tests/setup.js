import { config } from '@vue/test-utils'
import { vi, beforeAll, afterAll, afterEach } from 'vitest'
// WARNING: MSW temporarily disabled due to import issues
// import { setupServer } from 'msw/node'
// import * as handlers from './fixtures/mocks'

/**
 * Global Test Setup
 * 
 * Configures:
 * - Vue Test Utils
 * - Global mocks
 * - Environment variables
 * - API mocking (MSW)
 */

// Setup MSW server with all handlers
// const server = setupServer(...handlers.successHandlers)

// Start MSW server before all tests
beforeAll(() => {
  // server.listen({ onUnhandledRequest: 'warn' })
})

// Reset handlers between tests
afterEach(() => {
  // server.resetHandlers()
})

// Cleanup after all tests
afterAll(() => {
  // server.close()
})

// Mock window.matchMedia for responsive components
Object.defineProperty(window, 'matchMedia', {
  writable: true,
  value: vi.fn().mockImplementation(query => ({
    matches: false,
    media: query,
    onchange: null,
    addListener: vi.fn(),
    removeListener: vi.fn(),
    addEventListener: vi.fn(),
    removeEventListener: vi.fn(),
    dispatchEvent: vi.fn(),
  })),
})

// Mock window.scrollTo
window.scrollTo = vi.fn()

// Mock localStorage
const localStorageMock = {
  getItem: vi.fn(),
  setItem: vi.fn(),
  removeItem: vi.fn(),
  clear: vi.fn(),
}
global.localStorage = localStorageMock

// Mock sessionStorage
const sessionStorageMock = {
  getItem: vi.fn(),
  setItem: vi.fn(),
  removeItem: vi.fn(),
  clear: vi.fn(),
}
global.sessionStorage = sessionStorageMock

// Setup Vue Test Utils
config.global.stubs = {
  teleport: true,
  transition: false,
}

// Mock console methods to reduce noise in tests
const originalError = console.error
const originalWarn = console.warn

beforeAll(() => {
  console.error = vi.fn((...args) => {
    // Filter out expected Vue warnings
    const errorString = String(args[0])
    if (
      errorString.includes('[Vue warn]') ||
      errorString.includes('Not implemented: HTMLFormElement.prototype.submit')
    ) {
      return
    }
    originalError.call(console, ...args)
  })

  console.warn = vi.fn((...args) => {
    const warnString = String(args[0])
    if (warnString.includes('[Vue warn]')) {
      return
    }
    originalWarn.call(console, ...args)
  })
})

afterAll(() => {
  console.error = originalError
  console.warn = originalWarn
})

// Environment variables for tests
process.env.VITE_API_BASE_URL = 'http://api.test/api/v1'
process.env.VITE_OAUTH_CLIENT_ID = 'test-client'
process.env.VITE_APP_NAME = 'KSF Amortization Test'
