import { test, expect } from '@playwright/test';
import { waitForPageLoad } from '../helpers/test-utils';

test.describe.serial('Telas Extras', () => {
  // 1. Dashboard Enderecos
  test.describe('Dashboard Enderecos', () => {
    test('page loads', async ({ page }) => {
      await page.goto('/enderecos');
      await waitForPageLoad(page);
      await expect(page).toHaveURL(/\/enderecos/);
    });
  });

  // 3. Tipo Imovel
  test.describe('Tipo Imovel', () => {
    test('index page loads', async ({ page }) => {
      await page.goto('/tipo-imovel/');
      await waitForPageLoad(page);
      await expect(page).toHaveURL(/\/tipo-imovel/);
    });

    test('new form page loads', async ({ page }) => {
      await page.goto('/tipo-imovel/new');
      await waitForPageLoad(page);
      await expect(page).toHaveURL(/\/tipo-imovel\/new/);
    });
  });
});
