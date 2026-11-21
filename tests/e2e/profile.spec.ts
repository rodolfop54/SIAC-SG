import { test, expect, type Page } from '@playwright/test';
import AxeBuilder from '@axe-core/playwright';
import { readFileSync } from 'node:fs';

const config = Object.fromEntries(
  readFileSync('.env', 'utf8')
    .split(/\r?\n/)
    .map((line) => {
      const index = line.indexOf('=');
      return [line.slice(0, index), line.slice(index + 1)];
    }),
);

async function enter(page: Page) {
  await page.goto('/acceso');
  await page.getByRole('button', { name: 'Cuentas de demostración' }).click();
  await page.getByLabel('Alias de prueba').fill('DEMO-ALBA');
  await page.getByLabel('Contraseña de prueba', { exact: true }).fill(config.DEMO_PASSWORD);
  await page.getByRole('button', { name: 'Entrar a mi espacio', exact: true }).click();
  await expect(page).toHaveURL(/disponibilidad/);
}

test('Perfil: editar nombre conserva la sesión y la navegación del portal no muestra el bot', async ({
  page,
}) => {
  await enter(page);
  const navigation = page.getByRole('navigation', { name: 'Navegación principal', exact: true });
  await expect(navigation.getByRole('link', { name: 'Bot público', exact: true })).toHaveCount(0);
  await navigation.getByRole('link', { name: 'Mi perfil', exact: true }).click();
  await expect(page).toHaveURL(/perfil/);
  const originalName = await page
    .getByLabel('Nombre completo ficticio', { exact: true })
    .inputValue();
  await page
    .getByLabel('Nombre completo ficticio', { exact: true })
    .fill('Paciente Perfil Actualizado Ficticio');
  await page.getByRole('button', { name: 'Guardar nombre', exact: true }).click();
  await expect(page.getByRole('status')).toContainText('Tu nombre se actualizó');
  await expect(page.locator('.sidebar-account strong')).toHaveText(
    'Paciente Perfil Actualizado Ficticio',
  );
  await page.reload();
  await expect(page.getByLabel('Nombre completo ficticio', { exact: true })).toHaveValue(
    'Paciente Perfil Actualizado Ficticio',
  );
  await page.screenshot({ path: 'output/playwright/profile-updated-1366.png', fullPage: true });
  await page.getByLabel('Nombre completo ficticio', { exact: true }).fill(originalName);
  await page.getByRole('button', { name: 'Guardar nombre', exact: true }).click();
  await expect(page.getByRole('status')).toContainText('Tu nombre se actualizó');
  await navigation.getByRole('link', { name: 'Mis citas', exact: true }).click();
  await expect(page).toHaveURL(/mis-citas/);
  await expect(navigation.getByRole('link', { name: 'Bot público', exact: true })).toHaveCount(0);
});

for (const width of [360, 768, 1366]) {
  test(`Perfil: accesibilidad y reflujo a ${width}px`, async ({ page }) => {
    await page.setViewportSize({ width, height: 950 });
    await enter(page);
    await page.goto('/perfil');
    await expect(page.getByRole('heading', { name: 'Mi perfil', exact: true })).toBeVisible();
    await expect(page.getByLabel('Nuevo correo electrónico', { exact: true })).toBeVisible();
    await page.evaluate(() => document.fonts.ready);
    expect(
      await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth),
    ).toBeTruthy();
    const results = await new AxeBuilder({ page })
      .withTags(['wcag2a', 'wcag2aa', 'wcag21aa', 'wcag22aa'])
      .analyze();
    expect(results.violations).toEqual([]);
    await page.screenshot({ path: `output/playwright/profile-${width}.png`, fullPage: true });
  });
}
