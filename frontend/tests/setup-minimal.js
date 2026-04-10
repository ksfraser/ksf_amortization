// Minimal test setup for Vitest + Vue
import { config } from '@vue/test-utils'
import { vi } from 'vitest'

// Configure Vue Test Utils
config.global.stubs = {
  teleport: true,
  transition: false,
}

// Basic global mocks
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
}

// Environment variables for tests
process.env.VITE_API_BASE_URL = 'http://api.test/api/v1'
process.env.VITE_OAUTH_CLIENT_ID = 'test-client'
process.env.VITE_APP_NAME = 'KSF Amortization Test'
