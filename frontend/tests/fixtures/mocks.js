import { http, HttpResponse } from 'msw'

/**
 * MSW Mock Handlers
 * 
 * Mocks all API endpoints used in tests
 * Provides realistic responses and error scenarios
 */

const API_BASE = 'http://api.test/api/v1'

// Fake user data
const mockUser = {
  id: 'user-123',
  email: 'test@example.com',
  name: 'Test User',
  role: 'user',
  createdAt: '2026-01-01T00:00:00Z',
}

const mockAdminUser = {
  ...mockUser,
  role: 'admin',
}

// Fake OAuth clients
const mockClients = [
  {
    id: 'client-1',
    name: 'Test App',
    description: 'Test OAuth2 client',
    clientId: 'test-client-id',
    redirectUris: ['http://localhost:3000/callback'],
    scopes: ['read', 'write', 'profile'],
    createdAt: '2026-01-01T00:00:00Z',
  },
  {
    id: 'client-2',
    name: 'Another App',
    clientId: 'another-client',
    redirectUris: ['http://localhost:3001/callback'],
    scopes: ['read', 'profile'],
    createdAt: '2026-01-02T00:00:00Z',
  },
]

// Fake tokens
const mockTokens = [
  {
    id: 'token-1',
    name: 'Mobile App Token',
    token: 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...',
    createdAt: '2026-03-01T00:00:00Z',
    expiresAt: '2026-04-01T00:00:00Z',
    lastUsedAt: '2026-04-05T12:00:00Z',
  },
]

// Fake consents
const mockConsents = [
  {
    id: 'consent-1',
    clientName: 'Test App',
    scopes: ['read', 'profile'],
    grantedAt: '2026-03-15T00:00:00Z',
    lastUsedAt: '2026-04-04T18:00:00Z',
  },
]

// Fake metrics
const mockMetrics = {
  dashboard: {
    totalRequests: 15420,
    successRate: 99.85,
    averageLatency: 142,
    cacheHitRate: 87.5,
    errorsLast24h: 23,
    uptime: 99.99,
  },
  latency: {
    p50: 89,
    p95: 245,
    p99: 512,
    p999: 1204,
  },
  cache: {
    hits: 3247,
    misses: 487,
    hitRate: 87.5,
  },
  errors: [
    { code: 401, count: 12, percentage: 52 },
    { code: 500, count: 8, percentage: 35 },
    { code: 429, count: 3, percentage: 13 },
  ],
  health: 'healthy',
}

/**
 * Success Handlers
 */
export const successHandlers = [
  // Auth endpoints
  http.post(`${API_BASE}/auth/login`, () => {
    return HttpResponse.json({
      accessToken: 'test-access-token',
      refreshToken: 'test-refresh-token',
      user: mockUser,
    })
  }),

  http.post(`${API_BASE}/auth/logout`, () => {
    return HttpResponse.json({ success: true })
  }),

  http.post(`${API_BASE}/auth/refresh-token`, () => {
    return HttpResponse.json({
      accessToken: 'new-access-token',
      refreshToken: 'new-refresh-token',
    })
  }),

  http.get(`${API_BASE}/auth/me`, () => {
    return HttpResponse.json(mockUser)
  }),

  // Admin: Clients
  http.get(`${API_BASE}/admin/clients`, () => {
    return HttpResponse.json({
      clients: mockClients,
      total: mockClients.length,
      page: 1,
      limit: 10,
    })
  }),

  http.post(`${API_BASE}/admin/clients`, () => {
    return HttpResponse.json(mockClients[0], { status: 201 })
  }),

  http.get(`${API_BASE}/admin/clients/:id`, () => {
    return HttpResponse.json(mockClients[0])
  }),

  http.put(`${API_BASE}/admin/clients/:id`, () => {
    return HttpResponse.json(mockClients[0])
  }),

  http.delete(`${API_BASE}/admin/clients/:id`, () => {
    return HttpResponse.json({ success: true })
  }),

  http.post(`${API_BASE}/admin/clients/:id/rotate-secret`, () => {
    return HttpResponse.json({
      ...mockClients[0],
      clientSecret: 'new-secret-value',
    })
  }),

  // Metrics
  http.get(`${API_BASE}/metrics/dashboard`, () => {
    return HttpResponse.json(mockMetrics.dashboard)
  }),

  http.get(`${API_BASE}/metrics/latency`, () => {
    return HttpResponse.json(mockMetrics.latency)
  }),

  http.get(`${API_BASE}/metrics/cache`, () => {
    return HttpResponse.json(mockMetrics.cache)
  }),

  http.get(`${API_BASE}/metrics/errors`, () => {
    return HttpResponse.json(mockMetrics.errors)
  }),

  http.get(`${API_BASE}/metrics/health`, () => {
    return HttpResponse.json({ status: mockMetrics.health })
  }),

  http.get(`${API_BASE}/metrics/export`, () => {
    return HttpResponse.json(mockMetrics)
  }),

  // User endpoints
  http.get(`${API_BASE}/user/tokens`, () => {
    return HttpResponse.json({
      tokens: mockTokens,
      total: mockTokens.length,
    })
  }),

  http.delete(`${API_BASE}/user/tokens/:id`, () => {
    return HttpResponse.json({ success: true })
  }),

  http.get(`${API_BASE}/user/consents`, () => {
    return HttpResponse.json({
      consents: mockConsents,
      total: mockConsents.length,
    })
  }),

  http.delete(`${API_BASE}/user/consents/:id`, () => {
    return HttpResponse.json({ success: true })
  }),
]

/**
 * Error Handlers
 */
export const errorHandlers = {
  // 401 Unauthorized
  unauthorized: http.get(`${API_BASE}/auth/me`, () => {
    return HttpResponse.json(
      { error: 'Unauthorized', message: 'Invalid or expired token' },
      { status: 401 }
    )
  }),

  // 403 Forbidden
  forbidden: http.delete(`${API_BASE}/admin/clients/:id`, () => {
    return HttpResponse.json(
      { error: 'Forbidden', message: 'You do not have permission' },
      { status: 403 }
    )
  }),

  // 404 Not Found
  notFound: http.get(`${API_BASE}/admin/clients/:id`, () => {
    return HttpResponse.json(
      { error: 'Not Found', message: 'Client not found' },
      { status: 404 }
    )
  }),

  // 422 Validation Error
  validationError: http.post(`${API_BASE}/admin/clients`, () => {
    return HttpResponse.json(
      {
        error: 'Validation Failed',
        fields: {
          name: 'Name is required',
          redirectUris: 'At least one redirect URI is required',
        },
      },
      { status: 422 }
    )
  }),

  // 429 Too Many Requests
  rateLimited: http.get(`${API_BASE}/metrics/dashboard`, () => {
    return HttpResponse.json(
      { error: 'Too Many Requests', message: 'Rate limit exceeded' },
      { status: 429 }
    )
  }),

  // 500 Server Error
  serverError: http.get(`${API_BASE}/metrics/health`, () => {
    return HttpResponse.json(
      { error: 'Internal Server Error', message: 'Something went wrong' },
      { status: 500 }
    )
  }),
}

export const createErrorHandler = (endpoint, statusCode = 500, message = 'Error') => {
  return http.get(endpoint, () => {
    return HttpResponse.json(
      { error: message },
      { status: statusCode }
    )
  })
}

/**
 * Export mock data for use in tests
 */
export { mockUser, mockAdminUser, mockClients, mockTokens, mockConsents, mockMetrics }
