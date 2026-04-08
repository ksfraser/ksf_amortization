/**
 * E2E Test Fixtures & Helpers
 * 
 * Provides:
 * - Test user credentials
 * - Test data for various scenarios
 * - Helper functions for common operations
 */

export const testUsers = {
  validUser: {
    email: 'test@example.com',
    password: 'TestPassword123!',
    name: 'Test User',
  },
  adminUser: {
    email: 'admin@example.com',
    password: 'AdminPassword123!',
    name: 'Admin User',
  },
  invalidUser: {
    email: 'invalid@example.com',
    password: 'WrongPassword123!',
  },
}

export const testData = {
  client: {
    name: 'Test OAuth Client',
    redirectUri: 'http://localhost:3000/callback',
    description: 'Test client for E2E testing',
  },
  scope: {
    read: 'Read access to profile',
    write: 'Write access to profile',
    admin: 'Admin access',
  },
}

/**
 * Wait for network to be idle
 * Useful for waiting for API calls to complete
 */
export const waitForNetworkIdle = async (page) => {
  await page.waitForLoadState('networkidle')
}

/**
 * Login helper function
 */
export const login = async (page, user = testUsers.validUser) => {
  await page.goto('/login')
  
  // Fill login form
  await page.fill('input[type="email"]', user.email)
  await page.fill('input[type="password"]', user.password)
  
  // Submit form
  await page.click('button[type="submit"]')
  
  // Wait for navigation to dashboard
  await page.waitForURL('/dashboard', { timeout: 5000 })
  await waitForNetworkIdle(page)
}

/**
 * Logout helper function
 */
export const logout = async (page) => {
  // Click user menu
  await page.click('button:has-text("Test User"), button:has-text("Admin User")')
  
  // Click logout
  await page.click('button:has-text("Logout")')
  
  // Wait for redirect to login
  await page.waitForURL('/login', { timeout: 5000 })
}

/**
 * Fill and submit login form
 */
export const fillLoginForm = async (page, email, password) => {
  await page.fill('input[type="email"]', email)
  await page.fill('input[type="password"]', password)
  await page.click('input[type="checkbox"]') // Remember me
}

/**
 * Check if user is authenticated
 */
export const isAuthenticated = async (page) => {
  const url = page.url()
  return !url.includes('/login')
}

/**
 * Navigate to page and check title
 */
export const navigateAndCheckTitle = async (page, path, expectedTitle) => {
  await page.goto(path)
  await page.waitForLoadState('networkidle')
  const title = await page.title()
  return title.includes(expectedTitle)
}
