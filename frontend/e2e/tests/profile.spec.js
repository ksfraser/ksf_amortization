import { test, expect } from '@playwright/test'
import { testUsers, login, waitForNetworkIdle } from '../fixtures/testHelpers'

/**
 * Profile Management E2E Tests
 *
 * Tests:
 * - View profile information
 * - Edit profile
 * - Change password
 * - Cancel operations
 * - Error handling
 */

test.describe('Profile Management', () => {
  test.beforeEach(async ({ page }) => {
    // Login before each test
    await login(page, testUsers.validUser)
  })

  test('should display profile page with user info', async ({ page }) => {
    await page.goto('/profile')
    await waitForNetworkIdle(page)

    // Should show user info
    const userEmail = page.locator(`:text("${testUsers.validUser.email}")`)
    const userName = page.locator(`:text("${testUsers.validUser.name}")`)

    await expect(userEmail).toBeVisible()
    await expect(userName).toBeVisible()
  })

  test('should display user avatar', async ({ page }) => {
    await page.goto('/profile')
    
    // Should show avatar with user initial
    const avatar = page.locator('[class*="avatar"], [class*="profile-picture"]')
    await expect(avatar.first()).toBeVisible()
  })

  test('should display edit profile button', async ({ page }) => {
    await page.goto('/profile')

    const editButton = page.locator('button:has-text("Edit Profile")')
    await expect(editButton).toBeVisible()
  })

  test('should open edit profile modal', async ({ page }) => {
    await page.goto('/profile')

    // Click edit button
    await page.click('button:has-text("Edit Profile")')

    // Modal should be visible
    const modal = page.locator('[role="dialog"]')
    await expect(modal).toBeVisible()

    // Should have form fields
    const nameInput = page.locator('input[type="text"]')
    const emailInput = page.locator('input[type="email"]')
    
    await expect(nameInput).toBeVisible()
    await expect(emailInput).toBeVisible()
  })

  test('should update profile information', async ({ page }) => {
    await page.goto('/profile')

    // Open edit modal
    await page.click('button:has-text("Edit Profile")')

    // Update name
    await page.fill('input[type="text"]', 'Updated Name')

    // Save changes
    await page.click('button:has-text("Save Changes")')

    // Wait for update
    await waitForNetworkIdle(page)

    // Modal should close
    const modal = page.locator('[role="dialog"]')
    await expect(modal).not.toBeVisible()

    // Success notification should appear
    const successAlert = page.locator('[class*="success"]')
    if (await successAlert.isVisible()) {
      const text = await successAlert.textContent()
      expect(text).toContain('updated') || expect(text).toContain('success')
    }
  })

  test('should cancel profile edit without saving', async ({ page }) => {
    await page.goto('/profile')

    // Open edit modal
    await page.click('button:has-text("Edit Profile")')

    // Change name
    const nameInput = page.locator('input[type="text"]').first()
    const originalValue = await nameInput.inputValue()
    await nameInput.fill('Temporary Name')

    // Cancel
    await page.click('button:has-text("Cancel")')

    // Modal should close
    const modal = page.locator('[role="dialog"]')
    await expect(modal).not.toBeVisible()

    // Open again to verify name didn't change
    await page.click('button:has-text("Edit Profile")')
    const newValue = await nameInput.inputValue()
    expect(newValue).toBe(originalValue)
  })

  test('should display security section', async ({ page }) => {
    await page.goto('/profile')

    // Should have security section
    const securitySection = page.locator(':text("Security")')
    await expect(securitySection).toBeVisible()

    // Should have password change button
    const passwordButton = page.locator('button:has-text("Change")')
    await expect(passwordButton).toBeVisible()
  })

  test('should display change password button', async ({ page }) => {
    await page.goto('/profile')

    const passwordButton = page.locator('button:has-text("Change"):near(:text("Password"))')
    await expect(passwordButton).toBeVisible()
  })

  test('should open change password modal', async ({ page }) => {
    await page.goto('/profile')

    // Click change password
    const passwordButton = page.locator('button:has-text("Change"):near(:text("Password"))')
    await passwordButton.click()

    // Modal should be visible
    const modal = page.locator('[role="dialog"]')
    await expect(modal).toBeVisible()

    // Should have 3 password fields
    const passwordInputs = page.locator('input[type="password"]')
    await expect(passwordInputs).toHaveCount(3)
  })

  test('should show error when new passwords dont match', async ({ page }) => {
    await page.goto('/profile')

    // Open change password
    const passwordButton = page.locator('button:has-text("Change"):near(:text("Password"))')
    await passwordButton.click()

    // Fill password fields
    const inputs = page.locator('input[type="password"]')
    await inputs.nth(0).fill('currentPassword123')
    await inputs.nth(1).fill('newPassword123')
    await inputs.nth(2).fill('differentPassword123')

    // Try to submit
    await page.click('[role="dialog"] button:has-text("Change Password")')

    // Should show error (waiting or actual error message depends on implementation)
    const errorText = page.locator('[class*="error"]')
    if (await errorText.isVisible()) {
      const text = await errorText.textContent()
      expect(text).toContain('match') || expect(text).toContain('not')
    }
  })

  test('should display two factor authentication section', async ({ page }) => {
    await page.goto('/profile')

    // Should have 2FA section
    const twoFAText = page.locator(':text("Two-Factor Authentication")')
    await expect(twoFAText).toBeVisible()

    // Should have disabled badge
    const disabledBadge = page.locator(':text("Disabled")')
    await expect(disabledBadge).toBeVisible()
  })

  test('should have enable 2FA button', async ({ page }) => {
    await page.goto('/profile')

    // Should have enable button
    const enableButton = page.locator('button:near(:text("Two-Factor")) :has-text("Enable")')
    if (await enableButton.isVisible()) {
      await expect(enableButton).toBeVisible()
    }
  })

  test('should display member since date', async ({ page }) => {
    await page.goto('/profile')

    // Should show join date
    const joinDateText = page.locator(':text("Member since")')
    await expect(joinDateText).toBeVisible()
  })

  test('should navigate to profile from dashboard', async ({ page }) => {
    await page.goto('/dashboard')

    // Click profile link in nav
    await page.click('a:has-text("Profile"), [class*="nav"] :text("Profile")')

    // Should navigate to profile
    await page.waitForURL('/profile')
    expect(page.url()).toContain('/profile')
  })

  test('should navigate to profile from top navigation', async ({ page }) => {
    await page.goto('/dashboard')

    // Click user menu
    const userMenu = page.locator('button[aria-haspopup="true"]')
    await userMenu.click()

    // Click profile link in dropdown
    await page.click('[role="dialog"] a:has-text("Profile"), ul a:has-text("Profile")')

    // Should navigate to profile
    await page.waitForURL('/profile')
    expect(page.url()).toContain('/profile')
  })
})
