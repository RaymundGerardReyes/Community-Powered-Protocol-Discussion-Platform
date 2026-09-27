import type { Page } from '@playwright/test';

export const MOCK_PROTOCOL = {
  id: 1,
  slug: 'decentralized-zk-rollup-sequencing-protocol',
  title: 'Decentralized ZK Rollup Sequencing Protocol',
  description:
    'A robust, Byzantine-fault-tolerant specification for sequencing Layer 2 zero-knowledge rollup transactions.',
  category: 'layer2',
  version: '1.2.0',
  status: 'published',
  score: 120,
  votes_count: 12,
  average_rating: 4.8,
  reviews_count: 4,
  metadata: null,
  author: {
    id: 1,
    name: 'Vitalik B.',
    email: 'vitalik@protocol.io',
    created_at: '2026-09-01T00:00:00Z',
  },
  created_at: '2026-09-10T12:00:00Z',
  updated_at: '2026-09-10T12:00:00Z',
};

export const MOCK_THREAD = {
  id: 11,
  protocol_id: 1,
  title: 'Sequencer Consensus and Batch Finality',
  content: 'How should the protocol penalize malicious sequencers who withhold data?',
  body: 'How should the protocol penalize malicious sequencers who withhold data?',
  views_count: 42,
  votes_count: 7,
  comments_count: 1,
  author: {
    id: 101,
    name: 'Alice Cryptographer',
    email: 'alice@protocol.io',
    created_at: '2026-09-01T00:00:00Z',
  },
  created_at: '2026-09-12T14:00:00Z',
  updated_at: '2026-09-12T14:00:00Z',
  comments: [
    {
      id: 101,
      thread_id: 11,
      parent_id: null,
      content: 'Slashing 32 ETH staked on L1 provides adequate game-theoretic security.',
      votes_count: 3,
      author: {
        id: 102,
        name: 'Bob Auditor',
        email: 'bob@protocol.io',
        created_at: '2026-09-01T00:00:00Z',
      },
      replies: [],
      created_at: '2026-09-12T15:00:00Z',
      updated_at: '2026-09-12T15:00:00Z',
    },
  ],
};

export const MOCK_REVIEWS = [
  {
    id: 1,
    protocol_id: 1,
    rating: 5,
    feedback: 'Excellent security model and clear validator penalties.',
    created_at: '2026-09-13T10:00:00Z',
    updated_at: '2026-09-13T10:00:00Z',
    author: {
      id: 101,
      name: 'Alice Cryptographer',
      email: 'alice@protocol.io',
      created_at: '2026-09-01T00:00:00Z',
    },
  },
];

export async function setupApiMocks(page: Page) {
  await page.route('**/api/v1/protocols?*', async (route) => {
    await route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({
        data: [MOCK_PROTOCOL],
        meta: { current_page: 1, last_page: 1, total: 1, per_page: 15 },
      }),
    });
  });

  await page.route('**/api/v1/protocols', async (route) => {
    await route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({
        data: [MOCK_PROTOCOL],
        meta: { current_page: 1, last_page: 1, total: 1, per_page: 15 },
      }),
    });
  });

  await page.route('**/api/v1/protocols/*', async (route) => {
    const url = route.request().url();
    if (url.includes('/threads')) {
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          data: [MOCK_THREAD],
          meta: { current_page: 1, last_page: 1, total: 1 },
        }),
      });
    } else if (url.includes('/reviews')) {
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          data: MOCK_REVIEWS,
          meta: { current_page: 1, last_page: 1, total: 1 },
        }),
      });
    } else {
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({ data: MOCK_PROTOCOL }),
      });
    }
  });

  await page.route('**/api/v1/threads/*', async (route) => {
    const url = route.request().url();
    if (url.includes('/comments')) {
      const postData = route.request().postDataJSON?.() ?? {};
      const newComment = {
        id: 999,
        thread_id: 11,
        parent_id: postData.parent_id ?? null,
        content: postData.content ?? postData.body,
        votes_count: 0,
        author: {
          id: 1,
          name: 'Vitalik B.',
          email: 'vitalik@protocol.io',
          created_at: '2026-09-01T00:00:00Z',
        },
        replies: [],
        created_at: new Date().toISOString(),
        updated_at: new Date().toISOString(),
      };
      await route.fulfill({
        status: 201,
        contentType: 'application/json',
        body: JSON.stringify({ data: newComment }),
      });
    } else {
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({ data: MOCK_THREAD }),
      });
    }
  });

  await page.route('**/api/v1/auth/login', async (route) => {
    await route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({
        user: {
          id: 1,
          name: 'Vitalik B.',
          email: 'vitalik@protocol.io',
          created_at: '2026-09-01T00:00:00Z',
        },
        access_token: 'mock-e2e-token-xyz',
      }),
    });
  });

  await page.route('**/api/v1/votes', async (route) => {
    await route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({
        data: {
          action: 'created',
          current_vote: 1,
          votes_count: 8,
        },
      }),
    });
  });
}
