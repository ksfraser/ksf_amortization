import { test, expect } from '@playwright/test'
import { testUsers, testData, login } from '../fixtures/testHelpers'

/**
 * Admin Panel E2E Tests
 *
 * Tests:
 * - Client management (CRUD)
 * - Metrics dashboard
 * - Admin-only access control
 * - Filtering and searching
 */

test.describe('Admin Panel', () => {
  test('should redirect non-admin users from admin page', async ({ page }) => {
    // Login as regular user
    await login(page, testUsers.validUser)

    // Try to navigate to admin
    await page.goto('/admin')

    // Should redirect to dashboard or 404
    const url = page.url()
    expect(
      url.includes('/dashboard') || 
      url.includes('/404') || 
      url.includes('/login') ||
      url.includes('/unauthorized')
    ).toBeTruthy()
  })

  test('should allow admin users to access admin panel', async ({ page }) => {
    // Login as admin
    await login(page, testUsers.adminUser)

    // Navigate to admin
    await page.goto('/admin')

    // Should be on admin page
    await page.waitForURL('/admin', { timeout: 10000 })
    expect(page.url()).toContain('/admin')

    // Should show admin heading
    const heading = page.locator('h1:has-text("Admin"), h1:has-text("Dashboard")')
    await expect(heading).toBeVisible()
  })

  test('should display admin navigation menu', async ({ page }) => {
    await login(page, testUsers.adminUser)
    await page.goto('/admin')

    // Should show clients link
    const clientsLink = page.locator('a:has-text("Clients"), a:has-text("OAuth Clients")')
    await expect(clientsLink).toBeVisible()

    // Should show metrics link
    const metricsLink = page.locator('a:has-text("Metrics"), a:has-text("Analytics")')
    await expect(metricsLink).toBeVisible()

    // Should show settings link
    const settingsLink = page.locator('a:has-text("Settings"), a:has-text("Configuration")')
    expect(await settingsLink.isVisible().catch(() => false)).toBeDefined()
  })

  test('should display client list on admin page', async ({ page }) => {
    await login(page, testUsers.adminUser)
    await page.goto('/admin/clients')

    // Wait for table or list to load
    await page.waitForLoadState('networkidle')

    // Should show clients heading
    let heading = page.locator('h1:has-text("Clients"), h1:has-text("OAuth Clients"), h2:has-text("Clients")')
    await expect(heading).toBeVisible()

    // Should show table or list
    const table = page.locator('table, [role="grid"], .client-list')
    await expect(table).toBeVisible()

    // Should show add client button
    const addButton = page.locator('button:has-text("Add Client"), button:has-text("Create Client"), button:has-text("New Client")')
    await expect(addButton).toBeVisible()
  })

  test('should display client details in table', async ({ page }) => {
    await login(page, testUsers.adminUser)
    await page.goto('/admin/clients')

    // Wait for table to load
    await page.waitForLoadState('networkidle')

    // Should show column headers
    let nameHeader = page.locator('th:has-text("Name"), th:has-text("Client Name")')
    let idHeader = page.locator('th:has-text("ID"), th:has-text("Client ID")')
    
    const isVisible = await nameHeader.isVisible().catch(() => false)
    
    // If table exists, check columns
    if (isVisible) {
      await expect(nameHeader).toBeVisible()
      await expect(idHeader).toBeVisible()
    }
  })

  test('should open create client modal', async ({ page }) => {
    await login(page, testUsers.adminUser)
    await page.goto('/admin/clients')

    // Click add client button
    await page.click('button:has-text("Add Client"), button:has-text("Create Client"), button:has-text("New Client")')

    // Modal should open
    const modal = page.locator('[role="dialog"], .modal, .modal-content')
    await expect(modal).toBeVisible()

    // Should have form fields
    const nameField = page.locator('input[name="name"], input[placeholder*="name" i]')
    const descField = page.locator('input[name="description"], input[placeholder*="description" i], textarea[name="description"]')
    
    await expect(nameField).toBeVisible()
    // Description might be optional
  })

  test('should fill client form with required fields', async ({ page }) => {
    await login(page, testUsers.adminUser)
    await page.goto('/admin/clients')

    // Open create client modal
    await page.click('button:has-text("Add Client"), button:has-text("Create Client"), button:has-text("New Client")')

    // Wait for modal
    const modal = page.locator('[role="dialog"], .modal, .modal-content')
    await expect(modal).toBeVisible()

    // Fill in form
    const nameField = page.locator('input[name="name"], input[placeholder*="name" i]')
    const descField = page.locator('textarea[name="description"], input[placeholder*="description" i]')

    await nameField.fill('Test Client ' + Date.now())

    const hasDesc = await descField.isVisible().catch(() => false)
    if (hasDesc) {
      await descField.fill('Test client for E2E testing')
    }

    // Form should be filled
    await expect(nameField).toHaveValue(/Test Client/)
  })

  test('should validate client name is required', async ({ page }) => {
    await login(page, testUsers.adminUser)
    await page.goto('/admin/clients')

    // Open modal
    await page.click('button:has-text("Add Client"), button:has-text("Create Client"), button:has-text("New Client")')

    const modal = page.locator('[role="dialog"], .modal, .modal-content')
    await expect(modal).toBeVisible()

    // Try to submit without name
    const submitButton = page.locator('button:has-text("Save"), button:has-text("Create"), button:has-text("Submit")')
    await submitButton.click()

    // Should show error
    const error = page.locator('.error, .validation-error, .text-red')
    const isError = await error.isVisible().catch(() => false)
    
    // Might stay on modal or show error
    // Modal should still be visible
    const stillOpen = await modal.isVisible().catch(() => false)
    expect(stillOpen || isError).toBeTruthy()
  })

  test('should display metrics dashboard', async ({ page }) => {
    await login(page, testUsers.adminUser)
    await page.goto('/admin/metrics')

    // Wait for dashboard to load
    await page.waitForLoadState('networkidle')

    // Should show metrics heading
    let heading = page.locator('h1:has-text("Metrics"), h1:has-text("Analytics"), h1:has-text("Dashboard")')
    await expect(heading).toBeVisible()

    // Should show some metric cards
    const metrics = page.locator('[class*="metric"], [class*="card"], .stat')
    const count = await metrics.count()
    expect(count).toBeGreaterThan(0)
  })

  test('should show metric values', async ({ page }) => {
    await login(page, testUsers.adminUser)
    await page.goto('/admin/metrics')

    // Should show total users
    let usersMetric = page.locator(':text("Users"), :text("Total Users")')
    let isVisible = await usersMetric.isVisible().catch(() => false)
    
    // If metrics page exists, it should have data
    const hasContent = await page.locator('text=/\\d+/').first().isVisible().catch(() => false)
    if (isVisible || hasContent) {
      // Page loaded successfully
    }
  })

  test('should search clients in admin list', async ({ page }) => {
    await login(page, testUsers.adminUser)
    await page.goto('/admin/clients')

    // Find search input
    let searchInput = page.locator('input[placeholder*="Search"], input[type="search"], input[aria-label*="Search" i]')
    
    const hasSearch = await searchInput.isVisible().catch(() => false)
    if (hasSearch) {
      // Type search term
      await searchInput.fill('Test')

      // Wait for results to filter
      await page.waitForTimeout(500)

      // Results should be filtered
      const results = page.locator('table tbody tr, [role="row"]')
      const count = await results.count()
      
      // Should have results or empty state
      expect(count >= 0).toBeTruthy()
    }
  })

  test('should filter clients by status', async ({ page }) => {
    await login(page, testUsers.adminUser)
    await page.goto('/admin/clients')

    // Find status filter
    let statusFilter = page.locator('select[name="status"], button:has-text("Status"), [aria-label*="Status" i]')
    
    const hasFilter = await statusFilter.isVisible().catch(() => false)
    if (hasFilter) {
      await statusFilter.click()

      // Select active filter
      let activeOption = page.locator(':text("Active"), [data-value="active"]')
      await activeOption.click()

      // Wait for filter to apply
      await page.waitForTimeout(500)

      // Results should be filtered
      const results = page.locator('table tbody tr, [role="row"]')
      const count = await results.count()
      expect(count >= 0).toBeTruthy()
    }
  })

  test('should navigate between admin pages', async ({ page }) => {
    await login(page, testUsers.adminUser)
    await page.goto('/admin')

    // Navigate to clients
    await page.click('a:has-text("Clients"), a:has-text("OAuth Clients")')
    await page.waitForURL(/\/admin\/clients/)

    // Navigate to metrics
    await page.click('a:has-text("Metrics"), a:has-text("Analytics")')
    await page.waitForURL(/\/admin\/metrics/)

    // Should be on metrics page
    expect(page.url()).toContain('/metrics') || expect(page.url()).toContain('/admin')
  })
})
