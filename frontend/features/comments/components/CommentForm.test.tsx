import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import { describe, it, expect, vi, beforeEach } from 'vitest';
import { CommentForm } from './CommentForm';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import * as AuthContextModule from '@/features/auth/AuthContext';
import * as CommentsApiModule from '../api';

vi.mock('../api');

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

describe('CommentForm Component', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('renders sign-in callout when user is not authenticated', () => {
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

    renderWithClient(<CommentForm threadId={10} />);

    expect(screen.getByText(/You must be signed in to join this protocol discussion/i)).toBeInTheDocument();
    expect(screen.getByRole('button', { name: /Sign In \/ Demo Accounts/i })).toBeInTheDocument();
  });

  it('renders form with author name when user is authenticated', () => {
    vi.spyOn(AuthContextModule, 'useAuth').mockReturnValue({
      user: {
        id: 2,
        name: 'Vitalik B.',
        email: 'vitalik@protocol.io',
        created_at: '2026-09-24T00:00:00Z',
      },
      token: 'mock-token',
      isLoading: false,
      login: vi.fn(),
      logout: vi.fn(),
      openAuthModal: vi.fn(),
      closeAuthModal: vi.fn(),
      isAuthModalOpen: false,
    });

    renderWithClient(<CommentForm threadId={10} />);

    expect(screen.getByText('Vitalik B.')).toBeInTheDocument();
    expect(screen.getByPlaceholderText(/Share your thoughts on this protocol discussion/i)).toBeInTheDocument();
    expect(screen.getByRole('button', { name: /Post Comment/i })).toBeInTheDocument();
  });

  it('submits comment with content and thread_id', async () => {
    const mockCreateComment = vi.spyOn(CommentsApiModule, 'createComment').mockResolvedValue({
      id: 99,
      content: 'New feedback on sequencer liveness',
      thread_id: 10,
      parent_id: null,
      author: {
        id: 2,
        name: 'Vitalik B.',
        email: 'vitalik@protocol.io',
        created_at: '2026-09-24T00:00:00Z',
      },
      created_at: '2026-09-24T00:00:00Z',
      updated_at: '2026-09-24T00:00:00Z',
    });

    vi.spyOn(AuthContextModule, 'useAuth').mockReturnValue({
      user: {
        id: 2,
        name: 'Vitalik B.',
        email: 'vitalik@protocol.io',
        created_at: '2026-09-24T00:00:00Z',
      },
      token: 'mock-token',
      isLoading: false,
      login: vi.fn(),
      logout: vi.fn(),
      openAuthModal: vi.fn(),
      closeAuthModal: vi.fn(),
      isAuthModalOpen: false,
    });

    const onSuccess = vi.fn();
    renderWithClient(<CommentForm threadId={10} onSuccess={onSuccess} />);

    const textarea = screen.getByPlaceholderText(/Share your thoughts/i);
    fireEvent.change(textarea, { target: { value: 'New feedback on sequencer liveness' } });

    const submitBtn = screen.getByRole('button', { name: /Post Comment/i });
    fireEvent.click(submitBtn);

    await waitFor(() => {
      expect(mockCreateComment).toHaveBeenCalledWith({
        thread_id: 10,
        content: 'New feedback on sequencer liveness',
        body: 'New feedback on sequencer liveness',
        parent_id: undefined,
      });
      expect(onSuccess).toHaveBeenCalled();
    });
  });
});
