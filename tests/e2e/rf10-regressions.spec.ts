import { test, expect, type Page } from '@playwright/test';

const botKey = 'ClaveBot!123';
const webKey = 'ClaveWeb!123';

async function send(page: Page, message: string, label = 'Mensaje al asistente') {
  await page.getByLabel(label, { exact: true }).fill(message);
  await page.getByRole('button', { name: 'Enviar opción', exact: true }).click();
  await expect(page.locator('#message')).toHaveValue('');
}

async function identify(page: Page, document: string, existing = false, key = botKey) {
  await page.goto('/bot');
  await page.getByRole('button', { name: 'Cédula de ciudadanía (CC)', exact: true }).click();
  await send(page, document, 'Número de documento ficticio');
  if (existing) {
    await send(page, key, 'Clave de acceso del paciente');
  } else {
    await send(page, 'Paciente Regresion Ficticio', 'Nombre completo ficticio');
    await send(page, key, 'Crear clave de acceso');
    await send(page, key, 'Confirmar clave de acceso');
  }
  await expect(page.getByRole('button', { name: '1 · Consultar y reservar' })).toBeVisible();
}

async function selectReservation(page: Page) {
  await page.getByRole('button', { name: '1 · Consultar y reservar' }).click();
  for (let i = 0; i < 3; i++) await page.locator('.chat-option').first().click();
  await expect(page.getByRole('button', { name: 'Confirmar reserva', exact: true })).toBeVisible();
}

async function activateWeb(page: Page, document: string) {
  await page.getByRole('link', { name: 'Activar mi cuenta web', exact: true }).click();
  await page.getByLabel('Número de documento ficticio', { exact: true }).fill(document);
  await page.getByLabel('Contraseña', { exact: true }).fill(webKey);
  await page.getByLabel('Confirmar contraseña', { exact: true }).fill(webKey);
  await page.getByRole('button', { name: 'Crear mi cuenta web' }).click();
  await expect(page).toHaveURL(/mis-citas/);
}

async function webLogin(page: Page, document: string, password = webKey) {
  await page.goto('/acceso');
  await page.getByLabel('Número de documento ficticio', { exact: true }).fill(document);
  await page.getByLabel('Contraseña de prueba', { exact: true }).fill(password);
  await page.getByRole('button', { name: 'Entrar a mi espacio' }).click();
}

test('D01: bloqueo por documento muestra el aviso y la espera', async ({ page }) => {
  const document = 'LABD01' + Date.now();
  await identify(page, document);
  await activateWeb(page, document);
  await page.getByRole('button', { name: 'Cerrar sesión' }).click();
  for (let i = 0; i < 7; i++) {
    await page.getByLabel('Número de documento ficticio', { exact: true }).fill(document);
    await page.getByLabel('Contraseña de prueba', { exact: true }).fill('Incorrecta!123');
    await page.getByRole('button', { name: 'Entrar a mi espacio' }).click();
    await expect(page.getByLabel('Contraseña de prueba', { exact: true })).toHaveValue('');
  }
  await expect(
    page.getByText('Demasiados intentos fallidos. Espera un minuto antes de volver a intentar.', {
      exact: true,
    }),
  ).toBeVisible();
  await expect(page.getByLabel('Número de documento ficticio', { exact: true })).toHaveAttribute(
    'aria-invalid',
    'true',
  );
});

test('D02: comandos globales y rechazo de números reales durante identificación', async ({
  page,
}) => {
  await page.goto('/bot');
  await page.getByRole('button', { name: 'Cédula de ciudadanía (CC)', exact: true }).click();
  await send(page, '123456789', 'Número de documento ficticio');
  await expect(
    page.getByText(/Usa un identificador sintético con prefijo LAB o FICT/),
  ).toBeVisible();
  await send(page, 'menu', 'Número de documento ficticio');
  await expect(
    page.getByRole('button', { name: 'Cédula de ciudadanía (CC)', exact: true }),
  ).toBeVisible();
  await page.getByRole('button', { name: 'Cédula de ciudadanía (CC)', exact: true }).click();
  await send(page, 'LABD02' + Date.now(), 'Número de documento ficticio');
  await send(page, 'menú', 'Nombre completo ficticio');
  await expect(
    page.getByRole('button', { name: 'Cédula de ciudadanía (CC)', exact: true }),
  ).toBeVisible();
  await page.getByRole('button', { name: 'Cédula de ciudadanía (CC)', exact: true }).click();
  await send(page, '0', 'Número de documento ficticio');
  await expect(page.getByText(/La conversación finalizó/)).toBeVisible();
  await page.getByRole('button', { name: 'Iniciar conversación', exact: true }).click();
  await page.getByRole('button', { name: 'Cédula de ciudadanía (CC)', exact: true }).click();
  await send(page, 'LABD02' + Date.now(), 'Número de documento ficticio');
  await send(page, 'Paciente Ficticio', 'Nombre completo ficticio');
  await send(page, botKey, 'Crear clave de acceso');
  await send(page, 'salir', 'Confirmar clave de acceso');
  await expect(page.getByText(/La conversación finalizó/)).toBeVisible();
  await expect(page.locator('.bot-patient')).toHaveCount(0);
});

test('RF10 público: ciclo completo y sincronización de cambios entre ambos canales', async ({
  page,
  browser,
}) => {
  test.setTimeout(120000);
  const document = 'LABSYNC' + Date.now();
  await identify(page, document);
  await expect(page.getByRole('link', { name: 'Ingresar', exact: true })).toBeVisible();
  await selectReservation(page);
  await page.getByRole('button', { name: 'Confirmar reserva', exact: true }).click();
  await expect(page.locator('.incoming').last()).toContainText('Operación confirmada');
  const code = (await page.locator('.incoming').last().innerText()).match(
    /Código: ([a-f0-9-]{36})/,
  )![1];
  await page.getByRole('button', { name: '2 · Mis citas' }).click();
  await expect(page.locator('.incoming').last()).toContainText(code);
  await page.getByRole('button', { name: '5 · Recordatorios internos' }).click();
  await expect(page.locator('.incoming').last()).toContainText(/recordatorios/i);
  await page.getByRole('button', { name: '3 · Reprogramar' }).click();
  for (let i = 0; i < 3; i++) await page.locator('.chat-option').first().click();
  await page.getByRole('button', { name: 'Confirmar cambio', exact: true }).click();
  await expect(page.locator('.incoming').last()).toContainText(code);
  const botTime = (await page.locator('.incoming').last().innerText()).match(
    /\d{2}\/\d{2}\/\d{4} (\d{2}:\d{2})/,
  )![1];
  await activateWeb(page, document);
  const card = page.locator('.appointment-card').filter({ hasText: code });
  await expect(card).toContainText(botTime);
  await card.getByRole('link', { name: 'Reprogramar' }).click();
  const webTime = await page.locator('.slot-button').first().locator('span').first().innerText();
  await page.locator('.slot-button').first().click();
  await page.getByRole('button', { name: 'Revisar horario' }).click();
  await page.getByRole('button', { name: 'Confirmar cambio' }).click();
  await expect(page.locator('.code-block code')).toHaveText(code);

  const publicContext = await browser.newContext();
  const bot = await publicContext.newPage();
  await identify(bot, document, true, webKey);
  await expect(bot.getByRole('link', { name: 'Ingresar', exact: true })).toBeVisible();
  await bot.getByRole('button', { name: '2 · Mis citas' }).click();
  await expect(bot.locator('.incoming').last()).toContainText(code);
  await expect(bot.locator('.incoming').last()).toContainText(webTime);
  await page.getByRole('link', { name: 'Ir a mis citas' }).click();
  await card.getByRole('button', { name: 'Cancelar cita', exact: true }).click();
  await page.getByRole('button', { name: 'Sí, cancelar cita' }).click();
  await bot.getByRole('button', { name: '2 · Mis citas' }).click();
  await expect(bot.locator('.incoming').last()).toContainText('CANCELADA');
  await selectReservation(bot);
  await bot.getByRole('button', { name: 'Confirmar reserva', exact: true }).click();
  await expect(bot.locator('.incoming').last()).toContainText('Operación confirmada');
  const secondCode = (await bot.locator('.incoming').last().innerText()).match(
    /Código: ([a-f0-9-]{36})/,
  )![1];
  await bot.getByRole('button', { name: '4 · Cancelar' }).click();
  await bot.locator('.chat-option').first().click();
  await bot.getByRole('button', { name: 'Sí, cancelar esta cita' }).click();
  await expect(bot.locator('.incoming').last()).toContainText('CANCELADA');
  await page.reload();
  await expect(page.locator('.appointment-card').filter({ hasText: secondCode })).toContainText(
    'Cancelada',
  );
  await bot.getByRole('button', { name: '0 · Salir' }).click();
  await expect(bot.locator('.bot-patient')).toHaveCount(0);
  await expect(bot.locator('.chat-messages')).not.toContainText(code);
  await publicContext.close();
});

test('D03: nueva sesión, historial, navegación y acciones antiguas mantienen opciones vigentes', async ({
  page,
  browser,
}) => {
  test.setTimeout(90000);
  const document = 'LABD03' + Date.now();
  await identify(page, document);
  await activateWeb(page, document);
  await page.getByRole('link', { name: 'Bot público', exact: true }).click();
  await selectReservation(page);

  const freshContext = await browser.newContext();
  const fresh = await freshContext.newPage();
  await webLogin(fresh, document);
  await expect(fresh).toHaveURL(/disponibilidad/);
  await fresh.getByRole('link', { name: 'Bot público', exact: true }).click();
  await expect(fresh.getByRole('button', { name: '1 · Consultar y reservar' })).toBeVisible();
  await expect(fresh.getByRole('button', { name: 'Confirmar reserva', exact: true })).toHaveCount(
    0,
  );
  await page.reload();
  await expect(page.getByRole('button', { name: 'Confirmar reserva', exact: true })).toBeVisible();

  const otherTab = await page.context().newPage();
  await otherTab.goto('/bot');
  await otherTab.getByRole('button', { name: 'Volver al menú', exact: true }).last().click();
  await expect(otherTab.getByRole('button', { name: '1 · Consultar y reservar' })).toBeVisible();
  await page.getByRole('button', { name: 'Confirmar reserva', exact: true }).click();
  await expect(
    page.getByText('Las opciones cambiaron. Revisa las opciones actuales antes de continuar.', {
      exact: true,
    }),
  ).toBeVisible();
  await expect(page.getByRole('button', { name: 'Confirmar reserva', exact: true })).toHaveCount(0);
  await page.getByRole('link', { name: 'Mi espacio', exact: true }).click();
  await expect(page).toHaveURL(/mis-citas/);
  await expect(page.locator('.appointment-card')).toHaveCount(0);
  await otherTab.getByRole('button', { name: '1 · Consultar y reservar' }).click();
  await expect(
    otherTab.getByRole('button', { name: 'Consulta general', exact: true }),
  ).toBeVisible();
  await page.goBack();
  await expect(page).toHaveURL(/\/bot$/);
  await expect(page.getByRole('button', { name: 'Consulta general', exact: true })).toBeVisible();
  await send(page, 'opción desconocida');
  await expect(
    page.getByRole('button', { name: 'Volver al menú', exact: true }).first(),
  ).toBeVisible();
  await send(page, 'menú');
  await expect(page.getByRole('button', { name: '1 · Consultar y reservar' })).toBeVisible();
  await otherTab.close();
  await freshContext.close();
});
