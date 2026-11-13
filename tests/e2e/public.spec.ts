import { test, expect } from '@playwright/test';
import AxeBuilder from '@axe-core/playwright';
test('T11–T13 · inicio, acceso y recuperación pública en los tres anchos', async ({ page }) => {
  for (const width of [360, 768, 1366]) {
    await page.setViewportSize({ width, height: 950 });
    for (const [route, name] of [
      ['/', 'home'],
      ['/acceso', 'login'],
      ['/pagina-inexistente', 'error-404'],
    ]) {
      const response = await page.goto(route);
      expect(response?.status()).toBe(name === 'error-404' ? 404 : 200);
      await expect(page.locator('h1')).toBeVisible();
      await page.evaluate(() => document.fonts.ready);
      expect(
        await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth),
      ).toBeTruthy();
      await page.screenshot({ path: `output/playwright/${name}-${width}.png`, fullPage: true });
      const result = await new AxeBuilder({ page })
        .withTags(['wcag2a', 'wcag2aa', 'wcag21aa', 'wcag22aa'])
        .analyze();
      expect(result.violations).toEqual([]);
    }
  }
});
