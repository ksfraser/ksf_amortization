#!/usr/bin/env node

/**
 * Direct test runner using Vitest programmatically
 */

import { startVitest } from 'vitest/node'
import { fileURLToPath } from 'url'
import { dirname } from 'path'

const __dirname = dirname(fileURLToPath(import.meta.url))

async function runTests() {
  process.chdir(__dirname)
  
  console.log('[TEST RUNNER] Starting Vitest...')
  console.log('[TEST RUNNER] Working directory:', process.cwd())
  
  try {
    const vitest = await startVitest('run', [], {
      watch: false,
      globals: true,
      environment: 'happy-dom',
      reporter: 'verbose',
    })
    
    if (vitest?.exitCode === 0) {
      console.log('[TEST RUNNER] ✓ All tests passed!')
      process.exit(0)
    } else {
      console.log('[TEST RUNNER] ✗ Some tests failed')
      process.exit(vitest?.exitCode || 1)
    }
  } catch (error) {
    console.error('[TEST RUNNER] Error running tests:')
    console.error(error.message)
    console.error(error.stack)
    process.exit(1)
  }
}

runTests()
