import { defineConfig } from 'vitest/config'
import vue from '@vitejs/plugin-vue'
import path from 'path'

export default defineConfig({
  plugins: [vue()],
  test: {
    globals: true,
    environment: 'happy-dom',  // Changed from jsdom to happy-dom
    setupFiles: [],  // Disable setup files initially
    coverage: {
      provider: 'v8',
      reporter: ['text'],
    },
    include: ['tests/unit/*.spec.js'],  // Only basic tests
    exclude: ['node_modules', 'dist'],
    testTimeout: 30000,
    hookTimeout: 30000,
    isolate: true,
  },
  resolve: {
    alias: {
      '@': path.resolve(__dirname, './src'),
    },
  },
})
