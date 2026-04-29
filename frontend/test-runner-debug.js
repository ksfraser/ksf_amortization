#!/usr/bin/env node
/**
 * Vitest Hang Fix & Test Runner
 * 
 * Attempts to run vitest with proper timeout handling and error capturing
 * This script:
 * 1. Runs the simplest possible test first
 * 2. Gradually increases complexity
 * 3. Provides detailed debugging output
 */

const { spawn } = require('child_process')
const fs = require('fs')
const path = require('path')

const RED = '\x1b[31m'
const GREEN = '\x1b[32m'
const YELLOW = '\x1b[33m'
const CYAN = '\x1b[36m'
const RESET = '\x1b[0m'

function log(color, ...args) {
  console.log(color + args.join(' ') + RESET)
}

async function runTest(configFile, testPattern, timeoutSeconds = 45) {
  return new Promise((resolve) => {
    const cwd = __dirname
    const args = [
      'test',
      '--config', configFile,
      '--run',
      '--reporter=verbose',
      '--no-coverage',
      '--detect-open-handles', // Detect unresolved promises
    ]
    
    if (testPattern) {
      args.push('--include', testPattern)
    }

    log(CYAN, `\n📝 Running: npx vitest ${args.join(' ')}`)

    const proc = spawn('npx', ['vitest', ...args], {
      cwd,
      shell: true,
      timeout: timeoutSeconds * 1000,
    })

    let output = ''
    let errorOutput = ''

    proc.stdout.on('data', (data) => {
      output += data.toString()
      process.stdout.write(data)
    })

    proc.stderr.on('data', (data) => {
      errorOutput += data.toString()
      process.stderr.write(data)
    })

    // Kill after timeout
    const timer = setTimeout(() => {
      log(YELLOW, `\n⏱️  Timeout reached (${timeoutSeconds}s), killing process...`)
      proc.kill('SIGTERM')
      
      setTimeout(() => {
        if (!proc.killed) {
          proc.kill('SIGKILL')
        }
      }, 5000)
    }, timeoutSeconds * 1000)

    proc.on('exit', (code, signal) => {
      clearTimeout(timer)
      
      if (code === 0) {
        log(GREEN, `\n✅ Tests passed!`)
        resolve({ success: true, output, errorOutput })
      } else if (signal) {
        log(YELLOW, `\n⚠️  Process was killed (signal: ${signal})`)
        resolve({ success: false, killed: true, output, errorOutput })
      } else {
        log(RED, `\n❌ Tests failed (exit code: ${code})`)
        resolve({ success: false, killed: false, output, errorOutput })
      }
    })

    proc.on('error', (error) => {
      clearTimeout(timer)
      log(RED, `\n❌ Error spawning process:`, error.message)
      resolve({ success: false, error: error.message, output, errorOutput })
    })
  })
}

async function main() {
  log(CYAN, '═══════════════════════════════════════════════════')
  log(CYAN, '  Vitest Hang Fix & Test Runner v1.0')
  log(CYAN, '═══════════════════════════════════════════════════')

  log(YELLOW, '\n📋 Test Strategy:')
  log(CYAN, '  1. Run minimal/simplified config')
  log(CYAN, '  2. Run basic pure math tests only')
  log(CYAN, '  3. If successful, run all tests')
  
  // Phase 1: Minimal config, basic tests only
  log(YELLOW, '\n\n🔍 Phase 1: Basic Tests with Minimal Config')
  log(CYAN, '━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━')
  let result = await runTest('vitest.config.simple.js', 'tests/unit/basic.spec.js', 30)
  
  if (result.success) {
    log(GREEN, '\n✅ Phase 1 passed! Basic tests working.')
    
    // Phase 2: Full config, basic tests
    log(YELLOW, '\n\n🔍 Phase 2: Basic Tests with Full Config')
    log(CYAN, '━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━')
    result = await runTest('vitest.config.js', 'tests/unit/basic.spec.js', 30)
    
    if (result.success) {
      log(GREEN, '\n✅ Phase 2 passed! Can run with full config.')
      
      // Phase 3: All tests
      log(YELLOW, '\n\n🔍 Phase 3: All Tests with Full Config')
      log(CYAN, '━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━')
      result = await runTest('vitest.config.js', 'tests/**/*.spec.js', 60)
      
      if (result.success) {
        log(GREEN, '\n✅ Phase 3 passed! All tests working!')
      } else {
        log(YELLOW, '\n⚠️  Phase 3 failed, but phases 1-2 work.')
        log(CYAN, '\nTroubleshooting:')
        log(CYAN, '  - Check test output above for failed tests')
        log(CYAN, '  - Review failing test files for import issues')
        log(CYAN, '  - Check if specific test suite is causing hang')
      }
    } else {
      log(YELLOW, '\n⚠️  Phase 2 failed (full config issue)')
      log(CYAN, '\nSolution: Use simplified config')
      log(CYAN, '  npm test -- --config vitest.config.simple.js')
    }
  } else {
    log(RED, '\n❌ Phase 1 failed! Basic tests not running.')
    log(YELLOW, '\nDiagnostics:')
    log(CYAN, '  - Kill timeout happened, vitest is hanging')
    log(CYAN, '  - Check if Node.js/npm dependencies installed')
    log(CYAN, '  - Try: npm install')
    log(CYAN, '  - Check for circular dependencies or syntax errors')
  }

  log(CYAN, '\n\n═══════════════════════════════════════════════════')
  log(CYAN, '  Test Execution Complete')
  log(CYAN, '═══════════════════════════════════════════════════\n')
}

main().catch(err => {
  log(RED, 'Fatal error:', err.message)
  process.exit(1)
})
