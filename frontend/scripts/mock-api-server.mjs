import http from 'http';

const MOCK_USER = {
  id: 1,
  name: 'Vitalik B.',
  email: 'vitalik@protocol.io',
  created_at: '2026-09-01T00:00:00Z',
};

const MOCK_PROTOCOL = {
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
  author: MOCK_USER,
  created_at: '2026-09-10T12:00:00Z',
  updated_at: '2026-09-10T12:00:00Z',
};

const MOCK_THREAD = {
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

const MOCK_REVIEWS = [
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

const server = http.createServer((req, res) => {
  const origin = req.headers.origin || 'http://localhost:3000';
  res.setHeader('Access-Control-Allow-Origin', origin);
  res.setHeader('Access-Control-Allow-Credentials', 'true');
  res.setHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
  res.setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, Accept');

  if (req.method === 'OPTIONS') {
    res.writeHead(204);
    res.end();
    return;
  }

  let body = '';
  req.on('data', (chunk) => {
    body += chunk;
  });

  req.on('end', () => {
    const url = new URL(req.url, `http://${req.headers.host}`);
    const pathname = url.pathname;

    res.setHeader('Content-Type', 'application/json');

    if (pathname === '/api/v1/auth/login') {
      res.writeHead(200);
      res.end(JSON.stringify({ user: MOCK_USER, access_token: 'mock-e2e-token' }));
      return;
    }

    if (pathname === '/api/v1/auth/me') {
      res.writeHead(200);
      res.end(JSON.stringify({ user: MOCK_USER }));
      return;
    }

    if (pathname === '/api/v1/auth/logout') {
      res.writeHead(200);
      res.end(JSON.stringify({ message: 'Logged out successfully' }));
      return;
    }

    if (pathname === '/api/v1/protocols') {
      res.writeHead(200);
      res.end(
        JSON.stringify({
          data: [MOCK_PROTOCOL],
          meta: { current_page: 1, last_page: 1, total: 1, per_page: 15 },
        }),
      );
      return;
    }

    if (pathname.startsWith('/api/v1/protocols/')) {
      const parts = pathname.split('/');
      const lastPart = parts[parts.length - 1];

      if (lastPart === 'threads') {
        res.writeHead(200);
        res.end(
          JSON.stringify({
            data: [MOCK_THREAD],
            meta: { current_page: 1, last_page: 1, total: 1, per_page: 10 },
          }),
        );
        return;
      }

      if (lastPart === 'reviews') {
        res.writeHead(200);
        res.end(
          JSON.stringify({
            data: MOCK_REVIEWS,
            meta: { current_page: 1, last_page: 1, total: 1, per_page: 10 },
          }),
        );
        return;
      }

      res.writeHead(200);
      res.end(JSON.stringify({ data: MOCK_PROTOCOL }));
      return;
    }

    if (pathname.startsWith('/api/v1/threads/')) {
      if (pathname.endsWith('/comments')) {
        const parsed = body ? JSON.parse(body) : {};
        const newComment = {
          id: 999,
          thread_id: 11,
          parent_id: parsed.parent_id ?? null,
          content: parsed.content ?? parsed.body,
          votes_count: 0,
          author: MOCK_USER,
          replies: [],
          created_at: new Date().toISOString(),
          updated_at: new Date().toISOString(),
        };
        res.writeHead(201);
        res.end(JSON.stringify({ data: newComment }));
        return;
      }

      res.writeHead(200);
      res.end(JSON.stringify({ data: MOCK_THREAD }));
      return;
    }

    if (pathname === '/api/v1/votes') {
      res.writeHead(200);
      res.end(
        JSON.stringify({
          data: {
            action: 'created',
            current_vote: 1,
            votes_count: 8,
          },
        }),
      );
      return;
    }

    res.writeHead(404);
    res.end(JSON.stringify({ message: 'Not found' }));
  });
});

const PORT = 8000;
server.listen(PORT, '127.0.0.1', () => {
  console.log(`Mock API server listening on http://127.0.0.1:${PORT}`);
});
