import { test, expect } from '@playwright/test';
import AxeBuilder from '@axe-core/playwright';

async function send(page: import('@playwright/test').Page, value: string, label: string) {
  await page.getByLabel(label, { exact: true }).fill(value);
  await page.getByRole('button', { name: 'Enviar opción', exact: true }).click();
}

test('Bot público: cuenta compartida y acceso web directo con las mismas citas', async ({
  page,
}) => {
  const document = 'LAB' + Date.now();
  await page.goto('/');
  await page.getByRole('link', { name: 'Reservar por el bot público' }).click();
  await expect(page).toHaveURL(/\/bot$/);
  await expect(page.locator('.sidebar')).toHaveCount(0);
  await expect(page.getByRole('heading', { name: 'Reserva por chat' })).toBeVisible();
  await page.getByRole('button', { name: 'Cédula de ciudadanía (CC)', exact: true }).click();
  await send(page, document, 'Número de documento ficticio');
  await send(page, 'Paciente Bot Ficticio', 'Nombre completo ficticio');
  await send(page, 'ClaveBot!123', 'Crear clave de acceso');
  await send(page, 'ClaveBot!123', 'Confirmar clave de acceso');
  await expect(page.getByRole('button', { name: '1 · Consultar y reservar' })).toBeVisible();
  await expect(page.locator('.chat-messages')).not.toContainText(document);
  await expect(page.locator('.chat-messages')).not.toContainText('ClaveBot!123');
  await page.getByRole('button', { name: '1 · Consultar y reservar' }).click();
  for (let i = 0; i < 3; i++) await page.locator('.chat-option').first().click();
  await page.getByRole('button', { name: 'Confirmar reserva', exact: true }).click();
  await expect(page.locator('.incoming').last()).toContainText('Operación confirmada');
  const text = await page.locator('.incoming').last().innerText();
  const code = text.match(/Código: ([a-f0-9-]{36})/)![1];
  await page.screenshot({ path: 'output/playwright/public-bot-booking.png', fullPage: true });
  await page.getByRole('button', { name: 'Entrar a mi espacio web', exact: true }).click();
  await expect(page).toHaveURL(/mis-citas/);
  await expect(page.locator('.appointment-card').filter({ hasText: code })).toBeVisible();
  await page.getByRole('button', { name: 'Cerrar sesión' }).click();
  await page.getByLabel('Número de documento ficticio', { exact: true }).fill(document);
  await page.getByLabel('Contraseña de prueba', { exact: true }).fill('ClaveBot!123');
  await page.getByRole('button', { name: 'Entrar a mi espacio' }).click();
  await expect(page).toHaveURL(/disponibilidad/);
});

for (const width of [360, 768, 1366])
  test(`Bot WhatsApp público y registro: accesibilidad a ${width}px`, async ({ page }) => {
    await page.setViewportSize({ width, height: 950 });
    for (const [route, name] of [
      ['/bot', 'whatsapp-bot'],
      ['/registro', 'registration'],
    ]) {
      await page.goto(route);
      await expect(page.locator('h1')).toBeVisible();
      await page.evaluate(() => document.fonts.ready);
      expect(
        await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth),
      ).toBeTruthy();
      await page.screenshot({ path: `output/playwright/${name}-${width}.png`, fullPage: true });
      const a = await new AxeBuilder({ page })
        .withTags(['wcag2a', 'wcag2aa', 'wcag21aa', 'wcag22aa'])
        .analyze();
      expect(a.violations).toEqual([]);
    }
  });
