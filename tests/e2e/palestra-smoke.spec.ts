import { expect, test } from '@playwright/test';

const segreteriaEmail = process.env.E2E_SEGRETERIA_EMAIL ?? 'segreteria@example.com';
const segreteriaPassword = process.env.E2E_SEGRETERIA_PASSWORD ?? 'password';

test.describe('Palestra operativa smoke', () => {
    test('toggle ON, walkthrough e preset multi-operatore', async ({ page }) => {
        await page.goto('/login');
        await page.locator('#email').fill(segreteriaEmail);
        await page.locator('#password').fill(segreteriaPassword);
        await page.getByRole('button', { name: 'Accedi' }).click();

        await expect(page).toHaveURL(/\/segreteria/);

        await page.getByRole('button', { name: 'OFF' }).click();
        await page.getByRole('button', { name: 'Attiva demo' }).click();

        await expect(page.getByText('scope demo attivo in sessione')).toBeVisible();

        await page.goto('/segreteria');
        await expect(page.getByText('Progresso walkthrough')).toBeVisible();
        await expect(page.getByText('Prova flusso RENTRI')).toBeVisible();

        await page.goto('/segreteria/impostazioni/rentri');
        await page.locator('#selectedOperatorPreset').selectOption('sede_nord');
        await page.getByRole('button', { name: 'Applica preset sandbox' }).click();

        await expect(page.getByText(/Preset sandbox «Sede Nord/)).toBeVisible();
        await expect(page.locator('code', { hasText: 'DEMO-SITE-NORD-001' }).first()).toBeVisible();
    });
});
