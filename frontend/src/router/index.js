import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../stores/auth'

/**
 * Vue Router Configuration
 * 
 * Defines all routes for the SPA:
 * - Public routes (login, consent)
 * - Protected user routes (profile, tokens)
 * - Admin routes (clients, metrics) with role-based access
 * - Fallback routes (404, 500)
 */

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    // Public Routes
    {
      path: '/',
      redirect: '/dashboard',
    },
    {
      path: '/login',
      name: 'Login',
      component: () => import('../pages/auth/LoginPage.vue'),
      meta: {
        title: 'Login - KSF Amortization',
        requiresAuth: false,
      },
    },
    {
      path: '/consent',
      name: 'Consent',
      component: () => import('../pages/auth/ConsentPage.vue'),
      meta: {
        title: 'Grant Consent - KSF Amortization',
        requiresAuth: false,
      },
    },
    {
      path: '/callback',
      name: 'Callback',
      component: () => import('../pages/auth/CallbackPage.vue'),
      meta: {
        title: 'Authorization - KSF Amortization',
        requiresAuth: false,
      },
    },

    // User Routes
    {
      path: '/dashboard',
      name: 'Dashboard',
      component: () => import('../pages/user/DashboardPage.vue'),
      meta: {
        title: 'Dashboard - KSF Amortization',
        requiresAuth: true,
        roles: ['user', 'admin'],
      },
    },
    {
      path: '/profile',
      name: 'Profile',
      component: () => import('../pages/user/ProfilePage.vue'),
      meta: {
        title: 'My Profile - KSF Amortization',
        requiresAuth: true,
        roles: ['user', 'admin'],
      },
    },
    {
      path: '/tokens',
      name: 'Tokens',
      component: () => import('../pages/user/TokensPage.vue'),
      meta: {
        title: 'My Tokens - KSF Amortization',
        requiresAuth: true,
        roles: ['user', 'admin'],
      },
    },
    {
      path: '/consents',
      name: 'Consents',
      component: () => import('../pages/user/ConsentsPage.vue'),
      meta: {
        title: 'My Consents - KSF Amortization',
        requiresAuth: true,
        roles: ['user', 'admin'],
      },
    },

    // Admin Routes
    {
      path: '/admin',
      name: 'AdminDashboard',
      component: () => import('../pages/admin/AdminDashboardPage.vue'),
      meta: {
        title: 'Admin Dashboard - KSF Amortization',
        requiresAuth: true,
        roles: ['admin'],
      },
    },
    {
      path: '/admin/clients',
      name: 'Clients',
      component: () => import('../pages/admin/ClientsPage.vue'),
      meta: {
        title: 'OAuth Clients - KSF Amortization',
        requiresAuth: true,
        roles: ['admin'],
      },
    },
    {
      path: '/admin/clients/:id',
      name: 'ClientDetails',
      component: () => import('../pages/admin/ClientDetailsPage.vue'),
      meta: {
        title: 'Client Details - KSF Amortization',
        requiresAuth: true,
        roles: ['admin'],
      },
    },
    {
      path: '/admin/metrics',
      name: 'Metrics',
      component: () => import('../pages/admin/MetricsPage.vue'),
      meta: {
        title: 'Metrics - KSF Amortization',
        requiresAuth: true,
        roles: ['admin'],
      },
    },
    {
      path: '/admin/audit',
      name: 'AuditLog',
      component: () => import('../pages/admin/AuditLogPage.vue'),
      meta: {
        title: 'Audit Log - KSF Amortization',
        requiresAuth: true,
        roles: ['admin'],
      },
    },

    // Error Routes
    {
      path: '/404',
      name: 'NotFound',
      component: () => import('../pages/errors/NotFoundPage.vue'),
      meta: {
        title: 'Page Not Found - KSF Amortization',
      },
    },
    {
      path: '/500',
      name: 'ServerError',
      component: () => import('../pages/errors/ServerErrorPage.vue'),
      meta: {
        title: 'Server Error - KSF Amortization',
      },
    },
    {
      path: '/:pathMatch(.*)*',
      redirect: '/404',
    },
  ],
  scrollBehavior(to, from, savedPosition) {
    if (savedPosition) {
      return savedPosition
    } else {
      return { top: 0 }
    }
  },
})

/**
 * Navigation Guards
 * 
 * Enforce:
 * - Authentication requirements
 * - Role-based access control
 * - Page title updates
 */

router.beforeEach((to, from, next) => {
  const authStore = useAuthStore()
  const requiresAuth = to.meta.requiresAuth ?? false
  const allowedRoles = to.meta.roles ?? []

  // Check authentication
  if (requiresAuth && !authStore.isAuthenticated) {
    next('/login')
    return
  }

  // Check authorization (role-based access control)
  if (requiresAuth && allowedRoles.length > 0) {
    const userRole = authStore.user?.role ?? 'user'
    if (!allowedRoles.includes(userRole)) {
      next('/404')
      return
    }
  }

  // Update page title
  document.title = to.meta.title ?? 'KSF Amortization'

  next()
})

router.afterEach(() => {
  // Log navigation in development
  if (import.meta.env.DEV) {
    console.log('📍 Navigated to:', router.currentRoute.value.path)
  }
})

export default router
