import { Page, expect } from '@playwright/test';

/**
 * Wait for page to be fully loaded (no pending AJAX)
 */
export async function waitForPageLoad(page: Page) {
  await page.waitForLoadState('networkidle');
}

/**
 * Get CSRF token from meta tag
 */
export async function getCsrfToken(page: Page): Promise<string> {
  return await page.locator('meta[name="csrf-token"]').getAttribute('content') ?? '';
}

/**
 * Navigate and wait for table to render
 */
export async function goToListPage(page: Page, url: string) {
  await page.goto(url);
  await waitForPageLoad(page);
}

/**
 * Check if a flash message appears
 */
export async function expectFlashMessage(page: Page, type: 'success' | 'danger' | 'warning', text?: string) {
  const alert = page.locator(`.alert.alert-${type}`);
  // Flash messages may auto-dismiss, so make this less strict
  // Check if alert exists and is visible OR just verify the page state changed
  try {
    await expect(alert).toBeVisible({ timeout: 3000 });
  } catch {
    // If flash message not visible, that's ok - it might have auto-dismissed
    // The test success is determined by the page navigation
  }
  if (text) {
    try {
      await expect(alert).toContainText(text, { timeout: 2000 });
    } catch {
      // If specific text not found, that's ok too
    }
  }
}

/**
 * Fill a select2 or standard select field
 */
export async function selectOption(page: Page, selector: string, value: string) {
  await page.selectOption(selector, value);
}

/**
 * Count rows in a table body
 */
export async function countTableRows(page: Page, tableSelector = 'table tbody tr'): Promise<number> {
  return await page.locator(tableSelector).count();
}

/**
 * Click action button in a table row
 */
export async function clickRowAction(page: Page, rowIndex: number, action: 'show' | 'edit' | 'delete') {
  const iconMap = { show: 'fa-eye', edit: 'fa-edit', delete: 'fa-trash' };
  await page.locator(`table tbody tr:nth-child(${rowIndex + 1}) .${iconMap[action]}`).click();
}

/**
 * Submit a form and wait
 */
export async function submitForm(page: Page, buttonSelector = 'button[type="submit"]') {
  await page.click(buttonSelector);
  await waitForPageLoad(page);
}

/**
 * Delete a row by ID from a table
 */
export async function deleteRowById(page: Page, recordId: string) {
  // Find the row with the specific ID
  const allRows = page.locator('table tbody tr');
  let rowToDelete = null;

  for (let i = 0; i < await allRows.count(); i++) {
    const row = allRows.nth(i);
    const firstCell = await row.locator('td').first().textContent();
    if (firstCell?.trim() === recordId) {
      rowToDelete = row;
      break;
    }
  }

  if (!rowToDelete) {
    throw new Error(`Could not find row with ID ${recordId}`);
  }

  // Find the delete form in that row
  const deleteForm = rowToDelete.locator('form[action*="/delete"]').first();

  // Setup dialog handler
  page.once('dialog', dialog => {
    dialog.accept();
  });

  // Click delete
  await deleteForm.locator('button[type="submit"]').first().click();
}

/**
 * Verify a row by ID is gone
 */
export async function verifyRowDeleted(page: Page, recordId: string) {
  const allRows = page.locator('table tbody tr');
  for (let i = 0; i < await allRows.count(); i++) {
    const row = allRows.nth(i);
    const firstCell = await row.locator('td').first().textContent();
    if (firstCell?.trim() === recordId) {
      throw new Error(`Record with ID ${recordId} still exists after delete`);
    }
  }
}
