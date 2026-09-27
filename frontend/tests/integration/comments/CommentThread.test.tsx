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
});
