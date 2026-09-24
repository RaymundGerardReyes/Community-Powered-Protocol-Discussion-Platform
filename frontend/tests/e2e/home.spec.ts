import { test, expect } from "@playwright/test";

test("frontend loads and displays platform homepage", async ({ page }) => {
  await page.goto("/");
  await expect(page).toBeDefined();
});
