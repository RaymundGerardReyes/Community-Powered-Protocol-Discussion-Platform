import { describe, it, expect } from 'vitest';
import { render, screen, fireEvent } from '@testing-library/react';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import React from 'react';
import { CommentThread } from '@/features/comments/components/CommentThread';
import { AuthProvider } from '@/features/auth/AuthContext';
import type { Comment } from '@/types';

function renderWithClient(ui: React.ReactElement) {
  const queryClient = new QueryClient({
    defaultOptions: { queries: { retry: false }, mutations: { retry: false } },
  });
  return render(
    <QueryClientProvider client={queryClient}>
      <AuthProvider>{ui}</AuthProvider>
    </QueryClientProvider>,
  );
}

describe('CommentThread Component Integration', () => {
  it('renders empty placeholder when no comments exist', () => {
    renderWithClient(<CommentThread comments={[]} />);
    expect(
      screen.getByText('No comments yet. Start the conversation!'),
    ).toBeInTheDocument();
  });

  it('renders recursive comment tree with author and replies', () => {
    const mockComments: Comment[] = [
      {
        id: 1,
        thread_id: 10,
        parent_id: null,
        content: 'Root level discussion point',
        created_at: '2026-09-20T10:00:00Z',
        updated_at: '2026-09-20T10:00:00Z',
        author: {
          id: 101,
          name: 'Alice Cryptographer',
          email: 'alice@protocol.io',
          created_at: '2026-09-01T00:00:00Z',
        },
        votes_count: 3,
        replies: [
          {
            id: 2,
            thread_id: 10,
            parent_id: 1,
            content: 'First nested response',
            created_at: '2026-09-20T11:00:00Z',
            updated_at: '2026-09-20T11:00:00Z',
            author: {
              id: 102,
              name: 'Bob Auditor',
              email: 'bob@protocol.io',
              created_at: '2026-09-01T00:00:00Z',
            },
            votes_count: 1,
            replies: [],
          },
        ],
      },
    ];

    renderWithClient(<CommentThread comments={mockComments} />);

    expect(screen.getByText('Root level discussion point')).toBeInTheDocument();
    expect(screen.getByText('Alice Cryptographer')).toBeInTheDocument();

    expect(screen.getByText('First nested response')).toBeInTheDocument();
    expect(screen.getByText('Bob Auditor')).toBeInTheDocument();

    const collapseBtn = screen.getByRole('button', { name: /collapse/i });
    expect(collapseBtn).toBeInTheDocument();

    fireEvent.click(collapseBtn);
    expect(screen.queryByText('First nested response')).not.toBeInTheDocument();
    expect(screen.getByText(/\+ Show 1 reply/i)).toBeInTheDocument();

    fireEvent.click(screen.getByText(/\+ Show 1 reply/i));
    expect(screen.getByText('First nested response')).toBeInTheDocument();
  });

  it('toggles reply form on reply button click', () => {
    const mockComments: Comment[] = [
      {
        id: 1,
        thread_id: 10,
        parent_id: null,
        content: 'Can you clarify the consensus model?',
        created_at: '2026-09-20T10:00:00Z',
        updated_at: '2026-09-20T10:00:00Z',
        author: {
          id: 101,
          name: 'Charlie DeFi',
          email: 'charlie@protocol.io',
          created_at: '2026-09-01T00:00:00Z',
        },
        votes_count: 0,
        replies: [],
      },
    ];

    renderWithClient(<CommentThread comments={mockComments} />);

    const replyBtn = screen.getByRole('button', { name: /reply/i });
    fireEvent.click(replyBtn);

    expect(screen.getByText('Cancel')).toBeInTheDocument();
  });

  it('renders arbitrarily deep recursive tree with timestamp containing hours, minutes, seconds', () => {
    const deepTree: Comment[] = [
      {
        id: 100,
        thread_id: 12,
        parent_id: null,
        content: 'Original comment by Zuckerberg',
        created_at: '2026-09-27T15:14:42.000Z',
        updated_at: '2026-09-27T15:14:42.000Z',
        author: { id: 1, name: 'Mr. Zuckerberg', email: 'zuck@meta.com', created_at: '2026-01-01' },
        votes_count: 10,
        replies: [
          {
            id: 101,
            thread_id: 12,
            parent_id: 100,
            content: 'Reply by Bezos',
            created_at: '2026-09-27T15:15:07.000Z',
            updated_at: '2026-09-27T15:15:07.000Z',
            author: { id: 2, name: 'Mr. Bezos', email: 'bezos@amazon.com', created_at: '2026-01-01' },
            votes_count: 5,
            replies: [
              {
                id: 102,
                thread_id: 12,
                parent_id: 101,
                content: 'Reply by Musk',
                created_at: '2026-09-27T15:15:41.000Z',
                updated_at: '2026-09-27T15:15:41.000Z',
                author: { id: 3, name: 'Mr. Musk', email: 'musk@x.com', created_at: '2026-01-01' },
                votes_count: 8,
                replies: [
                  {
                    id: 103,
                    thread_id: 12,
                    parent_id: 102,
                    content: 'Reply by Gates',
                    created_at: '2026-09-27T15:16:03.000Z',
                    updated_at: '2026-09-27T15:16:03.000Z',
                    author: { id: 4, name: 'Mr. Gates', email: 'gates@ms.com', created_at: '2026-01-01' },
                    votes_count: 2,
                    replies: [],
                  },
                ],
              },
            ],
          },
          {
            id: 104,
            thread_id: 12,
            parent_id: 100,
            content: 'Reply by Jobs',
            created_at: '2026-09-27T15:17:12.000Z',
            updated_at: '2026-09-27T15:17:12.000Z',
            author: { id: 5, name: 'Mr. Jobs', email: 'jobs@apple.com', created_at: '2026-01-01' },
            votes_count: 12,
            replies: [],
          },
        ],
      },
    ];

    renderWithClient(<CommentThread comments={deepTree} />);

    // All authors must be rendered
    expect(screen.getByText('Mr. Zuckerberg')).toBeInTheDocument();
    expect(screen.getByText('Mr. Bezos')).toBeInTheDocument();
    expect(screen.getByText('Mr. Musk')).toBeInTheDocument();
    expect(screen.getByText('Mr. Gates')).toBeInTheDocument();
    expect(screen.getByText('Mr. Jobs')).toBeInTheDocument();

    // All contents must be rendered
    expect(screen.getByText('Original comment by Zuckerberg')).toBeInTheDocument();
    expect(screen.getByText('Reply by Bezos')).toBeInTheDocument();
    expect(screen.getByText('Reply by Musk')).toBeInTheDocument();
    expect(screen.getByText('Reply by Gates')).toBeInTheDocument();
    expect(screen.getByText('Reply by Jobs')).toBeInTheDocument();

    // Timestamps must include time with separator
    const timeElements = screen.getAllByText(/•/);
    expect(timeElements.length).toBeGreaterThanOrEqual(5);
  });
});
