import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import { describe, it, expect, vi, beforeEach } from 'vitest';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import React from 'react';
import { ThreadDiscussion } from '@/features/comments/components/ThreadDiscussion';
import * as AuthContextModule from '@/features/auth/AuthContext';
import * as CommentsApiModule from '@/features/comments/api';
import type { Comment } from '@/types';

vi.mock('@/features/comments/api');

function renderWithClient(ui: React.ReactElement) {
  const queryClient = new QueryClient({
    defaultOptions: { queries: { retry: false }, mutations: { retry: false } },
  });
  return render(
    <QueryClientProvider client={queryClient}>
      {ui}
    </QueryClientProvider>,
  );
}

const mockAuthor = {
  id: 1,
  name: 'Protocol Admin',
  email: 'admin@protocol.io',
  created_at: '2026-09-01T00:00:00Z',
};

describe('ThreadDiscussion Component Integration', () => {
  beforeEach(() => {
    vi.clearAllMocks();

    vi.spyOn(AuthContextModule, 'useAuth').mockReturnValue({
      user: mockAuthor,
      token: 'mock-token',
      isLoading: false,
      login: vi.fn(),
      logout: vi.fn(),
      openAuthModal: vi.fn(),
      closeAuthModal: vi.fn(),
      isAuthModalOpen: false,
    });
  });

  it('renders initial comments and updates tree dynamically when a reply is posted without reload', async () => {
    const initialComments: Comment[] = [
      {
        id: 12,
        thread_id: 10,
        parent_id: null,
        content: 'Original comment on protocol gas architecture',
        author: mockAuthor,
        created_at: '2026-09-27T16:20:00.000Z',
        updated_at: '2026-09-27T16:20:00.000Z',
        votes_count: 0,
        replies: [],
      },
    ];

    const mockNewReply: Comment = {
      id: 25,
      thread_id: 10,
      parent_id: 12,
      content: 'hola mamaen',
      author: mockAuthor,
      created_at: '2026-09-27T16:27:33.000Z',
      updated_at: '2026-09-27T16:27:33.000Z',
      votes_count: 0,
      replies: [],
    };

    vi.spyOn(CommentsApiModule, 'createComment').mockResolvedValue(mockNewReply);
    vi.spyOn(CommentsApiModule, 'fetchComments').mockResolvedValue([
      {
        ...initialComments[0],
        replies: [mockNewReply],
      },
    ]);

    renderWithClient(
      <ThreadDiscussion
        threadId={10}
        initialComments={initialComments}
        initialCount={1}
      />,
    );

    // Initial state: parent comment visible, initial count is 1
    expect(screen.getByText('Original comment on protocol gas architecture')).toBeInTheDocument();
    expect(screen.getByText('1')).toBeInTheDocument();

    // Click "Reply" on the comment
    const replyBtn = screen.getByRole('button', { name: /reply/i });
    fireEvent.click(replyBtn);

    // Reply form appears
    const replyTextarea = screen.getByPlaceholderText(/Write your reply/i);
    expect(replyTextarea).toBeInTheDocument();

    // User types "hola mamaen" and posts reply
    fireEvent.change(replyTextarea, { target: { value: 'hola mamaen' } });
    const postReplyBtn = screen.getByRole('button', { name: /post reply/i });
    fireEvent.click(postReplyBtn);

    // Without any page reload, the reply appears right under the parent!
    await waitFor(() => {
      expect(screen.getByText('hola mamaen')).toBeInTheDocument();
      // Total count badge dynamically increments to 2
      expect(screen.getByText('2')).toBeInTheDocument();
    });

    // Form automatically closed
    expect(screen.queryByPlaceholderText(/Write your reply/i)).not.toBeInTheDocument();
  });
});
