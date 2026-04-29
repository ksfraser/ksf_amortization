#!/usr/bin/env node
/**
 * Vitest Runner - Debug & Execute Tests
 * 
 * This script:
 * 1. Runs vitest with limited scope
 * 2. Captures errors to help debug
 * 3. Outputs to file and console
 */

const { spawn } = require('child_process')
const fs = require('fs')
const path = require('path')

const logFile = path.join(__dirname, 'test-run-debug.log')
const outputStream = fs.createWriteStream(logFile)

console.log('🧪 Starting Vitest with Debug Output')
console.log(`📝 Logging to: ${logFile}\n`)

// Run with simple config first
const args = [
  'test',
  '--config', 'vitest.config.simple.js',
  '--reporter=verbose',
  '--no-coverage',
  '--run',
]

console.log('Command:', `vitest ${args.join(' ')}\n`)

const proc = spawn('npx', ['vitest', ...args], {
  cwd: __dirname,
  stdio: ['pipe', 'pipe', 'pipe'],
  timeout: 60000,  // 60 second timeout
})

// Capture stdout
proc.stdout.on('data', (data) => {
  const message = data.toString()
  process.stdout.write(message)
  outputStream.write(message)
})

// Capture stderr
proc.stderr.on('data', (data) => {
  const message = data.toString()
  process.stderr.write(message)
  outputStream.write(`[STDERR] ${message}`)
})

// Handle process events
proc.on('error', (error) => {
  console.error('\n❌ Error spawning vitest:', error.message)
  outputStream.write(`\n[ERROR] ${error.message}`)
  process.exit(1)
})

proc.on('exit', (code, signal) => {
  console.log(`\n✅ Vitest exited with code ${code}${signal ? ` (signal: ${signal})` : ''}`)
  outputStream.write(`\n[EXIT] Code: ${code}, Signal: ${signal}\n`)
  outputStream.end()
  
  if (code === 0) {
    console.log('🎉 Tests passed!')
  } else if (code === null) {
    console.log('⚠️  Process was killed (likely timeout)')
  } else {
    console.log(`⚠️  Tests failed with exit code ${code}`)
  }
  
  process.exit(code || 1)
})

// Kill after 60 seconds if still running
setTimeout(() => {
  console.log('\n⏱️  Timeout reached, killing vitest...')
  proc.kill('SIGTERM')
  setTimeout(() => {
    if (!proc.killed) {
      proc.kill('SIGKILL')
    }
  }, 5000)
}, 60000)
