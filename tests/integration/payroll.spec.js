import { test, expect } from '@playwright/test';

test.describe('ksf_FA_HRM_PayrollCalc Payroll Module', () => {
  test('should load payroll list page', async ({ page }) => {
    await page.goto('/modules/ksf_FA_HRM_PayrollCalc/pages/payroll.php');
    await expect(page.locator('h1, h2')).toContainText(/Payroll/i);
  });
});
