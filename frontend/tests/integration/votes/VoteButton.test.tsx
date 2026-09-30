import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import { describe, it, expect, vi, beforeEach } from 'vitest';
import { VoteButton } from '@/features/votes/components/VoteButton';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import * as AuthContextModule from '@/features/auth/AuthContext';
import * as VotesApiModule from '@/features/votes/api';

vi.mock('@/features/votes/api', () => ({
  castVote: vi.fn(),
  fetchMyVotes: vi.fn().mockResolvedValue({
    protocol: {},
    thread: {},
    comment: {},
  }),
}));

function renderWithClient(ui: React.ReactElement) {
  const queryClient = new QueryClient({
    defaultOptions: { queries: { retry: false } },
  });
  return render(
    <QueryClientProvider client={queryClient}>
      {ui}
    </QueryClientProvider>
  );
}

describe('VoteButton Component', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('renders initial vote count and action buttons', () => {
    vi.spyOn(AuthContextModule, 'useAuth').mockReturnValue({
      user: null,
      token: null,
      isLoading: false,
      login: vi.fn(),
      logout: vi.fn(),
      openAuthModal: vi.fn(),
      closeAuthModal: vi.fn(),
      isAuthModalOpen: false,
    });

    renderWithClient(
      <VoteButton
        votableType="thread"
        votableId={5}
        currentVote={null}
        count={42}
      />
    );

    expect(screen.getByText('42')).toBeInTheDocument();
    expect(screen.getByRole('button', { name: /Upvote/i })).toBeInTheDocument();
    expect(screen.getByRole('button', { name: /Downvote/i })).toBeInTheDocument();
  });

  it('prompts unauthenticated user to sign in when clicking vote', () => {
    const openAuthModal = vi.fn();
    vi.spyOn(AuthContextModule, 'useAuth').mockReturnValue({
      user: null,
      token: null,
      isLoading: false,
      login: vi.fn(),
      logout: vi.fn(),
      openAuthModal,
      closeAuthModal: vi.fn(),
      isAuthModalOpen: false,
    });

    renderWithClient(
      <VoteButton
        votableType="thread"
        votableId={5}
        currentVote={null}
        count={42}
      />
    );

    const upvoteBtn = screen.getByRole('button', { name: /Upvote/i });
    fireEvent.click(upvoteBtn);

    expect(openAuthModal).toHaveBeenCalled();
    expect(screen.getByText(/Sign in to cast your vote/i)).toBeInTheDocument();
  });

  it('dispatches castVote when authenticated user clicks upvote', async () => {
    const mockCastVote = vi.spyOn(VotesApiModule, 'castVote').mockResolvedValue({
      action: 'created',
      current_vote: 1,
      votes_count: 43,
    });

    vi.spyOn(AuthContextModule, 'useAuth').mockReturnValue({
      user: {
        id: 1,
        name: 'Vitalik B.',
        email: 'vitalik@protocol.io',
        created_at: '2026-09-24T00:00:00Z',
      },
      token: 'valid-token',
      isLoading: false,
      login: vi.fn(),
      logout: vi.fn(),
      openAuthModal: vi.fn(),
      closeAuthModal: vi.fn(),
      isAuthModalOpen: false,
    });

    renderWithClient(
      <VoteButton
        votableType="thread"
        votableId={5}
        currentVote={null}
        count={42}
      />
    );

    const upvoteBtn = screen.getByRole('button', { name: /Upvote/i });
    fireEvent.click(upvoteBtn);

    await waitFor(() => {
      expect(mockCastVote).toHaveBeenCalledWith({
        votable_type: 'thread',
        votable_id: 5,
        value: 1,
      });
    });
  });
});
