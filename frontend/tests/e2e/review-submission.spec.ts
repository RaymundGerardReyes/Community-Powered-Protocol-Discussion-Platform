import { test, expect } from '@playwright/test';
import { setupApiMocks } from './mock-data';

test.describe('Protocol Detail & Peer Review Verification Flow', () => {
  test('user views protocol details, inspects peer reviews, and authenticates', async ({
    page,
  }) => {
    await setupApiMocks(page);

    // 1. Navigate directly to protocol detail page
    await page.goto('/protocols/decentralized-zk-rollup-sequencing-protocol');

    // 2. Verify protocol header, badges, and metadata
    await expect(page.locator('h1')).toHaveText(
      'Decentralized ZK Rollup Sequencing Protocol',
    );
    await expect(page.getByText('published')).toBeVisible();
    await expect(page.getByText('layer2')).toBeVisible();
    await expect(page.getByText('v1.2.0')).toBeVisible();

    // 3. Verify Peer Reviews section
    const reviewsSection = page.locator('section', { hasText: 'Peer Reviews' });
    await expect(reviewsSection).toBeVisible();
    await expect(reviewsSection.getByText('Alice Cryptographer')).toBeVisible();
    await expect(
      page.getByText('Excellent security model and clear validator penalties.'),
    ).toBeVisible();

    // 4. Verify Discussion Threads section
    await expect(page.locator('h2', { hasText: 'Discussion Threads' })).toBeVisible();
    await expect(
      page.getByText('Sequencer Consensus and Batch Finality'),
    ).toBeVisible();

    // 5. Sign in as demo user
    const signInBtn = page.getByRole('button', { name: 'Sign In', exact: true });
    await signInBtn.click();

    const vitalikBtn = page.getByRole('button', { name: /Vitalik B\./i });
    await expect(vitalikBtn).toBeVisible();
    await vitalikBtn.click();

    // 6. Verify user is authenticated
    await expect(page.getByText('Vitalik B.').first()).toBeVisible();
    await expect(page.getByRole('button', { name: 'Sign Out' })).toBeVisible();
  });
});
