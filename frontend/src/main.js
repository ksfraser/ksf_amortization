import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'
import router from './router'
import './styles/main.css'

/**
 * Vue.js 3 Application Entry Point
 * 
 * Initializes the Vue app with:
 * - Pinia store management
 * - Vue Router for client-side routing
 * - Global styles (Tailwind CSS)
 */

const app = createApp(App)

// Setup Pinia for state management
app.use(createPinia())

// Setup Vue Router for navigation
app.use(router)

// Mount the app
app.mount('#app')

// Development logging
if (import.meta.env.DEV) {
  console.log('🚀 KSF Amortization Frontend v1.0.0 Started')
  console.log('📍 API Endpoint:', import.meta.env.VITE_API_BASE_URL)
  console.log('🔐 OAuth Client:', import.meta.env.VITE_OAUTH_CLIENT_ID)
}
