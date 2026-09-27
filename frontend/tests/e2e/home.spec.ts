import { test, expect } from '@playwright/test';

test.describe('Platform Homepage & Navigation Flow', () => {
  test('redirects from root to protocols listing and displays core UI layout', async ({ page }) => {
    // 1. Visit root URL and verify redirect to /protocols
    await page.goto('/');
    await expect(page).toHaveURL(/\/protocols/);

    // 2. Verify platform header
    const brandLink = page.getByRole('link', { name: /Protocol Hub/i }).first();
    await expect(brandLink).toBeVisible();

    const navLink = page.getByRole('link', { name: 'Protocols', exact: true });
    await expect(navLink).toBeVisible();

    const signInBtn = page.getByRole('button', { name: 'Sign In', exact: true });
    await expect(signInBtn).toBeVisible();

    // 3. Verify hero title and description
    await expect(page.locator('h1')).toHaveText('Protocols');
    await expect(
      page.getByText('Discover, discuss, and vote on community-powered Web3 protocol standards.'),
    ).toBeVisible();

    // 4. Verify search and filter controls
    const searchInput = page.getByPlaceholder(/Search protocols by name/i);
    await expect(searchInput).toBeVisible();

    await expect(page.getByRole('button', { name: 'Latest' })).toBeVisible();
    await expect(page.getByRole('button', { name: 'Top Voted' })).toBeVisible();
    await expect(page.getByRole('button', { name: 'Highest Rated' })).toBeVisible();

    await expect(page.getByLabel('Filter by category')).toBeVisible();
    await expect(page.getByLabel('Filter by status')).toBeVisible();

    // 5. Verify protocol cards are rendered
    const firstArticle = page.locator('article').first();
    await expect(firstArticle).toBeVisible({ timeout: 15000 });

    // 6. Verify footer
    await expect(
      page.getByText('Community-Powered Protocol Discussion Platform'),
    ).toBeVisible();
  });
});
