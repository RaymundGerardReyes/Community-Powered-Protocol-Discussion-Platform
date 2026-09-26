import { test, expect } from '@playwright/test';

test.describe('Protocol Discussion & Voting Integration Flow', () => {
  test('full user journey: browse protocol, view thread, login via demo switcher, vote, and post comment', async ({ page }) => {
    // 1. Visit protocols listing
    await page.goto('/protocols');
    await expect(page.locator('h1')).toContainText('Protocols');

    // 2. Click on the first protocol card
    const firstProtocolLink = page.locator('article h3').first();
    await expect(firstProtocolLink).toBeVisible({ timeout: 15000 });
    await firstProtocolLink.click();

    // 3. Verify protocol detail page renders
    await expect(page).toHaveURL(/\/protocols\/.+/);
    await expect(page.locator('h2', { hasText: 'Discussion Threads' })).toBeVisible();

    // 4. Click the discussion thread link
    const threadLink = page.locator('section ul li h3').first();
    await expect(threadLink).toBeVisible();
    await threadLink.click();

    // 5. Verify thread detail page renders with content and discussion
    await expect(page).toHaveURL(/\/threads\/\d+/);
    await expect(page.locator('h2', { hasText: 'Discussion' })).toBeVisible();

    // 6. Before signing in, the comment box prompts to sign in
    await expect(
      page.getByText('You must be signed in to join this protocol discussion.')
    ).toBeVisible();

    // 7. Click Sign In in the header
    const signInBtn = page.getByRole('button', { name: 'Sign In', exact: true });
    await signInBtn.click();

    // 8. Select Vitalik B. from 1-Click Demo Accounts
    const vitalikBtn = page.getByRole('button', { name: /Vitalik B\./i });
    await expect(vitalikBtn).toBeVisible();
    await vitalikBtn.click();

    // 9. Verify user is now authenticated in UI
    await expect(page.getByText('Vitalik B.').first()).toBeVisible();
    await expect(page.getByRole('button', { name: 'Sign Out' })).toBeVisible();

    // 10. Verify comment form is now available for Vitalik B.
    await expect(page.getByText('Posting as Vitalik B.')).toBeVisible();
    const commentInput = page.getByPlaceholder(/Share your thoughts on this protocol discussion/i);
    await expect(commentInput).toBeVisible();

    // 11. Write and submit a new comment
    const uniqueCommentText = `E2E Automated Test Feedback: Sequencer threshold consensus verified at ${Date.now()}`;
    await commentInput.fill(uniqueCommentText);

    const postCommentBtn = page.getByRole('button', { name: 'Post Comment' });
    await postCommentBtn.click();

    // 12. Verify the posted comment appears in the discussion list
    await expect(page.getByText(uniqueCommentText)).toBeVisible({ timeout: 10000 });

    // 13. Upvote the thread and verify vote count increases
    const upvoteBtn = page.getByRole('button', { name: 'Upvote' }).first();
    await upvoteBtn.click();
    await expect(upvoteBtn).toHaveAttribute('aria-pressed', 'true');
  });
});
