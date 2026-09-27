import { test, expect } from '@playwright/test';

test.describe('Protocol Discovery & Filtering Flow', () => {
  test('user can sort, filter by category, search protocols, and navigate to protocol detail', async ({
    page,
  }) => {
    // 1. Navigate to /protocols
    await page.goto('/protocols');
    await expect(page.locator('h1')).toHaveText('Protocols');

    // 2. Click Top Voted sort pill
    const topVotedBtn = page.getByRole('button', { name: 'Top Voted' });
    await topVotedBtn.click();
    await expect(page).toHaveURL(/sort=top/);

    // 3. Switch to Highest Rated sort pill
    const highestRatedBtn = page.getByRole('button', { name: 'Highest Rated' });
    await highestRatedBtn.click();
    await expect(page).toHaveURL(/sort=rating/);

    // 4. Select DeFi from category dropdown
    const categorySelect = page.getByLabel('Filter by category');
    await categorySelect.selectOption('defi');
    await expect(page).toHaveURL(/category=defi/);

    // 5. Reset category back to all
    await categorySelect.selectOption('');

    // 6. Type search query in search input
    const searchInput = page.getByPlaceholder(/Search protocols by name/i);
    await searchInput.fill('Protocol');
    // Allow debounce
    await page.waitForTimeout(500);

    // 7. Click on the first visible protocol card
    const firstCardTitle = page.locator('article h3').first();
    await expect(firstCardTitle).toBeVisible({ timeout: 15000 });
    const targetTitle = await firstCardTitle.innerText();

    await firstCardTitle.click();

    // 8. Verify transition to protocol detail page
    await expect(page).toHaveURL(/\/protocols\/.+/);
    await expect(page.locator('h1')).toHaveText(targetTitle);

    // 9. Verify protocol metadata chips and sections
    await expect(page.locator('h2', { hasText: 'Discussion Threads' })).toBeVisible();
    await expect(page.locator('h2', { hasText: 'Peer Reviews' })).toBeVisible();
  });
});
