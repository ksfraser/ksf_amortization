import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import path from 'path'

/**
 * Vite Configuration
 * 
 * Configures Vue.js 3 Single Page Application build and dev server.
 * 
 * Features:
 * - Hot Module Replacement (HMR) for development
 * - Optimized production build with code splitting
 * - API proxy for development
 * - TypeScript support
 */

export default defineConfig({
  plugins: [vue()],
  resolve: {
    alias: {
      '@': path.resolve(__dirname, './src'),
      '~': path.resolve(__dirname, './node_modules'),
    },
    extensions: ['.mjs', '.js', '.ts', '.jsx', '.tsx', '.json', '.vue'],
  },
  server: {
    port: 5173,
    strictPort: false,
    open: true,
    cors: true,
    proxy: {
      '/api': {
        target: 'http://localhost:8000',
        changeOrigin: true,
        rewrite: (path) => path.replace(/^\/api/, '/api/v1'),
        secure: false,
      },
    },
  },
  build: {
    target: 'esnext',
    minify: 'esbuild',
    sourcemap: false,
    outDir: 'dist',
    assetsDir: 'assets',
    chunkSizeWarningLimit: 500,
    rollupOptions: {
      output: {
        manualChunks: {
          'vue-vendor': ['vue', 'vue-router'],
          'state': ['pinia'],
          'http': ['axios'],
        },
        entryFileNames: 'js/[name].[hash].js',
        chunkFileNames: 'js/[name].[hash].js',
        assetFileNames: 'assets/[name].[hash][extname]',
      },
    },
  },
  preview: {
    port: 4173,
    strictPort: false,
    open: false,
  },
  ssr: false,
  define: {
    __APP_VERSION__: JSON.stringify('1.0.0'),
    __APP_NAME__: JSON.stringify('KSF Amortization'),
  },
})
