# KSF Amortization Frontend

Vue.js 3 Single Page Application (SPA) for user authentication, profile management, and admin dashboard.

## Features

- **OAuth 2.0 Integration** - Secure login and authorization flows
- **User Dashboard** - Personal profile, token management, consents
- **Admin Console** - OAuth client management, metrics, audit logs
- **Responsive Design** - Mobile-first UI with TailwindCSS
- **Real-time Metrics** - Performance monitoring and health dashboards
- **Type Safety** - TypeScript support
- **Testing** - Unit tests with Vitest, E2E with Playwright

## Technology Stack

- **Vue.js 3** - Composition API for reactive components
- **Vite** - Lightning-fast build tool
- **Vue Router** - Client-side routing
- **Pinia** - State management
- **Axios** - HTTP client
- **TailwindCSS** - Utility-first CSS framework
- **Vitest** - Unit testing
- **Prettier & ESLint** - Code quality

## Installation

### Prerequisites

- Node.js 18+ 
- npm 9+ or yarn 1.22+

### Setup

1. Install dependencies:

```bash
npm install
```

2. Configure environment:

```bash
cp .env.example .env.development
# Edit .env.development with your API endpoint
```

3. Start development server:

```bash
npm run dev
```

Application will be available at `http://localhost:5173`

## Project Structure

```
frontend/
├── src/
│   ├── assets/           # Static files (images, fonts)
│   ├── components/       # Reusable Vue components
│   │   ├── auth/         # Auth-related components
│   │   ├── admin/        # Admin-related components
│   │   └── common/       # Shared components
│   ├── pages/            # Page-level components (routes)
│   ├── router/           # Vue Router configuration
│   ├── stores/           # Pinia stores
│   │   ├── auth.js       # Authentication state
│   │   ├── clients.js    # OAuth clients state
│   │   ├── metrics.js    # Metrics state
│   │   └── ui.js         # UI state (modals, loading)
│   ├── styles/           # Global styles
│   ├── utils/            # Utilities (API client, helpers)
│   ├── App.vue           # Root component
│   └── main.js           # Application entry point
├── public/               # Static assets served as-is
├── tests/                # Test files
├── vite.config.js        # Vite configuration
├── tsconfig.json         # TypeScript configuration
├── tailwind.config.js    # TailwindCSS configuration
├── eslint.config.js      # ESLint configuration
├── prettier.config.json  # Prettier configuration
└── package.json          # Dependencies and scripts
```

## Available Scripts

### Development

```bash
# Start dev server with HMR
npm run dev

# Start dev server and open in browser
npm run dev -- --open
```

### Building

```bash
# Build for production
npm run build

# Preview production build locally
npm run preview
```

### Code Quality

```bash
# Run ESLint
npm run lint

# Format code with Prettier
npm run format

# Run type checking
npm run type-check

# Run tests
npm run test

# Run tests with coverage
npm run test:coverage

# Run tests with UI
npm run test:ui
```

## Environment Configuration

### Development (.env.development)

```env
VITE_API_BASE_URL=http://localhost:8000/api/v1
VITE_OAUTH_CLIENT_ID=dev-client-123
VITE_OAUTH_SCOPES=read write profile email offline_access
VITE_APP_DEBUG=true
```

### Production (.env.production)

```env
VITE_API_BASE_URL=https://api.example.com/api/v1
VITE_OAUTH_CLIENT_ID=prod-client-abc123
VITE_OAUTH_SCOPES=read write profile email offline_access
VITE_APP_DEBUG=false
```

## API Integration

The frontend communicates with the backend REST API via Axios. Key endpoints:

### Authentication

- `POST /auth/login` - User login
- `POST /auth/consent` - Grant/deny consent
- `POST /auth/token` - Exchange code for token
- `POST /auth/logout` - Logout user

### User

- `GET /user/me` - Current user profile
- `GET /user/tokens` - User's tokens
- `GET /user/consents` - User's consents

### Admin

- `GET /admin/clients` - List OAuth clients
- `POST /admin/clients` - Create client
- `GET /admin/clients/{id}` - Get client details
- `PUT /admin/clients/{id}` - Update client
- `DELETE /admin/clients/{id}` - Delete client
- `POST /admin/clients/{id}/rotate-secret` - Rotate secret

### Metrics

- `GET /admin/metrics/dashboard` - Dashboard overview
- `GET /admin/metrics/latency` - Latency metrics
- `GET /admin/metrics/cache` - Cache metrics
- `GET /admin/metrics/errors` - Error metrics
- `GET /admin/metrics/health` - System health

## State Management (Pinia)

### Stores

**auth.js** - Authentication state and actions
- `login(email, password)` - Login user
- `logout()` - Logout user
- `fetchCurrentUser()` - Fetch user profile
- `refreshAccessToken()` - Refresh token
- `changePassword()` - Change password

**clients.js** - OAuth client management
- `fetchClients()` - Get all clients
- `createClient(data)` - Create client
- `updateClient(id, updates)` - Update client
- `deleteClient(id)` - Delete client
- `rotateSecret(id)` - Rotate client secret

**metrics.js** - Performance metrics
- `fetchDashboard()` - Get dashboard data
- `fetchLatency()` - Get latency metrics
- `fetchCache()` - Get cache metrics
- `fetchErrors()` - Get error metrics
- `fetchHealth()` - Get health status
- `fetchAll()` - Get all metrics
- `startAutoRefresh()` - Start auto-refresh
- `stopAutoRefresh()` - Stop auto-refresh

**ui.js** - UI state (modals, notifications, loading)
- Modal store for global modals
- Notification store for toast notifications
- Loading store for overlay loading states

## Testing

### Unit Tests

```bash
# Run all tests
npm run test

# Run specific test file
npm run test -- src/components/Login.spec.js

# Watch mode
npm run test -- --watch

# Coverage report
npm run test:coverage
```

### Example Test

```javascript
import { mount } from '@vue/test-utils'
import { describe, it, expect } from 'vitest'
import LoginForm from './LoginForm.vue'

describe('LoginForm', () => {
  it('renders form', () => {
    const wrapper = mount(LoginForm)
    expect(wrapper.find('form').exists()).toBe(true)
  })

  it('emits submit event', async () => {
    const wrapper = mount(LoginForm)
    await wrapper.find('form').trigger('submit')
    expect(wrapper.emitted('submit')).toHaveLength(1)
  })
})
```

## Component Guidelines

### Single File Components

```vue
<template>
  <div class="component">
    <h1>{{ title }}</h1>
    <button @click="handleClick">Click me</button>
  </div>
</template>

<script setup>
import { ref } from 'vue'

/**
 * Component Description
 * 
 * Props:
 * - title (String): Component title
 * 
 * Emits:
 * - click: When button clicked
 */

defineProps({
  title: {
    type: String,
    required: true,
  },
})

const emit = defineEmits(['click'])

const handleClick = () => {
  emit('click')
}
</script>

<style scoped>
.component {
  @apply p-4 rounded-lg border border-gray-200;
}
</style>
```

### Composition API Best Practices

- Use `<script setup>` syntax
- Keep composable logic in separate files
- Use `ref()` for reactive data
- Use `computed()` for derived state
- Use `watch()` for side effects

## Performance Tips

1. **Code Splitting** - Routes are lazy-loaded automatically with Vite
2. **Tree Shaking** - Unused code is removed at build time
3. **Minification** - Production builds are fully minified
4. **Image Optimization** - Use modern image formats (WebP)
5. **Caching** - Configure HTTP cache headers on server
6. **Lazy Loading** - Use `v-if` for non-critical content

## Troubleshooting

### API Requests Failing

1. Check that backend API is running on configured URL
2. Verify CORS is enabled on backend
3. Check browser console for specific errors
4. Verify authentication token is valid

### Hot Module Replacement (HMR) Not Working

1. Ensure Vite is running with `npm run dev`
2. Check that browser allows WebSocket connections
3. Try clearing browser cache and reloading

### Build Failing

1. Run `npm install` to ensure all dependencies are installed
2. Check for TypeScript errors: `npm run type-check`
3. Clear node_modules and reinstall: `rm -rf node_modules && npm install`

## Deployment

### Build for Production

```bash
npm run build
```

This creates an optimized `dist/` directory ready for deployment.

### Deploy to Server

Option 1: Static hosting (Netlify, Vercel, GitHub Pages)
```bash
npm run build
# Serve dist/ folder
```

Option 2: Traditional server
```bash
npm run build
# Copy dist/ to server's web root
```

### Environment Setup

1. Set production environment variables
2. Configure API base URL for production
3. Enable minification and tree-shaking
4. Configure CORS on backend

## Contributing

1. Create feature branch: `git checkout -b feature/feature-name`
2. Make changes and test: `npm run test`
3. Format code: `npm run format`
4. Commit and push: `git push origin feature/feature-name`
5. Create Pull Request

## Support

For issues and questions:
1. Check existing GitHub issues
2. Review documentation in PhaseProgress/
3. Contact development team

## License

MIT License - see LICENSE file for details
