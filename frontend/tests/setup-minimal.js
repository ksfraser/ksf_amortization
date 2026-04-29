// Minimal test setup for Vitest + Vue
import { config } from '@vue/test-utils'
import { vi } from 'vitest'

// Configure Vue Test Utils - disable most plugins to avoid hangs
config.global.stubs = {
  teleport: true,
  transition: false,
}

config.global.mocks = {
  $route: {
    params: {},
    query: {},
  },
  $router: {
    push: vi.fn(),
    go: vi.fn(),
  },
}

// Minimal window mocks
if (typeof window !== 'undefined') {
  window.scrollTo = vi.fn()
  
  // Mock matchMedia if not available
  if (!window.matchMedia) {
    window.matchMedia = vi.fn().mockImplementation(query => ({
      matches: false,
      media: query,
      onchange: null,
      addListener: vi.fn(),
      removeListener: vi.fn(),
      addEventListener: vi.fn(),
      removeEventListener: vi.fn(),
      dispatchEvent: vi.fn(),
    }))
  }
  
  // Mock localStorage
  if (!window.localStorage) {
    window.localStorage = {
      getItem: vi.fn(),
      setItem: vi.fn(),
      removeItem: vi.fn(),
      clear: vi.fn(),
    }
  }
}

// Environment variables for tests (lightweight)
process.env.VITE_API_BASE_URL = 'http://localhost:8000/api/v1'
process.env.VITE_OAUTH_CLIENT_ID = 'test-client'
process.env.VITE_APP_NAME = 'KSF Test'

// Global test timeout to prevent hangs
vi.setConfig({ testTimeout: 30000, hookTimeout: 30000 })

