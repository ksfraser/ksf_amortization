import { describe, it, expect, beforeEach, afterEach } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'

/**
 * Simple Pinia Store Test
 * 
 * Test loading a store without components to isolate issues
 */

describe('Pinia Initialization', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
  })

  it('should create a Pinia instance', () => {
    expect(true).toBe(true)
  })

  it('should allow creating stores', () => {
    // Just verify Pinia is accessible
    const pinia = createPinia()
    expect(pinia).toBeDefined()
    expect(pinia._s).toBeDefined()
  })
})
