import { test, expect } from '@playwright/test';
import AxeBuilder from '@axe-core/playwright';
import { readFileSync, mkdirSync, writeFileSync } from 'node:fs';
const env = Object.fromEntries(
  readFileSync('.env', 'utf8')
    .split(/\r?\n/)
    .map((l) => {
      const i = l.indexOf('=');
      return [l.slice(0, i), l.slice(i + 1)];
    }),
);
mkdirSync('output/playwright', { recursive: true });
async function login(page: import('@playwright/test').Page, alias = 'DEMO-ALBA') {
  await page.goto('/acceso');
  await page.getByRole('button', { name: 'Cuentas de demostración' }).click();
  await page.getByLabel('Alias de prueba').fill(alias);
  await page.getByLabel('Contraseña de prueba', { exact: true }).fill(env.DEMO_PASSWORD);
  await page.getByRole('button', { name: 'Entrar a mi espacio' }).click();
  await expect(page).toHaveURL(alias === 'DEMO-ADMIN' ? /admin\/agenda/ : /disponibilidad/);
}
test('T03–T06 · recorrido web persistente, reprogramar y cancelar con teclado', async ({
  page,
}) => {
  const errors: string[] = [];
  page.on('pageerror', (e) => errors.push(e.message));
  await login(page);
  await page.locator('.slot-button').first().click();
  await page.getByRole('button', { name: 'Revisar horario' }).click();
  await expect(page.getByRole('heading', { name: 'Revisa los detalles' })).toBeVisible();
  await page.getByRole('button', { name: 'Confirmar reserva' }).click();
  await expect(page).toHaveURL(/citas\/\d+/);
  await expect(page.getByRole('heading', { name: 'Tu cita de prueba está lista' })).toBeVisible();
  const code = await page.locator('.code-block code').innerText();
  const url = page.url();
  await page.reload();
  await expect(page.locator('.code-block code')).toHaveText(code);
  await page.screenshot({ path: 'output/playwright/confirmation-1366.png', fullPage: true });
  await page.getByRole('link', { name: 'Ir a mis citas' }).click();
  const card = page.locator('.appointment-card').filter({ hasText: code });
  await card.getByRole('link', { name: 'Reprogramar' }).click();
  await page.locator('.slot-button').first().click();
  await page.getByRole('button', { name: 'Revisar horario' }).click();
  await page.getByRole('button', { name: 'Confirmar cambio' }).click();
  await expect(page).toHaveURL(url);
  await expect(page.locator('.code-block code')).toHaveText(code);
  await page.getByRole('link', { name: 'Ir a mis citas' }).click();
  await page
    .locator('.appointment-card')
    .filter({ hasText: code })
    .getByRole('button', { name: 'Cancelar cita', exact: true })
    .click();
  await expect(page.getByRole('dialog')).toBeVisible();
  await expect(page.getByRole('button', { name: 'Conservar cita' })).toBeFocused();
  await page.keyboard.press('Escape');
  await expect(page.getByRole('dialog')).not.toBeVisible();
  await page
    .locator('.appointment-card')
    .filter({ hasText: code })
    .getByRole('button', { name: 'Cancelar cita', exact: true })
    .click();
  await page.getByRole('button', { name: 'Sí, cancelar cita' }).click();
  await expect(
    page
      .locator('.appointment-card')
      .filter({ hasText: code })
      .getByText('Cancelada', { exact: true }),
  ).toBeVisible();
  expect(errors).toEqual([]);
});
test('T10 · bot reserva y cancela, información compartida con web', async ({ page }) => {
  await login(page, 'DEMO-LEO');
  await page.getByRole('link', { name: 'Bot público', exact: true }).click();
  await page.getByRole('button', { name: 'Volver al menú', exact: true }).last().click();
  await page.getByRole('button', { name: '1 · Consultar y reservar' }).click();
  await page.locator('.chat-option').first().click();
  await page.locator('.chat-option').first().click();
  await page.locator('.chat-option').first().click();
  await page.getByRole('button', { name: 'Confirmar reserva', exact: true }).click();
  await expect(page.locator('.incoming').last()).toContainText('Operación confirmada');
  const text = await page.locator('.incoming').last().innerText();
  const code = text.match(/Código: ([a-f0-9-]{36})/)![1];
  await page.screenshot({ path: 'output/playwright/bot-1366.png', fullPage: true });
  await page.getByRole('link', { name: 'Mi espacio', exact: true }).click();
  await expect(page.locator('.appointment-card').filter({ hasText: code })).toBeVisible();
  await page.getByRole('link', { name: 'Bot público', exact: true }).click();
  await page.getByRole('button', { name: '4 · Cancelar' }).click();
  await page.locator('.chat-option').first().click();
  await page.getByRole('button', { name: 'Sí, cancelar esta cita' }).click();
  await expect(page.locator('.incoming').last()).toContainText('CANCELADA');
});
for (const width of [360, 768, 1366])
  test(`T11–T13 · reflujo, accesibilidad y capturas a ${width}px`, async ({ page }) => {
    await page.setViewportSize({ width, height: 950 });
    await login(page);
    const results = [];
    for (const [route, name] of [
      ['/disponibilidad', 'availability'],
      ['/mis-citas', 'appointments'],
      ['/bot', 'bot'],
    ]) {
      await page.goto(route);
      await expect(page.locator('h1')).toBeVisible();
      await page.evaluate(() => document.fonts.ready);
      await page.screenshot({ path: `output/playwright/${name}-${width}.png`, fullPage: true });
      expect(
        await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth),
      ).toBeTruthy();
      const audit = await new AxeBuilder({ page })
        .withTags(['wcag2a', 'wcag2aa', 'wcag21aa', 'wcag22aa'])
        .analyze();
      results.push({ route, violations: audit.violations });
      expect(audit.violations).toEqual([]);
    }
    await page.goto('/disponibilidad');
    if (width < 700) {
      await page.getByRole('button', { name: 'Abrir menú' }).click();
      await page.getByRole('link', { name: 'Mis citas', exact: true }).click();
      await expect(page).toHaveURL(/mis-citas/);
    }
    await page.emulateMedia({ reducedMotion: 'reduce' });
    await page.goto('/disponibilidad');
    expect(
      await page
        .locator('.button')
        .first()
        .evaluate((el) => getComputedStyle(el).transitionDuration),
    ).toBe('0s');
    writeFileSync(
      `output/playwright/accessibility-${width}.json`,
      JSON.stringify(results, null, 2),
    );
  });
test('T07–T09 · administración, filtros, crear franja, auditoría y reportes', async ({ page }) => {
  await login(page, 'DEMO-ADMIN');
  await page.getByRole('button', { name: 'Crear franja' }).click();
  await page.getByLabel('Servicio', { exact: true }).first().selectOption({ index: 1 });
  const date = new Date(Date.now() + (30 + Math.floor(Math.random() * 300)) * 86400000)
    .toISOString()
    .slice(0, 10);
  await page.getByLabel('Fecha y hora de Colombia').fill(date + 'T16:20');
  await page.getByRole('button', { name: 'Guardar franja' }).click();
  await expect(page.getByRole('status')).toContainText('Franja creada');
  await page.getByLabel('Fecha', { exact: true }).fill(date);
  await page.waitForURL(new RegExp(date));
  await page.getByLabel('Servicio', { exact: true }).selectOption({ index: 2 });
  const row = page.locator('tr').filter({ hasText: '16:20' });
  await row.getByRole('button', { name: 'Bloquear' }).click();
  await expect(row.getByText('Bloqueada', { exact: true })).toBeVisible();
  await row.getByRole('button', { name: 'Habilitar' }).click();
  await expect(row.getByText('Disponible', { exact: true })).toBeVisible();
  for (const width of [360, 768, 1366]) {
    await page.setViewportSize({ width, height: 950 });
    for (const [route, name] of [
      ['/admin/agenda', 'agenda'],
      ['/admin/auditoria', 'audit'],
      ['/admin/reportes', 'reports'],
    ]) {
      await page.goto(route);
      await page.evaluate(() => document.fonts.ready);
      await page.screenshot({ path: `output/playwright/${name}-${width}.png`, fullPage: true });
      expect(
        await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth),
      ).toBeTruthy();
      const a = await new AxeBuilder({ page })
        .withTags(['wcag2a', 'wcag2aa', 'wcag21aa', 'wcag22aa'])
        .analyze();
      expect(a.violations).toEqual([]);
    }
  }
});
test('T15 · CSRF rechaza solicitudes sin token y salida cierra rutas', async ({
  page,
  request,
}) => {
  const response = await request.post('/citas', {
    data: { slot_id: 1, request_key: 'a', confirmed: true },
  });
  expect(response.status()).toBe(419);
  await login(page);
  await page.getByRole('button', { name: 'Cerrar sesión' }).click();
  await expect(page).toHaveURL(/acceso\?ended=1/);
  await page.goto('/mis-citas');
  await expect(page).toHaveURL(/acceso/);
});
