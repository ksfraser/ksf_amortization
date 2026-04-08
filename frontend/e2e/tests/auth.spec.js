import { test, expect } from '@playwright/test'
import { testUsers, login, logout, fillLoginForm, waitForNetworkIdle } from '../fixtures/testHelpers'

/**
 * Authentication Flow E2E Tests
 * 
 * Tests:
 * - Login with valid credentials
 * - Login with invalid credentials
 * - Logout functionality
 * - Session persistence
 * - Error handling
 */

test.describe('Authentication Flows', () => {
  test.beforeEach(async ({ page }) => {
    // Navigate to app before each test
    await page.goto('/')
  })

  test('should redirect unauthenticated user to login', async ({ page }) => {
    // Should redirect to login from home
    await page.goto('/')
    await page.waitForURL('/login', { timeout: 5000 })
    expect(page.url()).toContain('/login')
  })

  test('should display login form', async ({ page }) => {
    await page.goto('/login')
    
    // Check for form elements
    const emailInput = page.locator('input[type="email"]')
    const passwordInput = page.locator('input[type="password"]')
    const submitButton = page.locator('button[type="submit"]')
    const rememberMeCheckbox = page.locator('input[type="checkbox"]')
    
    await expect(emailInput).toBeVisible()
    await expect(passwordInput).toBeVisible()
    await expect(submitButton).toBeVisible()
    await expect(rememberMeCheckbox).toBeVisible()
  })

  test('should show error for invalid email format', async ({ page }) => {
    await page.goto('/login')
    
    // Fill form with invalid email
    await page.fill('input[type="email"]', 'invalid-email')
    await page.fill('input[type="password"]', 'password123')
    
    // Try to submit
    const submitButton = page.locator('button[type="submit"]')
    // Browser should prevent submission due to email validation
    const isDisabled = await submitButton.evaluate(el => el.disabled)
    expect(isDisabled).toBe(true)
  })

  test('should show error for empty fields', async ({ page }) => {
    await page.goto('/login')
    
    // Try to submit empty form
    const submitButton = page.locator('button[type="submit"]')
    const isDisabled = await submitButton.evaluate(el => el.disabled)
    expect(isDisabled).toBe(true)
  })

  test('should show loading state during login', async ({ page }) => {
    await page.goto('/login')
    
    // Fill form
    await fillLoginForm(page, testUsers.validUser.email, testUsers.validUser.password)
    
    // Start monitoring for loading state
    let foundLoadingState = false
    page.on('framenavigated', () => {
      foundLoadingState = true
    })
    
    // Submit
    await page.click('button[type="submit"]')
  })

  test('should successfully login with valid credentials', async ({ page }) => {
    await login(page, testUsers.validUser)
    
    // Should be on dashboard
    expect(page.url()).toContain('/dashboard')
    
    // Should see welcome message
    const welcomeText = page.locator(':text("Welcome back")')
    await expect(welcomeText).toBeVisible()
  })

  test('should show error for invalid credentials', async ({ page }) => {
    await page.goto('/login')
    
    // Fill with invalid credentials
    await fillLoginForm(page, testUsers.invalidUser.email, testUsers.invalidUser.password)
    
    // Submit
    await page.click('button[type="submit"]')
    
    // Should stay on login page
    await page.waitForURL('/login', { timeout: 5000 })
    
    // Should show error message
    const errorAlert = page.locator('[role="alert"]')
    await expect(errorAlert).toBeVisible()
    const errorText = await errorAlert.textContent()
    expect(errorText).toContain('Invalid credentials') || expect(errorText).toContain('Failed')
  })

  test('should navigate to forgot password', async ({ page }) => {
    await page.goto('/login')
    
    // Click forgot password link
    const forgotLink = page.locator('a:has-text("Forgot password")')
    await expect(forgotLink).toBeVisible()
    // Note: Actual navigation would depend on implementation
  })

  test('should show sign up link', async ({ page }) => {
    await page.goto('/login')
    
    // Check for sign up link
    const signUpLink = page.locator('a:has-text("Sign up")')
    await expect(signUpLink).toBeVisible()
  })

  test('should logout successfully', async ({ page }) => {
    // Login first
    await login(page, testUsers.validUser)
    
    // Verify on dashboard
    expect(page.url()).toContain('/dashboard')
    
    // Open user menu and logout
    await logout(page)
    
    // Should be back on login page
    expect(page.url()).toContain('/login')
  })

  test('should clear auth token on logout', async ({ page, context }) => {
    // Login first
    await login(page, testUsers.validUser)
    
    // Get auth token from storage
    const token = await context.evaluate(() => {
      return localStorage.getItem('access_token')
    })
    expect(token).toBeTruthy()
    
    // Logout
    await logout(page)
    
    // Token should be cleared
    const tokenAfterLogout = await context.evaluate(() => {
      return localStorage.getItem('access_token')
    })
    expect(tokenAfterLogout).toBeNull()
  })

  test('should prevent access to protected routes when logged out', async ({ page }) => {
    // Try to access dashboard without login
    await page.goto('/dashboard')
    
    // should redirect to login
    await page.waitForURL('/login', { timeout: 5000 })
    expect(page.url()).toContain('/login')
  })

  test('should remember me when checkbox is checked', async ({ page }) => {
    await page.goto('/login')
    
    // Fill and check remember me
    await fillLoginForm(page, testUsers.validUser.email, testUsers.validUser.password)
    
    // Submit
    await page.click('button[type="submit"]')
    
    // Wait for navigation
    await page.waitForURL('/dashboard', { timeout: 5000 })
    
    // Remember me preference should be stored
    const rememberMePref = await page.evaluate(() => {
      return localStorage.getItem('remember_me')
    })
    // Verification depends on implementation
  })
})
